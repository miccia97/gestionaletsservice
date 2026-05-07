<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario | TS Service</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=2">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
:root {
    --primary: #22c55e;
    --primary-dark: #16a34a;
    --primary-light: #dcfce7;
    --primary-glow: rgba(34, 197, 94, 0.4);
    --secondary: #3b82f6;
    --secondary-dark: #2563eb;
    --secondary-light: #dbeafe;
    --purple: #8b5cf6;
    --purple-light: #ede9fe;
    --success: #10b981;
    --warning: #f59e0b;
    --warning-light: #fef3c7;
    --danger: #ef4444;
    --danger-light: #fee2e2;
    --info: #06b6d4;
    --bg-page: #f8fafc;
    --bg-card: #ffffff;
    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;
    --border-color: #e2e8f0;
    --border-light: #f1f5f9;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-md: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    --shadow-glow: 0 0 40px rgba(34, 197, 94, 0.15);
    --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition: 200ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-spring: 500ms cubic-bezier(0.34, 1.56, 0.64, 1);
    --radius-sm: 0.375rem;
    --radius: 0.5rem;
    --radius-md: 0.75rem;
    --radius-lg: 1rem;
    --radius-xl: 1.5rem;
}
*, *::before, *::after { box-sizing: border-box; }
body {
    margin: 0; font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, var(--bg-page) 0%, #e2e8f0 100%);
    min-height: 100vh; color: var(--text-primary);
    padding-top: 80px; line-height: 1.6; overflow-x: hidden;
}

/* PARTICLES */
.particles-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; overflow: hidden; }
.particle { position: absolute; border-radius: 50%; opacity: 0.15; animation: floatParticle 20s infinite ease-in-out; }
.particle:nth-child(1) { width: 300px; height: 300px; background: var(--primary); top: -100px; left: -100px; animation-delay: 0s; }
.particle:nth-child(2) { width: 200px; height: 200px; background: var(--secondary); top: 50%; right: -50px; animation-delay: -5s; }
.particle:nth-child(3) { width: 150px; height: 150px; background: var(--purple); bottom: 10%; left: 20%; animation-delay: -10s; }
.particle:nth-child(4) { width: 100px; height: 100px; background: var(--warning); top: 30%; left: 60%; animation-delay: -15s; }
@keyframes floatParticle {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(30px, -30px) scale(1.05); }
    50% { transform: translate(-20px, 20px) scale(0.95); }
    75% { transform: translate(15px, 15px) scale(1.02); }
}

/* TOAST */
#toast-container {
    position: fixed; top: 100px; right: 24px; z-index: 10000;
    display: flex; flex-direction: column; gap: 12px; pointer-events: none;
}
.toast {
    min-width: 320px; padding: 16px 20px; border-radius: var(--radius-lg);
    color: #fff; display: flex; align-items: center; gap: 12px;
    pointer-events: auto; backdrop-filter: blur(12px);
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    opacity: 0; transform: translateX(100px); animation: toastIn 0.4s forwards, toastOut 0.4s 4.5s forwards;
    font-weight: 500; font-size: 0.9rem;
}
.toast.success { background: linear-gradient(135deg, #059669, #10b981); }
.toast.error { background: linear-gradient(135deg, #dc2626, #ef4444); }
.toast-icon { font-size: 1.2rem; }
@keyframes toastIn { to { opacity: 1; transform: translateX(0); } }
@keyframes toastOut { from { opacity: 1; } to { opacity: 0; transform: translateX(100px); } }

/* MAIN CONTAINER */
.main-container {
    max-width: 1500px; margin: 0 auto; padding: 24px 32px 60px;
    position: relative; z-index: 1;
}

/* PAGE HEADER */
.page-header {
    text-align: center; margin-bottom: 32px;
    animation: fadeInUp 0.6s ease-out;
}
.page-header h1 {
    font-size: 2.75rem; font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; margin: 0 0 8px; letter-spacing: -0.02em;
}
.page-header p { color: var(--text-secondary); font-size: 1.1rem; margin: 0; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

/* STATS CARDS */
.stats-grid {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 20px; margin-bottom: 28px;
}
.stat-card {
    background: var(--bg-card); border-radius: var(--radius-xl); padding: 24px;
    border: 1px solid var(--border-color); position: relative; overflow: hidden;
    display: flex; align-items: center; gap: 20px;
    box-shadow: var(--shadow);
    cursor: pointer; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    opacity: 0; transform: translateY(30px);
    animation: cardSlideIn 0.5s ease-out forwards;
}
.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.15s; }
.stat-card:nth-child(3) { animation-delay: 0.2s; }
@keyframes cardSlideIn { to { opacity: 1; transform: translateY(0); } }
.stat-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--card-accent), var(--card-accent-light, var(--card-accent)));
    opacity: 0; transition: opacity 0.3s ease;
}
.stat-card:hover::before { opacity: 1; }
.stat-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg), 0 0 0 1px var(--card-accent);
}
.stat-card.card-green { --card-accent: var(--primary); }
.stat-card.card-blue { --card-accent: var(--secondary); }
.stat-card.card-orange { --card-accent: var(--warning); }
.stat-icon-wrap {
    width: 56px; height: 56px; border-radius: var(--radius-lg);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    transition: transform 0.3s ease;
}
.stat-card:hover .stat-icon-wrap { transform: scale(1.1) rotate(-5deg); }
.stat-card.card-green .stat-icon-wrap { background: var(--primary-light); color: var(--primary-dark); }
.stat-card.card-blue .stat-icon-wrap { background: var(--secondary-light); color: var(--secondary); }
.stat-card.card-orange .stat-icon-wrap { background: var(--warning-light); color: #d97706; }
.stat-icon-wrap i { font-size: 1.4rem; }
.stat-label { font-size: 0.85rem; font-weight: 500; color: var(--text-secondary); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
.stat-value {
    font-size: 2rem; font-weight: 700; line-height: 1.1; color: var(--text-primary);
}
.stat-card.card-green .stat-value { color: var(--primary-dark); }
.stat-card.card-blue .stat-value { color: var(--secondary); }
.stat-card.card-orange .stat-value { color: #d97706; }

/* SHIMMER */
@keyframes shimmer {
    0% { background-position: -200px 0; }
    100% { background-position: calc(200px + 100%) 0; }
}

/* FILTER CARD */
.filter-card {
    background: var(--bg-card); border-radius: var(--radius-xl); padding: 20px 24px;
    box-shadow: var(--shadow); border: 1px solid var(--border-color);
    margin-bottom: 24px;
    display: flex; flex-wrap: wrap; align-items: flex-end; gap: 16px;
    animation: fadeInUp 0.6s ease-out 0.25s both;
}
.filter-field { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 200px; }
.filter-field label {
    font-size: 0.8rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.5px; color: var(--text-secondary);
}
.filter-input {
    padding: 12px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-md);
    font-size: 0.95rem; font-family: 'Inter', sans-serif; color: var(--text-primary);
    background: var(--bg-page); outline: none; transition: all var(--transition);
    width: 100%;
}
.filter-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); background: var(--bg-card); }
.filter-input::placeholder { color: var(--text-muted); }
select.filter-input {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 14px center; padding-right: 36px;
}
.filter-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }

