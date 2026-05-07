<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
include 'db.php';

$oggi = date('Y-m-d');
$ieri = date('Y-m-d', strtotime('-1 day'));
$inizio_mese = date('Y-m-01');
$inizio_settimana = date('Y-m-d', strtotime('monday this week'));
$nome_utente = $_SESSION['nome'] ?? $_SESSION['nome_utente'] ?? 'Utente';

// === VENDITE OGGI ===
$q = $conn->query("SELECT COUNT(*) as num, COALESCE(SUM(totale),0) as tot FROM vendite WHERE DATE(data_vendita) = '$oggi'");
$vendite_oggi = $q->fetch_assoc();

// === VENDITE IERI (per confronto) ===
$q = $conn->query("SELECT COUNT(*) as num, COALESCE(SUM(totale),0) as tot FROM vendite WHERE DATE(data_vendita) = '$ieri'");
$vendite_ieri = $q->fetch_assoc();

// === VENDITE MESE ===
$q = $conn->query("SELECT COUNT(*) as num, COALESCE(SUM(totale),0) as tot FROM vendite WHERE DATE(data_vendita) >= '$inizio_mese'");
$vendite_mese = $q->fetch_assoc();

// === RIPARAZIONI APERTE ===
$q = $conn->query("SELECT 
    COUNT(*) as totali,
    SUM(CASE WHEN stato = 'In Attesa' THEN 1 ELSE 0 END) as in_attesa,
    SUM(CASE WHEN stato = 'In Lavorazione' THEN 1 ELSE 0 END) as in_lavorazione,
    SUM(CASE WHEN stato = 'Completata' THEN 1 ELSE 0 END) as completate
    FROM riparazioni WHERE stato NOT IN ('Consegnata','Annullata')");
$riparazioni = $q->fetch_assoc();

// === PRENOTAZIONI IN ATTESA ===
$q = $conn->query("SELECT COUNT(*) as num FROM prenotazioni_prodotti WHERE status = 'Pending'");
$prenotazioni_pending = $q->fetch_assoc()['num'];

// === BUONI REGALO ATTIVI ===
$q = $conn->query("SELECT COUNT(*) as num FROM buoni_regalo WHERE stato = 'Attivo'");
$buoni_attivi = $q->fetch_assoc()['num'];

// === BUONI IN SCADENZA (prossimi 30 giorni) ===
$q = $conn->query("SELECT COUNT(*) as num FROM buoni_regalo WHERE stato = 'Attivo' AND data_scadenza BETWEEN '$oggi' AND DATE_ADD('$oggi', INTERVAL 30 DAY)");
$buoni_scadenza = $q->fetch_assoc()['num'];

// === PRODOTTI SOTTO SCORTA (<=5) ===
$q = $conn->query("SELECT COUNT(*) as num FROM prodotti WHERE quantita <= 5 AND quantita > 0");
$sotto_scorta = $q->fetch_assoc()['num'];

// === PRODOTTI ESAURITI ===
$q = $conn->query("SELECT COUNT(*) as num FROM prodotti WHERE quantita <= 0");
$esauriti = $q->fetch_assoc()['num'];

// === TOTALE CLIENTI ===
$q = $conn->query("SELECT COUNT(*) as num FROM clienti_nuovo");
$totale_clienti = $q->fetch_assoc()['num'];

// === VALORE MAGAZZINO ===
$q = $conn->query("SELECT COALESCE(SUM(quantita * prezzo_acquisto),0) as valore FROM prodotti WHERE quantita > 0");
$valore_magazzino = $q->fetch_assoc()['valore'];

// === VENDITE ULTIMI 7 GIORNI (per grafico) ===
$vendite_7gg = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $label = date('D d', strtotime("-$i days"));
    $q = $conn->query("SELECT COALESCE(SUM(totale),0) as tot FROM vendite WHERE DATE(data_vendita) = '$d'");
    $vendite_7gg[] = ['label' => $label, 'value' => floatval($q->fetch_assoc()['tot'])];
}

