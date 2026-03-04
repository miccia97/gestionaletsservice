<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once 'db.php';
if (!$conn) {
    echo json_encode(["error" => "Connessione al database fallita."]);
    exit();
}

// CORREZIONE QUI: I nomi dei campi sono stati aggiornati
$sql = "SELECT id, nome, categoria, prezzo_vendita1, quantita, immagine FROM prodotti";
$result = $conn->query($sql);

if ($result === false) {
    echo json_encode(["error" => "Errore nella query SQL: " . $conn->error]);
    $conn->close();
    exit();
}

$products = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id'],
            'name' => $row['nome'],
            'category' => $row['categoria'],
            // CORREZIONE QUI: Mappatura con i campi del tuo DB
            'price' => (float) $row['prezzo_vendita1'],
            'stock' => (int) $row['quantita'],
            'image' => $row['immagine']
        ];
    }
}
$conn->close();

echo json_encode($products);
?>