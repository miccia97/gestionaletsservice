<?php
// Connessione al database
include 'db.php'; // assicurati che questo file contenga mysqli_connect()

// Esegui query per trovare l'id massimo
$sql = "SELECT MAX(id) AS max_id FROM permute_nuovo";
$result = $conn->query($sql);

$nextProgressivo = 1; // Default

if ($result && $row = $result->fetch_assoc()) {
    $nextId = $row['max_id'] + 1;
    $nextProgressivo = "PRG-" . str_pad($nextId, 5, "0", STR_PAD_LEFT);
}

echo $nextProgressivo;

$conn->close();
?>
