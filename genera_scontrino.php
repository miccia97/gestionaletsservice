<?php
// Impostazioni per visualizzare gli errori (utile in fase di sviluppo, disabilita in produzione)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Avvia la sessione PHP se non è già attiva
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclusione del file di connessione al database
// Assicurati che 'db.php' contenga la connessione $conn e la selezione del database
include 'db.php';

// Seleziona il database dopo aver stabilito la connessione (se non fatto in db.php)
$conn->select_db('gestionale_tsservice'); // <--- ASSICURATI CHE IL NOME DEL DATABASE SIA CORRETTO

$id_vendita = $_GET['id_vendita'] ?? 0; // Recupera l'ID della vendita dall'URL

// Array per contenere i dati dello scontrino
$dati_scontrino = [
    'info_negozio' => [
        'nome' => 'TS SERVICE',
        'slogan' => 'Il Tuo Alleato Digitale Sempre Al Passo Con Te!',
        'logo' => 'images/logo.png', // Percorso del logo
        'indirizzo' => 'Ctr Castromurro, 217',
        'citta' => 'Belvedere Marittimo, CS',
        'cap' => '87021',
        'piva' => 'IT03949550788',
        'telefono' => '+39 342 033 0279',
        'email' => 'info@tsservice.it',
        'sito_web' => 'www.tsservice.it'
    ],
    'data_ora' => date('d/m/Y H:i:s'),
    'id_vendita' => $id_vendita,
    'cliente' => 'Cliente Sconosciuto',
    'articoli' => [],
    'totali' => [
        'totale_vendita' => 0,
        'imponibile' => 0,
        'iva_percentuale' => 22, // Assumiamo IVA 22% fissa per lo scontrino
        'pagamento1_importo' => 0,
        'pagamento1_metodo' => '',
        'pagamento2_importo' => 0,
        'pagamento2_metodo' => '',
        'residuo' => 0
    ],
    'messaggio_footer' => 'Grazie per il tuo acquisto e torna a trovarci!'
];

