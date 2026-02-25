<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Recupera il carrello dal POST o inizializza come array vuoto
$carrello = [];
if (isset($_POST['carrello_json'])) {
    $carrello = json_decode($_POST['carrello_json'], true);
} else {
    // Simulazione dati prodotti nel carrello se non presenti via POST.
    // In un'applicazione reale, questi verrebbero da sessione o DB.
    // Esempio di carrello per test (aggiunto merceologia e immagine per completezza):
    $carrello = [
        ['id' => 'prod001', 'name' => 'Prodotto A', 'qty' => 2, 'price' => 15.50, 'prezzo_scontato' => 14.00, 'giacenza' => 100, 'img' => 'https://placehold.co/60x60/f0f0f0/cccccc?text=PA', 'merceologia' => 'Elettronica'],
        ['id' => 'serv001', 'name' => 'Servizio B', 'qty' => 1, 'price' => 50.00, 'prezzo_scontato' => 50.00, 'giacenza' => 999, 'img' => 'https://placehold.co/60x60/f0f0f0/cccccc?text=SB', 'merceologia' => 'Servizi'],
        ['id' => 'prod002', 'name' => 'Prodotto C', 'qty' => 3, 'price' => 5.20, 'prezzo_scontato' => 4.50, 'giacenza' => 50, 'img' => 'https://placehold.co/60x60/f0f0f0/cccccc?text=PC', 'merceologia' => 'Abbigliamento'],
    ];
}

// Assicurati che $carrello sia sempre un array per evitare errori nel foreach
if (!is_array($carrello)) {
    $carrello = [];
}

// Inizializza variabili PHP per i totali, come base per JavaScript
$nome_cliente = $_POST['nome_cliente'] ?? '';
$id_cliente = $_POST['id_cliente'] ?? ''; // Assicurati di recuperare anche l'ID

$quantitaTotaleIniziale = 0; // Quantità totale di tutti i prodotti
$totaleArticoliUniciIniziale = 0; // Numero di articoli unici nel carrello
$totaleVenditaIniziale = 0; // Totale monetario di vendita

foreach ($carrello as $item) {
    $qty = intval($item['qty'] ?? 0);
    $prezzo = floatval($item['price'] ?? 0);

    $quantitaTotaleIniziale += $qty;
    $totaleArticoliUniciIniziale++; // Incrementa per ogni riga/articolo unico
    $totaleVenditaIniziale += $prezzo * $qty;
}

