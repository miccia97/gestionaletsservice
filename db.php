<?php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'gestionale_tsservice';

// Tenta di connettersi al database MySQL
$conn = new mysqli($host, $user, $password, $dbname);
$db_connection_error = null; // Variabile per memorizzare l'errore di connessione

// Se la connessione fallisce, memorizza l'errore
if ($conn->connect_error) {
    $db_connection_error = 'Connessione fallita: ' . $conn->connect_error;
    $conn = null; // Imposta $conn a null per indicare che la connessione non è valida
}
// Altrimenti, imposta il charset per la connessione (buona pratica)
else {
    $conn->set_charset("utf8mb4");
}
?>
