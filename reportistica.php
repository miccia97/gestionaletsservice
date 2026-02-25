<?php
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!file_exists('db.php')) {
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: Il file db.php non è stato trovato!</div>";
    exit;
}
require_once 'db.php';

if (isset($db_connection_error) && $db_connection_error !== null) {
    $_SESSION['message'] = 'Errore critico: La connessione al database non è stata stabilita correttamente! ' . htmlspecialchars($db_connection_error, ENT_QUOTES, 'UTF-8');
    $_SESSION['isError'] = true;
}

$message = '';
if (isset($_SESSION['message'])) {
    $sessionMessage = $_SESSION['message'];
    $sessionIsError = $_SESSION['isError'] ?? false;
    $message = "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('" . addslashes($sessionMessage) . "', " . ($sessionIsError ? 'true' : 'false') . "); });</script>";
    unset($_SESSION['message']);
    unset($_SESSION['isError']);
}

// --- LOGICA PER RECUPERARE I DATI PER I REPORT ---
$current_stock_products = [];
$low_stock_products = [];
$above_stock_products = [];
$purchase_summary_products = [];
$total_stock_value = 0;
$recent_movements = [];
$most_purchased_products = [];
$purchase_trends = [];
$inventory_turnover = 0;
$total_purchased_quantity_all_time = 0;
$total_current_stock_quantity = 0;

// --- GESTIONE FILTRI GLOBALI ---
$start_date_global = $_GET['start_date_global'] ?? date('Y-m-01'); // Default primo giorno del mese corrente
$end_date_global = $_GET['end_date_global'] ?? date('Y-m-d');   // Default oggi
$min_stock_threshold = isset($_GET['min_stock_threshold']) && is_numeric($_GET['min_stock_threshold']) ? (int)$_GET['min_stock_threshold'] : 10;
$max_stock_threshold = isset($_GET['max_stock_threshold']) && is_numeric($_GET['max_stock_threshold']) ? (int)$_GET['max_stock_threshold'] : 100;

