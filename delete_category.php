<?php
// delete_category.php: Script PHP per eliminare una categoria
// Gestisce l'eliminazione in base al tipo di categoria (main_category, sub_category, sub_sub_category)
// Implementa l'eliminazione a cascata per i livelli inferiori

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php'; 
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'] ?? null;
$type = $input['type'] ?? null; // Tipo della categoria da eliminare

// Logga i dati in ingresso per il debug
error_log("delete_category.php - Dati ricevuti: ID=" . ($id ?? 'NULL') . ", Tipo=" . ($type ?? 'NULL'));

if (empty($id) || empty($type)) {
    echo json_encode(['success' => false, 'message' => 'ID e tipo della categoria sono obbligatori per l\'eliminazione.']);
    exit();
}

try {
    $conn->begin_transaction(); // Inizia una transazione per garantire l'integrità

    $stmt = null;
    $message = "Categoria eliminata con successo!";

    if ($type === 'main_category') {
        // Elimina sottosottocategorie collegate alle sottocategorie di questa categoria principale
        $stmt_del_sub_sub = $conn->prepare("DELETE FROM sottosottocategorie WHERE parent_subcategory_id IN (SELECT id FROM sottocategorie WHERE parent_category_id = ?)");
        if (!$stmt_del_sub_sub) {
            throw new Exception("Errore nella preparazione query DELETE sottosottocategorie (main_category): " . $conn->error);
        }
        $stmt_del_sub_sub->bind_param("i", $id);
        if (!$stmt_del_sub_sub->execute()) {
            throw new Exception("Errore nell'esecuzione DELETE sottosottocategorie (main_category): " . $stmt_del_sub_sub->error);
        }
        $stmt_del_sub_sub->close();
        error_log("Eliminate sottosottocategorie per main_category ID: " . $id);

        // Elimina sottocategorie collegate a questa categoria principale
        $stmt_del_sub = $conn->prepare("DELETE FROM sottocategorie WHERE parent_category_id = ?");
        if (!$stmt_del_sub) {
            throw new Exception("Errore nella preparazione query DELETE sottocategorie (main_category): " . $conn->error);
        }
        $stmt_del_sub->bind_param("i", $id);
        if (!$stmt_del_sub->execute()) {
            throw new Exception("Errore nell'esecuzione DELETE sottocategorie (main_category): " . $stmt_del_sub->error);
        }
        $stmt_del_sub->close();
        error_log("Eliminate sottocategorie per main_category ID: " . $id);

        // Infine, elimina la categoria principale
        $stmt = $conn->prepare("DELETE FROM categorie WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Errore nella preparazione query DELETE categorie: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        $message = "Categoria principale e tutte le sue dipendenze eliminate con successo!";

    } elseif ($type === 'sub_category') {
        // Elimina sottocategoria e tutte le sue sottosottocategorie
        // Elimina sottosottocategorie collegate a questa sottocategoria
        $stmt_del_sub_sub = $conn->prepare("DELETE FROM sottosottocategorie WHERE parent_subcategory_id = ?");
        if (!$stmt_del_sub_sub) {
            throw new Exception("Errore nella preparazione query DELETE sottosottocategorie (sub_category): " . $conn->error);
        }
        $stmt_del_sub_sub->bind_param("i", $id);
        if (!$stmt_del_sub_sub->execute()) {
            throw new Exception("Errore nell'esecuzione DELETE sottosottocategorie (sub_category): " . $stmt_del_sub_sub->error);
        }
        $stmt_del_sub_sub->close();
        error_log("Eliminate sottosottocategorie per sub_category ID: " . $id);

        // Infine, elimina la sottocategoria
        $stmt = $conn->prepare("DELETE FROM sottocategorie WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Errore nella preparazione query DELETE sottocategorie: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        $message = "Sottocategoria e le sue sottosottocategorie eliminate con successo!";

    } elseif ($type === 'sub_sub_category') {
        // Elimina solo la sottosottocategoria
        $stmt = $conn->prepare("DELETE FROM sottosottocategorie WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Errore nella preparazione query DELETE sottosottocategorie: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        $message = "Sottosottocategoria eliminata con successo!";

    } else {
        throw new Exception("Tipo di categoria non valido fornito per l'eliminazione: " . htmlspecialchars($type));
    }

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $conn->commit(); // Conferma la transazione
            error_log("Eliminazione riuscita per ID: " . $id . ", Tipo: " . $type);
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            $conn->rollback(); // Annulla la transazione
            error_log("Nessuna riga influenzata per ID: " . $id . ", Tipo: " . $type);
            echo json_encode(['success' => false, 'message' => 'Nessuna categoria trovata con l\'ID e il tipo specificati.']);
        }
    } else {
        $conn->rollback(); // Annulla la transazione
        throw new Exception("Errore nell'esecuzione della query di eliminazione: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    $conn->rollback(); // Annulla la transazione in caso di errore
    http_response_code(500);
    error_log("Errore durante l'eliminazione della categoria (ID: " . ($id ?? 'NULL') . ", Tipo: " . ($type ?? 'NULL') . "): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione della categoria: ' . $e->getMessage()]);
}
?>
