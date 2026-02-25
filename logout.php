<?php
// Avvia la sessione PHP. È fondamentale chiamare session_start()
// all'inizio di ogni script che interagisce con le sessioni,
// prima di qualsiasi output al browser.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Distrugge tutte le variabili di sessione.
// Questo rimuove tutti i dati associati alla sessione corrente.
$_SESSION = array();

// 2. Se si desidera distruggere completamente la sessione,
// è anche necessario cancellare il cookie di sessione.
// Nota: questo distruggerà la sessione, e non solo i dati della sessione!
// session_name() restituisce il nome del cookie di sessione (di default "PHPSESSID").
// session_get_cookie_params() restituisce i parametri del cookie di sessione.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Infine, distrugge la sessione.
// Questo elimina il file di sessione sul server.
session_destroy();

// 4. Reindirizza l'utente alla pagina di login o alla homepage.
// È buona pratica reindirizzare l'utente dopo un'operazione di logout.
// Assicurati che 'index.php' sia la tua pagina di login o la homepage.
header("Location: login.php");
exit; // È importante chiamare exit() dopo un header("Location:")
      // per assicurarsi che lo script termini immediatamente e il reindirizzamento avvenga.
?>
