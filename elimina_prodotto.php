<?php
include('db.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "DELETE FROM prodotti WHERE id = $id";

    if ($conn->query($query) === TRUE) {
        echo "Prodotto eliminato con successo.";
    } else {
        echo "Errore: " . $conn->error;
    }

    echo "<br><a href='prodotti.php'>Torna all'elenco</a>";
} else {
    echo "ID prodotto mancante.";
}
