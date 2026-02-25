<?php
// save_settings.php
// Riceve i dati delle impostazioni dal frontend e li aggiorna/inserisce nella tabella 'settings'.

// Abilita la visualizzazione degli errori per il debug (RIMUOVI IN PRODUZIONE!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php'; // Includi il file di connessione al database

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

$data = json_decode(file_get_contents('php://input'), true);

// DEBUG: Logga i dati ricevuti dal frontend
error_log("Dati ricevuti in save_settings.php: " . print_r($data, true));

if (empty($data) || !is_array($data)) {
    $response['message'] = 'Nessun dato ricevuto o formato non valido.';
    error_log("Errore: Nessun dato ricevuto o formato non valido. Dati: " . print_r($data, true));
    echo json_encode($response);
    exit();
}

// Inizia una transazione per garantire l'atomicita' degli aggiornamenti
$conn->begin_transaction();

try {
    foreach ($data as $key => $value) {
        // Determina il tipo di dato per il salvataggio
        $dataType = 'string';
        if (is_bool($value)) {
            $dataType = 'boolean';
            $value = $value ? 'true' : 'false'; // Salva booleani come stringhe 'true'/'false'
        } elseif (is_int($value)) {
            $dataType = 'integer';
        } elseif (is_float($value)) {
            $dataType = 'float';
        } elseif (is_array($value) || is_object($value)) {
            $dataType = 'json';
            $value = json_encode($value); // Salva array/oggetti come stringhe JSON
        }

        // Controlla se l'impostazione esiste già
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
        if (!$stmt_check) {
            throw new Exception("Errore nella preparazione della query di controllo: " . $conn->error);
        }
        $stmt_check->bind_param("s", $key);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($count > 0) {
            // Aggiorna l'impostazione esistente
            $stmt_update = $conn->prepare("UPDATE settings SET setting_value = ?, data_type = ? WHERE setting_key = ?");
            if (!$stmt_update) {
                throw new Exception("Errore nella preparazione dell'UPDATE: " . $conn->error);
            }
            $stmt_update->bind_param("sss", $value, $dataType, $key);
            error_log("Eseguo UPDATE per key: '{$key}', value: '{$value}', type: '{$dataType}'");
            if (!$stmt_update->execute()) {
                throw new Exception("Errore nell'esecuzione dell'UPDATE per key '{$key}': " . $stmt_update->error);
            }
            $stmt_update->close();
        } else {
            // Inserisci una nuova impostazione
            $stmt_insert = $conn->prepare("INSERT INTO settings (setting_key, setting_value, data_type) VALUES (?, ?, ?)");
            if (!$stmt_insert) {
                throw new Exception("Errore nella preparazione dell'INSERT: " . $conn->error);
            }
            $stmt_insert->bind_param("sss", $key, $value, $dataType);
            error_log("Eseguo INSERT per key: '{$key}', value: '{$value}', type: '{$dataType}'");
            if (!$stmt_insert->execute()) {
                throw new Exception("Errore nell'esecuzione dell'INSERT per key '{$key}': " . $stmt_insert->error);
            }
            $stmt_insert->close();
        }
    }

    $conn->commit(); // Commit della transazione se tutto è andato bene
    $response['success'] = true;
    $response['message'] = 'Impostazioni salvate con successo.';
    error_log("Impostazioni salvate con successo.");

} catch (Exception $e) {
    $conn->rollback(); // Rollback della transazione in caso di errore
    $response['message'] = 'Errore durante il salvataggio delle impostazioni: ' . $e->getMessage();
    error_log("Eccezione in save_settings.php: " . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close();
    }
}

echo json_encode($response);
?>
