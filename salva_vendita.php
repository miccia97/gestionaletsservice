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

// Imposta l'header per indicare che la risposta sarà in formato JSON
header('Content-Type: application/json');

// Inizializza un array per la risposta che verrà inviata al frontend
$response = ['success' => false, 'message' => ''];

// Controlla che la richiesta sia di tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = "Metodo di richiesta non valido.";
    echo json_encode($response);
    exit();
}

// Controllo dati obbligatori essenziali per la vendita
// `totale_da_pagare_finale` non è nella tabella vendite, ma è un dato importante dal frontend
if (!isset($_POST['id_cliente']) || !isset($_POST['carrello_json']) || !isset($_POST['totale_vendita'])) {
    $response['message'] = "Dati essenziali mancanti per la registrazione della vendita.";
    echo json_encode($response);
    exit();
}

// Recupero e validazione id_cliente
$id_cliente = intval($_POST['id_cliente']);
if ($id_cliente <= 0) {
    $response['message'] = "Errore: ID cliente non valido o mancante.";
    echo json_encode($response);
    exit();
}

// Decodifica il carrello JSON inviato dal frontend
$carrello = json_decode($_POST['carrello_json'], true);
if (!is_array($carrello) || count($carrello) === 0) {
    $response['message'] = "Carrello non valido o vuoto.";
    echo json_encode($response);
    exit();
}

// --- RECUPERO DEI DATI DAI CAMPI NASCOSTI DEL FORM (ADATTATI ALLA TUA TABELLA VENDITE) ---
// Questi valori provengono direttamente dai calcoli JavaScript del frontend
$totale_finale = floatval($_POST['totale_vendita'] ?? 0); // Corrisponde a 'totale' nella tua tabella
$pagamento1_valore = floatval($_POST['pagamento1_importo'] ?? 0); // Corrisponde a 'pagamento1'
$pagamento2_valore = floatval($_POST['pagamento2_importo'] ?? 0); // Corrisponde a 'pagamento2'
$residuo_valore = floatval($_POST['residuo_da_dare'] ?? 0); // Corrisponde a 'residuo'

// Questi campi sono presenti nel frontend ma non nella tabella `vendite` che hai fornito.
// Se vuoi salvarli, dovrai aggiungere le colonne corrispondenti alla tua tabella `vendite`.
$totale_da_pagare_frontend = floatval($_POST['totale_da_pagare_finale'] ?? 0); // Valore calcolato dal frontend
$pagamento1_metodo = $_POST['pagamento1_metodo'] ?? null;
$pagamento2_metodo = $_POST['pagamento2_metodo'] ?? null;
$id_buono = $_POST['id_buono'] ?? null;
$saldo = (isset($_POST['saldo']) && $_POST['saldo'] === '1') ? 1 : 0;
$acconto = (isset($_POST['acconto']) && $_POST['acconto'] === '1') ? 1 : 0;
$stampante2 = (isset($_POST['stampante2']) && $_POST['stampante2'] === '1') ? 1 : 0;
$scontrino = (isset($_POST['scontrino']) && $_POST['scontrino'] === '1') ? 1 : 0;
$scontrino_cortesia = (isset($_POST['scontrino_cortesia']) && $_POST['scontrino_cortesia'] === '1') ? 1 : 0;


// Recupera nome e cognome cliente dal DB (per sicurezza e completezza)
// Assumendo che la tabella clienti sia 'clienti_nuovo' e contenga 'nome' e 'cognome'
$stmt_cliente = $conn->prepare("SELECT nome, cognome FROM clienti_nuovo WHERE id = ?");
if (!$stmt_cliente) {
    $response['message'] = "Errore prepare cliente: " . $conn->error;
    echo json_encode($response);
    exit();
}
$stmt_cliente->bind_param("i", $id_cliente);
$stmt_cliente->execute();
$stmt_cliente->bind_result($nome_cliente, $cognome_cliente);
if (!$stmt_cliente->fetch()) {
    $response['message'] = "Errore: cliente con ID {$id_cliente} non trovato nel database.";
    echo json_encode($response);
    exit();
}
$stmt_cliente->close();
$nome_cliente_completo = trim($nome_cliente . ' ' . $cognome_cliente);


// --- INIZIO TRANSAZIONE ---
// Questo assicura che tutte le query (vendita e dettagli) siano atomiche: o tutte hanno successo o nessuna.
$conn->begin_transaction();

