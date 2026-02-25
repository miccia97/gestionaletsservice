<?php
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Avvia la sessione PHP all'inizio dello script
session_start();

// Includi il file di connessione al database MySQL
if (!file_exists('db.php')) {
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: Il file db.php non è stato trovato!</div>";
    exit;
}
require_once 'db.php';

// Inizializza la variabile message per l'uso nel HTML
$message = '';

// Controlla subito se c'è stato un errore di connessione al database da db.php
if (isset($db_connection_error) && $db_connection_error !== null) {
    $_SESSION['message'] = 'Errore critico: La connessione al database non è stata stabilita correttamente! ' . htmlspecialchars($db_connection_error, ENT_QUOTES, 'UTF-8');
    $_SESSION['isError'] = true;
    // Non fare un exit qui, lascia che la pagina si carichi per mostrare il messaggio
}

// --- Gestione richieste AJAX (Elimina Fattura e Visualizza Dettagli) ---
if (isset($_POST['action'])) {
    header('Content-Type: application/json'); // Tutte le risposte AJAX saranno JSON

    switch ($_POST['action']) {
        case 'delete_invoice':
            $invoiceId = $_POST['id'] ?? null;

            if (!$invoiceId || !is_numeric($invoiceId)) {
                echo json_encode(['success' => false, 'message' => 'ID fattura non valido per l\'eliminazione.']);
                exit;
            }

            try {
                mysqli_begin_transaction($conn);

                // 1. Elimina i dettagli fattura
                $stmt_delete_details = $conn->prepare("DELETE FROM dettagli_fattura WHERE fattura_id = ?");
                if ($stmt_delete_details === false) {
                    throw new mysqli_sql_exception("Errore nella preparazione DELETE dettagli_fattura: " . $conn->error);
                }
                $stmt_delete_details->bind_param('i', $invoiceId);
                if ($stmt_delete_details->execute() === false) {
                    throw new mysqli_sql_exception("Errore nell'esecuzione DELETE dettagli_fattura: " . $stmt_delete_details->error);
                }
                $stmt_delete_details->close();

                // 2. Elimina la fattura principale
                $stmt_delete_invoice = $conn->prepare("DELETE FROM fatture WHERE id = ?");
                if ($stmt_delete_invoice === false) {
                    throw new mysqli_sql_exception("Errore nella preparazione DELETE fatture: " . $conn->error);
                }
                $stmt_delete_invoice->bind_param('i', $invoiceId);
                if ($stmt_delete_invoice->execute() === false) {
                    throw new mysqli_sql_exception("Errore nell'esecuzione DELETE fatture: " . $stmt_delete_invoice->error);
                }
                $stmt_delete_invoice->close();

                mysqli_commit($conn);
                echo json_encode(['success' => true, 'message' => 'Fattura eliminata con successo!']);
                exit;

            } catch (mysqli_sql_exception $e) {
                mysqli_rollback($conn);
                error_log("ERRORE ELIMINAZIONE FATTURA (SQL): " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Errore database durante l\'eliminazione della fattura: ' . $e->getMessage()]);
                exit;
            } catch (Exception $e) {
                mysqli_rollback($conn);
                error_log("ERRORE ELIMINAZIONE FATTURA (GENERALE): " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Errore generale durante l\'eliminazione della fattura: ' . $e->getMessage()]);
                exit;
            }
            break;

        default:
            // Continua a gestire altre azioni POST se necessario
            break;
    }
} elseif (isset($_GET['action']) && $_GET['action'] === 'fetch_invoice_details') {
    header('Content-Type: application/json'); // Tutte le risposte AJAX saranno JSON

    $invoiceId = $_GET['id'] ?? null;

    if (!$invoiceId || !is_numeric($invoiceId)) {
        echo json_encode(['success' => false, 'message' => 'ID fattura non valido per il recupero dei dettagli.']);
        exit;
    }

    $invoiceData = null;
    $invoiceDetails = [];

    try {
        // Recupera i dati della fattura principale
        $stmt_invoice = $conn->prepare("SELECT f.id, f.numero_fattura, f.data_fattura, fo.ragione_sociale AS nome_fornitore, f.stato, f.totale_imponibile, f.totale_iva, f.totale_lordo, f.allegato_url FROM fatture f JOIN fornitori fo ON f.fornitore_id = fo.id WHERE f.id = ?");
        if ($stmt_invoice === false) {
            throw new mysqli_sql_exception("Errore nella preparazione GET fattura: " . $conn->error);
        }
        $stmt_invoice->bind_param('i', $invoiceId);
        $stmt_invoice->execute();
        $result_invoice = $stmt_invoice->get_result();
        $invoiceData = $result_invoice->fetch_assoc();
        $stmt_invoice->close();

        if (!$invoiceData) {
            echo json_encode(['success' => false, 'message' => 'Fattura non trovata.']);
            exit;
        }

        // Recupera i dettagli dei prodotti della fattura
        $stmt_details = $conn->prepare("SELECT prodotto_id, descrizione_prodotto, quantita, unita_misura, prezzo_unitario_netto, iva_percentuale, prezzo_unitario_lordo, totale_riga_netto, totale_riga_lordo, prodotto_senza_iva FROM dettagli_fattura WHERE fattura_id = ?");
        if ($stmt_details === false) {
            throw new mysqli_sql_exception("Errore nella preparazione GET dettagli_fattura: " . $conn->error);
        }
        $stmt_details->bind_param('i', $invoiceId);
        $stmt_details->execute();
        $result_details = $stmt_details->get_result();
        if ($result_details) {
            $invoiceDetails = $result_details->fetch_all(MYSQLI_ASSOC);
        }
        $stmt_details->close();

        echo json_encode(['success' => true, 'invoice' => $invoiceData, 'details' => $invoiceDetails]);
        exit;

    } catch (mysqli_sql_exception $e) {
        error_log("ERRORE RECUPERO DETTAGLI FATTURA (SQL): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore database durante il recupero dei dettagli: ' . $e->getMessage()]);
        exit;
    } catch (Exception $e) {
        error_log("ERRORE RECUPERO DETTAGLI FATTURA (GENERALE): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore generale durante il recupero dei dettagli: ' . $e->getMessage()]);
        exit;
    }
}


// Controlla se ci sono messaggi da visualizzare dalla sessione
if (isset($_SESSION['message'])) {
    $sessionMessage = $_SESSION['message'];
    $sessionIsError = $_SESSION['isError'] ?? false;
    // Prepara lo script per visualizzare il messaggio una volta che il DOM è caricato
    $message = "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('" . addslashes($sessionMessage) . "', " . ($sessionIsError ? 'true' : 'false') . "); });</script>";
    // Cancella i messaggi dalla sessione per evitare che riappaiano
    unset($_SESSION['message']);
    unset($_SESSION['isError']);
}

$fatture = [];
try {
    // Query per recuperare tutte le fatture e le informazioni sul fornitore
    // Utilizza un JOIN per ottenere il nome del fornitore dalla tabella 'fornitori'
    $stmt = $conn->prepare("SELECT f.id, f.numero_fattura, f.data_fattura, fo.ragione_sociale AS nome_fornitore, f.stato, f.totale_imponibile, f.totale_iva, f.totale_lordo, f.allegato_url FROM fatture f JOIN fornitori fo ON f.fornitore_id = fo.id ORDER BY f.data_fattura DESC, f.numero_fattura DESC");

    if ($stmt === false) {
        throw new mysqli_sql_exception("Errore nella preparazione della query di recupero fatture: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $fatture = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    $errorMessage = "Errore database durante il recupero delle fatture: " . $e->getMessage();
    $_SESSION['message'] = $errorMessage;
    $_SESSION['isError'] = true;
    error_log("ERRORE RECUPERO FATTURE (SQL): " . $e->getMessage() . " - Stack: " . $e->getTraceAsString());
    // Reindirizza per mostrare l'errore tramite showMessage (se non già reindirizzato per connessione)
    if (!isset($db_connection_error)) {
        header("Location: visualizza_fatture.php");
        exit();
    }
} catch (Exception $e) {
    $errorMessage = "Errore generale durante il recupero delle fatture: " . $e->getMessage();
    $_SESSION['message'] = $errorMessage;
    $_SESSION['isError'] = true;
    error_log("ERRORE GENERALE RECUPERO FATTURE: " . $e->getMessage() . " - Stack: " . $e->getTraceAsString());
    if (!isset($db_connection_error)) {
        header("Location: visualizza_fatture.php");
        exit();
    }
}
?>
