<?php
header('Content-Type: application/json');

$host = 'localhost';
$db   = 'gestionale_tsservice';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connessione al DB fallita']);
    exit;
}

$nome = $conn->real_escape_string($_POST['nomeBuono'] ?? '');
$valore = floatval($_POST['valoreBuono'] ?? 0);
$data_scadenza = $conn->real_escape_string($_POST['dataScadenza'] ?? '');
$destinatario = $conn->real_escape_string($_POST['destinatario'] ?? '');
$note = $conn->real_escape_string($_POST['note'] ?? '');

if (empty($nome) || $valore <= 0 || empty($data_scadenza)) {
    echo json_encode(['success' => false, 'message' => 'Compila tutti i campi obbligatori']);
    exit;
}

$sql = "INSERT INTO buoni_regalo (nome, valore, data_scadenza, destinatario, note)
        VALUES ('$nome', $valore, '$data_scadenza', '$destinatario', '$note')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true, 'message' => 'Buono salvato correttamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $conn->error]);
}

$conn->close();
?>
