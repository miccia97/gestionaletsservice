<?php
// update_orders.php
// Aggiorna l'ordine di visualizzazione e la gerarchia delle categorie

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

$data = json_decode(file_get_contents('php://input'), true);
$items = $data['items'] ?? [];

if (empty($items) || !is_array($items)) {
    $response['message'] = 'Dati di riordinamento mancanti o non validi.';
    echo json_encode($response);
    exit();
}

try {
    $conn->begin_transaction();

    foreach ($items as $item) {
        $itemId   = intval($item['id'] ?? 0);
        $newOrder = intval($item['order'] ?? 0);
        $type     = $item['type'] ?? '';
        $parentId = isset($item['parent_id']) && $item['parent_id'] !== null ? intval($item['parent_id']) : null;

        if ($itemId === 0) {
            throw new Exception("ID mancante per un elemento.");
        }

        switch ($type) {
            case 'main_category':
                $stmt = $conn->prepare("UPDATE categorie SET display_order = ? WHERE id = ?");
                $stmt->bind_param("ii", $newOrder, $itemId);
                break;

            case 'sub_category':
                if ($parentId === null) {
                    throw new Exception("parent_id mancante per sottocategoria ID $itemId.");
                }
                $stmt = $conn->prepare("UPDATE sottocategorie SET display_order = ?, parent_category_id = ? WHERE id = ?");
                $stmt->bind_param("iii", $newOrder, $parentId, $itemId);
                break;

            case 'sub_sub_category':
                if ($parentId === null) {
                    throw new Exception("parent_id mancante per sotto-sottocategoria ID $itemId.");
                }
                $stmt = $conn->prepare("UPDATE sottosottocategorie SET display_order = ?, parent_subcategory_id = ? WHERE id = ?");
                $stmt->bind_param("iii", $newOrder, $parentId, $itemId);
                break;

            default:
                throw new Exception("Tipo di categoria non valido: " . $type);
        }

        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Ordini aggiornati con successo.';

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Errore durante l\'aggiornamento degli ordini: ' . $e->getMessage();
    error_log("Errore in update_orders.php: " . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close();
    }
}

echo json_encode($response);
?>