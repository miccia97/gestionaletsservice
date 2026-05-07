<?php
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!file_exists('db.php')) {
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: Il file db.php non è stato trovato!</div>";
    exit;
}
require_once 'db.php';

if (isset($db_connection_error) && $db_connection_error !== null) {
    $_SESSION['message'] = 'Errore critico: La connessione al database non è stata stabilita correttamente! ' . htmlspecialchars($db_connection_error, ENT_QUOTES, 'UTF-8');
    $_SESSION['isError'] = true;
}

$message = '';
if (isset($_SESSION['message'])) {
    $sessionMessage = $_SESSION['message'];
    $sessionIsError = $_SESSION['isError'] ?? false;
    $message = "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('" . addslashes($sessionMessage) . "', " . ($sessionIsError ? 'true' : 'false') . "); });</script>";
    unset($_SESSION['message']);
    unset($_SESSION['isError']);
}

// --- LOGICA PER RECUPERARE I DATI PER I REPORT ---
$current_stock_products = [];
$low_stock_products = [];
$above_stock_products = [];
$purchase_summary_products = [];
$total_stock_value = 0;
$recent_movements = [];
$most_purchased_products = [];
$purchase_trends = [];
$inventory_turnover = 0;
$total_purchased_quantity_all_time = 0;
$total_current_stock_quantity = 0;

// --- GESTIONE FILTRI GLOBALI ---
$start_date_global = $_GET['start_date_global'] ?? date('Y-m-01');
$end_date_global = $_GET['end_date_global'] ?? date('Y-m-d');
$min_stock_threshold = isset($_GET['min_stock_threshold']) && is_numeric($_GET['min_stock_threshold']) ? (int)$_GET['min_stock_threshold'] : 10;
$max_stock_threshold = isset($_GET['max_stock_threshold']) && is_numeric($_GET['max_stock_threshold']) ? (int)$_GET['max_stock_threshold'] : 100;