if ($id_vendita > 0) {
    // Recupera i dati della vendita principale
    $stmt_vendita = $conn->prepare("SELECT nome_cliente, data_vendita, totale, pagamento1, pagamento2, residuo FROM vendite WHERE id = ?");
    if ($stmt_vendita) {
        $stmt_vendita->bind_param("i", $id_vendita);
        $stmt_vendita->execute();
        $result_vendita = $stmt_vendita->get_result();
        if ($vendita = $result_vendita->fetch_assoc()) {
            $dati_scontrino['cliente'] = htmlspecialchars($vendita['nome_cliente']);
            $dati_scontrino['data_ora'] = date('d/m/Y H:i:s', strtotime($vendita['data_vendita']));
            $dati_scontrino['totali']['totale_vendita'] = floatval($vendita['totale']);
            $dati_scontrino['totali']['pagamento1_importo'] = floatval($vendita['pagamento1']);
            $dati_scontrino['totali']['pagamento2_importo'] = floatval($vendita['pagamento2']);
            $dati_scontrino['totali']['residuo'] = floatval($vendita['residuo']);

            // Calcola l'imponibile per lo scontrino (IVA 22%)
            if ($dati_scontrino['totali']['iva_percentuale'] > 0) {
                 $dati_scontrino['totali']['imponibile'] = $dati_scontrino['totali']['totale_vendita'] / (1 + ($dati_scontrino['totali']['iva_percentuale'] / 100));
            } else {
                 $dati_scontrino['totali']['imponibile'] = $dati_scontrino['totali']['totale_vendita'];
            }

            // Nota: metodo di pagamento1 e pagamento2 non sono nella tua tabella `vendite` fornita.
            // Se li vuoi sullo scontrino, dovresti aggiungerli alla tabella `vendite`
            // e recuperarli qui. Per ora, saranno vuoti.
            $dati_scontrino['totali']['pagamento1_metodo'] = '';
            $dati_scontrino['totali']['pagamento2_metodo'] = '';
        }
        $stmt_vendita->close();
    } else {
        error_log("Errore prepare vendita: " . $conn->error);
    }

    // Recupera i dettagli degli articoli della vendita
    // NOTA BENE: Utilizzo 'nome' come nome della colonna, non 'nome_prodotto'.
    $stmt_dettagli = $conn->prepare("SELECT nome, quantita, prezzo_unitario, prezzo_scontato FROM vendite_dettagli WHERE id_vendita = ?");
    if ($stmt_dettagli) {
        $stmt_dettagli->bind_param("i", $id_vendita);
        $stmt_dettagli->execute();
        $result_dettagli = $stmt_dettagli->get_result();
        while ($item = $result_dettagli->fetch_assoc()) {
            $prezzo_applicato = (floatval($item['prezzo_scontato']) > 0 && floatval($item['prezzo_scontato']) < floatval($item['prezzo_unitario'])) ? floatval($item['prezzo_scontato']) : floatval($item['prezzo_unitario']);
            $dati_scontrino['articoli'][] = [
                'nome' => htmlspecialchars($item['nome']),
                'quantita' => intval($item['quantita']),
                'prezzo_unitario' => floatval($item['prezzo_unitario']),
                'prezzo_scontato' => floatval($item['prezzo_scontato']),
                'prezzo_applicato' => $prezzo_applicato,
                'subtotale' => $prezzo_applicato * intval($item['quantita'])
            ];
        }
        $stmt_dettagli->close();
    } else {
        error_log("Errore prepare dettagli vendita: " . $conn->error);
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Scontrino - Vendita #<?= htmlspecialchars($id_vendita) ?></title>
    <style>
        /* Stili generali per il corpo dello scontrino */
        body {
            font-family: 'Segoe UI', 'Roboto', 'Arial', sans-serif; /* Font più moderno */
            margin: 0;
            padding: 15px; /* Spazio intorno al contenuto */
            font-size: 12px; /* Dimensione base del font */
            line-height: 1.4;
            color: #000;
            background-color: #fff;
            width: 80mm; /* Larghezza tipica di uno scontrino termico */
            max-width: 80mm; /* Impedisce che si espanda oltre */
            box-sizing: border-box; /* Include padding e border nella larghezza */
            /* Regolazioni per la stampa */
            -webkit-print-color-adjust: exact !important; /* Per Safari/Chrome */
            print-color-adjust: exact !important; /* Standard */
        }

        /* Regole specifiche per la stampa */
        @media print {
            @page {
                size: auto; /* Adatta la dimensione della pagina al contenuto */
                margin: 0; /* Rimuovi tutti i margini del foglio */
            }
            body {
                width: 80mm; /* Forza la larghezza per la stampa */
                padding: 0; /* Rimuovi padding in stampa per massimizzare l'area utile */
                margin: 0;
            }
        }

        /* Contenitore principale dello scontrino */
        .scontrino-container {
            width: 100%;
            text-align: center;
            padding: 5px; /* Piccolo padding interno per il contenuto */
            box-sizing: border-box;
        }

        /* Sezione Header del negozio */
        .header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee; /* Linea più moderna */
        }
        .header img.logo {
            max-width: 60px; /* Dimensione del logo */
            height: auto;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 1.6em; /* Nome negozio più grande */
            font-weight: 700; /* Più grassetto */
            margin: 5px 0 0 0;
            text-transform: uppercase;
            color: #333; /* Colore più soft */
        }
        .header h2 {
            font-size: 0.9em; /* Slogan */
            margin: 0;
            color: #777; /* Slogan più tenue */
            font-style: italic;
        }
        .info-negozio p {
            margin: 1px 0;
            font-size: 0.85em; /* Testo info più piccolo */
            color: #555;
        }

        /* Linea divisoria */
        .divider {
            border-top: 1px dashed #ccc; /* Linea più leggera */
            margin: 15px 0;
        }

        /* Dettagli della transazione */
        .transaction-details {
            text-align: left;
            margin-bottom: 15px;
        }
        .transaction-details p {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
            font-size: 0.9em;
        }
        .transaction-details p span:first-child {
            font-weight: 600; /* Etichetta più grassa */
            color: #333;
        }
        .transaction-details p span:last-child {
            color: #555;
        }

        /* Lista degli articoli */
        .articoli-lista {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            text-align: left;
        }
        .articoli-lista th, .articoli-lista td {
            padding: 7px 0; /* Padding aumentato per leggibilità */
            vertical-align: top;
            border-bottom: 1px dotted #ddd; /* Linea più discreta */
        }
        .articoli-lista thead th {
            font-weight: 700;
            font-size: 0.9em;
            text-transform: uppercase;
            color: #333;
        }
        .articoli-lista tbody tr:last-child td {
            border-bottom: none; /* Nessun bordo sull'ultima riga */
        }

        .articoli-lista td {
            font-size: 0.9em;
            color: #333;
        }

        .articoli-lista .qty-col { width: 15%; text-align: center; }
        .articoli-lista .name-col { width: 55%; font-weight: 600; }
        .articoli-lista .price-col { width: 30%; text-align: right; font-weight: 600;}

        .articoli-lista .sconto-info {
            font-size: 0.75em;
            color: #777;
            padding-left: 15%; /* Allinea con la descrizione */
            font-style: italic;
        }

        /* Sezione Totali e Pagamenti */
        .totali {
            text-align: right;
            margin-top: 20px;
        }
        .totali p {
            display: flex;
            justify-content: space-between;
            margin: 7px 0; /* Spazio aumentato tra i totali */
            font-size: 1em;
        }
        .totali .label {
            text-align: left;
            flex-grow: 1;
            font-weight: 500;
            color: #333;
        }
        .totali .value {
            text-align: right;
            font-weight: 700; /* Valori più grassi */
            color: #222;
        }

        .totali .grand-total {
            font-size: 1.6em; /* Totale finale più grande */
            font-weight: 800; /* Extra grassetto */
            border-top: 2px solid #000;
            padding-top: 10px;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            color: #000;
        }
        .totali .payment-row, .totali .residuo-row, .totali .resto-row {
            font-size: 1.0em;
        }
        .totali .residuo-row .value {
            color: #dc3545; /* Rosso acceso per residuo da pagare */
        }
        .totali .resto-row .value {
            color: #28a745; /* Verde acceso per resto da dare */
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #ccc;
            font-size: 0.85em;
            font-style: italic;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="scontrino-container">
        <div class="header">
            <?php if (!empty($dati_scontrino['info_negozio']['logo'])): ?>
                <img src="<?= htmlspecialchars($dati_scontrino['info_negozio']['logo']) ?>" alt="Logo <?= htmlspecialchars($dati_scontrino['info_negozio']['nome']) ?>" class="logo">
            <?php endif; ?>
            <h1><?= htmlspecialchars($dati_scontrino['info_negozio']['nome']) ?></h1>
            <h2><?= htmlspecialchars($dati_scontrino['info_negozio']['slogan']) ?></h2>
        </div>

        <div class="info-negozio">
            <p><?= htmlspecialchars($dati_scontrino['info_negozio']['indirizzo']) ?>, <?= htmlspecialchars($dati_scontrino['info_negozio']['cap']) ?> <?= htmlspecialchars($dati_scontrino['info_negozio']['citta']) ?></p>
            <p>P.IVA: <?= htmlspecialchars($dati_scontrino['info_negozio']['piva']) ?></p>
            <p>Tel: <?= htmlspecialchars($dati_scontrino['info_negozio']['telefono']) ?></p>
            <p>Email: <?= htmlspecialchars($dati_scontrino['info_negozio']['email']) ?></p>
            <p>Sito: <?= htmlspecialchars($dati_scontrino['info_negozio']['sito_web']) ?></p>
        </div>

        <div class="divider"></div>

        <div class="transaction-details">
            <p><span>Data/Ora:</span> <span><?= htmlspecialchars($dati_scontrino['data_ora']) ?></span></p>
            <p><span>Scontrino ID:</span> <span>#<?= htmlspecialchars($dati_scontrino['id_vendita']) ?></span></p>
            <p><span>Cliente:</span> <span><?= htmlspecialchars($dati_scontrino['cliente']) ?></span></p>
        </div>

        <div class="divider"></div>

        <table class="articoli-lista">
            <thead>
                <tr>
                    <th class="qty-col">QTÀ</th>
                    <th class="name-col">DESCRIZIONE</th>
                    <th class="price-col">TOTALE</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dati_scontrino['articoli'] as $articolo): ?>
                    <tr>
                        <td class="qty-col"><?= htmlspecialchars($articolo['quantita']) ?>x</td>
                        <td class="name-col"><?= htmlspecialchars($articolo['nome']) ?></td>
                        <td class="price-col">€ <?= number_format($articolo['subtotale'], 2, ',', '.') ?></td>
                    </tr>
                    <?php if ($articolo['prezzo_scontato'] > 0 && $articolo['prezzo_scontato'] < $articolo['prezzo_unitario']): ?>
                        <tr>
                            <td></td>
                            <td colspan="2" class="sconto-info">
                                (Prezzo orig. € <?= number_format($articolo['prezzo_unitario'], 2, ',', '.') ?>)
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="divider"></div>

        <div class="totali">
            <p>
                <span class="label">Imponibile:</span>
                <span class="value">€ <?= number_format($dati_scontrino['totali']['imponibile'], 2, ',', '.') ?></span>
            </p>
            <p>
                <span class="label">IVA (<?= htmlspecialchars($dati_scontrino['totali']['iva_percentuale']) ?>%):</span>
                <span class="value">€ <?= number_format($dati_scontrino['totali']['totale_vendita'] - $dati_scontrino['totali']['imponibile'], 2, ',', '.') ?></span>
            </p>

            <p class="grand-total">
                <span class="label">TOTALE:</span>
                <span class="value">€ <?= number_format($dati_scontrino['totali']['totale_vendita'], 2, ',', '.') ?></span>
            </p>

            <?php if ($dati_scontrino['totali']['pagamento1_importo'] > 0): ?>
                <p class="payment-row">
                    <span class="label">Pagato (P1 - <?= htmlspecialchars($dati_scontrino['totali']['pagamento1_metodo'] ?: 'Contanti') ?>):</span>
                    <span class="value">€ <?= number_format($dati_scontrino['totali']['pagamento1_importo'], 2, ',', '.') ?></span>
                </p>
            <?php endif; ?>
            <?php if ($dati_scontrino['totali']['pagamento2_importo'] > 0): ?>
                <p class="payment-row">
                    <span class="label">Pagato (P2 - <?= htmlspecialchars($dati_scontrino['totali']['pagamento2_metodo'] ?: 'Carta') ?>):</span>
                    <span class="value">€ <?= number_format($dati_scontrino['totali']['pagamento2_importo'], 2, ',', '.') ?></span>
                </p>
            <?php endif; ?>

            <?php
            $resto = -$dati_scontrino['totali']['residuo']; // Il residuo negativo è il resto
            if ($resto > 0.01) { // Considera un piccolo margine per errori di float
                echo '<p class="resto-row"><span class="label">RESTO:</span> <span class="value">€ ' . number_format($resto, 2, ',', '.') . '</span></p>';
            } elseif ($dati_scontrino['totali']['residuo'] > 0.01) { // Se c'è un residuo positivo
                 echo '<p class="residuo-row"><span class="label">RESIDUO DA PAGARE:</span> <span class="value">€ ' . number_format($dati_scontrino['totali']['residuo'], 2, ',', '.') . '</span></p>';
            }
            ?>
        </div>

        <div class="divider"></div>

        <div class="footer">
            <p><?= htmlspecialchars($dati_scontrino['messaggio_footer']) ?></p>
        </div>
    </div>
</body>
</html>
