<?php
// Connessione al DB
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'gestionale_tsservice';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$nomeProdotto = isset($_GET['nomeProdotto']) ? $_GET['nomeProdotto'] : '';
$barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
$imei = isset($_GET['imei']) ? $_GET['imei'] : '';

// Costruzione query
$query = "SELECT * FROM prodotti WHERE 1=1";
$params = [];
$types = '';

if ($nomeProdotto !== '') {
    $query .= " AND nome LIKE ?";
    $params[] = '%' . $nomeProdotto . '%';
    $types .= 's';
}
if ($barcode !== '') {
    $query .= " AND descrizione LIKE ?";
    $params[] = '%' . $barcode . '%';
    $types .= 's';
}
if ($imei !== '') {
    $query .= " AND imei LIKE ?";
    $params[] = '%' . $imei . '%';
    $types .= 's';
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Stessa struttura dei tuoi div prodotto in index.php, esempio:
        echo '<div class="product-card">';
        echo '<img src="images/' . htmlspecialchars($row['immagine']) . '" alt="Foto Prodotto">';
        echo '<strong>' . htmlspecialchars($row['nome']) . '</strong>';
        echo '<div class="prezzi">';
        echo '<label><input type="checkbox" name="prezzo1"> €' . number_format($row['prezzo_vendita1'], 2) . '</label>';
        echo '<label><input type="checkbox" name="prezzo2"> €' . number_format($row['prezzo_vendita2'], 2) . '</label>';
        echo '</div>';
        echo '<div class="giacenza">Giacenza: ' . intval($row['quantita']) . '</div>';
        echo '<div class="cart-controls">';
        echo '<button class="carrello-btn" title="Aggiungi al carrello" aria-label="Aggiungi al carrello">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="green" viewBox="0 0 24 24">
                    <path d="M10 2H2v2h8V2zm12 0h-8v2h8V2zM4 6h16v2H4V6zm0 4h16v2H4v-2zm0 4h10v2H4v-2zm0 4h10v2H4v-2z"/>
                </svg>
              </button>';
        echo '<button class="freccia-down">−</button>';
        echo '<input type="number" value="1" min="1" max="' . intval($row['quantita']) . '" class="quantita-input">';
        echo '<button class="freccia-up">+</button>';
        echo '</div>';
        echo '</div>';
    }
} else {
    echo '<p>Nessun prodotto trovato.</p>';
}

$stmt->close();
$conn->close();
?>
