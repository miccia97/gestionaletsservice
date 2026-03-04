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
    // Passa il messaggio a JavaScript per mostrarlo come "toast"
    $message = "<script>document.addEventListener('DOMContentLoaded', function() { showToast('" . addslashes($sessionMessage) . "', " . ($sessionIsError ? "'error'" : "'success'") . "); });</script>";
    unset($_SESSION['message']);
    unset($_SESSION['isError']);
}


// --- LOGICA PER RECUPERARE LE FATTURE (LISTA) ---
$fatture = [];
try {
    // Query per recuperare tutte le fatture e le informazioni sul fornitore
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
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
/* ======================================= */
/*        PREMIUM DESIGN SYSTEM            */
/* ======================================= */
:root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --primary-light: rgba(99, 102, 241, 0.12);
    --primary-glow: rgba(99, 102, 241, 0.35);
    --blue: #3b82f6;
    --blue-light: rgba(59, 130, 246, 0.12);
    --green: #22c55e;
    --green-dark: #16a34a;
    --green-light: rgba(34, 197, 94, 0.12);
    --secondary: #8b5cf6;
    --secondary-light: rgba(139, 92, 246, 0.12);
    --warning: #f59e0b;
    --warning-light: rgba(245, 158, 11, 0.12);
    --danger: #ef4444;
    --danger-dark: #dc2626;
    --danger-light: rgba(239, 68, 68, 0.12);
    --cyan: #06b6d4;
    --cyan-light: rgba(6, 182, 212, 0.12);
    --bg-page: #0f172a;
    --bg-card: rgba(30, 41, 59, 0.7);
    --bg-card-solid: #1e293b;
    --bg-elevated: rgba(51, 65, 85, 0.5);
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
    --border-color: rgba(148, 163, 184, 0.12);
    --border-light: rgba(148, 163, 184, 0.06);
    --glass-border: rgba(148, 163, 184, 0.1);
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.2);
    --shadow: 0 4px 16px rgba(0,0,0,0.25);
    --shadow-md: 0 8px 30px rgba(0,0,0,0.3);
    --shadow-lg: 0 20px 50px rgba(0,0,0,0.4);
    --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition: 250ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-slow: 400ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-spring: 600ms cubic-bezier(0.34, 1.56, 0.64, 1);
    --radius-sm: 0.5rem;
    --radius: 0.75rem;
    --radius-md: 1rem;
    --radius-lg: 1.25rem;
    --radius-xl: 1.5rem;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: var(--bg-page);
    min-height: 100vh; color: var(--text-primary);
    padding-top: 80px; line-height: 1.6; overflow-x: hidden;
}

