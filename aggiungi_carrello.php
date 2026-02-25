<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

$id = $_POST['id'] ?? null;
$quantita = intval($_POST['quantita'] ?? 0);
$prezzo = floatval($_POST['prezzo'] ?? 0);

if (!$id || $quantita <= 0 || $prezzo <= 0) {
    echo json_encode(['success' => false, 'message' => 'Dati non validi']);
    exit;
}

// Connessione DB per prendere dati prodotto e giacenza
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'gestionale_tsservice';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Errore connessione DB']);
    exit;
}

$stmt = $conn->prepare("SELECT nome, quantita FROM prodotti WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Prodotto non trovato']);
    exit;
}

$prodotto = $result->fetch_assoc();
$conn->close();

$nomeProdotto = $prodotto['nome'];
$quantitaDisponibile = intval($prodotto['quantita']);

// Controllo se supero la giacenza disponibile
$quantitaNelCarrello = 0;
if (isset($_SESSION['carrello'])) {
    foreach ($_SESSION['carrello'] as $item) {
        if ($item['id'] == $id && $item['prezzo'] == $prezzo) {
            $quantitaNelCarrello = $item['quantita'];
            break;
        }
    }
}

if ($quantitaNelCarrello + $quantita > $quantitaDisponibile) {
    echo json_encode(['success' => false, 'message' => 'Quantità richiesta superiore alla giacenza disponibile']);
    exit;
}

// Aggiunta al carrello in sessione
if (!isset($_SESSION['carrello'])) {
    $_SESSION['carrello'] = [];
}

$foundIndex = null;
foreach ($_SESSION['carrello'] as $index => $item) {
    if ($item['id'] == $id && $item['prezzo'] == $prezzo) {
        $foundIndex = $index;
        break;
    }
}

if ($foundIndex !== null) {
    $_SESSION['carrello'][$foundIndex]['quantita'] += $quantita;
} else {
    $_SESSION['carrello'][] = [
        'id' => $id,
        'nome' => $nomeProdotto,
        'quantita' => $quantita,
        'prezzo' => $prezzo
    ];
}

echo json_encode(['success' => true]);
?>