/* BUTTONS */
.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 24px; border: none; border-radius: var(--radius-md);
    font-size: 0.95rem; font-weight: 600; font-family: 'Inter', sans-serif;
    cursor: pointer; transition: all var(--transition); white-space: nowrap;
    position: relative; overflow: hidden;
}
.btn::after {
    content: ''; position: absolute; width: 100%; height: 100%; top: 0; left: 0;
    background: linear-gradient(rgba(255,255,255,0.2), rgba(255,255,255,0)); opacity: 0; transition: opacity 0.2s;
}
.btn:hover::after { opacity: 1; }
.btn svg { width: 18px; height: 18px; flex-shrink: 0; }
.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff; box-shadow: 0 4px 14px var(--primary-glow);
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px var(--primary-glow); }
.btn-secondary {
    background: var(--border-light); color: var(--text-secondary);
}
.btn-secondary:hover { background: var(--border-color); color: var(--text-primary); }
.btn-purple {
    background: linear-gradient(135deg, var(--purple), #7c3aed);
    color: #fff; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}
.btn-purple:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4); }
.btn-danger {
    background: linear-gradient(135deg, var(--danger), #dc2626);
    color: #fff; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}
.btn-danger:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4); }

/* REPORT SECTION */
#report-section {
    display: none; background: var(--bg-card); border-radius: var(--radius-xl); padding: 28px;
    box-shadow: var(--shadow); border: 1px solid var(--border-color);
    margin-bottom: 28px; animation: fadeSlideIn 0.3s ease-out;
}
#report-section h2 { font-size: 1.4rem; font-weight: 700; margin: 0 0 20px; display: flex; align-items: center; gap: 10px; }
#report-section h2 i { color: var(--purple); }
#report-section h3 { font-size: 1rem; font-weight: 600; color: var(--text-secondary); text-align: center; margin-bottom: 12px; }
@keyframes fadeSlideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

/* TABLE */
.table-section { animation: fadeInUp 0.6s ease-out 0.35s both; }
.table-wrapper {
    background: var(--bg-card); border-radius: var(--radius-xl); overflow: hidden;
    box-shadow: var(--shadow); border: 1px solid var(--border-color);
}
.table-header-bar {
    padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;
    border-bottom: 1px solid var(--border-color);
}
.table-title {
    font-size: 1.15rem; font-weight: 700; color: var(--text-primary);
    display: flex; align-items: center; gap: 10px;
}
.table-title i { color: var(--primary); font-size: 1.1rem; }
.table-count {
    background: var(--primary-light); color: var(--primary-dark);
    padding: 4px 12px; border-radius: 999px; font-size: 0.85rem; font-weight: 600;
}
.table-wrapper .overflow-x-auto { overflow-x: auto; }
.inv-table { width: 100%; border-collapse: collapse; }
.inv-table thead { background: linear-gradient(180deg, #f8fafc, #f1f5f9); }
.inv-table th {
    padding: 14px 16px; text-align: left; font-size: 0.78rem;
    font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    color: var(--text-secondary); border-bottom: 2px solid var(--border-color);
    white-space: nowrap;
}
.inv-table th.sortable { cursor: pointer; user-select: none; transition: color var(--transition); }
.inv-table th.sortable:hover { color: var(--primary); }
.inv-table th .sort-icon { opacity: 0.3; margin-left: 6px; font-size: 0.7rem; }
.inv-table th.active .sort-icon { opacity: 1; color: var(--primary); }
.inv-table td {
    padding: 14px 16px; font-size: 0.9rem; border-bottom: 1px solid var(--border-light);
    vertical-align: middle;
}
.inv-table tbody tr { transition: all var(--transition-fast); }
.inv-table tbody tr:hover { background: rgba(34, 197, 94, 0.04); }
.inv-table tbody tr.selected { background: rgba(34, 197, 94, 0.08) !important; }
.item-thumbnail {
    width: 48px; height: 48px; object-fit: cover; border-radius: var(--radius-md);
    border: 2px solid var(--border-color); transition: transform var(--transition);
}
.inv-table tbody tr:hover .item-thumbnail { transform: scale(1.08); }
.item-name { font-weight: 600; color: var(--text-primary); }
.item-id { color: var(--text-muted); font-size: 0.82rem; font-weight: 500; }

/* CATEGORY BADGE */
.category-badge {
    background: var(--border-light); padding: 4px 12px; border-radius: 20px;
    font-size: 0.82rem; font-weight: 500; color: var(--text-secondary);
    display: inline-block;
}

/* QUANTITY BADGE */
.qty-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 36px; padding: 4px 10px; border-radius: 8px; font-weight: 700; font-size: 0.88rem;
}
.qty-ok { background: var(--primary-light); color: var(--primary-dark); }
.qty-low { background: var(--danger-light); color: var(--danger); }

/* ACTION BUTTONS */
.action-btn {
    width: 36px; height: 36px; border: none; border-radius: var(--radius-md);
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all var(--transition); font-size: 0.85rem;
    color: #fff;
}
.action-btn:disabled { opacity: 0.35; cursor: not-allowed; }
.barcode-btn { background: #475569; }
.barcode-btn:hover:not(:disabled) { background: #334155; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(71, 85, 105, 0.3); }
.edit-btn { background: var(--secondary); }
.edit-btn:hover { background: var(--secondary-dark); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.delete-btn { background: var(--danger); }
.delete-btn:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }
.actions-cell { display: flex; gap: 8px; justify-content: flex-end; }

/* PAGINATION */
#pagination-controls {
    display: flex; justify-content: center; align-items: center;
    padding: 20px; gap: 6px;
}
.page-btn {
    min-width: 40px; height: 40px; border: 2px solid var(--border-color);
    background: var(--bg-card); color: var(--text-secondary);
    border-radius: var(--radius-md); cursor: pointer;
    font-size: 0.88rem; font-weight: 600; font-family: 'Inter', sans-serif;
    transition: all var(--transition); display: flex; align-items: center; justify-content: center;
}
.page-btn:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
.page-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 4px 12px var(--primary-glow); }
.page-btn:disabled { cursor: not-allowed; opacity: 0.4; background: var(--border-light); }

/* BULK ACTIONS */
#bulk-actions-bar {
    position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%);
    background: linear-gradient(135deg, #1e293b, #334155);
    color: #fff; padding: 16px 24px; border-radius: var(--radius-xl);
    box-shadow: 0 20px 40px rgba(0,0,0,0.25); display: flex; align-items: center;
    gap: 20px; z-index: 1500; transition: bottom 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    backdrop-filter: blur(12px);
}
#bulk-actions-bar.visible { bottom: 24px; }
#bulk-actions-count { font-weight: 600; font-size: 0.9rem; }

