<?php
// stessa connessione e query con filtri come in index.php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'gestionale_tsservice';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$categoriaSelezionata = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$nomeProdotto = isset($_GET['nomeProdotto']) ? $_GET['nomeProdotto'] : '';
$barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
$imei = isset($_GET['imei']) ? $_GET['imei'] : '';

$query = "SELECT id, nome, descrizione, prezzo_vendita1, prezzo_vendita2, prezzo_acquisto, quantita, categoria, immagine FROM prodotti WHERE 1=1 ";
$params = [];
$types = "";

if ($categoriaSelezionata !== '') {
    $query .= " AND categoria = ? ";
    $params[] = $categoriaSelezionata;
    $types .= "s";
}
if ($nomeProdotto !== '') {
    $query .= " AND nome LIKE ? ";
    $params[] = "%" . $nomeProdotto . "%";
    $types .= "s";
}
if ($barcode !== '') {
    $query .= " AND barcode LIKE ? ";
    $params[] = "%" . $barcode . "%";
    $types .= "s";
}
if ($imei !== '') {
    $query .= " AND imei LIKE ? ";
    $params[] = "%" . $imei . "%";
    $types .= "s";
}

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Output HTML prodotti
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
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
                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 
                    0c-1.1 0-1.99.9-1.99 2S15.9 22 17 22s2-.9 2-2-.9-2-2-2zM7.16 
                    14l.84-2h7.45c.75 0 1.41-.41 1.75-1.03L21 4H5.21l-.94-2H0v2h2l3.6 
                    7.59-1.35 2.44C3.52 14.37 4.48 16 6 16h12v-2H7.16z"/>
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
$conn->close();
?>
