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

// Opzionale: Puoi anche recuperare qui i dati dell'utente loggato
// per usarli nella pagina, ad esempio:
// $current_user_id = $_SESSION['user_id'] ?? null;
// $current_user_email = $_SESSION['user_email'] ?? null;
// $current_user_ruolo = $_SESSION['user_ruolo'] ?? 'ospite';
?>
