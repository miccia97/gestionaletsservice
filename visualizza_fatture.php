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
    // Prepara il messaggio per il toast in caso di errore
    $_SESSION['message'] = "Errore nel caricamento delle fatture (SQL): " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    $_SESSION['isError'] = true;
    // Non possiamo fare redirect qui, quindi usiamo JS per mostrare il messaggio al caricamento della pagina
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showToast('" . addslashes($_SESSION['message']) . "', 'error'); });</script>";
    unset($_SESSION['message'], $_SESSION['isError']); // Pulisci subito dopo averlo usato
    error_log("Errore Visualizza Fatture (SQL): " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizza Fatture di Acquisto</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root {
            --theme-primary: #22c55e;
            --theme-primary-dark: #16a34a;
            --theme-primary-light: #f0fdf4;
            --theme-warning: #facc15;
            --theme-warning-light: #fefce8;
            --theme-info: #38bdf8;
            --theme-info-light: #ecfccb;
            --theme-danger: #ef4444;
            --theme-danger-dark: #dc2626;
            --theme-bg-page: #f8fafc;
            --theme-bg-card: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.07), 0 2px 4px -2px rgb(0 0 0 / 0.07);
            --card-radius: 0.75rem;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--theme-bg-page);
            color: var(--text-primary);
            padding-top: 80px;
        }

        .page-container {
            max-width: 1600px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2.25rem;
            font-weight: 800;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .btn-primary {
            background-color: var(--theme-primary);
            color: white;
            box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1), 0 1px 2px -1px rgba(0,0,0,0.1);
        }
        .btn-primary:hover {
            background-color: var(--theme-primary-dark);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }

        .summary-card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background-color: var(--theme-bg-card);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        .summary-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        .summary-card .icon {
            font-size: 1.75rem;
            padding: 1rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .summary-card.total { border-color: var(--theme-info); }
        .summary-card.total .icon { background-color: var(--theme-info-light); color: var(--theme-info); }
        .summary-card.paid { border-color: var(--theme-primary); }
        .summary-card.paid .icon { background-color: var(--theme-primary-light); color: var(--theme-primary); }
        .summary-card.pending { border-color: var(--theme-warning); }
        .summary-card.pending .icon { background-color: var(--theme-warning-light); color: var(--theme-warning); }

        .summary-card .value {
            font-size: 1.875rem;
            font-weight: 700;
        }
        .summary-card .label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .table-card {
            background-color: var(--theme-bg-card);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            padding: 2rem;
        }

        .filters-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .filters-container .search-wrapper { position: relative; flex-grow: 1; }
        .filters-container .search-icon { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }
        .filters-container input, .filters-container select {
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            background-color: var(--theme-bg-page);
        }
        .filters-container input { padding-left: 2.5rem; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead th {
            padding: 0.8rem 1rem;
            text-align: left;
            background-color: #f1f5f9;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--border-color);
        }

        .data-table tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            transition: background-color 0.2s ease;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .data-table tbody tr:hover {
            background-color: var(--theme-primary-light);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 500;
            font-size: 0.875rem;
        }
        .status-Da-Verificare { background-color: #fef9c3; color: #ca8a04; }
        .status-Registrata { background-color: #dbeafe; color: #2563eb; }
        .status-Pagata { background-color: #dcfce7; color: #16a34a; }

        .action-button {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            font-size: 1rem;
            color: var(--text-secondary);
            transition: all 0.2s ease;
            border-radius: 50%;
        }
        .action-button:hover {
            color: var(--text-primary);
            background-color: #e2e8f0;
        }
        .action-button.delete:hover {
            color: var(--theme-danger);
            background-color: #fee2e2;
        }
        
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
        }
        .pagination-info { font-size: 0.875rem; color: var(--text-secondary); }
        .pagination-buttons button {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            background-color: white;
            margin-left: 0.5rem;
            cursor: pointer;
        }
        .pagination-buttons button.active, .pagination-buttons button:hover {
            background-color: var(--theme-primary);
            color: white;
            border-color: var(--theme-primary);
        }
        .pagination-buttons button:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .toast {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            border-left-width: 4px;
            animation: toastIn 0.5s ease, toastOut 0.5s ease 3.5s forwards;
        }
        .toast.success { border-color: var(--theme-primary); }
        .toast.error { border-color: var(--theme-danger); }
        .toast-icon { font-size: 1.5rem; }
        .toast.success .toast-icon { color: var(--theme-primary); }
        .toast.error .toast-icon { color: var(--theme-danger); }

        @keyframes toastIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes toastOut { from { opacity: 1; } to { opacity: 0; transform: scale(0.9); } }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(30, 41, 59, 0.5);
            display: flex; justify-content: center; align-items: center;
            z-index: 5000;
            opacity: 0; visibility: hidden; transition: opacity 0.3s ease;
        }
        .modal-overlay.show { opacity: 1; visibility: visible; }
        
        .modal-content {
            background-color: var(--theme-bg-card);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }
        .modal-overlay.show .modal-content { transform: scale(1); }
        
        .modal-header { padding: 1.5rem; border-bottom: 1px solid var(--border-color); }
        .modal-header h2 { font-size: 1.5rem; font-weight: 700; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { padding: 1.5rem; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 1rem; }

        #viewInvoiceModalContent .modal-content { max-width: 900px; }
        #deleteInvoiceModalContent .modal-content { max-width: 500px; }
        
        .btn-danger { background-color: var(--theme-danger); color: white; }
        .btn-danger:hover { background-color: var(--theme-danger-dark); }
        .btn-secondary { background-color: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background-color: #cbd5e1; }

        /* Responsive */
        @media (max-width: 768px) {
            .page-container { padding: 0 1rem; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .data-table thead { display: none; }
            .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
            .data-table tr {
                margin-bottom: 1rem;
                border: 1px solid var(--border-color);
                border-radius: var(--card-radius);
                padding: 1rem;
            }
            .data-table td {
                border: none; padding: 0.5rem 0;
                display: flex; justify-content: space-between; align-items: center; text-align: right;
            }
            .data-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--text-secondary);
                text-align: left;
                padding-right: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; // Includi il tuo header centralizzato ?>

    <div class="page-container">
        <div class="page-header">
            <h1>Elenco Fatture</h1>
            <a href="gestione_fatture.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuova Fattura
            </a>
        </div>
        
        <!-- Summary Cards -->
        <div class="summary-card-container">
            <div class="summary-card total">
                <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div>
                    <div id="summaryTotalAmount" class="value">0,00 €</div>
                    <div class="label">Importo Totale</div>
                </div>
            </div>
            <div class="summary-card paid">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div id="summaryPaidAmount" class="value">0,00 €</div>
                    <div class="label">Importo Pagato</div>
                </div>
            </div>
            <div class="summary-card pending">
                <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <div id="summaryPendingAmount" class="value">0,00 €</div>
                    <div class="label">Da Verificare</div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="filters-container">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Cerca per n° fattura o fornitore...">
                </div>
                <select id="statusFilter">
                    <option value="">Tutti gli stati</option>
                    <option value="Da Verificare">Da Verificare</option>
                    <option value="Registrata">Registrata</option>
                    <option value="Pagata">Pagata</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table">
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
                        <!-- Le righe verranno popolate da JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <div id="paginationContainer" class="pagination-container">
                 <!-- La paginazione verrà generata da JavaScript -->
            </div>
        </div>
    </div>
    
    <div id="toastContainer" class="toast-container"></div>

    <div id="mainModal" class="modal-overlay">
        <!-- Delete Modal Content -->
        <div id="deleteInvoiceModalContent" class="hidden">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Conferma Eliminazione</h2>
                </div>
                <div class="modal-body">
                    <p>Sei sicuro di voler eliminare la fattura #<strong id="deleteInvoiceId"></strong>? L'azione è irreversibile.</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal()">Annulla</button>
                    <button class="btn btn-danger" id="confirmDeleteInvoiceButton">Elimina</button>
                </div>
            </div>
        </div>

        <!-- View Modal Content -->
        <div id="viewInvoiceModalContent" class="hidden">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Dettagli Fattura #<span id="viewInvoiceId"></span></h2>
                </div>
                <div class="modal-body">
                     <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div><strong class="block text-sm text-gray-500">Numero Fattura</strong> <span id="detailInvoiceNumber" class="text-lg"></span></div>
                        <div><strong class="block text-sm text-gray-500">Data</strong> <span id="detailInvoiceDate" class="text-lg"></span></div>
                        <div class="lg:col-span-2"><strong class="block text-sm text-gray-500">Fornitore</strong> <span id="detailSupplierName" class="text-lg"></span></div>
                        <div><strong class="block text-sm text-gray-500">Stato</strong> <span id="detailStatus"></span></div>
                        <div><strong class="block text-sm text-gray-500">Imponibile</strong> <span id="detailTotalNet" class="text-lg"></span></div>
                        <div><strong class="block text-sm text-gray-500">IVA</strong> <span id="detailTotalVAT" class="text-lg"></span></div>
                        <div><strong class="block text-sm text-gray-500">Totale</strong> <span id="detailTotalGross" class="text-lg font-bold"></span></div>
                     </div>
                     <div class="mb-4"><strong class="block text-sm text-gray-500">Allegato</strong> <span id="detailAttachment"></span></div>

                    <h3 class="text-xl font-semibold mb-4">Dettaglio Prodotti</h3>
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prodotto</th>
                                    <th class="p-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qtà</th>
                                    <th class="p-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Prezzo Netto</th>
                                    <th class="p-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">IVA %</th>
                                    <th class="p-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Totale Riga</th>
                                </tr>
                            </thead>
                            <tbody id="invoiceDetailsProductLines" class="bg-white divide-y divide-gray-200">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal()">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const allInvoices = <?php echo json_encode($fatture); ?>;
        
        let currentPage = 1;
        const rowsPerPage = 10;
        let filteredInvoices = [...allInvoices];

        // DOM Elements
        const tableBody = document.getElementById('invoicesTableBody');
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const paginationContainer = document.getElementById('paginationContainer');

        // Funzione per mostrare le notifiche "Toast"
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>';
            toast.innerHTML = `<div class="toast-icon">${icon}</div><div>${message}</div>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.remove();
            }, 4000);
        }

        // Gestione Modali
        const mainModal = document.getElementById('mainModal');
        const modalContents = {
            delete: document.getElementById('deleteInvoiceModalContent'),
            view: document.getElementById('viewInvoiceModalContent')
        };
        
        function openModal(type) {
            Object.values(modalContents).forEach(content => content.classList.add('hidden'));
            if (modalContents[type]) {
                modalContents[type].classList.remove('hidden');
                mainModal.classList.add('show');
            }
        }

        function closeModal() {
            mainModal.classList.remove('show');
        }

        mainModal.addEventListener('click', e => { if (e.target === mainModal) closeModal(); });

        function formatCurrency(value) {
            const numValue = parseFloat(value) || 0;
            return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(numValue);
        }

        function updateSummaryCards() {
            let totalAmount = 0;
            let paidAmount = 0;
            let pendingAmount = 0;
            
            allInvoices.forEach(invoice => {
                const total = parseFloat(invoice.totale_lordo) || 0;
                totalAmount += total;
                if(invoice.stato === 'Pagata') {
                    paidAmount += total;
                } else if(invoice.stato === 'Da Verificare') {
                    pendingAmount += total;
                }
            });

            document.getElementById('summaryTotalAmount').textContent = formatCurrency(totalAmount);
            document.getElementById('summaryPaidAmount').textContent = formatCurrency(paidAmount);
            document.getElementById('summaryPendingAmount').textContent = formatCurrency(pendingAmount);
        }
        
        function renderTable() {
            tableBody.innerHTML = '';
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const paginatedInvoices = filteredInvoices.slice(start, end);

            if (paginatedInvoices.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-gray-500">Nessuna fattura trovata per i criteri selezionati.</td></tr>`;
                return;
            }

            paginatedInvoices.forEach(fattura => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td data-label="ID:">${fattura.id}</td>
                    <td data-label="Numero Fattura:">${fattura.numero_fattura}</td>
                    <td data-label="Data:">${new Date(fattura.data_fattura).toLocaleDateString('it-IT')}</td>
                    <td data-label="Fornitore:">${fattura.nome_fornitore}</td>
                    <td data-label="Stato:">
                        <span class="status-badge status-${fattura.stato.replace(' ', '-')}">
                            ${fattura.stato}
                        </span>
                    </td>
                    <td data-label="Totale Lordo:" class="text-right font-semibold">${formatCurrency(fattura.totale_lordo)}</td>
                    <td data-label="Azioni:" class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button class="action-button" title="Visualizza Dettagli" data-action="view" data-id="${fattura.id}"><i class="fas fa-eye"></i></button>
                            <a href="gestione_fatture.php?id=${fattura.id}" class="action-button" title="Modifica Fattura"><i class="fas fa-edit"></i></a>
                            <button class="action-button delete" title="Elimina Fattura" data-action="delete" data-id="${fattura.id}"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }
        
        function renderPagination() {
            const totalPages = Math.ceil(filteredInvoices.length / rowsPerPage);
            paginationContainer.innerHTML = '';
            
            if (totalPages <= 1) return;

            let paginationInfo = `<div class="pagination-info">Mostrando ${filteredInvoices.length > 0 ? (currentPage - 1) * rowsPerPage + 1 : 0}-${Math.min(currentPage * rowsPerPage, filteredInvoices.length)} di ${filteredInvoices.length} risultati</div>`;
            
            let buttonsHTML = '<div class="pagination-buttons">';
            buttonsHTML += `<button onclick="changePage(currentPage - 1)" ${currentPage === 1 ? 'disabled' : ''}>Precedente</button>`;
            
            for (let i = 1; i <= totalPages; i++) {
                 buttonsHTML += `<button onclick="changePage(${i})" class="${i === currentPage ? 'active' : ''}">${i}</button>`;
            }

            buttonsHTML += `<button onclick="changePage(currentPage + 1)" ${currentPage === totalPages ? 'disabled' : ''}>Successivo</button>`;
            buttonsHTML += '</div>';

            paginationContainer.innerHTML = paginationInfo + buttonsHTML;
        }
        
        function changePage(page) {
            const totalPages = Math.ceil(filteredInvoices.length / rowsPerPage);
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            renderTable();
            renderPagination();
        }

        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase();
            const status = statusFilter.value;
            
            filteredInvoices = allInvoices.filter(invoice => {
                const matchesSearch = invoice.numero_fattura.toLowerCase().includes(searchTerm) || invoice.nome_fornitore.toLowerCase().includes(searchTerm);
                const matchesStatus = status === '' || invoice.stato === status;
                return matchesSearch && matchesStatus;
            });
            
            currentPage = 1;
            renderTable();
            renderPagination();
        }

        searchInput.addEventListener('input', applyFilters);
        statusFilter.addEventListener('change', applyFilters);

        // Gestione Azioni Tabella
        tableBody.addEventListener('click', async (event) => {
            const button = event.target.closest('.action-button');
            if (!button) return;

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
                            document.getElementById('detailAttachment').innerHTML = `<a href="${invoice.allegato_url}" target="_blank" class="text-blue-600 hover:underline">Visualizza file <i class="fas fa-external-link-alt ml-1"></i></a>`;
                        } else {
                            document.getElementById('detailAttachment').textContent = 'Nessun allegato';
                        }
                        
                        const productLinesBody = document.getElementById('invoiceDetailsProductLines');
                        productLinesBody.innerHTML = '';
                        if (details.length > 0) {
                            details.forEach(p => {
                                productLinesBody.innerHTML += `
                                    <tr class="hover:bg-gray-50">
                                        <td class="p-3 text-sm text-gray-700">${p.descrizione_prodotto}</td>
                                        <td class="p-3 text-sm text-gray-700 text-right">${p.quantita} ${p.unita_misura}</td>
                                        <td class="p-3 text-sm text-gray-700 text-right">${formatCurrency(p.prezzo_unitario_netto)}</td>
                                        <td class="p-3 text-sm text-gray-700 text-right">${p.iva_percentuale}%</td>
                                        <td class="p-3 text-sm text-gray-900 text-right font-semibold">${formatCurrency(p.totale_riga_lordo)}</td>
                                    </tr>`;
                            });
                        } else {
                             productLinesBody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">Nessun dettaglio prodotto trovato.</td></tr>';
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
                    // Rimuovi la fattura dall'array principale e ri-filtra/ri-renderizza
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
        
        // Initial Load
        document.addEventListener('DOMContentLoaded', () => {
            updateSummaryCards();
            applyFilters();
            <?php echo $message; // Esegue lo script JS per i messaggi di sessione ?>
        });

    </script>
</body>
</html>

