<?php
// update_payment_status.php

// Inizia il buffering dell'output. Questo catturerà qualsiasi output inatteso (come errori PHP)
// prima che l'header JSON venga inviato, prevenendo l'errore "Unexpected token '<'".
ob_start();

// Abilita il reporting degli errori per il debug in fase di sviluppo
// Assicurati che questo sia DISABILITATO in un ambiente di produzione per motivi di sicurezza!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Imposta l'header per indicare che la risposta sarà in formato JSON
header('Content-Type: application/json');

// Inizializza l'array di risposta
$response = ['success' => false, 'message' => ''];

try {
    // Include il file di connessione al database
    // Assicurati che 'db.php' si trovi nella stessa directory o che il percorso sia corretto.
    if (!file_exists('db.php')) {
        throw new Exception('Errore: File di configurazione del database (db.php) non trovato.');
    }
    require_once 'db.php';

    // Controlla se la connessione al database è stata stabilita correttamente
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Errore di connessione al database: ' . ($conn->connect_error ?? 'Sconosciuto'));
    }

    // Verifica che la richiesta sia di tipo POST e contenga i dati necessari
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recupera l'ID della riparazione dal corpo della richiesta POST
        // Utilizza filter_input per una validazione e sanificazione sicura
        $repairId = filter_input(INPUT_POST, 'repair_id', FILTER_VALIDATE_INT);
        
        // Puoi passare un nuovo stato specifico dalla chiamata JS, altrimenti usa un default
        $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_SANITIZE_STRING);
        if (empty($newStatus)) {
            $newStatus = 'Consegnata'; // Default se non fornito
        }

        // Validazione dei dati ricevuti
        if ($repairId === false || $repairId === null || $repairId <= 0) {
            throw new Exception('ID riparazione non valido.');
        }

        // Array degli stati consentiti per prevenire inserimenti non validi
        $allowed_statuses = ['In Attesa', 'In Lavorazione', 'Completata', 'Consegnata', 'Annullata'];
        if (!in_array($newStatus, $allowed_statuses)) {
            throw new Exception('Stato non valido fornito.');
        }

        // Inizia una transazione per assicurare l'integrità dei dati
        $conn->begin_transaction();

        // 1. Aggiorna lo stato della riparazione nella tabella 'riparazioni'
        $update_sql = "UPDATE riparazioni SET stato = ? WHERE id = ?";
        $stmt_update = $conn->prepare($update_sql);
        if ($stmt_update === false) {
            throw new Exception("Errore nella preparazione della query di aggiornamento: " . $conn->error);
        }
        $stmt_update->bind_param("si", $newStatus, $repairId);
        
        if (!$stmt_update->execute()) {
            throw new Exception("Errore nell'esecuzione della query di aggiornamento: " . $stmt_update->error);
        }
        $stmt_update->close();

        // 2. Registra l'evento nella cronologia della riparazione
        $event_description = "Pagamento verificato e stato aggiornato a \"" . $newStatus . "\".";
        $current_datetime = date('Y-m-d H:i:s'); // Data e ora attuali
        $user = "Sistema"; // O l'utente loggato, se hai un sistema di autenticazione

        $history_sql = "INSERT INTO riparazioni_storico (riparazione_id, data_evento, evento_descrizione, utente) VALUES (?, ?, ?, ?)";
        $stmt_history = $conn->prepare($history_sql);
        if ($stmt_history === false) {
            throw new Exception("Errore nella preparazione della query di storico: " . $conn->error);
        }
        $stmt_history->bind_param("isss", $repairId, $current_datetime, $event_description, $user);

        if (!$stmt_history->execute()) {
            throw new Exception("Errore nell'esecuzione della query di storico: " . $stmt_history->error);
        }
        $stmt_history->close();

        // Se tutto è andato bene, esegui il commit della transazione
        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Stato riparazione aggiornato con successo e cronologia registrata.';

    } else {
        // Metodo di richiesta non consentito
        $response['message'] = 'Metodo di richiesta non consentito.';
    }

} catch (Exception $e) {
    // Se si verifica un errore, esegui il rollback della transazione (se iniziata)
    if (isset($conn) && $conn->in_transaction) {
        $conn->rollback();
    }
    error_log("Errore in update_payment_status.php: " . $e->getMessage()); // Logga l'errore per il debug
    $response['message'] = 'Errore durante l\'aggiornamento dello stato: ' . $e->getMessage();
} finally {
    // Chiudi la connessione al database
    if (isset($conn) && $conn !== null) {
        $conn->close();
    }
    // Pulisce il buffer di output prima di inviare la risposta JSON
    ob_end_clean();
    echo json_encode($response);
    exit;
}

?>
