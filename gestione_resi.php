<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
include 'db.php';
include 'audit_helper.php';

// ===== AJAX Actions =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // Cerca vendita per # o nome cliente
    if ($action === 'search_vendita') {
        $q = trim($_POST['query'] ?? '');
        if (strlen($q) < 1) { echo json_encode([]); exit; }
        $like = "%$q%";
        $stmt = $conn->prepare("SELECT v.id, v.nome_cliente, v.totale, v.data_vendita,
            (SELECT GROUP_CONCAT(CONCAT(d.nome,' x',d.quantita) SEPARATOR ', ') FROM vendite_dettagli d WHERE d.id_vendita=v.id) as prodotti
            FROM vendite v WHERE v.id LIKE ? OR v.nome_cliente LIKE ? ORDER BY v.data_vendita DESC LIMIT 10");
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode($results);
        exit;
    }

    // Dettagli vendita  
    if ($action === 'get_vendita_dettagli') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT d.*, p.quantita as stock_attuale FROM vendite_dettagli d LEFT JOIN prodotti p ON d.id_prodotto = p.id WHERE d.id_vendita = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT * FROM vendite WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $vendita = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Check resi già fatti su questa vendita
        $stmt = $conn->prepare("SELECT rd.id_prodotto, SUM(rd.quantita) as gia_reso FROM resi r JOIN resi_dettagli rd ON rd.id_reso = r.id WHERE r.id_vendita = ? AND r.stato != 'Rifiutato' GROUP BY rd.id_prodotto");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resi_fatti = [];
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $resi_fatti[$row['id_prodotto']] = intval($row['gia_reso']); }
        $stmt->close();
        
        echo json_encode(['vendita' => $vendita, 'items' => $items, 'resi_fatti' => $resi_fatti]);
        exit;
    }

    // Salva reso
    if ($action === 'salva_reso') {
        $id_vendita = intval($_POST['id_vendita'] ?? 0);
        $tipo = $_POST['tipo'] ?? 'Parziale';
        $motivo = trim($_POST['motivo'] ?? '');
        $metodo_rimborso = trim($_POST['metodo_rimborso'] ?? 'Contanti');
        $note = trim($_POST['note'] ?? '');
        $prodotti = json_decode($_POST['prodotti'] ?? '[]', true);
        
        if (!$id_vendita) { echo json_encode(['error' => true, 'message' => 'Vendita non selezionata.']); exit; }
        if (empty($prodotti)) { echo json_encode(['error' => true, 'message' => 'Seleziona almeno un prodotto da restituire.']); exit; }
        
        // Prendi info vendita
        $v = $conn->query("SELECT * FROM vendite WHERE id = $id_vendita")->fetch_assoc();
        if (!$v) { echo json_encode(['error' => true, 'message' => 'Vendita non trovata.']); exit; }
        
        // Calcola importo
        $importo_totale = 0;
        foreach ($prodotti as $p) {
            $importo_totale += floatval($p['prezzo']) * intval($p['qty']);
        }
        
        $creato_da = $_SESSION['user_id'];
        
        $conn->begin_transaction();
        try {
            // Inserisci reso
            $stmt = $conn->prepare("INSERT INTO resi (id_vendita, id_cliente, nome_cliente, tipo, motivo, importo_reso, metodo_rimborso, note, stato, creato_da) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stato = 'Approvato';
            $stmt->bind_param("iisssdsssi", $id_vendita, $v['id_cliente'], $v['nome_cliente'], $tipo, $motivo, $importo_totale, $metodo_rimborso, $note, $stato, $creato_da);
            $stmt->execute();
            $id_reso = $conn->insert_id;
            $stmt->close();
            
            // Inserisci dettagli e aggiorna magazzino
            foreach ($prodotti as $p) {
                $id_prodotto = intval($p['id_prodotto']);
                $nome_prodotto = $p['nome'];
                $qty = intval($p['qty']);
                $prezzo = floatval($p['prezzo']);
                $rientro = intval($p['rientro'] ?? 1);
                
                $stmt = $conn->prepare("INSERT INTO resi_dettagli (id_reso, id_prodotto, nome_prodotto, quantita, prezzo_unitario, rientro_magazzino) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param("iisidi", $id_reso, $id_prodotto, $nome_prodotto, $qty, $prezzo, $rientro);
                $stmt->execute();
                $stmt->close();
                
                // Rientro a magazzino
                if ($rientro && $id_prodotto > 0) {
                    $conn->query("UPDATE prodotti SET quantita = quantita + $qty WHERE id = $id_prodotto");
                }
            }
            
            $conn->commit();
            registra_log($conn, 'Creazione reso', 'resi', $id_reso, "Vendita #$id_vendita - €" . number_format($importo_totale, 2, ',', '.'));
            echo json_encode(['success' => true, 'message' => "Reso #$id_reso registrato con successo. Importo: €" . number_format($importo_totale, 2, ',', '.')]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['error' => true, 'message' => 'Errore: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // Cambia stato reso
    if ($action === 'cambia_stato') {
        $id = intval($_POST['id'] ?? 0);
        $stato = $_POST['stato'] ?? '';
        $stati_validi = ['In Attesa', 'Approvato', 'Rimborsato', 'Rifiutato'];
        if (!in_array($stato, $stati_validi)) { echo json_encode(['error' => true, 'message' => 'Stato non valido.']); exit; }
        
        $stmt = $conn->prepare("UPDATE resi SET stato = ? WHERE id = ?");
        $stmt->bind_param("si", $stato, $id);
        $stmt->execute();
        $stmt->close();
        registra_log($conn, 'Modifica stato reso', 'resi', $id, "Nuovo stato: $stato");
        echo json_encode(['success' => true, 'message' => "Stato aggiornato a: $stato"]);
        exit;
    }
}

// Carica lista resi
$filter_stato = trim($_GET['stato'] ?? '');
$sql = "SELECT r.*, (SELECT GROUP_CONCAT(CONCAT(rd.nome_prodotto,' x',rd.quantita) SEPARATOR ', ') FROM resi_dettagli rd WHERE rd.id_reso=r.id) as prodotti_reso FROM resi r";
if ($filter_stato) { $sql .= " WHERE r.stato = '" . $conn->real_escape_string($filter_stato) . "'"; }
$sql .= " ORDER BY r.data_creazione DESC";
$resi = [];
$result = $conn->query($sql);
if ($result) { while ($r = $result->fetch_assoc()) { $resi[] = $r; } }

// Stats
$stats = $conn->query("SELECT 
    COUNT(*) as totali,
    SUM(CASE WHEN stato='In Attesa' THEN 1 ELSE 0 END) as in_attesa,
    SUM(CASE WHEN stato='Approvato' THEN 1 ELSE 0 END) as approvati,
    SUM(CASE WHEN stato='Rimborsato' THEN 1 ELSE 0 END) as rimborsati,
    SUM(CASE WHEN stato='Rifiutato' THEN 1 ELSE 0 END) as rifiutati,
    COALESCE(SUM(CASE WHEN stato != 'Rifiutato' THEN importo_reso ELSE 0 END),0) as totale_resi
    FROM resi")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Gestione Resi | TS Service</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/header-styles.css?v=1">
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<style>
:root{--primary:#22c55e;--primary-dark:#16a34a;--primary-light:#dcfce7;--primary-glow:rgba(34,197,94,0.4);--secondary:#3b82f6;--secondary-light:#dbeafe;--purple:#8b5cf6;--purple-light:#ede9fe;--orange:#f59e0b;--orange-light:#fef3c7;--danger:#ef4444;--danger-light:#fee2e2;--teal:#14b8a6;--teal-light:#ccfbf1;--bg-page:#f8fafc;--bg-card:#ffffff;--text-primary:#0f172a;--text-secondary:#64748b;--text-muted:#94a3b8;--border-color:#e2e8f0;--border-light:#f1f5f9;--shadow:0 4px 6px -1px rgb(0 0 0/0.1),0 2px 4px -2px rgb(0 0 0/0.1);--shadow-md:0 10px 15px -3px rgb(0 0 0/0.1);--shadow-lg:0 20px 25px -5px rgb(0 0 0/0.1);--radius-md:0.75rem;--radius-lg:1rem;--radius-xl:1.5rem;--transition:200ms cubic-bezier(0.4,0,0.2,1)}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,var(--bg-page) 0%,#e2e8f0 100%);min-height:100vh;color:var(--text-primary);padding-top:80px;line-height:1.6;overflow-x:hidden}
.particles-container{position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:0;overflow:hidden}
.particle{position:absolute;border-radius:50%;opacity:0.12;animation:floatP 22s infinite ease-in-out}
.particle:nth-child(1){width:320px;height:320px;background:var(--primary);top:-120px;left:-80px}
.particle:nth-child(2){width:220px;height:220px;background:var(--secondary);top:50%;right:-60px;animation-delay:-6s}
.particle:nth-child(3){width:180px;height:180px;background:var(--purple);bottom:8%;left:25%;animation-delay:-12s}
.particle:nth-child(4){width:120px;height:120px;background:var(--orange);top:25%;left:55%;animation-delay:-17s}
@keyframes floatP{0%,100%{transform:translate(0,0) scale(1)}25%{transform:translate(30px,-30px) scale(1.05)}50%{transform:translate(-20px,20px) scale(0.95)}75%{transform:translate(15px,15px) scale(1.02)}}

.main-container{max-width:1400px;margin:0 auto;padding:24px 32px 60px;position:relative;z-index:1}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:16px;animation:fadeUp 0.5s ease-out}
.page-header-left{display:flex;align-items:center;gap:16px}
.page-icon{width:56px;height:56px;border-radius:var(--radius-lg);background:linear-gradient(135deg,var(--danger),#dc2626);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;box-shadow:0 8px 20px rgba(239,68,68,0.3)}
.page-header h1{font-size:1.8rem;font-weight:800}
.page-header .subtitle{color:var(--text-secondary);font-size:0.95rem;margin-top:2px}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

/* Stats */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;animation:fadeUp 0.5s ease-out 0.1s both}
.stat-mini{background:var(--bg-card);border-radius:var(--radius-lg);padding:18px 20px;border:1px solid var(--border-color);box-shadow:var(--shadow);display:flex;align-items:center;gap:14px}
.stat-mini-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.stat-mini .stat-label{font-size:0.78rem;color:var(--text-muted);font-weight:500}
.stat-mini .stat-val{font-size:1.2rem;font-weight:700}

/* Buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 24px;border-radius:var(--radius-md);font-weight:700;font-size:0.9rem;font-family:'Inter',sans-serif;cursor:pointer;border:none;transition:all var(--transition)}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;box-shadow:0 4px 12px var(--primary-glow)}
.btn-primary:hover{transform:translateY(-2px)}
.btn-danger-filled{background:linear-gradient(135deg,var(--danger),#dc2626);color:#fff;box-shadow:0 4px 12px rgba(239,68,68,0.3)}
.btn-danger-filled:hover{transform:translateY(-2px)}
.btn-sm{padding:6px 14px;font-size:0.78rem;font-weight:600}
.btn-outline{background:transparent;color:var(--text-secondary);border:1.5px solid var(--border-color)}
.btn-outline:hover{background:var(--border-light)}
.btn-blue{background:linear-gradient(135deg,var(--secondary),#2563eb);color:#fff}

/* Filter tabs */
.filter-tabs{display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;animation:fadeUp 0.5s ease-out 0.15s both}
.filter-tab{padding:8px 18px;border-radius:30px;font-size:0.82rem;font-weight:600;text-decoration:none;border:1.5px solid var(--border-color);color:var(--text-secondary);background:#fff;transition:all var(--transition)}
.filter-tab:hover{border-color:var(--primary);color:var(--primary-dark)}
.filter-tab.active{background:var(--primary);color:#fff;border-color:var(--primary)}

/* Card + Table */
.card{background:var(--bg-card);border-radius:var(--radius-xl);box-shadow:var(--shadow);border:1px solid var(--border-color);overflow:hidden}
.table-wrapper{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th{padding:12px 16px;text-align:left;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);background:var(--border-light);border-bottom:2px solid var(--border-color)}
td{padding:14px 16px;font-size:0.88rem;border-bottom:1px solid var(--border-light)}
tbody tr:hover{background:rgba(239,68,68,0.03)}
tbody tr:last-child td{border-bottom:none}
.status-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:700}
.status-badge::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor}
.status-badge.attesa{background:var(--orange-light);color:#b45309}
.status-badge.approvato{background:var(--secondary-light);color:var(--secondary)}
.status-badge.rimborsato{background:var(--primary-light);color:var(--primary-dark)}
.status-badge.rifiutato{background:var(--danger-light);color:var(--danger)}
.empty-state{text-align:center;padding:60px;color:var(--text-muted)}
.empty-state i{font-size:3rem;display:block;margin-bottom:16px;color:var(--border-color)}

/* Modal */
#resoModal{display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center}
#resoModal.show{display:flex}
.modal-backdrop{position:absolute;inset:0;background:rgba(15,23,42,0.5);backdrop-filter:blur(4px)}
.modal-content{position:relative;background:#fff;border-radius:var(--radius-xl);box-shadow:var(--shadow-lg);width:95%;max-width:800px;max-height:92vh;overflow-y:auto;animation:modalIn 0.3s ease-out}
@keyframes modalIn{from{opacity:0;transform:scale(0.9) translateY(20px)}to{opacity:1;transform:scale(1) translateY(0)}}
.modal-header{padding:24px 28px;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1}
.modal-header h2{font-size:1.2rem;font-weight:800;display:flex;align-items:center;gap:10px}
.modal-close{width:36px;height:36px;border-radius:10px;border:none;background:var(--border-light);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1rem;color:var(--text-muted);transition:all var(--transition)}
.modal-close:hover{background:var(--danger-light);color:var(--danger)}
.modal-body{padding:28px}
.modal-footer{padding:20px 28px;border-top:1px solid var(--border-light);display:flex;justify-content:space-between;align-items:center;position:sticky;bottom:0;background:#fff}

/* Search vendita */
.vendita-search{position:relative;margin-bottom:24px}
.vendita-search input{width:100%;padding:14px 18px 14px 48px;border:2px solid var(--border-color);border-radius:var(--radius-lg);font-size:0.95rem;font-family:'Inter',sans-serif;outline:none;transition:all var(--transition)}
.vendita-search input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(34,197,94,0.12)}
.vendita-search .search-icon{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:1rem}
.vendita-results{position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid var(--border-color);border-radius:var(--radius-md);box-shadow:var(--shadow-lg);max-height:250px;overflow-y:auto;z-index:10;display:none}
.vendita-results.show{display:block}
.vendita-item{padding:12px 16px;cursor:pointer;border-bottom:1px solid var(--border-light);transition:background 0.15s}
.vendita-item:hover{background:var(--primary-light)}
.vendita-item:last-child{border-bottom:none}
.vendita-item .vi-id{font-weight:700;color:var(--primary-dark);font-size:0.85rem}
.vendita-item .vi-info{font-size:0.82rem;color:var(--text-secondary)}
.vendita-selected{background:var(--primary-light);border:2px solid var(--primary);border-radius:var(--radius-lg);padding:16px;margin-bottom:20px;display:none}
.vendita-selected.show{display:block}

/* Product items */
.product-items{margin-top:20px}
.product-item{display:grid;grid-template-columns:1fr 80px 100px 80px 50px;align-items:center;gap:12px;padding:14px 16px;border:1px solid var(--border-light);border-radius:var(--radius-md);margin-bottom:8px;transition:all var(--transition)}
.product-item.selected{border-color:var(--danger);background:rgba(239,68,68,0.03)}
.product-item .pi-name{font-weight:600;font-size:0.9rem}
.product-item .pi-sub{font-size:0.78rem;color:var(--text-muted)}
.product-item input[type="number"]{width:100%;padding:8px;border:1.5px solid var(--border-color);border-radius:8px;text-align:center;font-family:'Inter',sans-serif;font-size:0.85rem;outline:none}
.product-item input:focus{border-color:var(--primary)}
.product-item .pi-price{font-weight:700;color:var(--danger);text-align:right}
.pi-check{width:22px;height:22px;accent-color:var(--danger);cursor:pointer}

/* Form fields in modal */
.reso-fields{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px}
.reso-fields .full-w{grid-column:1/-1}
.reso-fields label{display:block;font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted);margin-bottom:4px}
.reso-fields select,.reso-fields textarea{width:100%;padding:10px 14px;border:1.5px solid var(--border-color);border-radius:var(--radius-md);font-size:0.88rem;font-family:'Inter',sans-serif;outline:none;transition:all var(--transition)}
.reso-fields select:focus,.reso-fields textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(34,197,94,0.12)}
.reso-fields textarea{resize:vertical;min-height:60px}

.total-bar{font-size:1.3rem;font-weight:800;color:var(--danger)}

/* Toast */
.toast-container{position:fixed;top:100px;right:24px;z-index:10001;display:flex;flex-direction:column;gap:12px;pointer-events:none}
.toast{min-width:320px;max-width:450px;pointer-events:auto;padding:14px 20px;border-radius:var(--radius-md);color:#fff;font-weight:600;font-size:0.9rem;display:flex;align-items:center;gap:10px;box-shadow:var(--shadow-lg);animation:slideIn 0.3s ease-out,fadeOut 0.4s 3s ease-in forwards}
.toast.success{background:linear-gradient(135deg,#10b981,#059669)}.toast.error{background:linear-gradient(135deg,#ef4444,#dc2626)}
@keyframes slideIn{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}
@keyframes fadeOut{to{opacity:0;transform:translateX(40px)}}

@media(max-width:768px){.main-container{padding:16px}.stats-row{grid-template-columns:1fr 1fr}.product-item{grid-template-columns:1fr;gap:8px}.reso-fields{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="particles-container"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
<div class="toast-container" id="toastContainer"></div>

<main class="main-container">

<div class="page-header">
    <div class="page-header-left">
        <div class="page-icon"><i class="fas fa-rotate-left"></i></div>
        <div>
            <h1>Gestione Resi e Rimborsi</h1>
            <p class="subtitle">Registra resi, gestisci rimborsi e rientri a magazzino</p>
        </div>
    </div>
    <button class="btn btn-danger-filled" onclick="openResoModal()"><i class="fas fa-plus"></i> Nuovo Reso</button>
</div>

<div class="stats-row">
    <div class="stat-mini">
        <div class="stat-mini-icon" style="background:var(--danger-light);color:var(--danger);"><i class="fas fa-rotate-left"></i></div>
        <div><div class="stat-label">Totale Resi</div><div class="stat-val" style="color:var(--danger);"><?php echo $stats['totali']; ?></div></div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-icon" style="background:var(--orange-light);color:#b45309;"><i class="fas fa-clock"></i></div>
        <div><div class="stat-label">In Attesa</div><div class="stat-val" style="color:#b45309;"><?php echo $stats['in_attesa']; ?></div></div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-icon" style="background:var(--primary-light);color:var(--primary-dark);"><i class="fas fa-check-circle"></i></div>
        <div><div class="stat-label">Rimborsati</div><div class="stat-val" style="color:var(--primary-dark);"><?php echo $stats['rimborsati']; ?></div></div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-icon" style="background:var(--secondary-light);color:var(--secondary);"><i class="fas fa-euro-sign"></i></div>
        <div><div class="stat-label">Valore Resi</div><div class="stat-val" style="color:var(--secondary);">&euro;<?php echo number_format($stats['totale_resi'], 2, ',', '.'); ?></div></div>
    </div>
</div>

<div class="filter-tabs">
    <a href="gestione_resi.php" class="filter-tab <?php echo !$filter_stato ? 'active' : ''; ?>">Tutti (<?php echo $stats['totali']; ?>)</a>
    <a href="?stato=In+Attesa" class="filter-tab <?php echo $filter_stato==='In Attesa' ? 'active' : ''; ?>">In Attesa (<?php echo $stats['in_attesa']; ?>)</a>
    <a href="?stato=Approvato" class="filter-tab <?php echo $filter_stato==='Approvato' ? 'active' : ''; ?>">Approvati (<?php echo $stats['approvati']; ?>)</a>
    <a href="?stato=Rimborsato" class="filter-tab <?php echo $filter_stato==='Rimborsato' ? 'active' : ''; ?>">Rimborsati (<?php echo $stats['rimborsati']; ?>)</a>
    <a href="?stato=Rifiutato" class="filter-tab <?php echo $filter_stato==='Rifiutato' ? 'active' : ''; ?>">Rifiutati (<?php echo $stats['rifiutati']; ?>)</a>
</div>

<div class="card">
    <div class="table-wrapper">
        <?php if (empty($resi)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3 style="font-weight:700;color:var(--text-secondary);margin-bottom:4px;">Nessun reso registrato</h3>
            <p>I resi appariranno qui una volta registrati.</p>
        </div>
        <?php else: ?>
        <table>
            <thead><tr>
                <th>#</th>
                <th>Vendita</th>
                <th>Cliente</th>
                <th>Prodotti</th>
                <th>Tipo</th>
                <th>Importo</th>
                <th>Metodo</th>
                <th>Stato</th>
                <th>Data</th>
                <th>Azioni</th>
            </tr></thead>
            <tbody>
            <?php foreach ($resi as $r): ?>
            <?php
                $sc = 'attesa';
                if ($r['stato']==='Approvato') $sc = 'approvato';
                elseif ($r['stato']==='Rimborsato') $sc = 'rimborsato';
                elseif ($r['stato']==='Rifiutato') $sc = 'rifiutato';
            ?>
            <tr>
                <td style="font-weight:700;color:var(--text-muted);">#<?php echo $r['id']; ?></td>
                <td><a href="dettagli_vendita.php?id=<?php echo $r['id_vendita']; ?>" style="font-weight:700;color:var(--secondary);text-decoration:none;">#<?php echo $r['id_vendita']; ?></a></td>
                <td style="font-weight:600;"><?php echo htmlspecialchars($r['nome_cliente'] ?: 'Anonimo'); ?></td>
                <td style="font-size:0.82rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($r['prodotti_reso'] ?? ''); ?>">
                    <?php echo htmlspecialchars($r['prodotti_reso'] ?: '—'); ?>
                </td>
                <td><span style="font-size:0.78rem;font-weight:600;padding:3px 10px;border-radius:20px;background:<?php echo $r['tipo']==='Totale' ? 'var(--danger-light)' : 'var(--orange-light)'; ?>;color:<?php echo $r['tipo']==='Totale' ? 'var(--danger)' : '#b45309'; ?>;"><?php echo $r['tipo']; ?></span></td>
                <td style="font-weight:700;color:var(--danger);">&euro;<?php echo number_format($r['importo_reso'], 2, ',', '.'); ?></td>
                <td style="font-size:0.82rem;"><?php echo htmlspecialchars($r['metodo_rimborso']); ?></td>
                <td><span class="status-badge <?php echo $sc; ?>"><?php echo $r['stato']; ?></span></td>
                <td style="font-size:0.82rem;color:var(--text-muted);white-space:nowrap;"><?php echo date('d/m/Y H:i', strtotime($r['data_creazione'])); ?></td>
                <td>
                    <select class="btn btn-sm btn-outline" onchange="cambiaStato(<?php echo $r['id']; ?>, this.value)" style="font-size:0.78rem;padding:4px 8px;cursor:pointer;">
                        <option value="">Cambia...</option>
                        <option value="In Attesa" <?php echo $r['stato']==='In Attesa'?'selected':''; ?>>In Attesa</option>
                        <option value="Approvato" <?php echo $r['stato']==='Approvato'?'selected':''; ?>>Approvato</option>
                        <option value="Rimborsato" <?php echo $r['stato']==='Rimborsato'?'selected':''; ?>>Rimborsato</option>
                        <option value="Rifiutato" <?php echo $r['stato']==='Rifiutato'?'selected':''; ?>>Rifiutato</option>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</main>

<!-- Modal Nuovo Reso -->
<div id="resoModal">
    <div class="modal-backdrop" onclick="closeResoModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-rotate-left" style="color:var(--danger);"></i> Registra Nuovo Reso</h2>
            <button class="modal-close" onclick="closeResoModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="vendita-search">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="venditaSearch" placeholder="Cerca vendita per # o nome cliente...">
                <div class="vendita-results" id="venditaResults"></div>
            </div>

            <div class="vendita-selected" id="venditaSelected">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <span style="font-weight:800;color:var(--primary-dark);font-size:0.9rem;" id="vsId"></span>
                        <span style="font-weight:600;margin-left:12px;" id="vsCliente"></span>
                    </div>
                    <span style="font-weight:700;font-size:1.1rem;" id="vsTotale"></span>
                </div>
                <div style="font-size:0.82rem;color:var(--text-muted);margin-top:4px;" id="vsData"></div>
            </div>

            <div id="productList" style="display:none;">
                <h3 style="font-size:0.95rem;font-weight:700;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-box" style="color:var(--danger);"></i> Seleziona prodotti da restituire
                </h3>
                <div class="product-items" id="productItems"></div>

                <div class="reso-fields">
                    <div>
                        <label>Motivo del reso</label>
                        <select id="resoMotivo">
                            <option value="Difettoso">Prodotto difettoso</option>
                            <option value="Errore acquisto">Errore di acquisto</option>
                            <option value="Non conforme">Non conforme alla descrizione</option>
                            <option value="Garanzia">Garanzia</option>
                            <option value="Altro">Altro</option>
                        </select>
                    </div>
                    <div>
                        <label>Metodo rimborso</label>
                        <select id="resoMetodo">
                            <option value="Contanti">Contanti</option>
                            <option value="Carta">Carta di credito/debito</option>
                            <option value="Bonifico">Bonifico bancario</option>
                            <option value="Buono">Buono acquisto</option>
                        </select>
                    </div>
                    <div class="full-w">
                        <label>Note (opzionale)</label>
                        <textarea id="resoNote" placeholder="Note aggiuntive..."></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer" id="modalFooter" style="display:none;">
            <div class="total-bar">Totale reso: &euro;<span id="totalReso">0,00</span></div>
            <div style="display:flex;gap:12px;">
                <button class="btn btn-outline" onclick="closeResoModal()">Annulla</button>
                <button class="btn btn-danger-filled" id="saveResoBtn" onclick="salvaReso()"><i class="fas fa-check"></i> Conferma Reso</button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedVendita = null;
let venditeItems = [];
let resiFatti = {};

function showToast(msg, type) {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.innerHTML = '<i class="fas '+(type==='success'?'fa-circle-check':'fa-circle-xmark')+'"></i> '+msg;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

function openResoModal() {
    document.getElementById('resoModal').classList.add('show');
    setTimeout(() => document.getElementById('venditaSearch').focus(), 200);
}
function closeResoModal() {
    document.getElementById('resoModal').classList.remove('show');
    selectedVendita = null;
    document.getElementById('venditaSearch').value = '';
    document.getElementById('venditaResults').classList.remove('show');
    document.getElementById('venditaSelected').classList.remove('show');
    document.getElementById('productList').style.display = 'none';
    document.getElementById('modalFooter').style.display = 'none';
}

// Search vendita
let searchTimer;
document.getElementById('venditaSearch').addEventListener('input', function() {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if (q.length < 1) { document.getElementById('venditaResults').classList.remove('show'); return; }
    searchTimer = setTimeout(() => {
        fetch('gestione_resi.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=search_vendita&query=' + encodeURIComponent(q)
        })
        .then(r => r.json())
        .then(data => {
            const box = document.getElementById('venditaResults');
            if (!data.length) { box.innerHTML = '<div style="padding:16px;color:var(--text-muted);text-align:center;">Nessuna vendita trovata</div>'; box.classList.add('show'); return; }
            box.innerHTML = data.map(v => 
                '<div class="vendita-item" onclick="selectVendita('+v.id+')">' +
                '<div class="vi-id">#' + v.id + ' — ' + (v.nome_cliente||'Anonimo') + '</div>' +
                '<div class="vi-info">€' + parseFloat(v.totale).toFixed(2).replace('.',',') + ' — ' + new Date(v.data_vendita).toLocaleDateString('it-IT') + '</div>' +
                (v.prodotti ? '<div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">'+v.prodotti+'</div>' : '') +
                '</div>'
            ).join('');
            box.classList.add('show');
        });
    }, 300);
});

function selectVendita(id) {
    document.getElementById('venditaResults').classList.remove('show');
    document.getElementById('venditaSearch').value = '';
    
    fetch('gestione_resi.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_vendita_dettagli&id=' + id
    })
    .then(r => r.json())
    .then(data => {
        selectedVendita = data.vendita;
        venditeItems = data.items;
        resiFatti = data.resi_fatti || {};

        document.getElementById('vsId').textContent = '#' + data.vendita.id;
        document.getElementById('vsCliente').textContent = data.vendita.nome_cliente || 'Cliente anonimo';
        document.getElementById('vsTotale').textContent = '€' + parseFloat(data.vendita.totale).toFixed(2).replace('.',',');
        document.getElementById('vsData').textContent = new Date(data.vendita.data_vendita).toLocaleString('it-IT');
        document.getElementById('venditaSelected').classList.add('show');

        renderProducts(data.items);
        document.getElementById('productList').style.display = 'block';
        document.getElementById('modalFooter').style.display = 'flex';
    });
}

function renderProducts(items) {
    const box = document.getElementById('productItems');
    box.innerHTML = items.map((item, i) => {
        const giaReso = resiFatti[item.id_prodotto] || 0;
        const maxQty = item.quantita - giaReso;
        if (maxQty <= 0) {
            return '<div class="product-item" style="opacity:0.5;"><div><div class="pi-name">'+htmlEsc(item.nome)+'</div><div class="pi-sub" style="color:var(--danger);">Già restituito completamente</div></div><div></div><div></div><div></div><div></div></div>';
        }
        const prezzo = item.prezzo_scontato || item.prezzo_unitario;
        return '<div class="product-item" id="pi-'+i+'">' +
            '<div><div class="pi-name">'+htmlEsc(item.nome)+'</div><div class="pi-sub">Venduti: '+item.quantita+(giaReso?' | Già resi: '+giaReso:'')+'</div></div>' +
            '<div style="text-align:center;font-size:0.82rem;color:var(--text-muted);">Max: '+maxQty+'</div>' +
            '<input type="number" min="0" max="'+maxQty+'" value="0" data-idx="'+i+'" data-max="'+maxQty+'" data-price="'+prezzo+'" data-prodotto-id="'+(item.id_prodotto||0)+'" data-nome="'+htmlEsc(item.nome)+'" onchange="updateTotal()" oninput="updateTotal()">' +
            '<div class="pi-price" id="price-'+i+'">€0,00</div>' +
            '<label title="Rientro a magazzino"><input type="checkbox" class="pi-check" data-idx="'+i+'" checked> <i class="fas fa-warehouse" style="font-size:0.75rem;color:var(--text-muted);"></i></label>' +
            '</div>';
    }).join('');
}

function updateTotal() {
    let total = 0;
    document.querySelectorAll('#productItems input[type="number"]').forEach(inp => {
        const qty = Math.min(parseInt(inp.value)||0, parseInt(inp.dataset.max));
        inp.value = qty;
        const price = parseFloat(inp.dataset.price) * qty;
        total += price;
        const idx = inp.dataset.idx;
        document.getElementById('price-'+idx).textContent = '€' + price.toFixed(2).replace('.',',');
        const row = document.getElementById('pi-'+idx);
        if (row) row.classList.toggle('selected', qty > 0);
    });
    document.getElementById('totalReso').textContent = total.toFixed(2).replace('.',',');
}

function salvaReso() {
    if (!selectedVendita) { showToast('Seleziona una vendita.', 'error'); return; }
    
    const prodotti = [];
    document.querySelectorAll('#productItems input[type="number"]').forEach(inp => {
        const qty = parseInt(inp.value) || 0;
        if (qty > 0) {
            const idx = inp.dataset.idx;
            const checkbox = document.querySelector('.pi-check[data-idx="'+idx+'"]');
            prodotti.push({
                id_prodotto: inp.dataset.prodottoId,
                nome: inp.dataset.nome,
                qty: qty,
                prezzo: parseFloat(inp.dataset.price),
                rientro: checkbox ? (checkbox.checked ? 1 : 0) : 1
            });
        }
    });
    
    if (!prodotti.length) { showToast('Seleziona almeno un prodotto.', 'error'); return; }
    
    const btn = document.getElementById('saveResoBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvataggio...';
    
    const fd = new FormData();
    fd.append('action', 'salva_reso');
    fd.append('id_vendita', selectedVendita.id);
    fd.append('tipo', prodotti.length === venditeItems.length ? 'Totale' : 'Parziale');
    fd.append('motivo', document.getElementById('resoMotivo').value);
    fd.append('metodo_rimborso', document.getElementById('resoMetodo').value);
    fd.append('note', document.getElementById('resoNote').value);
    fd.append('prodotti', JSON.stringify(prodotti));
    
    fetch('gestione_resi.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.error) { showToast(data.message, 'error'); }
        else { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1000); }
    })
    .catch(() => showToast('Errore di comunicazione.', 'error'))
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Conferma Reso'; });
}

function cambiaStato(id, stato) {
    if (!stato) return;
    fetch('gestione_resi.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=cambia_stato&id='+id+'&stato='+encodeURIComponent(stato)
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) showToast(data.message, 'error');
        else { showToast(data.message, 'success'); setTimeout(() => location.reload(), 800); }
    });
}

function htmlEsc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// Close results on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.vendita-search')) {
        document.getElementById('venditaResults').classList.remove('show');
    }
});
</script>
</body>
</html>
