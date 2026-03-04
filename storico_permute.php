<?php
// Errori PHP vanno nel log, non nell'output HTML
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
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
    $message = "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('" . addslashes($sessionMessage) . "', " . ($sessionIsError ? 'true' : 'false') . "); });</script>";
    unset($_SESSION['message']);
    unset($_SESSION['isError']);
}

// --- LOGICA PER RECUPERARE LE PERMUTE (LISTA) ---
$permute_data = [];
try {
    // Ordine predefinito
    $orderBy = $_GET['orderBy'] ?? 'id';
    $orderDir = $_GET['orderDir'] ?? 'DESC';

    // Validazione per evitare SQL Injection nell'ordinamento
    $allowedOrderBy = ['id', 'data', 'cliente', 'modello_nuovo', 'modello_usato', 'prezzo_nuovo', 'prezzo_permuta', 'status', 'created_at'];
    $allowedOrderDir = ['ASC', 'DESC'];

    $orderBy = in_array($orderBy, $allowedOrderBy) ? $orderBy : 'created_at'; // Default a created_at
    $orderDir = in_array($orderDir, $allowedOrderDir) ? $orderDir : 'DESC';

    // Gestione ricerca
    $searchTerm = $_GET['search'] ?? '';
    $whereClause = '';
    $queryParams = [];
    $paramTypes = '';

    if (!empty($searchTerm)) {
        $whereClause = " WHERE cliente LIKE ? OR modello_nuovo LIKE ? OR imei_nuovo LIKE ? OR modello_usato LIKE ? OR imei_usato LIKE ? OR COALESCE(telefono, telefono_cliente) LIKE ? OR progressivo LIKE ?";
        $searchTermLike = '%' . $searchTerm . '%';
        $queryParams = array_fill(0, 7, $searchTermLike);
        $paramTypes = 'sssssss';
    }

    $sql = "SELECT * FROM permute_nuovo" . $whereClause . " ORDER BY " . $orderBy . " " . $orderDir;

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new mysqli_sql_exception("Errore nella preparazione della query: " . $conn->error);
    }

    if (!empty($queryParams)) {
        $stmt->bind_param($paramTypes, ...$queryParams);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $permute_data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Errore nel caricamento delle permute (SQL): " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', true); });</script>";
    error_log("Errore Visualizza Permute (SQL): " . $e->getMessage());
} catch (Exception $e) {
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Errore generico nel caricamento delle permute: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', true); });</script>";
    error_log("Errore Visualizza Permute (Generale): " . $e->getMessage());
}

// Funzione helper per formattare la valuta
function formatCurrency($value) {
    return number_format($value, 2, ',', '.') . ' €';
}

