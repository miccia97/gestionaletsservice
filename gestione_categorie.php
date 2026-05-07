<?php
// Gestione Categorie e Sottocategorie (Frontend HTML/JS)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Categorie | TS Service</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=2">
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
    --success: #10b981;
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
.toast i { font-size: 1.2rem; }
@keyframes slideInToast { from { transform: translateX(110%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes fadeOutToast { to { opacity: 0; transform: translateX(40px); } }

/* Layout */
.main-container { max-width: 1400px; margin: 0 auto; padding: 24px 32px 60px; position: relative; z-index: 1; }

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
.page-header p { color: var(--text-secondary); font-size: 0.95rem; margin-top: 2px; }

/* Stats Row */
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px; }
.stat-card {
    background: var(--bg-card); border-radius: var(--radius-lg); padding: 20px 24px;
    box-shadow: var(--shadow-sm); border: 1px solid var(--border-light);
    display: flex; align-items: center; gap: 16px;
    transition: all var(--transition);
}
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.stat-icon {
    width: 48px; height: 48px; border-radius: var(--radius-md);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.stat-icon.green { background: var(--primary-light); color: var(--primary-dark); }
.stat-icon.blue { background: var(--blue-light); color: var(--blue-dark); }
.stat-icon.purple { background: var(--secondary-light); color: var(--secondary); }
.stat-icon.orange { background: var(--warning-light); color: var(--warning); }
.stat-value { font-size: 1.6rem; font-weight: 800; line-height: 1.2; }
.stat-label { font-size: 0.8rem; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; }

/* Main Grid */
.main-grid { display: grid; grid-template-columns: 1fr 380px; gap: 28px; align-items: flex-start; }

/* Card */
.card {
    background: var(--bg-card); border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md); border: 1px solid var(--border-light);
    overflow: hidden; transition: all var(--transition);
}
.card-header {
    padding: 24px 28px; border-bottom: 1px solid var(--border-light);
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
}
.card-header h2 { font-size: 1.15rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
.card-header h2 i { color: var(--primary); font-size: 1.1rem; }
.card-body { padding: 20px 28px 28px; }
.card-body-flush { padding: 0; }

/* Search Bar */
.search-bar { padding: 16px 28px; border-bottom: 1px solid var(--border-light); }
.search-bar input {
    width: 100%; padding: 10px 16px 10px 42px;
    border: 1px solid var(--border-color); border-radius: var(--radius-md);
    font-size: 0.9rem; font-family: 'Inter', sans-serif;
    background-color: #f8fafc;
    transition: all var(--transition); outline: none;
}
.search-bar input:focus {
    border-color: var(--primary); background-color: #fff;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12);
}

/* Category List */
#categoriesList { list-style: none; padding: 0; margin: 0; }
.children-container { list-style: none; padding: 0; margin: 0; }

.category-item {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 28px;
    border-bottom: 1px solid var(--border-light);
    transition: all var(--transition);
    position: relative; cursor: default;
}
.category-item:last-child { border-bottom: none; }
.category-item:hover { background: #f0fdf4; }

.category-level-0 { }
.category-level-1 { padding-left: 56px; background: #fafcff; }
.category-level-1::before {
    content: ''; position: absolute; left: 38px; top: 0; bottom: 0;
    width: 2px; background: linear-gradient(to bottom, var(--primary-light), var(--border-light));
}
.category-level-2 { padding-left: 84px; background: #f8f9fc; }
.category-level-2::before {
    content: ''; position: absolute; left: 66px; top: 0; bottom: 0;
    width: 2px; background: linear-gradient(to bottom, var(--blue-light), var(--border-light));
}

.drag-handle {
    cursor: grab; color: var(--text-muted); font-size: 0.85rem;
    padding: 4px; border-radius: 4px; transition: all var(--transition); flex-shrink: 0;
}
.drag-handle:hover { color: var(--primary); background: var(--primary-light); }
.drag-handle:active { cursor: grabbing; }

.cat-icon {
    width: 36px; height: 36px; border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; flex-shrink: 0;
}
.cat-icon.main { background: var(--primary-light); color: var(--primary-dark); }
.cat-icon.sub { background: var(--blue-light); color: var(--blue-dark); }
.cat-icon.subsub { background: var(--secondary-light); color: var(--secondary); }

.category-name { flex: 1; font-weight: 600; font-size: 0.95rem; color: var(--text-primary); }
.category-name .cat-badge {
    display: inline-block; font-size: 0.65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.06em;
    padding: 2px 8px; border-radius: 20px; margin-left: 8px; vertical-align: middle;
}
.cat-badge.main-badge { background: var(--primary-light); color: var(--primary-dark); }
.cat-badge.sub-badge { background: var(--blue-light); color: var(--blue-dark); }
.cat-badge.subsub-badge { background: var(--secondary-light); color: var(--secondary); }

.category-count {
    font-size: 0.75rem; color: var(--text-muted); font-weight: 500;
    padding: 2px 8px; background: var(--border-light); border-radius: 20px; white-space: nowrap;
}

.category-actions { display: flex; gap: 4px; flex-shrink: 0; }
.category-actions button {
    width: 34px; height: 34px; border-radius: var(--radius-sm); border: none;
    background: transparent; cursor: pointer; color: var(--text-muted);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; transition: all var(--transition);
}
.category-actions .edit-btn:hover { background: var(--blue-light); color: var(--blue-dark); }
.category-actions .delete-btn:hover { background: var(--danger-light); color: var(--danger-dark); }

.is-dragging { opacity: 0.4; background: var(--primary-light) !important; }
.drag-placeholder {
    height: 52px; margin: 2px 28px;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.06), rgba(34, 197, 94, 0.02));
    border: 2px dashed var(--primary); border-radius: var(--radius-md);
}
.drop-target-highlight { background: var(--primary-light) !important; }

.empty-state { text-align: center; padding: 60px 24px; color: var(--text-muted); }
.empty-state i { font-size: 2.5rem; margin-bottom: 16px; display: block; color: var(--border-color); }
.empty-state p { font-size: 0.95rem; }

.form-card { position: sticky; top: 100px; }
.form-card .card-header { background: linear-gradient(135deg, #f0fdf4, #ecfdf5); }

.form-group { margin-bottom: 20px; }
.form-label {
    display: block; font-weight: 600; font-size: 0.85rem;
    color: var(--text-secondary); margin-bottom: 8px;
    text-transform: uppercase; letter-spacing: 0.04em;
}
.form-input, .form-select {
    width: 100%; padding: 12px 16px;
    border: 1.5px solid var(--border-color); border-radius: var(--radius-md);
    font-size: 0.9rem; font-family: 'Inter', sans-serif;
    color: var(--text-primary); background: #f8fafc;
    outline: none; transition: all var(--transition);
}
.form-input:focus, .form-select:focus {
    border-color: var(--primary); background: #fff;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12);
}
.form-input::placeholder { color: var(--text-muted); }

.form-actions { display: flex; gap: 12px; margin-top: 24px; }

.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 24px; border-radius: var(--radius-md); font-weight: 700;
    font-size: 0.9rem; font-family: 'Inter', sans-serif;
    cursor: pointer; border: none; transition: all var(--transition); flex: 1;
}
.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff; box-shadow: 0 4px 12px var(--primary-glow);
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px var(--primary-glow); }
.btn-primary:active { transform: translateY(0); }
.btn-secondary {
    background: var(--border-light); color: var(--text-secondary);
    border: 1px solid var(--border-color);
}
.btn-secondary:hover { background: var(--border-color); color: var(--text-primary); }

/* Confirm Dialog */
.confirm-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px);
    z-index: 9000; opacity: 0; visibility: hidden; pointer-events: none; transition: all 0.3s ease;
}
.confirm-backdrop.show { opacity: 1; visibility: visible; pointer-events: auto; }
.confirm-dialog {
    position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9);
    background: #fff; border-radius: var(--radius-xl); padding: 32px;
    box-shadow: var(--shadow-xl); z-index: 9001; max-width: 420px; width: 90%;
    opacity: 0; visibility: hidden; pointer-events: none; transition: all 0.3s ease;
}
.confirm-dialog.show { opacity: 1; visibility: visible; pointer-events: auto; transform: translate(-50%, -50%) scale(1); }
.confirm-dialog .icon-circle {
    width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 20px;
    background: var(--danger-light); color: var(--danger);
    display: flex; align-items: center; justify-content: center; font-size: 1.6rem;
}
.confirm-dialog h3 { text-align: center; font-size: 1.2rem; font-weight: 700; margin-bottom: 8px; }
.confirm-dialog p { text-align: center; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 28px; }
.confirm-dialog .dialog-actions { display: flex; gap: 12px; }
.confirm-dialog .btn-cancel {
    flex: 1; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border-color);
    background: #fff; color: var(--text-secondary); font-weight: 600; cursor: pointer;
    font-family: 'Inter', sans-serif; font-size: 0.9rem; transition: all var(--transition);
}
.confirm-dialog .btn-cancel:hover { background: var(--border-light); }
.confirm-dialog .btn-danger {
    flex: 1; padding: 12px; border-radius: var(--radius-md); border: none;
    background: linear-gradient(135deg, var(--danger), var(--danger-dark));
    color: #fff; font-weight: 700; cursor: pointer; font-family: 'Inter', sans-serif;
    font-size: 0.9rem; transition: all var(--transition);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}