/* SKELETON */
.skeleton-row td { padding: 14px 16px; }
.skeleton {
    background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
    background-size: 400px 100%;
    border-radius: var(--radius-sm);
    animation: shimmer 1.5s ease-in-out infinite;
}

/* EMPTY STATE */
#empty-state { text-align: center; padding: 60px 20px; }
#empty-state i { font-size: 4rem; color: var(--text-muted); margin-bottom: 16px; }
#empty-state p { font-size: 1.1rem; color: var(--text-secondary); font-weight: 500; }

/* CHECKBOX */
.inv-checkbox {
    width: 18px; height: 18px; border-radius: 6px; border: 2px solid var(--border-color);
    appearance: none; -webkit-appearance: none; cursor: pointer;
    transition: all var(--transition); background: var(--bg-card); position: relative;
}
.inv-checkbox:checked {
    background: var(--primary); border-color: var(--primary);
}
.inv-checkbox:checked::after {
    content: ''; position: absolute; top: 2px; left: 5px;
    width: 5px; height: 10px; border: solid #fff;
    border-width: 0 2px 2px 0; transform: rotate(45deg);
}
.inv-checkbox:focus { box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2); }

/* MODALS — Use ID selectors to override header-styles.css .modal-overlay */
#itemModal, #confirmModal, #barcodeModal {
    position: fixed !important; inset: 0 !important; background: rgba(15, 23, 42, 0.6) !important;
    display: none !important; align-items: center !important; justify-content: center !important;
    z-index: 99999 !important;
    backdrop-filter: blur(4px) !important;
    opacity: 1 !important; visibility: visible !important; pointer-events: auto !important;
}
#itemModal.visible, #confirmModal.visible, #barcodeModal.visible {
    display: flex !important;
}
.modal-content {
    background: var(--bg-card); border-radius: var(--radius-xl);
    box-shadow: 0 25px 60px rgba(0,0,0,0.25); max-height: 90vh;
    display: flex; flex-direction: column;
    transform: translateY(20px) scale(0.96);
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    overflow: hidden;
}
#itemModal.visible .modal-content,
#confirmModal.visible .modal-content,
#barcodeModal.visible .modal-content { transform: translateY(0) scale(1); }
#itemModal .modal-content { max-width: 850px; width: 95%; }
#confirmModal .modal-content, #barcodeModal .modal-content { max-width: 480px; width: 95%; text-align: center; }
.modal-header {
    padding: 20px 28px; border-bottom: 1px solid var(--border-color);
    display: flex; justify-content: space-between; align-items: center;
    background: linear-gradient(180deg, #f0fdf4, #fff);
}
.modal-header h2 { font-size: 1.3rem; font-weight: 700; margin: 0; color: var(--text-primary); }
.modal-close-btn {
    width: 36px; height: 36px; border-radius: 10px; border: none;
    background: var(--border-light); cursor: pointer; display: flex;
    align-items: center; justify-content: center; color: var(--text-secondary);
    font-size: 1.1rem; transition: all var(--transition);
}
.modal-close-btn:hover { background: var(--danger-light); color: var(--danger); transform: rotate(90deg); }
.modal-body { padding: 24px 28px; overflow-y: auto; }
.modal-footer {
    padding: 16px 28px; border-top: 1px solid var(--border-color);
    background: linear-gradient(180deg, #fff, #f8fafc);
    display: flex; justify-content: flex-end; gap: 10px;
    border-bottom-left-radius: var(--radius-xl);
    border-bottom-right-radius: var(--radius-xl);
}
.modal-footer .btn-cancel {
    padding: 10px 20px; background: var(--bg-card); border: 2px solid var(--border-color);
    border-radius: var(--radius-md); color: var(--text-secondary); font-weight: 600;
    font-size: 0.9rem; cursor: pointer; font-family: 'Inter', sans-serif;
    transition: all var(--transition);
}
.modal-footer .btn-cancel:hover { border-color: var(--text-muted); color: var(--text-primary); }
.modal-footer .btn-save {
    padding: 10px 24px; border: none; border-radius: var(--radius-md);
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff; font-weight: 700; font-size: 0.9rem; cursor: pointer;
    font-family: 'Inter', sans-serif; box-shadow: 0 4px 12px var(--primary-glow);
    transition: all var(--transition);
}
.modal-footer .btn-save:hover { transform: translateY(-1px); box-shadow: 0 6px 16px var(--primary-glow); }

/* TABS */
.tab-nav {
    display: flex; border-bottom: 2px solid var(--border-light); margin-bottom: 24px;
    gap: 4px; overflow-x: auto;
}
.tab-button {
    padding: 10px 18px; border: none; background: transparent;
    cursor: pointer; font-size: 0.88rem; font-weight: 600;
    color: var(--text-muted); border-bottom: 3px solid transparent;
    transition: all var(--transition); font-family: 'Inter', sans-serif;
    white-space: nowrap; border-radius: var(--radius-sm) var(--radius-sm) 0 0;
}
.tab-button:hover { color: var(--text-primary); background: var(--border-light); }
.tab-button.active { color: var(--primary); border-bottom-color: var(--primary); background: var(--primary-light); }
.tab-content { display: none; animation: fadeIn 0.3s ease-out; }
.tab-content.active { display: block; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* FORM FIELDS INSIDE MODAL */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-grid .col-span-full { grid-column: 1 / -1; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label {
    font-size: 0.78rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.5px; color: var(--text-secondary);
}
.form-group input, .form-group select, .form-group textarea {
    padding: 10px 14px; border: 2px solid var(--border-color); border-radius: var(--radius-md);
    font-size: 0.9rem; font-family: 'Inter', sans-serif; color: var(--text-primary);
    background: var(--bg-card); outline: none; transition: all var(--transition);
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: var(--primary); box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
}
.form-group select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; padding-right: 36px; }
.form-group input[type="file"] { padding: 8px; font-size: 0.85rem; }
.form-group input[readonly] { background: var(--border-light); color: var(--text-muted); }

/* IMAGE PREVIEW */
#imagePreviewContainer {
    border: 2px dashed var(--border-color); border-radius: var(--radius-lg);
    padding: 12px; background: var(--border-light);
    display: none; justify-content: center; align-items: center;
    height: 200px; position: relative; margin-top: 12px;
}
#imagePreviewContainer.show { display: flex; }
#itemImagePreview { max-width: 100%; height: 100%; object-fit: contain; border-radius: var(--radius-md); }
#clearImageBtn {
    position: absolute; top: 8px; right: 8px; width: 28px; height: 28px;
    border-radius: 50%; background: var(--danger); color: #fff; border: none;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; transition: all var(--transition);
}
#clearImageBtn:hover { transform: scale(1.1); }

/* CONFIRM MODAL */
#confirmModal .modal-content { padding: 40px 32px; }
#confirmModal .confirm-icon { font-size: 3.5rem; color: var(--danger); margin-bottom: 16px; }
#confirmModal h2 { font-size: 1.5rem; font-weight: 700; margin-bottom: 8px; }
#confirmModal p { color: var(--text-secondary); margin-bottom: 28px; font-size: 0.95rem; }
#confirmModal .confirm-btns { display: flex; justify-content: center; gap: 12px; }

/* BARCODE MODAL */
#barcodeModal .modal-header { padding-bottom: 16px; }
#barcodeModal .modal-body { display: flex; flex-direction: column; align-items: center; padding: 24px; }

/* RESPONSIVE */
@media (max-width: 1024px) {
    .stats-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .main-container { padding: 16px 16px 40px; }
    .page-header h1 { font-size: 1.8rem; }
    .stats-grid { grid-template-columns: 1fr; }
    .filter-card { flex-direction: column; }
    .filter-field { min-width: 100%; }
    .form-grid { grid-template-columns: 1fr; }
    .inv-table th, .inv-table td { padding: 10px 12px; font-size: 0.82rem; }
    .tab-button { padding: 8px 12px; font-size: 0.82rem; }
    .table-header-bar { flex-direction: column; gap: 12px; align-items: flex-start; }
}
@media print {
    .particles-container, #toast-container, #bulk-actions-bar, .filter-card, #pagination-controls, .table-header-bar { display: none !important; }
    body { padding-top: 0; background: #fff; }
    .table-wrapper { box-shadow: none; border: 1px solid #ddd; }
}
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <!-- Particles -->
    <div class="particles-container">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div id="toast-container"></div>

    <main class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Panoramica Inventario</h1>
            <p>Gestione completa dei prodotti e del magazzino</p>
        </div>

        <!-- Dashboard Stats -->
        <div id="dashboard" class="stats-grid">
            <div class="stat-card card-green">
                <div class="stat-icon-wrap"><i class="fas fa-euro-sign"></i></div>
                <div>
                    <div class="stat-label">Valore Totale Inventario</div>
                    <div id="totalValue" class="stat-value">&euro; 0,00</div>
                </div>
            </div>
            <div class="stat-card card-blue">
                <div class="stat-icon-wrap"><i class="fas fa-boxes-stacked"></i></div>
                <div>
                    <div class="stat-label">Articoli Totali</div>
                    <div id="totalItems" class="stat-value">0</div>
                </div>
            </div>
            <div class="stat-card card-orange">
                <div class="stat-icon-wrap"><i class="fas fa-triangle-exclamation"></i></div>
                <div>
                    <div class="stat-label">Articoli in Esaurimento</div>
                    <div id="lowStockItems" class="stat-value">0</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <div class="filter-field">
                <label for="searchInput"><i class="fas fa-search" style="margin-right: 4px;"></i>Cerca articolo</label>
                <input type="text" id="searchInput" class="filter-input" placeholder="Nome, ID, barcode...">
            </div>
            <div class="filter-field">
                <label for="categoryFilter"><i class="fas fa-filter" style="margin-right: 4px;"></i>Categoria</label>
                <select id="categoryFilter" class="filter-input"></select>
            </div>
            <div class="filter-actions">
                <button id="toggleReportBtn" class="btn btn-purple">
                    <i class="fas fa-chart-pie"></i> Report
                </button>
                <button id="exportCsvBtn" class="btn btn-secondary">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
                <button id="addArticleBtn" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Aggiungi Articolo
                </button>
            </div>
        </div>

        <!-- Report Section -->
        <div id="report-section">
            <h2><i class="fas fa-chart-bar"></i>Analisi Inventario</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
                <div>
                    <h3>Valore per Categoria</h3>
                    <canvas id="categoryValueChart"></canvas>
                </div>
                <div>
                    <h3>Top 5 Articoli per Quantit&agrave;</h3>
                    <canvas id="topItemsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-section">
            <div class="table-wrapper">
                <div class="table-header-bar">
                    <div class="table-title"><i class="fas fa-table-list"></i> Articoli in Magazzino <span id="tableCountBadge" class="table-count">0</span></div>
                </div>
                <div class="overflow-x-auto">
                    <table class="inv-table">
                        <thead id="tableHeader">
                            <tr>
                                <th style="width: 48px; text-align: center;"><input type="checkbox" id="selectAllCheckbox" class="inv-checkbox"></th>
                                <th style="width: 72px;">Immagine</th>
                                <th class="sortable" data-sort="id">ID <i class="fas fa-sort sort-icon"></i></th>
                                <th class="sortable" data-sort="name">Nome <i class="fas fa-sort sort-icon"></i></th>
                                <th class="sortable" data-sort="quantity">Quantit&agrave; <i class="fas fa-sort sort-icon"></i></th>
                                <th class="sortable" data-sort="prezzo_vendita1">Prezzo (&euro;) <i class="fas fa-sort sort-icon"></i></th>
                                <th>Categoria</th>
                                <th style="text-align: right;">Azioni</th>
                            </tr>
                        </thead>
                        <tbody id="inventoryTableBody"></tbody>
                    </table>
                </div>
            </div>

            <div id="pagination-controls"></div>
        </div>
    </main>

    <!-- Bulk Actions Bar -->
    <div id="bulk-actions-bar">
        <span id="bulk-actions-count" style="font-weight: 600;">0 articoli selezionati</span>
        <button id="bulk-delete-btn" class="btn btn-danger">
            <i class="fas fa-trash"></i> Elimina Selezionati
        </button>
    </div>

    <!-- Item Modal (Add/Edit) -->
    <div id="itemModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"></h2>
                <button type="button" class="modal-close-btn" onclick="closeItemModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="itemForm" style="display: flex; flex-direction: column; flex-grow: 1;" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="tab-nav">
                        <button type="button" class="tab-button active" onclick="showTab('principale')"><i class="fas fa-cube" style="margin-right: 6px;"></i>Principale</button>
                        <button type="button" class="tab-button" onclick="showTab('dettagli')"><i class="fas fa-tags" style="margin-right: 6px;"></i>Dettagli</button>
                        <button type="button" class="tab-button" onclick="showTab('categorizzazione')"><i class="fas fa-folder-tree" style="margin-right: 6px;"></i>Categorie</button>
                        <button type="button" class="tab-button" onclick="showTab('immagine')"><i class="fas fa-image" style="margin-right: 6px;"></i>Immagine</button>
                        <button type="button" class="tab-button" onclick="showTab('storico')"><i class="fas fa-clock-rotate-left" style="margin-right: 6px;"></i>Storico</button>
                    </div>

                    <!-- Tab Principale -->
                    <div id="tab-principale" class="tab-content active">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="itemName">Nome Articolo *</label>
                                <input type="text" id="itemName" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="itemQuantity">Quantit&agrave; *</label>
                                <input type="number" id="itemQuantity" name="quantity" required min="0">
                            </div>
                            <div class="form-group col-span-full">
                                <label for="itemDescription">Descrizione</label>
                                <textarea id="itemDescription" name="description" rows="3"></textarea>
                            </div>
                            <input type="hidden" id="itemId" name="id">
                            <input type="hidden" id="itemDataCreazione" name="data_creazione">
                        </div>
                    </div>

                    <!-- Tab Dettagli -->
                    <div id="tab-dettagli" class="tab-content">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="itemPrezzoVendita1">Prezzo Vendita 1 (&euro;)</label>
                                <input type="number" id="itemPrezzoVendita1" name="prezzo_vendita1" min="0" step="0.01">
                            </div>
                            <div class="form-group">
                                <label for="itemPrezzoVendita2">Prezzo Vendita 2 (&euro;)</label>
                                <input type="number" id="itemPrezzoVendita2" name="prezzo_vendita2" min="0" step="0.01">
                            </div>
                            <div class="form-group">
                                <label for="itemPrezzoAcquisto">Prezzo Acquisto (&euro;)</label>
                                <input type="number" id="itemPrezzoAcquisto" name="prezzo_acquisto" min="0" step="0.01">
                            </div>
                            <div class="form-group">
                                <label for="itemBarcode">Barcode</label>
                                <input type="text" id="itemBarcode" name="barcode">
                            </div>
                            <div class="form-group">
                                <label for="itemImei">IMEI</label>
                                <input type="text" id="itemImei" name="imei">
                            </div>
                        </div>
                    </div>

                    <!-- Tab Categorie -->
                    <div id="tab-categorizzazione" class="tab-content">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="itemCategoria">Categoria</label>
                                <select id="itemCategoria" name="categoria"></select>
                            </div>
                            <div class="form-group">
                                <label for="itemSottocategoria">Sottocategoria</label>
                                <select id="itemSottocategoria" name="sottocategoria"></select>
                            </div>
                            <div class="form-group">
                                <label for="itemSottoSottocategoria">Sotto Sottocategoria</label>
                                <select id="itemSottoSottocategoria" name="sottosottocategoria"></select>
                            </div>
                            <div class="form-group">
                                <label for="itemTipoProdotto">Tipo Prodotto</label>
                                <input type="text" id="itemTipoProdotto" name="tipo_prodotto">
                            </div>
                        </div>
                    </div>

                    <!-- Tab Immagine -->
                    <div id="tab-immagine" class="tab-content">
                        <div class="form-group">
                            <label for="itemImage">Carica Immagine</label>
                            <input type="file" id="itemImage" name="image" accept="image/*">
                        </div>
                        <div id="imagePreviewContainer">
                            <img id="itemImagePreview" src="#" alt="Anteprima" style="display: none;">
                            <button type="button" id="clearImageBtn" title="Rimuovi"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    <!-- Tab Storico -->
                    <div id="tab-storico" class="tab-content">
                        <p id="log-loading-message" style="text-align: center; color: var(--text-muted);">Caricamento storico...</p>
                        <div id="log-container" style="max-height: 260px; overflow-y: auto;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeItemModal()" class="btn-cancel">Annulla</button>
                    <button type="submit" id="saveBtn" class="btn-save"><i class="fas fa-save" style="margin-right: 6px;"></i>Salva</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm Delete Modal -->
    <div id="confirmModal">
        <div class="modal-content" style="padding: 40px 32px; text-align: center;">
            <div class="confirm-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h2>Conferma Eliminazione</h2>
            <p>Sei sicuro di voler eliminare <span id="confirmItemName" style="font-weight: 700;"></span>? L'azione &egrave; irreversibile.</p>
            <div class="confirm-btns">
                <button type="button" id="cancelDeleteBtn" class="btn-cancel" style="padding: 10px 24px; border: 2px solid var(--border-color); border-radius: var(--radius-md); background: var(--bg-card); color: var(--text-secondary); font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif;">Annulla</button>
                <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Elimina</button>
            </div>
        </div>
    </div>

    <!-- Barcode Modal -->
    <div id="barcodeModal">
        <div class="modal-content" style="max-width: 480px; width: 95%;">
            <div class="modal-header">
                <h2 id="barcodeModalTitle"></h2>
                <button type="button" class="modal-close-btn" onclick="closeBarcodeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" style="display: flex; flex-direction: column; align-items: center;">
                <canvas id="barcodeCanvas"></canvas>
            </div>
            <div class="modal-footer">
                <button id="printBarcodeBtn" class="btn btn-primary"><i class="fas fa-print" style="margin-right: 6px;"></i>Stampa</button>
            </div>
        </div>
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
        const formFields = {
            id: document.getElementById("itemId"),
            name: document.getElementById("itemName"),
            quantity: document.getElementById("itemQuantity"),
            description: document.getElementById("itemDescription"),
            prezzo_vendita1: document.getElementById("itemPrezzoVendita1"),
            prezzo_vendita2: document.getElementById("itemPrezzoVendita2"),
            prezzo_acquisto: document.getElementById("itemPrezzoAcquisto"),
            categoria: document.getElementById("itemCategoria"),
            sottocategoria: document.getElementById("itemSottocategoria"),
            sottosottocategoria: document.getElementById("itemSottoSottocategoria"),
            tipo_prodotto: document.getElementById("itemTipoProdotto"),
            barcode: document.getElementById("itemBarcode"),
            imei: document.getElementById("itemImei"),
            data_creazione: document.getElementById("itemDataCreazione"),
            image: document.getElementById("itemImage")
        };
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
            toast.addEventListener('animationend', (e) => { if (e.animationName === 'toastOut') toast.remove(); });
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
                    allInventoryItems = [];
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
                if (!item) return false;
                const name = item.name || "";
                const id = item.id || "";
                const category = item.categoria || "";
                const matchesSearch = searchTerm === '' ||
                    name.toLowerCase().includes(searchTerm) ||
                    String(id).toLowerCase().includes(searchTerm) ||
                    String(item.barcode || '').toLowerCase().includes(searchTerm);
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
            // Update table count badge
            const badge = document.getElementById('tableCountBadge');
            if (badge) badge.textContent = filtered.length;
        }

        function renderTable(items) {
            inventoryTableBody.innerHTML = '';
            if (items.length === 0) {
                const message = (searchInput.value || categoryFilter.value) ? "Nessun articolo corrisponde ai filtri." : "L'inventario \u00e8 vuoto.";
                showEmptyState(message);
            } else {
                items.forEach(item => {
                    const row = document.createElement('tr');
                    const itemName = item.name || 'Senza nome';
                    if (selectedItems.has(String(item.id))) row.classList.add('selected');
                    const imageSrc = item.immagine ? `${UPLOADS_DIR}${item.immagine}` : 'https://placehold.co/100x100/e2e8f0/94a3b8?text=N/A';
                    const hasBarcode = item.barcode && String(item.barcode).trim() !== '';
                    const qty = parseInt(item.quantity || 0);
                    const qtyBadgeClass = qty < LOW_STOCK_THRESHOLD ? 'qty-badge qty-low' : 'qty-badge qty-ok';

                    row.innerHTML = `
                        <td style="text-align: center;"><input type="checkbox" data-id="${item.id}" class="row-checkbox inv-checkbox" ${selectedItems.has(String(item.id)) ? 'checked' : ''}></td>
                        <td><img src="${imageSrc}" alt="${itemName}" class="item-thumbnail"></td>
                        <td class="item-id">#${item.id || 'N/A'}</td>
                        <td class="item-name">${itemName}</td>
                        <td><span class="${qtyBadgeClass}">${qty}</span></td>
                        <td style="font-weight: 600;">&euro; ${parseFloat(item.prezzo_vendita1 || 0).toFixed(2)}</td>
                        <td><span class="category-badge">${item.categoria || 'N/D'}</span></td>
                        <td><div class="actions-cell">
                            <button data-id="${item.id}" data-barcode="${item.barcode || ''}" class="action-btn barcode-btn" ${!hasBarcode ? 'disabled' : ''} title="${hasBarcode ? 'Mostra barcode' : 'Barcode non impostato'}"><i class="fas fa-barcode"></i></button>
                            <button data-id="${item.id}" class="action-btn edit-btn"><i class="fas fa-pencil-alt"></i></button>
                            <button data-id="${item.id}" data-name="${itemName}" class="action-btn delete-btn"><i class="fas fa-trash"></i></button>
                        </div></td>
                    `;
                    inventoryTableBody.appendChild(row);
                });
            }
        }
        
        function updateDashboard(items) {
            const totalValue = items.reduce((sum, item) => sum + ((item.quantity || 0) * (item.prezzo_acquisto || 0)), 0);
            const totalItems = items.reduce((sum, item) => sum + parseInt(item.quantity || 0), 0);
            const lowStockItems = items.filter(item => (item.quantity || 0) < LOW_STOCK_THRESHOLD).length;
            
            animateValue(document.getElementById('totalValue'), totalValue, true);
            animateValue(document.getElementById('totalItems'), totalItems, false);
            animateValue(document.getElementById('lowStockItems'), lowStockItems, false);
        }

        function animateValue(element, target, isCurrency) {
            const duration = 800;
            const start = performance.now();
            const startVal = 0;
            function update(now) {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = startVal + (target - startVal) * eased;
                if (isCurrency) {
                    element.textContent = '\u20AC ' + current.toFixed(2);
                } else {
                    element.textContent = Math.round(current);
                }
                if (progress < 1) requestAnimationFrame(update);
            }
            requestAnimationFrame(update);
        }

        function renderPagination(totalItems) {
            const totalPages = Math.ceil(totalItems / ITEMS_PER_PAGE);
            paginationControls.innerHTML = '';
            if (totalPages <= 1) return;
            const prevBtn = document.createElement('button');
            prevBtn.innerHTML = '&laquo;';
            prevBtn.className = 'page-btn';
            prevBtn.disabled = currentPage === 1;
            prevBtn.onclick = () => { currentPage--; processAndRenderData(); };
            paginationControls.appendChild(prevBtn);
            for (let i = 1; i <= totalPages; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = 'page-btn';
                if (i === currentPage) pageBtn.classList.add('active');
                pageBtn.onclick = () => { currentPage = i; processAndRenderData(); };
                paginationControls.appendChild(pageBtn);
            }
            const nextBtn = document.createElement('button');
            nextBtn.innerHTML = '&raquo;';
            nextBtn.className = 'page-btn';
            nextBtn.disabled = currentPage === totalPages;
            nextBtn.onclick = () => { currentPage++; processAndRenderData(); };
            paginationControls.appendChild(nextBtn);
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
            if (categoryValueChart) categoryValueChart.destroy();
            categoryValueChart = new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(categoryValues),
                    datasets: [{
                        label: 'Valore Inventario',
                        data: Object.values(categoryValues),
                        backgroundColor: ['#22c55e', '#3b82f6', '#f97316', '#ef4444', '#8b5cf6', '#f59e0b', '#64748b'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    plugins: { legend: { position: 'bottom', labels: { padding: 16, font: { family: 'Inter', size: 12 } } } },
                    cutout: '65%'
                }
            });
            const sortedByQuantity = [...allInventoryItems].sort((a, b) => (b.quantity || 0) - (a.quantity || 0)).slice(0, 5);
            const topCtx = document.getElementById('topItemsChart').getContext('2d');
            if (topItemsChart) topItemsChart.destroy();
            topItemsChart = new Chart(topCtx, {
                type: 'bar',
                data: {
                    labels: sortedByQuantity.map(item => item.name || 'Senza nome'),
                    datasets: [{
                        label: 'Quantit\u00e0 in magazzino',
                        data: sortedByQuantity.map(item => item.quantity || 0),
                        backgroundColor: '#22c55e',
                        borderRadius: 8,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: { x: { grid: { display: false } }, y: { grid: { display: false } } }
                }
            });
        }

        function exportToCSV() {
            const headers = ['ID', 'Nome', 'Quantit\u00e0', 'Prezzo Vendita', 'Prezzo Acquisto', 'Categoria', 'Descrizione'];
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
            if (checkbox.checked) { selectedItems.add(id); }
            else { selectedItems.delete(id); }
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
            try {
                const e = await fetch(`${API_URL}?id=${id}`);
                if (!e.ok) throw new Error("Articolo non trovato");
                const t = await e.json();
                currentItemId = id;
                modalTitle.textContent = "Modifica Articolo";
                itemForm.reset();
                resetImagePreview();
                Object.keys(formFields).forEach(key => {
                    if (t[key] !== undefined && formFields[key].type !== 'file') {
                        formFields[key].value = t[key];
                    }
                });
                if (t.immagine) {
                    itemImagePreview.src = UPLOADS_DIR + t.immagine;
                    itemImagePreview.style.display = 'block';
                    imagePreviewContainer.classList.add('show');
                }
                await populateDropdowns(t.categoria, t.sottocategoria, t.sottosottocategoria);
                showTab('principale');
                itemModal.classList.add('visible');
                await fetchAndRenderAuditLog(id);
            } catch (err) {
                showToast("Errore nel caricamento dei dati dell'articolo.", 'error');
                console.error(err);
            }
        }
        
        async function fetchAndRenderAuditLog(itemId) {
            const logContainer = document.getElementById('log-container');
            const loadingMsg = document.getElementById('log-loading-message');
            logContainer.innerHTML = '';
            loadingMsg.textContent = 'Caricamento storico...';
            loadingMsg.style.display = 'block';
            try {
                const logData = [
                    { user: 'admin', action: 'Articolo creato', timestamp: '2025-09-10 10:30:00' },
                    { user: 'mario.rossi', action: 'Quantit\u00e0 modificata da 10 a 8', timestamp: '2025-09-11 15:00:00' }
                ];
                if (logData.length === 0) {
                    loadingMsg.textContent = 'Nessuna modifica registrata.';
                } else {
                    loadingMsg.style.display = 'none';
                    const list = document.createElement('ul');
                    list.style.cssText = 'list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px;';
                    logData.forEach(entry => {
                        const item = document.createElement('li');
                        item.style.cssText = 'padding: 12px 16px; background: var(--border-light); border-radius: var(--radius-md); border-left: 3px solid var(--primary);';
                        item.innerHTML = `<div style="font-weight: 600; font-size: 0.85rem; color: var(--text-primary);">${entry.action}</div><div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">${entry.timestamp} &mdash; ${entry.user}</div>`;
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
            if (!item) return;
            document.getElementById('barcodeModalTitle').textContent = `Barcode: ${item.name || 'Senza nome'}`;
            try {
                JsBarcode("#barcodeCanvas", barcodeValue);
                barcodeModal.classList.add('visible');
            } catch (error) {
                console.error("Errore generazione barcode:", error);
                showToast('Formato barcode non valido.', 'error');
            }
        }

        function closeBarcodeModal() { barcodeModal.classList.remove('visible'); }

        function showLoadingState() {
            inventoryTableBody.innerHTML = '';
            for (let i = 0; i < 5; i++) {
                const row = document.createElement('tr');
                row.className = 'skeleton-row';
                row.innerHTML = `
                    <td><div class="skeleton" style="height: 18px; width: 18px; margin: 0 auto;"></div></td>
                    <td><div class="skeleton" style="height: 48px; width: 48px; border-radius: var(--radius-md);"></div></td>
                    <td><div class="skeleton" style="height: 16px; width: 40px;"></div></td>
                    <td><div class="skeleton" style="height: 16px; width: 160px;"></div></td>
                    <td><div class="skeleton" style="height: 16px; width: 50px;"></div></td>
                    <td><div class="skeleton" style="height: 16px; width: 80px;"></div></td>
                    <td><div class="skeleton" style="height: 16px; width: 100px;"></div></td>
                    <td style="text-align: right;"><div class="skeleton" style="height: 36px; width: 130px; margin-left: auto;"></div></td>
                `;
                inventoryTableBody.appendChild(row);
            }
        }

        function showEmptyState(msg) {
            inventoryTableBody.innerHTML = `<tr><td colspan="8"><div id="empty-state"><i class="fas fa-box-open"></i><p>${msg}</p></div></td></tr>`;
        }
        
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
        
        function openAddModal() {
            currentItemId = null;
            modalTitle.textContent = "Aggiungi Nuovo Articolo";
            itemForm.reset();
            formFields.data_creazione.value = new Date().toISOString().slice(0, 10);
            resetImagePreview();
            populateDropdowns();
            showTab('principale');
            itemModal.classList.add('visible');
            setTimeout(() => formFields.barcode.focus(), 100);
        }

        function closeItemModal() { itemModal.classList.remove('visible'); }
        
        function openConfirmDeleteModal(id, name) {
            openConfirmationModal(`"${name}"`, () => handleDelete(id));
        }

        function closeConfirmDeleteModal() { confirmModal.classList.remove('visible'); }
        
        async function handleFormSubmit(e) {
            e.preventDefault();
            const formData = new FormData(itemForm);
            if (currentItemId) formData.append("_method", "PUT");
            try {
                const res = await fetch(API_URL + (currentItemId ? `?id=${currentItemId}` : ""), { method: "POST", body: formData });
                if (!res.ok) throw new Error("Errore di rete.");
                const result = await res.json();
                if (!result.success) throw new Error(result.message || "Errore sconosciuto.");
                showToast(currentItemId ? "Articolo modificato!" : "Articolo aggiunto!", "success");
                closeItemModal();
                await fetchData();
            } catch (e) {
                showToast(`Salvataggio fallito: ${e.message}`, "error");
            }
        }

        function populateCategoryFilter() {
            categoryFilter.innerHTML = '<option value="">Tutte le categorie</option>';
            const cats = [...new Set(fetchedCategories.map(e => e.categoria).filter(Boolean))];
            cats.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat;
                opt.textContent = cat;
                categoryFilter.appendChild(opt);
            });
        }
        
        async function populateDropdowns(selectedCategory = "", selectedSubcategory = "", selectedSubSubcategory = "") {
            const allCategories = [...new Set(fetchedCategories.map(cat => cat.categoria).filter(Boolean))];
            populateSelect(formFields.categoria, allCategories, selectedCategory);
            populateSelect(formFields.sottocategoria, [], "");
            populateSelect(formFields.sottosottocategoria, [], "");
            if (selectedCategory) {
                await handleCategoryChange(selectedSubcategory, selectedSubSubcategory);
            }
        }
        
        async function handleCategoryChange(selectedSub = "", selectedSubSub = "") {
            const category = formFields.categoria.value;
            const subcategories = [...new Set(fetchedCategories.filter(c => c.categoria === category).map(c => c.sottocategoria).filter(Boolean))];
            populateSelect(formFields.sottocategoria, subcategories, selectedSub);
            await handleSubcategoryChange(selectedSubSub);
        }

        async function handleSubcategoryChange(selected = "") {
            const category = formFields.categoria.value;
            const subcategory = formFields.sottocategoria.value;
            const subsubcategories = [...new Set(fetchedCategories.filter(c => c.categoria === category && c.sottocategoria === subcategory).map(c => c.sottosottocategoria).filter(Boolean))];
            populateSelect(formFields.sottosottocategoria, subsubcategories, selected);
        }

        function populateSelect(selectElement, options, selectedValue) {
            const finalSelected = selectedValue || "";
            selectElement.innerHTML = '<option value="">Seleziona...</option>';
            options.forEach(opt => {
                const optionEl = document.createElement("option");
                optionEl.value = opt;
                optionEl.textContent = opt;
                selectElement.appendChild(optionEl);
            });
            selectElement.value = finalSelected;
        }

        function resetImagePreview() {
            formFields.image.value = "";
            itemImagePreview.src = "#";
            itemImagePreview.style.display = "none";
            imagePreviewContainer.classList.remove("show");
        }

        function showTab(tabName) {
            document.querySelectorAll(".tab-content").forEach(el => el.classList.remove("active"));
            document.querySelectorAll(".tab-button").forEach(el => el.classList.remove("active"));
            document.getElementById(`tab-${tabName}`).classList.add("active");
            document.querySelector(`.tab-button[onclick="showTab('${tabName}')"]`).classList.add("active");
        }

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
            e.currentTarget.innerHTML = isVisible ? '<i class="fas fa-chart-pie" style="margin-right: 6px;"></i>Report' : '<i class="fas fa-eye-slash" style="margin-right: 6px;"></i>Nascondi';
        });
        document.getElementById('addArticleBtn').addEventListener('click', openAddModal);
        itemForm.addEventListener('submit', handleFormSubmit);
        formFields.image.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                itemImagePreview.src = URL.createObjectURL(file);
                itemImagePreview.style.display = 'block';
                imagePreviewContainer.classList.add('show');
            }
        });
        document.getElementById('clearImageBtn').addEventListener('click', resetImagePreview);
        formFields.categoria.addEventListener('change', () => handleCategoryChange());
        formFields.sottocategoria.addEventListener('change', () => handleSubcategoryChange());
        formFields.barcode.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                formFields.name.focus();
            }
        });
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                currentPage = 1;
                processAndRenderData();
            }
        });
        let inventoryScannerBuffer = '';
        let inventoryScannerTimer = null;
        document.addEventListener('keydown', (e) => {
            const target = e.target;
            const isEditable = target && (target.matches('input, textarea, select') || target.isContentEditable);
            if (isEditable) return;
            if (e.ctrlKey || e.metaKey || e.altKey) return;

            if (e.key === 'Enter') {
                if (inventoryScannerBuffer.length >= 4) {
                    e.preventDefault();
                    searchInput.value = inventoryScannerBuffer;
                    currentPage = 1;
                    processAndRenderData();
                }
                inventoryScannerBuffer = '';
                return;
            }

            if (e.key.length !== 1) return;
            inventoryScannerBuffer += e.key;
            clearTimeout(inventoryScannerTimer);
            inventoryScannerTimer = setTimeout(() => { inventoryScannerBuffer = ''; }, 120);
        });
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
