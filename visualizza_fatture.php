<?php
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Avvia la sessione PHP - Assicurati che sia sempre all'inizio del file
session_start();

// Includi il file di connessione al database MySQL
if (!file_exists('db.php')) {
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: Il file db.php non è stato trovato!</div>";
    exit;
}
require_once 'db.php';

// Controlla subito se c'è stato un errore di connessione al database da db.php
if (!isset($conn) || $conn === null) {
    $db_error_message = isset($db_connection_error) ? $db_connection_error : 'Connessione al database non stabilita.';
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: " . htmlspecialchars($db_error_message, ENT_QUOTES, 'UTF-8') . "</div>";
    exit;
}

// Inizializza la variabile message per l'uso nel HTML
$message = '';

// Controlla se ci sono messaggi da visualizzare dalla sessione
if (isset($_SESSION['message'])) {
    $sessionMessage = $_SESSION['message'];
    $sessionIsError = $_SESSION['isError'] ?? false;
    $message = "<script>document.addEventListener('DOMContentLoaded', function() { showToast('" . addslashes($sessionMessage) . "', " . ($sessionIsError ? "'error'" : "'success'") . "); });</script>";
    unset($_SESSION['message']);
    unset($_SESSION['isError']);
}


// --- LOGICA PER RECUPERARE LE FATTURE (LISTA) ---
$fatture = [];
try {
    $stmt = $conn->prepare("SELECT f.id, f.numero_fattura, f.data_fattura, fo.ragione_sociale AS nome_fornitore, f.stato, f.totale_imponibile, f.totale_iva, f.totale_lordo, f.allegato_url FROM fatture f JOIN fornitori fo ON f.fornitore_id = fo.id ORDER BY f.data_fattura DESC, f.id DESC");

    if ($stmt === false) {
        throw new mysqli_sql_exception("Errore nella preparazione della query di recupero fatture: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $fatture = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    $_SESSION['message'] = "Errore nel caricamento delle fatture (SQL): " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    $_SESSION['isError'] = true;
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showToast('" . addslashes($_SESSION['message']) . "', 'error'); });</script>";
    unset($_SESSION['message'], $_SESSION['isError']);
    error_log("Errore Visualizza Fatture (SQL): " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elenco Fatture | TS Service</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=2">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
                    <style>
/* ======================================= */
/*        PREMIUM DESIGN SYSTEM            */
/* ======================================= */
:root {
    --primary: #22c55e;
    --primary-dark: #16a34a;
    --primary-light: #dcfce7;
    --primary-glow: rgba(34, 197, 94, 0.4);
    --blue: #3b82f6;
    --blue-dark: #2563eb;
    --blue-light: #dbeafe;
    --green: #22c55e;
    --green-dark: #16a34a;
    --green-light: #dcfce7;
    --secondary: #8b5cf6;
    --secondary-light: #ede9fe;
    --success: #10b981;
    --warning: #f59e0b;
    --warning-light: #fef3c7;
    --danger: #ef4444;
    --danger-dark: #dc2626;
    --danger-light: #fee2e2;
    --info: #06b6d4;
    --info-light: #ecfeff;
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
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: linear-gradient(135deg, var(--bg-page) 0%, #e2e8f0 100%);
    min-height: 100vh; color: var(--text-primary);
    padding-top: 80px; line-height: 1.6; overflow-x: hidden;
}

/* ======================================= */
/*           FLOATING PARTICLES            */
/* ======================================= */
.particles-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; overflow: hidden; }
.particle { position: absolute; border-radius: 50%; opacity: 0.12; animation: floatParticle 20s infinite ease-in-out; }
.particle:nth-child(1) { width: 300px; height: 300px; background: var(--primary); top: -100px; left: -100px; animation-delay: 0s; }
.particle:nth-child(2) { width: 200px; height: 200px; background: var(--blue); top: 50%; right: -50px; animation-delay: -5s; }
.particle:nth-child(3) { width: 150px; height: 150px; background: var(--secondary); bottom: 10%; left: 20%; animation-delay: -10s; }
.particle:nth-child(4) { width: 100px; height: 100px; background: var(--warning); top: 30%; left: 60%; animation-delay: -15s; }
@keyframes floatParticle {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(30px, -30px) scale(1.05); }
    50% { transform: translate(-20px, 20px) scale(0.95); }
    75% { transform: translate(15px, 15px) scale(1.02); }
}

/* ======================================= */
/*              TOAST SYSTEM               */
/* ======================================= */
.toast-container {
    position: fixed; top: 100px; right: 24px; z-index: 10000;
    display: flex; flex-direction: column; gap: 12px; pointer-events: none;
}
.toast {
    background: var(--bg-card); border-radius: var(--radius-lg);
    padding: 16px 20px; box-shadow: var(--shadow-lg);
    display: flex; align-items: center; gap: 12px;
    min-width: 320px; max-width: 450px; pointer-events: auto;
    transform: translateX(120%); opacity: 0;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    border-left: 4px solid var(--primary);
}
.toast.show { transform: translateX(0); opacity: 1; }
.toast.toast-success { border-left-color: var(--success); }
.toast.toast-error { border-left-color: var(--danger); }
.toast-icon {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 1rem;
}
.toast-success .toast-icon { background: var(--green-light); color: var(--green); }
.toast-error .toast-icon { background: var(--danger-light); color: var(--danger); }
.toast-content { flex: 1; }
.toast-title { font-weight: 600; font-size: 0.92rem; color: var(--text-primary); }
.toast-close {
    background: none; border: none; color: var(--text-muted); cursor: pointer;
    padding: 4px; border-radius: var(--radius-sm); transition: all var(--transition);
}
.toast-close:hover { background: var(--border-light); color: var(--text-primary); }

/* ======================================= */
/*              MAIN LAYOUT                */
/* ======================================= */
.main-container {
    max-width: 1500px; margin: 0 auto; padding: 24px 32px;
    position: relative; z-index: 1;
}

/* ======================================= */
/*             PAGE HEADER                 */
/* ======================================= */
.page-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 28px; flex-wrap: wrap; gap: 20px;
    animation: fadeInUp 0.5s ease-out;
}
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.page-header-left {}
.page-title {
    font-size: 2.2rem; font-weight: 800; color: var(--text-primary);
    display: flex; align-items: center; gap: 16px;
    letter-spacing: -0.02em;
}
.page-title-icon {
    width: 54px; height: 54px; border-radius: var(--radius-lg); position: relative;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3);
    transition: transform var(--transition-spring);
}
.page-title-icon:hover { transform: scale(1.08) rotate(-5deg); }
.page-title-icon i { font-size: 1.3rem; color: #fff; }
.page-subtitle { color: var(--text-secondary); font-size: 0.95rem; font-weight: 400; margin-top: 4px; }

.btn-new-invoice {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 14px 28px; border: none; border-radius: var(--radius-md);
    font-size: 0.92rem; font-weight: 700; font-family: 'Inter', sans-serif;
    cursor: pointer; text-decoration: none; color: #fff;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    box-shadow: 0 4px 20px rgba(34, 197, 94, 0.35);
    transition: all var(--transition); position: relative; overflow: hidden;
}
.btn-new-invoice::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent);
    opacity: 0; transition: opacity var(--transition);
}
.btn-new-invoice:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(34, 197, 94, 0.45); }
.btn-new-invoice:hover::before { opacity: 1; }
.btn-new-invoice i { font-size: 0.85rem; }

