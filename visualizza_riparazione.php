<?php
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Avvia la sessione PHP - Assicurati che sia sempre all'inizio del file
session_start();

// Includi il file di connessione al database MySQL
if (!file_exists('db.php')) {
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: Il file db.php non è stato trovato!</div>";
    // TERMINA LO SCRIPT se db.php non esiste
    exit;
}
require_once 'db.php';

// Controlla subito se c'è stato un errore di connessione al database da db.php
// Se $conn non è stata inizializzata o è null, significa che la connessione è fallita.
if (!isset($conn) || $conn === null) {
    $db_error_message = isset($db_connection_error) ? $db_connection_error : 'Connessione al database non stabilita.';
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: " . htmlspecialchars($db_error_message, ENT_QUOTES, 'UTF-8') . "</div>";
    // TERMINA LO SCRIPTO se la connessione al DB fallisce
    exit;
}

// Ottiene l'ID della riparazione dalla query string, con validazione
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Reindirizza o mostra un errore se l'ID non è valido
if ($id <= 0) {
    // Reindirizza a una pagina di errore o alla lista delle riparazioni
    $_SESSION['message'] = 'ID riparazione non valido.';
    $_SESSION['isError'] = true;
    header('Location: storico_riparazioni.php'); // O la tua pagina di errore
    exit;
}

// Prepara e esegue la query per ottenere i dettagli della riparazione e del cliente
$sql = "SELECT r.*, c.nome AS cliente_nome, c.cognome AS cliente_cognome
        FROM riparazioni r
        LEFT JOIN clienti_nuovo c ON r.cliente_id = c.id
        WHERE r.id = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    // Gestione errori per la preparazione della query
    $_SESSION['message'] = 'Errore nella preparazione della query per i dettagli della riparazione.';
    $_SESSION['isError'] = true;
    header('Location: storico_riparazioni.php');
    exit;
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Verifica se la riparazione è stata trovata
if ($result->num_rows === 0) {
    $_SESSION['message'] = "Riparazione ID #$id non trovata.";
    $_SESSION['isError'] = true;
    header('Location: storico_riparazioni.php');
    exit;
}

// Recupera i dati della riparazione
$riparazione = $result->fetch_assoc();
$stmt->close(); // Chiudi lo statement dopo l'uso

// Compone il nome completo del cliente
$clienteCompleto = trim(($riparazione['cliente_nome'] ?? '') . ' ' . ($riparazione['cliente_cognome'] ?? ''));

// Rimuove campi non necessari dall'array $riparazione per l'output nel ciclo
// Questi campi sono già gestiti specificamente (id, cliente_id, cliente_nome, cliente_cognome)
unset(
    $riparazione['id'],
    $riparazione['cliente_id'],
    $riparazione['cliente_nome'],
    $riparazione['cliente_cognome']
);

// Mappa i nomi dei campi del database a etichette più leggibili per l'interfaccia
// Aggiungi qui altri campi se ne hai nel tuo database e vuoi visualizzarli.
// Assicurati che i nomi delle chiavi (es. 'data_riparazione') corrispondano
// esattamente ai nomi delle colonne nella tua tabella 'riparazioni'.
$fieldLabels = [
    'telefono' => 'Telefono',
    'diagnosi' => 'Diagnosi/Difetto',
    'modello' => 'Modello',
    'data_creazione' => 'Data Creazione',
    'stato' => 'Stato',
    'costo_effettivo' => 'Costo Effettivo (€)',
    // 'data_riparazione' => 'Data Riparazione', // Esempio: se esistesse in riparazioni
    // 'descrizione_problema' => 'Descrizione Problema',
    // 'intervento_effettuato' => 'Intervento Effettuato',
    // 'stato_riparazione' => 'Stato Riparazione',
    // 'costo' => 'Costo (€)',
    // 'data_consegna_prevista' => 'Consegna Prevista',
    // 'note' => 'Note Aggiuntive',
    // 'modello_dispositivo' => 'Modello Dispositivo',
    // 'numero_seriale' => 'Numero Seriale',
    // 'accessori_inclusi' => 'Accessori Inclusi',
    // 'tecnico_responsabile' => 'Tecnico Responsabile',
    // 'data_ritiro' => 'Data Ritiro',
    // 'garanzia' => 'Garanzia',
    // 'metodo_pagamento' => 'Metodo di Pagamento',
];

