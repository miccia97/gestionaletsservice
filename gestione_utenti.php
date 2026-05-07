<?php
session_start();

include 'db.php';

$access_denied_html = '';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$current_user_role = $_SESSION['role'] ?? 'Guest';
if ($current_user_role !== 'Amministratore') {
    $access_denied_html = '
        <div class="access-denied-card">
            <div class="access-denied-icon"><i class="fas fa-shield-halved"></i></div>
            <h3>Accesso Negato</h3>
            <p>Non hai i permessi necessari per visualizzare questa pagina.<br>Solo gli amministratori possono accedere a questa sezione.</p>
            <a href="homepage.php" class="btn btn-primary" style="margin-top:20px;text-decoration:none"><i class="fas fa-arrow-left"></i> Torna alla Home</a>
        </div>
    ';
    $utenti = [];
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    // GET: fetch single user for edit modal
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_user' && isset($_GET['id'])) {
        $user_id_to_fetch = intval($_GET['id']);
        $stmt_fetch = $conn->prepare("SELECT id, nome_utente, nome, email, ruolo, attivo FROM utenti WHERE id = ?");
        $stmt_fetch->bind_param("i", $user_id_to_fetch);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows === 1) {
            echo json_encode(['success' => true, 'data' => $result_fetch->fetch_assoc()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
        }
        $stmt_fetch->close();
        exit();
    }

    // POST: create or edit user
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = $_POST['action'] ?? 'create';
        $user_id = $_POST['user_id'] ?? null;
        $nome_utente = trim($_POST['nome_utente'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password_chiaro = $_POST['password'] ?? '';
        $conferma_password = $_POST['conferma_password'] ?? '';
        $ruolo = trim($_POST['ruolo'] ?? '');
        $attivo = isset($_POST['attivo']) ? 1 : 0;
        $error_message = '';

        if (empty($nome_utente) || empty($nome) || empty($email) || empty($ruolo)) {
            $error_message = "Tutti i campi obbligatori devono essere compilati.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "L'indirizzo email non \u00e8 valido.";
        } else {
            if ($action === 'create') {
                if (empty($password_chiaro) || empty($conferma_password)) {
                    $error_message = "La password e la conferma sono obbligatorie.";
                } elseif ($password_chiaro !== $conferma_password) {
                    $error_message = "Le password non corrispondono.";
                } elseif (strlen($password_chiaro) < 8) {
                    $error_message = "La password deve essere di almeno 8 caratteri.";
                } else {
                    $stmt_check = $conn->prepare("SELECT id FROM utenti WHERE nome_utente = ? OR email = ?");
                    $stmt_check->bind_param("ss", $nome_utente, $email);
                    $stmt_check->execute();
                    if ($stmt_check->get_result()->num_rows > 0) {
                        $error_message = "Nome utente o email gi\u00e0 esistenti.";
                    } else {
                        $hashed_password = password_hash($password_chiaro, PASSWORD_DEFAULT);
                        $stmt_insert = $conn->prepare("INSERT INTO utenti (nome_utente, nome, email, password, ruolo, attivo) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt_insert->bind_param("sssssi", $nome_utente, $nome, $email, $hashed_password, $ruolo, $attivo);
                        if (!$stmt_insert->execute()) { $error_message = "Errore durante la creazione."; }
                        $stmt_insert->close();
                    }
                    $stmt_check->close();
                }
            } elseif ($action === 'edit') {
                if (empty($user_id)) {
                    $error_message = "ID utente non fornito.";
                } else {
                    $stmt_check = $conn->prepare("SELECT id FROM utenti WHERE (nome_utente = ? OR email = ?) AND id != ?");
                    $stmt_check->bind_param("ssi", $nome_utente, $email, $user_id);
                    $stmt_check->execute();
                    if ($stmt_check->get_result()->num_rows > 0) {
                        $error_message = "Nome utente o email gi\u00e0 in uso da un altro utente.";
                    } else {
                        $update_password = false;
                        if (!empty($password_chiaro)) {
                            if ($password_chiaro !== $conferma_password) {
                                $error_message = "Le nuove password non corrispondono.";
                            } elseif (strlen($password_chiaro) < 8) {
                                $error_message = "La nuova password deve essere di almeno 8 caratteri.";
                            } else {
                                $hashed_password = password_hash($password_chiaro, PASSWORD_DEFAULT);
                                $update_password = true;
                            }
                        }
                        if (empty($error_message)) {
                            $sql_update = "UPDATE utenti SET nome_utente = ?, nome = ?, email = ?, ruolo = ?, attivo = ? " . ($update_password ? ", password = ? " : "") . "WHERE id = ?";
                            $stmt_update = $conn->prepare($sql_update);
                            if ($update_password) {
                                $stmt_update->bind_param("ssssisi", $nome_utente, $nome, $email, $ruolo, $attivo, $hashed_password, $user_id);
                            } else {
                                $stmt_update->bind_param("ssssii", $nome_utente, $nome, $email, $ruolo, $attivo, $user_id);
                            }
                            if (!$stmt_update->execute()) { $error_message = "Errore durante l'aggiornamento."; }
                            $stmt_update->close();
                        }
                    }
                    $stmt_check->close();
                }
            }
        }

        if (!empty($error_message)) {
            echo json_encode(['success' => false, 'message' => $error_message]);
        } else {
            echo json_encode(['success' => true, 'message' => "Utente " . ($action === 'create' ? 'creato' : 'aggiornato') . " con successo!"]);
        }
        exit();
    }

    header('Content-Type: text/html; charset=UTF-8');

    $utenti = [];
    $result = $conn->query("SELECT id, nome_utente, nome, email, ruolo, attivo, data_creazione, ultimo_accesso FROM utenti ORDER BY nome_utente ASC");
    if ($result) { $utenti = $result->fetch_all(MYSQLI_ASSOC); }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Utenti | TS Service</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=2">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
                    <style>
/* ============================================= */
/*          PREMIUM DESIGN SYSTEM                */
/* ============================================= */
:root {
    --primary: #22c55e;
    --primary-dark: #16a34a;
    --primary-light: #dcfce7;
    --primary-glow: rgba(34,197,94,0.4);
    --blue: #3b82f6;
    --blue-dark: #2563eb;
    --blue-light: #dbeafe;
    --secondary: #8b5cf6;
    --secondary-light: #ede9fe;
    --warning: #f59e0b;
    --warning-light: #fef3c7;
    --danger: #ef4444;
    --danger-dark: #dc2626;
    --danger-light: #fee2e2;
    --info: #06b6d4;
    --bg-page: #f0fdf4;
    --bg-card: #ffffff;
    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;
    --border-color: #e2e8f0;
    --border-light: #f1f5f9;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.07), 0 2px 4px -2px rgb(0 0 0 / 0.05);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.08), 0 4px 6px -4px rgb(0 0 0 / 0.04);
    --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.05);
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 20px;
    --transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

* { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--bg-page);
    color: var(--text-primary);
    padding-top: 80px;
    line-height: 1.6;
    overflow-x: hidden;
}

/* Particles */
.particles-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; overflow: hidden; }
.particle { position: absolute; border-radius: 50%; opacity: 0.12; animation: floatParticle 20s infinite ease-in-out; }
.particle:nth-child(1) { width: 300px; height: 300px; background: var(--primary); top: -100px; left: -100px; animation-delay: 0s; }
.particle:nth-child(2) { width: 200px; height: 200px; background: var(--blue); top: 50%; right: -50px; animation-delay: -5s; }
.particle:nth-child(3) { width: 150px; height: 150px; background: var(--secondary); bottom: 10%; left: 20%; animation-delay: -10s; }
.particle:nth-child(4) { width: 100px; height: 100px; background: var(--warning); top: 30%; left: 60%; animation-delay: -15s; }
@keyframes floatParticle {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    25% { transform: translate(30px, -30px) rotate(90deg); }
    50% { transform: translate(-20px, 20px) rotate(180deg); }
    75% { transform: translate(15px, -15px) rotate(270deg); }
}

/* Toast */
.toast-container {
    position: fixed; top: 100px; right: 24px; z-index: 10000;
    display: flex; flex-direction: column; gap: 12px; pointer-events: none;
}
.toast {
    min-width: 320px; max-width: 450px; pointer-events: auto;
    padding: 14px 20px; border-radius: var(--radius-md);
    color: #fff; font-weight: 600; font-size: 0.9rem;
    display: flex; align-items: center; gap: 10px;
    box-shadow: var(--shadow-lg);
    animation: slideInToast 0.3s ease-out, fadeOutToast 0.4s 3s ease-in forwards;
}
.toast.success { background: linear-gradient(135deg, #10b981, #059669); }
.toast.error   { background: linear-gradient(135deg, #ef4444, #dc2626); }
.toast.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
.toast.info    { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.toast i { font-size: 1.2rem; }
@keyframes slideInToast { from { transform: translateX(110%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes fadeOutToast  { to { opacity: 0; transform: translateX(40px); } }

/* Layout */
.main-container { max-width: 1400px; margin: 0 auto; padding: 24px 32px 60px; position: relative; z-index: 1; }

/* Page Header */
.page-header {
    margin-bottom: 32px; display: flex; align-items: center;
    justify-content: space-between; flex-wrap: wrap; gap: 16px;
}
.page-header-left { display: flex; align-items: center; gap: 16px; }
.page-icon {
    width: 56px; height: 56px; border-radius: var(--radius-lg);
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.4rem;
    box-shadow: 0 8px 20px var(--primary-glow);
}
.page-header h1 { font-size: 1.8rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.025em; }
.page-header .subtitle { color: var(--text-secondary); font-size: 0.95rem; margin-top: 2px; }

/* Stats row */
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 28px; }
.stat-card {
    background: var(--bg-card); border-radius: var(--radius-lg);
    padding: 20px 24px; display: flex; align-items: center; gap: 16px;
    box-shadow: var(--shadow-sm); border: 1px solid var(--border-light);
    transition: all var(--transition);
}
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.stat-icon {
    width: 48px; height: 48px; border-radius: var(--radius-md);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.stat-icon.green  { background: var(--primary-light); color: var(--primary-dark); }
.stat-icon.blue   { background: var(--blue-light); color: var(--blue-dark); }
.stat-icon.purple { background: var(--secondary-light); color: var(--secondary); }
.stat-icon.orange { background: var(--warning-light); color: var(--warning); }
.stat-value { font-size: 1.5rem; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
.stat-label { font-size: 0.8rem; color: var(--text-secondary); font-weight: 500; }

/* Buttons */
.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 28px; border-radius: var(--radius-md); font-weight: 700;
    font-size: 0.9rem; font-family: 'Inter', sans-serif;
    cursor: pointer; border: none; transition: all var(--transition);
}
.btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }
.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff; box-shadow: 0 4px 12px var(--primary-glow);
}
.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px var(--primary-glow); }
.btn-sm { padding: 8px 18px; font-size: 0.85rem; font-weight: 600; }
.btn-outline {
    background: transparent; color: var(--text-secondary);
    border: 1.5px solid var(--border-color);
}
.btn-outline:hover { background: var(--border-light); color: var(--text-primary); border-color: var(--text-muted); }
.btn-blue {
    background: linear-gradient(135deg, var(--blue), var(--blue-dark));
    color: #fff; box-shadow: 0 4px 12px rgba(59,130,246,0.3);
}
.btn-blue:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(59,130,246,0.4); }

/* Card */
.card {
    background: var(--bg-card); border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md); border: 1px solid var(--border-light);
    overflow: hidden;
}
.card-header {
    padding: 20px 28px; display: flex; align-items: center;
    justify-content: space-between; gap: 16px;
    border-bottom: 1px solid var(--border-light);
    background: linear-gradient(135deg, #f8fdf9, #f0fdf4);
}
.card-header-title {
    font-size: 1rem; font-weight: 700; color: var(--text-primary);
    display: flex; align-items: center; gap: 10px;
}
.card-header-title i { color: var(--primary); font-size: 0.95rem; }

/* Search within card header */
.search-box {
    position: relative; width: 280px;
}
.search-box input {
    width: 100%; padding: 9px 14px 9px 38px;
    border: 1.5px solid var(--border-color); border-radius: var(--radius-md);
    font-size: 0.85rem; font-family: 'Inter', sans-serif;
    background: #fff; color: var(--text-primary); outline: none;
    transition: all var(--transition);
}
.search-box input:focus {
    border-color: var(--primary); box-shadow: 0 0 0 3px rgba(34,197,94,0.12);
}
.search-box input::placeholder { color: var(--text-muted); }
.search-box i {
    position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); font-size: 0.85rem; pointer-events: none;
}

/* Table */
.table-wrapper { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table thead th {
    padding: 14px 20px; text-align: left; font-weight: 700;
    font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em;
    color: var(--text-muted); background: #fafcfa; border-bottom: 1px solid var(--border-light);
    white-space: nowrap;
}
.data-table tbody td {
    padding: 16px 20px; border-bottom: 1px solid var(--border-light);
    font-size: 0.9rem; vertical-align: middle; color: var(--text-primary);
}
.data-table tbody tr { transition: background var(--transition); }
.data-table tbody tr:hover { background: #f0fdf4; }
.data-table tbody tr:last-child td { border-bottom: none; }

/* User cell */
.user-cell { display: flex; align-items: center; gap: 12px; }
.user-avatar {
    width: 40px; height: 40px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.85rem; color: #fff; flex-shrink: 0;
    text-transform: uppercase;
}
.user-cell-info { line-height: 1.3; }
.user-cell-name { font-weight: 600; font-size: 0.9rem; }
.user-cell-username { font-size: 0.78rem; color: var(--text-muted); }

/* Badges */
.badge {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 0.78rem; font-weight: 600; padding: 4px 14px;
    border-radius: 20px; white-space: nowrap;
}
.badge::before { content: ''; width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.badge-active  { background: var(--primary-light); color: var(--primary-dark); }
.badge-active::before  { background: var(--primary); }
.badge-inactive { background: var(--danger-light); color: var(--danger-dark); }
.badge-inactive::before { background: var(--danger); }

.role-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 0.78rem; font-weight: 600; padding: 4px 12px;
    border-radius: 20px;
}
.role-admin  { background: var(--secondary-light); color: var(--secondary); }
.role-default { background: var(--blue-light); color: var(--blue-dark); }

/* Action buttons in table */
.action-btn {
    width: 36px; height: 36px; border-radius: var(--radius-md);
    display: inline-flex; align-items: center; justify-content: center;
    border: 1.5px solid var(--border-color); background: #fff;
    color: var(--text-secondary); cursor: pointer;
    transition: all var(--transition); font-size: 0.85rem;
}
.action-btn:hover { background: var(--primary-light); color: var(--primary-dark); border-color: var(--primary); }
.action-btn.danger:hover { background: var(--danger-light); color: var(--danger-dark); border-color: var(--danger); }

/* Empty State */
.empty-state {
    padding: 60px 20px; text-align: center;
}
.empty-state i { font-size: 3rem; color: var(--text-muted); margin-bottom: 16px; }
.empty-state h3 { font-weight: 700; font-size: 1.1rem; color: var(--text-secondary); margin-bottom: 6px; }
.empty-state p { font-size: 0.9rem; color: var(--text-muted); }

/* ===================== MODAL ===================== */
.modal-backdrop {
    position: fixed; inset: 0; background: rgba(15,23,42,0.5);
    backdrop-filter: blur(4px); z-index: 9000;
    opacity: 0; visibility: hidden; pointer-events: none;
    transition: all 0.3s ease;
}
.modal-backdrop.show { opacity: 1; visibility: visible; pointer-events: auto; }

.modal-panel {
    position: fixed; top: 50%; left: 50%;
    transform: translate(-50%, -50%) scale(0.92);
    background: var(--bg-card); border-radius: var(--radius-xl);
    box-shadow: var(--shadow-xl); z-index: 9001;
    max-width: 620px; width: 92%; max-height: 90vh; overflow-y: auto;
    opacity: 0; visibility: hidden; pointer-events: none;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-panel.show {
    opacity: 1; visibility: visible; pointer-events: auto;
    transform: translate(-50%, -50%) scale(1);
}

.modal-header {
    padding: 24px 28px; display: flex; align-items: center;
    justify-content: space-between; border-bottom: 1px solid var(--border-light);
}
.modal-header-left { display: flex; align-items: center; gap: 14px; }
.modal-header-icon {
    width: 44px; height: 44px; border-radius: var(--radius-md);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
}
.modal-header-icon.green { background: var(--primary-light); color: var(--primary-dark); }
.modal-header-icon.blue  { background: var(--blue-light); color: var(--blue-dark); }
.modal-title { font-size: 1.15rem; font-weight: 700; }
.modal-subtitle { font-size: 0.82rem; color: var(--text-secondary); }
.modal-close {
    width: 36px; height: 36px; border-radius: 50%; border: none;
    background: var(--border-light); color: var(--text-muted);
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; transition: all var(--transition);
}
.modal-close:hover { background: var(--danger-light); color: var(--danger); }

.modal-body { padding: 28px; }

/* Form in modal */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 24px; }
.form-grid .full-width { grid-column: 1 / -1; }
.input-group { margin-bottom: 0; }
.input-label {
    display: block; font-weight: 600; font-size: 0.78rem;
    color: var(--text-secondary); margin-bottom: 7px;
    text-transform: uppercase; letter-spacing: 0.04em;
}
.input-wrapper { position: relative; }
.input-wrapper .input-icon {
    position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); font-size: 0.9rem; pointer-events: none;
    transition: color var(--transition); width: 20px; text-align: center;
}
.input-wrapper input,
.input-wrapper select {
    width: 100%; padding: 12px 14px 12px 52px;
    border: 1.5px solid var(--border-color); border-radius: var(--radius-md);
    font-size: 0.9rem; font-family: 'Inter', sans-serif;
    color: var(--text-primary); background: #f8fafc;
    outline: none; transition: all var(--transition);
}
.input-wrapper select { padding-left: 52px; cursor: pointer; }
.input-wrapper input:focus,
.input-wrapper select:focus {
    border-color: var(--primary); background: #fff;
    box-shadow: 0 0 0 3px rgba(34,197,94,0.12);
}
.input-wrapper input:focus ~ .input-icon { color: var(--primary); }
.input-wrapper input::placeholder { color: var(--text-muted); }

/* Checkbox */
.checkbox-row { display: flex; align-items: center; gap: 10px; padding: 6px 0; }
.checkbox-row input[type="checkbox"] {
    width: 20px; height: 20px; accent-color: var(--primary);
    cursor: pointer; border-radius: 4px;
}
.checkbox-row label {
    font-weight: 600; font-size: 0.9rem; color: var(--text-primary); cursor: pointer;
}

.modal-footer {
    padding: 20px 28px; border-top: 1px solid var(--border-light);
    display: flex; justify-content: flex-end; gap: 12px;
}

/* Section divider */
.section-divider {
    display: flex; align-items: center; gap: 12px;
    margin: 20px 0 16px; color: var(--text-muted); font-size: 0.78rem;
    font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em;
}
.section-divider::after { content: ''; flex: 1; height: 1px; background: var(--border-light); }

/* Access Denied */
.access-denied-card {
    max-width: 540px; margin: 60px auto; text-align: center;
    background: var(--bg-card); padding: 48px 40px; border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg); border: 1px solid var(--border-light);
}
.access-denied-icon {
    width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 24px;
    background: var(--warning-light); color: var(--warning);
    display: flex; align-items: center; justify-content: center; font-size: 2rem;
}
.access-denied-card h3 { font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
.access-denied-card p { color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; }

/* Responsive */
@media (max-width: 768px) {
    .main-container { padding: 16px; }
    .page-header h1 { font-size: 1.4rem; }
    .stats-row { grid-template-columns: 1fr 1fr; }
    .form-grid { grid-template-columns: 1fr; }
    .card-header { flex-direction: column; align-items: stretch; }
    .search-box { width: 100%; }
    .data-table thead th, .data-table tbody td { padding: 12px 14px; }
}
@media (max-width: 480px) {
    .stats-row { grid-template-columns: 1fr; }
}
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="particles-container">
        <div class="particle"></div><div class="particle"></div>
        <div class="particle"></div><div class="particle"></div>
    </div>
    <div class="toast-container" id="toastContainer"></div>

    <main class="main-container">
        <?php if (!empty($access_denied_html)): ?>
            <?php echo $access_denied_html; ?>
        <?php else: ?>

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-left">
                <div class="page-icon"><i class="fas fa-users-gear"></i></div>
                <div>
                    <h1>Gestione Utenti</h1>
                    <p class="subtitle">Gestisci gli account e i permessi del team</p>
                </div>
            </div>
            <button class="btn btn-primary" onclick="openModal()">
                <i class="fas fa-user-plus"></i> Aggiungi Utente
            </button>
        </div>

        <!-- Stats -->
        <?php
            $total = count($utenti);
            $active = count(array_filter($utenti, function($u){ return $u['attivo'] == 1; }));
            $admins = count(array_filter($utenti, function($u){ return $u['ruolo'] === 'Amministratore'; }));
            $inactive = $total - $active;
        ?>
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-users"></i></div>
                <div><div class="stat-value"><?php echo $total; ?></div><div class="stat-label">Utenti Totali</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-circle-check"></i></div>
                <div><div class="stat-value"><?php echo $active; ?></div><div class="stat-label">Attivi</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-user-shield"></i></div>
                <div><div class="stat-value"><?php echo $admins; ?></div><div class="stat-label">Amministratori</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-user-slash"></i></div>
                <div><div class="stat-value"><?php echo $inactive; ?></div><div class="stat-label">Disattivati</div></div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="fas fa-list"></i> Elenco Utenti</span>
                <div class="search-box">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Cerca utente..." oninput="filterTable()">
                </div>
            </div>
            <div class="table-wrapper">
                <table class="data-table" id="userTable">
                    <thead>
                        <tr>
                            <th>Utente</th>
                            <th>Email</th>
                            <th>Ruolo</th>
                            <th>Stato</th>
                            <th style="text-align:center;width:80px">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($utenti) === 0): ?>
                        <tr><td colspan="5">
                            <div class="empty-state">
                                <i class="fas fa-user-group"></i>
                                <h3>Nessun utente trovato</h3>
                                <p>Clicca "Aggiungi Utente" per creare il primo account.</p>
                            </div>
                        </td></tr>
                        <?php else: ?>
                        <?php
                            $avatarColors = ['#22c55e','#3b82f6','#8b5cf6','#f59e0b','#ef4444','#06b6d4','#ec4899','#f97316'];
                            foreach ($utenti as $i => $user):
                                $color = $avatarColors[$i % count($avatarColors)];
                                $initials = mb_strtoupper(mb_substr($user['nome'], 0, 1));
                                if (strpos($user['nome'], ' ') !== false) {
                                    $parts = explode(' ', $user['nome']);
                                    $initials = mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
                                }
                                $isAdmin = $user['ruolo'] === 'Amministratore';
                        ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar" style="background:<?php echo $color; ?>"><?php echo htmlspecialchars($initials); ?></div>
                                    <div class="user-cell-info">
                                        <div class="user-cell-name"><?php echo htmlspecialchars($user['nome']); ?></div>
                                        <div class="user-cell-username">@<?php echo htmlspecialchars($user['nome_utente']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge <?php echo $isAdmin ? 'role-admin' : 'role-default'; ?>">
                                    <i class="fas <?php echo $isAdmin ? 'fa-crown' : 'fa-user'; ?>" style="font-size:0.7rem"></i>
                                    <?php echo htmlspecialchars($user['ruolo']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $user['attivo'] ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $user['attivo'] ? 'Attivo' : 'Disattivo'; ?>
                                </span>
                            </td>
                            <td style="text-align:center">
                                <button class="action-btn" onclick="openModal(<?php echo $user['id']; ?>)" title="Modifica">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>
    </main>

    <!-- ==================== MODAL ==================== -->
    <div class="modal-backdrop" id="modalBackdrop"></div>
    <div class="modal-panel" id="modalPanel">
        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-header-icon green" id="modalIcon"><i class="fas fa-user-plus"></i></div>
                <div>
                    <div class="modal-title" id="modalTitle">Nuovo Utente</div>
                    <div class="modal-subtitle" id="modalSubtitle">Compila i dati per creare un account</div>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <form id="userForm">
            <input type="hidden" name="action" id="form_action" value="create">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="modal-body">
                <div class="section-divider">Dati Personali</div>
                <div class="form-grid">
                    <div class="input-group">
                        <label class="input-label" for="nome_utente">Nome Utente</label>
                        <div class="input-wrapper">
                            <input type="text" id="nome_utente" name="nome_utente" placeholder="Username" required>
                            <i class="fas fa-at input-icon"></i>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="nome">Nome Completo</label>
                        <div class="input-wrapper">
                            <input type="text" id="nome" name="nome" placeholder="Nome e Cognome" required>
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>
                    <div class="input-group full-width">
                        <label class="input-label" for="email">Email</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" placeholder="email@esempio.it" required>
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                    </div>
                    <div class="input-group full-width">
                        <label class="input-label" for="ruolo">Ruolo</label>
                        <div class="input-wrapper">
                            <select id="ruolo" name="ruolo" required>
                                <option value="" disabled selected>Seleziona ruolo...</option>
                                <option value="Amministratore">Amministratore</option>
                                <option value="Cassiere">Cassiere</option>
                                <option value="Magazziniere">Magazziniere</option>
                                <option value="Contabile">Contabile</option>
                                <option value="Dipendente">Dipendente</option>
                            </select>
                            <i class="fas fa-user-tag input-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="section-divider">Sicurezza</div>
                <div class="form-grid">
                    <div class="input-group">
                        <label class="input-label" for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" placeholder="Minimo 8 caratteri" autocomplete="new-password">
                            <i class="fas fa-lock input-icon"></i>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="conferma_password">Conferma Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="conferma_password" name="conferma_password" placeholder="Ripeti password" autocomplete="new-password">
                            <i class="fas fa-lock input-icon"></i>
                        </div>
                    </div>
                </div>

                <div style="margin-top:20px;">
                    <div class="checkbox-row">
                        <input type="checkbox" id="attivo" name="attivo" checked>
                        <label for="attivo">Account Attivo</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Annulla</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-floppy-disk"></i> <span>Salva Utente</span>
                </button>
            </div>
        </form>
    </div>

<script>
// ========== TOAST ==========
function showNotification(message, type) {
    if (!['success','error','warning','info'].includes(type)) type = 'success';
    var container = document.getElementById('toastContainer');
    var toast = document.createElement('div');
    toast.className = 'toast ' + type;
    var icons = { success:'fa-circle-check', error:'fa-circle-xmark', warning:'fa-triangle-exclamation', info:'fa-circle-info' };
    toast.innerHTML = '<i class="fas '+(icons[type]||icons.success)+'"></i> '+message;
    container.appendChild(toast);
    setTimeout(function() { if(toast.parentNode) toast.remove(); }, 3500);
}

// ========== MODAL ==========
var backdrop = document.getElementById('modalBackdrop');
var panel = document.getElementById('modalPanel');
var form = document.getElementById('userForm');

function openModalUI() {
    backdrop.classList.add('show');
    panel.classList.add('show');
}
function closeModal() {
    backdrop.classList.remove('show');
    panel.classList.remove('show');
}
backdrop.addEventListener('click', closeModal);

async function openModal(userId) {
    form.reset();
    document.getElementById('attivo').checked = true;

    if (userId) {
        document.getElementById('modalTitle').textContent = 'Modifica Utente';
        document.getElementById('modalSubtitle').textContent = 'Aggiorna i dati dell\'account';
        document.getElementById('modalIcon').innerHTML = '<i class="fas fa-pen-to-square"></i>';
        document.getElementById('modalIcon').className = 'modal-header-icon blue';
        document.getElementById('form_action').value = 'edit';
        document.getElementById('edit_user_id').value = userId;

        try {
            var response = await fetch('gestione_utenti.php?action=get_user&id=' + userId);
            var result = await response.json();
            if (result.success) {
                var user = result.data;
                document.getElementById('nome_utente').value = user.nome_utente;
                document.getElementById('nome').value = user.nome;
                document.getElementById('email').value = user.email;
                document.getElementById('ruolo').value = user.ruolo;
                document.getElementById('attivo').checked = (user.attivo == 1);
            } else {
                showNotification(result.message, 'error');
                return;
            }
        } catch (e) {
            showNotification('Errore nel caricamento dei dati.', 'error');
            return;
        }
    } else {
        document.getElementById('modalTitle').textContent = 'Nuovo Utente';
        document.getElementById('modalSubtitle').textContent = 'Compila i dati per creare un account';
        document.getElementById('modalIcon').innerHTML = '<i class="fas fa-user-plus"></i>';
        document.getElementById('modalIcon').className = 'modal-header-icon green';
        document.getElementById('form_action').value = 'create';
        document.getElementById('edit_user_id').value = '';
    }
    openModalUI();
}

// ========== FORM SUBMIT ==========
form.addEventListener('submit', async function(e) {
    e.preventDefault();
    var submitBtn = document.getElementById('submitBtn');
    var span = submitBtn.querySelector('span');
    var icon = submitBtn.querySelector('i');
    submitBtn.disabled = true;
    span.textContent = 'Salvataggio...';
    icon.className = 'fas fa-spinner fa-spin';

    try {
        var response = await fetch('gestione_utenti.php', { method: 'POST', body: new FormData(form) });
        var result = await response.json();
        if (result.success) {
            showNotification(result.message, 'success');
            closeModal();
            setTimeout(function() { window.location.reload(); }, 1200);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (e) {
        showNotification('Errore di comunicazione con il server.', 'error');
    } finally {
        submitBtn.disabled = false;
        span.textContent = 'Salva Utente';
        icon.className = 'fas fa-floppy-disk';
    }
});

// ========== SEARCH / FILTER ==========
function filterTable() {
    var query = document.getElementById('searchInput').value.toLowerCase();
    var rows = document.querySelectorAll('#userTable tbody tr');
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}
</script>
</body>
</html>