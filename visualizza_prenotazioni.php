<?php
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Avvia la sessione PHP - Assicurati che sia sempre all'inizio del file
session_start();

// Determine if this is an AJAX request
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == 'true';

// Includi il file di connessione al database MySQL
if (!file_exists('db.php')) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Errore critico: Il file db.php non è stato trovato!']);
    } else {
        echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: Il file db.php non è stato trovato! Assicurati che il file esista nella stessa directory.</div>";
    }
    exit;
}
require_once 'db.php';

// Controlla subito se c'è stato un errore di connessione al database da db.php
// Se $conn non è stata inizializzata o è null, significa che la connessione è fallita.
if (!isset($conn) || $conn === null) {
    $db_error_message = isset($db_connection_error) ? $db_connection_error : 'Connessione al database non stabilita. Controlla le credenziali in db.php.';
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Errore critico: ' . $db_error_message]);
    } else {
        echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: " . htmlspecialchars($db_error_message, ENT_QUOTES, 'UTF-8') . "</div>";
    }
    exit;
}


// Inizializza la variabile message per l'uso nel HTML (se non è una richiesta AJAX)
$message = '';
if (!$is_ajax) {
    if (isset($_SESSION['message'])) {
        $sessionMessage = $_SESSION['message'];
        $sessionIsError = $_SESSION['isError'] ?? false;
        // Utilizza addslashes per gestire apici e altri caratteri speciali nel messaggio JS
        $message = "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('" . addslashes($sessionMessage) . "', " . ($sessionIsError ? 'true' : 'false') . "); });</script>";
        unset($_SESSION['message']);
        unset($_SESSION['isError']);
    }
}


// --- LOGICA PER RECUPERARE LE PRENOTAZIONI ---
$prenotazioni = [];
try {
    // Ordine predefinito
    $orderBy = $_GET['orderBy'] ?? 'id';
    $orderDir = $_GET['orderDir'] ?? 'DESC'; // Default newest first

    // Validazione per evitare SQL Injection nell'ordinamento
    // Assicurati che TUTTE queste colonne esistano nella tua tabella 'prenotazioni_prodotti'.
    $allowedOrderBy = ['id', 'product_name', 'quantity', 'product_total_price', 'deposit_amount', 'remaining_amount', 'customer_name', 'customer_phone', 'reservation_date', 'status', 'created_at', 'data_arrivo_previsto', 'notestext'];
    $allowedOrderDir = ['ASC', 'DESC'];

    $orderBy = in_array($orderBy, $allowedOrderBy) ? $orderBy : 'id';
    $orderDir = in_array($orderDir, $allowedOrderDir) ? $orderDir : 'DESC';

    // Gestione ricerca
    $searchTerm = $_GET['search'] ?? '';
    $whereClause = '';
    $queryParams = [];
    $paramTypes = '';

    if (!empty($searchTerm)) {
        // La ricerca ora include anche il nome del fornitore per il campo di ricerca generico
        $whereClause = " WHERE p.product_name LIKE ? OR p.customer_name LIKE ? OR p.customer_phone LIKE ? OR f.ragione_sociale LIKE ? ";
        $searchTermLike = '%' . $searchTerm . '%';
        $queryParams[] = $searchTermLike;
        $queryParams[] = $searchTermLike;
        $queryParams[] = $searchTermLike;
        $queryParams[] = $searchTermLike; // Aggiunto per fornitore_ragione_sociale
        $paramTypes .= 'ssss'; // Aggiornato a 'ssss'
    }

    // QUERY PRINCIPALE PER RECUPERARE LE PRENOTAZIONI
    // VERIFICA CHE TUTTE QUESTE COLONNE ESISTANO NEL TUO DATABASE!
    // In particolare:
    // - tabella `prenotazioni_prodotti` (alias `p`): tutte le colonne (p.*),
    //   e assicurati che le colonne `fornitore_id` e `data_arrivo_previsto` siano presenti.
    // - tabella `fornitori` (alias `f`): `id`, `ragione_sociale`
    $sql = "SELECT p.*, f.ragione_sociale AS fornitore_ragione_sociale
            FROM prenotazioni_prodotti p
            LEFT JOIN fornitori f ON p.fornitore_id = f.id" . $whereClause . " ORDER BY " . $orderBy . " " . $orderDir;

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new mysqli_sql_exception("Errore nella preparazione della query: " . $conn->error);
    }

    if (!empty($queryParams)) {
        // Usa call_user_func_array per bind_param con un numero variabile di parametri
        $stmt->bind_param($paramTypes, ...$queryParams);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $prenotazioni = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    error_log("Errore Visualizza Prenotazioni (SQL): " . $e->getMessage());
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Errore nel caricamento delle prenotazioni (SQL): ' . $e->getMessage()]);
    } else {
        $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Errore nel caricamento delle prenotazioni (SQL): " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', true); });</script>";
    }
    exit;
} catch (Exception $e) {
    error_log("Errore generico nel caricamento delle prenotazioni: " . $e->getMessage());
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Errore generico nel caricamento delle prenotazioni: ' . $e->getMessage()]);
    } else {
        $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Errore generico nel caricamento delle prenotazioni: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', true); });</script>";
    }
    exit;
}

// If it's an AJAX request, output JSON and exit
if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'prenotazioni' => $prenotazioni]);
    exit;
}


// --- RECUPERO DATI AGGIUNTIVI PER LE SELECT E AUTOCONFIGURAZIONE JS ---
// Questi dati sono usati per popolare i campi di autocompletamento nel modale
$fornitori_data = [];
try {
    $result_fornitori = $conn->query("SELECT id, ragione_sociale FROM fornitori ORDER BY ragione_sociale");
    if ($result_fornitori) {
        $fornitori_data = $result_fornitori->fetch_all(MYSQLI_ASSOC);
        $result_fornitori->free();
    }
} catch (mysqli_sql_exception $e) {
    error_log("Errore nel caricamento fornitori per JS: " . $e->getMessage());
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Attenzione: Errore nel caricamento dei fornitori per l\'autocompletamento: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', true); });</script>";
}

$prodotti_catalogo_data = [];
try {
    // Select product ID, name, and current quantity for the 'Articoli' tab
    $result_prodotti = $conn->query("SELECT id, nome, quantita FROM prodotti ORDER BY nome");
    if ($result_prodotti) {
        $prodotti_catalogo_data = $result_prodotti->fetch_all(MYSQLI_ASSOC);
        $result_prodotti->free();
    }
} catch (mysqli_sql_exception $e) {
    error_log("Errore nel caricamento prodotti catalogo per JS: " . $e->getMessage());
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Attenzione: Errore nel caricamento del catalogo prodotti per l\'autocompletamento: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', true); });</script>";
}

// NEW: Recupero dei movimenti di magazzino associati alle prenotazioni
$movimenti_prenotazione_data = [];
try {
    // Join con la tabella `prodotti` per ottenere il nome del prodotto
    $sql_movimenti = "SELECT pam.prenotazione_id, pam.prodotto_id, pam.quantita_movimentata, pam.tipo_movimento, pam.data_movimento, p.nome AS product_name
                      FROM prenotazioni_articoli_movimenti pam
                      JOIN prodotti p ON pam.prodotto_id = p.id
                      ORDER BY pam.data_movimento DESC";
    $result_movimenti = $conn->query($sql_movimenti);
    if ($result_movimenti) {
        $movimenti_prenotazione_data = $result_movimenti->fetch_all(MYSQLI_ASSOC);
        $result_movimenti->free();
    }
} catch (mysqli_sql_exception $e) {
    error_log("Errore nel caricamento movimenti prenotazione per JS: " . $e->getMessage());
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Attenzione: Errore nel caricamento dei movimenti di prenotazione: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', true); });</script>";
}