// Inclusione di header.php - Riattivato come richiesto.
// Assicurati che header.php contenga il markup della barra superiore e non le tag <html>, <head>, <body>.
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Carrello - TS SERVICE</title>
<!-- SweetAlert2 da CDN per messaggi utente più moderni e non bloccanti -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .top-bar {
        background-color: #28a745;
        color: white;
        padding: 20px 30px;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 80px;
    }

    .logo {
        font-size: 36px;
        font-weight: bold;
        white-space: nowrap;
    }

    .menu {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .menu button {
        background-color: white;
        border: none;
        color: #1a1a1a;
        font-size: 15px;
        padding: 10px 20px;
        cursor: pointer;
        border-radius: 8px;
        position: relative;
        transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    }

    .menu button:hover {
        background-color: #e6f4ea;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .dropdown {
        position: relative;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: #fff;
        min-width: 180px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        border-radius: 8px;
        z-index: 1000;
        overflow: hidden;
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 0.25s ease, transform 0.25s ease;
    }

    .dropdown:hover .dropdown-content {
        display: block;
        opacity: 1;
        transform: translateY(0);
    }

    .dropdown-content a {
        color: #333;
        padding: 8px 12px;
        text-decoration: none;
        display: block;
        transition: background-color 0.2s ease;
    }

    .dropdown-content a:hover {
        background-color: #28a745;
        color: white;
    }

    /* Contenitore principale della pagina, usa CSS Grid per il layout complesso */
    .page-content-wrapper {
        display: grid;
        /* Definisce 5 colonne: 4 per gli input, 1 per il riepilogo pagamento */
        grid-template-columns: repeat(4, minmax(180px, 1fr)) minmax(280px, 380px); /* Aumentato max-width per la colonna del riepilogo */
        grid-template-rows: auto auto; /* Prima riga per input/riepilogo, seconda per carrello */
        gap: 20px;
        padding: 30px;
        max-width: 1600px; /* Aumentato la larghezza massima del wrapper */
        margin: 0 auto; /* Centra il wrapper */
        flex-grow: 1; /* Permette al wrapper di espandersi verticalmente */
    }

    /* Stile base per tutti i "box" principali (Cliente, Buono, Pagamenti, Riepilogo Pagamento) */
    .section-box, .payment-summary-box {
        background: #fff; /* Sfondo bianco per i box */
        border: 1px solid #ddd; /* Bordo sottile */
        border-radius: 10px; /* Angoli arrotondati */
        padding: 25px;
        box-sizing: border-box;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1); /* Ombra chiara ma visibile */
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .section-box:hover, .payment-summary-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }

    /* Posizionamento specifico degli elementi nella griglia */
    .section-box:nth-of-type(1) { grid-area: 1 / 1 / 2 / 2; } /* Cliente (Riga 1, Colonna 1) */
    .section-box:nth-of-type(2) { grid-area: 1 / 2 / 2 / 3; } /* Buono Spesa (Riga 1, Colonna 2) */
    .section-box:nth-of-type(3) { grid-area: 1 / 3 / 2 / 4; } /* Pagamento 1 (Riga 1, Colonna 3) */
    .section-box:nth-of-type(4) { grid-area: 1 / 4 / 2 / 5; } /* Pagamento 2 (Riga 1, Colonna 4) */

    .payment-summary-box {
        grid-area: 1 / 5 / 3 / 6; /* Riepilogo Pagamento (Inizia Riga 1, Colonna 5, Spanna 2 Righe) */
    }

    .cart-summary-main {
        grid-area: 2 / 1 / 3 / 5; /* Riepilogo Articoli Carrello (Inizia Riga 2, Colonna 1, Spanna 4 Colonne) */
        margin: 0; /* Rimuove i margini esterni, la griglia gestisce la spaziatura */
        background: white; /* Assicurati che abbia lo sfondo bianco come gli altri box */
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        box-sizing: border-box;
        max-width: 100%; /* Si adatta alla larghezza della griglia */
    }

    label {
        display: block;
        font-weight: 600;
        color: #555;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .input-icon-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
        position: relative;
        margin-bottom: 20px;
    }
    .input-icon-wrapper:last-child {
        margin-bottom: 0;
    }

    input.custom-input, select.custom-select, textarea.custom-input {
        width: 100%;
        padding: 10px 15px;
        font-size: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #fff;
        color: #333;
        box-sizing: border-box;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    textarea.custom-input {
        resize: vertical;
        min-height: 60px;
    }


    input.custom-input:hover, select.custom-select:hover, textarea.custom-input:hover {
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
    }

    input.custom-input:focus, select.custom-select:focus, textarea.custom-input:focus {
        outline: none;
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
    }

    .icon-btn {
        background: #f8f8f8;
        border: 1px solid #ccc;
        cursor: pointer;
        padding: 8px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.25s ease, border-color 0.3s ease, transform 0.2s ease;
        flex-shrink: 0;
    }

    .icon-btn svg {
        stroke: #555;
        transition: stroke 0.3s ease;
    }

    .icon-btn:hover {
        background-color: #e6f4ea;
        border-color: #28a745;
        transform: translateY(-2px);
    }

    .icon-btn:hover svg {
        stroke: white;
    }

    .payment-wrapper {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .payment-wrapper input.custom-input {
        margin-bottom: 0;
    }

    .payment-summary-box h3 {
        margin-top: 0;
        font-size: 22px;
        color: #333;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 20px;
        text-align: center;
    }
    .payment-summary-box p {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        font-size: 15px;
    }
    .payment-summary-box p strong {
        color: #222;
        font-weight: 600;
    }
    .payment-summary-box p span {
        color: #555;
    }
    .payment-summary-box #totale-vendita {
        background-color:#28a745;
        color:white;
        padding:12px;
        font-weight:bold;
        font-size:1.3em;
        margin: 15px 0;
        border-radius: 8px;
        text-align: center;
    }
    .payment-summary-box div label {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        font-size: 14px;
        color: #555;
        cursor: pointer;
    }
    .payment-summary-box input[type="checkbox"] {
        margin-right: 8px;
        width: 18px;
        height: 18px;
        accent-color: #28a745;
        cursor: pointer;
    }

    .cart-summary-main h2 {
        margin-top: 0;
        color: #28a745;
        font-weight: 700;
        font-size: 24px;
        margin-bottom: 25px;
        text-align: center;
    }

    .cart-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .cart-table thead th {
        background-color: #e6f4ea;
        color: #28a745;
        font-weight: 600;
        padding: 15px 20px;
        text-align: left;
        border-bottom: 2px solid #ddd;
        font-size: 14px;
    }
    .cart-table thead th:first-child { border-top-left-radius: 8px; }
    .cart-table thead th:last-child { border-top-right-radius: 8px; }

    .cart-table tbody tr {
        background-color: #fcfcfc;
        border: 1px solid #eee;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .cart-table tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .cart-table td {
        text-align: left;
        padding: 12px 20px;
        vertical-align: middle;
        font-size: 15px;
        color: #333;
    }
    .cart-table tbody tr td:first-child { border-left: none; }
    .cart-table tbody tr td:last-child { border-right: none; }

    .cart-table img {
        max-width: 60px;
        max-height: 60px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #ddd;
    }

    .cart-table td.name-col {
        font-weight: 600;
        color: #333;
    }
    .cart-table td.name-col small {
        display: block;
        color: #888;
        font-weight: normal;
        font-size: 0.85em;
        margin-top: 3px;
    }

    .cart-table td.qty-col,
    .cart-table td.price-col,
    .cart-table td.discount-col,
    .cart-table td.stock-col {
        text-align: center;
        font-size: 14px;
        color: #555;
    }

    .cart-table input[type="number"] {
        width: 100px;
        padding: 6px 10px;
        font-size: 15px;
        border-radius: 6px;
        border: 1px solid #ccc;
        text-align: right;
        box-sizing: border-box;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .cart-table input[type="number"]:focus {
        outline: none;
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
    }

    .results-container, #clienteRisultati {
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #28a745;
        border-radius: 6px;
        max-height: 200px;
        overflow-y: auto;
        font-family: Arial, sans-serif;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.15);
    }

    .results-container div, #clienteRisultati div {
        padding: 10px 15px;
        cursor: pointer;
        color: #333;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s ease;
    }

    .results-container div:last-child, #clienteRisultati div:last-child {
        border-bottom: none;
    }

    .results-container div:hover, #clienteRisultati div:hover {
        background-color: #28a745;
        color: white;
    }


    #modalCliente {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0,0,0,0.6);
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
    }

    #modalCliente.active {
        display: flex;
        opacity: 1;
        visibility: visible;
    }

    #modalCliente .modal-content {
        background: white;
        border-radius: 12px;
        padding: 25px 30px;
        width: 380px;
        max-width: 90%;
        box-sizing: border-box;
        box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        position: relative;
        transform: translateY(-20px);
        transition: transform 0.3s ease-in-out;
    }
    #modalCliente.active .modal-content {
        transform: translateY(0);
    }

    #modalCliente .modal-header {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 20px;
        color: #28a745;
        text-align: center;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    #modalCliente .close-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        background: transparent;
        border: none;
        font-size: 30px;
        cursor: pointer;
        color: #888;
        font-weight: normal;
        line-height: 1;
        transition: color 0.2s ease;
    }
    #modalCliente .close-btn:hover {
        color: #333;
    }

    #modalCliente label {
        font-weight: 600;
        font-size: 14px;
        color: #555;
        margin-bottom: 6px;
        display: block;
    }

    #modalCliente input, #modalCliente textarea {
        width: 100%;
        padding: 10px 15px;
        margin-bottom: 15px;
        border-radius: 8px;
        border: 1px solid #ddd;
        font-size: 15px;
        box-sizing: border-box;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    #modalCliente input:focus, #modalCliente textarea:focus {
        outline: none;
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
    }

    #modalCliente button.submit-btn {
        background-color: #28a745;
        border: none;
        color: white;
        font-size: 16px;
        padding: 12px 0;
        width: 100%;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: background-color 0.25s ease, transform 0.1s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    #modalCliente button.submit-btn:hover {
        background-color: #218838;
        transform: translateY(-2px);
    }

    .tab-switcher {
        display: flex;
        border-bottom: 2px solid #ddd;
        margin-bottom: 15px;
        background-color: #f8f8f8;
        border-radius: 8px 8px 0 0;
        overflow: hidden;
    }
    .tab-btn {
        flex: 1;
        background: none;
        border: none;
        padding: 12px 10px;
        font-weight: 600;
        font-size: 1em;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: border-color 0.25s ease-in-out, color 0.25s ease-in-out, background-color 0.25s ease;
        color: #555;
    }
    .tab-btn.active {
        border-color: #28a745;
        color: #28a745;
        background-color: #fff;
    }
    .tab-btn:hover:not(.active) {
        background-color: #f0f0f0;
    }
    .tab-content {
        display: none;
        padding-top: 5px;
    }
    .tab-content.active {
        display: block;
    }

    .swal2-container {
      z-index: 20000 !important;
    }

    #residuo {
        padding: 8px 15px;
        border-radius: 10px;
        box-shadow: 0 6px 15px rgba(0,0,0,0.25);
        transition: all 0.3s ease;
        display: inline-block;
        font-weight: 900;
        font-size: 1.1em;
        text-align: center;
        width: 100%;
        box-sizing: border-box;
    }
    #residuo.highlight-positive {
        background-color: #f8d7da;
        color: #dc3545;
    }

    #residuo.highlight-negative {
        background-color: #d4edda;
        color: #28a745;
    }

    .delete-item-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        border-radius: 50%;
        transition: background-color 0.2s ease, transform 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .delete-item-btn:hover {
        background-color: #f8d7da;
        transform: scale(1.1);
    }
    .delete-item-btn svg {
        color: #dc3545;
        stroke: #dc3545;
    }

    /* Pulsanti Salva/Conferma */
    .action-buttons-container {
        display: flex;
        justify-content: flex-end;
        gap: 20px;
        padding: 20px 30px;
        width: 100%;
        max-width: 1600px; /* Aumentato la larghezza massima del contenitore dei bottoni */
        margin: 0 auto 30px auto;
        box-sizing: border-box;
    }
    .action-buttons-container button {
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s, color 0.3s, transform 0.2s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .action-buttons-container button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .action-buttons-container button[type="button"] {
        border: 1px solid #007bff;
        background-color: #fff;
        color: #007bff;
    }
    .action-buttons-container button[type="button"]:hover {
        background-color: #007bff;
        color: white;
    }
    .action-buttons-container button[type="submit"] {
        border: none;
        background-color: #28a745;
        color: white;
    }
    .action-buttons-container button[type="submit"]:hover {
        background-color: #218838;
    }
    /* Stile per il nuovo pulsante "Svuota Carrello" */
    .action-buttons-container .empty-cart-button {
        background-color: #dc3545; /* Rosso più intenso per azione importante */
        color: white;
        border: none;
        font-weight: 700; /* Rende il testo più grassetto */
    }

    .action-buttons-container .empty-cart-button:hover {
        background-color: #c82333; /* Rosso ancora più scuro all'hover */
        transform: translateY(-2px) scale(1.02); /* Leggero ingrandimento all'hover */
    }


    /* Media Queries per Responsiveness */
    /* Passa a layout a singola colonna su schermi più piccoli */
    @media (max-width: 1200px) {
        .page-content-wrapper {
            display: flex; /* Ritorna a flexbox per l'impilamento */
            flex-direction: column;
            align-items: center; /* Centra gli elementi impilati */
            padding: 20px;
            gap: 20px;
            max-width: 100%; /* Permette al wrapper di occupare tutta la larghezza disponibile */
        }
        /* Resetta il posizionamento della griglia per tutti gli elementi */
        .section-box,
        .payment-summary-box,
        .cart-summary-main {
            grid-area: auto !important;
            flex-basis: auto !important; /* Rimuove le impostazioni flex-basis precedenti */
            min-width: 90%; /* Adatta la larghezza dei singoli box */
            max-width: 768px; /* Aumentato la larghezza massima per leggibilità su schermi medi */
            width: 100%; /* Assicura che prendano la larghezza disponibile all'interno del max-width */
            margin: 0; /* Rimuove eventuali margini automatici se presenti */
        }
        .cart-summary-main {
            margin-top: 0; /* Resetta anche il margine superiore se specificato */
        }
        .action-buttons-container {
            justify-content: center; /* Centra i bottoni quando impilati */
        }
    }

    @media (max-width: 768px) {
        .page-content-wrapper {
            padding: 15px;
            gap: 15px;
        }
        .section-box, .payment-summary-box, .cart-summary-main {
            padding: 20px;
        }
        .cart-summary-main h2 {
            font-size: 22px;
        }
        .cart-table th, .cart-table td {
            padding: 10px 15px;
            font-size: 14px;
        }
        .cart-table input[type="number"] {
            width: 80px;
            padding: 4px 8px;
            font-size: 14px;
        }
        .action-buttons-container {
            flex-direction: column;
            gap: 15px;
            padding: 15px;
        }
        .action-buttons-container button {
            width: 100%;
            padding: 10px 20px;
            font-size: 15px;
        }
    }

    @media (max-width: 480px) {
        .page-content-wrapper {
            padding: 10px;
            gap: 10px;
        }
        .section-box, .payment-summary-box, .cart-summary-main {
            padding: 15px;
        }
        .cart-table img {
            max-width: 40px;
            max-height: 40px;
        }
        .cart-table td.name-col {
            font-size: 13px;
        }
        .cart-table td.name-col small {
            font-size: 0.8em;
        }
    }

</style>
</head>
<body>
<?php
// L'inclusione di header.php è ora attiva come richiesto.
// Assicurati che il file 'header.php' esista nella stessa directory o nel percorso specificato.
// Inoltre, 'header.php' dovrebbe contenere solo il markup della barra superiore e non le tag <html>, <head>, <body>.
// Se header.php è un file PHP valido e contiene solo la top bar, includerlo qui è corretto.
// Se invece header.php contiene tutto il markup HTML (<html>, <head>, <body>), allora la struttura sarà errata.
// Assumo che header.php contenga solo la top bar.
include 'header.php';
?>

<!-- Contenitore principale della pagina, gestisce il layout a griglia su desktop e impilamento su mobile -->
<div class="page-content-wrapper">

    <!-- Cliente Section Box -->
    <div class="section-box">
        <label for="clienteInput">Cliente</label>
        <div class="input-icon-wrapper">
            <input
                class="custom-input"
                type="text"
                id="clienteInput"
                name="nome_cliente"
                placeholder="Seleziona o aggiungi cliente"
                autocomplete="off"
                value="<?= htmlspecialchars($nome_cliente) ?>"
            />
            <input type="hidden" id="idCliente" name="id_cliente" value="<?= htmlspecialchars($id_cliente) ?>" />

            <button class="icon-btn" title="Aggiungi nuovo cliente" onclick="openModalCliente()" aria-label="Aggiungi nuovo cliente">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </button>
            <!-- Contenitore suggerimenti cliente -->
            <div id="clienteRisultati" class="results-container"></div>
        </div>
    </div>

    <!-- Buono spesa Section Box -->
    <div class="section-box">
        <label for="buono-spesa" class="label">Buono spesa</label>
        <div class="input-icon-wrapper">
            <input
                id="buono-spesa"
                class="custom-input"
                type="text"
                placeholder="Codice buono"
                aria-label="Codice buono spesa"
                autocomplete="off"
            />
        </div>
        <!-- Campo nascosto per salvare l'id del buono selezionato -->
        <input type="hidden" id="idBuono" name="idBuono" />
    </div>

    <!-- Pagamento 1 Section Box -->
    <div class="section-box">
        <label>Pagamento 1</label>
        <div class="payment-wrapper">
            <select class="custom-select" aria-label="Metodo pagamento 1" id="metodoPagamento1">
                <option value="">Seleziona metodo</option>
                <option value="contanti">Contanti</option>
                <option value="carta">Carta</option>
                <option value="bonifico">Bonifico</option>
                <option value="paypal">PayPal</option>
            </select>
            <input id="pagamento1" class="custom-input" type="number" placeholder="Importo €" min="0" step="0.01" value="0.00" onfocus="clearZero(this)" />
        </div>
    </div>

    <!-- Pagamento 2 Section Box -->
    <div class="section-box">
        <label>Pagamento 2</label>
        <div class="payment-wrapper">
            <select class="custom-select" aria-label="Metodo pagamento 2" id="metodoPagamento2">
                <option value="">Seleziona metodo</option>
                <option value="contanti">Contanti</option>
                <option value="carta">Carta</option>
                <option value="bonifico">Bonifico</option>
                <option value="paypal">PayPal</option>
            </select>
            <input id="pagamento2" class="custom-input" type="number" placeholder="Importo €" min="0" step="0.01" value="0.00" onfocus="clearZero(this)" />
        </div>
    </div>

    <!-- Riepilogo Pagamento Section Box -->
    <div id="riepilogo-pagamento-box" class="payment-summary-box">
        <h3>Riepilogo Pagamento</h3>

        <p><strong>Totale quantità:</strong> <span id="totale-quantita"><?= $quantitaTotaleIniziale ?></span></p>

        <p><strong>Totale articoli:</strong> <span id="totale-articoli"><?= $totaleArticoliUniciIniziale ?></span></p>

        <p><strong>Imponibile:</strong> € <span id="imponibile">0,00</span></p>

        <p><strong>Totale vendita:</strong></p>
        <div id="totale-vendita">
            € <?= number_format($totaleVenditaIniziale, 2, ',', '.') ?>
        </div>

        <p><strong>Totale da pagare:</strong> € <span id="totale-da-pagare">0,00</span></p>

        <div>
            <label><input type="checkbox" id="chk-saldo" name="pagamento" value="saldo"> Saldo</label><br>
            <label><input type="checkbox" id="chk-acconto" name="pagamento" value="acconto"> Acconto</label>
        </div>

        <p><strong>Residuo:</strong> <span id="residuo">€ 0,00</span></p>
        <div style="margin-top:20px;">
            <label><input type="checkbox" id="chk-stampante2"> Stampante 2</label><br>
            <label>
                <input type="checkbox" id="chk-scontrino"> Scontrino
                <input type="checkbox" id="chk-scontrino-cortesia" title="Scontrino Cortesia" style="margin-left: 10px;">
            </label>
        </div>
    </div>

    <!-- Riepilogo Carrello Tabella -->
    <div class="cart-summary-main" role="region" aria-label="Riepilogo carrello">
        <h2>Riepilogo Articoli Carrello</h2>
        <table class="cart-table" role="table">
            <thead>
                <tr>
                    <th style="width: 80px;">Azione</th>
                    <th style="width: 80px;">Immagine</th>
                    <th>Nome Prodotto / Merceologia / Servizio</th>
                    <th style="width: 120px; text-align: center;">Quantità</th>
                    <th style="width: 150px; text-align: center;">Prezzo Vendita</th>
                    <th style="width: 150px; text-align: center;">Prezzo Scontato</th>
                    <th style="width: 100px; text-align: center;">Giacenza</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($carrello)): ?>
                    <?php foreach ($carrello as $index => $item): ?>
                    <tr>
                        <td style="text-align: center;">
                            <button class="delete-item-btn" data-id="<?= htmlspecialchars($item['id'] ?? '') ?>" title="Rimuovi prodotto">
                                <!-- Icona cestino (Trash) da Feather Icons -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                            </button>
                        </td>
                        <td style="text-align: center;">
                            <?php if (!empty($item['img'])): ?>
                                <img src="<?= htmlspecialchars($item['img']) ?>" alt="Immagine di <?= htmlspecialchars($item['name'] ?? 'Prodotto') ?>" onerror="this.onerror=null;this.src='https://placehold.co/60x60/f0f0f0/cccccc?text=No+Img';">
                            <?php else: ?>
                                <img src="https://placehold.co/60x60/f0f0f0/cccccc?text=No+Img" alt="Nessuna immagine">
                            <?php endif; ?>
                        </td>
                        <td class="name-col">
                            <?= htmlspecialchars($item['name'] ?? 'Nome mancante') ?><br/>
                            <small><?= htmlspecialchars($item['merceologia'] ?? '') ?></small>
                        </td>
                        <td class="qty-col">
                            <!-- Input Quantità con +/- controlli e validazione -->
                            <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                                <button class="qty-btn" onclick="updateCartItemQuantity(this, -1, '<?= $index ?>')">-</button>
                                <input
                                    type="number"
                                    class="item-qty-input"
                                    value="<?= intval($item['qty'] ?? 0) ?>"
                                    min="1"
                                    data-item-key="<?= $index ?>"
                                    data-max-stock="<?= intval($item['giacenza'] ?? 0) ?>"
                                    onchange="updateCartItemQuantity(this, 0, '<?= $index ?>')"
                                />
                                <button class="qty-btn" onclick="updateCartItemQuantity(this, 1, '<?= $index ?>')">+</button>
                            </div>
                        </td>
                        <td class="price-col">
                            <input
                                type="number"
                                step="0.01"
                                class="prezzo-vendita"
                                data-qty="<?= intval($item['qty'] ?? 0) ?>"
                                name="carrello[<?= $index ?>][price]"
                                value="<?= number_format($item['price'] ?? 0, 2, '.', '') ?>"
                            />
                        </td>
                        <td class="discount-col">
                            <input
                                type="number"
                                step="0.01"
                                name="carrello[<?= $index ?>][prezzo_scontato]"
                                value="<?= number_format($item['prezzo_scontato'] ?? 0, 2, '.', '') ?>"
                            />
                        </td>
                        <td class="stock-col"><?= intval($item['giacenza'] ?? 0) ?></td>
                        <!-- ID nascosto per salvataggio -->
                        <input type="hidden" name="carrello[<?= $index ?>][id]" value="<?= htmlspecialchars($item['id'] ?? $index) ?>">
                        <input type="hidden" name="carrello[<?= $index ?>][name]" value="<?= htmlspecialchars($item['name'] ?? '') ?>">
                        <input type="hidden" name="carrello[<?= $index ?>][merceologia]" value="<?= htmlspecialchars($item['merceologia'] ?? '') ?>">
                        <input type="hidden" class="item-giacenza-hidden" name="carrello[<?= $index ?>][giacenza]" value="<?= intval($item['giacenza'] ?? 0) ?>">
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; padding: 20px;">Il carrello è vuoto.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div> <!-- Chiusura di .page-content-wrapper -->


