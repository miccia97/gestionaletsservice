<?php
session_start(); // Avvia la sessione PHP

// --- Controllo degli Accessi ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Abilita la visualizzazione degli errori per il debug (RIMUOVERE IN PRODUZIONE!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// NUOVA FUNZIONE PER LE ICONE DI CATEGORIA
function getCategoryIcon($categoryName) {
    $iconName = 'tag'; // Icona di default
    switch (strtolower($categoryName)) {
        case 'telefonia': $iconName = 'smartphone'; break;
        case 'informatica': $iconName = 'laptop'; break;
        case 'tablet': $iconName = 'tablet'; break; // Aggiunta nuova icona
        case 'accessori': $iconName = 'stylus'; break; // Icona modificata
        case 'riparazioni': $iconName = 'wrench'; break;
    }
    $icons = [
        'tag' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>',
        'smartphone' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>',
        'laptop' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 16V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9m16 0H4m16 0 1.28 2.55A1 1 0 0 1 20.7 20H3.3a1 1 0 0 1-.58-1.45L4 16"/></svg>',
        'tablet' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>',
        'stylus' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>',
        'wrench' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>'
    ];
    return $icons[$iconName] ?? $icons['tag'];
}

include 'db.php'; // Connessione al database MySQL

// --- Recupera Categorie Principali dal DB ---
$categorie_principali = [];
$cat_res = $conn->query("SELECT id, nome, display_order FROM categorie ORDER BY display_order ASC");
if ($cat_res) { while ($row = $cat_res->fetch_assoc()) { $categorie_principali[] = $row; } } 
else { error_log("Errore recupero categorie principali: " . $conn->error); }

// --- Mappa ID Categoria a Nome ---
$category_id_to_name_map = [];
foreach ($categorie_principali as $cat) { $category_id_to_name_map[$cat['id']] = strtolower($cat['nome']); }

// --- Recupera Sottocategorie dal DB ---
$sottocategorie_raw = [];
$sotto_res = $conn->query("SELECT id, nome, parent_category_id, display_order FROM sottocategorie ORDER BY display_order ASC");
if ($sotto_res) { while ($row = $sotto_res->fetch_assoc()) { $sottocategorie_raw[] = $row; } } 
else { error_log("Errore recupero sottocategorie: " . $conn->error); }

// --- Mappa ID Sottocategoria a Nome ---
$subcategory_id_to_name_map = [];
foreach ($sottocategorie_raw as $subcat) { $subcategory_id_to_name_map[$subcat['id']] = strtolower($subcat['nome']); }

// --- Recupera Sottosottocategorie dal DB ---
$sottosottocategorie_raw = [];
$sotto_sotto_res = $conn->query("SELECT id, nome, parent_subcategory_id, display_order FROM sottosottocategorie ORDER BY display_order ASC");
if ($sotto_sotto_res) { while ($row = $sotto_sotto_res->fetch_assoc()) { $sottosottocategorie_raw[] = $row; } } 
else { error_log("Errore recupero sottosottocategorie: " . $conn->error); }


// --- Ricostruisci la struttura completa per il frontend JavaScript (3 livelli) ---
$full_categories_js_for_frontend = [];
foreach ($categorie_principali as $cat) { $full_categories_js_for_frontend[strtolower($cat['nome'])] = []; }
foreach ($sottocategorie_raw as $subcat) {
    $parentId = $subcat['parent_category_id'];
    $parentName = $category_id_to_name_map[$parentId] ?? null;
    if ($parentName && isset($full_categories_js_for_frontend[$parentName])) {
        $full_categories_js_for_frontend[$parentName][strtolower($subcat['nome'])] = [];
    }
}
foreach ($sottosottocategorie_raw as $subsubcat) {
    $parentSubcategoryId = $subsubcat['parent_subcategory_id'];
    $parentSubcategoryName = $subcategory_id_to_name_map[$parentSubcategoryId] ?? null;
    if ($parentSubcategoryName) {
        $mainCategoryName = null;
        foreach ($sottocategorie_raw as $subcat_check) {
            if ($subcat_check['id'] == $parentSubcategoryId) {
                $mainCategoryName = $category_id_to_name_map[$subcat_check['parent_category_id']] ?? null;
                break;
            }
        }
        if ($mainCategoryName && isset($full_categories_js_for_frontend[strtolower($mainCategoryName)][strtolower($parentSubcategoryName)])) {
            $full_categories_js_for_frontend[strtolower($mainCategoryName)][strtolower($parentSubcategoryName)][] = $subsubcat['nome'];
        }
    }
}