try {
    // Report 1: Giacenze Attuali
    $stmt_current_stock = $conn->prepare("SELECT p.nome, c.nome AS categoria_nome, p.quantita, p.barcode FROM prodotti p LEFT JOIN categorie c ON p.categoria = c.nome ORDER BY p.nome ASC");
    $stmt_current_stock->execute();
    $result_current_stock = $stmt_current_stock->get_result();
    $current_stock_products = $result_current_stock->fetch_all(MYSQLI_ASSOC);
    $stmt_current_stock->close();

    // Report 2: Prodotti Sotto Scorta
    $stmt_low_stock = $conn->prepare("SELECT p.nome, c.nome AS categoria_nome, p.quantita, p.barcode FROM prodotti p LEFT JOIN categorie c ON p.categoria = c.nome WHERE p.quantita <= ? ORDER BY p.quantita ASC");
    $stmt_low_stock->bind_param('i', $min_stock_threshold);
    $stmt_low_stock->execute();
    $result_low_stock = $stmt_low_stock->get_result();
    $low_stock_products = $result_low_stock->fetch_all(MYSQLI_ASSOC);
    $stmt_low_stock->close();
    
    // Report 3: Prodotti Sopra Scorta
    $stmt_above_stock = $conn->prepare("SELECT p.nome, c.nome AS categoria_nome, p.quantita, p.barcode FROM prodotti p LEFT JOIN categorie c ON p.categoria = c.nome WHERE p.quantita >= ? ORDER BY p.quantita DESC");
    $stmt_above_stock->bind_param('i', $max_stock_threshold);
    $stmt_above_stock->execute();
    $result_above_stock = $stmt_above_stock->get_result();
    $above_stock_products = $result_above_stock->fetch_all(MYSQLI_ASSOC);
    $stmt_above_stock->close();

    // Report 4: Riepilogo Acquisti Prodotti con filtro data globale
    $sql_purchase_summary = "SELECT p.nome, c.nome AS categoria_nome, SUM(df.quantita) AS quantita_acquistata, SUM(df.quantita * df.prezzo_unitario_netto) AS valore_acquisto_netto 
                             FROM dettagli_fattura df 
                             JOIN prodotti p ON df.prodotto_id = p.id 
                             LEFT JOIN categorie c ON p.categoria = c.nome
                             JOIN fatture f ON df.fattura_id = f.id
                             WHERE f.data_fattura BETWEEN ? AND ?
                             GROUP BY p.id, p.nome, c.nome ORDER BY quantita_acquistata DESC";
    $stmt_purchase_summary = $conn->prepare($sql_purchase_summary);
    $stmt_purchase_summary->bind_param('ss', $start_date_global, $end_date_global);
    $stmt_purchase_summary->execute();
    $result_purchase_summary = $stmt_purchase_summary->get_result();
    $purchase_summary_products = $result_purchase_summary->fetch_all(MYSQLI_ASSOC);
    $stmt_purchase_summary->close();

    // Report 5: Valore Totale Magazzino
    $stmt_total_value = $conn->prepare("SELECT SUM(p.quantita * p.prezzo_acquisto) AS total_stock_value FROM prodotti p");
    $stmt_total_value->execute();
    $total_stock_value = $stmt_total_value->get_result()->fetch_assoc()['total_stock_value'] ?? 0;
    $stmt_total_value->close();

    // Report 6: Storico Movimenti Recenti (ultimi 10 acquisti)
    $stmt_recent_movements = $conn->prepare("SELECT df.data_creazione, p.nome, df.quantita, df.prezzo_unitario_netto, 'Acquisto' as tipo_movimento, f.numero_fattura FROM dettagli_fattura df JOIN prodotti p ON df.prodotto_id = p.id JOIN fatture f ON df.fattura_id = f.id ORDER BY df.data_creazione DESC LIMIT 10");
    $stmt_recent_movements->execute();
    $recent_movements = $stmt_recent_movements->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_recent_movements->close();

    // Report 7: Prodotti Più Acquistati (Top 5 nel periodo globale)
    $stmt_most_purchased = $conn->prepare("SELECT p.nome, c.nome AS categoria_nome, SUM(df.quantita) AS total_quantity_purchased FROM dettagli_fattura df JOIN prodotti p ON df.prodotto_id = p.id LEFT JOIN categorie c ON p.categoria = c.nome JOIN fatture f ON df.fattura_id = f.id WHERE f.data_fattura BETWEEN ? AND ? GROUP BY p.id, p.nome, c.nome ORDER BY total_quantity_purchased DESC LIMIT 5");
    $stmt_most_purchased->bind_param('ss', $start_date_global, $end_date_global);
    $stmt_most_purchased->execute();
    $most_purchased_products = $stmt_most_purchased->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_most_purchased->close();

    // Report 8: Trend Acquisti per Mese (nel periodo globale)
    $sql_purchase_trends = "SELECT DATE_FORMAT(f.data_fattura, '%Y-%m') AS period, SUM(df.quantita) AS total_quantity, SUM(df.quantita * df.prezzo_unitario_netto) AS total_value_net FROM dettagli_fattura df JOIN fatture f ON df.fattura_id = f.id WHERE f.data_fattura BETWEEN ? AND ? GROUP BY period ORDER BY period ASC";
    $stmt_purchase_trends = $conn->prepare($sql_purchase_trends);
    $stmt_purchase_trends->bind_param('ss', $start_date_global, $end_date_global);
    $stmt_purchase_trends->execute();
    $purchase_trends = $stmt_purchase_trends->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_purchase_trends->close();

    // Report 9: Rotazione Magazzino
    $stmt_turnover_data = $conn->prepare("SELECT (SELECT SUM(quantita) FROM dettagli_fattura) as total_purchased, (SELECT SUM(quantita) FROM prodotti) as current_stock");
    $stmt_turnover_data->execute();
    $turnover_data = $stmt_turnover_data->get_result()->fetch_assoc();
    $total_purchased_quantity_all_time = $turnover_data['total_purchased'] ?? 0;
    $total_current_stock_quantity = $turnover_data['current_stock'] ?? 0;
    $inventory_turnover = ($total_current_stock_quantity > 0) ? ($total_purchased_quantity_all_time / $total_current_stock_quantity) : 0;
    $stmt_turnover_data->close();

} catch (mysqli_sql_exception $e) {
    error_log("Errore nel caricamento dei report (SQL): " . $e->getMessage());
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Errore database: " . addslashes($e->getMessage()) . "', true); });</script>";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analisi Magazzino | TS Service</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=2">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
:root {
    --primary: #22c55e;
    --primary-dark: #16a34a;
    --primary-light: #dcfce7;
    --primary-glow: rgba(34, 197, 94, 0.4);
    --secondary: #3b82f6;
    --secondary-dark: #2563eb;
    --secondary-light: #dbeafe;
    --purple: #8b5cf6;
    --purple-light: #ede9fe;
    --success: #10b981;
    --warning: #f59e0b;
    --warning-light: #fef3c7;
    --danger: #ef4444;
    --danger-light: #fee2e2;
    --info: #06b6d4;
    --bg-page: #f8fafc;
    --bg-card: #ffffff;
    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;
    --border-color: #e2e8f0;
    --border-light: #f1f5f9;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-md: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    --shadow-glow: 0 0 40px rgba(34, 197, 94, 0.15);
    --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition: 200ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-spring: 500ms cubic-bezier(0.34, 1.56, 0.64, 1);
    --radius-sm: 0.375rem;
    --radius: 0.5rem;
    --radius-md: 0.75rem;
    --radius-lg: 1rem;
    --radius-xl: 1.5rem;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, var(--bg-page) 0%, #e2e8f0 100%);
    min-height: 100vh; color: var(--text-primary);
    padding-top: 80px; line-height: 1.6; overflow-x: hidden;
}

/* PARTICLES */
.particles-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; overflow: hidden; }
.particle { position: absolute; border-radius: 50%; opacity: 0.15; animation: floatParticle 22s infinite ease-in-out; }
.particle:nth-child(1) { width: 320px; height: 320px; background: var(--primary); top: -120px; left: -80px; animation-delay: 0s; }
.particle:nth-child(2) { width: 220px; height: 220px; background: var(--secondary); top: 50%; right: -60px; animation-delay: -6s; }
.particle:nth-child(3) { width: 180px; height: 180px; background: var(--purple); bottom: 8%; left: 25%; animation-delay: -12s; }
.particle:nth-child(4) { width: 120px; height: 120px; background: var(--warning); top: 25%; left: 55%; animation-delay: -17s; }
@keyframes floatParticle {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(30px, -30px) scale(1.05); }
    50% { transform: translate(-20px, 20px) scale(0.95); }
    75% { transform: translate(15px, 15px) scale(1.02); }
}

