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
        $whereClause = " WHERE cliente LIKE ? OR modello_nuovo LIKE ? OR imei_nuovo LIKE ? OR modello_usato LIKE ? OR imei_usato LIKE ? OR telefono_cliente LIKE ? OR progressivo LIKE ?";
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
  <title>Elenco Permute</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <!-- JsBarcode CDN per la generazione di codici a barre -->
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    /* Variabili CSS per il tema verde e stili generali (da storico_riparazioni.php) */
    :root {
        --brand-green: #28a745;        /* Base Green */
        --brand-green-dark: #1e8449;   /* Darker shade for gradients */
        --brand-green-light: #e0f2e8;  /* Very light green for backgrounds/hovers */
        --brand-green-accent: #34d399; /* A brighter, more lively green for accents */
        --brand-green-text: #065f46;   /* Darker green for text on light backgrounds */
        --brand-green-hover-bg: #d1fae5; /* Very light green for hover backgrounds */

        --bg-color-page: #f3f4f6; /* Consistent background for the entire page */
        --text-color-primary: #1f2937; /* Darker primary text for readability */
        --text-color-secondary: #6b7280; /* Muted text for secondary info */
        --border-color-light: #e5e7eb; /* Light border for subtle separation */
        --card-bg: #fff;              /* White background for cards */
        --card-radius: 0.75rem;       /* Consistent radius for elements */
        --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); /* Consistent shadow */

        /* Specific status colors for Permute */
        --status-in-trattativa: #2563eb; /* Blue */
        --status-accettata: #f59e0b;     /* Orange */
        --status-rifiutata: #dc2626;     /* Red */
        --status-completata: #10b981;    /* Green */
        --status-annullata: #6b7280;     /* Grey */

        /* Specific styles for table headers and rows */
        --table-header-bg: linear-gradient(135deg, var(--brand-green), var(--brand-green-dark));
        --table-row-hover-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        --table-border: 1px solid var(--border-color-light);
    }

    body {
        margin: 0;
        font-family: 'Inter', sans-serif;
        background: var(--bg-color-page);
        color: var(--text-color-primary);
        padding-top: 90px; /* Space for top-bar */
        line-height: 1.6;
    }

    /* Modifica per rendere più ampio (come storico_riparazioni.php) */
    .main-content-container {
        max-width: 1400px; /* Aumentato per un layout più ampio */
        margin: 2rem auto; /* Aumentato il margine verticale */
        padding: 2rem; /* Aumentato il padding */
        background-color: var(--card-bg);
        border-radius: var(--card-radius);
        box-shadow: var(--card-shadow);
    }

    h1 {
        text-align: center;
        margin-bottom: 2rem; /* Aumentato il margine inferiore */
        font-weight: 700;
        color: var(--text-color-primary);
        font-size: 2.5rem; /* Aumentato il font size */
    }

    /* Stili per la barra di ricerca/filtro (da storico_riparazioni.php) */
    .filter-search-card {
        background-color: var(--card-bg);
        padding: 1.5rem; /* Aumentato il padding */
        border-radius: var(--card-radius);
        margin-bottom: 1.5rem; /* Aumentato il margine inferiore */
        box-shadow: var(--card-shadow);
        border: 1px solid var(--border-color-light);
    }
    .filter-search-card h2 {
        font-size: 1.5rem; /* Aumentato il font size */
        font-weight: 600;
        color: var(--text-color-primary);
        margin-bottom: 1.2rem; /* Aumentato il margine inferiore */
    }
    .filter-search-card label {
        font-size: 0.9rem; /* leggermente aumentato */
        font-weight: 500;
        color: var(--text-color-secondary);
    }
    .filter-search-card input[type="text"] {
        width: 100%;
        padding: 0.75rem; /* Aumentato il padding */
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 1rem; /* Aumentato il font size */
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .filter-search-card input[type="text"]:focus {
        border-color: var(--brand-green);
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
        outline: none;
    }
    .filter-search-card button[type="submit"] {
        background-color: var(--brand-green);
        color: white;
        font-weight: 700;
        padding: 0.6rem 1rem; /* Aumentato il padding */
        border-radius: 0.5rem; /* Reintrodotto il bordo arrotondato */
        border: none;
        transition: background-color 0.2s ease;
        box-shadow: var(--card-shadow); /* Reintrodotto l'ombra */
    }
    .filter-search-card button[type="submit"]:hover {
        background-color: var(--brand-green-dark);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15); /* Ombra più pronunciata al hover */
    }
    .filter-search-card a.reset-btn {
        background-color: #e5e7eb;
        color: #4b5563;
        font-weight: 600;
        padding: 0.6rem 1rem; /* Aumentato il padding */
        border-radius: 0.5rem; /* Reintrodotto il bordo arrotondato */
        border: none;
        transition: background-color 0.2s ease;
        box-shadow: var(--card-shadow); /* Reintrodotto l'ombra */
    }
    .filter-search-card a.reset-btn:hover {
        background-color: #d1d5db;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    /* Contenitore della tabella (da storico_riparazioni.php) */
    .table-container-card {
        border-radius: var(--card-radius);
        box-shadow: var(--card-shadow);
        overflow-x: auto;
        border: 1px solid var(--border-color-light);
        background-color: var(--card-bg);
        padding: 1.5rem; /* Aumentato il padding */
    }
    .table-container-card h2 {
        font-size: 1.3rem; /* Aumentato il font size */
        font-weight: 600;
        color: var(--text-color-primary);
        margin-bottom: 1.2rem; /* Aumentato il margine inferiore */
    }

    /* CUSTOM TABLE GRID STYLES (Adattato per permute e reso più ampio) */
    .custom-table-grid {
        display: flex;
        flex-direction: column;
        gap: 0.8rem; /* Aumentato il gap tra le righe */
    }

    .custom-table-head,
    .custom-table-row {
        display: grid;
        /* Colonne adattate per la tabella permute - Maggiori larghezze */
        grid-template-columns: 
            50px    /* ID */
            110px   /* Data */
            1.6fr   /* Cliente */
            1.6fr   /* Modello Nuovo */
            1.6fr   /* Modello Usato */
            120px   /* Prezzo Nuovo */
            120px   /* Prezzo Permuta */
            110px   /* Status */
            150px;  /* Azioni */
        align-items: center;
        padding: 0.8rem 0.6rem; /* Aumentato il padding delle celle */
        border-radius: 0.5rem; /* Aumentato il border radius */
        font-size: 0.9rem; /* Aumentato il font size */
        color: var(--text-color-primary);
        position: relative;
        z-index: 1;
    }

    .custom-table-head {
        font-weight: 600;
        color: white;
        background: var(--table-header-bg);
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.25);
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .custom-table-row {
        background: var(--card-bg);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--border-color-light);
        transition: transform 0.2s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.2s cubic-bezier(0.2, 0.8, 0.2, 1), background-color 0.2s ease;
        cursor: default;
    }
    .custom-table-row:hover {
        transform: translateY(-2px);
        box-shadow: var(--table-row-hover-shadow);
        background-color: var(--brand-green-hover-bg);
    }

    /* New class for active row z-index */
    .custom-table-row.z-index-active-row {
        z-index: 10;
    }

    /* Stato badge (adattato per permute e reso più grande) */
    .status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.3rem 0.8rem; /* Aumentato il padding */
        border-radius: 999px;
        font-size: 0.75rem; /* Aumentato il font size */
        font-weight: 600;
        white-space: nowrap;
        box-shadow: 0 1px 4px rgba(0,0,0,0.1);
    }
    .status.in_trattativa { background-color: var(--status-in-trattativa); color: white; }
    .status.accettata { background-color: var(--status-accettata); color: white; }
    .status.rifiutata { background-color: var(--status-rifiutata); color: white; }
    .status.completata { background-color: var(--status-completata); color: white; }
    .status.annullata { background-color: var(--status-annullata); color: white; }

    .price-display {
        font-weight: 600;
        color: var(--brand-green-text);
        text-align: center;
        font-size: 0.95rem; /* Aumentato il font size */
    }

    /* Bottone azioni e popup (da storico_riparazioni.php e reso più grande) */
    .actions-wrapper {
        position: relative;
        user-select: none;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
    }
    .btn-actions {
        background-color: #e5e7eb;
        color: #4b5563;
        border: none;
        padding: 0.4rem 0.8rem; /* Aumentato il padding */
        border-radius: 0.5rem; /* Aumentato il border radius */
        font-weight: 500;
        font-size: 0.8rem; /* Aumentato il font size */
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem; /* Aumentato il gap */
    }
    .btn-actions:hover {
        background-color: #d1d5db;
        color: #1f2937;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }
    .btn-actions svg {
        width: 0.8rem; /* Aumentato la dimensione dell'icona */
        height: 0.8rem;
        transition: transform 0.2s ease;
    }
    .btn-actions.open svg {
        transform: rotate(180deg);
    }

    .popup {
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: var(--card-bg);
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        border-radius: 0.5rem; /* Aumentato il border radius */
        width: 150px; /* Aumentata la larghezza del popup */
        display: none;
        z-index: 999;
        overflow: hidden;
        opacity: 0;
        transform: translateY(10px) translateX(-50%); /* Aumentato il translateY */
        transition: opacity 0.3s ease, transform 0.3s ease;
        border: 1px solid var(--border-color-light);
    }
    .popup.show {
        display: block;
        opacity: 1;
        transform: translateY(0) translateX(-50%);
    }
    .popup ul {
        list-style: none;
        margin: 0; padding: 0;
    }
    .popup ul li {
        padding: 0.6rem 0.9rem; /* Aumentato il padding */
        cursor: pointer;
        font-size: 0.85rem; /* Aumentato il font size */
        font-weight: 500;
        color: var(--text-color-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem; /* Aumentato il gap */
        transition: background-color 0.15s ease;
    }
    .popup ul li:hover {
        background-color: var(--brand-green-light);
        color: var(--brand-green-text);
    }
    .popup ul li.delete {
        color: var(--status-rifiutata); /* Rosso per elimina */
    }
    .popup ul li.delete:hover {
        background-color: #fee2e2;
        color: #991b1b;
    }
    .popup ul li svg {
        width: 0.9rem; /* Aumentato la dimensione dell'icona */
        height: 0.9rem;
        fill: currentColor;
        stroke: currentColor;
    }

    /* Stili dei modali (da storico_riparazioni.php e reso più grande) */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.4);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    .modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }
    .modal-content {
        background-color: var(--card-bg);
        padding: 2rem; /* Aumentato il padding */
        border-radius: 1rem; /* Aumentato il border radius */
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        max-width: 90%;
        width: 900px; /* Aumentata la larghezza */
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        transform: translateY(-20px); /* Aumentato il translateY */
        transition: transform 0.3s ease;
    }
    .modal-overlay.show .modal-content {
        transform: translateY(0);
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color-light);
        padding-bottom: 1.2rem;
        margin-bottom: 1.2rem; /* Aumentato il margine inferiore */
    }
    .modal-header h2 {
        font-size: 1.6rem; /* Aumentato il font size */
        font-weight: 600;
        color: var(--text-color-primary);
        margin: 0;
    }
    .modal-close-button {
        background: none;
        border: none;
        font-size: 1.8rem; /* Aumentato il font size */
        color: #9ca3af;
        cursor: pointer;
        line-height: 1;
        transition: color 0.2s ease;
    }
    .modal-close-button:hover {
        color: #4b5563;
    }
    .modal-body {
        padding: 1.2rem 0; /* Aumentato il padding */
    }
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 1rem; /* Aumentato il gap */
        border-top: 1px solid var(--border-color-light);
        padding-top: 1.2rem; /* Aumentato il padding */
        margin-top: 1.2rem; /* Aumentato il margine superiore */
    }
    .modal-footer button {
        padding: 0.6rem 1.5rem; /* Aumentato il padding */
        border-radius: 0.6rem; /* Aumentato il border radius */
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    }
    .modal-footer .btn-cancel {
        background-color: #e5e7eb;
        color: #4b5563;
        border: 1px solid #d1d5db;
    }
    .modal-footer .btn-cancel:hover {
        background-color: #d1d5db;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
    }
    .modal-footer .btn-primary {
        background: linear-gradient(135deg, var(--brand-green), var(--brand-green-dark));
        color: white;
        border: none;
        box-shadow: 0 4px 10px rgba(40, 167, 69, 0.25);
    }
    .modal-footer .btn-primary:hover {
        background: linear-gradient(135deg, var(--brand-green-dark), var(--brand-green));
        box-shadow: 0 6px 15px rgba(34, 153, 84, 0.35);
        transform: translateY(-1px);
    }

    /* Form elements inside modal */
    .modal-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem; /* Aumentato il gap */
    }
    .modal-form-group {
        margin-bottom: 0.8rem; /* Aumentato il margine inferiore */
    }
    .modal-form-group label {
        display: block;
        font-size: 0.9rem; /* Aumentato il font size */
        font-weight: 500;
        color: var(--text-color-secondary);
        margin-bottom: 0.3rem; /* Aumentato il margine inferiore */
    }
    .modal-form-group input,
    .modal-form-group textarea,
    .modal-form-group select {
        width: 100%;
        padding: 0.6rem 0.8rem; /* Aumentato il padding */
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 1rem; /* Aumentato il font size */
        color: var(--text-color-primary);
        background-color: #f9fafb;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .modal-form-group input:focus,
    .modal-form-group textarea:focus,
    .modal-form-group select:focus {
        border-color: var(--brand-green);
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
        outline: none;
        background-color: #fff;
    }
    .modal-form-group textarea {
        min-height: 90px; /* Aumentato l'altezza minima */
        resize: vertical;
    }
    .modal-form-group input[readonly] {
        background-color: #eceff1;
        color: #000;
        cursor: not-allowed;
        opacity: 1;
    }

    /* Delete Modal Specifics */
    .delete-modal-content {
        width: 550px; /* Larghezza aumentata */
        text-align: center;
    }
    .delete-modal-content .modal-body {
        padding: 1.5rem 0; /* Aumentato il padding */
        font-size: 1.1rem; /* Aumentato il font size */
        color: var(--text-color-primary);
    }
    .delete-modal-content .modal-footer {
        justify-content: center;
    }
    .delete-modal-content .btn-primary {
        background: linear-gradient(135deg, var(--status-rifiutata), #dc2626); /* Red gradient */
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.25);
    }
    .delete-modal-content .btn-primary:hover {
        background: linear-gradient(135deg, #dc2626, var(--status-rifiutata));
        box-shadow: 0 6px 15px rgba(220, 38, 38, 0.35);
    }

    /* Message Box Styles (da storico_riparazioni.php) */
    .message-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        pointer-events: none;
        background-color: rgba(0,0,0,0.0);
        transition: background-color 0.3s ease;
    }
    .message-container.active {
        background-color: rgba(0,0,0,0.3);
    }
    .message-box {
        background-color: #ffffff;
        color: #333;
        padding: 1.8rem 3rem; /* Aumentato il padding */
        border-radius: 0.85rem; /* Aumentato il border radius */
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.25); /* Ombra più pronunciata */
        z-index: 10001;
        max-width: 90%;
        text-align: center;
        font-size: 1.2rem; /* Aumentato il font size */
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1.2rem; /* Aumentato il gap */
        opacity: 0;
        transform: translateY(-60px); /* Aumentato il translateY */
        transition: opacity 0.3s ease, transform 0.3s ease;
        pointer-events: auto;
    }
    .message-box.show {
        opacity: 1;
        transform: translateY(0);
    }
    .message-box.success {
        border: 2px solid var(--brand-green);
        color: var(--brand-green-text);
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

    /* Header styles (copiati dal gestionale principale) */
    .top-bar {
      background-color: var(--brand-green);
      color: white;
      padding: 30px 30px;
      font-size: 18px;
      width: 100vw;
      box-sizing: border-box;
      display: flex;
      align-items: center;
      gap: 150px;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1000;
      box-shadow: var(--card-shadow);
    }

    .logo {
      font-size: 36px;
      font-weight: bold;
      white-space: nowrap;
      color: white;
      text-decoration: none;
      cursor: pointer;
    }

    .logo:hover {
      color: white;
      text-decoration: none;
    }

    nav ul {
      list-style: none;
      margin: 0;
      padding: 0;
      display: flex;
      gap: 15px;
    }

    nav ul li {
      position: relative;
      padding-top: 5px;
      padding-bottom: 5px;
      margin-top: -5px;
      margin-bottom: -5px;
    }

    nav ul li button,
    nav ul li a {
      background-color: var(--card-bg);
      border: none;
      color: var(--text-color-primary);
      font-size: 16px;
      padding: 15px 30px;
      cursor: pointer;
      border-radius: 5px;
      user-select: none;
      text-decoration: none;
      display: block;
      white-space: nowrap;
      transition: all 0.2s ease;
    }

    nav ul li button:hover,
    nav ul li a:hover {
        background-color: #e2e6ea;
    }

    button.no-arrow::after {
      content: "";
    }

    nav ul li.has-dropdown > button::after,
    nav ul li.has-dropdown > a::after {
      content: " \25BC";
      font-size: 10px;
      color: var(--text-color-primary);
      margin-left: 8px;
    }

    nav ul li ul.dropdown {
      display: none;
      position: absolute;
      top: 100%;
      right: 0;
      background-color: var(--card-bg);
      min-width: 200px;
      border-radius: 8px;
      box-shadow: var(--card-shadow);
      padding: 0;
      margin-top: 0;
      list-style: none;
      z-index: 1000;
      transform-origin: top;
      animation: scaleYIn 0.3s ease;
    }

    @keyframes scaleYIn {
        from { opacity: 0; transform: scaleY(0.8); }
        to { opacity: 1; transform: scaleY(1); }
    }

    nav ul li:hover > ul.dropdown {
      display: block;
    }

    nav ul li ul.dropdown li a,
    nav ul li ul.dropdown li button {
      padding: 10px 15px;
      color: var(--text-color-primary);
      background-color: transparent;
      width: 100%;
      text-align: left;
      border-radius: 0;
      font-size: 15px;
    }

    nav ul li ul.dropdown li a:hover,
    nav ul li ul.dropdown li button:hover {
      background-color: var(--brand-green);
      color: white;
    }

    nav ul li ul.dropdown li.has-submenu > a::after {
      content: " \25B6";
      float: right;
      font-size: 10px;
      margin-left: 10px;
      color: var(--text-color-secondary);
    }

    nav ul li ul.dropdown li ul.submenu {
      display: none;
      position: absolute;
      top: 0;
      left: 100%;
      background-color: var(--card-bg);
      min-width: 180px;
      border-radius: 8px;
      box-shadow: var(--card-shadow);
      padding: 0;
      margin: 0;
      list-style: none;
      z-index: 1100;
      transform-origin: left;
      animation: scaleXIn 0.3s ease;
    }

    @keyframes scaleXIn {
        from { opacity: 0; transform: scaleX(0.8); }
        to { opacity: 1; transform: scaleX(1); }
    }

    nav ul li ul.dropdown li:hover > ul.submenu {
      display: block;
    }

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

  </style>
</head>
<body>
  <?php include 'header.php'; // Includi il tuo header ?>

  <div class="main-content-container">
    <h1>Elenco Permute</h1>

    <?php echo $message; // Mostra messaggi di sistema ?>

    <div class="filter-search-card">
        <h2>Cerca e Filtra Permute</h2>
        <form method="GET" action="storico_permute.php" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Cerca per Cliente, Modello, IMEI, Telefono o Progressivo</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Es. Mario Rossi, iPhone, IMEI123..." class="w-full p-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-base">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-grow bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-5 rounded-md transition duration-200 ease-in-out shadow-lg">
                    Cerca
                </button>
                <a href="storico_permute.php" class="flex-shrink-0 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-5 rounded-md transition duration-200 ease-in-out reset-btn">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="table-container-card">
      <h2>Lista Permute</h2>
      <div class="custom-table-grid" role="grid" aria-label="Elenco delle permute">

        <div class="custom-table-head" role="row">
          <div>ID</div>
          <div>Data</div>
          <div>Cliente</div>
          <div>Modello Nuovo</div>
          <div>Modello Usato</div>
          <div>Prezzo Nuovo</div>
          <div>Prezzo Permuta</div>
          <div>Status</div>
          <div>Azioni</div>
        </div>

        <?php if (!empty($permute_data)): ?>
          <?php foreach ($permute_data as $row): ?>
            <?php
            $dataVis = !empty($row['data']) ? date('d/m/Y', strtotime($row['data'])) : '-';
            $statusClass = getPermutaStatusClasses($row['status'] ?? '');
            $clienteNome = htmlspecialchars($row['cliente'] ?? 'N/A');
            $prezzoNuovo = formatCurrency((float)($row['prezzo_nuovo'] ?? 0));
            $prezzoPermuta = formatCurrency((float)($row['prezzo_permuta'] ?? 0));
            ?>
            <div class="custom-table-row" role="row" tabindex="0" aria-rowindex="<?= $row['id'] ?>">
              <div data-label="ID:"><?= htmlspecialchars($row['id']) ?></div>
              <div data-label="Data:"><?= $dataVis ?></div>
              <div data-label="Cliente:"><?= $clienteNome ?></div>
              <div data-label="Modello Nuovo:"><?= htmlspecialchars($row['modello_nuovo'] ?? 'N/A') ?></div>
              <div data-label="Modello Usato:"><?= htmlspecialchars($row['modello_usato'] ?? 'N/A') ?></div>
              <div data-label="Prezzo Nuovo:" class="price-display"><?= $prezzoNuovo ?></div>
              <div data-label="Prezzo Permuta:" class="price-display"><?= $prezzoPermuta ?></div>
              <div data-label="Status:">
                  <span class="status <?= $statusClass ?>">
                      <?= htmlspecialchars($row['status'] ?? 'N/A') ?>
                  </span>
              </div>
              <div class="actions-wrapper">
                <button class="btn-actions" aria-haspopup="true" aria-expanded="false" aria-controls="popup-permuta-<?= $row['id'] ?>" aria-label="Apri menu azioni permuta ID <?= $row['id'] ?>">
                  <span>Azioni</span>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.27a.75.75 0 01-.02-1.06z"/></svg>
                </button>
                <div class="popup" id="popup-permuta-<?= $row['id'] ?>" role="menu" aria-label="Azioni permuta <?= $row['id'] ?>">
                  <ul>
                    <li role="menuitem" tabindex="-1" onclick="openPrintPermutaModal(<?= $row['id'] ?>)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M2 2h12v10H2V2zm1 1v8h10V3H3z"/>
                        <path d="M5 12h6v1H5v-1z"/>
                      </svg>
                      Stampa
                    </li>
                    <li role="menuitem" tabindex="-1" onclick="openEditPermutaModal(<?= $row['id'] ?>)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M12.146.146a.5.5 0 01.708 0l3 3a.5.5 0 010 .708l-9.793 9.793a.5.5 0 01-.168.11l-5 2a.5.5 0 01-.65-.65l2-5a.5.5 0 01.11-.168L12.146.146zM11.207 2L4 9.207V11h1.793L14 3.793 11.207 2z"/>
                      </svg>
                      Modifica
                    </li>
                    <li role="menuitem" tabindex="-1" onclick="openViewPermutaModal(<?= $row['id'] ?>)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM8 13a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z"/>
                      </svg>
                      Visualizza
                    </li>
                    <li role="menuitem" tabindex="-1" onclick="openAllegatiPermutaModal(<?= $row['id'] ?>)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M4.5 3a.5.5 0 000 1h7a2.5 2.5 0 110 5h-1v1h1a3.5 3.5 0 100-7h-7z"/>
                            <path d="M4 7v6a2 2 0 104 0V7H4z"/>
                        </svg>
                        Allegati
                    </li>
                    <li role="menuitem" tabindex="-1" onclick="openBarcodePermutaModal(<?= $row['id'] ?>)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M1 2h1v12H1V2zm3 0h1v12H4V2zm2 0h1v12H6V2zm2 0h1v12H8V2zm2 0h1v12h-1V2zm2 0h1v12h-1V2z"/>
                        </svg>
                        Barcode
                    </li>
                    <li role="menuitem" tabindex="-1" onclick="openEmailPermutaModal(<?= $row['id'] ?>)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M0 4a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H2a2 2 0 01-2-2V4z"/>
                            <path fill-rule="evenodd" d="M.05 4.555L8 9.414l7.95-4.86A1 1 0 0015 4H1a1 1 0 00-.95.555z"/>
                        </svg>
                        Invia Email
                    </li>
                    <li role="menuitem" tabindex="-1" onclick="openPrivacyPermutaModal(<?= $row['id'] ?>)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 0a5 5 0 00-5 5v3a5 5 0 0010 0V5a5 5 0 00-5-5zM3 5a5 5 0 015-5 5 5 0 015 5v3a5 5 0 01-10 0V5z"/>
                            <path d="M8 8a2 2 0 110-4 2 2 0 010 4z"/>
                        </svg>
                        Privacy
                    </li>
                    <li role="menuitem" tabindex="-1" class="delete" onclick="openDeletePermutaModal(<?= $row['id'] ?>)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M5.5 5.5a.5.5 0 00-.707.707L7.293 8l-2.5 2.5a.5.5 0 10.707.707L8 8.707l2.5 2.5a.5.5 0 00.707-.707L8.707 8l2.5-2.5a.5.5 0 00-.707-.707L8 7.293 5.5 5.5z"/>
                        <path fill-rule="evenodd" d="M1 2.5A1.5 1.5 0 012.5 1h11A1.5 1.5 0 0115 2.5v1a.5.5 0 01-1 0v-1a.5.5 0 00-.5-.5h-11a.5.5 0 00-.5.5v1a.5.5 0 01-1 0v-1z"/>
                      </svg>
                      Elimina
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="custom-table-row">Nessuna permuta trovata.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Message Box Container -->
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

    <!-- Edit Permuta Modal Content -->
    <div id="editPermutaModalContent" class="modal-content hidden">
        <div class="modal-header">
            <h2>Modifica Permuta #<span id="editPermutaId"></span></h2>
            <button class="modal-close-button" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editPermutaForm">
                <input type="hidden" id="editPermutaHiddenId" name="id">
                <div class="modal-grid">
                    <div class="modal-form-group">
                        <label for="editPermutaCliente">Cliente</label>
                        <input type="text" id="editPermutaCliente" name="cliente" required>
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaTelefono">Telefono</label>
                        <input type="text" id="editPermutaTelefono" name="telefono">
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaProgressivo">Progressivo</label>
                        <input type="text" id="editPermutaProgressivo" name="progressivo" readonly>
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaData">Data</label>
                        <input type="date" id="editPermutaData" name="data" required>
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaModelloNuovo">Modello Nuovo</label>
                        <input type="text" id="editPermutaModelloNuovo" name="modello_nuovo">
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaImeiNuovo">IMEI Nuovo</label>
                        <input type="text" id="editPermutaImeiNuovo" name="imei_nuovo">
                    </div>
                    <div class="modal-form-group full-width">
                        <label for="editPermutaNoteNuovo">Note Nuovo</label>
                        <textarea id="editPermutaNoteNuovo" name="note_nuovo" rows="3"></textarea> <!-- Aumentato rows -->
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaPrezzoNuovo">Prezzo Nuovo (€)</label>
                        <input type="number" step="0.01" id="editPermutaPrezzoNuovo" name="prezzo_nuovo">
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaCostoProdotto">Costo Prodotto (€)</label>
                        <input type="number" step="0.01" id="editPermutaCostoProdotto" name="costo_prodotto">
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaModelloUsato">Modello Usato</label>
                        <input type="text" id="editPermutaModelloUsato" name="modello_usato">
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaImeiUsato">IMEI Usato</label>
                        <input type="text" id="editPermutaImeiUsato" name="imei_usato">
                    </div>
                    <div class="modal-form-group full-width">
                        <label for="editPermutaNoteUsato">Note Usato</label>
                        <textarea id="editPermutaNoteUsato" name="note_usato" rows="3"></textarea> <!-- Aumentato rows -->
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaPrezzoPermuta">Prezzo Permuta (€)</label>
                        <input type="number" step="0.01" id="editPermutaPrezzoPermuta" name="prezzo_permuta">
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaCostoRiparazione">Costo Riparazione Usato (€)</label>
                        <input type="number" step="0.01" id="editPermutaCostoRiparazione" name="costo_riparazione">
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaCostoAccessori">Costo Accessori (€)</label>
                        <input type="number" step="0.01" id="editPermutaCostoAccessori" name="costo_accessori">
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaDifferenza">Differenza da Pagare (€)</label>
                        <input type="number" step="0.01" id="editPermutaDifferenza" name="differenza">
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaPrezzoVendita">Prezzo di Vendita Finale (€)</label>
                        <input type="number" step="0.01" id="editPermutaPrezzoVendita" name="prezzo_vendita">
                    </div>
                    <div class="modal-form-group">
                        <label for="editPermutaStatus">Status</label>
                        <select id="editPermutaStatus" name="status">
                            <option value="In Trattativa">In Trattativa</option>
                            <option value="Accettata">Accettata</option>
                            <option value="Rifiutata">Rifiutata</option>
                            <option value="Completata">Completata</option>
                            <option value="Annullata">Annullata</option>
                        </select>
                    </div>
                    <div class="modal-form-group full-width">
                        <label for="editPermutaTestOk">Test Effettuati</label>
                        <textarea id="editPermutaTestOk" name="test_ok" rows="3"></textarea> <!-- Aumentato rows -->
                    </div>
                    <div class="modal-form-group full-width">
                        <label for="editPermutaNoteGenerali">Note Generali</label>
                        <textarea id="editPermutaNoteGenerali" name="note_generali" rows="3"></textarea> <!-- Aumentato rows -->
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Annulla</button>
            <button class="btn-primary" onclick="savePermuta()">Salva Modifiche</button>
        </div>
    </div>

    <!-- View Permuta Modal Content -->
    <div id="viewPermutaModalContent" class="modal-content hidden">
        <div class="modal-header">
            <h2>Dettagli Permuta #<span id="viewPermutaId"></span></h2>
            <button class="modal-close-button" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="permutaDetailsContent" class="p-2">
                <div class="view-modal-details">
                    <div class="view-modal-details-group"><strong>ID Permuta:</strong> <span id="viewPermutaDetailId"></span></div>
                    <div class="view-modal-details-group"><strong>Progressivo:</strong> <span id="viewPermutaDetailProgressivo"></span></div>
                    <div class="view-modal-details-group"><strong>Data:</strong> <span id="viewPermutaDetailData"></span></div>
                    <div class="view-modal-details-group"><strong>Cliente:</strong> <span id="viewPermutaDetailCliente"></span></div>
                    <div class="view-modal-details-group"><strong>Telefono:</strong> <span id="viewPermutaDetailTelefono"></span></div>
                    <div class="view-modal-details-group"><strong>Status:</strong> <span id="viewPermutaDetailStatus"></span></div>
                </div>

                <h3 class="view-modal-section-title">Dispositivo Nuovo</h3>
                <div class="view-modal-details">
                    <div class="view-modal-details-group"><strong>Modello:</strong> <span id="viewPermutaDetailModelloNuovo"></span></div>
                    <div class="view-modal-details-group"><strong>IMEI:</strong> <span id="viewPermutaDetailImeiNuovo"></span></div>
                    <div class="view-modal-details-group full-width-detail"><strong>Note:</strong> <span id="viewPermutaDetailNoteNuovo"></span></div>
                    <div class="view-modal-details-group"><strong>Prezzo:</strong> <span id="viewPermutaDetailPrezzoNuovo"></span></div>
                    <div class="view-modal-details-group"><strong>Costo Prodotto:</strong> <span id="viewPermutaDetailCostoProdotto"></span></div>
                </div>

                <h3 class="view-modal-section-title">Dispositivo Usato (Permuta)</h3>
                <div class="view-modal-details">
                    <div class="view-modal-details-group"><strong>Modello:</strong> <span id="viewPermutaDetailModelloUsato"></span></div>
                    <div class="view-modal-details-group"><strong>IMEI:</strong> <span id="viewPermutaDetailImeiUsato"></span></div>
                    <div class="view-modal-details-group full-width-detail"><strong>Note:</strong> <span id="viewPermutaDetailNoteUsato"></span></div>
                    <div class="view-modal-details-group"><strong>Prezzo Permuta:</strong> <span id="viewPermutaDetailPrezzoPermuta"></span></div>
                    <div class="view-modal-details-group"><strong>Costo Ricondizionamento:</strong> <span id="viewPermutaDetailCostoRiparazione"></span></div>
                </div>

                <h3 class="view-modal-section-title">Riepilogo Finanziario</h3>
                <div class="view-modal-details">
                    <div class="view-modal-details-group"><strong>Costo Accessori:</strong> <span id="viewPermutaDetailCostoAccessori"></span></div>
                    <div class="view-modal-details-group"><strong>Differenza da Pagare:</strong> <span id="viewPermutaDetailDifferenza"></span></div>
                    <div class="view-modal-details-group"><strong>Prezzo di Vendita Finale:</strong> <span id="viewPermutaDetailPrezzoVendita"></span></div>
                </div>

                <h3 class="view-modal-section-title">Note e Test</h3>
                <div class="view-modal-details">
                    <div class="view-modal-details-group full-width-detail"><strong>Test Effettuati:</strong> <span id="viewPermutaDetailTestOk"></span></div>
                    <div class="view-modal-details-group full-width-detail"><strong>Note Generali:</strong> <span id="viewPermutaDetailNoteGenerali"></span></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Chiudi</button>
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
    // Questa variabile deve essere popolata con i dati PHP come fatto sopra
    const initialPermuteData = <?php echo json_encode($permute_data); ?>;
    let currentModalPermutaId = null;

    // --- Helper Functions (da storico_riparazioni.php) ---
    let messageTimeout;
    function showMessage(message, isError = false) {
        console.log(`showMessage: ${isError ? 'ERROR' : 'INFO'} - ${message}`);

        const messageContainer = document.getElementById('messageContainer');
        const messageBox = document.getElementById('messageBox');
        
        clearTimeout(messageTimeout);

        messageBox.classList.remove('error', 'success', 'show');
        messageBox.style.animation = 'none'; 
        void messageBox.offsetWidth;

        let iconSvg = '';
        if (isError) {
            messageBox.classList.add('error');
            iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.38 3.375 2.07 3.375h14.006c1.69 0 2.936-1.875 2.069-3.375l-7.005-12.004a1.125 1.125 0 00-1.932 0l-7.005 12.004zM12 15.75h.007v.008H12v-.008z" />
                       </svg>`;
        } else {
            messageBox.classList.add('success');
            iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                       </svg>`;
        }
        messageBox.innerHTML = `${iconSvg} <span>${message}</span>`;
        
        messageContainer.classList.remove('hidden');
        messageContainer.classList.add('active');
        messageBox.classList.add('show');

        const displayDuration = isError ? 2000 : 3000; // Increased duration
        const fadeOutDuration = 500;

        messageTimeout = setTimeout(() => {
            messageBox.classList.remove('show');
            messageBox.style.animation = 'fadeOutAnimation 0.5s forwards';
            setTimeout(() => {
                messageContainer.classList.add('hidden');
                messageContainer.classList.remove('active');
            }, fadeOutDuration);
        }, displayDuration);
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

        mainModal.classList.add('show');
        currentModalPermutaId = permutaId;
    }

    function closeModal() {
        mainModal.classList.remove('show');
        setTimeout(() => {
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
            document.getElementById('editPermutaTelefono').value = permuta.telefono_cliente || ''; // Use telefono_cliente
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
            document.getElementById('viewPermutaDetailTelefono').textContent = permuta.telefono_cliente || 'N/D'; // Use telefono_cliente
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
        
        document.getElementById('emailPermutaTo').value = permuta ? permuta.telefono_cliente || '' : ''; // Use telefono_cliente
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
