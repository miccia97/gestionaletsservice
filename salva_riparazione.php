<?php
// Connessione al DB (metti la tua configurazione)
$host = "localhost";
$user = "root";
$pass = "";
$db = "gestionale_tsservice";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = $conn->real_escape_string($_POST['cliente_id']);
    $modello = $conn->real_escape_string($_POST['modello']);
    $imei = $conn->real_escape_string($_POST['imei']);
    $codice_sblocco = $conn->real_escape_string($_POST['codice_sblocco']);
    $account = $conn->real_escape_string($_POST['account'] ?? '');
    $diagnosi = $conn->real_escape_string($_POST['diagnosi'] ?? '');
    $salva_dati = isset($_POST['salva_dati']) ? 1 : 0;
    $costo_preventivato = floatval($_POST['costo_preventivato'] ?? 0);
    $costo_effettivo = floatval($_POST['costo_effettivo'] ?? 0);
    $hardware_ritirato = $conn->real_escape_string($_POST['hardware_ritirato'] ?? '');
    $dispositivo_sostitutivo = $conn->real_escape_string($_POST['dispositivo_sostitutivo'] ?? '');
    $telefono = $conn->real_escape_string($_POST['telefono'] ?? '');
    $stato = $conn->real_escape_string($_POST['stato'] ?? '');

    // Inserisci i dati nella tabella riparazioni (adatta campi se necessario)
    $sql_insert = "INSERT INTO riparazioni (cliente_id, modello, imei, codice_sblocco, account, diagnosi, salva_dati, costo_preventivato, costo_effettivo, hardware_ritirato, dispositivo_sostitutivo, telefono, stato)
    VALUES ('$cliente_id', '$modello', '$imei', '$codice_sblocco', '$account', '$diagnosi', $salva_dati, $costo_preventivato, $costo_effettivo, '$hardware_ritirato', '$dispositivo_sostitutivo', '$telefono', '$stato')";

    if ($conn->query($sql_insert) === TRUE) {
        // Prendo l'ID appena inserito
        $last_id = $conn->insert_id;
        // Redirect a pagina stampa con id riparazione
        header("Location: stampa_riparazione.php?id=$last_id");
        exit;
    } else {
        echo "<p style='color:red;'>Errore durante il salvataggio: " . $conn->error . "</p>";
    }
} else {
    echo "Accesso non consentito.";
}
?>