/* ======================================= */
/*          SUMMARY CARDS                  */
/* ======================================= */
.summary-grid {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 20px; margin-bottom: 28px;
}
@media (max-width: 900px) { .summary-grid { grid-template-columns: 1fr; } }

.summary-card {
    background: var(--bg-card); border-radius: var(--radius-xl);
    padding: 24px 28px; box-shadow: var(--shadow);
    border: 1px solid var(--border-color); position: relative; overflow: hidden;
    display: flex; align-items: center; gap: 20px;
    cursor: default; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    opacity: 0; transform: translateY(30px);
    animation: cardSlideIn 0.5s ease-out forwards;
}
.summary-card:nth-child(1) { animation-delay: 0.1s; }
.summary-card:nth-child(2) { animation-delay: 0.18s; }
.summary-card:nth-child(3) { animation-delay: 0.26s; }
@keyframes cardSlideIn { to { opacity: 1; transform: translateY(0); } }

.summary-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--card-accent), var(--card-accent-end, var(--card-accent)));
    opacity: 0; transition: opacity var(--transition-slow);
}
.summary-card:hover::before { opacity: 1; }
.summary-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg), 0 0 0 1px var(--card-accent); }

.summary-card.total { --card-accent: var(--blue); --card-accent-end: var(--info); border-left: 4px solid var(--blue); }
.summary-card.paid { --card-accent: var(--green); --card-accent-end: var(--success); border-left: 4px solid var(--green); }
.summary-card.pending { --card-accent: var(--warning); --card-accent-end: #d97706; border-left: 4px solid var(--warning); }

.summary-card-icon {
    width: 52px; height: 52px; border-radius: var(--radius-lg);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
    transition: transform var(--transition-spring);
}
.summary-card:hover .summary-card-icon { transform: scale(1.1) rotate(-5deg); }

.summary-card.total .summary-card-icon { background: var(--blue-light); color: var(--blue); }
.summary-card.paid .summary-card-icon { background: var(--green-light); color: var(--green); }
.summary-card.pending .summary-card-icon { background: var(--warning-light); color: var(--warning); }

.summary-card-value {
    font-size: 1.85rem; font-weight: 800; letter-spacing: -0.5px; line-height: 1.2;
    color: var(--text-primary);
}
.summary-card-label {
    font-size: 0.82rem; font-weight: 500; color: var(--text-secondary);
    text-transform: uppercase; letter-spacing: 0.5px;
}

/* ======================================= */
/*            TABLE CARD                   */
/* ======================================= */
.table-card {
    background: var(--bg-card); border-radius: var(--radius-xl);
    box-shadow: var(--shadow); border: 1px solid var(--border-color);
    overflow: hidden; position: relative;
    animation: fadeInUp 0.5s ease-out 0.35s both;
}

/* Filters */
.filters-bar {
    padding: 20px 24px; display: flex; gap: 14px; flex-wrap: wrap;
    border-bottom: 1px solid var(--border-color);
}
.search-wrapper {
    position: relative; flex: 1; min-width: 250px;
}
.search-wrapper i {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); font-size: 0.9rem;
}
.search-input {
    width: 100%; padding: 12px 14px 12px 40px;
    border: 1px solid var(--border-color); border-radius: var(--radius);
    font-size: 0.9rem; font-family: 'Inter', sans-serif;
    color: var(--text-primary); background: #f8fafc;
    outline: none; transition: all var(--transition);
}
.search-input:focus {
    border-color: var(--primary); background: #fff;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
}
.search-input::placeholder { color: var(--text-muted); }