/* ======================================= */
/*           FLOATING PARTICLES            */
/* ======================================= */
.particles-container { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
.particle {
    position: absolute; border-radius: 50%; opacity: 0;
    animation: floatParticle linear infinite;
}
@keyframes floatParticle {
    0% { opacity: 0; transform: translateY(100vh) scale(0); }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { opacity: 0; transform: translateY(-10vh) scale(1); }
}

/* ======================================= */
/*              TOAST SYSTEM               */
/* ======================================= */
.toast-container {
    position: fixed; top: 100px; right: 24px; z-index: 10000;
    display: flex; flex-direction: column; gap: 12px; pointer-events: none;
}
.toast {
    min-width: 340px; padding: 16px 20px; border-radius: var(--radius-lg);
    color: #fff; display: flex; align-items: center; gap: 12px;
    pointer-events: auto; backdrop-filter: blur(20px);
    box-shadow: 0 15px 50px rgba(0,0,0,0.3);
    opacity: 0; transform: translateX(120px);
    animation: toastIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards, toastOut 0.4s 4s forwards;
    font-weight: 600; font-size: 0.88rem;
}
.toast.success { background: linear-gradient(135deg, rgba(16, 185, 129, 0.9), rgba(5, 150, 105, 0.9)); border: 1px solid rgba(52, 211, 153, 0.3); }
.toast.error { background: linear-gradient(135deg, rgba(239, 68, 68, 0.9), rgba(220, 38, 38, 0.9)); border: 1px solid rgba(252, 165, 165, 0.3); }
.toast i { font-size: 1.1rem; }
@keyframes toastIn { to { opacity: 1; transform: translateX(0); } }
@keyframes toastOut { from { opacity: 1; } to { opacity: 0; transform: translateX(120px); } }

/* ======================================= */
/*              MAIN LAYOUT                */
/* ======================================= */
.main-container {
    max-width: 1500px; margin: 0 auto; padding: 0 28px 80px;
    position: relative; z-index: 1;
}

/* ======================================= */
/*             PAGE HEADER                 */
/* ======================================= */
.page-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 32px; flex-wrap: wrap; gap: 20px;
}
.page-header-left { display: flex; flex-direction: column; gap: 4px; }
.page-title {
    font-size: 2.2rem; font-weight: 900; color: #fff;
    display: flex; align-items: center; gap: 16px;
    letter-spacing: -0.5px;
}
.page-title-icon {
    width: 54px; height: 54px; border-radius: 16px; position: relative;
    background: linear-gradient(135deg, var(--green), var(--green-dark));
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 10px 30px rgba(34, 197, 94, 0.3);
}
.page-title-icon i { font-size: 1.35rem; color: #fff; }
.page-subtitle { color: var(--text-secondary); font-size: 0.92rem; font-weight: 400; margin-top: 4px; }

.btn-new-invoice {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 14px 28px; border: none; border-radius: var(--radius-md);
    font-size: 0.92rem; font-weight: 700; font-family: 'Inter', sans-serif;
    cursor: pointer; text-decoration: none; color: #fff;
    background: linear-gradient(135deg, var(--green), var(--green-dark));
    box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4);
    transition: all var(--transition); position: relative; overflow: hidden;
}
.btn-new-invoice::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
    opacity: 0; transition: opacity var(--transition);
}
.btn-new-invoice:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(34, 197, 94, 0.5); }
.btn-new-invoice:hover::before { opacity: 1; }
.btn-new-invoice i { font-size: 0.9rem; }

/* ======================================= */
/*          SUMMARY CARDS                  */
/* ======================================= */
.summary-grid {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 20px; margin-bottom: 32px;
}
@media (max-width: 900px) { .summary-grid { grid-template-columns: 1fr; } }

.summary-card {
    background: var(--bg-card); backdrop-filter: blur(20px);
    border-radius: var(--radius-xl); padding: 24px 28px;
    border: 1px solid var(--glass-border); position: relative; overflow: hidden;
    display: flex; align-items: center; gap: 20px;
    transition: all var(--transition); cursor: default;
}
.summary-card::before {
    content: ''; position: absolute; top: 0; left: 0; bottom: 0; width: 4px;
}
.summary-card::after {
    content: ''; position: absolute; top: -30px; right: -30px;
    width: 100px; height: 100px; border-radius: 50%; opacity: 0.06;
    transition: all var(--transition);
}
.summary-card:hover { transform: translateY(-4px); }
.summary-card:hover::after { opacity: 0.1; transform: scale(1.3); }