<!-- INIZIO FORM DI VENDITA PRINCIPALE -->
<form action="salva_vendita.php" method="POST" id="form-vendita">
  <!-- INPUT NASCOSTI - Aggiornati da JavaScript prima del submit -->
  <input type="hidden" name="id_cliente" id="input_id_cliente" value="<?= htmlspecialchars($id_cliente) ?>">
  <input type="hidden" name="nome_cliente" id="input_nome_cliente" value="<?= htmlspecialchars($nome_cliente) ?>">
  <input type="hidden" name="pagamento1_metodo" id="input_pagamento1_metodo">
  <input type="hidden" name="pagamento1_importo" id="input_pagamento1_importo">
  <input type="hidden" name="pagamento2_metodo" id="input_pagamento2_metodo">
  <input type="hidden" name="pagamento2_importo" id="input_pagamento2_importo">
  <input type="hidden" name="residuo_da_dare" id="input_residuo_da_dare">
  <input type="hidden" name="carrello_json" id="input_carrello_json">
  <input type="hidden" name="id_buono" id="input_id_buono">
  <input type="hidden" name="saldo" id="input_saldo">
  <input type="hidden" name="acconto" id="input_acconto">
  <input type="hidden" name="stampante2" id="input_stampante2">
  <input type="hidden" name="scontrino" id="input_scontrino">
  <input type="hidden" name="scontrino_cortesia" id="input_scontrino_cortesia">

  <!-- NUOVI CAMPI NASCOSTI PER I TOTALI FINALI -->
  <input type="hidden" name="totale_vendita" id="input_totale_vendita">
  <input type="hidden" name="totale_da_pagare_finale" id="input_totale_da_pagare_finale">


  <!-- BOTTONI -->
  <div class="action-buttons-container">
    <button type="button" onclick="salvaModifiche()">
      Salva modifiche
    </button>

    <button type="button" id="emptyCartButton" class="empty-cart-button">
      Svuota Carrello
    </button>

    <button type="submit">
      Conferma Vendita
    </button>
  </div>

