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
  $normalized = trim(strtolower($categoryName));
  $normalized = str_replace('&', 'e', $normalized);
  $normalized = str_replace(['.', ',', '"', "'", '!', '?', '(', ')', '[', ']', '/', '\\'], '', $normalized);
  $normalized = preg_replace('/\s+/', ' ', $normalized);
  switch ($normalized) {
    case 'telefonia':
    case 'smartphone':
      $iconName = 'smartphone';
      break;
    case 'sim':
      $iconName = 'sim';
      break;
    case 'accessori telefonia':
    case 'accessori':
      $iconName = 'headphones';
      break;
    case 'gadget pers':
    case 'gadget personalizzati':
      $iconName = 'gift';
      break;
    case 'informatica':
      $iconName = 'monitor';
      break;
    case 'tablet':
    case 'tablet e accessori':
    case 'tablet accessori':
      $iconName = 'tablet';
      break;
    case 'usato':
      $iconName = 'recycle';
      break;
    case 'funko pop':
      $iconName = 'funko';
      break;
    case 'pezzi assistenza':
      $iconName = 'tools';
      break;
    case 'smartwatch':
      $iconName = 'watch';
      break;
    case 'consolle e giochi':
    case 'console e giochi':
      $iconName = 'gamepad';
      break;
    case 'action figure':
      $iconName = 'robot';
      break;
    case 'computer':
      $iconName = 'laptop';
      break;
    case 'manga':
      $iconName = 'book';
      break;
    case 'carte collezionabili':
    case 'carte':
      $iconName = 'cards';
      break;
    case 'assistenza':
    case 'riparazioni':
      $iconName = 'wrench';
      break;
    case 'tv e correlati':
    case 'tv':
    case 'televisori':
      $iconName = 'tv';
      break;
    case 'audio':
      $iconName = 'speaker';
      break;
  }
  $icons = [
    'tag' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>',
    'smartphone' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>',
    'laptop' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 16V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9m16 0H4m16 0 1.28 2.55A1 1 0 0 1 20.7 20H3.3a1 1 0 0 1-.58-1.45L4 16"/></svg>',
    'tablet' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>',
    'wrench' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
    'sim' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 5L14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7l-2-2z"/><path d="M8 12h8"/><path d="M8 16h8"/><path d="M8 8h3"/></svg>',
    'headphones' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/></svg>',
    'gift' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 1 1 0-5C10 3 12 8 12 8s2-5 4.5-5a2.5 2.5 0 1 1 0 5"/></svg>',
    'monitor' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
    'recycle' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 19H4.815a1.83 1.83 0 0 1-1.57-.881 1.785 1.785 0 0 1-.004-1.784L7.196 9.5"/><path d="M11 19h8.203a1.83 1.83 0 0 0 1.556-.89 1.784 1.784 0 0 0 0-1.775l-1.226-2.12"/><path d="m14 16-3 3 3 3"/><path d="M8.293 13.596 4.875 8.5l-3.625 6.25"/><path d="m9.5 5.5 4-7"/><path d="M21 10h-5.5l-2.25-3.896"/></svg>',
    'funko' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M12 13v8"/><path d="M9 18h6"/><circle cx="10" cy="7" r="1" fill="currentColor"/><circle cx="14" cy="7" r="1" fill="currentColor"/></svg>',
    'tools' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h4L17 11l-4-4L3 17v4z"/><path d="m14.5 5.5 4 4"/><path d="M12 8 4 16"/><path d="M18 2l4 4-2 2-4-4z"/></svg>',
    'watch' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="6"/><path d="M12 10v2l1 1"/><path d="M16.51 17.35l-.35 3.83a2 2 0 0 1-2 1.82H9.83a2 2 0 0 1-2-1.82l-.35-3.83"/><path d="M7.49 6.65l.35-3.83A2 2 0 0 1 9.83 1h4.35a2 2 0 0 1 2 1.82l.35 3.83"/></svg>',
    'gamepad' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15" cy="13" r="1"/><circle cx="18" cy="10" r="1"/><path d="M17.32 5H6.68a4 4 0 0 0-3.978 3.59c-.006.052-.01.101-.017.152C2.604 9.416 2 14.456 2 16a3 3 0 0 0 3 3c1 0 1.5-.5 2-1l1.414-1.414A2 2 0 0 1 9.828 16h4.344a2 2 0 0 1 1.414.586L17 18c.5.5 1 1 2 1a3 3 0 0 0 3-3c0-1.545-.604-6.584-.685-7.258-.007-.05-.011-.1-.017-.151A4 4 0 0 0 17.32 5z"/></svg>',
    'robot' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/><line x1="8" y1="16" x2="8" y2="16"/><line x1="16" y1="16" x2="16" y2="16"/></svg>',
    'book' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
    'cards' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="14" height="17" rx="2"/><path d="M8 21h12a2 2 0 0 0 2-2V7"/><path d="M6 11h4"/><path d="M6 15h4"/></svg>',
    'tv' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/></svg>',
    'speaker' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><circle cx="12" cy="14" r="4"/><line x1="12" y1="6" x2="12.01" y2="6"/></svg>'
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
$sql = "SELECT id, nome, barcode, imei, prezzo_vendita1, prezzo_vendita2, immagine, quantita, categoria, sottocategoria, sottosottocategoria, data_creazione FROM prodotti ORDER BY nome ASC";
$prodotti_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Homepage Gestionale</title>
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=1">
  <style>
    :root {
      --primary-color: #22c55e;
      --primary-hover: #16a34a;
      --primary-light: #dcfce7;
      --secondary-color: #64748b;
      --success-color: #22c55e;
      --danger-color: #ef4444;
      --danger-hover: #dc2626;
      --light-bg: #f8fafc;
      --white-bg: #ffffff;
      --text-dark: #1e293b;
      --text-light: #64748b;
      --text-muted: #94a3b8;
      --border-color: #e2e8f0;
      --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
      --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
      --shadow-glow: 0 0 20px rgba(34, 197, 94, 0.15);
      --border-radius: 12px;
      --border-radius-lg: 16px;
      --warning-color: #f59e0b;
      --warning-light: #fef3c7;
      --info-color: #3b82f6;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: var(--light-bg);
      color: var(--text-dark);
      -webkit-font-smoothing: antialiased;
      overflow-x: hidden;
    }

    /* RIMOZIONE PARTICLE CONTAINER PROBLEMATICHE */
    .particles-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 0;
      overflow: hidden;
      display: none; /* NASCONDI LE PARTICLE */
    }

    .particle {
      position: absolute;
      border-radius: 50%;
      opacity: 0.05; /* RIDOTTO DA 0.12 A 0.05 */
      animation: floatParticle 22s infinite ease-in-out;
      filter: blur(1px);
    }

    @keyframes floatParticle {
      0%, 100% { transform: translate(0, 0) scale(1); }
      25% { transform: translate(30px, -30px) scale(1.05); }
      50% { transform: translate(-20px, 20px) scale(0.95); }
      75% { transform: translate(15px, 15px) scale(1.02); }
    }

    .main-content {
      display: flex;
      padding: 30px;
      gap: 30px;
      position: relative;
      z-index: 1;
      min-height: calc(100vh - 200px);
    }

    .sidebar {
      display: flex;
      flex-direction: column;
      gap: 8px;
      width: 220px;
      flex-shrink: 0;
      background: var(--white-bg);
      padding: 16px;
      border-radius: var(--border-radius-lg);
      border: 1px solid var(--border-color);
      box-shadow: var(--shadow-sm);
      height: fit-content;
      position: sticky;
      top: 170px;
    }

    .reset-btn, .category-btn {
      display: flex;
      align-items: center;
      gap: 12px;
      background: var(--light-bg);
      border: 2px solid transparent;
      padding: 12px 14px;
      font-size: 14px;
      cursor: pointer;
      border-radius: 10px;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      text-align: left;
      font-weight: 500;
      color: var(--text-dark);
    }

    .reset-btn:hover, .category-btn:hover {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
      color: var(--white-bg);
      border-color: transparent;
      transform: translateX(4px);
      box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    }

    .category-btn.active, .reset-btn.active {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
      color: var(--white-bg);
      border-color: transparent;
      box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25);
    }

    .search-panel {
      background: var(--white-bg);
      padding: 28px;
      border-radius: var(--border-radius-lg);
      box-shadow: var(--shadow-md);
      margin-bottom: 30px;
      border: 1px solid var(--border-color);
    }

    .search-panel h3 {
      margin-top: 0;
      margin-bottom: 24px;
      font-size: 20px;
      font-weight: 700;
      color: var(--text-dark);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .search-panel h3::before {
      content: '';
      width: 4px;
      height: 24px;
      background: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-hover) 100%);
      border-radius: 2px;
    }

    .input-row {
      display: flex;
      gap: 20px;
      align-items: flex-end;
    }

    .input-group {
      display: flex;
      flex-direction: column;
      flex: 1;
      position: relative;
    }

    .input-group label {
      margin-bottom: 10px;
      font-size: 13px;
      color: var(--text-muted);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .input-group input {
      padding: 12px 14px;
      border: 2px solid var(--border-color);
      border-radius: 10px;
      background: var(--light-bg);
      width: 100%;
      box-sizing: border-box;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      font-size: 15px;
      color: var(--text-dark);
    }

    .input-group input::placeholder {
      color: var(--text-muted);
    }

    .input-group input:focus {
      outline: none;
      border-color: var(--primary-color);
      background: var(--white-bg);
      box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
    }

    .clear-filters-btn {
      background: var(--white-bg);
      border: 2px solid var(--border-color);
      color: var(--text-light);
      padding: 12px 20px;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .clear-filters-btn:hover {
      background: var(--danger-color);
      border-color: var(--danger-color);
      color: white;
      transform: translateY(-2px);
    }

    .content-with-cart {
      display: flex;
      gap: 30px;
      align-items: flex-start;
    }

    .product-grid {
      flex: 1;
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 24px;
      position: relative;
      min-height: 400px;
    }

    .product-card {
      background: var(--white-bg);
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius);
      overflow: hidden;
      box-shadow: var(--shadow-md);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
      animation: fadeIn 0.4s ease-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .product-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-lg), var(--shadow-glow);
      border-color: var(--primary-light);
    }

    .product-image-container {
      width: 100%;
      height: 180px;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 16px;
      box-sizing: border-box;
      background: var(--light-bg);
    }

    .product-image-container img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .product-card:hover .product-image-container img {
      transform: scale(1.08);
    }

    .product-info {
      padding: 16px;
      display: flex;
      flex-direction: column;
      flex-grow: 1;
      border-top: 1px solid var(--border-color);
      background: var(--white-bg);
    }

    .product-name {
      font-weight: 600;
      font-size: 14px;
      margin-bottom: 10px;
      color: var(--text-dark);
      line-height: 1.4;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      letter-spacing: -0.2px;
    }

    .price-selector {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 6px;
      margin-bottom: 12px;
    }

    .price-selector label {
      padding: 10px 8px;
      border: 2px solid var(--border-color);
      border-radius: 10px;
      text-align: center;
      cursor: pointer;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      font-size: 15px;
      font-weight: 700;
      color: var(--text-dark);
      background: var(--light-bg);
    }

    .price-selector input[type="radio"] {
      display: none;
    }

    .price-selector label:hover {
      border-color: var(--primary-color);
      background-color: var(--primary-light);
      transform: translateY(-2px);
    }

    .price-selector label:has(input:checked) {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
      color: var(--white-bg);
      border-color: transparent;
      box-shadow: 0 4px 12px rgba(34, 197, 94, 0.35);
      transform: translateY(-2px);
    }

    .stock {
      font-size: 11px;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      margin-bottom: 12px;
      color: var(--success-color);
      background-color: var(--primary-light);
      border: none;
      width: fit-content;
    }

    .stock::before {
      content: '';
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: currentColor;
    }

    .stock.low-stock {
      color: #d97706;
      background-color: var(--warning-light);
    }

    .stock.out-of-stock {
      color: var(--danger-color);
      background-color: #fee2e2;
    }

    .controls-container {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-top: auto;
      padding-top: 12px;
      border-top: 1px solid var(--border-color);
    }

    .qty-controls {
      display: flex;
      align-items: center;
      background: var(--light-bg);
      border-radius: 10px;
      padding: 4px;
      gap: 2px;
    }

    .qty-btn {
      width: 32px;
      height: 32px;
      border: none;
      background: var(--white-bg);
      color: var(--text-dark);
      border-radius: 8px;
      cursor: pointer;
      display: flex;
      justify-content: center;
      align-items: center;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      font-weight: 600;
      font-size: 16px;
    }

    .qty-btn:hover {
      background: var(--primary-color);
      color: white;
      transform: scale(1.1);
    }

    .qty-input {
      width: 36px;
      text-align: center;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      padding: 4px;
      font-weight: 700;
      color: var(--text-dark);
      background: transparent;
      height: 32px;
      box-sizing: border-box;
    }

    .qty-input:focus {
      outline: none;
      background: var(--white-bg);
    }

    .add-to-cart-btn {
      background: linear-gradient(135deg, var(--success-color) 0%, var(--primary-hover) 100%);
      border: none;
      padding: 0;
      cursor: pointer;
      margin-left: auto;
      width: 46px;
      height: 46px;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
    }

    .add-to-cart-btn:hover {
      background: linear-gradient(135deg, var(--primary-hover) 0%, #15803d 100%);
      transform: scale(1.15) rotate(5deg);
      box-shadow: 0 8px 25px rgba(34, 197, 94, 0.45);
    }

    .add-to-cart-btn svg {
      stroke: var(--white-bg);
      transition: all 0.2s ease;
    }

    .empty-grid-message {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      text-align: center;
      color: var(--text-muted);
      font-size: 1em;
      display: none;
      padding: 40px;
      background: var(--white-bg);
      border-radius: var(--border-radius-lg);
      border: 2px dashed var(--border-color);
      max-width: 320px;
    }

    .empty-grid-message p {
      margin: 0 0 8px 0;
      font-weight: 600;
      color: var(--text-dark);
      font-size: 16px;
    }

    .cart-preview {
      display: none;
      width: 360px;
      border: none;
      padding: 0;
      border-radius: var(--border-radius-lg);
      background: var(--white-bg);
      box-shadow: 0 10px 40px rgba(0,0,0,0.12);
      flex-direction: column;
      position: sticky;
      top: 170px;
      overflow: hidden;
      border: 1px solid var(--border-color);
      height: fit-content;
      max-height: calc(100vh - 200px);
    }

    .cart-preview.visible {
      display: flex;
    }

    .cart-header {
      margin: 0;
      padding: 20px 24px;
      font-size: 18px;
      font-weight: 700;
      color: white;
      background: linear-gradient(135deg, var(--primary-color) 0%, #15803d 50%, var(--primary-hover) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      letter-spacing: -0.3px;
    }

    .cart-header svg {
      width: 22px;
      height: 22px;
    }

    .cart-badge-count {
      background: white;
      color: var(--primary-hover);
      font-size: 14px;
      font-weight: 800;
      padding: 4px 12px;
      border-radius: 20px;
      min-width: 24px;
      text-align: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .cart-preview ul {
      flex: 1;
      list-style: none;
      padding: 16px;
      margin: 0;
      max-height: 380px;
      overflow-y: auto;
      position: relative;
      min-height: 140px;
      background: linear-gradient(180deg, var(--white-bg) 0%, var(--light-bg) 100%);
    }

    .cart-preview ul::-webkit-scrollbar {
      width: 6px;
    }

    .cart-preview ul::-webkit-scrollbar-track {
      background: transparent;
      border-radius: 3px;
    }

    .cart-preview ul::-webkit-scrollbar-thumb {
      background: var(--border-color);
      border-radius: 3px;
    }

    .cart-preview ul::-webkit-scrollbar-thumb:hover {
      background: var(--text-muted);
    }

    .cart-preview li {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px;
      margin-bottom: 10px;
      background: var(--white-bg);
      border-radius: 14px;
      position: relative;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      border: 1px solid var(--border-color);
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }

    .cart-preview li:hover {
      border-color: var(--primary-light);
      box-shadow: 0 4px 15px rgba(34, 197, 94, 0.1);
      transform: translateX(4px);
    }

    .empty-cart-message {
      display: none;
      text-align: center;
      color: var(--text-muted);
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 100%;
      padding: 24px;
    }

    .empty-cart-message.visible {
      display: block;
    }

    .item-img {
      width: 52px;
      height: 52px;
      border-radius: 12px;
      object-fit: cover;
      border: 2px solid var(--light-bg);
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      flex-shrink: 0;
      background: var(--light-bg);
    }

    .item-info {
      flex: 1;
      min-width: 0;
    }

    .item-info .name {
      font-weight: 600;
      font-size: 13px;
      display: block;
      color: var(--text-dark);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 4px;
      line-height: 1.3;
    }

    .item-info .details {
      font-size: 11px;
      color: var(--text-muted);
      display: block;
    }

    .item-info .subtotal {
      font-size: 15px;
      font-weight: 800;
      color: var(--primary-hover);
      display: block;
      margin-top: 6px;
    }

    .cart-summary {
      padding: 24px;
      background: linear-gradient(180deg, #f0fdf4 0%, #dcfce7 100%);
      border-top: none;
    }

    .cart-total {
      font-weight: 800;
      text-align: center;
      font-size: 32px;
      color: var(--primary-hover);
      margin-bottom: 4px;
      text-shadow: 0 2px 4px rgba(34, 197, 94, 0.15);
    }

    .cart-total-label {
      text-align: center;
      font-size: 12px;
      color: var(--text-muted);
      margin-bottom: 20px;
      display: block;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 600;
    }

    .cart-actions {
      display: flex;
      gap: 10px;
    }

    .cart-actions button {
      padding: 14px 18px;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      font-size: 15px;
      font-weight: 700;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      flex: 1;
      letter-spacing: -0.2px;
    }

    .go-cart-btn {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
      color: white;
      box-shadow: 0 4px 15px rgba(34, 197, 94, 0.35);
    }

    .go-cart-btn:hover {
      background: linear-gradient(135deg, var(--primary-hover) 0%, #15803d 100%);
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(34, 197, 94, 0.45);
    }

    .empty-cart-btn {
      background: white;
      color: var(--danger-color);
      border: 2px solid #fecaca;
    }

    .empty-cart-btn:hover {
      background: var(--danger-color);
      color: white;
      border-color: var(--danger-color);
      transform: translateY(-3px);
    }

    @media (max-width: 1200px) {
      .main-content {
        flex-direction: column;
        padding: 20px;
      }

      .sidebar {
        width: 100%;
        flex-direction: row;
        flex-wrap: wrap;
        margin-top: 0;
        padding: 12px;
        gap: 6px;
        position: static;
      }

      .content-with-cart {
        flex-direction: column-reverse;
        gap: 20px;
      }

      .cart-preview {
        width: 100%;
        position: static;
        border-radius: var(--border-radius);
        max-height: none;
      }

      .product-grid {
        gap: 16px;
      }
    }

    @media (max-width: 768px) {
      .input-row {
        flex-direction: column;
        align-items: stretch;
        gap: 16px;
      }

      .search-panel {
        padding: 20px;
      }

      .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 12px;
      }

      .product-card {
        min-height: 340px;
      }

      .product-image-container {
        height: 140px;
      }
    }

    @media (max-width: 480px) {
      .main-content {
        padding: 12px;
      }

      .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
      }

      .product-card {
        min-height: 300px;
      }

      .product-name {
        font-size: 12px;
      }
    }

    #toast-fixed {
      position: fixed;
      bottom: 30px;
      right: 30px;
      z-index: 999999;
      padding: 16px 24px;
      border-radius: 14px;
      font-size: 14px;
      font-weight: 600;
      color: #fff;
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
      box-shadow: 0 8px 30px rgba(34, 197, 94, 0.4);
      transform: translateX(calc(100% + 50px));
      opacity: 0;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      align-items: center;
      gap: 12px;
      max-width: 400px;
    }

    #toast-fixed.show {
      transform: translateX(0);
      opacity: 1;
    }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <!-- RIMOZIONE/DISABILITAZIONE DEL PARTICLES CONTAINER -->
  <!-- <div class="particles-container">...</div> -->

  <div id="toast-container" aria-live="polite" aria-atomic="true"></div>

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

    <section class="input-section" style="flex: 1;">
      <div class="search-panel">
        <h3>Filtra Prodotti</h3>
        <div class="input-row" role="search">
          <div class="input-group">
            <label for="input-nome">Nome Prodotto</label>
            <input type="text" id="input-nome" placeholder="Cerca per nome..." oninput="filtraProdotti()" />
          </div>
          <div class="input-group">
            <label for="input-barcode">Codice a Barre</label>
            <input type="text" id="input-barcode" placeholder="Cerca per barcode..." oninput="filtraProdotti()" />
          </div>
          <div class="input-group">
            <label for="input-imei">IMEI</label>
            <input type="text" id="input-imei" placeholder="Cerca per IMEI..." oninput="filtraProdotti()" />
          </div>
          <button class="clear-filters-btn" onclick="document.querySelector('.reset-btn').click()">Pulisci</button>
        </div>
      </div>

      <div class="content-with-cart">
        <div class="product-grid" id="product-grid">
          <?php while($prod = $prodotti_result->fetch_assoc()): ?>
            <?php 
              $nome_low = strtolower($prod['nome']);
              $barcode_low = strtolower($prod['barcode']);
              $imei_low = strtolower($prod['imei']);
              $cat_low = strtolower($prod['categoria']);
              $subcat_low = strtolower($prod['sottocategoria'] ?? '');
              $subsubcat_low = strtolower($prod['sottosottocategoria'] ?? '');
              
              $stock_class = '';
              $stock_text = 'Disponibile: ' . intval($prod['quantita']);
              if ($prod['quantita'] <= 0) {
                $stock_class = 'out-of-stock';
                $stock_text = 'Esaurito';
              } elseif ($prod['quantita'] <= 10) {
                $stock_class = 'low-stock';
                $stock_text = 'Pochi pezzi: ' . intval($prod['quantita']);
              }
            ?>
            <div class="product-card" data-id="<?php echo $prod['id']; ?>" data-stock="<?php echo intval($prod['quantita']); ?>" data-nome="<?php echo htmlspecialchars($nome_low); ?>" data-barcode="<?php echo htmlspecialchars($barcode_low); ?>" data-imei="<?php echo htmlspecialchars($imei_low); ?>" data-categoria="<?php echo htmlspecialchars($cat_low); ?>" data-sottocategoria="<?php echo htmlspecialchars($subcat_low); ?>" data-sottosottocategoria="<?php echo htmlspecialchars($subsubcat_low); ?>">
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
                  </button>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
          <div class="empty-grid-message" id="empty-grid-message"><p>Nessun prodotto trovato.</p></div>
        </div>

        <aside class="cart-preview" id="cart-preview" role="region" aria-label="Anteprima carrello">
          <div class="cart-header">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.53h9.72a2 2 0 0 0 2-1.53l1.66-7.43H5.12"/></svg>
            Carrello
            <span class="cart-badge-count" id="cart-badge-count">0</span>
          </div>
          <ul id="cart-items">
            <div class="empty-cart-message" id="empty-cart-message">
              <p>Il tuo carrello è vuoto</p>
            </div>
          </ul>
          <div class="cart-summary">
            <span class="cart-total-label">Totale da pagare</span>
            <div class="cart-total" id="cart-total">€0,00</div>
            <div class="cart-actions">
              <button class="go-cart-btn" onclick="vaiAlCarrello()">Vai al carrello</button>
              <button class="empty-cart-btn" onclick="svuotaCarrello()">Svuota</button>
            </div>
          </div>
        </aside>
      </div>
    </section>
  </div>

  <div id="toast-fixed"></div>

  <script>
    let filtroCategoria = null;
    let cart = JSON.parse(localStorage.getItem('cart')) || {};
    
    const cartPreview = document.getElementById('cart-preview');
    const cartItemsList = document.getElementById('cart-items');
    const cartTotalElem = document.getElementById('cart-total');
    const emptyCartMessage = document.getElementById('empty-cart-message');
    const toastFixed = document.getElementById('toast-fixed');

    function showToast(msg, type = 'success') {
      toastFixed.className = '';
      toastFixed.classList.add(type);
      toastFixed.textContent = msg;
      toastFixed.classList.add('show');
      setTimeout(() => {
        toastFixed.classList.remove('show');
      }, 3000);
    }

    function filtraCategoria(btn, catName) {
      document.querySelectorAll('.sidebar button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      filtroCategoria = catName;
      filtraProdotti();
    }

    function filtraProdotti() {
      const nome = document.getElementById('input-nome').value.toLowerCase();
      const barcode = document.getElementById('input-barcode').value.toLowerCase();
      const imei = document.getElementById('input-imei').value.toLowerCase();
      let count = 0;

      document.querySelectorAll('.product-card').forEach(card => {
        const matchText = card.dataset.nome.includes(nome) &&
                         card.dataset.barcode.includes(barcode) &&
                         card.dataset.imei.includes(imei);
        const matchCat = !filtroCategoria || card.dataset.categoria === filtroCategoria;

        if (matchText && matchCat) {
          card.style.display = 'block';
          count++;
        } else {
          card.style.display = 'none';
        }
      });

      document.getElementById('empty-grid-message').style.display = count === 0 ? 'block' : 'none';
    }

    function modificaQuantita(btn, d) {
      const i = btn.parentElement.querySelector('.qty-input');
      i.value = Math.min(Math.max(parseInt(i.value) + d, 1), parseInt(i.max));
    }

    function formatPrice(p) {
      return '€' + parseFloat(p).toFixed(2).replace('.', ',');
    }

    function aggiungiAlCarrello(e, btn) {
      e.stopPropagation();
      const card = btn.closest('.product-card');
      const id = card.dataset.id;
      const name = card.querySelector('.product-name').textContent;
      const img = card.querySelector('img').src;
      const price = parseFloat(card.querySelector('input[type=radio]:checked').value);
      const qty = parseInt(card.querySelector('.qty-input').value);
      const stock = parseInt(card.dataset.stock);
      const key = `${id}_${price.toFixed(2)}`;

      if ((cart[key]?.qty || 0) + qty > stock) {
        showToast(`Giacenza non sufficiente (${stock} pz)!`, "error");
        return;
      }

      const isNew = !cart[key];
      if (cart[key]) {
        cart[key].qty += qty;
      } else {
        cart[key] = { id, name, price, qty, img, giacenza: stock };
      }

      aggiornaAnteprima(isNew);
      showToast(`"${name}" aggiunto al carrello.`);
    }

    function aggiornaAnteprima(isNewItem = false) {
      cartItemsList.innerHTML = '';
      let tot = 0;
      let itemCount = 0;
      const cartEntries = Object.entries(cart);
      const hasItems = cartEntries.length > 0;

      cartEntries.forEach(([key, it]) => {
        itemCount += it.qty;
      });

      document.getElementById('cart-badge-count').textContent = itemCount;
      cartPreview.classList.toggle('visible', hasItems);
      emptyCartMessage.classList.toggle('visible', !hasItems);
      document.querySelector('.cart-summary').style.display = hasItems ? 'block' : 'none';

      if (hasItems) {
        cartEntries.forEach(([key, it]) => {
          const subtotal = it.qty * it.price;
          tot += subtotal;
          const li = document.createElement('li');
          li.innerHTML = `
            <img class="item-img" src="${it.img}" alt="${it.name}" />
            <div class="item-info">
              <span class="name">${it.name}</span>
              <span class="details">${it.qty} × ${formatPrice(it.price)}</span>
              <span class="subtotal">${formatPrice(subtotal)}</span>
            </div>
            <button class="remove-btn" onclick="rimuoviDalCarrello('${key}')" title="Rimuovi" style="position: absolute; top: -6px; right: -6px; background: #ef4444; border: 2px solid white; color: white; cursor: pointer; padding: 4px; border-radius: 8px; display: flex; align-items: center; width: 24px; height: 24px; justify-content: center;">
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
          `;
          cartItemsList.appendChild(li);
        });
      } else {
        cartItemsList.appendChild(emptyCartMessage);
      }

      cartTotalElem.textContent = formatPrice(tot);
      localStorage.setItem('cart', JSON.stringify(cart));
    }

    function rimuoviDalCarrello(key) {
      delete cart[key];
      aggiornaAnteprima();
      showToast("Prodotto rimosso.", "success");
    }

    function svuotaCarrello() {
      if (!Object.keys(cart).length) {
        showToast("Il carrello è già vuoto.", "warning");
        return;
      }
      cart = {};
      aggiornaAnteprima();
      showToast("Carrello svuotato.", "warning");
    }

    function vaiAlCarrello() {
      if (!Object.keys(cart).length) {
        showToast("Il carrello è vuoto!", "warning");
        return;
      }
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'carrello.php';
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'carrello_json';
      input.value = JSON.stringify(Object.values(cart));
      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();
    }

    window.addEventListener('DOMContentLoaded', () => {
      aggiornaAnteprima();
      document.querySelector('.reset-btn').click();
    });
  </script>
</body>
</html>