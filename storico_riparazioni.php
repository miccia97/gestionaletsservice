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
  :root {
      --brand-green: #22c55e;
      --brand-green-dark: #16a34a;
      --brand-green-light: #f0fdf4;
      --brand-orange: #f97316;
      --brand-blue: #3b82f6;
      --brand-red: #ef4444;
      --bg-page: #f1f5f9;
      --text-primary: #1e293b;
      --text-secondary: #64748b;
      --border-color: #e2e8f0;
      --card-bg: #ffffff;
      --card-radius: 0.75rem;
      --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  }
  body {
      font-family: 'Inter', sans-serif;
      background: var(--bg-page);
      color: var(--text-primary);
      padding-top: 80px;
  }
  .page-container {
      max-width: 1600px;
      margin: 2rem auto;
      padding: 0 2rem;
  }
  .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
  .page-header h1 { font-size: 2.25rem; font-weight: 800; }
  .btn-primary {
      display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem;
      border-radius: 0.5rem; font-weight: 600; cursor: pointer; text-decoration: none;
      background-color: var(--brand-green); color: white; box-shadow: var(--card-shadow);
      transition: all 0.2s ease-in-out;
  }
  .btn-primary:hover { background-color: var(--brand-green-dark); transform: translateY(-2px); }
  
  .summary-card-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
  .summary-card {
      background-color: var(--card-bg); border-radius: var(--card-radius); box-shadow: var(--card-shadow);
      padding: 1.5rem; display: flex; align-items: center; gap: 1.5rem;
      border-left: 5px solid; transition: all 0.3s ease;
  }
  .summary-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); }
  .summary-card .icon { font-size: 1.75rem; padding: 1rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
  .summary-card.pending { border-color: var(--brand-orange); }
  .summary-card.pending .icon { background-color: #fff7ed; color: var(--brand-orange); }
  .summary-card.in-progress { border-color: var(--brand-blue); }
  .summary-card.in-progress .icon { background-color: #eff6ff; color: var(--brand-blue); }
  .summary-card.completed { border-color: var(--brand-green); }
  .summary-card.completed .icon { background-color: var(--brand-green-light); color: var(--brand-green); }
  .summary-card .value { font-size: 2rem; font-weight: 700; }
  .summary-card .label { font-size: 0.875rem; color: var(--text-secondary); }

  .table-main-card { background-color: var(--card-bg); border-radius: var(--card-radius); box-shadow: var(--card-shadow); padding: 2rem; }
  .filters-container { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
  .filters-container .search-wrapper { position: relative; flex-grow: 1; }
  .filters-container .search-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }
  .filters-container input { width: 100%; padding: 0.75rem 1rem 0.75rem 3rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background-color: var(--bg-page); }
  
  .repairs-table { width: 100%; border-collapse: collapse; }
  .repairs-table thead th {
      padding: 0.8rem 1rem; text-align: left; background-color: #f8fafc;
      color: var(--text-secondary); font-weight: 600; font-size: 0.75rem;
      text-transform: uppercase; letter-spacing: 0.05em;
  }
  .repairs-table tbody td { padding: 1rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
  .repairs-table tbody tr { transition: background-color 0.2s ease; }
  .repairs-table tbody tr:hover { background-color: var(--brand-green-light); }
  .status-badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.75rem; border-radius: 9999px; font-weight: 500; font-size: 0.8rem; }
  .status-badge::before { content: ''; width: 8px; height: 8px; border-radius: 50%; }
  .status-in-attesa { background-color: #fff7ed; color: #c2410c; } .status-in-attesa::before { background-color: var(--brand-orange); }
  .status-in-lavorazione { background-color: #eff6ff; color: #1d4ed8; } .status-in-lavorazione::before { background-color: var(--brand-blue); }
  .status-completata { background-color: #f0fdf4; color: #15803d; } .status-completata::before { background-color: var(--brand-green); }
  .status-consegnata { background-color: #f0fdf4; color: #166534; } .status-consegnata::before { background-color: #16a34a; }
  .status-annullata { background-color: #fef2f2; color: #991b1b; } .status-annullata::before { background-color: var(--brand-red); }
  
  .actions-wrapper { position: relative; }
  .btn-actions { background: transparent; border: none; cursor: pointer; padding: 0.5rem; font-size: 1.1rem; color: var(--text-secondary); border-radius: 50%; transition: all 0.2s ease; }
  .btn-actions:hover { color: var(--text-primary); background-color: #e2e8f0; }
  .actions-popup {
      position: absolute; top: 100%; right: 0; background: var(--card-bg);
      box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
      border-radius: 0.5rem; width: 200px; z-index: 50;
      opacity: 0; visibility: hidden; transform: translateY(10px);
      transition: all 0.2s ease-in-out; pointer-events: none;
  }
  .actions-popup.show { opacity: 1; visibility: visible; transform: translateY(0); pointer-events: auto; }
  .actions-popup ul { list-style: none; margin: 0; padding: 0.5rem; }
  .actions-popup li { padding: 0.6rem 0.8rem; cursor: pointer; font-size: 0.875rem; font-weight: 500; color: var(--text-primary); display: flex; align-items: center; gap: 0.75rem; border-radius: 0.375rem; }
  .actions-popup li:hover { background-color: var(--brand-green-light); color: var(--brand-green-dark); }
  .actions-popup li.delete:hover { background-color: #fee2e2; color: var(--brand-red); }

  .modal-overlay {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background-color: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(4px);
      display: flex;
      justify-content: center; align-items: center; z-index: 1000;
      opacity: 0; visibility: hidden; transition: all 0.3s ease;
      padding: 1rem;
  }
  .modal-overlay.show { opacity: 1; visibility: visible; }
  .modal-content {
      background-color: var(--card-bg); 
      padding: 0; 
      border-radius: 20px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      max-width: 95%; 
      width: 900px;
      max-height: 90vh; 
      overflow: hidden;
      display: flex;
      flex-direction: column;
      position: relative;
      transform: translateY(20px) scale(0.95); 
      transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .modal-overlay.show .modal-content { transform: translateY(0) scale(1); }
  
  .modal-header { 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      padding: 1.5rem 2rem;
      background: linear-gradient(135deg, var(--brand-green) 0%, #10b981 100%);
      color: white;
      flex-shrink: 0;
  }
  .modal-header h2 { 
      font-size: 1.35rem; 
      font-weight: 700;
      color: white;
      margin: 0;
  }
  .modal-close-button { 
      background: rgba(255,255,255,0.2); 
      border: none; 
      font-size: 1.5rem; 
      color: white; 
      cursor: pointer;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      line-height: 1;
  }
  .modal-close-button:hover {
      background: rgba(255,255,255,0.3);
      transform: rotate(90deg);
  }
  
  .modal-content .tab-buttons { 
      display: flex; 
      justify-content: center;
      gap: 8px;
      padding: 1rem 2rem;
      background: #f8fafc;
      border-bottom: none;
      margin-bottom: 0;
      flex-shrink: 0;
  }
  .modal-content .tab-button { 
      padding: 0.65rem 1.25rem; 
      cursor: pointer; 
      border: none; 
      background: white;
      border-radius: 10px;
      font-weight: 600; 
      font-size: 0.85rem;
      color: var(--text-secondary); 
      margin-bottom: 0;
      transition: all 0.2s ease;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  }
  .modal-content .tab-button:hover:not(.active) {
      background: #f1f5f9;
      color: var(--text-primary);
  }
  .modal-content .tab-button.active { 
      color: white; 
      background: var(--brand-green);
      box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
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
  
  /* Form fields nel modal */
  .modal-content label.font-medium,
  .modal-content label {
      display: block;
      font-weight: 600;
      font-size: 0.75rem;
      color: var(--text-secondary);
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.3px;
  }
  .modal-content input[type="text"],
  .modal-content input[type="number"],
  .modal-content input[type="email"],
  .modal-content select,
  .modal-content textarea {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 2px solid var(--border-color);
      border-radius: 10px;
      font-size: 0.95rem;
      transition: all 0.2s ease;
      background: white;
      margin-top: 0;
  }
  .modal-content input:focus,
  .modal-content select:focus,
  .modal-content textarea:focus {
      border-color: var(--brand-green);
      outline: none;
      box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
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
      padding: 0.75rem 1.5rem; 
      border-radius: 10px; 
      font-weight: 600;
      font-size: 0.9rem;
      border: none;
      cursor: pointer;
      transition: all 0.2s ease;
  }
  .modal-footer .btn-cancel { 
      background-color: #f1f5f9; 
      color: #64748b;
      border: 2px solid #e2e8f0;
  }
  .modal-footer .btn-cancel:hover {
      background-color: #e2e8f0;
  }
  .modal-footer .btn-save { 
      background-color: var(--brand-green); 
      color: white; 
  }
  .modal-footer .btn-save:hover {
      background-color: var(--brand-green-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
  }

  .tab-buttons { display: flex; border-bottom: 2px solid var(--border-color); margin-bottom: 1.5rem; }
  .tab-button { padding: 0.75rem 1.25rem; cursor: pointer; border: none; background: none; font-weight: 600; color: var(--text-secondary); border-bottom: 3px solid transparent; margin-bottom: -2px; }
  .tab-button.active { color: var(--brand-green); border-color: var(--brand-green); }
  .tab-content { display: none; } .tab-content.active { display: block; }

  @media (max-width: 768px) {
      .page-container { padding: 0 1rem; }
      .repairs-table thead { display: none; }
      .repairs-table tbody, .repairs-table tr, .repairs-table td { display: block; width: 100%; }
      .repairs-table tr { margin-bottom: 1rem; border: 1px solid var(--border-color); border-radius: var(--card-radius); padding: 1rem; }
      .repairs-table td { border: none; padding: 0.5rem 0; display: flex; justify-content: space-between; align-items: center; text-align: right; }
      .repairs-table td::before { content: attr(data-label); font-weight: 600; color: var(--text-secondary); text-align: left; }
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
    <div class="page-container">
        <div class="page-header">
            <h1>Dashboard Riparazioni</h1>
            <a href="nuova_riparazione.php" class="btn-primary"><i class="fas fa-plus"></i> Nuova Riparazione</a>
        </div>

        <div class="summary-card-container">
            <div class="summary-card pending">
                <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <div id="summaryPending" class="value">0</div>
                    <div class="label">In Attesa</div>
                </div>
            </div>
            <div class="summary-card in-progress">
                <div class="icon"><i class="fas fa-cogs"></i></div>
                <div>
                    <div id="summaryInProgress" class="value">0</div>
                    <div class="label">In Lavorazione</div>
                </div>
            </div>
            <div class="summary-card completed">
                <div class="icon"><i class="fas fa-check-double"></i></div>
                <div>
                    <div id="summaryCompleted" class="value">0</div>
                    <div class="label">Completate</div>
                </div>
            </div>
             <div class="summary-card completed">
                <div class="icon"><i class="fas fa-euro-sign"></i></div>
                <div>
                    <div id="summaryTotalRevenue" class="value">0,00 €</div>
                    <div class="label">Totale Incassato (Completate)</div>
                </div>
            </div>
        </div>

        <div class="table-main-card">
            <div class="filters-container">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Cerca per ID, cliente, modello, difetto...">
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

  const tableBody = document.getElementById('repairsTableBody');
  const searchInput = document.getElementById('searchInput');

  function formatCurrency(value) {
    const numValue = parseFloat(value) || 0;
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(numValue);
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
  }

  function renderTable() {
    tableBody.innerHTML = '';
    if (filteredRepairs.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-gray-500">Nessuna riparazione trovata.</td></tr>`;
        return;
    }
    filteredRepairs.forEach(r => {
        const row = document.createElement('tr');
        const statusClass = (r.stato || '').toLowerCase().replace(' ', '-');
        const shortDiagnosi = (r.diagnosi || '').length > 40 ? r.diagnosi.substring(0, 40) + '...' : r.diagnosi;
        row.innerHTML = `
            <td data-label="ID:">${r.id}</td>
            <td data-label="Cliente:">${r.cliente_nome || ''} ${r.cliente_cognome || ''}</td>
            <td data-label="Modello:">${r.modello || ''}</td>
            <td data-label="Difetto:" title="${r.diagnosi || ''}">${shortDiagnosi}</td>
            <td data-label="Data:">${new Date(r.data_creazione).toLocaleDateString('it-IT')}</td>
            <td data-label="Stato:"><span class="status-badge status-${statusClass}">${r.stato || 'N/D'}</span></td>
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

  function applyFilters() {
      const searchTerm = searchInput.value.toLowerCase();
      filteredRepairs = allRepairs.filter(r => {
          return (r.id.toString().includes(searchTerm) ||
                 (r.cliente_nome || '').toLowerCase().includes(searchTerm) ||
                 (r.cliente_cognome || '').toLowerCase().includes(searchTerm) ||
                 (r.modello || '').toLowerCase().includes(searchTerm) ||
                 (r.diagnosi || '').toLowerCase().includes(searchTerm));
      });
      renderTable();
  }
  
  searchInput.addEventListener('input', applyFilters);

  document.addEventListener('click', (e) => {
    const activePopup = document.querySelector('.actions-popup.show');
    if (e.target.closest('.btn-actions')) {
        const button = e.target.closest('.btn-actions');
        const id = button.dataset.id;
        const popup = document.getElementById(`popup-${id}`);
        if (activePopup && activePopup !== popup) activePopup.classList.remove('show');
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
      renderTable();
      <?php echo $message; ?>
  });
</script>

</body>
</html>

