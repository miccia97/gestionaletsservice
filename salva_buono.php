<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
header('Content-Type: application/json');

require_once 'db.php';

try {

    // Dati dal corpo JSON
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input || !isset($input['codiceBuono'], $input['importoBuono'], $input['dataScadenza'])) {
        throw new Exception("Dati incompleti o non ricevuti correttamente.");
    }

    $codice = $input['codiceBuono'];
    $importo = floatval($input['importoBuono']);
    $scadenza = $input['dataScadenza'];

    // Prepara e inserisci
    $stmt = $conn->prepare("INSERT INTO buono_spesa (codice_buono, importo, data_scadenza) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $codice, $importo, $scadenza);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Buono creato con successo."]);

} catch (mysqli_sql_exception $e) {
    if (str_contains($e->getMessage(), "Duplicate entry")) {
        echo json_encode(["success" => false, "message" => "Codice buono già esistente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Errore MySQL: " . $e->getMessage()]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