// Funzione per ottenere le classi CSS per lo stato
function getPermutaStatusClasses($status) {
    switch ($status) {
        case 'In Trattativa':
            return 'in_trattativa';
        case 'Accettata':
            return 'accettata';
        case 'Rifiutata':
            return 'rifiutata';
        case 'Completata':
            return 'completata';
        case 'Annullata':
            return 'annullata';
        default:
            return 'in_trattativa'; // Default
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Dashboard Permute | TS Service</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=1">
          <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

  <style>
    /* ========== MODERN CSS VARIABLES ========== */
    :root {
        /* Primary Colors */
        --primary: #22c55e;
        --primary-dark: #16a34a;
        --primary-light: #dcfce7;
        --primary-glow: rgba(34, 197, 94, 0.4);
        
        /* Secondary Colors */
        --secondary: #3b82f6;
        --secondary-dark: #2563eb;
        --secondary-light: #dbeafe;
        
        /* Status Colors */
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --info: #3b82f6;
        --purple: #8b5cf6;
        
        /* Neutral Colors */
        --bg-page: #f8fafc;
        --bg-card: #ffffff;
        --text-primary: #0f172a;
        --text-secondary: #64748b;
        --text-muted: #94a3b8;
        --border-color: #e2e8f0;
        --border-light: #f1f5f9;
        
        /* Shadows */
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        --shadow-md: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        --shadow-glow: 0 0 40px rgba(34, 197, 94, 0.15);
        
        /* Transitions */
        --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
        --transition: 200ms cubic-bezier(0.4, 0, 0.2, 1);
        --transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
        --transition-spring: 500ms cubic-bezier(0.34, 1.56, 0.64, 1);
        
        /* Border Radius */
        --radius-sm: 0.375rem;
        --radius: 0.5rem;
        --radius-md: 0.75rem;
        --radius-lg: 1rem;
        --radius-xl: 1.5rem;
        
        /* Legacy mappings */
        --brand-green: #22c55e;
        --brand-green-dark: #16a34a;
        --brand-green-light: #dcfce7;
        --brand-green-accent: #34d399;
        --brand-green-text: #065f46;
        --brand-green-hover-bg: #d1fae5;
        --bg-color-page: #f8fafc;
        --text-color-primary: #0f172a;
        --text-color-secondary: #64748b;
        --border-color-light: #e2e8f0;
        --card-bg: #ffffff;
        --card-radius: 1rem;
        --card-shadow: var(--shadow-md);
        
        /* Status colors for permute */
        --status-in-trattativa: #3b82f6;
        --status-accettata: #f59e0b;
        --status-rifiutata: #ef4444;
        --status-completata: #22c55e;
        --status-annullata: #64748b;
    }

    /* ========== RESET & BASE ========== */
    *, *::before, *::after {
        box-sizing: border-box;
    }

    .hidden { display: none !important; }

    body {
        margin: 0;
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, var(--bg-page) 0%, #e2e8f0 100%);
        min-height: 100vh;
        color: var(--text-primary);
        padding-top: 80px;
        line-height: 1.6;
        overflow-x: hidden;
    }

    /* ========== FLOATING PARTICLES ========== */
    .particles-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 0;
        overflow: hidden;
    }

    .particle {
        position: absolute;
        border-radius: 50%;
        opacity: 0.15;
        animation: floatParticle 20s infinite ease-in-out;
    }

    .particle:nth-child(1) {
        width: 300px;
        height: 300px;
        background: var(--primary);
        top: -100px;
        left: -100px;
        animation-delay: 0s;
    }

    .particle:nth-child(2) {
        width: 200px;
        height: 200px;
        background: var(--secondary);
        top: 50%;
        right: -50px;
        animation-delay: -5s;
    }

    .particle:nth-child(3) {
        width: 150px;
        height: 150px;
        background: var(--purple);
        bottom: 10%;
        left: 20%;
        animation-delay: -10s;
    }

    .particle:nth-child(4) {
        width: 100px;
        height: 100px;
        background: var(--warning);
        top: 30%;
        left: 60%;
        animation-delay: -15s;
    }

    @keyframes floatParticle {
        0%, 100% { transform: translate(0, 0) scale(1); }
        25% { transform: translate(30px, -30px) scale(1.05); }
        50% { transform: translate(-20px, 20px) scale(0.95); }
        75% { transform: translate(15px, 15px) scale(1.02); }
    }

    /* ========== TOAST NOTIFICATIONS ========== */
    .toast-container {
        position: fixed;
        top: 100px;
        right: 24px;
        z-index: 10000;
        display: flex;
        flex-direction: column;
        gap: 12px;
        pointer-events: none;
    }

    .toast {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        padding: 16px 20px;
        box-shadow: var(--shadow-lg);
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 320px;
        max-width: 450px;
        pointer-events: auto;
        transform: translateX(120%);
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        border-left: 4px solid var(--primary);
    }

    .toast.show {
        transform: translateX(0);
        opacity: 1;
    }

    .toast.toast-success { border-left-color: var(--success); }
    .toast.toast-error { border-left-color: var(--danger); }
    .toast.toast-warning { border-left-color: var(--warning); }
    .toast.toast-info { border-left-color: var(--info); }

    .toast-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .toast-success .toast-icon { background: var(--primary-light); color: var(--primary); }
    .toast-error .toast-icon { background: #fee2e2; color: var(--danger); }
    .toast-warning .toast-icon { background: #fef3c7; color: var(--warning); }
    .toast-info .toast-icon { background: var(--secondary-light); color: var(--secondary); }

    .toast-content {
        flex: 1;
    }

    .toast-title {
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--text-primary);
        margin-bottom: 2px;
    }

    .toast-message {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    .toast-close {
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: 4px;
        border-radius: var(--radius-sm);
        transition: var(--transition);
    }

    .toast-close:hover {
        background: var(--border-light);
        color: var(--text-primary);
    }

    /* ========== MAIN CONTAINER ========== */
    .main-content-container {
        max-width: 1500px;
        margin: 0 auto;
        padding: 24px 32px;
        position: relative;
        z-index: 1;
    }

    /* ========== PAGE HEADER ========== */
    .page-header {
        text-align: center;
        margin-bottom: 32px;
        animation: fadeInUp 0.6s ease-out;
    }

    .page-header h1 {
        font-size: 2.75rem;
        font-weight: 800;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 8px;
        letter-spacing: -0.02em;
    }

    .page-header p {
        color: var(--text-secondary);
        font-size: 1.1rem;
        margin: 0;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ========== SUMMARY CARDS ========== */
    .summary-panel {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
        margin-bottom: 28px;
    }

    .summary-card {
        background: var(--bg-card);
        border-radius: var(--radius-xl);
        padding: 24px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        position: relative;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        opacity: 0;
        transform: translateY(30px);
        animation: cardSlideIn 0.5s ease-out forwards;
    }

    .summary-card:nth-child(1) { animation-delay: 0.1s; }
    .summary-card:nth-child(2) { animation-delay: 0.15s; }
    .summary-card:nth-child(3) { animation-delay: 0.2s; }
    .summary-card:nth-child(4) { animation-delay: 0.25s; }
    .summary-card:nth-child(5) { animation-delay: 0.3s; }

    @keyframes cardSlideIn {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--card-accent), var(--card-accent-light, var(--card-accent)));
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .summary-card:hover::before {
        opacity: 1;
    }

    .summary-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--shadow-lg), 0 0 0 1px var(--card-accent);
    }

    .summary-card.active {
        box-shadow: var(--shadow-lg), 0 0 0 2px var(--card-accent);
    }

    .summary-card.active::before {
        opacity: 1;
    }

    .summary-card--total { --card-accent: var(--primary); --card-accent-light: var(--primary-light); }
    .summary-card--trattativa { --card-accent: var(--secondary); --card-accent-light: var(--secondary-light); }
    .summary-card--accettata { --card-accent: var(--warning); --card-accent-light: #fef3c7; }
    .summary-card--completata { --card-accent: var(--success); --card-accent-light: #d1fae5; }
    .summary-card--valore { --card-accent: var(--purple); --card-accent-light: #ede9fe; }

    .summary-icon {
        width: 52px;
        height: 52px;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        transition: transform 0.3s ease;
    }

    .summary-card:hover .summary-icon {
        transform: scale(1.1) rotate(-5deg);
    }

    .summary-card--total .summary-icon { background: var(--primary-light); color: var(--primary); }
    .summary-card--trattativa .summary-icon { background: var(--secondary-light); color: var(--secondary); }
    .summary-card--accettata .summary-icon { background: #fef3c7; color: var(--warning); }
    .summary-card--completata .summary-icon { background: #d1fae5; color: var(--success); }
    .summary-card--valore .summary-icon { background: #ede9fe; color: var(--purple); }

    .summary-icon svg {
        width: 26px;
        height: 26px;
    }

    .summary-label {
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--text-secondary);
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .summary-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.1;
    }

    .summary-card--valore .summary-value {
        font-size: 1.6rem;
        color: var(--purple);
    }

    /* ========== FILTER BAR ========== */
    .filter-bar {
        background: var(--bg-card);
        border-radius: var(--radius-xl);
        padding: 20px 24px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        margin-bottom: 24px;
        display: flex;
        gap: 16px;
        align-items: flex-end;
        flex-wrap: wrap;
        animation: fadeInUp 0.6s ease-out 0.3s both;
    }

    .filter-group {
        flex: 1;
        min-width: 200px;
    }

    .filter-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filter-input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 0.95rem;
        background: var(--bg-page);
        color: var(--text-primary);
        transition: all 0.2s ease;
    }

    .filter-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-glow);
        background: var(--bg-card);
    }

    .filter-input::placeholder {
        color: var(--text-muted);
    }

    .filter-actions {
        display: flex;
        gap: 10px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: var(--radius-md);
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        position: relative;
        overflow: hidden;
    }

    .btn::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        background: linear-gradient(rgba(255,255,255,0.2), rgba(255,255,255,0));
        opacity: 0;
        transition: opacity 0.2s;
    }

    .btn:hover::after {
        opacity: 1;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        box-shadow: 0 4px 14px var(--primary-glow);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px var(--primary-glow);
    }

    .btn-secondary {
        background: var(--border-light);
        color: var(--text-secondary);
    }

    .btn-secondary:hover {
        background: var(--border-color);
        color: var(--text-primary);
    }

    .btn svg {
        width: 18px;
        height: 18px;
    }

    /* ========== PERMUTE GRID ========== */
    .permute-section {
        animation: fadeInUp 0.6s ease-out 0.4s both;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .section-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title svg {
        width: 24px;
        height: 24px;
        color: var(--primary);
    }

    .permute-count {
        background: var(--primary-light);
        color: var(--primary-dark);
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .permute-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 20px;
    }

    /* ========== PERMUTA CARD ========== */
    .permuta-card {
        background: var(--bg-card);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        opacity: 0;
        transform: translateY(20px) scale(0.98);
        animation: permutaCardIn 0.4s ease-out forwards;
        transform-style: preserve-3d;
        perspective: 1000px;
    }

    @keyframes permutaCardIn {
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .permuta-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg), var(--shadow-glow);
    }

    .permuta-card-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

    .permuta-card-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
        animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
        0%, 100% { transform: rotate(0deg); }
        50% { transform: rotate(180deg); }
    }

    .permuta-id {
        display: flex;
        align-items: center;
        gap: 10px;
        color: white;
        position: relative;
        z-index: 1;
    }

    .permuta-id-badge {
        background: rgba(255,255,255,0.2);
        padding: 6px 14px;
        border-radius: var(--radius);
        font-weight: 700;
        font-size: 1rem;
        backdrop-filter: blur(10px);
    }

    .permuta-progressivo {
        font-size: 0.85rem;
        opacity: 0.9;
    }

    .permuta-status {
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
        z-index: 1;
    }

    .permuta-status.in_trattativa { background: var(--secondary); color: white; }
    .permuta-status.accettata { background: var(--warning); color: white; }
    .permuta-status.rifiutata { background: var(--danger); color: white; }
    .permuta-status.completata { background: var(--success); color: white; }
    .permuta-status.annullata { background: var(--text-muted); color: white; }

    .permuta-card-body {
        padding: 20px;
    }

    .permuta-client {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--border-light);
    }

    .client-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-light), var(--secondary-light));
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--primary-dark);
        flex-shrink: 0;
    }

    .client-info {
        flex: 1;
        min-width: 0;
    }

    .client-name {
        font-weight: 600;
        font-size: 1.05rem;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .client-date {
        font-size: 0.85rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .permuta-devices {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }

    .device-box {
        background: var(--bg-page);
        border-radius: var(--radius-md);
        padding: 14px;
        border: 1px solid var(--border-light);
    }

    .device-label {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted);
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .device-label svg {
        width: 14px;
        height: 14px;
    }

    .device-label.nuovo { color: var(--primary); }
    .device-label.usato { color: var(--warning); }

    .device-model {
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .device-price {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-top: 4px;
    }

    .device-price strong {
        color: var(--primary);
        font-weight: 700;
    }

    .permuta-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 16px;
        border-top: 1px solid var(--border-light);
    }

    .permuta-diff {
        text-align: left;
    }

    .diff-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 2px;
    }

    .diff-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--primary);
    }

    .permuta-actions {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        width: 38px;
        height: 38px;
        border-radius: var(--radius);
        border: 1px solid var(--border-color);
        background: var(--bg-card);
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-action:hover {
        border-color: var(--primary);
        color: var(--primary);
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .btn-action.danger:hover {
        border-color: var(--danger);
        color: var(--danger);
        background: #fee2e2;
    }

    .btn-action svg {
        width: 18px;
        height: 18px;
    }

    /* Action dropdown for card */
    .card-actions-wrapper {
        position: relative;
    }

    .card-popup {
        position: absolute;
        bottom: 100%;
        right: 0;
        margin-bottom: 8px;
        background: var(--bg-card);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border-color);
        min-width: 180px;
        z-index: 100;
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: all 0.2s ease;
    }

    .card-popup.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .card-popup-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        font-size: 0.9rem;
        color: var(--text-primary);
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .card-popup-item:first-child {
        border-radius: var(--radius-md) var(--radius-md) 0 0;
    }

    .card-popup-item:last-child {
        border-radius: 0 0 var(--radius-md) var(--radius-md);
    }

    .card-popup-item:hover {
        background: var(--primary-light);
        color: var(--primary-dark);
    }

    .card-popup-item.danger {
        color: var(--danger);
    }

    .card-popup-item.danger:hover {
        background: #fee2e2;
    }

    .card-popup-item svg {
        width: 16px;
        height: 16px;
    }

    /* ========== EMPTY STATE ========== */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: var(--bg-card);
        border-radius: var(--radius-xl);
        border: 2px dashed var(--border-color);
    }

    .empty-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        background: var(--primary-light);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
    }

    .empty-icon svg {
        width: 40px;
        height: 40px;
    }

    .empty-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 8px;
    }

    .empty-text {
        color: var(--text-secondary);
        max-width: 400px;
        margin: 0 auto;
    }

    /* ========== MODALS ========== */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background: var(--bg-card);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-lg);
        max-width: 90%;
        width: 900px;
        max-height: 90vh;
        overflow: hidden;
        transform: scale(0.9) translateY(20px);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .modal-overlay.show .modal-content {
        transform: scale(1) translateY(0);
    }

    /* Legacy modal styles for compatibility */
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-color);
    }

    .modal-header h2 {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }

    .modal-close-button {
        background: var(--border-light);
        border: none;
        width: 36px;
        height: 36px;
        border-radius: var(--radius);
        font-size: 1.5rem;
        color: var(--text-secondary);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
    }

    .modal-close-button:hover {
        background: var(--border-color);
        color: var(--text-primary);
    }

    .modal-body {
        padding: 24px;
        max-height: 60vh;
        overflow-y: auto;
    }

    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background: var(--bg-page);
    }

    .modal-footer .btn-cancel {
        padding: 10px 20px;
        background: var(--border-light);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        color: var(--text-secondary);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    .modal-footer .btn-cancel:hover {
        background: var(--border-color);
        color: var(--text-primary);
    }

    .modal-footer .btn-primary {
        padding: 10px 20px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border: none;
        border-radius: var(--radius-md);
        color: white;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 12px var(--primary-glow);
        transition: all 0.2s ease;
    }

    .modal-footer .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px var(--primary-glow);
    }

    /* Delete modal */
    .delete-modal-content {
        width: 480px;
        text-align: center;
    }

    .delete-modal-content .modal-body {
        padding: 32px 24px;
    }

    .delete-modal-content .modal-body p {
        font-size: 1.05rem;
        color: var(--text-primary);
        margin-bottom: 8px;
    }

    .delete-modal-content .modal-footer {
        justify-content: center;
    }

    .delete-modal-content .modal-footer .btn-primary {
        background: linear-gradient(135deg, var(--danger), #dc2626);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .delete-modal-content .modal-footer .btn-primary:hover {
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
    }

    /* Form groups */
    .modal-form-group {
        margin-bottom: 16px;
    }

    .modal-form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 8px;
    }

    .modal-form-group input,
    .modal-form-group textarea,
    .modal-form-group select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 0.95rem;
        background: var(--bg-page);
        color: var(--text-primary);
        transition: all 0.2s ease;
    }

    .modal-form-group input:focus,
    .modal-form-group textarea:focus,
    .modal-form-group select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-glow);
        background: var(--bg-card);
    }

    .modal-form-group textarea {
        min-height: 100px;
        resize: vertical;
    }

    .modal-form-group input[readonly] {
        background: var(--border-light);
        color: var(--text-secondary);
        cursor: not-allowed;
    }

    /* Message Box (legacy support) */
    .message-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        pointer-events: none;
        background: rgba(0,0,0,0);
        transition: background 0.3s ease;
    }
    
    .message-container.active {
        display: flex;
        background: rgba(0,0,0,0.3);
    }
    
    .message-box {
        background: var(--bg-card);
        padding: 24px 40px;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        max-width: 90%;
        text-align: center;
        font-size: 1.1rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 16px;
        opacity: 0;
        transform: translateY(-40px);
        transition: all 0.3s ease;
        pointer-events: auto;
    }
    
    .message-box.show {
        opacity: 1;
        transform: translateY(0);
    }
    
    .message-box.success {
        border: 2px solid var(--primary);
        color: var(--primary-dark);
    }
    
    .message-box.error {
        border: 2px solid var(--danger);
        color: var(--danger);
    }
    
    .message-box svg {
        width: 28px;
        height: 28px;
        flex-shrink: 0;
    }

    @keyframes fadeOutAnimation {
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }
    .message-box.error {
        border: 2px solid var(--status-rifiutata);
        color: #c0392b;
    }
    .message-box svg {
        width: 32px; /* Aumentato la dimensione dell'icona */
        height: 32px;
        flex-shrink: 0;
    }
    .message-box.success svg {
        color: var(--brand-green);
    }
    .message-box.error svg {
        color: var(--status-rifiutata);
    }
    @keyframes fadeOutAnimation {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(-60px); }
    }
    @keyframes fadeInAnimation {
        from { opacity: 0; transform: translateY(-60px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Header styles - gestiti da header-styles.css */

    /* Responsive adjustments */
    @media (max-width: 1500px) {
        .custom-table-head, .custom-table-row {
            grid-template-columns: 
                45px    /* ID */
                100px    /* Data */
                1.5fr   /* Cliente */
                1.5fr   /* Modello Nuovo */
                1.5fr   /* Modello Usato */
                110px   /* Prezzo Nuovo */
                110px   /* Prezzo Permuta */
                100px   /* Status */
                140px;  /* Azioni */
        }
        .modal-content {
            width: 800px; /* Reduced for a slightly smaller feel */
        }
    }

    @media (max-width: 1200px) {
        body { padding: 15px; padding-top: 80px; }
        h1 { font-size: 2rem; margin-bottom: 1.8rem; }
        .main-content-container { margin: 1.5rem auto; padding: 1.5rem; max-width: 100%; }
        .filter-search-card { padding: 1.2rem; margin-bottom: 1.2rem; }
        .filter-search-card h2 { font-size: 1.3rem; margin-bottom: 1rem; }
        .filter-search-card button, .filter-search-card a { padding: 0.5rem 0.9rem; font-size: 0.95rem; }
        .table-container-card { padding: 1.2rem; }
        .table-container-card h2 { font-size: 1.1rem; margin-bottom: 1rem; }

        .custom-table-head, .custom-table-row {
            grid-template-columns: 
                40px    /* ID */
                90px    /* Data */
                1.4fr   /* Cliente */
                1.4fr   /* Modello Nuovo */
                1.4fr   /* Modello Usato */
                100px   /* Prezzo Nuovo */
                100px   /* Prezzo Permuta */
                90px   /* Status */
                120px;  /* Azioni */
            padding: 0.7rem 0.5rem;
            font-size: 0.85rem;
        }
        .status {
            padding: 0.25rem 0.7rem;
            font-size: 0.7rem;
        }
        .btn-actions {
            padding: 0.3rem 0.6rem; 
            font-size: 0.75rem; 
            gap: 0.2rem; 
        }
        .btn-actions svg {
            width: 0.75rem; height: 0.75rem;
        }
        .popup {
            width: 130px; 
            border-radius: 0.4rem;
        }
        .popup ul li {
            padding: 0.5rem 0.8rem; 
            font-size: 0.8rem; 
            gap: 0.4rem; 
        }
        .popup ul li svg {
            width: 0.8rem; height: 0.8rem;
        }
        .modal-content {
            width: 700px; 
        }
        .modal-header h2 { font-size: 1.4rem; }
        .modal-close-button { font-size: 1.6rem; }
        .modal-body { padding: 1rem 0; }
        .modal-footer { gap: 0.8rem; padding-top: 1rem; margin-top: 1rem;}
        .modal-footer button { padding: 0.5rem 1.2rem; font-size: 0.9rem; border-radius: 0.5rem;}
        .modal-grid { grid-template-columns: 1fr; gap: 0.8rem;}
        .modal-form-group input, .modal-form-group textarea, .modal-form-group select {
            padding: 0.5rem 0.7rem; font-size: 0.95rem; border-radius: 0.35rem;
        }
    }

    @media (max-width: 900px) {
        body { padding: 10px; padding-top: 70px; }
        h1 { font-size: 1.8rem; margin-bottom: 1.5rem; }
        .main-content-container { margin: 1rem auto; padding: 1rem; }
        .filter-search-card { padding: 1rem; margin-bottom: 1rem; }
        .filter-search-card h2 { font-size: 1.1rem; margin-bottom: 0.8rem; }
        .filter-search-card button, .filter-search-card a { padding: 0.4rem 0.8rem; font-size: 0.85rem; }
        .table-container-card { padding: 1rem; }
        .table-container-card h2 { font-size: 1rem; margin-bottom: 0.8rem; }
        
        .custom-table-head { display: none; }
        
        .custom-table-row {
            grid-template-columns: none;
            display: flex; 
            flex-direction: column;
            align-items: flex-start;
            gap: 0.4rem;
            padding: 0.8rem;
            font-size: 0.85rem;
            border-radius: 0.6rem;
            box-shadow: 0 1px 8px rgba(0, 0, 0, 0.1);
        }

        .custom-table-row > div {
            width: 100%;
            text-align: left !important;
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
        }
        .custom-table-row > div::before {
            content: attr(data-label);
            font-weight: 700;
            color: var(--text-color-secondary);
            margin-right: 0.5rem;
            display: inline-block;
            min-width: 60px;
        }
        /* Etichette specifiche per mobile */
        .custom-table-row > div:nth-child(1)::before { content: 'ID: '; }
        .custom-table-row > div:nth-child(2)::before { content: 'Data: '; }
        .custom-table-row > div:nth-child(3)::before { content: 'Cliente: '; }
        .custom-table-row > div:nth-child(4)::before { content: 'Modello Nuovo: '; }
        .custom-table-row > div:nth-child(5)::before { content: 'Modello Usato: '; }
        .custom-table-row > div:nth-child(6)::before { content: 'Prezzo Nuovo: '; }
        .custom-table-row > div:nth-child(7)::before { content: 'Prezzo Permuta: '; }
        .custom-table-row > div:nth-child(8)::before { content: 'Status: '; }
        .custom-table-row > div:nth-child(9)::before { content: ''; }

        .price-display { text-align: left !important; }
        .status { margin: 0; }
        .actions-wrapper { 
            width: 100%; 
            text-align: left; 
            margin-top: 0.8rem; 
            justify-content: flex-start;
            padding-left: 0;
        }
        .btn-actions { width: auto; min-width: 120px; margin: 0;} 
        .popup { 
            left: 0; 
            right: auto;
            transform: translateY(10px);
            width: 95%; max-width: 160px; 
        }

        /* Modali su mobile */
        .modal-content {
            padding: 1.2rem;
            border-radius: 0.5rem;
            width: 95%; 
        }
        .modal-header h2 { font-size: 1.3rem; }
        .modal-close-button { font-size: 1.5rem; }
        .modal-body { padding: 0.8rem 0; }
        .modal-footer { gap: 0.6rem; padding-top: 0.8rem; margin-top: 0.8rem;}
        .modal-footer button { padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 0.4rem;}
    }

    @media (max-width: 500px) {
        body { padding: 8px; padding-top: 60px; }
        h1 { font-size: 1.6rem; margin-bottom: 1.2rem; }
        .main-content-container { margin: 0.8rem auto; padding: 0.8rem; border-radius: 0.5rem;}
        .filter-search-card { padding: 0.8rem; margin-bottom: 0.8rem; }
        .filter-search-card h2 { font-size: 1rem; margin-bottom: 0.6rem; }
        .filter-search-card button, .filter-search-card a { padding: 0.35rem 0.7rem; font-size: 0.8rem; }
        .table-container-card { padding: 0.8rem; border-radius: 0.5rem;}
        .table-container-card h2 { font-size: 0.9rem; margin-bottom: 0.6rem; }

        .custom-table-row { padding: 0.6rem; border-radius: 0.5rem;}
        .status { font-size: 0.65rem; padding: 0.15rem 0.5rem; }
        .btn-actions { font-size: 0.7rem; padding: 0.2rem 0.4rem;} 
        .popup { width: 120px; } 
        .popup ul li { padding: 0.4rem 0.6rem; font-size: 0.75rem;} 
    }

    /* Stili specifici per il modale di Visualizzazione */
    .view-modal-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Reso più ampio */
        gap: 1.2rem; /* Aumentato il gap */
        margin-bottom: 1.2rem;
    }

    .view-modal-details-group {
        border: 1px solid var(--border-color-light);
        border-radius: 0.6rem; /* Aumentato border radius */
        padding: 1rem; /* Aumentato padding */
        background-color: #f9fafb;
    }

    .view-modal-details-group strong {
        display: block;
        font-size: 0.95rem; /* Aumentato font size */
        color: var(--text-color-secondary);
        margin-bottom: 0.3rem; /* Aumentato margin bottom */
    }

    .view-modal-details-group span {
        font-size: 1.05rem; /* Aumentato font size */
        color: var(--text-color-primary);
        word-wrap: break-word; /* Permette al testo di andare a capo */
        white-space: pre-wrap; /* Mantiene gli a capo e gli spazi nel testo libero */
    }

    .view-modal-section-title {
        font-size: 1.2rem; /* Aumentato font size */
        font-weight: 600;
        color: var(--brand-green-text);
        margin-top: 1.8rem; /* Aumentato margin top */
        margin-bottom: 1rem; /* Aumentato margin bottom */
        padding-bottom: 0.6rem; /* Aumentato padding bottom */
        border-bottom: 1px dashed var(--border-color-light);
    }

    .full-width-detail {
        grid-column: 1 / -1; /* Occupa l'intera larghezza della griglia */
    }

    /* ========== REDESIGNED EDIT MODAL ========== */
    .edit-permuta-modal {
        width: 95%;
        max-width: 900px;
        padding: 0;
        border-radius: 20px;
        overflow: hidden;
    }
    
    .edit-modal-header {
        background: linear-gradient(135deg, var(--brand-green) 0%, var(--brand-green-dark) 100%);
        padding: 1.5rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .edit-modal-header h2 {
        color: white;
        font-size: 1.4rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .edit-modal-header h2 svg {
        width: 28px;
        height: 28px;
    }
    
    .edit-modal-close {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    
    .edit-modal-close:hover {
        background: rgba(255,255,255,0.3);
    }
    
    .edit-modal-close svg {
        width: 24px;
        height: 24px;
    }
    
    .edit-modal-body {
        padding: 2rem;
        max-height: 65vh;
        overflow-y: auto;
    }
    
    .edit-section {
        margin-bottom: 2rem;
    }
    
    .edit-section:last-child {
        margin-bottom: 0;
    }
    
    .edit-section-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .edit-section-title svg {
        width: 20px;
        height: 20px;
        color: var(--brand-green);
    }
    
    .edit-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .edit-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    
    .edit-field {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    
    .edit-field.full-width {
        grid-column: 1 / -1;
    }
    
    .edit-field label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .edit-field input,
    .edit-field textarea,
    .edit-field select {
        padding: 0.75rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.2s;
        background: #f8fafc;
    }
    
    .edit-field input:focus,
    .edit-field textarea:focus,
    .edit-field select:focus {
        outline: none;
        border-color: var(--brand-green);
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.15);
        background: white;
    }
    
    .edit-field input[readonly] {
        background: #e2e8f0;
        color: #64748b;
        cursor: not-allowed;
    }
    
    .edit-field textarea {
        min-height: 80px;
        resize: vertical;
    }
    
    .edit-modal-footer {
        padding: 1.25rem 2rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }
    
    .btn-edit-cancel {
        padding: 0.75rem 1.5rem;
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-edit-cancel:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
    
    .btn-edit-save {
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, var(--brand-green) 0%, var(--brand-green-dark) 100%);
        border: none;
        border-radius: 10px;
        font-weight: 600;
        color: white;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }
    
    .btn-edit-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }
    
    .btn-edit-save svg {
        width: 18px;
        height: 18px;
    }

    /* ========== REDESIGNED VIEW MODAL ========== */
    .view-permuta-modal {
        width: 95%;
        max-width: 900px;
        padding: 0;
        border-radius: 20px;
        overflow: hidden;
    }
    
    .view-modal-header {
        background: linear-gradient(135deg, var(--brand-green) 0%, var(--brand-green-dark) 100%);
        padding: 1.5rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .view-modal-header h2 {
        color: white;
        font-size: 1.4rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .view-modal-header h2 svg {
        width: 28px;
        height: 28px;
    }
    
    .view-modal-close {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    
    .view-modal-close:hover {
        background: rgba(255,255,255,0.3);
    }
    
    .view-modal-close svg {
        width: 24px;
        height: 24px;
    }
    
    .view-modal-body {
        padding: 2rem;
        max-height: 65vh;
        overflow-y: auto;
    }
    
    .view-section {
        margin-bottom: 1.5rem;
    }
    
    .view-section:last-child {
        margin-bottom: 0;
    }
    
    .view-section-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .view-section-header svg {
        width: 20px;
        height: 20px;
        color: var(--brand-green);
    }
    
    .view-info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    
    .view-info-item {
        background: #f8fafc;
        padding: 1rem;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }
    
    .view-info-item.full-width {
        grid-column: 1 / -1;
    }
    
    .view-info-item label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.35rem;
    }
    
    .view-info-item span {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        display: block;
        word-break: break-word;
    }
    
    .view-info-item span.price {
        color: var(--brand-green);
        font-size: 1.1rem;
    }
    
    .view-status-badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .view-status-badge.in_trattativa {
        background: #dbeafe;
        color: #1d4ed8;
    }
    
    .view-status-badge.completata {
        background: #dcfce7;
        color: #16a34a;
    }
    
    .view-status-badge.accettata {
        background: #ffedd5;
        color: #c2410c;
    }
    
    .view-status-badge.rifiutata {
        background: #fee2e2;
        color: #dc2626;
    }
    
    .view-status-badge.annullata {
        background: #f3f4f6;
        color: #6b7280;
    }
    
    .view-modal-footer {
        padding: 1.25rem 2rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }
    
    .btn-view-close {
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, var(--brand-green) 0%, var(--brand-green-dark) 100%);
        border: none;
        border-radius: 10px;
        font-weight: 600;
        color: white;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }
    
    .btn-view-close:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }

    @media (max-width: 768px) {
        .edit-grid, .edit-grid-3, .view-info-grid {
            grid-template-columns: 1fr;
        }
        
        .edit-modal-body, .view-modal-body {
            padding: 1rem;
        }
        
        .edit-modal-header, .view-modal-header {
            padding: 1rem 1.5rem;
        }
        
        .edit-modal-header h2, .view-modal-header h2 {
            font-size: 1.1rem;
        }
    }

    /* ========== RESPONSIVE MEDIA QUERIES FOR NEW CARD LAYOUT ========== */
    @media (max-width: 1400px) {
        .summary-panel {
            grid-template-columns: repeat(3, 1fr);
        }
        .permute-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 1100px) {
        .summary-panel {
            grid-template-columns: repeat(2, 1fr);
        }
        .permute-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .filter-bar {
            flex-direction: column;
            gap: 1rem;
        }
        .filter-bar .filter-search {
            width: 100%;
        }
    }

    @media (max-width: 900px) {
        body {
            padding: 10px;
            padding-top: 70px;
        }
        .main-content-container {
            padding: 1.25rem;
            border-radius: 16px;
        }
        .page-header h1 {
            font-size: 2rem;
        }
        .summary-panel {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .summary-card {
            padding: 1rem;
        }
        .summary-value {
            font-size: 1.8rem;
        }
        .permute-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        .permuta-card-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }
        .permuta-header-right {
            width: 100%;
            justify-content: space-between;
        }
    }

    @media (max-width: 600px) {
        body {
            padding: 8px;
            padding-top: 60px;
        }
        .main-content-container {
            padding: 1rem;
            border-radius: 12px;
        }
        .page-header h1 {
            font-size: 1.6rem;
        }
        .page-header p {
            font-size: 0.9rem;
        }
        .summary-panel {
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        .summary-card {
            padding: 0.875rem;
        }
        .summary-icon {
            width: 40px;
            height: 40px;
        }
        .summary-icon svg {
            width: 20px;
            height: 20px;
        }
        .summary-value {
            font-size: 1.5rem;
        }
        .summary-label {
            font-size: 0.7rem;
        }
        .filter-bar {
            padding: 1rem;
        }
        .filter-search input {
            font-size: 0.9rem;
            padding: 0.6rem 0.6rem 0.6rem 2.5rem;
        }
        .filter-buttons {
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 0.5rem 0.875rem;
            font-size: 0.8rem;
        }
        .permuta-card {
            border-radius: 12px;
        }
        .permuta-card-header {
            padding: 0.875rem;
        }
        .permuta-card-body {
            padding: 0.875rem;
        }
        .permuta-id {
            font-size: 0.85rem;
        }
        .permuta-date {
            font-size: 0.7rem;
        }
        .device-info strong {
            font-size: 0.85rem;
        }
        .device-price {
            font-size: 0.9rem;
        }
        .permuta-footer {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }
        .permuta-footer-info {
            width: 100%;
            flex-direction: column;
            gap: 0.5rem;
        }
        .permuta-differenza {
            width: 100%;
            text-align: center;
            padding: 0.5rem;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(22, 163, 74, 0.1));
            border-radius: 8px;
        }
        .permuta-actions {
            width: 100%;
            justify-content: center;
        }
        .card-menu-dropdown {
            right: 0;
            left: auto;
            min-width: 140px;
        }
        .toast {
            min-width: 280px;
            max-width: 90vw;
            font-size: 0.85rem;
        }
    }

    @media (max-width: 400px) {
        .summary-panel {
            grid-template-columns: 1fr;
        }
        .summary-card {
            flex-direction: row;
            justify-content: flex-start;
            gap: 1rem;
            text-align: left;
        }
        .summary-card .summary-icon {
            margin-bottom: 0;
        }
        .summary-card-content {
            display: flex;
            flex-direction: column;
        }
    }

  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <!-- Floating Particles Background -->
  <div class="particles-container">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
  </div>

  <!-- Toast Container -->
  <div class="toast-container" id="toastContainer"></div>

  <div class="main-content-container">
    <?php echo $message; ?>

    <!-- Page Header -->
    <div class="page-header">
        <h1>Dashboard Permute</h1>
        <p>Gestisci tutte le operazioni di permuta in un unico posto</p>
    </div>

    <?php
    // Calculate statistics
    $totalPermute = count($permute_data);
    $inTrattativa = count(array_filter($permute_data, fn($p) => ($p['status'] ?? '') === 'In Trattativa'));
    $accettate = count(array_filter($permute_data, fn($p) => ($p['status'] ?? '') === 'Accettata'));
    $completate = count(array_filter($permute_data, fn($p) => ($p['status'] ?? '') === 'Completata'));
    $valoreTotale = array_sum(array_map(fn($p) => (float)($p['differenza'] ?? 0), array_filter($permute_data, fn($p) => ($p['status'] ?? '') === 'Completata')));
    ?>

    <!-- Summary Cards -->
    <div class="summary-panel">
        <div class="summary-card summary-card--total" onclick="filterByStatus('')" data-status="">
            <div class="summary-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 1l4 4-4 4"></path>
                    <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                    <path d="M7 23l-4-4 4-4"></path>
                    <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                </svg>
            </div>
            <div class="summary-label">Totale Permute</div>
            <div class="summary-value" data-count="<?php echo $totalPermute; ?>">0</div>
        </div>

        <div class="summary-card summary-card--trattativa" onclick="filterByStatus('In Trattativa')" data-status="In Trattativa">
            <div class="summary-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="summary-label">In Trattativa</div>
            <div class="summary-value" data-count="<?php echo $inTrattativa; ?>">0</div>
        </div>

        <div class="summary-card summary-card--accettata" onclick="filterByStatus('Accettata')" data-status="Accettata">
            <div class="summary-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
            </div>
            <div class="summary-label">Accettate</div>
            <div class="summary-value" data-count="<?php echo $accettate; ?>">0</div>
        </div>

        <div class="summary-card summary-card--completata" onclick="filterByStatus('Completata')" data-status="Completata">
            <div class="summary-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="summary-label">Completate</div>
            <div class="summary-value" data-count="<?php echo $completate; ?>">0</div>
        </div>

        <div class="summary-card summary-card--valore">
            <div class="summary-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div class="summary-label">Valore Completate</div>
            <div class="summary-value"><?php echo formatCurrency($valoreTotale); ?></div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="storico_permute.php" class="filter-bar" id="filterForm">
        <div class="filter-group" style="flex: 2;">
            <label for="search">Cerca Permuta</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" 
                   placeholder="Cliente, modello, IMEI, progressivo..." class="filter-input">
        </div>
        <input type="hidden" id="statusFilter" name="status" value="">
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                Cerca
            </button>
            <a href="storico_permute.php" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                    <path d="M3 3v5h5"></path>
                </svg>
                Reset
            </a>
        </div>
    </form>

    <!-- Permute Section -->
    <div class="permute-section">
        <div class="section-header">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="9" y1="21" x2="9" y2="9"></line>
                </svg>
                Elenco Permute
            </h2>
            <span class="permute-count"><?php echo $totalPermute; ?> permute</span>
        </div>

        <?php if (!empty($permute_data)): ?>
        <div class="permute-grid" id="permuteGrid">
            <?php foreach ($permute_data as $index => $row): ?>
            <?php
            $dataVis = !empty($row['data']) ? date('d/m/Y', strtotime($row['data'])) : '-';
            $statusClass = getPermutaStatusClasses($row['status'] ?? '');
            $clienteNome = htmlspecialchars($row['cliente'] ?? 'N/A');
            $clienteIniziali = strtoupper(substr($clienteNome, 0, 2));
            $prezzoNuovo = (float)($row['prezzo_nuovo'] ?? 0);
            $prezzoPermuta = (float)($row['prezzo_permuta'] ?? 0);
            $differenza = (float)($row['differenza'] ?? 0);
            ?>
            <div class="permuta-card" style="animation-delay: <?php echo $index * 0.05; ?>s" data-status="<?php echo htmlspecialchars($row['status'] ?? ''); ?>">
                <div class="permuta-card-header">
                    <div class="permuta-id">
                        <span class="permuta-id-badge">#<?php echo htmlspecialchars($row['id']); ?></span>
                        <?php if (!empty($row['progressivo'])): ?>
                        <span class="permuta-progressivo"><?php echo htmlspecialchars($row['progressivo']); ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="permuta-status <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars($row['status'] ?? 'N/A'); ?>
                    </span>
                </div>
                
                <div class="permuta-card-body">
                    <div class="permuta-client">
                        <div class="client-avatar"><?php echo $clienteIniziali; ?></div>
                        <div class="client-info">
                            <div class="client-name"><?php echo $clienteNome; ?></div>
                            <div class="client-date">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <?php echo $dataVis; ?>
                            </div>
                        </div>
                    </div>

                    <div class="permuta-devices">
                        <div class="device-box">
                            <div class="device-label nuovo">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                                    <line x1="12" y1="18" x2="12.01" y2="18"></line>
                                </svg>
                                Nuovo
                            </div>
                            <div class="device-model"><?php echo htmlspecialchars($row['modello_nuovo'] ?? 'N/A'); ?></div>
                            <div class="device-price">Prezzo: <strong><?php echo formatCurrency($prezzoNuovo); ?></strong></div>
                        </div>
                        <div class="device-box">
                            <div class="device-label usato">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="17 1 21 5 17 9"></polyline>
                                    <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                                    <polyline points="7 23 3 19 7 15"></polyline>
                                    <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                                </svg>
                                Usato
                            </div>
                            <div class="device-model"><?php echo htmlspecialchars($row['modello_usato'] ?? 'N/A'); ?></div>
                            <div class="device-price">Valutato: <strong><?php echo formatCurrency($prezzoPermuta); ?></strong></div>
                        </div>
                    </div>

                    <div class="permuta-footer">
                        <div class="permuta-diff">
                            <div class="diff-label">Differenza</div>
                            <div class="diff-value"><?php echo formatCurrency($differenza); ?></div>
                        </div>
                        <div class="permuta-actions">
                            <button class="btn-action" onclick="openViewPermutaModal(<?php echo $row['id']; ?>)" title="Visualizza">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                            <button class="btn-action" onclick="openEditPermutaModal(<?php echo $row['id']; ?>)" title="Modifica">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="btn-action" onclick="openPrintPermutaModal(<?php echo $row['id']; ?>)" title="Stampa">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                    <rect x="6" y="14" width="12" height="8"></rect>
                                </svg>
                            </button>
                            <div class="card-actions-wrapper">
                                <button class="btn-action" onclick="toggleCardMenu(event, <?php echo $row['id']; ?>)" title="Altre azioni">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="1"></circle>
                                        <circle cx="19" cy="12" r="1"></circle>
                                        <circle cx="5" cy="12" r="1"></circle>
                                    </svg>
                                </button>
                                <div class="card-popup" id="popup-card-<?php echo $row['id']; ?>">
                                    <div class="card-popup-item" onclick="openAllegatiPermutaModal(<?php echo $row['id']; ?>)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                                        </svg>
                                        Allegati
                                    </div>
                                    <div class="card-popup-item" onclick="openBarcodePermutaModal(<?php echo $row['id']; ?>)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 5v14"></path>
                                            <path d="M8 5v14"></path>
                                            <path d="M12 5v14"></path>
                                            <path d="M17 5v14"></path>
                                            <path d="M21 5v14"></path>
                                        </svg>
                                        Barcode
                                    </div>
                                    <div class="card-popup-item" onclick="openEmailPermutaModal(<?php echo $row['id']; ?>)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                            <polyline points="22,6 12,13 2,6"></polyline>
                                        </svg>
                                        Invia Email
                                    </div>
                                    <div class="card-popup-item" onclick="openPrivacyPermutaModal(<?php echo $row['id']; ?>)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                        </svg>
                                        Privacy
                                    </div>
                                    <div class="card-popup-item danger" onclick="openDeletePermutaModal(<?php echo $row['id']; ?>)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                        Elimina
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 1l4 4-4 4"></path>
                    <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                    <path d="M7 23l-4-4 4-4"></path>
                    <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                </svg>
            </div>
            <h3 class="empty-title">Nessuna permuta trovata</h3>
            <p class="empty-text">Non ci sono permute che corrispondono ai criteri di ricerca. Prova a modificare i filtri.</p>
        </div>
        <?php endif; ?>
    </div>
  </div>

  <!-- Message Box Container (Legacy) -->
  <div id="messageContainer" class="message-container hidden">
      <div id="messageBox" class="message-box"></div>
  </div>

  <!-- Main Modal Overlay -->
  <div id="mainModal" class="modal-overlay hidden">
    <!-- Delete Permuta Modal Content -->
    <div id="deletePermutaModalContent" class="modal-content delete-modal-content hidden">
        <div class="modal-header">
            <h2>Conferma Eliminazione Permuta #<span id="deletePermutaId"></span></h2>
            <button class="modal-close-button" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Sei sicuro di voler eliminare la permuta #<span id="deletePermutaIdConfirm"></span>?</p>
            <p>Questa azione è irreversibile.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Annulla</button>
            <button class="btn-primary" id="confirmDeletePermutaButton">Conferma Elimina</button>
        </div>
    </div>

    <!-- Edit Permuta Modal Content - REDESIGNED -->
    <div id="editPermutaModalContent" class="modal-content edit-permuta-modal hidden">
        <div class="edit-modal-header">
            <h2>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Modifica Permuta #<span id="editPermutaId"></span>
            </h2>
            <button class="edit-modal-close" onclick="closeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="edit-modal-body">
            <form id="editPermutaForm">
                <input type="hidden" id="editPermutaHiddenId" name="id">
                
                <!-- Sezione Dati Generali -->
                <div class="edit-section">
                    <div class="edit-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Dati Generali
                    </div>
                    <div class="edit-grid">
                        <div class="edit-field">
                            <label>Cliente</label>
                            <input type="text" id="editPermutaCliente" name="cliente" required>
                        </div>
                        <div class="edit-field">
                            <label>Telefono</label>
                            <input type="text" id="editPermutaTelefono" name="telefono">
                        </div>
                        <div class="edit-field">
                            <label>Progressivo</label>
                            <input type="text" id="editPermutaProgressivo" name="progressivo" readonly>
                        </div>
                        <div class="edit-field">
                            <label>Data</label>
                            <input type="date" id="editPermutaData" name="data" required>
                        </div>
                    </div>
                </div>
                
                <!-- Sezione Dispositivo Nuovo -->
                <div class="edit-section">
                    <div class="edit-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                            <line x1="12" y1="18" x2="12.01" y2="18"></line>
                        </svg>
                        Dispositivo Nuovo (Venduto)
                    </div>
                    <div class="edit-grid">
                        <div class="edit-field">
                            <label>Modello Nuovo</label>
                            <input type="text" id="editPermutaModelloNuovo" name="modello_nuovo">
                        </div>
                        <div class="edit-field">
                            <label>IMEI Nuovo</label>
                            <input type="text" id="editPermutaImeiNuovo" name="imei_nuovo">
                        </div>
                        <div class="edit-field">
                            <label>Prezzo Nuovo (€)</label>
                            <input type="number" step="0.01" id="editPermutaPrezzoNuovo" name="prezzo_nuovo">
                        </div>
                        <div class="edit-field">
                            <label>Costo Prodotto (€)</label>
                            <input type="number" step="0.01" id="editPermutaCostoProdotto" name="costo_prodotto">
                        </div>
                        <div class="edit-field full-width">
                            <label>Note Nuovo</label>
                            <textarea id="editPermutaNoteNuovo" name="note_nuovo" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Sezione Dispositivo Usato -->
                <div class="edit-section">
                    <div class="edit-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="17 1 21 5 17 9"></polyline>
                            <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                            <polyline points="7 23 3 19 7 15"></polyline>
                            <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                        </svg>
                        Dispositivo Usato (Ritirato)
                    </div>
                    <div class="edit-grid">
                        <div class="edit-field">
                            <label>Modello Usato</label>
                            <input type="text" id="editPermutaModelloUsato" name="modello_usato">
                        </div>
                        <div class="edit-field">
                            <label>IMEI Usato</label>
                            <input type="text" id="editPermutaImeiUsato" name="imei_usato">
                        </div>
                        <div class="edit-field">
                            <label>Prezzo Permuta (€)</label>
                            <input type="number" step="0.01" id="editPermutaPrezzoPermuta" name="prezzo_permuta">
                        </div>
                        <div class="edit-field">
                            <label>Costo Riparazione (€)</label>
                            <input type="number" step="0.01" id="editPermutaCostoRiparazione" name="costo_riparazione">
                        </div>
                        <div class="edit-field full-width">
                            <label>Note Usato</label>
                            <textarea id="editPermutaNoteUsato" name="note_usato" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Sezione Riepilogo Finanziario -->
                <div class="edit-section">
                    <div class="edit-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        Riepilogo Finanziario e Stato
                    </div>
                    <div class="edit-grid-3">
                        <div class="edit-field">
                            <label>Costo Accessori (€)</label>
                            <input type="number" step="0.01" id="editPermutaCostoAccessori" name="costo_accessori">
                        </div>
                        <div class="edit-field">
                            <label>Differenza da Pagare (€)</label>
                            <input type="number" step="0.01" id="editPermutaDifferenza" name="differenza">
                        </div>
                        <div class="edit-field">
                            <label>Prezzo Vendita Finale (€)</label>
                            <input type="number" step="0.01" id="editPermutaPrezzoVendita" name="prezzo_vendita">
                        </div>
                        <div class="edit-field">
                            <label>Status</label>
                            <select id="editPermutaStatus" name="status">
                                <option value="In Trattativa">In Trattativa</option>
                                <option value="Accettata">Accettata</option>
                                <option value="Rifiutata">Rifiutata</option>
                                <option value="Completata">Completata</option>
                                <option value="Annullata">Annullata</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Sezione Note -->
                <div class="edit-section">
                    <div class="edit-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        Note e Test
                    </div>
                    <div class="edit-grid">
                        <div class="edit-field">
                            <label>Test Effettuati</label>
                            <textarea id="editPermutaTestOk" name="test_ok" rows="3"></textarea>
                        </div>
                        <div class="edit-field">
                            <label>Note Generali</label>
                            <textarea id="editPermutaNoteGenerali" name="note_generali" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="edit-modal-footer">
            <button type="button" class="btn-edit-cancel" onclick="closeModal()">Annulla</button>
            <button type="button" class="btn-edit-save" onclick="savePermuta()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Salva Modifiche
            </button>
        </div>
    </div>

    <!-- View Permuta Modal Content - REDESIGNED -->
    <div id="viewPermutaModalContent" class="modal-content view-permuta-modal hidden">
        <div class="view-modal-header">
            <h2>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                Dettagli Permuta #<span id="viewPermutaId"></span>
            </h2>
            <button class="view-modal-close" onclick="closeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="view-modal-body">
            <div id="permutaDetailsContent">
                <!-- Sezione Dati Generali -->
                <div class="view-section">
                    <div class="view-section-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Dati Generali
                    </div>
                    <div class="view-info-grid">
                        <div class="view-info-item">
                            <label>ID Permuta</label>
                            <span id="viewPermutaDetailId"></span>
                        </div>
                        <div class="view-info-item">
                            <label>Progressivo</label>
                            <span id="viewPermutaDetailProgressivo"></span>
                        </div>
                        <div class="view-info-item">
                            <label>Data</label>
                            <span id="viewPermutaDetailData"></span>
                        </div>
                        <div class="view-info-item">
                            <label>Cliente</label>
                            <span id="viewPermutaDetailCliente"></span>
                        </div>
                        <div class="view-info-item">
                            <label>Telefono</label>
                            <span id="viewPermutaDetailTelefono"></span>
                        </div>
                        <div class="view-info-item">
                            <label>Status</label>
                            <span id="viewPermutaDetailStatus"></span>
                        </div>
                    </div>
                </div>

                <!-- Sezione Dispositivo Nuovo -->
                <div class="view-section">
                    <div class="view-section-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                            <line x1="12" y1="18" x2="12.01" y2="18"></line>
                        </svg>
                        Dispositivo Nuovo (Venduto)
                    </div>
                    <div class="view-info-grid">
                        <div class="view-info-item">
                            <label>Modello</label>
                            <span id="viewPermutaDetailModelloNuovo"></span>
                        </div>
                        <div class="view-info-item">
                            <label>IMEI</label>
                            <span id="viewPermutaDetailImeiNuovo"></span>
                        </div>
                        <div class="view-info-item">
                            <label>Prezzo</label>
                            <span id="viewPermutaDetailPrezzoNuovo" class="price"></span>
                        </div>
                        <div class="view-info-item">
                            <label>Costo Prodotto</label>
                            <span id="viewPermutaDetailCostoProdotto" class="price"></span>
                        </div>
                        <div class="view-info-item full-width">
                            <label>Note</label>
                            <span id="viewPermutaDetailNoteNuovo"></span>
                        </div>
                    </div>
                </div>

                <!-- Sezione Dispositivo Usato -->
                <div class="view-section">
                    <div class="view-section-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="17 1 21 5 17 9"></polyline>
                            <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                            <polyline points="7 23 3 19 7 15"></polyline>
                            <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                        </svg>
                        Dispositivo Usato (Ritirato)
                    </div>
                    <div class="view-info-grid">
                        <div class="view-info-item">
                            <label>Modello</label>
                            <span id="viewPermutaDetailModelloUsato"></span>
                        </div>
                        <div class="view-info-item">
                            <label>IMEI</label>
                            <span id="viewPermutaDetailImeiUsato"></span>
                        </div>
                        <div class="view-info-item">
                            <label>Prezzo Permuta</label>
                            <span id="viewPermutaDetailPrezzoPermuta" class="price"></span>
                        </div>
                        <div class="view-info-item">
                            <label>Costo Ricondizionamento</label>
                            <span id="viewPermutaDetailCostoRiparazione" class="price"></span>
                        </div>
                        <div class="view-info-item full-width">
                            <label>Note</label>
                            <span id="viewPermutaDetailNoteUsato"></span>
                        </div>
                    </div>
                </div>

                <!-- Sezione Riepilogo Finanziario -->
                <div class="view-section">
                    <div class="view-section-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        Riepilogo Finanziario
                    </div>
                    <div class="view-info-grid">
                        <div class="view-info-item">
                            <label>Costo Accessori</label>
                            <span id="viewPermutaDetailCostoAccessori" class="price"></span>
                        </div>
                        <div class="view-info-item">
                            <label>Differenza da Pagare</label>
                            <span id="viewPermutaDetailDifferenza" class="price"></span>
                        </div>
                        <div class="view-info-item">
                            <label>Prezzo Vendita Finale</label>
                            <span id="viewPermutaDetailPrezzoVendita" class="price"></span>
                        </div>
                    </div>
                </div>

                <!-- Sezione Note e Test -->
                <div class="view-section">
                    <div class="view-section-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        Note e Test
                    </div>
                    <div class="view-info-grid">
                        <div class="view-info-item full-width">
                            <label>Test Effettuati</label>
                            <span id="viewPermutaDetailTestOk"></span>
                        </div>
                        <div class="view-info-item full-width">
                            <label>Note Generali</label>
                            <span id="viewPermutaDetailNoteGenerali"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="view-modal-footer">
            <button type="button" class="btn-view-close" onclick="closeModal()">Chiudi</button>
        </div>
    </div>

    <!-- Allegati Permuta Modal Content -->
    <div id="allegatiPermutaModalContent" class="modal-content hidden">
        <div class="modal-header">
            <h2>Allegati Permuta #<span id="allegatiPermutaId"></span></h2>
            <button class="modal-close-button" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-md mb-4 text-center">Gestisci gli allegati per questa permuta.</p>
            <div id="allegatiPermutaList" class="border border-gray-200 rounded-md p-3 mb-4 max-h-60 overflow-y-auto bg-gray-50 text-center">
                <p class="text-base text-gray-500 italic">La gestione degli allegati richiede un'implementazione lato server dedicata per l'upload e la visualizzazione dei file.</p> <!-- Aumentato font size -->
                <p class="text-base text-gray-500 italic mt-2">Per ora, questa funzionalità è un segnaposto.</p> <!-- Aumentato font size -->
            </div>
            <div class="modal-form-group">
                <label for="allegatoPermutaFileInput">Carica Nuovo Allegato:</label>
                <input type="file" id="allegatoPermutaFileInput" name="allegato" class="w-full" disabled>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Chiudi</button>
            <button class="btn-primary" disabled>Carica Allegato (In sviluppo)</button>
        </div>
    </div>

    <!-- Barcode Permuta Modal Content -->
    <div id="barcodePermutaModalContent" class="modal-content delete-modal-content hidden">
        <div class="modal-header">
            <h2>Barcode Permuta #<span id="barcodePermutaId"></span></h2>
            <button class="modal-close-button" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body text-center">
            <p class="text-lg mb-4">Il barcode per la permuta ID <span class="font-bold text-green-600" id="barcodePermutaDisplayId"></span>:</p> <!-- Aumentato font size -->
            <div id="barcodeImageContainer" class="flex flex-col justify-center items-center p-5 border border-gray-300 rounded-md bg-white"> <!-- Aumentato padding -->
                <svg id="barcodeSvg"></svg>
                <p class="text-base text-gray-500 mt-3">Valore: <span class="font-semibold" id="barcodePermutaValue"></span></p> <!-- Aumentato font size e margin top -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Chiudi</button>
            <button class="btn-primary" onclick="printBarcodePermutaContent()">Stampa Barcode</button>
        </div>
    </div>

    <!-- Invia Email Permuta Modal Content -->
    <div id="inviaEmailPermutaModalContent" class="modal-content hidden">
        <div class="modal-header">
            <h2>Invia Email Permuta #<span id="emailPermutaId"></span></h2>
            <button class="modal-close-button" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="emailPermutaForm">
                <div class="modal-form-group">
                    <label for="emailPermutaTo">A:</label>
                    <input type="email" id="emailPermutaTo" name="to_email" class="w-full" placeholder="indirizzo@esempio.com" required>
                </div>
                <div class="modal-form-group">
                    <label for="emailPermutaSubject">Oggetto:</label>
                    <input type="text" id="emailPermutaSubject" name="subject" class="w-full" placeholder="Aggiornamento Permuta #ID">
                </div>
                <div class="modal-form-group">
                    <label for="emailPermutaBody">Corpo del Messaggio:</label>
                    <textarea id="emailPermutaBody" name="body" rows="10" class="w-full" placeholder="Gentile cliente, la sua permuta è..."></textarea> <!-- Aumentato rows -->
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Annulla</button>
            <button class="btn-primary" id="sendEmailPermutaButton">Invia Email</button>
        </div>
    </div>

    <!-- Privacy Permuta Modal Content -->
    <div id="privacyPermutaModalContent" class="modal-content hidden">
        <div class="modal-header">
            <h2>Informativa Privacy Permuta #<span id="privacyPermutaId"></span></h2>
            <button class="modal-close-button" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="privacyPermutaContent" class="prose max-w-none text-gray-700">
                <h3 class="text-xl font-semibold mb-3">Informativa sul Trattamento dei Dati Personali (GDPR) - Permute</h3> <!-- Aumentato font size -->
                <p class="mb-3">Ai sensi del Regolamento (UE) 2016/679 (GDPR), La informiamo che i Suoi dati personali, da Lei liberamente forniti, saranno trattati per le seguenti finalità:</p> <!-- Aumentato font size -->
                <ul class="list-disc list-inside mb-5 text-lg"> <!-- Aumentato font size -->
                    <li>Gestione e registrazione dell'operazione di permuta.</li>
                    <li>Adempimento degli obblighi legali, contabili e fiscali.</li>
                    <li>Comunicazioni relative allo stato della permuta.</li>
                </ul>
                <p class="mb-3">Il trattamento sarà effettuato con modalità informatiche e manuali, nel rispetto dei principi di correttezza, liceità, trasparenza e di tutela della Sua riservatezza e dei Suoi diritti. I Suoi dati potranno essere comunicati a terzi solo se strettamente necessario per l'erogazione del servizio o per obblighi di legge.</p> <!-- Aumentato font size -->
                <p class="font-semibold text-lg">Titolare del Trattamento:</p> <!-- Aumentato font size -->
                <p class="text-base mb-4">TS SERVICE<br>Contrada Castromurro - 217<br>87021 BELVEDERE M.MO (CS)<br>Tel. 3420330279<br>Email: info@tsservice.it</p> <!-- Aumentato font size -->
                <p class="text-lg">Lei ha il diritto di accedere ai Suoi dati, di chiederne la rettifica, la cancellazione, la limitazione del trattamento, di opporsi al trattamento e di esercitare il diritto alla portabilità dei dati, ai sensi degli articoli da 15 a 22 del GDPR. Per esercitare tali diritti, può contattare il Titolare del Trattamento ai recapiti sopra indicati.</p> <!-- Aumentato font size -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Chiudi</button>
        </div>
    </div>

  </div> <!-- End Main Modal Overlay -->


<script>
    // ========== MODERN JS - TOAST NOTIFICATIONS ========== 
    function showToast(message, type = 'success', duration = 4000) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icons = {
            success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
        };
        
        const titles = {
            success: 'Successo',
            error: 'Errore',
            warning: 'Attenzione',
            info: 'Info'
        };
        
        toast.innerHTML = `
            <div class="toast-icon">${icons[type]}</div>
            <div class="toast-content">
                <div class="toast-title">${titles[type]}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        `;
        
        container.appendChild(toast);
        
        // Trigger animation
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });
        
        // Auto remove
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, duration);
    }

    // ========== ANIMATED COUNTER ========== 
    function animateCounter(element, target, duration = 1500) {
        const start = 0;
        const startTime = performance.now();
        
        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(start + (target - start) * easeOut);
            
            element.textContent = current;
            
            if (progress < 1) {
                requestAnimationFrame(update);
            } else {
                element.textContent = target;
            }
        }
        
        requestAnimationFrame(update);
    }

    // ========== FILTER BY STATUS ========== 
    let currentStatusFilter = '';

    function filterByStatus(status) {
        currentStatusFilter = status;
        
        // Update active card
        document.querySelectorAll('.summary-card').forEach(card => {
            card.classList.remove('active');
            if (card.dataset.status === status) {
                card.classList.add('active');
            }
        });
        
        // Filter cards
        const cards = document.querySelectorAll('.permuta-card');
        let visibleCount = 0;
        
        cards.forEach((card, index) => {
            const cardStatus = card.dataset.status;
            const shouldShow = status === '' || cardStatus === status;
            
            if (shouldShow) {
                card.style.display = '';
                card.style.animationDelay = `${visibleCount * 0.05}s`;
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Update count badge
        const countBadge = document.querySelector('.permute-count');
        if (countBadge) {
            countBadge.textContent = `${visibleCount} permute`;
        }
    }

    // ========== TOGGLE CARD MENU ========== 
    function toggleCardMenu(event, permutaId) {
        event.stopPropagation();
        
        // Close all other menus
        document.querySelectorAll('.card-popup.show').forEach(popup => {
            if (popup.id !== `popup-card-${permutaId}`) {
                popup.classList.remove('show');
            }
        });
        
        const popup = document.getElementById(`popup-card-${permutaId}`);
        popup.classList.toggle('show');
    }

    // Close popups on click outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.card-actions-wrapper')) {
            document.querySelectorAll('.card-popup.show').forEach(popup => {
                popup.classList.remove('show');
            });
        }
    });

    // ========== 3D TILT EFFECT FOR CARDS ========== 
    function init3DTilt() {
        const cards = document.querySelectorAll('.permuta-card');
        
        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;
                
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-8px)`;
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    }

    // ========== PARTICLE PARALLAX ========== 
    function initParticleParallax() {
        const particles = document.querySelectorAll('.particle');
        
        document.addEventListener('mousemove', (e) => {
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;
            
            particles.forEach((particle, index) => {
                const speed = (index + 1) * 10;
                const xOffset = (x - 0.5) * speed;
                const yOffset = (y - 0.5) * speed;
                
                particle.style.transform = `translate(${xOffset}px, ${yOffset}px)`;
            });
        });
    }

    // ========== RIPPLE EFFECT ========== 
    function initRippleEffect() {
        document.querySelectorAll('.btn, .btn-action, .summary-card').forEach(button => {
            button.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const ripple = document.createElement('span');
                ripple.style.cssText = `
                    position: absolute;
                    background: rgba(255,255,255,0.4);
                    border-radius: 50%;
                    width: 100px;
                    height: 100px;
                    left: ${x - 50}px;
                    top: ${y - 50}px;
                    transform: scale(0);
                    animation: rippleAnim 0.6s ease-out;
                    pointer-events: none;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });
        
        // Add ripple animation if not exists
        if (!document.getElementById('rippleStyle')) {
            const style = document.createElement('style');
            style.id = 'rippleStyle';
            style.textContent = `
                @keyframes rippleAnim {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }

    // ========== INITIALIZE ON DOM READY ========== 
    document.addEventListener('DOMContentLoaded', function() {
        // Animate counters
        document.querySelectorAll('.summary-value[data-count]').forEach(el => {
            const target = parseInt(el.dataset.count) || 0;
            animateCounter(el, target, 1200);
        });
        
        // Initialize effects
        init3DTilt();
        initParticleParallax();
        initRippleEffect();
        
        // Highlight active nav link
        const currentPath = window.location.pathname.split('/').pop();
        document.querySelectorAll('nav ul li a').forEach(link => {
            if (link.getAttribute('href')?.split('/').pop() === currentPath) {
                link.classList.add('active-link');
            }
        });
    });

    // ========== LEGACY SUPPORT ========== 
    const initialPermuteData = <?php echo json_encode($permute_data); ?>;
    let currentModalPermutaId = null;

    // --- Helper Functions ---
    let messageTimeout;
    function showMessage(message, isError = false) {
        // Use new toast system instead
        showToast(message, isError ? 'error' : 'success');
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    }

    function ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // function escapeHtml(text) { // Not strictly needed when data comes from json_encode and then used as textContent
    //     const str = String(text);
    //     const map = {
    //         '&': '&amp;',
    //         '<': '&lt;',
    //         '>': '&gt;',
    //         '"': '&quot;',
    //         "'": '&#039;'
    //     };
    //     return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    // }

    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function formatDateTime(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    // This function already exists in PHP, but is also used for JS manipulation of status classes
    // function getPermutaStatusClasses(status) {
    //     switch (status) {
    //         case 'In Trattativa': return 'in_trattativa';
    //         case 'Accettata': return 'accettata';
    //         case 'Rifiutata': return 'rifiutata';
    //         case 'Completata': return 'completata';
    //         case 'Annullata': return 'annullata';
    //         default: return 'in_trattativa';
    //     }
    // }

    // --- Logica Modali (da storico_riparazioni.php, adattata per permute) ---
    const mainModal = document.getElementById('mainModal');
    const deletePermutaModalContent = document.getElementById('deletePermutaModalContent');
    const editPermutaModalContent = document.getElementById('editPermutaModalContent');
    const viewPermutaModalContent = document.getElementById('viewPermutaModalContent');
    const allegatiPermutaModalContent = document.getElementById('allegatiPermutaModalContent');
    const barcodePermutaModalContent = document.getElementById('barcodePermutaModalContent');
    const inviaEmailPermutaModalContent = document.getElementById('inviaEmailPermutaModalContent');
    const privacyPermutaModalContent = document.getElementById('privacyPermutaModalContent');


    function openModal(modalContentId, permutaId = null) {
        closeAllPopups(); // Chiude qualsiasi menu a tendina delle azioni aperto
        
        // Nasconde prima tutti i contenuti dei modali
        deletePermutaModalContent.classList.add('hidden');
        editPermutaModalContent.classList.add('hidden');
        viewPermutaModalContent.classList.add('hidden');
        allegatiPermutaModalContent.classList.add('hidden');
        barcodePermutaModalContent.classList.add('hidden');
        inviaEmailPermutaModalContent.classList.add('hidden');
        privacyPermutaModalContent.classList.add('hidden');

        // Mostra il contenuto del modale richiesto
        const modalToShow = document.getElementById(modalContentId);
        if (modalToShow) {
            modalToShow.classList.remove('hidden');
        }

        mainModal.classList.remove('hidden');
        requestAnimationFrame(() => mainModal.classList.add('show'));
        currentModalPermutaId = permutaId;
    }

    function closeModal() {
        mainModal.classList.remove('show');
        setTimeout(() => {
            mainModal.classList.add('hidden');
            deletePermutaModalContent.classList.add('hidden');
            editPermutaModalContent.classList.add('hidden');
            viewPermutaModalContent.classList.add('hidden');
            allegatiPermutaModalContent.classList.add('hidden');
            barcodePermutaModalContent.classList.add('hidden');
            inviaEmailPermutaModalContent.classList.add('hidden');
            privacyPermutaModalContent.classList.add('hidden');
        }, 300);
        currentModalPermutaId = null;
    }

    mainModal.addEventListener('click', (e) => {
        if (e.target === mainModal) {
            closeModal();
        }
    });

    // Funzioni per l'apertura dei modali delle azioni
    window.openPrintPermutaModal = function(permutaId) {
        // Apre stampa_permuta.php in una nuova finestra, passando l'ID della permuta
        window.open(`stampa_permuta.php?id_permuta=${permutaId}`, '_blank');
    };

    window.openEditPermutaModal = function(permutaId) {
        const permuta = initialPermuteData.find(p => p.id == permutaId);
        if (permuta) {
            document.getElementById('editPermutaId').textContent = permutaId;
            document.getElementById('editPermutaHiddenId').value = permutaId;
            document.getElementById('editPermutaCliente').value = permuta.cliente || '';
            document.getElementById('editPermutaTelefono').value = permuta.telefono || permuta.telefono_cliente || '';
            document.getElementById('editPermutaProgressivo').value = permuta.progressivo || '';
            document.getElementById('editPermutaData').value = permuta.data ? permuta.data.split(' ')[0] : ''; // Only date part
            document.getElementById('editPermutaModelloNuovo').value = permuta.modello_nuovo || '';
            document.getElementById('editPermutaImeiNuovo').value = permuta.imei_nuovo || '';
            document.getElementById('editPermutaNoteNuovo').value = permuta.note_nuovo || '';
            document.getElementById('editPermutaPrezzoNuovo').value = parseFloat(permuta.prezzo_nuovo || 0).toFixed(2);
            document.getElementById('editPermutaCostoProdotto').value = parseFloat(permuta.costo_prodotto || 0).toFixed(2);
            document.getElementById('editPermutaModelloUsato').value = permuta.modello_usato || '';
            document.getElementById('editPermutaImeiUsato').value = permuta.imei_usato || '';
            document.getElementById('editPermutaNoteUsato').value = permuta.note_usato || '';
            document.getElementById('editPermutaPrezzoPermuta').value = parseFloat(permuta.prezzo_permuta || 0).toFixed(2);
            document.getElementById('editPermutaCostoRiparazione').value = parseFloat(permuta.costo_riparazione || 0).toFixed(2);
            document.getElementById('editPermutaCostoAccessori').value = parseFloat(permuta.costo_accessori || 0).toFixed(2);
            document.getElementById('editPermutaDifferenza').value = parseFloat(permuta.differenza || 0).toFixed(2);
            document.getElementById('editPermutaPrezzoVendita').value = parseFloat(permuta.prezzo_vendita || 0).toFixed(2);
            document.getElementById('editPermutaStatus').value = permuta.status || 'In Trattativa';
            document.getElementById('editPermutaTestOk').value = permuta.test_ok || '';
            document.getElementById('editPermutaNoteGenerali').value = permuta.note_generali || '';
            
            openModal('editPermutaModalContent', permutaId);
        } else {
            showMessage('Permuta non trovata per la modifica!', true);
        }
    };

    window.savePermuta = async function() {
        showMessage('Salvataggio modifiche in corso...', false);
        const form = document.getElementById('editPermutaForm');
        const formData = new FormData(form);

        try {
            const response = await fetch('update_permuta.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
            }

            const result = await response.json();

            if (result.success) {
                showMessage(result.message, false);
                closeModal();
                // Ricarica la pagina per riflettere i dati aggiornati dalla sessione PHP
                location.reload(); 
            } else {
                showMessage(result.message, true);
            }
        } catch (error) {
            console.error('Errore durante il salvataggio della permuta:', error);
            showMessage(`Errore di rete o server durante il salvataggio: ${error.message}`, true);
        }
    };

    window.openViewPermutaModal = function(permutaId) {
        const permuta = initialPermuteData.find(p => p.id == permutaId);
        if (permuta) {
            document.getElementById('viewPermutaId').textContent = permutaId;
            document.getElementById('viewPermutaDetailId').textContent = permuta.id;
            document.getElementById('viewPermutaDetailProgressivo').textContent = permuta.progressivo || 'N/D';
            document.getElementById('viewPermutaDetailData').textContent = formatDate(permuta.data);
            document.getElementById('viewPermutaDetailCliente').textContent = permuta.cliente || 'N/D';
            document.getElementById('viewPermutaDetailTelefono').textContent = permuta.telefono || permuta.telefono_cliente || 'N/D';
            document.getElementById('viewPermutaDetailModelloNuovo').textContent = permuta.modello_nuovo || 'N/D';
            document.getElementById('viewPermutaDetailImeiNuovo').textContent = permuta.imei_nuovo || 'N/D';
            document.getElementById('viewPermutaDetailNoteNuovo').textContent = permuta.note_nuovo || 'N/D';
            document.getElementById('viewPermutaDetailPrezzoNuovo').textContent = formatCurrency(permuta.prezzo_nuovo);
            document.getElementById('viewPermutaDetailCostoProdotto').textContent = formatCurrency(permuta.costo_prodotto);
            document.getElementById('viewPermutaDetailModelloUsato').textContent = permuta.modello_usato || 'N/D';
            document.getElementById('viewPermutaDetailImeiUsato').textContent = permuta.imei_usato || 'N/D';
            document.getElementById('viewPermutaDetailNoteUsato').textContent = permuta.note_usato || 'N/D';
            document.getElementById('viewPermutaDetailPrezzoPermuta').textContent = formatCurrency(permuta.prezzo_permuta);
            document.getElementById('viewPermutaDetailCostoRiparazione').textContent = formatCurrency(permuta.costo_riparazione);
            document.getElementById('viewPermutaDetailCostoAccessori').textContent = formatCurrency(permuta.costo_accessori);
            document.getElementById('viewPermutaDetailDifferenza').textContent = formatCurrency(permuta.differenza);
            document.getElementById('viewPermutaDetailPrezzoVendita').textContent = formatCurrency(permuta.prezzo_vendita);
            document.getElementById('viewPermutaDetailStatus').textContent = permuta.status || 'N/D';
            
            // Try to parse test_ok if it's a JSON string, otherwise use as-is
            let testOkContent = permuta.test_ok || 'N/D';
            try {
                const testData = JSON.parse(permuta.test_ok);
                if (typeof testData === 'object' && testData !== null) {
                    testOkContent = Object.entries(testData)
                        .map(([key, value]) => {
                            const esito = value.esito || 'N/D';
                            const note = value.note ? ` (${value.note})` : '';
                            return `${ucfirst(key)}: ${esito}${note}`;
                        })
                        .join('\n'); // Use newline for better readability in the modal
                }
            } catch (e) {
                // Not a valid JSON, use original string
            }
            document.getElementById('viewPermutaDetailTestOk').textContent = testOkContent;
            document.getElementById('viewPermutaDetailNoteGenerali').textContent = permuta.note_generali || 'N/D';

            openModal('viewPermutaModalContent', permutaId);
        } else {
            showMessage('Permuta non trovata per la visualizzazione!', true);
        }
    };

    window.openAllegatiPermutaModal = function(permutaId) {
        document.getElementById('allegatiPermutaId').textContent = permutaId;
        openModal('allegatiPermutaModalContent', permutaId);
        // Qui dovresti caricare la lista degli allegati reali tramite AJAX
    };

    window.openBarcodePermutaModal = function(permutaId) {
        document.getElementById('barcodePermutaId').textContent = permutaId;
        document.getElementById('barcodePermutaDisplayId').textContent = permutaId;
        document.getElementById('barcodePermutaValue').textContent = permutaId; // Il valore sarà l'ID
        
        // Genera il barcode SVG
        const barcodeSvgElement = document.getElementById('barcodeSvg');
        if (barcodeSvgElement) {
            JsBarcode(barcodeSvgElement, String(permutaId), {
                format: "CODE128",
                displayValue: true, // Mostra il valore sotto il barcode
                lineColor: "#34495e", // Colore delle barre
                width: 2,
                height: 80,
                flat: true // Per una resa più pulita
            });
        }

        openModal('barcodePermutaModalContent', permutaId);
    };

    // Funzione per stampare il contenuto del modale del barcode
    window.printBarcodePermutaContent = function() {
        const printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Stampa Barcode Permuta</title>');
        printWindow.document.write('<style>');
        printWindow.document.write(`
            body { font-family: 'Inter', sans-serif; margin: 0; padding: 15mm; text-align: center; }
            svg { display: block; margin: 20px auto; }
            p { font-size: 16px; color: #333; }
            @page { size: auto; margin: 15mm; }
            @media print {
                body { padding: 0; }
                .modal-header, .modal-footer { display: none; }
            }
        `);
        printWindow.document.write('</style></head><body>');
        
        // Clona l'SVG generato da JsBarcode
        const originalSvg = document.getElementById('barcodeSvg').cloneNode(true);
        printWindow.document.body.appendChild(originalSvg);

        printWindow.document.write('<h2>Barcode Permuta #' + document.getElementById('barcodePermutaId').textContent + '</h2>');
        printWindow.document.write('<p>Valore: ' + document.getElementById('barcodePermutaValue').textContent + '</p>');
        
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    };


    window.openEmailPermutaModal = function(permutaId) {
        const permuta = initialPermuteData.find(p => p.id == permutaId);
        document.getElementById('emailPermutaId').textContent = permutaId;
        
        document.getElementById('emailPermutaTo').value = permuta ? (permuta.telefono || permuta.telefono_cliente || '') : '';
        document.getElementById('emailPermutaSubject').value = `Aggiornamento Permuta #${permutaId}`;
        document.getElementById('emailPermutaBody').value = `Gentile cliente ${permuta.cliente || ''},\n\nLa sua permuta ID #${permutaId} (${permuta.modello_usato || 'dispositivo'}) è nello stato: ${ucfirst(permuta.status || 'N/D')}.\n\nCordiali saluti,\nTS Service`;
        
        openModal('inviaEmailPermutaModalContent', permutaId);

        // Event listener per il pulsante "Invia Email"
        document.getElementById('sendEmailPermutaButton').onclick = () => {
            showMessage('Funzionalità di invio email in sviluppo. Dati:', false);
            console.log('Invio Email:', {
                to: document.getElementById('emailPermutaTo').value,
                subject: document.getElementById('emailPermutaSubject').value,
                body: document.getElementById('emailPermutaBody').value
            });
            // Qui andrebbe la richiesta AJAX a un backend per l'invio reale dell'email
            closeModal();
        };
    };

    window.openPrivacyPermutaModal = function(permutaId) {
        document.getElementById('privacyPermutaId').textContent = permutaId;
        openModal('privacyPermutaModalContent', permutaId);
        // Il contenuto della privacy è statico per ora
    };

    // Funzione per aprire il modale di eliminazione permuta
    window.openDeletePermutaModal = function(permutaId) {
        document.getElementById('deletePermutaId').textContent = permutaId;
        document.getElementById('deletePermutaIdConfirm').textContent = permutaId; // Anche qui
        openModal('deletePermutaModalContent', permutaId);
        // Allega il listener del click per il pulsante di conferma eliminazione
        document.getElementById('confirmDeletePermutaButton').onclick = () => confirmDeletePermuta(permutaId);
    };

    // Funzione per confermare ed eseguire l'eliminazione (AJAX reale)
    async function confirmDeletePermuta(permutaId) {
        console.log('Conferma eliminazione per permuta ID:', permutaId);
        showMessage('Eliminazione in corso...', false);

        try {
            const formData = new FormData();
            formData.append('permuta_id', permutaId);

            const response = await fetch('elimina_permuta.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Errore HTTP! Stato: ${response.status}, Messaggio: ${errorText}`);
            }

            const result = await response.json();

            if (result.success) {
                showMessage('Permuta eliminata con successo!', false);
                closeModal();
                location.reload(); // Ricarica la pagina per riflettere l'eliminazione
            } else {
                showMessage(`Errore nell'eliminazione: ${result.message}`, true);
            }
        } catch (error) {
            console.error('Errore AJAX eliminazione permuta:', error);
            showMessage(`Errore di rete o server durante l'eliminazione: ${error.message}`, true);
        }
    }


    // --- Logica Toggle Popup (da storico_riparazioni.php) ---
    document.querySelectorAll('.btn-actions').forEach(button => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const expanded = button.getAttribute('aria-expanded') === 'true';
            closeAllPopups();

            if (!expanded) {
                button.setAttribute('aria-expanded', 'true');
                const popup = document.getElementById(button.getAttribute('aria-controls'));
                popup.classList.add('show');
                
                const parentRow = button.closest('.custom-table-row');
                if (parentRow) {
                    parentRow.classList.add('z-index-active-row');
                }
                popup.querySelector('[role="menuitem"]').focus();
            }
        });
    });

    function closeAllPopups() {
        document.querySelectorAll('.btn-actions').forEach(btn => btn.setAttribute('aria-expanded', 'false'));
        document.querySelectorAll('.popup').forEach(popup => popup.classList.remove('show'));
        document.querySelectorAll('.custom-table-row.z-index-active-row').forEach(row => {
            row.classList.remove('z-index-active-row');
        });
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.actions-wrapper')) {
            closeAllPopups();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllPopups();
            closeModal();
        }
    });

    // Gestione ordinamento tabella (da storico_riparazioni.php)
    document.querySelectorAll('.custom-table-head div[role="columnheader"]').forEach(header => {
        header.addEventListener('click', () => {
            const sortByMap = {
                'id': 'id',
                'data': 'data',
                'cliente': 'cliente',
                'modello nuovo': 'modello_nuovo',
                'modello usato': 'modello_usato',
                'prezzo nuovo': 'prezzo_nuovo',
                'prezzo permuta': 'prezzo_permuta',
                'status': 'status'
            };
            const headerText = header.textContent.trim().toLowerCase();
            const sortBy = sortByMap[headerText] || 'created_at'; // Default to created_at if not explicitly mapped
            let currentOrderDir = 'DESC';

            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.get('orderBy') === sortBy) {
                currentOrderDir = urlParams.get('orderDir') === 'ASC' ? 'DESC' : 'ASC';
            }

            urlParams.set('orderBy', sortBy);
            urlParams.set('orderDir', currentOrderDir);
            window.location.search = urlParams.toString();
        });
    });

    // Script per evidenziare il link attivo nell'header
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('nav ul li a');

        navLinks.forEach(link => {
            const linkPath = link.getAttribute('href');
            const linkFileName = linkPath.split('/').pop();
            if (linkFileName === currentPath) {
                link.classList.add('active-link');
            }
        });
    });

</script>
</body>
</html>
<?php
// Chiusura della connessione al database alla fine dello script
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
?>
