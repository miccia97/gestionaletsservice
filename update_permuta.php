<?php
// Avvia la sessione per gestire i messaggi di feedback
session_start();

// Imposta l'header per il tipo di contenuto JSON
header('Content-Type: application/json');

// Attivazione debugging (solo per sviluppo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Includi il file di connessione al database
if (!file_exists('db.php')) {
    echo json_encode(['success' => false, 'message' => 'Errore: File di connessione al database (db.php) non trovato.']);
    exit;
}
require_once 'db.php';

// Controlla se c'è stato un errore di connessione al database
if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'Errore: Connessione al database non stabilita.']);
    exit;
}

// Verifica che la richiesta sia di tipo POST e che l'ID della permuta sia presente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']); // Assicurati che l'ID sia un intero

    // Recupera e sanitizza tutti i dati dal POST
    // Utilizza htmlspecialchars per prevenire XSS e real_escape_string per prevenire SQL injection nei valori delle stringhe
    // ma preferisci i prepared statement per una maggiore sicurezza.
    $cliente = isset($_POST['cliente']) ? htmlspecialchars($conn->real_escape_string($_POST['cliente'])) : '';
    $telefono = isset($_POST['telefono']) ? htmlspecialchars($conn->real_escape_string($_POST['telefono'])) : '';
    // Il progressivo non dovrebbe essere modificabile dall'esterno, ma lo includiamo per completezza se necessario.
    // Per un campo readonly sul frontend, non è necessario rileggere il suo valore da POST se non lo si aggiorna.
    // $progressivo = isset($_POST['progressivo']) ? htmlspecialchars($conn->real_escape_string($_POST['progressivo'])) : '';
    
    $data_permuta_str = isset($_POST['data']) ? htmlspecialchars($_POST['data']) : null;
    $data = null;
    if ($data_permuta_str) {
        // Formatta la data nel formato YYYY-MM-DD per il database
        $data = date('Y-m-d', strtotime($data_permuta_str));
    } else {
        // Se la data è mancante, potresti voler restituire un errore o assegnare un valore predefinito
        echo json_encode(['success' => false, 'message' => 'Errore: la data della permuta è obbligatoria.']);
        $conn->close();
        exit;
    }

    $modello_nuovo = isset($_POST['modello_nuovo']) ? htmlspecialchars($conn->real_escape_string($_POST['modello_nuovo'])) : '';
    $imei_nuovo = isset($_POST['imei_nuovo']) ? htmlspecialchars($conn->real_escape_string($_POST['imei_nuovo'])) : '';
    $note_nuovo = isset($_POST['note_nuovo']) ? htmlspecialchars($conn->real_escape_string($_POST['note_nuovo'])) : '';
    $prezzo_nuovo = isset($_POST['prezzo_nuovo']) ? floatval($_POST['prezzo_nuovo']) : 0.00;
    $costo_prodotto = isset($_POST['costo_prodotto']) ? floatval($_POST['costo_prodotto']) : 0.00;
    
    $modello_usato = isset($_POST['modello_usato']) ? htmlspecialchars($conn->real_escape_string($_POST['modello_usato'])) : '';
    $imei_usato = isset($_POST['imei_usato']) ? htmlspecialchars($conn->real_escape_string($_POST['imei_usato'])) : '';
    $note_usato = isset($_POST['note_usato']) ? htmlspecialchars($conn->real_escape_string($_POST['note_usato'])) : '';
    $prezzo_permuta = isset($_POST['prezzo_permuta']) ? floatval($_POST['prezzo_permuta']) : 0.00;
    $costo_riparazione = isset($_POST['costo_riparazione']) ? floatval($_POST['costo_riparazione']) : 0.00;
    
    $costo_accessori = isset($_POST['costo_accessori']) ? floatval($_POST['costo_accessori']) : 0.00;
    $differenza = isset($_POST['differenza']) ? floatval($_POST['differenza']) : 0.00;
    $prezzo_vendita = isset($_POST['prezzo_vendita']) ? floatval($_POST['prezzo_vendita']) : 0.00;
    $status = isset($_POST['status']) ? htmlspecialchars($conn->real_escape_string($_POST['status'])) : 'In Trattativa'; // Valore di default
    $test_ok = isset($_POST['test_ok']) ? htmlspecialchars($conn->real_escape_string($_POST['test_ok'])) : '';
    $note_generali = isset($_POST['note_generali']) ? htmlspecialchars($conn->real_escape_string($_POST['note_generali'])) : '';

    // Esegui l'aggiornamento nel database usando un prepared statement per sicurezza
    try {
        // La colonna 'data_permuta' è un duplicato di 'data', user should clarify its usage. 
        // Per ora, aggiorniamo solo la colonna 'data'. Se 'data_permuta' è richiesta, va aggiunta.
        $stmt = $conn->prepare("UPDATE permute_nuovo SET 
            cliente = ?, telefono = ?, data = ?, modello_nuovo = ?, imei_nuovo = ?, note_nuovo = ?,
            prezzo_nuovo = ?, costo_prodotto = ?, modello_usato = ?, imei_usato = ?, note_usato = ?,
            prezzo_permuta = ?, costo_riparazione = ?, costo_accessori = ?, differenza = ?,
            prezzo_vendita = ?, status = ?, test_ok = ?, note_generali = ?
            WHERE id = ?");

        if ($stmt === false) {
            throw new Exception("Errore nella preparazione della query: " . $conn->error);
        }

        $stmt->bind_param(
            "ssssssddssssdddssssi", // String, String, String (Date), String, String, String, Double, Double, String, String, String, Double, Double, Double, Double, Double, String, String, String, Integer
            $cliente, $telefono, $data, $modello_nuovo, $imei_nuovo, $note_nuovo,
            $prezzo_nuovo, $costo_prodotto, $modello_usato, $imei_usato, $note_usato,
            $prezzo_permuta, $costo_riparazione, $costo_accessori, $differenza,
            $prezzo_vendita, $status, $test_ok, $note_generali,
            $id
        );

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = 'Permuta aggiornata con successo!';
                $_SESSION['isError'] = false;
                echo json_encode(['success' => true, 'message' => 'Permuta aggiornata con successo!']);
            } else {
                $_SESSION['message'] = 'Nessuna modifica apportata o permuta non trovata.';
                $_SESSION['isError'] = false; // Non è un errore, ma un'informazione
                echo json_encode(['success' => true, 'message' => 'Nessuna modifica apportata o permuta non trovata.']);
            }
        } else {
            throw new Exception("Errore nell'esecuzione della query: " . $stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['message'] = 'Errore durante l\'aggiornamento della permuta: ' . $e->getMessage();
        $_SESSION['isError'] = true;
        error_log("Errore aggiornamento permuta: " . $e->getMessage()); // Log dell'errore per il debugging
        echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento della permuta: ' . $e->getMessage()]);
    } finally {
        if (isset($stmt) && $stmt instanceof mysqli_stmt) {
            $stmt->close();
        }
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
    }
} else {
    $_SESSION['message'] = 'Richiesta non valida o ID permuta mancante.';
    $_SESSION['isError'] = true;
    echo json_encode(['success' => false, 'message' => 'Richiesta non valida.']);
}
?>
