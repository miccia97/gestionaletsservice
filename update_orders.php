<?php
// update_orders.php
// Aggiorna l'ordine di visualizzazione di un gruppo di categorie/sottocategorie

// Abilita la visualizzazione degli errori per il debug (RIMUOVI IN PRODUZIONE!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php'; // Assicurati che il tuo file di configurazione del database si chiami 'db.php'

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

$data = json_decode(file_get_contents('php://input'), true);

$items = $data['items'] ?? []; // Array di oggetti {id, order}
$parentId = $data['parentId'] ?? null; // Parent ID per identificare la tabella (null per categorie principali)

if (empty($items) || !is_array($items)) {
    $response['message'] = 'Dati di riordinamento mancanti o non validi.';
    echo json_encode($response);
    exit();
}

try {
    $conn->begin_transaction(); // Inizia una transazione

    $table = ($parentId === null) ? 'categorie' : 'sottocategorie';
    $id_column = 'id'; // Entrambe le tabelle usano 'id'
    $order_column = 'display_order';
    $parent_column = 'parent_category_id'; // Solo per sottocategorie

    foreach ($items as $item) {
        $itemId = $item['id'] ?? null;
        $newOrder = $item['order'] ?? null;

        if ($itemId === null || $newOrder === null) {
            throw new Exception("ID o ordine mancante per un elemento.");
        }

        if ($table === 'categorie') {
            $stmt = $conn->prepare("UPDATE {$table} SET {$order_column} = ? WHERE {$id_column} = ?");
            $stmt->bind_param("ii", $newOrder, $itemId);
        } else { // sottocategorie
            $stmt = $conn->prepare("UPDATE {$table} SET {$order_column} = ? WHERE {$id_column} = ? AND {$parent_column} = ?");
            $stmt->bind_param("iii", $newOrder, $itemId, $parentId);
        }
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit(); // Conferma la transazione
    $response['success'] = true;
    $response['message'] = 'Ordini aggiornati con successo.';

} catch (Exception $e) {
    $conn->rollback(); // Annulla la transazione in caso di errore
    $response['message'] = 'Errore durante l\'aggiornamento degli ordini: ' . $e->getMessage();
    // Per debug, puoi anche stampare l'errore direttamente nei log del server:
    // error_log("Errore PHP in update_orders.php: " . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close(); // Chiudi la connessione al database
    }
}

echo json_encode($response); // Restituisci la risposta JSON
?>