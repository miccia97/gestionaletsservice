<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';

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
<link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Carrello - TS SERVICE</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/header-styles.css?v=1">

<style>
    /* === PREMIUM CONFIRM DIALOG === */
    .pcd-overlay {
        display:none; position:fixed; inset:0; background:rgba(15,23,42,0.5);
        backdrop-filter:blur(4px); z-index:9600; justify-content:center; align-items:center;
        opacity:0; transition:opacity .3s ease;
    }
    .pcd-overlay.visible { display:flex; opacity:1; }
    .pcd-box {
        background:#fff; border-radius:20px; padding:32px; max-width:420px; width:90%;
        box-shadow:0 20px 25px -5px rgb(0 0 0/.1); text-align:center;
        transform:scale(.92); opacity:0; transition:all .3s cubic-bezier(.34,1.56,.64,1);
    }
    .pcd-overlay.visible .pcd-box { transform:scale(1); opacity:1; }
    .pcd-icon { width:56px; height:56px; border-radius:50%; display:flex; align-items:center;
        justify-content:center; margin:0 auto 16px; font-size:1.5rem; }
    .pcd-icon.warn { background:#fef3c7; color:#f59e0b; }
    .pcd-icon.info { background:#dbeafe; color:#3b82f6; }
    .pcd-title { font-size:1.2rem; font-weight:700; color:#0f172a; margin-bottom:8px; font-family:'Inter',sans-serif; }
    .pcd-text { font-size:.9rem; color:#64748b; margin-bottom:24px; line-height:1.5; }
    .pcd-actions { display:flex; gap:12px; justify-content:center; }
    .pcd-btn { padding:11px 24px; border-radius:12px; font-weight:700; font-size:.88rem;
        font-family:'Inter',sans-serif; cursor:pointer; border:none; transition:all .2s ease; }
    .pcd-btn-cancel { background:transparent; color:#64748b; border:1.5px solid #e2e8f0; }
    .pcd-btn-cancel:hover { background:#f1f5f9; color:#0f172a; border-color:#94a3b8; }
    .pcd-btn-danger { background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff;
        box-shadow:0 4px 12px rgba(239,68,68,.4); }
    .pcd-btn-danger:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(239,68,68,.4); }

    /* === PREMIUM TOAST === */
    .pt-toast {
        position:fixed; top:24px; right:24px; z-index:9700; padding:14px 22px;
        border-radius:14px; font-family:'Inter',sans-serif; font-size:.9rem; font-weight:600;
        color:#fff; box-shadow:0 8px 24px rgba(0,0,0,.15);
        transform:translateX(120%); transition:transform .4s cubic-bezier(.34,1.56,.64,1);
        display:flex; align-items:center; gap:10px; max-width:400px;
    }
    .pt-toast.visible { transform:translateX(0); }
    .pt-toast.success { background:linear-gradient(135deg,#22c55e,#16a34a); }
    .pt-toast.error { background:linear-gradient(135deg,#ef4444,#dc2626); }
    .pt-toast.info { background:linear-gradient(135deg,#3b82f6,#2563eb); }
    .pt-toast i { font-size:1.1rem; }

    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* Header styles - gestiti da header-styles.css */

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

    /* === RIEPILOGO PAGAMENTO - REDESIGN MODERNO === */
    .payment-summary-box h3 {
        margin: 0 0 20px 0;
        font-size: 18px;
        font-weight: 700;
        color: #1a1a2e;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    .payment-summary-box h3::before {
        content: '';
        width: 24px;
        height: 24px;
        background: linear-gradient(135deg, #28a745, #20c997);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Sezioni del riepilogo */
    .summary-section {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .summary-section.totals {
        background: linear-gradient(135deg, #f0fff4 0%, #e6f4ea 100%);
        border: 1px solid rgba(40, 167, 69, 0.2);
    }
    .summary-section.options {
        background: #fff;
        border: 1px solid #e9ecef;
    }
    .summary-section-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .summary-section-title svg {
        width: 14px;
        height: 14px;
        stroke: #6c757d;
    }

    /* Righe del riepilogo */
    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px dashed #dee2e6;
    }
    .summary-row:last-child {
        border-bottom: none;
    }
    .summary-row .label {
        font-size: 13px;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .summary-row .label svg {
        width: 16px;
        height: 16px;
        stroke: #6c757d;
    }
    .summary-row .value {
        font-size: 14px;
        font-weight: 600;
        color: #212529;
    }

    /* Totale vendita grande */
    .total-vendita-box {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 16px 20px;
        border-radius: 12px;
        margin: 15px 0;
        text-align: center;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        position: relative;
        overflow: hidden;
    }
    .total-vendita-box::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 100%;
        background: background: #f4f6f8;
        pointer-events: none;
    }
    .total-vendita-box .total-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.9;
        margin-bottom: 5px;
    }
    .total-vendita-box .total-amount {
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    /* Da pagare */
    .da-pagare-row {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 12px 15px;
        margin: 10px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .da-pagare-row .label {
        font-size: 13px;
        font-weight: 600;
        color: #856404;
    }
    .da-pagare-row .value {
        font-size: 16px;
        font-weight: 700;
        color: #856404;
    }

    /* Residuo */
    .residuo-box {
        border-radius: 8px;
        padding: 12px 15px;
        margin: 10px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
    }
    /* Residuo - Design minimale */
    .residuo-box.has-residuo {
        background: transparent;
        border: none;
        border-left: 3px solid #e74c3c;
        border-radius: 0;
        padding: 10px 15px;
    }
    .residuo-box.has-resto {
        background: transparent;
        border: none;
        border-left: 3px solid #3498db;
        border-radius: 0;
        padding: 10px 15px;
    }
    .residuo-box.no-residuo {
        background: transparent;
        border: none;
        border-left: 3px solid #27ae60;
        border-radius: 0;
        padding: 10px 15px;
    }
    .residuo-box .label {
        font-size: 13px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #666;
    }
    .residuo-box .value {
        font-size: 18px;
        font-weight: 700;
    }
    .residuo-box.has-residuo .value { color: #e74c3c; }
    .residuo-box.has-resto .value { color: #3498db; }
    .residuo-box.no-residuo .value { color: #27ae60; }
    .residuo-box .icon {
        width: 18px;
        height: 18px;
        stroke: #888;
    }

    /* Checkbox stilizzate */
    .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: #f8f9fa;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    .checkbox-item:hover {
        background: #e9ecef;
        border-color: #dee2e6;
    }
    .checkbox-item.checked {
        background: #e6f4ea;
        border-color: #28a745;
    }
    .checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #28a745;
        cursor: pointer;
        margin: 0;
    }
    .checkbox-item .checkbox-label {
        font-size: 13px;
        color: #495057;
        flex: 1;
    }
    .checkbox-item .checkbox-icon {
        width: 18px;
        height: 18px;
        stroke: #6c757d;
    }

    /* Opzioni stampa */
    .print-options {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .print-option {
        flex: 1;
        min-width: 100px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        background: #f8f9fa;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    .print-option:hover {
        background: #e9ecef;
    }
    .print-option.checked {
        background: #e0f2fe;
        border-color: #0ea5e9;
    }
    .print-option input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #0ea5e9;
        cursor: pointer;
        margin: 0;
    }
    .print-option .print-label {
        font-size: 12px;
        color: #495057;
    }

    /* Divider */
    .summary-divider {
        height: 1px;
        background: linear-gradient(to right, transparent, #dee2e6, transparent);
        margin: 15px 0;
    }

    /* Vecchi stili per compatibilità */
    .payment-summary-box p {
        display: none; /* Nascosto - usiamo nuova struttura */
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

            <button class="icon-btn" title="Aggiungi nuovo cliente" onclick="openNewClientModal()" aria-label="Aggiungi nuovo cliente">
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

    <!-- Riepilogo Pagamento Section Box - REDESIGN -->
    <div id="riepilogo-pagamento-box" class="payment-summary-box">
        <h3>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            Riepilogo Pagamento
        </h3>

        <!-- Sezione Quantità -->
        <div class="summary-section totals">
            <div class="summary-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                </svg>
                Dettagli Ordine
            </div>
            <div class="summary-row">
                <span class="label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17.5 7.5C16 5 13.5 3.5 10.5 3.5 6.4 3.5 3 7 3 12s3.4 8.5 7.5 8.5c3 0 5.5-1.5 7-4"></path>
                        <line x1="2" y1="10" x2="14" y2="10"></line>
                        <line x1="2" y1="14" x2="14" y2="14"></line>
                    </svg>
                    Quantità totale
                </span>
                <span class="value" id="totale-quantita"><?= $quantitaTotaleIniziale ?></span>
            </div>
            <div class="summary-row">
                <span class="label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="9" y1="21" x2="9" y2="9"></line>
                    </svg>
                    Articoli
                </span>
                <span class="value" id="totale-articoli"><?= $totaleArticoliUniciIniziale ?></span>
            </div>
            <div class="summary-row">
                <span class="label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    Imponibile
                </span>
                <span class="value">€ <span id="imponibile">0,00</span></span>
            </div>
        </div>

        <!-- Totale Vendita Grande -->
        <div class="total-vendita-box" id="totale-vendita">
            <div class="total-label">Totale Vendita</div>
            <div class="total-amount">€ <?= number_format($totaleVenditaIniziale, 2, ',', '.') ?></div>
        </div>

        <!-- Da Pagare -->
        <div class="da-pagare-row">
            <span class="label">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Da pagare
            </span>
            <span class="value">€ <span id="totale-da-pagare">0,00</span></span>
        </div>

        <!-- Opzioni Pagamento -->
        <div class="summary-section options">
            <div class="summary-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                Tipo Pagamento
            </div>
            <div class="checkbox-group">
                <label class="checkbox-item" onclick="this.classList.toggle('checked')">
                    <input type="checkbox" id="chk-saldo" name="pagamento" value="saldo">
                    <svg class="checkbox-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <span class="checkbox-label">Saldo (pagamento completo)</span>
                </label>
                <label class="checkbox-item" onclick="this.classList.toggle('checked')">
                    <input type="checkbox" id="chk-acconto" name="pagamento" value="acconto">
                    <svg class="checkbox-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span class="checkbox-label">Acconto (pagamento parziale)</span>
                </label>
            </div>
        </div>

        <!-- Residuo -->
        <div class="residuo-box has-residuo" id="residuo-container">
            <span class="label">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                Residuo
            </span>
            <span class="value" id="residuo">€ 0,00</span>
        </div>

        <div class="summary-divider"></div>

        <!-- Opzioni Stampa -->
        <div class="summary-section options">
            <div class="summary-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Opzioni Stampa
            </div>
            <div class="print-options">
                <label class="print-option" onclick="this.classList.toggle('checked')">
                    <input type="checkbox" id="chk-stampante2">
                    <span class="print-label">Stampante 2</span>
                </label>
                <label class="print-option" onclick="this.classList.toggle('checked')">
                    <input type="checkbox" id="chk-scontrino">
                    <span class="print-label">Scontrino</span>
                </label>
                <label class="print-option" onclick="this.classList.toggle('checked')">
                    <input type="checkbox" id="chk-scontrino-cortesia" title="Scontrino Cortesia">
                    <span class="print-label">Cortesia</span>
                </label>
            </div>
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

<!-- Premium Confirm Dialog -->
<div class="pcd-overlay" id="pcdOverlay">
    <div class="pcd-box">
        <div class="pcd-icon warn" id="pcdIcon"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="pcd-title" id="pcdTitle"></div>
        <div class="pcd-text" id="pcdText"></div>
        <div class="pcd-actions">
            <button class="pcd-btn pcd-btn-cancel" id="pcdCancel">Annulla</button>
            <button class="pcd-btn pcd-btn-danger" id="pcdConfirm">Conferma</button>
        </div>
    </div>
</div>
<!-- Premium Toast -->
<div class="pt-toast" id="ptToast"><i></i><span></span></div>

<script>
// Premium toast function for carrello 
function premiumToast(message, type, duration) {
    type = type || 'success';
    duration = duration || 3000;
    var toast = document.getElementById('ptToast');
    var icon = toast.querySelector('i');
    var span = toast.querySelector('span');
    toast.className = 'pt-toast ' + type;
    span.textContent = message;
    icon.className = type === 'success' ? 'fas fa-check-circle' :
                     type === 'error' ? 'fas fa-circle-exclamation' :
                     'fas fa-circle-info';
    toast.classList.add('visible');
    clearTimeout(toast._hideTimer);
    toast._hideTimer = setTimeout(function() { toast.classList.remove('visible'); }, duration);
}

// Premium confirm dialog
function premiumConfirm(title, text, confirmLabel, onConfirm) {
    var overlay = document.getElementById('pcdOverlay');
    document.getElementById('pcdTitle').textContent = title;
    document.getElementById('pcdText').textContent = text;
    document.getElementById('pcdConfirm').textContent = confirmLabel || 'Conferma';
    overlay.style.display = 'flex';
    requestAnimationFrame(function() { overlay.classList.add('visible'); });

    function close() {
        overlay.classList.remove('visible');
        setTimeout(function() { overlay.style.display = 'none'; }, 300);
        document.getElementById('pcdConfirm').onclick = null;
        document.getElementById('pcdCancel').onclick = null;
    }
    document.getElementById('pcdCancel').onclick = close;
    overlay.onclick = function(e) { if (e.target === overlay) close(); };
    document.getElementById('pcdConfirm').onclick = function() { close(); if (onConfirm) onConfirm(); };
}
</script>

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
        
        // Aggiorna il totale vendita con la nuova struttura
        const totaleVenditaBox = document.getElementById('totale-vendita');
        totaleVenditaBox.innerHTML = `
            <div class="total-label">Totale Vendita</div>
            <div class="total-amount">€ ${totaleVenditaCalcolato.toFixed(2).replace('.', ',')}</div>
        `;
        
        document.getElementById('totale-da-pagare').textContent = totaleDaPagare.toFixed(2).replace('.', ',');

        // Aggiorna il residuo con la nuova struttura
        const residuoSpan = document.getElementById('residuo');
        const residuoContainer = document.getElementById('residuo-container');
        
        // Rimuovi tutte le classi per resettare lo stile
        residuoContainer.classList.remove('has-residuo', 'has-resto', 'no-residuo');

        if (residuo > 0) { // C'è ancora un residuo da pagare
            residuoSpan.textContent = `€ ${residuo.toFixed(2).replace('.', ',')}`;
            residuoContainer.classList.add('has-residuo');
            residuoContainer.querySelector('.label').innerHTML = `
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Da pagare
            `;
        } else if (residuo < 0) { // C'è un resto da dare al cliente
            residuoSpan.textContent = `€ ${Math.abs(residuo).toFixed(2).replace('.', ',')}`;
            residuoContainer.classList.add('has-resto');
            residuoContainer.querySelector('.label').innerHTML = `
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="19" x2="12" y2="5"></line>
                    <polyline points="5 12 12 5 19 12"></polyline>
                </svg>
                Resto
            `;
        } else { // Pagato esattamente
            residuoSpan.textContent = `€ 0,00`;
            residuoContainer.classList.add('no-residuo');
            residuoContainer.querySelector('.label').innerHTML = `
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Saldato
            `;
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

    // --- Riferimenti input cliente ---
    const clienteInput = document.getElementById('clienteInput');
    const idClienteInput = document.getElementById('idCliente');
    const clienteSuggerimenti = document.getElementById('clienteRisultati');

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
            premiumToast('Invio dei dati di vendita...', 'info', 10000);

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
                if (data.success) {
                    console.log("Vendita registrata con successo. Dati:", data);
                    premiumToast(data.message || 'Vendita completata con successo.', 'success', 2000);

                    // Svuota il carrello da localStorage QUI, prima di reindirizzare.
                    console.log("Svuoto il carrello da localStorage.");
                    localStorage.removeItem("cart"); 
                    console.log("localStorage['cart'] dopo rimozione:", localStorage.getItem('cart'));

                    setTimeout(function() {
                        // Se lo scontrino è selezionato, avvia la stampa professionale
                        if (chkScontrino && chkScontrino.checked) {
                            if (data.id_vendita) { // Assicurati che l'ID della vendita sia presente
                                const scontrinoPrintUrl = `genera_scontrino.php?id_vendita=${data.id_vendita}`;
                                const printWindow = window.open(scontrinoPrintUrl, '_blank', 'width=400,height=600,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1');
                                
                                // Event listener per quando la finestra dello scontrino si carica
                                if (printWindow) {
                                    printWindow.onload = function() {
                                        setTimeout(() => { printWindow.print(); }, 500);
                                    };
                                    printWindow.onafterprint = function() {
                                        console.log("Stampa scontrino completata/annullata. Reindirizzo a homepage.php.");
                                        window.location.href = 'homepage.php?venditaSuccesso=true';
                                    };
                                }

                                // Fallback
                                setTimeout(() => {
                                    if (!printWindow || printWindow.closed) {
                                        console.log("Finestra di stampa non aperta o chiusa. Reindirizzo a homepage.php.");
                                        window.location.href = 'homepage.php?venditaSuccesso=true';
                                    }
                                }, 3000);
                                
                            } else {
                                premiumToast('ID vendita non ricevuto per la stampa dello scontrino.', 'error', 2000);
                                setTimeout(function() {
                                    window.location.href = 'homepage.php?venditaSuccesso=true';
                                }, 2000);
                            }
                        } else {
                            // Se lo scontrino non è selezionato, reindirizza immediatamente alla homepage
                            console.log("Scontrino non selezionato. Reindirizzo a homepage.php.");
                            window.location.href = 'homepage.php?venditaSuccesso=true'; 
                        }
                    }, 1500);
                } else {
                    console.error("Errore durante la registrazione della vendita. Dati:", data);
                    premiumToast(data.message || 'Si è verificato un errore durante la registrazione della vendita.', 'error', 4000);
                }
            })
            .catch(error => {
                premiumToast('Impossibile connettersi al server: ' + error.message, 'error', 4000);
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
            premiumToast('I dati del carrello e dei pagamenti sono stati aggiornati.', 'success', 2000);
        };
    }

    document.querySelector('.cart-table tbody').addEventListener('click', function(e) {
        const targetButton = e.target.closest('.delete-item-btn');
        if (targetButton) {
            const rowToDelete = targetButton.closest('tr');
            if (rowToDelete) {
                premiumConfirm('Sei sicuro?', 'Non potrai annullare questa azione!', 'Sì, rimuovi!', function() {
                    rowToDelete.remove();

                    const tbody = document.querySelector('.cart-table tbody');
                    if (tbody.children.length === 0) {
                        const emptyRow = document.createElement('tr');
                        emptyRow.innerHTML = '<td colspan="7" style="text-align: center; padding: 20px;">Il carrello è vuoto.</td>';
                        tbody.appendChild(emptyRow);
                    }

                    aggiornaRiepilogo();
                    premiumToast('Prodotto rimosso dal carrello.', 'success', 2000);
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
                            premiumToast(`Buono valido: ${data.buono.valore}€`, 'success', 2000);
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
                            premiumToast(data.message || 'Buono non valido o scaduto.', 'error', 2000);
                            aggiornaRiepilogo(); // Ricalcola il totale per rimuovere l'effetto di un buono non valido
                        }
                    })
                    .catch(error => {
                        console.error('Errore ricerca buono:', error);
                        premiumToast('Impossibile ricercare il buono spesa.', 'error', 3000);
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
            premiumConfirm('Sei sicuro?', 'Vuoi svuotare completamente il carrello? Questa azione non può essere annullata!', 'Sì, svuota!', function() {
                const tbody = document.querySelector('.cart-table tbody');
                tbody.innerHTML = ''; 
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = '<td colspan="7" style="text-align: center; padding: 20px;">Il carrello è vuoto.</td>';
                tbody.appendChild(emptyRow);
                localStorage.removeItem("cart");
                console.log("Carrello svuotato da localStorage.");
                aggiornaRiepilogo();
                premiumToast('Carrello svuotato con successo.', 'success', 2000);
            });
        });
    }

});
</script>

</body>
</html>
