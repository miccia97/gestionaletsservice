<?php
include('db.php');

// Recupera l'ID del cliente dalla query string
$id_cliente = $_GET['id'];

// Esegui la query per eliminare il cliente
$query = "DELETE FROM clienti WHERE id = $id_cliente";

if ($conn->query($query) === TRUE) {
    echo "Cliente eliminato con successo!";
    echo "<br><a href='clienti.php'>Vai alla lista clienti</a>";
} else {
    echo "Errore: " . $conn->error;
}
?>
