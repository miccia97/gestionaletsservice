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
    /* PARTICLES */
    .particles-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; overflow: hidden; }
    .particle { position: absolute; border-radius: 50%; opacity: 0.12; animation: floatParticle 22s infinite ease-in-out; }
    .particle:nth-child(1) { width: 320px; height: 320px; background: var(--primary-color); top: -120px; left: -80px; animation-delay: 0s; }
    .particle:nth-child(2) { width: 220px; height: 220px; background: var(--info-color); top: 50%; right: -60px; animation-delay: -6s; }
    .particle:nth-child(3) { width: 180px; height: 180px; background: #8b5cf6; bottom: 8%; left: 25%; animation-delay: -12s; }
    .particle:nth-child(4) { width: 120px; height: 120px; background: var(--warning-color); top: 25%; left: 55%; animation-delay: -17s; }
    @keyframes floatParticle {
        0%, 100% { transform: translate(0, 0) scale(1); }
        25% { transform: translate(30px, -30px) scale(1.05); }
        50% { transform: translate(-20px, 20px) scale(0.95); }
        75% { transform: translate(15px, 15px) scale(1.02); }
    }

    /* Badge Nuovo sui prodotti recenti */
    .new-badge {
      position: absolute;
      top: 12px;
      right: 12px;
      background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
      color: white;
      font-size: 9px;
      font-weight: 700;
      padding: 5px 10px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      z-index: 10;
      box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
      animation: badgePulse 2s ease-in-out infinite;
    }
    @keyframes badgePulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    
    body {
      margin: 0; 
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: var(--light-bg); 
      color: var(--text-dark);
      -webkit-font-smoothing: antialiased;
    }
    .main-content { display: flex; padding: 30px; gap: 30px; position: relative; z-index: 1; }
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
    .reset-btn svg, .category-btn svg {
        color: var(--text-light);
        transition: all 0.25s ease;
        flex-shrink: 0;
    }
    .reset-btn:hover, .category-btn:hover { 
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%); 
      color: var(--white-bg); 
      border-color: transparent;
      transform: translateX(4px); 
      box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    }
    .reset-btn:hover svg, .category-btn:hover svg,
    .category-btn.active svg, .reset-btn.active svg {
        color: var(--white-bg);
    }
    .category-btn.active, .reset-btn.active {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%); 
      color: var(--white-bg); 
      border-color: transparent;
      box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25);
    }
    
    .input-section { flex-grow: 1; }
    
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
    .input-row { display: flex; gap: 20px; align-items: flex-end; }
    .input-group { display: flex; flex-direction: column; flex: 1; position: relative; }
    .input-group label { 
      margin-bottom: 10px; 
      font-size: 13px; 
      color: var(--text-muted); 
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .input-icon {
        position: absolute;
        left: 14px;
        top: 43px;
        color: var(--text-muted);
        pointer-events: none;
        transition: color 0.2s ease;
    }
    .input-group:focus-within .input-icon {
        color: var(--primary-color);
    }
    .input-group input { 
      padding: 12px 14px 12px 44px; 
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
    .input-group input:hover {
      border-color: #cbd5e1;
      background: var(--white-bg);
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

    /* Barre sottocategorie */
    .subcategory-container, .subsubcategory-container {
      margin: 24px 0; 
      display: none; 
      justify-content: flex-start; 
      flex-wrap: wrap; 
      gap: 10px; 
      max-height: 0;
      overflow: hidden; 
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
      opacity: 0;
      padding: 0;
    }
    .subcategory-container.visible, .subsubcategory-container.visible { 
      display: flex; 
      max-height: 200px; 
      opacity: 1;
      padding: 16px;
      background: var(--white-bg);
      border-radius: var(--border-radius);
      border: 1px solid var(--border-color);
      box-shadow: var(--shadow-sm);
    }
    .subcategory-container button, .subsubcategory-container button {
      background: var(--light-bg); 
      border: 2px solid transparent; 
      padding: 10px 18px; 
      border-radius: 25px; 
      font-size: 13px; 
      font-weight: 600;
      cursor: pointer; 
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      color: var(--text-dark);
    }
    .subcategory-container button:hover, .subsubcategory-container button:hover { 
      background: var(--primary-light); 
      color: var(--primary-hover); 
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(34, 197, 94, 0.2);
    }
    .subcategory-container button[aria-pressed="true"] { 
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%); 
      color: white; 
      box-shadow: 0 4px 15px rgba(34, 197, 94, 0.35);
      border-color: transparent;
    }
    .subsubcategory-container button { 
      background: #f1f5f9;
      font-size: 12px;
      padding: 8px 14px;
    }
    .subsubcategory-container button:hover { 
      background: var(--secondary-color); 
      color: white;
    }
    .subsubcategory-container button[aria-pressed="true"] { 
      background: var(--secondary-color); 
      color: white; 
      box-shadow: 0 4px 12px rgba(100, 116, 139, 0.3);
    }

    .content-with-cart { display: flex; gap: 30px; align-items: flex-start; }
    .product-grid { 
      flex: 1; 
      display: grid; 
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); 
      gap: 24px; 
      position: relative;
      min-height: 400px;
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
    .empty-grid-message svg {
      width: 64px;
      height: 64px;
      margin-bottom: 16px;
      stroke: var(--border-color);
      stroke-width: 1.5;
    }
    .empty-grid-message p {
      margin: 0 0 8px 0;
      font-weight: 600;
      color: var(--text-dark);
      font-size: 16px;
    }
    .empty-grid-message span {
      font-size: 13px;
      color: var(--text-muted);
    }
    
    /* --- CARD PRODOTTO CON EFFETTO FLIP --- */
    .product-card {
      perspective: 1000px;
      background-color: transparent;
      border: none;
      box-shadow: none;
      display: flex;
      flex-direction: column;
      min-height: 400px;
      animation: fadeIn 0.4s ease-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .product-card:hover .card-front {
      transform: translateY(-8px);
      box-shadow: var(--shadow-lg), var(--shadow-glow);
      border-color: var(--primary-light);
    }
    .card-inner {
      position: relative;
      width: 100%;
      flex: 1;
      transition: transform 0.6s;
      transform-style: preserve-3d;
    }
    .product-card.is-flipped .card-inner {
      transform: rotateY(180deg);
    }
    .card-front, .card-back {
      width: 100%;
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
      position: relative;
    }
    .card-back {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, var(--white-bg) 0%, var(--light-bg) 100%);
      color: var(--text-dark);
      transform: rotateY(180deg);
      padding: 20px;
      font-size: 13px;
      box-sizing: border-box;
      justify-content: flex-start;
      pointer-events: none;
    }
    .product-card.is-flipped .card-back {
      pointer-events: auto;
    }
    .product-card.is-flipped .card-front {
      pointer-events: none;
    }
    .card-back h5 {
      margin: 0 0 16px 0; 
      font-size: 15px; 
      text-align: center;
      font-weight: 700;
      color: var(--text-dark);
      padding-bottom: 12px;
      border-bottom: 2px solid var(--primary-light);
    }
    .card-back-details { 
      flex: 1;
      overflow-y: auto;
    }
    .card-back-details p { 
      margin: 0 0 10px 0; 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      background: var(--white-bg);
      padding: 10px 12px;
      border-radius: 10px;
      border: 1px solid var(--border-color);
    }
    .card-back-details p:last-child { margin-bottom: 0; }
    .card-back-details p strong { 
      color: var(--text-muted); 
      margin-right: 10px; 
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    .card-back-details p span { 
      font-weight: 700; 
      text-align: right; 
      word-break: break-all;
      color: var(--text-dark);
      font-size: 13px;
    }
    .flip-back-btn {
      position: absolute; 
      top: 12px; 
      right: 12px; 
      background: var(--white-bg); 
      border: none; 
      cursor: pointer; 
      padding: 8px; 
      line-height: 1; 
      color: var(--text-muted);
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: all 0.2s ease;
    }
    .flip-back-btn:hover { 
      color: var(--danger-color);
      transform: rotate(90deg);
    }
    .modal-loader { display: block; }
    .spinner { 
      border: 3px solid var(--border-color); 
      width: 40px; 
      height: 40px; 
      border-radius: 50%; 
      border-top-color: var(--primary-color); 
      animation: spin 0.8s ease infinite; 
      margin: auto;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    
    /* Skeleton Loading */
    .skeleton-card {
      background: var(--white-bg);
      border-radius: var(--border-radius);
      overflow: hidden;
      border: 1px solid var(--border-color);
    }
    .skeleton-image {
      height: 180px;
      background: linear-gradient(90deg, var(--light-bg) 25%, #e2e8f0 50%, var(--light-bg) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.5s infinite;
    }
    .skeleton-content {
      padding: 16px;
    }
    .skeleton-line {
      height: 14px;
      background: linear-gradient(90deg, var(--light-bg) 25%, #e2e8f0 50%, var(--light-bg) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.5s infinite;
      border-radius: 6px;
      margin-bottom: 12px;
    }
    .skeleton-line.short { width: 60%; }
    .skeleton-line.medium { width: 80%; }
    .skeleton-btn {
      height: 44px;
      background: linear-gradient(90deg, var(--light-bg) 25%, #e2e8f0 50%, var(--light-bg) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.5s infinite;
      border-radius: 10px;
      margin-top: 16px;
    }
    @keyframes shimmer {
      0% { background-position: -200% 0; }
      100% { background-position: 200% 0; }
    }

    .product-image-container { 
      width: 100%; 
      height: 180px; 
      display: flex; 
      justify-content: center; 
      align-items: center; 
      padding: 16px; 
      box-sizing: border-box; 
      background: transparent;
      position: relative;
    }
    .card-front img { 
      max-width: 100%; 
      max-height: 100%; 
      object-fit: contain; 
      transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .product-card:hover .card-front img { transform: scale(1.08); }
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
    
    .price-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 12px; }
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
    .price-selector input[type="radio"] { display: none; }
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
    /* Overlay per prodotti esauriti */
    .product-card.out-of-stock-card .product-image-container::after {
      content: 'ESAURITO';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-15deg);
      background: rgba(239, 68, 68, 0.9);
      color: white;
      padding: 8px 24px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 1px;
      border-radius: 4px;
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }
    .product-card.out-of-stock-card .card-front img {
      opacity: 0.4;
      filter: grayscale(30%);
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
    .qty-btn:active {
      transform: scale(0.95);
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
    .qty-input::-webkit-inner-spin-button, .qty-input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
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
    .add-to-cart-btn:active {
      transform: scale(0.95);
    }
    .add-to-cart-btn.success { 
      background: linear-gradient(135deg, #10B981 0%, #059669 100%); 
      transform: scale(1.15);
      animation: successPop 0.4s ease;
    }
    @keyframes successPop {
      0%, 100% { transform: scale(1.15); }
      50% { transform: scale(1.3); }
    }
    .add-to-cart-btn svg { stroke: var(--white-bg); transition: all 0.2s ease; }
    .info-btn { 
      position: absolute; 
      top: 12px; 
      left: 12px; 
      width: 32px; 
      height: 32px; 
      border-radius: 10px; 
      border: none; 
      background: var(--white-bg); 
      cursor: pointer; 
      color: var(--text-muted); 
      display: flex; 
      justify-content: center; 
      align-items: center; 
      z-index: 10; 
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .info-btn:hover { 
      background: var(--info-color); 
      color: white;
      transform: scale(1.1) rotate(10deg);
      box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
    }
    .info-btn svg {
      width: 16px;
      height: 16px;
    }

    /* --- ANTEPRIMA CARRELLO MIGLIORATA --- */
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
      top: 24px; 
      overflow: hidden;
      border: 1px solid var(--border-color);
    }
    .cart-preview.visible { display: flex; }
    .cart-preview.pulse { animation: cartPulse 0.6s cubic-bezier(0.4, 0, 0.2, 1); }
    @keyframes cartPulse {
      0%, 100% { transform: scale(1); box-shadow: 0 10px 40px rgba(0,0,0,0.12); }
      50% { transform: scale(1.02); box-shadow: 0 15px 50px rgba(34, 197, 94, 0.35); }
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
    .cart-header svg { width: 22px; height: 22px; }
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
    /* Scrollbar stilizzata */
    .cart-preview ul::-webkit-scrollbar { width: 6px; }
    .cart-preview ul::-webkit-scrollbar-track { background: transparent; border-radius: 3px; }
    .cart-preview ul::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 3px; }
    .cart-preview ul::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }
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
    .cart-preview li:last-child { margin-bottom: 0; }
    .cart-preview li.new-item { animation: newItemPop 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
    @keyframes newItemPop {
      0% { transform: scale(0.8) translateX(-20px); opacity: 0; }
      60% { transform: scale(1.02) translateX(5px); }
      100% { transform: scale(1) translateX(0); opacity: 1; }
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
    .empty-cart-message.visible { display: block; }
    .empty-cart-message svg { 
      width: 64px; 
      height: 64px; 
      margin-bottom: 16px; 
      stroke-width: 1; 
      color: var(--border-color);
    }
    .empty-cart-message p { 
      font-size: 15px; 
      margin: 0 0 4px 0;
      font-weight: 600;
      color: var(--text-light);
    }
    .empty-cart-message span {
      font-size: 13px;
      color: var(--text-muted);
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
    .item-info { flex: 1; min-width: 0; }
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
    .cart-item-qty-controls { 
      display: flex; 
      align-items: center; 
      gap: 2px; 
      background: var(--light-bg); 
      border-radius: 10px; 
      padding: 4px;
    }
    .cart-qty-btn { 
      width: 28px; 
      height: 28px; 
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
    }
    .cart-qty-btn:hover { 
      background: var(--primary-color); 
      color: white;
      transform: scale(1.1);
    }
    .cart-qty-input { 
      width: 32px; 
      text-align: center; 
      border: none; 
      background: transparent; 
      font-size: 13px; 
      font-weight: 700; 
      color: var(--text-dark);
    }
    .remove-btn { 
      position: absolute; 
      top: -6px; 
      right: -6px; 
      background: var(--danger-color); 
      border: 2px solid var(--white-bg);
      color: white; 
      cursor: pointer; 
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
      padding: 4px; 
      border-radius: 8px; 
      display: flex; 
      align-items: center;
      opacity: 0;
      transform: scale(0.8);
      box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    }
    .cart-preview li:hover .remove-btn {
      opacity: 1;
      transform: scale(1);
    }
    .remove-btn:hover { 
      transform: scale(1.15);
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.5);
    }
    .remove-btn svg { width: 12px; height: 12px; }
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
    .cart-actions { display: flex; gap: 10px; }
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

    /* Toast fisso sullo schermo */
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
    #toast-fixed::before {
      content: '✓';
      width: 24px;
      height: 24px;
      background: rgba(255,255,255,0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
    }
    #toast-fixed.show {
      transform: translateX(0);
      opacity: 1;
    }
    #toast-fixed.error { 
      background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%); 
      box-shadow: 0 8px 30px rgba(239, 68, 68, 0.4);
    }
    #toast-fixed.error::before { content: '✗'; }
    #toast-fixed.warning { 
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); 
      color: #1e293b;
      box-shadow: 0 8px 30px rgba(245, 158, 11, 0.4);
    }
    #toast-fixed.warning::before { content: '!'; }

    /* Modals */
    .modal-overlay { 
      position: fixed; 
      top: 0; 
      left: 0; 
      width: 100%; 
      height: 100%; 
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(8px);
      display: flex; 
      justify-content: center; 
      align-items: center; 
      z-index: 10000; 
      opacity: 0; 
      visibility: hidden; 
      pointer-events: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
    }
    .modal-overlay.active { opacity: 1; visibility: visible; pointer-events: auto; }
    .modal-overlay.visible { opacity: 1; visibility: visible; pointer-events: auto; }
    .modal-content { 
      background: var(--white-bg); 
      border-radius: 20px; 
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25); 
      width: 90%; 
      transform: scale(0.9) translateY(20px); 
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
      padding: 36px; 
      text-align: center; 
      max-width: 420px;
      border: 1px solid var(--border-color);
    }
    .modal-overlay.active .modal-content { transform: scale(1) translateY(0); }
    .modal-content h4 { 
      margin-top: 0; 
      font-size: 22px; 
      font-weight: 700;
      color: var(--text-dark); 
      margin-bottom: 24px;
      line-height: 1.3;
    }
    .modal-buttons { display: flex; justify-content: center; gap: 12px; margin-top: 28px; }
    .modal-buttons button { 
      padding: 12px 28px; 
      border: none; 
      border-radius: 12px; 
      font-size: 15px; 
      font-weight: 600;
      cursor: pointer; 
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .modal-buttons .confirm-btn { 
      background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%); 
      color: #fff;
      box-shadow: 0 4px 15px rgba(239, 68, 68, 0.35);
    }
    .modal-buttons .confirm-btn:hover { 
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(239, 68, 68, 0.45);
    }
    .modal-buttons .cancel-btn { 
      background: var(--light-bg); 
      color: var(--text-dark);
      border: 2px solid var(--border-color);
    }
    .modal-buttons .cancel-btn:hover { 
      background: var(--border-color);
      transform: translateY(-2px);
    }

    /* Badge animato */
    .cart-badge-count {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .cart-badge-count.bump {
      animation: badgeBump 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    @keyframes badgeBump {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.3); }
    }
    
    /* Focus visible per accessibilità */
    button:focus-visible, 
    a:focus-visible,
    input:focus-visible {
      outline: 3px solid var(--primary-color);
      outline-offset: 2px;
    }
    
    /* Selection color */
    ::selection {
      background: var(--primary-light);
      color: var(--primary-hover);
    }

    /* Responsive migliorato */
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
      }
      .sidebar .category-btn,
      .sidebar .reset-btn {
        padding: 10px 14px;
        font-size: 13px;
      }
      .content-with-cart { 
        flex-direction: column-reverse;
        gap: 20px;
      } 
      .cart-preview { 
        width: 100%; 
        position: static;
        border-radius: var(--border-radius);
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
      .search-panel h3 {
        font-size: 18px;
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
      .price-selector label {
        padding: 8px 6px;
        font-size: 13px;
      }
      .cart-preview {
        border-radius: 12px;
      }
      .cart-header {
        padding: 16px 20px;
        font-size: 16px;
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
      .controls-container {
        flex-wrap: wrap;
        gap: 8px;
      }
      .add-to-cart-btn {
        width: 40px;
        height: 40px;
      }
    }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <!-- Particles -->
  <div class="particles-container">
      <div class="particle"></div>
      <div class="particle"></div>
      <div class="particle"></div>
      <div class="particle"></div>
  </div>

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
              
              // Controlla se il prodotto è nuovo (aggiunto negli ultimi 7 giorni)
              $is_new = false;
              if (!empty($prod['data_creazione'])) {
                  $created = strtotime($prod['data_creazione']);
                  $seven_days_ago = strtotime('-7 days');
                  $is_new = ($created >= $seven_days_ago);
              }
            ?>
            <div class="product-card" data-id="<?php echo $prod['id']; ?>" data-stock="<?php echo intval($prod['quantita']); ?>" data-nome="<?php echo htmlspecialchars($nome_low); ?>" data-barcode="<?php echo htmlspecialchars($barcode_low); ?>" data-imei="<?php echo htmlspecialchars($imei_low); ?>" data-categoria="<?php echo htmlspecialchars($cat_low); ?>" data-sottocategoria="<?php echo htmlspecialchars($subcat_low); ?>" data-sottosottocategoria="<?php echo htmlspecialchars($subsubcat_low); ?>">
                <div class="card-inner">
                    <div class="card-front">
                        <?php if ($is_new): ?>
                        <span class="new-badge">Nuovo</span>
                        <?php endif; ?>
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
          <div class="cart-header">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.53h9.72a2 2 0 0 0 2-1.53l1.66-7.43H5.12"/></svg>
            Carrello
            <span class="cart-badge-count" id="cart-badge-count">0</span>
          </div>
          <ul id="cart-items">
            <div class="empty-cart-message" id="empty-cart-message">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.53h9.72a2 2 0 0 0 2-1.53l1.66-7.43H5.12"/></svg>
                <p>Il tuo carrello è vuoto</p>
            </div>
          </ul>
          <div class="cart-summary">
              <span class="cart-total-label">Totale da pagare</span>
              <div class="cart-total" id="cart-total">€0,00</div>
              <div class="cart-actions">
                <button class="go-cart-btn" onclick="vaiAlCarrello()">Vai al carrello</button>
                <button class="empty-cart-btn" onclick="mostraConfermaSvuotaCarrello()">Svuota</button>
              </div>
          </div>
        </aside>
      </div>
    </section>
  </div>

  <!-- Toast fisso sullo schermo -->
  <div id="toast-fixed"></div>

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
    const toastFixed = document.getElementById('toast-fixed');

    // Toast fisso sullo schermo (slide da destra)
    function showToast(msg, type = 'success') {
      toastFixed.className = '';
      toastFixed.classList.add(type);
      toastFixed.textContent = msg;
      toastFixed.classList.add('show');
      setTimeout(() => {
        toastFixed.classList.remove('show');
      }, 3000);
    }

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

    function aggiornaAnteprima(isNewItem = false) {
        cartItemsList.innerHTML = '';
        let tot = 0;
        let itemCount = 0;
        const cartEntries = Object.entries(cart);
        const hasItems = cartEntries.length > 0;
        
        // Aggiorna badge contatore
        cartEntries.forEach(([key, it]) => { itemCount += it.qty; });
        document.getElementById('cart-badge-count').textContent = itemCount;
        
        cartPreview.classList.toggle('visible', hasItems);
        emptyCartMessage.classList.toggle('visible', !hasItems);
        document.querySelector('.cart-summary').style.display = hasItems ? 'block' : 'none';
        
        // Animazione pulse quando si aggiunge
        if (isNewItem && hasItems) {
            cartPreview.classList.remove('pulse');
            void cartPreview.offsetWidth; // Force reflow
            cartPreview.classList.add('pulse');
        }
        
        if (hasItems) {
            cartEntries.forEach(([key, it], index) => {
                const subtotal = it.qty * it.price;
                tot += subtotal;
                const li = document.createElement('li');
                if (isNewItem && index === cartEntries.length - 1) li.classList.add('new-item');
                li.innerHTML = `
                  <img class="item-img" src="${it.img}" alt="${it.name}" />
                  <div class="item-info">
                    <span class="name">${it.name}</span>
                    <span class="details">${it.qty} × ${formatPrice(it.price)}</span>
                    <span class="subtotal">${formatPrice(subtotal)}</span>
                  </div>
                  <div class="cart-item-qty-controls">
                    <button class="cart-qty-btn" onclick="modificaQuantitaCart('${key}', -1)"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
                    <input type="number" class="cart-qty-input" value="${it.qty}" min="1" max="${it.giacenza}" onchange="modificaQuantitaCart('${key}', parseInt(this.value) - ${it.qty})" />
                    <button class="cart-qty-btn" onclick="modificaQuantitaCart('${key}', 1)"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
                  </div>
                  <button class="remove-btn" onclick="mostraConfermaRimozione('${key}', '${it.name}')" title="Rimuovi"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>`;
                cartItemsList.appendChild(li);
            });
        } else { cartItemsList.appendChild(emptyCartMessage); }
        cartTotalElem.textContent = formatPrice(tot);
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

        const isNew = !cart[key];
        if (cart[key]) { cart[key].qty += qty; showToast(`Quantità di "${name}" aggiornata.`, "success"); } 
        else { cart[key] = { id, name, price, qty, img, giacenza: stock }; showToast(`"${name}" aggiunto al carrello.`, "success"); }
        aggiornaAnteprima(isNew);
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