.status-select {
    padding: 12px 36px 12px 14px; border: 1px solid var(--border-color);
    border-radius: var(--radius); font-size: 0.9rem;
    font-family: 'Inter', sans-serif; color: var(--text-primary);
    background: #f8fafc; outline: none;
    transition: all var(--transition); cursor: pointer;
    appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center;
    min-width: 200px;
}
.status-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
}

/* Table */
.table-scroll { overflow-x: auto; }
.table-scroll::-webkit-scrollbar { height: 6px; }
.table-scroll::-webkit-scrollbar-track { background: transparent; }
.table-scroll::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.1); border-radius: 10px; }

.premium-table { width: 100%; border-collapse: collapse; }
.premium-table th {
    padding: 14px 24px; text-align: left; font-size: 0.75rem;
    font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;
    color: var(--text-secondary); background: #f8fafc;
    border-bottom: 2px solid var(--border-color);
}
.premium-table th.text-right { text-align: right; }
.premium-table th.text-center { text-align: center; }

.premium-table td {
    padding: 16px 24px; font-size: 0.9rem;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary); vertical-align: middle;
    transition: background-color var(--transition-fast);
}
.premium-table tbody tr:hover { background: var(--primary-light); }
.premium-table tbody tr:last-child td { border-bottom: none; }

.td-id { color: var(--text-muted); font-weight: 600; font-size: 0.85rem; }
.td-invoice-num { font-weight: 600; color: var(--text-primary); }
.td-date { color: var(--text-secondary); }
.td-supplier { font-weight: 500; }
.td-total { font-weight: 700; text-align: right; }
.td-actions { text-align: center; }