// === ULTIME 8 VENDITE ===
$ultime_vendite = [];
$q = $conn->query("SELECT v.id, v.nome_cliente, v.totale, v.data_vendita FROM vendite v ORDER BY v.data_vendita DESC LIMIT 8");
if ($q) { while ($r = $q->fetch_assoc()) { $ultime_vendite[] = $r; } }

// === ULTIME 5 RIPARAZIONI ===
$ultime_riparazioni = [];
$q = $conn->query("SELECT r.id, r.modello, r.stato, r.data_creazione, CONCAT(c.nome,' ',c.cognome) as cliente 
    FROM riparazioni r LEFT JOIN clienti_nuovo c ON r.cliente_id = c.id 
    ORDER BY r.data_creazione DESC LIMIT 5");
if ($q) { while ($r = $q->fetch_assoc()) { $ultime_riparazioni[] = $r; } }

// === TOP 5 PRODOTTI PIU VENDUTI (mese corrente) ===
$top_prodotti = [];
$q = $conn->query("SELECT d.nome, SUM(d.quantita) as qty 
    FROM vendite_dettagli d JOIN vendite v ON d.id_vendita = v.id 
    WHERE DATE(v.data_vendita) >= '$inizio_mese'
    GROUP BY d.nome ORDER BY qty DESC LIMIT 5");
if ($q) { while ($r = $q->fetch_assoc()) { $top_prodotti[] = $r; } }

// === PERMUTE APERTE ===
$q = $conn->query("SELECT COUNT(*) as num FROM permute_nuovo WHERE status IN ('In Trattativa','Accettata')");
$permute_aperte = $q ? $q->fetch_assoc()['num'] : 0;

// === MARGINE OGGI (venduto - costo acquisto) ===
$q = $conn->query("SELECT COALESCE(SUM(d.quantita * d.prezzo_scontato),0) as ricavo, 
    COALESCE(SUM(d.quantita * p.prezzo_acquisto),0) as costo
    FROM vendite_dettagli d 
    JOIN vendite v ON d.id_vendita = v.id 
    LEFT JOIN prodotti p ON d.id_prodotto = p.id 
    WHERE DATE(v.data_vendita) = '$oggi'");
$margine_row = $q->fetch_assoc();
$margine_oggi = $margine_row['ricavo'] - $margine_row['costo'];

// Calcolo variazione percentuale vendite vs ieri
$var_vendite = 0;
if ($vendite_ieri['tot'] > 0) {
    $var_vendite = round((($vendite_oggi['tot'] - $vendite_ieri['tot']) / $vendite_ieri['tot']) * 100, 1);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard | TS Service</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/header-styles.css?v=2">
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
    --primary: #22c55e;
    --primary-dark: #16a34a;
    --primary-light: #dcfce7;
    --primary-glow: rgba(34,197,94,0.4);
    --secondary: #3b82f6;
    --secondary-light: #dbeafe;
    --purple: #8b5cf6;
    --purple-light: #ede9fe;
    --orange: #f59e0b;
    --orange-light: #fef3c7;
    --danger: #ef4444;
    --danger-light: #fee2e2;
    --teal: #14b8a6;
    --teal-light: #ccfbf1;
    --bg-page: #f8fafc;
    --bg-card: #ffffff;
    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;
    --border-color: #e2e8f0;
    --border-light: #f1f5f9;
    --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-md: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    --radius-md: 0.75rem;
    --radius-lg: 1rem;
    --radius-xl: 1.5rem;
    --transition: 200ms cubic-bezier(0.4, 0, 0.2, 1);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', sans-serif; background: linear-gradient(135deg, var(--bg-page) 0%, #e2e8f0 100%);
    min-height: 100vh; color: var(--text-primary); padding-top: 80px; line-height: 1.6; overflow-x: hidden;
}

/* PARTICLES */
.particles-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; overflow: hidden; }
.particle { position: absolute; border-radius: 50%; opacity: 0.12; animation: floatP 22s infinite ease-in-out; }
.particle:nth-child(1) { width: 320px; height: 320px; background: var(--primary); top: -120px; left: -80px; }
.particle:nth-child(2) { width: 220px; height: 220px; background: var(--secondary); top: 50%; right: -60px; animation-delay: -6s; }
.particle:nth-child(3) { width: 180px; height: 180px; background: var(--purple); bottom: 8%; left: 25%; animation-delay: -12s; }
.particle:nth-child(4) { width: 120px; height: 120px; background: var(--orange); top: 25%; left: 55%; animation-delay: -17s; }
@keyframes floatP { 0%,100%{transform:translate(0,0) scale(1)} 25%{transform:translate(30px,-30px) scale(1.05)} 50%{transform:translate(-20px,20px) scale(0.95)} 75%{transform:translate(15px,15px) scale(1.02)} }

/* LAYOUT */
.main-container { max-width: 1500px; margin: 0 auto; padding: 24px 32px 60px; position: relative; z-index: 1; }

/* WELCOME */
.welcome-section { margin-bottom: 32px; animation: fadeUp 0.6s ease-out; }
.welcome-section h1 { font-size: 2.5rem; font-weight: 800; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin: 0 0 4px; }
.welcome-section p { color: var(--text-secondary); font-size: 1.05rem; margin: 0; }
.welcome-section .date-badge { display: inline-flex; align-items: center; gap: 8px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 30px; padding: 6px 16px; font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); margin-top: 12px; box-shadow: var(--shadow); }
.welcome-section .date-badge i { color: var(--primary); }
@keyframes fadeUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }

