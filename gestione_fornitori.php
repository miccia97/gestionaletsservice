<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
include 'db.php';
include 'audit_helper.php';

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $ragione_sociale = trim($_POST['ragione_sociale'] ?? '');
        $partita_iva = trim($_POST['partita_iva'] ?? '');
        $codice_fiscale = trim($_POST['codice_fiscale'] ?? '');
        $indirizzo = trim($_POST['indirizzo'] ?? '');
        $citta = trim($_POST['citta'] ?? '');
        $cap = trim($_POST['cap'] ?? '');
        $provincia = trim($_POST['provincia'] ?? '');
        $paese = trim($_POST['paese'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($nome)) {
            echo json_encode(['error' => true, 'message' => 'Il nome è obbligatorio.']);
            exit;
        }

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE fornitori SET nome=?, ragione_sociale=?, partita_iva=?, codice_fiscale=?, indirizzo=?, citta=?, cap=?, provincia=?, paese=?, telefono=?, email=? WHERE id=?");
            $stmt->bind_param("sssssssssssi", $nome, $ragione_sociale, $partita_iva, $codice_fiscale, $indirizzo, $citta, $cap, $provincia, $paese, $telefono, $email, $id);
            $stmt->execute();
            registra_log($conn, 'Modifica fornitore', 'fornitori', $id, $nome);
            echo json_encode(['success' => true, 'message' => 'Fornitore aggiornato con successo.']);
        } else {
            $stmt = $conn->prepare("INSERT INTO fornitori (nome, ragione_sociale, partita_iva, codice_fiscale, indirizzo, citta, cap, provincia, paese, telefono, email) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssssssss", $nome, $ragione_sociale, $partita_iva, $codice_fiscale, $indirizzo, $citta, $cap, $provincia, $paese, $telefono, $email);
            $stmt->execute();
            $new_id = $conn->insert_id;
            registra_log($conn, 'Creazione fornitore', 'fornitori', $new_id, $nome);
            echo json_encode(['success' => true, 'message' => 'Fornitore aggiunto con successo.']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        // Controlla fatture collegate
        $check = $conn->prepare("SELECT COUNT(*) as c FROM fatture WHERE fornitore_id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $count = $check->get_result()->fetch_assoc()['c'];
        $check->close();

        if ($count > 0) {
            echo json_encode(['error' => true, 'message' => "Impossibile eliminare: ci sono $count fatture collegate a questo fornitore."]);
            exit;
        }

        $q = $conn->query("SELECT nome FROM fornitori WHERE id=$id");
        $nome_f = $q ? $q->fetch_assoc()['nome'] : '';

        $stmt = $conn->prepare("DELETE FROM fornitori WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        registra_log($conn, 'Eliminazione fornitore', 'fornitori', $id, $nome_f);
        echo json_encode(['success' => true, 'message' => 'Fornitore eliminato.']);
        $stmt->close();
        exit;
    }

    if ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM fornitori WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        echo json_encode($r ?: ['error' => true]);
        exit;
    }
}

// Carica fornitori
$search = trim($_GET['search'] ?? '');
$sql = "SELECT f.*, (SELECT COUNT(*) FROM fatture WHERE fornitore_id = f.id) as num_fatture FROM fornitori f";
if (!empty($search)) {
    $sql .= " WHERE f.nome LIKE ? OR f.ragione_sociale LIKE ? OR f.partita_iva LIKE ? OR f.email LIKE ? OR f.telefono LIKE ?";
}
$sql .= " ORDER BY f.nome ASC";

if (!empty($search)) {
    $stmt = $conn->prepare($sql);
    $like = "%$search%";
    $stmt->bind_param("sssss", $like, $like, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$fornitori = [];
if ($result) { while ($r = $result->fetch_assoc()) { $fornitori[] = $r; } }
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Gestione Fornitori | TS Service</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/header-styles.css?v=1">
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<style>
:root{--primary:#22c55e;--primary-dark:#16a34a;--primary-light:#dcfce7;--primary-glow:rgba(34,197,94,0.4);--secondary:#3b82f6;--secondary-light:#dbeafe;--purple:#8b5cf6;--purple-light:#ede9fe;--orange:#f59e0b;--orange-light:#fef3c7;--danger:#ef4444;--danger-light:#fee2e2;--bg-page:#f8fafc;--bg-card:#ffffff;--text-primary:#0f172a;--text-secondary:#64748b;--text-muted:#94a3b8;--border-color:#e2e8f0;--border-light:#f1f5f9;--shadow:0 4px 6px -1px rgb(0 0 0/0.1),0 2px 4px -2px rgb(0 0 0/0.1);--shadow-md:0 10px 15px -3px rgb(0 0 0/0.1),0 4px 6px -4px rgb(0 0 0/0.1);--shadow-lg:0 20px 25px -5px rgb(0 0 0/0.1),0 8px 10px -6px rgb(0 0 0/0.1);--radius-md:0.75rem;--radius-lg:1rem;--radius-xl:1.5rem;--transition:200ms cubic-bezier(0.4,0,0.2,1)}
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

/* Page Header */
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:16px;animation:fadeUp 0.5s ease-out}
.page-header-left{display:flex;align-items:center;gap:16px}
.page-icon{width:56px;height:56px;border-radius:var(--radius-lg);background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;box-shadow:0 8px 20px var(--primary-glow)}
.page-header h1{font-size:1.8rem;font-weight:800;letter-spacing:-0.025em}
.page-header .subtitle{color:var(--text-secondary);font-size:0.95rem;margin-top:2px}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

/* Toolbar */
.toolbar{display:flex;align-items:center;gap:14px;margin-bottom:24px;flex-wrap:wrap;animation:fadeUp 0.5s ease-out 0.1s both}
.search-box{position:relative;flex:1;min-width:260px;max-width:450px}
.search-box input{width:100%;padding:12px 16px 12px 44px;border:1.5px solid var(--border-color);border-radius:var(--radius-md);font-size:0.9rem;font-family:'Inter',sans-serif;background:#fff;outline:none;transition:all var(--transition)}
.search-box input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(34,197,94,0.12)}
.search-box i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none}
.count-badge{background:var(--primary-light);color:var(--primary-dark);padding:6px 16px;border-radius:30px;font-size:0.85rem;font-weight:700;display:flex;align-items:center;gap:6px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 24px;border-radius:var(--radius-md);font-weight:700;font-size:0.9rem;font-family:'Inter',sans-serif;cursor:pointer;border:none;transition:all var(--transition)}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;box-shadow:0 4px 12px var(--primary-glow)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px var(--primary-glow)}
.btn-sm{padding:8px 16px;font-size:0.82rem;font-weight:600}
.btn-outline{background:transparent;color:var(--text-secondary);border:1.5px solid var(--border-color)}
.btn-outline:hover{background:var(--border-light);color:var(--text-primary)}
.btn-danger{background:linear-gradient(135deg,var(--danger),#dc2626);color:#fff}
.btn-danger:hover{transform:translateY(-1px)}
.btn-blue{background:linear-gradient(135deg,var(--secondary),#2563eb);color:#fff}
.btn-blue:hover{transform:translateY(-1px)}

/* Card */
.card{background:var(--bg-card);border-radius:var(--radius-xl);box-shadow:var(--shadow);border:1px solid var(--border-color);overflow:hidden}

/* Table */
.table-wrapper{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th{padding:14px 16px;text-align:left;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);background:var(--border-light);border-bottom:2px solid var(--border-color)}
td{padding:14px 16px;font-size:0.88rem;border-bottom:1px solid var(--border-light)}
tr{transition:background 0.15s}
tbody tr:hover{background:rgba(34,197,94,0.04)}
tbody tr:last-child td{border-bottom:none}
.cell-name{font-weight:700;color:var(--text-primary);font-size:0.92rem}
.cell-sub{font-size:0.78rem;color:var(--text-muted);margin-top:2px}
.badge{display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:700}
.badge-green{background:var(--primary-light);color:var(--primary-dark)}
.badge-gray{background:var(--border-light);color:var(--text-muted)}
.actions-cell{display:flex;gap:6px}

/* Empty */
.empty-state{text-align:center;padding:60px 20px;color:var(--text-muted)}
.empty-state i{font-size:3rem;margin-bottom:16px;display:block;color:var(--border-color)}
.empty-state h3{font-size:1.1rem;font-weight:700;color:var(--text-secondary);margin-bottom:4px}

/* Modal */
#supplierModal{display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center}
#supplierModal.show{display:flex}
.modal-backdrop{position:absolute;inset:0;background:rgba(15,23,42,0.5);backdrop-filter:blur(4px)}
.modal-content{position:relative;background:#fff;border-radius:var(--radius-xl);box-shadow:var(--shadow-lg);width:90%;max-width:680px;max-height:90vh;overflow-y:auto;animation:modalIn 0.3s ease-out}
@keyframes modalIn{from{opacity:0;transform:scale(0.9) translateY(20px)}to{opacity:1;transform:scale(1) translateY(0)}}
.modal-header{padding:24px 28px;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1}
.modal-header h2{font-size:1.2rem;font-weight:800;display:flex;align-items:center;gap:10px}
.modal-close{width:36px;height:36px;border-radius:10px;border:none;background:var(--border-light);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1rem;color:var(--text-muted);transition:all var(--transition)}
.modal-close:hover{background:var(--danger-light);color:var(--danger)}
.modal-body{padding:28px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px 24px}
.form-grid .full-width{grid-column:1/-1}
.input-group{margin-bottom:0}
.input-label{display:block;font-weight:600;font-size:0.8rem;color:var(--text-secondary);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.04em}
.input-field{width:100%;padding:11px 14px;border:1.5px solid var(--border-color);border-radius:var(--radius-md);font-size:0.9rem;font-family:'Inter',sans-serif;outline:none;transition:all var(--transition);background:#f8fafc}
.input-field:focus{border-color:var(--primary);background:#fff;box-shadow:0 0 0 3px rgba(34,197,94,0.12)}
.modal-footer{padding:20px 28px;border-top:1px solid var(--border-light);display:flex;justify-content:flex-end;gap:12px;position:sticky;bottom:0;background:#fff}

/* Toast */
.toast-container{position:fixed;top:100px;right:24px;z-index:10001;display:flex;flex-direction:column;gap:12px;pointer-events:none}
.toast{min-width:320px;max-width:450px;pointer-events:auto;padding:14px 20px;border-radius:var(--radius-md);color:#fff;font-weight:600;font-size:0.9rem;display:flex;align-items:center;gap:10px;box-shadow:var(--shadow-lg);animation:slideInToast 0.3s ease-out,fadeOutToast 0.4s 3s ease-in forwards}
.toast.success{background:linear-gradient(135deg,#10b981,#059669)}.toast.error{background:linear-gradient(135deg,#ef4444,#dc2626)}
@keyframes slideInToast{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}
@keyframes fadeOutToast{to{opacity:0;transform:translateX(40px)}}

/* Confirm */
.confirm-backdrop{position:fixed;inset:0;background:rgba(15,23,42,0.5);backdrop-filter:blur(4px);z-index:10002;display:none}
.confirm-backdrop.show{display:block}
.confirm-dialog{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(0.9);background:#fff;border-radius:var(--radius-xl);padding:32px;box-shadow:var(--shadow-lg);z-index:10003;max-width:440px;width:90%;opacity:0;visibility:hidden;transition:all 0.3s}
.confirm-dialog.show{opacity:1;visibility:visible;transform:translate(-50%,-50%) scale(1)}
.confirm-dialog .icon-circle{width:64px;height:64px;border-radius:50%;margin:0 auto 20px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;background:var(--danger-light);color:var(--danger)}
.confirm-dialog h3{text-align:center;font-size:1.2rem;font-weight:700;margin-bottom:8px}
.confirm-dialog p{text-align:center;color:var(--text-secondary);font-size:0.9rem;margin-bottom:28px}
.confirm-dialog .dialog-actions{display:flex;gap:12px}
.confirm-dialog .dialog-actions .btn{flex:1}

@media(max-width:768px){.main-container{padding:16px 16px 40px}.page-header h1{font-size:1.4rem}.toolbar{flex-direction:column;align-items:stretch}.search-box{max-width:100%}.form-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="particles-container"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
<div class="toast-container" id="toastContainer"></div>

<!-- Confirm Dialog -->
<div class="confirm-backdrop" id="confirmBackdrop"></div>
<div class="confirm-dialog" id="confirmDialog">
    <div class="icon-circle"><i class="fas fa-triangle-exclamation"></i></div>
    <h3 id="confirmTitle">Conferma eliminazione</h3>
    <p id="confirmMsg">Sei sicuro di voler eliminare questo fornitore?</p>
    <div class="dialog-actions">
        <button class="btn btn-outline" onclick="closeConfirm()">Annulla</button>
        <button class="btn btn-danger" id="confirmOkBtn"><i class="fas fa-trash-can"></i> Elimina</button>
    </div>
</div>

<main class="main-container">
    <div class="page-header">
        <div class="page-header-left">
            <div class="page-icon"><i class="fas fa-truck-field"></i></div>
            <div>
                <h1>Gestione Fornitori</h1>
                <p class="subtitle">Anagrafica e contatti dei tuoi fornitori</p>
            </div>
        </div>
        <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Nuovo Fornitore</button>
    </div>

    <div class="toolbar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Cerca per nome, P.IVA, email, telefono..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="count-badge"><i class="fas fa-building"></i> <?php echo count($fornitori); ?> fornitori</div>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <?php if (empty($fornitori)): ?>
                <div class="empty-state">
                    <i class="fas fa-building-circle-xmark"></i>
                    <h3>Nessun fornitore trovato</h3>
                    <p>Inizia aggiungendo il tuo primo fornitore.</p>
                </div>
            <?php else: ?>
            <table>
                <thead><tr>
                    <th>Fornitore</th>
                    <th>P.IVA / C.F.</th>
                    <th>Contatti</th>
                    <th>Indirizzo</th>
                    <th>Fatture</th>
                    <th>Azioni</th>
                </tr></thead>
                <tbody>
                <?php foreach ($fornitori as $f): ?>
                <tr>
                    <td>
                        <div class="cell-name"><?php echo htmlspecialchars($f['nome']); ?></div>
                        <?php if ($f['ragione_sociale']): ?><div class="cell-sub"><?php echo htmlspecialchars($f['ragione_sociale']); ?></div><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($f['partita_iva']): ?><div style="font-weight:600;font-size:0.82rem;"><?php echo htmlspecialchars($f['partita_iva']); ?></div><?php endif; ?>
                        <?php if ($f['codice_fiscale']): ?><div class="cell-sub"><?php echo htmlspecialchars($f['codice_fiscale']); ?></div><?php endif; ?>
                        <?php if (!$f['partita_iva'] && !$f['codice_fiscale']): ?><span style="color:var(--text-muted);">—</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($f['telefono']): ?><div style="font-size:0.85rem;"><i class="fas fa-phone" style="color:var(--primary);font-size:0.7rem;margin-right:4px;"></i><?php echo htmlspecialchars($f['telefono']); ?></div><?php endif; ?>
                        <?php if ($f['email']): ?><div style="font-size:0.82rem;color:var(--text-muted);margin-top:2px;"><i class="fas fa-envelope" style="font-size:0.7rem;margin-right:4px;"></i><?php echo htmlspecialchars($f['email']); ?></div><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($f['citta']): ?>
                            <div style="font-size:0.85rem;"><?php echo htmlspecialchars($f['citta']); ?><?php if($f['provincia']): ?> (<?php echo htmlspecialchars($f['provincia']); ?>)<?php endif; ?></div>
                            <?php if ($f['indirizzo']): ?><div class="cell-sub"><?php echo htmlspecialchars($f['indirizzo']); ?></div><?php endif; ?>
                        <?php else: ?>
                            <span style="color:var(--text-muted);">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($f['num_fatture'] > 0): ?>
                            <span class="badge badge-green"><i class="fas fa-file-invoice"></i> <?php echo $f['num_fatture']; ?></span>
                        <?php else: ?>
                            <span class="badge badge-gray">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="actions-cell">
                            <button class="btn btn-blue btn-sm" onclick="editFornitore(<?php echo $f['id']; ?>)" title="Modifica"><i class="fas fa-pen"></i></button>
                            <button class="btn btn-danger btn-sm" onclick="deleteFornitore(<?php echo $f['id']; ?>, '<?php echo addslashes(htmlspecialchars($f['nome'])); ?>')" title="Elimina"><i class="fas fa-trash-can"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal -->
<div id="supplierModal">
    <div class="modal-backdrop" onclick="closeModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-truck-field" style="color:var(--primary);"></i> <span id="modalTitle">Nuovo Fornitore</span></h2>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <form id="supplierForm">
            <div class="modal-body">
                <input type="hidden" id="fId" name="id" value="0">
                <input type="hidden" name="action" value="save">
                <div class="form-grid">
                    <div class="input-group">
                        <label class="input-label" for="fNome">Nome *</label>
                        <input class="input-field" type="text" id="fNome" name="nome" required placeholder="Nome fornitore">
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="fRagSoc">Ragione Sociale</label>
                        <input class="input-field" type="text" id="fRagSoc" name="ragione_sociale" placeholder="Ragione sociale">
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="fPIVA">Partita IVA</label>
                        <input class="input-field" type="text" id="fPIVA" name="partita_iva" placeholder="IT01234567890">
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="fCF">Codice Fiscale</label>
                        <input class="input-field" type="text" id="fCF" name="codice_fiscale" placeholder="Codice fiscale">
                    </div>
                    <div class="input-group full-width">
                        <label class="input-label" for="fIndirizzo">Indirizzo</label>
                        <input class="input-field" type="text" id="fIndirizzo" name="indirizzo" placeholder="Via, numero civico">
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="fCitta">Citt&agrave;</label>
                        <input class="input-field" type="text" id="fCitta" name="citta" placeholder="Citt&agrave;">
                    </div>
                    <div class="input-group" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label class="input-label" for="fCAP">CAP</label>
                            <input class="input-field" type="text" id="fCAP" name="cap" placeholder="00100">
                        </div>
                        <div>
                            <label class="input-label" for="fProv">Prov.</label>
                            <input class="input-field" type="text" id="fProv" name="provincia" placeholder="RM" maxlength="2" style="text-transform:uppercase;">
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="fTel">Telefono</label>
                        <input class="input-field" type="tel" id="fTel" name="telefono" placeholder="+39 ...">
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="fEmail">Email</label>
                        <input class="input-field" type="email" id="fEmail" name="email" placeholder="email@fornitore.it">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Annulla</button>
                <button type="submit" class="btn btn-primary" id="saveBtn"><i class="fas fa-floppy-disk"></i> Salva Fornitore</button>
            </div>
        </form>
    </div>
</div>

<script>
function showToast(msg, type) {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    const icons = {success:'fa-circle-check', error:'fa-circle-xmark'};
    t.innerHTML = '<i class="fas '+(icons[type]||'fa-circle-info')+'"></i> '+msg;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

function openModal(editData) {
    const m = document.getElementById('supplierModal');
    document.getElementById('modalTitle').textContent = editData ? 'Modifica Fornitore' : 'Nuovo Fornitore';
    document.getElementById('fId').value = editData ? editData.id : 0;
    document.getElementById('fNome').value = editData ? (editData.nome || '') : '';
    document.getElementById('fRagSoc').value = editData ? (editData.ragione_sociale || '') : '';
    document.getElementById('fPIVA').value = editData ? (editData.partita_iva || '') : '';
    document.getElementById('fCF').value = editData ? (editData.codice_fiscale || '') : '';
    document.getElementById('fIndirizzo').value = editData ? (editData.indirizzo || '') : '';
    document.getElementById('fCitta').value = editData ? (editData.citta || '') : '';
    document.getElementById('fCAP').value = editData ? (editData.cap || '') : '';
    document.getElementById('fProv').value = editData ? (editData.provincia || '') : '';
    document.getElementById('fTel').value = editData ? (editData.telefono || '') : '';
    document.getElementById('fEmail').value = editData ? (editData.email || '') : '';
    m.classList.add('show');
    setTimeout(() => document.getElementById('fNome').focus(), 200);
}

function closeModal() {
    document.getElementById('supplierModal').classList.remove('show');
}

function editFornitore(id) {
    fetch('gestione_fornitori.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get&id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { showToast('Errore nel caricamento.', 'error'); return; }
        openModal(data);
    })
    .catch(() => showToast('Errore di comunicazione.', 'error'));
}

// Save form
document.getElementById('supplierForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvataggio...';

    fetch('gestione_fornitori.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.error) { showToast(data.message, 'error'); }
        else { showToast(data.message, 'success'); setTimeout(() => location.reload(), 800); }
    })
    .catch(() => showToast('Errore di comunicazione.', 'error'))
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Salva Fornitore'; });
});

// Delete
let _deleteId = null;
function deleteFornitore(id, nome) {
    _deleteId = id;
    document.getElementById('confirmMsg').textContent = 'Vuoi eliminare il fornitore "' + nome + '"? Questa azione non può essere annullata.';
    document.getElementById('confirmBackdrop').classList.add('show');
    document.getElementById('confirmDialog').classList.add('show');
}
function closeConfirm() {
    document.getElementById('confirmBackdrop').classList.remove('show');
    document.getElementById('confirmDialog').classList.remove('show');
    _deleteId = null;
}
document.getElementById('confirmBackdrop').addEventListener('click', closeConfirm);
document.getElementById('confirmOkBtn').addEventListener('click', function() {
    if (!_deleteId) return;
    closeConfirm();
    fetch('gestione_fornitori.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=delete&id=' + _deleteId
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { showToast(data.message, 'error'); }
        else { showToast(data.message, 'success'); setTimeout(() => location.reload(), 800); }
    });
});

// Search
let searchTimer;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const q = this.value.trim();
        window.location.href = 'gestione_fornitori.php' + (q ? '?search=' + encodeURIComponent(q) : '');
    }, 500);
});
</script>
</body>
</html>
