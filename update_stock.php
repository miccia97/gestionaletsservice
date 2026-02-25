<?php
// update_stock.php
// Questo script gestisce le richieste AJAX per lo scarico di prodotti dal magazzino
// e la registrazione dell'evento nella cronologia della prenotazione.

// Attivazione debugging PHP - Rimuovere in produzione
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Avvia la sessione PHP
session_start();

// Includi il file di connessione al database MySQL
if (!file_exists('db.php')) {
    echo json_encode(['success' => false, 'message' => 'Errore critico: Il file db.php non è stato trovato!']);
    exit;
}
require_once 'db.php';

// Controlla subito se c'è stato un errore di connessione al database da db.php
if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'Errore di connessione al database: ' . ($db_connection_error ?? 'Sconosciuto')]);
    exit;
}

// Assicurati che la richiesta sia POST e che l'azione sia 'decrement_stock'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'decrement_stock') {
    header('Content-Type: application/json'); // Imposta l'intestazione per una risposta JSON

    $productId = $_POST['productId'] ?? null;
    $quantityToUnload = $_POST['quantity'] ?? null;
    $reservationId = $_POST['reservationId'] ?? null; // ID della prenotazione associata

    // Validazione input
    if ($productId === null || !is_numeric($productId) || $productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID Prodotto non valido.']);
        exit;
    }
    if ($quantityToUnload === null || !is_numeric($quantityToUnload) || $quantityToUnload <= 0) {
        echo json_encode(['success' => false, 'message' => 'Quantità da scaricare non valida.']);
        exit;
    }
    if ($reservationId === null || !is_numeric($reservationId) || $reservationId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID Prenotazione non valido.']);
        exit;
    }

    $productId = (int)$productId;
    $quantityToUnload = (int)$quantityToUnload;
    $reservationId = (int)$reservationId;
    $currentDateTime = date('Y-m-d H:i:s'); // Data e ora attuali per la cronologia

    // Inizia una transazione per garantire l'atomicità delle operazioni
    mysqli_begin_transaction($conn);

    try {
        // 1. Recupera la giacenza attuale del prodotto e il suo nome
        $stmt_get_stock = $conn->prepare("SELECT nome, quantita FROM prodotti WHERE id = ? FOR UPDATE"); // FOR UPDATE per blocco riga
        if ($stmt_get_stock === false) {
            throw new mysqli_sql_exception("Errore nella preparazione della query di recupero giacenza: " . $conn->error);
        }
        $stmt_get_stock->bind_param('i', $productId);
        $stmt_get_stock->execute();
        $result_stock = $stmt_get_stock->get_result();
        $product_data = $result_stock->fetch_assoc();
        $stmt_get_stock->close();

        if (!$product_data) {
            throw new Exception("Prodotto non trovato nel catalogo.");
        }

        $current_stock = (int)$product_data['quantita'];
        $product_name = $product_data['nome'];

        // Controllo della giacenza
        if ($quantityToUnload > $current_stock) {
            throw new Exception("Giacenza insufficiente per scaricare il prodotto. Disponibili: " . $current_stock);
        }

        // 2. Decrementa la quantità del prodotto nel magazzino (tabella 'prodotti')
        $stmt_update_stock = $conn->prepare("UPDATE prodotti SET quantita = quantita - ? WHERE id = ?");
        if ($stmt_update_stock === false) {
            throw new mysqli_sql_exception("Errore nella preparazione della query di aggiornamento magazzino: " . $conn->error);
        }
        $stmt_update_stock->bind_param('ii', $quantityToUnload, $productId);
        if ($stmt_update_stock->execute() === false) {
            throw new mysqli_sql_exception("Errore nell'esecuzione dell'aggiornamento magazzino: " . $stmt_update_stock->error);
        }
        $stmt_update_stock->close();

        $new_stock = $current_stock - $quantityToUnload; // Calcola la nuova giacenza

        // 3. Registra il movimento nella tabella 'prenotazioni_articoli_movimenti'
        $stmt_insert_movement = $conn->prepare("INSERT INTO prenotazioni_articoli_movimenti (prenotazione_id, prodotto_id, quantita_movimentata, tipo_movimento, data_movimento) VALUES (?, ?, ?, ?, ?)");
        if ($stmt_insert_movement === false) {
            throw new mysqli_sql_exception("Errore nella preparazione della query di inserimento movimento: " . $conn->error);
        }
        $tipo_movimento = 'scarico_prenotazione';
        $stmt_insert_movement->bind_param('iiiss', $reservationId, $productId, $quantityToUnload, $tipo_movimento, $currentDateTime);
        if ($stmt_insert_movement->execute() === false) {
            throw new mysqli_sql_exception("Errore nell'esecuzione dell'inserimento movimento: " . $stmt_insert_movement->error);
        }
        $stmt_insert_movement->close();

        // 4. Registra l'evento nella tabella 'prenotazioni_storico'
        // Assumiamo che ci sia un utente loggato, altrimenti usiamo 'Sistema'
        $user_who_made_change = $_SESSION['username'] ?? 'Utente Sconosciuto'; // Puoi personalizzare come recuperare l'utente
        $event_description = "Articolo \"{$product_name}\" scaricato dal magazzino: {$quantityToUnload} pz. Nuova giacenza: {$new_stock}.";

        $stmt_insert_history = $conn->prepare("INSERT INTO prenotazioni_storico (prenotazione_id, data_evento, evento_descrizione, utente) VALUES (?, ?, ?, ?)");
        if ($stmt_insert_history === false) {
            throw new mysqli_sql_exception("Errore nella preparazione della query di inserimento storico: " . $conn->error);
        }
        $stmt_insert_history->bind_param('isss', $reservationId, $currentDateTime, $event_description, $user_who_made_change);
        if ($stmt_insert_history->execute() === false) {
            throw new mysqli_sql_exception("Errore nell'esecuzione dell'inserimento storico: " . $stmt_insert_history->error);
        }
        $stmt_insert_history->close();


        // Commit della transazione se tutte le operazioni sono andate a buon fine
        mysqli_commit($conn);

        echo json_encode([
            'success' => true,
            'message' => 'Prodotto scaricato e movimento registrato con successo.',
            'newStock' => $new_stock
        ]);

    } catch (Exception $e) {
        // Rollback della transazione in caso di errore
        mysqli_rollback($conn);
        error_log("Errore durante lo scarico magazzino per prenotazione " . $reservationId . ": " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore durante lo scarico del prodotto: ' . $e->getMessage()]);
    }

} else {
    // Richiesta non valida
    echo json_encode(['success' => false, 'message' => 'Richiesta non valida.']);
}

$conn->close(); // Chiudi la connessione al database
?>