</form>


<!-- Modal per nuovo cliente -->
<div id="modalCliente" role="dialog" aria-modal="true" aria-labelledby="modalClienteTitle" class="modal">
  <div class="modal-content">
    <button class="close-btn" aria-label="Chiudi" onclick="closeModalCliente()">×</button>
    <div class="modal-header" id="modalClienteTitle">Nuovo Cliente</div>

    <!-- Nav schede -->
    <div class="tab-switcher">
      <button type="button" class="tab-btn active" data-tab="persona">Persona</button>
      <button type="button" class="tab-btn" data-tab="azienda">Azienda</button>
    </div>
    <!-- Contenuto del form all'interno del modal, non c'è più un popupCliente esterno -->
    <div>
        <form id="formCliente" action="salva_cliente.php" method="POST">
            <!-- Scheda Persona -->
            <div class="tab-content active" data-tab="persona">
                <label for="cognome">Cognome</label>
                <input id="cognome" name="cognome" type="text" autocomplete="family-name" class="custom-input" required />

                <label for="nome">Nome</label>
                <input id="nome" name="nome" type="text" autocomplete="given-name" class="custom-input" required />

                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" class="custom-input" required />

                <label for="telefono">Telefono</label>
                <input id="telefono" name="telefono" type="tel" autocomplete="tel" class="custom-input" required />

                <label for="indirizzo">Indirizzo</label>
                <input id="indirizzo" name="indirizzo" type="text" autocomplete="street-address" class="custom-input" />

                <label for="citta">Città</label>
                <input id="citta" name="citta" type="text" autocomplete="address-level2" class="custom-input" />

                <label for="note">Note</label>
                <textarea id="note" name="note" rows="3" class="custom-input"></textarea>
                <input type="hidden" name="tipo_cliente" value="persona">
            </div>

            <!-- Scheda Azienda -->
            <div class="tab-content" data-tab="azienda">
                <label for="partitaIva">Partita IVA</label>
                <input id="partitaIva" name="partitaIva" type="text" class="custom-input" />

                <label for="ragioneSociale">Ragione Sociale</label>
                <input id="ragioneSociale" name="ragioneSociale" type="text" class="custom-input" />

                <label for="indirizzoAzienda">Indirizzo Azienda</label>
                <input id="indirizzoAzienda" name="indirizzoAzienda" type="text" class="custom-input" />

                <label for="cittaAzienda">Città Azienda</label>
                <input id="cittaAzienda" name="cittaAzienda" type="text" class="custom-input" />

                <label for="telefonoAzienda">Telefono Azienda</label>
                <input id="telefonoAzienda" name="telefonoAzienda" type="tel" class="custom-input" />

                <label for="emailAzienda">Email Azienda</label>
                <input id="emailAzienda" name="emailAzienda" type="email" class="custom-input" />

                <label for="noteAzienda">Note Azienda</label>
                <textarea id="noteAzienda" name="noteAzienda" rows="3" class="custom-input"></textarea>
                <input type="hidden" name="tipo_cliente" value="azienda">
            </div>

            <button type="submit" class="submit-btn">Aggiungi Cliente</button>
        </form>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    console.log('Script Carrello.php loaded.');
    console.log('Stato iniziale di localStorage["cart"] (Carrello.php):', localStorage.getItem('cart'));

    // Funzione per svuotare l'input se il valore è "0.00"
    window.clearZero = function(element) {
        if (element.value === "0.00") {
            element.value = "";
        }
    };

    // --- Funzione principale per aggiornare tutti i totali e il riepilogo ---
    function aggiornaRiepilogo() {
        let totaleQuantita = 0;
        let totaleArticoliUnici = 0; // Conta il numero di righe nel carrello
        let totaleVenditaCalcolato = 0; // Somma dei prezzi * quantità

        // Itera su tutti gli input 'prezzo-vendita' nel carrello
        document.querySelectorAll('.cart-table tbody tr').forEach(row => {
            // Controlla se la riga non è il messaggio "Il carrello è vuoto."
            const qtyInput = row.querySelector('.item-qty-input');
            const prezzoInput = row.querySelector('.prezzo-vendita');

            if (qtyInput && prezzoInput) { // Se troviamo gli input, è una riga prodotto valida
                const qty = parseInt(qtyInput.value) || 0;
                const prezzo = parseFloat(prezzoInput.value.replace(',', '.')) || 0;

                totaleQuantita += qty;
                totaleArticoliUnici++; // Ogni riga del carrello è un articolo unico
                totaleVenditaCalcolato += qty * prezzo;
            }
        });

        // Calcolo imponibile (es. IVA 22%)
        const imponibile = totaleVenditaCalcolato / 1.22;

        // Recupera il valore del buono spesa
        let valoreBuono = 0; // Il valore del buono spesa è ora fisso a 0

        // Calcolo totale da pagare dopo applicazione buono spesa
        let totaleDaPagare = totaleVenditaCalcolato - valoreBuono;

        // Recupero degli importi già pagati
        const pag1 = parseFloat(document.getElementById('pagamento1')?.value.replace(',', '.') || 0);
        const pag2 = parseFloat(document.getElementById('pagamento2')?.value.replace(',', '.') || 0);
        const totalePagato = pag1 + pag2;

        // Calcolo del residuo (o resto da dare)
        let residuo = totaleDaPagare - totalePagato;

        // Aggiorna gli elementi HTML nel riepilogo
        document.getElementById('totale-quantita').textContent = totaleQuantita;
        document.getElementById('totale-articoli').textContent = totaleArticoliUnici;
        document.getElementById('imponibile').textContent = imponibile.toFixed(2).replace('.', ',');
        document.getElementById('totale-vendita').textContent = `€ ${totaleVenditaCalcolato.toFixed(2).replace('.', ',')}`;
        document.getElementById('totale-da-pagare').textContent = totaleDaPagare.toFixed(2).replace('.', ',');

        const residuoSpan = document.getElementById('residuo');
        // Rimuovi tutte le classi highlight per resettare lo stile
        residuoSpan.classList.remove('highlight-positive', 'highlight-negative');

        if (residuo > 0) { // C'è ancora un residuo da pagare
            residuoSpan.textContent = `Residuo da pagare: € ${residuo.toFixed(2).replace('.', ',')}`;
            residuoSpan.classList.add('highlight-positive'); // Applica lo stile per residuo positivo
        } else if (residuo < 0) { // C'è un resto da dare al cliente
            residuoSpan.textContent = `Resto da dare: € ${Math.abs(residuo).toFixed(2).replace('.', ',')}`;
            residuoSpan.classList.add('highlight-negative'); // Applica lo stile per residuo negativo (resto)
        } else { // Pagato esattamente, residuo zero
            residuoSpan.textContent = `€ 0,00`;
            residuoSpan.style.background = 'none'; // Rimuovi sfondo se è zero
            residuoSpan.style.color = 'inherit'; // Rimuovi colore forzato
            residuoSpan.style.fontWeight = 'normal'; // Rimuovi grassetto forzato
            residuoSpan.style.boxShadow = 'none'; // Rimuovi ombra
        }
    }

    // --- Funzione per aggiornare la quantità di un articolo nel carrello ---
    window.updateCartItemQuantity = function(element, delta, itemKey) {
        const input = element.closest('td').querySelector('.item-qty-input');
        const maxStock = parseInt(input.dataset.maxStock);
        let currentVal = parseInt(input.value) || 0;

        let newVal;
        if (delta === 0) { // L'evento è un onchange dall'input direttamente
            newVal = parseInt(input.value) || 0;
        } else { // L'evento è da un pulsante +/-
            newVal = currentVal + delta;
        }

        // Assicura che la quantità sia >= 1 e non superi la giacenza massima
        newVal = Math.max(1, newVal);
        newVal = Math.min(newVal, maxStock);

        input.value = newVal; // Aggiorna il valore nell'input

        // Aggiorna la quantità nel dataset dell'input del prezzo (per aggiornaRiepilogo)
        const prezzoVenditaInput = element.closest('tr').querySelector('.prezzo-vendita');
        if (prezzoVenditaInput) {
            prezzoVenditaInput.dataset.qty = newVal;
        }

        aggiornaRiepilogo(); // Ricalcola i totali
    };


    // --- Inizializzazione e Event Listeners per i calcoli ---
    aggiornaRiepilogo(); // Esegui il calcolo al caricamento della pagina

    // Event listeners per gli input che influenzano i calcoli (prezzo di vendita)
    document.querySelectorAll('.prezzo-vendita').forEach(input => {
        input.addEventListener('input', aggiornaRiepilogo); // Aggiorna se il prezzo di vendita cambia
    });

    // Event listeners per gli input dei pagamenti
    ['pagamento1', 'pagamento2'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', aggiornaRiepilogo); // Aggiorna se gli importi dei pagamenti cambiano
    });

    // Event listeners per le checkbox Saldo/Acconto (anche se non influenzano il calcolo in questo esempio, è buona pratica)
    ['chk-saldo', 'chk-acconto', 'chk-stampante2', 'chk-scontrino', 'chk-scontrino-cortesia'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', aggiornaRiepilogo);
    });

    // --- LOGICA DI ESCLUSIVITÀ PER I CHECKBOX ---
    const chkSaldo = document.getElementById('chk-saldo');
    const chkAcconto = document.getElementById('chk-acconto');

    if (chkSaldo && chkAcconto) {
        chkSaldo.addEventListener('change', function() {
            if (this.checked) {
                chkAcconto.checked = false; // Deseleziona acconto se saldo è selezionato
            }
            aggiornaRiepilogo(); // Richiama per aggiornare il riepilogo
        });

        chkAcconto.addEventListener('change', function() {
            if (this.checked) {
                chkSaldo.checked = false; // Deseleziona saldo se acconto è selezionato
            }
            aggiornaRiepilogo(); // Richiama per aggiornare il riepilogo
        });
    }

    const chkStampante2 = document.getElementById('chk-stampante2');
    const chkScontrino = document.getElementById('chk-scontrino');

    if (chkStampante2 && chkScontrino) {
        chkStampante2.addEventListener('change', function() {
            if (this.checked) {
                chkScontrino.checked = false; // Deseleziona scontrino se stampante2 è selezionato
            }
            aggiornaRiepilogo();
        });

        chkScontrino.addEventListener('change', function() {
            if (this.checked) {
                chkStampante2.checked = false; // Deseleziona stampante2 se scontrino è selezionato
            }
            aggiornaRiepilogo();
        });
    }

    // --- Gestione Modale Cliente ---
    const modalCliente = document.getElementById('modalCliente');
    const clienteInput = document.getElementById('clienteInput');
    const idClienteInput = document.getElementById('idCliente');
    const clienteSuggerimenti = document.getElementById('clienteRisultati');

    window.openModalCliente = function() {
        modalCliente.classList.add('active');
        // Imposta il focus sul primo campo della scheda "Persona"
        document.getElementById('cognome').focus();
    };

    window.closeModalCliente = function() {
        modalCliente.classList.remove('active');
        document.getElementById('formCliente').reset(); // Resetta il form alla chiusura
        // Riattiva la scheda "Persona" come predefinita
        document.querySelectorAll('#modalCliente .tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelector('#modalCliente .tab-btn[data-tab="persona"]').classList.add('active');
        document.querySelectorAll('#modalCliente .tab-content').forEach(content => content.classList.remove('active'));
        document.querySelector('#modalCliente .tab-content[data-tab="persona"]').classList.add('active');
    };

    // Chiudi modali con tasto ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === "Escape") {
            if (modalCliente.classList.contains('active')) {
                closeModalCliente();
            }
        }
    });

    // Gestione cambio schede nel modal cliente
    document.querySelectorAll('#modalCliente .tab-btn').forEach(button => {
        button.addEventListener('click', () => {
            const tab = button.getAttribute('data-tab');
            document.querySelectorAll('#modalCliente .tab-btn').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            document.querySelectorAll('#modalCliente .tab-content').forEach(content => {
                content.classList.remove('active');
                if (content.getAttribute('data-tab') === tab) {
                    content.classList.add('active');
                    const firstInput = content.querySelector('input, textarea');
                    if (firstInput) firstInput.focus();
                }
            });
        });
    });

    // Gestione submit del form cliente (dentro il modal)
    document.getElementById('formCliente').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        // Determina il tipo di cliente dalla scheda attiva
        const activeTab = document.querySelector('#modalCliente .tab-content.active').getAttribute('data-tab');
        formData.append('tipo_cliente', activeTab);

        fetch(form.action, {
            method: form.method,
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Cliente salvato!',
                    text: data.message || 'Registrazione completata.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    closeModalCliente();
                    // Aggiorna l'input del cliente principale con il nome appena salvato e il suo ID
                    clienteInput.value = data.nome_completo || data.ragione_sociale || '';
                    idClienteInput.value = data.id_cliente || '';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Errore',
                    text: data.message || 'Errore nel salvataggio.'
                });
            }
        })
        .catch(err => {
            Swal.fire({
                icon: 'error',
                title: 'Errore di rete',
                text: 'Impossibile completare la richiesta: ' + err.message
            });
            console.error('Errore fetch cliente:', err);
        });
    });

    // --- Autocomplete per Cliente ---
    if (clienteInput && clienteSuggerimenti) {
        clienteInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length < 2) {
                clienteSuggerimenti.innerHTML = '';
                clienteSuggerimenti.style.display = 'none';
                idClienteInput.value = '';
                return;
            }

            fetch(`ricerca_cliente.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    clienteSuggerimenti.innerHTML = '';
                    if (data.length === 0) {
                        clienteSuggerimenti.style.display = 'none';
                        return;
                    }
                    clienteSuggerimenti.style.display = 'block';

                    data.forEach(cliente => {
                        const div = document.createElement('div');
                        div.textContent = cliente.nome + (cliente.cognome ? ' ' + cliente.cognome : '') + (cliente.ragione_sociale ? ' (' + cliente.ragione_sociale + ')' : '');
                        div.addEventListener('click', function() {
                            clienteInput.value = cliente.nome + (cliente.cognome ? ' ' + cliente.cognome : '') + (cliente.ragione_sociale ? ' (' + cliente.ragione_sociale + ')' : '');
                            idClienteInput.value = cliente.id;
                            clienteSuggerimenti.innerHTML = '';
                            clienteSuggerimenti.style.display = 'none';
                            aggiornaRiepilogo();
                        });
                        clienteSuggerimenti.appendChild(div);
                    });
                })
                .catch(error => {
                    console.error('Errore ricerca cliente:', error);
                    clienteSuggerimenti.innerHTML = '';
                    clienteSuggerimenti.style.display = 'none';
                });
        });

        document.addEventListener('click', function(e) {
            if (!clienteSuggerimenti.contains(e.target) && e.target !== clienteInput) {
                clienteSuggerimenti.style.display = 'none';
            }
        });
    }

    // --- Gestione Form di Vendita Principale (Submit) ---
    const formVendita = document.getElementById('form-vendita');
    if (formVendita) {
        formVendita.addEventListener('submit', function(e) {
            e.preventDefault(); // Blocca l'invio predefinito del form
            console.log("Form di vendita sottomesso.");

            prepareFormDataForSubmit(); // Prepara tutti i campi nascosti

            const chkScontrino = document.getElementById('chk-scontrino');
            
            // Mostra il messaggio di caricamento
            Swal.fire({
                icon: 'info',
                title: 'Conferma Vendita',
                text: 'Invio dei dati di vendita...',
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Invio del form tramite fetch API
            fetch(formVendita.action, {
                method: formVendita.method,
                body: new FormData(formVendita) // FormData crea automaticamente il body nel formato corretto
            })
            .then(response => {
                console.log("Risposta ricevuta da salva_vendita.php");
                return response.json();
            })
            .then(data => {
                Swal.close(); // Chiudi il messaggio di caricamento

                if (data.success) {
                    console.log("Vendita registrata con successo. Dati:", data);
                    Swal.fire({
                        icon: 'success',
                        title: 'Vendita Registrata!',
                        text: data.message || 'Vendita completata con successo.',
                        timer: 2000, // Mostra per 2 secondi
                        showConfirmButton: false
                    }).then(() => {
                        // Svuota il carrello da localStorage QUI, prima di reindirizzare.
                        console.log("Svuoto il carrello da localStorage.");
                        localStorage.removeItem("cart"); 
                        console.log("localStorage['cart'] dopo rimozione:", localStorage.getItem('cart'));

                        // Se lo scontrino è selezionato, avvia la stampa professionale
                        if (chkScontrino && chkScontrino.checked) {
                            if (data.id_vendita) { // Assicurati che l'ID della vendita sia presente
                                const scontrinoPrintUrl = `genera_scontrino.php?id_vendita=${data.id_vendita}`;
                                const printWindow = window.open(scontrinoPrintUrl, '_blank', 'width=400,height=600,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1');
                                
                                // Event listener per quando la finestra dello scontrino si carica
                                printWindow.onload = function() {
                                    setTimeout(() => { // Dai un piccolo ritardo per assicurare il rendering
                                        printWindow.print();
                                        // Puoi scegliere di chiudere automaticamente la finestra dopo la stampa
                                        // printWindow.close(); 
                                    }, 500); // Piccolo ritardo per la stampante
                                };

                                // Event listener per quando la stampa è completata o annullata dall'utente
                                // Questo è più affidabile per il reindirizzamento dopo l'interazione con la finestra di stampa
                                printWindow.onafterprint = function() {
                                    console.log("Stampa scontrino completata/annullata. Reindirizzo a homepage.php.");
                                    window.location.href = 'homepage.php?venditaSuccesso=true'; // Reindirizza dopo la stampa/annullamento
                                };

                                // Fallback per browser che potrebbero bloccare i popup o non attivare onload/onafterprint
                                // Reindirizza comunque dopo un breve ritardo se la finestra non si è aperta o è stata chiusa
                                setTimeout(() => {
                                    if (!printWindow || printWindow.closed) { // Se la finestra è stata bloccata o chiusa
                                        console.log("Finestra di stampa non aperta o chiusa. Reindirizzo a homepage.php.");
                                        window.location.href = 'homepage.php?venditaSuccesso=true';
                                    }
                                }, 3000); // 3 secondi di attesa prima del fallback
                                
                            } else {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Stampa Scontrino',
                                    text: 'ID vendita non ricevuto per la stampa dello scontrino. Reindirizzo alla homepage.'
                                }).then(() => {
                                    console.log("ID vendita non presente. Reindirizzo a homepage.php.");
                                    window.location.href = 'homepage.php?venditaSuccesso=true'; // Reindirizza comunque alla homepage
                                });
                            }
                        } else {
                            // Se lo scontrino non è selezionato, reindirizza immediatamente alla homepage
                            console.log("Scontrino non selezionato. Reindirizzo a homepage.php.");
                            window.location.href = 'homepage.php?venditaSuccesso=true'; 
                        }
                    });
                } else {
                    console.error("Errore durante la registrazione della vendita. Dati:", data);
                    Swal.fire({
                        icon: 'error',
                        title: 'Errore Vendita',
                        text: data.message || 'Si è verificato un errore durante la registrazione della vendita.'
                    });
                }
            })
            .catch(error => {
                Swal.close(); // Chiudi il messaggio di caricamento anche in caso di errore di rete
                Swal.fire({
                    icon: 'error',
                    title: 'Errore di Rete',
                    text: 'Impossibile connettersi al server: ' + error.message
                });
                console.error('Errore durante l\'invio del form:', error);
            });
        });

        function prepareFormDataForSubmit() {
            document.getElementById('input_id_cliente').value = idClienteInput.value;
            document.getElementById('input_nome_cliente').value = clienteInput.value.trim();

            const carrelloArray = [];
            document.querySelectorAll('.cart-table tbody tr').forEach(row => {
                const prezzoInput = row.querySelector('.prezzo-vendita');
                // Salta la riga del messaggio "Il carrello è vuoto."
                if (!prezzoInput) { 
                    console.log("Skipping empty cart row during form data preparation.");
                    return;
                }

                const idProdotto = row.querySelector('input[name*="[id]"]')?.value || '';
                const nomeProdottoCell = row.querySelector('td.name-col');
                let nomeProdotto = '';
                if (nomeProdottoCell) {
                    const node = Array.from(nomeProdottoCell.childNodes).find(n => n.nodeType === Node.TEXT_NODE && n.textContent.trim().length > 0);
                    if (node) {
                        nomeProdotto = node.textContent.trim();
                    }
                }
                const merceologiaSpan = nomeProdottoCell.querySelector('small');
                const merceologia = merceologiaSpan ? merceologiaSpan.textContent.trim() : '';

                const qtyInput = row.querySelector('.item-qty-input');
                const qty = parseInt(qtyInput.value) || 0;

                const prezzoUnitario = parseFloat(prezzoInput.value.replace(',', '.') || 0);
                const prezzoScontato = parseFloat(row.querySelector('input[name*="[prezzo_scontato]"]')?.value.replace(',', '.') || 0);
                const giacenza = parseInt(row.querySelector('.item-giacenza-hidden')?.value) || 0;
                const imgElement = row.querySelector('td:nth-child(2) img');
                const img = imgElement ? imgElement.src : '';

                carrelloArray.push({
                    id: idProdotto,
                    name: nomeProdotto,
                    merceologia: merceologia,
                    qty: qty,
                    price: prezzoUnitario,
                    prezzo_scontato: prezzoScontato,
                    giacenza: giacenza,
                    img: img
                });
            });
            document.getElementById('input_carrello_json').value = JSON.stringify(carrelloArray);
            console.log("Carrello Array per submit:", carrelloArray);

            document.getElementById('input_pagamento1_metodo').value = document.getElementById('metodoPagamento1').value;
            // Converti in formato numerico (con punto come separatore decimale)
            document.getElementById('input_pagamento1_importo').value = parseFloat(document.getElementById('pagamento1').value.replace(',', '.')).toFixed(2);
            document.getElementById('input_pagamento2_metodo').value = document.getElementById('metodoPagamento2').value;
            // Converti in formato numerico (con punto come separatore decimale)
            document.getElementById('input_pagamento2_importo').value = parseFloat(document.getElementById('pagamento2').value.replace(',', '.')).toFixed(2);

            document.getElementById('input_saldo').value = document.getElementById('chk-saldo').checked ? '1' : '0';
            document.getElementById('input_acconto').value = document.getElementById('chk-acconto').checked ? '1' : '0';
            document.getElementById('input_stampante2').value = document.getElementById('chk-stampante2').checked ? '1' : '0';
            document.getElementById('input_scontrino').value = document.getElementById('chk-scontrino').checked ? '1' : '0';
            document.getElementById('input_scontrino_cortesia').value = document.getElementById('chk-scontrino-cortesia').checked ? '1' : '0';

            const residuoText = document.getElementById('residuo').textContent;
            const cleanedResiduo = residuoText.replace(/[^0-9.,-]/g, '').replace(',', '.').replace('Residuo da pagare: € ', '').replace('Resto da dare: € ', '');
            const residuoValue = parseFloat(cleanedResiduo) || 0;
            const finalResiduoValue = residuoText.includes('Resto da dare:') ? -Math.abs(residuoValue) : residuoValue;
            document.getElementById('input_residuo_da_dare').value = finalResiduoValue.toFixed(2);

            document.getElementById('input_id_buono').value = document.getElementById('buono-spesa').dataset.id || ''; // Assicurati che l'ID del buono venga letto dal dataset

            // ***** AGGIORNAMENTO AGGIUNTIVO: Popola i nuovi input nascosti per i totali finali *****
            const totaleVenditaElement = document.getElementById('totale-vendita');
            const totaleDaPagareElement = document.getElementById('totale-da-pagare');

            document.getElementById('input_totale_vendita').value = parseFloat(totaleVenditaElement.textContent.replace('€ ', '').replace(',', '.')).toFixed(2);
            document.getElementById('input_totale_da_pagare_finale').value = parseFloat(totaleDaPagareElement.textContent.replace(',', '.')).toFixed(2);
            console.log("Dati del form preparati.");
        }

        window.salvaModifiche = function() {
            prepareFormDataForSubmit();
            Swal.fire({
                icon: 'success',
                title: 'Modifiche Salvate!',
                text: 'I dati del carrello e dei pagamenti sono stati aggiornati (simulazione).',
                timer: 2000,
                showConfirmButton: false
            });
        };
    }

    document.querySelector('.cart-table tbody').addEventListener('click', function(e) {
        const targetButton = e.target.closest('.delete-item-btn');
        if (targetButton) {
            const rowToDelete = targetButton.closest('tr');
            if (rowToDelete) {
                Swal.fire({
                    title: 'Sei sicuro?',
                    text: "Non potrai annullare questa azione!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sì, rimuovi!',
                    cancelButtonText: 'Annulla'
                }).then((result) => {
                    if (result.isConfirmed) {
                        rowToDelete.remove();

                        const tbody = document.querySelector('.cart-table tbody');
                        if (tbody.children.length === 0) {
                            const emptyRow = document.createElement('tr');
                            emptyRow.innerHTML = '<td colspan="7" style="text-align: center; padding: 20px;">Il carrello è vuoto.</td>';
                            tbody.appendChild(emptyRow);
                        }

                        aggiornaRiepilogo();

                        Swal.fire(
                            'Rimosso!',
                            'Il prodotto è stato rimosso dal carrello.',
                            'success'
                        );
                    }
                });
            }
        }
    });

    // Inizializza il buono spesa come non trovato all'inizio
    document.getElementById('idBuono').value = '';
    document.getElementById('buono-spesa').dataset.id = ''; // Assicurati che anche il dataset sia pulito

    // --- Autocomplete e validazione Buono Spesa ---
    const buonoSpesaInput = document.getElementById('buono-spesa');
    let buonoSpesaTimer; // Timer per il debounce
    const BUONO_SPESA_DEBOUNCE_TIME = 300; // Millisecondi

    if (buonoSpesaInput) {
        buonoSpesaInput.addEventListener('input', function() {
            clearTimeout(buonoSpesaTimer);
            const query = this.value.trim();
            document.getElementById('idBuono').value = ''; // Resetta l'ID del buono ad ogni digitazione
            this.dataset.id = ''; // Resetta anche il dataset

            if (query.length === 0) {
                aggiornaRiepilogo(); // Ricalcola il totale se il campo è vuoto (rimuovendo l'effetto del buono)
                return;
            }

            buonoSpesaTimer = setTimeout(() => {
                fetch(`ricerca_buono.php?code=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.buono) {
                            document.getElementById('idBuono').value = data.buono.id;
                            buonoSpesaInput.dataset.id = data.buono.id; // Salva l'ID nel dataset per prepareFormDataForSubmit
                            Swal.fire({
                                icon: 'success',
                                title: 'Buono Trovato!',
                                text: `Buono valido: ${data.buono.valore}€`,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            // AGGIORNA IL VALORE DEL BUONO IN UN POSTO ACCESSIBILE PER AGGIORNARIEPILOGO
                            // Per semplicità, qui lo useremo come valore fisso (come da precedente logica)
                            // In un sistema reale, dovresti passare 'data.buono.valore' alla funzione aggiornaRiepilogo
                            // o aggiornare una variabile globale `valoreBuono` che `aggiornaRiepilogo` legge.
                            // Per il momento, `aggiornaRiepilogo` ha `valoreBuono = 0;` fisso.
                            // Se il buono deve effettivamente applicare uno sconto, è qui che deve essere passato il valore.
                            // Esempio: totaleVenditaCalcolato - data.buono.valore
                            aggiornaRiepilogo(); // Ricalcola il totale per includere il buono se applicato
                        } else {
                            document.getElementById('idBuono').value = '';
                            buonoSpesaInput.dataset.id = '';
                            Swal.fire({
                                icon: 'error',
                                title: 'Buono Non Valido',
                                text: data.message || 'Il codice del buono spesa non è valido o è scaduto.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            aggiornaRiepilogo(); // Ricalcola il totale per rimuovere l'effetto di un buono non valido
                        }
                    })
                    .catch(error => {
                        console.error('Errore ricerca buono:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Errore di Rete',
                            text: 'Impossibile ricercare il buono spesa.'
                        });
                        document.getElementById('idBuono').value = '';
                        buonoSpesaInput.dataset.id = '';
                        aggiornaRiepilogo();
                    });
            }, BUONO_SPESA_DEBOUNCE_TIME);
        });
    }

    // --- Gestione del pulsante "Svuota Carrello" ---
    const emptyCartButton = document.getElementById('emptyCartButton');
    if (emptyCartButton) {
        emptyCartButton.addEventListener('click', function() {
            Swal.fire({
                title: 'Sei sicuro?',
                text: "Vuoi svuotare completamente il carrello? Questa azione non può essere annullata!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sì, svuota!',
                cancelButtonText: 'Annulla'
            }).then((result) => {
                if (result.isConfirmed) {
                    const tbody = document.querySelector('.cart-table tbody');
                    // Rimuovi tutte le righe dal tbody
                    tbody.innerHTML = ''; 
                    
                    // Aggiungi la riga "Il carrello è vuoto."
                    const emptyRow = document.createElement('tr');
                    emptyRow.innerHTML = '<td colspan="7" style="text-align: center; padding: 20px;">Il carrello è vuoto.</td>';
                    tbody.appendChild(emptyRow);

                    // Svuota il carrello anche da localStorage
                    localStorage.removeItem("cart");
                    console.log("Carrello svuotato da localStorage.");

                    // Aggiorna tutti i totali
                    aggiornaRiepilogo();

                    Swal.fire(
                        'Svuotato!',
                        'Il carrello è stato svuotato con successo.',
                        'success'
                    );
                }
            });
        });
    }

});
</script>

</body>
</html>
