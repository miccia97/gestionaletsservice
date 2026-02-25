<?php
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Avvia la sessione PHP
session_start();

// Imposta l'header per la risposta JSON
header('Content-Type: application/json');

// Includi il file di connessione al database
if (!file_exists('db.php')) {
    echo json_encode(['success' => false, 'message' => 'Errore critico: Il file db.php non è stato trovato!']);
    exit;
}
require_once 'db.php';

// Controlla la connessione al database
if (!isset($conn) || $conn === null) {
    $db_error_message = isset($db_connection_error) ? $db_connection_error : 'Connessione al database non stabilita.';
    echo json_encode(['success' => false, 'message' => 'Errore critico: ' . htmlspecialchars($db_error_message, ENT_QUOTES, 'UTF-8')]);
    exit;
}

// Verifica che la richiesta sia di tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo di richiesta non valido.']);
    exit;
}

// Recupera i dati dalla richiesta POST
$productId = filter_input(INPUT_POST, 'productId', FILTER_VALIDATE_INT);
$quantityToUnload = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$repairId = filter_input(INPUT_POST, 'repairId', FILTER_VALIDATE_INT);

// Validazione dei dati di input
if ($productId === null || $productId === false ||
    $quantityToUnload === null || $quantityToUnload === false || $quantityToUnload <= 0 ||
    $repairId === null || $repairId === false) {
    echo json_encode(['success' => false, 'message' => 'Dati di input non validi. Assicurati che ID Prodotto, Quantità e ID Riparazione siano numeri validi e che la quantità sia maggiore di zero.']);
    exit;
}

// Inizia una transazione per garantire l'integrità dei dati
$conn->begin_transaction();

try {
    // 1. Recupera la giacenza attuale del prodotto
    $stmt = $conn->prepare("SELECT nome, quantita FROM prodotti WHERE id = ? FOR UPDATE"); // FOR UPDATE blocca la riga
    if ($stmt === false) {
        throw new Exception("Errore nella preparazione della query per recupero prodotto: " . $conn->error);
    }
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if (!$product) {
        throw new Exception("Prodotto non trovato nel magazzino.");
    }

    $currentStock = $product['quantita'];
    $productName = $product['nome'];

    // 2. Controlla se la giacenza è sufficiente
    if ($currentStock < $quantityToUnload) {
        throw new Exception("Giacenza insufficiente per il prodotto '$productName'. Disponibili: $currentStock.");
    }

    // 3. Decrementa la quantità del prodotto nel magazzino
    $newStock = $currentStock - $quantityToUnload;
    $stmt = $conn->prepare("UPDATE prodotti SET quantita = ? WHERE id = ?");
    if ($stmt === false) {
        throw new Exception("Errore nella preparazione della query per aggiornamento prodotto: " . $conn->error);
    }
    $stmt->bind_param("ii", $newStock, $productId);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        throw new Exception("Nessuna riga aggiornata per il prodotto. Potrebbe non esistere o la quantità era già la stessa.");
    }
    $stmt->close();

    // 4. Registra il movimento nella tabella riparazioni_articoli_movimenti
    $tipoMovimento = 'scarico_riparazione';
    $stmt = $conn->prepare("INSERT INTO riparazioni_articoli_movimenti (riparazione_id, prodotto_id, quantita_movimentata, tipo_movimento, note) VALUES (?, ?, ?, ?, ?)");
    if ($stmt === false) {
        throw new Exception("Errore nella preparazione della query per inserimento movimento: " . $conn->error);
    }
    $noteMovimento = "Scarico per riparazione: '$productName' - $quantityToUnload pz.";
    $stmt->bind_param("iiiss", $repairId, $productId, $quantityToUnload, $tipoMovimento, $noteMovimento);
    $stmt->execute();
    $stmt->close();

    // 5. Registra l'evento nella tabella riparazioni_storico
    $eventoDescrizione = "Articolo \"$productName\" associato e scaricato dal magazzino: $quantityToUnload pz. Nuova giacenza: $newStock.";
    $utente = "Sistema"; // O potresti passare l'utente loggato se hai un sistema di autenticazione
    $stmt = $conn->prepare("INSERT INTO riparazioni_storico (riparazione_id, evento_descrizione, utente) VALUES (?, ?, ?)");
    if ($stmt === false) {
        throw new Exception("Errore nella preparazione della query per inserimento storico: " . $conn->error);
    }
    $stmt->bind_param("iss", $repairId, $eventoDescrizione, $utente);
    $stmt->execute();
    $stmt->close();

    // 6. Recupera la lista aggiornata di tutti i prodotti
    $productsList = [];
    $stmt = $conn->prepare("SELECT id, nome, quantita FROM prodotti ORDER BY nome");
    if ($stmt === false) {
        throw new Exception("Errore nella preparazione della query per recupero lista prodotti: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $productsList = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Se tutto va bene, esegui il commit della transazione
    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Magazzino aggiornato e movimento registrato con successo!',
        'newStock' => $newStock,
        'productsList' => $productsList // Aggiunta la lista dei prodotti
    ]);

} catch (Exception $e) {
    // In caso di errore, annulla la transazione
    $conn->rollback();
    error_log("Errore in update_stock_for_repair.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento del magazzino: ' . $e->getMessage()]);
} finally {
    // Chiudi la connessione al database
    if (isset($conn) && $conn !== null) {
        $conn->close();
    }
}
?>
