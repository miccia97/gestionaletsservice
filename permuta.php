<?php
session_start();
include 'auth_check.php';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Nuova Permuta | TS Service</title>
    <!-- Font Awesome per le icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/header-styles.css?v=<?php echo time(); ?>">
    <style>
        /* CSS per lo stile della pagina - Design Migliorato */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f4f8; /* Sfondo leggero e moderno */
            margin: 0;
            padding: 20px;
            padding-top: 100px; /* Spazio per header fisso */
            color: #334155; /* Colore testo scuro ma morbido */
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px; /* Larghezza maggiore per contenere più contenuto */
            margin: 30px auto;
            background-color: #ffffff;
            padding: 40px; /* Più padding interno */
            border-radius: 16px; /* Angoli più arrotondati */
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1); /* Ombra più definita */
            width: 100%;
            box-sizing: border-box;
        }

        h1 {
            text-align: center;
            color: #22c55e; /* Blu primario più vibrante */
            margin-bottom: 40px;
            font-size: 2.5em; /* Dimensione più grande */
            font-weight: 900; /* Più audace */
            letter-spacing: -0.02em;
        }

        fieldset {
            border: 1px solid #e2e8f0; /* Bordo sottile */
            border-radius: 12px; /* Angoli arrotondati */
            padding: 30px; /* Più padding */
            margin-bottom: 35px;
            background-color: #f8fafc; /* Leggermente grigio chiaro per i fieldset */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.04); /* Ombra interna delicata */
        }

        legend {
            font-size: 1.4em; /* Legenda più grande */
            font-weight: 700; /* Più audace */
            color: #16a34a; /* Blu scuro per la legenda */
            padding: 0 20px;
            border-bottom: none;
            width: auto;
            background-color: #f8fafc;
            border-radius: 8px;
            transform: translateY(-50%);
            margin-left: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 25px; /* Più spazio tra i gruppi */
        }

        .form-group label {
            display: block;
            margin-bottom: 10px; /* Più spazio sotto la label */
            font-weight: 600;
            color: #475569;
            font-size: 0.95em;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 14px 16px; /* Più padding per gli input */
            border: 1px solid #cbd5e0;
            border-radius: 10px; /* Angoli più arrotondati */
            font-size: 1em;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background-color: #ffffff;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group input[type="date"]:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #22c55e; /* Blu primario al focus */
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.25); /* Anello di focus blu */
            outline: none;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px; /* Altezza minima maggiore */
        }

        .form-group input[type="file"] {
            padding: 10px 0;
            background-color: #edf2f7;
            border-radius: 10px;
            border: 1px solid #a0aec0;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .form-group input[type="file"]:hover {
            background-color: #e2e8f0;
        }

        h4 {
            margin-top: 40px;
            margin-bottom: 25px;
            color: #22c55e;
            border-bottom: 2px solid #e0f2ff; /* Bordo più spesso */
            padding-bottom: 10px;
            font-size: 1.35em;
            font-weight: 700;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: separate; /* Per gli angoli arrotondati della tabella */
            border-spacing: 0;
            margin-bottom: 20px;
            background-color: #ffffff;
            border-radius: 10px;
        }

        table th,
        table td {
            border: 1px solid #e2e8f0;
            padding: 14px; /* Più padding */
            text-align: left;
            vertical-align: middle; /* Allinea al centro verticalmente */
            font-size: 0.95em;
        }

        table th {
            background-color: #e0f2ff; /* Sfondo per l'intestazione */
            font-weight: 700;
            color: #16a34a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        table thead tr:first-child th:first-child { border-top-left-radius: 10px; }
        table thead tr:first-child th:last-child { border-top-right-radius: 10px; }
        table tbody tr:last-child td:first-child { border-bottom-left-radius: 10px; }
        table tbody tr:last-child td:last-child { border-bottom-right-radius: 10px; }


        table tbody tr:nth-child(even) {
            background-color: #f7fafc;
        }

        table input[type="text"],
        table select {
            width: calc(100% - 10px);
            padding: 10px;
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            box-sizing: border-box;
            background-color: #ffffff;
        }
        table input[type="text"]:focus,
        table select:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
            outline: none;
        }

        table input[type="checkbox"] {
            margin-right: 8px;
            transform: scale(1.3); /* Rende la checkbox più visibile */
            vertical-align: middle;
            accent-color: #22c55e; /* Colore blu per la checkbox */
        }

        .costo-item {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            align-items: center;
            flex-wrap: wrap; /* Per responsività */
        }

        .costo-item input[type="text"] {
            flex-grow: 2;
            min-width: 180px; /* Min-width per desktop */
        }

        .costo-item input[type="number"] {
            width: 150px; /* Larghezza fissa per l'importo */
            text-align: right;
            min-width: 100px;
        }

        .remove-costo-btn {
            background-color: #dc3545; /* Rosso per eliminare */
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9em;
            white-space: nowrap;
            transition: background-color 0.2s ease, transform 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .remove-costo-btn:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }

        #add_costo_btn {
            background-color: #28a745; /* Verde per aggiungere */
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.05em;
            margin-top: 15px;
            transition: background-color 0.2s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        #add_costo_btn:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px dashed #e2e8f0; /* Linea tratteggiata */
        }

        .summary-line:last-of-type:not(.total) {
            border-bottom: none;
        }

        .summary-line label {
            font-weight: 600;
            color: #475569;
            flex-grow: 1;
            font-size: 1em;
        }

        .summary-line span {
            font-weight: 700;
            color: #22c55e;
            text-align: right;
            min-width: 120px;
            font-size: 1.1em;
        }

        .summary-line.highlight {
            background-color: #e0f7fa; /* Azzurro molto chiaro */
            border-radius: 10px;
            padding: 15px 20px;
            margin-top: 20px;
            border: 1px solid #b2ebf2;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .summary-line.highlight label,
        .summary-line.highlight span {
            color: #00838f; /* Blu verde per highlight */
            font-size: 1.25em;
        }

        .summary-line.total {
            background-color: #e6ffed; /* Verde chiaro per il totale */
            border: 1px solid #a7d9b9;
            border-radius: 12px;
            padding: 25px 30px;
            margin-top: 30px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .summary-line.total label,
        .summary-line.total span {
            font-size: 1.8em; /* Grande per il totale */
            color: #1e8449; /* Verde scuro per il totale */
            font-weight: 900;
        }

        .form-actions {
            text-align: center;
            margin-top: 50px; /* Più spazio sopra i bottoni */
            display: flex;
            justify-content: center;
            gap: 25px; /* Più spazio tra i bottoni */
            flex-wrap: wrap;
        }

        .form-actions button {
            padding: 15px 35px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.15em;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            background-image: linear-gradient(to right, #22c55e 0%, #16a34a 100%); /* Gradiente per submit */
            color: white;
        }

        .form-actions button[type="submit"] {
             background-image: linear-gradient(to right, #22c55e 0%, #16a34a 100%);
        }

        .form-actions button[type="submit"]:hover {
            background-image: linear-gradient(to right, #16a34a 0%, #14532d 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        .form-actions button:not([type="submit"]) {
            background-color: #6c757d; /* Grigio neutro */
            background-image: none; /* Rimuovi gradiente per altri bottoni */
            color: white;
        }

        .form-actions button:not([type="submit"]):hover {
            background-color: #5a6268;
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        #stampa_riepilogo_btn {
            background-color: #17a2b8; /* Blu-verde per stampa */
        }

        #stampa_riepilogo_btn:hover {
            background-color: #138496;
        }

        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 20px; /* Più spazio tra le immagini */
            margin-top: 20px;
            border: 2px dashed #a0aec0; /* Bordo più visibile */
            padding: 15px;
            border-radius: 10px;
            background-color: #f7fafc;
            min-height: 80px; /* Altezza minima maggiore */
            align-items: center;
            justify-content: center;
            color: #718096;
            font-style: italic;
        }

        .image-preview img {
            max-width: 150px; /* Immagini leggermente più grandi */
            max-height: 150px;
            object-fit: cover;
            border: 3px solid #22c55e; /* Bordo colorato */
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .image-preview img:hover {
            transform: scale(1.08);
            border-color: #28a745; /* Cambia colore al hover */
        }

        .image-preview:empty::before {
            content: 'Nessuna immagine selezionata';
            font-style: italic;
            color: #a0aec0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 20px;
                margin: 10px auto;
            }

            h1 {
                font-size: 2em;
                margin-bottom: 30px;
            }

            fieldset {
                padding: 15px;
                margin-bottom: 25px;
            }

            legend {
                font-size: 1.2em;
                padding: 0 10px;
                margin-left: 5px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group input[type="text"],
            .form-group input[type="number"],
            .form-group input[type="date"],
            .form-group textarea,
            .form-group select {
                padding: 10px 12px;
                border-radius: 8px;
            }

            h4 {
                margin-top: 30px;
                margin-bottom: 15px;
                font-size: 1.15em;
            }

            table th,
            table td {
                padding: 10px;
                font-size: 0.85em;
            }

            .costo-item {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .costo-item input[type="number"] {
                width: 100%;
            }

            .remove-costo-btn {
                width: 100%;
                padding: 10px;
            }

            #add_costo_btn {
                width: 100%;
                padding: 12px;
            }

            .summary-line label,
            .summary-line span {
                font-size: 0.9em;
            }

            .summary-line.highlight label,
            .summary-line.highlight span {
                font-size: 1.1em;
            }

            .summary-line.total label,
            .summary-line.total span {
                font-size: 1.4em;
            }

            .form-actions {
                flex-direction: column;
                gap: 15px;
                margin-top: 30px;
            }

            .form-actions button {
                width: 100%;
                padding: 12px;
            }
        }

        /* Stili per il Modal/Popup */
        .modal-overlay {
            display: none; /* Nascosto di default */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6); /* Sfondo semi-trasparente */
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: #ffffff;
            padding: 20px; /* Ridotto il padding */
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 550px; /* Larghezza massima del popup ridotta */
            position: relative;
            animation: fadeIn 0.3s ease-out;
            max-height: 90vh; /* Altezza massima per scorrimento su schermi piccoli */
            overflow-y: auto; /* Abilita lo scroll se il contenuto è troppo lungo */
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 20px; /* Ridotto il margine inferiore */
        }

        .modal-header h3 {
            margin: 0;
            color: #22c55e;
            font-size: 1.4em; /* Dimensione ridotta */
            font-weight: 700;
        }

        .close-button {
            background: none;
            border: none;
            font-size: 1.6em; /* Dimensione ridotta */
            color: #6c757d;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .close-button:hover {
            color: #dc3545;
        }

        .modal-body .form-group {
            margin-bottom: 15px; /* Ridotto il margine inferiore */
        }

        .modal-body label {
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }

        .modal-body input[type="text"],
        .modal-body input[type="email"],
        .modal-body textarea {
            width: 100%;
            padding: 10px 12px; /* Ridotto il padding */
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            font-size: 0.95em; /* Dimensione del font leggermente ridotta */
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .modal-body input[type="text"]:focus,
        .modal-body input[type="email"]:focus,
        .modal-body textarea:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
            outline: none;
        }
        
        .modal-body h4 {
            margin-top: 25px; /* Ridotto il margine superiore */
            margin-bottom: 10px; /* Ridotto il margine inferiore */
            color: #22c55e;
            border-bottom: 1px solid #e0f2ff;
            padding-bottom: 5px; /* Ridotto il padding */
            font-size: 1.1em; /* Dimensione del font leggermente ridotta */
            font-weight: 600;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px; /* Ridotto il gap */
            margin-top: 20px; /* Ridotto il margine superiore */
            padding-top: 15px; /* Ridotto il padding */
            border-top: 1px solid #e2e8f0;
        }

        .modal-footer button {
            padding: 10px 20px; /* Ridotto il padding */
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9em; /* Dimensione del font leggermente ridotta */
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-footer .save-button {
            background-color: #28a745;
            color: white;
        }

        .modal-footer .save-button:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }

        .modal-footer .cancel-button {
            background-color: #6c757d;
            color: white;
        }

        .modal-footer .cancel-button:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }

        /* Stile per l'icona "Aggiungi Cliente" */
        .client-input-container {
            position: relative; /* Necessario per posizionare i suggerimenti */
            display: flex;
            align-items: center;
            gap: 10px; /* Spazio tra input e icona */
        }

        .client-input-container input[type="text"] {
            flex-grow: 1; /* Permette all'input di occupare lo spazio disponibile */
        }

        .add-client-icon {
            font-size: 1.5em; /* Dimensione dell'icona */
            color: #28a745; /* Colore verde */
            cursor: pointer;
            transition: color 0.2s ease, transform 0.2s ease;
            padding: 8px; /* Per un'area cliccabile più grande */
            border-radius: 50%; /* Rende l'icona rotonda */
            background-color: #e6ffed; /* Sfondo leggero */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
        }

        .add-client-icon:hover {
            color: #218838;
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
        }

        /* Animazioni */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }

        /* Stili per i tab */
        .tab-buttons {
            display: flex;
            justify-content: center;
            margin-bottom: 20px; /* Ridotto il margine inferiore */
            gap: 8px; /* Ridotto il gap */
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
        }

        .tab-button {
            background-color: #e9ecef;
            border: 1px solid #dee2e6;
            padding: 10px 15px; /* Ridotto il padding */
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-size: 0.9em; /* Dimensione del font leggermente ridotta */
            font-weight: 600;
            color: #495057;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
            white-space: nowrap; /* Evita che il testo vada a capo */
        }

        .tab-button.active {
            background-color: #22c55e;
            color: white;
            border-color: #22c55e;
            border-bottom-color: transparent; /* Nasconde il bordo inferiore della scheda attiva */
        }

        .tab-button:hover:not(.active) {
            background-color: #e2f0ff;
            color: #16a34a;
        }

        .tab-content {
            display: none; /* Nasconde tutte le schede di contenuto di default */
            padding-top: 10px; /* Ridotto il padding */
            animation: fadeIn 0.3s ease-out; /* Animazione per la comparsa */
        }

        .tab-content.active {
            display: block; /* Mostra solo la scheda attiva */
        }

        /* Stili per il suggerimento prodotti personalizzato */
        .product-input-wrapper, .client-input-wrapper { /* Generalizzato per i suggerimenti */
            position: relative;
        }

        #product_suggestions, #client_suggestions {
            position: absolute;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            background-color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            z-index: 10;
            display: none; /* Nascosto di default */
            margin-top: 5px; /* Piccolo spazio tra input e suggerimenti */
        }

        .product-suggestion-item, .client-suggestion-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f4f8;
            transition: background-color 0.2s ease;
            font-size: 0.95em;
            color: #334155;
            display: flex;
            justify-content: space-between; /* Per allineare nome e telefono */
            align-items: center;
        }

        .product-suggestion-item:last-child, .client-suggestion-item:last-child {
            border-bottom: none;
        }

        .product-suggestion-item:hover, .client-suggestion-item:hover {
            background-color: #e2f0ff;
        }

        .product-suggestion-item .model-name, .client-suggestion-item .client-name {
            font-weight: 600;
            color: #22c55e;
        }

        .product-suggestion-item .imei-info, .client-suggestion-item .phone-info {
            font-size: 0.8em;
            color: #6c757d;
            margin-left: 10px;
            white-space: nowrap; /* Impedisce al telefono di andare a capo */
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <h1>Gestione Nuova Permuta</h1>

        <!-- Il form punta allo script PHP che gestirà il salvataggio nel database -->
        <form action="salva_permuta.php" method="POST" enctype="multipart/form-data">

            <!-- 1. Dettagli Generali della Permuta -->
            <fieldset>
                <legend>1. Dettagli Generali della Permuta</legend>
                <div class="form-group">
                    <label for="numero_permuta">Numero Permuta:</label>
                    <!-- Questo valore sarà pre-compilato da JavaScript, simulando un ID generato lato server -->
                    <input type="text" id="numero_permuta" name="numero_permuta_display" value="Automatico al Salvataggio" readonly>
                    <!-- Campo nascosto per inviare solo il numero progressivo al server -->
                    <input type="hidden" id="numero_progressivo" name="numero_progressivo">
                </div>
                <div class="form-group">
                    <label for="data_permuta">Data Permuta:</label>
                    <!-- Questo valore sarà pre-compilato da JavaScript -->
                    <input type="date" id="data_permuta" name="data_permuta" required>
                </div>
                
                <!-- Campo per cliente esistente con icona per aggiungere nuovo -->
                <div class="form-group">
                    <label for="cliente">Cliente:</label>
                    <div class="client-input-container">
                        <!-- Rimosso datalist -->
                        <input type="text" id="cliente" name="cliente" placeholder="Cerca o seleziona cliente" autocomplete="off" required>
                        <!-- Campo nascosto per l'ID del cliente -->
                        <input type="hidden" id="cliente_id" name="cliente_id">
                        <!-- Contenitore per i suggerimenti cliente personalizzati -->
                        <div id="client_suggestions"></div>
                        <i class="fas fa-plus-circle add-client-icon" id="open_new_client_modal_btn" title="Aggiungi nuovo cliente"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="telefono_cliente">Telefono Cliente:</label>
                    <!-- Questo campo verrà popolato sia dal cliente esistente che dal nuovo cliente -->
                    <input type="text" id="telefono_cliente" name="telefono_cliente" placeholder="Es: 3331234567" pattern="[0-9]{10,15}" title="Inserisci un numero di telefono valido (10-15 cifre)">
                </div>
                <div class="form-group">
                    <label for="stato_permuta">Stato Permuta:</label>
                    <select id="stato_permuta" name="stato_permuta" required>
                        <option value="In Trattativa">In Trattativa</option>
                        <option value="Accettata">Accettata</option>
                        <option value="Rifiutata">Rifiutata</option>
                        <option value="Completata">Completata</option>
                        <option value="Annullata">Annullata</option>
                    </select>
                </div>
            </fieldset>

            <!-- 2. Il Tuo Prodotto (Ceduto al Cliente) -->
            <fieldset>
                <legend>2. Il Tuo Prodotto (Ceduto al Cliente)</legend>
                <div class="form-group">
                    <label for="tuo_modello">Modello:</label>
                    <!-- Wrapper per il campo di input e i suggerimenti -->
                    <div class="product-input-wrapper">
                        <input type="text" id="tuo_modello" name="tuo_modello" placeholder="Cerca o inserisci modello" autocomplete="off" required>
                        <!-- Contenitore per i suggerimenti prodotti -->
                        <div id="product_suggestions"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="tuo_imei">IMEI / Seriale:</label>
                    <input type="text" id="tuo_imei" name="tuo_imei">
                </div>
                <div class="form-group">
                    <label for="tuo_valore_vendita">Valore di Vendita (€):</label>
                    <input type="number" id="tuo_valore_vendita" name="tuo_valore_vendita" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="tuo_note">Note Prodotto Ceduto:</label>
                    <textarea id="tuo_note" name="tuo_note" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="tuo_foto">Allegati / Foto:</label>
                    <input type="file" id="tuo_foto" name="tuo_foto[]" multiple accept="image/*">
                    <div id="tuo_foto_preview" class="image-preview"></div>
                </div>
            </fieldset>

            <!-- 3. Prodotto del Cliente (Ricevuto in Permuta) -->
            <fieldset>
                <legend>3. Prodotto del Cliente (Ricevuto in Permuta)</legend>
                <div class="form-group">
                    <label for="cliente_modello">Modello:</label>
                    <input type="text" id="cliente_modello" name="cliente_modello" required>
                </div>
                <div class="form-group">
                    <label for="cliente_imei">IMEI / Seriale:</label>
                    <input type="text" id="cliente_imei" name="cliente_imei">
                </div>
                <div class="form-group">
                    <label for="cliente_note">Note Generali Prodotto Ricevuto:</label>
                    <textarea id="cliente_note" name="cliente_note" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="cliente_valore_permuta">Valore Permuta Proposto (€):</label>
                    <input type="number" id="cliente_valore_permuta" name="cliente_valore_permuta" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="cliente_foto">Allegati / Foto:</label>
                    <input type="file" id="cliente_foto" name="cliente_foto[]" multiple accept="image/*">
                    <div id="cliente_foto_preview" class="image-preview"></div>
                </div>

                <!-- Tabella Valutazione Tecnica del Dispositivo -->
                <h4>Tabella Valutazione Tecnica del Dispositivo</h4>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Componente/Funzionalità</th>
                                <th>Esito Test</th>
                                <th>Note Tecniche / Problemi Rilevati</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Display</td>
                                <td>
                                    <select name="test_display_esito">
                                        <option value="Funzionante">Funzionante</option>
                                        <option value="Danneggiato">Danneggiato</option>
                                        <option value="Guasto">Guasto</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_display_note"></td>
                            </tr>
                            <tr>
                                <td>Touchscreen</td>
                                <td>
                                    <select name="test_touch_esito">
                                        <option value="Funzionante">Funzionante</option>
                                        <option value="Parzialmente Funzionante">Parzialmente Funzionante</option>
                                        <option value="Non Funzionante">Non Funzionante</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_touch_note"></td>
                            </tr>
                            <tr>
                                <td>Batteria</td>
                                <td>
                                    <select name="test_batteria_esito">
                                        <option value="Ottima">Ottima</option>
                                        <option value="Buona">Buona</option>
                                        <option value="Scarso">Scarso</option>
                                        <option value="Da Sostituire">Da Sostituire</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_batteria_note" placeholder="% salute, cicli"></td>
                            </tr>
                            <tr>
                                <td>Fotocamera Posteriore</td>
                                <td>
                                    <select name="test_cam_post_esito">
                                        <option value="Funzionante">Funzionante</option>
                                        <option value="Difettosa">Difettosa</option>
                                        <option value="Non Funzionante">Non Funzionante</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_cam_post_note"></td>
                            </tr>
                            <tr>
                                <td>Fotocamera Anteriore</td>
                                <td>
                                    <select name="test_cam_ant_esito">
                                        <option value="Funzionante">Funzionante</option>
                                        <option value="Difettosa">Difettosa</option>
                                        <option value="Non Funzionante">Non Funzionante</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_cam_ant_note"></td>
                            </tr>
                            <tr>
                                <td>Audio (Altoparlanti)</td>
                                <td>
                                    <select name="test_audio_esito">
                                        <option value="Funzionante">Funzionante</option>
                                        <option value="Distorto">Distorto</option>
                                        <option value="Non Funzionante">Non Funzionante</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_audio_note"></td>
                            </tr>
                            <tr>
                                <td>Microfono</td>
                                <td>
                                    <select name="test_mic_esito">
                                        <option value="Funzionante">Funzionante</option>
                                        <option value="Difettoso">Difettoso</option>
                                        <option value="Non Funzionante">Non Funzionante</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_mic_note"></td>
                            </tr>
                             <tr>
                                <td>Connettività Wi-Fi</td>
                                <td>
                                    <select name="test_wifi_esito">
                                        <option value="Funzionante">Funzionante</option>
                                        <option value="Instabile">Instabile</option>
                                        <option value="Non Funzionante">Non Funzionante</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_wifi_note"></td>
                            </tr>
                            <tr>
                                <td>Connettività Bluetooth</td>
                                <td>
                                    <select name="test_bt_esito">
                                        <option value="Funzionante">Funzionante</option>
                                        <option value="Instabile">Instabile</option>
                                        <option value="Non Funzionante">Non Funzionante</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_bt_note"></td>
                            </tr>
                            <tr>
                                <td>Porta di Ricarica</td>
                                <td>
                                    <select name="test_ricarica_esito">
                                        <option value="Funzionante">Funzionante</option>
                                        <option value="Difettosa">Difettosa</option>
                                        <option value="Non Funzionante">Non Funzionante</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_ricarica_note"></td>
                            </tr>
                            <tr>
                                <td>Tasti Fisici (Volume, Accensione)</td>
                                <td>
                                    <select name="test_tasti_esito">
                                        <option value="Funzionante">Funzionante</option>
                                        <option value="Bloccati">Bloccati</option>
                                        <option value="Non Funzionante">Non Funzionante</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_tasti_note"></td>
                            </tr>
                            <tr>
                                <td>Sensori (Prossimità, Luminosità, ecc.)</td>
                                <td>
                                    <select name="test_sensori_esito">
                                        <option value="Funzionante">Funzionante</option>
                                        <option value="Parzialmente Funzionante">Parzialmente Funzionante</option>
                                        <option value="Non Funzionante">Non Funzionante</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_sensori_note"></td>
                            </tr>
                            <tr>
                                <td>Sblocco Biometrico (Impronta/Facciale)</td>
                                <td>
                                    <select name="test_sblocco_bio_esito">
                                        <option value="Funzionante">Funzionante</option>
                                        <option value="Non Funzionante">Non Funzionante</option>
                                        <option value="Non Applicabile">Non Applicabile</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_sblocco_bio_note"></td>
                            </tr>
                             <tr>
                                <td>Reset di Fabbrica Eseguito</td>
                                <td>
                                    <input type="checkbox" name="test_reset_fabbrica" value="Si"> Sì
                                </td>
                                <td><input type="text" name="test_reset_fabbrica_note"></td>
                            </tr>
                             <tr>
                                <td>Account Collegati</td>
                                <td>
                                    <select name="test_accounts_esito">
                                        <option value="Liberi">Liberi</option>
                                        <option value="Presenti">Presenti</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_accounts_note" placeholder="Es: Account Google, iCloud, ecc."></td>
                            </tr>
                             <tr>
                                <td>Altro (Specificare)</td>
                                <td>
                                    <select name="test_altro_esito">
                                        <option value="N/A">N/A</option>
                                        <option value="Funzionante">Funzionante</option>
                                        <option value="Difettoso">Difettoso</option>
                                    </select>
                                </td>
                                <td><input type="text" name="test_altro_note"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </fieldset>

            <!-- 4. Calcoli e Conguaglio Finale -->
            <fieldset>
                <legend>4. Calcoli e Conguaglio Finale</legend>
                <h4>Costi di Ricondizionamento / Riparazione Previsti</h4>
                <div id="costi_ricondizionamento_container">
                    <div class="costo-item">
                        <input type="text" name="costo_descrizione[]" placeholder="Descrizione Costo">
                        <input type="number" name="costo_importo[]" step="0.01" min="0" class="costo-importo" value="0">
                        <button type="button" class="remove-costo-btn">Rimuovi</button>
                    </div>
                </div>
                <button type="button" id="add_costo_btn">Aggiungi Costo</button>

                <div class="summary-line">
                    <label>Totale Costi di Ricondizionamento:</label>
                    <span id="totale_costi_ricondizionamento">€ 0.00</span>
                    <!-- Campo nascosto per inviare al server -->
                    <input type="hidden" id="totale_costi_ricondizionamento_val" name="totale_costi_ricondizionamento_val">
                </div>

                <!-- Nuovi campi per costi accessori e costo prodotto -->
                <div class="form-group">
                    <label for="costo_accessori_input">Costo Accessori (€):</label>
                    <input type="number" id="costo_accessori_input" name="costo_accessori_input" step="0.01" min="0" value="0">
                </div>
                <div class="form-group">
                    <label for="costo_prodotto_input">Costo Prodotto (€):</label>
                    <input type="number" id="costo_prodotto_input" name="costo_prodotto_input" step="0.01" min="0" value="0">
                </div>
                <div class="form-group">
                    <label for="prezzo_vendita_input">Prezzo di Vendita Finale (€):</label>
                    <input type="number" id="prezzo_vendita_input" name="prezzo_vendita_input" step="0.01" min="0" value="0">
                </div>
                <div class="form-group">
                    <label for="note_generali_input">Note Generali Aggiuntive:</label>
                    <textarea id="note_generali_input" name="note_generali_input" rows="3" placeholder="Aggiungi qui eventuali note generali..."></textarea>
                </div>
                
                <div class="summary-line">
                    <label>Valore di Vendita Prodotto Ceduto:</label>
                    <span id="valore_vendita_ceduto">€ 0.00</span>
                </div>
                <div class="summary-line">
                    <label>Valore Permuta Prodotto Ricevuto (Iniziale):</label>
                    <span id="valore_permuta_ricevuto">€ 0.00</span>
                </div>
                <div class="summary-line highlight">
                    <label>Valore Netto Prodotto Ricevuto:</label>
                    <span id="valore_netto_ricevuto">€ 0.00</span>
                    <!-- Campo nascosto per inviare al server -->
                    <input type="hidden" id="valore_netto_ricevuto_val" name="valore_netto_ricevuto_val">
                </div>
                <div class="summary-line total">
                    <label>Conguaglio Cliente:</label>
                    <span id="conguaglio_cliente">€ 0.00</span>
                    <!-- Campo nascosto per inviare al server -->
                    <input type="hidden" id="conguaglio_cliente_val" name="conguaglio_cliente_val">
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" name="salva_permuta">Salva Permuta</button>
                <button type="button" onclick="alert('Funzionalità Annulla - In un sistema reale, verresti reindirizzato alla dashboard o alla lista permute.');">Annulla</button>
                <button type="button" id="stampa_riepilogo_btn">Stampa Riepilogo</button>
            </div>
        </form>
    </div>

    <!-- Modal per l'aggiunta di un nuovo cliente -->
    <div id="new_client_modal_overlay" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Aggiungi Nuovo Cliente</h3>
                <button type="button" class="close-button" id="close_new_client_modal_btn">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Tab Buttons -->
                <div class="tab-buttons">
                    <button type="button" class="tab-button active" data-tab="personal_data_tab">Dati Personali</button>
                    <button type="button" class="tab-button" data-tab="company_data_tab">Dati Aziendali</button>
                </div>

                <!-- Tab Content: Dati Personali -->
                <div id="personal_data_tab" class="tab-content active">
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_nome">Nome:</label>
                        <input type="text" id="modal_nuovo_cliente_nome" placeholder="Nome" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_cognome">Cognome:</label>
                        <input type="text" id="modal_nuovo_cliente_cognome" placeholder="Cognome" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_telefono">Telefono:</label>
                        <input type="text" id="modal_nuovo_cliente_telefono" placeholder="Es: 3331234567" pattern="[0-9]{10,15}" title="Inserisci un numero di telefono valido (10-15 cifre)">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_email">Email:</label>
                        <input type="email" id="modal_nuovo_cliente_email" placeholder="nome@esempio.com">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_indirizzo">Indirizzo:</label>
                        <input type="text" id="modal_nuovo_cliente_indirizzo" placeholder="Via Roma, 1">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_citta">Città:</label>
                        <input type="text" id="modal_nuovo_cliente_citta" placeholder="Roma">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_note">Note:</label>
                        <textarea id="modal_nuovo_cliente_note" rows="3" placeholder="Note aggiuntive sul cliente"></textarea>
                    </div>
                </div>

                <!-- Tab Content: Dati Aziendali -->
                <div id="company_data_tab" class="tab-content">
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_ragione_sociale">Ragione Sociale:</label>
                        <input type="text" id="modal_nuovo_cliente_ragione_sociale" placeholder="Nome S.p.A.">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_partita_iva">Partita IVA:</label>
                        <input type="text" id="modal_nuovo_cliente_partita_iva" placeholder="IT12345678901">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_indirizzo_azienda">Indirizzo Azienda:</label>
                        <input type="text" id="modal_nuovo_cliente_indirizzo_azienda" placeholder="Via dell'Industria, 5">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_citta_azienda">Città Azienda:</label>
                        <input type="text" id="modal_nuovo_cliente_citta_azienda" placeholder="Milano">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_telefono_azienda">Telefono Azienda:</label>
                        <input type="text" id="modal_nuovo_cliente_telefono_azienda" placeholder="Es: 0212345678" pattern="[0-9]{10,15}" title="Inserisci un numero di telefono valido (10-15 cifre)">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_email_azienda">Email Azienda:</label>
                        <input type="email" id="modal_nuovo_cliente_email_azienda" placeholder="info@azienda.com">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_note_azienda">Note Azienda:</label>
                        <textarea id="modal_nuovo_cliente_note_azienda" rows="3" placeholder="Note aggiuntive sull'azienda"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-button" id="cancel_new_client_modal_btn">Annulla</button>
                <button type="button" class="save-button" id="save_new_client_btn">Salva Cliente</button>
            </div>
        </div>
    </div>

    <script>
        // JavaScript per l'interattività lato client
        document.addEventListener('DOMContentLoaded', function() {
            const numeroPermutaDisplay = document.getElementById('numero_permuta');
            const numeroProgressivoInput = document.getElementById('numero_progressivo'); // Hidden field
            const dataPermutaInput = document.getElementById('data_permuta');
            const tuoValoreVenditaInput = document.getElementById('tuo_valore_vendita');
            const clienteValorePermutaInput = document.getElementById('cliente_valore_permuta');
            const costiContainer = document.getElementById('costi_ricondizionamento_container');
            const addCostoBtn = document.getElementById('add_costo_btn');

            const totaleCostiSpan = document.getElementById('totale_costi_ricondizionamento');
            const valoreVenditaCedutoSpan = document.getElementById('valore_vendita_ceduto');
            const valorePermutaRicevutoSpan = document.getElementById('valore_permuta_ricevuto');
            const valoreNettoRicevutoSpan = document.getElementById('valore_netto_ricevuto');
            const conguaglioClienteSpan = document.getElementById('conguaglio_cliente');

            // Hidden inputs for calculated values
            const totaleCostiValInput = document.getElementById('totale_costi_ricondizionamento_val');
            const valoreNettoRicevutoValInput = document.getElementById('valore_netto_ricevuto_val');
            const conguaglioClienteValInput = document.getElementById('conguaglio_cliente_val');

            // Campi cliente esistente e per l'autocompletamento
            const clienteInput = document.getElementById('cliente');
            const clienteIdInput = document.getElementById('cliente_id'); // Nuovo campo nascosto per l'ID del cliente
            const clientSuggestionsContainer = document.getElementById('client_suggestions'); // Nuovo contenitore suggerimenti clienti
            const telefonoClienteInput = document.getElementById('telefono_cliente');

            // Campi prodotti per autocompletamento
            const tuoModelloInput = document.getElementById('tuo_modello');
            const productSuggestionsContainer = document.getElementById('product_suggestions'); // Contenitore dei suggerimenti
            const tuoImeiInput = document.getElementById('tuo_imei'); // Campo IMEI

            // Elementi del Modal/Popup per nuovo cliente
            const openNewClientModalBtn = document.getElementById('open_new_client_modal_btn');
            const newClientModalOverlay = document.getElementById('new_client_modal_overlay');
            const closeNewClientModalBtn = document.getElementById('close_new_client_modal_btn');
            const cancelNewClientModalBtn = document.getElementById('cancel_new_client_modal_btn');
            const saveNewClientBtn = document.getElementById('save_new_client_btn');
            
            // Campi del modal per nuovo cliente (dati personali)
            const modalNuovoClienteNomeInput = document.getElementById('modal_nuovo_cliente_nome');
            const modalNuovoClienteCognomeInput = document.getElementById('modal_nuovo_cliente_cognome');
            const modalNuovoClienteTelefonoInput = document.getElementById('modal_nuovo_cliente_telefono');
            const modalNuovoClienteEmailInput = document.getElementById('modal_nuovo_cliente_email');
            const modalNuovoClienteIndirizzoInput = document.getElementById('modal_nuovo_cliente_indirizzo');
            const modalNuovoClienteCittaInput = document.getElementById('modal_nuovo_cliente_citta');
            const modalNuovoClienteNoteInput = document.getElementById('modal_nuovo_cliente_note');

            // Campi del modal per nuovo cliente (dati aziendali)
            const modalNuovoClienteRagioneSocialeInput = document.getElementById('modal_nuovo_cliente_ragione_sociale');
            const modalNuovoClientePartitaIvaInput = document.getElementById('modal_nuovo_cliente_partita_iva');
            const modalNuovoClienteIndirizzoAziendaInput = document.getElementById('modal_nuovo_cliente_indirizzo_azienda');
            const modalNuovoClienteCittaAziendaInput = document.getElementById('modal_nuovo_cliente_citta_azienda');
            const modalNuovoClienteTelefonoAziendaInput = document.getElementById('modal_nuovo_cliente_telefono_azienda');
            const modalNuovoClienteEmailAziendaInput = document.getElementById('modal_nuovo_cliente_email_azienda');
            const modalNuovoClienteNoteAziendaInput = document.getElementById('modal_nuovo_cliente_note_azienda');

            // Elementi per la gestione dei tab
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');


            // Variabili per tenere traccia dell'ultimo timeout di ricerca
            let searchClientTimeout = null;
            let searchProductTimeout = null;
            // Dati dei clienti e prodotti (popolati da AJAX)
            let currentClientOptions = [];
            let currentProductOptions = []; // Nuova variabile per i prodotti

            // --- Logica per il Numero Permuta (Simulazione JS) ---
            // Il numero progressivo NON viene generato all'avvio della pagina.
            // Verrà simulato un aggiornamento dopo un salvataggio riuscito del form.
            function updatePermutaProgressiveID_onSave(newId) {
                numeroProgressivoInput.value = newId; // Set hidden field
                numeroPermutaDisplay.value = `PMT-${String(newId).padStart(5, '0')}`; // Display formatted
            }

            // Imposta la data odierna
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0'); // Mesi da 0 a 11
            const day = String(today.getDate()).padStart(2, '0');
            dataPermutaInput.value = `${year}-${month}-${day}`;


            // Funzione per aggiornare tutti i calcoli
            function updateCalculations() {
                const tuoValoreVendita = parseFloat(tuoValoreVenditaInput.value) || 0;
                const clienteValorePermuta = parseFloat(clienteValorePermutaInput.value) || 0;

                let totaleCosti = 0;
                // Itera su tutti gli input con classe 'costo-importo'
                document.querySelectorAll('.costo-item .costo-importo').forEach(input => {
                    totaleCosti += parseFloat(input.value) || 0;
                });

                const valoreNettoRicevuto = clienteValorePermuta - totaleCosti;
                const conguaglioCliente = tuoValoreVendita - valoreNettoRicevuto;

                // Aggiorna gli span con i valori calcolati
                valoreVenditaCedutoSpan.textContent = `€ ${tuoValoreVendita.toFixed(2)}`;
                valorePermutaRicevutoSpan.textContent = `€ ${clienteValorePermuta.toFixed(2)}`;
                totaleCostiSpan.textContent = `€ ${totaleCosti.toFixed(2)}`;
                valoreNettoRicevutoSpan.textContent = `€ ${valoreNettoRicevuto.toFixed(2)}`;
                conguaglioClienteSpan.textContent = `€ ${conguaglioCliente.toFixed(2)}`;

                // Aggiorna i campi hidden per l'invio al server
                totaleCostiValInput.value = totaleCosti.toFixed(2);
                valoreNettoRicevutoValInput.value = valoreNettoRicevuto.toFixed(2);
                conguaglioClienteValInput.value = conguaglioCliente.toFixed(2);


                // Cambia colore del conguaglio se negativo (cliente deve ricevere denaro)
                if (conguaglioCliente < 0) {
                    conguaglioClienteSpan.style.color = '#dc3545'; // Rosso per negativo
                } else {
                    conguaglioClienteSpan.style.color = '#1e8449'; // Verde scuro per positivo o neutro
                }
            }

            // Listener per i campi di input che influenzano i calcoli
            tuoValoreVenditaInput.addEventListener('input', updateCalculations);
            clienteValorePermutaInput.addEventListener('input', updateCalculations);
            // Delega l'evento ai contenitori per i campi di costo dinamici
            costiContainer.addEventListener('input', function(event) {
                if (event.target.classList.contains('costo-importo')) {
                    updateCalculations();
                }
            });

            // Gestione aggiunta/rimozione costi
            addCostoBtn.addEventListener('click', function() {
                const newCostoItem = document.createElement('div');
                newCostoItem.classList.add('costo-item');
                newCostoItem.innerHTML = `
                    <input type="text" name="costo_descrizione[]" placeholder="Descrizione Costo">
                    <input type="number" name="costo_importo[]" step="0.01" min="0" class="costo-importo" value="0">
                    <button type="button" class="remove-costo-btn">Rimuovi</button>
                `;
                costiContainer.appendChild(newCostoItem);
                updateCalculations(); // Aggiorna i calcoli con il nuovo campo
            });

            // Listener per i pulsanti "Rimuovi" (delegato al container per elementi dinamici)
            costiContainer.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-costo-btn')) {
                    event.target.closest('.costo-item').remove();
                    updateCalculations(); // Aggiorna i calcoli dopo la rimozione
                }
            });

            // Funzione per l'anteprima delle immagini
            function setupImagePreview(inputId, previewId) {
                const input = document.getElementById(inputId);
                const preview = document.getElementById(previewId);

                input.addEventListener('change', function() {
                    preview.innerHTML = ''; // Pulisci le anteprime esistenti
                    if (this.files && this.files.length > 0) {
                        Array.from(this.files).forEach(file => {
                            if (file.type.startsWith('image/')) {
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    const img = document.createElement('img');
                                    img.src = e.target.result;
                                    img.alt = 'Anteprima immagine';
                                    preview.appendChild(img);
                                };
                                reader.readAsDataURL(file);
                            }
                        });
                    } else {
                        // Se non ci sono file, reimposta l'anteprima per mostrare il messaggio di placeholder
                        preview.innerHTML = '';
                    }
                });
            }

            setupImagePreview('tuo_foto', 'tuo_foto_preview');
            setupImagePreview('cliente_foto', 'cliente_foto_preview');

            // Funzione per la ricerca del cliente tramite AJAX e popolamento suggerimenti personalizzati
            async function fetchClientsAndDisplaySuggestions(query) {
                console.log(`Ricerca cliente per query: "${query}"`);
                clientSuggestionsContainer.innerHTML = ''; // Pulisci i suggerimenti esistenti
                clientSuggestionsContainer.style.display = 'none'; // Nascondi il contenitore per ora
                currentClientOptions = []; // Pulisci le opzioni caricate

                if (query.length < 2) {
                    telefonoClienteInput.value = '';
                    clienteIdInput.value = ''; // Pulisci anche l'ID del cliente
                    return;
                }

                if (searchClientTimeout) {
                    clearTimeout(searchClientTimeout);
                }

                searchClientTimeout = setTimeout(async () => {
                    try {
                        const response = await fetch(`get_clienti.php?q=${encodeURIComponent(query)}`);
                        if (!response.ok) {
                            const errorText = await response.text();
                            throw new Error(`HTTP error! status: ${response.status}. Details: ${errorText}`);
                        }
                        const clients = await response.json();
                        console.log("Dati clienti parsati:", clients);

                        currentClientOptions = clients; // Salva i clienti nella cache

                        if (clients.length > 0) {
                            clients.forEach(client => {
                                const suggestionItem = document.createElement('div');
                                suggestionItem.classList.add('client-suggestion-item');
                                suggestionItem.innerHTML = `
                                    <span class="client-name">${client.nome_cliente}</span>
                                    ${client.telefono ? `<span class="phone-info">${client.telefono}</span>` : ''}
                                `;
                                suggestionItem.dataset.id = client.id; // Salva l'ID del cliente
                                suggestionItem.dataset.nomeCliente = client.nome_cliente;
                                suggestionItem.dataset.telefono = client.telefono || '';

                                suggestionItem.addEventListener('click', function() {
                                    clienteInput.value = this.dataset.nomeCliente;
                                    telefonoClienteInput.value = this.dataset.telefono;
                                    clienteIdInput.value = this.dataset.id; // Imposta l'ID del cliente
                                    clientSuggestionsContainer.style.display = 'none'; // Nascondi i suggerimenti
                                });
                                clientSuggestionsContainer.appendChild(suggestionItem);
                            });
                            clientSuggestionsContainer.style.display = 'block'; // Mostra il contenitore
                        } else {
                            const noResultItem = document.createElement('div');
                            noResultItem.classList.add('client-suggestion-item');
                            noResultItem.textContent = 'Nessun cliente trovato';
                            noResultItem.style.fontStyle = 'italic';
                            noResultItem.style.color = '#718096';
                            noResultItem.style.cursor = 'default';
                            clientSuggestionsContainer.appendChild(noResultItem);
                            clientSuggestionsContainer.style.display = 'block';
                        }
                    } catch (error) {
                        console.error("Errore durante la ricerca clienti:", error);
                        clientSuggestionsContainer.innerHTML = '';
                        clientSuggestionsContainer.style.display = 'none';
                        currentClientOptions = [];
                    }
                }, 300);
            }

            // Listener per il campo cliente (per ricerca e suggerimenti)
            clienteInput.addEventListener('input', function() {
                fetchClientsAndDisplaySuggestions(this.value);
            });

            // Nascondi i suggerimenti quando l'input del cliente perde il focus
            clienteInput.addEventListener('blur', function() {
                setTimeout(() => {
                    clientSuggestionsContainer.style.display = 'none';
                }, 200); // Piccolo ritardo per permettere il click sul suggerimento
            });

            // Mostra i suggerimenti se l'input del cliente ottiene il focus e ha del testo
            clienteInput.addEventListener('focus', function() {
                if (this.value.length >= 2 && currentClientOptions.length > 0) {
                    clientSuggestionsContainer.style.display = 'block';
                }
            });


            // Funzione per la ricerca dei prodotti tramite AJAX e popolamento del suggerimento personalizzato
            async function fetchProductsAndDisplaySuggestions(query) {
                console.log(`Ricerca prodotto per query: "${query}"`);
                productSuggestionsContainer.innerHTML = ''; // Pulisci i suggerimenti esistenti
                productSuggestionsContainer.style.display = 'none'; // Nascondi il contenitore per ora
                currentProductOptions = []; // Pulisci le opzioni caricate

                if (query.length < 2) {
                    tuoImeiInput.value = ''; // Pulisci IMEI quando la query è troppo corta
                    return;
                }

                if (searchProductTimeout) {
                    clearTimeout(searchProductTimeout);
                }

                searchProductTimeout = setTimeout(async () => {
                    try {
                        const response = await fetch(`get_prodotti.php?q=${encodeURIComponent(query)}`);
                        if (!response.ok) {
                            const errorText = await response.text();
                            throw new Error(`HTTP error! status: ${response.status}. Details: ${errorText}`);
                        }
                        const products = await response.json();
                        console.log("Dati prodotti parsati:", products);

                        currentProductOptions = products; // Salva i prodotti nella cache

                        if (products.length > 0) {
                            products.forEach(product => {
                                const suggestionItem = document.createElement('div');
                                suggestionItem.classList.add('product-suggestion-item');
                                suggestionItem.innerHTML = `
                                    <span class="model-name">${product.modello}</span>
                                    ${product.imei ? `<span class="imei-info">(IMEI: ${product.imei})</span>` : ''}
                                `;
                                suggestionItem.dataset.modello = product.modello;
                                suggestionItem.dataset.imei = product.imei || ''; // Assicurati che ci sia un valore per l'IMEI

                                suggestionItem.addEventListener('click', function() {
                                    tuoModelloInput.value = this.dataset.modello;
                                    tuoImeiInput.value = this.dataset.imei;
                                    productSuggestionsContainer.style.display = 'none'; // Nascondi i suggerimenti
                                });
                                productSuggestionsContainer.appendChild(suggestionItem);
                            });
                            productSuggestionsContainer.style.display = 'block'; // Mostra il contenitore
                        } else {
                            // Se nessun prodotto trovato, puoi mostrare un messaggio o semplicemente non mostrare nulla
                            const noResultItem = document.createElement('div');
                            noResultItem.classList.add('product-suggestion-item');
                            noResultItem.textContent = 'Nessun prodotto trovato';
                            noResultItem.style.fontStyle = 'italic';
                            noResultItem.style.color = '#718096';
                            noResultItem.style.cursor = 'default';
                            productSuggestionsContainer.appendChild(noResultItem);
                            productSuggestionsContainer.style.display = 'block';
                        }
                    } catch (error) {
                        console.error("Errore durante la ricerca prodotti:", error);
                        productSuggestionsContainer.innerHTML = '';
                        productSuggestionsContainer.style.display = 'none';
                        currentProductOptions = [];
                    }
                }, 300);
            }

            // Listener per il campo modello prodotto (per ricerca e suggerimenti)
            tuoModelloInput.addEventListener('input', function() {
                fetchProductsAndDisplaySuggestions(this.value);
            });

            // Nascondi i suggerimenti quando l'input perde il focus, ma con un piccolo ritardo
            // per permettere il click sul suggerimento.
            tuoModelloInput.addEventListener('blur', function() {
                setTimeout(() => {
                    productSuggestionsContainer.style.display = 'none';
                }, 200); // Piccolo ritardo per permettere il click
            });

            // Mostra i suggerimenti se l'input ottiene il focus e ha del testo
            tuoModelloInput.addEventListener('focus', function() {
                if (this.value.length >= 2 && currentProductOptions.length > 0) {
                    productSuggestionsContainer.style.display = 'block';
                }
            });


            // Gestione del Modal/Popup "Aggiungi Nuovo Cliente"
            openNewClientModalBtn.addEventListener('click', function() {
                newClientModalOverlay.style.display = 'flex'; // Mostra il modal
                // Pulisci tutti i campi all'apertura
                modalNuovoClienteNomeInput.value = '';
                modalNuovoClienteCognomeInput.value = '';
                modalNuovoClienteTelefonoInput.value = '';
                modalNuovoClienteEmailInput.value = '';
                modalNuovoClienteIndirizzoInput.value = '';
                modalNuovoClienteCittaInput.value = '';
                modalNuovoClienteNoteInput.value = '';
                modalNuovoClienteRagioneSocialeInput.value = '';
                modalNuovoClientePartitaIvaInput.value = '';
                modalNuovoClienteIndirizzoAziendaInput.value = '';
                modalNuovoClienteCittaAziendaInput.value = '';
                modalNuovoClienteTelefonoAziendaInput.value = '';
                modalNuovoClienteEmailAziendaInput.value = '';
                modalNuovoClienteNoteAziendaInput.value = '';

                // Attiva il primo tab di default all'apertura del modal
                tabButtons[0].click(); // Simula un click sul primo bottone del tab
                modalNuovoClienteNomeInput.focus(); // Metti il focus sul primo campo del primo tab
            });

            closeNewClientModalBtn.addEventListener('click', function() {
                newClientModalOverlay.style.display = 'none'; // Nascondi il modal
            });

            cancelNewClientModalBtn.addEventListener('click', function() {
                newClientModalOverlay.style.display = 'none'; // Nascondi il modal
            });

            saveNewClientBtn.addEventListener('click', async function() {
                // Raccogli tutti i dati dai campi del modal (anche quelli nascosti, poiché sono sempre nel DOM)
                const nome = modalNuovoClienteNomeInput.value.trim();
                const cognome = modalNuovoClienteCognomeInput.value.trim();
                const telefono = modalNuovoClienteTelefonoInput.value.trim();
                const email = modalNuovoClienteEmailInput.value.trim();
                const indirizzo = modalNuovoClienteIndirizzoInput.value.trim();
                const citta = modalNuovoClienteCittaInput.value.trim();
                const note = modalNuovoClienteNoteInput.value.trim();
                const ragioneSociale = modalNuovoClienteRagioneSocialeInput.value.trim();
                const partitaIva = modalNuovoClientePartitaIvaInput.value.trim();
                const indirizzoAzienda = modalNuovoClienteIndirizzoAziendaInput.value.trim();
                const cittaAzienda = modalNuovoClienteCittaAziendaInput.value.trim();
                const telefonoAzienda = modalNuovoClienteTelefonoAziendaInput.value.trim();
                const emailAzienda = modalNuovoClienteEmailAziendaInput.value.trim();
                const noteAzienda = modalNuovoClienteNoteAziendaInput.value.trim();

                if (!nome || !cognome) { // Nome e Cognome sono obbligatori secondo il tuo schema
                    alert('Nome e Cognome del cliente sono obbligatori.');
                    // Se manca nome/cognome, assicurati che il tab dei dati personali sia visibile
                    showTab('personal_data_tab');
                    modalNuovoClienteNomeInput.focus();
                    return;
                }

                // Validazione minima per il telefono
                const phonePattern = /^[0-9]{10,15}$/;
                if (telefono && !phonePattern.test(telefono)) {
                    alert('Inserisci un numero di telefono personale valido (10-15 cifre, solo numeri).');
                    showTab('personal_data_tab');
                    modalNuovoClienteTelefonoInput.focus();
                    return;
                }
                if (telefonoAzienda && !phonePattern.test(telefonoAzienda)) {
                    alert('Inserisci un numero di telefono aziendale valido (10-15 cifre, solo numeri).');
                    showTab('company_data_tab');
                    modalNuovoClienteTelefonoAziendaInput.focus();
                    return;
                }

                // Validazione minima per l'email
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (email && !emailPattern.test(email)) {
                    alert('Inserisci un indirizzo email personale valido.');
                    showTab('personal_data_tab');
                    modalNuovoClienteEmailInput.focus();
                    return;
                }
                if (emailAzienda && !emailPattern.test(emailAzienda)) {
                    alert('Inserisci un indirizzo email aziendale valido.');
                    showTab('company_data_tab');
                    modalNuovoClienteEmailAziendaInput.focus();
                    return;
                }


                try {
                    const response = await fetch('add_cliente.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ 
                            nome: nome,
                            cognome: cognome,
                            email: email,
                            telefono: telefono,
                            indirizzo: indirizzo,
                            citta: citta,
                            note: note,
                            partita_iva: partitaIva,
                            ragione_sociale: ragioneSociale,
                            indirizzo_azienda: indirizzoAzienda,
                            citta_azienda: cittaAzienda,
                            telefono_azienda: telefonoAzienda,
                            email_azienda: emailAzienda,
                            note_azienda: noteAzienda
                        })
                    });

                    const result = await response.json();

                    if (response.ok && result.status === 'success') {
                        alert(result.message);
                        // Popola i campi cliente principali con il nuovo nome completo e telefono
                        // Il campo 'cliente' nel form permuta è per il nome completo (Nome + Cognome)
                        clienteInput.value = `${nome} ${cognome}`.trim();
                        telefonoClienteInput.value = telefono || telefonoAzienda; // Preferisci il telefono personale, altrimenti quello aziendale
                        clienteIdInput.value = result.id; // Imposta l'ID del nuovo cliente nel campo nascosto

                        newClientModalOverlay.style.display = 'none'; // Chiudi il modal
                        // Ricarica i suggerimenti per assicurarti che il nuovo cliente sia disponibile per la ricerca futura.
                        fetchClientsAndDisplaySuggestions(''); 
                    } else {
                        alert('Errore nel salvataggio del cliente: ' + (result.message || 'Errore sconosciuto.'));
                        console.error('Errore dal server:', result);
                    }
                } catch (error) {
                    console.error('Errore nella richiesta di salvataggio cliente:', error);
                    alert('Si è verificato un errore durante il salvataggio del cliente. Riprova più tardi.');
                }
            });

            // Funzione per mostrare un tab specifico
            function showTab(tabId) {
                tabContents.forEach(content => {
                    content.classList.remove('active');
                });
                tabButtons.forEach(button => {
                    button.classList.remove('active');
                });

                document.getElementById(tabId).classList.add('active');
                document.querySelector(`.tab-button[data-tab="${tabId}"]`).classList.add('active');
            }

            // Listener per i pulsanti dei tab
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.dataset.tab;
                    showTab(tabId);
                });
            });


            // Listener per il pulsante Stampa Riepilogo
            document.getElementById('stampa_riepilogo_btn').addEventListener('click', function() {
                window.print(); // Apre la finestra di stampa del browser
            });

            // Gestione del submit del form
            document.querySelector('form').addEventListener('submit', async function(event) {
                event.preventDefault(); // Impedisci il submit di default

                const form = event.target;
                const formData = new FormData(form);

                // Invia i dati al backend tramite Fetch API
                try {
                    const response = await fetch('salva_permuta.php', {
                        method: 'POST',
                        body: formData // FormData gestisce automaticamente i tipi di contenuto per file e campi
                    });

                    const result = await response.json();

                    if (response.ok && result.status === 'success') {
                        // Aggiorna il numero progressivo mostrato e il campo nascosto con l'ID reale dal backend
                        updatePermutaProgressiveID_onSave(result.new_id);
                        alert(result.message);

                        // Reset del form e re-inizializzazione di alcuni campi per una nuova immissione
                        form.reset();
                        dataPermutaInput.value = `${year}-${month}-${day}`; // Re-imposta la data odierna
                        updateCalculations(); // Reset dei calcoli
                        numeroPermutaDisplay.value = "Automatico al Salvataggio"; // Reset display per prossima immissione
                        numeroProgressivoInput.value = ""; // Pulisci il valore nascosto
                        clienteIdInput.value = ""; // Pulisci anche l'ID del cliente
                        document.getElementById('tuo_foto_preview').innerHTML = ''; // Pulisci anteprima foto
                        document.getElementById('cliente_foto_preview').innerHTML = ''; // Pulisci anteprima foto
                        productSuggestionsContainer.innerHTML = ''; // Pulisci suggerimenti prodotto
                        productSuggestionsContainer.style.display = 'none'; // Nascondi suggerimenti prodotto
                        clientSuggestionsContainer.innerHTML = ''; // Pulisci suggerimenti cliente
                        clientSuggestionsContainer.style.display = 'none'; // Nascondi suggerimenti cliente

                    } else {
                        alert('Errore durante il salvataggio della permuta: ' + (result.message || 'Errore sconosciuto.'));
                        console.error('Errore dal server:', result);
                    }
                } catch (error) {
                    console.error('Errore nella richiesta di salvataggio permuta:', error);
                    alert('Si è verificato un errore durante il salvataggio della permuta. Riprova più tardi.');
                }
            });

            // Inizializza i calcoli al caricamento della pagina
            updateCalculations();
        });
    </script>
</body>
</html>