// NEW: Recupero della cronologia della prenotazione (SOLO DA DB, NESSUNA MOCK DATA)
$prenotazione_storico_data = [];
try {
    $sql_storico = "SELECT id, prenotazione_id, data_evento, evento_descrizione, utente FROM prenotazioni_storico ORDER BY data_evento ASC";
    $result_storico = $conn->query($sql_storico);
    if ($result_storico) {
        $prenotazione_storico_data = $result_storico->fetch_all(MYSQLI_ASSOC);
        $result_storico->free();
    }
    // Rimosso il blocco di generazione di mock data. Ora dipende interamente dal DB.

} catch (mysqli_sql_exception $e) {
    error_log("Errore nel caricamento storico prenotazione per JS: " . $e->getMessage());
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Attenzione: Errore nel caricamento della cronologia prenotazione: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', true); });</script>";
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizza Prenotazioni</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Variabili CSS per il tema verde e stili generali */
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

            /* Specific status colors (updated for consistent naming) */
            --status-pending: #f59e0b;     /* Orange for In Attesa */
            --status-completed: #10b981;   /* Brighter Green for Completata */
            --status-cancelled: #ef4444;   /* Red for Annullata */
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg-color-page);
            color: var(--text-color-primary);
            padding-top: 90px; /* Space for top-bar */
            line-height: 1.6;
        }
        
        /* Stili della top-bar (presupposti da header.php) */
        .top-bar {
            background-color: var(--brand-green);
            color: white;
            padding: 30px 30px;
            font-size: 18px;
            width: 100vw;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            gap: 150px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        .logo {
            font-size: 36px;
            font-weight: bold;
            white-space: nowrap;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }
        .logo:hover {
            color: white;
            text-decoration: none;
        }
        nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 15px;
        }
        nav ul li {
            position: relative;
        }
        nav ul li button,
        nav ul li a {
            background-color: white;
            border: none;
            color: #1a1a1a73;
            font-size: 16px;
            padding: 15px 30px;
            cursor: pointer;
            border-radius: 5px;
            user-select: none;
            text-decoration: none;
            display: block;
        }
        button.no-arrow::after {
            content: "";
        }
        nav ul li.has-dropdown > button::after,
        nav ul li.has-dropdown > a::after {
            content: " ▼";
            font-size: 10px;
            color: #1a1a1a73;
        }
        nav ul li ul.dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            min-width: 180px;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            padding: 0;
            margin: 0;
            list-style: none;
            z-index: 1000;
        }
        nav ul li:hover > ul.dropdown {
            display: block;
        }
        nav ul li ul.dropdown li a {
            padding: 8px 12px;
            color: #333;
        }
        nav ul li ul.dropdown li a:hover {
            background-color: var(--brand-green);
            color: white;
        }
        nav ul li ul.dropdown li.has-submenu > a::after {
            content: " ▶";
            float: right;
            font-size: 12px;
            margin-left: 10px;
            color: #333;
        }
        nav ul li ul.dropdown li ul.submenu {
            display: none;
            position: absolute;
            top: 0;
            left: 100%;
            background-color: white;
            min-width: 160px;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            padding: 0;
            margin: 0;
            list-style: none;
            z-index: 1100;
        }
        nav ul li ul.dropdown li:hover > ul.submenu {
            display: block;
        }
        nav ul li ul.submenu li a {
            padding: 8px 12px;
            color: #333;
        }
        nav ul li ul.submenu li a:hover {
            background-color: var(--brand-green);
            color: white;
        }
        /* Fine stili top-bar */

        /* Contenitore principale della pagina (come in visualizza_riparazioni.php) */
        .main-content-container {
            max-width: 1400px; /* Increased max-width */
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 700;
            color: var(--text-color-primary);
            font-size: 2rem; /* Ridotto ulteriormente per farlo più piccolo */
        }

        /* Stili per la barra di ricerca/filtro (come in visualizza_riparazioni.php) */
        .filter-search-card {
            background-color: var(--card-bg);
            padding: 1.5rem;
            border-radius: var(--card-radius);
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color-light);
        }
        .filter-search-card h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color-primary);
            margin-bottom: 1.25rem;
        }
        .filter-search-card label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-color-secondary);
        }
        .filter-search-card input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .filter-search-card input[type="text"]:focus {
            border-color: var(--brand-green);
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
            outline: none;
        }
        .filter-search-card button[type="submit"] {
            background-color: var(--brand-green);
            color: white;
            font-weight: 700;
            padding: 0.4rem 0.8rem; /* Ridotto il padding per renderli più piccoli */
            border-radius: 0; /* Rimosso il bordo arrotondato */
            border: none; /* Rimosso il bordo */
            transition: background-color 0.2s ease;
            box-shadow: none; /* Rimosso l'ombra */
        }
        .filter-search-card button[type="submit"]:hover {
            background-color: var(--brand-green-dark);
            box-shadow: none; /* Rimosso l'ombra */
        }
        .filter-search-card a.reset-btn {
            background-color: #e5e7eb;
            color: #4b5563;
            font-weight: 600;
            padding: 0.4rem 0.8rem; /* Ridotto il padding per renderli più piccoli */
            border-radius: 0; /* Rimosso il bordo arrotondato */
            border: none; /* Rimosso il bordo */
            transition: background-color 0.2s ease;
            box-shadow: none; /* Rimosso l'ombra */
        }
        .filter-search-card a.reset-btn:hover {
            background-color: #d1d5db;
        }

        /* Contenitore della tabella (come in visualizza_riparazioni.php) */
        .table-container-card {
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            overflow-x: auto; /* Permette lo scroll orizzontale su schermi piccoli */
            border: 1px solid var(--border-color-light);
            background-color: var(--card-bg);
            padding: 1.5rem; /* Padding interno alla card della tabella */
        }
        .table-container-card h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color-primary);
            margin-bottom: 1.25rem;
        }

        /* CUSTOM TABLE GRID STYLES */
        .custom-table-grid {
            display: flex;
            flex-direction: column;
            gap: 0.75rem; /* Spazio tra le righe */
        }

        .custom-table-head,
        .custom-table-row {
            display: grid;
            /* ORDER: ID, Prodotto, Quantità, Totale Prodotto, Acconto, Saldo, Cliente, Telefono Cliente, Data Prenotazione, Stato, Azioni, Data Creazione */
            grid-template-columns: 50px 2fr 1fr 1fr 1fr 1.5fr 1.5fr 1fr 1.2fr 110px 100px 140px; /* Adjusted column order and widths */
            align-items: center;
            padding: 0.75rem 0.5rem; /* Padding delle celle */
            border-radius: 0.375rem; /* Bordi arrotondati per le singole righe */
            font-size: 0.825rem; /* Dimensione del font */
            color: var(--text-color-primary);
            position: relative; /* Crucial for stacking context of rows */
            z-index: 1; /* Default stacking order for rows */
        }

        .custom-table-head {
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, var(--brand-green), var(--brand-green-dark));
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.25); /* Ombra per l'header */
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .custom-table-row {
            background: var(--card-bg);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); /* Ombra leggera per le righe */
            border: 1px solid var(--border-color-light);
            transition: transform 0.2s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.2s cubic-bezier(0.2, 0.8, 0.2, 1), background-color 0.2s ease;
            cursor: default;
        }
        .custom-table-row:nth-child(even) {
            background-color: #f8fafc; /* Sfondo leggermente diverso per righe pari */
        }
        .custom-table-row:hover {
            transform: translateY(-3px); /* Effetto lift al hover */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); /* Ombra più forte al hover */
            background-color: var(--brand-green-hover-bg); /* Tinta verde al hover */
        }

        /* New class for active row z-index */
        .custom-table-row.z-index-active-row {
            z-index: 10; /* Bring active row to front */
        }

        /* Stato badge */
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
        .status.status-completed { background-color: var(--status-completed); color: white; }
        .status.status-cancelled { background-color: var(--status-cancelled); color: white; }

        .total-cost { /* For monetary values like total product price, deposit, remaining */
            font-weight: 600;
            color: var(--brand-green-text);
            text-align: center;
            font-size: 1rem;
        }

        /* Bottone azioni e popup (stilizzati come dropdown di riparazioni) */
        .actions-wrapper {
            position: relative;
            user-select: none;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
        }
        .btn-actions {
            background-color: #e5e7eb; /* Colore di default come in prenotazioni */
            color: #4b5563;
            border: none;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .btn-actions:hover {
            background-color: #d1d5db;
            color: #1f2937;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
        }
        .btn-actions svg {
            width: 1rem;
            height: 1rem;
            transition: transform 0.2s ease;
        }
        .btn-actions.open svg {
            transform: rotate(180deg);
        }

        .popup {
            position: absolute;
            top: 100%; /* Sotto il bottone */
            right: 0; /* Align to the right of the button's wrapper */
            background: var(--card-bg);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            border-radius: 0.5rem;
            width: 180px; /* Larghezza adeguata */
            display: none;
            z-index: 999; /* Supera qualsiasi altro elemento per essere sempre visibile */
            overflow: hidden;
            opacity: 0;
            transform: translateY(10px); /* Inizia leggermente più in basso per l'animazione */
            transition: opacity 0.3s ease, transform 0.3s ease;
            border: 1px solid var(--border-color-light);
        }
        .popup.show {
            display: block;
            opacity: 1;
            transform: translateY(0); /* Torna alla posizione originale */
        }
        .popup ul {
            list-style: none;
            margin: 0; padding: 0;
        }
        .popup ul li {
            padding: 0.75rem 1rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-color-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: background-color 0.15s ease;
        }
        .popup ul li:hover {
            background-color: var(--brand-green-light);
            color: var(--brand-green-text);
        }
        .popup ul li.delete {
            color: var(--status-cancelled);
        }
        .popup ul li.delete:hover {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .popup ul li svg {
            width: 1rem;
            height: 1rem;
            fill: currentColor;
            stroke: currentColor;
        }

        /* Stili dei modali (copiati da visualizza_riparazioni.php) */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            background-color: var(--card-bg);
            padding: 2rem;
            border-radius: var(--card-radius);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            max-width: 90%;
            width: 950px; /* Increased max-width for tabs, consistent with previous prenotazioni */
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        .modal-overlay.show .modal-content {
            transform: translateY(0);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color-light);
            padding-bottom: 1rem;
            margin-bottom: 1.25rem;
        }
        .modal-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color-primary);
            margin: 0;
        }
        .modal-close-button {
            background: none;
            border: none;
            font-size: 1.8rem;
            color: #9ca3af;
            cursor: pointer;
            line-height: 1;
            transition: color 0.2s ease;
        }
        .modal-close-button:hover {
            color: #4b5563;
        }
        .modal-body {
            padding: 1.25rem 0;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            border-top: 1px solid var(--border-color-light);
            padding-top: 1.25rem;
            margin-top: 1.25rem;
        }
        .modal-footer button {
            padding: 0.625rem 1.5rem;
            border-radius: 0.625rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }
        .modal-footer .btn-cancel {
            background-color: #e5e7eb;
            color: #4b5563;
            border: 1px solid #d1d5db;
        }
        .modal-footer .btn-cancel:hover {
            background-color: #d1d5db;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
        }
        .modal-footer .btn-primary {
            background: linear-gradient(135deg, var(--brand-green), var(--brand-green-dark));
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.25);
        }
        .modal-footer .btn-primary:hover {
            background: linear-gradient(135deg, var(--brand-green-dark), var(--brand-green));
            box-shadow: 0 6px 15px rgba(34, 153, 84, 0.35);
            transform: translateY(-1px);
        }

        /* Form elements inside modal */
        .modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .modal-form-group {
            margin-bottom: 0.8rem;
        }
        .modal-form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-color-secondary);
            margin-bottom: 0.3rem;
        }
        .modal-form-group input,
        .modal-form-group textarea,
        .modal-form-group select {
            width: 100%;
            padding: 0.625rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.9375rem;
            color: var(--text-color-primary);
            background-color: #f9fafb;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .modal-form-group input:focus,
        .modal-form-group textarea:focus,
        .modal-form-group select:focus {
            border-color: var(--brand-green);
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
            outline: none;
            background-color: #fff;
        }
        .modal-form-group input[readonly] {
            background-color: #eceff1;
            color: #000; /* Forzato colore nero */
            cursor: not-allowed;
            opacity: 1; /* Forzata opacità completa */
        }
        .modal-form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        /* Message Box Styles (copiati da visualizza_riparazioni.php) */
        .message-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            pointer-events: none;
            background-color: rgba(0,0,0,0.0);
            transition: background-color 0.3s ease;
        }
        .message-container.active {
            background-color: rgba(0,0,0,0.3);
        }
        .message-box {
            background-color: #ffffff;
            color: #333;
            padding: 1.5rem 2.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            z-index: 10001;
            max-width: 90%;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            opacity: 0;
            transform: translateY(-50px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            pointer-events: auto;
        }
        .message-box.show {
            opacity: 1;
            transform: translateY(0);
        }
        .message-box.success {
            border: 2px solid var(--brand-green);
            color: var(--brand-green-text);
        }
        .message-box.error {
            border: 2px solid var(--status-cancelled);
            color: #c0392b; /* Darker red text */
        }
        .message-box svg {
            width: 28px;
            height: 28px;
            flex-shrink: 0;
        }
        .message-box.success svg {
            color: var(--brand-green);
        }
        .message-box.error svg {
            color: var(--status-cancelled);
        }
        @keyframes fadeOutAnimation {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-50px); }
        }
        @keyframes fadeInAnimation {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Stili per le schede (tabs) nel modale */
        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 1.5rem;
        }
        .tab-button {
            padding: 0.75rem 1.25rem;
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 500;
            color: #6b7280;
            transition: all 0.2s ease;
            border-bottom: 2px solid transparent;
        }
        .tab-button:hover {
            color: var(--brand-green);
            border-color: #bfdbfe;
        }
        .tab-button.active {
            color: var(--brand-green);
            border-color: var(--brand-green);
            font-weight: 600;
        }
        .tab-content {
            display: none; /* Hidden by default */
        }
        .tab-content.active {
            display: block;
        }
        /* Style for autocomplete list */
        .autocomplete-list {
            position: absolute;
            background-color: white !important; /* Force white background */
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            max-height: 200px;
            overflow-y: auto;
            z-index: 2000 !important; /* Increased z-index to be on top of modal */
            width: 100%;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            top: 100%; /* Position explicitly below the input */
            display: none; /* Initially hidden, controlled by JS */
        }
        .autocomplete-list div {
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            border-bottom: 1px solid #e5e7eb;
            color: var(--text-color-primary) !important; /* Ensure text is visible */
        }
        .autocomplete-list div:last-child {
            border-bottom: none;
        }
        .autocomplete-list div:hover {
            background-color: #f3f4f6;
            color: var(--brand-green);
            font-weight: 500;
        }

        /* History Entry styles from previous version */
        .history-entry {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            color: var(--text-color-primary);
            box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);
        }
        .history-entry .timestamp {
            font-weight: 600;
            color: #4a5568;
            margin-right: 1rem;
            min-width: 150px; /* Ensure timestamp is visible */
            flex-shrink: 0;
        }
        .history-entry .description {
            flex-grow: 1;
            color: #4a5568;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1500px) {
            .custom-table-head, .custom-table-row {
                /* ORDER: ID, Prodotto, Quantità, Totale Prodotto, Acconto, Saldo, Cliente, Telefono Cliente, Data Prenotazione, Stato, Azioni, Data Creazione */
                grid-template-columns: 50px 2fr 1fr 1fr 1fr 1.5fr 1.5fr 1fr 1.2fr 110px 100px 140px; 
            }
        }

        @media (max-width: 1200px) {
            body { padding: 15px; padding-top: 80px; } /* Reduce padding */
            h1 { font-size: 2rem; margin-bottom: 30px; }
            .main-content-container { margin: 1.5rem auto; padding: 1.5rem; }
            .filter-search-card { padding: 1.25rem; margin-bottom: 1rem; }
            .filter-search-card h2 { font-size: 1.3rem; margin-bottom: 1rem; }
            .table-container-card { padding: 1.25rem; }
            .table-container-card h2 { font-size: 1.1rem; margin-bottom: 1rem; }

            .custom-table-head, .custom-table-row {
                /* ORDER: ID, Prodotto, Quantità, Totale Prodotto, Acconto, Saldo, Cliente, Telefono Cliente, Data Prenotazione, Stato, Azioni, Data Creazione */
                grid-template-columns: 40px 1.5fr 0.8fr 0.8fr 0.8fr 1.2fr 1.2fr 0.8fr 1fr 90px 80px 110px; /* Adjusted column widths for smaller screens */
                padding: 0.6rem 0.8rem;
                font-size: 0.8rem;
            }
            .status {
                padding: 0.2rem 0.6rem;
                font-size: 0.7rem;
            }
            .btn-actions {
                padding: 0.4rem 0.6rem;
                font-size: 0.8rem;
                gap: 0.2rem;
            }
            .btn-actions svg {
                width: 0.9rem; height: 0.9rem;
            }
            .popup {
                width: 160px; /* Reduced popup width */
                border-radius: 0.375rem;
            }
            .popup ul li {
                padding: 0.6rem 0.8rem;
                font-size: 0.8rem;
                gap: 0.5rem;
            }
            .popup ul li svg {
                width: 0.9rem; height: 0.9rem;
            }

            /* Modal responsive adjustments */
            .modal-content {
                width: 800px; /* Adjust modal width for tablet */
            }
        }

        @media (max-width: 900px) {
            body { padding: 10px; padding-top: 70px; }
            h1 { font-size: 1.8rem; margin-bottom: 25px; }
            .main-content-container { margin: 1rem auto; padding: 1rem; }
            .filter-search-card { padding: 1rem; margin-bottom: 0.8rem; }
            .filter-search-card h2 { font-size: 1.2rem; margin-bottom: 0.8rem; }
            .filter-search-card button, .filter-search-card a { padding: 0.6rem 1rem; font-size: 0.9rem; }
            .table-container-card { padding: 1rem; }
            .table-container-card h2 { font-size: 1rem; margin-bottom: 0.8rem; }
            
            .custom-table-head { display: none; } /* Nasconde l'header tradizionale su mobile */
            
            .custom-table-row {
                grid-template-columns: none; /* Disabilita la griglia */
                display: flex; 
                flex-direction: column; /* Impila le colonne verticalmente */
                align-items: flex-start;
                gap: 0.4rem;
                padding: 0.8rem;
                font-size: 0.85rem;
                border-bottom: 1px solid var(--border-color-light);
                border-radius: 0.75rem; /* Mantiene le righe come "card" */
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); /* Ombra per le card mobile */
            }

            .custom-table-row > div {
                width: 100%;
                text-align: left !important;
                white-space: normal; /* Permette al testo di andare a capo */
                overflow: visible; /* Assicura che il contenuto non sia nascosto */
                text-overflow: clip; /* Nessun ellipsis quando il testo va a capo */
            }
            .custom-table-row > div::before {
                content: attr(data-label);
                font-weight: 700;
                color: var(--text-color-secondary);
                margin-right: 0.5rem;
                display: inline-block;
                min-width: 60px; /* Allinea le etichette */
            }
            /* Etichette specifiche per mobile */
            .custom-table-row > div:nth-child(1)::before { content: 'ID: '; }
            .custom-table-row > div:nth-child(2)::before { content: 'Prodotto: '; }
            .custom-table-row > div:nth-child(3)::before { content: 'Quantità: '; }
            /* div:nth-child(4) 'Prezzo Unit.' removed */
            .custom-table-row > div:nth-child(4)::before { content: 'Totale Prod.: '; } /* Re-indexed */
            .custom-table-row > div:nth-child(5)::before { content: 'Acconto: '; } /* Re-indexed */
            .custom-table-row > div:nth-child(6)::before { content: 'Saldo: '; } /* Re-indexed */
            .custom-table-row > div:nth-child(7)::before { content: 'Cliente: '; } /* Re-indexed */
            .custom-table-row > div:nth-child(8)::before { content: 'Telefono Cliente: '; } /* Re-indexed */
            .custom-table-row > div:nth-child(9)::before { content: 'Data Pren.: '; } /* Re-indexed */
            .custom-table-row > div:nth-child(10)::before { content: 'Stato: '; } /* Re-indexed */
            .custom-table-row > div:nth-child(11)::before { content: 'Azioni: '; } /* Re-indexed */
            .custom-table-row > div:nth-child(12)::before { content: 'Data Creazione: '; } /* Re-indexed */


            .total-cost { text-align: left !important; }
            .status { margin: 0; }
            .actions-wrapper { 
                width: 100%; 
                text-align: left; 
                margin-top: 0.8rem; 
                justify-content: flex-start;
                padding-left: 0;
            }
            .btn-actions { width: auto; min-width: 140px; margin: 0;}
            .popup { 
                left: 0; 
                right: auto;
                transform: translateY(10px);
                width: 90%; max-width: 200px;
            }

            /* Modali su mobile */
            .modal-content {
                padding: 1.25rem;
                border-radius: 0.5rem;
                width: 90%; /* Smaller modal width for phone */
            }
            .modal-header h2 { font-size: 1.3rem; }
            .modal-close-button { font-size: 1.5rem; }
            .modal-body { padding: 1rem 0; }
            .modal-footer { gap: 0.8rem; padding-top: 1rem; margin-top: 1rem;}
            .modal-footer button { padding: 0.5rem 1rem; font-size: 0.9rem; border-radius: 0.5rem;}
            .modal-grid { grid-template-columns: 1fr; gap: 0.8rem;} /* Una colonna su mobile */
            .modal-form-group input, .modal-form-group textarea, .modal-form-group select {
                padding: 0.5rem; font-size: 0.9rem; border-radius: 0.3rem;
            }
        }

        @media (max-width: 500px) {
            body { padding: 8px; padding-top: 60px; }
            h1 { font-size: 1.5rem; margin-bottom: 20px; }
            .main-content-container { margin: 0.8rem auto; padding: 0.8rem; border-radius: 0.5rem;}
            .filter-search-card { padding: 0.8rem; margin-bottom: 0.6rem; }
            .filter-search-card h2 { font-size: 1rem; margin-bottom: 0.6rem; }
            .filter-search-card button, .filter-search-card a { padding: 0.5rem 0.8rem; font-size: 0.85rem; }
            .table-container-card { padding: 0.8rem; border-radius: 0.5rem;}
            .table-container-card h2 { font-size: 0.9rem; margin-bottom: 0.6rem; }

            .custom-table-row { padding: 0.6rem; border-radius: 0.5rem;}
            .status { font-size: 0.65rem; padding: 0.15rem 0.5rem; }
            .btn-actions { font-size: 0.75rem; padding: 0.3rem 0.5rem;}
            .popup { width: 140px; }
            .popup ul li { padding: 0.5rem 0.7rem; font-size: 0.75rem;}
        }

    </style>
