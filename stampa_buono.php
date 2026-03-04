<?php
// stampa_buono.php

// Abilita la visualizzazione degli errori per il debugging (rimuovere in produzione)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connessione al database
$host = 'localhost';
$db   = 'gestionale_tsservice';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$buono = null;

// Verifica se è stato passato un ID buono valido
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $buono_id = (int)$_GET['id'];
    
    // Prepara e esegui la query per recuperare i dati del buono
    $stmt = $conn->prepare("SELECT * FROM buoni_regalo WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $buono_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $buono = $result->fetch_assoc();
        $stmt->close();
    }
}

// Se il buono non è stato trovato, reindirizza o mostra un errore
if (!$buono) {
    echo "Buono regalo non trovato o ID non valido.";
    // Potresti anche reindirizzare alla lista dei buoni:
    // header('Location: visualizza_buoni.php');
    // exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Stampa Buono Regalo - <?= htmlspecialchars($buono['nome'] ?? 'Errore') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
    <style>
        :root {
            --brand-color-start: #28a745;
            --brand-color-end: #218838;
            --text-light: #ffffff;
            --text-dark: #34495e;
            --bg-page: #f4f7f6;
            --shadow-color: rgba(0, 0, 0, 0.2);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            color: var(--text-dark);
            background-color: var(--bg-page);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .page-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .control-btn {
            background: var(--brand-color-start);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .control-btn.secondary {
            background: #6c757d;
        }
        .control-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .gift-card-print-container {
            width: 100%;
            max-width: 550px;
            aspect-ratio: 1.586; /* Proporzioni simili a una carta di credito */
            background: linear-gradient(135deg, var(--brand-color-start), var(--brand-color-end));
            color: var(--text-light);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 15px 30px var(--shadow-color);
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.3);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .card-chip {
            width: 50px;
            height: 40px;
            background: linear-gradient(135deg, #e0c580, #f8f0d3, #e0c580);
            border-radius: 6px;
            border: 1px solid rgba(0,0,0,0.1);
            position: absolute;
            top: 90px;
            left: 40px;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.2);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5em;
            font-weight: 800;
            text-shadow: 1px 1px 3px var(--shadow-color);
        }

        .title {
            font-size: 0.9em;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
        }

        .card-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .main-info {
            text-align: left;
        }

        .value {
            font-size: 3.5em;
            font-weight: 900;
            line-height: 1;
            text-shadow: 2px 2px 6px var(--shadow-color);
        }
        .code {
            font-size: 1.4em;
            font-weight: 600;
            letter-spacing: 1.5px;
            background-color: rgba(255, 255, 255, 0.15);
            padding: 5px 10px;
            border-radius: 8px;
            margin-top: 10px;
            display: inline-block;
        }
        
        .qr-code {
            background: white;
            padding: 10px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #qrcode canvas {
            width: 120px !important;
            height: 120px !important;
        }


        .card-footer {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px dashed rgba(255, 255, 255, 0.3);
        }
        
        .personal-details {
            display: flex;
            justify-content: space-between;
        }

        .info-block {
            font-size: 0.85em;
        }

        .info-block strong {
            font-size: 0.8em;
            font-weight: 500;
            opacity: 0.8;
            display: block;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .info-block span {
            font-weight: 600;
        }

        .expiry-details {
            font-size: 0.9em;
            text-align: center;
            margin-top: 5px;
        }

        .expiry-details strong {
             opacity: 0.8;
        }
        

        .instructions {
            font-size: 0.8em;
            text-align: center;
            opacity: 0.9;
            margin-top: 5px;
            font-weight: 500;
        }
        
        .terms {
            font-size: 0.7em;
            opacity: 0.7;
            line-height: 1.3;
            text-align: center;
            margin-top: 5px;
        }

        /* Stili per la stampa */
        @media print {
            body {
                margin: 0;
                padding: 0;
                background-color: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .page-controls {
                display: none !important;
            }
            .gift-card-print-container {
                width: 90%;
                max-width: 1000px;
                margin: 20mm auto;
                box-shadow: none;
                border: none;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <?php if ($buono): ?>
    <div class="page-controls">
        <a href="visualizza_buoni.php" class="control-btn secondary" role="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/></svg>
            Torna all'Elenco
        </a>
    </div>

    <div class="gift-card-print-container">
        <div class="card-chip"></div>
        <div class="card-header">
            <div class="logo">TS Service</div>
            <div class="title">Buono Regalo</div>
        </div>

        <div class="card-main">
            <div class="main-info">
                <div class="value"><?= number_format($buono['valore'], 2, ',', '.') ?> &euro;</div>
                <div class="code"><?= htmlspecialchars($buono['nome']) ?></div>
            </div>
            <div class="qr-code" id="qrcode" title="Codice: <?= htmlspecialchars($buono['nome']) ?>"></div>
        </div>
        
        <div class="card-footer">
            <div class="personal-details">
                <div class="info-block">
                    <strong>PER</strong>
                    <span><?= htmlspecialchars($buono['destinatario']) ?: 'Non specificato' ?></span>
                </div>
                <div class="info-block" style="text-align: right;">
                    <strong>DA PARTE DI</strong>
                    <span><?= !empty($buono['note']) ? htmlspecialchars($buono['note']) : 'Non specificato' ?></span>
                </div>
            </div>
            <div class="expiry-details">
                <strong>Scade il:</strong>
                <span><?= htmlspecialchars($buono['data_scadenza']) ?: 'Nessuna' ?></span>
            </div>
            <div class="instructions">
                Presenta questo buono in cassa per l'utilizzo.
            </div>
            <div class="terms">
                Questo buono non è rimborsabile e non può essere convertito in denaro.
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const code = "<?= htmlspecialchars($buono['nome']) ?>";
            new QRCode(document.getElementById("qrcode"), {
                text: code,
                width: 120,
                height: 120,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
            
            // Avvia la stampa automaticamente al caricamento della pagina
            window.print();

            // Dopo la stampa (o l'annullamento), torna alla pagina precedente
            window.onafterprint = function() {
                window.location.href = 'visualizza_buoni.php';
            };
        });
    </script>
    <?php else: ?>
        <p>Impossibile caricare il buono regalo per la stampa. L'ID fornito non è valido o il buono non esiste.</p>
        <a href="visualizza_buoni.php" class="control-btn secondary" role="button">← Torna all'Elenco Buoni</a>
    <?php endif; ?>
</body>
</html>

