<?php
// add_category.php: Script PHP per aggiungere una nuova categoria
// Gestisce l'aggiunta in base al tipo di genitore (main_category, sub_category, none)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php'; 
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$name = $input['nome'] ?? null; // Usa 'nome' come inviato dal frontend
$parentId = $input['parent_id'] ?? null; // ID del genitore (può essere di main_category o sub_category)
$parentType = $input['parent_type'] ?? 'none'; // Tipo del genitore ('main_category', 'sub_category', 'none')
$order = $input['order'] ?? 0; // Ordine di visualizzazione

// Logga i dati in ingresso per il debug (visibile nei log degli errori del server PHP)
error_log("add_category.php - Dati ricevuti: " . print_r($input, true));

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Il nome della categoria non può essere vuoto.']);
    exit();
}

try {
    $stmt = null;
    $message = "Categoria aggiunta con successo!";
    $sql_query = ""; // Per memorizzare la query SQL per il debug

    if ($parentType === 'none') {
        // Aggiungi una categoria principale nella tabella 'categorie'
        $sql_query = "INSERT INTO categorie (nome, display_order) VALUES (?, ?)";
        $stmt = $conn->prepare($sql_query);
        if ($stmt) {
            $stmt->bind_param("si", $name, $order);
        }
        $message = "Categoria principale aggiunta con successo!";
    } elseif ($parentType === 'main_category') {
        // Aggiungi una sottocategoria nella tabella 'sottocategorie'
        // parent_category_id si riferisce all'ID della categoria principale
        $sql_query = "INSERT INTO sottocategorie (nome, parent_category_id, display_order) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql_query);
        if ($stmt) {
            $stmt->bind_param("sii", $name, $parentId, $order);
        }
        $message = "Sottocategoria aggiunta con successo!";
    } elseif ($parentType === 'sub_category') {
        // Aggiungi una sottosottocategoria nella tabella 'sottosottocategorie'
        // parent_subcategory_id si riferisce all'ID della sottocategoria
        $sql_query = "INSERT INTO sottosottocategorie (nome, parent_subcategory_id, display_order) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql_query);
        if ($stmt) {
            $stmt->bind_param("sii", $name, $parentId, $order);
        }
        $message = "Sottosottocategoria aggiunta con successo!";
    } else {
        throw new Exception("Tipo di genitore non valido fornito: " . htmlspecialchars($parentType));
    }

    if (!$stmt) {
        throw new Exception("Errore nella preparazione dello statement SQL: " . $conn->error . " Query: " . $sql_query);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $message, 'id' => $conn->insert_id]);
    } else {
        // Cattura l'errore specifico del database
        throw new Exception("Errore nell'esecuzione della query: " . $stmt->error . " Query: " . $sql_query . " Parametri: " . json_encode(['name' => $name, 'parentId' => $parentId, 'order' => $order, 'parentType' => $parentType]));
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    // Aggiunto il tipo di genitore e il messaggio completo dell'eccezione per un debug più facile
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiunta della categoria (tipo genitore: ' . htmlspecialchars($parentType) . '): ' . $e->getMessage()]);
}
?>
