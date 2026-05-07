<?php
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!file_exists('db.php')) {
    echo "<div style='text-align:center;padding:2rem;color:#ef4444;font-family:Inter,sans-serif;'>Errore critico: Il file db.php non &egrave; stato trovato!</div>";
    exit;
}
require_once 'db.php';

if (!isset($conn) || $conn === null) {
    $db_error_message = isset($db_connection_error) ? $db_connection_error : 'Connessione al database non stabilita.';
    echo "<div style='text-align:center;padding:2rem;color:#ef4444;font-family:Inter,sans-serif;'>Errore critico: " . htmlspecialchars($db_error_message, ENT_QUOTES, 'UTF-8') . "</div>";
    exit;
}

$message = '';
if (isset($_SESSION['message'])) {
    $sessionMessage = $_SESSION['message'];
    $sessionIsError = $_SESSION['isError'] ?? false;
    $message = "<script>document.addEventListener('DOMContentLoaded', function() { showToast('" . addslashes($sessionMessage) . "', " . ($sessionIsError ? "'error'" : "'success'") . "); });</script>";
    unset($_SESSION['message']);
    unset($_SESSION['isError']);
}

// --- Recupero dati buoni regalo ---
$buoni_data = [];
try {
    $searchTerm = $_GET['search'] ?? '';
    $whereClause = '';
    $queryParams = [];
    $paramTypes = '';

    if (!empty($searchTerm)) {
        $whereClause = " WHERE nome LIKE ? OR destinatario LIKE ? OR note LIKE ? OR stato LIKE ? OR CAST(valore AS CHAR) LIKE ?";
        $searchTermLike = '%' . $searchTerm . '%';
        $queryParams = array_fill(0, 5, $searchTermLike);
        $paramTypes = 'sssss';
    }

    $sql = "SELECT * FROM buoni_regalo" . $whereClause . " ORDER BY data_creazione DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) throw new mysqli_sql_exception("Errore nella preparazione della query: " . $conn->error);
    if (!empty($queryParams)) $stmt->bind_param($paramTypes, ...$queryParams);
    $stmt->execute();
    $result = $stmt->get_result();
    $buoni_data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showToast('Errore nel caricamento dei buoni: " . addslashes($e->getMessage()) . "', 'error'); });</script>";
    error_log("Errore Visualizza Buoni (SQL): " . $e->getMessage());
}

// Calcolo statistiche
$totalBuoni = count($buoni_data);
$attivi = count(array_filter($buoni_data, fn($b) => ($b['stato'] ?? '') === 'Attivo'));
$usati = count(array_filter($buoni_data, fn($b) => ($b['stato'] ?? '') === 'Usato'));
$scaduti = count(array_filter($buoni_data, fn($b) => ($b['stato'] ?? '') === 'Scaduto'));
$valoreTotaleAttivi = array_sum(array_map(fn($b) => (float)($b['valore'] ?? 0), array_filter($buoni_data, fn($b) => ($b['stato'] ?? '') === 'Attivo')));

function formatCurrencyBuoni($value) {
    return number_format((float)$value, 2, ',', '.') . ' &euro;';
}

