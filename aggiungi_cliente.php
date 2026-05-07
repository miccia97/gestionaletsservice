<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $indirizzo = trim($_POST['indirizzo'] ?? '');
    $citta = trim($_POST['citta'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $ragione_sociale = trim($_POST['ragione_sociale'] ?? '');
    $partita_iva = trim($_POST['partita_iva'] ?? '');
    $indirizzo_azienda = trim($_POST['indirizzo_azienda'] ?? '');
    $citta_azienda = trim($_POST['citta_azienda'] ?? '');
    $telefono_azienda = trim($_POST['telefono_azienda'] ?? '');
    $email_azienda = trim($_POST['email_azienda'] ?? '');
    $note_azienda = trim($_POST['note_azienda'] ?? '');

    if (empty($nome) || empty($cognome)) {
        $error = 'Nome e Cognome sono obbligatori.';
    } else {
        $stmt = $conn->prepare("INSERT INTO clienti_nuovo (nome, cognome, email, telefono, indirizzo, citta, note, partita_iva, ragione_sociale, indirizzo_azienda, citta_azienda, telefono_azienda, email_azienda, note_azienda) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssss", $nome, $cognome, $email, $telefono, $indirizzo, $citta, $note, $partita_iva, $ragione_sociale, $indirizzo_azienda, $citta_azienda, $telefono_azienda, $email_azienda, $note_azienda);
        if ($stmt->execute()) {
            $success = "Cliente \"$nome $cognome\" aggiunto con successo!";
            // Reset POST per pulire il form
            $_POST = [];
        } else {
            $error = "Errore: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <title>Aggiungi Cliente | TS Service</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/header-styles.css?v=2">
    <style>
/* ======================================= */
/*        PREMIUM DESIGN SYSTEM            */
/* ======================================= */
:root {
    --primary: #22c55e;
    --primary-dark: #16a34a;
    --primary-light: #dcfce7;
    --primary-glow: rgba(34, 197, 94, 0.4);
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
    animation: slideInToast 0.3s ease-out, fadeOutToast 0.4s 4s ease-in forwards;
}
.toast.success { background: linear-gradient(135deg, #10b981, #059669); }
.toast.error { background: linear-gradient(135deg, #ef4444, #dc2626); }
.toast i { font-size: 1.2rem; }
.toast a { color: #fff; text-decoration: underline; margin-left: auto; font-size: 0.85rem; opacity: 0.9; }
.toast a:hover { opacity: 1; }
@keyframes slideInToast { from { transform: translateX(110%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes fadeOutToast { to { opacity: 0; transform: translateX(40px); } }

/* Layout */
.main-container { max-width: 900px; margin: 0 auto; padding: 24px 32px 60px; position: relative; z-index: 1; }

/* Page Header */
.page-header {
    margin-bottom: 32px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 16px;
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
.page-header-right { display: flex; gap: 10px; }

/* Card */
.card {
    background: var(--bg-card); border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md); border: 1px solid var(--border-light);
    overflow: hidden;
}

/* Tab Navigation */
.tab-nav {
    display: flex; border-bottom: 1px solid var(--border-light);
    padding: 0; background: linear-gradient(135deg, #f8fdf9, #f0fdf4);
    overflow-x: auto;
}
.tab-nav .tab-button {
    flex: 1; padding: 18px 28px; cursor: pointer; border: none; background: none;
    font-weight: 600; font-size: 0.9rem; color: var(--text-muted);
    border-bottom: 3px solid transparent; margin-bottom: -1px;
    transition: all var(--transition);
    display: flex; align-items: center; justify-content: center; gap: 10px; white-space: nowrap;
    font-family: 'Inter', sans-serif;
}
.tab-nav .tab-button i { font-size: 1rem; width: 20px; text-align: center; }
.tab-nav .tab-button:hover { color: var(--primary-dark); background: rgba(34, 197, 94, 0.04); }
.tab-nav .tab-button.active {
    color: var(--primary-dark); border-color: var(--primary);
    background: rgba(34, 197, 94, 0.06);
}

/* Tab panel */
.tab-panel { display: none; animation: fadePanel 0.4s ease; }
.tab-panel.active { display: block; }
@keyframes fadePanel { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

.tab-body { padding: 36px 40px; }

/* Section Header */
.section-header { margin-bottom: 28px; }
.section-header h2 {
    font-size: 1.25rem; font-weight: 800; color: var(--text-primary);
    display: flex; align-items: center; gap: 12px;
}
.section-header h2 .header-icon {
    width: 40px; height: 40px; border-radius: var(--radius-md);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem;
}
.section-header h2 .header-icon.green { background: var(--primary-light); color: var(--primary-dark); }
.section-header h2 .header-icon.blue { background: var(--blue-light); color: var(--blue-dark); }
.section-header h2 .header-icon.purple { background: var(--secondary-light); color: var(--secondary); }
.section-header p {
    color: var(--text-secondary); font-size: 0.9rem; margin-top: 8px;
    max-width: 65ch; line-height: 1.5;
}

/* Form Grid */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px 28px; }
.form-grid .full-width { grid-column: 1 / -1; }

/* Input Group */
.input-group { margin-bottom: 0; }
.input-label {
    display: block; font-weight: 600; font-size: 0.8rem;
    color: var(--text-secondary); margin-bottom: 8px;
    text-transform: uppercase; letter-spacing: 0.04em;
}
.input-label .req { color: var(--danger); font-weight: 700; }
.input-wrapper { position: relative; }
.input-wrapper .input-icon {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); font-size: 0.9rem; pointer-events: none;
    transition: color var(--transition);
}
.input-wrapper.textarea-wrap .input-icon {
    top: 16px; transform: none;
}
.input-wrapper input,
.input-wrapper textarea {
    width: 100%; padding: 12px 16px 12px 44px;
    border: 1.5px solid var(--border-color); border-radius: var(--radius-md);
    font-size: 0.9rem; font-family: 'Inter', sans-serif;
    color: var(--text-primary); background: #f8fafc;
    outline: none; transition: all var(--transition);
}
.input-wrapper textarea {
    resize: vertical; min-height: 80px; padding-top: 14px;
}
.input-wrapper input:focus,
.input-wrapper textarea:focus {
    border-color: var(--primary); background: #fff;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12);
}
.input-wrapper input:focus ~ .input-icon,
.input-wrapper textarea:focus ~ .input-icon { color: var(--primary); }
.input-wrapper input::placeholder,
.input-wrapper textarea::placeholder { color: var(--text-muted); }

/* Divider */
.form-divider {
    grid-column: 1 / -1;
    display: flex; align-items: center; gap: 14px;
    margin: 8px 0;
}
.form-divider span {
    font-size: 0.72rem; font-weight: 700; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.08em; white-space: nowrap;
}
.form-divider::after {
    content: ''; flex: 1; height: 1px; background: var(--border-color);
}

/* Section Footer */
.section-footer {
    border-top: 1px solid var(--border-light);
    padding-top: 24px; margin-top: 32px;
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
}

/* Buttons */
.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 28px; border-radius: var(--radius-md); font-weight: 700;
    font-size: 0.9rem; font-family: 'Inter', sans-serif;
    cursor: pointer; border: none; transition: all var(--transition);
    text-decoration: none;
}
.btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }
.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff; box-shadow: 0 4px 12px var(--primary-glow);
}
.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px var(--primary-glow); }
.btn-outline {
    background: transparent; color: var(--text-secondary);
    border: 1.5px solid var(--border-color);
}
.btn-outline:hover { background: var(--border-light); color: var(--text-primary); border-color: var(--text-muted); }
.btn-ghost {
    background: transparent; color: var(--text-secondary); border: none; padding: 12px 16px;
}
.btn-ghost:hover { color: var(--text-primary); background: rgba(0,0,0,0.04); border-radius: var(--radius-md); }

/* Quick stats */
.quick-stats {
    display: flex; gap: 12px; margin-bottom: 24px;
}
.stat-chip {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 18px; border-radius: var(--radius-md);
    background: var(--bg-card); border: 1px solid var(--border-light);
    box-shadow: var(--shadow-sm); font-size: 0.85rem; color: var(--text-secondary);
}
.stat-chip i { font-size: 0.9rem; }
.stat-chip strong { color: var(--text-primary); font-weight: 700; }

/* Responsive */
@media (max-width: 768px) {
    .main-container { padding: 16px; }
    .page-header h1 { font-size: 1.4rem; }
    .tab-body { padding: 24px 20px; }
    .tab-nav .tab-button { padding: 14px 18px; font-size: 0.85rem; }
    .form-grid { grid-template-columns: 1fr; }
    .section-footer { flex-direction: column; }
    .section-footer .btn { width: 100%; }
    .quick-stats { flex-wrap: wrap; }
    .page-header-right { width: 100%; }
    .page-header-right .btn { flex: 1; }
}
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="particles-container">
        <div class="particle"></div><div class="particle"></div>
        <div class="particle"></div><div class="particle"></div>
    </div>

    <div class="toast-container" id="toastContainer">
        <?php if (!empty($success)): ?>
            <div class="toast success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?> <a href="clienti.php">Vai alla lista</a></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="toast error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
    </div>

    <main class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-left">
                <div class="page-icon"><i class="fas fa-user-plus"></i></div>
                <div>
                    <h1>Nuovo Cliente</h1>
                    <p class="subtitle">Registra un nuovo cliente nell'anagrafica</p>
                </div>
            </div>
            <div class="page-header-right">
                <a href="clienti.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Lista Clienti</a>
            </div>
        </div>

        <!-- Form Card -->
        <form method="POST" action="" id="clientForm">
            <div class="card">
                <nav class="tab-nav">
                    <button type="button" data-tab="personal" class="tab-button active"><i class="fas fa-user"></i> Dati Personali</button>
                    <button type="button" data-tab="company" class="tab-button"><i class="fas fa-building"></i> Dati Aziendali</button>
                </nav>

                <!-- ==================== TAB: DATI PERSONALI ==================== -->
                <div id="personal-panel" class="tab-panel active">
                    <div class="tab-body">
                        <div class="section-header">
                            <h2>
                                <span class="header-icon green"><i class="fas fa-user"></i></span>
                                Informazioni Personali
                            </h2>
                            <p>Inserisci i dati anagrafici e i contatti personali del cliente.</p>
                        </div>

                        <div class="form-grid">
                            <div class="input-group">
                                <label class="input-label" for="nome">Nome <span class="req">*</span></label>
                                <div class="input-wrapper">
                                    <input type="text" id="nome" name="nome" placeholder="Mario" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                                    <i class="fas fa-user input-icon"></i>
                                </div>
                            </div>
                            <div class="input-group">
                                <label class="input-label" for="cognome">Cognome <span class="req">*</span></label>
                                <div class="input-wrapper">
                                    <input type="text" id="cognome" name="cognome" placeholder="Rossi" required value="<?= htmlspecialchars($_POST['cognome'] ?? '') ?>">
                                    <i class="fas fa-user input-icon"></i>
                                </div>
                            </div>

                            <div class="form-divider"><span>Contatti</span></div>

                            <div class="input-group">
                                <label class="input-label" for="telefono">Telefono</label>
                                <div class="input-wrapper">
                                    <input type="tel" id="telefono" name="telefono" placeholder="333 123 4567" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                                    <i class="fas fa-phone input-icon"></i>
                                </div>
                            </div>
                            <div class="input-group">
                                <label class="input-label" for="email">Email</label>
                                <div class="input-wrapper">
                                    <input type="email" id="email" name="email" placeholder="mario@esempio.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                    <i class="fas fa-envelope input-icon"></i>
                                </div>
                            </div>

                            <div class="form-divider"><span>Indirizzo</span></div>

                            <div class="input-group">
                                <label class="input-label" for="indirizzo">Indirizzo</label>
                                <div class="input-wrapper">
                                    <input type="text" id="indirizzo" name="indirizzo" placeholder="Via Roma, 1" value="<?= htmlspecialchars($_POST['indirizzo'] ?? '') ?>">
                                    <i class="fas fa-location-dot input-icon"></i>
                                </div>
                            </div>
                            <div class="input-group">
                                <label class="input-label" for="citta">Città</label>
                                <div class="input-wrapper">
                                    <input type="text" id="citta" name="citta" placeholder="Roma" value="<?= htmlspecialchars($_POST['citta'] ?? '') ?>">
                                    <i class="fas fa-city input-icon"></i>
                                </div>
                            </div>

                            <div class="input-group full-width">
                                <label class="input-label" for="note">Note</label>
                                <div class="input-wrapper textarea-wrap">
                                    <textarea id="note" name="note" placeholder="Annotazioni sul cliente..." rows="3"><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
                                    <i class="fas fa-sticky-note input-icon"></i>
                                </div>
                            </div>
                        </div>

                        <div class="section-footer">
                            <button type="reset" class="btn btn-outline" onclick="switchTab('personal')"><i class="fas fa-rotate-left"></i> Reset</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Salva Cliente</button>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB: DATI AZIENDALI ==================== -->
                <div id="company-panel" class="tab-panel">
                    <div class="tab-body">
                        <div class="section-header">
                            <h2>
                                <span class="header-icon blue"><i class="fas fa-building"></i></span>
                                Informazioni Aziendali
                            </h2>
                            <p>Se il cliente è un'azienda, compila i dati societari e i contatti aziendali.</p>
                        </div>

                        <div class="form-grid">
                            <div class="input-group">
                                <label class="input-label" for="ragione_sociale">Ragione Sociale</label>
                                <div class="input-wrapper">
                                    <input type="text" id="ragione_sociale" name="ragione_sociale" placeholder="Azienda S.r.l." value="<?= htmlspecialchars($_POST['ragione_sociale'] ?? '') ?>">
                                    <i class="fas fa-building input-icon"></i>
                                </div>
                            </div>
                            <div class="input-group">
                                <label class="input-label" for="partita_iva">Partita IVA</label>
                                <div class="input-wrapper">
                                    <input type="text" id="partita_iva" name="partita_iva" placeholder="IT12345678901" value="<?= htmlspecialchars($_POST['partita_iva'] ?? '') ?>">
                                    <i class="fas fa-id-card input-icon"></i>
                                </div>
                            </div>

                            <div class="form-divider"><span>Contatti Aziendali</span></div>

                            <div class="input-group">
                                <label class="input-label" for="telefono_azienda">Telefono Azienda</label>
                                <div class="input-wrapper">
                                    <input type="tel" id="telefono_azienda" name="telefono_azienda" placeholder="02 1234567" value="<?= htmlspecialchars($_POST['telefono_azienda'] ?? '') ?>">
                                    <i class="fas fa-phone input-icon"></i>
                                </div>
                            </div>
                            <div class="input-group">
                                <label class="input-label" for="email_azienda">Email Azienda</label>
                                <div class="input-wrapper">
                                    <input type="email" id="email_azienda" name="email_azienda" placeholder="info@azienda.com" value="<?= htmlspecialchars($_POST['email_azienda'] ?? '') ?>">
                                    <i class="fas fa-envelope input-icon"></i>
                                </div>
                            </div>

                            <div class="form-divider"><span>Sede Aziendale</span></div>

                            <div class="input-group">
                                <label class="input-label" for="indirizzo_azienda">Indirizzo Azienda</label>
                                <div class="input-wrapper">
                                    <input type="text" id="indirizzo_azienda" name="indirizzo_azienda" placeholder="Via dell'Industria, 5" value="<?= htmlspecialchars($_POST['indirizzo_azienda'] ?? '') ?>">
                                    <i class="fas fa-location-dot input-icon"></i>
                                </div>
                            </div>
                            <div class="input-group">
                                <label class="input-label" for="citta_azienda">Città Azienda</label>
                                <div class="input-wrapper">
                                    <input type="text" id="citta_azienda" name="citta_azienda" placeholder="Milano" value="<?= htmlspecialchars($_POST['citta_azienda'] ?? '') ?>">
                                    <i class="fas fa-city input-icon"></i>
                                </div>
                            </div>

                            <div class="input-group full-width">
                                <label class="input-label" for="note_azienda">Note Azienda</label>
                                <div class="input-wrapper textarea-wrap">
                                    <textarea id="note_azienda" name="note_azienda" placeholder="Annotazioni sull'azienda..." rows="3"><?= htmlspecialchars($_POST['note_azienda'] ?? '') ?></textarea>
                                    <i class="fas fa-sticky-note input-icon"></i>
                                </div>
                            </div>
                        </div>

                        <div class="section-footer">
                            <button type="reset" class="btn btn-outline" onclick="switchTab('personal')"><i class="fas fa-rotate-left"></i> Reset</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Salva Cliente</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <script>
    // Tab switching
    function switchTab(tabId) {
        document.querySelectorAll('.tab-nav .tab-button').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelector(`.tab-nav .tab-button[data-tab="${tabId}"]`).classList.add('active');
        document.getElementById(tabId + '-panel').classList.add('active');
    }
    document.querySelectorAll('.tab-nav .tab-button').forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });

    // Auto-dismiss toasts
    document.querySelectorAll('.toast').forEach(toast => {
        setTimeout(() => toast.remove(), 5000);
    });
    </script>
</body>
</html>