.summary-card.total::before { background: linear-gradient(180deg, var(--blue), var(--primary)); }
.summary-card.total::after { background: var(--blue); }
.summary-card.paid::before { background: linear-gradient(180deg, var(--green), #059669); }
.summary-card.paid::after { background: var(--green); }
.summary-card.pending::before { background: linear-gradient(180deg, var(--warning), #d97706); }
.summary-card.pending::after { background: var(--warning); }

.summary-card-icon {
    width: 52px; height: 52px; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.summary-card.total .summary-card-icon { background: var(--blue-light); color: #60a5fa; }
.summary-card.paid .summary-card-icon { background: var(--green-light); color: #4ade80; }
.summary-card.pending .summary-card-icon { background: var(--warning-light); color: #fbbf24; }

.summary-card:hover .summary-card-icon { transform: scale(1.1); }
.summary-card-icon { transition: transform var(--transition-spring); }

.summary-card-value {
    font-size: 1.8rem; font-weight: 800; letter-spacing: -0.5px; line-height: 1.2;
}
.summary-card.total .summary-card-value { color: #60a5fa; }
.summary-card.paid .summary-card-value { color: #4ade80; }
.summary-card.pending .summary-card-value { color: #fbbf24; }
.summary-card-label { font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; }

/* ======================================= */
/*            TABLE CARD                   */
/* ======================================= */
.table-card {
    background: var(--bg-card); backdrop-filter: blur(20px);
    border-radius: var(--radius-xl); border: 1px solid var(--glass-border);
    overflow: hidden; position: relative;
}
.table-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.4), rgba(139, 92, 246, 0.4), transparent);
}

/* Filters */
.filters-bar {
    padding: 24px 28px; display: flex; gap: 14px; flex-wrap: wrap;
    border-bottom: 1px solid var(--border-light);
}
.search-wrapper {
    position: relative; flex: 1; min-width: 250px;
}
.search-wrapper i {
    position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); font-size: 0.9rem;
}
.search-input {
    width: 100%; padding: 13px 16px 13px 44px;
    border: 2px solid var(--border-color); border-radius: var(--radius-md);
    font-size: 0.9rem; font-family: 'Inter', sans-serif;
    color: var(--text-primary); background: rgba(15, 23, 42, 0.5);
    outline: none; transition: all var(--transition);
}
.search-input:focus {
    border-color: var(--primary); background: rgba(15, 23, 42, 0.8);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
}
.search-input::placeholder { color: var(--text-muted); }

.status-select {
    padding: 13px 40px 13px 16px; border: 2px solid var(--border-color);
    border-radius: var(--radius-md); font-size: 0.9rem;
    font-family: 'Inter', sans-serif; color: var(--text-primary);
    background: rgba(15, 23, 42, 0.5); outline: none;
    transition: all var(--transition); cursor: pointer;
    appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 14px center;
    min-width: 200px;
}
.status-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
}
.status-select option { background: #1e293b; color: var(--text-primary); }

/* Table */
.table-scroll { overflow-x: auto; }
.table-scroll::-webkit-scrollbar { height: 6px; }
.table-scroll::-webkit-scrollbar-track { background: transparent; }
.table-scroll::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.2); border-radius: 10px; }

.premium-table { width: 100%; border-collapse: collapse; }
.premium-table thead { position: sticky; top: 0; z-index: 2; }
.premium-table th {
    padding: 14px 24px; text-align: left; font-size: 0.7rem;
    font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
    color: var(--text-muted); background: var(--bg-card-solid);
    border-bottom: 1px solid var(--border-color);
}
.premium-table th.text-right { text-align: right; }
.premium-table th.text-center { text-align: center; }

.premium-table td {
    padding: 16px 24px; font-size: 0.9rem;
    border-bottom: 1px solid var(--border-light);
    color: var(--text-primary); vertical-align: middle;
}
.premium-table tbody tr { transition: all var(--transition-fast); }
.premium-table tbody tr:hover { background: rgba(99, 102, 241, 0.04); }
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
    padding: 6px 14px; border-radius: 20px;
    font-weight: 600; font-size: 0.78rem; letter-spacing: 0.2px;
}
.status-Da-Verificare { background: var(--warning-light); color: #fbbf24; }
.status-Registrata { background: var(--blue-light); color: #60a5fa; }
.status-Pagata { background: var(--green-light); color: #4ade80; }

/* Action buttons */
.actions-group { display: flex; align-items: center; justify-content: center; gap: 6px; }
.action-btn {
    width: 36px; height: 36px; border-radius: 10px; border: none;
    background: transparent; cursor: pointer; font-size: 0.9rem;
    color: var(--text-muted); display: flex; align-items: center;
    justify-content: center; transition: all var(--transition);
    text-decoration: none;
}
.action-btn:hover { color: var(--primary); background: var(--primary-light); }
.action-btn.delete:hover { color: var(--danger); background: var(--danger-light); }
.action-btn.edit:hover { color: var(--warning); background: var(--warning-light); }

/* Empty state */
.empty-state {
    text-align: center; padding: 60px 20px; color: var(--text-muted);
}
.empty-state i { font-size: 3rem; display: block; margin-bottom: 16px; opacity: 0.3; }
.empty-state p { font-size: 0.95rem; }

/* Pagination */
.pagination-bar {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 28px; border-top: 1px solid var(--border-light);
}
.pagination-info { font-size: 0.82rem; color: var(--text-muted); font-weight: 500; }
.pagination-buttons { display: flex; gap: 6px; }
.page-btn {
    padding: 8px 14px; border-radius: var(--radius-sm);
    border: 2px solid var(--border-color); background: transparent;
    color: var(--text-secondary); font-size: 0.82rem; font-weight: 600;
    font-family: 'Inter', sans-serif; cursor: pointer;
    transition: all var(--transition);
}
.page-btn:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
.page-btn.active { background: var(--primary); border-color: var(--primary); color: #fff; }
.page-btn:disabled { opacity: 0.35; cursor: not-allowed; }

/* ======================================= */
/*              MODALS                     */
/* ======================================= */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(8px);
    display: flex; justify-content: center; align-items: center;
    z-index: 5000; opacity: 0; visibility: hidden;
    transition: all 0.3s ease;
}
.modal-overlay.show { opacity: 1; visibility: visible; }

.modal-content {
    background: var(--bg-card-solid); border: 1px solid var(--glass-border);
    border-radius: var(--radius-xl); width: 90%;
    max-height: 90vh; overflow-y: auto;
    transform: scale(0.92) translateY(20px);
    transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 25px 60px rgba(0,0,0,0.5);
}
.modal-content::-webkit-scrollbar { width: 6px; }
.modal-content::-webkit-scrollbar-track { background: transparent; }
.modal-content::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.2); border-radius: 10px; }
.modal-overlay.show .modal-content { transform: scale(1) translateY(0); }

.modal-header {
    padding: 24px 28px; border-bottom: 1px solid var(--border-color);
    display: flex; align-items: center; justify-content: space-between;
}
.modal-header h2 { font-size: 1.3rem; font-weight: 700; color: var(--text-primary); }
.modal-close {
    width: 36px; height: 36px; border-radius: 10px; border: none;
    background: rgba(148, 163, 184, 0.1); cursor: pointer;
    color: var(--text-muted); font-size: 1.1rem; display: flex;
    align-items: center; justify-content: center;
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
.detail-item {}
.detail-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 6px; }
.detail-value { font-size: 1.05rem; font-weight: 600; color: var(--text-primary); }
.detail-value.bold { font-size: 1.2rem; font-weight: 800; color: #60a5fa; }

/* Modal product table */
.modal-table-wrap {
    border: 1px solid var(--border-color); border-radius: var(--radius);
    overflow: hidden; margin-top: 8px;
}
.modal-table { width: 100%; border-collapse: collapse; }
.modal-table th {
    padding: 12px 16px; text-align: left; font-size: 0.7rem;
    font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;
    color: var(--text-muted); background: rgba(15, 23, 42, 0.5);
    border-bottom: 1px solid var(--border-color);
}
.modal-table th.text-right { text-align: right; }
.modal-table td {
    padding: 12px 16px; font-size: 0.88rem; color: var(--text-primary);
    border-bottom: 1px solid var(--border-light);
}
.modal-table td.text-right { text-align: right; }
.modal-table tbody tr:last-child td { border-bottom: none; }
.modal-table tbody tr:hover { background: rgba(99, 102, 241, 0.04); }

/* Buttons for modals */
.btn-modal {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px; border: none; border-radius: var(--radius);
    font-size: 0.88rem; font-weight: 700; font-family: 'Inter', sans-serif;
    cursor: pointer; transition: all var(--transition);
}
.btn-modal-secondary {
    background: rgba(148, 163, 184, 0.15); color: var(--text-secondary);
    border: 2px solid var(--border-color);
}
.btn-modal-secondary:hover { border-color: var(--text-muted); color: var(--text-primary); }
.btn-modal-danger {
    background: linear-gradient(135deg, var(--danger), var(--danger-dark));
    color: #fff; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
}
.btn-modal-danger:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4); }

/* Hidden utility */
.hidden { display: none !important; }

/* ======================================= */
/*        ENTRANCE ANIMATION               */
/* ======================================= */
.fade-in {
    opacity: 0; transform: translateY(20px);
    animation: fadeSlideIn 0.5s ease forwards;
}
@keyframes fadeSlideIn {
    to { opacity: 1; transform: translateY(0); }
}
.fade-in:nth-child(1) { animation-delay: 0s; }
.fade-in:nth-child(2) { animation-delay: 0.08s; }
.fade-in:nth-child(3) { animation-delay: 0.16s; }

/* ======================================= */
/*           RESPONSIVE                    */
/* ======================================= */
@media (max-width: 768px) {
    .main-container { padding: 0 16px 50px; }
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
        font-weight: 700; font-size: 0.72rem; text-transform: uppercase;
        letter-spacing: 0.8px; color: var(--text-muted);
    }
}

@media print {
    .particles-container, .toast-container, .filters-bar, .pagination-bar { display: none !important; }
    body { padding-top: 0; background: #fff; color: #000; }
    .table-card, .summary-card, .modal-content { background: #fff; box-shadow: none; border: 1px solid #ddd; backdrop-filter: none; }
    .page-title, .summary-card-value, .summary-card-label, .premium-table td, .premium-table th { color: #000 !important; }
    .status-badge { border: 1px solid #ccc; }
}
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <!-- Floating particles -->
    <div class="particles-container" id="particles"></div>

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
            <div class="summary-card total fade-in">
                <div class="summary-card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div>
                    <div id="summaryTotalAmount" class="summary-card-value">0,00 &euro;</div>
                    <div class="summary-card-label">Importo Totale</div>
                </div>
            </div>
            <div class="summary-card paid fade-in">
                <div class="summary-card-icon"><i class="fas fa-circle-check"></i></div>
                <div>
                    <div id="summaryPaidAmount" class="summary-card-value">0,00 &euro;</div>
                    <div class="summary-card-label">Importo Pagato</div>
                </div>
            </div>
            <div class="summary-card pending fade-in">
                <div class="summary-card-icon"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <div id="summaryPendingAmount" class="summary-card-value">0,00 &euro;</div>
                    <div class="summary-card-label">Da Verificare</div>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="table-card fade-in" style="animation-delay: 0.24s;">
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
                    <h2><i class="fas fa-triangle-exclamation" style="color: var(--danger); margin-right: 10px;"></i>Conferma Eliminazione</h2>
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
                    <h2><i class="fas fa-file-invoice" style="color: var(--primary); margin-right: 10px;"></i>Dettagli Fattura #<span id="viewInvoiceId"></span></h2>
                    <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Numero Fattura</div>
                            <div class="detail-value" id="detailInvoiceNumber"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Data</div>
                            <div class="detail-value" id="detailInvoiceDate"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Fornitore</div>
                            <div class="detail-value" id="detailSupplierName"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Stato</div>
                            <div class="detail-value" id="detailStatus"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Imponibile</div>
                            <div class="detail-value" id="detailTotalNet"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">IVA</div>
                            <div class="detail-value" id="detailTotalVAT"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Totale Lordo</div>
                            <div class="detail-value bold" id="detailTotalGross"></div>
                        </div>
                        <div class="detail-item">
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

    // ========== PARTICLES ==========
    function initParticles() {
        const container = document.getElementById('particles');
        const colors = ['rgba(99, 102, 241, 0.3)', 'rgba(139, 92, 246, 0.25)', 'rgba(34, 197, 94, 0.2)', 'rgba(59, 130, 246, 0.2)'];
        for (let i = 0; i < 30; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            const size = Math.random() * 4 + 2;
            p.style.cssText = `
                width: ${size}px; height: ${size}px;
                left: ${Math.random() * 100}%;
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                animation-duration: ${Math.random() * 20 + 15}s;
                animation-delay: ${Math.random() * 15}s;
            `;
            container.appendChild(p);
        }
    }

    // ========== TOAST ==========
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
        toast.innerHTML = `<i class="fas ${icon}"></i><span>${message}</span>`;
        container.appendChild(toast);
        toast.addEventListener('animationend', (e) => { if (e.animationName === 'toastOut') toast.remove(); });
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
                        <a href="gestione_fatture.php?id=${f.id}" class="action-btn edit" title="Modifica"><i class="fas fa-pen-to-square"></i></a>
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
        html += `<button class="page-btn" onclick="changePage(currentPage - 1)" ${currentPage === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;
        for (let i = 1; i <= totalPages; i++) {
            html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
        }
        html += `<button class="page-btn" onclick="changePage(currentPage + 1)" ${currentPage === totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;
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
                        document.getElementById('detailAttachment').innerHTML = `<a href="${invoice.allegato_url}" target="_blank" style="color: #60a5fa; text-decoration: none; font-weight: 600;">Visualizza file <i class="fas fa-external-link-alt" style="margin-left: 4px; font-size: 0.8rem;"></i></a>`;
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
        initParticles();
        updateSummaryCards();
        applyFilters();
        <?php echo $message; ?>
    });
    </script>
</body>
</html>