/* Status badges */
.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 14px; border-radius: 9999px;
    font-weight: 500; font-size: 0.875rem;
}
.status-Da-Verificare { background: #fef9c3; color: #ca8a04; }
.status-Registrata { background: #dbeafe; color: #2563eb; }
.status-Pagata { background: #dcfce7; color: #16a34a; }

/* Action buttons */
.actions-group { display: flex; align-items: center; justify-content: center; gap: 4px; }
.action-btn {
    width: 36px; height: 36px; border-radius: 50%; border: none;
    background: transparent; cursor: pointer; font-size: 0.95rem;
    color: var(--text-secondary); display: flex; align-items: center;
    justify-content: center; transition: all var(--transition);
    text-decoration: none;
}
.action-btn:hover { color: var(--text-primary); background: #e2e8f0; }
.action-btn.delete:hover { color: var(--danger); background: var(--danger-light); }

/* Empty state */
.empty-state {
    text-align: center; padding: 60px 20px; color: var(--text-secondary);
}
.empty-state i { font-size: 2.5rem; display: block; margin-bottom: 14px; color: var(--text-muted); }
.empty-state p { font-size: 0.95rem; }

/* Pagination */
.pagination-bar {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 24px; border-top: 1px solid var(--border-color);
}
.pagination-info { font-size: 0.875rem; color: var(--text-secondary); }
.pagination-buttons { display: flex; gap: 6px; }
.page-btn {
    padding: 8px 14px; border-radius: var(--radius);
    border: 1px solid var(--border-color); background: white;
    color: var(--text-primary); font-size: 0.82rem; font-weight: 500;
    font-family: 'Inter', sans-serif; cursor: pointer;
    transition: all var(--transition);
}
.page-btn:hover:not(:disabled) {
    background: var(--primary); color: #fff;
    border-color: var(--primary);
}
.page-btn.active {
    background: var(--primary); border-color: var(--primary); color: #fff;
}
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* ======================================= */
/*              MODALS                     */
/* ======================================= */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(30, 41, 59, 0.5);
    display: flex; justify-content: center; align-items: center;
    z-index: 5000; opacity: 0; visibility: hidden;
    transition: opacity 0.3s ease;
}
.modal-overlay.show { opacity: 1; visibility: visible; }

.modal-content {
    background: var(--bg-card); border-radius: var(--radius-xl);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    width: 90%; max-height: 90vh; overflow-y: auto;
    transform: scale(0.95); transition: transform 0.3s ease;
}
.modal-content::-webkit-scrollbar { width: 6px; }
.modal-content::-webkit-scrollbar-track { background: transparent; }
.modal-content::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.1); border-radius: 10px; }
.modal-overlay.show .modal-content { transform: scale(1); }

.modal-header {
    padding: 24px 28px; border-bottom: 1px solid var(--border-color);
    display: flex; align-items: center; justify-content: space-between;
}
.modal-header h2 {
    font-size: 1.3rem; font-weight: 700; color: var(--text-primary);
    display: flex; align-items: center; gap: 10px;
}
.modal-header h2 i { font-size: 1.1rem; }
.modal-close {
    width: 36px; height: 36px; border-radius: var(--radius);
    border: none; background: var(--border-light); cursor: pointer;
    color: var(--text-muted); font-size: 1rem;
    display: flex; align-items: center; justify-content: center;
    transition: all var(--transition);
}
.modal-close:hover { background: var(--danger-light); color: var(--danger); }

.modal-body { padding: 28px; }
.modal-footer {
    padding: 20px 28px; border-top: 1px solid var(--border-color);
    display: flex; justify-content: flex-end; gap: 12px;
}

/* Modal detail grid */
.detail-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px; margin-bottom: 28px;
}
.detail-label {
    font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.05em; color: var(--text-secondary); margin-bottom: 6px;
}
.detail-value { font-size: 1.05rem; font-weight: 600; color: var(--text-primary); }
.detail-value.bold { font-size: 1.2rem; font-weight: 800; color: var(--primary); }

