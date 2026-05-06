<?php
// --- Controllo Autenticazione ---
// Questo script deve essere incluso all'inizio di ogni pagina protetta.

// Avvia la sessione se non è già stata avviata.
// Questo è fondamentale per accedere alle variabili di sessione,
// come $_SESSION['loggedin'].
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Controlla se l'utente non è loggato.
// Se la variabile di sessione 'loggedin' non esiste o non è true,
// l'utente non è autenticato.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Memorizza un messaggio per informare l'utente che deve effettuare il login
    $_SESSION['message'] = 'Devi effettuare il login per accedere a questa pagina.';
    $_SESSION['isError'] = true; // Indica che è un messaggio di errore

    // Reindirizza l'utente alla pagina di login.
    // Assicurati che il percorso a 'login.php' sia corretto rispetto
    // alla posizione in cui salvi questo file 'auth_check.php'.
    header('Location: login.php');
    exit; // Termina l'esecuzione dello script per evitare che la pagina venga caricata
}

// Se l'utente è loggato, lo script continua l'esecuzione normalmente
// e la pagina protetta verrà visualizzata.

// ===== SISTEMA PERMESSI GRANULARI (RBAC) =====
// Ruoli: admin, manager, utente
// admin: accesso completo
// manager: tutto tranne gestione utenti, audit log, backup
// utente: solo vendita, assistenza, inventario lettura

$current_user_ruolo = $_SESSION['ruolo'] ?? 'utente';

// Mappa permessi per ruolo
$permessi_ruolo = [
    'admin' => ['*'], // tutto
    'manager' => [
        'vendita', 'assistenza', 'moduli', 'magazzino', 'inventario',
        'reportistica', 'fatture', 'categorie', 'fornitori', 'resi',
        'clienti', 'dashboard', 'settings_base', 'chiusura_cassa'
    ],
    'utente' => [
        'vendita', 'assistenza', 'moduli', 'inventario_lettura',
        'clienti', 'dashboard'
    ]
];

/**
 * Verifica se l'utente corrente ha un determinato permesso.
 * @param string $permesso Il permesso da verificare
 * @return bool
 */
function ha_permesso($permesso) {
    global $permessi_ruolo, $current_user_ruolo;
    $ruolo = $current_user_ruolo;
    if (!isset($permessi_ruolo[$ruolo])) return false;
    if (in_array('*', $permessi_ruolo[$ruolo])) return true;
    return in_array($permesso, $permessi_ruolo[$ruolo]);
}

/**
 * Verifica se l'utente è admin
 * @return bool
 */
function is_admin() {
    return ($_SESSION['ruolo'] ?? 'utente') === 'admin';
}

/**
 * Blocca l'accesso se l'utente non ha il permesso richiesto.
 * Mostra un messaggio di errore e termina l'esecuzione.
 * @param string $permesso Il permesso richiesto
 */
function richiedi_permesso($permesso) {
    if (!ha_permesso($permesso)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Accesso Negato</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
        <style>body{font-family:"Inter",sans-serif;background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
        .box{text-align:center;padding:60px;max-width:500px;}.icon{font-size:4rem;margin-bottom:20px;}
        h1{font-size:1.5rem;font-weight:800;color:#0f172a;margin-bottom:8px;}
        p{color:#64748b;font-size:0.95rem;line-height:1.6;margin-bottom:24px;}
        a{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;
        text-decoration:none;border-radius:12px;font-weight:700;font-size:0.9rem;box-shadow:0 4px 12px rgba(34,197,94,0.4);transition:transform 0.2s;}
        a:hover{transform:translateY(-2px);}</style></head><body>
        <div class="box"><div class="icon">🔒</div><h1>Accesso Negato</h1>
        <p>Non hai i permessi necessari per accedere a questa sezione. Contatta un amministratore se ritieni sia un errore.</p>
        <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Torna alla Dashboard</a></div></body></html>';
        exit;
    }
}
?>
