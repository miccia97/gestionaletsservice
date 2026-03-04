<?php
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Avvia la sessione PHP
session_start();

// Includi il file di connessione al database MySQL
if (!file_exists('db.php')) {
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: Il file db.php non è stato trovato!</div>";
    exit;
}
require_once 'db.php';

// Controlla subito se c'è stato un errore di connessione al database
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
    $message = "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: " . ($sessionIsError ? "'error'" : "'success'") . ",
                title: '" . addslashes($sessionMessage) . "',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true
            });
        });
    </script>";
    unset($_SESSION['message']);
    unset($_SESSION['isError']);
}

// --- LOGICA PER RECUPERARE I BUONI REGALO ---
$buoni_data = [];
try {
    $searchTerm = $_GET['search'] ?? '';
    $whereClause = '';
    $queryParams = [];
    $paramTypes = '';

    if (!empty($searchTerm)) {
        $whereClause = " WHERE nome LIKE ? OR destinatario LIKE ? OR note LIKE ? OR stato LIKE ?";
        $searchTermLike = '%' . $searchTerm . '%';
        $queryParams = array_fill(0, 4, $searchTermLike);
        $paramTypes = 'ssss';
    }

    $sql = "SELECT * FROM buoni_regalo" . $whereClause . " ORDER BY data_creazione DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new mysqli_sql_exception("Errore nella preparazione della query: " . $conn->error);
    }

    if (!empty($queryParams)) {
        $stmt->bind_param($paramTypes, ...$queryParams);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $buoni_data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Errore Database', 'Impossibile caricare i buoni regalo.', 'error'); });</script>";
    error_log("Errore Visualizza Buoni (SQL): " . $e->getMessage());
} catch (Exception $e) {
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Errore', 'Errore generico nel caricamento.', 'error'); });</script>";
    error_log("Errore Visualizza Buoni (Generale): " . $e->getMessage());
}

