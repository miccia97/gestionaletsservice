<?php
// reorder_category.php
// Riordina le categorie (scambia l'ordine di visualizzazione di due categorie)

// Abilita la visualizzazione degli errori per il debug (RIMUOVI IN PRODUZIONE!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php'; // CORREZIONE QUI: Assicurati che il tuo file di configurazione del database si chiami 'db.php'

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

$data = json_decode(file_get_contents('php://input'), true);

$id1 = $data['id1'] ?? '';
$order1 = $data['order1'] ?? '';
$id2 = $data['id2'] ?? '';
$order2 = $data['order2'] ?? '';
$parentId = $data['parentId'] ?? null; // Per determinare quale tabella usare (null per categorie principali)

if (empty($id1) || !isset($order1) || empty($id2) || !isset($order2)) {
    $response['message'] = 'Dati di riordinamento mancanti.';
    echo json_encode($response);
    exit();
}

try {
    // Inizia una transazione per garantire l'integrità dei dati
    $conn->begin_transaction();

    if ($parentId === null) { // Categorie principali (tabella 'categorie')
        // Scambia l'ordine di visualizzazione nella tabella 'categorie'
        // Assicurati che la colonna 'display_order' esista nella tabella 'categorie'
        $stmt1 = $conn->prepare("UPDATE categorie SET display_order = ? WHERE id = ?");
        $stmt1->bind_param("ii", $order2, $id1);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("UPDATE categorie SET display_order = ? WHERE id = ?");
        $stmt2->bind_param("ii", $order1, $id2);
        $stmt2->execute();
        $stmt2->close();
    } else { // Sottocategorie (tabella 'sottocategorie')
        // Scambia l'ordine di visualizzazione nella tabella 'sottocategorie'
        // Assicurati che la colonna 'display_order' esista nella tabella 'sottocategorie'
        $stmt1 = $conn->prepare("UPDATE sottocategorie SET display_order = ? WHERE id = ?");
        $stmt1->bind_param("ii", $order2, $id1);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("UPDATE sottocategorie SET display_order = ? WHERE id = ?");
        $stmt2->bind_param("ii", $order1, $id2);
        $stmt2->execute();
        $stmt2->close();
    }

    $conn->commit(); // Conferma la transazione
    $response['success'] = true;
    $response['message'] = 'Categorie riordinate con successo.';

} catch (Exception $e) {
    $conn->rollback(); // Annulla la transazione in caso di errore
    $response['message'] = 'Errore durante il riordinamento: ' . $e->getMessage();
    // Per debug, puoi anche stampare l'errore direttamente nei log del server:
    // error_log("Errore PHP in reorder_category.php: " . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close(); // Chiudi la connessione al database
    }
}

echo json_encode($response); // Restituisci la risposta JSON
?>