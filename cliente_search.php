<?php
header('Content-Type: application/json');
if (!isset($_GET['q'])) {
    echo json_encode([]);
    exit;
}

$query = $_GET['q'];

$host = 'localhost';
$user = 'root';         // cambia con i tuoi dati
$password = '';   // cambia con i tuoi dati
$dbname = 'gestionale_tsservice';     // cambia con i tuoi dati

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

// Cerca nome + cognome che contengano la query (limite 10 risultati)
$stmt = $conn->prepare("SELECT nome, cognome FROM clienti_nuovo WHERE CONCAT(nome, ' ', cognome) LIKE ? LIMIT 10");
$like_query = "%$query%";
$stmt->bind_param('s', $like_query);
$stmt->execute();

$result = $stmt->get_result();
$clienti = [];

while ($row = $result->fetch_assoc()) {
    $clienti[] = $row['nome'] . ' ' . $row['cognome'];
}

$stmt->close();
$conn->close();

echo json_encode($clienti);
