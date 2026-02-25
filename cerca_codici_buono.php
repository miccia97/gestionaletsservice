<?php
// Connessione al database
$conn = new mysqli("localhost", "root", "", "gestionale_tsservice"); // Cambia nome_database

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

$suggerimenti = [];

if (!empty($q)) {
    $stmt = $conn->prepare("SELECT codice_buono FROM buoni_spesa WHERE codice_buono LIKE CONCAT(?, '%') LIMIT 10");
    $stmt->bind_param("s", $q);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $suggerimenti[] = $row['codice_buono'];
    }

    $stmt->close();
}

echo json_encode($suggerimenti);
$conn->close();
