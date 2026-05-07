<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// NOTA: Poiché questo file ora include header.php, la gestione della sessione e della connessione
// al database viene ereditata da quel file. Rimuoviamo i duplicati da qui.
require_once 'db.php'; // Assicuriamoci che la connessione sia disponibile per le query in questa pagina.

if (!isset($conn)) {
    exit("Errore critico: Connessione al database non riuscita. Controllare db.php.");
}

$tickets_data = [];
$clienti_data = [];

try {
    // Recupero Clienti per il modale e i filtri
    $clienti_result = $conn->query("SELECT id, nome, cognome FROM clienti_nuovo ORDER BY cognome, nome");
    if ($clienti_result) {
        $clienti_data = $clienti_result->fetch_all(MYSQLI_ASSOC);
    }
    
    // La query principale ora carica TUTTI i ticket, il filtro è gestito da JS
    $sql = "SELECT t.*, c.nome AS cliente_nome, c.cognome AS cliente_cognome 
            FROM tickets t 
            LEFT JOIN clienti_nuovo c ON t.cliente_id = c.id 
            ORDER BY t.data_ultima_modifica DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        $tickets_data = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Calcolo statistiche
    $stats_aperti = 0;
    $stats_in_attesa = 0;
    $stats_critici = 0;

    foreach ($tickets_data as $ticket) {
        if (in_array($ticket['stato'], ['Aperto', 'In Lavorazione', 'In attesa di risposta cliente'])) {
            $stats_aperti++;
        }
        if ($ticket['stato'] === 'In attesa di risposta cliente') {
            $stats_in_attesa++;
        }
        if ($ticket['priorita'] === 'Critica' && !in_array($ticket['stato'], ['Risolto', 'Chiuso'])) {
            $stats_critici++;
        }
    }

} catch (Exception $e) {
    echo "Errore nel caricamento dati: " . $e->getMessage();
}