// Funzione helper per formattare la valuta
function formatCurrency($value) {
    return number_format($value, 2, ',', '.') . ' €';
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Buoni Regalo | TS Service</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
<style>
  * { transition: all 0.2s ease; }
  
  :root {
      --brand-green: #22c55e;
      --brand-green-dark: #16a34a;
      --brand-green-light: #f0fdf4;
      --brand-orange: #f97316;
      --brand-orange-light: #fff7ed;
      --brand-blue: #3b82f6;
      --brand-blue-light: #eff6ff;
      --brand-red: #ef4444;
      --brand-red-light: #fef2f2;
      --brand-purple: #8b5cf6;
      --brand-purple-light: #f5f3ff;
      --brand-yellow: #eab308;
      --brand-yellow-light: #fefce8;
      --brand-gray: #64748b;
      --brand-gray-light: #f1f5f9;
      --bg-page: linear-gradient(145deg, #f8fafc 0%, #eef2f7 100%);
      --text-primary: #1e293b;
      --text-secondary: #64748b;
      --border-color: #e2e8f0;
      --card-bg: #ffffff;
      --card-radius: 16px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.04);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.06);
      --shadow-lg: 0 12px 28px rgba(0,0,0,0.08);
      --shadow-glow: 0 8px 24px rgba(34, 197, 94, 0.15);
  }
  
  body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg-page);
      color: var(--text-primary);
      padding-top: 80px;
      min-height: 100vh;
  }
  
  .page-container {
      max-width: 1700px;
      margin: 1.5rem auto;
      padding: 0 1.5rem;
  }
  
  /* PAGE HEADER */
  .page-header { 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      margin-bottom: 1.75rem;
      padding: 0 0.5rem;
  }
  .page-header h1 { 
      font-size: 1.85rem; 
      font-weight: 800;
      color: var(--text-primary);
      letter-spacing: -0.5px;
  }
  
  /* BUTTON PRIMARY */
  .btn-primary {
      display: inline-flex; 
      align-items: center; 
      gap: 0.5rem; 
      padding: 0.85rem 1.75rem;
      border-radius: 12px; 
      font-weight: 600; 
      font-size: 0.95rem;
      cursor: pointer; 
      text-decoration: none;
      background: linear-gradient(135deg, var(--brand-green) 0%, var(--brand-green-dark) 100%);
      color: white; 
      box-shadow: var(--shadow-glow);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border: none;
  }
  .btn-primary:hover { 
      transform: translateY(-3px); 
      box-shadow: 0 12px 28px rgba(34, 197, 94, 0.3);
  }
  .btn-primary:active {
      transform: translateY(-1px);
  }
  
  /* SUMMARY CARDS */
  .summary-card-container { 
      display: grid; 
      grid-template-columns: repeat(4, 1fr); 
      gap: 1.25rem; 
      margin-bottom: 1.75rem; 
  }
  .summary-card {
      background-color: var(--card-bg); 
      border-radius: var(--card-radius); 
      box-shadow: var(--shadow-sm);
      padding: 1.25rem 1.5rem; 
      display: flex; 
      align-items: center; 
      gap: 1.25rem;
      border-left: 4px solid; 
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      cursor: pointer;
      user-select: none;
  }
  .summary-card::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 100px;
      height: 100px;
      border-radius: 50%;
      opacity: 0.05;
      transform: translate(30%, -30%);
  }
  .summary-card:hover { 
      transform: translateY(-4px); 
      box-shadow: var(--shadow-lg);
  }
  .summary-card .icon { 
      font-size: 1.35rem; 
      padding: 0.9rem; 
      border-radius: 14px; 
      display: flex; 
      align-items: center; 
      justify-content: center;
      position: relative;
      z-index: 1;
  }
  
  /* Card variants */
  .summary-card.total { border-color: var(--brand-green); }
  .summary-card.total::before { background: var(--brand-green); }
  .summary-card.total .icon { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); color: var(--brand-green); }
  
  .summary-card.active-status { border-color: var(--brand-blue); }
  .summary-card.active-status::before { background: var(--brand-blue); }
  .summary-card.active-status .icon { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: var(--brand-blue); }
  
  .summary-card.used-status { border-color: var(--brand-gray); }
  .summary-card.used-status::before { background: var(--brand-gray); }
  .summary-card.used-status .icon { background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); color: var(--brand-gray); }
  
  .summary-card.value-status { border-color: var(--brand-purple); }
  .summary-card.value-status::before { background: var(--brand-purple); }
  .summary-card.value-status .icon { background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); color: var(--brand-purple); }
  
  .summary-card .value { 
      font-size: 1.65rem; 
      font-weight: 800;
      letter-spacing: -0.5px;
      color: var(--text-primary);
  }
  .summary-card .label { 
      font-size: 0.8rem; 
      color: var(--text-secondary);
      font-weight: 500;
      margin-top: 2px;
  }
  
  .summary-card.active {
      box-shadow: 0 0 0 3px var(--brand-green), var(--shadow-lg) !important;
  }
  .summary-card.active-status.active { box-shadow: 0 0 0 3px var(--brand-blue), var(--shadow-lg) !important; }
  .summary-card.used-status.active { box-shadow: 0 0 0 3px var(--brand-gray), var(--shadow-lg) !important; }
  .summary-card.value-status.active { box-shadow: 0 0 0 3px var(--brand-purple), var(--shadow-lg) !important; }

  /* TABLE CARD */
  .table-main-card { 
      background-color: var(--card-bg); 
      border-radius: var(--card-radius); 
      box-shadow: var(--shadow-sm); 
      padding: 1.5rem;
      transition: all 0.3s ease;
  }
  .table-main-card:hover {
      box-shadow: var(--shadow-md);
  }
  
  /* FILTERS */
  .filters-container { 
      display: flex; 
      gap: 1rem; 
      margin-bottom: 1.25rem; 
  }
  .filters-container .search-wrapper { 
      position: relative; 
      flex-grow: 1;
  }
  .filters-container .search-icon { 
      position: absolute; 
      left: 1rem; 
      top: 50%; 
      transform: translateY(-50%); 
      color: var(--text-secondary);
      font-size: 0.9rem;
  }
  .filters-container input { 
      width: 100%; 
      padding: 0.85rem 1rem 0.85rem 2.75rem; 
      border-radius: 12px; 
      border: 2px solid var(--border-color); 
      background-color: #f8fafc;
      font-size: 0.95rem;
      font-weight: 500;
      transition: all 0.25s ease;
  }
  .filters-container input:hover {
      border-color: #cbd5e1;
      background: white;
  }
  .filters-container input:focus {
      border-color: var(--brand-green);
      background: white;
      box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
      outline: none;
  }
  .filters-container input::placeholder {
      color: #94a3b8;
  }
  
  /* FILTERS BAR */
  .filters-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
      margin-bottom: 1.25rem;
      padding: 1rem;
      background: #f8fafc;
      border-radius: 12px;
  }
  .filter-group {
      display: flex;
      align-items: center;
      gap: 8px;
  }
  .filter-group label {
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--text-secondary);
      text-transform: uppercase;
  }
  .filter-select {
      padding: 0.5rem 2rem 0.5rem 0.75rem;
      border: 2px solid var(--border-color);
      border-radius: 10px;
      font-size: 0.85rem;
      font-weight: 500;
      background: white;
      cursor: pointer;
      transition: all 0.2s ease;
  }
  .filter-select:hover { border-color: #cbd5e1; }
  .filter-select:focus { border-color: var(--brand-green); outline: none; }
  
  .filter-date {
      padding: 0.5rem 0.75rem;
      border: 2px solid var(--border-color);
      border-radius: 10px;
      font-size: 0.85rem;
      background: white;
  }
  .filter-date:focus { border-color: var(--brand-green); outline: none; }
  
  .btn-reset-filters {
      padding: 0.5rem 1rem;
      background: #f1f5f9;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text-secondary);
      cursor: pointer;
      transition: all 0.2s ease;
  }
  .btn-reset-filters:hover {
      background: #e2e8f0;
      color: var(--text-primary);
  }
  
  .btn-export {
      padding: 0.5rem 1rem;
      background: linear-gradient(135deg, var(--brand-blue), #2563eb);
      border: none;
      border-radius: 10px;
      font-size: 0.8rem;
      font-weight: 600;
      color: white;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s ease;
      margin-left: auto;
  }
  .btn-export:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  }
  
  /* TABLE */
  .buoni-table { 
      width: 100%; 
      border-collapse: separate;
      border-spacing: 0 8px;
  }
  .buoni-table thead th {
      padding: 0.9rem 1rem; 
      text-align: left; 
      background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
      color: #15803d; 
      font-weight: 700; 
      font-size: 0.7rem;
      text-transform: uppercase; 
      letter-spacing: 0.6px;
      border: none;
  }
  .buoni-table thead th:first-child { border-radius: 12px 0 0 12px; }
  .buoni-table thead th:last-child { border-radius: 0 12px 12px 0; }
  
  .buoni-table tbody tr {
      background: #fafafa;
      border-radius: 12px;
      transition: all 0.25s ease;
  }
  .buoni-table tbody tr:hover { 
      background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
      transform: scale(1.005);
      box-shadow: 0 4px 12px rgba(0,0,0,0.04);
  }
  .buoni-table tbody td { 
      padding: 1rem; 
      border: none;
      vertical-align: middle;
      font-size: 0.9rem;
  }
  .buoni-table tbody td:first-child { border-radius: 12px 0 0 12px; }
  .buoni-table tbody td:last-child { border-radius: 0 12px 12px 0; }
  
  /* STATUS BADGES */
  .status-badge { 
      display: inline-flex; 
      align-items: center; 
      gap: 0.4rem; 
      padding: 0.4rem 0.85rem; 
      border-radius: 20px; 
      font-weight: 600; 
      font-size: 0.75rem;
      letter-spacing: 0.2px;
  }
  .status-badge::before { 
      content: ''; 
      width: 7px; 
      height: 7px; 
      border-radius: 50%;
      animation: pulse 2s infinite;
  }
  @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
  }
  
  .status-attivo { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); color: #15803d; }
  .status-attivo::before { background-color: var(--brand-green); }
  .status-usato { background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); color: #475569; }
  .status-usato::before { background-color: var(--brand-gray); animation: none; }
  .status-scaduto { background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); color: #991b1b; }
  .status-scaduto::before { background-color: var(--brand-red); animation: none; }
  
  /* EXPIRY BADGES */
  .expiry-badge {
      font-size: 0.75rem;
      font-weight: 600;
      padding: 0.25rem 0.6rem;
      border-radius: 6px;
  }
  .expiry-ok { background: #f0fdf4; color: #16a34a; }
  .expiry-warning { background: #fefce8; color: #ca8a04; }
  .expiry-expired { background: #fef2f2; color: #dc2626; }
  .expiry-none { background: #f1f5f9; color: #94a3b8; }
  
  /* VALORE BADGE */
  .valore-display {
      font-weight: 700;
      font-size: 1rem;
      color: var(--brand-green-dark);
  }
  
  /* CODE DISPLAY */
  .code-display {
      font-family: 'Courier New', monospace;
      font-weight: 700;
      font-size: 0.9rem;
      color: var(--text-primary);
      background: #f1f5f9;
      padding: 0.3rem 0.6rem;
      border-radius: 6px;
      letter-spacing: 1px;
  }
  
  /* ACTIONS */
  .actions-wrapper { 
      position: relative;
      display: flex;
      justify-content: center;
  }
  .btn-actions { 
      background: #f1f5f9; 
      border: none; 
      cursor: pointer; 
      padding: 0.6rem; 
      font-size: 1rem; 
      color: var(--text-secondary); 
      border-radius: 10px; 
      transition: all 0.2s ease;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
  }
  .btn-actions:hover { 
      color: white;
      background: var(--brand-green);
      transform: scale(1.1);
  }
  
  .actions-popup {
      position: absolute;
      top: 100%;
      right: 0;
      margin-top: 8px;
      background: #ffffff;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
      border-radius: 14px; 
      width: 210px; 
      z-index: 9999;
      opacity: 0; 
      visibility: hidden; 
      transform: translateY(-10px);
      transition: all 0.2s ease;
      pointer-events: none;
      border: 1px solid #e2e8f0;
  }
  .actions-popup.show { 
      opacity: 1; 
      visibility: visible; 
      transform: translateY(0); 
      pointer-events: auto; 
  }
  .actions-popup ul { 
      list-style: none; 
      margin: 0; 
      padding: 0.5rem;
      background: #ffffff;
      border-radius: 14px;
  }
  .actions-popup li { 
      padding: 0.7rem 1rem; 
      cursor: pointer; 
      font-size: 0.85rem; 
      font-weight: 500; 
      color: #1e293b; 
      display: flex; 
      align-items: center; 
      gap: 0.75rem; 
      border-radius: 10px;
      transition: all 0.15s ease;
      background: #ffffff;
  }
  .actions-popup li:hover { 
      background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
      color: var(--brand-green-dark);
  }
  .actions-popup li.delete:hover { 
      background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
      color: var(--brand-red);
  }

  /* INLINE STATUS SELECT */
  .inline-status-select {
      appearance: none;
      border: none;
      background: transparent;
      padding: 0.4rem 1.5rem 0.4rem 0.75rem;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.75rem;
      cursor: pointer;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.5rem center;
      transition: all 0.2s ease;
  }
  .inline-status-select:hover {
      box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.3);
  }
  .inline-status-select.status-attivo { background-color: #f0fdf4; color: #15803d; }
  .inline-status-select.status-usato { background-color: #f1f5f9; color: #475569; }
  .inline-status-select.status-scaduto { background-color: #fef2f2; color: #991b1b; }

  /* MODAL */
  .modal-overlay {
      position: fixed; 
      top: 0; 
      left: 0; 
      width: 100%; 
      height: 100%;
      background-color: rgba(15, 23, 42, 0.5);
      backdrop-filter: blur(6px);
      display: flex;
      justify-content: center; 
      align-items: center; 
      z-index: 1000;
      opacity: 0; 
      visibility: hidden; 
      transition: all 0.35s ease;
      padding: 1rem;
  }
  .modal-overlay.show { opacity: 1; visibility: visible; }
  
  .modal-content {
      background-color: var(--card-bg); 
      padding: 0; 
      border-radius: 20px;
      box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
      max-width: 95%; 
      width: 650px;
      max-height: 90vh; 
      overflow: hidden;
      display: flex;
      flex-direction: column;
      position: relative;
      transform: translateY(30px) scale(0.9); 
      transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .modal-overlay.show .modal-content { 
      transform: translateY(0) scale(1); 
  }
  
  .modal-header { 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      padding: 1.5rem 2rem;
      background: linear-gradient(135deg, var(--brand-green) 0%, #10b981 100%);
      color: white;
      flex-shrink: 0;
      position: relative;
      overflow: hidden;
  }
  .modal-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
      animation: shimmer 2s infinite;
  }
  @keyframes shimmer {
      0% { transform: translateX(-100%); }
      100% { transform: translateX(100%); }
  }
  .modal-header h2 { 
      font-size: 1.25rem; 
      font-weight: 700;
      color: white;
      margin: 0;
      position: relative;
      z-index: 1;
  }
  .modal-close-button { 
      background: rgba(255,255,255,0.2); 
      border: none; 
      font-size: 1.4rem; 
      color: white; 
      cursor: pointer;
      width: 38px;
      height: 38px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.25s ease;
      line-height: 1;
      position: relative;
      z-index: 1;
  }
  .modal-close-button:hover {
      background: rgba(255,255,255,0.3);
      transform: rotate(90deg) scale(1.1);
  }
  
  .modal-body {
      padding: 1.5rem 2rem;
      overflow-y: auto;
      background: #f8fafc;
  }
  
  .form-grid { 
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.25rem;
  }
  .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
  .form-group.full-width { grid-column: 1 / -1; }
  
  .modal-body label {
      display: block;
      font-weight: 700;
      font-size: 0.7rem;
      color: var(--text-secondary);
      margin-bottom: 4px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
  }
  .modal-body input[type="text"],
  .modal-body input[type="number"],
  .modal-body input[type="date"],
  .modal-body select,
  .modal-body textarea {
      width: 100%;
      padding: 0.85rem 1rem;
      border: 2px solid var(--border-color);
      border-radius: 12px;
      font-size: 0.95rem;
      transition: all 0.25s ease;
      background: white;
      font-weight: 500;
  }
  .modal-body input:hover,
  .modal-body select:hover,
  .modal-body textarea:hover {
      border-color: #cbd5e1;
  }
  .modal-body input:focus,
  .modal-body select:focus,
  .modal-body textarea:focus {
      border-color: var(--brand-green);
      outline: none;
      box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
  }
  .modal-body input[readonly] {
      background-color: #f8f9fa;
      color: #6c757d;
      cursor: not-allowed;
  }
  
  .modal-footer { 
      display: flex; 
      justify-content: flex-end; 
      gap: 0.75rem; 
      border-top: 1px solid var(--border-color); 
      padding: 1.25rem 2rem;
      background: white;
      flex-shrink: 0;
  }
  .modal-footer .btn { 
      padding: 0.85rem 1.75rem; 
      border-radius: 12px; 
      font-weight: 600;
      font-size: 0.9rem;
      border: none;
      cursor: pointer;
      transition: all 0.25s ease;
  }
  .modal-footer .btn-cancel { 
      background-color: #f1f5f9; 
      color: #64748b;
      border: 2px solid #e2e8f0;
  }
  .modal-footer .btn-cancel:hover {
      background-color: #e2e8f0;
      transform: translateY(-2px);
  }
  .modal-footer .btn-save { 
      background: linear-gradient(135deg, var(--brand-green) 0%, var(--brand-green-dark) 100%);
      color: white; 
  }
  .modal-footer .btn-save:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
  }

  /* RESULTS COUNT */
  .results-info {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
      padding: 0 0.5rem;
  }
  .results-count {
      font-size: 0.9rem;
      color: var(--text-secondary);
  }
  .results-count strong {
      color: var(--text-primary);
      font-weight: 700;
  }
  
  /* PAGINATION */
  .pagination-container {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid var(--border-color);
  }
  .pagination-btn {
      min-width: 38px;
      height: 38px;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid var(--border-color);
      background: white;
      border-radius: 10px;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-secondary);
      cursor: pointer;
      transition: all 0.2s ease;
  }
  .pagination-btn:hover:not(:disabled) {
      border-color: var(--brand-green);
      color: var(--brand-green);
  }
  .pagination-btn.active {
      background: var(--brand-green);
      border-color: var(--brand-green);
      color: white;
  }
  .pagination-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
  }
  .pagination-info {
      font-size: 0.85rem;
      color: var(--text-secondary);
      margin: 0 1rem;
  }
  
  /* TOAST */
  .toast-container {
      position: fixed;
      top: 100px;
      right: 20px;
      z-index: 99999;
      display: flex;
      flex-direction: column;
      gap: 10px;
  }
  .toast {
      padding: 1rem 1.5rem;
      background: white;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 300px;
      transform: translateX(120%);
      transition: transform 0.3s ease;
      border-left: 4px solid;
  }
  .toast.show { transform: translateX(0); }
  .toast.success { border-color: var(--brand-green); }
  .toast.success .toast-icon { color: var(--brand-green); }
  .toast.error { border-color: var(--brand-red); }
  .toast.error .toast-icon { color: var(--brand-red); }
  .toast.info { border-color: var(--brand-blue); }
  .toast.info .toast-icon { color: var(--brand-blue); }
  .toast-icon { font-size: 1.25rem; }
  .toast-message { font-weight: 500; color: var(--text-primary); }
  .toast-close {
      margin-left: auto;
      background: none;
      border: none;
      color: var(--text-secondary);
      cursor: pointer;
      font-size: 1.2rem;
  }
  
  /* VALUE HIGHLIGHT */
  .value-highlight {
      font-size: 1.1rem;
      font-weight: 700;
      text-align: center;
      padding: 0.85rem 1rem;
      border: 2px solid var(--brand-green);
      border-radius: 12px;
      color: var(--brand-green-dark);
      background: linear-gradient(135deg, #f0fdf4, #dcfce7);
  }
  
  /* SCROLLBAR */
  ::-webkit-scrollbar { width: 8px; height: 8px; }
  ::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
  ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
  ::-webkit-scrollbar-thumb:hover { background: var(--brand-green); }
  
  /* ANIMATIONS */
  @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
  }
  .summary-card { animation: fadeInUp 0.5s ease-out forwards; }
  .summary-card:nth-child(1) { animation-delay: 0.1s; }
  .summary-card:nth-child(2) { animation-delay: 0.2s; }
  .summary-card:nth-child(3) { animation-delay: 0.3s; }
  .summary-card:nth-child(4) { animation-delay: 0.4s; }
  .table-main-card { animation: fadeInUp 0.5s ease-out 0.3s forwards; opacity: 0; }

  @media (max-width: 1200px) {
      .summary-card-container { grid-template-columns: repeat(2, 1fr); }
  }
  @media (max-width: 768px) {
      .summary-card-container { grid-template-columns: 1fr; }
      .page-container { padding: 0 1rem; }
      .page-header { flex-direction: column; gap: 1rem; align-items: flex-start; }
      .buoni-table thead { display: none; }
      .buoni-table tbody, .buoni-table tr, .buoni-table td { display: block; width: 100%; }
      .buoni-table tr { margin-bottom: 1rem; border: 1px solid var(--border-color); border-radius: var(--card-radius); padding: 1rem; }
      .buoni-table td { border: none; padding: 0.5rem 0; display: flex; justify-content: space-between; align-items: center; text-align: right; }
      .buoni-table td::before { content: attr(data-label); font-weight: 600; color: var(--text-secondary); text-align: left; }
      .form-grid { grid-template-columns: 1fr; }
      .filters-bar { flex-direction: column; align-items: stretch; }
  }
  
  @media print {
      body * { visibility: hidden; }
  }
</style>
</head>
<body>
<?php include 'header.php'; ?>
    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <div class="page-container">
        <div class="page-header">
            <h1>Dashboard Buoni Regalo</h1>
            <div style="display: flex; gap: 12px;">
                <button class="btn-export" onclick="exportToCSV()"><i class="fas fa-file-excel"></i> Esporta CSV</button>
                <a href="add_buono_regalo.php" class="btn-primary"><i class="fas fa-plus"></i> Nuovo Buono</a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-card-container">
            <div class="summary-card total" data-filter="" onclick="filterByCard(this)">
                <div class="icon"><i class="fas fa-gift"></i></div>
                <div>
                    <div id="summaryTotal" class="value">0</div>
                    <div class="label">Totale Buoni</div>
                </div>
            </div>
            <div class="summary-card active-status" data-filter="Attivo" onclick="filterByCard(this)">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div id="summaryAttivi" class="value">0</div>
                    <div class="label">Attivi</div>
                </div>
            </div>
            <div class="summary-card used-status" data-filter="Usato" onclick="filterByCard(this)">
                <div class="icon"><i class="fas fa-receipt"></i></div>
                <div>
                    <div id="summaryUsati" class="value">0</div>
                    <div class="label">Usati</div>
                </div>
            </div>
            <div class="summary-card value-status" data-filter="" onclick="filterByCard(this)">
                <div class="icon"><i class="fas fa-euro-sign"></i></div>
                <div>
                    <div id="summaryValoreTotale" class="value">0,00 €</div>
                    <div class="label">Valore Attivi</div>
                </div>
            </div>
        </div>

        <!-- Main Table Card -->
        <div class="table-main-card">
            <!-- Filters Bar -->
            <div class="filters-bar">
                <div class="filter-group">
                    <label>Stato</label>
                    <select id="filterStatus" class="filter-select" onchange="applyFilters()">
                        <option value="">Tutti</option>
                        <option value="Attivo">✅ Attivo</option>
                        <option value="Usato">📋 Usato</option>
                        <option value="Scaduto">❌ Scaduto</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Scadenza Da</label>
                    <input type="date" id="filterDateFrom" class="filter-date" onchange="applyFilters()">
                </div>
                <div class="filter-group">
                    <label>Scadenza A</label>
                    <input type="date" id="filterDateTo" class="filter-date" onchange="applyFilters()">
                </div>
                <button class="btn-reset-filters" onclick="resetFilters()"><i class="fas fa-times"></i> Reset</button>
            </div>
            
            <!-- Search -->
            <div class="filters-container">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Cerca per codice, destinatario, note...">
                </div>
            </div>
            
            <!-- Results Info -->
            <div class="results-info">
                <div class="results-count">
                    <strong id="resultsCount">0</strong> buoni trovati
                </div>
                <div>
                    <select id="itemsPerPage" class="filter-select" onchange="changeItemsPerPage()">
                        <option value="10">10 per pagina</option>
                        <option value="25" selected>25 per pagina</option>
                        <option value="50">50 per pagina</option>
                        <option value="100">100 per pagina</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div style="overflow-x: auto;">
                <table class="buoni-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Codice</th>
                            <th class="text-right">Valore</th>
                            <th>Destinatario</th>
                            <th>Data Creazione</th>
                            <th>Scadenza</th>
                            <th>Stato</th>
                            <th class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody id="buoniTableBody">
                        <!-- Righe popolate da JS -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pagination-container" id="paginationContainer"></div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="mainModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Modifica Buono Regalo</h2>
                <button class="modal-close-button" onclick="closeModal()">&times;</button>
            </div>
            <form id="editBuonoForm">
                <input type="hidden" id="editBuonoId" name="id">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="editBuonoValore">Valore Buono (€)</label>
                            <input type="number" id="editBuonoValore" name="valore" step="0.01" min="0" required class="value-highlight">
                        </div>
                        <div class="form-group">
                            <label for="editBuonoStato">Stato</label>
                            <select id="editBuonoStato" name="stato_buono">
                                <option value="Attivo">✅ Attivo</option>
                                <option value="Usato">📋 Usato</option>
                                <option value="Scaduto">❌ Scaduto</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label for="editBuonoCodice">Codice Buono</label>
                            <input type="text" id="editBuonoCodice" name="codice_buono" readonly>
                        </div>
                        <div class="form-group">
                            <label for="editBuonoDestinatario">Destinatario</label>
                            <input type="text" id="editBuonoDestinatario" name="destinatario" placeholder="Nome destinatario...">
                        </div>
                        <div class="form-group">
                            <label for="editBuonoScadenza">Data Scadenza</label>
                            <input type="date" id="editBuonoScadenza" name="data_scadenza">
                        </div>
                        <div class="form-group full-width">
                            <label for="editBuonoNote">Note</label>
                            <textarea id="editBuonoNote" name="mittente_note" rows="3" placeholder="Note aggiuntive..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeModal()">Annulla</button>
                    <button type="submit" class="btn btn-save"><i class="fas fa-save"></i> Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>

<script>
  // === DATA ===
  const allBuoni = <?php echo json_encode($buoni_data); ?>;
  let filteredBuoni = [...allBuoni];
  let currentPage = 1;
  let itemsPerPage = 25;

  const tableBody = document.getElementById('buoniTableBody');
  const searchInput = document.getElementById('searchInput');

  // === TOAST NOTIFICATIONS ===
  function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle' };
    toast.innerHTML = `
      <i class="fas ${icons[type]} toast-icon"></i>
      <span class="toast-message">${message}</span>
      <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  }

  // === FORMAT CURRENCY ===
  function formatCurrencyJS(value) {
    const numValue = parseFloat(value) || 0;
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(numValue);
  }

  // === EXPIRY HELPER ===
  function getExpiryInfo(dateStr) {
    if (!dateStr || dateStr === '0000-00-00' || dateStr === null) {
      return { text: 'Nessuna', class: 'expiry-none' };
    }
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const expiry = new Date(dateStr);
    expiry.setHours(0, 0, 0, 0);
    const diffDays = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));

    if (diffDays > 30) {
      return { text: `${diffDays}g rimanenti`, class: 'expiry-ok' };
    } else if (diffDays > 0) {
      return { text: `${diffDays}g rimanenti`, class: 'expiry-warning' };
    } else if (diffDays === 0) {
      return { text: 'Scade oggi!', class: 'expiry-warning' };
    } else {
      return { text: `Scaduto da ${Math.abs(diffDays)}g`, class: 'expiry-expired' };
    }
  }

  // === SUMMARY CARDS ===
  function updateSummaryCards() {
    let total = allBuoni.length;
    let attivi = 0, usati = 0, valoreTotaleAttivi = 0;

    allBuoni.forEach(b => {
      if (b.stato === 'Attivo') {
        attivi++;
        valoreTotaleAttivi += parseFloat(b.valore) || 0;
      }
      if (b.stato === 'Usato') usati++;
    });

    document.getElementById('summaryTotal').textContent = total;
    document.getElementById('summaryAttivi').textContent = attivi;
    document.getElementById('summaryUsati').textContent = usati;
    document.getElementById('summaryValoreTotale').textContent = formatCurrencyJS(valoreTotaleAttivi);
  }

  // === FILTER BY CARD CLICK ===
  window.filterByCard = function(card) {
    const filterValue = card.dataset.filter;
    
    document.querySelectorAll('.summary-card').forEach(c => c.classList.remove('active'));
    
    if (document.getElementById('filterStatus').value === filterValue) {
      document.getElementById('filterStatus').value = '';
    } else {
      card.classList.add('active');
      document.getElementById('filterStatus').value = filterValue;
    }
    
    applyFilters();
  }

  // === RESET FILTERS ===
  window.resetFilters = function() {
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('searchInput').value = '';
    document.querySelectorAll('.summary-card').forEach(c => c.classList.remove('active'));
    currentPage = 1;
    applyFilters();
    showToast('Filtri resettati', 'info');
  }

  // === CHANGE ITEMS PER PAGE ===
  window.changeItemsPerPage = function() {
    itemsPerPage = parseInt(document.getElementById('itemsPerPage').value);
    currentPage = 1;
    renderTable();
    renderPagination();
  }

  // === PAGINATION ===
  function renderPagination() {
    const container = document.getElementById('paginationContainer');
    const totalPages = Math.ceil(filteredBuoni.length / itemsPerPage);
    
    if (totalPages <= 1) {
      container.innerHTML = '';
      return;
    }
    
    let html = `
      <button class="pagination-btn" onclick="goToPage(1)" ${currentPage === 1 ? 'disabled' : ''}>
        <i class="fas fa-angle-double-left"></i>
      </button>
      <button class="pagination-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
        <i class="fas fa-angle-left"></i>
      </button>
    `;
    
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
      html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
    }
    
    html += `
      <button class="pagination-btn" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
        <i class="fas fa-angle-right"></i>
      </button>
      <button class="pagination-btn" onclick="goToPage(${totalPages})" ${currentPage === totalPages ? 'disabled' : ''}>
        <i class="fas fa-angle-double-right"></i>
      </button>
      <span class="pagination-info">Pagina ${currentPage} di ${totalPages}</span>
    `;
    
    container.innerHTML = html;
  }

  window.goToPage = function(page) {
    currentPage = page;
    renderTable();
    renderPagination();
    window.scrollTo({ top: document.querySelector('.table-main-card').offsetTop - 100, behavior: 'smooth' });
  }

  // === RENDER TABLE ===
  function renderTable() {
    tableBody.innerHTML = '';
    
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const paginatedBuoni = filteredBuoni.slice(start, end);
    
    document.getElementById('resultsCount').textContent = filteredBuoni.length;
    
    if (paginatedBuoni.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 3rem; color: #94a3b8; font-style: italic;">
        <i class="fas fa-gift" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; opacity: 0.3;"></i>
        Nessun buono regalo trovato.
      </td></tr>`;
      return;
    }
    
    paginatedBuoni.forEach(b => {
      const row = document.createElement('tr');
      const statusClass = 'status-' + (b.stato || '').toLowerCase();
      const expiryInfo = getExpiryInfo(b.data_scadenza);
      const dataCreazione = b.data_creazione ? new Date(b.data_creazione).toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';
      const dataScadenza = b.data_scadenza && b.data_scadenza !== '0000-00-00' ? new Date(b.data_scadenza).toLocaleDateString('it-IT') : '-';
      
      row.innerHTML = `
        <td data-label="ID:"><strong>#${b.id}</strong></td>
        <td data-label="Codice:"><span class="code-display">${b.nome || '-'}</span></td>
        <td data-label="Valore:" class="text-right"><span class="valore-display">${formatCurrencyJS(b.valore)}</span></td>
        <td data-label="Destinatario:">${b.destinatario || '<span style="color:#94a3b8">-</span>'}</td>
        <td data-label="Data Creazione:">${dataCreazione}</td>
        <td data-label="Scadenza:">
          <div>
            <div style="margin-bottom: 2px;">${dataScadenza}</div>
            <span class="expiry-badge ${expiryInfo.class}">${expiryInfo.text}</span>
          </div>
        </td>
        <td data-label="Stato:">
          <select class="inline-status-select ${statusClass}" onchange="updateStatusInline(${b.id}, this.value)" data-id="${b.id}">
            <option value="Attivo" ${b.stato === 'Attivo' ? 'selected' : ''}>✅ Attivo</option>
            <option value="Usato" ${b.stato === 'Usato' ? 'selected' : ''}>📋 Usato</option>
            <option value="Scaduto" ${b.stato === 'Scaduto' ? 'selected' : ''}>❌ Scaduto</option>
          </select>
        </td>
        <td class="text-center">
          <div class="actions-wrapper">
            <button class="btn-actions" data-id="${b.id}"><i class="fas fa-ellipsis-v"></i></button>
            <div class="actions-popup" id="popup-${b.id}">
              <ul>
                <li onclick="openEditModal(${b.id})"><i class="fas fa-edit fa-fw"></i> Modifica</li>
                <li onclick="window.open('stampa_buono.php?id=${b.id}','_blank')"><i class="fas fa-print fa-fw"></i> Stampa</li>
                <li class="delete" onclick="deleteBuono(${b.id})"><i class="fas fa-trash-alt fa-fw"></i> Elimina</li>
              </ul>
            </div>
          </div>
        </td>
      `;
      tableBody.appendChild(row);
    });
  }

  // === INLINE STATUS UPDATE ===
  window.updateStatusInline = async function(id, newStatus) {
    const select = document.querySelector(`select[data-id="${id}"]`);
    const oldClass = select.className.match(/status-[\w-]+/)?.[0] || '';
    const newClass = 'status-' + newStatus.toLowerCase();
    
    select.classList.remove(oldClass);
    select.classList.add(newClass);
    
    // Update local data
    const buono = allBuoni.find(b => b.id == id);
    if (buono) {
      buono.stato = newStatus;
      updateSummaryCards();
    }
    
    // Send to server
    const data = {
      id: id,
      valore: buono.valore,
      codice_buono: buono.nome,
      stato_buono: newStatus,
      destinatario: buono.destinatario || '',
      data_scadenza: buono.data_scadenza || '',
      mittente_note: buono.note || ''
    };
    
    try {
      const response = await fetch('update_buono.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      const result = await response.json();
      if (result.success) {
        showToast(`Stato aggiornato a "${newStatus}"`, 'success');
      } else {
        showToast('Errore durante l\'aggiornamento', 'error');
      }
    } catch (e) {
      showToast('Errore di rete', 'error');
    }
  }

  // === EXPORT CSV ===
  window.exportToCSV = function() {
    let csv = 'ID,Codice,Valore,Destinatario,Data Creazione,Data Scadenza,Stato\n';
    filteredBuoni.forEach(b => {
      csv += `${b.id},"${b.nome || ''}",${b.valore || 0},"${(b.destinatario || '').replace(/"/g, '""')}","${b.data_creazione || ''}","${b.data_scadenza || ''}","${b.stato || ''}"\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `buoni_regalo_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    
    showToast(`Esportati ${filteredBuoni.length} buoni regalo`, 'success');
  }

  // === APPLY FILTERS ===
  function applyFilters() {
    const searchTerm = searchInput.value.toLowerCase();
    const statusFilter = document.getElementById('filterStatus').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    
    filteredBuoni = allBuoni.filter(b => {
      // Search
      const matchesSearch = !searchTerm || 
        (b.nome || '').toLowerCase().includes(searchTerm) ||
        (b.destinatario || '').toLowerCase().includes(searchTerm) ||
        (b.note || '').toLowerCase().includes(searchTerm) ||
        b.id.toString().includes(searchTerm);
      
      // Status
      const matchesStatus = !statusFilter || b.stato === statusFilter;
      
      // Date range (on scadenza)
      let matchesDateFrom = true, matchesDateTo = true;
      if (b.data_scadenza && b.data_scadenza !== '0000-00-00') {
        const scadenza = new Date(b.data_scadenza);
        if (dateFrom) matchesDateFrom = scadenza >= new Date(dateFrom);
        if (dateTo) matchesDateTo = scadenza <= new Date(dateTo + 'T23:59:59');
      } else {
        // Se non ha scadenza, non filtrare per data
        if (dateFrom || dateTo) return false;
      }
      
      return matchesSearch && matchesStatus && matchesDateFrom && matchesDateTo;
    });
    
    currentPage = 1;
    renderTable();
    renderPagination();
  }
  
  searchInput.addEventListener('input', applyFilters);

  // === ACTIONS POPUP ===
  document.addEventListener('click', (e) => {
    const activePopup = document.querySelector('.actions-popup.show');
    
    document.querySelectorAll('.buoni-table tbody tr').forEach(row => {
      row.style.position = '';
      row.style.zIndex = '';
    });
    
    if (e.target.closest('.btn-actions')) {
      const button = e.target.closest('.btn-actions');
      const id = button.dataset.id;
      const popup = document.getElementById(`popup-${id}`);
      
      if (activePopup && activePopup !== popup) activePopup.classList.remove('show');
      
      const row = button.closest('tr');
      if (row) {
        row.style.position = 'relative';
        row.style.zIndex = '100';
      }
      
      popup.classList.toggle('show');
    } else if (activePopup && !e.target.closest('.actions-popup')) {
      activePopup.classList.remove('show');
    }
  });

  // === MODAL ===
  const mainModal = document.getElementById('mainModal');
  
  function openModal() {
    mainModal.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  
  function closeModal() {
    mainModal.classList.remove('show');
    document.body.style.overflow = '';
  }
  
  mainModal.addEventListener('click', e => { if (e.target === mainModal) closeModal(); });

  // === OPEN EDIT MODAL ===
  window.openEditModal = function(id) {
    const buono = allBuoni.find(b => b.id == id);
    if (!buono) return;
    
    document.getElementById('editBuonoId').value = buono.id;
    document.getElementById('editBuonoValore').value = parseFloat(buono.valore || 0).toFixed(2);
    document.getElementById('editBuonoCodice').value = buono.nome || '';
    document.getElementById('editBuonoDestinatario').value = buono.destinatario || '';
    document.getElementById('editBuonoScadenza').value = (buono.data_scadenza && buono.data_scadenza !== '0000-00-00') ? buono.data_scadenza : '';
    document.getElementById('editBuonoNote').value = buono.note || '';
    document.getElementById('editBuonoStato').value = buono.stato || 'Attivo';
    
    openModal();
  }

  // === SAVE EDIT ===
  document.getElementById('editBuonoForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    if (!data.valore || parseFloat(data.valore) <= 0) {
      Swal.fire('Attenzione', 'Il valore del buono deve essere maggiore di zero.', 'warning');
      return;
    }

    try {
      const response = await fetch('update_buono.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      const result = await response.json();
      
      if (response.ok && result.success) {
        // Update local data
        const buono = allBuoni.find(b => b.id == data.id);
        if (buono) {
          buono.valore = data.valore;
          buono.nome = data.codice_buono;
          buono.destinatario = data.destinatario;
          buono.data_scadenza = data.data_scadenza;
          buono.note = data.mittente_note;
          buono.stato = data.stato_buono;
        }
        
        closeModal();
        updateSummaryCards();
        applyFilters();
        
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: 'Buono aggiornato con successo!',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true
        });
      } else {
        Swal.fire('Errore', result.message || 'Errore durante l\'aggiornamento.', 'error');
      }
    } catch (error) {
      Swal.fire('Errore di Rete', 'Impossibile comunicare con il server.', 'error');
    }
  });

  // === DELETE BUONO ===
  window.deleteBuono = function(id) {
    Swal.fire({
      title: 'Sei sicuro?',
      text: "Questa azione è irreversibile!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Sì, elimina!',
      cancelButtonText: 'Annulla'
    }).then(async (result) => {
      if (result.isConfirmed) {
        // Implementa chiamata DELETE se hai un endpoint
        // Per ora simuliamo
        const idx = allBuoni.findIndex(b => b.id == id);
        if (idx > -1) {
          allBuoni.splice(idx, 1);
          updateSummaryCards();
          applyFilters();
          showToast('Buono eliminato con successo', 'success');
        }
      }
    });
  }

  // === INITIAL LOAD ===
  document.addEventListener('DOMContentLoaded', () => {
    updateSummaryCards();
    applyFilters();
    <?php echo $message; ?>
  });
</script>

</body>
</html>
