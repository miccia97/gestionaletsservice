<?php
// api/salva_assistenza.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once 'db.php'; // Same directory

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica la connessione al database
    if (!isset($conn) || $conn === null) {
        $response['message'] = 'Errore di connessione al database.';
        echo json_encode($response);
        exit;
    }

    // Recupera i dati dal POST
    $cliente_id = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
    $cliente_nome_temp = $_POST['cliente_nome_temp'] ?? null; // Usato se cliente_id non è presente
    $modello = $_POST['modello'] ?? '';
    $imei = $_POST['imei'] ?? '';
    $codice_sblocco = $_POST['codice_sblocco'] ?? '';
    $codice_sblocco_grafico = $_POST['codice_sblocco_grafico'] ?? '';
    $account = $_POST['account'] ?? '';
    $salva_dati = isset($_POST['salva_dati']) ? (int)$_POST['salva_dati'] : 0;
    $diagnosi = $_POST['diagnosi'] ?? '';
    $costo_preventivato = isset($_POST['costo_preventivato']) ? (float)$_POST['costo_preventivato'] : 0.00;
    $hardware_ritirato = $_POST['hardware_ritirato'] ?? '';
    $dispositivo_sostitutivo = $_POST['dispositivo_sostitutivo'] ?? '';
    $stato_riparazione = $_POST['stato_riparazione'] ?? 'In Lavorazione';

    // Validazione minima dei dati
    if (empty($modello) || empty($diagnosi)) {
        $response['message'] = 'Modello e Diagnosi sono campi obbligatori.';
        echo json_encode($response);
        $conn->close();
        exit;
    }

    // Se cliente_id non è fornito, potremmo voler creare un nuovo cliente "temporaneo"
    // o semplicemente usarne il nome, a seconda della logica di business.
    // Per ora, se cliente_id è nullo, e cliente_nome_temp è fornito, lo consideriamo come un cliente non registrato formalmente.
    // Potresti voler aggiungere logica per cercare il cliente per nome/cognome qui se non si usa l'autocomplete.

    try {
        // Genera il prossimo numero progressivo
        $max_progressivo = 0;
        $stmt_max_progressivo = $conn->prepare("SELECT MAX(CAST(progressivo AS UNSIGNED)) AS max_num FROM riparazioni");
        if ($stmt_max_progressivo) {
            $stmt_max_progressivo->execute();
            $result_max_progressivo = $stmt_max_progressivo->get_result();
            if ($row = $result_max_progressivo->fetch_assoc()) {
                $max_progressivo = (int)$row['max_num'];
            }
            $stmt_max_progressivo->close();
        } else {
            throw new Exception("Errore nella preparazione della query MAX progressivo: " . $conn->error);
        }
        $new_progressivo = $max_progressivo + 1;
        // Formatta il progressivo con zeri iniziali (es. 001, 010, 100)
        $progressivo = str_pad($new_progressivo, 3, '0', STR_PAD_LEFT);

        // Prepara la query di inserimento
        $sql = "INSERT INTO riparazioni (
            progressivo, cliente_id, modello, imei, codice_sblocco, codice_sblocco_grafico,
            account, salva_dati, diagnosi, costo_preventivato, hardware_ritirato,
            dispositivo_sostitutivo, stato_riparazione, data_creazione
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )";
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Errore nella preparazione della query: " . $conn->error);
        }

        // Bind dei parametri
        // 's' per stringa, 'i' per intero, 'd' per double (float)
        $stmt->bind_param("sisssssisdsss",
            $progressivo,
            $cliente_id, // Sarà null se non selezionato dall'autocomplete
            $modello,
            $imei,
            $codice_sblocco,
            $codice_sblocco_grafico,
            $account,
            $salva_dati,
            $diagnosi,
            $costo_preventivato,
            $hardware_ritirato,
            $dispositivo_sostitutivo,
            $stato_riparazione
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Assistenza #$progressivo salvata con successo!";
            $response['id_assistenza'] = $conn->insert_id;
            $response['progressivo'] = $progressivo;
        } else {
            throw new Exception("Errore nell'esecuzione della query: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        $response['message'] = "Errore durante il salvataggio dell'assistenza: " . $e->getMessage();
        error_log("Errore salva_assistenza.php: " . $e->getMessage());
    } finally {
        if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            $conn->close();
        }
    }
} else {
    $response['message'] = 'Metodo di richiesta non valido.';
}

echo json_encode($response);
?>