// Funzioni helper
function getPriorityColor($priority) {
    $map = ['Critica' => '#ef4444', 'Alta' => '#f59e0b', 'Media' => '#3b82f6', 'Bassa' => '#6b7280'];
    return $map[$priority] ?? '#d1d5db';
}
function getStatusBadgeClass($status) {
    $map = ['Aperto' => 'aperto', 'In Lavorazione' => 'in-lavorazione', 'In attesa di risposta cliente' => 'in-attesa', 'Risolto' => 'risolto', 'Chiuso' => 'chiuso'];
    return $map[$status] ?? 'chiuso';
}
function getAvatarInitials($nome, $cognome) {
    $nomeInit = !empty($nome) ? mb_substr($nome, 0, 1) : '';
    $cognomeInit = !empty($cognome) ? mb_substr($cognome, 0, 1) : '';
    return strtoupper($nomeInit . $cognomeInit);
}
function getAvatarColor($name) {
    $colors = ['#f87171', '#fb923c', '#facc15', '#4ade80', '#38bdf8', '#818cf8', '#c084fc', '#f472b6'];
    $hash = crc32($name);
    return $colors[abs($hash) % count($colors)];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Cruscotto Ticket</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=2">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --primary-light: #dcfce7;
            --secondary: #3b82f6;
            --secondary-light: #dbeafe;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --purple: #8b5cf6;
            --purple-light: #ede9fe;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.375rem;
            --radius: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #f8fafc 50%, #eff6ff 100%);
            min-height: 100vh;
            padding-top: 90px;
            color: var(--gray-700);
            line-height: 1.5;
        }
        
        .container { 
            max-width: 1600px; 
            margin: 0 auto; 
            padding: 2rem 1.5rem; 
        }
        
        /* PAGE HEADER */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            animation: fadeInDown 0.5s ease-out;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .page-title i {
            color: var(--primary);
            font-size: 1.75rem;
        }
        
        .btn-new-ticket {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            border-radius: var(--radius-lg);
            cursor: pointer;
            box-shadow: var(--shadow-md), 0 0 0 0 rgba(34, 197, 94, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .btn-new-ticket:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg), 0 0 0 4px rgba(34, 197, 94, 0.2);
        }
        
        .btn-new-ticket:active {
            transform: translateY(0);
        }
        
        /* SUMMARY CARDS */
        .summary-panel { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.5s ease-out backwards;
        }
        
        .summary-card:nth-child(1) { animation-delay: 0.1s; }
        .summary-card:nth-child(2) { animation-delay: 0.2s; }
        .summary-card:nth-child(3) { animation-delay: 0.3s; }
        .summary-card:nth-child(4) { animation-delay: 0.4s; }
        
        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, transparent 0%, rgba(255,255,255,0.5) 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .summary-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }
        
        .summary-card:hover::before {
            opacity: 1;
        }
        
        .summary-card.active {
            border-color: var(--primary);
            box-shadow: var(--shadow-lg), 0 0 0 3px rgba(34, 197, 94, 0.15);
        }
        
        .summary-card.green { border-left: 4px solid var(--primary); }
        .summary-card.purple { border-left: 4px solid var(--purple); }
        .summary-card.red { border-left: 4px solid var(--danger); }
        .summary-card.blue { border-left: 4px solid var(--secondary); }
        
        .summary-card-icon {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .summary-card-icon i {
            font-size: 1.5rem;
        }
        
        .summary-card.green .summary-card-icon { background: var(--primary-light); color: var(--primary-dark); }
        .summary-card.purple .summary-card-icon { background: var(--purple-light); color: var(--purple); }
        .summary-card.red .summary-card-icon { background: var(--danger-light); color: var(--danger); }
        .summary-card.blue .summary-card-icon { background: var(--secondary-light); color: var(--secondary); }
        
        .summary-card-content { flex: 1; }
        
        .summary-card-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--gray-800);
            line-height: 1;
            margin-bottom: 0.25rem;
        }
        
        .summary-card-label {
            font-size: 0.875rem;
            color: var(--gray-500);
            font-weight: 500;
        }
        
        /* FILTER BAR */
        .filter-bar {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            animation: fadeInUp 0.5s ease-out 0.3s backwards;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: 2fr repeat(3, 1fr) auto;
            gap: 1rem;
            align-items: end;
        }
        
        @media (max-width: 1024px) {
            .filter-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 640px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .filter-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        
        .search-wrapper {
            position: relative;
        }
        
        .search-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1rem;
        }
        
        .search-wrapper input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.2s;
            background: var(--gray-50);
        }
        
        .search-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
        }
        
        .filter-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            font-size: 0.95rem;
            font-family: inherit;
            background: var(--gray-50);
            cursor: pointer;
            transition: all 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.25em 1.25em;
            padding-right: 2.5rem;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
        }
        
        .btn-reset {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: var(--gray-100);
            color: var(--gray-600);
            font-weight: 500;
            font-size: 0.875rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .btn-reset:hover {
            background: var(--gray-200);
            border-color: var(--gray-300);
        }
        
        /* RESULTS INFO */
        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 0 0.5rem;
            animation: fadeIn 0.5s ease-out 0.4s backwards;
        }
        
        .results-count {
            font-size: 0.95rem;
            color: var(--gray-600);
        }
        
        .results-count strong {
            color: var(--gray-800);
            font-weight: 700;
        }
        
        .view-toggle {
            display: flex;
            gap: 0.5rem;
        }
        
        .view-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            cursor: pointer;
            color: var(--gray-500);
            transition: all 0.2s;
        }
        
        .view-btn:hover, .view-btn.active {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary-dark);
        }
        
        /* TICKET GRID */
        .ticket-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
        }
        
        @media (max-width: 640px) {
            .ticket-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .ticket-card {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid var(--priority-color, var(--gray-300));
            display: flex;
            flex-direction: column;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease-out forwards;
            position: relative;
            overflow: hidden;
        }
        
        .ticket-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--priority-color, var(--gray-300)), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .ticket-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-xl);
        }
        
        .ticket-card:hover::before {
            opacity: 1;
        }
        
        .ticket-card-body {
            padding: 1.5rem;
            flex-grow: 1;
        }
        
        .ticket-card-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .client-avatar {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            color: white;
            flex-shrink: 0;
            box-shadow: var(--shadow-sm);
        }
        
        .ticket-header-content {
            flex: 1;
            min-width: 0;
        }
        
        .ticket-card-title {
            font-weight: 700;
            color: var(--gray-800);
            font-size: 1.1rem;
            line-height: 1.4;
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .ticket-card-subtitle {
            font-size: 0.8rem;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .ticket-id {
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .priority-indicator {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.875rem;
            cursor: help;
            transition: transform 0.2s;
        }
        
        .priority-indicator:hover {
            transform: scale(1.1);
        }
        
        .priority-indicator.critical { background: var(--danger-light); color: var(--danger); }
        .priority-indicator.high { background: var(--warning-light); color: var(--warning); }
        .priority-indicator.medium { background: var(--secondary-light); color: var(--secondary); }
        .priority-indicator.low { background: var(--gray-100); color: var(--gray-500); }
        
        .ticket-description {
            font-size: 0.9rem;
            color: var(--gray-600);
            border-left: 3px solid var(--gray-200);
            padding-left: 1rem;
            margin: 1rem 0;
            line-height: 1.6;
            background: var(--gray-50);
            padding: 0.75rem 1rem;
            border-radius: 0 var(--radius) var(--radius) 0;
        }
        
        .ticket-card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-100);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.875rem;
            border-radius: var(--radius-2xl);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .status-badge.aperto { background: var(--warning-light); color: #b45309; }
        .status-badge.in-lavorazione { background: var(--secondary-light); color: #1d4ed8; }
        .status-badge.in-attesa { background: var(--purple-light); color: var(--purple); }
        .status-badge.risolto { background: var(--primary-light); color: var(--primary-dark); }
        .status-badge.chiuso { background: var(--gray-100); color: var(--gray-600); }
        
        .ticket-date {
            font-size: 0.8rem;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }
        
        .ticket-date i {
            font-size: 0.75rem;
        }
        
        .ticket-card-footer {
            background: var(--gray-50);
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--gray-100);
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-2xl);
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-action-primary {
            background: var(--primary-light);
            color: var(--primary-dark);
        }
        
        .btn-action-primary:hover {
            background: var(--primary);
            color: white;
        }
        
        .btn-action-secondary {
            background: var(--gray-100);
            color: var(--gray-600);
        }
        
        .btn-action-secondary:hover {
            background: var(--gray-200);
            color: var(--gray-800);
        }
        
        .btn-action-special {
            background: var(--secondary-light);
            color: var(--secondary);
        }
        
        .btn-action-special:hover {
            background: var(--secondary);
            color: white;
        }
        
        .btn-action-warning {
            background: var(--warning-light);
            color: #b45309;
        }
        
        .btn-action-warning:hover {
            background: var(--warning);
            color: white;
        }
        
        /* NO RESULTS */
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            grid-column: 1 / -1;
        }
        
        .no-results i {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: 1.5rem;
        }
        
        .no-results h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        
        .no-results p {
            color: var(--gray-500);
        }
        
        /* MODAL - ENHANCED DESIGN */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 1rem;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            padding: 0;
            border-radius: var(--radius-2xl);
            width: 100%;
            max-width: 480px;
            max-height: 90vh;
            overflow: hidden;
            transform: translateY(30px) scale(0.9);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.25),
                0 0 0 1px rgba(255, 255, 255, 0.1);
        }
        
        .modal-overlay.active .modal-content {
            transform: translateY(0) scale(1);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0;
            border-bottom: none;
            position: relative;
            overflow: hidden;
        }
        
        .modal-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .modal-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .modal-title i {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .modal-close {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-lg);
            cursor: pointer;
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(4px);
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 2rem;
            overflow-y: auto;
            max-height: calc(90vh - 180px);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--gray-600);
            margin-bottom: 0.625rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .form-group label i {
            font-size: 0.85rem;
            color: var(--primary);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            color: var(--gray-700);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--gray-400);
        }
        
        .form-group input:hover,
        .form-group select:hover,
        .form-group textarea:hover {
            border-color: var(--gray-300);
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1), 0 2px 8px rgba(34, 197, 94, 0.15);
        }
        
        .form-group select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.25em 1.25em;
            padding-right: 2.5rem;
            cursor: pointer;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            line-height: 1.6;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem 2rem;
            background: var(--gray-50);
            border-top: 1px solid var(--gray-100);
            margin-top: 0;
        }
        
        .btn-modal {
            padding: 0.875rem 1.75rem;
            border-radius: var(--radius-xl);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-modal-cancel {
            background: white;
            color: var(--gray-600);
            border: 2px solid var(--gray-200);
        }
        
        .btn-modal-cancel:hover {
            background: var(--gray-100);
            border-color: var(--gray-300);
            transform: translateY(-2px);
        }
        
        .btn-modal-save {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.35);
            position: relative;
            overflow: hidden;
        }
        
        .btn-modal-save::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-modal-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
        }
        
        .btn-modal-save:hover::before {
            left: 100%;
        }
        
        .btn-modal-save:active {
            transform: translateY(0);
        }
        
        /* TOAST */
        .toast-container {
            position: fixed;
            top: 100px;
            right: 1.5rem;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .toast {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            transform: translateX(120%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 280px;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast.success { border-left: 4px solid var(--primary); }
        .toast.error { border-left: 4px solid var(--danger); }
        .toast.info { border-left: 4px solid var(--secondary); }
        
        .toast-icon {
            font-size: 1.25rem;
        }
        
        .toast.success .toast-icon { color: var(--primary); }
        .toast.error .toast-icon { color: var(--danger); }
        .toast.info .toast-icon { color: var(--secondary); }
        
        .toast-message {
            flex: 1;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--gray-700);
        }
        
        .toast-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--gray-400);
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        
        .toast-close:hover {
            color: var(--gray-600);
        }
        
        /* ANIMATIONS */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 5px rgba(34, 197, 94, 0.3); }
            50% { box-shadow: 0 0 20px rgba(34, 197, 94, 0.6); }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes countUp {
            from { opacity: 0; transform: scale(0.5); }
            to { opacity: 1; transform: scale(1); }
        }
        
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        @keyframes cardEntrance {
            0% {
                opacity: 0;
                transform: translateY(40px) scale(0.9);
            }
            60% {
                transform: translateY(-5px) scale(1.02);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        @keyframes iconBounce {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.2); }
            50% { transform: scale(0.95); }
            75% { transform: scale(1.1); }
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* ENHANCED BODY */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(34, 197, 94, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(139, 92, 246, 0.03) 0%, transparent 40%);
            pointer-events: none;
            z-index: -1;
        }
        
        /* FLOATING PARTICLES */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            overflow: hidden;
        }
        
        .particle {
            position: absolute;
            width: 6px;
            height: 6px;
            background: var(--primary);
            border-radius: 50%;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }
        
        .particle:nth-child(1) { left: 10%; top: 20%; animation-delay: 0s; }
        .particle:nth-child(2) { left: 20%; top: 80%; animation-delay: 1s; }
        .particle:nth-child(3) { left: 60%; top: 40%; animation-delay: 2s; }
        .particle:nth-child(4) { left: 80%; top: 60%; animation-delay: 3s; }
        .particle:nth-child(5) { left: 40%; top: 10%; animation-delay: 4s; }
        .particle:nth-child(6) { left: 90%; top: 90%; animation-delay: 5s; }
        
        /* RIPPLE EFFECT */
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }
        
        /* ENHANCED BUTTONS */
        .btn-new-ticket,
        .btn-action,
        .btn-modal {
            position: relative;
            overflow: hidden;
        }
        
        .btn-new-ticket::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }
        
        .btn-new-ticket:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }
        
        .btn-new-ticket i {
            transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .btn-new-ticket:hover i {
            transform: rotate(90deg);
        }
        
        /* SKELETON LOADING */
        .skeleton {
            background: linear-gradient(90deg, var(--gray-200) 25%, var(--gray-100) 50%, var(--gray-200) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: var(--radius);
        }
        
        /* ENHANCED SUMMARY CARDS */
        .summary-card {
            transform-style: preserve-3d;
            perspective: 1000px;
        }
        
        .summary-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            opacity: 0;
            transition: opacity 0.3s;
            background: linear-gradient(135deg, 
                rgba(255,255,255,0.4) 0%, 
                rgba(255,255,255,0) 50%,
                rgba(0,0,0,0.05) 100%);
            pointer-events: none;
        }
        
        .summary-card:hover::after {
            opacity: 1;
        }
        
        .summary-card:hover .summary-card-icon {
            animation: iconBounce 0.5s ease;
        }
        
        .summary-card:hover .summary-card-value {
            transform: scale(1.1);
        }
        
        .summary-card-value {
            transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            display: inline-block;
        }
        
        .summary-card-icon {
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .summary-card.green:hover .summary-card-icon {
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
        }
        
        .summary-card.purple:hover .summary-card-icon {
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }
        
        .summary-card.red:hover .summary-card-icon {
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }
        
        .summary-card.blue:hover .summary-card-icon {
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        
        /* ENHANCED TICKET CARDS */
        .ticket-card {
            animation: cardEntrance 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) backwards;
            transform-style: preserve-3d;
        }
        
        .ticket-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            opacity: 0;
            transition: opacity 0.4s;
            background: linear-gradient(135deg, 
                rgba(255,255,255,0.1) 0%, 
                rgba(255,255,255,0) 100%);
            pointer-events: none;
        }
        
        .ticket-card:hover::after {
            opacity: 1;
        }
        
        .ticket-card:hover .client-avatar {
            transform: scale(1.1) rotate(-5deg);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        .ticket-card:hover .priority-indicator {
            animation: pulse 1s infinite;
        }
        
        .ticket-card:hover .ticket-card-title {
            color: var(--primary-dark);
        }
        
        .client-avatar {
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .ticket-card-title {
            transition: color 0.3s ease;
        }
        
        /* ENHANCED STATUS BADGES */
        .status-badge {
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
            overflow: hidden;
        }
        
        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }
        
        .ticket-card:hover .status-badge::before {
            left: 100%;
        }
        
        .status-badge:hover {
            transform: translateY(-2px) scale(1.05);
        }
        
        /* ENHANCED BUTTONS */
        .btn-action {
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .btn-action::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.4s, height 0.4s;
        }
        
        .btn-action:hover::before {
            width: 200%;
            height: 200%;
        }
        
        .btn-action:hover {
            transform: translateY(-3px);
        }
        
        .btn-action:active {
            transform: translateY(0) scale(0.98);
        }
        
        .btn-action i {
            transition: transform 0.3s;
        }
        
        .btn-action:hover i {
            transform: scale(1.2);
        }
        
        /* ENHANCED FILTER BAR */
        .filter-bar {
            position: relative;
            overflow: hidden;
        }
        
        .filter-bar::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, 
                transparent 40%, 
                rgba(34, 197, 94, 0.03) 50%, 
                transparent 60%);
            animation: gradientShift 8s ease infinite;
            pointer-events: none;
        }
        
        .search-wrapper input,
        .filter-select {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .search-wrapper input:focus,
        .filter-select:focus {
            transform: translateY(-2px);
        }
        
        .search-wrapper i {
            transition: all 0.3s;
        }
        
        .search-wrapper input:focus + i,
        .search-wrapper:focus-within i {
            color: var(--primary);
            transform: translateY(-50%) scale(1.1);
        }
        
        /* ENHANCED MODAL */
        .modal-overlay {
            backdrop-filter: blur(8px);
        }
        
        .modal-content {
            animation: none;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .modal-overlay.active .modal-content {
            animation: modalEntrance 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        @keyframes modalEntrance {
            0% {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            60% {
                transform: translateY(10px) scale(1.02);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.2);
        }
        
        /* ENHANCED TOAST */
        .toast {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .toast.show {
            animation: toastEntrance 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        @keyframes toastEntrance {
            0% {
                opacity: 0;
                transform: translateX(100%) scale(0.8);
            }
            60% {
                transform: translateX(-10px) scale(1.02);
            }
            100% {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }
        
        .toast-icon {
            animation: iconBounce 0.5s ease 0.2s;
        }
        
        /* ENHANCED NO RESULTS */
        .no-results i {
            animation: float 3s ease-in-out infinite;
        }
        
        .no-results:hover i {
            animation: iconBounce 0.5s ease;
        }
        
        /* SMOOTH FOCUS STATES */
        *:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
            border-radius: var(--radius);
        }
        
        /* LOADING STATE FOR BUTTONS */
        .btn-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* MICRO INTERACTIONS */
        .ticket-card-footer {
            transition: background 0.3s;
        }
        
        .ticket-card:hover .ticket-card-footer {
            background: var(--gray-100);
        }
        
        .ticket-description {
            transition: all 0.3s;
        }
        
        .ticket-card:hover .ticket-description {
            border-left-color: var(--primary);
            background: rgba(34, 197, 94, 0.05);
        }
        
        /* SCROLLBAR */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--gray-300) 0%, var(--gray-400) 100%);
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, var(--gray-400) 0%, var(--gray-500) 100%);
        }
        
        /* SELECTION */
        ::selection {
            background: var(--primary-light);
            color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <!-- Floating Particles -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <div id="toastContainer" class="toast-container"></div>
    
    <div class="container">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-headset"></i>
                Cruscotto Ticket
            </h1>
            <button onclick="openTicketModal()" class="btn-new-ticket">
                <i class="fas fa-plus"></i>
                Nuovo Ticket
            </button>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="summary-panel">
            <div class="summary-card green" data-filter="Aperti" onclick="filterByCard(this)">
                <div class="summary-card-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="summary-card-content">
                    <p class="summary-card-value" id="summaryActive"><?= $stats_aperti ?></p>
                    <p class="summary-card-label">Ticket Attivi</p>
                </div>
            </div>
            <div class="summary-card purple" data-filter="In attesa di risposta cliente" onclick="filterByCard(this)">
                <div class="summary-card-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="summary-card-content">
                    <p class="summary-card-value" id="summaryWaiting"><?= $stats_in_attesa ?></p>
                    <p class="summary-card-label">In Attesa Cliente</p>
                </div>
            </div>
            <div class="summary-card red" data-filter="Critica" onclick="filterByCard(this)">
                <div class="summary-card-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="summary-card-content">
                    <p class="summary-card-value" id="summaryCritical"><?= $stats_critici ?></p>
                    <p class="summary-card-label">Criticità Aperte</p>
                </div>
            </div>
            <div class="summary-card blue" data-filter="Risolto" onclick="filterByCard(this)">
                <div class="summary-card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="summary-card-content">
                    <p class="summary-card-value" id="summaryResolved">
                        <?php 
                        $resolved = 0;
                        foreach($tickets_data as $t) {
                            if($t['stato'] === 'Risolto') $resolved++;
                        }
                        echo $resolved;
                        ?>
                    </p>
                    <p class="summary-card-label">Risolti</p>
                </div>
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="filter-bar">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Cerca per titolo o cliente</label>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="search-input" placeholder="Es: Schermo rotto, Mario Rossi...">
                    </div>
                </div>
                <div class="filter-group">
                    <label>Cliente</label>
                    <select id="filter_cliente" class="filter-select">
                        <option value="">Tutti i clienti</option>
                        <?php foreach($clienti_data as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cognome'] . ' ' . $c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Priorità</label>
                    <select id="filter_priorita" class="filter-select">
                        <option value="">Tutte</option>
                        <option value="Critica">🔴 Critica</option>
                        <option value="Alta">🟠 Alta</option>
                        <option value="Media">🔵 Media</option>
                        <option value="Bassa">⚪ Bassa</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Stato</label>
                    <select id="filter_stato" class="filter-select">
                        <option value="Aperti">Solo Aperti</option>
                        <option value="Tutti">Tutti</option>
                        <option value="Aperto">⏳ Aperto</option>
                        <option value="In Lavorazione">🔧 In Lavorazione</option>
                        <option value="In attesa di risposta cliente">💬 In attesa cliente</option>
                        <option value="Risolto">✅ Risolto</option>
                        <option value="Chiuso">📁 Chiuso</option>
                    </select>
                </div>
                <button class="btn-reset" onclick="resetFilters()">
                    <i class="fas fa-times"></i>
                    Reset
                </button>
            </div>
        </div>

        <!-- RESULTS INFO -->
        <div class="results-info">
            <div class="results-count">
                <strong id="resultsCount"><?= count($tickets_data) ?></strong> ticket trovati
            </div>
            <div class="view-toggle">
                <button class="view-btn active" title="Vista griglia">
                    <i class="fas fa-th-large"></i>
                </button>
                <button class="view-btn" title="Vista lista">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>

        <!-- TICKET GRID -->
        <div class="ticket-grid" id="ticketGrid">
            <?php if (empty($tickets_data)): ?>
                <div class="no-results">
                    <i class="fas fa-inbox"></i>
                    <h3>Nessun ticket presente</h3>
                    <p>Crea il tuo primo ticket cliccando sul pulsante "Nuovo Ticket"</p>
                </div>
            <?php else: ?>
                <?php foreach($tickets_data as $index => $ticket): 
                    $statusClass = strtolower(str_replace([' ', '_'], '-', $ticket['stato']));
                    if(strpos($statusClass, 'attesa') !== false) $statusClass = 'in-attesa';
                ?>
                <div class="ticket-card" 
                    data-title="<?= htmlspecialchars(strtolower($ticket['titolo'])) ?>"
                    data-client-name="<?= htmlspecialchars(strtolower($ticket['cliente_nome'] . ' ' . $ticket['cliente_cognome'])) ?>"
                    data-client-id="<?= $ticket['cliente_id'] ?>"
                    data-priority="<?= $ticket['priorita'] ?>"
                    data-status="<?= $ticket['stato'] ?>"
                    style="--priority-color: <?= getPriorityColor($ticket['priorita']) ?>; animation-delay: <?= $index * 50 ?>ms;">
                    
                    <div class="ticket-card-body">
                        <div class="ticket-card-header">
                            <div class="client-avatar" style="background: <?= getAvatarColor($ticket['cliente_nome'] . $ticket['cliente_cognome']) ?>;">
                                <?= getAvatarInitials($ticket['cliente_nome'], $ticket['cliente_cognome']) ?>
                            </div>
                            <div class="ticket-header-content">
                                <p class="ticket-card-title"><?= htmlspecialchars($ticket['titolo']) ?></p>
                                <p class="ticket-card-subtitle">
                                    <span class="ticket-id">#<?= $ticket['id'] ?></span>
                                    <span>•</span>
                                    <span><?= htmlspecialchars($ticket['cliente_nome'] . ' ' . $ticket['cliente_cognome']) ?></span>
                                </p>
                            </div>
                            <div class="priority-indicator <?= strtolower($ticket['priorita'] === 'Critica' ? 'critical' : ($ticket['priorita'] === 'Alta' ? 'high' : ($ticket['priorita'] === 'Media' ? 'medium' : 'low'))) ?>" 
                                 title="Priorità: <?= $ticket['priorita'] ?>">
                                <?php 
                                $pIcons = ['Critica' => '❌', 'Alta' => '⚠️', 'Media' => 'ℹ️', 'Bassa' => '○'];
                                echo $pIcons[$ticket['priorita']] ?? '○';
                                ?>
                            </div>
                        </div>
                        
                        <?php if(!empty($ticket['descrizione'])): ?>
                            <p class="ticket-description">
                                <?= htmlspecialchars(mb_strimwidth($ticket['descrizione'], 0, 120, "...")) ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="ticket-card-meta">
                            <span class="status-badge <?= $statusClass ?>">
                                <?php 
                                $statusIcons = ['Aperto' => '⏳', 'In Lavorazione' => '🔧', 'In attesa di risposta cliente' => '💬', 'Risolto' => '✅', 'Chiuso' => '📁'];
                                echo ($statusIcons[$ticket['stato']] ?? '') . ' ' . htmlspecialchars($ticket['stato']);
                                ?>
                            </span>
                            <p class="ticket-date">
                                <i class="far fa-clock"></i>
                                <?= date("d/m/Y H:i", strtotime($ticket['data_ultima_modifica'])) ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="ticket-card-footer">
                        <?php if (empty($ticket['riparazione_id']) && in_array($ticket['stato'], ['Aperto', 'In Lavorazione'])): ?>
                            <a href="javascript:void(0);" onclick="createRepairFromTicket(<?= $ticket['id'] ?>)" class="btn-action btn-action-special">
                                <i class="fas fa-tools"></i> Crea Riparazione
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($ticket['stato'] == 'Aperto'): ?>
                            <button onclick="changeTicketStatus(<?= $ticket['id'] ?>, 'In Lavorazione')" class="btn-action btn-action-primary">
                                <i class="fas fa-play"></i> Prendi in carico
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($ticket['stato'] == 'In Lavorazione'): ?>
                            <button onclick="changeTicketStatus(<?= $ticket['id'] ?>, 'In attesa di risposta cliente')" class="btn-action btn-action-warning">
                                <i class="fas fa-pause"></i> In Attesa
                            </button>
                            <button onclick="changeTicketStatus(<?= $ticket['id'] ?>, 'Risolto')" class="btn-action btn-action-primary">
                                <i class="fas fa-check"></i> Risolto
                            </button>
                        <?php endif; ?>
                        
                        <button onclick='openTicketModal(<?= json_encode($ticket, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="btn-action btn-action-secondary">
                            <i class="fas fa-eye"></i> Dettagli
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div id="no-results-message" class="no-results" style="display: none;">
                <i class="fas fa-search"></i>
                <h3>Nessun ticket trovato</h3>
                <p>Prova a modificare i filtri o il termine di ricerca.</p>
            </div>
        </div>
    </div>

    <!-- MODAL -->
    <div id="ticketModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle" class="modal-title">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Nuovo Ticket</span>
                </h3>
                <button type="button" onclick="closeModal()" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="ticketForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="ticketId">
                    
                    <div class="form-group">
                        <label for="clienteId"><i class="fas fa-user"></i> Cliente</label>
                        <select name="cliente_id" id="clienteId" required>
                            <option value="">Seleziona un cliente...</option>
                            <?php foreach($clienti_data as $cliente): ?>
                                <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['cognome'] . ' ' . $cliente['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ticketTitolo"><i class="fas fa-heading"></i> Titolo</label>
                        <input type="text" name="titolo" id="ticketTitolo" placeholder="Es: Schermo rotto iPhone 12" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="ticketDescrizione"><i class="fas fa-align-left"></i> Descrizione</label>
                        <textarea name="descrizione" id="ticketDescrizione" rows="4" placeholder="Descrivi il problema in dettaglio..."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ticketStato"><i class="fas fa-circle-notch"></i> Stato</label>
                            <select name="stato" id="ticketStato">
                                <option value="Aperto">⏳ Aperto</option>
                                <option value="In Lavorazione">🔧 In Lavorazione</option>
                                <option value="In attesa di risposta cliente">💬 In attesa cliente</option>
                                <option value="Risolto">✅ Risolto</option>
                                <option value="Chiuso">📁 Chiuso</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ticketPriorita"><i class="fas fa-flag"></i> Priorità</label>
                            <select name="priorita" id="ticketPriorita">
                                <option value="Bassa">🟢 Bassa</option>
                                <option value="Media">🔵 Media</option>
                                <option value="Alta">🟠 Alta</option>
                                <option value="Critica">🔴 Critica</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" class="btn-modal btn-modal-cancel">
                        <i class="fas fa-times"></i> Annulla
                    </button>
                    <button type="submit" class="btn-modal btn-modal-save">
                        <i class="fas fa-check"></i> Salva Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>

<script>
    // TOAST NOTIFICATIONS
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle' };
        toast.innerHTML = `
            <i class="fas ${icons[type]} toast-icon"></i>
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // FILTER BY CARD CLICK
    function filterByCard(card) {
        const filterValue = card.dataset.filter;
        
        // Toggle active state
        document.querySelectorAll('.summary-card').forEach(c => c.classList.remove('active'));
        
        // Check if it's a priority filter or status filter
        if (filterValue === 'Critica') {
            if (document.getElementById('filter_priorita').value === filterValue) {
                document.getElementById('filter_priorita').value = '';
            } else {
                card.classList.add('active');
                document.getElementById('filter_priorita').value = filterValue;
            }
        } else {
            if (document.getElementById('filter_stato').value === filterValue) {
                document.getElementById('filter_stato').value = 'Aperti';
            } else {
                card.classList.add('active');
                document.getElementById('filter_stato').value = filterValue;
            }
        }
        
        filterTickets();
    }

    // RESET FILTERS
    function resetFilters() {
        document.getElementById('search-input').value = '';
        document.getElementById('filter_cliente').value = '';
        document.getElementById('filter_priorita').value = '';
        document.getElementById('filter_stato').value = 'Aperti';
        document.querySelectorAll('.summary-card').forEach(c => c.classList.remove('active'));
        filterTickets();
        showToast('Filtri resettati', 'info');
    }

    document.addEventListener('DOMContentLoaded', function() {
        // --- Logica Filtri in Tempo Reale ---
        const searchInput = document.getElementById('search-input');
        const clienteFilter = document.getElementById('filter_cliente');
        const prioritaFilter = document.getElementById('filter_priorita');
        const statoFilter = document.getElementById('filter_stato');
        const ticketCards = document.querySelectorAll('.ticket-card');
        const noResultsMessage = document.getElementById('no-results-message');
        const resultsCount = document.getElementById('resultsCount');

        window.filterTickets = function() {
            const searchTerm = searchInput.value.toLowerCase();
            const clienteId = clienteFilter.value;
            const priorita = prioritaFilter.value;
            const stato = statoFilter.value;
            let visibleCards = 0;

            ticketCards.forEach((card, index) => {
                const title = card.dataset.title;
                const clientName = card.dataset.clientName;
                const cardClienteId = card.dataset.clientId;
                const cardPriority = card.dataset.priority;
                const cardStatus = card.dataset.status;

                let show = true;
                if (searchTerm && !(title.includes(searchTerm) || clientName.includes(searchTerm))) show = false;
                if (clienteId && cardClienteId !== clienteId) show = false;
                if (priorita && cardPriority !== priorita) show = false;
                if (stato) {
                    if (stato === 'Aperti' && (cardStatus === 'Risolto' || cardStatus === 'Chiuso')) show = false;
                    else if (stato !== 'Aperti' && stato !== 'Tutti' && cardStatus !== stato) show = false;
                }
                
                if (show) {
                    card.style.display = 'flex';
                    card.style.animationDelay = (visibleCards * 50) + 'ms';
                    visibleCards++;
                } else {
                    card.style.display = 'none';
                }
            });

            resultsCount.textContent = visibleCards;
            noResultsMessage.style.display = visibleCards === 0 ? 'block' : 'none';
        }

        [searchInput, clienteFilter, prioritaFilter, statoFilter].forEach(el => {
            el.addEventListener('input', filterTickets);
            el.addEventListener('change', filterTickets);
        });
        
        filterTickets();
    });

    // --- Funzioni del Modale e Azioni ---
    const ticketModal = document.getElementById('ticketModal');
    const form = document.getElementById('ticketForm');

    function openTicketModal(ticket = null) {
        form.reset();
        if (ticket) {
            document.getElementById('modalTitle').innerHTML = `<i class="fas fa-edit"></i><span>Modifica Ticket #${ticket.id}</span>`;
            document.getElementById('ticketId').value = ticket.id;
            document.getElementById('clienteId').value = ticket.cliente_id;
            document.getElementById('ticketTitolo').value = ticket.titolo;
            document.getElementById('ticketDescrizione').value = ticket.descrizione;
            document.getElementById('ticketStato').value = ticket.stato;
            document.getElementById('ticketPriorita').value = ticket.priorita;
        } else {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i><span>Nuovo Ticket</span>';
            document.getElementById('ticketId').value = '';
        }
        ticketModal.classList.add('active');
    }

    function closeModal() {
        ticketModal.classList.remove('active');
    }

    ticketModal.addEventListener('click', function(event) {
        if (event.target === ticketModal) {
            closeModal();
        }
    });

    async function changeTicketStatus(ticketId, newStatus) {
        const result = await Swal.fire({
            title: 'Conferma azione',
            text: `Vuoi cambiare lo stato in "${newStatus}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sì, conferma',
            cancelButtonText: 'Annulla'
        });

        if (!result.isConfirmed) return;

        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('id', ticketId);
        formData.append('stato', newStatus);

        try {
            const response = await fetch('gestione_ticket_actions.php', { method: 'POST', body: formData });
            const res = await response.json();
            if (res.success) {
                showToast(`Stato aggiornato a "${newStatus}"`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(res.message || 'Errore durante l\'aggiornamento', 'error');
            }
        } catch (error) {
            showToast('Errore di connessione con il server', 'error');
        }
    }

    async function createRepairFromTicket(ticketId) {
        const result = await Swal.fire({
            title: 'Creare una Riparazione?',
            text: `Stai per trasformare il ticket #${ticketId} in una riparazione ufficiale.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sì, crea riparazione',
            cancelButtonText: 'Annulla'
        });

        if (result.isConfirmed) {
            window.location.href = `crea_riparazione_da_ticket.php?ticket_id=${ticketId}`;
        }
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'save');

        try {
            const response = await fetch('gestione_ticket_actions.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                showToast('Ticket salvato con successo!', 'success');
                closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message || 'Si è verificato un errore.', 'error');
            }
        } catch (error) {
            showToast('Errore di connessione con il server', 'error');
        }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
        if (e.key === 'n' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            openTicketModal();
        }
    });

    // ==========================================
    // ENHANCED ANIMATIONS & INTERACTIONS
    // ==========================================
    
    // Animated Counter
    function animateCounter(element, target, duration = 1000) {
        const start = 0;
        const startTime = performance.now();
        
        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function (ease-out-cubic)
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(start + (target - start) * easeOut);
            
            element.textContent = current;
            
            if (progress < 1) {
                requestAnimationFrame(update);
            } else {
                element.textContent = target;
            }
        }
        
        requestAnimationFrame(update);
    }
    
    // Initialize counters on load
    document.addEventListener('DOMContentLoaded', function() {
        const counters = document.querySelectorAll('.summary-card-value');
        
        // Use Intersection Observer for counters
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = parseInt(entry.target.textContent) || 0;
                    entry.target.textContent = '0';
                    setTimeout(() => {
                        animateCounter(entry.target, target, 1200);
                    }, 200);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        counters.forEach(counter => counterObserver.observe(counter));
    });
    
    // Ripple Effect
    function createRipple(event) {
        const button = event.currentTarget;
        const ripple = document.createElement('span');
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        
        button.appendChild(ripple);
        
        ripple.addEventListener('animationend', () => {
            ripple.remove();
        });
    }
    
    // Add ripple to all buttons
    document.querySelectorAll('.btn-new-ticket, .btn-action, .btn-modal, .summary-card').forEach(btn => {
        btn.addEventListener('click', createRipple);
    });
    
    // Card Tilt Effect
    document.querySelectorAll('.ticket-card').forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;
            
            this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-6px)`;
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0)';
        });
    });
    
    // Summary Card Hover Effect
    document.querySelectorAll('.summary-card').forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = (y - centerY) / 30;
            const rotateY = (centerX - x) / 30;
            
            this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-4px)`;
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0)';
        });
    });
    
    // Smooth page transitions
    document.querySelectorAll('a[href]').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                e.preventDefault();
                document.body.style.opacity = '0';
                document.body.style.transform = 'translateY(-20px)';
                document.body.style.transition = 'all 0.3s ease';
                setTimeout(() => {
                    window.location.href = href;
                }, 300);
            }
        });
    });
    
    // Parallax particles on mouse move
    document.addEventListener('mousemove', function(e) {
        const particles = document.querySelectorAll('.particle');
        const x = e.clientX / window.innerWidth;
        const y = e.clientY / window.innerHeight;
        
        particles.forEach((particle, index) => {
            const speed = (index + 1) * 0.5;
            const offsetX = (x - 0.5) * speed * 30;
            const offsetY = (y - 0.5) * speed * 30;
            particle.style.transform = `translate(${offsetX}px, ${offsetY}px)`;
        });
    });
    
    // Staggered card animation on filter
    window.originalFilterTickets = window.filterTickets;
    window.filterTickets = function() {
        const ticketCards = document.querySelectorAll('.ticket-card');
        ticketCards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
        });
        
        setTimeout(() => {
            if (typeof originalFilterTickets === 'function') {
                originalFilterTickets();
            }
            
            let delay = 0;
            ticketCards.forEach(card => {
                if (card.style.display !== 'none') {
                    setTimeout(() => {
                        card.style.transition = 'all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, delay);
                    delay += 50;
                }
            });
        }, 50);
    };
    
    // Input focus effects
    document.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
            this.parentElement.style.transition = 'transform 0.3s ease';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
    
    // Page load animation
    window.addEventListener('load', function() {
        document.body.style.opacity = '0';
        document.body.style.transform = 'translateY(20px)';
        
        requestAnimationFrame(() => {
            document.body.style.transition = 'all 0.5s ease';
            document.body.style.opacity = '1';
            document.body.style.transform = 'translateY(0)';
        });
    });
    
    console.log('🎨 Enhanced UI loaded with smooth animations!');
</script>
</body>
</html>

