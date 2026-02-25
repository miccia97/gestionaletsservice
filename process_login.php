<?php
session_start(); // Avvia la sessione PHP all'inizio

// --- DATI DI CONNESSIONE AL DATABASE ---
// !!! Importante: ADATTA QUESTI VALORI con le tue credenziali reali del database MySQL !!!
$servername = "localhost";    // Indirizzo del server MySQL (solitamente "localhost")
$username_db = "root"; // Il nome utente che usi per accedere al tuo database
$password_db = ""; // La password del tuo utente del database
$dbname = "gestionale_tsservice";     // Il nome del tuo database (dove si trova la tabella 'utenti')

// --- Connessione al database ---
// Crea una nuova connessione usando MySQLi
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Controlla se la connessione è avvenuta con successo
if ($conn->connect_error) {
    // Se c'è un errore di connessione, interrompi lo script e mostra un messaggio
    die("Connessione al database fallita: " . $conn->connect_error);
}

// --- Gestione della richiesta POST dal modulo di login ---
// Verifica se la richiesta HTTP è stata fatta con il metodo POST (cioè, il form è stato inviato)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera il nome utente e la password inviati dal form
    // Usiamo real_escape_string per prevenire SQL Injection sul nome utente
    $username_input = $conn->real_escape_string($_POST['username']);
    $password_input = $_POST['password']; // La password non viene "escapata" qui perché la verificheremo con password_verify()

    // --- Prepara la query SQL per recuperare i dati dell'utente ---
    // Usiamo una prepared statement per maggiore sicurezza (prevenzione SQL Injection)
    $sql = "SELECT id, nome_utente, nome, email, password, ruolo, attivo FROM utenti WHERE nome_utente = ?";
    $stmt = $conn->prepare($sql); // Prepara la query

    // Controlla se la preparazione della query è fallita
    if (false === $stmt) {
        $_SESSION['login_error'] = "Errore nella preparazione della query: " . $conn->error;
        header('Location: login.php');
        exit();
    }

    // Collega i parametri alla query: "s" indica che $username_input è una stringa
    $stmt->bind_param("s", $username_input);
    $stmt->execute(); // Esegui la query
    $result = $stmt->get_result(); // Ottieni il risultato della query

    // --- Verifica le credenziali dell'utente ---
    // Controlla se è stato trovato esattamente un utente con quel nome utente
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc(); // Estrai i dati dell'utente come array associativo

        // Verifica la password inserita con l'hash salvato nel database
        // E controlla se l'account è attivo (il campo 'attivo' dovrebbe essere 1)
        if (password_verify($password_input, $user['password']) && $user['attivo'] == 1) {
            // --- Login riuscito! ---
            // Salva le informazioni essenziali dell'utente nella sessione
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['nome_utente']; // Il nome utente per login
            $_SESSION['user_name'] = $user['nome'];       // Il nome del dipendente per i saluti
            $_SESSION['user_email'] = $user['email'];     // L'email del dipendente
            $_SESSION['role'] = $user['ruolo'];           // Il ruolo per i permessi

            // Aggiorna la data dell'ultimo accesso nel database (opzionale, se hai il campo 'ultimo_accesso')
            $update_last_login_sql = "UPDATE utenti SET ultimo_accesso = NOW() WHERE id = ?";
            $update_last_login_stmt = $conn->prepare($update_last_login_sql);
            if (false !== $update_last_login_stmt) {
                $update_last_login_stmt->bind_param("i", $user['id']);
                $update_last_login_stmt->execute();
                $update_last_login_stmt->close();
            }

            // Reindirizza l'utente alla dashboard o alla pagina principale del gestionale
            header('Location: homepage.php');
            exit(); // Termina lo script dopo il reindirizzamento
        } else {
            // --- Password errata o account non attivo ---
            $_SESSION['login_error'] = "Nome utente o password errati, o account non attivo.";
            header('Location: login.php'); // Torna alla pagina di login con il messaggio di errore
            exit();
        }
    } else {
        // --- Nome utente non trovato ---
        $_SESSION['login_error'] = "Nome utente o password errati.";
        header('Location: login.php'); // Torna alla pagina di login con il messaggio di errore
        exit();
    }
}

// Chiudi la connessione al database
$conn->close();
?>