.confirm-dialog .btn-danger:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(239, 68, 68, 0.4); }

@media (max-width: 1024px) {
    .main-grid { grid-template-columns: 1fr; }
    .form-card { position: static; order: -1; }
}
@media (max-width: 640px) {
    .main-container { padding: 16px; }
    .page-header h1 { font-size: 1.4rem; }
    .stats-row { grid-template-columns: 1fr 1fr; }
    .category-item { padding: 12px 16px; }
    .category-level-1 { padding-left: 40px; }
    .category-level-2 { padding-left: 56px; }
    .card-header, .card-body { padding-left: 20px; padding-right: 20px; }
}
@media print {
    .particles-container, .toast-container, .form-card { display: none !important; }
    body { padding-top: 0; background: #fff; }
    .card { box-shadow: none; border: 1px solid #ddd; }
}
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <!-- Floating particles -->
    <div class="particles-container">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <!-- Confirm Dialog -->
    <div class="confirm-backdrop" id="confirmBackdrop"></div>
    <div class="confirm-dialog" id="confirmDialog">
        <div class="icon-circle"><i class="fas fa-triangle-exclamation"></i></div>
        <h3 id="confirmTitle">Sei sicuro?</h3>
        <p id="confirmMessage">Questa azione non pu&ograve; essere annullata.</p>
        <div class="dialog-actions">
            <button class="btn-cancel" id="confirmCancel">Annulla</button>
            <button class="btn-danger" id="confirmOk"><i class="fas fa-trash-can"></i> Elimina</button>
        </div>
    </div>

    <main class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-left">
                <div class="page-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                    <h1>Gestione Categorie</h1>
                    <p>Organizza la struttura delle categorie dei tuoi prodotti</p>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-row" id="statsRow">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-folder"></i></div>
                <div><div class="stat-value" id="statMain">-</div><div class="stat-label">Categorie</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-folder-tree"></i></div>
                <div><div class="stat-value" id="statSub">-</div><div class="stat-label">Sottocategorie</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-sitemap"></i></div>
                <div><div class="stat-value" id="statSubSub">-</div><div class="stat-label">Sotto-sotto</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-shapes"></i></div>
                <div><div class="stat-value" id="statTotal">-</div><div class="stat-label">Totale</div></div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="main-grid">
            <!-- Category Tree -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list-tree"></i> Albero Categorie</h2>
                </div>
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Cerca categorie..." oninput="handleSearch()">
                </div>
                <div class="card-body-flush">
                    <ul id="categoriesList">
                        <li class="empty-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Caricamento categorie...</p>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Add/Edit Form -->
            <div class="card form-card">
                <div class="card-header">
                    <h2 id="formTitle"><i class="fas fa-plus-circle"></i> <span>Aggiungi Categoria</span></h2>
                </div>
                <div class="card-body">
                    <form id="categoryForm">
                        <input type="hidden" id="categoryId">
                        <input type="hidden" id="categoryType">
                        <div class="form-group">
                            <label class="form-label" for="categoryName">Nome Categoria</label>
                            <input type="text" id="categoryName" class="form-input" placeholder="Es. Smartphone" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="parentCategory">Categoria Genitore</label>
                            <select id="parentCategory" class="form-select">
                                <option value="" data-parent-type="none">(Nessun genitore &mdash; livello principale)</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="button" id="cancelBtn" class="btn btn-secondary" style="display: none;"><i class="fas fa-xmark"></i> Annulla</button>
                            <button type="submit" id="addUpdateBtn" class="btn btn-primary"><i class="fas fa-plus"></i> Aggiungi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

<script>
// ========== GLOBALS ==========
var categoriesData = [];
var draggedElement = null;
var placeholder = document.createElement('li');
placeholder.className = 'drag-placeholder';

// ========== INIT ==========
document.addEventListener('DOMContentLoaded', function() {
    initApp();
    document.getElementById('categoryForm').addEventListener('submit', handleFormSubmit);
    document.getElementById('cancelBtn').addEventListener('click', resetForm);
});

async function initApp() { await loadCategories(); }

// ========== TOAST ==========
function showNotification(message, type) {
    if (type !== 'success' && type !== 'error' && type !== 'warning') type = 'success';
    var container = document.getElementById('toastContainer');
    var toast = document.createElement('div');
    toast.className = 'toast ' + type;
    var icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', warning: 'fa-triangle-exclamation' };
    toast.innerHTML = '<i class="fas ' + (icons[type] || icons.success) + '"></i> ' + message;
    container.appendChild(toast);
    setTimeout(function() { if (toast.parentNode) toast.remove(); }, 3500);
}

// ========== CONFIRM DIALOG ==========
var _confirmResolve = null;

function showConfirm(title, msg) {
    return new Promise(function(resolve) {
        _confirmResolve = resolve;
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmMessage').textContent = msg;
        document.getElementById('confirmBackdrop').classList.add('show');
        document.getElementById('confirmDialog').classList.add('show');
    });
}

function closeConfirm() {
    document.getElementById('confirmBackdrop').classList.remove('show');
    document.getElementById('confirmDialog').classList.remove('show');
}

document.getElementById('confirmCancel').addEventListener('click', function() {
    closeConfirm();
    if (_confirmResolve) { _confirmResolve(false); _confirmResolve = null; }
});
document.getElementById('confirmOk').addEventListener('click', function() {
    closeConfirm();
    if (_confirmResolve) { _confirmResolve(true); _confirmResolve = null; }
});
document.getElementById('confirmBackdrop').addEventListener('click', function() {
    closeConfirm();
    if (_confirmResolve) { _confirmResolve(false); _confirmResolve = null; }
});

// ========== LOAD CATEGORIES ==========
async function loadCategories() {
    try {
        var response = await fetch('api/get_categories_data.php');
        if (!response.ok) throw new Error('HTTP error! status: ' + response.status);
        var result = await response.json();
        if (result.success) {
            categoriesData = result.data;
            renderCategories();
            populateParentDropdown();
            updateStats();
        } else {
            showNotification('Errore: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification("Errore di comunicazione con il server.", 'error');
        console.error("Errore caricamento categorie:", error);
    }
}

// ========== STATS ==========
function updateStats() {
    var m = 0, s = 0, ss = 0;
    function cnt(cats, d) {
        cats.forEach(function(c) {
            if (d === 0) m++;
            else if (d === 1) s++;
            else ss++;
            if (c.children && c.children.length > 0) cnt(c.children, d + 1);
        });
    }
    cnt(categoriesData, 0);
    document.getElementById('statMain').textContent = m;
    document.getElementById('statSub').textContent = s;
    document.getElementById('statSubSub').textContent = ss;
    document.getElementById('statTotal').textContent = m + s + ss;
}

// ========== RENDER CATEGORIES ==========
function escHtml(str) {
    var d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function renderCategories() {
    var listContainer = document.getElementById('categoriesList');
    listContainer.innerHTML = '';

    if (categoriesData.length === 0) {
        listContainer.innerHTML = '<li class="empty-state"><i class="fas fa-folder-open"></i><p>Nessuna categoria trovata. Aggiungine una!</p></li>';
        return;
    }

    function buildCategoryTree(categories, parentElement, level) {
        categories.forEach(function(category) {
            var li = document.createElement('li');
            li.className = 'category-item category-level-' + level;
            li.dataset.id = category.id;
            li.dataset.type = category.type;
            li.dataset.depth = level;
            li.draggable = true;

            var iconClass = level === 0 ? 'main' : (level === 1 ? 'sub' : 'subsub');
            var faIcon = level === 0 ? 'fa-folder' : (level === 1 ? 'fa-folder-tree' : 'fa-tag');
            var badgeClass = level === 0 ? 'main-badge' : (level === 1 ? 'sub-badge' : 'subsub-badge');
            var badgeText = level === 0 ? '' : (level === 1 ? 'Sub' : 'Sub-sub');

            var childCount = (category.children && category.children.length > 0)
                ? '<span class="category-count">' + category.children.length + ' sotto</span>' : '';

            li.innerHTML =
                '<div class="drag-handle"><i class="fas fa-grip-vertical"></i></div>' +
                '<div class="cat-icon ' + iconClass + '"><i class="fas ' + faIcon + '"></i></div>' +
                '<span class="category-name">' + escHtml(category.nome) +
                    (badgeText ? ' <span class="cat-badge ' + badgeClass + '">' + badgeText + '</span>' : '') +
                '</span>' +
                childCount +
                '<div class="category-actions">' +
                    '<button class="edit-btn" title="Modifica"><i class="fas fa-pen"></i></button>' +
                    '<button class="delete-btn" title="Elimina"><i class="fas fa-trash-can"></i></button>' +
                '</div>';

            parentElement.appendChild(li);

            li.addEventListener('dragstart', handleDragStart);
            li.addEventListener('dragend', handleDragEnd);
            li.addEventListener('dragover', handleDragOver);
            li.addEventListener('dragleave', handleDragLeave);
            li.addEventListener('drop', handleDrop);

            li.querySelector('.edit-btn').addEventListener('click', (function(cat) {
                return function() { setupEditForm(cat); };
            })(category));
            li.querySelector('.delete-btn').addEventListener('click', (function(cat) {
                return function() { confirmDelete(cat.id, cat.type); };
            })(category));

            if (category.children && category.children.length > 0) {
                var childrenContainer = document.createElement('ul');
                childrenContainer.className = 'children-container';
                parentElement.appendChild(childrenContainer);
                buildCategoryTree(category.children, childrenContainer, level + 1);
            }
        });
    }
    buildCategoryTree(categoriesData, listContainer, 0);
}

// ========== SEARCH ==========
function handleSearch() {
    var term = document.getElementById('searchInput').value.toLowerCase().trim();
    var items = document.querySelectorAll('.category-item');
    items.forEach(function(item) {
        var name = item.querySelector('.category-name').textContent.toLowerCase();
        item.style.display = (!term || name.includes(term)) ? '' : 'none';
    });
}

// ========== DRAG & DROP ==========
function handleDragStart(e) {
    draggedElement = e.currentTarget;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', draggedElement.dataset.id);
    setTimeout(function() { if (draggedElement) draggedElement.classList.add('is-dragging'); }, 0);
}

function handleDragEnd() {
    if (draggedElement) { draggedElement.classList.remove('is-dragging'); draggedElement = null; }
    if (placeholder.parentNode) placeholder.remove();
    document.querySelectorAll('.drop-target-highlight').forEach(function(el) { el.classList.remove('drop-target-highlight'); });
}

function handleDragOver(e) {
    e.preventDefault();
    var target = e.target.closest('.category-item');
    if (!target || target === draggedElement) return;

    document.querySelectorAll('.drop-target-highlight').forEach(function(el) { el.classList.remove('drop-target-highlight'); });

    var targetRect = target.getBoundingClientRect();
    var isOverTopHalf = e.clientY < targetRect.top + targetRect.height / 2;
    var targetDepth = parseInt(target.dataset.depth);
    var draggedDepth = parseInt(draggedElement.dataset.depth);

    if (targetDepth < 2 && draggedDepth <= targetDepth) {
        var dropZoneThreshold = targetRect.height * 0.25;
        if (e.clientY > targetRect.top + dropZoneThreshold && e.clientY < targetRect.bottom - dropZoneThreshold) {
            target.classList.add('drop-target-highlight');
            if (placeholder.parentNode) placeholder.remove();
            return;
        }
    }

    if (isOverTopHalf) {
        target.parentElement.insertBefore(placeholder, target);
    } else {
        target.parentElement.insertBefore(placeholder, target.nextElementSibling);
    }
}

function handleDragLeave(e) {
    var target = e.target.closest('.category-item');
    if (target) target.classList.remove('drop-target-highlight');
}

async function handleDrop(e) {
    e.preventDefault();
    var highlightedTarget = document.querySelector('.drop-target-highlight');
    if (highlightedTarget) {
        highlightedTarget.classList.remove('drop-target-highlight');
        var childrenContainer = highlightedTarget.nextElementSibling;
        if (!childrenContainer || !childrenContainer.classList.contains('children-container')) {
            childrenContainer = document.createElement('ul');
            childrenContainer.className = 'children-container';
            highlightedTarget.insertAdjacentElement('afterend', childrenContainer);
        }
        childrenContainer.prepend(draggedElement);
    } else if (placeholder.parentNode) {
        placeholder.replaceWith(draggedElement);
    } else {
        return;
    }
    await updateStructure();
}

async function updateStructure() {
    var items = [];
    function traverseDOM(container, parentId, parentType, depth) {
        var children = Array.from(container.children).filter(function(el) { return el.classList.contains('category-item'); });
        children.forEach(function(item, index) {
            var id = item.dataset.id;
            var currentType = depth === 0 ? 'main_category' : (depth === 1 ? 'sub_category' : 'sub_sub_category');
            items.push({ id: id, order: index, parent_id: parentId, type: currentType });
            var nextContainer = item.nextElementSibling;
            if (nextContainer && nextContainer.classList.contains('children-container')) {
                traverseDOM(nextContainer, id, currentType, depth + 1);
            }
        });
    }
    traverseDOM(document.getElementById('categoriesList'), null, 'none', 0);
    await updateOrdersOnServer(items);
}

async function updateOrdersOnServer(items) {
    try {
        var response = await fetch('update_orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: items })
        });
        var result = await response.json();
        if (result.success) showNotification(result.message, 'success');
        else showNotification('Errore: ' + result.message, 'error');
        await loadCategories();
    } catch (error) {
        showNotification("Errore durante il riordinamento.", 'error');
        await loadCategories();
    }
}

// ========== FORM ==========
function populateParentDropdown() {
    var parentSelect = document.getElementById('parentCategory');
    var currentId = document.getElementById('categoryId').value;
    parentSelect.innerHTML = '<option value="" data-parent-type="none">(Nessun genitore)</option>';

    function addOptions(categories, indent, depth) {
        categories.forEach(function(cat) {
            if (cat.id == currentId || depth >= 2) return;
            var option = document.createElement('option');
            option.value = cat.id;
            option.textContent = indent + cat.nome;
            option.dataset.parentType = cat.type;
            parentSelect.appendChild(option);
            if (cat.children && cat.children.length > 0) {
                addOptions(cat.children, indent + '\u2014 ', depth + 1);
            }
        });
    }
    addOptions(categoriesData, '', 0);
}

function resetForm() {
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryType').value = '';
    document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle"></i> <span>Aggiungi Categoria</span>';
    document.getElementById('addUpdateBtn').innerHTML = '<i class="fas fa-plus"></i> Aggiungi';
    document.getElementById('cancelBtn').style.display = 'none';
    populateParentDropdown();
}

function setupEditForm(category) {
    document.getElementById('formTitle').innerHTML = '<i class="fas fa-pen-to-square"></i> <span>Modifica: ' + escHtml(category.nome) + '</span>';
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryType').value = category.type;
    document.getElementById('categoryName').value = category.nome;

    var parentId = null;
    if (category.type === 'sub_category') parentId = category.parent_category_id;
    else if (category.type === 'sub_sub_category') parentId = category.parent_subcategory_id;

    populateParentDropdown();
    document.getElementById('parentCategory').value = parentId || "";

    document.getElementById('addUpdateBtn').innerHTML = '<i class="fas fa-check"></i> Aggiorna';
    document.getElementById('cancelBtn').style.display = 'inline-flex';

    if (window.innerWidth <= 1024) {
        document.querySelector('.form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();
    var id = document.getElementById('categoryId').value;
    var type = document.getElementById('categoryType').value;
    var name = document.getElementById('categoryName').value.trim();
    var parentSelect = document.getElementById('parentCategory');
    var selectedOption = parentSelect.options[parentSelect.selectedIndex];
    var parentId = selectedOption.value || null;
    var parentType = selectedOption.dataset.parentType || 'none';

    if (!name) return showNotification("Il nome della categoria &egrave; obbligatorio.", 'error');

    var endpoint = id ? 'update_category.php' : 'add_category.php';
    var bodyData = { nome: name, parent_id: parentId, parent_type: parentType };
    if (id) { bodyData.id = id; bodyData.type = type; }

    try {
        var response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(bodyData)
        });
        var result = await response.json();
        if (result.success) {
            showNotification(result.message, 'success');
            resetForm();
            await loadCategories();
        } else {
            showNotification('Errore: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification("Errore di comunicazione con il server.", 'error');
    }
}

// ========== DELETE ==========
async function confirmDelete(id, type) {
    var ok = await showConfirm(
        'Eliminare questa categoria?',
        "Eliminando questa categoria, eliminerai anche tutte le sue sottocategorie. L'azione non \u00e8 reversibile."
    );
    if (ok) await deleteCategory(id, type);
}

async function deleteCategory(id, type) {
    try {
        var response = await fetch('delete_category.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, type: type })
        });
        var result = await response.json();
        if (result.success) {
            showNotification(result.message, 'success');
            await loadCategories();
        } else {
            showNotification('Errore: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification("Errore di comunicazione con il server.", 'error');
    }
}
</script>
</body>
</html>