<?php
// stampa_permuta.php - Design Moderno 2024
// Scheda di permuta formattata per la stampa con design professionale

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connessione al database
$host = 'localhost';
$dbname = 'gestionale_tsservice';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Errore connessione DB: " . $e->getMessage());
}

// Recupera l'ID della permuta
$id_permuta = isset($_GET['id_permuta']) ? intval($_GET['id_permuta']) : 0;

if ($id_permuta <= 0) {
    die('ID permuta mancante o non valido.');
}

// Query per recuperare i dati della permuta
$stmt = $pdo->prepare("SELECT * FROM permute_nuovo WHERE id = ?");
$stmt->execute([$id_permuta]);
$permuta = $stmt->fetch();

if (!$permuta) {
    die('Permuta non trovata.');
}

// --- Funzioni Helper ---

function formatDate($date) {
    if (!$date || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return '-';
    }
    if (strpos($date, ' ') !== false) {
        $date = explode(' ', $date)[0];
    }
    return date('d/m/Y', strtotime($date));
}

function formatPrice($price) {
    if ($price === null || $price === '' || !is_numeric($price)) {
        return '-';
    }
    return '€ ' . number_format(floatval($price), 2, ',', '.');
}

function escapeHtml($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function getStatusBadgeClass($status) {
    $status = strtolower($status ?? '');
    switch ($status) {
        case 'completata': return 'status-completata';
        case 'accettata': return 'status-accettata';
        case 'rifiutata': return 'status-rifiutata';
        case 'annullata': return 'status-annullata';
        default: return 'status-trattativa';
    }
}

function getStatusIcon($status) {
    $status = strtolower($status ?? '');
    switch ($status) {
        case 'completata': return '✅';
        case 'accettata': return '👍';
        case 'rifiutata': return '❌';
        case 'annullata': return '🚫';
        default: return '🔄';
    }
}

// Parsing test data dal campo test_ok
$parsedTests = [];
$test_ok_data = json_decode($permuta['test_ok'] ?? '', true);

if (json_last_error() === JSON_ERROR_NONE && is_array($test_ok_data)) {
    // Mapping nomi test per visualizzazione più leggibile
    $testLabels = [
        'display' => 'Display',
        'touch' => 'Touchscreen',
        'batteria' => 'Batteria',
        'cam_post' => 'Fotocamera Post.',
        'cam_ant' => 'Fotocamera Ant.',
        'audio' => 'Audio/Speaker',
        'mic' => 'Microfono',
        'wifi' => 'Wi-Fi',
        'bt' => 'Bluetooth',
        'ricarica' => 'Ricarica',
        'tasti' => 'Tasti Fisici',
        'sensori' => 'Sensori',
        'sblocco_bio' => 'Sblocco Biometrico',
        'reset_fabbrica' => 'Reset Fabbrica',
        'accounts' => 'Account',
        'altro' => 'Altro'
    ];
    
    foreach ($test_ok_data as $testKey => $testDetails) {
        if (!is_array($testDetails)) continue;
        
        $esito = $testDetails['esito'] ?? '';
        $note = $testDetails['note'] ?? '';
        
        // Determina se il test è OK
        $result_bool = (
            stripos($esito, 'Funzionante') !== false || 
            stripos($esito, 'OK') !== false || 
            stripos($esito, 'Ottima') !== false || 
            stripos($esito, 'Buona') !== false ||
            stripos($esito, 'Liberi') !== false ||
            stripos($esito, 'Si') !== false ||
            stripos($esito, 'Eseguito') !== false
        );
        
        $displayName = $testLabels[$testKey] ?? ucfirst($testKey);
        
        $parsedTests[$displayName] = [
            'result_bool' => $result_bool,
            'esito' => escapeHtml($esito),
            'item_note' => escapeHtml($note)
        ];
    }
}

// Calcoli finanziari
$prezzo_nuovo = floatval($permuta['prezzo_nuovo'] ?? 0);
$prezzo_permuta = floatval($permuta['prezzo_permuta'] ?? 0);
$costo_riparazione = floatval($permuta['costo_riparazione'] ?? 0);
$costo_accessori = floatval($permuta['costo_accessori'] ?? 0);
$costo_prodotto = floatval($permuta['costo_prodotto'] ?? 0);
$differenza = floatval($permuta['differenza'] ?? ($prezzo_nuovo - $prezzo_permuta));
$prezzo_vendita = floatval($permuta['prezzo_vendita'] ?? 0);

// Calcolo margine e valore netto
$conguaglio_cliente = $prezzo_nuovo - $prezzo_permuta;
$valore_netto_ricevuto = $prezzo_permuta - $costo_riparazione;
$margine_stimato = $conguaglio_cliente + $valore_netto_ricevuto - $costo_prodotto - $costo_accessori;

// Usa data_permuta se disponibile, altrimenti data
$data_permuta = $permuta['data_permuta'] ?? $permuta['data'] ?? $permuta['created_at'];
// Usa telefono_cliente se disponibile, altrimenti telefono
$telefono = $permuta['telefono_cliente'] ?? $permuta['telefono'] ?? 'N/D';

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheda Permuta #<?= escapeHtml($permuta['progressivo'] ?? $permuta['id']) ?> - TS Service</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-green: #28a745;
            --brand-green-dark: #1e7e34;
            --brand-green-light: #d4edda;
            --text-dark: #1a202c;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f1f5f9;
            color: var(--text-dark);
            line-height: 1.5;
            padding: 20px;
        }

        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* Header con Logo Grande */
        .document-header {
            background: linear-gradient(135deg, var(--brand-green) 0%, var(--brand-green-dark) 100%);
            padding: 30px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-section img {
            width: 180px;
            height: 70px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .company-info h1 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }

        .company-info p {
            font-size: 0.75rem;
            opacity: 0.9;
            line-height: 1.4;
        }

        .document-info {
            text-align: right;
        }

        .document-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.8;
            margin-bottom: 4px;
        }

        .document-number {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .document-date {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-top: 8px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
        }

        .status-trattativa { background: rgba(255,255,255,0.2); color: white; }
        .status-accettata { background: #fbbf24; color: #78350f; }
        .status-completata { background: #34d399; color: #064e3b; }
        .status-rifiutata { background: #f87171; color: #7f1d1d; }
        .status-annullata { background: #9ca3af; color: #1f2937; }

        /* Content */
        .document-content {
            padding: 30px 40px;
        }

        /* Section */
        .section {
            margin-bottom: 28px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--brand-green);
        }

        .section-icon {
            width: 32px;
            height: 32px;
            background: var(--brand-green-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .section-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--brand-green-dark);
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .info-grid.cols-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        .info-item {
            background: var(--bg-light);
            border-radius: 10px;
            padding: 14px 16px;
            border: 1px solid var(--border-color);
        }

        .info-item.full-width {
            grid-column: 1 / -1;
        }

        .info-item.highlight {
            background: linear-gradient(135deg, var(--brand-green) 0%, var(--brand-green-dark) 100%);
            color: white;
            border: none;
        }

        .info-item.highlight .info-label {
            color: rgba(255,255,255,0.8);
        }

        .info-item.highlight .info-value {
            color: white;
        }

        .info-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 4px;
            font-weight: 600;
        }

        .info-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .info-value.large {
            font-size: 1.3rem;
            font-weight: 800;
        }

        /* Two Column Layout */
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .column-box {
            background: var(--bg-light);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border-color);
        }

        .column-box.ceduto {
            border-left: 4px solid #17a2b8;
        }

        .column-box.ricevuto {
            border-left: 4px solid var(--brand-green);
        }

        .column-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .column-box.ceduto .column-title {
            color: #17a2b8;
        }

        .column-box.ricevuto .column-title {
            color: var(--brand-green-dark);
        }

        .column-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed var(--border-color);
        }

        .column-item:last-child {
            border-bottom: none;
        }

        .column-item-label {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .column-item-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Test Grid */
        .test-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .test-item {
            background: var(--bg-light);
            border-radius: 8px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--border-color);
            font-size: 0.75rem;
        }

        .test-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .test-icon.ok {
            background: #d1fae5;
            color: #059669;
        }

        .test-icon.ko {
            background: #fee2e2;
            color: #dc2626;
        }

        .test-name {
            font-weight: 600;
            color: var(--text-dark);
            line-height: 1.2;
        }

        .test-note {
            font-size: 0.65rem;
            color: var(--text-muted);
        }

        /* Financial Summary */
        .financial-summary {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            padding: 20px;
            border: 2px solid var(--brand-green);
        }

        .financial-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed var(--border-color);
        }

        .financial-row:last-child {
            border-bottom: none;
        }

        .financial-row.total {
            background: linear-gradient(135deg, var(--brand-green) 0%, var(--brand-green-dark) 100%);
            color: white;
            margin: 16px -20px -20px;
            padding: 18px 20px;
            border-radius: 0 0 10px 10px;
        }

        .financial-label {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .financial-value {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .financial-row.total .financial-label,
        .financial-row.total .financial-value {
            color: white;
        }

        .financial-row.total .financial-value {
            font-size: 1.4rem;
        }

        /* Disclaimer */
        .disclaimer {
            background: #fefce8;
            border: 1px solid #fde047;
            border-radius: 10px;
            padding: 16px 20px;
            margin-top: 24px;
        }

        .disclaimer-title {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #a16207;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .disclaimer-text {
            font-size: 0.7rem;
            color: #713f12;
            line-height: 1.6;
        }

        /* Signatures */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            padding-top: 24px;
            border-top: 2px dashed var(--border-color);
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-bottom: 2px solid var(--text-dark);
            margin-bottom: 8px;
            height: 50px;
        }

        .signature-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Print Button */
        .print-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: linear-gradient(135deg, var(--brand-green) 0%, var(--brand-green-dark) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            transition: all 0.3s ease;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: white;
            color: var(--text-dark);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-back:hover {
            border-color: var(--brand-green);
            color: var(--brand-green);
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .print-container {
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }

            .print-actions {
                display: none !important;
            }
            
            .print-hide {
                display: none !important;
            }

            .document-header {
                padding: 20px 30px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .logo-section img {
                width: 150px;
                height: 60px;
            }

            .document-content {
                padding: 20px 30px;
            }

            .section {
                margin-bottom: 20px;
            }

            .test-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .test-item {
                padding: 6px 8px;
                font-size: 0.65rem;
            }

            .disclaimer {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .financial-summary,
            .financial-row.total,
            .status-badge,
            .section-icon,
            .test-icon {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <!-- Print Actions -->
    <div class="print-actions">
        <a href="javascript:history.back()" class="btn-back">
            ← Indietro
        </a>
        <button onclick="window.print()" class="btn-print">
            🖨️ Stampa Scheda
        </button>
    </div>

    <div class="print-container">
        <!-- Header -->
        <div class="document-header">
            <div class="logo-section">
                <img src="images/LOGO PNG2.png" alt="TS Service Logo" onerror="this.style.display='none'">
                <div class="company-info">
                    <h1>TS SERVICE</h1>
                    <p>Contrada Castromurro, 217<br>
                    87021 Belvedere M.mo (CS)<br>
                    Tel: 342 033 0279<br>
                    info@tsservice.it</p>
                </div>
            </div>
            <div class="document-info">
                <div class="document-title">Scheda di Permuta</div>
                <div class="document-number">#<?= escapeHtml($permuta['progressivo'] ?? $permuta['id']) ?></div>
                <div class="document-date">📅 <?= formatDate($data_permuta) ?></div>
                <div class="status-badge <?= getStatusBadgeClass($permuta['status']) ?>">
                    <?= getStatusIcon($permuta['status']) ?> <?= escapeHtml($permuta['status'] ?? 'In Trattativa') ?>
                </div>
            </div>
        </div>

        <div class="document-content">
            <!-- Dati Cliente -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon">👤</div>
                    <div class="section-title">Dati Cliente</div>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nome Completo</div>
                        <div class="info-value"><?= escapeHtml($permuta['cliente'] ?? 'N/D') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Telefono</div>
                        <div class="info-value"><?= escapeHtml($telefono) ?></div>
                    </div>
                </div>
            </div>

            <!-- Prodotti Scambiati -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon">🔄</div>
                    <div class="section-title">Dettagli Scambio</div>
                </div>
                <div class="two-columns">
                    <!-- Prodotto Ceduto (Nuovo) -->
                    <div class="column-box ceduto">
                        <div class="column-title">📤 Prodotto Ceduto al Cliente</div>
                        <div class="column-item">
                            <span class="column-item-label">Modello</span>
                            <span class="column-item-value"><?= escapeHtml($permuta['modello_nuovo'] ?? 'N/D') ?></span>
                        </div>
                        <div class="column-item">
                            <span class="column-item-label">IMEI/Seriale</span>
                            <span class="column-item-value"><?= escapeHtml($permuta['imei_nuovo'] ?? 'N/D') ?></span>
                        </div>
                        <div class="column-item">
                            <span class="column-item-label">Valore</span>
                            <span class="column-item-value" style="color: #17a2b8; font-size: 1.1rem;"><?= formatPrice($prezzo_nuovo) ?></span>
                        </div>
                        <?php if (!empty($permuta['note_nuovo'])): ?>
                        <div class="column-item" style="flex-direction: column; align-items: flex-start;">
                            <span class="column-item-label">Note</span>
                            <span class="column-item-value" style="font-weight: 400; font-size: 0.8rem; margin-top: 4px;"><?= escapeHtml($permuta['note_nuovo']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Prodotto Ricevuto (Usato) -->
                    <div class="column-box ricevuto">
                        <div class="column-title">📥 Prodotto Ricevuto in Permuta</div>
                        <div class="column-item">
                            <span class="column-item-label">Modello</span>
                            <span class="column-item-value"><?= escapeHtml($permuta['modello_usato'] ?? 'N/D') ?></span>
                        </div>
                        <div class="column-item">
                            <span class="column-item-label">IMEI/Seriale</span>
                            <span class="column-item-value"><?= escapeHtml($permuta['imei_usato'] ?? 'N/D') ?></span>
                        </div>
                        <div class="column-item">
                            <span class="column-item-label">Valore Permuta</span>
                            <span class="column-item-value" style="color: var(--brand-green); font-size: 1.1rem;"><?= formatPrice($prezzo_permuta) ?></span>
                        </div>
                        <?php if (!empty($permuta['note_usato'])): ?>
                        <div class="column-item" style="flex-direction: column; align-items: flex-start;">
                            <span class="column-item-label">Note</span>
                            <span class="column-item-value" style="font-weight: 400; font-size: 0.8rem; margin-top: 4px;"><?= escapeHtml($permuta['note_usato']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Test Effettuati -->
            <?php if (!empty($parsedTests)): ?>
            <div class="section">
                <div class="section-header">
                    <div class="section-icon">🔍</div>
                    <div class="section-title">Valutazione Tecnica Dispositivo Ricevuto</div>
                </div>
                <div class="test-grid">
                    <?php foreach ($parsedTests as $testName => $testDetails): ?>
                    <div class="test-item">
                        <div class="test-icon <?= $testDetails['result_bool'] ? 'ok' : 'ko' ?>">
                            <?= $testDetails['result_bool'] ? '✓' : '✗' ?>
                        </div>
                        <div>
                            <div class="test-name"><?= $testName ?></div>
                            <?php if (!empty($testDetails['esito'])): ?>
                            <div class="test-note"><?= $testDetails['esito'] ?><?= !empty($testDetails['item_note']) ? ' - ' . $testDetails['item_note'] : '' ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Riepilogo Finanziario -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon">💰</div>
                    <div class="section-title">Riepilogo Economico</div>
                </div>
                
                <!-- Sezione Conguaglio Cliente -->
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">📋 Conguaglio Cliente</div>
                    <div class="financial-summary" style="margin-bottom: 0;">
                        <div class="financial-row">
                            <span class="financial-label">Valore Prodotto Ceduto</span>
                            <span class="financial-value"><?= formatPrice($prezzo_nuovo) ?></span>
                        </div>
                        <div class="financial-row">
                            <span class="financial-label">Valore Permuta Riconosciuto</span>
                            <span class="financial-value" style="color: var(--brand-green);">- <?= formatPrice($prezzo_permuta) ?></span>
                        </div>
                        <div class="financial-row total">
                            <span class="financial-label">💵 Conguaglio da Pagare</span>
                            <span class="financial-value"><?= formatPrice($differenza) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Sezione Analisi Costi e Margine (Solo uso interno) -->
                <div class="print-hide" style="margin-top: 20px; padding-top: 16px; border-top: 2px dashed var(--border-color);">
                    <div style="font-size: 0.75rem; font-weight: 600; color: #f59e0b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">📊 Analisi Costi e Margine (Uso Interno)</div>
                    <div class="financial-summary" style="background: #fffbeb; border-color: #fde047;">
                        <?php if ($costo_riparazione > 0): ?>
                        <div class="financial-row">
                            <span class="financial-label">🔧 Costi Ricondizionamento</span>
                            <span class="financial-value" style="color: #dc2626;"><?= formatPrice($costo_riparazione) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($costo_accessori > 0): ?>
                        <div class="financial-row">
                            <span class="financial-label">🎁 Costo Accessori</span>
                            <span class="financial-value" style="color: #dc2626;"><?= formatPrice($costo_accessori) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($costo_prodotto > 0): ?>
                        <div class="financial-row">
                            <span class="financial-label">📦 Costo Prodotto Ceduto</span>
                            <span class="financial-value" style="color: #dc2626;"><?= formatPrice($costo_prodotto) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="financial-row">
                            <span class="financial-label">💎 Valore Netto Ricevuto</span>
                            <span class="financial-value"><?= formatPrice($valore_netto_ricevuto) ?></span>
                        </div>
                        <div class="financial-row total" style="background: <?= $margine_stimato >= 0 ? 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)' : 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)' ?>;">
                            <span class="financial-label" style="color: white;">💹 Margine Stimato</span>
                            <span class="financial-value" style="color: white;"><?= formatPrice($margine_stimato) ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if ($prezzo_vendita > 0): ?>
                <div class="info-grid" style="margin-top: 16px;">
                    <div class="info-item highlight">
                        <div class="info-label">Prezzo Vendita Finale</div>
                        <div class="info-value large"><?= formatPrice($prezzo_vendita) ?></div>
                    </div>
                    <?php if (!empty($permuta['data_vendita'])): ?>
                    <div class="info-item">
                        <div class="info-label">Data Vendita</div>
                        <div class="info-value"><?= formatDate($permuta['data_vendita']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Note Generali -->
            <?php if (!empty($permuta['note_generali'])): ?>
            <div class="section">
                <div class="section-header">
                    <div class="section-icon">📝</div>
                    <div class="section-title">Note Generali</div>
                </div>
                <div class="info-item full-width">
                    <div class="info-value" style="font-weight: 400; white-space: pre-wrap;"><?= escapeHtml($permuta['note_generali']) ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Foto Allegati -->
            <?php 
            $foto_ceduto = json_decode($permuta['foto_ceduto_paths'] ?? '[]', true) ?: [];
            $foto_ricevuto = json_decode($permuta['foto_ricevuto_paths'] ?? '[]', true) ?: [];
            if (!empty($foto_ceduto) || !empty($foto_ricevuto)): 
            ?>
            <div class="section print-hide">
                <div class="section-header">
                    <div class="section-icon">📷</div>
                    <div class="section-title">Foto Allegati</div>
                </div>
                <div class="two-columns">
                    <?php if (!empty($foto_ceduto)): ?>
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 8px;">Foto Prodotto Ceduto</div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <?php foreach ($foto_ceduto as $foto): ?>
                            <img src="<?= escapeHtml($foto) ?>" alt="Foto Ceduto" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color);" onerror="this.style.display='none'">
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($foto_ricevuto)): ?>
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 8px;">Foto Prodotto Ricevuto</div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <?php foreach ($foto_ricevuto as $foto): ?>
                            <img src="<?= escapeHtml($foto) ?>" alt="Foto Ricevuto" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color);" onerror="this.style.display='none'">
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Disclaimer -->
            <div class="disclaimer">
                <div class="disclaimer-title">⚠️ Condizioni e Consenso</div>
                <div class="disclaimer-text">
                    Il cliente dichiara di aver preso visione e accettato le condizioni generali esposte nel punto vendita. 
                    I dispositivi consegnati in permuta vengono valutati al momento della consegna; eventuali difetti non dichiarati 
                    potrebbero comportare una revisione del valore riconosciuto. Il cliente acconsente al trattamento dei dati 
                    personali ai sensi del GDPR (Reg. UE 2016/679). Qualsiasi reclamo dovrà essere presentato entro 7 giorni 
                    lavorativi dalla data dell'operazione.
                </div>
            </div>

            <!-- Firme -->
            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Firma Cliente</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Firma Operatore</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Auto-print quando la pagina è caricata
            // window.print();
        };
    </script>
</body>
</html>
