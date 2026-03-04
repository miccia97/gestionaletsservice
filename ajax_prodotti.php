<?php
header('Content-Type: application/json');
require_once 'db.php';

$categoriaSelezionata = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$nomeProdotto = isset($_GET['nomeProdotto']) ? $_GET['nomeProdotto'] : '';
$barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
$imei = isset($_GET['imei']) ? $_GET['imei'] : '';

$query = "SELECT id, nome, immagine, prezzo_vendita1, prezzo_vendita2, prezzo_acquisto, quantita, categoria FROM prodotti WHERE 1=1 ";
$params = [];
$types = "";

if ($categoriaSelezionata !== '') {
    $query .= " AND categoria = ? ";
    $params[] = $categoriaSelezionata;
    $types .= "s";
}
if ($nomeProdotto !== '') {
    $query .= " AND nome LIKE ? ";
    $params[] = "%$nomeProdotto%";
    $types .= "s";
}
if ($barcode !== '') {
    $query .= " AND barcode LIKE ? ";
    $params[] = "%$barcode%";
    $types .= "s";
}
if ($imei !== '') {
    $query .= " AND imei LIKE ? ";
    $params[] = "%$imei%";
    $types .= "s";
}

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$prodotti = [];
while ($row = $result->fetch_assoc()) {
    $prodotti[] = $row;
}

$conn->close();
echo json_encode($prodotti);
