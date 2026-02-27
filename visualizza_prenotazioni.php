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
        /* ========== MODERN DESIGN SYSTEM ========== */
        :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --primary-light: #dcfce7;
            --secondary: #3b82f6;
            --danger: #ef4444;
            --warning: #f59e0b;
            --purple: #8b5cf6;
            --bg-page: #f0fdf4;
            --bg-card: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px -5px rgba(0,0,0,0.1);
            --shadow-xl: 0 25px 50px -12px rgba(0,0,0,0.15);
            --transition-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
            
            --status-pending: #f59e0b;
            --status-completed: #22c55e;
            --status-cancelled: #ef4444;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #d1fae5 100%);
            background-attachment: fixed;
            color: var(--text-primary);
            padding-top: 90px;
            min-height: 100vh;
        }

        /* ========== FLOATING PARTICLES ========== */
        .particles-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        .particle {
            position: absolute;
            width: 8px;
            height: 8px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            opacity: 0.15;
            animation: floatParticle 20s infinite ease-in-out;
        }
        .particle:nth-child(1) { left: 10%; top: 20%; animation-delay: 0s; }
        .particle:nth-child(2) { left: 80%; top: 40%; animation-delay: -5s; width: 12px; height: 12px; }
        .particle:nth-child(3) { left: 30%; top: 70%; animation-delay: -10s; width: 6px; height: 6px; }
        .particle:nth-child(4) { left: 70%; top: 80%; animation-delay: -15s; }
        
        @keyframes floatParticle {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(30px, -30px) rotate(90deg); }
            50% { transform: translate(-20px, 20px) rotate(180deg); }
            75% { transform: translate(20px, 10px) rotate(270deg); }
        }

        /* ========== TOAST NOTIFICATIONS ========== */
        .toast-container {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            transform: translateX(120%);
            animation: slideInToast 0.4s var(--transition-spring) forwards;
            border-left: 4px solid var(--primary);
        }
        .toast.error { border-left-color: var(--danger); }
        .toast.warning { border-left-color: var(--warning); }
        .toast-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .toast-icon.success { background: var(--primary-light); color: var(--primary); }
        .toast-icon.error { background: #fee2e2; color: var(--danger); }
        .toast-message { flex: 1; font-size: 0.9rem; color: var(--text-primary); }
        @keyframes slideInToast {
            to { transform: translateX(0); }
        }

        /* ========== MAIN CONTAINER ========== */
        .main-content-container {
            max-width: 1500px;
            margin: 2rem auto;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        /* ========== PAGE HEADER ========== */
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.5px;
        }
        .page-header p {
            color: var(--text-secondary);
            font-size: 1rem;
            margin: 0;
        }

        /* ========== SUMMARY CARDS ========== */
        .summary-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 1.25rem;
            box-shadow: var(--shadow-md);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s var(--transition-spring);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        .summary-card:hover::before { opacity: 1; }
        .summary-card.active {
            border-color: var(--primary);
            background: var(--primary-light);
        }
        .summary-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
        }
        .summary-icon svg { width: 26px; height: 26px; }
        .summary-card--total .summary-icon { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }
        .summary-card--pending .summary-icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
        .summary-card--completed .summary-icon { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #16a34a; }
        .summary-card--cancelled .summary-icon { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #dc2626; }
        .summary-card--value .summary-icon { background: linear-gradient(135deg, #f3e8ff, #e9d5ff); color: #9333ea; }
        .summary-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        .summary-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        /* ========== FILTER BAR ========== */
        .filter-bar {
            background: white;
            border-radius: 16px;
            padding: 1.25rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }
        .filter-search {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        .filter-search input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: #f8fafc;
        }
        .filter-search input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
        }
        .filter-search svg {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: var(--text-secondary);
        }
        .filter-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-search {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }
        .btn-reset {
            padding: 0.75rem 1.5rem;
            background: #f1f5f9;
            color: var(--text-secondary);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-reset:hover {
            background: #e2e8f0;
        }

        /* ========== PRENOTAZIONI GRID ========== */
        .prenotazioni-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.25rem;
        }
        
        .prenotazione-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all 0.3s var(--transition-spring);
            animation: cardFadeIn 0.5s ease forwards;
            opacity: 0;
            border: 1px solid var(--border-color);
        }
        .prenotazione-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        @keyframes cardFadeIn {
            to { opacity: 1; }
        }
        
        .prenotazione-card-header {
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .prenotazione-id {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .prenotazione-id-badge {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .prenotazione-date {
            font-size: 0.8rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .prenotazione-date svg { width: 14px; height: 14px; }
        
        .prenotazione-status {
            padding: 0.35rem 0.875rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .prenotazione-status.pending { background: #fef3c7; color: #92400e; }
        .prenotazione-status.completed { background: #dcfce7; color: #166534; }
        .prenotazione-status.cancelled { background: #fee2e2; color: #991b1b; }
        
        .prenotazione-card-body {
            padding: 1.25rem;
        }
        
        .prenotazione-product {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px dashed var(--border-color);
        }
        .product-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-light), #bbf7d0);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .product-icon svg { width: 24px; height: 24px; color: var(--primary-dark); }
        .product-info { flex: 1; }
        .product-name {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            line-height: 1.3;
        }
        .product-qty {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .prenotazione-client {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .client-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            color: #2563eb;
        }
        .client-info { flex: 1; }
        .client-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-primary);
        }
        .client-phone {
            font-size: 0.8rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .client-phone svg { width: 12px; height: 12px; }
        
        .prenotazione-amounts {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .amount-box {
            text-align: center;
            padding: 0.75rem;
            border-radius: 10px;
            background: #f8fafc;
        }
        .amount-box.total { background: linear-gradient(135deg, #dbeafe, #bfdbfe); }
        .amount-box.deposit { background: linear-gradient(135deg, #dcfce7, #bbf7d0); }
        .amount-box.remaining { background: linear-gradient(135deg, #fef3c7, #fde68a); }
        .amount-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 0.25rem;
        }
        .amount-value {
            font-size: 1rem;
            font-weight: 700;
        }
        .amount-box.total .amount-value { color: #2563eb; }
        .amount-box.deposit .amount-value { color: #16a34a; }
        .amount-box.remaining .amount-value { color: #d97706; }
        
        .prenotazione-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        .prenotazione-created {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        .prenotazione-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: none;
            background: #f1f5f9;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .btn-action:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }
        .btn-action svg { width: 18px; height: 18px; }
        
        /* Card Actions Dropdown */
        .card-actions-wrapper {
            position: relative;
        }
        .card-popup {
            position: absolute;
            bottom: 100%;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-color);
            min-width: 160px;
            display: none;
            z-index: 100;
            overflow: hidden;
            margin-bottom: 8px;
        }
        .card-popup.show { display: block; }
        .card-popup-item {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .card-popup-item:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
        }
        .card-popup-item.danger:hover {
            background: #fee2e2;
            color: #dc2626;
        }
        .card-popup-item svg { width: 16px; height: 16px; }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
        }
        .empty-icon {
            width: 80px;
            height: 80px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .empty-icon svg { width: 40px; height: 40px; color: var(--primary); }
        .empty-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        .empty-text {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* ========== MESSAGE BOX (Legacy) ========== */
        .message-container { display: none; }
        .message-box {
            position: fixed;
            top: 100px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--primary);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            z-index: 9999;
            font-weight: 600;
            display: none;
        }
        .message-box.error { background-color: var(--danger); }
        .message-box.show { display: block; }

        /* ========== MODALS ========== */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        .hidden {
            display: none !important;
        }
        .modal-overlay.hidden {
            display: flex !important;
            opacity: 0;
            visibility: hidden;
        }
        .modal-overlay.show.hidden {
            display: flex !important;
        }
        .modal-content.hidden {
            display: none !important;
        }
        .modal-content {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
            max-width: 90%;
            width: 900px;
            max-height: 90vh;
            height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transform: translateY(20px) scale(0.95);
            transition: transform 0.3s var(--transition-spring);
        }
        #editReservationForm {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        .modal-overlay.show .modal-content {
            transform: translateY(0) scale(1);
        }
        .modal-header {
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
        }
        .modal-close-button {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .modal-close-button:hover {
            background: rgba(255,255,255,0.3);
        }
        .modal-body {
            padding: 1.5rem 2rem;
            overflow-y: auto;
            flex: 1 1 auto;
            min-height: 0;
            max-height: 100%;
        }
        .modal-footer {
            padding: 1rem 2rem;
            background: #f8fafc;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        
        /* Tab Buttons */
        .tab-buttons {
            display: flex;
            gap: 0.5rem;
            padding: 0 2rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
        }
        .tab-button {
            padding: 1rem 1.5rem;
            border: none;
            background: transparent;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            position: relative;
            transition: all 0.2s ease;
        }
        .tab-button::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary);
            transform: scaleX(0);
            transition: transform 0.2s ease;
        }
        .tab-button:hover { color: var(--primary); }
        .tab-button.active {
            color: var(--primary);
        }
        .tab-button.active::after {
            transform: scaleX(1);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }

        /* Modal Form */
        .modal-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .modal-form-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .modal-form-group.col-span-2 {
            grid-column: span 2;
        }
        .modal-form-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .modal-form-group input,
        .modal-form-group select,
        .modal-form-group textarea {
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: #f8fafc;
        }
        .modal-form-group input:focus,
        .modal-form-group select:focus,
        .modal-form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
        }
        .modal-form-group input[readonly] {
            background: #e2e8f0;
            color: var(--text-secondary);
        }

        .modal-footer button {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }
        .btn-cancel {
            background: #f1f5f9;
            color: var(--text-secondary);
            border: 2px solid var(--border-color) !important;
        }
        .btn-cancel:hover { background: #e2e8f0; }
        .btn-save {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4);
        }

        /* Autocomplete */
        .autocomplete-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            box-shadow: var(--shadow-lg);
            display: none;
        }
        .autocomplete-list.show { display: block; }
        .autocomplete-list div {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .autocomplete-list div:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        /* Status badges in modal */
        .status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.35rem 0.875rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .status.status-pending, .status-pending { background: #fef3c7; color: #92400e; }
        .status.status-completed, .status-completed { background: #dcfce7; color: #166534; }
        .status.status-cancelled, .status-cancelled { background: #fee2e2; color: #991b1b; }

        /* ========== MODAL EDIT ENHANCEMENTS ========== */
        .deposit-section {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border-radius: 12px;
            padding: 1.25rem;
            margin-top: 1.5rem;
            border: 1px solid #bbf7d0;
        }
        .deposit-section-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .deposit-section-title::before {
            content: '💰';
        }
        
        .add-deposit-group .add-deposit-wrapper {
            display: flex;
            gap: 0.5rem;
        }
        .add-deposit-group .add-deposit-wrapper input {
            flex: 1;
        }
        .btn-add-deposit {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .btn-add-deposit:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
        }
        
        .readonly-highlight {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0) !important;
            border-color: #86efac !important;
            font-weight: 600 !important;
            color: #166534 !important;
        }
        .saldo-highlight {
            background: linear-gradient(135deg, #fef3c7, #fde68a) !important;
            border-color: #fcd34d !important;
            font-weight: 700 !important;
            color: #92400e !important;
            font-size: 1.1rem !important;
        }
        
        /* Stock Management in Articoli Tab */
        .stock-management-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            padding: 1.25rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }
        .btn-unload {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }
        .btn-unload:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        .btn-show-stock {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            margin-bottom: 1rem;
        }
        .btn-show-stock:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
        }
        .btn-show-stock.hidden {
            display: none;
        }
        
        .movements-section h5 {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
        }
        .movements-list {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            max-height: 200px;
            overflow-y: auto;
        }
        .empty-movements, .empty-history {
            color: var(--text-secondary);
            font-style: italic;
            font-size: 0.9rem;
            text-align: center;
            padding: 1rem;
        }
        
        .tab-section-header {
            margin-bottom: 1.5rem;
        }
        .tab-section-header h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 0.25rem 0;
        }
        .tab-section-header p {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin: 0;
        }
        
        .history-list {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            max-height: 300px;
            overflow-y: auto;
        }
        .history-entry {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border: 1px solid var(--border-color);
        }
        .history-entry .timestamp {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--primary-dark);
            white-space: nowrap;
        }
        .history-entry .description {
            flex: 1;
            font-size: 0.875rem;
            color: var(--text-primary);
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1200px) {
            .prenotazioni-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 900px) {
            .main-content-container {
                padding: 1rem;
                margin: 1rem;
            }
            .summary-panel {
                grid-template-columns: repeat(2, 1fr);
            }
            .prenotazioni-grid {
                grid-template-columns: 1fr;
            }
            .filter-bar {
                flex-direction: column;
            }
            .filter-search {
                width: 100%;
            }
            .filter-actions {
                width: 100%;
            }
            .filter-actions button {
                flex: 1;
            }
            .modal-grid {
                grid-template-columns: 1fr;
            }
            .modal-form-group.col-span-2 {
                grid-column: span 1;
            }
            .stock-management-grid {
                grid-template-columns: 1fr;
            }
            .modal-content {
                width: 95%;
                max-height: 95vh;
            }
        }
        @media (max-width: 600px) {
            .page-header h1 { font-size: 1.75rem; }
            .summary-panel { grid-template-columns: 1fr 1fr; gap: 0.75rem; }
            .summary-card { padding: 1rem; }
            .summary-value { font-size: 1.5rem; }
            .prenotazione-amounts { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; // Includi la barra di navigazione ?>

    <!-- Floating Particles -->
    <div class="particles-container">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <div class="main-content-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📦 Prenotazioni Prodotti</h1>
            <p>Gestisci tutte le prenotazioni e i depositi dei clienti</p>
        </div>

        <?php echo $message; // Mostra messaggi di sistema (se presenti da PHP) ?>

        <?php
        // Calcola statistiche
        $totalPrenotazioni = count($prenotazioni);
        $pending = 0;
        $completed = 0;
        $cancelled = 0;
        $valoreTotale = 0;
        foreach ($prenotazioni as $p) {
            switch ($p['status'] ?? '') {
                case 'In Attesa': $pending++; break;
                case 'Completata': $completed++; break;
                case 'Annullata': $cancelled++; break;
            }
            $valoreTotale += floatval($p['product_total_price'] ?? 0);
        }
        ?>

        <!-- Summary Panel -->
        <div class="summary-panel">
            <div class="summary-card summary-card--total" onclick="filterByStatus('all')">
                <div class="summary-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div class="summary-label">Totale</div>
                <div class="summary-value"><?= $totalPrenotazioni ?></div>
            </div>
            <div class="summary-card summary-card--pending" onclick="filterByStatus('In Attesa')">
                <div class="summary-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                    </svg>
                </div>
                <div class="summary-label">In Attesa</div>
                <div class="summary-value"><?= $pending ?></div>
            </div>
            <div class="summary-card summary-card--completed" onclick="filterByStatus('Completata')">
                <div class="summary-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/>
                    </svg>
                </div>
                <div class="summary-label">Completate</div>
                <div class="summary-value"><?= $completed ?></div>
            </div>
            <div class="summary-card summary-card--cancelled" onclick="filterByStatus('Annullata')">
                <div class="summary-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
                <div class="summary-label">Annullate</div>
                <div class="summary-value"><?= $cancelled ?></div>
            </div>
            <div class="summary-card summary-card--value">
                <div class="summary-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="summary-label">Valore Totale</div>
                <div class="summary-value"><?= number_format($valoreTotale, 0, ',', '.') ?>€</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form id="searchForm" method="GET" action="visualizza_prenotazioni.php" class="filter-bar">
            <div class="filter-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Cerca per prodotto, cliente, telefono...">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-search">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                    Cerca
                </button>
                <a href="visualizza_prenotazioni.php" class="btn-reset" id="resetBtn">Reset</a>
            </div>
        </form>

        <!-- Prenotazioni Grid -->
        <?php if (empty($prenotazioni)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div class="empty-title">Nessuna prenotazione trovata</div>
                <div class="empty-text">Prova a modificare i filtri di ricerca o crea una nuova prenotazione</div>
            </div>
        <?php else: ?>
            <div class="prenotazioni-grid" id="reservationsGrid">
                <?php 
                $cardIndex = 0;
                foreach ($prenotazioni as $prenotazione): 
                    $prod_name = htmlspecialchars($prenotazione['product_name'] ?? '');
                    $qty = htmlspecialchars($prenotazione['quantity'] ?? '');
                    $total_p = number_format($prenotazione['product_total_price'] ?? 0, 2, ',', '.');
                    $deposit_a = number_format($prenotazione['deposit_amount'] ?? 0, 2, ',', '.');
                    $remaining_a = number_format($prenotazione['remaining_amount'] ?? 0, 2, ',', '.');
                    $cust_name = htmlspecialchars($prenotazione['customer_name'] ?? '');
                    $cust_phone = htmlspecialchars($prenotazione['customer_phone'] ?? '');
                    $res_date = isset($prenotazione['reservation_date']) ? date('d/m/Y', strtotime($prenotazione['reservation_date'])) : '';
                    $created_at_fmt = isset($prenotazione['created_at']) ? date('d/m/Y H:i', strtotime($prenotazione['created_at'])) : '';
                    
                    // Status class
                    $status_class = '';
                    $status_text = '';
                    switch ($prenotazione['status'] ?? '') {
                        case 'In Attesa':
                            $status_class = 'pending';
                            $status_text = 'In Attesa';
                            break;
                        case 'Completata':
                            $status_class = 'completed';
                            $status_text = 'Completata';
                            break;
                        case 'Annullata':
                            $status_class = 'cancelled';
                            $status_text = 'Annullata';
                            break;
                        default:
                            $status_class = 'pending';
                            $status_text = ucfirst($prenotazione['status'] ?? 'N/D');
                            break;
                    }
                    
                    // Client initials
                    $nameParts = explode(' ', $cust_name);
                    $initials = '';
                    foreach ($nameParts as $part) {
                        $initials .= strtoupper(substr($part, 0, 1));
                    }
                    $initials = substr($initials, 0, 2);
                ?>
                <div class="prenotazione-card" style="animation-delay: <?= $cardIndex * 0.05 ?>s" data-status="<?= htmlspecialchars($prenotazione['status'] ?? '') ?>">
                    <div class="prenotazione-card-header">
                        <div class="prenotazione-id">
                            <span class="prenotazione-id-badge">#<?= $prenotazione['id'] ?></span>
                            <span class="prenotazione-date">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                <?= $res_date ?>
                            </span>
                        </div>
                        <span class="prenotazione-status <?= $status_class ?>"><?= $status_text ?></span>
                    </div>
                    <div class="prenotazione-card-body">
                        <div class="prenotazione-product">
                            <div class="product-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div class="product-info">
                                <div class="product-name"><?= $prod_name ?: 'Prodotto non specificato' ?></div>
                                <div class="product-qty">Quantità: <?= $qty ?: '1' ?></div>
                            </div>
                        </div>
                        
                        <div class="prenotazione-client">
                            <div class="client-avatar"><?= $initials ?: '?' ?></div>
                            <div class="client-info">
                                <div class="client-name"><?= $cust_name ?: 'Cliente non specificato' ?></div>
                                <div class="client-phone">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                    </svg>
                                    <?= $cust_phone ?: 'N/D' ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="prenotazione-amounts">
                            <div class="amount-box total">
                                <div class="amount-label">Totale</div>
                                <div class="amount-value"><?= $total_p ?> €</div>
                            </div>
                            <div class="amount-box deposit">
                                <div class="amount-label">Acconto</div>
                                <div class="amount-value"><?= $deposit_a ?> €</div>
                            </div>
                            <div class="amount-box remaining">
                                <div class="amount-label">Saldo</div>
                                <div class="amount-value"><?= $remaining_a ?> €</div>
                            </div>
                        </div>
                        
                        <div class="prenotazione-footer">
                            <span class="prenotazione-created">Creata: <?= $created_at_fmt ?></span>
                            <div class="prenotazione-actions">
                                <button type="button" class="btn-action" onclick="openEditReservationModal(<?= $prenotazione['id'] ?>)" title="Modifica">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button type="button" class="btn-action" onclick="window.open('stampa_prenotazione.php?id=<?= $prenotazione['id'] ?>','_blank')" title="Stampa">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
                                    </svg>
                                </button>
                                <div class="card-actions-wrapper">
                                    <button type="button" class="btn-action" onclick="toggleCardPopup(this, <?= $prenotazione['id'] ?>)" title="Altre azioni">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>
                                        </svg>
                                    </button>
                                    <div class="card-popup" id="cardPopup-<?= $prenotazione['id'] ?>">
                                        <div class="card-popup-item" onclick="openEditReservationModal(<?= $prenotazione['id'] ?>)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                            Modifica
                                        </div>
                                        <div class="card-popup-item" onclick="window.open('stampa_prenotazione.php?id=<?= $prenotazione['id'] ?>','_blank')">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
                                            </svg>
                                            Stampa scheda
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php $cardIndex++; endforeach; ?>
            </div>
        <?php endif; ?>
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
                <button class="modal-close-button" onclick="closeModal()">×</button>
            </div>
            <div class="tab-buttons">
                <button type="button" class="tab-button active" data-tab="anagrafe">Anagrafe</button>
                <button type="button" class="tab-button" data-tab="articoli">Articoli</button>
                <button type="button" class="tab-button" data-tab="scheda">Scheda</button>
            </div>

            <form id="editReservationForm">
                <input type="hidden" id="editReservationId" name="id">
                
                <div class="modal-body">
                    <!-- Tab: Anagrafe -->
                    <div id="anagrafeTabContent" class="tab-content active">
                        <div class="modal-grid">
                            <div class="modal-form-group">
                                <label for="editReservationDisplayId">ID Prenotazione</label>
                                <input type="text" id="editReservationDisplayId" readonly>
                            </div>
                            <div class="modal-form-group">
                                <label for="editProductName">Nome Prodotto</label>
                                <input type="text" id="editProductName" name="product_name" required>
                            </div>
                            <div class="modal-form-group">
                                <label for="editQuantity">Quantità</label>
                                <input type="number" step="1" id="editQuantity" name="quantity" required>
                            </div>
                            <div class="modal-form-group">
                                <label for="editProductTotalPrice">Totale Prodotto (€)</label>
                                <input type="number" step="0.01" id="editProductTotalPrice" name="product_total_price">
                            </div>
                        </div>

                        <!-- Sezione Acconti -->
                        <div class="deposit-section">
                            <div class="deposit-section-title">Gestione Pagamenti</div>
                            <div class="modal-grid">
                                <div class="modal-form-group">
                                    <label for="editDepositAmount">Totale Acconti Pagati (€)</label>
                                    <input type="number" step="0.01" id="editDepositAmount" name="deposit_amount" readonly class="readonly-highlight">
                                </div>
                                <div class="modal-form-group add-deposit-group">
                                    <label for="editNewDepositAmount">Aggiungi Nuovo Acconto (€)</label>
                                    <div class="add-deposit-wrapper">
                                        <input type="number" step="0.01" id="editNewDepositAmount" value="0.00">
                                        <button type="button" id="addDepositBtn" class="btn-add-deposit">
                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="modal-form-group col-span-2">
                                    <label for="editRemainingAmount">Saldo da Dare (€)</label>
                                    <input type="number" step="0.01" id="editRemainingAmount" name="remaining_amount" readonly class="saldo-highlight">
                                </div>
                            </div>
                        </div>

                        <!-- Cliente e Dettagli -->
                        <div class="modal-grid" style="margin-top: 1.5rem;">
                            <div class="modal-form-group">
                                <label for="editCustomerName">Nome Cliente</label>
                                <input type="text" id="editCustomerName" name="customer_name" required>
                            </div>
                            <div class="modal-form-group">
                                <label for="editCustomerPhone">Telefono Cliente</label>
                                <input type="text" id="editCustomerPhone" name="customer_phone">
                            </div>
                            <div class="modal-form-group col-span-2" style="position: relative;">
                                <label for="editSupplierName">Fornitore</label>
                                <input type="text" id="editSupplierName" placeholder="Cerca o seleziona fornitore">
                                <input type="hidden" id="editSupplierId" name="fornitore_id">
                                <div id="editSupplierAutocompleteList" class="autocomplete-list"></div>
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
                        <div class="tab-section-header">
                            <h4>Gestione Articoli Magazzino</h4>
                            <p>Scarica prodotti dal magazzino per questa prenotazione</p>
                        </div>

                        <button type="button" id="showStockManagementBtn" class="btn-show-stock hidden">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            Mostra Gestione Magazzino
                        </button>

                        <div id="stockManagementSection" class="stock-management-grid">
                            <div class="modal-form-group" style="position: relative;">
                                <label for="searchProductToUnload">Cerca Prodotto</label>
                                <input type="text" id="searchProductToUnload" placeholder="Nome prodotto...">
                                <input type="hidden" id="selectedProductIdToUnload">
                                <div id="productToUnloadAutocompleteList" class="autocomplete-list"></div>
                            </div>
                            <div class="modal-form-group">
                                <label for="productCurrentStock">Giacenza Attuale</label>
                                <input type="text" id="productCurrentStock" readonly value="N/D" class="readonly-highlight">
                            </div>
                            <div class="modal-form-group">
                                <label for="quantityToUnload">Quantità da Scaricare</label>
                                <input type="number" step="1" id="quantityToUnload" min="1" value="1">
                            </div>
                            <div class="modal-form-group" style="display: flex; align-items: flex-end;">
                                <button type="button" id="unloadFromStockBtn" class="btn-unload">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                        <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                                    </svg>
                                    Scarica
                                </button>
                            </div>
                        </div>

                        <div class="movements-section">
                            <h5>Articoli Scaricati</h5>
                            <div id="reservationMovementsList" class="movements-list">
                                <p class="empty-movements" id="noMovementsMessage">Nessun articolo scaricato per questa prenotazione.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Scheda -->
                    <div id="schedaTabContent" class="tab-content">
                        <div class="tab-section-header">
                            <h4>Cronologia Prenotazione</h4>
                            <p>Eventi e modifiche registrati</p>
                        </div>
                        <div id="reservationHistoryList" class="history-list">
                            <p class="empty-history" id="noHistoryEntriesMessage">Nessun evento registrato.</p>
                        </div>
                    </div>
                </div>
            </form>

            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal()">Annulla</button>
                <button class="btn-save" onclick="saveReservation()">Salva Modifiche</button>
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

        mainModal.classList.remove('hidden'); // Remove hidden class
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
            mainModal.classList.add('hidden'); // Add hidden class back
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

    // ========== NEW CARD LAYOUT FUNCTIONS ==========
    
    // Show toast notification
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const iconHtml = type === 'success' 
            ? '<div class="toast-icon success">✓</div>'
            : type === 'error'
            ? '<div class="toast-icon error">✕</div>'
            : '<div class="toast-icon warning">!</div>';
        
        toast.innerHTML = `${iconHtml}<span class="toast-message">${message}</span>`;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.transform = 'translateX(120%)';
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    }
    
    // Filter by status
    function filterByStatus(status) {
        const cards = document.querySelectorAll('.prenotazione-card');
        const summaryCards = document.querySelectorAll('.summary-card');
        
        summaryCards.forEach(card => card.classList.remove('active'));
        
        if (status === 'all') {
            cards.forEach(card => card.style.display = 'block');
            document.querySelector('.summary-card--total')?.classList.add('active');
        } else {
            cards.forEach(card => {
                const cardStatus = card.getAttribute('data-status');
                card.style.display = (cardStatus === status) ? 'block' : 'none';
            });
            
            // Highlight active summary card
            if (status === 'In Attesa') {
                document.querySelector('.summary-card--pending')?.classList.add('active');
            } else if (status === 'Completata') {
                document.querySelector('.summary-card--completed')?.classList.add('active');
            } else if (status === 'Annullata') {
                document.querySelector('.summary-card--cancelled')?.classList.add('active');
            }
        }
    }
    
    // Toggle card popup menu
    let activeCardPopup = null;
    function toggleCardPopup(btn, id) {
        event.stopPropagation();
        const popup = document.getElementById(`cardPopup-${id}`);
        
        // Close other popups
        document.querySelectorAll('.card-popup.show').forEach(p => {
            if (p !== popup) p.classList.remove('show');
        });
        
        if (popup) {
            popup.classList.toggle('show');
            activeCardPopup = popup.classList.contains('show') ? popup : null;
        }
    }
    
    // Close popups on outside click
    document.addEventListener('click', function(e) {
        if (activeCardPopup && !e.target.closest('.card-actions-wrapper')) {
            activeCardPopup.classList.remove('show');
            activeCardPopup = null;
        }
    });
</script>
</body>
</html>