/* STAT CARDS */
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
.stat-card {
    background: var(--bg-card); border-radius: var(--radius-xl); padding: 24px;
    border: 1px solid var(--border-color); display: flex; align-items: center; gap: 18px;
    cursor: default; transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
    box-shadow: var(--shadow); position: relative; overflow: hidden;
    opacity: 0; transform: translateY(30px); animation: cardIn 0.5s ease-out forwards;
}
.stat-card:nth-child(1){animation-delay:0.1s} .stat-card:nth-child(2){animation-delay:0.15s}
.stat-card:nth-child(3){animation-delay:0.2s} .stat-card:nth-child(4){animation-delay:0.25s}
@keyframes cardIn { to { opacity:1; transform:translateY(0); } }
.stat-card::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; background:linear-gradient(90deg, var(--card-accent), var(--card-accent-end, var(--card-accent))); opacity:0; transition:opacity 0.3s; }
.stat-card:hover::before { opacity:1; }
.stat-card:hover { transform:translateY(-6px); box-shadow: var(--shadow-lg), 0 0 0 1px var(--card-accent); }
.stat-card.green { --card-accent: var(--primary); --card-accent-end: #4ade80; }
.stat-card.blue { --card-accent: var(--secondary); --card-accent-end: #60a5fa; }
.stat-card.orange { --card-accent: var(--orange); --card-accent-end: #fbbf24; }
.stat-card.red { --card-accent: var(--danger); --card-accent-end: #f87171; }
.stat-card.purple { --card-accent: var(--purple); --card-accent-end: #a78bfa; }
.stat-card.teal { --card-accent: var(--teal); --card-accent-end: #2dd4bf; }
.stat-icon {
    width: 56px; height: 56px; border-radius: var(--radius-lg); display: flex;
    align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.3rem;
    transition: transform 0.3s;
}
.stat-card:hover .stat-icon { transform: scale(1.1) rotate(-5deg); }
.stat-card.green .stat-icon { background: var(--primary-light); color: var(--primary-dark); }
.stat-card.blue .stat-icon { background: var(--secondary-light); color: var(--secondary); }
.stat-card.orange .stat-icon { background: var(--orange-light); color: #d97706; }
.stat-card.red .stat-icon { background: var(--danger-light); color: var(--danger); }
.stat-card.purple .stat-icon { background: var(--purple-light); color: var(--purple); }
.stat-card.teal .stat-icon { background: var(--teal-light); color: #0d9488; }
.stat-label { font-size: 0.82rem; font-weight: 500; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.stat-value { font-size: 1.75rem; font-weight: 700; line-height: 1.1; }
.stat-card.green .stat-value { color: var(--primary-dark); }
.stat-card.blue .stat-value { color: var(--secondary); }
.stat-card.orange .stat-value { color: #d97706; }
.stat-card.red .stat-value { color: var(--danger); }
.stat-card.purple .stat-value { color: var(--purple); }
.stat-card.teal .stat-value { color: #0d9488; }
.stat-trend { display: inline-flex; align-items: center; gap: 4px; font-size: 0.78rem; font-weight: 700; margin-top: 4px; padding: 2px 8px; border-radius: 20px; }
.stat-trend.up { background: var(--primary-light); color: var(--primary-dark); }
.stat-trend.down { background: var(--danger-light); color: var(--danger); }
.stat-trend.neutral { background: var(--border-light); color: var(--text-muted); }

/* SECONDARY STATS ROW */
.stats-row-secondary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
.mini-stat {
    background: var(--bg-card); border-radius: var(--radius-lg); padding: 18px 20px;
    border: 1px solid var(--border-color); box-shadow: var(--shadow);
    display: flex; align-items: center; gap: 14px;
    opacity: 0; transform: translateY(20px); animation: cardIn 0.5s ease-out forwards;
}
.mini-stat:nth-child(1){animation-delay:0.3s} .mini-stat:nth-child(2){animation-delay:0.35s}
.mini-stat:nth-child(3){animation-delay:0.4s} .mini-stat:nth-child(4){animation-delay:0.45s}
.mini-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1rem; }
.mini-stat .mini-label { font-size: 0.78rem; color: var(--text-muted); font-weight: 500; }
.mini-stat .mini-value { font-size: 1.2rem; font-weight: 700; }

/* GRID */
.dashboard-grid { display: grid; grid-template-columns: 5fr 3fr; gap: 24px; margin-bottom: 28px; animation: fadeUp 0.6s ease-out 0.3s both; }
.full-width-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; animation: fadeUp 0.6s ease-out 0.4s both; }

/* CARD */
.card {
    background: var(--bg-card); border-radius: var(--radius-xl); box-shadow: var(--shadow);
    border: 1px solid var(--border-color); overflow: hidden; transition: all var(--transition);
}
.card:hover { box-shadow: var(--shadow-md); }
.card-header {
    padding: 20px 24px; border-bottom: 1px solid var(--border-light);
    display: flex; align-items: center; justify-content: space-between;
}
.card-title { font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
.card-title-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; }
.card-title-icon.green { background: var(--primary-light); color: var(--primary-dark); }
.card-title-icon.blue { background: var(--secondary-light); color: var(--secondary); }
.card-title-icon.purple { background: var(--purple-light); color: var(--purple); }
.card-title-icon.orange { background: var(--orange-light); color: #d97706; }
.card-title-icon.red { background: var(--danger-light); color: var(--danger); }
.card-title-icon.teal { background: var(--teal-light); color: #0d9488; }
.card-body { padding: 24px; }
.card-body-compact { padding: 16px 24px; }
.card-link { font-size: 0.82rem; font-weight: 600; color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 4px; transition: all 0.2s; }
.card-link:hover { color: var(--primary-dark); gap: 8px; }

/* TABLE */
.dash-table { width: 100%; border-collapse: collapse; }
.dash-table th { padding: 12px 16px; text-align: left; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); background: var(--border-light); border-bottom: 2px solid var(--border-color); }
.dash-table td { padding: 14px 16px; font-size: 0.88rem; border-bottom: 1px solid var(--border-light); }
.dash-table tbody tr { transition: background 0.15s; }
.dash-table tbody tr:hover { background: rgba(34,197,94,0.04); }
.dash-table tbody tr:last-child td { border-bottom: none; }

/* BADGES */
.status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
.status-badge::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
.status-badge.attesa { background: var(--orange-light); color: #b45309; }
.status-badge.lavorazione { background: var(--secondary-light); color: var(--secondary); }
.status-badge.completata { background: var(--primary-light); color: var(--primary-dark); }

/* QUICK ACTIONS */
.quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.quick-action-btn {
    display: flex; align-items: center; gap: 12px; padding: 16px 18px;
    background: var(--bg-page); border: 2px solid var(--border-color); border-radius: var(--radius-lg);
    text-decoration: none; color: var(--text-primary); font-weight: 600; font-size: 0.9rem;
    transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
}
.quick-action-btn:hover { border-color: var(--primary); background: var(--primary-light); color: var(--primary-dark); transform: translateY(-3px); box-shadow: 0 6px 20px var(--primary-glow); }
.quick-action-btn i { font-size: 1.1rem; width: 24px; text-align: center; }

/* ALERT ITEMS */
.alert-item {
    display: flex; align-items: center; gap: 14px; padding: 14px 0;
    border-bottom: 1px solid var(--border-light);
}
.alert-item:last-child { border-bottom: none; }
.alert-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.alert-dot.red { background: var(--danger); box-shadow: 0 0 8px rgba(239,68,68,0.4); }
.alert-dot.orange { background: var(--orange); box-shadow: 0 0 8px rgba(245,158,11,0.4); }
.alert-dot.blue { background: var(--secondary); box-shadow: 0 0 8px rgba(59,130,246,0.4); }
.alert-info { flex: 1; }
.alert-title { font-weight: 600; font-size: 0.88rem; }
.alert-meta { font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }
.alert-count { font-size: 1.2rem; font-weight: 800; padding: 4px 14px; border-radius: 20px; }
.alert-count.red { background: var(--danger-light); color: var(--danger); }
.alert-count.orange { background: var(--orange-light); color: #b45309; }
.alert-count.blue { background: var(--secondary-light); color: var(--secondary); }

/* TOP PRODUCTS */
.top-product-item { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--border-light); }
.top-product-item:last-child { border-bottom: none; }
.top-rank { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.82rem; flex-shrink: 0; }
.top-rank.gold { background: #fef3c7; color: #b45309; }
.top-rank.silver { background: #f1f5f9; color: #475569; }
.top-rank.bronze { background: #fff7ed; color: #c2410c; }
.top-rank.normal { background: var(--border-light); color: var(--text-muted); }
.top-name { flex: 1; font-weight: 600; font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.top-qty { font-weight: 700; font-size: 0.88rem; color: var(--primary-dark); background: var(--primary-light); padding: 4px 12px; border-radius: 20px; }

/* RESPONSIVE */
@media (max-width: 1200px) { .stats-grid, .stats-row-secondary { grid-template-columns: repeat(2,1fr); } .dashboard-grid { grid-template-columns: 1fr; } .full-width-grid { grid-template-columns: 1fr; } }
@media (max-width: 768px) { .main-container { padding: 16px 16px 40px; } .welcome-section h1 { font-size: 1.8rem; } .stats-grid, .stats-row-secondary { grid-template-columns: 1fr; } .quick-actions { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="particles-container">
    <div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div>
</div>

<main class="main-container">

    <!-- WELCOME -->
    <div class="welcome-section">
        <h1>Bentornato, <?php echo htmlspecialchars($nome_utente); ?>!</h1>
        <p>Ecco il riepilogo della tua attivit&agrave; di oggi</p>
        <div class="date-badge">
            <i class="fas fa-calendar-day"></i>
            <?php 
                $mesi_it = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
                $giorni_it = ['Domenica','Luned&igrave;','Marted&igrave;','Mercoled&igrave;','Gioved&igrave;','Venerd&igrave;','Sabato'];
                echo $giorni_it[date('w')] . ' ' . date('d') . ' ' . $mesi_it[date('n')-1] . ' ' . date('Y');
            ?>
        </div>
    </div>

    <!-- STAT CARDS ROW 1 -->
    <div class="stats-grid">
        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-cash-register"></i></div>
            <div>
                <div class="stat-label">Vendite Oggi</div>
                <div class="stat-value">&euro;<?php echo number_format($vendite_oggi['tot'], 2, ',', '.'); ?></div>
                <?php if ($var_vendite > 0): ?>
                    <div class="stat-trend up"><i class="fas fa-arrow-up"></i> +<?php echo $var_vendite; ?>% vs ieri</div>
                <?php elseif ($var_vendite < 0): ?>
                    <div class="stat-trend down"><i class="fas fa-arrow-down"></i> <?php echo $var_vendite; ?>% vs ieri</div>
                <?php else: ?>
                    <div class="stat-trend neutral"><i class="fas fa-minus"></i> invariato</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-receipt"></i></div>
            <div>
                <div class="stat-label">Transazioni Oggi</div>
                <div class="stat-value"><?php echo $vendite_oggi['num']; ?></div>
                <div class="stat-trend neutral"><i class="fas fa-calendar-week"></i> Mese: <?php echo $vendite_mese['num']; ?></div>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fas fa-screwdriver-wrench"></i></div>
            <div>
                <div class="stat-label">Riparazioni Aperte</div>
                <div class="stat-value"><?php echo $riparazioni['totali']; ?></div>
                <div class="stat-trend neutral"><i class="fas fa-clock"></i> <?php echo $riparazioni['in_attesa']; ?> in attesa</div>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div>
                <div class="stat-label">Margine Oggi</div>
                <div class="stat-value">&euro;<?php echo number_format($margine_oggi, 2, ',', '.'); ?></div>
                <div class="stat-trend <?php echo $margine_oggi >= 0 ? 'up' : 'down'; ?>">
                    <i class="fas fa-<?php echo $margine_oggi >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                    ricavo - costo
                </div>
            </div>
        </div>
    </div>

    <!-- SECONDARY STATS -->
    <div class="stats-row-secondary">
        <div class="mini-stat">
            <div class="mini-icon" style="background:var(--teal-light);color:#0d9488;"><i class="fas fa-users"></i></div>
            <div>
                <div class="mini-label">Clienti Totali</div>
                <div class="mini-value" style="color:#0d9488;"><?php echo $totale_clienti; ?></div>
            </div>
        </div>
        <div class="mini-stat">
            <div class="mini-icon" style="background:var(--primary-light);color:var(--primary-dark);"><i class="fas fa-warehouse"></i></div>
            <div>
                <div class="mini-label">Valore Magazzino</div>
                <div class="mini-value" style="color:var(--primary-dark);">&euro;<?php echo number_format($valore_magazzino, 2, ',', '.'); ?></div>
            </div>
        </div>
        <div class="mini-stat">
            <div class="mini-icon" style="background:var(--secondary-light);color:var(--secondary);"><i class="fas fa-bookmark"></i></div>
            <div>
                <div class="mini-label">Prenotazioni in Attesa</div>
                <div class="mini-value" style="color:var(--secondary);"><?php echo $prenotazioni_pending; ?></div>
            </div>
        </div>
        <div class="mini-stat">
            <div class="mini-icon" style="background:var(--purple-light);color:var(--purple);"><i class="fas fa-gift"></i></div>
            <div>
                <div class="mini-label">Buoni Attivi</div>
                <div class="mini-value" style="color:var(--purple);"><?php echo $buoni_attivi; ?></div>
            </div>
        </div>
    </div>

    <!-- MAIN GRID: Chart + Sidebar -->
    <div class="dashboard-grid">
        <!-- LEFT: Sales Chart -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-title-icon green"><i class="fas fa-chart-area"></i></div>
                    Andamento Vendite &mdash; Ultimi 7 Giorni
                </div>
                <a href="reportistica.php" class="card-link">Report completo <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="90"></canvas>
            </div>
        </div>

        <!-- RIGHT: Alerts + Quick Actions -->
        <div style="display:flex;flex-direction:column;gap:24px;">
            <!-- ALERTS -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <div class="card-title-icon red"><i class="fas fa-bell"></i></div>
                        Avvisi
                    </div>
                </div>
                <div class="card-body-compact">
                    <?php if ($esauriti > 0): ?>
                    <div class="alert-item">
                        <div class="alert-dot red"></div>
                        <div class="alert-info">
                            <div class="alert-title">Prodotti Esauriti</div>
                            <div class="alert-meta">Giacenza a zero &mdash; necessario riordino</div>
                        </div>
                        <div class="alert-count red"><?php echo $esauriti; ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($sotto_scorta > 0): ?>
                    <div class="alert-item">
                        <div class="alert-dot orange"></div>
                        <div class="alert-info">
                            <div class="alert-title">Sotto Scorta</div>
                            <div class="alert-meta">Prodotti con giacenza &le; 5 pezzi</div>
                        </div>
                        <div class="alert-count orange"><?php echo $sotto_scorta; ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($buoni_scadenza > 0): ?>
                    <div class="alert-item">
                        <div class="alert-dot blue"></div>
                        <div class="alert-info">
                            <div class="alert-title">Buoni in Scadenza</div>
                            <div class="alert-meta">Scadono nei prossimi 30 giorni</div>
                        </div>
                        <div class="alert-count blue"><?php echo $buoni_scadenza; ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($permute_aperte > 0): ?>
                    <div class="alert-item">
                        <div class="alert-dot orange"></div>
                        <div class="alert-info">
                            <div class="alert-title">Permute Aperte</div>
                            <div class="alert-meta">In trattativa o accettate</div>
                        </div>
                        <div class="alert-count orange"><?php echo $permute_aperte; ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($esauriti == 0 && $sotto_scorta == 0 && $buoni_scadenza == 0 && $permute_aperte == 0): ?>
                    <div style="text-align:center;padding:24px;color:var(--text-muted);">
                        <i class="fas fa-check-circle" style="font-size:2rem;color:var(--primary);display:block;margin-bottom:10px;"></i>
                        Tutto in ordine! Nessun avviso.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <div class="card-title-icon teal"><i class="fas fa-bolt"></i></div>
                        Azioni Rapide
                    </div>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="homepage.php" class="quick-action-btn"><i class="fas fa-cart-plus"></i> Nuova Vendita</a>
                        <a href="inventario.php" class="quick-action-btn"><i class="fas fa-boxes-stacked"></i> Inventario</a>
                        <a href="gestisci_ticket.php" class="quick-action-btn"><i class="fas fa-ticket"></i> Ticket</a>
                        <a href="chiusura_cassa.php" class="quick-action-btn"><i class="fas fa-calculator"></i> Chiusura Cassa</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTTOM GRID: Tables -->
    <div class="full-width-grid">
        <!-- ULTIME VENDITE -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-title-icon green"><i class="fas fa-receipt"></i></div>
                    Ultime Vendite
                </div>
                <a href="visualizza_vendite.php" class="card-link">Vedi tutte <i class="fas fa-arrow-right"></i></a>
            </div>
            <div style="overflow-x:auto;">
                <table class="dash-table">
                    <thead><tr><th>#</th><th>Cliente</th><th>Totale</th><th>Data</th></tr></thead>
                    <tbody>
                    <?php if (empty($ultime_vendite)): ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:30px;">Nessuna vendita registrata</td></tr>
                    <?php else: ?>
                        <?php foreach ($ultime_vendite as $v): ?>
                        <tr>
                            <td style="font-weight:600;color:var(--text-muted);">#<?php echo $v['id']; ?></td>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($v['nome_cliente'] ?: 'Anonimo'); ?></td>
                            <td style="font-weight:700;color:var(--primary-dark);">&euro;<?php echo number_format($v['totale'], 2, ',', '.'); ?></td>
                            <td style="color:var(--text-muted);font-size:0.82rem;"><?php echo date('d/m/Y H:i', strtotime($v['data_vendita'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ULTIME RIPARAZIONI + TOP PRODOTTI -->
        <div style="display:flex;flex-direction:column;gap:24px;">
            <!-- RIPARAZIONI -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <div class="card-title-icon orange"><i class="fas fa-screwdriver-wrench"></i></div>
                        Ultime Riparazioni
                    </div>
                    <a href="storico_riparazioni.php" class="card-link">Vedi tutte <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body-compact">
                    <?php if (empty($ultime_riparazioni)): ?>
                        <div style="text-align:center;padding:20px;color:var(--text-muted);">Nessuna riparazione</div>
                    <?php else: ?>
                        <?php foreach ($ultime_riparazioni as $r): ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border-light);">
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:600;font-size:0.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($r['modello']); ?></div>
                                <div style="font-size:0.78rem;color:var(--text-muted);"><?php echo htmlspecialchars($r['cliente'] ?? '—'); ?> &middot; <?php echo date('d/m', strtotime($r['data_creazione'])); ?></div>
                            </div>
                            <?php 
                                $sc = 'attesa';
                                if ($r['stato'] === 'In Lavorazione') $sc = 'lavorazione';
                                elseif ($r['stato'] === 'Completata' || $r['stato'] === 'Consegnata') $sc = 'completata';
                            ?>
                            <span class="status-badge <?php echo $sc; ?>"><?php echo htmlspecialchars($r['stato']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TOP PRODOTTI -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <div class="card-title-icon purple"><i class="fas fa-ranking-star"></i></div>
                        Top Prodotti del Mese
                    </div>
                </div>
                <div class="card-body-compact">
                    <?php if (empty($top_prodotti)): ?>
                        <div style="text-align:center;padding:20px;color:var(--text-muted);">Nessuna vendita questo mese</div>
                    <?php else: ?>
                        <?php foreach ($top_prodotti as $i => $tp): ?>
                        <?php 
                            $rankClass = 'normal';
                            if ($i === 0) $rankClass = 'gold';
                            elseif ($i === 1) $rankClass = 'silver';
                            elseif ($i === 2) $rankClass = 'bronze';
                        ?>
                        <div class="top-product-item">
                            <div class="top-rank <?php echo $rankClass; ?>"><?php echo $i + 1; ?></div>
                            <div class="top-name" title="<?php echo htmlspecialchars($tp['nome']); ?>"><?php echo htmlspecialchars($tp['nome']); ?></div>
                            <div class="top-qty"><?php echo intval($tp['qty']); ?> pz</div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.font.family = 'Inter';
    Chart.defaults.color = '#64748b';

    const labels = <?php echo json_encode(array_column($vendite_7gg, 'label')); ?>;
    const values = <?php echo json_encode(array_column($vendite_7gg, 'value')); ?>;

    const ctx = document.getElementById('salesChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 350);
    gradient.addColorStop(0, 'rgba(34,197,94,0.25)');
    gradient.addColorStop(1, 'rgba(34,197,94,0.02)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Vendite (\u20ac)',
                data: values,
                borderColor: '#22c55e',
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#22c55e',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 8,
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { padding: 20, font: { size: 12, weight: '600' }, usePointStyle: true } },
                tooltip: {
                    backgroundColor: '#1e293b', titleFont: { size: 13, weight: '700' },
                    bodyFont: { size: 12 }, padding: 14, cornerRadius: 10, displayColors: false,
                    callbacks: { label: ctx => '\u20ac' + ctx.parsed.y.toFixed(2).replace('.',',') }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11, weight: '500' } } },
                y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 11, weight: '500' }, callback: v => '\u20ac' + v } }
            },
            interaction: { intersect: false, mode: 'index' }
        }
    });
});
</script>
</body>
</html>