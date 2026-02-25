<?php
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
    $map = ['Aperto' => 'bg-amber-100 text-amber-800', 'In Lavorazione' => 'bg-blue-100 text-blue-800', 'In attesa di risposta cliente' => 'bg-purple-100 text-purple-800', 'Risolto' => 'bg-green-100 text-green-800', 'Chiuso' => 'bg-gray-100 text-gray-800'];
    return $map[$status] ?? 'bg-gray-100 text-gray-800';
}
function getPriorityIcon($priority) {
    // La logica per le icone rimane invariata
    $icons = [
        'Critica' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>',
        'Alta'    => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 13a1 1 0 110-2 1 1 0 010 2zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>',
        'Media'   => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>',
        'Bassa'   => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.707-10.293a1 1 0 00-1.414-1.414l-3 3a1 1 0 001.414 1.414L10 9.414l2.293 2.293a1 1 0 001.414-1.414l-3-3z" clip-rule="evenodd" /></svg>',
    ];
    return $icons[$priority] ?? $icons['Bassa'];
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
    <title>Cruscotto Ticket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- MODIFICA: Rimosso il link a Inter, useremo Poppins dall'header -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Stile Generale */
        body { 
            /* MODIFICA: Usiamo il font Poppins definito nell'header per coerenza */
            font-family: 'Poppins', sans-serif; 
            background-color: #f0f2f5; 
            /* Il padding-top viene gestito dallo stile dell'header */
            padding-top: 90px;
            background-image: radial-gradient(#d1d5db 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .container { max-width: 1600px; margin: 2rem auto; padding: 0 1.5rem; }
        
        .summary-panel { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .summary-card {
            background-color: white; padding: 1.5rem; border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
            display: flex; align-items: center; gap: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .summary-card:hover { transform: translateY(-3px); box-shadow: 0 7px 10px -3px rgba(0,0,0,0.1); }
        .summary-card-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .summary-card-value { font-size: 2rem; font-weight: 800; color: #1f2937; line-height: 1; }
        .summary-card-label { font-size: 0.875rem; color: #6b7280; font-weight: 500; }

        .filter-bar { background: white; padding: 1rem 1.5rem; border-radius: 0.75rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        
        .ticket-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 1.5rem;
        }
        .ticket-card {
            background-color: white; border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-top: 4px solid var(--priority-color, #d1d5db);
            display: flex; flex-direction: column; justify-content: space-between;
            opacity: 0; transform: translateY(20px);
            animation: fadeIn 0.5s ease-out forwards;
        }
        .ticket-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1); }
        .ticket-card-body { padding: 1.25rem; flex-grow: 1; }
        .ticket-card-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .client-avatar {
            width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 0.875rem; color: white; flex-shrink: 0;
        }
        .priority-icon { flex-shrink: 0; margin-left: auto; }
        .ticket-card-title { font-weight: 600; color: #1f2937; font-size: 1.125rem; line-height: 1.4; }
        .ticket-card-subtitle { font-size: 0.875rem; color: #6b7280; }
        .ticket-description-excerpt {
            font-size: 0.875rem; color: #4b5563; border-left: 3px solid #e5e7eb;
            padding-left: 0.75rem; margin: 1rem 0; line-height: 1.6;
        }
        .ticket-card-meta { display: flex; justify-content: space-between; align-items: center; margin-top: 1.25rem; }
        .ticket-card-date { font-size: 0.8rem; color: #6b7280; display: flex; align-items: center; gap: 0.25rem; }
        .ticket-card-footer {
            background-color: #f9fafb; padding: 0.75rem 1.25rem; border-top: 1px solid #e5e7eb;
            border-bottom-left-radius: 0.75rem; border-bottom-right-radius: 0.75rem;
            display: flex; justify-content: flex-end; gap: 0.5rem;
        }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        
        .ticket-card-footer button, .ticket-card-footer a {
            font-size: 0.8rem; font-weight: 500; padding: 0.3rem 0.8rem; border-radius: 999px;
            transition: all 0.2s; border: 1px solid transparent; text-decoration: none; display: inline-block;
        }
        .btn-action-main { background-color: #dcfce7; color: #166534; } /* MODIFICA: Colore verde per coerenza */
        .btn-action-main:hover { background-color: #bbf7d0; }
        .btn-action-secondary { background-color: #f1f5f9; color: #475569; }
        .btn-action-secondary:hover { background-color: #e2e8f0; }
        .btn-action-special { background-color: #e0f2f1; color: #00796b; } /* MODIFICA: Colore verde acqua per "Crea Riparazione" */
        .btn-action-special:hover { background-color: #b2dfdb; }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center;
            z-index: 1000; opacity: 0; visibility: hidden; transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .modal-content {
            background: white; padding: 2rem; border-radius: 0.5rem; width: 500px; max-width: 90%;
            transform: translateY(-20px); transition: transform 0.3s ease;
        }
        .modal-overlay.active .modal-content { transform: translateY(0); }
        @keyframes fadeIn { to { opacity: 1; transform: translateY(0); } }
        #no-results-message { text-align: center; padding: 3rem; background-color: white; border-radius: 0.75rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        /* MODIFICA: Stile per il link attivo nel menu (da applicare con JS) */
        nav a.active-link {
             background-color: #1e8449; /* Verde scuro, coerente con il brand */
             color: white;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <!-- Intestazione e Filtri -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Cruscotto Ticket</h1>
            <button onclick="openTicketModal()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md shadow-md hover:shadow-lg transition-shadow">
                + Nuovo Ticket
            </button>
        </div>

        <!-- Pannello di Riepilogo -->
        <div class="summary-panel">
            <div class="summary-card">
                <!-- MODIFICA: Colori verdi per coerenza con il brand -->
                <div class="summary-card-icon bg-green-100 text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 4h5m-5 4h5m-5-4h.01M9 7h.01M13 7h.01M13 11h.01" /></svg>
                </div>
                <div>
                    <p class="summary-card-value"><?= $stats_aperti ?></p>
                    <p class="summary-card-label">Ticket Attivi</p>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-card-icon bg-purple-100 text-purple-600">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                </div>
                <div>
                    <p class="summary-card-value"><?= $stats_in_attesa ?></p>
                    <p class="summary-card-label">In Attesa Cliente</p>
                </div>
            </div>
            <div class="summary-card">
                 <div class="summary-card-icon bg-red-100 text-red-600">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <div>
                    <p class="summary-card-value"><?= $stats_critici ?></p>
                    <p class="summary-card-label">Criticità Aperte</p>
                </div>
            </div>
        </div>

        <div class="filter-bar">
            <!-- La barra dei filtri (invariata) -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="search-input" class="text-sm font-medium text-gray-700">Cerca per titolo o cliente</label>
                    <input type="text" id="search-input" placeholder="Es: Schermo rotto, Mario Rossi..." class="w-full p-2 border rounded-md">
                </div>
                <div>
                    <label for="filter_cliente" class="text-sm font-medium text-gray-700">Cliente</label>
                    <select id="filter_cliente" class="w-full p-2 border rounded-md">
                        <option value="">Tutti i clienti</option>
                        <?php foreach($clienti_data as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cognome'] . ' ' . $c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_priorita" class="text-sm font-medium text-gray-700">Priorità</label>
                    <select id="filter_priorita" class="w-full p-2 border rounded-md">
                        <option value="">Tutte le priorità</option>
                        <option>Critica</option> <option>Alta</option> <option>Media</option> <option>Bassa</option>
                    </select>
                </div>
                <div>
                    <label for="filter_stato" class="text-sm font-medium text-gray-700">Stato</label>
                    <select id="filter_stato" class="w-full p-2 border rounded-md">
                        <option value="Aperti">Solo Aperti</option> <option value="Tutti">Tutti</option>
                        <option>Aperto</option> <option>In Lavorazione</option>
                        <option>In attesa di risposta cliente</option> <option>Risolto</option> <option>Chiuso</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Contenitore della Griglia di Ticket -->
        <div class="ticket-grid">
            <?php if (empty($tickets_data)): ?>
                <div class="text-center text-gray-500 mt-8 col-span-full">Nessun ticket presente nel sistema.</div>
            <?php else: ?>
                <?php foreach($tickets_data as $index => $ticket): ?>
                <div class="ticket-card" 
                    data-title="<?= htmlspecialchars(strtolower($ticket['titolo'])) ?>"
                    data-client-name="<?= htmlspecialchars(strtolower($ticket['cliente_nome'] . ' ' . $ticket['cliente_cognome'])) ?>"
                    data-client-id="<?= $ticket['cliente_id'] ?>"
                    data-priority="<?= $ticket['priorita'] ?>"
                    data-status="<?= $ticket['stato'] ?>"
                    style="--priority-color: <?= getPriorityColor($ticket['priorita']) ?>; animation-delay: <?= $index * 30 ?>ms;">
                    
                    <div class="ticket-card-body">
                        <!-- Contenuto della scheda (invariato) -->
                        <div class="ticket-card-header">
                            <div class="client-avatar" style="background-color: <?= getAvatarColor($ticket['cliente_nome'] . $ticket['cliente_cognome']) ?>;">
                                <?= getAvatarInitials($ticket['cliente_nome'], $ticket['cliente_cognome']) ?>
                            </div>
                            <div class="flex-grow">
                                <p class="ticket-card-title"><?= htmlspecialchars($ticket['titolo']) ?></p>
                                <p class="ticket-card-subtitle">
                                    Ticket #<?= $ticket['id'] ?> &bull; <?= htmlspecialchars($ticket['cliente_nome'] . ' ' . $ticket['cliente_cognome']) ?>
                                </p>
                            </div>
                            <div class="priority-icon" title="Priorità: <?= $ticket['priorita'] ?>"><?= getPriorityIcon($ticket['priorita']) ?></div>
                        </div>
                        <?php if(!empty($ticket['descrizione'])): ?>
                            <p class="ticket-description-excerpt">
                                <?= htmlspecialchars(mb_strimwidth($ticket['descrizione'], 0, 100, "...")) ?>
                            </p>
                        <?php endif; ?>
                        <div class="ticket-card-meta">
                            <span class="status-badge <?= getStatusBadgeClass($ticket['stato']) ?>">
                                <?= htmlspecialchars($ticket['stato']) ?>
                            </span>
                            <p class="ticket-card-date">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>
                                <span><?= date("d/m/Y H:i", strtotime($ticket['data_ultima_modifica'])) ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="ticket-card-footer">
                        <?php if (empty($ticket['riparazione_id']) && in_array($ticket['stato'], ['Aperto', 'In Lavorazione'])): ?>
                            <a href="javascript:void(0);" onclick="createRepairFromTicket(<?= $ticket['id'] ?>)" class="btn-action-special">Crea Riparazione</a>
                        <?php endif; ?>
                        <?php if ($ticket['stato'] == 'Aperto'): ?>
                            <button onclick="changeTicketStatus(<?= $ticket['id'] ?>, 'In Lavorazione')" class="btn-action-main">Prendi in carico</button>
                        <?php endif; ?>
                        <?php if ($ticket['stato'] == 'In Lavorazione'): ?>
                            <button onclick="changeTicketStatus(<?= $ticket['id'] ?>, 'In attesa di risposta cliente')" class="btn-action-secondary">In Attesa</button>
                            <button onclick="changeTicketStatus(<?= $ticket['id'] ?>, 'Risolto')" class="btn-action-main">Risolto</button>
                        <?php endif; ?>
                        <button onclick='openTicketModal(<?= json_encode($ticket, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="btn-action-secondary">Dettagli</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div id="no-results-message" class="col-span-full" style="display: none;">
                 <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Nessun ticket trovato</h3>
                <p class="mt-1 text-sm text-gray-500">Prova a modificare i filtri o il termine di ricerca.</p>
            </div>
        </div>
    </div>

    <!-- Modale -->
    <div id="ticketModal" class="modal-overlay">
      <div class="modal-content">
        <h3 id="modalTitle" class="text-xl font-bold mb-4">Nuovo Ticket</h3>
        <form id="ticketForm">
          <input type="hidden" name="id" id="ticketId">
          <div class="mb-4">
            <label for="clienteId" class="block text-sm font-medium text-gray-700">Cliente</label>
            <select name="cliente_id" id="clienteId" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                <option value="">-- Seleziona un cliente --</option>
                <?php foreach($clienti_data as $cliente): ?>
                    <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['cognome'] . ' ' . $cliente['nome']) ?></option>
                <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-4">
            <label for="ticketTitolo" class="block text-sm font-medium text-gray-700">Titolo</label>
            <input type="text" name="titolo" id="ticketTitolo" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
          </div>
          <div class="mb-4">
            <label for="ticketDescrizione" class="block text-sm font-medium text-gray-700">Descrizione</label>
            <textarea name="descrizione" id="ticketDescrizione" rows="4" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4 mb-4">
              <div>
                <label for="ticketStato" class="block text-sm font-medium text-gray-700">Stato</label>
                <select name="stato" id="ticketStato" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                  <option>Aperto</option> <option>In Lavorazione</option> <option>In attesa di risposta cliente</option> <option>Risolto</option> <option>Chiuso</option>
                </select>
              </div>
              <div>
                <label for="ticketPriorita" class="block text-sm font-medium text-gray-700">Priorità</label>
                <select name="priorita" id="ticketPriorita" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                  <option>Bassa</option> <option>Media</option> <option>Alta</option> <option>Critica</option>
                </select>
              </div>
          </div>
          <div class="flex justify-end gap-4">
            <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 rounded">Annulla</button>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Salva Ticket</button>
          </div>
        </form>
      </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- SCRIPT PER EVIDENZIARE IL LINK ATTIVO ---
        const currentPage = window.location.pathname.split('/').pop();
        if (currentPage === 'gestisci_ticket.php') {
            const activeLink = document.querySelector('nav a[href="gestisci_ticket.php"]');
            if (activeLink) {
                activeLink.classList.add('active-link');
            }
        }

        // --- Logica Filtri in Tempo Reale ---
        const searchInput = document.getElementById('search-input');
        const clienteFilter = document.getElementById('filter_cliente');
        const prioritaFilter = document.getElementById('filter_priorita');
        const statoFilter = document.getElementById('filter_stato');
        const ticketCards = document.querySelectorAll('.ticket-card');
        const noResultsMessage = document.getElementById('no-results-message');

        function filterTickets() {
            const searchTerm = searchInput.value.toLowerCase();
            const clienteId = clienteFilter.value;
            const priorita = prioritaFilter.value;
            const stato = statoFilter.value;
            let visibleCards = 0;

            ticketCards.forEach(card => {
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
                
                card.style.display = show ? 'flex' : 'none';
                if(show) visibleCards++;
            });

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
            document.getElementById('modalTitle').innerText = `Modifica Ticket #${ticket.id}`;
            document.getElementById('ticketId').value = ticket.id;
            document.getElementById('clienteId').value = ticket.cliente_id;
            document.getElementById('ticketTitolo').value = ticket.titolo;
            document.getElementById('ticketDescrizione').value = ticket.descrizione;
            document.getElementById('ticketStato').value = ticket.stato;
            document.getElementById('ticketPriorita').value = ticket.priorita;
        } else {
            document.getElementById('modalTitle').innerText = 'Nuovo Ticket';
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
            title: `Sei sicuro?`,
            text: `Vuoi cambiare lo stato in "${newStatus}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sì, cambia!',
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
                Swal.fire('Fatto!', 'Lo stato del ticket è stato aggiornato.', 'success').then(() => location.reload());
            } else {
                Swal.fire('Errore!', res.message, 'error');
            }
        } catch (error) {
            Swal.fire('Errore!', 'Errore di connessione con il server.', 'error');
        }
    }

    async function createRepairFromTicket(ticketId) {
        const result = await Swal.fire({
            title: 'Creare una Riparazione?',
            text: `Stai per trasformare il ticket #${ticketId} in una riparazione ufficiale. Il ticket verrà chiuso. Continuare?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sì, crea riparazione!',
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

        const response = await fetch('gestione_ticket_actions.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            Swal.fire('Successo!', 'Ticket salvato correttamente.', 'success').then(() => location.reload());
        } else {
            Swal.fire('Errore!', result.message || 'Si è verificato un errore.', 'error');
        }
    });
</script>
</body>
</html>

