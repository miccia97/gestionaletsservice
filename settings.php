<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impostazioni | TS Service</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=1">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
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
    --info: #06b6d4;
    --info-light: #ecfeff;
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
.toast.error { background: linear-gradient(135deg, #ef4444, #dc2626); }
.toast.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
.toast.info { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.toast i { font-size: 1.2rem; }
@keyframes slideInToast { from { transform: translateX(110%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes fadeOutToast { to { opacity: 0; transform: translateX(40px); } }

/* Layout */
.main-container { max-width: 1100px; margin: 0 auto; padding: 24px 32px 60px; position: relative; z-index: 1; }

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
.tab-button {
    padding: 18px 28px; cursor: pointer; border: none; background: none;
    font-weight: 600; font-size: 0.9rem; color: var(--text-muted);
    border-bottom: 3px solid transparent; margin-bottom: -1px;
    transition: all var(--transition);
    display: flex; align-items: center; gap: 10px; white-space: nowrap;
    font-family: 'Inter', sans-serif;
}
.tab-button i {
    font-size: 1rem; width: 20px; text-align: center;
}
.tab-button:hover { color: var(--primary-dark); background: rgba(34, 197, 94, 0.04); }
.tab-button.active {
    color: var(--primary-dark); border-color: var(--primary);
    background: rgba(34, 197, 94, 0.06);
}

/* Tab panel */
.tab-panel { display: none; animation: fadePanel 0.4s ease; }
.tab-panel.active { display: block; }
@keyframes fadePanel { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

.tab-body { padding: 36px 40px; }

/* Section Header */
.section-header { margin-bottom: 32px; }
.section-header h2 {
    font-size: 1.35rem; font-weight: 800; color: var(--text-primary);
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
.section-header h2 .header-icon.orange { background: var(--warning-light); color: var(--warning); }
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
.input-wrapper { position: relative; }
.input-wrapper .input-icon {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); font-size: 0.9rem; pointer-events: none;
    transition: color var(--transition);
}
.input-wrapper input,
.input-wrapper select {
    width: 100%; padding: 12px 16px 12px 44px;
    border: 1.5px solid var(--border-color); border-radius: var(--radius-md);
    font-size: 0.9rem; font-family: 'Inter', sans-serif;
    color: var(--text-primary); background: #f8fafc;
    outline: none; transition: all var(--transition);
}
.input-wrapper select { padding-left: 16px; }
.input-wrapper input:focus,
.input-wrapper select:focus {
    border-color: var(--primary); background: #fff;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12);
}
.input-wrapper input:focus ~ .input-icon,
.input-wrapper input:focus + .input-icon { color: var(--primary); }
.input-wrapper input::placeholder { color: var(--text-muted); }

/* Setting Row */
.setting-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 24px; border-radius: var(--radius-lg);
    background: var(--border-light); border: 1px solid transparent;
    transition: all var(--transition); gap: 16px;
}
.setting-row:hover { border-color: var(--border-color); background: #f0fdf4; }
.setting-row + .setting-row { margin-top: 16px; }
.setting-row-info { flex: 1; }
.setting-row-info h3 {
    font-weight: 700; font-size: 0.95rem; color: var(--text-primary);
    display: flex; align-items: center; gap: 8px;
}
.setting-row-info h3 i { color: var(--text-muted); font-size: 0.85rem; }
.setting-row-info p { color: var(--text-secondary); font-size: 0.85rem; margin-top: 4px; }

/* Toggle Switch */
.toggle-switch { position: relative; display: inline-block; width: 52px; height: 28px; flex-shrink: 0; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.slider {
    position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
    background: #cbd5e1; transition: 0.3s ease; border-radius: 28px;
}
.slider:before {
    position: absolute; content: ""; height: 22px; width: 22px;
    left: 3px; bottom: 3px; background: white;
    transition: 0.3s ease; border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
input:checked + .slider { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); }
input:checked + .slider:before { transform: translateX(24px); }

/* Section Footer */
.section-footer {
    border-top: 1px solid var(--border-light);
    padding-top: 24px; margin-top: 32px;
    display: flex; justify-content: flex-end; gap: 12px;
}

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
.btn-sm {
    padding: 8px 18px; font-size: 0.85rem; font-weight: 600;
}
.btn-blue {
    background: linear-gradient(135deg, var(--blue), var(--blue-dark));
    color: #fff; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}
.btn-blue:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(59, 130, 246, 0.4); }
.btn-outline {
    background: transparent; color: var(--text-secondary);
    border: 1.5px solid var(--border-color);
}
.btn-outline:hover { background: var(--border-light); color: var(--text-primary); border-color: var(--text-muted); }

/* Danger Zone */
.danger-zone {
    margin-top: 32px; border: 2px solid var(--danger);
    border-radius: var(--radius-lg); overflow: hidden;
}
.danger-zone-header {
    background: var(--danger-light); padding: 16px 24px;
    display: flex; align-items: center; gap: 10px;
    font-weight: 700; font-size: 0.95rem; color: var(--danger-dark);
}
.danger-zone-header i { font-size: 1rem; }
.danger-zone-body { padding: 20px 24px; }
.danger-zone-body .setting-row { background: #fff8f8; }
.danger-zone-body .setting-row:hover { background: var(--danger-light); border-color: rgba(239, 68, 68, 0.2); }
.danger-zone-body .setting-row-info h3 { color: var(--danger-dark); }
.btn-danger {
    background: linear-gradient(135deg, var(--danger), var(--danger-dark));
    color: #fff; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}
.btn-danger:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(239, 68, 68, 0.4); }

/* Confirm Dialog */
.confirm-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px);
    z-index: 9000; opacity: 0; visibility: hidden; pointer-events: none; transition: all 0.3s ease;
}
.confirm-backdrop.show { opacity: 1; visibility: visible; pointer-events: auto; }
.confirm-dialog {
    position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9);
    background: #fff; border-radius: var(--radius-xl); padding: 32px;
    box-shadow: var(--shadow-xl); z-index: 9001; max-width: 440px; width: 90%;
    opacity: 0; visibility: hidden; pointer-events: none; transition: all 0.3s ease;
}
.confirm-dialog.show { opacity: 1; visibility: visible; pointer-events: auto; transform: translate(-50%, -50%) scale(1); }
.confirm-dialog .icon-circle {
    width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 20px;
    display: flex; align-items: center; justify-content: center; font-size: 1.6rem;
}
.confirm-dialog .icon-circle.danger { background: var(--danger-light); color: var(--danger); }
.confirm-dialog .icon-circle.warning { background: var(--warning-light); color: var(--warning); }
.confirm-dialog h3 { text-align: center; font-size: 1.2rem; font-weight: 700; margin-bottom: 8px; }
.confirm-dialog p { text-align: center; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 28px; }
.confirm-dialog .dialog-actions { display: flex; gap: 12px; }
.confirm-dialog .dialog-actions .btn { flex: 1; }

/* Badge */
.badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; padding: 3px 10px; border-radius: 20px;
}
.badge-green { background: var(--primary-light); color: var(--primary-dark); }
.badge-blue { background: var(--blue-light); color: var(--blue-dark); }

/* Select without icon */
.select-clean {
    width: 200px; padding: 10px 16px;
    border: 1.5px solid var(--border-color); border-radius: var(--radius-md);
    font-size: 0.9rem; font-family: 'Inter', sans-serif;
    color: var(--text-primary); background: #f8fafc;
    outline: none; transition: all var(--transition); cursor: pointer;
}
.select-clean:focus {
    border-color: var(--primary); background: #fff;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12);
}

/* Responsive */
@media (max-width: 768px) {
    .main-container { padding: 16px; }
    .page-header h1 { font-size: 1.4rem; }
    .tab-body { padding: 24px 20px; }
    .tab-button { padding: 14px 18px; font-size: 0.85rem; }
    .form-grid { grid-template-columns: 1fr; }
    .setting-row { flex-direction: column; align-items: flex-start; }
    .setting-row .btn, .setting-row .toggle-switch { align-self: flex-end; }
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

    <!-- Confirm Dialog -->
    <div class="confirm-backdrop" id="confirmBackdrop"></div>
    <div class="confirm-dialog" id="confirmDialog">
        <div class="icon-circle danger" id="confirmIcon"><i class="fas fa-triangle-exclamation"></i></div>
        <h3 id="confirmTitle">Sei sicuro?</h3>
        <p id="confirmMessage">Questa azione non pu&ograve; essere annullata.</p>
        <div class="dialog-actions">
            <button class="btn btn-outline" id="confirmCancel">Annulla</button>
            <button class="btn btn-danger" id="confirmOk"><i class="fas fa-trash-can"></i> Conferma</button>
        </div>
    </div>

    <main class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-left">
                <div class="page-icon"><i class="fas fa-gear"></i></div>
                <div>
                    <h1>Impostazioni</h1>
                    <p class="subtitle">Configura il tuo gestionale e le preferenze</p>
                </div>
            </div>
        </div>

        <!-- Settings Card -->
        <div class="card">
            <nav class="tab-nav">
                <button data-tab="info" class="tab-button active"><i class="fas fa-store"></i> Informazioni</button>
                <button data-tab="appearance" class="tab-button"><i class="fas fa-palette"></i> Aspetto</button>
                <button data-tab="security" class="tab-button"><i class="fas fa-shield-halved"></i> Sicurezza</button>
                <button data-tab="data" class="tab-button"><i class="fas fa-database"></i> Dati</button>
            </nav>

            <!-- ==================== TAB: INFORMAZIONI ==================== -->
            <div id="info-panel" class="tab-panel active">
                <div class="tab-body">
                    <div class="section-header">
                        <h2>
                            <span class="header-icon green"><i class="fas fa-store"></i></span>
                            Informazioni Negozio
                        </h2>
                        <p>Questi dettagli appariranno su ricevute, fatture e altri documenti ufficiali del tuo negozio.</p>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label class="input-label" for="shopName">Nome Negozio</label>
                            <div class="input-wrapper">
                                <input type="text" id="shopName" placeholder="Nome del tuo negozio">
                                <i class="fas fa-store input-icon"></i>
                            </div>
                        </div>
                        <div class="input-group">
                            <label class="input-label" for="shopVAT">Partita IVA / C.F.</label>
                            <div class="input-wrapper">
                                <input type="text" id="shopVAT" placeholder="Partita IVA o Codice Fiscale">
                                <i class="fas fa-id-card input-icon"></i>
                            </div>
                        </div>
                        <div class="input-group full-width">
                            <label class="input-label" for="shopAddress">Indirizzo</label>
                            <div class="input-wrapper">
                                <input type="text" id="shopAddress" placeholder="Via, numero civico, citt&agrave;, CAP">
                                <i class="fas fa-location-dot input-icon"></i>
                            </div>
                        </div>
                        <div class="input-group">
                            <label class="input-label" for="shopPhone">Telefono</label>
                            <div class="input-wrapper">
                                <input type="tel" id="shopPhone" placeholder="Numero di telefono">
                                <i class="fas fa-phone input-icon"></i>
                            </div>
                        </div>
                        <div class="input-group">
                            <label class="input-label" for="shopEmail">Email</label>
                            <div class="input-wrapper">
                                <input type="email" id="shopEmail" placeholder="Indirizzo email">
                                <i class="fas fa-envelope input-icon"></i>
                            </div>
                        </div>
                    </div>

                    <div class="section-footer">
                        <button id="saveShopInfoBtn" class="btn btn-primary" data-original-text="Salva Informazioni" data-original-icon="fas fa-floppy-disk">
                            <i class="fas fa-floppy-disk"></i>
                            <span>Salva Informazioni</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ==================== TAB: ASPETTO ==================== -->
            <div id="appearance-panel" class="tab-panel">
                <div class="tab-body">
                    <div class="section-header">
                        <h2>
                            <span class="header-icon blue"><i class="fas fa-palette"></i></span>
                            Preferenze di Visualizzazione
                        </h2>
                        <p>Personalizza l'interfaccia del gestionale per adattarla alle tue preferenze di lavoro.</p>
                    </div>

                    <div class="setting-row">
                        <div class="setting-row-info">
                            <h3><i class="fas fa-moon"></i> Modalit&agrave; Scura</h3>
                            <p>Riduci l'affaticamento degli occhi in condizioni di scarsa illuminazione.</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="darkModeToggle">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="setting-row">
                        <div class="setting-row-info">
                            <h3><i class="fas fa-table-list"></i> Elementi per pagina</h3>
                            <p>Numero di righe visualizzate nelle tabelle del gestionale.</p>
                        </div>
                        <select id="itemsPerPage" class="select-clean">
                            <option value="10">10 elementi</option>
                            <option value="20">20 elementi</option>
                            <option value="50">50 elementi</option>
                            <option value="100">100 elementi</option>
                        </select>
                    </div>

                    <div class="section-footer">
                        <button id="saveDisplayPrefsBtn" class="btn btn-primary" data-original-text="Salva Preferenze" data-original-icon="fas fa-floppy-disk">
                            <i class="fas fa-floppy-disk"></i>
                            <span>Salva Preferenze</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ==================== TAB: SICUREZZA ==================== -->
            <div id="security-panel" class="tab-panel">
                <div class="tab-body">
                    <div class="section-header">
                        <h2>
                            <span class="header-icon purple"><i class="fas fa-shield-halved"></i></span>
                            Sicurezza Account
                        </h2>
                        <p>Gestisci la tua password e le opzioni di accesso per proteggere il tuo account.</p>
                    </div>

                    <div class="setting-row">
                        <div class="setting-row-info">
                            <h3><i class="fas fa-key"></i> Cambia Password</h3>
                            <p>Si consiglia di utilizzare una password lunga e complessa.</p>
                        </div>
                        <button class="btn btn-blue btn-sm" onclick="changePassword()">
                            <i class="fas fa-pen"></i> Cambia
                        </button>
                    </div>

                    <div class="setting-row">
                        <div class="setting-row-info">
                            <h3><i class="fas fa-mobile-screen"></i> Autenticazione a Due Fattori (2FA)
                                <span class="badge badge-blue">Consigliato</span>
                            </h3>
                            <p>Aggiungi un ulteriore livello di sicurezza al tuo account.</p>
                        </div>
                        <button class="btn btn-blue btn-sm" onclick="setup2FA()">
                            <i class="fas fa-lock"></i> Abilita
                        </button>
                    </div>

                    <div class="setting-row">
                        <div class="setting-row-info">
                            <h3><i class="fas fa-clock-rotate-left"></i> Sessioni Attive</h3>
                            <p>Visualizza e gestisci le sessioni attive del tuo account.</p>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="showNotification('Funzione non implementata.', 'info')">
                            <i class="fas fa-eye"></i> Visualizza
                        </button>
                    </div>
                </div>
            </div>

            <!-- ==================== TAB: DATI ==================== -->
            <div id="data-panel" class="tab-panel">
                <div class="tab-body">
                    <div class="section-header">
                        <h2>
                            <span class="header-icon orange"><i class="fas fa-database"></i></span>
                            Gestione Dati
                        </h2>
                        <p>Esporta o importa i dati del tuo gestionale per backup o analisi esterne.</p>
                    </div>

                    <div class="setting-row">
                        <div class="setting-row-info">
                            <h3><i class="fas fa-database"></i> Backup Database <span class="badge badge-green">SQL</span></h3>
                            <p>Scarica un backup completo del database in formato SQL. Conservalo in un luogo sicuro.</p>
                        </div>
                        <button id="backupDbBtn" class="btn btn-primary btn-sm" onclick="backupDatabase()">
                            <i class="fas fa-download"></i> Backup Ora
                        </button>
                    </div>

                    <div class="setting-row">
                        <div class="setting-row-info">
                            <h3><i class="fas fa-file-csv"></i> Esporta Prodotti <span class="badge badge-green">CSV</span></h3>
                            <p>Scarica un file CSV con tutti i tuoi prodotti e le giacenze.</p>
                        </div>
                        <button id="exportDataBtn" class="btn btn-primary btn-sm">
                            <i class="fas fa-download"></i> Esporta
                        </button>
                    </div>

                    <div class="setting-row">
                        <div class="setting-row-info">
                            <h3><i class="fas fa-file-arrow-up"></i> Importa Dati</h3>
                            <p>Carica un file CSV per importare prodotti nel gestionale.</p>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="showNotification('Funzione non implementata.', 'info')">
                            <i class="fas fa-upload"></i> Importa
                        </button>
                    </div>

                    <!-- Danger Zone -->
                    <div class="danger-zone">
                        <div class="danger-zone-header">
                            <i class="fas fa-triangle-exclamation"></i> Zona Pericolo
                        </div>
                        <div class="danger-zone-body">
                            <div class="setting-row">
                                <div class="setting-row-info">
                                    <h3><i class="fas fa-trash-can"></i> Elimina Tutti i Dati</h3>
                                    <p>Questa azione &egrave; irreversibile. Tutti i prodotti, clienti e vendite verranno cancellati.</p>
                                </div>
                                <button class="btn btn-danger btn-sm" onclick="deleteAllData()">
                                    <i class="fas fa-trash-can"></i> Elimina
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

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

// ========== CONFIRM DIALOG ==========
var _confirmResolve = null;
function showConfirm(title, message, iconType) {
    return new Promise(function(resolve) {
        _confirmResolve = resolve;
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmMessage').textContent = message;
        var ic = document.getElementById('confirmIcon');
        ic.className = 'icon-circle ' + (iconType || 'danger');
        document.getElementById('confirmBackdrop').classList.add('show');
        document.getElementById('confirmDialog').classList.add('show');
    });
}
function closeConfirm() {
    document.getElementById('confirmBackdrop').classList.remove('show');
    document.getElementById('confirmDialog').classList.remove('show');
}
document.getElementById('confirmCancel').addEventListener('click', function() { closeConfirm(); if(_confirmResolve){_confirmResolve(false);_confirmResolve=null;} });
document.getElementById('confirmOk').addEventListener('click', function() { closeConfirm(); if(_confirmResolve){_confirmResolve(true);_confirmResolve=null;} });
document.getElementById('confirmBackdrop').addEventListener('click', function() { closeConfirm(); if(_confirmResolve){_confirmResolve(false);_confirmResolve=null;} });

// ========== INIT ==========
document.addEventListener('DOMContentLoaded', function() {
    // Tab navigation
    var tabButtons = document.querySelectorAll('.tab-button');
    var tabPanels = document.querySelectorAll('.tab-panel');
    tabButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            tabButtons.forEach(function(b) { b.classList.remove('active'); });
            button.classList.add('active');
            tabPanels.forEach(function(panel) {
                panel.classList.toggle('active', panel.id === button.dataset.tab + '-panel');
            });
        });
    });

    loadSettings();

    document.getElementById('saveShopInfoBtn').addEventListener('click', saveShopInfo);
    document.getElementById('saveDisplayPrefsBtn').addEventListener('click', saveDisplayPreferences);
    document.getElementById('exportDataBtn').addEventListener('click', exportData);
    document.getElementById('darkModeToggle').addEventListener('change', function() {
        applyDarkMode(this.checked);
    });
});

// ========== DARK MODE ==========
function applyDarkMode(isDark) {
    document.body.classList.toggle('dark-mode', isDark);
}

// ========== LOAD SETTINGS ==========
function loadSettings() {
    try {
        var settings = JSON.parse(localStorage.getItem('appSettings')) || {};
        document.getElementById('shopName').value = settings.shopName || '';
        document.getElementById('shopAddress').value = settings.shopAddress || '';
        document.getElementById('shopPhone').value = settings.shopPhone || '';
        document.getElementById('shopEmail').value = settings.shopEmail || '';
        document.getElementById('shopVAT').value = settings.shopVAT || '';
        var darkMode = settings.darkMode === true;
        document.getElementById('darkModeToggle').checked = darkMode;
        applyDarkMode(darkMode);
        document.getElementById('itemsPerPage').value = settings.itemsPerPage || '10';
    } catch (e) {
        console.error("Errore caricamento impostazioni:", e);
        showNotification("Errore nel caricamento delle impostazioni.", "error");
    }
}

// ========== SAVE HELPERS ==========
function setButtonLoading(button, isLoading) {
    var span = button.querySelector('span');
    var icon = button.querySelector('i');
    if (isLoading) {
        button.disabled = true;
        if(span) span.textContent = 'Salvataggio...';
        if(icon) icon.className = 'fas fa-spinner fa-spin';
    } else {
        button.disabled = false;
        if(span) span.textContent = button.dataset.originalText;
        if(icon) icon.className = button.dataset.originalIcon;
    }
}

async function saveData(settingsData, button) {
    setButtonLoading(button, true);
    try {
        await new Promise(function(r) { setTimeout(r, 800); });
        var current = JSON.parse(localStorage.getItem('appSettings')) || {};
        var merged = Object.assign({}, current, settingsData);
        localStorage.setItem('appSettings', JSON.stringify(merged));
        showNotification("Impostazioni salvate con successo!", "success");
    } catch (e) {
        console.error("Errore salvataggio:", e);
        showNotification("Errore nel salvataggio.", "error");
    } finally {
        setButtonLoading(button, false);
    }
}

function saveShopInfo() {
    saveData({
        shopName: document.getElementById('shopName').value,
        shopAddress: document.getElementById('shopAddress').value,
        shopPhone: document.getElementById('shopPhone').value,
        shopEmail: document.getElementById('shopEmail').value,
        shopVAT: document.getElementById('shopVAT').value
    }, document.getElementById('saveShopInfoBtn'));
}

function saveDisplayPreferences() {
    saveData({
        darkMode: document.getElementById('darkModeToggle').checked,
        itemsPerPage: parseInt(document.getElementById('itemsPerPage').value)
    }, document.getElementById('saveDisplayPrefsBtn'));
}

function exportData() {
    showNotification("Esportazione dati in corso...", "info");
}

// ========== BACKUP DATABASE ==========
function backupDatabase() {
    var btn = document.getElementById('backupDbBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> In corso...';
    showNotification("Backup in corso, attendere...", "info");
    
    // Usa un iframe nascosto per il download
    var iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = 'backup_database.php';
    document.body.appendChild(iframe);
    
    // Check se il download inizia
    setTimeout(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download"></i> Backup Ora';
        showNotification("Se il download non si avvia, verifica i permessi admin.", "warning");
    }, 5000);
    
    // Cleanup iframe dopo un po'
    setTimeout(function() { document.body.removeChild(iframe); }, 30000);
}

// ========== SECURITY ACTIONS ==========
function changePassword() {
    showNotification("Funzione non implementata in questa demo.", "info");
}
function setup2FA() {
    showNotification("Funzione non implementata in questa demo.", "info");
}

// ========== DELETE ALL ==========
async function deleteAllData() {
    var ok = await showConfirm(
        'Sei assolutamente sicuro?',
        "Questa azione eliminer\u00e0 TUTTI i dati del gestionale e non potr\u00e0 essere annullata!",
        'danger'
    );
    if (ok) {
        showNotification("Azione non implementata in questa demo.", "info");
    }
}
</script>
</body>
</html>