/* TOAST */
.toast-container {
    position: fixed; top: 100px; right: 24px; z-index: 10000;
    display: flex; flex-direction: column; gap: 12px; pointer-events: none;
}
.toast {
    min-width: 320px; padding: 16px 20px; border-radius: var(--radius-lg);
    color: #fff; display: flex; align-items: center; gap: 12px;
    pointer-events: auto; backdrop-filter: blur(12px);
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    opacity: 0; transform: translateX(100px);
    animation: toastIn 0.4s forwards, toastOut 0.4s 4.5s forwards;
    font-weight: 500; font-size: 0.9rem;
}
.toast.success { background: linear-gradient(135deg, #059669, #10b981); }
.toast.error { background: linear-gradient(135deg, #dc2626, #ef4444); }
@keyframes toastIn { to { opacity: 1; transform: translateX(0); } }
@keyframes toastOut { from { opacity: 1; } to { opacity: 0; transform: translateX(100px); } }

/* LAYOUT */
.main-container {
    max-width: 1500px; margin: 0 auto; padding: 24px 32px 60px;
    position: relative; z-index: 1;
}

/* PAGE HEADER */
.page-header {
    text-align: center; margin-bottom: 32px;
    animation: fadeInUp 0.6s ease-out;
}
.page-header h1 {
    font-size: 2.75rem; font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; margin: 0 0 8px; letter-spacing: -0.02em;
}
.page-header p { color: var(--text-secondary); font-size: 1.1rem; margin: 0; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

/* FILTER CARD */
.filter-card {
    background: var(--bg-card); border-radius: var(--radius-xl); padding: 24px 28px;
    box-shadow: var(--shadow); border: 1px solid var(--border-color);
    margin-bottom: 28px;
    animation: fadeInUp 0.6s ease-out 0.1s both;
}
.filter-grid {
    display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;
}
.filter-field { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 160px; }
.filter-field label {
    font-size: 0.8rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.5px; color: var(--text-secondary);
}
.filter-input {
    padding: 12px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-md);
    font-size: 0.95rem; font-family: 'Inter', sans-serif; color: var(--text-primary);
    background: var(--bg-page); outline: none; transition: all var(--transition); width: 100%;
}
.filter-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); background: var(--bg-card); }
.quick-date-group { display: flex; gap: 6px; align-items: flex-end; }
.quick-btn {
    padding: 12px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-md);
    background: var(--bg-card); cursor: pointer; font-size: 0.88rem;
    font-weight: 600; font-family: 'Inter', sans-serif; color: var(--text-secondary);
    transition: all var(--transition); white-space: nowrap;
}
.quick-btn:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
.filter-actions { display: flex; gap: 10px; align-items: flex-end; }

/* BUTTONS */
.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 24px; border: none; border-radius: var(--radius-md);
    font-size: 0.95rem; font-weight: 600; font-family: 'Inter', sans-serif;
    cursor: pointer; transition: all var(--transition); white-space: nowrap;
    text-decoration: none; text-align: center;
    position: relative; overflow: hidden;
}
.btn::after {
    content: ''; position: absolute; width: 100%; height: 100%; top: 0; left: 0;
    background: linear-gradient(rgba(255,255,255,0.2), rgba(255,255,255,0)); opacity: 0; transition: opacity 0.2s;
}
.btn:hover::after { opacity: 1; }
.btn i { font-size: 0.85rem; }
.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff; box-shadow: 0 4px 14px var(--primary-glow);
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px var(--primary-glow); }
.btn-secondary {
    background: var(--border-light); color: var(--text-secondary);
}
.btn-secondary:hover { background: var(--border-color); color: var(--text-primary); }

