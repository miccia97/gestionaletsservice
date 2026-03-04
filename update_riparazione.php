<?php
// Capture any PHP warnings/notices so they don't corrupt JSON output
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Avvia la sessione PHP
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Imposta l'header per la risposta JSON
header('Content-Type: application/json');

// Helper function: clean output buffer before sending JSON
function sendJson($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

// Includi il file di connessione al database
if (!file_exists('db.php')) {
    echo json_encode(['success' => false, 'message' => 'Errore critico: Il file db.php non è stato trovato!']);
    exit;
}
require_once 'db.php';

// Controlla la connessione al database
if (!isset($conn) || $conn === null) {
    $db_error_message = isset($db_connection_error) ? $db_connection_error : 'Connessione al database non stabilita.';
    echo json_encode(['success' => false, 'message' => 'Errore critico: ' . htmlspecialchars($db_error_message, ENT_QUOTES, 'UTF-8')]);
    exit;
}

// Verifica che la richiesta sia di tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo di richiesta non valido.']);
    exit;
}

// Recupera e valida i dati del form principale della riparazione
$repairId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$cliente_nome = filter_input(INPUT_POST, 'cliente_nome', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$cliente_cognome = filter_input(INPUT_POST, 'cliente_cognome', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$diagnosi = filter_input(INPUT_POST, 'diagnosi', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$modello = filter_input(INPUT_POST, 'modello', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$stato = filter_input(INPUT_POST, 'stato', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$costo_effettivo = filter_input(INPUT_POST, 'costo_effettivo', FILTER_VALIDATE_FLOAT);

// Decodifica gli aggiornamenti della cronologia e dei movimenti
$history_updates_json = $_POST['history_updates'] ?? '[]';
$movements_updates_json = $_POST['movements_updates'] ?? '[]';

$history_updates = json_decode($history_updates_json, true);
$movements_updates = json_decode($movements_updates_json, true);

if ($repairId === null || $repairId === false ||
    $cliente_nome === null || $cliente_nome === false ||
    $cliente_cognome === null || $cliente_cognome === false ||
    $stato === null || $stato === false) {
    echo json_encode(['success' => false, 'message' => 'Dati della riparazione principali non validi o mancanti.']);
    exit;
}

// Inizia una transazione per garantire l'integrità dei dati
$conn->begin_transaction();

try {
    // Prima di aggiornare la riparazione, recupera il cliente_id associato
    $stmt_get_client_id = $conn->prepare("SELECT cliente_id FROM riparazioni WHERE id = ?");
    if ($stmt_get_client_id === false) {
        throw new Exception("Errore nella preparazione della query per recuperare cliente_id: " . $conn->error);
    }
    $stmt_get_client_id->bind_param("i", $repairId);
    $stmt_get_client_id->execute();
    $result_get_client_id = $stmt_get_client_id->get_result();
    $repair_data = $result_get_client_id->fetch_assoc();
    $stmt_get_client_id->close();

    $cliente_id_from_repair = $repair_data['cliente_id'] ?? null;

    // 1. Aggiorna i dati nella tabella 'clienti_nuovo' se cliente_id esiste e nomi sono cambiati
    if ($cliente_id_from_repair !== null) {
        // Recupera i nomi attuali del cliente per confrontarli
        $stmt_get_current_client = $conn->prepare("SELECT nome, cognome FROM clienti_nuovo WHERE id = ?");
        if ($stmt_get_current_client === false) {
            throw new Exception("Errore nella preparazione della query per recuperare nomi cliente attuali: " . $conn->error);
        }
        $stmt_get_current_client->bind_param("i", $cliente_id_from_repair);
        $stmt_get_current_client->execute();
        $current_client_data = $stmt_get_current_client->get_result()->fetch_assoc();
        $stmt_get_current_client->close();

        // Solo se i nomi sono effettivamente cambiati, aggiorna
        if ($current_client_data && ($current_client_data['nome'] !== $cliente_nome || $current_client_data['cognome'] !== $cliente_cognome)) {
            $stmt_update_client = $conn->prepare("UPDATE clienti_nuovo SET nome = ?, cognome = ? WHERE id = ?");
            if ($stmt_update_client === false) {
                throw new Exception("Errore nella preparazione della query di aggiornamento cliente: " . $conn->error);
            }
            $stmt_update_client->bind_param("ssi", $cliente_nome, $cliente_cognome, $cliente_id_from_repair);
            $stmt_update_client->execute();
            $stmt_update_client->close();
        }
    } else {
        // Se cliente_id è NULL, potresti voler gestire la creazione di un nuovo cliente
        // Per ora, logghiamo un avviso. La creazione di un nuovo cliente non è inclusa in questo fix diretto.
        error_log("Riparazione ID $repairId non ha un cliente_id associato per aggiornare nome/cognome.");
    }

    // 2. Aggiorna i dati nella tabella 'riparazioni' (senza cliente_nome e cliente_cognome)
    $stmt = $conn->prepare("UPDATE riparazioni SET 
                            telefono = ?, 
                            diagnosi = ?, 
                            modello = ?, 
                            stato = ?, 
                            costo_effettivo = ? 
                            WHERE id = ?");

    if ($stmt === false) {
        throw new Exception("Errore nella preparazione della query di aggiornamento riparazione: " . $conn->error);
    }

    $stmt->bind_param("ssssdi", 
                       $telefono, 
                       $diagnosi, 
                       $modello, 
                       $stato, 
                       $costo_effettivo, 
                       $repairId);
    $stmt->execute();
    $stmt->close();

    // 3. Inserisci nuove voci nello storico (riparazioni_storico)
    if (!empty($history_updates)) {
        $stmt_history = $conn->prepare("INSERT INTO riparazioni_storico (riparazione_id, data_evento, evento_descrizione, utente) VALUES (?, ?, ?, ?)");
        if ($stmt_history === false) {
            throw new Exception("Errore nella preparazione della query di inserimento storico: " . $conn->error);
        }
        foreach ($history_updates as $entry) {
            // Assicurati che i dati siano validi e usa valori di default se necessario
            $hist_repair_id = $entry['riparazione_id'] ?? $repairId;
            $hist_evento_descrizione = $entry['evento_descrizione'] ?? 'Evento sconosciuto.';
            $hist_utente = $entry['utente'] ?? 'Sistema';

            // Converti la stringa ISO 8601 nel formato DATETIME di MySQL
            $datetime_obj = date_create($entry['data_evento'] ?? null);
            if ($datetime_obj) {
                $hist_data_evento = date_format($datetime_obj, 'Y-m-d H:i:s');
            } else {
                $hist_data_evento = date('Y-m-d H:i:s'); // Fallback all'ora del server
            }

            $stmt_history->bind_param("isss", $hist_repair_id, $hist_data_evento, $hist_evento_descrizione, $hist_utente);
            $stmt_history->execute();
        }
        $stmt_history->close();
    }

    // 4. Inserisci nuovi movimenti (riparazioni_articoli_movimenti)
    // Nota: lo scarico effettivo del magazzino è gestito da update_stock_for_repair.php
    // Questo blocco si limita a registrare i movimenti passati dall'interfaccia se ce ne sono.
    if (!empty($movements_updates)) {
        $stmt_movements = $conn->prepare("INSERT INTO riparazioni_articoli_movimenti (riparazione_id, prodotto_id, quantita_movimentata, tipo_movimento, note, data_movimento) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt_movements === false) {
            throw new Exception("Errore nella preparazione della query di inserimento movimenti: " . $conn->error);
        }
        foreach ($movements_updates as $movement) {
            $mov_repair_id = $movement['riparazione_id'] ?? $repairId;
            $mov_prodotto_id = $movement['prodotto_id'] ?? null;
            $mov_quantita = $movement['quantita_movimentata'] ?? 0;
            $mov_tipo = $movement['tipo_movimento'] ?? 'aggiunta_riparazione';
            $mov_note = $movement['note'] ?? 'Movimento articoli.';
            
            // Converti la stringa ISO 8601 nel formato DATETIME di MySQL
            $datetime_obj_mov = date_create($movement['data_movimento'] ?? null);
            if ($datetime_obj_mov) {
                $mov_data_movimento = date_format($datetime_obj_mov, 'Y-m-d H:i:s');
            } else {
                $mov_data_movimento = date('Y-m-d H:i:s'); // Fallback all'ora del server
            }

            if ($mov_prodotto_id === null) {
                error_log("Movimento saltato: prodotto_id mancante per riparazione $mov_repair_id.");
                continue; // Salta questo movimento se manca il prodotto_id
            }

            $stmt_movements->bind_param("iiisss", $mov_repair_id, $mov_prodotto_id, $mov_quantita, $mov_tipo, $mov_note, $mov_data_movimento);
            $stmt_movements->execute();
        }
        $stmt_movements->close();
    }

    // Se tutto va bene, esegui il commit della transazione
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Riparazione aggiornata con successo!']);

} catch (Exception $e) {
    // In caso di errore, annulla la transazione
    $conn->rollback();
    error_log("Errore in update_riparazione.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento della riparazione: ' . $e->getMessage()]);
} finally {
    // Chiudi la connessione al database
    if (isset($conn) && $conn !== null) {
        $conn->close();
    }
}
?>