function getExpiryClass($dateStr) {
    if (empty($dateStr) || $dateStr === '0000-00-00') return 'none';
    $today = new DateTime('today');
    $expiry = new DateTime($dateStr);
    $diff = $today->diff($expiry);
    $days = (int)$diff->format('%R%a');
    if ($days < 0) return 'expired';
    if ($days <= 7) return 'warning';
    return 'ok';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Buoni Regalo | TS Service</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=2">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
                <style>
:root {
    --primary: #22c55e;
    --primary-dark: #16a34a;
    --primary-light: #dcfce7;
    --primary-glow: rgba(34, 197, 94, 0.4);
    --secondary: #3b82f6;
    --secondary-dark: #2563eb;
    --secondary-light: #dbeafe;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
    --purple: #8b5cf6;
    --gray: #64748b;
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
.hidden { display: none !important; }
*, *::before, *::after { box-sizing: border-box; }
body {
    margin: 0; font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, var(--bg-page) 0%, #e2e8f0 100%);
    min-height: 100vh; color: var(--text-primary);
    padding-top: 80px; line-height: 1.6; overflow-x: hidden;
}
/* PARTICLES */
.particles-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; overflow: hidden; }
.particle { position: absolute; border-radius: 50%; opacity: 0.15; animation: floatParticle 20s infinite ease-in-out; }
.particle:nth-child(1) { width: 300px; height: 300px; background: var(--primary); top: -100px; left: -100px; animation-delay: 0s; }
.particle:nth-child(2) { width: 200px; height: 200px; background: var(--secondary); top: 50%; right: -50px; animation-delay: -5s; }
.particle:nth-child(3) { width: 150px; height: 150px; background: var(--purple); bottom: 10%; left: 20%; animation-delay: -10s; }
.particle:nth-child(4) { width: 100px; height: 100px; background: var(--warning); top: 30%; left: 60%; animation-delay: -15s; }
@keyframes floatParticle {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(30px, -30px) scale(1.05); }
    50% { transform: translate(-20px, 20px) scale(0.95); }
    75% { transform: translate(15px, 15px) scale(1.02); }
}
/* TOAST */
.toast-container { position: fixed; top: 100px; right: 24px; z-index: 10000; display: flex; flex-direction: column; gap: 12px; pointer-events: none; }
.toast { background: var(--bg-card); border-radius: var(--radius-lg); padding: 16px 20px; box-shadow: var(--shadow-lg); display: flex; align-items: center; gap: 12px; min-width: 320px; max-width: 450px; pointer-events: auto; transform: translateX(120%); opacity: 0; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); border-left: 4px solid var(--primary); }
.toast.show { transform: translateX(0); opacity: 1; }
.toast.toast-success { border-left-color: var(--success); }
.toast.toast-error { border-left-color: var(--danger); }
.toast-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1rem; }
.toast-success .toast-icon { background: var(--primary-light); color: var(--primary); }
.toast-error .toast-icon { background: #fee2e2; color: var(--danger); }
.toast-content { flex: 1; }
.toast-title { font-weight: 600; font-size: 0.95rem; color: var(--text-primary); }
.toast-close { background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px; border-radius: var(--radius-sm); }
.toast-close:hover { background: var(--border-light); color: var(--text-primary); }
/* MAIN */
.main-content-container { max-width: 1500px; margin: 0 auto; padding: 24px 32px; position: relative; z-index: 1; }
/* PAGE HEADER */
.page-header { text-align: center; margin-bottom: 32px; animation: fadeInUp 0.6s ease-out; }
.page-header h1 { font-size: 2.75rem; font-weight: 800; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 8px; letter-spacing: -0.02em; }
.page-header p { color: var(--text-secondary); font-size: 1.1rem; margin: 0; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
/* SUMMARY */
.summary-panel { display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 28px; }
.summary-card { background: var(--bg-card); border-radius: var(--radius-xl); padding: 24px; box-shadow: var(--shadow); border: 1px solid var(--border-color); position: relative; overflow: hidden; cursor: pointer; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); opacity: 0; transform: translateY(30px); animation: cardSlideIn 0.5s ease-out forwards; }
.summary-card:nth-child(1) { animation-delay: 0.1s; }
.summary-card:nth-child(2) { animation-delay: 0.15s; }
.summary-card:nth-child(3) { animation-delay: 0.2s; }
.summary-card:nth-child(4) { animation-delay: 0.25s; }
.summary-card:nth-child(5) { animation-delay: 0.3s; }
@keyframes cardSlideIn { to { opacity: 1; transform: translateY(0); } }
.summary-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--card-accent), var(--card-accent-light, var(--card-accent))); opacity: 0; transition: opacity 0.3s ease; }
.summary-card:hover::before { opacity: 1; }
.summary-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg), 0 0 0 1px var(--card-accent); }
.summary-card.active { box-shadow: var(--shadow-lg), 0 0 0 2px var(--card-accent); }
.summary-card.active::before { opacity: 1; }
.summary-card--total { --card-accent: var(--primary); }
.summary-card--attivi { --card-accent: var(--secondary); }
.summary-card--usati { --card-accent: var(--gray); }
.summary-card--scaduti { --card-accent: var(--danger); }
.summary-card--valore { --card-accent: var(--purple); }
.summary-icon { width: 52px; height: 52px; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; transition: transform 0.3s ease; }
.summary-card:hover .summary-icon { transform: scale(1.1) rotate(-5deg); }
.summary-card--total .summary-icon { background: var(--primary-light); color: var(--primary); }
.summary-card--attivi .summary-icon { background: var(--secondary-light); color: var(--secondary); }
.summary-card--usati .summary-icon { background: #f1f5f9; color: var(--gray); }
.summary-card--scaduti .summary-icon { background: #fee2e2; color: var(--danger); }
.summary-card--valore .summary-icon { background: #ede9fe; color: var(--purple); }
.summary-icon svg { width: 26px; height: 26px; }
.summary-label { font-size: 0.85rem; font-weight: 500; color: var(--text-secondary); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
.summary-value { font-size: 2rem; font-weight: 700; color: var(--text-primary); line-height: 1.1; }
.summary-card--valore .summary-value { font-size: 1.6rem; color: var(--purple); }
/* FILTER BAR */
.filter-bar { background: var(--bg-card); border-radius: var(--radius-xl); padding: 20px 24px; box-shadow: var(--shadow); border: 1px solid var(--border-color); margin-bottom: 24px; display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; animation: fadeInUp 0.6s ease-out 0.3s both; }
.filter-group { flex: 1; min-width: 200px; }
.filter-group label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
.filter-input { width: 100%; padding: 12px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-md); font-size: 0.95rem; background: var(--bg-page); color: var(--text-primary); transition: all 0.2s ease; font-family: 'Inter', sans-serif; }
.filter-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); background: var(--bg-card); }
.filter-input::placeholder { color: var(--text-muted); }
.filter-actions { display: flex; gap: 10px; flex-wrap: wrap; }
/* BUTTONS */
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; border-radius: var(--radius-md); font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; border: none; position: relative; overflow: hidden; text-decoration: none; font-family: 'Inter', sans-serif; white-space: nowrap; }
.btn::after { content: ''; position: absolute; width: 100%; height: 100%; top: 0; left: 0; background: linear-gradient(rgba(255,255,255,0.2), rgba(255,255,255,0)); opacity: 0; transition: opacity 0.2s; }
.btn:hover::after { opacity: 1; }
.btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; box-shadow: 0 4px 14px var(--primary-glow); }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px var(--primary-glow); }
.btn-secondary { background: var(--border-light); color: var(--text-secondary); }
.btn-secondary:hover { background: var(--border-color); color: var(--text-primary); }
.btn svg { width: 18px; height: 18px; }
/* SECTION HEADER */
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.section-title { font-size: 1.3rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 10px; }
.section-title svg { width: 24px; height: 24px; color: var(--primary); }
.buoni-count { background: var(--primary-light); color: var(--primary-dark); padding: 4px 12px; border-radius: 999px; font-size: 0.85rem; font-weight: 600; }
/* BUONI GRID */
.buoni-section { animation: fadeInUp 0.6s ease-out 0.4s both; }
.buoni-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 20px; }
/* BUONO CARD */
.buono-card { background: var(--bg-card); border-radius: var(--radius-xl); box-shadow: var(--shadow); border: 1px solid var(--border-color); overflow: hidden; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); opacity: 0; transform: translateY(20px) scale(0.98); animation: buonoCardIn 0.4s ease-out forwards; }
@keyframes buonoCardIn { to { opacity: 1; transform: translateY(0) scale(1); } }
.buono-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg), var(--shadow-glow); }
.buono-card-header { padding: 18px 22px; display: flex; justify-content: space-between; align-items: center; position: relative; overflow: hidden; }
.buono-card-header.attivo { background: linear-gradient(135deg, var(--primary), #10b981); }
.buono-card-header.usato { background: linear-gradient(135deg, #475569, #334155); }
.buono-card-header.scaduto { background: linear-gradient(135deg, var(--danger), #dc2626); }
.buono-card-header::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%); animation: shimmerRotate 3s infinite; }
@keyframes shimmerRotate { 0%, 100% { transform: rotate(0deg); } 50% { transform: rotate(180deg); } }
.buono-code-area { display: flex; align-items: center; gap: 10px; position: relative; z-index: 1; }
.buono-code-badge { background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: var(--radius); font-weight: 700; font-size: 1rem; color: white; backdrop-filter: blur(10px); font-family: 'Courier New', monospace; letter-spacing: 1.5px; }
.buono-id-small { font-size: 0.8rem; opacity: 0.85; color: white; position: relative; z-index: 1; }
.buono-status-badge { padding: 6px 14px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; position: relative; z-index: 1; background: rgba(255,255,255,0.25); color: white; backdrop-filter: blur(10px); }
.buono-card-body { padding: 22px; }
/* VALUE AREA */
.buono-value-area { display: flex; align-items: center; justify-content: center; margin-bottom: 20px; padding: 16px; background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-radius: var(--radius-lg); border: 2px solid #bbf7d0; }
.buono-value-area .euro-sign { font-size: 1.2rem; font-weight: 600; color: var(--primary); margin-right: 4px; opacity: 0.7; }
.buono-value-area .value-number { font-size: 2rem; font-weight: 800; color: var(--primary-dark); letter-spacing: -0.5px; }
.buono-card.usato-card .buono-value-area { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); border-color: #cbd5e1; }
.buono-card.usato-card .buono-value-area .euro-sign, .buono-card.usato-card .buono-value-area .value-number { color: var(--gray); }
.buono-card.scaduto-card .buono-value-area { background: linear-gradient(135deg, #fef2f2, #fee2e2); border-color: #fecaca; }
.buono-card.scaduto-card .buono-value-area .euro-sign, .buono-card.scaduto-card .buono-value-area .value-number { color: var(--danger); }
/* INFO GRID */
.buono-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
.buono-info-item { background: var(--bg-page); padding: 12px 14px; border-radius: var(--radius-md); border: 1px solid var(--border-light); }
.buono-info-item.full-width { grid-column: 1 / -1; }
.buono-info-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
.buono-info-label svg { width: 13px; height: 13px; }
.buono-info-value { font-weight: 600; font-size: 0.95rem; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
/* EXPIRY */
.expiry-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 999px; margin-top: 4px; }
.expiry-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
.expiry-ok { background: #dcfce7; color: #15803d; }
.expiry-ok::before { background: var(--primary); }
.expiry-warning { background: #fef3c7; color: #92400e; }
.expiry-warning::before { background: var(--warning); animation: pulse 2s infinite; }
.expiry-expired { background: #fee2e2; color: #991b1b; }
.expiry-expired::before { background: var(--danger); }
.expiry-none { background: #f1f5f9; color: var(--text-muted); }
.expiry-none::before { background: var(--text-muted); }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
/* CARD FOOTER */
.buono-card-footer { display: flex; justify-content: flex-end; align-items: center; padding-top: 16px; border-top: 1px solid var(--border-light); gap: 8px; }
.btn-action { width: 38px; height: 38px; border-radius: var(--radius); border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; }
.btn-action:hover { border-color: var(--primary); color: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow); }
.btn-action.danger:hover { border-color: var(--danger); color: var(--danger); background: #fee2e2; }
.btn-action svg { width: 18px; height: 18px; }
/* EMPTY STATE */
.empty-state { text-align: center; padding: 60px 20px; background: var(--bg-card); border-radius: var(--radius-xl); border: 2px dashed var(--border-color); }
.empty-icon { width: 80px; height: 80px; margin: 0 auto 20px; background: var(--primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary); }
.empty-icon svg { width: 40px; height: 40px; }
.empty-title { font-size: 1.3rem; font-weight: 600; color: var(--text-primary); margin-bottom: 8px; }
.empty-text { color: var(--text-secondary); max-width: 400px; margin: 0 auto; }
/* MODAL — override header-styles.css .modal-overlay */
#editModal, #createModal { display: none !important; opacity: 1 !important; visibility: visible !important; pointer-events: auto !important; z-index: 99999 !important; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); justify-content: center; align-items: center; isolation: isolate; }
#editModal.show, #createModal.show { display: flex !important; }
.modal-content { background: var(--bg-card); border-radius: 20px; box-shadow: var(--shadow-lg); max-width: 95%; width: 650px; max-height: 90vh; overflow: hidden; transform: scale(0.9) translateY(20px); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); pointer-events: auto !important; }
#editModal.show .modal-content, #createModal.show .modal-content { transform: scale(1) translateY(0); }
.modal-header-green { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; }
.modal-header-green h2 { color: white; font-size: 1.3rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.75rem; }
.modal-header-green h2 svg { width: 24px; height: 24px; }
.modal-close-btn { background: rgba(255,255,255,0.2); border: none; color: white; width: 40px; height: 40px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.modal-close-btn:hover { background: rgba(255,255,255,0.3); }
.modal-close-btn svg { width: 24px; height: 24px; }
.modal-body-form { padding: 2rem; max-height: 65vh; overflow-y: auto; background: #f8fafc; }
.edit-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.edit-field { display: flex; flex-direction: column; gap: 0.35rem; }
.edit-field.full-width { grid-column: 1 / -1; }
.edit-field label { font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; }
.edit-field input, .edit-field textarea, .edit-field select { padding: 0.75rem 1rem; border: 2px solid var(--border-color); border-radius: 10px; font-size: 0.95rem; transition: all 0.2s; background: white; font-family: 'Inter', sans-serif; }
.edit-field input:focus, .edit-field textarea:focus, .edit-field select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15); background: white; }
.edit-field input[readonly] { background: #e2e8f0; color: var(--text-secondary); cursor: not-allowed; }
.edit-field textarea { min-height: 80px; resize: vertical; }
.edit-field .value-input { font-size: 1.3rem; font-weight: 700; text-align: center; color: var(--primary-dark); border-color: var(--primary); background: linear-gradient(135deg, #f0fdf4, #dcfce7); }
.modal-footer-btns { padding: 1.25rem 2rem; background: white; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 0.75rem; }
.btn-modal-cancel { padding: 0.75rem 1.5rem; background: white; border: 2px solid var(--border-color); border-radius: 10px; font-weight: 600; color: var(--text-secondary); cursor: pointer; transition: all 0.2s; font-family: 'Inter', sans-serif; font-size: 0.95rem; }
.btn-modal-cancel:hover { background: #f1f5f9; border-color: #cbd5e1; }
.btn-modal-save { padding: 0.75rem 1.5rem; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border: none; border-radius: 10px; font-weight: 600; color: white; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px var(--primary-glow); font-family: 'Inter', sans-serif; font-size: 0.95rem; }
.btn-modal-save:hover { transform: translateY(-2px); box-shadow: 0 6px 20px var(--primary-glow); }
.btn-modal-save svg { width: 18px; height: 18px; }
/* SCROLLBAR */
::-webkit-scrollbar { width: 8px; height: 8px; }
::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: var(--primary); }
/* RESPONSIVE */
@media (max-width: 1400px) { .summary-panel { grid-template-columns: repeat(3, 1fr); } .buoni-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 1100px) { .summary-panel { grid-template-columns: repeat(2, 1fr); } .filter-bar { flex-direction: column; } .filter-group { min-width: auto; } }
@media (max-width: 768px) { .summary-panel { grid-template-columns: 1fr 1fr; gap: 12px; } .summary-card { padding: 16px; } .summary-value { font-size: 1.5rem; } .buoni-grid { grid-template-columns: 1fr; } .main-content-container { padding: 16px; } .page-header h1 { font-size: 2rem; } .edit-grid { grid-template-columns: 1fr; } .modal-body-form { padding: 1rem; } }
@media (max-width: 500px) { .summary-panel { grid-template-columns: 1fr; } .buono-info-grid { grid-template-columns: 1fr; } .page-header h1 { font-size: 1.6rem; } .filter-actions { flex-direction: column; width: 100%; } .filter-actions .btn { width: 100%; } }
</style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="particles-container">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
</div>

<div class="toast-container" id="toastContainer"></div>

<div class="main-content-container">
    <?php echo $message; ?>

    <div class="page-header">
        <h1>Dashboard Buoni Regalo</h1>
        <p>Gestisci tutti i buoni regalo in un unico posto</p>
    </div>

    <!-- Summary Cards -->
    <div class="summary-panel">
        <div class="summary-card summary-card--total" onclick="filterByStatus('')" data-status="">
            <div class="summary-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12v10H4V12"></path><path d="M2 7h20v5H2z"></path><path d="M12 22V7"></path><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>
            </div>
            <div class="summary-label">Totale Buoni</div>
            <div class="summary-value" data-count="<?php echo $totalBuoni; ?>">0</div>
        </div>
        <div class="summary-card summary-card--attivi" onclick="filterByStatus('Attivo')" data-status="Attivo">
            <div class="summary-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <div class="summary-label">Attivi</div>
            <div class="summary-value" data-count="<?php echo $attivi; ?>">0</div>
        </div>
        <div class="summary-card summary-card--usati" onclick="filterByStatus('Usato')" data-status="Usato">
            <div class="summary-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            </div>
            <div class="summary-label">Usati</div>
            <div class="summary-value" data-count="<?php echo $usati; ?>">0</div>
        </div>
        <div class="summary-card summary-card--scaduti" onclick="filterByStatus('Scaduto')" data-status="Scaduto">
            <div class="summary-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div class="summary-label">Scaduti</div>
            <div class="summary-value" data-count="<?php echo $scaduti; ?>">0</div>
        </div>
        <div class="summary-card summary-card--valore">
            <div class="summary-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 7.5C16 5 13.5 3.5 10.5 3.5 6.4 3.5 3 7 3 12s3.4 8.5 7.5 8.5c3 0 5.5-1.5 7-4"></path><line x1="2" y1="10" x2="14" y2="10"></line><line x1="2" y1="14" x2="14" y2="14"></line></svg>
            </div>
            <div class="summary-label">Valore Attivi</div>
            <div class="summary-value"><?php echo formatCurrencyBuoni($valoreTotaleAttivi); ?></div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group" style="flex: 2;">
            <label>Cerca Buono</label>
            <input type="text" id="searchInput" class="filter-input" placeholder="Codice, destinatario, note, valore..." value="<?php echo htmlspecialchars($searchTerm ?? ''); ?>">
        </div>
        <div class="filter-actions">
            <button class="btn btn-primary" onclick="applySearch()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                Cerca
            </button>
            <a href="visualizza_buoni.php" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                Reset
            </a>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Nuovo Buono
            </button>
        </div>
    </div>

    <!-- Buoni Section -->
    <div class="buoni-section">
        <div class="section-header">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12v10H4V12"></path><path d="M2 7h20v5H2z"></path><path d="M12 22V7"></path></svg>
                Elenco Buoni Regalo
            </h2>
            <span class="buoni-count" id="buoniCount"><?php echo $totalBuoni; ?> buoni</span>
        </div>

        <?php if (!empty($buoni_data)): ?>
        <div class="buoni-grid" id="buoniGrid">
            <?php foreach ($buoni_data as $index => $row): 
                $stato = htmlspecialchars($row['stato'] ?? 'Attivo');
                $statoLower = strtolower($stato);
                $valore = (float)($row['valore'] ?? 0);
                $codice = htmlspecialchars($row['nome'] ?? 'N/A');
                $destinatario = htmlspecialchars($row['destinatario'] ?? '');
                $dataCreazione = !empty($row['data_creazione']) ? date('d/m/Y H:i', strtotime($row['data_creazione'])) : '-';
                $dataScadenza = $row['data_scadenza'] ?? '';
                $dataScadenzaVis = (!empty($dataScadenza) && $dataScadenza !== '0000-00-00') ? date('d/m/Y', strtotime($dataScadenza)) : 'Nessuna';
                $expiryClass = getExpiryClass($dataScadenza);
                $note = htmlspecialchars($row['note'] ?? '');
                $cardClass = ($statoLower === 'usato') ? 'usato-card' : (($statoLower === 'scaduto') ? 'scaduto-card' : '');
                
                $expiryLabel = 'Nessuna scadenza';
                if (!empty($dataScadenza) && $dataScadenza !== '0000-00-00') {
                    $today = new DateTime('today');
                    $expiry = new DateTime($dataScadenza);
                    $days = (int)$today->diff($expiry)->format('%R%a');
                    if ($days > 0) $expiryLabel = $days . 'g rimanenti';
                    elseif ($days === 0) $expiryLabel = 'Scade oggi!';
                    else $expiryLabel = 'Scaduto da ' . abs($days) . 'g';
                }
            ?>
            <div class="buono-card <?php echo $cardClass; ?>" 
                 style="animation-delay: <?php echo min($index * 0.05, 1); ?>s"
                 data-status="<?php echo $stato; ?>"
                 data-id="<?php echo $row['id']; ?>"
                 data-nome="<?php echo $codice; ?>"
                 data-valore="<?php echo $valore; ?>"
                 data-destinatario="<?php echo $destinatario; ?>"
                 data-scadenza="<?php echo htmlspecialchars($dataScadenza); ?>"
                 data-note="<?php echo $note; ?>"
                 data-stato="<?php echo $stato; ?>">
                
                <div class="buono-card-header <?php echo $statoLower; ?>">
                    <div class="buono-code-area">
                        <span class="buono-code-badge"><?php echo $codice; ?></span>
                        <span class="buono-id-small">#<?php echo $row['id']; ?></span>
                    </div>
                    <span class="buono-status-badge"><?php echo $stato; ?></span>
                </div>

                <div class="buono-card-body">
                    <div class="buono-value-area">
                        <span class="euro-sign">&euro;</span>
                        <span class="value-number"><?php echo number_format($valore, 2, ',', '.'); ?></span>
                    </div>

                    <div class="buono-info-grid">
                        <div class="buono-info-item">
                            <div class="buono-info-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                Destinatario
                            </div>
                            <div class="buono-info-value"><?php echo $destinatario ?: '-'; ?></div>
                        </div>
                        <div class="buono-info-item">
                            <div class="buono-info-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                Data Creazione
                            </div>
                            <div class="buono-info-value"><?php echo $dataCreazione; ?></div>
                        </div>
                        <div class="buono-info-item full-width">
                            <div class="buono-info-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                Scadenza
                            </div>
                            <div class="buono-info-value"><?php echo $dataScadenzaVis; ?></div>
                            <span class="expiry-badge expiry-<?php echo $expiryClass; ?>"><?php echo $expiryLabel; ?></span>
                        </div>
                        <?php if (!empty($note)): ?>
                        <div class="buono-info-item full-width">
                            <div class="buono-info-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                Note
                            </div>
                            <div class="buono-info-value" style="white-space: normal; font-weight: 400; font-size: 0.85rem; color: var(--text-secondary);"><?php echo nl2br($note); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="buono-card-footer">
                        <button class="btn-action" onclick="openEditModal(<?php echo $row['id']; ?>)" title="Modifica">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        </button>
                        <button class="btn-action" onclick="window.open('stampa_buono.php?id=<?php echo $row['id']; ?>','_blank')" title="Stampa">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        </button>
                        <button class="btn-action danger" onclick="deleteBuono(<?php echo $row['id']; ?>)" title="Elimina">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12v10H4V12"></path><path d="M2 7h20v5H2z"></path><path d="M12 22V7"></path><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>
            </div>
            <h3 class="empty-title">Nessun buono regalo trovato</h3>
            <p class="empty-text">Non ci sono buoni che corrispondono ai criteri di ricerca. Prova a modificare i filtri o crea un nuovo buono.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <div class="modal-header-green">
            <h2>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                Modifica Buono Regalo
            </h2>
            <button class="modal-close-btn" onclick="closeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form id="editBuonoForm">
            <input type="hidden" id="editId" name="id">
            <div class="modal-body-form">
                <div class="edit-grid">
                    <div class="edit-field">
                        <label>Valore Buono (&euro;)</label>
                        <input type="number" id="editValore" name="valore" step="0.01" min="0" required class="value-input">
                    </div>
                    <div class="edit-field">
                        <label>Stato</label>
                        <select id="editStato" name="stato_buono">
                            <option value="Attivo">&#x2705; Attivo</option>
                            <option value="Usato">&#x1F4CB; Usato</option>
                            <option value="Scaduto">&#x274C; Scaduto</option>
                        </select>
                    </div>
                    <div class="edit-field full-width">
                        <label>Codice Buono</label>
                        <input type="text" id="editCodice" name="codice_buono" readonly>
                    </div>
                    <div class="edit-field">
                        <label>Destinatario</label>
                        <input type="text" id="editDestinatario" name="destinatario" placeholder="Nome destinatario...">
                    </div>
                    <div class="edit-field">
                        <label>Data Scadenza</label>
                        <input type="date" id="editScadenza" name="data_scadenza">
                    </div>
                    <div class="edit-field full-width">
                        <label>Note</label>
                        <textarea id="editNote" name="mittente_note" rows="3" placeholder="Note aggiuntive..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer-btns">
                <button type="button" class="btn-modal-cancel" onclick="closeModal()">Annulla</button>
                <button type="submit" class="btn-modal-save">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    Salva Modifiche
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Create Buono Modal -->
<style>
.create-modal .modal-content {
    max-width: 560px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(0,0,0,0.3), 0 0 0 1px rgba(255,255,255,0.05);
    animation: modalSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(40px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.create-modal .modal-header-green {
    background: linear-gradient(135deg, #059669, #10b981, #34d399);
    padding: 24px 28px;
    position: relative;
    overflow: hidden;
}
.create-modal .modal-header-green::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -30%;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
    border-radius: 50%;
}
.create-modal .modal-header-green::after {
    content: '';
    position: absolute;
    bottom: -40%;
    left: -20%;
    width: 150px;
    height: 150px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
}
.create-modal .modal-header-green h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
    position: relative;
    z-index: 1;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
.create-modal .modal-header-green h2 svg {
    width: 26px;
    height: 26px;
    flex-shrink: 0;
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.15));
}
.create-modal .header-subtitle {
    color: rgba(255,255,255,0.85);
    font-size: 0.82rem;
    margin-top: 4px;
    margin-left: 38px;
    position: relative;
    z-index: 1;
}
.create-modal .modal-close-btn {
    position: absolute;
    top: 16px;
    right: 16px;
    background: rgba(255,255,255,0.2);
    border: none;
    border-radius: 10px;
    width: 36px;
    height: 36px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    z-index: 2;
    backdrop-filter: blur(4px);
}
.create-modal .modal-close-btn:hover {
    background: rgba(255,255,255,0.35);
    transform: rotate(90deg);
}
.create-modal .modal-close-btn svg {
    width: 18px;
    height: 18px;
    stroke: #fff;
}
.create-modal .modal-body-form {
    padding: 28px;
    background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 40%);
}
.create-modal .create-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.create-modal .create-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.create-modal .create-field.full-width {
    grid-column: 1 / -1;
}
.create-modal .create-field label {
    font-size: 0.78rem;
    font-weight: 600;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.create-modal .create-field label .required-dot {
    width: 6px;
    height: 6px;
    background: #ef4444;
    border-radius: 50%;
    display: inline-block;
}
.create-modal .create-field input,
.create-modal .create-field select,
.create-modal .create-field textarea {
    padding: 12px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.95rem;
    font-family: 'Inter', sans-serif;
    color: #1f2937;
    background: #fff;
    transition: all 0.25s;
    outline: none;
}
.create-modal .create-field input:focus,
.create-modal .create-field select:focus,
.create-modal .create-field textarea:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12);
}
.create-modal .create-field input::placeholder,
.create-modal .create-field textarea::placeholder {
    color: #9ca3af;
}
.create-modal .create-field .value-input {
    font-size: 1.3rem;
    font-weight: 700;
    color: #059669;
    letter-spacing: 0.5px;
}
.create-modal .code-input-wrap {
    display: flex;
    gap: 8px;
}
.create-modal .code-input-wrap input {
    flex: 1;
    font-family: 'JetBrains Mono', 'SF Mono', 'Fira Code', monospace;
    font-weight: 600;
    letter-spacing: 1.5px;
    color: #059669;
    background: #f0fdf4;
    border-style: dashed;
}
.create-modal .btn-copy-code,
.create-modal .btn-gen-code {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    background: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s;
}
.create-modal .btn-copy-code:hover { border-color: #10b981; background: #f0fdf4; }
.create-modal .btn-gen-code:hover { border-color: #8b5cf6; background: #f5f3ff; }
.create-modal .btn-copy-code svg,
.create-modal .btn-gen-code svg {
    width: 18px;
    height: 18px;
    stroke: #6b7280;
    transition: stroke 0.2s;
}
.create-modal .btn-copy-code:hover svg { stroke: #059669; }
.create-modal .btn-gen-code:hover svg { stroke: #7c3aed; }
.create-modal .modal-footer-btns {
    padding: 20px 28px;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    border-top: 1px solid #f3f4f6;
    background: #fff;
}
.create-modal .btn-modal-cancel {
    padding: 12px 24px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    background: #fff;
    color: #6b7280;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
}
.create-modal .btn-modal-cancel:hover { border-color: #d1d5db; background: #f9fafb; color: #374151; }
.create-modal .btn-modal-create {
    padding: 12px 28px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #059669, #10b981);
    color: #fff;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.25s;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.35);
    position: relative;
    overflow: hidden;
}
.create-modal .btn-modal-create::before {
    content: '';
    position: absolute;
    top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}
.create-modal .btn-modal-create:hover::before { left: 100%; }
.create-modal .btn-modal-create:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
}
.create-modal .btn-modal-create:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.create-modal .btn-modal-create svg {
    width: 18px;
    height: 18px;
}
.create-modal .create-field select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 36px;
}
.create-modal .quick-value-chips {
    display: flex;
    gap: 8px;
    margin-top: 4px;
}
.create-modal .quick-chip {
    padding: 5px 12px;
    border: 1.5px solid #d1fae5;
    border-radius: 20px;
    background: #ecfdf5;
    color: #059669;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
}
.create-modal .quick-chip:hover { background: #d1fae5; border-color: #10b981; }
.create-modal .quick-chip.active { background: #059669; color: #fff; border-color: #059669; }
.create-modal .spinner-icon {
    display: none;
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top: 2px solid #fff;
    border-radius: 50%;
    animation: spinBtn 0.6s linear infinite;
}
@keyframes spinBtn { to { transform: rotate(360deg); } }
@media (max-width: 600px) {
    .create-modal .modal-content { margin: 10px; border-radius: 16px; }
    .create-modal .create-grid { grid-template-columns: 1fr; }
    .create-modal .modal-body-form { padding: 20px; }
}
</style>

<div class="modal-overlay create-modal" id="createModal">
    <div class="modal-content">
        <div class="modal-header-green">
            <h2>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12v10H4V12"></path><path d="M2 7h20v5H2z"></path><path d="M12 22V7"></path><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>
                Nuovo Buono Regalo
            </h2>
            <p class="header-subtitle">Compila i dettagli per creare un nuovo buono regalo</p>
            <button class="modal-close-btn" onclick="closeCreateModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form id="createBuonoForm">
            <div class="modal-body-form">
                <div class="create-grid">
                    <!-- Valore -->
                    <div class="create-field">
                        <label>Valore Buono (&euro;) <span class="required-dot"></span></label>
                        <input type="number" id="createValore" name="valoreBuono" step="0.01" min="0.01" required class="value-input" placeholder="0,00">
                        <div class="quick-value-chips">
                            <button type="button" class="quick-chip" onclick="setQuickValue(25)">25&euro;</button>
                            <button type="button" class="quick-chip" onclick="setQuickValue(50)">50&euro;</button>
                            <button type="button" class="quick-chip" onclick="setQuickValue(100)">100&euro;</button>
                            <button type="button" class="quick-chip" onclick="setQuickValue(200)">200&euro;</button>
                        </div>
                    </div>
                    <!-- Stato -->
                    <div class="create-field">
                        <label>Stato</label>
                        <select id="createStato" name="stato">
                            <option value="Attivo">&#x2705; Attivo</option>
                            <option value="Usato">&#x1F4CB; Usato</option>
                            <option value="Scaduto">&#x274C; Scaduto</option>
                        </select>
                    </div>
                    <!-- Codice Buono -->
                    <div class="create-field full-width">
                        <label>Codice Buono <span class="required-dot"></span></label>
                        <div class="code-input-wrap">
                            <input type="text" id="createCodice" name="nomeBuono" readonly placeholder="Generato automaticamente...">
                            <button type="button" class="btn-gen-code" onclick="regenerateCode()" title="Rigenera codice">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                            </button>
                            <button type="button" class="btn-copy-code" onclick="copyCode()" title="Copia codice">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                            </button>
                        </div>
                    </div>
                    <!-- Destinatario -->
                    <div class="create-field">
                        <label>Destinatario</label>
                        <input type="text" id="createDestinatario" name="destinatario" placeholder="Nome destinatario...">
                    </div>
                    <!-- Data Scadenza -->
                    <div class="create-field">
                        <label>Data Scadenza <span class="required-dot"></span></label>
                        <input type="date" id="createScadenza" name="dataScadenza" required>
                    </div>
                    <!-- Note -->
                    <div class="create-field full-width">
                        <label>Mittente / Note</label>
                        <textarea id="createNote" name="note" rows="3" placeholder="Note aggiuntive, mittente, messaggio personale..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer-btns">
                <button type="button" class="btn-modal-cancel" onclick="closeCreateModal()">Annulla</button>
                <button type="submit" class="btn-modal-create" id="createSubmitBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12v10H4V12"></path><path d="M2 7h20v5H2z"></path><path d="M12 22V7"></path></svg>
                    <span class="btn-text">Crea Buono</span>
                    <span class="spinner-icon"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
var allBuoni = <?php echo json_encode($buoni_data); ?>;
var currentStatusFilter = '';

function showToast(message, type) {
    type = type || 'success';
    var container = document.getElementById('toastContainer');
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    var iconHtml = type === 'error' ? '<i class="fas fa-times-circle"></i>' : '<i class="fas fa-check-circle"></i>';
    toast.innerHTML = '<div class="toast-icon">' + iconHtml + '</div><div class="toast-content"><div class="toast-title">' + message + '</div></div><button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>';
    container.appendChild(toast);
    setTimeout(function() { toast.classList.add('show'); }, 10);
    setTimeout(function() { toast.classList.remove('show'); setTimeout(function() { toast.remove(); }, 400); }, 4000);
}

function animateCounters() {
    document.querySelectorAll('.summary-value[data-count]').forEach(function(el) {
        var target = parseInt(el.dataset.count);
        var duration = 1000;
        var start = performance.now();
        function update(now) {
            var elapsed = now - start;
            var progress = Math.min(elapsed / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(target * eased);
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    });
}

function filterByStatus(status) {
    if (currentStatusFilter === status) { currentStatusFilter = ''; } else { currentStatusFilter = status; }
    document.querySelectorAll('.summary-card').forEach(function(c) { c.classList.remove('active'); });
    if (currentStatusFilter) {
        var ac = document.querySelector('.summary-card[data-status="' + currentStatusFilter + '"]');
        if (ac) ac.classList.add('active');
    }
    var cards = document.querySelectorAll('.buono-card');
    var visibleCount = 0;
    cards.forEach(function(card) {
        if (!currentStatusFilter || card.dataset.status === currentStatusFilter) { card.style.display = ''; visibleCount++; } else { card.style.display = 'none'; }
    });
    document.getElementById('buoniCount').textContent = visibleCount + ' buoni';
}

function applySearch() {
    var term = document.getElementById('searchInput').value.trim();
    window.location.href = term ? 'visualizza_buoni.php?search=' + encodeURIComponent(term) : 'visualizza_buoni.php';
}

document.getElementById('searchInput').addEventListener('keypress', function(e) { if (e.key === 'Enter') { e.preventDefault(); applySearch(); } });

// --- Edit Modal ---
var editModal = document.getElementById('editModal');

function openEditModal(id) {
    var card = document.querySelector('.buono-card[data-id="' + id + '"]');
    if (!card) return;
    document.getElementById('editId').value = id;
    document.getElementById('editValore').value = parseFloat(card.dataset.valore).toFixed(2);
    document.getElementById('editCodice').value = card.dataset.nome;
    document.getElementById('editDestinatario').value = card.dataset.destinatario || '';
    document.getElementById('editScadenza').value = (card.dataset.scadenza && card.dataset.scadenza !== '0000-00-00') ? card.dataset.scadenza : '';
    document.getElementById('editNote').value = card.dataset.note || '';
    document.getElementById('editStato').value = card.dataset.stato || 'Attivo';
    editModal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() { editModal.classList.remove('show'); document.body.style.overflow = ''; }
editModal.addEventListener('click', function(e) { if (e.target === editModal) closeModal(); });

document.getElementById('editBuonoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Leggi direttamente dagli input per evitare problemi locale con FormData
    var valoreRaw = document.getElementById('editValore').value.replace(',', '.');
    var valoreNum = parseFloat(valoreRaw);
    if (!valoreNum || valoreNum <= 0) { showToast('Il valore deve essere maggiore di zero.', 'error'); return; }
    var data = {
        id: document.getElementById('editId').value,
        valore: valoreNum,
        codice_buono: document.getElementById('editCodice').value,
        stato_buono: document.getElementById('editStato').value,
        destinatario: document.getElementById('editDestinatario').value,
        data_scadenza: document.getElementById('editScadenza').value,
        mittente_note: document.getElementById('editNote').value
    };
    console.log('Invio aggiornamento buono:', data);
    var saveBtn = this.querySelector('.btn-modal-save');
    if (saveBtn) { saveBtn.disabled = true; saveBtn.style.opacity = '0.6'; }
    fetch('update_buono.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
    .then(function(response) {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
    })
    .then(function(result) {
        console.log('Risposta server:', result);
        if (result.success) {
            closeModal();
            showToast('Buono aggiornato con successo!', 'success');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            showToast(result.message || "Errore durante l'aggiornamento.", 'error');
        }
    })
    .catch(function(err) {
        console.error('Errore aggiornamento buono:', err);
        showToast('Errore di rete: ' + err.message, 'error');
    })
    .finally(function() {
        if (saveBtn) { saveBtn.disabled = false; saveBtn.style.opacity = '1'; }
    });
});

// --- Create Modal ---
var createModal = document.getElementById('createModal');

function generateRandomCode() {
    var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    var code = 'BUONO-';
    for (var i = 0; i < 8; i++) { code += chars.charAt(Math.floor(Math.random() * chars.length)); }
    return code;
}

function openCreateModal() {
    document.getElementById('createBuonoForm').reset();
    document.getElementById('createCodice').value = generateRandomCode();
    document.getElementById('createStato').value = 'Attivo';
    // Set default expiry to 1 year from now
    var defaultExpiry = new Date();
    defaultExpiry.setFullYear(defaultExpiry.getFullYear() + 1);
    document.getElementById('createScadenza').value = defaultExpiry.toISOString().split('T')[0];
    // Remove active chips
    document.querySelectorAll('.quick-chip').forEach(function(c) { c.classList.remove('active'); });
    createModal.classList.add('show');
    document.body.style.overflow = 'hidden';
    setTimeout(function() { document.getElementById('createValore').focus(); }, 300);
}

function closeCreateModal() { createModal.classList.remove('show'); document.body.style.overflow = ''; }
createModal.addEventListener('click', function(e) { if (e.target === createModal) closeCreateModal(); });

function regenerateCode() {
    var input = document.getElementById('createCodice');
    input.style.opacity = '0.3';
    setTimeout(function() {
        input.value = generateRandomCode();
        input.style.opacity = '1';
    }, 150);
}

function copyCode() {
    var code = document.getElementById('createCodice').value;
    if (!code) return;
    navigator.clipboard.writeText(code).then(function() {
        showToast('Codice copiato: ' + code, 'success');
    }).catch(function() {
        // Fallback
        var temp = document.createElement('textarea');
        temp.value = code;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        showToast('Codice copiato: ' + code, 'success');
    });
}

function setQuickValue(val) {
    document.getElementById('createValore').value = val;
    document.querySelectorAll('.quick-chip').forEach(function(c) {
        c.classList.toggle('active', parseInt(c.textContent) === val);
    });
}

document.getElementById('createBuonoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('createSubmitBtn');
    var btnText = btn.querySelector('.btn-text');
    var spinner = btn.querySelector('.spinner-icon');
    var valore = parseFloat(document.getElementById('createValore').value);
    var codice = document.getElementById('createCodice').value.trim();
    var scadenza = document.getElementById('createScadenza').value;

    if (!valore || valore <= 0) { showToast('Inserisci un valore valido per il buono.', 'error'); return; }
    if (!codice) { showToast('Il codice buono \u00e8 obbligatorio.', 'error'); return; }
    if (!scadenza) { showToast('La data di scadenza \u00e8 obbligatoria.', 'error'); return; }

    // Show loading state
    btn.disabled = true;
    btnText.textContent = 'Creazione...';
    spinner.style.display = 'inline-block';

    var formData = new FormData(this);

    fetch('salva_buono_regalo.php', { method: 'POST', body: formData })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        if (result.success) {
            closeCreateModal();
            showToast('Buono regalo creato con successo!', 'success');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            showToast(result.message || 'Errore durante la creazione.', 'error');
        }
    })
    .catch(function(err) {
        showToast('Errore di rete. Riprova.', 'error');
    })
    .finally(function() {
        btn.disabled = false;
        btnText.textContent = 'Crea Buono';
        spinner.style.display = 'none';
    });
});

// --- Escape key closes any open modal ---
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (createModal.classList.contains('show')) { closeCreateModal(); }
        else if (editModal.classList.contains('show')) { closeModal(); }
    }
});

function deleteBuono(id) {
    if (!confirm('Sei sicuro di voler eliminare questo buono regalo? Questa azione \u00e8 irreversibile.')) return;
    showToast('Funzione di eliminazione in fase di implementazione.', 'error');
}

document.addEventListener('DOMContentLoaded', function() { animateCounters(); });
</script>
</body>
</html>