try {
    // 1. Inserisci la vendita principale nella tabella 'vendite'
    // La query è stata adattata per corrispondere esattamente alla tua tabella 'vendite':
    // id INT(11) AUTO_INCREMENT PRIMARY KEY
    // id_cliente INT(11)
    // nome_cliente VARCHAR(255)
    // data_vendita DATETIME DEFAULT CURRENT_TIMESTAMP
    // totale DECIMAL(10,2)           <- Corrisponde a 'totale' nella tua tabella
    // pagamento1 DECIMAL(10,2)        <- Corrisponde a 'pagamento1'
    // pagamento2 DECIMAL(10,2)        <- Corrisponde a 'pagamento2'
    // residuo DECIMAL(10,2)           <- Corrisponde a 'residuo'
    $stmt_vendita = $conn->prepare("INSERT INTO vendite (
        id_cliente, nome_cliente, data_vendita, totale, pagamento1, pagamento2, residuo
    ) VALUES (?, ?, NOW(), ?, ?, ?, ?)");

    if (!$stmt_vendita) {
        throw new Exception("Errore nella preparazione della query di vendita: " . $conn->error);
    }

    // Tipi per bind_param: i=integer, s=string, d=double/float
    // Corrisponde a (id_cliente, nome_cliente, totale, pagamento1, pagamento2, residuo)
    $stmt_vendita->bind_param("isdddd",
        $id_cliente,
        $nome_cliente_completo,
        $totale_finale,       // Colonna 'totale'
        $pagamento1_valore,   // Colonna 'pagamento1'
        $pagamento2_valore,   // Colonna 'pagamento2'
        $residuo_valore       // Colonna 'residuo'
    );
    $stmt_vendita->execute();

    if ($stmt_vendita->error) {
        throw new Exception("Errore nell'esecuzione della query di vendita: " . $stmt_vendita->error);
    }

    $id_vendita = $conn->insert_id; // Ottieni l'ID dell'ultima vendita inserita
    $stmt_vendita->close();


    // 2. Inserisci i dettagli dei prodotti nella tabella 'vendite_dettagli'
    // La query è stata adattata per corrispondere esattamente alla tua tabella 'vendite_dettagli':
    // id INT AUTO_INCREMENT PRIMARY KEY
    // id_vendita INT
    // id_prodotto INT
    // nome VARCHAR(255)
    // quantita INT
    // prezzo_unitario DECIMAL(10, 2)
    // prezzo_scontato DECIMAL(10, 2)
    // FOREIGN KEY (id_vendita) REFERENCES vendite(id) ON DELETE CASCADE
    $stmt_dettagli = $conn->prepare("INSERT INTO vendite_dettagli (
        id_vendita, id_prodotto, nome, quantita, prezzo_unitario, prezzo_scontato
    ) VALUES (?, ?, ?, ?, ?, ?)");

    if (!$stmt_dettagli) {
        throw new Exception("Errore nella preparazione della query dei dettagli: " . $conn->error);
    }

    foreach ($carrello as $item) {
        // Recupera i dati di ogni articolo, fornendo valori di default
        $item_id_prodotto = $item['id'] ?? null; // ID del prodotto
        $item_name = $item['name'] ?? null;      // Nome del prodotto (corrisponde a 'nome' nella tabella)
        $item_qty = intval($item['qty'] ?? 0);
        $item_price = floatval($item['price'] ?? 0); // Prezzo unitario
        $item_prezzo_scontato = floatval($item['prezzo_scontato'] ?? 0); // Prezzo scontato

        // Tipi per bind_param: i=integer, s=string, d=double/float
        // Corrisponde a (id_vendita, id_prodotto, nome, quantita, prezzo_unitario, prezzo_scontato)
        $stmt_dettagli->bind_param("iisidd",
            $id_vendita,
            $item_id_prodotto,
            $item_name,
            $item_qty,
            $item_price,
            $item_prezzo_scontato
        );
        $stmt_dettagli->execute();

        if ($stmt_dettagli->error) {
            throw new Exception("Errore nell'esecuzione della query dettagli per prodotto " . ($item_name ?? '') . ": " . $stmt_dettagli->error);
        }

        // --- LOGICA AGGIORNATA: AGGIORNA LA COLONNA 'quantita' NELLA TABELLA 'prodotti' ---
        // Assumiamo che la tua tabella dei prodotti sia 'prodotti' e abbia una colonna 'quantita'
        // E che l'ID del prodotto nel carrello corrisponda all'ID nella tabella 'prodotti'.
        $stmt_update_quantita = $conn->prepare("UPDATE prodotti SET quantita = quantita - ? WHERE id = ?");
        if (!$stmt_update_quantita) {
            throw new Exception("Errore nella preparazione della query di aggiornamento quantita: " . $conn->error);
        }
        // Il tipo per la quantità venduta è 'i' (integer), per l'ID del prodotto è 'i' (integer)
        $stmt_update_quantita->bind_param("ii", $item_qty, $item_id_prodotto);
        $stmt_update_quantita->execute();

        if ($stmt_update_quantita->error) {
            throw new Exception("Errore nell'esecuzione della query di aggiornamento quantita per prodotto ID " . ($item_id_prodotto ?? '') . ": " . $stmt_update_quantita->error);
        }
        $stmt_update_quantita->close(); // Chiudi lo statement dopo ogni esecuzione
    }
    $stmt_dettagli->close(); // Chiudi lo statement dei dettagli dopo il loop

    $conn->commit(); // Conferma la transazione se tutte le operazioni sono riuscite
    $response['success'] = true;
    $response['message'] = "Vendita registrata con successo! ID: " . $id_vendita;
    $response['id_vendita'] = $id_vendita; // Invia l'ID della vendita al frontend

} catch (mysqli_sql_exception $e) {
    // Cattura errori specifici di MySQLi
    $conn->rollback(); // Annulla la transazione in caso di errore
    $response['message'] = "Errore SQL durante la registrazione della vendita: " . $e->getMessage();
    error_log("SQL Exception in salva_vendita: " . $e->getMessage()); // Logga l'errore per debug
} catch (Exception $e) {
    // Cattura altri tipi di eccezioni
    $conn->rollback(); // Annulla la transazione
    $response['message'] = "Errore generico durante la registrazione della vendita: " . $e->getMessage();
    error_log("General Exception in salva_vendita: " . $e->getMessage()); // Logga l'errore per debug
} finally {
    // Chiudi la connessione al database in ogni caso
    $conn->close();
}

// Invia la risposta JSON al frontend
echo json_encode($response);
