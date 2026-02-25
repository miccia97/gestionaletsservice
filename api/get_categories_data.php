<?php
// api/get_categories_data.php: Backend PHP per recuperare i dati delle categorie in formato JSON

// Abilita la visualizzazione degli errori per il debug (RIMUOVERE IN PRODUZIONE!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Includi il file di connessione al database (assumi che db.php sia nella directory padre)
include '../db.php'; 

// Imposta l'intestazione per le risposte JSON
header('Content-Type: application/json');

// Gestisce la richiesta GET per recuperare tutte le categorie
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // 1. Recupera Categorie Principali (Livello 0) dalla tabella 'categorie'
        $stmt_l1 = $conn->prepare("SELECT id, nome, display_order FROM categorie ORDER BY display_order ASC");
        $stmt_l1->execute();
        $main_categories = $stmt_l1->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_l1->close();

        // 2. Recupera Sottocategorie (Livello 1) dalla tabella 'sottocategorie'
        $stmt_l2 = $conn->prepare("SELECT id, nome, parent_category_id, display_order FROM sottocategorie ORDER BY display_order ASC");
        $stmt_l2->execute();
        $sub_categories = $stmt_l2->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_l2->close();

        // 3. Recupera Sottosottocategorie (Livello 2) dalla tabella 'sottosottocategorie'
        $stmt_l3 = $conn->prepare("SELECT id, nome, parent_subcategory_id, display_order FROM sottosottocategorie ORDER BY display_order ASC");
        $stmt_l3->execute();
        $sub_sub_categories = $stmt_l3->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_l3->close();

        // Costruisci la gerarchia completa
        $hierarchical_data = [];

        // Mappa per lookup rapido di sottocategorie per parent_category_id
        $sub_categories_by_parent = [];
        foreach ($sub_categories as $sub_cat) {
            $sub_categories_by_parent[$sub_cat['parent_category_id']][] = $sub_cat;
        }

        // Mappa per lookup rapido di sottosottocategorie per parent_subcategory_id
        $sub_sub_categories_by_parent = [];
        foreach ($sub_sub_categories as $sub_sub_cat) {
            $sub_sub_categories_by_parent[$sub_sub_cat['parent_subcategory_id']][] = $sub_sub_cat;
        }

        foreach ($main_categories as $main_cat) {
            $main_cat['level'] = 0;
            $main_cat['type'] = 'main_category'; // Aggiungi tipo per JS
            $main_cat['children'] = [];

            // Aggiungi sottocategorie
            if (isset($sub_categories_by_parent[$main_cat['id']])) {
                foreach ($sub_categories_by_parent[$main_cat['id']] as $sub_cat) {
                    $sub_cat['level'] = 1;
                    $sub_cat['type'] = 'sub_category'; // Aggiungi tipo per JS
                    $sub_cat['children'] = [];

                    // Aggiungi sottosottocategorie
                    if (isset($sub_sub_categories_by_parent[$sub_cat['id']])) {
                        foreach ($sub_sub_categories_by_parent[$sub_cat['id']] as $sub_sub_cat) {
                            $sub_sub_cat['level'] = 2;
                            $sub_sub_cat['type'] = 'sub_sub_category'; // Aggiungi tipo per JS
                            $sub_cat['children'][] = $sub_sub_cat;
                        }
                    }
                    $main_cat['children'][] = $sub_cat;
                }
            }
            $hierarchical_data[] = $main_cat;
        }
        
        echo json_encode(['success' => true, 'data' => $hierarchical_data]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore nel recupero delle categorie: ' . $e->getMessage()]);
    }
    exit();
}

// Se la richiesta non è GET, restituisci un errore
http_response_code(405); // Metodo non consentito
echo json_encode(['success' => false, 'message' => 'Metodo HTTP non consentito.']);

?>
