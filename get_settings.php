<?php
// get_settings.php
// Recupera tutte le impostazioni dalla tabella 'settings' e le restituisce in formato JSON.

// Abilita la visualizzazione degli errori per il debug (RIMUOVI IN PRODUZIONE!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php'; // Includi il file di connessione al database

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'data' => []];

try {
    $sql = "SELECT setting_key, setting_value, data_type FROM settings";
    $result = $conn->query($sql);

    if ($result) {
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $key = $row['setting_key'];
            $value = $row['setting_value'];
            $dataType = $row['data_type'];

            // Converte il valore nel tipo di dato corretto
            switch ($dataType) {
                case 'boolean':
                    $settings[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'integer':
                    $settings[$key] = (int)$value;
                    break;
                case 'float':
                    $settings[$key] = (float)$value;
                    break;
                case 'json':
                    $settings[$key] = json_decode($value, true);
                    break;
                default: // 'string' o qualsiasi altro tipo
                    $settings[$key] = $value;
                    break;
            }
        }
        $response['success'] = true;
        $response['data'] = $settings;
    } else {
        $response['message'] = 'Errore durante il recupero delle impostazioni: ' . $conn->error;
        error_log("Errore get_settings.php: " . $conn->error);
    }

} catch (Exception $e) {
    $response['message'] = 'Eccezione: ' . $e->getMessage();
    error_log("Eccezione in get_settings.php: " . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close();
    }
}

echo json_encode($response);
?>
