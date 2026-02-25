<?php
// update_category.php: Script PHP per aggiornare una categoria esistente
// Gestisce l'aggiornamento in base al tipo di categoria (main_category, sub_category, sub_sub_category)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php'; 
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'] ?? null;
$name = $input['nome'] ?? null;
$type = $input['type'] ?? null; // Tipo della categoria da aggiornare
$parentId = $input['parent_id'] ?? null; // Nuovo ID del genitore
$parentType = $input['parent_type'] ?? 'none'; // Nuovo tipo del genitore

if (empty($id) || empty($name) || empty($type)) {
    echo json_encode(['success' => false, 'message' => 'ID, nome e tipo della categoria sono obbligatori.']);
    exit();
}

try {
    $stmt = null;
    $message = "Categoria aggiornata con successo!";

    if ($type === 'main_category') {
        // Aggiorna una categoria principale
        // Una categoria principale non può avere un genitore, quindi ignora parentId/parentType se forniti
        $stmt = $conn->prepare("UPDATE categorie SET nome = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        $message = "Categoria principale aggiornata con successo!";
    } elseif ($type === 'sub_category') {
        // Aggiorna una sottocategoria
        // parent_category_id si riferisce all'ID della categoria principale
        $stmt = $conn->prepare("UPDATE sottocategorie SET nome = ?, parent_category_id = ? WHERE id = ?");
        $stmt->bind_param("sii", $name, $parentId, $id);
        $message = "Sottocategoria aggiornata con successo!";
    } elseif ($type === 'sub_sub_category') {
        // Aggiorna una sottosottocategoria
        // parent_subcategory_id si riferisce all'ID della sottocategoria
        $stmt = $conn->prepare("UPDATE sottosottocategorie SET nome = ?, parent_subcategory_id = ? WHERE id = ?");
        $stmt->bind_param("sii", $name, $parentId, $id);
        $message = "Sottosottocategoria aggiornata con successo!";
    } else {
        throw new Exception("Tipo di categoria non valido fornito: " . htmlspecialchars($type));
    }

    if ($stmt && $stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nessuna categoria trovata con l\'ID specificato o nessun cambiamento.']);
        }
    } else {
        throw new Exception("Errore nell'esecuzione della query: " . ($stmt ? $stmt->error : "Statement non preparato."));
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento della categoria: ' . $e->getMessage()]);
}
?>
