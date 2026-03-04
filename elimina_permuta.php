<?php
// elimina_permuta.php

ob_start(); // Inizia il buffering dell'output

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Includi il file di connessione al database
    // Assicurati che 'db.php' si trovi nella stessa directory o che il percorso sia corretto.
    if (!file_exists('db.php')) {
        throw new Exception('Errore: File di configurazione del database (db.php) non trovato.');
    }
    require_once 'db.php';

    // Controlla se la connessione al database è stata stabilita correttamente
    if (!isset($conn) || $conn === null) {
        throw new Exception('Errore di connessione al database: ' . ($db_connection_error ?? 'Sconosciuto'));
    }

    // Verifica che la richiesta sia di tipo POST e contenga i dati necessari
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recupera l'ID della permuta dal corpo della richiesta POST
        // Utilizza filter_input per una validazione e sanificazione sicura
        $permutaId = filter_input(INPUT_POST, 'permuta_id', FILTER_VALIDATE_INT);

        // Validazione dell'ID della permuta
        if ($permutaId === false || $permutaId === null || $permutaId <= 0) {
            throw new Exception('ID permuta non valido per l\'eliminazione.');
        }

        // Inizia una transazione per assicurare l'integrità dei dati
        $conn->begin_transaction();

        // 1. Elimina la permuta dalla tabella 'permute_nuovo'
        // NOTA: Se hai tabelle correlate con FOREIGN KEY che non hanno ON DELETE CASCADE,
        // dovrai eliminare prima i record correlati prima di eliminare la riga principale.
        // Assumo che le tue FOREIGN KEY siano configurate con ON DELETE CASCADE o che non ci siano dati orfani.
        $delete_sql = "DELETE FROM permute_nuovo WHERE id = ?";
        $stmt_delete = $conn->prepare($delete_sql);
        if ($stmt_delete === false) {
            throw new Exception("Errore nella preparazione della query di eliminazione: " . $conn->error);
        }
        $stmt_delete->bind_param("i", $permutaId);
        
        if (!$stmt_delete->execute()) {
            throw new Exception("Errore nell'esecuzione della query di eliminazione: " . $stmt_delete->error);
        }
        $stmt_delete->close();

        // Se l'eliminazione ha avuto successo, esegui il commit della transazione
        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Permuta eliminata con successo.';

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
    error_log("Errore in elimina_permuta.php: " . $e->getMessage());
    // Restituisci un messaggio di errore generico al client
    $response['message'] = 'Errore durante l\'eliminazione della permuta: ' . $e->getMessage();
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
