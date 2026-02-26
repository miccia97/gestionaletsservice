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
    // TERMINA LO SCRIPT se db.php non esiste
    exit;
}
require_once 'db.php';

// Controlla subito se c'è stato un errore di connessione al database da db.php
// Se $conn non è stata inizializzata o è null, significa che la connessione è fallita.
if (!isset($conn) || $conn === null) {
    $db_error_message = isset($db_connection_error) ? $db_connection_error : 'Connessione al database non stabilita.';
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: " . htmlspecialchars($db_error_message, ENT_QUOTES, 'UTF-8') . "</div>";
    // TERMINA LO SCRIPTO se la connessione al DB fallisce
    exit;
}

// Inizializza la variabile message per l'uso nel HTML
$message = '';

// Controlla se ci sono messaggi da visualizzare dalla sessione
if (isset($_SESSION['message'])) {
    $sessionMessage = $_SESSION['message'];
    $sessionIsError = $_SESSION['isError'] ?? false;
    // Usa SweetAlert2 per i messaggi di sessione
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

// --- LOGICA PER RECUPERARE LE RIPARAZIONI (LISTA) ---
$riparazioni_data = []; // Nome user-friendly per JS
try {
    // Gestione ricerca
    $searchTerm = $_GET['search'] ?? '';
    $whereClause = '';
    $queryParams = [];
    $paramTypes = '';

    if (!empty($searchTerm)) {
        $whereClause = " WHERE r.diagnosi LIKE ? OR r.modello LIKE ? OR COALESCE(r.telefono, c.telefono) LIKE ? OR c.nome LIKE ? OR c.cognome LIKE ? OR r.id LIKE ?";
        $searchTermLike = '%' . $searchTerm . '%';
        array_push($queryParams, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike);
        $paramTypes .= 'ssssss';
    }

    $sql = "SELECT 
                r.id, r.cliente_id, c.nome AS cliente_nome, c.cognome AS cliente_cognome, 
                COALESCE(r.telefono, c.telefono) AS telefono, r.diagnosi, r.modello, 
                r.data_creazione, r.stato, r.costo_effettivo
            FROM riparazioni r
            LEFT JOIN clienti_nuovo c ON r.cliente_id = c.id" . $whereClause . " ORDER BY r.id DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) throw new mysqli_sql_exception("Errore nella preparazione della query: " . $conn->error);
    if (!empty($queryParams)) $stmt->bind_param($paramTypes, ...$queryParams);
    
    $stmt->execute();
    $result = $stmt->get_result();
    $riparazioni_data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    error_log("Errore Visualizza Riparazioni (SQL): " . $e->getMessage());
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Errore Database', 'Impossibile caricare le riparazioni.', 'error'); });</script>";
}

// --- RECUPERO DATI PER L'AUTOCOMPLETAMENTO E LE SCHEDE ---
$prodotti_catalogo_data = [];
try {
    $result_prodotti = $conn->query("SELECT id, nome, quantita FROM prodotti ORDER BY nome");
    if ($result_prodotti) $prodotti_catalogo_data = $result_prodotti->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) { error_log("Errore caricamento prodotti: " . $e->getMessage()); }

$riparazioni_movimenti_data = [];
try {
    $result_movimenti = $conn->query("SELECT ram.riparazione_id, ram.prodotto_id, ram.quantita_movimentata, p.nome AS product_name, ram.data_movimento FROM riparazioni_articoli_movimenti ram JOIN prodotti p ON ram.prodotto_id = p.id ORDER BY ram.data_movimento DESC");
    if ($result_movimenti) $riparazioni_movimenti_data = $result_movimenti->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) { error_log("Errore caricamento movimenti: " . $e->getMessage()); }