// --- Prendi prodotti dal DB ---
$sql = "SELECT id, nome, barcode, imei, prezzo_vendita1, prezzo_vendita2, immagine, quantita, categoria, sottocategoria, sottosottocategoria FROM prodotti ORDER BY nome ASC";
$prodotti_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Homepage Gestionale</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root {
      --primary-color: #28a745;
      --primary-hover: #218838;
      --secondary-color: #6c757d;
      --success-color: #28a745;
      --danger-color: #dc3545;
      --danger-hover: #c82333;
      --light-bg: #f8f9fa;
      --white-bg: #ffffff;
      --text-dark: #212529;
      --text-light: #6c757d;
      --border-color: #dee2e6;
      --shadow-sm: 0 1px 2px rgba(0,0,0,0.04);
      --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
      --border-radius: 8px;
    }
    body { 
      margin: 0; 
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: var(--light-bg); 
      color: var(--text-dark);
      -webkit-font-smoothing: antialiased;
    }
    .main-content { display: flex; padding: 30px; gap: 30px; }
    .sidebar { display: flex; flex-direction: column; gap: 12px; width: 220px; flex-shrink: 0; }
    .reset-btn, .category-btn {
      display: flex; 
      align-items: center; 
      gap: 12px; 
      background: var(--white-bg); border: 1px solid var(--border-color); padding: 12px; font-size: 15px; cursor: pointer; border-radius: var(--border-radius);
      transition: all 0.2s ease;
      box-shadow: var(--shadow-sm);
      text-align: left;
      font-weight: 500;
      color: var(--text-dark);
    }
    .reset-btn svg, .category-btn svg {
        color: var(--text-light);
        transition: color 0.2s ease;
    }
    .reset-btn:hover, .category-btn:hover { 
      background-color: var(--primary-color); 
      color: var(--white-bg); 
      border-color: var(--primary-color);
      transform: translateY(-2px); 
      box-shadow: var(--shadow-md);
    }
    .reset-btn:hover svg, .category-btn:hover svg,
    .category-btn.active svg, .reset-btn.active svg {
        color: var(--white-bg);
    }
    .category-btn.active, .reset-btn.active {
       background-color: var(--primary-color); 
      color: var(--white-bg); 
      border-color: var(--primary-color);
    }
    
    .input-section { flex-grow: 1; }
    
    .search-panel {
      background: var(--white-bg);
      padding: 24px;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-md);
      margin-bottom: 30px;
    }
    .search-panel h3 { margin-top: 0; margin-bottom: 20px; font-size: 18px; }
    .input-row { display: flex; gap: 20px; align-items: flex-end; }
    .input-group { display: flex; flex-direction: column; flex: 1; position: relative; }
    .input-group label { margin-bottom: 8px; font-size: 14px; color: var(--text-light); font-weight: 500; }
    .input-icon {
        position: absolute;
        left: 12px;
        top: 41px;
        color: var(--text-light);
        pointer-events: none;
    }
    .input-group input { 
      padding: 10px 12px 10px 40px; 
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-sm);
      width: 100%;
      box-sizing: border-box;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
      font-size: 15px;
    }
    .input-group input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25);
    }
    .clear-filters-btn {
        background: none;
        border: 1px solid var(--border-color);
        color: var(--text-light);
        padding: 10px 15px;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s ease;
    }
    .clear-filters-btn:hover {
        background: var(--light-bg);
        border-color: var(--secondary-color);
        color: var(--text-dark);
    }

    /* Barre sottocategorie */
    .subcategory-container, .subsubcategory-container {
      margin: 20px 0; display: none; justify-content: flex-start; flex-wrap: wrap; gap: 10px; max-height: 0;
      overflow: hidden; transition: max-height 0.4s ease-out, opacity 0.4s ease; opacity: 0;
    }
    .subcategory-container.visible, .subsubcategory-container.visible { display: flex; max-height: 200px; opacity: 1; }
    .subcategory-container button, .subsubcategory-container button {
      background: #e9ecef; border: 1px solid transparent; padding: 8px 14px; border-radius: 20px; font-size: 13px; font-weight: 500;
      cursor: pointer; transition: all 0.2s ease;
    }
    .subcategory-container button:hover, .subsubcategory-container button:hover { background-color: var(--primary-color); color: white; transform: translateY(-1px); }
    .subcategory-container button[aria-pressed="true"] { background-color: var(--primary-color); color: white; box-shadow: var(--shadow-sm); }
    .subsubcategory-container button { background: #ced4da; }
    .subsubcategory-container button:hover { background-color: var(--secondary-color); }
    .subsubcategory-container button[aria-pressed="true"] { background-color: var(--secondary-color); color: white; box-shadow: var(--shadow-sm); }

    .content-with-cart { display: flex; gap: 30px; align-items: flex-start; }
    .product-grid { flex: 1; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 30px; position: relative; }
    .empty-grid-message { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: var(--text-light); font-size: 1.1em; display: none; }
    
    /* --- CARD PRODOTTO CON EFFETTO FLIP --- */
    .product-card {
      perspective: 1000px;
      background-color: transparent;
      border: none;
      box-shadow: none;
      min-height: 375px; /* Dimensione finale per renderla compatta */
      animation: fadeIn 0.4s ease-out;
    }
    .product-card:hover .card-front {
      transform: translateY(-5px);
      box-shadow: 0 8px 15px rgba(0,0,0,0.08);
    }
    .card-inner {
      position: relative;
      width: 100%;
      height: 100%;
      transition: transform 0.6s;
      transform-style: preserve-3d;
    }
    .product-card.is-flipped .card-inner {
      transform: rotateY(180deg);
    }
    .card-front, .card-back {
      position: absolute;
      width: 100%;
      height: 100%;
      -webkit-backface-visibility: hidden;
      backface-visibility: hidden;
      display: flex;
      flex-direction: column;
      border-radius: var(--border-radius);
      overflow: hidden;
      border: 1px solid var(--border-color);
      box-shadow: var(--shadow-md);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-front {
      background: var(--white-bg);
    }
    .card-back {
      background-color: var(--white-bg);
      color: var(--text-dark);
      transform: rotateY(180deg);
      padding: 15px;
      font-size: 13px;
      box-sizing: border-box;
      justify-content: center;
    }
    .card-back h5 {
      margin: 0 0 10px 0; font-size: 16px; text-align: center;
      border-bottom: 1px solid var(--border-color); padding-bottom: 10px;
    }
    .card-back-details p { margin: 0 0 8px 0; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--light-bg); padding-bottom: 8px; }
    .card-back-details p:last-child { border-bottom: none; }
    .card-back-details p strong { color: var(--text-light); margin-right: 10px; font-size: 12px; }
    .card-back-details p span { font-weight: 600; text-align: right; word-break: break-all; }
    .flip-back-btn {
      position: absolute; top: 8px; right: 8px; background: none; border: none; cursor: pointer; padding: 5px; line-height: 1; color: var(--text-light);
    }
    .flip-back-btn:hover { color: var(--text-dark); }
    .modal-loader { display: block; }
    .spinner { border: 4px solid rgba(0, 0, 0, 0.1); width: 36px; height: 36px; border-radius: 50%; border-left-color: var(--primary-color); animation: spin 1s ease infinite; margin: auto;}
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

    .product-image-container { width: 100%; height: 160px; display: flex; justify-content: center; align-items: center; padding: 10px; box-sizing: border-box; background-color: transparent; }
    .card-front img { max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.3s ease; }
    .product-card:hover .card-front img { transform: scale(1.05); }
    .product-info { padding: 12px; display: flex; flex-direction: column; flex-grow: 1; border-top: 1px solid var(--border-color); }
    .product-name { font-weight: 600; font-size: 15px; margin-bottom: 8px; color: var(--text-dark); line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    .price-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px; }
    .price-selector label { padding: 8px; border: 1px solid var(--border-color); border-radius: var(--border-radius); text-align: center; cursor: pointer; transition: all 0.2s ease; font-size: 14px; font-weight: 600; color: var(--text-dark); }
    .price-selector input[type="radio"] { display: none; }
    .price-selector label:hover { border-color: var(--secondary-color); background-color: var(--light-bg); }
    .price-selector label:has(input:checked) { background-color: var(--primary-color); color: var(--white-bg); border-color: var(--primary-color); box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2); }

    .stock { font-size: 12px; font-weight: 600; padding: 3px 8px; border-radius: 20px; display: inline-block; margin-bottom: 12px; color: var(--text-dark); background-color: var(--light-bg); border: 1px solid var(--border-color); }
    .stock.low-stock { color: #856404; background-color: #fff3cd; border-color: #ffeeba; }
    .stock.out-of-stock { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
    .controls-container { display: flex; align-items: center; gap: 8px; margin-top: auto; }
    .qty-btn { width: 36px; height: 36px; border: 1px solid var(--border-color); background: var(--white-bg); color: var(--text-light); border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: all 0.2s ease; }
    .qty-btn:hover { background-color: var(--light-bg); color: var(--text-dark); }
    .qty-input { width: 40px; text-align: center; border: 1px solid var(--border-color); border-radius: var(--border-radius); font-size: 16px; padding: 4px; font-weight: 600; color: var(--text-dark); background-color: var(--white-bg); height: 36px; box-sizing: border-box; }
    .qty-input:focus { outline: none; border-color: var(--primary-color); }
    .qty-input::-webkit-inner-spin-button, .qty-input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .add-to-cart-btn { background: var(--success-color); border: none; padding: 0; cursor: pointer; margin-left: auto; width: 44px; height: 44px; border-radius: 50%; display: flex; justify-content: center; align-items: center; transition: all 0.2s ease; }
    .add-to-cart-btn:hover { background: var(--primary-hover); transform: scale(1.1); }
    .add-to-cart-btn.success { background-color: #10B981; transform: scale(1.1); }
    .add-to-cart-btn svg { stroke: var(--white-bg); transition: opacity 0.2s ease; }
    .info-btn { position: absolute; top: 10px; left: 10px; width: 30px; height: 30px; border-radius: 50%; border: none; background-color: rgba(255, 255, 255, 0.8); backdrop-filter: blur(2px); cursor: pointer; color: var(--text-dark); display: flex; justify-content: center; align-items: center; z-index: 10; transition: all 0.2s ease; box-shadow: var(--shadow-sm); }
    .info-btn:hover { background-color: var(--white-bg); transform: scale(1.1); }

    /* --- ANTEPRIMA CARRELLO RIVISTA --- */
    .cart-preview { display: none; width: 340px; border: 1px solid var(--border-color); padding: 0; border-radius: var(--border-radius); background: var(--white-bg); box-shadow: var(--shadow-md); flex-direction: column; position: sticky; top: 24px; overflow: hidden; }
    .cart-preview.visible { display: flex; }
    .cart-preview h3 { margin: 0; padding: 16px; font-size: 18px; color: var(--text-dark); background-color: var(--light-bg); border-bottom: 1px solid var(--border-color); text-align: center; }
    .cart-preview ul { flex: 1; list-style: none; padding: 20px; margin: 0; max-height: 450px; overflow-y: auto; position: relative; min-height: 150px;}
    .cart-preview li { display: flex; align-items: center; gap: 16px; padding-bottom: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); }
    .cart-preview li:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .empty-cart-message { display: none; text-align: center; color: var(--text-light); position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 100%; }
    .empty-cart-message.visible { display: block; }
    .empty-cart-message svg { width: 48px; height: 48px; margin-bottom: 12px; stroke-width: 1.5; color: var(--border-color); }
    .item-img { width: 60px; height: 60px; border-radius: var(--border-radius); object-fit: cover; border: 1px solid var(--border-color); flex-shrink: 0; }
    .item-info { flex: 1; font-size: 15px; }
    .item-info .name { font-weight: 600; margin-bottom: 6px; display: block; color: var(--text-dark); }
    .item-info .details { font-size: 13px; color: var(--text-light); }
    .cart-item-qty-controls { display: flex; align-items: center; gap: 6px; }
    .cart-qty-btn { width: 28px; height: 28px; border: 1px solid var(--border-color); background: var(--white-bg); color: var(--text-light); border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: all 0.2s ease; }
    .cart-qty-btn:hover { background-color: var(--light-bg); color: var(--text-dark); }
    .cart-qty-input { width: 32px; text-align: center; border: 1px solid var(--border-color); border-radius: 5px; padding: 4px 0; font-size: 14px; color: var(--text-dark); }
    .remove-btn { background: none; border: none; color: var(--text-light); cursor: pointer; transition: all 0.2s ease; padding: 0 5px; display: flex; align-items: center; }
    .remove-btn:hover { color: var(--danger-color); }
    .cart-summary { padding: 20px; border-top: 1px solid var(--border-color); background: var(--light-bg); }
    .cart-total { font-weight: 700; text-align: right; font-size: 20px; color: var(--text-dark); }
    .cart-actions { display: flex; justify-content: space-between; margin-top: 16px; gap: 10px; }
    .cart-actions button { padding: 10px 15px; border: 1px solid transparent; border-radius: var(--border-radius); cursor: pointer; font-size: 15px; font-weight: 600; transition: all 0.2s ease; box-shadow: var(--shadow-sm); flex: 1; }
    .go-cart-btn { background: var(--primary-color); color: var(--white-bg); }
    .go-cart-btn:hover { background-color: var(--primary-hover); }
    .empty-cart-btn { background: var(--white-bg); color: var(--danger-color); border-color: var(--danger-color); }
    .empty-cart-btn:hover { background-color: var(--danger-color); color: var(--white-bg); }

    /* Toast Notifications */
    #toast-container { position: fixed; bottom: 20px; right: 20px; z-index: 9999; }
    .toast { padding: 12px 20px; border-radius: var(--border-radius); color: #fff; background: var(--success-color); box-shadow: 0 5px 15px rgba(0,0,0,0.2); opacity: 0; transform: translateY(20px); animation: slideUp 0.4s forwards, fadeOutToast 0.4s forwards 3s; font-size: 15px; font-weight: 500; }
    .toast.error { background: var(--danger-color); }
    .toast.warning { background: #ffc107; color: #333; }
    @keyframes slideUp { to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeOutToast { to { opacity: 0; transform: translateY(20px); } }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }

    /* Modals */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); display: flex; justify-content: center; align-items: center; z-index: 10000; opacity: 0; visibility: hidden; transition: opacity 0.3s ease, visibility 0.3s ease; }
    .modal-overlay.active { opacity: 1; visibility: visible; }
    .modal-content { background-color: #fff; border-radius: var(--border-radius); box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); width: 90%; transform: scale(0.95); transition: transform 0.3s ease; padding: 30px; text-align: center; max-width: 400px; }
    .modal-overlay.active .modal-content { transform: scale(1); }
    .modal-content h4 { margin-top: 0; font-size: 20px; color: var(--text-dark); margin-bottom: 20px; }
    .modal-buttons { display: flex; justify-content: center; gap: 15px; margin-top: 20px; }
    .modal-buttons button { padding: 10px 20px; border: none; border-radius: var(--border-radius); font-size: 16px; cursor: pointer; transition: all 0.2s ease; }
    .modal-buttons .confirm-btn { background-color: var(--danger-color); color: #fff; }
    .modal-buttons .confirm-btn:hover { background-color: var(--danger-hover); }
    .modal-buttons .cancel-btn { background-color: var(--secondary-color); color: #fff; }
    .modal-buttons .cancel-btn:hover { background-color: #5a6268; }

    @media (max-width: 1200px) { .main-content { flex-direction: column; } .sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; margin-top: 0;} .content-with-cart { flex-direction: column-reverse; } .cart-preview { width: 100%; position: static; } }
    @media (max-width: 768px) { .input-row { flex-direction: column; align-items: stretch; } }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <div id="toast-container" aria-live="polite" aria-atomic="true"></div>

  <div class="modal-overlay" id="confirmation-modal">
    <div class="modal-content">
      <h4 id="modal-message">Sei sicuro?</h4>
      <div class="modal-buttons">
        <button class="confirm-btn" id="modal-confirm-btn">Sì</button>
        <button class="cancel-btn" onclick="annullaAzione()">Annulla</button>
      </div>
    </div>
  </div>

  <div class="main-content">
    <aside class="sidebar" role="navigation" aria-label="Categorie prodotti">
      <button class="reset-btn" onclick="filtraCategoria(this, null)" aria-label="Mostra tutte le categorie">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
        Tutte
      </button>
      <?php foreach($categorie_principali as $cat): ?>
        <button class="category-btn" onclick="filtraCategoria(this, '<?php echo addslashes(strtolower($cat['nome'])); ?>')">
          <?php echo getCategoryIcon($cat['nome']); ?>
          <?php echo htmlspecialchars($cat['nome']); ?>
        </button>
      <?php endforeach; ?>
    </aside>

    <section class="input-section">
      <div class="search-panel">
        <h3>Filtra Prodotti</h3>
        <div class="input-row" role="search">
          <div class="input-group">
            <label for="input-nome">Nome Prodotto</label>
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="input-nome" placeholder="Cerca per nome..." oninput="debounce(filtraProdotti, 300)" />
          </div>
          <div class="input-group">
            <label for="input-barcode">Codice a Barre</label>
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5v14"/><path d="M8 5v14"/><path d="M12 5v14"/><path d="M17 5v14"/><path d="M21 5v14"/></svg>
            <input type="text" id="input-barcode" placeholder="Cerca per barcode..." oninput="debounce(filtraProdotti, 300)" />
          </div>
          <div class="input-group">
            <label for="input-imei">IMEI</label>
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9h16"/><path d="M4 15h16"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>
            <input type="text" id="input-imei" placeholder="Cerca per IMEI..." oninput="debounce(filtraProdotti, 300)" />
          </div>
          <button class="clear-filters-btn" onclick="document.querySelector('.reset-btn').click()">Pulisci</button>
        </div>
      </div>

      <div class="subcategory-container" id="subcategory-container" role="region" aria-label="Sottocategorie prodotti"></div>
      <div class="subsubcategory-container" id="subsubcategory-container" role="region" aria-label="Sotto-sottocategorie prodotti"></div>

      <div class="content-with-cart">
        <div class="product-grid" id="product-grid">
          <?php while($prod = $prodotti_result->fetch_assoc()): ?>
            <?php 
              $nome_low = strtolower($prod['nome']); $barcode_low = strtolower($prod['barcode']); $imei_low = strtolower($prod['imei']); 
              $cat_low = strtolower($prod['categoria']); $subcat_low = strtolower($prod['sottocategoria'] ?? ''); $subsubcat_low = strtolower($prod['sottosottocategoria'] ?? '');
              $stock_class = '';
              $stock_text = 'Disponibile: ' . intval($prod['quantita']);
              if ($prod['quantita'] <= 0) { $stock_class = 'out-of-stock'; $stock_text = 'Esaurito'; } 
              elseif ($prod['quantita'] <= 10) { $stock_class = 'low-stock'; $stock_text = 'Pochi pezzi: ' . intval($prod['quantita']); }
            ?>
            <div class="product-card" data-id="<?php echo $prod['id']; ?>" data-stock="<?php echo intval($prod['quantita']); ?>" data-nome="<?php echo htmlspecialchars($nome_low); ?>" data-barcode="<?php echo htmlspecialchars($barcode_low); ?>" data-imei="<?php echo htmlspecialchars($imei_low); ?>" data-categoria="<?php echo htmlspecialchars($cat_low); ?>" data-sottocategoria="<?php echo htmlspecialchars($subcat_low); ?>" data-sottosottocategoria="<?php echo htmlspecialchars($subsubcat_low); ?>">
                <div class="card-inner">
                    <div class="card-front">
                        <button class="info-btn" title="Vedi dettagli" onclick="toggleCardDetails('<?php echo $prod['id']; ?>', this, event)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        </button>
                        <div class="product-image-container">
                            <img src="uploads/<?php echo htmlspecialchars($prod['immagine']); ?>" alt="<?php echo htmlspecialchars($prod['nome']); ?>" onerror="this.src='https://placehold.co/150x150/e0e0e0/555555?text=No%20Img';" />
                        </div>
                        <div class="product-info">
                            <div class="product-name" title="<?php echo htmlspecialchars($prod['nome']); ?>"><?php echo htmlspecialchars($prod['nome']); ?></div>
                            <div class="price-selector">
                                <label>
                                    <input type="radio" name="price-<?php echo $prod['id']; ?>" value="<?php echo $prod['prezzo_vendita1']; ?>" checked>
                                    <span>€<?php echo number_format($prod['prezzo_vendita1'], 2, ',', '.'); ?></span>
                                </label>
                                <label>
                                    <input type="radio" name="price-<?php echo $prod['id']; ?>" value="<?php echo $prod['prezzo_vendita2']; ?>">
                                    <span>€<?php echo number_format($prod['prezzo_vendita2'], 2, ',', '.'); ?></span>
                                </label>
                            </div>
                            <div class="stock <?php echo $stock_class; ?>"><?php echo $stock_text; ?></div>
                            <div class="controls-container">
                                <button class="qty-btn" onclick="modificaQuantita(this,-1)" aria-label="Diminuisci">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                </button>
                                <input type="number" class="qty-input" value="1" min="1" max="<?php echo intval($prod['quantita']); ?>" aria-label="Quantità" />
                                <button class="qty-btn" onclick="modificaQuantita(this,1)" aria-label="Aumenta">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                </button>
                                <button class="add-to-cart-btn" title="Aggiungi al carrello" onclick="aggiungiAlCarrello(event, this)" aria-label="Aggiungi al carrello">
                                    <svg class="cart-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.53h9.72a2 2 0 0 0 2-1.53l1.66-7.43H5.12"></path></svg>
                                    <svg class="check-icon" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-back">
                        <div class="modal-loader"><div class="spinner"></div></div>
                    </div>
                </div>
            </div>
          <?php endwhile; ?>
          <div class="empty-grid-message" id="empty-grid-message"><p>Nessun prodotto trovato.<br>Prova a modificare i filtri di ricerca.</p></div>
        </div>

        <aside class="cart-preview" id="cart-preview" role="region" aria-label="Anteprima carrello">
          <h3>Carrello</h3>
          <ul id="cart-items">
            <div class="empty-cart-message" id="empty-cart-message">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <p>Il tuo carrello è vuoto.</p>
            </div>
          </ul>
          <div class="cart-summary">
              <div class="cart-total" id="cart-total">Totale: €0,00</div>
              <div class="cart-actions">
                <button class="go-cart-btn" onclick="vaiAlCarrello()">Vai al carrello</button>
                <button class="empty-cart-btn" onclick="mostraConfermaSvuotaCarrello()">Svuota</button>
              </div>
          </div>
        </aside>
      </div>
    </section>
  </div>

  <script>
    // Variabili Globali
    let filtroCategoria = null, filtroSottocategoria = null, filtroSottoSottocategoria = null, itemToRemoveKey = null;
    let cart = JSON.parse(localStorage.getItem('cart')) || {};
    let debounceTimer;
    const full_categories_data = <?php echo json_encode($full_categories_js_for_frontend); ?>;
    
    // Riferimenti DOM
    const cartPreview = document.getElementById('cart-preview');
    const cartItemsList = document.getElementById('cart-items');
    const cartTotalElem = document.getElementById('cart-total');
    const emptyCartMessage = document.getElementById('empty-cart-message');
    const confirmationModal = document.getElementById('confirmation-modal');
    const modalMessage = document.getElementById('modal-message');
    const modalConfirmBtn = document.getElementById('modal-confirm-btn');

    function debounce(func, delay) { clearTimeout(debounceTimer); debounceTimer = setTimeout(func, delay); }

    // --- NUOVA FUNZIONE FILTRO UNIFICATA ---
    function filtraCategoria(btn, catName) { 
      // Aggiornamento UI
      document.querySelectorAll('.sidebar button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      
      // Logica Reset
      if (catName === null) {
          filtroCategoria = null;
          document.getElementById('input-nome').value = '';
          document.getElementById('input-barcode').value = '';
          document.getElementById('input-imei').value = '';
      } else {
          filtroCategoria = catName;
      }
      
      // Logica comune
      filtroSottocategoria = null; 
      filtroSottoSottocategoria = null;
      aggiornaSottocategorie(); 
      aggiornaSottoSottocategorie(); 
      filtraProdotti(); 
      salvaFiltri();
    }
    
    function aggiornaSottocategorie() {
      const container = document.getElementById('subcategory-container');
      const subcategories = filtroCategoria ? Object.keys(full_categories_data[filtroCategoria] || {}) : [];
      if (!filtroCategoria || subcategories.length === 0) { container.classList.remove('visible'); filtroSottocategoria = null; } 
      else {
        container.classList.add('visible');
        let html = `<button onclick="filtraSottocategoria('')" aria-pressed="true">Tutte</button>`;
        subcategories.forEach(sub => html += `<button onclick="filtraSottocategoria('${sub}')">${sub.charAt(0).toUpperCase() + sub.slice(1)}</button>`);
        container.innerHTML = html;
        aggiornaStatoBottoni('#subcategory-container', filtroSottocategoria);
      }
    }

    function filtraSottocategoria(subcatName) {
      filtroSottocategoria = subcatName || null;
      filtroSottoSottocategoria = null;
      aggiornaSottoSottocategorie(); 
      aggiornaStatoBottoni('#subcategory-container', filtroSottocategoria); 
      filtraProdotti(); 
      salvaFiltri();
    }

    function aggiornaSottoSottocategorie() {
        const container = document.getElementById('subsubcategory-container');
        let subsubcategories = [];
        if (filtroCategoria && filtroSottocategoria && full_categories_data[filtroCategoria]?.[filtroSottocategoria]) {
            subsubcategories = full_categories_data[filtroCategoria][filtroSottocategoria];
        }
        if (!filtroSottocategoria || subsubcategories.length === 0) { container.classList.remove('visible'); filtroSottoSottocategoria = null; } 
        else {
            container.classList.add('visible');
            let html = `<button onclick="filtraSottoSottocategoria('')" aria-pressed="true">Tutte</button>`;
            subsubcategories.forEach(sub => html += `<button onclick="filtraSottoSottocategoria('${sub.toLowerCase()}')">${sub}</button>`);
            container.innerHTML = html;
            aggiornaStatoBottoni('#subsubcategory-container', filtroSottoSottocategoria);
        }
    }

    function filtraSottoSottocategoria(subsubcatName) {
        filtroSottoSottocategoria = subsubcatName || null;
        aggiornaStatoBottoni('#subsubcategory-container', filtroSottoSottocategoria); 
        filtraProdotti(); 
        salvaFiltri();
    }
    
    function aggiornaStatoBottoni(container, filter) {
      document.querySelectorAll(`${container} button`).forEach(btn => {
        const isAllBtn = btn.textContent.toLowerCase() === 'tutte';
        const filterVal = (filter || '').toLowerCase();
        btn.setAttribute('aria-pressed', (filterVal === '' && isAllBtn) || (btn.textContent.toLowerCase() === filterVal) ? 'true' : 'false');
      });
    }

    function filtraProdotti() {
        salvaFiltri();
        const nome = document.getElementById('input-nome').value.toLowerCase();
        const barcode = document.getElementById('input-barcode').value.toLowerCase();
        const imei = document.getElementById('input-imei').value.toLowerCase();
        let count = 0;
        document.querySelectorAll('.product-card').forEach(card => {
            const matchText = card.dataset.nome.includes(nome) && card.dataset.barcode.includes(barcode) && card.dataset.imei.includes(imei);
            const matchCat = !filtroCategoria || card.dataset.categoria === filtroCategoria;
            const matchSub = !filtroSottocategoria || card.dataset.sottocategoria === filtroSottocategoria;
            const matchSubSub = !filtroSottoSottocategoria || card.dataset.sottosottocategoria === filtroSottoSottocategoria;
            if (matchText && matchCat && matchSub && matchSubSub) { card.style.display = 'block'; count++; } 
            else { card.style.display = 'none'; }
        });
        document.getElementById('empty-grid-message').style.display = count === 0 ? 'block' : 'none';
    }

    function showToast(msg, type = 'success') {
      const cont = document.getElementById('toast-container'), toast = document.createElement('div');
      toast.className = `toast ${type}`; toast.textContent = msg; cont.appendChild(toast);
      toast.addEventListener('animationend', e => { if (e.animationName === 'fadeOutToast') toast.remove(); });
    }

    function salvaCarrello() { localStorage.setItem('cart', JSON.stringify(cart)); }
    function modificaQuantita(btn, d) { const i = btn.parentElement.querySelector('.qty-input'); i.value = Math.min(Math.max(parseInt(i.value) + d, 1), parseInt(i.max)); }
    function formatPrice(p) { return '€' + parseFloat(p).toFixed(2).replace('.',','); }

    function modificaQuantitaCart(key, delta) {
        if (!cart[key]) return;
        const item = cart[key];
        let newQty = item.qty + delta;
        if (newQty < 1) { newQty = 1; }
        if (newQty > item.giacenza) { newQty = item.giacenza; showToast(`Quantità massima per "${item.name}"`, "warning"); }
        item.qty = newQty;
        aggiornaAnteprima();
    }

    function aggiornaAnteprima() {
        cartItemsList.innerHTML = '';
        let tot = 0;
        const hasItems = Object.keys(cart).length > 0;
        cartPreview.classList.toggle('visible', hasItems);
        emptyCartMessage.classList.toggle('visible', !hasItems);
        document.querySelector('.cart-summary').style.display = hasItems ? 'block' : 'none';
        if (hasItems) {
            Object.entries(cart).forEach(([key, it]) => {
                tot += it.qty * it.price;
                const li = document.createElement('li');
                li.innerHTML = `
                  <img class="item-img" src="${it.img}" alt="${it.name}" />
                  <div class="item-info"><span class="name">${it.name}</span><span class="details">${it.qty} &times; ${formatPrice(it.price)}</span></div>
                  <div class="cart-item-qty-controls">
                    <button class="cart-qty-btn" onclick="modificaQuantitaCart('${key}', -1)"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
                    <input type="number" class="cart-qty-input" value="${it.qty}" min="1" max="${it.giacenza}" onchange="modificaQuantitaCart('${key}', parseInt(this.value) - ${it.qty})" />
                    <button class="cart-qty-btn" onclick="modificaQuantitaCart('${key}', 1)"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
                  </div>
                  <button class="remove-btn" onclick="mostraConfermaRimozione('${key}', '${it.name}')"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg></button>`;
                cartItemsList.appendChild(li);
            });
        } else { cartItemsList.appendChild(emptyCartMessage); }
      cartTotalElem.textContent = 'Totale: '+formatPrice(tot);
      salvaCarrello();
    }

    function mostraConfermaRimozione(key, name) {
      itemToRemoveKey = key;
      modalMessage.textContent = `Sei sicuro di voler eliminare "${name}" dal carrello?`;
      modalConfirmBtn.textContent = 'Sì, elimina';
      modalConfirmBtn.onclick = confermaRimozione;
      confirmationModal.classList.add('active');
    }

    function confermaRimozione() {
      if (itemToRemoveKey) { delete cart[itemToRemoveKey]; aggiornaAnteprima(); showToast("Prodotto rimosso.", "success"); }
      annullaAzione();
    }
    
    function mostraConfermaSvuotaCarrello() {
        if (!Object.keys(cart).length) { showToast("Il carrello è già vuoto.", "warning"); return; }
        modalMessage.textContent = `Sei sicuro di voler svuotare l'intero carrello?`;
        modalConfirmBtn.textContent = 'Sì, svuota';
        modalConfirmBtn.onclick = confermaSvuotaCarrello;
        confirmationModal.classList.add('active');
    }

    function confermaSvuotaCarrello() { svuotaCarrello(); annullaAzione(); }
    function annullaAzione() { itemToRemoveKey = null; confirmationModal.classList.remove('active'); }

    function toggleCardDetails(id, triggerElement, e) {
        if (e) e.stopPropagation();
        const card = triggerElement.closest('.product-card');
        const cardBack = card.querySelector('.card-back');

        const isFlipped = card.classList.contains('is-flipped');

        if (!isFlipped && !card.dataset.detailsLoaded) {
            // Fetch details only when flipping to the back for the first time
            fetch(`api.php?id=${id}`)
                .then(res => res.ok ? res.json() : Promise.reject(res.status))
                .then(p => {
                    card.dataset.detailsLoaded = 'true';
                    cardBack.innerHTML = `
                        <button class="flip-back-btn" title="Chiudi" onclick="toggleCardDetails(null, this, event)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                        <h5>Dettagli</h5>
                        <div class="card-back-details">
                            <p><strong>Nome:</strong> <span>${p.name || 'N/D'}</span></p>
                            <p><strong>Barcode:</strong> <span>${p.barcode || 'N/D'}</span></p>
                            <p><strong>IMEI:</strong> <span>${p.imei || 'N/D'}</span></p>
                            <p><strong>Acquisto:</strong> <span>${formatPrice(p.prezzo_acquisto || 0)}</span></p>
                            <p><strong>Categoria:</strong> <span>${p.categoria || 'N/D'}</span></p>
                            <p><strong>Sottocat.:</strong> <span>${p.sottocategoria || 'N/D'}</span></p>
                        </div>
                    `;
                })
                .catch(err => {
                    cardBack.innerHTML = `<p style="color:var(--danger-color);text-align:center;">Errore.</p>
                    <button class="flip-back-btn" title="Chiudi" onclick="toggleCardDetails(null, this, event)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>`;
                    showToast('Errore nel caricare i dettagli.', 'error');
                });
        }

        card.classList.toggle('is-flipped');
    }

    function aggiungiAlCarrello(e, btn) {
        e.stopPropagation();
        const card = btn.closest('.product-card'), id = card.dataset.id, name = card.querySelector('.product-name').textContent, img = card.querySelector('img').src,
              price = parseFloat(card.querySelector('input[type=radio]:checked').value), qty = parseInt(card.querySelector('.qty-input').value),
              stock = parseInt(card.dataset.stock), key = `${id}_${price.toFixed(2)}`;
        
        let totalInCart = Object.values(cart).filter(i => i.id === id).reduce((sum, i) => sum + i.qty, 0);
        if (totalInCart - (cart[key]?.qty || 0) + qty > stock) { showToast(`Giacenza non sufficiente (${stock} pz)!`, "error"); return; }
        
        btn.classList.add('success');
        btn.querySelector('.cart-icon').style.display = 'none'; btn.querySelector('.check-icon').style.display = 'inline-block';
        setTimeout(() => { btn.classList.remove('success'); btn.querySelector('.cart-icon').style.display = 'inline-block'; btn.querySelector('.check-icon').style.display = 'none'; }, 1500);

        if (cart[key]) { cart[key].qty += qty; showToast(`Quantità di "${name}" aggiornata.`, "success"); } 
        else { cart[key] = { id, name, price, qty, img, giacenza: stock }; showToast(`"${name}" aggiunto al carrello.`, "success"); }
        aggiornaAnteprima();
    }
    
    function svuotaCarrello() { cart = {}; aggiornaAnteprima(); showToast("Carrello svuotato.", "warning"); }
    
    function vaiAlCarrello() {
      if (!Object.keys(cart).length) { showToast("Il carrello è vuoto!", "warning"); return; } 
      const form = document.createElement('form'); form.method = 'POST'; form.action = 'carrello.php';
      const input = document.createElement('input'); input.type = 'hidden'; input.name = 'carrello_json'; input.value = JSON.stringify(Object.values(cart));
      form.appendChild(input); document.body.appendChild(form); form.submit();
    }

    function salvaFiltri() {
        sessionStorage.setItem('filtriProdotti', JSON.stringify({
            categoria: filtroCategoria, sottocategoria: filtroSottocategoria, sottosottocategoria: filtroSottoSottocategoria,
            nome: document.getElementById('input-nome').value, barcode: document.getElementById('input-barcode').value, imei: document.getElementById('input-imei').value
        }));
    }

    function caricaFiltri() {
        const f = JSON.parse(sessionStorage.getItem('filtriProdotti'));
        if (f) {
            document.getElementById('input-nome').value = f.nome || '';
            document.getElementById('input-barcode').value = f.barcode || '';
            document.getElementById('input-imei').value = f.imei || '';

            let btnToClick = document.querySelector('.reset-btn');
            if (f.categoria) {
                const targetBtn = Array.from(document.querySelectorAll('.category-btn')).find(b => b.textContent.trim().toLowerCase() === f.categoria);
                if (targetBtn) btnToClick = targetBtn;
            }
            btnToClick.click();
            
            if (f.sottocategoria) filtraSottocategoria(f.sottocategoria);
            if (f.sottosottocategoria) filtraSottoSottocategoria(f.sottosottocategoria);

        } else {
            document.querySelector('.reset-btn').click();
        }
    }

    window.addEventListener('DOMContentLoaded', () => {
      if (new URLSearchParams(window.location.search).get('venditaSuccesso') === 'true') {
          localStorage.removeItem('cart'); sessionStorage.removeItem('filtriProdotti'); cart = {};
          history.replaceState({}, document.title, window.location.pathname);
      }
      caricaFiltri(); 
      aggiornaAnteprima();
    });
  </script>
</body>
</html>


