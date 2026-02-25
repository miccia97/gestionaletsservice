<?php
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Imposta l'intestazione per una risposta JSON
header('Content-Type: application/json');

// Includi il file di connessione al database MySQL
if (!file_exists('db.php')) {
    error_log("Errore: Il file db.php non è stato trovato!");
    echo json_encode(['success' => false, 'message' => 'Errore critico: Il file db.php non è stato trovato!']);
    exit;
}
require_once 'db.php';

// Controlla subito se c'è stato un errore di connessione al database
if (!isset($conn) || $conn === null) {
    $db_error_message = isset($db_connection_error) ? $db_connection_error : 'Connessione al database non stabilita.';
    error_log("Errore critico di connessione al database: " . $db_error_message);
    echo json_encode(['success' => false, 'message' => 'Errore critico di connessione al database: ' . $db_error_message]);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    // --- DEBUG: LOG DI TUTTI I DATI RICEVUTI VIA POST ---
    error_log("Dati POST ricevuti in update_reservation.php: " . print_r($_POST, true));

    // Recupera i dati dalla richiesta POST
    $id = $_POST['id'] ?? null;
    $product_name = $_POST['product_name'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $unit_price = $_POST['unit_price'] ?? 0.0;
    $product_total_price = $_POST['product_total_price'] ?? 0.0;
    $deposit_amount = $_POST['deposit_amount'] ?? 0.0;
    $remaining_amount = $_POST['remaining_amount'] ?? 0.0;
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $reservation_date = $_POST['reservation_date'] ?? '';
    $fornitore_id = $_POST['fornitore_id'] ?? null; // Può essere null
    $data_arrivo_previsto = $_POST['data_arrivo_previsto'] ?? null; // Può essere null
    $notestext = $_POST['notestext'] ?? '';
    $status = $_POST['status'] ?? 'In Attesa';

    // NEW: Recupera i dati di cronologia come stringa JSON e decodificali
    $historyUpdatesJson = $_POST['history_updates'] ?? '[]';
    $historyUpdates = json_decode($historyUpdatesJson, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Errore nella decodifica JSON della cronologia: " . json_last_error_msg() . " - JSON ricevuto: " . $historyUpdatesJson);
        // Se il JSON è malformato, trattiamo historyUpdates come vuoto per evitare errori.
        $historyUpdates = [];
    } else {
        // --- DEBUG: LOG DEI DATI DI CRONOLOGIA DECODIFICATI ---
        error_log("Dati cronologia decodificati (historyUpdates): " . print_r($historyUpdates, true));
    }


    if ($id === null) {
        throw new Exception("ID prenotazione mancante.");
    }

    // Validazione e pulizia input
    $id = (int)$id;
    $quantity = (int)$quantity;
    $unit_price = (float)$unit_price;
    $product_total_price = (float)$product_total_price;
    $deposit_amount = (float)$deposit_amount;
    $remaining_amount = (float)$remaining_amount;
    $fornitore_id = $fornitore_id !== '' ? (int)$fornitore_id : null; // Converti a int o lascia null
    
    // Assicurati che le date siano nel formato corretto o null
    $reservation_date = !empty($reservation_date) ? $reservation_date : null;
    $data_arrivo_previsto = !empty($data_arrivo_previsto) ? $data_arrivo_previsto : null;

    // Prepara la query di aggiornamento per la tabella 'prenotazioni_prodotti'
    // Assicurati che tutti i nomi delle colonne corrispondano a quelli nel tuo database
    $sql_update_prenotazione = "UPDATE prenotazioni_prodotti SET
                                product_name = ?, quantity = ?, unit_price = ?, product_total_price = ?,
                                deposit_amount = ?, remaining_amount = ?, customer_name = ?, customer_phone = ?,
                                reservation_date = ?, fornitore_id = ?, data_arrivo_previsto = ?,
                                notestext = ?, status = ?
                                WHERE id = ?";

    $stmt_prenotazione = $conn->prepare($sql_update_prenotazione);

    if ($stmt_prenotazione === false) {
        error_log("Errore nella preparazione della query di aggiornamento prenotazione: " . $conn->error);
        throw new mysqli_sql_exception("Errore nella preparazione della query di aggiornamento prenotazione: " . $conn->error);
    }

    // Bind dei parametri per l'aggiornamento della prenotazione
    // 's' per stringa, 'i' per intero, 'd' per double (float)
    // Corretto il tipo per customer_name da 'd' a 's'
    $stmt_prenotazione->bind_param("sidddssssisssi",
        $product_name, $quantity, $unit_price, $product_total_price,
        $deposit_amount, $remaining_amount, $customer_name, $customer_phone,
        $reservation_date, $fornitore_id, $data_arrivo_previsto,
        $notestext, $status, $id
    );

    $stmt_prenotazione->execute();

    if ($stmt_prenotazione->error) {
        error_log("Errore nell'esecuzione dell'aggiornamento prenotazione (prenotazioni_prodotti): " . $stmt_prenotazione->error);
        throw new mysqli_sql_exception("Errore nell'esecuzione dell'aggiornamento prenotazione: " . $stmt_prenotazione->error);
    } else {
        error_log("Aggiornamento prenotazione (prenotazioni_prodotti) eseguito con successo per ID: " . $id . ". Righe influenzate: " . $stmt_prenotazione->affected_rows);
    }


    // NEW: Inserimento delle voci di cronologia nella tabella 'prenotazioni_storico'
    if (!empty($historyUpdates) && is_array($historyUpdates)) {
        $stmt_history = $conn->prepare("INSERT INTO prenotazioni_storico (prenotazione_id, data_evento, evento_descrizione, utente) VALUES (?, ?, ?, ?)");
        if ($stmt_history === false) {
            error_log("Errore nella preparazione della query di inserimento cronologia (prenotazioni_storico): " . $conn->error);
            throw new mysqli_sql_exception("Errore nella preparazione della query di inserimento cronologia: " . $conn->error);
        }

        foreach ($historyUpdates as $entry) {
            // Assicurati che i tipi di dato corrispondano alle colonne del tuo DB
            // e che i campi necessari siano presenti nell'array $entry
            $history_prenotazione_id = $entry['prenotazione_id'] ?? null;
            $history_data_evento = $entry['data_evento'] ?? null;
            $history_evento_descrizione = $entry['evento_descrizione'] ?? null;
            $history_utente = $entry['utente'] ?? 'Sistema'; // Default a 'Sistema' se non fornito

            if ($history_prenotazione_id && $history_data_evento && $history_evento_descrizione) {
                $stmt_history->bind_param("isss", $history_prenotazione_id, $history_data_evento, $history_evento_descrizione, $history_utente);
                $stmt_history->execute();
                if ($stmt_history->error) {
                    error_log("Errore durante l'inserimento della voce di cronologia per prenotazione ID {$history_prenotazione_id}: " . $stmt_history->error);
                    // Non bloccare l'intera operazione per un errore di log, ma registra l'errore.
                } else {
                    error_log("Voce di cronologia inserita con successo per prenotazione ID {$history_prenotazione_id}: " . $history_evento_descrizione);
                }
            } else {
                error_log("Voci di cronologia incomplete o mancanti (saltate): " . print_r($entry, true));
            }
        }
        $stmt_history->close();
    } elseif (!empty($historyUpdates) && !is_array($historyUpdates)) {
        error_log("Il parametro history_updates non è un array valido dopo la decodifica JSON: " . $historyUpdatesJson);
    }


    $response['success'] = true;
    $response['message'] = 'Prenotazione aggiornata con successo!';

} catch (mysqli_sql_exception $e) {
    $response['message'] = 'Errore database: ' . $e->getMessage();
    error_log("Errore SQL finale in update_reservation.php: " . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Errore: ' . $e->getMessage();
    error_log("Errore generale finale in update_reservation.php: " . $e->getMessage());
} finally {
    // Chiudi la connessione al database
    if (isset($conn) && $conn !== null) {
        $conn->close();
    }
}

echo json_encode($response);
?>