/* STAT CARDS */
.stats-grid {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 20px; margin-bottom: 32px;
}
.stat-card {
    background: var(--bg-card); border-radius: var(--radius-xl); padding: 24px;
    border: 1px solid var(--border-color); position: relative; overflow: hidden;
    display: flex; align-items: center; gap: 20px;
    cursor: pointer; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: var(--shadow);
    opacity: 0; transform: translateY(30px);
    animation: cardSlideIn 0.5s ease-out forwards;
}
.stat-card:nth-child(1) { animation-delay: 0.15s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.25s; }
.stat-card:nth-child(4) { animation-delay: 0.3s; }
@keyframes cardSlideIn { to { opacity: 1; transform: translateY(0); } }
.stat-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--card-accent), var(--card-accent-light, var(--card-accent)));
    opacity: 0; transition: opacity 0.3s ease;
}
.stat-card:hover::before { opacity: 1; }
.stat-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg), 0 0 0 1px var(--card-accent);
}
.stat-card.card-green { --card-accent: var(--primary); }
.stat-card.card-red { --card-accent: var(--danger); }
.stat-card.card-teal { --card-accent: #14b8a6; }
.stat-card.card-purple { --card-accent: var(--purple); }
.stat-icon-wrap {
    width: 56px; height: 56px; border-radius: var(--radius-lg);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    transition: transform 0.3s ease;
}
.stat-card:hover .stat-icon-wrap { transform: scale(1.1) rotate(-5deg); }
.stat-card.card-green .stat-icon-wrap { background: var(--primary-light); color: var(--primary-dark); }
.stat-card.card-red .stat-icon-wrap { background: var(--danger-light); color: var(--danger); }
.stat-card.card-teal .stat-icon-wrap { background: #ccfbf1; color: #0d9488; }
.stat-card.card-purple .stat-icon-wrap { background: var(--purple-light); color: var(--purple); }
.stat-icon-wrap i { font-size: 1.3rem; }
.stat-label {
    font-size: 0.85rem; font-weight: 500; color: var(--text-secondary);
    margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;
}
.stat-value { font-size: 1.75rem; font-weight: 700; line-height: 1.1; }
.stat-card.card-green .stat-value { color: var(--primary-dark); }
.stat-card.card-red .stat-value { color: var(--danger); }
.stat-card.card-teal .stat-value { color: #0d9488; }
.stat-card.card-purple .stat-value { color: var(--purple); }

/* DASHBOARD GRID */
.dashboard-grid {
    display: grid; grid-template-columns: 5fr 3fr;
    gap: 24px; margin-bottom: 32px;
    animation: fadeInUp 0.6s ease-out 0.35s both;
}
.charts-column { display: flex; flex-direction: column; gap: 24px; }
.sidebar-column { display: flex; flex-direction: column; gap: 24px; }

/* CARDS */
.card {
    background: var(--bg-card); border-radius: var(--radius-xl);
    box-shadow: var(--shadow); border: 1px solid var(--border-color);
    overflow: hidden; transition: all var(--transition);
}
.card:hover { box-shadow: var(--shadow-md); }
.card-header {
    padding: 20px 24px; border-bottom: 1px solid var(--border-light);
    display: flex; align-items: center; justify-content: space-between;
}
.card-title {
    font-size: 1.1rem; font-weight: 700; color: var(--text-primary);
    display: flex; align-items: center; gap: 10px;
}
.card-title-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; font-size: 0.95rem;
}
.card-title-icon.green { background: var(--primary-light); color: var(--primary-dark); }
.card-title-icon.blue { background: var(--secondary-light); color: var(--secondary); }
.card-title-icon.purple { background: var(--purple-light); color: var(--purple); }
.card-title-icon.orange { background: #fff7ed; color: #ea580c; }
.card-title-icon.red { background: var(--danger-light); color: var(--danger); }
.card-title-icon.teal { background: #ccfbf1; color: #0d9488; }
.card-body { padding: 24px; }
.card-body-compact { padding: 16px 24px; }
.csv-btn {
    width: 36px; height: 36px; border-radius: 10px; border: 2px solid var(--border-color);
    background: var(--bg-card); cursor: pointer; font-size: 0.85rem;
    color: var(--text-muted); display: flex; align-items: center; justify-content: center;
    transition: all var(--transition);
}
.csv-btn:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }

/* ROTAZIONE INFO */
.turnover-card .turnover-value {
    font-size: 3.5rem; font-weight: 900; color: var(--purple);
    text-align: center; letter-spacing: -2px; margin: 16px 0 8px;
}
.turnover-card .turnover-label {
    text-align: center; font-size: 0.88rem; color: var(--text-secondary); line-height: 1.6;
}

/* MOVEMENTS LIST */
.movement-item {
    padding: 14px 0; border-bottom: 1px solid var(--border-light);
    display: flex; align-items: center; gap: 14px;
}
.movement-item:last-child { border-bottom: none; }
.movement-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
    background: var(--primary); box-shadow: 0 0 8px var(--primary-glow);
}
.movement-info { flex: 1; min-width: 0; }
.movement-name { font-weight: 600; font-size: 0.88rem; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.movement-meta { font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }

/* TABLES */
.table-card { overflow: hidden; }
.table-scroll { overflow-x: auto; max-height: 400px; overflow-y: auto; }
.premium-table { width: 100%; border-collapse: collapse; }
.premium-table thead { position: sticky; top: 0; z-index: 2; }
.premium-table th {
    padding: 14px 20px; text-align: left; font-size: 0.75rem;
    font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px;
    color: var(--text-secondary); background: linear-gradient(180deg, #f8fafc, #f1f5f9);
    border-bottom: 2px solid var(--border-color);
}
.premium-table td {
    padding: 14px 20px; font-size: 0.9rem; border-bottom: 1px solid var(--border-light);
    color: var(--text-primary);
}
.premium-table tbody tr { transition: background var(--transition-fast); }
.premium-table tbody tr:hover { background: rgba(34, 197, 94, 0.04); }
.qty-badge {
    display: inline-flex; align-items: center; padding: 4px 12px;
    border-radius: 20px; font-weight: 700; font-size: 0.82rem;
}
.qty-badge.low { background: var(--danger-light); color: var(--danger); }
.qty-badge.high { background: var(--primary-light); color: var(--primary-dark); }
.category-pill {
    display: inline-block; padding: 4px 12px; border-radius: 20px;
    background: var(--border-light); font-size: 0.82rem;
    font-weight: 500; color: var(--text-secondary);
}
.empty-table-msg {
    text-align: center; padding: 40px; color: var(--text-muted); font-size: 0.95rem;
}
.empty-table-msg i { font-size: 2rem; display: block; margin-bottom: 12px; }

/* DETAIL SECTION */
.detail-tables-section { animation: fadeInUp 0.6s ease-out 0.45s both; }

/* HIDDEN TABLES */
.hidden-export { display: none; }

/* RESPONSIVE */
@media (max-width: 1200px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .dashboard-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .main-container { padding: 16px 16px 40px; }
    .page-header h1 { font-size: 1.8rem; }
    .stats-grid { grid-template-columns: 1fr; }
    .filter-grid { flex-direction: column; }
    .filter-field { min-width: 100%; }
    .stat-value { font-size: 1.4rem; }
    .quick-date-group { flex-wrap: wrap; }
}
@media print {
    .particles-container, .toast-container, .filter-card { display: none !important; }
    body { padding-top: 0; background: #fff; }
    .card { box-shadow: none; border: 1px solid #ddd; }
    .stat-card:hover, .card:hover { transform: none; box-shadow: none; }
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

    <div class="toast-container" id="toast-container"></div>

    <main class="main-container">
        <?php echo $message; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Dashboard Analisi Magazzino</h1>
            <p>Reportistica avanzata, analisi trend e monitoraggio scorte in tempo reale</p>
        </div>

        <!-- Filter Card -->
        <div class="filter-card">
            <form id="globalFilterForm" action="reportistica.php" method="GET">
                <div class="filter-grid">
                    <div class="filter-field">
                        <label for="start_date_global">Data Inizio</label>
                        <input type="date" id="start_date_global" name="start_date_global" value="<?php echo htmlspecialchars($start_date_global, ENT_QUOTES, 'UTF-8'); ?>" class="filter-input">
                    </div>
                    <div class="filter-field">
                        <label for="end_date_global">Data Fine</label>
                        <input type="date" id="end_date_global" name="end_date_global" value="<?php echo htmlspecialchars($end_date_global, ENT_QUOTES, 'UTF-8'); ?>" class="filter-input">
                    </div>
                    <div class="filter-field">
                        <label>&nbsp;</label>
                        <div class="quick-date-group">
                            <button type="button" onclick="setQuickDate('last7days')" class="quick-btn">7 Giorni</button>
                            <button type="button" onclick="setQuickDate('last30days')" class="quick-btn">30 Giorni</button>
                            <button type="button" onclick="setQuickDate('this_month')" class="quick-btn">Mese</button>
                        </div>
                    </div>
                    <div class="filter-field">
                        <label for="min_stock_threshold">Soglia Min Scorta</label>
                        <input type="number" id="min_stock_threshold" name="min_stock_threshold" value="<?php echo htmlspecialchars($min_stock_threshold, ENT_QUOTES, 'UTF-8'); ?>" class="filter-input">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Applica</button>
                        <a href="reportistica.php" class="btn btn-secondary"><i class="fas fa-rotate-left"></i> Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card card-green" onclick="scrollToSection('totalValueCard')">
                <div class="stat-icon-wrap"><i class="fas fa-coins"></i></div>
                <div>
                    <div class="stat-label">Valore Totale</div>
                    <div class="stat-value" id="statTotalValue"><?php echo number_format($total_stock_value, 2, ',', '.') . ' &euro;'; ?></div>
                </div>
            </div>
            <div class="stat-card card-red" onclick="scrollToSection('lowStockCard')">
                <div class="stat-icon-wrap"><i class="fas fa-arrow-trend-down"></i></div>
                <div>
                    <div class="stat-label">Sotto Scorta</div>
                    <div class="stat-value" id="statLowStock"><?php echo count($low_stock_products); ?></div>
                </div>
            </div>
            <div class="stat-card card-teal" onclick="scrollToSection('aboveStockCard')">
                <div class="stat-icon-wrap"><i class="fas fa-arrow-trend-up"></i></div>
                <div>
                    <div class="stat-label">Sopra Scorta</div>
                    <div class="stat-value" id="statAboveStock"><?php echo count($above_stock_products); ?></div>
                </div>
            </div>
            <div class="stat-card card-purple" onclick="scrollToSection('turnoverCard')">
                <div class="stat-icon-wrap"><i class="fas fa-rotate"></i></div>
                <div>
                    <div class="stat-label">Rotazione</div>
                    <div class="stat-value" id="statTurnover"><?php echo number_format($inventory_turnover, 2, ',', '.'); ?>x</div>
                </div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Left: Charts -->
            <div class="charts-column">
                <!-- Trend Acquisti -->
                <div class="card" id="purchaseTrendCard">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-title-icon blue"><i class="fas fa-chart-area"></i></div>
                            Trend Acquisti
                        </div>
                        <button onclick="exportTableToCSV('purchaseTrendTable', 'trend-acquisti.csv')" class="csv-btn" title="Esporta CSV"><i class="fas fa-download"></i></button>
                    </div>
                    <div class="card-body">
                        <canvas id="purchaseTrendChart"></canvas>
                    </div>
                </div>

                <!-- Prodotti Più Acquistati -->
                <div class="card" id="topProductsCard">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-title-icon green"><i class="fas fa-ranking-star"></i></div>
                            Prodotti Più Acquistati
                        </div>
                        <button onclick="exportTableToCSV('mostPurchasedTable', 'prodotti-piu-acquistati.csv')" class="csv-btn" title="Esporta CSV"><i class="fas fa-download"></i></button>
                    </div>
                    <div class="card-body">
                        <canvas id="topProductsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Right: Sidebar -->
            <div class="sidebar-column">
                <!-- Rotazione -->
                <div class="card turnover-card" id="turnoverCard">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-title-icon purple"><i class="fas fa-rotate"></i></div>
                            Rotazione Magazzino
                        </div>
                    </div>
                    <div class="card-body" style="text-align: center;">
                        <div class="turnover-value"><?php echo number_format($inventory_turnover, 2, ',', '.'); ?>x</div>
                        <p class="turnover-label">Indica quante volte il magazzino si &egrave; &ldquo;rinnovato&rdquo; in base al rapporto tra acquistato totale e giacenza attuale.</p>
                    </div>
                </div>

                <!-- Movimenti Recenti -->
                <div class="card" id="recentMovementsCard">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-title-icon orange"><i class="fas fa-clock-rotate-left"></i></div>
                            Movimenti Recenti
                        </div>
                        <button onclick="exportTableToCSV('recentMovementsTable', 'movimenti-recenti.csv')" class="csv-btn" title="Esporta CSV"><i class="fas fa-download"></i></button>
                    </div>
                    <div class="card-body-compact">
                        <?php if (empty($recent_movements)): ?>
                            <div class="empty-table-msg">
                                <i class="fas fa-inbox"></i>
                                Nessun movimento recente
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_movements as $mov): ?>
                            <div class="movement-item">
                                <div class="movement-dot"></div>
                                <div class="movement-info">
                                    <div class="movement-name"><?php echo htmlspecialchars($mov['nome']); ?></div>
                                    <div class="movement-meta">Q.t&agrave;: <?php echo number_format($mov['quantita'], 3, ',', '.'); ?> &mdash; <?php echo date("d/m/Y", strtotime($mov['data_creazione'])); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Tables -->
        <div class="detail-tables-section" style="display: flex; flex-direction: column; gap: 28px;">

            <!-- Sotto Scorta -->
            <div class="card table-card" id="lowStockCard">
                <div class="card-header">
                    <div class="card-title">
                        <div class="card-title-icon red"><i class="fas fa-arrow-trend-down"></i></div>
                        Dettaglio Prodotti Sotto Scorta (&le; <?php echo $min_stock_threshold; ?>)
                    </div>
                    <button onclick="exportTableToCSV('lowStockTable', 'prodotti-sotto-scorta.csv')" class="csv-btn" title="Esporta CSV"><i class="fas fa-download"></i></button>
                </div>
                <div class="table-scroll">
                    <?php if (empty($low_stock_products)): ?>
                        <div class="empty-table-msg">
                            <i class="fas fa-check-circle" style="color: var(--primary);"></i>
                            Nessun prodotto sotto scorta
                        </div>
                    <?php else: ?>
                    <table class="premium-table" id="lowStockTable">
                        <thead>
                            <tr><th>Prodotto</th><th>Quantit&agrave;</th><th>Categoria</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach($low_stock_products as $p): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($p['nome']); ?></td>
                            <td><span class="qty-badge low"><?php echo htmlspecialchars($p['quantita']); ?></span></td>
                            <td><span class="category-pill"><?php echo htmlspecialchars($p['categoria_nome'] ?? 'N/A'); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sopra Scorta -->
            <div class="card table-card" id="aboveStockCard">
                <div class="card-header">
                    <div class="card-title">
                        <div class="card-title-icon green"><i class="fas fa-arrow-trend-up"></i></div>
                        Dettaglio Prodotti Sopra Scorta (&ge; <?php echo $max_stock_threshold; ?>)
                    </div>
                    <button onclick="exportTableToCSV('aboveStockTable', 'prodotti-sopra-scorta.csv')" class="csv-btn" title="Esporta CSV"><i class="fas fa-download"></i></button>
                </div>
                <div class="table-scroll">
                    <?php if (empty($above_stock_products)): ?>
                        <div class="empty-table-msg">
                            <i class="fas fa-box-open" style="color: var(--text-muted);"></i>
                            Nessun prodotto sopra la soglia
                        </div>
                    <?php else: ?>
                    <table class="premium-table" id="aboveStockTable">
                        <thead>
                            <tr><th>Prodotto</th><th>Quantit&agrave;</th><th>Categoria</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach($above_stock_products as $p): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($p['nome']); ?></td>
                            <td><span class="qty-badge high"><?php echo htmlspecialchars($p['quantita']); ?></span></td>
                            <td><span class="category-pill"><?php echo htmlspecialchars($p['categoria_nome'] ?? 'N/A'); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- Hidden tables for CSV export -->
    <div class="hidden-export">
        <table id="purchaseTrendTable">
            <thead><tr><th>Periodo</th><th>Quantità</th><th>Valore</th></tr></thead>
            <tbody>
            <?php foreach($purchase_trends as $d): ?>
            <tr><td><?php echo $d['period']; ?></td><td><?php echo $d['total_quantity']; ?></td><td><?php echo $d['total_value_net']; ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <table id="mostPurchasedTable">
            <thead><tr><th>Prodotto</th><th>Categoria</th><th>Quantità</th></tr></thead>
            <tbody>
            <?php foreach($most_purchased_products as $p): ?>
            <tr><td><?php echo htmlspecialchars($p['nome']); ?></td><td><?php echo htmlspecialchars($p['categoria_nome'] ?? 'N/A'); ?></td><td><?php echo $p['total_quantity_purchased']; ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <table id="recentMovementsTable">
            <thead><tr><th>Data</th><th>Prodotto</th><th>Quantità</th><th>Tipo</th><th>Fattura</th></tr></thead>
            <tbody>
            <?php foreach($recent_movements as $m): ?>
            <tr><td><?php echo $m['data_creazione']; ?></td><td><?php echo htmlspecialchars($m['nome']); ?></td><td><?php echo $m['quantita']; ?></td><td><?php echo $m['tipo_movimento']; ?></td><td><?php echo htmlspecialchars($m['numero_fattura']); ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // === Chart.js Global Defaults ===
        Chart.defaults.font.family = 'Inter';
        Chart.defaults.color = '#64748b';

        // === Data from PHP ===
        const purchaseTrendData = <?php echo json_encode($purchase_trends); ?>;
        const topProductsData = <?php echo json_encode($most_purchased_products); ?>;

        // === Trend Acquisti Chart ===
        const trendCtx = document.getElementById('purchaseTrendChart').getContext('2d');
        const trendGradient = trendCtx.createLinearGradient(0, 0, 0, 400);
        trendGradient.addColorStop(0, 'rgba(34, 197, 94, 0.25)');
        trendGradient.addColorStop(1, 'rgba(34, 197, 94, 0.02)');

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: purchaseTrendData.map(d => d.period),
                datasets: [{
                    label: 'Valore Acquistato (\u20ac)',
                    data: purchaseTrendData.map(d => d.total_value_net),
                    borderColor: '#22c55e',
                    backgroundColor: trendGradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#22c55e',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { padding: 20, font: { size: 12, weight: '600' }, usePointStyle: true } },
                    tooltip: {
                        backgroundColor: '#1e293b', titleFont: { size: 13, weight: '700' },
                        bodyFont: { size: 12 }, padding: 14, cornerRadius: 10,
                        displayColors: false
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11, weight: '500' } } },
                    y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 11, weight: '500' } } }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });

        // === Top Products Chart ===
        const topCtx = document.getElementById('topProductsChart').getContext('2d');
        new Chart(topCtx, {
            type: 'bar',
            data: {
                labels: topProductsData.map(p => p.nome),
                datasets: [{
                    label: 'Quantit\u00e0 Acquistata',
                    data: topProductsData.map(p => p.total_quantity_purchased),
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(6, 182, 212, 0.8)'
                    ],
                    borderRadius: 8,
                    borderSkipped: false,
                    barThickness: 28,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b', titleFont: { size: 13, weight: '700' },
                        bodyFont: { size: 12 }, padding: 14, cornerRadius: 10
                    }
                },
                scales: {
                    x: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 11, weight: '500' } } },
                    y: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } }
                }
            }
        });

        // === Animations handled by CSS cardSlideIn ===
    });

    // === Smooth Scroll ===
    function scrollToSection(cardId) {
        document.getElementById(cardId).scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    // === Quick Date Filters ===
    function setQuickDate(period) {
        const endDate = new Date();
        let startDate = new Date();
        
        if (period === 'last7days') startDate.setDate(endDate.getDate() - 7);
        if (period === 'last30days') startDate.setDate(endDate.getDate() - 30);
        if (period === 'this_month') startDate.setDate(1);

        document.getElementById('start_date_global').value = startDate.toISOString().split('T')[0];
        document.getElementById('end_date_global').value = endDate.toISOString().split('T')[0];
        
        document.getElementById('globalFilterForm').submit();
    }

    // === CSV Export ===
    function exportTableToCSV(tableId, filename) {
        let csv = [];
        const rows = document.querySelectorAll(`#${tableId} tr`);
        
        for (const row of rows) {
            let cols = [];
            const cells = row.querySelectorAll("th, td");
            for (const cell of cells) {
                cols.push(`"${cell.innerText.replace(/"/g, '""')}"`);
            }
            csv.push(cols.join(','));
        }

        const csvFile = new Blob([csv.join('\n')], { type: "text/csv" });
        const downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }

    // === Toast / Message ===
    function showMessage(msg, isError = false) {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        const iconClass = isError ? 'fa-exclamation-circle' : 'fa-check-circle';
        toast.className = `toast ${isError ? 'error' : 'success'}`;
        toast.innerHTML = `<i class="fas ${iconClass}"></i><span>${msg}</span>`;
        container.appendChild(toast);
        toast.addEventListener('animationend', (e) => { if (e.animationName === 'toastOut') toast.remove(); });
    }
    </script>
</body>
</html>