// Funzione helper per formattare la valuta
function formatCurrency($value) {
    return number_format($value, 2, ',', '.') . ' €';
}

// Funzione per ottenere le classi CSS per lo stato (riportata da visualizza_riparazioni.php per coerenza)
function getStatusClasses($status) {
    switch ($status) {
        case 'In Attesa':
            return 'status-pending';
        case 'In Lavorazione':
            return 'status-in-progress';
        case 'Completata':
            return 'status-completed';
        case 'Consegnata':
            return 'status-delivered';
        case 'Annullata':
        default:
            return 'status-cancelled'; // Per default o stato non riconosciuto
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Dettagli Riparazione #<?php echo htmlspecialchars($id); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=1">
    <script src="https://cdn.tailwindcss.com"></script>
        <style>
        /* Variabili CSS per il tema verde e stili generali (da visualizza_riparazioni.php) */
        :root {
            --brand-green: #28a745;        /* Base Green */
            --brand-green-dark: #1e8449;   /* Darker shade for gradients */
            --brand-green-light: #e0f2e8;  /* Very light green for backgrounds/hovers */
            --brand-green-accent: #34d399; /* A brighter, more lively green for accents */
            --brand-green-text: #065f46;   /* Darker green for text on light backgrounds */
            --brand-green-hover-bg: #d1fae5; /* Very light green for hover backgrounds */

            --bg-color-page: #f3f4f6; /* Consistent background for the entire page */
            --text-color-primary: #1f2937; /* Darker primary text for readability */
            --text-color-secondary: #6b7280; /* Muted text for secondary info */
            --border-color-light: #e5e7eb; /* Light border for subtle separation */
            --card-bg: #fff;              /* White background for cards */
            --card-radius: 0.75rem;       /* Consistent radius for elements */
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); /* Consistent shadow */

            /* Specific status colors */
            --status-pending: #f59e0b;     /* Orange for In Attesa */
            --status-in-progress: #34d399; /* Accent Green for In Lavorazione */
            --status-completed: #10b981;   /* Brighter Green for Completata */
            --status-delivered: #059669;   /* Deeper Green for Consegnata */
            --status-cancelled: #ef4444;   /* Red for Annullata */
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg-color-page);
            color: var(--text-color-primary);
            padding-top: 90px; /* Spazio per la top-bar */
            line-height: 1.6;
        }

        /* Contenitore principale della pagina (come in visualizza_riparazioni.php) */
        .main-content-container {
            max-width: 1400px; /* Increased max-width */
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            animation: fadeIn 0.6s ease-out forwards;
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 700;
            color: var(--text-color-primary);
            font-size: 2rem;
        }

        /* Stili per la card dettagli (uniformati al gestionale) */
        .detail-card {
            background-color: var(--card-bg);
            padding: 1.5rem;
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color-light);
            margin-bottom: 1.5rem; /* Spazio sotto la card */
        }

        .detail-card-header {
            border-bottom: 1px solid var(--border-color-light);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .detail-card-header h2 {
            font-size: 1.5rem;
            color: var(--text-color-primary);
            margin: 0;
            font-weight: 600;
        }

        .detail-card-header span {
            font-size: 1rem;
            color: var(--text-color-secondary);
            font-weight: 500;
        }

        /* Corpo della Card (Dettagli) */
        .card-body-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Layout a griglia responsive */
            gap: 1rem; /* Spazio tra gli elementi */
        }

        .detail-item {
            background: #f8f9fa; /* Sfondo leggermente colorato per ogni item */
            border: 1px solid #e9ecef;
            border-radius: 0.5rem; /* Bordi leggermente meno arrotondati */
            padding: 0.8rem 1rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05); /* Ombra più soft */
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }

        .detail-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
        }

        .detail-item label {
            display: block;
            font-size: 0.8rem;
            color: var(--text-color-secondary);
            margin-bottom: 0.2rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .detail-item p {
            font-size: 0.95rem;
            color: var(--text-color-primary);
            margin: 0;
            font-weight: 400;
            word-break: break-word;
            white-space: pre-wrap; /* Mantiene la formattazione dei salti di riga */
        }

        /* Azioni (Pulsanti) - Uniformati a quelli di visualizza_riparazioni.php */
        .card-actions {
            margin-top: 2rem;
            border-top: 1px solid var(--border-color-light);
            padding-top: 1.5rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.625rem 1.5rem;
            border-radius: 0.625rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            border: none; /* Rimuove il bordo default */
            text-decoration: none; /* Per i link */
            display: inline-flex; /* Per allineamento icona/testo se presente */
            align-items: center;
            justify-content: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--brand-green), var(--brand-green-dark));
            color: white;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.25);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--brand-green-dark), var(--brand-green));
            box-shadow: 0 6px 15px rgba(34, 153, 84, 0.35);
            transform: translateY(-1px);
        }

        .btn-cancel { /* Usato per "Torna alla lista" */
            background-color: #e5e7eb;
            color: #4b5563;
            border: 1px solid #d1d5db;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }
        .btn-cancel:hover {
            background-color: #d1d5db;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
        }

        /* Status badge (da visualizza_riparazioni.php per coerenza) */
        .status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
        .status.status-pending { background-color: var(--status-pending); color: white; }
        .status.status-in-progress { background-color: var(--status-in-progress); color: white; }
        .status.status-completed { background-color: var(--status-completed); color: white; }
        .status.status-delivered { background-color: var(--status-delivered); color: white; }
        .status.status-cancelled { background-color: var(--status-cancelled); color: white; }

        /* Animazioni */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Media Queries per la Responsiveness */
        @media (max-width: 768px) {
            body { padding-top: 70px; }
            .main-content-container {
                margin: 1.5rem auto;
                padding: 1.5rem;
            }

            h1 {
                font-size: 1.8rem;
                margin-bottom: 1.5rem;
            }

            .detail-card-header {
                flex-direction: column;
                align-items: flex-start;
                padding-bottom: 0.8rem;
                margin-bottom: 1rem;
            }

            .detail-card-header h2 {
                font-size: 1.3rem;
                margin-bottom: 0.4rem;
            }

            .detail-card-header span {
                font-size: 0.9rem;
            }

            .card-body-grid {
                grid-template-columns: 1fr; /* Una colonna su mobile */
                gap: 0.8rem;
            }

            .detail-item {
                padding: 0.7rem 0.9rem;
            }

            .detail-item label {
                font-size: 0.75rem;
            }

            .detail-item p {
                font-size: 0.9rem;
            }

            .card-actions {
                flex-direction: column;
                gap: 0.8rem;
                padding-top: 1rem;
            }

            .btn {
                width: 100%;
                font-size: 0.85rem;
                padding: 0.7rem 1.2rem;
            }
        }

        @media (max-width: 500px) {
            body { padding-top: 60px; }
            .main-content-container {
                margin: 1rem auto;
                padding: 1rem;
                border-radius: 0.5rem;
            }
            h1 {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            .detail-card {
                padding: 1rem;
                border-radius: 0.5rem;
            }
            .detail-card-header h2 {
                font-size: 1.1rem;
            }
            .detail-item {
                padding: 0.6rem 0.8rem;
            }
        }

    </style>
</head>
<body>

<?php include 'header.php'; // Assicurati che header.php includa il tuo menu di navigazione ?>

<div class="main-content-container">
    <h1>Dettagli Riparazione</h1>

    <div class="detail-card">
        <div class="detail-card-header">
            <h2>Riparazione #<?php echo htmlspecialchars($id); ?></h2>
            <span>Cliente: <?php echo htmlspecialchars($clienteCompleto ?: "Sconosciuto"); ?></span>
        </div>

        <div class="card-body-grid">
            <!-- Visualizza sempre il campo Cliente per primo, anche se è già nell'intestazione, per completezza -->
            <div class="detail-item">
                <label>Cliente</label>
                <p><?php echo htmlspecialchars($clienteCompleto ?: "Sconosciuto"); ?></p>
            </div>

            <?php foreach ($riparazione as $campo => $valore): ?>
                <?php
                // Formattazione speciale per alcuni campi
                $displayValue = htmlspecialchars($valore ?? '');
                if ($campo === 'costo_effettivo') {
                    $displayValue = formatCurrency((float)$valore);
                } elseif ($campo === 'data_creazione') {
                    $displayValue = date("d/m/Y H:i", strtotime($valore)); // Formato data e ora più completo
                } elseif ($campo === 'stato') {
                    $status_class = getStatusClasses($valore);
                    $displayValue = '<span class="status ' . $status_class . '">' . htmlspecialchars(ucfirst($valore)) . '</span>';
                }
                
                // Se il campo non è presente in $fieldLabels, possiamo ignorarlo o usare un'etichetta generica
                $label = isset($fieldLabels[$campo]) ? $fieldLabels[$campo] : ucfirst(str_replace('_', ' ', $campo));
                ?>
                <div class="detail-item">
                    <label><?php echo htmlspecialchars($label); ?></label>
                    <p><?php echo $displayValue; ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card-actions">
            <!-- Corretto il percorso del link: da visualizza_riparazioni.php a visualizza_riparazione.php -->
            <a href="visualizza_riparazione.php" class="btn btn-cancel">Torna alla lista</a>
            <a href="javascript:void(0)" onclick="window.opener.openEditRepairModal(<?php echo $id; ?>); window.close();" class="btn btn-primary">Modifica Riparazione</a>
        </div>
    </div>
</div>

<script>
    // Funzione per mostrare messaggi (copiata da visualizza_riparazioni.php)
    let messageTimeout;
    function showMessage(message, isError = false) {
        console.log(`showMessage: ${isError ? 'ERROR' : 'INFO'} - ${message}`);

        const messageContainer = document.getElementById('messageContainer');
        let messageBox = document.getElementById('messageBox');
        
        // Se il messageBox non esiste, crealo (per pagine senza container globali)
        if (!messageBox) {
            messageContainer = document.createElement('div');
            messageContainer.id = 'messageContainer';
            messageContainer.className = 'message-container hidden';
            document.body.appendChild(messageContainer);

            messageBox = document.createElement('div');
            messageBox.id = 'messageBox';
            messageBox.className = 'message-box';
            messageContainer.appendChild(messageBox);
        }

        clearTimeout(messageTimeout);

        messageBox.classList.remove('error', 'success', 'show');
        messageBox.style.animation = 'none'; 
        void messageBox.offsetWidth; // Trigger reflow to restart animation

        let iconSvg = '';
        if (isError) {
            messageBox.classList.add('error');
            iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.38 3.375 2.07 3.375h14.006c1.69 0 2.936-1.875 2.069-3.375l-7.005-12.004a1.125 1.125 0 00-1.932 0l-7.005 12.004zM12 15.75h.007v.008H12v-.008z" />
                       </svg>`;
        } else {
            messageBox.classList.add('success');
            iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                       </svg>`;
        }
        messageBox.innerHTML = `${iconSvg} <span>${message}</span>`;
        
        messageContainer.classList.remove('hidden');
        messageContainer.classList.add('active');
        messageBox.classList.add('show');

        const displayDuration = isError ? 1500 : 2000;
        const fadeOutDuration = 500;

        messageTimeout = setTimeout(() => {
            messageBox.classList.remove('show');
            messageBox.style.animation = 'fadeOutAnimation 0.5s forwards';
            setTimeout(() => {
                messageContainer.classList.add('hidden');
                messageContainer.classList.remove('active');
            }, fadeOutDuration);
        }, displayDuration);
    }

    // Aggiungi un listener per i messaggi provenienti dalla finestra che ha aperto questa
    window.addEventListener('message', function(event) {
        // Assicurati che il messaggio provenga da una sorgente fidata (adatta l'origin se necessario)
        // if (event.origin !== "http://tuo_dominio.com") return; // Rimuovi o adatta in produzione

        if (event.data && event.data.type === 'showAppMessage') {
            showMessage(event.data.message, event.data.isError);
        }
    });

    // Modifica il link "Modifica Riparazione" per chiamare la funzione del parent e chiudere la finestra
    // La funzione `openEditRepairModal` deve esistere nella finestra che ha aperto questa (e.g. storico_riparazioni.php)
    // Ho già aggiornato il link nell'HTML, questa parte JS è solo per riferimento o debug.
</script>

</body>
</html>

<?php
// Ensure $conn is closed only if it was successfully opened
if (isset($conn) && $conn !== null) {
    $conn->close();
}
?>
