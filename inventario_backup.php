<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Inventario - Professionale</title>
    <!-- Carica Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome per icone -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" rel="stylesheet">
    <!-- NUOVO: Chart.js per i grafici -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- NUOVO: JsBarcode per i codici a barre -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        :root {
            --brand-color: #28a745;
            --brand-dark: #218838;
            --primary-action-color: #0d6efd; /* Blu per azioni primarie */
            --primary-action-dark: #0b5ed7;
            --secondary-action-color: #6c757d; /* Grigio per azioni secondarie */
            --secondary-action-dark: #5c636a;
            --danger-color: #dc3545;
            --danger-dark: #bb2d3b;
            --warning-color: #ffc107;
            --text-dark: #34495e;
            --text-light: #7f8c8d;
            --border-color: #dee2e6;
            --bg-light: #f8f9fa;
            --bg-white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: var(--bg-light);
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
        }
        
        /* Stili Dashboard Cards */
        .stat-card {
            background-color: var(--bg-white); border-radius: 0.75rem; padding: 1.5rem;
            box-shadow: var(--shadow-md); border: 1px solid var(--border-color);
            display: flex; align-items: center; gap: 1rem;
        }
        .stat-icon {
            font-size: 2rem; width: 60px; height: 60px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; color: white;
        }
        .stat-value { font-size: 1.75rem; font-weight: 700; }
        .stat-label { font-size: 0.9rem; color: var(--text-light); }
        
        .filter-card {
            background-color: var(--bg-white); padding: 1.5rem; border-radius: 0.75rem;
            box-shadow: var(--shadow-md); border: 1px solid var(--border-color);
        }

        #toast-container { position: fixed; top: 90px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 0.5rem; }
        .toast { min-width: 300px; padding: 1rem 1.5rem; border-radius: 0.5rem; color: white; box-shadow: var(--shadow-lg); display: flex; align-items: center; gap: 1rem; opacity: 0; transform: translateX(100%); animation: slideIn 0.5s forwards, fadeOut 0.5s 4.5s forwards; }
        .toast.success { background-color: #198754; }
        .toast.error { background-color: var(--danger-color); }
        .toast-icon { font-size: 1.5rem; }
        @keyframes slideIn { to { opacity: 1; transform: translateX(0); } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; transform: translateX(100%); } }

        .table-container { border: 1px solid var(--border-color); border-radius: 0.75rem; overflow: hidden; box-shadow: var(--shadow-md); }
        table thead { background-color: var(--bg-light); }
        table th.sortable { cursor: pointer; user-select: none; }
        table th.sortable:hover { background-color: #e9ecef; }
        table th .sort-icon { opacity: 0.4; margin-left: 0.5rem; }
        table th.active .sort-icon { opacity: 1; color: var(--primary-action-color); }

        table tbody tr:nth-child(even) { background-color: var(--bg-light); }
        table tbody tr:hover { background-color: #e9ecef; }
        table tbody tr.selected { background-color: #cfe2ff !important; }
        
        .item-thumbnail { width: 50px; height: 50px; object-fit: cover; border-radius: 0.375rem; border: 2px solid var(--border-color); }

        .skeleton-row td { padding: 1rem; }
        .skeleton { background-color: #e0e0e0; border-radius: 0.25rem; animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }

        #empty-state { text-align: center; padding: 4rem 1rem; }
        #empty-state i { font-size: 4rem; color: #ced4da; }
        #empty-state p { font-size: 1.2rem; margin-top: 1rem; color: var(--text-light); }
        
        .action-btn { border: none; color: white; padding: 0.5rem 0.8rem; border-radius: 0.375rem; font-size: 0.875rem; transition: all 0.2s ease; cursor: pointer; box-shadow: var(--shadow-sm); }
        .action-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .action-btn i { margin-right: 0.3rem; }
        .edit-btn { background-color: var(--secondary-action-color); }
        .edit-btn:hover { background-color: var(--secondary-action-dark); transform: translateY(-1px); }
        .delete-btn { background-color: var(--danger-color); }
        .delete-btn:hover { background-color: var(--danger-dark); transform: translateY(-1px); }
        .barcode-btn { background-color: #34495e; }
        .barcode-btn:hover:not(:disabled) { background-color: #2c3e50; transform: translateY(-1px); }

        #pagination-controls { display: flex; justify-content: center; align-items: center; padding: 1rem; gap: 0.5rem; }
        .page-btn { border: 1px solid var(--border-color); background-color: white; color: var(--primary-action-color); padding: 0.5rem 1rem; border-radius: 0.375rem; cursor: pointer; transition: all 0.2s; }
        .page-btn:hover, .page-btn.active { background-color: var(--primary-action-color); color: white; }
        .page-btn:disabled { cursor: not-allowed; background-color: #e9ecef; color: var(--text-light); border-color: #e9ecef;}

        #bulk-actions-bar {
            position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%);
            width: auto; max-width: 90%; background-color: var(--text-dark); color: white;
            padding: 1rem 1.5rem; border-radius: 0.75rem; box-shadow: var(--shadow-lg);
            display: flex; align-items: center; gap: 1.5rem; z-index: 1500;
            transition: bottom 0.3s ease-in-out;
        }
        #bulk-actions-bar.visible { bottom: 20px; }
        
        #report-section {
            display: none; background-color: var(--bg-white); padding: 1.5rem; border-radius: 0.75rem;
            box-shadow: var(--shadow-md); border: 1px solid var(--border-color); margin-bottom: 2rem;
        }

        .modal-overlay{position:fixed;inset:0;background-color:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:2000;opacity:0;visibility:hidden;transition:opacity .3s,visibility .3s}.modal-overlay.visible{opacity:1;visibility:visible}.modal-content{background-color:var(--bg-white);border-radius:.75rem;box-shadow:var(--shadow-lg);max-height:90vh;display:flex;flex-direction:column;transform:scale(.95);transition:transform .3s}.modal-overlay.visible .modal-content{transform:scale(1)}#itemModal .modal-content{max-width:800px;width:95%}#confirmModal .modal-content, #barcodeModal .modal-content{max-width:450px;width:95%;text-align:center}.modal-header{padding:1.5rem;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center}.modal-header h2{font-size:1.5rem;font-weight:600;margin:0}.close-btn{background:0 0;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-light)}.modal-body{padding:1.5rem;overflow-y:auto}.modal-footer{padding:1.5rem;border-top:1px solid var(--border-color);background-color:var(--bg-light);display:flex;justify-content:flex-end;gap:.75rem;border-bottom-left-radius:.75rem;border-bottom-right-radius:.75rem}.tab-nav{display:flex;border-bottom:1px solid var(--border-color);margin-bottom:1.5rem}.tab-button{padding:.75rem 1.25rem;border:none;background-color:transparent;cursor:pointer;font-size:1rem;font-weight:500;color:var(--text-light);border-bottom:3px solid transparent;transition:all .2s ease}.tab-button.active{color:var(--primary-action-color);border-bottom-color:var(--primary-action-color)}.tab-content{display:none}.tab-content.active{display:block;animation:fadeIn .4s}@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
    </style>
</head>
<body class="flex flex-col items-center p-4 sm:p-6 lg:p-8">

    <?php include 'header.php'; ?>

    <div id="toast-container"></div>

    <main class="w-full max-w-7xl mt-8">
        <h1 class="text-4xl font-bold text-gray-800 mb-6">Panoramica Inventario</h1>

        <div id="dashboard" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="stat-card"><div class="stat-icon bg-blue-500"><i class="fas fa-dollar-sign"></i></div><div><div id="totalValue" class="stat-value">€ 0.00</div><div class="stat-label">Valore Totale Inventario</div></div></div>
            <div class="stat-card"><div class="stat-icon bg-green-500"><i class="fas fa-boxes-stacked"></i></div><div><div id="totalItems" class="stat-value">0</div><div class="stat-label">Articoli Totali</div></div></div>
            <div class="stat-card"><div class="stat-icon bg-yellow-500"><i class="fas fa-exclamation-triangle"></i></div><div><div id="lowStockItems" class="stat-value">0</div><div class="stat-label">Articoli in Esaurimento</div></div></div>
        </div>

        <div class="filter-card mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <div class="lg:col-span-1"><label for="searchInput" class="block text-sm font-medium text-gray-700 mb-1">Cerca articolo</label><input type="text" id="searchInput" placeholder="Nome, ID..." class="w-full p-2 border border-gray-300 rounded-md"></div>
                <div><label for="categoryFilter" class="block text-sm font-medium text-gray-700 mb-1">Filtra per Categoria</label><select id="categoryFilter" class="w-full p-2 border border-gray-300 rounded-md bg-white"></select></div>
                <div class="flex justify-start md:justify-end gap-2 col-span-full md:col-span-2 lg:col-span-3">
                    <button id="toggleReportBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-5 rounded-md shadow-md transition w-full sm:w-auto"><i class="fas fa-chart-pie mr-2"></i>Mostra Report</button>
                    <button id="exportCsvBtn" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-md shadow-md transition w-full sm:w-auto"><i class="fas fa-file-csv mr-2"></i>Esporta CSV</button>
                    <button id="addArticleBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-md shadow-md transition w-full sm:w-auto"><i class="fas fa-plus mr-2"></i>Aggiungi Articolo</button>
                </div>
            </div>
        </div>
        
        <div id="report-section">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Analisi Inventario</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-lg font-semibold text-center mb-2">Valore per Categoria</h3>
                    <canvas id="categoryValueChart"></canvas>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-center mb-2">Top 5 Articoli per Quantità</h3>
                    <canvas id="topItemsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="table-container bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead id="tableHeader">
                        <tr>
                            <th class="p-4 w-12"><input type="checkbox" id="selectAllCheckbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"></th>
                            <th class="p-4 text-left w-24">Immagine</th>
                            <th class="p-4 text-left sortable" data-sort="id">ID<i class="fas fa-sort sort-icon"></i></th>
                            <th class="p-4 text-left sortable" data-sort="name">Nome<i class="fas fa-sort sort-icon"></i></th>
                            <th class="p-4 text-left sortable" data-sort="quantity">Quantità<i class="fas fa-sort sort-icon"></i></th>
                            <th class="p-4 text-left sortable" data-sort="prezzo_vendita1">Prezzo (€)<i class="fas fa-sort sort-icon"></i></th>
                            <th class="p-4 text-left">Categoria</th>
                            <th class="p-4 text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody">
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="pagination-controls" class="mt-4"></div>

    </main>
    
    <div id="bulk-actions-bar">
        <span id="bulk-actions-count" class="font-semibold">0 articoli selezionati</span>
        <button id="bulk-delete-btn" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-md shadow-md transition flex items-center gap-2">
            <i class="fas fa-trash"></i> Elimina Selezionati
        </button>
    </div>

    <!-- Modali -->
    <div id="itemModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h2 id="modalTitle"></h2><button type="button" class="close-btn" onclick="closeItemModal()"><i class="fas fa-times"></i></button></div>
            <form id="itemForm" class="flex-grow flex flex-col" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="tab-nav">
                        <button type="button" class="tab-button active" onclick="showTab('principale')">Principale</button>
                        <button type="button" class="tab-button" onclick="showTab('dettagli')">Dettagli</button>
                        <button type="button" class="tab-button" onclick="showTab('categorizzazione')">Categorie</button>
                        <button type="button" class="tab-button" onclick="showTab('immagine')">Immagine</button>
                        <button type="button" class="tab-button" onclick="showTab('storico')">Storico</button>
                    </div>
                    <div id="tab-principale" class="tab-content active"><div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label for="itemName" class="block text-sm font-medium text-gray-700">Nome Articolo*</label><input type="text" id="itemName" name="name" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div><div><label for="itemQuantity" class="block text-sm font-medium text-gray-700">Quantità*</label><input type="number" id="itemQuantity" name="quantity" required min="0" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div><div class="col-span-full"><label for="itemDescription" class="block text-sm font-medium text-gray-700">Descrizione</label><textarea id="itemDescription" name="description" rows="3" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></textarea></div><input type="hidden" id="itemId" name="id"><input type="hidden" id="itemDataCreazione" name="data_creazione"></div></div>
                    <div id="tab-dettagli" class="tab-content"><div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label for="itemPrezzoVendita1" class="block text-sm font-medium">Prezzo Vendita 1 (€)</label><input type="number" id="itemPrezzoVendita1" name="prezzo_vendita1" min="0" step="0.01" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div><div><label for="itemPrezzoVendita2" class="block text-sm font-medium">Prezzo Vendita 2 (€)</label><input type="number" id="itemPrezzoVendita2" name="prezzo_vendita2" min="0" step="0.01" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div><div><label for="itemPrezzoAcquisto" class="block text-sm font-medium">Prezzo Acquisto (€)</label><input type="number" id="itemPrezzoAcquisto" name="prezzo_acquisto" min="0" step="0.01" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div><div><label for="itemBarcode" class="block text-sm font-medium">Barcode</label><input type="text" id="itemBarcode" name="barcode" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div><div><label for="itemImei" class="block text-sm font-medium">IMEI</label><input type="text" id="itemImei" name="imei" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div></div></div>
                    <div id="tab-categorizzazione" class="tab-content"><div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label for="itemCategoria" class="block text-sm font-medium">Categoria</label><select id="itemCategoria" name="categoria" class="mt-1 block w-full p-2 border bg-white border-gray-300 rounded-md"></select></div><div><label for="itemSottocategoria" class="block text-sm font-medium">Sottocategoria</label><select id="itemSottocategoria" name="sottocategoria" class="mt-1 block w-full p-2 border bg-white border-gray-300 rounded-md"></select></div><div><label for="itemSottoSottocategoria" class="block text-sm font-medium">Sotto Sottocategoria</label><select id="itemSottoSottocategoria" name="sottosottocategoria" class="mt-1 block w-full p-2 border bg-white border-gray-300 rounded-md"></select></div><div><label for="itemTipoProdotto" class="block text-sm font-medium">Tipo Prodotto</label><input type="text" id="itemTipoProdotto" name="tipo_prodotto" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div></div></div>
                    <div id="tab-immagine" class="tab-content"><label for="itemImage" class="block text-sm font-medium text-gray-700">Carica Immagine</label><input type="file" id="itemImage" name="image" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"><div id="imagePreviewContainer" class="mt-4 hidden justify-center items-center relative border border-gray-200 rounded-lg p-2 h-48 bg-gray-50"><img id="itemImagePreview" src="#" alt="Anteprima" class="max-w-full h-full object-contain rounded-md" style="display: none;"><button type="button" id="clearImageBtn" class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-1.5 flex items-center justify-center leading-none" title="Rimuovi"><i class="fas fa-times text-xs"></i></button></div></div>
                    <div id="tab-storico" class="tab-content">
                        <p id="log-loading-message" class="text-center text-gray-500">Caricamento storico...</p>
                        <div id="log-container" class="max-h-64 overflow-y-auto"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" onclick="closeItemModal()" class="py-2 px-4 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Annulla</button><button type="submit" id="saveBtn" class="py-2 px-4 bg-blue-600 text-white rounded-md hover:bg-blue-700">Salva</button></div>
            </form>
        </div>
    </div>
    <div id="confirmModal" class="modal-overlay">
        <div class="modal-content p-8"><div class="text-red-500 text-4xl mb-4"><i class="fas fa-exclamation-triangle"></i></div><h2 class="text-2xl font-bold mb-2">Conferma Eliminazione</h2><p class="text-gray-600 mb-6">Sei sicuro di voler eliminare <span id="confirmItemName" class="font-semibold"></span>? L'azione è irreversibile.</p><div class="flex justify-center gap-4"><button type="button" id="cancelDeleteBtn" class="py-2 px-6 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Annulla</button><button type="button" id="confirmDeleteBtn" class="py-2 px-6 bg-red-600 text-white rounded-md hover:bg-red-700">Elimina</button></div></div>
    </div>
    <div id="barcodeModal" class="modal-overlay">
        <div class="modal-content p-8"><div class="modal-header pb-4"><h2 id="barcodeModalTitle" class="text-2xl font-bold"></h2><button type="button" class="close-btn" onclick="closeBarcodeModal()"><i class="fas fa-times"></i></button></div><div class="modal-body items-center flex flex-col"><canvas id="barcodeCanvas"></canvas></div><div class="modal-footer"><button id="printBarcodeBtn" class="py-2 px-4 bg-blue-600 text-white rounded-md hover:bg-blue-700"><i class="fas fa-print mr-2"></i>Stampa</button></div></div>
    </div>


    <script>
        // --- CONFIGURAZIONE E COSTANTI ---
        const API_URL = 'api.php';
        const UPLOADS_DIR = 'uploads/';
        const ITEMS_PER_PAGE = 10;
        const LOW_STOCK_THRESHOLD = 5;

        // --- RIFERIMENTI DOM ---
        const inventoryTableBody = document.getElementById('inventoryTableBody');
        const tableHeader = document.getElementById('tableHeader');
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const itemModal = document.getElementById('itemModal');
        const modalTitle = document.getElementById('modalTitle');
        const itemForm = document.getElementById('itemForm');
        const confirmModal = document.getElementById('confirmModal');
        const toastContainer = document.getElementById('toast-container');
        const paginationControls = document.getElementById('pagination-controls');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const bulkActionsBar = document.getElementById('bulk-actions-bar');
        const bulkActionsCount = document.getElementById('bulk-actions-count');
        const barcodeModal = document.getElementById('barcodeModal');
        const formFields = {id:document.getElementById("itemId"),name:document.getElementById("itemName"),quantity:document.getElementById("itemQuantity"),description:document.getElementById("itemDescription"),prezzo_vendita1:document.getElementById("itemPrezzoVendita1"),prezzo_vendita2:document.getElementById("itemPrezzoVendita2"),prezzo_acquisto:document.getElementById("itemPrezzoAcquisto"),categoria:document.getElementById("itemCategoria"),sottocategoria:document.getElementById("itemSottocategoria"),sottosottocategoria:document.getElementById("itemSottoSottocategoria"),tipo_prodotto:document.getElementById("itemTipoProdotto"),barcode:document.getElementById("itemBarcode"),imei:document.getElementById("itemImei"),data_creazione:document.getElementById("itemDataCreazione"),image:document.getElementById("itemImage")};
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
        const itemImagePreview = document.getElementById('itemImagePreview');
        
        // --- STATO APPLICAZIONE ---
        let allInventoryItems = [];
        let filteredAndSortedItems = [];
        let fetchedCategories = [];
        let currentItemId = null;
        let currentPage = 1;
        let sortState = { column: 'id', direction: 'asc' };
        let selectedItems = new Set();
        let categoryValueChart = null;
        let topItemsChart = null;

        // --- FUNZIONI PRINCIPALI ---

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            toast.className = `toast ${type}`;
            toast.innerHTML = `<i class="fas ${iconClass} toast-icon"></i><span>${message}</span>`;
            toastContainer.appendChild(toast);
            toast.addEventListener('animationend', () => { if (toast.style.animationName === 'fadeOut') toast.remove() });
        }

        async function fetchData() {
            showLoadingState();
            try {
                const [inventoryRes, categoriesRes] = await Promise.all([
                    fetch(API_URL),
                    fetch(`${API_URL}?get=categories`)
                ]);
                if (!inventoryRes.ok || !categoriesRes.ok) throw new Error('Errore di rete');
                allInventoryItems = await inventoryRes.json();
                fetchedCategories = await categoriesRes.json();
                
                if (!Array.isArray(allInventoryItems)) {
                    console.error("L'API dell'inventario non ha restituito un array:", allInventoryItems);
                    allInventoryItems = []; // Previene errori successivi
                    throw new Error("Formato dati inventario non valido.");
                }

                populateCategoryFilter();
                processAndRenderData();
                renderCharts();
            } catch (error) {
                console.error('Errore caricamento dati:', error);
                showToast('Impossibile caricare i dati.', 'error');
                showEmptyState("Errore di connessione o formato dati non valido.");
            }
        }

        function processAndRenderData() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedCategory = categoryFilter.value;
            
            let filtered = allInventoryItems.filter(item => {
                if (!item) return false; // Sicurezza extra per dati corrotti
                const name = item.name || "";
                const id = item.id || "";
                const category = item.categoria || "";

                const matchesSearch = searchTerm === '' ||
                    name.toLowerCase().includes(searchTerm) ||
                    String(id).toLowerCase().includes(searchTerm);
                const matchesCategory = selectedCategory === '' || category === selectedCategory;
                return matchesSearch && matchesCategory;
            });

            filtered.sort((a, b) => {
                let valA = a[sortState.column];
                let valB = b[sortState.column];
                const isNumeric = typeof (valA || valB) === 'number';

                valA = valA ?? (isNumeric ? 0 : '');
                valB = valB ?? (isNumeric ? 0 : '');

                if (isNumeric) {
                    return sortState.direction === 'asc' ? valA - valB : valB - valA;
                } else {
                    return sortState.direction === 'asc' 
                        ? String(valA).localeCompare(String(valB)) 
                        : String(valB).localeCompare(String(valA));
                }
            });

            filteredAndSortedItems = filtered;
            updateDashboard(filtered);
            const paginatedItems = filtered.slice((currentPage - 1) * ITEMS_PER_PAGE, currentPage * ITEMS_PER_PAGE);
            renderTable(paginatedItems);
            renderPagination(filtered.length);
            updateBulkActionsBar();
        }

        function renderTable(items) {
            inventoryTableBody.innerHTML = '';
            if (items.length === 0) {
                const message = (searchInput.value || categoryFilter.value) ? "Nessun articolo corrisponde ai filtri." : "L'inventario è vuoto.";
                showEmptyState(message);
            } else {
                items.forEach(item => {
                    const row = document.createElement('tr');
                    const itemName = item.name || 'Senza nome';
                    if (selectedItems.has(String(item.id))) row.classList.add('selected');
                    const imageSrc = item.immagine ? `${UPLOADS_DIR}${item.immagine}` : 'https://placehold.co/100x100/e2e8f0/e2e8f0?text=N/A';
                    const hasBarcode = item.barcode && String(item.barcode).trim() !== '';

                    row.innerHTML = `
                        <td class="p-4"><input type="checkbox" data-id="${item.id}" class="row-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" ${selectedItems.has(String(item.id)) ? 'checked' : ''}></td>
                        <td class="p-2"><img src="${imageSrc}" alt="${itemName}" class="item-thumbnail"></td>
                        <td class="p-4">${item.id || 'N/A'}</td>
                        <td class="p-4 font-semibold">${itemName}</td>
                        <td class="p-4">${item.quantity || 0}</td>
                        <td class="p-4">${parseFloat(item.prezzo_vendita1 || 0).toFixed(2)}</td>
                        <td class="p-4">${item.categoria || 'N/D'}</td>
                        <td class="p-4 text-right whitespace-nowrap">
                            <button data-id="${item.id}" data-barcode="${item.barcode || ''}" class="action-btn barcode-btn" ${!hasBarcode ? 'disabled' : ''} title="${hasBarcode ? 'Mostra barcode' : 'Barcode non impostato'}"><i class="fas fa-barcode"></i></button>
                            <button data-id="${item.id}" class="action-btn edit-btn ml-2"><i class="fas fa-pencil-alt"></i></button>
                            <button data-id="${item.id}" data-name="${itemName}" class="action-btn delete-btn ml-2"><i class="fas fa-trash"></i></button>
                        </td>
                    `;
                    inventoryTableBody.appendChild(row);
                });
            }
        }
        
        function updateDashboard(items) {
            const totalValue = items.reduce((sum, item) => sum + ((item.quantity || 0) * (item.prezzo_acquisto || 0)), 0);
            const totalItems = items.reduce((sum, item) => sum + parseInt(item.quantity || 0), 0);
            const lowStockItems = items.filter(item => (item.quantity || 0) < LOW_STOCK_THRESHOLD).length;
            document.getElementById('totalValue').textContent = `€ ${totalValue.toFixed(2)}`;
            document.getElementById('totalItems').textContent = totalItems;
            document.getElementById('lowStockItems').textContent = lowStockItems;
        }

        function renderPagination(totalItems) {
             const totalPages = Math.ceil(totalItems / ITEMS_PER_PAGE); paginationControls.innerHTML = ''; if (totalPages <= 1) return;
             const prevBtn = document.createElement('button'); prevBtn.innerHTML = '&laquo;'; prevBtn.className = 'page-btn'; prevBtn.disabled = currentPage === 1; prevBtn.onclick = () => { currentPage--; processAndRenderData(); }; paginationControls.appendChild(prevBtn);
             for (let i = 1; i <= totalPages; i++) { const pageBtn = document.createElement('button'); pageBtn.textContent = i; pageBtn.className = 'page-btn'; if (i === currentPage) pageBtn.classList.add('active'); pageBtn.onclick = () => { currentPage = i; processAndRenderData(); }; paginationControls.appendChild(pageBtn); }
             const nextBtn = document.createElement('button'); nextBtn.innerHTML = '&raquo;'; nextBtn.className = 'page-btn'; nextBtn.disabled = currentPage === totalPages; nextBtn.onclick = () => { currentPage++; processAndRenderData(); }; paginationControls.appendChild(nextBtn);
        }
        
        function handleSort(e) {
            const header = e.target.closest('.sortable');
            if (!header) return;
            const column = header.dataset.sort;
            if (sortState.column === column) {
                sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
            } else {
                sortState.column = column;
                sortState.direction = 'asc';
            }
            document.querySelectorAll('th.sortable').forEach(th => {
                th.classList.remove('active');
                th.querySelector('.sort-icon').className = 'fas fa-sort sort-icon';
            });
            header.classList.add('active');
            header.querySelector('.sort-icon').className = `fas fa-sort-${sortState.direction === 'asc' ? 'up' : 'down'} sort-icon`;
            currentPage = 1;
            processAndRenderData();
        }

        function renderCharts() {
            const categoryValues = allInventoryItems.reduce((acc, item) => {
                const category = item.categoria || 'Non categorizzato';
                const value = (item.quantity || 0) * (item.prezzo_acquisto || 0);
                acc[category] = (acc[category] || 0) + value;
                return acc;
            }, {});
            const catCtx = document.getElementById('categoryValueChart').getContext('2d');
            if(categoryValueChart) categoryValueChart.destroy();
            categoryValueChart = new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(categoryValues),
                    datasets: [{
                        label: 'Valore Inventario',
                        data: Object.values(categoryValues),
                        backgroundColor: ['#3b82f6', '#10b981', '#f97316', '#ef4444', '#8b5cf6', '#f59e0b', '#64748b'],
                    }]
                }
            });
            const sortedByQuantity = [...allInventoryItems].sort((a, b) => (b.quantity || 0) - (a.quantity || 0)).slice(0, 5);
            const topCtx = document.getElementById('topItemsChart').getContext('2d');
            if(topItemsChart) topItemsChart.destroy();
            topItemsChart = new Chart(topCtx, {
                type: 'bar',
                data: {
                    labels: sortedByQuantity.map(item => item.name || 'Senza nome'),
                    datasets: [{
                        label: 'Quantità in magazzino',
                        data: sortedByQuantity.map(item => item.quantity || 0),
                        backgroundColor: '#22c55e',
                    }]
                },
                options: { indexAxis: 'y' }
            });
        }

        function exportToCSV() {
            const headers = ['ID', 'Nome', 'Quantità', 'Prezzo Vendita', 'Prezzo Acquisto', 'Categoria', 'Descrizione'];
            const rows = filteredAndSortedItems.map(item => [
                item.id || '',
                `"${(item.name || '').replace(/"/g, '""')}"`,
                item.quantity || 0,
                item.prezzo_vendita1 || 0,
                item.prezzo_acquisto || 0,
                item.categoria || '',
                `"${(item.description || '').replace(/"/g, '""')}"`
            ]);
            const csvContent = "data:text/csv;charset=utf-8," 
                + headers.join(',') + '\n' 
                + rows.map(e => e.join(',')).join('\n');
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "inventario.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            showToast('Esportazione CSV completata!', 'success');
        }
        
        function handleSelection(e) {
            const checkbox = e.target;
            const id = checkbox.dataset.id;
            if (checkbox.checked) {
                selectedItems.add(id);
            } else {
                selectedItems.delete(id);
            }
            checkbox.closest('tr').classList.toggle('selected', checkbox.checked);
            updateBulkActionsBar();
        }

        function handleSelectAll(e) {
            const isChecked = e.target.checked;
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = isChecked;
                const id = cb.dataset.id;
                if (isChecked) { selectedItems.add(id); } 
                else { selectedItems.delete(id); }
                cb.closest('tr').classList.toggle('selected', isChecked);
            });
            updateBulkActionsBar();
        }

        function updateBulkActionsBar() {
            const count = selectedItems.size;
            if (count > 0) {
                bulkActionsCount.textContent = `${count} articol${count > 1 ? 'i' : 'o'} selezionat${count > 1 ? 'i' : 'o'}`;
                bulkActionsBar.classList.add('visible');
            } else {
                bulkActionsBar.classList.remove('visible');
            }
            const visibleCheckboxes = document.querySelectorAll('.row-checkbox').length;
            selectAllCheckbox.checked = count > 0 && count === visibleCheckboxes;
            selectAllCheckbox.indeterminate = count > 0 && count < visibleCheckboxes;
        }

        function handleBulkDelete() {
            const onConfirm = async () => {
                const count = selectedItems.size;
                 try {
                    const res = await fetch(API_URL, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ids: Array.from(selectedItems) })
                    });
                    if (!res.ok) throw new Error('Errore server');
                    const result = await res.json();
                    if (!result.success) throw new Error(result.message);
                    
                    showToast(`${count} articoli eliminati con successo!`, 'success');
                    selectedItems.clear();
                } catch (error) {
                    showToast(`Eliminazione fallita: ${error.message}`, 'error');
                } finally {
                    closeConfirmDeleteModal();
                    await fetchData();
                }
            };
            openConfirmationModal(`${selectedItems.size} articoli`, onConfirm);
        }
        
        async function openEditModal(id) {
            try{const e=await fetch(`${API_URL}?id=${id}`);if(!e.ok)throw new Error("Articolo non trovato");const t=await e.json();currentItemId=id,modalTitle.textContent="Modifica Articolo",itemForm.reset(),resetImagePreview(),Object.keys(formFields).forEach(e=>{void 0!==t[e]&&"file"!==formFields[e].type&&(formFields[e].value=t[e])}),t.immagine&&(itemImagePreview.src=UPLOADS_DIR+t.immagine,itemImagePreview.style.display="block",imagePreviewContainer.classList.remove("hidden")),await populateDropdowns(t.categoria,t.sottocategoria,t.sottosottocategoria),showTab("principale"),itemModal.classList.add("visible"),await fetchAndRenderAuditLog(id)}catch(e){showToast("Errore nel caricamento dei dati dell'articolo.","error"),console.error(e)}
        }
        
        async function fetchAndRenderAuditLog(itemId) {
            const logContainer = document.getElementById('log-container');
            const loadingMsg = document.getElementById('log-loading-message');
            logContainer.innerHTML = '';
            loadingMsg.textContent = 'Caricamento storico...';
            loadingMsg.style.display = 'block';
            try {
                // Dati fittizi per la demo
                const logData = [
                    { user: 'admin', action: 'Articolo creato', timestamp: '2025-09-10 10:30:00' },
                    { user: 'mario.rossi', action: 'Quantità modificata da 10 a 8', timestamp: '2025-09-11 15:00:00' }
                ];
                if(logData.length === 0) {
                    loadingMsg.textContent = 'Nessuna modifica registrata.';
                } else {
                    loadingMsg.style.display = 'none';
                    const list = document.createElement('ul');
                    list.className = 'list-disc pl-5 space-y-2';
                    logData.forEach(entry => {
                        const item = document.createElement('li');
                        item.innerHTML = `<span class="font-semibold">${entry.timestamp}</span>: ${entry.action} (utente: <span class="italic">${entry.user}</span>)`;
                        list.appendChild(item);
                    });
                    logContainer.appendChild(list);
                }
            } catch (error) {
                loadingMsg.textContent = 'Impossibile caricare lo storico.';
                console.error('Errore caricamento log:', error);
            }
        }
        
        function openBarcodeModal(itemId, barcodeValue) {
            const item = allInventoryItems.find(i => String(i.id) === String(itemId));
            if (!item) return; // Sicurezza
            document.getElementById('barcodeModalTitle').textContent = `Barcode per: ${item.name || 'Senza nome'}`;
            try {
                JsBarcode("#barcodeCanvas", barcodeValue);
                barcodeModal.classList.add('visible');
            } catch (error) {
                console.error("Errore generazione barcode:", error);
                showToast('Formato barcode non valido.', 'error');
            }
        }

        function closeBarcodeModal() {
            barcodeModal.classList.remove('visible');
        }

        function showLoadingState(){inventoryTableBody.innerHTML="";for(let i=0;i<5;i++){const e=document.createElement("tr");e.className="skeleton-row",e.innerHTML=`<td><div class="skeleton h-4 w-4 ml-4 rounded"></div></td><td><div class="skeleton h-12 w-12 rounded-md"></div></td><td><div class="skeleton h-4 w-12"></div></td><td><div class="skeleton h-4 w-48"></div></td><td><div class="skeleton h-4 w-16"></div></td><td><div class="skeleton h-4 w-24"></div></td><td><div class="skeleton h-4 w-32"></div></td><td class="text-right"><div class="skeleton h-8 w-32 ml-auto"></div></td>`,inventoryTableBody.appendChild(e)}}
        function showEmptyState(e){inventoryTableBody.innerHTML=`<tr><td colspan="8"><div id="empty-state"><i class="fas fa-box-open"></i><p>${e}</p></div></td></tr>`}
        
        function openConfirmationModal(message, onConfirmCallback) {
            document.getElementById('confirmItemName').textContent = message;
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            newConfirmBtn.addEventListener('click', () => onConfirmCallback(), { once: true });
            confirmModal.classList.add('visible');
        }

        async function handleDelete(id) {
            try {
                const res = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
                if (!res.ok) throw new Error("Errore dal server.");
                const result = await res.json();
                if (!result.success) throw new Error(result.message || "Eliminazione fallita.");
                showToast("Articolo eliminato!", "success");
            } catch (e) {
                showToast(`Eliminazione fallita: ${e.message}`, "error");
            } finally {
                closeConfirmDeleteModal();
                await fetchData();
            }
        }
        
        function openAddModal(){currentItemId=null,modalTitle.textContent="Aggiungi Nuovo Articolo",itemForm.reset(),formFields.data_creazione.value=(new Date).toISOString().slice(0,10),resetImagePreview(),populateDropdowns(),showTab("principale"),itemModal.classList.add("visible")}
        function closeItemModal(){itemModal.classList.remove("visible")}
        
        function openConfirmDeleteModal(id, name){
            openConfirmationModal(`"${name}"`, () => handleDelete(id));
        }

        function closeConfirmDeleteModal(){confirmModal.classList.remove("visible")}
        
        async function handleFormSubmit(e){
            e.preventDefault();
            const formData = new FormData(itemForm);
            if(currentItemId) formData.append("_method","PUT");
            try {
                const res = await fetch(API_URL + (currentItemId ? `?id=${currentItemId}` : ""), { method: "POST", body: formData });
                if(!res.ok) throw new Error("Errore di rete.");
                const result = await res.json();
                if(!result.success) throw new Error(result.message || "Errore sconosciuto.");
                showToast(currentItemId ? "Articolo modificato!" : "Articolo aggiunto!", "success");
                closeItemModal();
                await fetchData();
            } catch(e) {
                showToast(`Salvataggio fallito: ${e.message}`, "error");
            }
        }

        function populateCategoryFilter(){categoryFilter.innerHTML='<option value="">Tutte le categorie</option>';const e=[...new Set(fetchedCategories.map(e=>e.categoria).filter(Boolean))];e.forEach(e=>{const t=document.createElement("option");t.value=e,t.textContent=e,categoryFilter.appendChild(t)})}
        
        async function populateDropdowns(selectedCategory = "", selectedSubcategory = "", selectedSubSubcategory = "") {
            const allCategories = [...new Set(fetchedCategories.map(cat => cat.categoria).filter(Boolean))];
            populateSelect(formFields.categoria, allCategories, selectedCategory);
            populateSelect(formFields.sottocategoria, [], "");
            populateSelect(formFields.sottosottocategoria, [], "");
            if (selectedCategory) {
                await handleCategoryChange(selectedSubcategory, selectedSubSubcategory);
            }
        }
        
        async function handleCategoryChange(selectedSub = "", selectedSubSub = ""){const category = formFields.categoria.value; const subcategories = [...new Set(fetchedCategories.filter(c => c.categoria === category).map(c => c.sottocategoria).filter(Boolean))]; populateSelect(formFields.sottocategoria, subcategories, selectedSub); await handleSubcategoryChange(selectedSubSub);}
        async function handleSubcategoryChange(selected = ""){const category = formFields.categoria.value; const subcategory = formFields.sottocategoria.value; const subsubcategories = [...new Set(fetchedCategories.filter(c => c.categoria === category && c.sottocategoria === subcategory).map(c => c.sottosottocategoria).filter(Boolean))]; populateSelect(formFields.sottosottocategoria, subsubcategories, selected);}
        function populateSelect(selectElement, options, selectedValue){const finalSelected = selectedValue || ""; selectElement.innerHTML='<option value="">Seleziona...</option>'; options.forEach(opt => { const optionEl = document.createElement("option"); optionEl.value = opt; optionEl.textContent = opt; selectElement.appendChild(optionEl); }); selectElement.value = finalSelected; }
        function resetImagePreview(){formFields.image.value="",itemImagePreview.src="#",itemImagePreview.style.display="none",imagePreviewContainer.classList.add("hidden")}
        function showTab(tabName){document.querySelectorAll(".tab-content").forEach(el=>el.classList.remove("active"));document.querySelectorAll(".tab-button").forEach(el=>el.classList.remove("active"));document.getElementById(`tab-${tabName}`).classList.add("active");document.querySelector(`.tab-button[onclick="showTab('${tabName}')"]`).classList.add("active")}

        // --- EVENT LISTENERS ---
        document.addEventListener('DOMContentLoaded', fetchData);
        searchInput.addEventListener('input', () => { currentPage = 1; processAndRenderData(); });
        categoryFilter.addEventListener('change', () => { currentPage = 1; processAndRenderData(); });
        tableHeader.addEventListener('click', handleSort);
        document.getElementById('exportCsvBtn').addEventListener('click', exportToCSV);
        document.getElementById('toggleReportBtn').addEventListener('click', (e) => {
            const reportSection = document.getElementById('report-section');
            const isVisible = reportSection.style.display === 'block';
            reportSection.style.display = isVisible ? 'none' : 'block';
            e.target.innerHTML = isVisible ? '<i class="fas fa-chart-pie mr-2"></i>Mostra Report' : '<i class="fas fa-eye-slash mr-2"></i>Nascondi Report';
        });
        document.getElementById('addArticleBtn').addEventListener('click', openAddModal);
        itemForm.addEventListener('submit', handleFormSubmit);
        formFields.image.addEventListener('change', (e) => { const file = e.target.files[0]; if(file) { itemImagePreview.src = URL.createObjectURL(file); itemImagePreview.style.display = 'block'; imagePreviewContainer.classList.remove('hidden'); } });
        document.getElementById('clearImageBtn').addEventListener('click', resetImagePreview);
        formFields.categoria.addEventListener('change', () => handleCategoryChange());
        formFields.sottocategoria.addEventListener('change', () => handleSubcategoryChange());
        document.getElementById('cancelDeleteBtn').addEventListener('click', closeConfirmDeleteModal);
        inventoryTableBody.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.edit-btn');
            const deleteBtn = e.target.closest('.delete-btn');
            const barcodeBtn = e.target.closest('.barcode-btn');
            const checkbox = e.target.closest('.row-checkbox');
            
            if (e.target.tagName === 'INPUT' && checkbox) {
                handleSelection(e);
                return;
            }

            if (editBtn) openEditModal(editBtn.dataset.id);
            if (deleteBtn) openConfirmDeleteModal(deleteBtn.dataset.id, deleteBtn.dataset.name);
            if (barcodeBtn && !barcodeBtn.disabled) openBarcodeModal(barcodeBtn.dataset.id, barcodeBtn.dataset.barcode);
        });
        selectAllCheckbox.addEventListener('change', handleSelectAll);
        document.getElementById('bulk-delete-btn').addEventListener('click', handleBulkDelete);
        document.getElementById('printBarcodeBtn').addEventListener('click', () => {
             const canvas = document.getElementById('barcodeCanvas');
             const dataUrl = canvas.toDataURL();
             let windowContent = '<!DOCTYPE html><html><head><title>Stampa Barcode</title></head><body>';
             windowContent += '<img src="' + dataUrl + '" style="max-width: 100%;">';
             windowContent += '</body></html>';
             const printWin = window.open('', '', 'width=400,height=400');
             printWin.document.open();
             printWin.document.write(windowContent);
             printWin.document.close();
             printWin.focus();
             printWin.print();
             printWin.close();
        });

    </script>
</body>
</html>