</head>
<body>
    <?php include 'header.php'; // Includi la barra di navigazione ?>

    <div class="main-content-container">
        <h1>Visualizza Prenotazioni</h1>

        <?php echo $message; // Mostra messaggi di sistema (se presenti da PHP) ?>

        <div class="filter-search-card">
            <h2>Filtra e Cerca Prenotazioni</h2>
            <form id="searchForm" method="GET" action="visualizza_prenotazioni.php" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Cerca per Prodotto, Cliente o Telefono</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Es. Smartphone, Mario Rossi, 342..." class="w-full p-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-base">
                </div>
                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-grow bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-5 rounded-md transition duration-200 ease-in-out shadow-lg">
                        Cerca
                    </button>
                    <a href="#" id="resetBtn" class="flex-shrink-0 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-5 rounded-md transition duration-200 ease-in-out reset-btn">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="table-container-card">
            <h2>Lista Prenotazioni</h2>
            <div class="custom-table-grid" id="reservationsTableBody" role="grid" aria-label="Lista delle prenotazioni">
                <div class="custom-table-head" role="row">
                    <div role="columnheader" tabindex="0" data-sort="id">ID</div>
                    <div role="columnheader" tabindex="0" data-sort="product_name">Prodotto</div>
                    <div role="columnheader" tabindex="0" data-sort="quantity">Quantità</div>
                    <!-- Prezzo Unitario rimosso -->
                    <div role="columnheader" tabindex="0" data-sort="product_total_price">Totale Prodotto</div>
                    <div role="columnheader" tabindex="0" data-sort="deposit_amount">Acconto</div>
                    <div role="columnheader" tabindex="0" data-sort="remaining_amount">Saldo</div>
                    <div role="columnheader" tabindex="0" data-sort="customer_name">Cliente</div>
                    <div role="columnheader" tabindex="0" data-sort="customer_phone">Telefono Cliente</div>
                    <div role="columnheader" tabindex="0" data-sort="reservation_date">Data Prenotazione</div>
                    <div role="columnheader" tabindex="0" data-sort="status">Stato</div>
                    <div role="columnheader">Azioni</div>
                    <div role="columnheader" tabindex="0" data-sort="created_at">Data Creazione</div>
                </div>

                <?php if (empty($prenotazioni)): ?>
                    <div class="custom-table-row" role="row" tabindex="0" style="justify-content:center; color: var(--text-color-secondary);">
                        Nessuna prenotazione trovata.
                    </div>
                <?php else: ?>
                    <?php foreach ($prenotazioni as $prenotazione): ?>
                        <?php
                            $prod_name = htmlspecialchars($prenotazione['product_name'] ?? '');
                            $qty = htmlspecialchars($prenotazione['quantity'] ?? '');
                            // $unit_p = formatCurrency($prenotazione['unit_price'] ?? 0); // Rimosso
                            $total_p = number_format($prenotazione['product_total_price'] ?? 0, 2, ',', '.') . ' €';
                            $deposit_a = number_format($prenotazione['deposit_amount'] ?? 0, 2, ',', '.') . ' €';
                            $remaining_a = number_format($prenotazione['remaining_amount'] ?? 0, 2, ',', '.') . ' €';
                            $cust_name = htmlspecialchars($prenotazione['customer_name'] ?? '');
                            $cust_phone = htmlspecialchars($prenotazione['customer_phone'] ?? '');
                            $res_date = htmlspecialchars(isset($prenotazione['reservation_date']) ? date('d/m/Y', strtotime($prenotazione['reservation_date'])) : '');
                            
                            // PHP version of getStatusClasses (for initial load)
                            $status_class = '';
                            switch ($prenotazione['status'] ?? '') {
                                case 'In Attesa':
                                    $status_class = 'status-pending';
                                    break;
                                case 'Completata':
                                    $status_class = 'status-completed';
                                    break;
                                case 'Annullata':
                                    $status_class = 'status-cancelled';
                                    break;
                                default:
                                    $status_class = 'status-pending';
                                    break;
                            }

                            $created_at_fmt = htmlspecialchars(isset($prenotazione['created_at']) ? date('d/m/Y H:i', strtotime($prenotazione['created_at'])) : '');
                        ?>
                        <div class="custom-table-row" role="row" tabindex="0" aria-rowindex="<?= $prenotazione['id'] ?>">
                            <div role="gridcell" data-label="ID:"><?= $prenotazione['id'] ?></div>
                            <div role="gridcell" data-label="Prodotto:"><?= $prod_name ?></div>
                            <div role="gridcell" data-label="Quantità:"><?= $qty ?></div>
                            <!-- Prezzo Unitario Cell rimosso -->
                            <div role="gridcell" data-label="Totale Prodotto:"><?= $total_p ?></div>
                            <div role="gridcell" data-label="Acconto:"><?= $deposit_a ?></div>
                            <div role="gridcell" data-label="Saldo:"><?= $remaining_a ?></div>
                            <div role="gridcell" data-label="Cliente:"><?= $cust_name ?></div>
                            <div role="gridcell" data-label="Telefono Cliente:"><?= $cust_phone ?></div>
                            <div role="gridcell" data-label="Data Prenotazione:"><?= $res_date ?></div>
                            <div role="gridcell" data-label="Stato:">
                                <span class="status <?= $status_class ?>" aria-label="Stato prenotazione: <?= htmlspecialchars($prenotazione['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <?= ucfirst($prenotazione['status'] ?? '') ?>
                                </span>
                            </div>
                            <div role="gridcell" class="actions-wrapper">
                                <button class="btn-actions" aria-haspopup="true" aria-expanded="false" aria-controls="popup-<?= $prenotazione['id'] ?>" aria-label="Apri menu azioni prenotazione ID <?= $prenotazione['id'] ?>">
                                    Azioni
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 10l5 5 5-5z"/></svg>
                                </button>
                                <div class="popup" id="popup-<?= $prenotazione['id'] ?>" role="menu" aria-label="Azioni prenotazione <?= $prenotazione['id'] ?>">
                                    <ul>
                                        <li role="menuitem" tabindex="-1" onclick="openEditReservationModal(<?= $prenotazione['id'] ?>)">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M12.146.146a.5.5 0 01.708 0l3 3a.5.5 0 010 .708l-9.793 9.793a.5.5 0 01-.168.11l-5 2a.5.5 0 01-.65-.65l2-5a.5.5 0 01.11-.168L12.146.146zM11.207 2L4 9.207V11h1.793L14 3.793 11.207 2z"/>
                                            </svg>
                                            Modifica
                                        </li>
                                        <li role="menuitem" tabindex="-1" onclick="window.open('stampa_prenotazione.php?id=<?= $prenotazione['id'] ?>','_blank')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M2 2h12v10H2V2zm1 1v8h10V3H3z"/>
                                                <path d="M5 12h6v1H5v-1z"/>
                                            </svg>
                                            Stampa scheda
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div role="gridcell" data-label="Data Creazione:"><?= $created_at_fmt ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Message Box Container -->
    <div id="messageContainer" class="message-container hidden">
        <div id="messageBox" class="message-box"></div>
    </div>

    <!-- Main Modal Overlay -->
    <div id="mainModal" class="modal-overlay hidden">
        <!-- Edit Reservation Modal Content -->
        <div id="editReservationModalContent" class="modal-content hidden">
            <div class="modal-header">
                <h2 id="editModalTitle">Modifica Prenotazione #<span id="modalReservationId"></span></h2>
                <button class="modal-close-button" onclick="closeModal()">&times;</button>
            </div>
            <div class="tab-buttons">
                <button type="button" class="tab-button active" data-tab="anagrafe">Anagrafe</button>
                <button type="button" class="tab-button" data-tab="articoli">Articoli</button>
                <button type="button" class="tab-button" data-tab="scheda">Scheda</button>
            </div>

            <form id="editReservationForm">
                <input type="hidden" id="editReservationId" name="id">

                <!-- Tab: Anagrafe -->
                <div id="anagrafeTabContent" class="tab-content active">
                    <div class="modal-grid">
                        <div class="modal-form-group">
                            <label for="editReservationDisplayId">ID Prenotazione</label>
                            <input type="text" id="editReservationDisplayId" class="w-full" readonly>
                        </div>
                        <div class="modal-form-group">
                            <label for="editProductName">Nome Prodotto</label>
                            <input type="text" id="editProductName" name="product_name" required>
                        </div>
                        <div class="modal-form-group">
                            <label for="editQuantity">Quantità</label>
                            <input type="number" step="1" id="editQuantity" name="quantity" required>
                        </div>
                        <!-- Prezzo Unitario rimosso -->
                        <div class="modal-form-group">
                            <label for="editProductTotalPrice">Totale Prodotto (€)</label>
                            <input type="number" step="0.01" id="editProductTotalPrice" name="product_total_price">
                        </div>

                        <!-- Gestione Acconti -->
                        <div class="modal-form-group col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-4 mt-4 border-gray-200">
                            <div>
                                <label for="editDepositAmount">Totale Acconti Pagati (€)</label>
                                <input type="number" step="0.01" id="editDepositAmount" name="deposit_amount" readonly>
                            </div>
                            <div class="flex items-end space-x-2">
                                <div class="flex-grow">
                                    <label for="editNewDepositAmount">Aggiungi Nuovo Acconto (€)</label>
                                    <input type="number" step="0.01" id="editNewDepositAmount" value="0.00">
                                </div>
                                <button type="button" id="addDepositBtn" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2.5 px-4 rounded-md transition duration-200 ease-in-out">
                                    +
                                </button>
                            </div>
                            <div>
                                <label for="editRemainingAmount">Saldo da Dare (€)</label>
                                <input type="number" step="0.01" id="editRemainingAmount" name="remaining_amount" readonly>
                            </div>
                        </div>

                        <!-- Cliente e Dettagli Aggiuntivi -->
                        <div class="modal-form-group">
                            <label for="editCustomerName">Nome Cliente</label>
                            <input type="text" id="editCustomerName" name="customer_name" required>
                        </div>
                        <div class="modal-form-group">
                            <label for="editCustomerPhone">Telefono Cliente</label>
                            <input type="text" id="editCustomerPhone" name="customer_phone">
                        </div>
                        <div class="modal-form-group col-span-2 relative">
                            <label for="editSupplierName">Fornitore</label>
                            <input type="text" id="editSupplierName" placeholder="Cerca o seleziona fornitore">
                            <input type="hidden" id="editSupplierId" name="fornitore_id">
                            <div id="editSupplierAutocompleteList" class="autocomplete-list w-full mt-1"></div>
                        </div>
                        <div class="modal-form-group">
                            <label for="editReservationDate">Data Prenotazione</label>
                            <input type="date" id="editReservationDate" name="reservation_date" required>
                        </div>
                        <div class="modal-form-group">
                            <label for="editExpectedArrivalDate">Data Arrivo Previsto</label>
                            <input type="date" id="editExpectedArrivalDate" name="data_arrivo_previsto">
                        </div>
                        <div class="modal-form-group col-span-2">
                            <label for="editNotes">Note</label>
                            <textarea id="editNotes" name="notestext" rows="3"></textarea>
                        </div>
                        <div class="modal-form-group">
                            <label for="editStatus">Stato</label>
                            <select id="editStatus" name="status">
                                <option value="In Attesa">In Attesa</option>
                                <option value="Completata">Completata</option>
                                <option value="Annullata">Annullata</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tab: Articoli -->
                <div id="articoliTabContent" class="tab-content">
                    <h4 class="text-lg font-medium text-gray-700 mb-4">Gestione Articoli Magazzino per questa Prenotazione</h4>
                    <p class="text-sm text-gray-600 mb-4">Usa questa sezione per scaricare prodotti dal magazzino e registrarli per questa prenotazione.</p>

                    <div id="stockManagementSection" class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end mb-6 border-b pb-4 border-gray-200">
                        <div class="relative modal-form-group">
                            <label for="searchProductToUnload">Cerca Prodotto da Scaricare</label>
                            <input type="text" id="searchProductToUnload" placeholder="Nome prodotto">
                            <input type="hidden" id="selectedProductIdToUnload">
                            <div id="productToUnloadAutocompleteList" class="autocomplete-list w-full mt-1"></div>
                        </div>
                        <div class="modal-form-group">
                            <label for="productCurrentStock">Giacenza Attuale</label>
                            <input type="text" id="productCurrentStock" readonly value="N/D">
                        </div>
                        <div class="modal-form-group">
                            <label for="quantityToUnload">Quantità da Scaricare</label>
                            <input type="number" step="1" id="quantityToUnload" min="1" value="1">
                        </div>
                        <button type="button" id="unloadFromStockBtn" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-4 rounded-md transition duration-200 ease-in-out">
                            Scarica da Magazzino
                        </button>
                    </div>

                    <h5 class="text-md font-medium text-gray-700 mb-3">Riepilogo Articoli Scaricati per questa Prenotazione:</h5>
                    <div id="reservationMovementsList" class="space-y-3 max-h-60 overflow-y-auto border border-gray-200 rounded-md p-3 bg-gray-50">
                        <!-- Qui verranno caricati gli articoli già associati a questa prenotazione -->
                        <p class="text-sm text-gray-500 italic" id="noMovementsMessage">Nessun articolo scaricato per questa prenotazione.</p>
                    </div>

                    <!-- Button to show stock management section -->
                    <button type="button" id="showStockManagementBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-md transition duration-200 ease-in-out mt-4 hidden">
                        Aggiungi Articolo da Scaricare
                    </button>
                </div>

                <!-- Tab: Scheda -->
                <div id="schedaTabContent" class="tab-content">
                    <h4 class="text-lg font-medium text-gray-700 mb-4">Cronologia Prenotazione</h4>
                    <p class="text-sm text-gray-600 mb-4">Visualizza gli eventi importanti relativi a questa prenotazione.</p>
                    <div id="reservationHistoryList" class="space-y-3 max-h-80 overflow-y-auto border border-gray-200 rounded-md p-3 bg-gray-50">
                        <!-- Qui verranno caricati gli eventi della cronologia -->
                        <p class="text-sm text-gray-500 italic" id="noHistoryEntriesMessage">Nessun evento registrato per questa prenotazione.</p>
                    </div>
                </div>
            </form>

            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal()">Annulla</button>
                <button class="btn-primary" onclick="saveReservation()">Salva Modifiche</button>
            </div>
        </div>
    </div>

