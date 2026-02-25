<?php
// get_categories.php
// Recupera tutte le categorie e sottocategorie dal database

// Abilita la visualizzazione degli errori per il debug (RIMUOVI IN PRODUZIONE!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php'; // CORREZIONE QUI: Cambiato da 'db_config.php' a 'db.php'

header('Content-Type: application/json'); // Imposta l'header per la risposta JSON

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // Recupera le categorie principali
    // Assicurati che la colonna 'display_order' esista nella tabella 'categorie'
    $sql_categories = "SELECT id, nome AS name, NULL AS parentId, display_order AS 'order' FROM categorie ORDER BY display_order ASC";
    $result_categories = $conn->query($sql_categories);

    if (!$result_categories) {
        throw new Exception("Errore nella query delle categorie: " . $conn->error);
    }

    $categories = [];
    if ($result_categories->num_rows > 0) {
        while($row = $result_categories->fetch_assoc()) {
            $categories[] = $row;
        }
    }

    // Recupera le sottocategorie
    // Assicurati che le colonne 'parent_category_id' e 'display_order' esistano nella tabella 'sottocategorie'
    $sql_subcategories = "SELECT id, nome AS name, parent_category_id AS parentId, display_order AS 'order' FROM sottocategorie ORDER BY display_order ASC";
    $result_subcategories = $conn->query($sql_subcategories);

    if (!$result_subcategories) {
        throw new Exception("Errore nella query delle sottocategorie: " . $conn->error);
    }

    $subcategories = [];
    if ($result_subcategories->num_rows > 0) {
        while($row = $result_subcategories->fetch_assoc()) {
            $subcategories[] = $row;
        }
    }

    // Combina tutte le voci (categorie e sottocategorie)
    $all_items = array_merge($categories, $subcategories);

    // Ordina tutti gli elementi in memoria per l'ordine di visualizzazione
    // Questo è un fallback, l'ORDER BY nelle query SQL è già la fonte primaria.
    usort($all_items, function($a, $b) {
        return $a['order'] - $b['order'];
    });

    $response['success'] = true;
    $response['data'] = $all_items;
    $response['message'] = 'Categorie caricate con successo.';

} catch (Exception $e) {
    // Cattura qualsiasi eccezione e la include nella risposta JSON
    $response['message'] = 'Errore nel recupero delle categorie: ' . $e->getMessage();
    // Per debug, puoi anche stampare l'errore direttamente:
    // error_log("Errore PHP in get_categories.php: " . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close(); // Chiudi la connessione al database
    }
}

echo json_encode($response); // Restituisci la risposta JSON
?>