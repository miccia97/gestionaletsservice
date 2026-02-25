<?php
// elimina_riparazione.php

// Inizia il buffering dell'output per catturare eventuali errori o output indesiderati
ob_start();

// Abilita il reporting degli errori per il debug.
// Disabilita in ambiente di produzione!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Imposta l'header per indicare che la risposta sarà in formato JSON
header('Content-Type: application/json');

// Inizializza l'array di risposta JSON
$response = ['success' => false, 'message' => ''];

try {
    // Includi il file di connessione al database
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

        // Validazione dell'ID della riparazione
        if ($repairId === false || $repairId === null || $repairId <= 0) {
            throw new Exception('ID riparazione non valido per l\'eliminazione.');
        }

        // Inizia una transazione per assicurare l'integrità dei dati
        $conn->begin_transaction();

        // 1. Elimina la riparazione dalla tabella 'riparazioni'
        // NOTA: Se hai tabelle correlate con FOREIGN KEY che non hanno ON DELETE CASCADE,
        // dovrai eliminare prima i record correlati (es. da riparazioni_storico, riparazioni_articoli_movimenti)
        // prima di eliminare la riga principale in 'riparazioni'.
        // Assumo che le tue FOREIGN KEY siano configurate con ON DELETE CASCADE o che non ci siano dati orfani.
        $delete_sql = "DELETE FROM riparazioni WHERE id = ?";
        $stmt_delete = $conn->prepare($delete_sql);
        if ($stmt_delete === false) {
            throw new Exception("Errore nella preparazione della query di eliminazione: " . $conn->error);
        }
        $stmt_delete->bind_param("i", $repairId);
        
        if (!$stmt_delete->execute()) {
            throw new Exception("Errore nell'esecuzione della query di eliminazione: " . $stmt_delete->error);
        }
        $stmt_delete->close();

        // Se l'eliminazione ha avuto successo, esegui il commit della transazione
        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Riparazione eliminata con successo.';

    } else {
        // Metodo di richiesta non consentito
        $response['message'] = 'Metodo di richiesta non consentito.';
    }

} catch (Exception $e) {
    // Se si verifica un errore, esegui il rollback della transazione (se iniziata)
    if (isset($conn) && $conn->in_transaction) {
        $conn->rollback();
    }
    // Logga l'errore completo per il debug lato server
    error_log("Errore in elimina_riparazione.php: " . $e->getMessage());
    // Restituisci un messaggio di errore generico al client
    $response['message'] = 'Errore durante l\'eliminazione della riparazione: ' . $e->getMessage();
} finally {
    // Chiudi la connessione al database
    if (isset($conn) && $conn !== null) {
        $conn->close();
    }
    // Pulisce il buffer di output e invia la risposta JSON
    ob_end_clean();
    echo json_encode($response);
    exit;
}
?>