<script>
    // Funzione per mostrare messaggi (migliorata per debug e stili)
    let messageTimeout; // Per tenere traccia del timeout del messaggio

    function showMessage(message, isError = false) {
        console.log(`showMessage: ${isError ? 'ERROR' : 'INFO'} - ${message}`); // DEBUG LOG

        const messageContainer = document.getElementById('messageContainer');
        const messageBox = document.getElementById('messageBox');
        
        // Pulisci eventuali timeout precedenti
        clearTimeout(messageTimeout);

        // Resetta le classi e lo stile dell'animazione
        messageBox.classList.remove('error', 'success', 'show');
        messageBox.style.animation = 'none'; 
        void messageBox.offsetWidth; // Trigger reflow per riapplicare l'animazione

        // Aggiungi l'icona appropriata
        let iconSvg = '';
        if (isError) {
            messageBox.classList.add('error');
            iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.38 3.375 2.07 3.375h14.006c1.69 0 2.936-1.875 2.069-3.375l-7.005-12.004a1.125 1.125 0 00-1.932 0l-7.005 12.004zM12 15.75h.007v.008H12v-.008z" />
                       </svg>`;
        } else {
            messageBox.classList.add('success'); // Nuova classe per i messaggi di successo
            iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                       </svg>`;
        }
        messageBox.innerHTML = `${iconSvg} <span>${message}</span>`;
        
        messageContainer.classList.remove('hidden'); // Mostra il contenitore
        messageContainer.classList.add('active'); // Aggiungi la classe per lo sfondo semi-trasparente
        messageBox.classList.add('show'); // Fa apparire il box con animazione fadeIn

        // Imposta un timer per far scomparire il messaggio e nascondere il contenitore
        const displayDuration = isError ? 2000 : 1500; // Durata di visualizzazione (2s per errori, 1.5s per successi)
        const fadeOutDuration = 500; // Durata dell'animazione di fadeOut (deve corrispondere al CSS)

        messageTimeout = setTimeout(() => {
            messageBox.classList.remove('show'); // Innesca l'animazione di fadeOut
            messageBox.style.animation = 'fadeOutAnimation 0.5s forwards';
            setTimeout(() => {
                messageContainer.classList.add('hidden'); // Nasconde il contenitore dopo l'animazione
                messageContainer.classList.remove('active'); // Rimuovi la classe per lo sfondo
            }, fadeOutDuration);
        }, displayDuration);
    }

    // Data passed from PHP to JavaScript
    const initialFornitori = <?php echo json_encode($fornitori_data); ?>;
    const initialProdottiCatalogo = <?php echo json_encode($prodotti_catalogo_data); ?>;
    const initialMovimentiPrenotazione = <?php echo json_encode($movimenti_prenotazione_data); ?>;
    const initialPrenotazioneStorico = <?php echo json_encode($prenotazione_storico_data); ?>;

    // References to DOM elements
    const searchForm = document.getElementById('searchForm');
    const searchTermInput = document.getElementById('search');
    const resetBtn = document.getElementById('resetBtn');
    const reservationsTableBody = document.getElementById('reservationsTableBody');

    const mainModal = document.getElementById('mainModal');
    const editReservationModalContent = document.getElementById('editReservationModalContent');
    const modalReservationIdSpan = document.getElementById('modalReservationId');
    const editReservationIdInput = document.getElementById('editReservationId');
    const editProductNameInput = document.getElementById('editProductName');
    const editQuantityInput = document.getElementById('editQuantity');
    const editProductTotalPriceInput = document.getElementById('editProductTotalPrice'); // No longer readonly
    const editDepositAmountInput = document.getElementById('editDepositAmount');
    const editNewDepositAmountInput = document.getElementById('editNewDepositAmount');
    const addDepositBtn = document.getElementById('addDepositBtn');
    const editRemainingAmountInput = document.getElementById('editRemainingAmount');
    const editCustomerNameInput = document.getElementById('editCustomerName');
    const editCustomerPhoneInput = document.getElementById('editCustomerPhone');
    const editReservationDateInput = document.getElementById('editReservationDate');
    const editExpectedArrivalDateInput = document.getElementById('editExpectedArrivalDate');
    const editNotesInput = document.getElementById('editNotes');
    const editStatusSelect = document.getElementById('editStatus');

    const editSupplierNameInput = document.getElementById('editSupplierName');
    const editSupplierIdInput = document.getElementById('editSupplierId');
    const editSupplierAutocompleteList = document.getElementById('editSupplierAutocompleteList');

    const searchProductToUnloadInput = document.getElementById('searchProductToUnload');
    const selectedProductIdToUnloadInput = document.getElementById('selectedProductIdToUnload');
    const productToUnloadAutocompleteList = document.getElementById('productToUnloadAutocompleteList');
    const productCurrentStockInput = document.getElementById('productCurrentStock');
    const quantityToUnloadInput = document.getElementById('quantityToUnload');
    const unloadFromStockBtn = document.getElementById('unloadFromStockBtn');
    const reservationMovementsList = document.getElementById('reservationMovementsList');
    const noMovementsMessage = document.getElementById('noMovementsMessage');
    const stockManagementSection = document.getElementById('stockManagementSection');
    const showStockManagementBtn = document.getElementById('showStockManagementBtn');

    const reservationHistoryList = document.getElementById('reservationHistoryList');
    const noHistoryEntriesMessage = document.getElementById('noHistoryEntriesMessage');
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    let originalReservationData = {};
    let currentModalHistoryUpdates = [];
    // `currentActiveReservations` will be updated dynamically after AJAX calls
    let currentActiveReservations = <?php echo json_encode($prenotazioni); ?>; 

    // Helper functions for JavaScript to be used in the browser
    /**
     * Formats a numeric value as currency in EUR.
     * @param {number} value - The numeric value to format.
     * @returns {string} The formatted currency string.
     */
    function formatCurrency(value) {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    }

    /**
     * Capitalizes the first letter of a string.
     * @param {string} str - The input string.
     * @returns {string} The string with the first letter capitalized.
     */
    function ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    /**
     * Escapes HTML special characters in a string to prevent XSS.
     * @param {*} text - The input value to escape.
     * @returns {string} The HTML-escaped string.
     */
    function escapeHtml(text) {
        // Ensure text is a string
        const str = String(text);
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Formats a date string into 'DD/MM/YYYY' format.
     * @param {string} dateString - The date string (e.g., 'YYYY-MM-DD').
     * @returns {string} The formatted date string.
     */
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    /**
     * Formats a date-time string into 'DD/MM/YYYY HH:MM' format.
     * @param {string} dateString - The date-time string (e.g., 'YYYY-MM-DD HH:MM:SS').
     * @returns {string} The formatted date-time string.
     */
    function formatDateTime(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    /**
     * Returns CSS classes based on the reservation status.
     * @param {string} status - The status of the reservation.
     * @returns {string} The CSS class string.
     */
    function getStatusClasses(status) {
        switch (status) {
            case 'In Attesa':
                return 'status-pending';
            case 'Completata':
                return 'status-completed';
            case 'Annullata':
                return 'status-cancelled';
            default:
                return 'status-pending'; // Default to "In Attesa" for unrecognized statuses
        }
    }


    /**
     * Re-initializes all dynamic event listeners after table content updates.
     */
    function initEventListeners() {
        // --- Gestione Ordinamento Tabella ---
        document.querySelectorAll('.custom-table-head div[role="columnheader"]').forEach(header => {
            header.removeEventListener('click', handleSortClick); // Prevent duplicate listeners
            header.addEventListener('click', handleSortClick);
        });

        // --- Gestione Dropdown Azioni (Popups) ---
        document.querySelectorAll('.btn-actions').forEach(button => {
            button.removeEventListener('click', handleActionButtonClick); // Prevent duplicate listeners
            button.addEventListener('click', handleActionButtonClick);
        });
    }

    /**
     * Handles clicks on table header for sorting.
     * @param {Event} event - The click event.
     */
    function handleSortClick(event) {
        const sortBy = this.dataset.sort;
        if (!sortBy) return;

        const urlParams = new URLSearchParams(window.location.search);
        let currentOrderDir = urlParams.get('orderDir') || 'DESC';

        // Toggle sort direction if clicking the same column
        if (urlParams.get('orderBy') === sortBy) {
            currentOrderDir = currentOrderDir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            // Default to DESC for new sort column
            currentOrderDir = 'DESC';
        }

        urlParams.set('orderBy', sortBy);
        urlParams.set('orderDir', currentOrderDir);
        urlParams.set('search', searchTermInput.value); // Preserve search term
        
        loadReservationsTable(urlParams.toString());
    }

    /**
     * Handles clicks on the action button to open/close the popup.
     * @param {Event} event - The click event.
     */
    let activeDropdownElement = null; // Stores the currently open popup element
    let activeDropdownButton = null; // Stores the button that opened the current popup

    function handleActionButtonClick(event) {
        event.stopPropagation(); // Prevent document click from closing it immediately

        const popupMenu = this.nextElementSibling; // The .popup div is the next sibling
        
        // If this dropdown is already open, close it
        if (activeDropdownButton === this && activeDropdownElement && activeDropdownElement.classList.contains('show')) {
            closeAllPopups();
        } else {
            // Close any currently open dropdown before opening a new one
            closeAllPopups();
            
            // Open the new dropdown
            popupMenu.classList.add('show');
            this.classList.add('open'); // Add class to rotate arrow

            activeDropdownElement = popupMenu;
            activeDropdownButton = this;

            // Add z-index to parent row to bring it to front (visual effect for overlapping rows)
            const parentRow = this.closest('.custom-table-row');
            if (parentRow) {
                parentRow.classList.add('z-index-active-row');
            }
        }
    }

    /**
     * Closes all active dropdown popups and resets their state.
     */
    function closeAllPopups() {
        document.querySelectorAll('.btn-actions').forEach(btn => btn.classList.remove('open'));
        document.querySelectorAll('.popup').forEach(popup => {
            popup.classList.remove('show');
        });
        // Remove z-index-active-row from all rows
        document.querySelectorAll('.custom-table-row.z-index-active-row').forEach(row => {
            row.classList.remove('z-index-active-row');
        });

        activeDropdownElement = null;
        activeDropdownButton = null;
    }

    // Global click listener to close popups if clicking outside
    document.addEventListener('click', function(event) {
        if (activeDropdownElement && activeDropdownButton) {
            if (!activeDropdownElement.contains(event.target) && !activeDropdownButton.contains(event.target)) {
                closeAllPopups();
            }
        }
    });

    // Escape key to close popups and modals
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllPopups();
            closeModal();
        }
    });

    /**
     * Fetches reservation data via AJAX and updates the table.
     * @param {string} queryString - The URL query string (e.g., "orderBy=id&orderDir=ASC&search=term").
     */
    async function loadReservationsTable(queryString) {
        try {
            showMessage("Caricamento prenotazioni...", false);

            // Modify fetch URL to request JSON data
            const fetchUrl = `visualizza_prenotazioni.php?ajax=true&${queryString}`;

            const response = await fetch(fetchUrl);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json(); // Expect JSON response

            if (!result.success) {
                throw new Error(result.message || 'Errore sconosciuto durante il caricamento dei dati.');
            }

            currentActiveReservations = result.prenotazioni; // Update the global reservation data

            // Clear existing table rows (but keep the header)
            const currentHeaderHtml = reservationsTableBody.querySelector('.custom-table-head').outerHTML;
            reservationsTableBody.innerHTML = currentHeaderHtml;

            if (currentActiveReservations.length === 0) {
                reservationsTableBody.innerHTML += `
                    <div class="custom-table-row" role="row" tabindex="0" style="justify-content:center; color: var(--text-color-secondary);">
                        Nessuna prenotazione trovata.
                    </div>`;
            } else {
                currentActiveReservations.forEach(prenotazione => {
                    const prod_name = escapeHtml(prenotazione.product_name || '');
                    const qty = escapeHtml(prenotazione.quantity || '');
                    const total_p = formatCurrency(prenotazione.product_total_price || 0);
                    const deposit_a = formatCurrency(prenotazione.deposit_amount || 0);
                    const remaining_a = formatCurrency(prenotazione.remaining_amount || 0);
                    const cust_name = escapeHtml(prenotazione.customer_name || '');
                    const cust_phone = escapeHtml(prenotazione.customer_phone || '');
                    const res_date = escapeHtml(prenotazione.reservation_date ? formatDate(prenotazione.reservation_date) : '');
                    const status_class = getStatusClasses(prenotazione.status || '');
                    const created_at_fmt = escapeHtml(prenotazione.created_at ? formatDateTime(prenotazione.created_at) : '');

                    const rowHtml = `
                        <div class="custom-table-row" role="row" tabindex="0" aria-rowindex="${prenotazione.id}">
                            <div role="gridcell" data-label="ID:">${prenotazione.id}</div>
                            <div role="gridcell" data-label="Prodotto:">${prod_name}</div>
                            <div role="gridcell" data-label="Quantità:">${qty}</div>
                            <div role="gridcell" data-label="Totale Prodotto:">${total_p}</div>
                            <div role="gridcell" data-label="Acconto:">${deposit_a}</div>
                            <div role="gridcell" data-label="Saldo:">${remaining_a}</div>
                            <div role="gridcell" data-label="Cliente:">${cust_name}</div>
                            <div role="gridcell" data-label="Telefono Cliente:">${cust_phone}</div>
                            <div role="gridcell" data-label="Data Prenotazione:">${res_date}</div>
                            <div role="gridcell" data-label="Stato:">
                                <span class="status ${status_class}" aria-label="Stato prenotazione: ${escapeHtml(prenotazione.status || '')}">
                                    ${ucfirst(prenotazione.status || '')}
                                </span>
                            </div>
                            <div role="gridcell" class="actions-wrapper">
                                <button class="btn-actions" aria-haspopup="true" aria-expanded="false" aria-controls="popup-${prenotazione.id}" aria-label="Apri menu azioni prenotazione ID ${prenotazione.id}">
                                    Azioni
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 10l5 5 5-5z"/></svg>
                                </button>
                                <div class="popup" id="popup-${prenotazione.id}" role="menu" aria-label="Azioni prenotazione ${prenotazione.id}">
                                    <ul>
                                        <li role="menuitem" tabindex="-1" onclick="openEditReservationModal(${prenotazione.id})">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M12.146.146a.5.5 0 01.708 0l3 3a.5.5 0 010 .708l-9.793 9.793a.5.5 0 01-.168.11l-5 2a.5.5 0 01-.65-.65l2-5a.5.5 0 01.11-.168L12.146.146zM11.207 2L4 9.207V11h1.793L14 3.793 11.207 2z"/>
                                            </svg>
                                            Modifica
                                        </li>
                                        <li role="menuitem" tabindex="-1" onclick="window.open('stampa_prenotazione.php?id=${prenotazione.id}','_blank')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M2 2h12v10H2V2zm1 1v8h10V3H3z"/>
                                                <path d="M5 12h6v1H5v-1z"/>
                                            </svg>
                                            Stampa scheda
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div role="gridcell" data-label="Data Creazione:">${created_at_fmt}</div>
                        </div>
                    `;
                    reservationsTableBody.innerHTML += rowHtml;
                });
            }

            // Update URL in browser history without reloading
            window.history.pushState({}, '', `visualizza_prenotazioni.php?${queryString}`);

            // Re-initialize event listeners for new elements
            initEventListeners();
            showMessage("Prenotazioni caricate con successo!", false);

        } catch (error) {
            console.error('Errore durante il caricamento delle prenotazioni:', error);
            showMessage(`Errore nel caricamento delle prenotazioni: ${error.message}`, true);
        }
    }


    // --- Initial page load / DOMContentLoaded ---
    document.addEventListener('DOMContentLoaded', function() {
        initEventListeners(); // Set up initial listeners

        // Handle search form submission via AJAX
        searchForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission
            const searchTerm = searchTermInput.value;
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('search', searchTerm);
            urlParams.delete('orderBy'); // Reset sorting on new search
            urlParams.delete('orderDir');
            loadReservationsTable(urlParams.toString());
        });

        // Handle reset button click via AJAX
        resetBtn.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default link behavior
            searchTermInput.value = ''; // Clear search input
            loadReservationsTable(''); // Load table with no filters/sorting
        });
    });


    // --- Modal Logic ---
    let currentModalReservationId = null;

    /**
     * Opens the main modal and displays the specified content.
     * @param {string} modalContentElementId - The ID of the modal content div to show (e.g., 'editReservationModalContent').
     * @param {number|string|null} relatedId - The ID of the reservation/item related to the modal.
     */
    function openModal(modalContentElementId, relatedId = null) {
        closeAllPopups(); // Ensure popups are closed
        
        // Hide all potential modal contents first
        editReservationModalContent.classList.add('hidden');

        // Show the specific modal content requested
        const modalToShow = document.getElementById(modalContentElementId);
        if (modalToShow) {
            modalToShow.classList.remove('hidden');
        }

        mainModal.classList.add('show'); // Show the overlay
        currentModalReservationId = relatedId; // Store the ID for the active reservation
    }

    /**
     * Closes the main modal and hides its content.
     */
    window.closeModal = function() {
        mainModal.classList.remove('show'); // Hide the overlay
        setTimeout(() => {
            editReservationModalContent.classList.add('hidden');
        }, 300); // Should match CSS transition duration
        currentModalReservationId = null; // Clear the stored ID
        // Reset autocomplete lists in the modal
        editSupplierAutocompleteList.style.display = 'none';
        productToUnloadAutocompleteList.style.display = 'none';
    };

    mainModal.addEventListener('click', (e) => {
        if (e.target === mainModal) {
            closeModal();
        }
    });

    /**
     * Populates the edit reservation modal with data and opens it.
     * @param {number} reservationId - The ID of the reservation to edit.
     */
    window.openEditReservationModal = function(reservationId) {
        closeAllPopups();

        const reservation = currentActiveReservations.find(p => parseInt(p.id) === parseInt(reservationId));

        if (reservation) {
            currentModalHistoryUpdates = []; // Reset temporary history updates

            originalReservationData = {
                product_name: reservation.product_name,
                quantity: parseFloat(reservation.quantity),
                product_total_price: parseFloat(reservation.product_total_price), // Now direct input
                deposit_amount: parseFloat(reservation.deposit_amount),
                customer_name: reservation.customer_name,
                customer_phone: reservation.customer_phone,
                reservation_date: reservation.reservation_date ? reservation.reservation_date.split(' ')[0] : '',
                fornitore_id: reservation.fornitore_id ? parseInt(reservation.fornitore_id) : '',
                data_arrivo_previsto: reservation.data_arrivo_previsto ? reservation.data_arrivo_previsto.split(' ')[0] : '',
                notestext: reservation.notestext || '',
                status: reservation.status
            };

            editReservationIdInput.value = reservation.id;
            modalReservationIdSpan.textContent = reservation.id;
            editProductNameInput.value = reservation.product_name;
            editQuantityInput.value = reservation.quantity;
            
            editProductTotalPriceInput.value = parseFloat(reservation.product_total_price).toFixed(2); // No longer readonly
            editDepositAmountInput.value = parseFloat(reservation.deposit_amount).toFixed(2);
            editRemainingAmountInput.value = (parseFloat(reservation.product_total_price) - parseFloat(reservation.deposit_amount)).toFixed(2);
            editNewDepositAmountInput.value = '0.00';

            editCustomerNameInput.value = reservation.customer_name;
            editCustomerPhoneInput.value = reservation.customer_phone;
            editReservationDateInput.value = originalReservationData.reservation_date;

            editSupplierNameInput.value = reservation.fornitore_ragione_sociale || '';
            editSupplierIdInput.value = originalReservationData.fornitore_id;
            editExpectedArrivalDateInput.value = originalReservationData.data_arrivo_previsto;
            editNotesInput.value = reservation.notestext || '';

            editStatusSelect.value = reservation.status;

            const movementsForThisReservation = initialMovimentiPrenotazione.filter(
                m => parseInt(m.prenotazione_id) === parseInt(reservationId) && m.tipo_movimento === 'scarico_prenotazione'
            );
            renderReservationMovements(movementsForThisReservation);

            if (movementsForThisReservation.length > 0) {
                stockManagementSection.classList.add('hidden');
                showStockManagementBtn.classList.remove('hidden');
            } else {
                stockManagementSection.classList.remove('hidden');
                showStockManagementBtn.classList.add('hidden');
            }

            const historyForThisReservation = initialPrenotazioneStorico.filter(
                h => parseInt(h.prenotazione_id) === parseInt(reservationId)
            ).sort((a, b) => new Date(a.data_evento).getTime() - new Date(b.data_evento).getTime());
            
            renderReservationHistory(historyForThisReservation);

            switchTab('anagrafe'); // Always start on Anagrafe tab
            openModal('editReservationModalContent', reservationId); // Open the modal
        } else {
            showMessage("Prenotazione non trovata!", true);
        }
    };

    /**
     * Recalculates remaining amount in the modal based on total product price and deposits.
     */
    function recalculateModalTotals() {
        const totalProductPrice = parseFloat(editProductTotalPriceInput.value) || 0;
        const totalPaidDeposits = parseFloat(editDepositAmountInput.value) || 0;

        const remainingAmount = totalProductPrice - totalPaidDeposits;

        editRemainingAmountInput.value = remainingAmount.toFixed(2);
    }

    // Add event listeners for recalculation (only to relevant fields)
    editProductTotalPriceInput.addEventListener('input', recalculateModalTotals);
    // editQuantityInput.addEventListener('input', recalculateModalTotals); // Not needed anymore as total product is direct input
    // editUnitPriceInput.addEventListener('input', recalculateModalTotals); // Not needed anymore as unit price is removed


    addDepositBtn.addEventListener('click', () => {
        const currentReservationId = editReservationIdInput.value;
        let currentTotalDeposits = parseFloat(editDepositAmountInput.value) || 0;
        let newDeposit = parseFloat(editNewDepositAmountInput.value) || 0;

        if (newDeposit > 0) {
            currentTotalDeposits += newDeposit;
            editDepositAmountInput.value = currentTotalDeposits.toFixed(2);
            editNewDepositAmountInput.value = '0.00';
            recalculateModalTotals();

            showMessage(`Acconto di ${newDeposit.toFixed(2)} € aggiunto. Totale acconti: ${currentTotalDeposits.toFixed(2)} €`);

            const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
            const newHistoryEntry = {
                prenotazione_id: parseInt(currentReservationId),
                data_evento: now,
                evento_descrizione: `Acconto di ${newDeposit.toFixed(2)} € aggiunto.`,
                utente: 'Sistema'
            };
            // Add to the local history array (for display in modal)
            // Note: This does not persist to DB until saveReservation is called
            initialPrenotazioneStorico.push(newHistoryEntry);
            currentModalHistoryUpdates.push(newHistoryEntry);

            const updatedHistoryForThisReservation = initialPrenotazioneStorico.filter(
                h => parseInt(h.prenotazione_id) === parseInt(currentReservationId)
            ).sort((a, b) => new Date(a.data_evento).getTime() - new Date(b.data_evento).getTime());
            renderReservationHistory(updatedHistoryForThisReservation);

        } else {
            showMessage("Inserisci un valore valido per il nuovo acconto.", true);
        }
    });


    /**
     * Handles tab switching within the modal.
     * @param {string} tabName - The name of the tab to switch to ('anagrafe', 'articoli', 'scheda').
     */
    function switchTab(tabName) {
        tabContents.forEach(content => {
            content.classList.remove('active');
            content.style.display = 'none';
        });
        tabButtons.forEach(button => {
            button.classList.remove('active');
        });

        const selectedTabContent = document.getElementById(`${tabName}TabContent`);
        const selectedTabButton = document.querySelector(`.tab-button[data-tab="${tabName}"]`);

        if (selectedTabContent && selectedTabButton) {
            selectedTabContent.classList.add('active');
            selectedTabContent.style.display = 'block';
            selectedTabButton.classList.add('active');
        }
    }

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabName = button.dataset.tab;
            switchTab(tabName);
        });
    });

    /**
     * Sets up autocomplete functionality for an input field.
     * @param {HTMLElement} inputElement - The input field.
     * @param {HTMLElement} listElement - The div to display autocomplete suggestions.
     * @param {Array<Object>} dataArray - The array of data to search through.
     * @param {Function} displayProperty - A function to get the string to display for each item.
     * @param {Function} selectCallback - A function to call when an item is selected.
     */
    function setupAutocomplete(inputElement, listElement, dataArray, displayProperty, selectCallback) {
        inputElement.addEventListener('input', () => {
            const searchTerm = inputElement.value.toLowerCase();
            listElement.innerHTML = '';
            listElement.style.display = 'none'; // Ensure hidden by default

            if (searchTerm.length < 1) { 
                return;
            }

            const filteredData = dataArray.filter(item => {
                const itemDisplayName = displayProperty(item);
                return itemDisplayName && itemDisplayName.toLowerCase().includes(searchTerm);
            });

            if (filteredData.length === 0) {
                const noResultsDiv = document.createElement('div');
                noResultsDiv.textContent = "Nessun risultato trovato.";
                noResultsDiv.className = 'p-2 text-gray-500 italic';
                listElement.appendChild(noResultsDiv);
                listElement.style.display = 'block';
                return;
            }

            filteredData.forEach(item => {
                const div = document.createElement('div');
                div.textContent = displayProperty(item);
                div.className = 'p-2 cursor-pointer hover:bg-gray-200';
                div.addEventListener('click', () => {
                    selectCallback(item);
                    listElement.style.display = 'none';
                });
                listElement.appendChild(div);
            });

            listElement.style.display = 'block'; // Show the list after populating
        });

        document.addEventListener('click', (event) => {
            if (!inputElement.contains(event.target) && !listElement.contains(event.target)) {
                listElement.style.display = 'none';
            }
        });
    }

    setupAutocomplete(
        editSupplierNameInput,
        editSupplierAutocompleteList,
        initialFornitori,
        item => item.ragione_sociale,
        (selectedSupplier) => {
            editSupplierNameInput.value = selectedSupplier.ragione_sociale;
            editSupplierIdInput.value = selectedSupplier.id;
        }
    );

    setupAutocomplete(
        searchProductToUnloadInput,
        productToUnloadAutocompleteList,
        initialProdottiCatalogo,
        item => item.nome,
        (selectedProduct) => {
            searchProductToUnloadInput.value = selectedProduct.nome;
            selectedProductIdToUnloadInput.value = selectedProduct.id;
            productCurrentStockInput.value = selectedProduct.quantita !== null && selectedProduct.quantita !== undefined ? selectedProduct.quantita : '0';
            showMessage(`Prodotto selezionato: ${selectedProduct.nome}. Giacenza attuale: ${productCurrentStockInput.value}.`);
        }
    );

    /**
     * Renders the movements related to the current reservation in the 'Articoli' tab.
     * @param {Array<Object>} movements - Array of movement objects.
     */
    function renderReservationMovements(movements) {
        reservationMovementsList.innerHTML = '';
        const aggregatedMovements = {};

        if (movements.length === 0) {
            noMovementsMessage.style.display = 'block';
        } else {
            noMovementsMessage.style.display = 'none';

            movements.forEach(movement => {
                const key = movement.prodotto_id;
                if (aggregatedMovements[key]) {
                    aggregatedMovements[key].quantita_movimentata += parseInt(movement.quantita_movimentata);
                } else {
                    aggregatedMovements[key] = { ...movement, quantita_movimentata: parseInt(movement.quantita_movimentata) };
                }
            });

            for (const productId in aggregatedMovements) {
                const movement = aggregatedMovements[productId];
                const movementElement = document.createElement('div');
                movementElement.className = 'bg-white p-2 border border-gray-100 rounded-md shadow-sm text-sm flex justify-between items-center';
                movementElement.innerHTML = `
                    <p class="text-gray-700 font-medium">${movement.product_name}: <span class="text-red-600">${movement.quantita_movimentata}</span> pz scaricati</p>
                `;
                reservationMovementsList.appendChild(movementElement);
            }
        }
    }

    /**
     * Renders the history entries for the current reservation in the 'Scheda' tab.
     * @param {Array<Object>} historyEntries - Array of history entry objects.
     */
    function renderReservationHistory(historyEntries) {
        reservationHistoryList.innerHTML = '';

        if (historyEntries.length === 0) {
            noHistoryEntriesMessage.style.display = 'block';
        } else {
            noHistoryEntriesMessage.style.display = 'none';
            historyEntries.forEach(entry => {
                const historyElement = document.createElement('div');
                historyElement.className = 'history-entry';

                const date = new Date(entry.data_evento);
                // Format date and time without seconds for cleaner view
                const formattedDate = date.toLocaleDateString('it-IT', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                historyElement.innerHTML = `
                    <span class="timestamp">${formattedDate}</span>
                    <span class="description">${entry.evento_descrizione}</span>
                `;
                reservationHistoryList.appendChild(historyElement);
            });
        }
    }

    // --- Unload From Stock Button Logic ---
    unloadFromStockBtn.addEventListener('click', async () => {
        const productId = selectedProductIdToUnloadInput.value;
        const quantityToUnload = parseInt(quantityToUnloadInput.value) || 0;
        const productName = searchProductToUnloadInput.value.trim();
        let currentStock = parseInt(productCurrentStockInput.value);
        const reservationId = editReservationIdInput.value;

        if (isNaN(currentStock)) {
            currentStock = 0;
        }

        if (!productId || !productName) {
            showMessage("Seleziona un prodotto dal catalogo per scaricarlo.", true);
            return;
        }
        if (quantityToUnload <= 0) {
            showMessage("Inserisci una quantità valida da scaricare (maggiore di zero).", true);
            return;
        }
        if (!reservationId) {
            showMessage("ID Prenotazione non disponibile. Riprova ad aprire la prenotazione.", true);
            return;
        }

        if (quantityToUnload > currentStock) {
            showMessage(`Impossibile scaricare ${quantityToUnload} unità di ${productName}. Giacenza insufficiente. Disponibili: ${currentStock}.`, true);
            return;
        }

        const formData = new FormData();
        formData.append('productId', productId);
        formData.append('quantity', quantityToUnload);
        formData.append('action', 'decrement_stock');
        formData.append('reservationId', reservationId);

        showMessage("Scaricamento magazzino in corso...", false);

        try {
            const response = await fetch('update_stock.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
            }

            const result = await response.json();

            if (result.success) {
                productCurrentStockInput.value = result.newStock;

                const productInCatalog = initialProdottiCatalogo.find(p => parseInt(p.id) === parseInt(productId));
                if (productInCatalog) {
                    productInCatalog.quantita = result.newStock;
                }

                const newMovement = {
                    prenotazione_id: parseInt(reservationId),
                    prodotto_id: parseInt(productId),
                    quantita_movimentata: quantityToUnload,
                    tipo_movimento: 'scarico_prenotazione',
                    data_movimento: new Date().toISOString(),
                    product_name: productName
                };
                initialMovimentiPrenotazione.push(newMovement);
                const movementsForThisReservation = initialMovimentiPrenotazione.filter(
                    m => parseInt(m.prenotazione_id) === parseInt(reservationId) && m.tipo_movimento === 'scarico_prenotazione'
                );
                renderReservationMovements(movementsForThisReservation);

                showMessage(`Scaricato ${quantityToUnload} unità di ${productName}. Nuova giacenza: ${result.newStock}.`, false);
                searchProductToUnloadInput.value = '';
                selectedProductIdToUnloadInput.value = '';
                quantityToUnloadInput.value = '1';
                productCurrentStockInput.value = 'N/D';

                stockManagementSection.classList.add('hidden');
                showStockManagementBtn.classList.remove('hidden');

                const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
                const newHistoryEntry = {
                    prenotazione_id: parseInt(reservationId),
                    data_evento: now,
                    evento_descrizione: `Articolo "${productName}" scaricato dal magazzino: ${quantityToUnload} pz. Nuova giacenza: ${result.newStock}.`,
                    utente: 'Sistema'
                };
                initialPrenotazioneStorico.push(newHistoryEntry);
                currentModalHistoryUpdates.push(newHistoryEntry);

                const updatedHistoryForThisReservation = initialPrenotazioneStorico.filter(
                    h => parseInt(h.prenotazione_id) === parseInt(reservationId)
                ).sort((a, b) => new Date(a.data_evento).getTime() - new Date(b.data_evento).getTime());
                renderReservationHistory(updatedHistoryForThisReservation);

            } else {
                showMessage(`Errore nello scarico del prodotto: ${result.message}`, true);
            }
        } catch (error) {
            console.error('Errore AJAX scarico magazzino:', error);
            showMessage(`Errore di rete o server durante lo scarico magazzino: ${error.message}`, true);
        }
    });

    showStockManagementBtn.addEventListener('click', () => {
        stockManagementSection.classList.remove('hidden');
        showStockManagementBtn.classList.add('hidden');
    });

    window.saveReservation = async function() {
        console.log("Save button clicked!");

        const currentReservationId = editReservationIdInput.value;
        const updatedReservation = {
            id: currentReservationId,
            product_name: editProductNameInput.value,
            quantity: parseFloat(editQuantityInput.value),
            product_total_price: parseFloat(editProductTotalPriceInput.value), // Now from direct input
            deposit_amount: parseFloat(editDepositAmountInput.value),
            remaining_amount: parseFloat(editRemainingAmountInput.value),
            customer_name: editCustomerNameInput.value,
            customer_phone: editCustomerPhoneInput.value,
            reservation_date: editReservationDateInput.value,
            fornitore_id: editSupplierIdInput.value,
            data_arrivo_previsto: editExpectedArrivalDateInput.value,
            notestext: editNotesInput.value,
            status: editStatusSelect.value
        };

        if (!updatedReservation.product_name.trim() || isNaN(updatedReservation.quantity) || updatedReservation.quantity <= 0 ||
            isNaN(updatedReservation.product_total_price) || updatedReservation.product_total_price <= 0 || // Validate new field
            !updatedReservation.customer_name.trim() || !updatedReservation.reservation_date) {
            showMessage("Per favor, compila tutti i campi obbligatori (Prodotto, Quantità, Totale Prodotto, Nome Cliente, Data Prenotazione) e assicurati che i valori siano validi.", true);
            return;
        }

        const now = new Date().toISOString().slice(0, 19).replace('T', ' ');

        if (updatedReservation.product_name !== originalReservationData.product_name) {
            currentModalHistoryUpdates.push({
                prenotazione_id: parseInt(currentReservationId),
                data_evento: now,
                evento_descrizione: `Nome prodotto aggiornato da "${originalReservationData.product_name}" a "${updatedReservation.product_name}".`,
                utente: 'Sistema'
            });
        }
        if (updatedReservation.quantity !== originalReservationData.quantity) {
            currentModalHistoryUpdates.push({
                prenotazione_id: parseInt(currentReservationId),
                data_evento: now,
                evento_descrizione: `Quantità aggiornata da ${originalReservationData.quantity} a ${updatedReservation.quantity}.`,
                utente: 'Sistema'
            });
        }
        // Removed check for unit_price history
        if (updatedReservation.product_total_price !== originalReservationData.product_total_price) {
             currentModalHistoryUpdates.push({
                prenotazione_id: parseInt(currentReservationId),
                data_evento: now,
                evento_descrizione: `Totale Prodotto aggiornato da ${originalReservationData.product_total_price.toFixed(2)}€ a ${updatedReservation.product_total_price.toFixed(2)}€.`,
                utente: 'Sistema'
            });
        }
        if (updatedReservation.customer_name !== originalReservationData.customer_name) {
            currentModalHistoryUpdates.push({
                prenotazione_id: parseInt(currentReservationId),
                data_evento: now,
                evento_descrizione: `Nome cliente aggiornato da "${originalReservationData.customer_name}" a "${updatedReservation.customer_name}".`,
                utente: 'Sistema'
            });
        }
        if (updatedReservation.customer_phone !== originalReservationData.customer_phone) {
            currentModalHistoryUpdates.push({
                prenotazione_id: parseInt(currentReservationId),
                data_evento: now,
                evento_descrizione: `Telefono cliente aggiornato da "${originalReservationData.customer_phone}" a "${updatedReservation.customer_phone}".`,
                utente: 'Sistema'
            });
        }
        if (updatedReservation.reservation_date !== originalReservationData.reservation_date) {
            currentModalHistoryUpdates.push({
                prenotazione_id: parseInt(currentReservationId),
                data_evento: now,
                evento_descrizione: `Data prenotazione aggiornata da ${originalReservationData.reservation_date || 'N/D'} a ${updatedReservation.reservation_date}.`,
                utente: 'Sistema'
            });
        }
        if (parseInt(updatedReservation.fornitore_id) !== parseInt(originalReservationData.fornitore_id)) {
            const oldSupplier = initialFornitori.find(f => parseInt(f.id) === parseInt(originalReservationData.fornitore_id))?.ragione_sociale || 'N/D';
            const newSupplier = initialFornitori.find(f => parseInt(f.id) === parseInt(updatedReservation.fornitore_id))?.ragione_sociale || 'N/D';
            currentModalHistoryUpdates.push({
                prenotazione_id: parseInt(currentReservationId),
                data_evento: now,
                evento_descrizione: `Fornitore aggiornato da "${oldSupplier}" a "${newSupplier}".`,
                utente: 'Sistema'
            });
        }
        if (updatedReservation.data_arrivo_previsto !== originalReservationData.data_arrivo_previsto) {
            currentModalHistoryUpdates.push({
                prenotazione_id: parseInt(currentReservationId),
                data_evento: now,
                evento_descrizione: `Data arrivo previsto aggiornata da ${originalReservationData.data_arrivo_previsto || 'N/D'} a ${updatedReservation.data_arrivo_previsto}.`,
                utente: 'Sistema'
            });
        }
        if (updatedReservation.notestext !== originalReservationData.notestext) {
            currentModalHistoryUpdates.push({
                prenotazione_id: parseInt(currentReservationId),
                data_evento: now,
                evento_descrizione: `Note aggiornate.`,
                utente: 'Sistema'
            });
        }
        if (updatedReservation.status !== originalReservationData.status) {
            currentModalHistoryUpdates.push({
                prenotazione_id: parseInt(currentReservationId),
                data_evento: now,
                evento_descrizione: `Stato prenotazione cambiato da "${originalReservationData.status}" a "${updatedReservation.status}".`,
                utente: 'Sistema'
            });
        }

        showMessage("Salvataggio modifiche in corso...", false);

        try {
            const formData = new FormData();
            for (const key in updatedReservation) {
                formData.append(key, updatedReservation[key]);
            }
            formData.append('history_updates', JSON.stringify(currentModalHistoryUpdates));

            console.log("Sending form data to update_reservation.php:", [...formData.entries()]);

            const response = await fetch('update_reservation.php', {
                method: 'POST',
                body: formData
            });

            console.log("Fetch response:", response);

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
            }

            const result = await response.json();

            console.log("Result from backend (update_reservation.php):", result);

            if (result.success) {
                showMessage(`Prenotazione ID ${currentReservationId} salvata con successo!`, false);
                // Instead of full reload, refresh table via AJAX
                closeModal();
                const urlParams = new URLSearchParams(window.location.search);
                loadReservationsTable(urlParams.toString()); // Reload current view
            } else {
                showMessage(`Errore nel salvataggio: ${result.message}`, true);
            }
        } catch (error) {
            console.error('Errore AJAX salvataggio prenotazione:', error);
            showMessage(`Errore di rete o server durante lo salvataggio: ${error.message}`, true);
        }
    };
</script>
</body>
</html>