$riparazioni_storico_data = [];
try {
    $result_storico = $conn->query("SELECT id, riparazione_id, data_evento, evento_descrizione, utente FROM riparazioni_storico ORDER BY data_evento ASC");
    if ($result_storico) $riparazioni_storico_data = $result_storico->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) { error_log("Errore caricamento storico: " . $e->getMessage()); }

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
    <title>Storico Riparazioni</title>
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
  .summary-card.pending { border-color: var(--brand-orange); }
  .summary-card.pending::before { background: var(--brand-orange); }
  .summary-card.pending .icon { background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); color: var(--brand-orange); }
  
  .summary-card.in-progress { border-color: var(--brand-blue); }
  .summary-card.in-progress::before { background: var(--brand-blue); }
  .summary-card.in-progress .icon { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: var(--brand-blue); }
  
  .summary-card.completed { border-color: var(--brand-green); }
  .summary-card.completed::before { background: var(--brand-green); }
  .summary-card.completed .icon { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); color: var(--brand-green); }
  
  .summary-card.revenue { border-color: var(--brand-purple); }
  .summary-card.revenue::before { background: var(--brand-purple); }
  .summary-card.revenue .icon { background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); color: var(--brand-purple); }
  
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
  
  /* TABLE */
  .repairs-table { 
      width: 100%; 
      border-collapse: separate;
      border-spacing: 0 8px;
  }
  .repairs-table thead th {
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
  .repairs-table thead th:first-child { border-radius: 12px 0 0 12px; }
  .repairs-table thead th:last-child { border-radius: 0 12px 12px 0; }
  
  .repairs-table tbody tr {
      background: #fafafa;
      border-radius: 12px;
      transition: all 0.25s ease;
  }
  .repairs-table tbody tr:hover { 
      background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
      transform: scale(1.005);
      box-shadow: 0 4px 12px rgba(0,0,0,0.04);
  }
  .repairs-table tbody td { 
      padding: 1rem; 
      border: none;
      vertical-align: middle;
      font-size: 0.9rem;
  }
  .repairs-table tbody td:first-child { border-radius: 12px 0 0 12px; }
  .repairs-table tbody td:last-child { border-radius: 0 12px 12px 0; }
  
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
  
  .status-in-attesa { background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); color: #c2410c; }
  .status-in-attesa::before { background-color: var(--brand-orange); }
  .status-in-lavorazione { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #1d4ed8; }
  .status-in-lavorazione::before { background-color: var(--brand-blue); animation: none; }
  .status-completata { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); color: #15803d; }
  .status-completata::before { background-color: var(--brand-green); animation: none; }
  .status-consegnata { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); color: #166534; }
  .status-consegnata::before { background-color: #16a34a; animation: none; }
  .status-annullata { background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); color: #991b1b; }
  .status-annullata::before { background-color: var(--brand-red); animation: none; }
  
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
      width: 900px;
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
  
  /* MODAL TABS */
  .modal-content .tab-buttons { 
      display: flex; 
      justify-content: center;
      gap: 10px;
      padding: 1.25rem 2rem;
      background: #f8fafc;
      border-bottom: none;
      margin-bottom: 0;
      flex-shrink: 0;
  }
  .modal-content .tab-button { 
      padding: 0.75rem 1.5rem; 
      cursor: pointer; 
      border: none; 
      background: white;
      border-radius: 12px;
      font-weight: 600; 
      font-size: 0.85rem;
      color: var(--text-secondary); 
      margin-bottom: 0;
      transition: all 0.25s ease;
      box-shadow: 0 2px 6px rgba(0,0,0,0.06);
  }
  .modal-content .tab-button:hover:not(.active) {
      background: #f1f5f9;
      color: var(--text-primary);
      transform: translateY(-2px);
  }
  .modal-content .tab-button.active { 
      color: white; 
      background: linear-gradient(135deg, var(--brand-green) 0%, var(--brand-green-dark) 100%);
      box-shadow: 0 6px 16px rgba(34, 197, 94, 0.3);
      transform: translateY(-2px);
  }
  
  .modal-content form,
  .modal-content > div:not(.modal-header):not(.modal-footer):not(.tab-buttons) {
      flex: 1;
      overflow-y: auto;
      padding: 1.5rem 2rem;
      background: #f8fafc;
  }
  
  .modal-content .tab-content { 
      display: none; 
  } 
  .modal-content .tab-content.active { 
      display: block; 
  }
  
  /* FORM FIELDS */
  .modal-content label.font-medium,
  .modal-content label {
      display: block;
      font-weight: 700;
      font-size: 0.7rem;
      color: var(--text-secondary);
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
  }
  .modal-content input[type="text"],
  .modal-content input[type="number"],
  .modal-content input[type="email"],
  .modal-content select,
  .modal-content textarea {
      width: 100%;
      padding: 0.85rem 1rem;
      border: 2px solid var(--border-color);
      border-radius: 12px;
      font-size: 0.95rem;
      transition: all 0.25s ease;
      background: white;
      margin-top: 0;
      font-weight: 500;
  }
  .modal-content input:hover,
  .modal-content select:hover,
  .modal-content textarea:hover {
      border-color: #cbd5e1;
  }
  .modal-content input:focus,
  .modal-content select:focus,
  .modal-content textarea:focus {
      border-color: var(--brand-green);
      outline: none;
      box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
  }
  
  /* MODAL FOOTER */
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

  .tab-buttons { 
      display: flex; 
      border-bottom: 2px solid var(--border-color); 
      margin-bottom: 1.5rem; 
  }
  .tab-button { 
      padding: 0.75rem 1.25rem; 
      cursor: pointer; 
      border: none; 
      background: none; 
      font-weight: 600; 
      color: var(--text-secondary); 
      border-bottom: 3px solid transparent; 
      margin-bottom: -2px; 
  }
  .tab-button.active { color: var(--brand-green); border-color: var(--brand-green); }
  .tab-content { display: none; } .tab-content.active { display: block; }
  
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
      .repairs-table thead { display: none; }
      .repairs-table tbody, .repairs-table tr, .repairs-table td { display: block; width: 100%; }
      .repairs-table tr { margin-bottom: 1rem; border: 1px solid var(--border-color); border-radius: var(--card-radius); padding: 1rem; }
      .repairs-table td { border: none; padding: 0.5rem 0; display: flex; justify-content: space-between; align-items: center; text-align: right; }
      .repairs-table td::before { content: attr(data-label); font-weight: 600; color: var(--text-secondary); text-align: left; }
  }
  
  /* SUMMARY CARDS CLICKABLE */
  .summary-card {
      cursor: pointer;
      user-select: none;
  }
  .summary-card.active {
      box-shadow: 0 0 0 3px var(--brand-green), var(--shadow-lg) !important;
  }
  .summary-card.pending.active { box-shadow: 0 0 0 3px var(--brand-orange), var(--shadow-lg) !important; }
  .summary-card.in-progress.active { box-shadow: 0 0 0 3px var(--brand-blue), var(--shadow-lg) !important; }
  .summary-card.revenue.active { box-shadow: 0 0 0 3px var(--brand-purple), var(--shadow-lg) !important; }
  
  /* PROGRESS BAR */
  .progress-container {
      margin-top: 10px;
      width: 100%;
  }
  .progress-bar {
      height: 6px;
      background: #e2e8f0;
      border-radius: 10px;
      overflow: hidden;
  }
  .progress-bar-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--brand-purple), #a78bfa);
      border-radius: 10px;
      transition: width 0.5s ease;
  }
  .progress-label {
      display: flex;
      justify-content: space-between;
      font-size: 0.7rem;
      color: var(--text-secondary);
      margin-top: 4px;
  }
  
  /* AVATAR */
  .client-avatar {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.8rem;
      color: white;
      margin-right: 10px;
      flex-shrink: 0;
  }
  .client-info {
      display: flex;
      align-items: center;
  }
  .client-name {
      font-weight: 600;
      color: var(--text-primary);
  }
  
  /* PRIORITY BADGE */
  .priority-badge {
      padding: 0.25rem 0.6rem;
      border-radius: 6px;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.3px;
  }
  .priority-urgent { background: #fef2f2; color: #dc2626; }
  .priority-normal { background: #eff6ff; color: #2563eb; }
  .priority-low { background: #f0fdf4; color: #16a34a; }
  
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
  .inline-status-select.status-in-attesa { background-color: #fff7ed; color: #c2410c; }
  .inline-status-select.status-in-lavorazione { background-color: #eff6ff; color: #1d4ed8; }
  .inline-status-select.status-completata { background-color: #f0fdf4; color: #15803d; }
  .inline-status-select.status-consegnata { background-color: #ecfdf5; color: #166534; }
  .inline-status-select.status-annullata { background-color: #fef2f2; color: #991b1b; }
  
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
  
  /* TOAST NOTIFICATIONS */
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
  
  @media print {
      body * { visibility: hidden; }
      #receiptContentToPrint, #receiptContentToPrint * { visibility: visible; }
      #receiptContentToPrint { position: absolute; left: 0; top: 0; width: 100%; }
  }
</style>
</head>
<body>
<?php include 'header.php'; ?>
    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <div class="page-container">
        <div class="page-header">
            <h1>Dashboard Riparazioni</h1>
            <div style="display: flex; gap: 12px;">
                <button class="btn-export" onclick="exportToExcel()"><i class="fas fa-file-excel"></i> Esporta Excel</button>
                <a href="nuova_riparazione.php" class="btn-primary"><i class="fas fa-plus"></i> Nuova Riparazione</a>
            </div>
        </div>

        <div class="summary-card-container">
            <div class="summary-card pending" data-filter="In Attesa" onclick="filterByCard(this)">
                <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <div id="summaryPending" class="value">0</div>
                    <div class="label">In Attesa</div>
                </div>
            </div>
            <div class="summary-card in-progress" data-filter="In Lavorazione" onclick="filterByCard(this)">
                <div class="icon"><i class="fas fa-cogs"></i></div>
                <div>
                    <div id="summaryInProgress" class="value">0</div>
                    <div class="label">In Lavorazione</div>
                </div>
            </div>
            <div class="summary-card completed" data-filter="Completata" onclick="filterByCard(this)">
                <div class="icon"><i class="fas fa-check-double"></i></div>
                <div>
                    <div id="summaryCompleted" class="value">0</div>
                    <div class="label">Completate</div>
                </div>
            </div>
             <div class="summary-card revenue" data-filter="" onclick="filterByCard(this)">
                <div class="icon"><i class="fas fa-euro-sign"></i></div>
                <div>
                    <div id="summaryTotalRevenue" class="value">0,00 €</div>
                    <div class="label">Obiettivo: <span id="goalPercent">0</span>%</div>
                    <div class="progress-container">
                        <div class="progress-bar">
                            <div id="goalProgressBar" class="progress-bar-fill" style="width: 0%"></div>
                        </div>
                        <div class="progress-label">
                            <span id="currentRevenue">€0</span>
                            <span id="goalAmount">Obiettivo: €5.000</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-main-card">
            <!-- Filters Bar -->
            <div class="filters-bar">
                <div class="filter-group">
                    <label>Stato</label>
                    <select id="filterStatus" class="filter-select" onchange="applyFilters()">
                        <option value="">Tutti</option>
                        <option value="In Attesa">⏳ In Attesa</option>
                        <option value="In Lavorazione">🔧 In Lavorazione</option>
                        <option value="Completata">✅ Completata</option>
                        <option value="Consegnata">📤 Consegnata</option>
                        <option value="Annullata">❌ Annullata</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Priorità</label>
                    <select id="filterPriority" class="filter-select" onchange="applyFilters()">
                        <option value="">Tutte</option>
                        <option value="urgent">🔴 Urgente</option>
                        <option value="normal">🔵 Normale</option>
                        <option value="low">🟢 Bassa</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Da</label>
                    <input type="date" id="filterDateFrom" class="filter-date" onchange="applyFilters()">
                </div>
                <div class="filter-group">
                    <label>A</label>
                    <input type="date" id="filterDateTo" class="filter-date" onchange="applyFilters()">
                </div>
                <button class="btn-reset-filters" onclick="resetFilters()"><i class="fas fa-times"></i> Reset</button>
            </div>
            
            <div class="filters-container">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Cerca per ID, cliente, modello, difetto...">
                </div>
            </div>
            
            <!-- Results Info -->
            <div class="results-info">
                <div class="results-count">
                    <strong id="resultsCount">0</strong> riparazioni trovate
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

            <div class="overflow-x-auto">
                <table class="repairs-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Modello</th>
                            <th>Difetto</th>
                            <th>Data</th>
                            <th>Priorità</th>
                            <th>Stato</th>
                            <th class="text-right">Costo</th>
                            <th class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody id="repairsTableBody">
                        <!-- Righe popolate da JS -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pagination-container" id="paginationContainer">
                <!-- Popolato da JS -->
            </div>
        </div>
    </div>

    <!-- Main Modal Container -->
    <div id="mainModal" class="modal-overlay">
        <!-- Edit Modal -->
        <div id="editRepairModalContent" class="modal-content" style="display: none;">
            <div class="modal-header">
                <h2>✏️ Modifica Riparazione #<span id="modalRepairId"></span></h2>
                <button class="modal-close-button" onclick="closeModal()">&times;</button>
            </div>
            <div class="tab-buttons">
                <button type="button" class="tab-button active" data-tab="anagrafe">👤 Anagrafe</button>
                <button type="button" class="tab-button" data-tab="articoli">📦 Articoli</button>
                <button type="button" class="tab-button" data-tab="scheda">📋 Scheda</button>
            </div>
            <form id="editRepairForm">
                 <input type="hidden" id="editRepairId" name="id">
                 <div id="anagrafeTabContent" class="tab-content active">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label>Nome Cliente</label><input type="text" id="editClienteNome" name="cliente_nome"></div>
                        <div><label>Cognome Cliente</label><input type="text" id="editClienteCognome" name="cliente_cognome"></div>
                        <div><label>Telefono</label><input type="text" id="editTelefono" name="telefono"></div>
                        <div><label>Modello</label><input type="text" id="editModello" name="modello"></div>
                        <div><label>Stato</label>
                            <select id="editStato" name="stato">
                                <option value="In Attesa">⏳ In Attesa</option>
                                <option value="In Lavorazione">🔧 In Lavorazione</option>
                                <option value="Completata">✅ Completata</option>
                                <option value="Consegnata">📤 Consegnata</option>
                                <option value="Annullata">❌ Annullata</option>
                            </select>
                        </div>
                        <div><label>Costo (€)</label><input type="number" step="0.01" id="editCostoEffettivo" name="costo_effettivo"></div>
                        <div class="md:col-span-2"><label>Diagnosi/Difetto</label><textarea id="editDiagnosi" name="diagnosi" rows="3"></textarea></div>
                    </div>
                 </div>
                 <div id="articoliTabContent" class="tab-content">
                    <h4 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">📦 Gestione Articoli</h4>
                    <div id="stockManagementSection" class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end mb-6 p-4 bg-white rounded-xl" style="border: 2px dashed var(--border-color);">
                        <div class="relative">
                            <label>Cerca Prodotto</label><input type="text" id="searchProductToUnload" placeholder="Nome prodotto...">
                            <input type="hidden" id="selectedProductIdToUnload"><div id="productToUnloadAutocompleteList" class="absolute bg-white border rounded shadow-lg w-full z-10"></div>
                        </div>
                        <div><label>Giacenza</label><input type="text" id="productCurrentStock" readonly value="N/D" style="background: #f1f5f9;"></div>
                        <div><label>Quantità da Associare</label><input type="number" id="quantityToUnload" min="1" value="1"></div>
                        <button type="button" id="unloadFromStockBtn" style="background: var(--brand-blue); color: white; padding: 0.75rem 1rem; border-radius: 10px; font-weight: 600; border: none; cursor: pointer;">Associa & Scarica</button>
                    </div>
                    <h5 class="text-md font-semibold mb-3" style="color: var(--text-primary);">Riepilogo Articoli Associati:</h5>
                    <div id="repairMovementsList" class="space-y-2 max-h-60 overflow-y-auto p-3 bg-white rounded-xl" style="border: 1px solid var(--border-color);"></div>
                 </div>
                 <div id="schedaTabContent" class="tab-content">
                    <h4 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">📋 Cronologia Eventi</h4>
                    <div id="repairHistoryList" class="space-y-3 max-h-80 overflow-y-auto p-3 bg-white rounded-xl" style="border: 1px solid var(--border-color);"></div>
                 </div>
            </form>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal()">Annulla</button>
                <button class="btn btn-save" onclick="saveRepair()">💾 Salva Modifiche</button>
            </div>
        </div>
        
        <!-- Payment Modal -->
        <div id="paymentModalContent" class="modal-content" style="display: none; width: 500px;">
            <div class="modal-header">
                <h2>Registra Pagamento per Riparazione #<span id="paymentModalRepairId"></span></h2>
                <button class="modal-close-button" onclick="closeModal()">&times;</button>
            </div>
            <div>
                <p class="mb-4">Cliente: <strong id="paymentModalClientName"></strong></p>
                <p class="mb-6 text-2xl font-bold">Importo Totale: <span id="paymentModalTotalAmount"></span></p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="font-medium">Metodo di Pagamento</label>
                        <select id="paymentMethod" class="w-full mt-1 p-2 border rounded bg-white">
                            <option>Contanti</option>
                            <option>Carta di Credito/Debito</option>
                            <option>Bonifico Bancario</option>
                            <option>Altro</option>
                        </select>
                    </div>
                    <div>
                        <label class="font-medium">Importo Pagato (€)</label>
                        <input type="number" id="paymentAmount" step="0.01" class="w-full mt-1 p-2 border rounded">
                    </div>
                    <div class="md:col-span-2">
                        <label class="font-medium">Note</label>
                        <textarea id="paymentNotes" rows="3" class="w-full mt-1 p-2 border rounded"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal()">Annulla</button>
                <button class="btn btn-save" onclick="savePayment()">Salva Pagamento</button>
            </div>
        </div>

        <!-- SMS Modal -->
        <div id="smsModalContent" class="modal-content" style="display: none; width: 500px;">
             <div class="modal-header">
                <h2>Invia SMS per Riparazione #<span id="smsModalRepairId"></span></h2>
                <button class="modal-close-button" onclick="closeModal()">&times;</button>
            </div>
            <div>
                <p class="mb-2">Destinatario: <strong id="smsModalClientName"></strong></p>
                <p class="mb-4">Numero: <strong id="smsModalClientPhone"></strong></p>
                <div>
                    <label class="font-medium">Messaggio</label>
                    <textarea id="smsMessage" rows="5" class="w-full mt-1 p-2 border rounded"></textarea>
                    <div class="text-xs text-gray-500 mt-1">
                        <button type="button" class="text-blue-500 hover:underline" onclick="setSmsTemplate('pronta')">Usa template 'Riparazione Pronta'</button> |
                        <button type="button" class="text-blue-500 hover:underline" onclick="setSmsTemplate('preventivo')">Usa template 'Preventivo'</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal()">Annulla</button>
                <button class="btn btn-save" onclick="sendSms()">Invia SMS</button>
            </div>
        </div>

        <!-- Receipt Modal -->
        <div id="receiptModalContent" class="modal-content" style="display: none; width: 800px;">
            <div class="modal-header">
                <h2>Ricevuta per Riparazione #<span id="receiptModalRepairId"></span></h2>
                <button class="modal-close-button" onclick="closeModal()">&times;</button>
            </div>
            <div id="receiptContentToPrint" class="p-4 border rounded bg-white">
                <!-- Receipt content will be generated here by JS -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal()">Chiudi</button>
                <button class="btn btn-save" onclick="printReceipt()"><i class="fas fa-print mr-2"></i>Stampa</button>
            </div>
        </div>
    </div>

<script>
  const allRepairs = <?php echo json_encode($riparazioni_data); ?>;
  const allProducts = <?php echo json_encode($prodotti_catalogo_data); ?>;
  const allMovements = <?php echo json_encode($riparazioni_movimenti_data); ?>;
  const allHistory = <?php echo json_encode($riparazioni_storico_data); ?>;
  
  let filteredRepairs = [...allRepairs];
  let currentPage = 1;
  let itemsPerPage = 25;
  const MONTHLY_GOAL = 5000; // Obiettivo mensile

  const tableBody = document.getElementById('repairsTableBody');
  const searchInput = document.getElementById('searchInput');

  // Colori per avatar
  const avatarColors = [
    '#22c55e', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', 
    '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16'
  ];

  function getAvatarColor(name) {
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
      hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    return avatarColors[Math.abs(hash) % avatarColors.length];
  }

  function getInitials(nome, cognome) {
    return ((nome || '').charAt(0) + (cognome || '').charAt(0)).toUpperCase() || '?';
  }

  // Determina priorità in base ai giorni
  function getPriority(dataCreazione) {
    const days = Math.floor((new Date() - new Date(dataCreazione)) / (1000 * 60 * 60 * 24));
    if (days > 7) return 'urgent';
    if (days > 3) return 'normal';
    return 'low';
  }

  function formatCurrency(value) {
    const numValue = parseFloat(value) || 0;
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(numValue);
  }

  // TOAST NOTIFICATIONS
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

  function updateSummaryCards() {
    let pending = 0, inProgress = 0, completed = 0, totalRevenue = 0;
    allRepairs.forEach(r => {
        if (r.stato === 'In Attesa') pending++;
        if (r.stato === 'In Lavorazione') inProgress++;
        if (r.stato === 'Completata' || r.stato === 'Consegnata') {
            completed++;
            totalRevenue += parseFloat(r.costo_effettivo) || 0;
        }
    });
    document.getElementById('summaryPending').textContent = pending;
    document.getElementById('summaryInProgress').textContent = inProgress;
    document.getElementById('summaryCompleted').textContent = completed;
    document.getElementById('summaryTotalRevenue').textContent = formatCurrency(totalRevenue);
    
    // Progress bar obiettivo
    const percent = Math.min(100, (totalRevenue / MONTHLY_GOAL) * 100);
    document.getElementById('goalProgressBar').style.width = percent + '%';
    document.getElementById('goalPercent').textContent = Math.round(percent);
    document.getElementById('currentRevenue').textContent = formatCurrency(totalRevenue);
    document.getElementById('goalAmount').textContent = 'Obiettivo: ' + formatCurrency(MONTHLY_GOAL);
  }

  // FILTER BY CARD CLICK
  window.filterByCard = function(card) {
    const filterValue = card.dataset.filter;
    
    // Toggle active state
    document.querySelectorAll('.summary-card').forEach(c => c.classList.remove('active'));
    
    if (document.getElementById('filterStatus').value === filterValue) {
      // Deseleziona
      document.getElementById('filterStatus').value = '';
    } else {
      card.classList.add('active');
      document.getElementById('filterStatus').value = filterValue;
    }
    
    applyFilters();
  }

  // RESET FILTERS
  window.resetFilters = function() {
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterPriority').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('searchInput').value = '';
    document.querySelectorAll('.summary-card').forEach(c => c.classList.remove('active'));
    currentPage = 1;
    applyFilters();
    showToast('Filtri resettati', 'info');
  }

  // CHANGE ITEMS PER PAGE
  window.changeItemsPerPage = function() {
    itemsPerPage = parseInt(document.getElementById('itemsPerPage').value);
    currentPage = 1;
    renderTable();
    renderPagination();
  }

  // PAGINATION
  function renderPagination() {
    const container = document.getElementById('paginationContainer');
    const totalPages = Math.ceil(filteredRepairs.length / itemsPerPage);
    
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
    
    // Numeri pagina
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

  function renderTable() {
    tableBody.innerHTML = '';
    
    // Paginazione
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const paginatedRepairs = filteredRepairs.slice(start, end);
    
    // Update results count
    document.getElementById('resultsCount').textContent = filteredRepairs.length;
    
    if (paginatedRepairs.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-gray-500">Nessuna riparazione trovata.</td></tr>`;
        return;
    }
    
    paginatedRepairs.forEach(r => {
        const row = document.createElement('tr');
        const statusClass = (r.stato || '').toLowerCase().replace(' ', '-');
        const shortDiagnosi = (r.diagnosi || '').length > 30 ? r.diagnosi.substring(0, 30) + '...' : r.diagnosi;
        const clientName = `${r.cliente_nome || ''} ${r.cliente_cognome || ''}`.trim();
        const initials = getInitials(r.cliente_nome, r.cliente_cognome);
        const avatarColor = getAvatarColor(clientName);
        const priority = getPriority(r.data_creazione);
        const priorityLabels = { urgent: 'Urgente', normal: 'Normale', low: 'Bassa' };
        
        row.innerHTML = `
            <td data-label="ID:"><strong>#${r.id}</strong></td>
            <td data-label="Cliente:">
                <div class="client-info">
                    <div class="client-avatar" style="background: ${avatarColor}">${initials}</div>
                    <span class="client-name">${clientName || 'N/D'}</span>
                </div>
            </td>
            <td data-label="Modello:">${r.modello || ''}</td>
            <td data-label="Difetto:" title="${r.diagnosi || ''}">${shortDiagnosi}</td>
            <td data-label="Data:">${new Date(r.data_creazione).toLocaleDateString('it-IT')}</td>
            <td data-label="Priorità:"><span class="priority-badge priority-${priority}">${priorityLabels[priority]}</span></td>
            <td data-label="Stato:">
                <select class="inline-status-select status-${statusClass}" onchange="updateStatusInline(${r.id}, this.value)" data-id="${r.id}">
                    <option value="In Attesa" ${r.stato === 'In Attesa' ? 'selected' : ''}>⏳ In Attesa</option>
                    <option value="In Lavorazione" ${r.stato === 'In Lavorazione' ? 'selected' : ''}>🔧 In Lavorazione</option>
                    <option value="Completata" ${r.stato === 'Completata' ? 'selected' : ''}>✅ Completata</option>
                    <option value="Consegnata" ${r.stato === 'Consegnata' ? 'selected' : ''}>📤 Consegnata</option>
                    <option value="Annullata" ${r.stato === 'Annullata' ? 'selected' : ''}>❌ Annullata</option>
                </select>
            </td>
            <td data-label="Costo:" class="text-right font-semibold">${formatCurrency(r.costo_effettivo)}</td>
            <td class="text-center">
                <div class="actions-wrapper">
                    <button class="btn-actions" data-id="${r.id}"><i class="fas fa-ellipsis-v"></i></button>
                    <div class="actions-popup" id="popup-${r.id}">
                        <ul>
                            <li onclick="openEditRepairModal(${r.id})"><i class="fas fa-edit fa-fw"></i>Modifica</li>
                            <li onclick="window.open('stampa_riparazione.php?id=${r.id}','_blank')"><i class="fas fa-print fa-fw"></i> Stampa Scheda</li>
                            <li onclick="openSmsModal(${r.id})"><i class="fas fa-comment-sms fa-fw"></i> Invia SMS</li>
                            <li onclick="openReceiptModal(${r.id})"><i class="fas fa-receipt fa-fw"></i> Genera Ricevuta</li>
                            <li onclick="openPaymentModal(${r.id})"><i class="fas fa-credit-card fa-fw"></i> Verifica Pagamento</li>
                            <li class="delete" onclick="openDeleteRepairModal(${r.id})"><i class="fas fa-trash-alt fa-fw"></i> Elimina</li>
                        </ul>
                    </div>
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });
  }

  // INLINE STATUS UPDATE
  window.updateStatusInline = async function(id, newStatus) {
    const select = document.querySelector(`select[data-id="${id}"]`);
    const oldClass = select.className.match(/status-[\w-]+/)?.[0] || '';
    const newClass = 'status-' + newStatus.toLowerCase().replace(' ', '-');
    
    select.classList.remove(oldClass);
    select.classList.add(newClass);
    
    // Update local data
    const repair = allRepairs.find(r => r.id == id);
    if (repair) {
      repair.stato = newStatus;
      updateSummaryCards();
    }
    
    // Send to server
    const formData = new FormData();
    formData.append('id', id);
    formData.append('stato', newStatus);
    
    try {
      const response = await fetch('update_riparazione.php', { method: 'POST', body: formData });
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

  // EXPORT TO EXCEL
  window.exportToExcel = function() {
    let csv = 'ID,Cliente,Modello,Difetto,Data,Stato,Costo\n';
    filteredRepairs.forEach(r => {
      csv += `${r.id},"${r.cliente_nome || ''} ${r.cliente_cognome || ''}","${r.modello || ''}","${(r.diagnosi || '').replace(/"/g, '""')}",${new Date(r.data_creazione).toLocaleDateString('it-IT')},${r.stato || ''},${r.costo_effettivo || 0}\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `riparazioni_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    
    showToast(`Esportate ${filteredRepairs.length} riparazioni`, 'success');
  }

  function applyFilters() {
      const searchTerm = searchInput.value.toLowerCase();
      const statusFilter = document.getElementById('filterStatus').value;
      const priorityFilter = document.getElementById('filterPriority').value;
      const dateFrom = document.getElementById('filterDateFrom').value;
      const dateTo = document.getElementById('filterDateTo').value;
      
      filteredRepairs = allRepairs.filter(r => {
          // Search
          const matchesSearch = (r.id.toString().includes(searchTerm) ||
                 (r.cliente_nome || '').toLowerCase().includes(searchTerm) ||
                 (r.cliente_cognome || '').toLowerCase().includes(searchTerm) ||
                 (r.modello || '').toLowerCase().includes(searchTerm) ||
                 (r.diagnosi || '').toLowerCase().includes(searchTerm));
          
          // Status
          const matchesStatus = !statusFilter || r.stato === statusFilter;
          
          // Priority
          const priority = getPriority(r.data_creazione);
          const matchesPriority = !priorityFilter || priority === priorityFilter;
          
          // Date range
          const repairDate = new Date(r.data_creazione);
          const matchesDateFrom = !dateFrom || repairDate >= new Date(dateFrom);
          const matchesDateTo = !dateTo || repairDate <= new Date(dateTo + 'T23:59:59');
          
          return matchesSearch && matchesStatus && matchesPriority && matchesDateFrom && matchesDateTo;
      });
      
      currentPage = 1;
      renderTable();
      renderPagination();
  }
  
  searchInput.addEventListener('input', applyFilters);

  document.addEventListener('click', (e) => {
    const activePopup = document.querySelector('.actions-popup.show');
    
    // Rimuovi z-index da tutte le righe
    document.querySelectorAll('.repairs-table tbody tr').forEach(row => {
        row.style.position = '';
        row.style.zIndex = '';
    });
    
    if (e.target.closest('.btn-actions')) {
        const button = e.target.closest('.btn-actions');
        const id = button.dataset.id;
        const popup = document.getElementById(`popup-${id}`);
        
        // Chiudi altri popup
        if (activePopup && activePopup !== popup) activePopup.classList.remove('show');
        
        // Aumenta z-index della riga corrente
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

  const mainModal = document.getElementById('mainModal');
  let originalRepairData = {};
  
  function openModal(modalContent) {
      if (modalContent) modalContent.style.display = 'block';
      mainModal.classList.add('show');
      document.body.style.overflow = 'hidden';
  }
  
  function closeModal() {
      mainModal.classList.remove('show');
      document.querySelectorAll('.modal-content').forEach(mc => mc.style.display = 'none');
      document.body.style.overflow = '';
  }

  mainModal.addEventListener('click', e => { if (e.target === mainModal) closeModal(); });

  window.openEditRepairModal = function(id) {
    const repair = allRepairs.find(r => r.id == id);
    if (!repair) return;
    
    originalRepairData = { ...repair };
    document.getElementById('modalRepairId').textContent = id;
    document.getElementById('editRepairId').value = id;
    document.getElementById('editClienteNome').value = repair.cliente_nome || '';
    document.getElementById('editClienteCognome').value = repair.cliente_cognome || '';
    document.getElementById('editTelefono').value = repair.telefono || '';
    document.getElementById('editModello').value = repair.modello || '';
    document.getElementById('editStato').value = repair.stato || 'In Attesa';
    document.getElementById('editCostoEffettivo').value = parseFloat(repair.costo_effettivo || 0).toFixed(2);
    document.getElementById('editDiagnosi').value = repair.diagnosi || '';

    renderRepairMovements(id);
    renderRepairHistory(id);

    switchTab('anagrafe');
    openModal(document.getElementById('editRepairModalContent'));
  }
  
  function renderRepairMovements(repairId) {
      const movements = allMovements.filter(m => m.riparazione_id == repairId);
      const listEl = document.getElementById('repairMovementsList');
      listEl.innerHTML = '';
      if (movements.length > 0) {
          movements.forEach(m => {
              listEl.innerHTML += `<div class="p-2 bg-white rounded border flex justify-between"><span>${m.product_name} <strong>(${m.quantita_movimentata} pz)</strong></span><span class="text-xs text-gray-500">${new Date(m.data_movimento).toLocaleDateString('it-IT')}</span></div>`;
          });
      } else {
          listEl.innerHTML = `<p class="text-sm text-gray-500 italic">Nessun articolo associato.</p>`;
      }
  }

  function renderRepairHistory(repairId) {
      const history = allHistory.filter(h => h.riparazione_id == repairId);
      const listEl = document.getElementById('repairHistoryList');
      listEl.innerHTML = '';
      if (history.length > 0) {
          history.forEach(h => {
              listEl.innerHTML += `<div class="p-2 bg-white rounded border"><strong>${new Date(h.data_evento).toLocaleString('it-IT')}:</strong> ${h.evento_descrizione}</div>`;
          });
      } else {
          listEl.innerHTML = `<p class="text-sm text-gray-500 italic">Nessuna cronologia registrata.</p>`;
      }
  }

  document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', () => {
        const tabName = button.dataset.tab;
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        button.classList.add('active');
        document.getElementById(`${tabName}TabContent`).classList.add('active');
    });
  });
  function switchTab(tabName) {
      document.querySelector(`.tab-button[data-tab="${tabName}"]`).click();
  }

  window.saveRepair = async function() {
      const form = document.getElementById('editRepairForm');
      const formData = new FormData(form);
      
      const updatedData = Object.fromEntries(formData.entries());
      let history_updates = [];
      
      for(const key in updatedData) {
          if (updatedData[key] != originalRepairData[key]) {
              history_updates.push({
                  evento_descrizione: `Campo '${key}' aggiornato da '${originalRepairData[key]}' a '${updatedData[key]}'`,
                  utente: 'Sistema'
              });
          }
      }
      formData.append('history_updates', JSON.stringify(history_updates));

      try {
          const response = await fetch('update_riparazione.php', { method: 'POST', body: formData });
          const result = await response.json();
          if(result.success) {
              Swal.fire('Salvato!', 'La riparazione è stata aggiornata.', 'success').then(() => location.reload());
          } else {
              Swal.fire('Errore', result.message, 'error');
          }
      } catch (e) { Swal.fire('Errore di Rete', e.message, 'error'); }
  }

  window.openDeleteRepairModal = function(id) {
      Swal.fire({
          title: 'Sei sicuro?',
          text: "Non potrai annullare questa azione!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Sì, elimina!',
          cancelButtonText: 'Annulla'
      }).then(async (result) => {
          if (result.isConfirmed) {
              const formData = new FormData();
              formData.append('repair_id', id);
              try {
                const response = await fetch('elimina_riparazione.php', { method: 'POST', body: formData });
                const res = await response.json();
                if(res.success) {
                    Swal.fire('Eliminato!', 'La riparazione è stata eliminata.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Errore', res.message, 'error');
                }
              } catch (e) { Swal.fire('Errore di Rete', e.message, 'error'); }
          }
      });
  }

    // --- Funzioni Azioni Aggiuntive (Implementate) ---
    window.openPaymentModal = function(id) {
        const repair = allRepairs.find(r => r.id == id);
        if (!repair) return;

        document.getElementById('paymentModalRepairId').textContent = id;
        document.getElementById('paymentModalClientName').textContent = `${repair.cliente_nome || ''} ${repair.cliente_cognome || ''}`;
        const totalAmount = parseFloat(repair.costo_effettivo || 0);
        document.getElementById('paymentModalTotalAmount').textContent = formatCurrency(totalAmount);
        document.getElementById('paymentAmount').value = totalAmount.toFixed(2);
        document.getElementById('paymentMethod').selectedIndex = 0;
        document.getElementById('paymentNotes').value = '';

        openModal(document.getElementById('paymentModalContent'));
    }

    window.savePayment = function() {
        const repairId = document.getElementById('paymentModalRepairId').textContent;
        const amount = document.getElementById('paymentAmount').value;
        closeModal();
        Swal.fire({
            title: 'Pagamento Registrato!',
            text: `(Demo) Pagamento di ${formatCurrency(amount)} per #${repairId} salvato.`,
            icon: 'success'
        });
    }

    window.openSmsModal = function(id) {
        const repair = allRepairs.find(r => r.id == id);
        if (!repair) return;
        
        document.getElementById('smsModalRepairId').textContent = id;
        document.getElementById('smsModalClientName').textContent = `${repair.cliente_nome || ''} ${repair.cliente_cognome || ''}`;
        document.getElementById('smsModalClientPhone').textContent = repair.telefono || 'N/D';
        
        window.setSmsTemplate('pronta');
        openModal(document.getElementById('smsModalContent'));
    }

    window.setSmsTemplate = function(templateName) {
        const repairId = document.getElementById('smsModalRepairId').textContent;
        const repair = allRepairs.find(r => r.id == repairId);
        if (!repair) return;

        const clientName = repair.cliente_nome || '';
        const model = repair.modello || '';
        let message = '';
        
        if (templateName === 'pronta') {
            message = `Gentile ${clientName}, la informiamo che la riparazione del suo ${model} (scheda #${repairId}) è stata completata ed è pronto per il ritiro. Cordiali Saluti.`;
        } else if (templateName === 'preventivo') {
            const cost = formatCurrency(repair.costo_effettivo);
            message = `Gentile ${clientName}, il preventivo per la riparazione del suo ${model} (scheda #${repairId}) è di ${cost}. Attendiamo sua conferma per procedere. Cordiali Saluti.`;
        }
        document.getElementById('smsMessage').value = message;
    }

    window.sendSms = function() {
        const repairId = document.getElementById('smsModalRepairId').textContent;
        closeModal();
        Swal.fire({
            title: 'SMS Inviato!',
            text: `(Demo) Messaggio per la riparazione #${repairId} inviato con successo.`,
            icon: 'success'
        });
    }

    window.openReceiptModal = function(id) {
        const repair = allRepairs.find(r => r.id == id);
        if (!repair) return;
        
        const movements = allMovements.filter(m => m.riparazione_id == id);
        
        document.getElementById('receiptModalRepairId').textContent = id;
        
        let movementsHtml = '';
        if (movements.length > 0) {
            movements.forEach(m => {
                movementsHtml += `<div class="flex justify-between py-1"><span>${m.product_name} (x${m.quantita_movimentata})</span></div>`;
            });
        } else {
            movementsHtml = `<div class="py-1"><span>Manodopera</span></div>`;
        }

        const receiptContent = `
            <div class="p-6 text-gray-800">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">NOME AZIENDA</h3>
                        <p class="text-sm text-gray-600">Via Esempio 123, 00100 Città<br>P.IVA: 12345678901</p>
                    </div>
                    <div class="text-right">
                        <h4 class="text-lg font-semibold text-gray-900">RICEVUTA N. ${id}</h4>
                        <p class="text-sm text-gray-600">Data: ${new Date().toLocaleDateString('it-IT')}</p>
                    </div>
                </div>
                <div class="mb-6 border-t pt-4">
                    <h5 class="font-semibold text-gray-700">Cliente:</h5>
                    <p>${repair.cliente_nome || ''} ${repair.cliente_cognome || ''}</p>
                </div>
                <div class="mb-6">
                     <h5 class="font-semibold text-gray-700 mb-2">Dettagli Riparazione:</h5>
                     <div class="border rounded p-3 bg-gray-50">
                        <p><strong>Dispositivo:</strong> ${repair.modello}</p>
                        <p><strong>Difetto Riscontrato:</strong> ${repair.diagnosi}</p>
                     </div>
                </div>
                <div>
                     <h5 class="font-semibold text-gray-700 mb-2">Riepilogo Costi:</h5>
                     <div class="border rounded p-3">
                        ${movementsHtml}
                        <div class="flex justify-between font-bold border-t mt-4 pt-2 text-xl text-gray-900">
                            <span>TOTALE</span>
                            <span>${formatCurrency(repair.costo_effettivo)}</span>
                        </div>
                     </div>
                </div>
            </div>
        `;
        
        document.getElementById('receiptContentToPrint').innerHTML = receiptContent;
        openModal(document.getElementById('receiptModalContent'));
    }

    window.printReceipt = function() {
        window.print();
    }

  // --- Autocomplete per Prodotti ---
  const searchProductInput = document.getElementById('searchProductToUnload');
  const autocompleteList = document.getElementById('productToUnloadAutocompleteList');
  searchProductInput.addEventListener('input', () => {
      const searchTerm = searchProductInput.value.toLowerCase();
      autocompleteList.innerHTML = '';
      if(searchTerm.length < 2) return;
      const filtered = allProducts.filter(p => p.nome.toLowerCase().includes(searchTerm));
      filtered.forEach(p => {
          const item = document.createElement('div');
          item.className = 'p-2 cursor-pointer hover:bg-gray-100';
          item.textContent = p.nome;
          item.onclick = () => {
              searchProductInput.value = p.nome;
              document.getElementById('selectedProductIdToUnload').value = p.id;
              document.getElementById('productCurrentStock').value = p.quantita;
              autocompleteList.innerHTML = '';
          };
          autocompleteList.appendChild(item);
      });
  });

  // --- Scarico Magazzino ---
  document.getElementById('unloadFromStockBtn').addEventListener('click', async () => {
      const repairId = document.getElementById('editRepairId').value;
      const productId = document.getElementById('selectedProductIdToUnload').value;
      const quantity = document.getElementById('quantityToUnload').value;

      if(!productId || !quantity) {
          Swal.fire('Attenzione', 'Seleziona un prodotto e inserisci una quantità.', 'warning');
          return;
      }
      const formData = new FormData();
      formData.append('repairId', repairId);
      formData.append('productId', productId);
      formData.append('quantity', quantity);
      
      try {
          const response = await fetch('update_stock_for_repair.php', { method: 'POST', body: formData });
          const result = await response.json();
          if(result.success) {
              Swal.fire('Fatto!', 'Articolo associato e magazzino aggiornato.', 'success');
              // Aggiorna dati localmente e UI
              const product = allProducts.find(p => p.id == productId);
              if(product) product.quantita = result.newStock;
              allMovements.push({riparazione_id: repairId, product_name: product.nome, quantita_movimentata: quantity, data_movimento: new Date()});
              renderRepairMovements(repairId);
          } else {
              Swal.fire('Errore', result.message, 'error');
          }
      } catch (e) { Swal.fire('Errore di Rete', e.message, 'error'); }
  });


  // Initial Load
  document.addEventListener('DOMContentLoaded', () => {
      updateSummaryCards();
      applyFilters(); // This calls renderTable and renderPagination
      <?php echo $message; ?>
  });
</script>

</body>
</html>