/* Modal product table */
.modal-table-wrap {
    border: 1px solid var(--border-color); border-radius: var(--radius-md);
    overflow: hidden; margin-top: 8px;
}
.modal-table { width: 100%; border-collapse: collapse; }
.modal-table th {
    padding: 12px 16px; text-align: left; font-size: 0.72rem;
    font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;
    color: var(--text-secondary); background: #f8fafc;
    border-bottom: 1px solid var(--border-color);
}
.modal-table th.text-right { text-align: right; }
.modal-table td {
    padding: 12px 16px; font-size: 0.88rem; color: var(--text-primary);
    border-bottom: 1px solid var(--border-light);
}
.modal-table td.text-right { text-align: right; }
.modal-table tbody tr:last-child td { border-bottom: none; }
.modal-table tbody tr:hover { background: #f8fafc; }

/* Modal buttons */
.btn-modal {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px; border: none; border-radius: var(--radius);
    font-size: 0.88rem; font-weight: 600; font-family: 'Inter', sans-serif;
    cursor: pointer; transition: all var(--transition);
}
.btn-modal-secondary { background: #e2e8f0; color: #334155; }
.btn-modal-secondary:hover { background: #cbd5e1; }
.btn-modal-danger { background: var(--danger); color: #fff; }
.btn-modal-danger:hover { background: var(--danger-dark); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }

/* ======================================= */
/*           RESPONSIVE                    */
/* ======================================= */
@media (max-width: 768px) {
    .main-container { padding: 16px; }
    .page-header { flex-direction: column; align-items: flex-start; }
    .page-title { font-size: 1.6rem; }
    .summary-card-value { font-size: 1.4rem; }
    .filters-bar { flex-direction: column; }
    .status-select { min-width: unset; width: 100%; }
    .premium-table thead { display: none; }
    .premium-table tbody, .premium-table tr, .premium-table td { display: block; width: 100%; }
    .premium-table tr {
        margin-bottom: 12px; border: 1px solid var(--border-color);
        border-radius: var(--radius-md); padding: 16px;
        background: var(--bg-card);
    }
    .premium-table td {
        border: none; padding: 6px 0;
        display: flex; justify-content: space-between; align-items: center;
    }
    .premium-table td::before {
        content: attr(data-label);
        font-weight: 600; font-size: 0.75rem; text-transform: uppercase;
        letter-spacing: 0.05em; color: var(--text-secondary);
    }
}

@media print {
    .particles-container, .toast-container, .filters-bar, .pagination-bar { display: none !important; }
    body { padding-top: 0; background: #fff; }
    .table-card, .summary-card { box-shadow: none; border: 1px solid #ddd; }
}
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <!-- Floating particles -->
    <div class="particles-container">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <main class="main-container">

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-left">
                <h1 class="page-title">
                    <div class="page-title-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    Elenco Fatture
                </h1>
                <p class="page-subtitle">Gestione e monitoraggio fatture di acquisto</p>
            </div>
            <a href="gestione_fatture.php" class="btn-new-invoice">
                <i class="fas fa-plus"></i> Nuova Fattura
            </a>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card total">
                <div class="summary-card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div>
                    <div id="summaryTotalAmount" class="summary-card-value">0,00 &euro;</div>
                    <div class="summary-card-label">Importo Totale</div>
                </div>
            </div>
            <div class="summary-card paid">
                <div class="summary-card-icon"><i class="fas fa-circle-check"></i></div>
                <div>
                    <div id="summaryPaidAmount" class="summary-card-value">0,00 &euro;</div>
                    <div class="summary-card-label">Importo Pagato</div>
                </div>
            </div>
            <div class="summary-card pending">
                <div class="summary-card-icon"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <div id="summaryPendingAmount" class="summary-card-value">0,00 &euro;</div>
                    <div class="summary-card-label">Da Verificare</div>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="filters-bar">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Cerca per n&deg; fattura o fornitore...">
                </div>
                <select id="statusFilter" class="status-select">
                    <option value="">Tutti gli stati</option>
                    <option value="Da Verificare">Da Verificare</option>
                    <option value="Registrata">Registrata</option>
                    <option value="Pagata">Pagata</option>
                </select>
            </div>

            <div class="table-scroll">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Numero Fattura</th>
                            <th>Data</th>
                            <th>Fornitore</th>
                            <th>Stato</th>
                            <th class="text-right">Totale Lordo</th>
                            <th class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody id="invoicesTableBody">
                    </tbody>
                </table>
            </div>

            <div id="paginationContainer" class="pagination-bar"></div>
        </div>
    </main>

    <!-- Main Modal Overlay -->
    <div id="mainModal" class="modal-overlay">
        <!-- Delete Modal -->
        <div id="deleteInvoiceModalContent" class="hidden">
            <div class="modal-content" style="max-width: 480px;">
                <div class="modal-header">
                    <h2><i class="fas fa-triangle-exclamation" style="color: var(--danger);"></i> Conferma Eliminazione</h2>
                    <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p style="color: var(--text-secondary); line-height: 1.7;">Sei sicuro di voler eliminare la fattura <strong style="color: var(--text-primary);">#<span id="deleteInvoiceId"></span></strong>? L'azione &egrave; irreversibile.</p>
                </div>
                <div class="modal-footer">
                    <button class="btn-modal btn-modal-secondary" onclick="closeModal()">Annulla</button>
                    <button class="btn-modal btn-modal-danger" id="confirmDeleteInvoiceButton"><i class="fas fa-trash-alt"></i> Elimina</button>
                </div>
            </div>
        </div>

        <!-- View Modal -->
        <div id="viewInvoiceModalContent" class="hidden">
            <div class="modal-content" style="max-width: 900px;">
                <div class="modal-header">
                    <h2><i class="fas fa-file-invoice" style="color: var(--blue);"></i> Dettagli Fattura #<span id="viewInvoiceId"></span></h2>
                    <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <div class="detail-grid">
                        <div>
                            <div class="detail-label">Numero Fattura</div>
                            <div class="detail-value" id="detailInvoiceNumber"></div>
                        </div>
                        <div>
                            <div class="detail-label">Data</div>
                            <div class="detail-value" id="detailInvoiceDate"></div>
                        </div>
                        <div>
                            <div class="detail-label">Fornitore</div>
                            <div class="detail-value" id="detailSupplierName"></div>
                        </div>
                        <div>
                            <div class="detail-label">Stato</div>
                            <div class="detail-value" id="detailStatus"></div>
                        </div>
                        <div>
                            <div class="detail-label">Imponibile</div>
                            <div class="detail-value" id="detailTotalNet"></div>
                        </div>
                        <div>
                            <div class="detail-label">IVA</div>
                            <div class="detail-value" id="detailTotalVAT"></div>
                        </div>
                        <div>
                            <div class="detail-label">Totale Lordo</div>
                            <div class="detail-value bold" id="detailTotalGross"></div>
                        </div>
                        <div>
                            <div class="detail-label">Allegato</div>
                            <div class="detail-value" id="detailAttachment"></div>
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-boxes-stacked" style="color: var(--secondary);"></i> Dettaglio Prodotti
                        </h3>
                    </div>
                    <div class="modal-table-wrap">
                        <table class="modal-table">
                            <thead>
                                <tr>
                                    <th>Prodotto</th>
                                    <th class="text-right">Qt&agrave;</th>
                                    <th class="text-right">Prezzo Netto</th>
                                    <th class="text-right">IVA %</th>
                                    <th class="text-right">Totale Riga</th>
                                </tr>
                            </thead>
                            <tbody id="invoiceDetailsProductLines"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-modal btn-modal-secondary" onclick="closeModal()">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // ========== DATA FROM PHP ==========
    const allInvoices = <?php echo json_encode($fatture); ?>;

    let currentPage = 1;
    const rowsPerPage = 10;
    let filteredInvoices = [...allInvoices];

    // DOM Elements
    const tableBody = document.getElementById('invoicesTableBody');
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const paginationContainer = document.getElementById('paginationContainer');

    // ========== TOAST ==========
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        const iconClass = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
        const iconColor = type === 'success' ? 'var(--green)' : 'var(--danger)';
        const iconBg = type === 'success' ? 'var(--green-light)' : 'var(--danger-light)';
        toast.innerHTML = `
            <div class="toast-icon" style="background:${iconBg}; color:${iconColor};"><i class="fas ${iconClass}"></i></div>
            <div class="toast-content"><div class="toast-title">${message}</div></div>
            <button class="toast-close" onclick="this.parentElement.classList.remove('show'); setTimeout(()=>this.parentElement.remove(), 300);"><i class="fas fa-xmark"></i></button>
        `;
        container.appendChild(toast);
        requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }

    // ========== MODALS ==========
    const mainModal = document.getElementById('mainModal');
    const modalContents = {
        delete: document.getElementById('deleteInvoiceModalContent'),
        view: document.getElementById('viewInvoiceModalContent')
    };

    function openModal(type) {
        Object.values(modalContents).forEach(c => c.classList.add('hidden'));
        if (modalContents[type]) {
            modalContents[type].classList.remove('hidden');
            mainModal.classList.add('show');
        }
    }
    function closeModal() { mainModal.classList.remove('show'); }
    mainModal.addEventListener('click', e => { if (e.target === mainModal) closeModal(); });

    // ========== FORMATTING ==========
    function formatCurrency(value) {
        return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(parseFloat(value) || 0);
    }

    // ========== SUMMARY CARDS ==========
    function updateSummaryCards() {
        let totalAmount = 0, paidAmount = 0, pendingAmount = 0;
        allInvoices.forEach(inv => {
            const total = parseFloat(inv.totale_lordo) || 0;
            totalAmount += total;
            if (inv.stato === 'Pagata') paidAmount += total;
            else if (inv.stato === 'Da Verificare') pendingAmount += total;
        });
        animateValue('summaryTotalAmount', totalAmount);
        animateValue('summaryPaidAmount', paidAmount);
        animateValue('summaryPendingAmount', pendingAmount);
    }

    function animateValue(elId, target) {
        const el = document.getElementById(elId);
        const duration = 1200;
        const start = performance.now();
        function update(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 4);
            el.textContent = formatCurrency(target * eased);
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }

    // ========== TABLE RENDERING ==========
    function renderTable() {
        tableBody.innerHTML = '';
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const page = filteredInvoices.slice(start, end);

        if (page.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><i class="fas fa-file-circle-xmark"></i><p>Nessuna fattura trovata per i criteri selezionati.</p></div></td></tr>`;
            return;
        }

        page.forEach(f => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td data-label="ID:" class="td-id">${f.id}</td>
                <td data-label="N. Fattura:" class="td-invoice-num">${f.numero_fattura}</td>
                <td data-label="Data:" class="td-date">${new Date(f.data_fattura).toLocaleDateString('it-IT')}</td>
                <td data-label="Fornitore:" class="td-supplier">${f.nome_fornitore}</td>
                <td data-label="Stato:">
                    <span class="status-badge status-${f.stato.replace(' ', '-')}">${f.stato}</span>
                </td>
                <td data-label="Totale:" class="td-total">${formatCurrency(f.totale_lordo)}</td>
                <td data-label="Azioni:" class="td-actions">
                    <div class="actions-group">
                        <button class="action-btn" title="Visualizza" data-action="view" data-id="${f.id}"><i class="fas fa-eye"></i></button>
                        <a href="gestione_fatture.php?id=${f.id}" class="action-btn" title="Modifica"><i class="fas fa-pen-to-square"></i></a>
                        <button class="action-btn delete" title="Elimina" data-action="delete" data-id="${f.id}"><i class="fas fa-trash-can"></i></button>
                    </div>
                </td>
            `;
            tableBody.appendChild(row);
        });
    }

    // ========== PAGINATION ==========
    function renderPagination() {
        const totalPages = Math.ceil(filteredInvoices.length / rowsPerPage);
        paginationContainer.innerHTML = '';
        if (totalPages <= 1 && filteredInvoices.length <= rowsPerPage) {
            if (filteredInvoices.length > 0) {
                paginationContainer.innerHTML = `<div class="pagination-info">Mostrando ${filteredInvoices.length} risultat${filteredInvoices.length === 1 ? 'o' : 'i'}</div><div></div>`;
            }
            return;
        }

        const startItem = filteredInvoices.length > 0 ? (currentPage - 1) * rowsPerPage + 1 : 0;
        const endItem = Math.min(currentPage * rowsPerPage, filteredInvoices.length);

        let html = `<div class="pagination-info">Mostrando ${startItem}-${endItem} di ${filteredInvoices.length} risultati</div>`;
        html += '<div class="pagination-buttons">';
        html += `<button class="page-btn" onclick="changePage(currentPage - 1)" ${currentPage === 1 ? 'disabled' : ''}>Precedente</button>`;
        for (let i = 1; i <= totalPages; i++) {
            html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
        }
        html += `<button class="page-btn" onclick="changePage(currentPage + 1)" ${currentPage === totalPages ? 'disabled' : ''}>Successivo</button>`;
        html += '</div>';
        paginationContainer.innerHTML = html;
    }

    function changePage(page) {
        const totalPages = Math.ceil(filteredInvoices.length / rowsPerPage);
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        renderTable();
        renderPagination();
    }

    // ========== FILTERS ==========
    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase();
        const status = statusFilter.value;
        filteredInvoices = allInvoices.filter(inv => {
            const matchesSearch = inv.numero_fattura.toLowerCase().includes(searchTerm) || inv.nome_fornitore.toLowerCase().includes(searchTerm);
            const matchesStatus = status === '' || inv.stato === status;
            return matchesSearch && matchesStatus;
        });
        currentPage = 1;
        renderTable();
        renderPagination();
    }

    searchInput.addEventListener('input', applyFilters);
    statusFilter.addEventListener('change', applyFilters);

    // ========== TABLE ACTIONS ==========
    tableBody.addEventListener('click', async (event) => {
        const button = event.target.closest('.action-btn');
        if (!button || button.tagName === 'A') return;

        const action = button.dataset.action;
        const id = button.dataset.id;

        if (action === 'delete') {
            document.getElementById('deleteInvoiceId').textContent = id;
            document.getElementById('confirmDeleteInvoiceButton').onclick = () => confirmDelete(id);
            openModal('delete');
        }

        if (action === 'view') {
            try {
                const response = await fetch(`api-php-inventario.php?action=fetch_invoice_details&id=${id}`);
                if (!response.ok) throw new Error('Network response was not ok');
                const result = await response.json();

                if (result.success) {
                    const { invoice, details } = result;
                    document.getElementById('viewInvoiceId').textContent = invoice.id;
                    document.getElementById('detailInvoiceNumber').textContent = invoice.numero_fattura;
                    document.getElementById('detailInvoiceDate').textContent = new Date(invoice.data_fattura).toLocaleDateString('it-IT');
                    document.getElementById('detailSupplierName').textContent = invoice.nome_fornitore;
                    document.getElementById('detailStatus').innerHTML = `<span class="status-badge status-${invoice.stato.replace(' ', '-')}">${invoice.stato}</span>`;
                    document.getElementById('detailTotalNet').textContent = formatCurrency(invoice.totale_imponibile);
                    document.getElementById('detailTotalVAT').textContent = formatCurrency(invoice.totale_iva);
                    document.getElementById('detailTotalGross').textContent = formatCurrency(invoice.totale_lordo);

                    if (invoice.allegato_url) {
                        document.getElementById('detailAttachment').innerHTML = `<a href="${invoice.allegato_url}" target="_blank" style="color: var(--blue); text-decoration: none; font-weight: 600;">Visualizza file <i class="fas fa-external-link-alt" style="margin-left: 4px; font-size: 0.8rem;"></i></a>`;
                    } else {
                        document.getElementById('detailAttachment').textContent = 'Nessun allegato';
                        document.getElementById('detailAttachment').style.color = 'var(--text-muted)';
                    }

                    const productLinesBody = document.getElementById('invoiceDetailsProductLines');
                    productLinesBody.innerHTML = '';
                    if (details.length > 0) {
                        details.forEach(p => {
                            productLinesBody.innerHTML += `
                                <tr>
                                    <td>${p.descrizione_prodotto}</td>
                                    <td class="text-right">${p.quantita} ${p.unita_misura}</td>
                                    <td class="text-right">${formatCurrency(p.prezzo_unitario_netto)}</td>
                                    <td class="text-right">${p.iva_percentuale}%</td>
                                    <td class="text-right" style="font-weight: 700;">${formatCurrency(p.totale_riga_lordo)}</td>
                                </tr>`;
                        });
                    } else {
                        productLinesBody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 24px; color: var(--text-muted);">Nessun dettaglio prodotto trovato.</td></tr>';
                    }
                    openModal('view');
                } else {
                    showToast(result.message || 'Errore nel caricamento dei dettagli.', 'error');
                }
            } catch (error) {
                showToast('Errore di connessione con il server.', 'error');
                console.error('Fetch error:', error);
            }
        }
    });

    async function confirmDelete(id) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete_invoice');
            formData.append('id', id);
            const response = await fetch('api-php-inventario.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                showToast('Fattura eliminata con successo.', 'success');
                const index = allInvoices.findIndex(inv => inv.id == id);
                if (index > -1) allInvoices.splice(index, 1);
                applyFilters();
                updateSummaryCards();
            } else {
                showToast(result.message || 'Impossibile eliminare la fattura.', 'error');
            }
        } catch (error) {
            showToast('Errore di connessione durante l\'eliminazione.', 'error');
            console.error('Delete error:', error);
        } finally {
            closeModal();
        }
    }

    // ========== INIT ==========
    document.addEventListener('DOMContentLoaded', () => {
        updateSummaryCards();
        applyFilters();
        <?php echo $message; ?>
    });
    </script>
</body>
</html>
