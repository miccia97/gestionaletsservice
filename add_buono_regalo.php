<?php
header('Content-Type: application/json');

// Dettagli di connessione al database (Assicurati che db.php esista e sia corretto)
require_once 'db.php'; // Incluide il tuo file di connessione al database

// Controlla la connessione dal db.php
if (isset($db_connection_error) && $db_connection_error !== null) {
    echo json_encode(["status" => "error", "message" => "Connessione al database fallita: " . $db_connection_error]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if ($data === null) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Richiesta JSON non valida"]);
    exit();
}

// Estrai e sanitizza i dati dal JSON
$codice_buono = $conn->real_escape_string($data['codice_buono'] ?? '');
$valore_buono = (float)($data['valore_buono'] ?? 0.00);
$data_emissione = $conn->real_escape_string($data['data_emissione'] ?? date('Y-m-d')); // Default alla data odierna
$data_scadenza = $conn->real_escape_string($data['data_scadenza'] ?? null);
$destinatario_nome = $conn->real_escape_string($data['destinatario_nome'] ?? null);
$destinatario_email = $conn->real_escape_string($data['destinatario_email'] ?? null);
$messaggio = $conn->real_escape_string($data['messaggio'] ?? null);
$stato = $conn->real_escape_string($data['stato'] ?? 'Attivo');
$note = $conn->real_escape_string($data['note'] ?? null);
$cliente_id = ($data['cliente_id'] !== null && is_numeric($data['cliente_id'])) ? (int)$data['cliente_id'] : null;

// Validazione dati essenziali
if (empty($codice_buono) || $valore_buono <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Codice Buono e Valore Buono sono obbligatori e validi."]);
    exit();
}

try {
    // Prepara la query SQL per l'inserimento
    // ASSICURATI CHE LA TUA TABELLA 'buoni_regalo' ABBIA QUESTE COLONNE
    $sql = "INSERT INTO buoni_regalo (
                codice_buono, valore_buono, data_emissione, data_scadenza,
                destinatario_nome, destinatario_email, messaggio, stato, note, cliente_id, data_creazione
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Errore nella preparazione della query: " . $conn->error);
    }

    // Tipi: sdsisssiss (string, decimal, string, string, string, string, string, string, string, int)
    // Nota: 's' per data_scadenza, destinatario_nome, destinatario_email, messaggio, note anche se NULL
    // 'i' per cliente_id, che può essere NULL, bind_param lo gestisce automaticamente.
    $stmt->bind_param('sdsissssis',
        $codice_buono, $valore_buono, $data_emissione, $data_scadenza,
        $destinatario_nome, $destinatario_email, $messaggio, $stato, $note, $cliente_id
    );

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Buono regalo creato con successo!", "id" => $conn->insert_id, "codice_buono" => $codice_buono]);
    } else {
        throw new Exception("Errore nell'esecuzione della query: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Errore durante la creazione del buono regalo: " . $e->getMessage()]);
    error_log("Errore Buono Regalo: " . $e->getMessage());
} finally {
    $conn->close();
}
?>