try {
    // Report 1: Giacenze Attuali (Non soggetto a filtri data)
    $stmt_current_stock = $conn->prepare("SELECT p.nome, c.nome AS categoria_nome, p.quantita, p.barcode FROM prodotti p LEFT JOIN categorie c ON p.categoria = c.nome ORDER BY p.nome ASC");
    $stmt_current_stock->execute();
    $result_current_stock = $stmt_current_stock->get_result();
    $current_stock_products = $result_current_stock->fetch_all(MYSQLI_ASSOC);
    $stmt_current_stock->close();

    // Report 2: Prodotti Sotto Scorta
    $stmt_low_stock = $conn->prepare("SELECT p.nome, c.nome AS categoria_nome, p.quantita, p.barcode FROM prodotti p LEFT JOIN categorie c ON p.categoria = c.nome WHERE p.quantita <= ? ORDER BY p.quantita ASC");
    $stmt_low_stock->bind_param('i', $min_stock_threshold);
    $stmt_low_stock->execute();
    $result_low_stock = $stmt_low_stock->get_result();
    $low_stock_products = $result_low_stock->fetch_all(MYSQLI_ASSOC);
    $stmt_low_stock->close();
    
    // Report 3: Prodotti Sopra Scorta
    $stmt_above_stock = $conn->prepare("SELECT p.nome, c.nome AS categoria_nome, p.quantita, p.barcode FROM prodotti p LEFT JOIN categorie c ON p.categoria = c.nome WHERE p.quantita >= ? ORDER BY p.quantita DESC");
    $stmt_above_stock->bind_param('i', $max_stock_threshold);
    $stmt_above_stock->execute();
    $result_above_stock = $stmt_above_stock->get_result();
    $above_stock_products = $result_above_stock->fetch_all(MYSQLI_ASSOC);
    $stmt_above_stock->close();

    // Report 4: Riepilogo Acquisti Prodotti con filtro data globale
    $sql_purchase_summary = "SELECT p.nome, c.nome AS categoria_nome, SUM(df.quantita) AS quantita_acquistata, SUM(df.quantita * df.prezzo_unitario_netto) AS valore_acquisto_netto 
                             FROM dettagli_fattura df 
                             JOIN prodotti p ON df.prodotto_id = p.id 
                             LEFT JOIN categorie c ON p.categoria = c.nome
                             JOIN fatture f ON df.fattura_id = f.id
                             WHERE f.data_fattura BETWEEN ? AND ?
                             GROUP BY p.id, p.nome, c.nome ORDER BY quantita_acquistata DESC";
    $stmt_purchase_summary = $conn->prepare($sql_purchase_summary);
    $stmt_purchase_summary->bind_param('ss', $start_date_global, $end_date_global);
    $stmt_purchase_summary->execute();
    $result_purchase_summary = $stmt_purchase_summary->get_result();
    $purchase_summary_products = $result_purchase_summary->fetch_all(MYSQLI_ASSOC);
    $stmt_purchase_summary->close();

    // Report 5: Valore Totale Magazzino
    $stmt_total_value = $conn->prepare("SELECT SUM(p.quantita * p.prezzo_acquisto) AS total_stock_value FROM prodotti p");
    $stmt_total_value->execute();
    $total_stock_value = $stmt_total_value->get_result()->fetch_assoc()['total_stock_value'] ?? 0;
    $stmt_total_value->close();

    // Report 6: Storico Movimenti Recenti (ultimi 10 acquisti)
    $stmt_recent_movements = $conn->prepare("SELECT df.data_creazione, p.nome, df.quantita, df.prezzo_unitario_netto, 'Acquisto' as tipo_movimento, f.numero_fattura FROM dettagli_fattura df JOIN prodotti p ON df.prodotto_id = p.id JOIN fatture f ON df.fattura_id = f.id ORDER BY df.data_creazione DESC LIMIT 10");
    $stmt_recent_movements->execute();
    $recent_movements = $stmt_recent_movements->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_recent_movements->close();

    // Report 7: Prodotti Più Acquistati (Top 5 nel periodo globale)
    $stmt_most_purchased = $conn->prepare("SELECT p.nome, c.nome AS categoria_nome, SUM(df.quantita) AS total_quantity_purchased FROM dettagli_fattura df JOIN prodotti p ON df.prodotto_id = p.id LEFT JOIN categorie c ON p.categoria = c.nome JOIN fatture f ON df.fattura_id = f.id WHERE f.data_fattura BETWEEN ? AND ? GROUP BY p.id, p.nome, c.nome ORDER BY total_quantity_purchased DESC LIMIT 5");
    $stmt_most_purchased->bind_param('ss', $start_date_global, $end_date_global);
    $stmt_most_purchased->execute();
    $most_purchased_products = $stmt_most_purchased->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_most_purchased->close();

    // Report 8: Trend Acquisti per Mese (nel periodo globale)
    $sql_purchase_trends = "SELECT DATE_FORMAT(f.data_fattura, '%Y-%m') AS period, SUM(df.quantita) AS total_quantity, SUM(df.quantita * df.prezzo_unitario_netto) AS total_value_net FROM dettagli_fattura df JOIN fatture f ON df.fattura_id = f.id WHERE f.data_fattura BETWEEN ? AND ? GROUP BY period ORDER BY period ASC";
    $stmt_purchase_trends = $conn->prepare($sql_purchase_trends);
    $stmt_purchase_trends->bind_param('ss', $start_date_global, $end_date_global);
    $stmt_purchase_trends->execute();
    $purchase_trends = $stmt_purchase_trends->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_purchase_trends->close();

    // Report 9: Rotazione Magazzino
    $stmt_turnover_data = $conn->prepare("SELECT (SELECT SUM(quantita) FROM dettagli_fattura) as total_purchased, (SELECT SUM(quantita) FROM prodotti) as current_stock");
    $stmt_turnover_data->execute();
    $turnover_data = $stmt_turnover_data->get_result()->fetch_assoc();
    $total_purchased_quantity_all_time = $turnover_data['total_purchased'] ?? 0;
    $total_current_stock_quantity = $turnover_data['current_stock'] ?? 0;
    $inventory_turnover = ($total_current_stock_quantity > 0) ? ($total_purchased_quantity_all_time / $total_current_stock_quantity) : 0;
    $stmt_turnover_data->close();

} catch (mysqli_sql_exception $e) {
    error_log("Errore nel caricamento dei report (SQL): " . $e->getMessage());
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Errore database: " . addslashes($e->getMessage()) . "', true); });</script>";
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analisi Magazzino</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .top-bar { /* Stili header esistenti */ }
        .card { background-color: #ffffff; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1); transition: all 0.2s ease-in-out; }
        .card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1); }
        .summary-card { padding: 1rem; text-align: center; cursor: pointer; }
        .summary-card p { font-size: 2.25rem; font-weight: 700; }
        .summary-card h3 { font-size: 0.9rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
        .table-header th { padding: 0.75rem 1rem; text-align: left; background-color: #f9fafb; font-weight: 600; color: #374151; }
        .table-body td { padding: 0.75rem 1rem; border-top: 1px solid #e5e7eb; }
        .table-body tr:nth-child(even) { background-color: #f9fafb; }
        /* Stili per messaggi e pulsante scroll (invariati) */
    </style>
</head>
<body class="pt-24">
    <?php include 'header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php echo $message; ?>

        <!-- NUOVO: Barra Filtri Globale -->
        <div class="card p-4 mb-8">
            <form id="globalFilterForm" action="reportistica.php" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <div>
                    <label for="start_date_global" class="block text-sm font-medium text-gray-700">Data Inizio</label>
                    <input type="date" id="start_date_global" name="start_date_global" value="<?php echo htmlspecialchars($start_date_global, ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="end_date_global" class="block text-sm font-medium text-gray-700">Data Fine</label>
                    <input type="date" id="end_date_global" name="end_date_global" value="<?php echo htmlspecialchars($end_date_global, ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <button type="button" onclick="setQuickDate('last7days')" class="w-full text-sm py-2 px-3 bg-gray-200 hover:bg-gray-300 rounded-md">7 Giorni</button>
                    <button type="button" onclick="setQuickDate('last30days')" class="w-full text-sm py-2 px-3 bg-gray-200 hover:bg-gray-300 rounded-md">30 Giorni</button>
                    <button type="button" onclick="setQuickDate('this_month')" class="w-full text-sm py-2 px-3 bg-gray-200 hover:bg-gray-300 rounded-md">Mese</button>
                </div>
                <div>
                    <label for="min_stock_threshold" class="block text-sm font-medium text-gray-700">Soglia Min Scorta</label>
                    <input type="number" name="min_stock_threshold" value="<?php echo htmlspecialchars($min_stock_threshold, ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700">Applica</button>
                    <a href="reportistica.php" class="w-full text-center py-2 px-4 bg-gray-600 text-white font-semibold rounded-md shadow-sm hover:bg-gray-700">Reset</a>
                </div>
            </form>
        </div>

        <!-- Riepilogo Generale (Card Interattive) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            <div class="summary-card card bg-white" onclick="scrollToSection('totalValueCard')">
                <h3>Valore Totale</h3>
                <p class="text-blue-600"><?php echo number_format($total_stock_value, 2, ',', '.') . ' €'; ?></p>
            </div>
            <div class="summary-card card bg-white" onclick="scrollToSection('lowStockCard')">
                <h3>Sotto Scorta</h3>
                <p class="text-red-500"><?php echo count($low_stock_products); ?></p>
            </div>
            <div class="summary-card card bg-white" onclick="scrollToSection('aboveStockCard')">
                <h3>Sopra Scorta</h3>
                <p class="text-green-500"><?php echo count($above_stock_products); ?></p>
            </div>
            <div class="summary-card card bg-white" onclick="scrollToSection('turnoverCard')">
                <h3>Rotazione</h3>
                <p class="text-indigo-600"><?php echo number_format($inventory_turnover, 2, ',', '.'); ?>x</p>
            </div>
        </div>

        <!-- Dashboard a Griglia -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Colonna Sinistra (Grafici) -->
            <div class="lg:col-span-2 space-y-8">
                <div class="card p-6" id="purchaseTrendCard">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold">Trend Acquisti</h2>
                        <button onclick="exportTableToCSV('purchaseTrendTable', 'trend-acquisti.csv')" class="text-gray-500 hover:text-blue-600"><i class="fas fa-download"></i></button>
                    </div>
                    <canvas id="purchaseTrendChart"></canvas>
                </div>
                <div class="card p-6" id="topProductsCard">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold">Prodotti Più Acquistati</h2>
                        <button onclick="exportTableToCSV('mostPurchasedTable', 'prodotti-piu-acquistati.csv')" class="text-gray-500 hover:text-blue-600"><i class="fas fa-download"></i></button>
                    </div>
                    <canvas id="topProductsChart"></canvas>
                </div>
            </div>

            <!-- Colonna Destra (Dati Veloci) -->
            <div class="space-y-8">
                <div class="card p-4" id="turnoverCard">
                    <h2 class="text-lg font-semibold mb-2">Rotazione Magazzino</h2>
                    <p class="text-sm text-gray-500">Indica quante volte il magazzino si è "rinnovato" in base al rapporto tra acquistato totale e giacenza attuale.</p>
                </div>
                <div class="card p-4" id="recentMovementsCard">
                    <div class="flex justify-between items-center mb-2">
                        <h2 class="text-lg font-semibold">Movimenti Recenti</h2>
                        <button onclick="exportTableToCSV('recentMovementsTable', 'movimenti-recenti.csv')" class="text-gray-500 hover:text-blue-600"><i class="fas fa-download"></i></button>
                    </div>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($recent_movements as $mov): ?>
                        <li class="py-2">
                            <p class="font-medium text-sm"><?php echo htmlspecialchars($mov['nome']); ?></p>
                            <p class="text-xs text-gray-500">Q.tà: <?php echo $mov['quantita']; ?> - <?php echo date("d/m/Y", strtotime($mov['data_creazione'])); ?></p>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Sezioni Tabellari Dettagliate -->
        <div class="space-y-8 mt-8">
            <div class="card p-6" id="lowStockCard">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Dettaglio Prodotti Sotto Scorta (&le; <?php echo $min_stock_threshold; ?>)</h2>
                    <button onclick="exportTableToCSV('lowStockTable', 'prodotti-sotto-scorta.csv')" class="text-gray-500 hover:text-blue-600"><i class="fas fa-download"></i></button>
                </div>
                <div class="overflow-x-auto max-h-96">
                    <table class="min-w-full" id="lowStockTable">
                        <thead class="sticky top-0 bg-white"><tr class="table-header"><th>Prodotto</th><th>Quantità</th><th>Categoria</th></tr></thead>
                        <tbody class="table-body">
                        <?php foreach($low_stock_products as $p): ?>
                        <tr><td><?php echo htmlspecialchars($p['nome']); ?></td><td><?php echo htmlspecialchars($p['quantita']); ?></td><td><?php echo htmlspecialchars($p['categoria_nome'] ?? 'N/A'); ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card p-6" id="aboveStockCard">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Dettaglio Prodotti Sopra Scorta (&ge; <?php echo $max_stock_threshold; ?>)</h2>
                    <button onclick="exportTableToCSV('aboveStockTable', 'prodotti-sopra-scorta.csv')" class="text-gray-500 hover:text-blue-600"><i class="fas fa-download"></i></button>
                </div>
                <div class="overflow-x-auto max-h-96">
                    <table class="min-w-full" id="aboveStockTable">
                        <thead class="sticky top-0 bg-white"><tr class="table-header"><th>Prodotto</th><th>Quantità</th><th>Categoria</th></tr></thead>
                        <tbody class="table-body">
                        <?php foreach($above_stock_products as $p): ?>
                        <tr><td><?php echo htmlspecialchars($p['nome']); ?></td><td><?php echo htmlspecialchars($p['quantita']); ?></td><td><?php echo htmlspecialchars($p['categoria_nome'] ?? 'N/A'); ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
    
    <!-- Tabelledati nascoste per l'esportazione e i grafici -->
    <div class="hidden">
        <table id="purchaseTrendTable">
            <thead><tr><th>Periodo</th><th>Quantità</th><th>Valore</th></tr></thead>
            <tbody>
            <?php foreach($purchase_trends as $d): ?>
            <tr><td><?php echo $d['period']; ?></td><td><?php echo $d['total_quantity']; ?></td><td><?php echo $d['total_value_net']; ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <table id="mostPurchasedTable">
            <thead><tr><th>Prodotto</th><th>Categoria</th><th>Quantità</th></tr></thead>
            <tbody>
            <?php foreach($most_purchased_products as $p): ?>
            <tr><td><?php echo htmlspecialchars($p['nome']); ?></td><td><?php echo htmlspecialchars($p['categoria_nome'] ?? 'N/A'); ?></td><td><?php echo $p['total_quantity_purchased']; ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <table id="recentMovementsTable">
            <thead><tr><th>Data</th><th>Prodotto</th><th>Quantità</th><th>Tipo</th><th>Fattura</th></tr></thead>
            <tbody>
            <?php foreach($recent_movements as $m): ?>
            <tr><td><?php echo $m['data_creazione']; ?></td><td><?php echo htmlspecialchars($m['nome']); ?></td><td><?php echo $m['quantita']; ?></td><td><?php echo $m['tipo_movimento']; ?></td><td><?php echo htmlspecialchars($m['numero_fattura']); ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dati dai PHP
        const purchaseTrendData = <?php echo json_encode($purchase_trends); ?>;
        const topProductsData = <?php echo json_encode($most_purchased_products); ?>;

        // Renderizza Grafico Trend Acquisti
        const trendCtx = document.getElementById('purchaseTrendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: purchaseTrendData.map(d => d.period),
                datasets: [{
                    label: 'Valore Acquistato (€)',
                    data: purchaseTrendData.map(d => d.total_value_net),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            }
        });

        // Renderizza Grafico Prodotti più Acquistati
        const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
        new Chart(topProductsCtx, {
            type: 'bar',
            data: {
                labels: topProductsData.map(p => p.nome),
                datasets: [{
                    label: 'Quantità Acquistata',
                    data: topProductsData.map(p => p.total_quantity_purchased),
                    backgroundColor: '#10b981',
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y'
            }
        });
    });

    function scrollToSection(cardId) {
        document.getElementById(cardId).scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    function setQuickDate(period) {
        const endDate = new Date();
        let startDate = new Date();
        
        if(period === 'last7days') startDate.setDate(endDate.getDate() - 7);
        if(period === 'last30days') startDate.setDate(endDate.getDate() - 30);
        if(period === 'this_month') startDate.setDate(1);

        document.getElementById('start_date_global').value = startDate.toISOString().split('T')[0];
        document.getElementById('end_date_global').value = endDate.toISOString().split('T')[0];
        
        document.getElementById('globalFilterForm').submit();
    }

    function exportTableToCSV(tableId, filename) {
        let csv = [];
        const rows = document.querySelectorAll(`#${tableId} tr`);
        
        for (const row of rows) {
            let cols = [];
            const cells = row.querySelectorAll("th, td");
            
            for (const cell of cells) {
                cols.push(`"${cell.innerText.replace(/"/g, '""')}"`); // Gestisce le virgolette
            }
            csv.push(cols.join(','));
        }

        const csvFile = new Blob([csv.join('\n')], {type: "text/csv"});
        const downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }

    // Altre funzioni JS esistenti (showMessage, etc.)
    </script>
</body>
</html>

