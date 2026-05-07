<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
include 'db.php';

$ruolo = $_SESSION['ruolo'] ?? 'utente';
if ($ruolo !== 'admin') { echo '<p style="padding:40px;text-align:center;font-family:Inter,sans-serif;">Accesso riservato agli amministratori.</p>'; exit; }

// Filtri
$filtro_utente = trim($_GET['utente'] ?? '');
$filtro_azione = trim($_GET['azione'] ?? '');
$filtro_data_da = trim($_GET['data_da'] ?? '');
$filtro_data_a = trim($_GET['data_a'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Build query
$where = [];
$params = [];
$types = '';

if ($filtro_utente) { $where[] = "nome_utente LIKE ?"; $params[] = "%$filtro_utente%"; $types .= 's'; }
if ($filtro_azione) { $where[] = "azione LIKE ?"; $params[] = "%$filtro_azione%"; $types .= 's'; }
if ($filtro_data_da) { $where[] = "DATE(data_creazione) >= ?"; $params[] = $filtro_data_da; $types .= 's'; }
if ($filtro_data_a) { $where[] = "DATE(data_creazione) <= ?"; $params[] = $filtro_data_a; $types .= 's'; }

$where_sql = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

// Count
$stmt = $conn->prepare("SELECT COUNT(*) as tot FROM audit_log" . $where_sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$totale = $stmt->get_result()->fetch_assoc()['tot'];
$stmt->close();
$total_pages = max(1, ceil($totale / $per_page));

// Fetch
$stmt = $conn->prepare("SELECT * FROM audit_log" . $where_sql . " ORDER BY data_creazione DESC LIMIT ? OFFSET ?");
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Azioni uniche per filtro
$azioni_uniche = [];
$q = $conn->query("SELECT DISTINCT azione FROM audit_log ORDER BY azione");
if ($q) { while ($r = $q->fetch_assoc()) { $azioni_uniche[] = $r['azione']; } }
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registro Attivit&agrave; | TS Service</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/header-styles.css?v=2">
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<style>
:root{--primary:#22c55e;--primary-dark:#16a34a;--primary-light:#dcfce7;--primary-glow:rgba(34,197,94,0.4);--secondary:#3b82f6;--secondary-light:#dbeafe;--purple:#8b5cf6;--purple-light:#ede9fe;--orange:#f59e0b;--orange-light:#fef3c7;--danger:#ef4444;--danger-light:#fee2e2;--bg-page:#f8fafc;--bg-card:#ffffff;--text-primary:#0f172a;--text-secondary:#64748b;--text-muted:#94a3b8;--border-color:#e2e8f0;--border-light:#f1f5f9;--shadow:0 4px 6px -1px rgb(0 0 0/0.1),0 2px 4px -2px rgb(0 0 0/0.1);--shadow-md:0 10px 15px -3px rgb(0 0 0/0.1),0 4px 6px -4px rgb(0 0 0/0.1);--radius-md:0.75rem;--radius-lg:1rem;--radius-xl:1.5rem;--transition:200ms cubic-bezier(0.4,0,0.2,1)}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,var(--bg-page) 0%,#e2e8f0 100%);min-height:100vh;color:var(--text-primary);padding-top:80px;line-height:1.6;overflow-x:hidden}
.particles-container{position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:0;overflow:hidden}
.particle{position:absolute;border-radius:50%;opacity:0.12;animation:floatP 22s infinite ease-in-out}
.particle:nth-child(1){width:320px;height:320px;background:var(--primary);top:-120px;left:-80px}
.particle:nth-child(2){width:220px;height:220px;background:var(--secondary);top:50%;right:-60px;animation-delay:-6s}
.particle:nth-child(3){width:180px;height:180px;background:var(--purple);bottom:8%;left:25%;animation-delay:-12s}
@keyframes floatP{0%,100%{transform:translate(0,0) scale(1)}25%{transform:translate(30px,-30px) scale(1.05)}50%{transform:translate(-20px,20px) scale(0.95)}75%{transform:translate(15px,15px) scale(1.02)}}
.main-container{max-width:1400px;margin:0 auto;padding:24px 32px 60px;position:relative;z-index:1}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:16px;animation:fadeUp 0.5s ease-out}
.page-header-left{display:flex;align-items:center;gap:16px}
.page-icon{width:56px;height:56px;border-radius:var(--radius-lg);background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;box-shadow:0 8px 20px var(--primary-glow)}
.page-header h1{font-size:1.8rem;font-weight:800}
.page-header .subtitle{color:var(--text-secondary);font-size:0.95rem;margin-top:2px}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

/* Filters */
.filters{display:flex;gap:12px;align-items:flex-end;margin-bottom:24px;flex-wrap:wrap;animation:fadeUp 0.5s ease-out 0.1s both}
.filter-group label{display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:4px}
.filter-group input,.filter-group select{padding:10px 14px;border:1.5px solid var(--border-color);border-radius:var(--radius-md);font-size:0.85rem;font-family:'Inter',sans-serif;outline:none;transition:all var(--transition);background:#fff}
.filter-group input:focus,.filter-group select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(34,197,94,0.12)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 20px;border-radius:var(--radius-md);font-weight:700;font-size:0.85rem;font-family:'Inter',sans-serif;cursor:pointer;border:none;transition:all var(--transition)}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;box-shadow:0 4px 12px var(--primary-glow)}
.btn-primary:hover{transform:translateY(-2px)}
.btn-outline{background:transparent;color:var(--text-secondary);border:1.5px solid var(--border-color)}
.btn-outline:hover{background:var(--border-light)}
.count-badge{background:var(--primary-light);color:var(--primary-dark);padding:6px 16px;border-radius:30px;font-size:0.85rem;font-weight:700}

.card{background:var(--bg-card);border-radius:var(--radius-xl);box-shadow:var(--shadow);border:1px solid var(--border-color);overflow:hidden}
.table-wrapper{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th{padding:12px 16px;text-align:left;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);background:var(--border-light);border-bottom:2px solid var(--border-color)}
td{padding:12px 16px;font-size:0.85rem;border-bottom:1px solid var(--border-light)}
tbody tr:hover{background:rgba(34,197,94,0.04)}
tbody tr:last-child td{border-bottom:none}

.action-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:700}
.action-badge.create{background:var(--primary-light);color:var(--primary-dark)}
.action-badge.update{background:var(--secondary-light);color:var(--secondary)}
.action-badge.delete{background:var(--danger-light);color:var(--danger)}
.action-badge.login{background:var(--purple-light);color:var(--purple)}
.action-badge.other{background:var(--orange-light);color:#b45309}

.pagination{display:flex;justify-content:center;gap:8px;margin-top:24px}
.pagination a,.pagination span{padding:8px 14px;border-radius:8px;font-size:0.85rem;font-weight:600;text-decoration:none;border:1px solid var(--border-color);color:var(--text-secondary);background:#fff;transition:all var(--transition)}
.pagination a:hover{border-color:var(--primary);color:var(--primary-dark);background:var(--primary-light)}
.pagination .active{background:var(--primary);color:#fff;border-color:var(--primary)}

.empty-state{text-align:center;padding:60px;color:var(--text-muted)}
.empty-state i{font-size:3rem;display:block;margin-bottom:16px;color:var(--border-color)}

@media(max-width:768px){.main-container{padding:16px}.filters{flex-direction:column}.page-header h1{font-size:1.4rem}}
</style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="particles-container"><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
<main class="main-container">

<div class="page-header">
    <div class="page-header-left">
        <div class="page-icon"><i class="fas fa-clock-rotate-left"></i></div>
        <div>
            <h1>Registro Attivit&agrave;</h1>
            <p class="subtitle">Tracciamento di tutte le azioni effettuate nel gestionale</p>
        </div>
    </div>
    <div class="count-badge"><i class="fas fa-list-check"></i> <?php echo number_format($totale, 0, ',', '.'); ?> eventi</div>
</div>

<form method="GET" class="filters">
    <div class="filter-group">
        <label>Utente</label>
        <input type="text" name="utente" value="<?php echo htmlspecialchars($filtro_utente); ?>" placeholder="Nome utente...">
    </div>
    <div class="filter-group">
        <label>Azione</label>
        <select name="azione">
            <option value="">Tutte</option>
            <?php foreach ($azioni_uniche as $a): ?>
            <option value="<?php echo htmlspecialchars($a); ?>" <?php echo $filtro_azione === $a ? 'selected' : ''; ?>><?php echo htmlspecialchars($a); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label>Da</label>
        <input type="date" name="data_da" value="<?php echo htmlspecialchars($filtro_data_da); ?>">
    </div>
    <div class="filter-group">
        <label>A</label>
        <input type="date" name="data_a" value="<?php echo htmlspecialchars($filtro_data_a); ?>">
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtra</button>
    <a href="audit_log.php" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
</form>

<div class="card">
    <div class="table-wrapper">
        <?php if (empty($logs)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3 style="font-weight:700;color:var(--text-secondary);margin-bottom:4px;">Nessun evento registrato</h3>
                <p>Le attivit&agrave; verranno registrate automaticamente.</p>
            </div>
        <?php else: ?>
        <table>
            <thead><tr>
                <th>Data/Ora</th>
                <th>Utente</th>
                <th>Azione</th>
                <th>Tabella</th>
                <th>ID Record</th>
                <th>Dettagli</th>
                <th>IP</th>
            </tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
            <?php
                $ac = 'other';
                $al = strtolower($log['azione']);
                if (strpos($al, 'creaz') !== false || strpos($al, 'aggiunt') !== false || strpos($al, 'salv') !== false) $ac = 'create';
                elseif (strpos($al, 'modific') !== false || strpos($al, 'aggior') !== false) $ac = 'update';
                elseif (strpos($al, 'elimin') !== false || strpos($al, 'cancel') !== false) $ac = 'delete';
                elseif (strpos($al, 'login') !== false || strpos($al, 'logout') !== false || strpos($al, 'backup') !== false) $ac = 'login';
            ?>
            <tr>
                <td style="font-size:0.82rem;white-space:nowrap;">
                    <div style="font-weight:600;"><?php echo date('d/m/Y', strtotime($log['data_creazione'])); ?></div>
                    <div style="color:var(--text-muted);font-size:0.78rem;"><?php echo date('H:i:s', strtotime($log['data_creazione'])); ?></div>
                </td>
                <td style="font-weight:600;"><?php echo htmlspecialchars($log['nome_utente'] ?? '—'); ?></td>
                <td><span class="action-badge <?php echo $ac; ?>"><?php echo htmlspecialchars($log['azione']); ?></span></td>
                <td style="font-size:0.82rem;color:var(--text-muted);"><?php echo htmlspecialchars($log['tabella'] ?? '—'); ?></td>
                <td style="font-weight:600;"><?php echo $log['record_id'] ? '#' . $log['record_id'] : '—'; ?></td>
                <td style="font-size:0.82rem;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($log['dettagli'] ?? ''); ?>"><?php echo htmlspecialchars($log['dettagli'] ?? '—'); ?></td>
                <td style="font-size:0.78rem;color:var(--text-muted);font-family:monospace;"><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php if ($total_pages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&laquo;</a>
    <?php endif; ?>
    <?php
    $start = max(1, $page - 3);
    $end = min($total_pages, $page + 3);
    for ($i = $start; $i <= $end; $i++): ?>
        <?php if ($i == $page): ?>
            <span class="active"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $total_pages): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">&raquo;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

</main>
</body>
</html>
