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
error_log("PHP_DEBUG: Script gestione_fatture.php started execution."); // New very early log


// Controlla subito se c'è stato un errore di connessione al database da db.php
if (isset($db_connection_error) && $db_connection_error !== null) {
    // Se la richiesta è AJAX, restituisci JSON con l'errore
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Errore di connessione al database per richiesta AJAX: ' . $db_connection_error
        ]);
        exit; // Termina lo script dopo la risposta JSON
    } else {
        // Altrimenti, per una normale richiesta di pagina, mostra l'errore HTML tramite sessione
        $_SESSION['message'] = 'Errore critico: La connessione al database non è stata stabilita correttamente! ' . htmlspecialchars($db_connection_error, ENT_QUOTES, 'UTF-8');
        $_SESSION['isError'] = true;
        // Non fare un exit qui, lascia che la pagina si carichi per mostrare il messaggio
    }
}

// Inizializza la variabile message per l'uso nel HTML
$message = '';

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

// --- LOGICA PER CARICARE LA FATTURA ESISTENTE PER LA MODIFICA ---
$invoice_to_edit = null;
$invoice_details_to_edit = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $fattura_id_to_load = (int)$_GET['id'];
    error_log("PHP: Loading invoice for edit with ID: " . $fattura_id_to_load);

    try {
        // Recupera i dati della fattura principale
        $stmt_main = $conn->prepare("SELECT f.*, fo.ragione_sociale AS nome_fornitore FROM fatture f JOIN fornitori fo ON f.fornitore_id = fo.id WHERE f.id = ?");
        if ($stmt_main === false) {
            throw new mysqli_sql_exception("Errore nella preparazione della query principale fattura: " . $conn->error);
        }
        $stmt_main->bind_param('i', $fattura_id_to_load);
        $stmt_main->execute();
        $result_main = $stmt_main->get_result();
        $invoice_to_edit = $result_main->fetch_assoc();
        $stmt_main->close();

        if ($invoice_to_edit) {
            error_log("PHP: Main invoice data loaded: " . print_r($invoice_to_edit, true));
            // Recupera i dettagli dei prodotti associati a questa fattura
            $stmt_details = $conn->prepare("SELECT * FROM dettagli_fattura WHERE fattura_id = ? ORDER BY id ASC");
            if ($stmt_details === false) {
                throw new mysqli_sql_exception("Errore nella preparazione della query dettagli fattura: " . $conn->error);
            }
            $stmt_details->bind_param('i', $fattura_id_to_load);
            $stmt_details->execute();
            $result_details = $stmt_details->get_result();
            while ($row = $result_details->fetch_assoc()) {
                $invoice_details_to_edit[] = $row;
            }
            $stmt_details->close();
            error_log("PHP: Invoice details loaded: " . print_r($invoice_details_to_edit, true));
        } else {
            // Se la fattura non esiste, reindirizza o mostra un errore
            $_SESSION['message'] = 'Fattura non trovata per la modifica.';
            $_SESSION['isError'] = true;
            header("Location: visualizza_fatture.php");
            exit();
        }

    } catch (mysqli_sql_exception $e) {
        error_log("Errore nel caricamento fattura per modifica (SQL): " . $e->getMessage());
        $_SESSION['message'] = 'Errore database durante il caricamento della fattura per la modifica.';
        $_SESSION['isError'] = true;
        header("Location: visualizza_fatture.php");
        exit();
    } catch (Exception $e) {
        error_log("Errore generale nel caricamento fattura per modifica: " . $e->getMessage());
        $_SESSION['message'] = 'Errore generale durante il caricamento della fattura per la modifica.';
        $_SESSION['isError'] = true;
        header("Location: visualizza_fatture.php");
        exit();
    }
}


// --- LOGICA PER GESTIRE LE RICHIESTE AJAX DEL MODAL (NUOVO PRODOTTO, NUOVO FORNITORE, ecc.) ---
if (isset($_POST['action'])) {
    header('Content-Type: application/json'); // Assicura che la risposta sia JSON per le richieste AJAX

    switch ($_POST['action']) {
        case 'add_new_product_to_catalog':
            $name = $_POST['name'] ?? '';
            $code = $_POST['code'] ?? '';
            $categoryId = $_POST['categoryId'] ?? ''; // Questo è l'ID numerico della categoria
            $priceNet = $_POST['priceNet'] ?? '';
            $quantity = $_POST['quantity'] ?? ''; // Quantità iniziale per il magazzino
            $priceSale1 = $_POST['priceSale1'] ?? null; // Nuovo campo: Prezzo di vendita 1
            $priceSale2 = $_POST['priceSale2'] ?? null; // Nuovo campo: Prezzo di vendita 2

            // Validazione campi obbligatori
            if (empty($name) || empty($categoryId) || !is_numeric($priceNet) || $priceNet < 0 || !is_numeric($quantity) || $quantity < 0) {
                echo json_encode(['success' => false, 'message' => 'Tutti i campi obbligatori (Nome, Categoria, Prezzo Acquisto Netto, Quantità Iniziale) devono essere validi e compilati.']);
                exit;
            }


            // Validazione per prezzo_vendita1 e prezzo_vendita2 se presenti
            if ($priceSale1 !== null && $priceSale1 !== '' && !is_numeric($priceSale1)) {
                echo json_encode(['success' => false, 'message' => 'Prezzo Vendita 1 deve essere un valore numerico valido.']);
                exit;
            }
            if ($priceSale2 !== null && $priceSale2 !== '' && !is_numeric($priceSale2)) {
                echo json_encode(['success' => false, 'message' => 'Prezzo Vendita 2 deve essere un valore numerico valido.']);
                exit;
            }


            try {
                // Trova il nome della categoria dalla tabella 'categorie' basandosi sull'ID
                $stmt_get_cat_name = $conn->prepare("SELECT nome FROM categorie WHERE id = ?");
                if ($stmt_get_cat_name === false) {
                    echo json_encode(['success' => false, 'message' => 'Errore nella preparazione della query categoria: ' . $conn->error]);
                    exit;
                }
                $stmt_get_cat_name->bind_param('i', $categoryId);
                $stmt_get_cat_name->execute();
                $result_cat_name = $stmt_get_cat_name->get_result();
                $cat_row = $result_cat_name->fetch_assoc();
                $categoryName = $cat_row ? $cat_row['nome'] : null; // Prendi il nome della categoria
                $stmt_get_cat_name->close();


                if ($categoryName === null) {
                     echo json_encode(['success' => false, 'message' => 'Categoria selezionata non valida.']);
                     exit;
                }

                // Inserisci il nuovo prodotto nella tabella `prodotti` con i nuovi campi
                $stmt_insert_new_product = $conn->prepare("INSERT INTO prodotti (nome, categoria, quantita, prezzo_acquisto, barcode, prezzo_vendita1, prezzo_vendita2) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt_insert_new_product === false) {
                    echo json_encode(['success' => false, 'message' => 'Errore nella preparazione della query inserimento prodotto: ' . $conn->error]);
                    exit;
                }
                // 'ssidsdd' -> string, string, integer, double, string, double, double
                // Nota: i valori null per prezzo_vendita1 e prezzo_vendita2 devono essere gestiti con 'd' nel bind_param se la colonna è numerica e ammette NULL.
                // Per evitare problemi con bind_param per valori null numerici, converti a 0 se null.
                $priceSale1_val = ($priceSale1 === null || $priceSale1 === '') ? 0.00 : (float)$priceSale1;
                $priceSale2_val = ($priceSale2 === null || $priceSale2 === '') ? 0.00 : (float)$priceSale2;

                $stmt_insert_new_product->bind_param('ssidsdd', $name, $categoryName, $quantity, $priceNet, $code, $priceSale1_val, $priceSale2_val);
                if ($stmt_insert_new_product->execute() === false) {
                    echo json_encode(['success' => false, 'message' => 'Errore nell\'esecuzione inserimento prodotto: ' . $stmt_insert_new_product->error]);
                    exit;
                }
                $new_product_id = $conn->insert_id; // Ottieni l'ID del prodotto appena inserito
                $stmt_insert_new_product->close();

                echo json_encode([
                    'success' => true,
                    'id_prodotto' => $new_product_id,
                    'nome_prodotto' => $name,
                    'codice_sku' => $code, // Usiamo 'barcode' come 'codice_sku'
                    'unita_misura' => 'pz', // Default per UM (non presente in `prodotti` schema)
                    'category' => $categoryName, // Ritorna il nome della categoria
                    'priceNet' => (float)$priceNet, // Ritorna il prezzo netto come float
                    'priceSale1' => (float)$priceSale1_val, // Nuovo campo
                    'priceSale2' => (float)$priceSale2_val // Nuovo campo
                ]);
                exit;
            } catch (mysqli_sql_exception $e) {
                error_log("ERRORE AGGIUNTA PRODOTTO (SQL): " . $e->getMessage()); // Log completo dell'errore SQL
                echo json_encode(['success' => false, 'message' => 'Errore database durante l\'aggiunta del prodotto: ' . $e->getMessage()]);
                exit;
            }
            break;

        case 'add_new_supplier':
            $name = $_POST['name'] ?? ''; // Mappa a 'nome' e 'ragione_sociale'
            $partitaIva = $_POST['partitaIva'] ?? null;
            $codiceFiscale = $_POST['codiceFiscale'] ?? null;
            $indirizzo = $_POST['address'] ?? null;
            $citta = $_POST['city'] ?? null;
            $cap = $_POST['cap'] ?? null;
            $provincia = $_POST['province'] ?? null;
            $paese = $_POST['country'] ?? null;
            $telefono = $_POST['phone'] ?? null;
            $email = $_POST['email'] ?? null;

            // Log di debugging dei dati ricevuti
            error_log("Dati ricevuti per nuovo fornitore: " . print_r($_POST, true));

            // Validazione: 'nome' (mappato da $name) è NOT NULL nello schema del DB fornitori.
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Nome/Ragione Sociale del fornitore è obbligatorio.']);
                exit;
            }
            // Aggiungere altre validazioni se necessario per altri campi (es. formato email, numeri telefono)

            try {
                // Inserisci il nuovo fornitore nella tabella 'fornitori' con tutti i campi specificati
                $stmt_insert_supplier = $conn->prepare("INSERT INTO fornitori (nome, ragione_sociale, partita_iva, codice_fiscale, indirizzo, citta, cap, provincia, paese, telefono, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt_insert_supplier === false) {
                    error_log("Errore nella preparazione query inserimento fornitore: " . $conn->error);
                    echo json_encode(['success' => false, 'message' => 'Errore nella preparazione query inserimento fornitore: ' . $conn->error]);
                    exit;
                }
                // Log dei parametri per bind
                error_log("Parametri bind fornitore: " . json_encode([$name, $name, $partitaIva, $codiceFiscale, $indirizzo, $citta, $cap, $provincia, $paese, $telefono, $email]));

                // Tutti i campi aggiunti sono stringhe ('s')
                $stmt_insert_supplier->bind_param('sssssssssss', $name, $name, $partitaIva, $codiceFiscale, $indirizzo, $citta, $cap, $provincia, $paese, $telefono, $email);
                if ($stmt_insert_supplier->execute() === false) {
                    error_log("Errore nell'esecuzione inserimento fornitore: " . $stmt_insert_supplier->error);
                    echo json_encode(['success' => false, 'message' => 'Errore nell\'esecuzione inserimento fornitore: ' . $stmt_insert_supplier->error]);
                    exit;
                }
                $new_supplier_id = $conn->insert_id;
                $stmt_insert_supplier->close();

                echo json_encode([
                    'success' => true,
                    'id' => $new_supplier_id,
                    'ragione_sociale' => $name // Ritorna ragione_sociale per aggiornare la UI
                ]);
                exit;
            } catch (mysqli_sql_exception $e) {
                error_log("ERRORE AGGIUNTA FORNITORE (SQL): " . $e->getMessage()); // Log completo dell'errore SQL
                echo json_encode(['success' => false, 'message' => 'Errore database durante l\'aggiunta del fornitore: ' . $e->getMessage()]);
                exit;
            }
            break;

        default:
            // Se l'azione non è riconosciuta per una richiesta AJAX
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Azione AJAX non riconosciuta.']);
            exit;
    }
}


// --- LOGICA DI ELABORAZIONE DEL FORM (SALVATAGGIO FATTURA COMPLETA) ---
// Questa parte viene eseguita solo se non è una richiesta AJAX con 'action'
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    error_log("PHP: Received POST request for invoice submission.");
    error_log("PHP: Raw POST data: " . print_r($_POST, true));
    error_log("PHP: Files data: " . print_r($_FILES, true));

    // 1. Dati della Fattura Principale
    $invoice_id_to_update = $_POST['invoiceId'] ?? null; // Recupera l'ID se presente per la modifica
    $numero_fattura = $_POST['invoiceNumber'] ?? '';
    $data_fattura = $_POST['invoiceDate'] ?? '';
    $id_fornitore = $_POST['supplierId'] ?? ''; // Questo è l'ID numerico del fornitore, dovrebbe essere un intero
    $stato_fattura = $_POST['invoiceStatus'] ?? 'Da Verificare';

    // Validazione fornitore ID
    if (empty($id_fornitore) || !is_numeric($id_fornitore) || (int)$id_fornitore <= 0) {
        $_SESSION['message'] = 'Errore: Fornitore non selezionato o ID fornitore non valido.';
        $_SESSION['isError'] = true;
        error_log("ERRORE SALVATAGGIO FATTURA: Fornitore ID non valido: " . $id_fornitore);
        // Reindirizza alla pagina precedente o a se stessa, mantenendo l'ID della fattura se presente
        $redirect_url = 'gestione_fatture.php';
        if ($invoice_id_to_update) {
            $redirect_url .= '?id=' . $invoice_id_to_update;
        }
        header("Location: " . $redirect_url);
        exit();
    }
    $id_fornitore_int = (int)$id_fornitore;


    // Calcolo totali (saranno aggiornati dopo l'inserimento dei dettagli)
    $totale_imponibile = 0;
    $totale_iva = 0;
    $totale_lordo = 0;

    // 2. Gestione Allegato Fattura (Logica semplificata, richiede cartella 'uploads/fatture' scrivibile)
    $percorso_allegato = null;
    // Se è una modifica e non viene caricato un nuovo file, mantieni l'allegato esistente
    if ($invoice_id_to_update && isset($invoice_to_edit['allegato_url'])) {
        $percorso_allegato = $invoice_to_edit['allegato_url'];
    }

    if (isset($_FILES['invoiceAttachment']) && $_FILES['invoiceAttachment']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/fatture/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Crea la directory se non esiste
        }
        $file_name = uniqid('fattura_') . '_' . basename($_FILES['invoiceAttachment']['name']);
        $percorso_allegato = $upload_dir . $file_name;

        if (!move_uploaded_file($_FILES['invoiceAttachment']['tmp_name'], $percorso_allegato)) {
            $_SESSION['message'] = 'Errore nel caricamento del file allegato.';
            $_SESSION['isError'] = true;
            $percorso_allegato = null; // Reset if upload fails
        }
    }

    try {
        // Inizia la transazione (MySQLi)
        mysqli_begin_transaction($conn);
        error_log("PHP_DEBUG: Starting database transaction.");

        $id_fattura = 0; // Inizializza l'ID della fattura

        if ($invoice_id_to_update) {
            // È una MODIFICA: Aggiorna la fattura esistente
            error_log("PHP_DEBUG: Updating existing invoice with ID: " . $invoice_id_to_update);
            // Non aggiorniamo totale_imponibile, totale_iva, totale_lordo qui, verranno aggiornati dopo
            $stmt_fattura = $conn->prepare("UPDATE fatture SET numero_fattura = ?, data_fattura = ?, fornitore_id = ?, stato = ?, allegato_url = ? WHERE id = ?");
            if ($stmt_fattura === false) {
                throw new mysqli_sql_exception("Errore nella preparazione della query di aggiornamento fattura: " . $conn->error);
            }
            error_log("PHP_DEBUG: Binding parameters for UPDATE fatture: Types='ssisdi', Values=" . json_encode([$numero_fattura, $data_fattura, $id_fornitore_int, $stato_fattura, $percorso_allegato, $invoice_id_to_update]));
            $stmt_fattura->bind_param('ssisdi', $numero_fattura, $data_fattura, $id_fornitore_int, $stato_fattura, $percorso_allegato, $invoice_id_to_update);
            $exec_success = $stmt_fattura->execute();
            if ($exec_success === false) {
                throw new mysqli_sql_exception("Errore nell'esecuzione dell'aggiornamento fattura: " . $stmt_fattura->error);
            }
            $id_fattura = $invoice_id_to_update; // Usa l'ID esistente
            $stmt_fattura->close();
            error_log("PHP_DEBUG: Invoice updated successfully. ID: " . $id_fattura);


            // Elimina i vecchi dettagli fattura prima di inserire i nuovi
            $stmt_delete_details = $conn->prepare("DELETE FROM dettagli_fattura WHERE fattura_id = ?");
            if ($stmt_delete_details === false) {
                throw new mysqli_sql_exception("Errore nella preparazione della query di eliminazione dettagli: " . $conn->error);
            }
            error_log("PHP_DEBUG: Deleting old details for fattura_id: " . $id_fattura);
            $stmt_delete_details->bind_param('i', $id_fattura);
            $stmt_delete_details->execute();
            $stmt_delete_details->close();
            error_log("PHP_DEBUG: Old invoice details deleted for invoice ID: " . $id_fattura);

        } else {
            // È un NUOVO INSERIMENTO: Inserisci una nuova fattura
            error_log("PHP_DEBUG: Inserting new invoice.");
            $stmt_fattura = $conn->prepare("INSERT INTO fatture (numero_fattura, data_fattura, fornitore_id, stato, allegato_url, totale_imponibile, totale_iva, totale_lordo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_fattura === false) {
                throw new mysqli_sql_exception("Errore nella preparazione della query di inserimento fattura: " . $conn->error);
            }
            error_log("PHP_DEBUG: Binding parameters for INSERT fatture: Types='ssisdddd', Values=" . json_encode([$numero_fattura, $data_fattura, $id_fornitore_int, $stato_fattura, $percorso_allegato, $totale_imponibile, $totale_iva, $totale_lordo]));
            $stmt_fattura->bind_param('ssisdddd', $numero_fattura, $data_fattura, $id_fornitore_int, $stato_fattura, $percorso_allegato, $totale_imponibile, $totale_iva, $totale_lordo);
            $exec_success = $stmt_fattura->execute();
            if ($exec_success === false) {
                throw new mysqli_sql_exception("Errore nell'esecuzione dell'inserimento fattura: " . $stmt_fattura->error);
            }
            $id_fattura = $conn->insert_id;
            $stmt_fattura->close();
            error_log("PHP_DEBUG: New invoice inserted successfully. ID: " . $id_fattura);
        }

        // Check if an invoice ID was actually generated/determined
        if ($id_fattura === 0) {
            throw new Exception("Nessun ID fattura valido per l'operazione. Possibile errore di inserimento o duplicato (es. chiave unica su numero_fattura).");
        }
        error_log("PHP_DEBUG: Invoice ID for details processing: " . $id_fattura);


        // 3. Dettagli Prodotti
        $product_lines_json = $_POST['productLinesData'] ?? '[]';
        $product_lines = json_decode($product_lines_json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Errore nella decodifica dei dati delle linee prodotto: " . json_last_error_msg() . " JSON ricevuto: " . $product_lines_json);
        }
        error_log("PHP_DEBUG: Decoded product lines for insertion: " . print_r($product_lines, true));


        foreach ($product_lines as $index => $line) {
            $prodotto_id = $line['productId'] ?? null;
            $descrizione_prodotto = $line['productName'] ?? '';
            $quantita = $line['quantity'] ?? 0;
            $unita_misura = $line['unitMeasure'] ?? 'pz';
            $prezzo_unitario_netto = $line['priceNet'] ?? 0;
            $iva_percentuale = $line['vatRate'] ?? 22;
            $prodotto_senza_iva = $line['isVatExempt'] ?? false;

            // Calcola prezzo_unitario_lordo
            $prezzo_unitario_lordo = $prodotto_senza_iva ? $prezzo_unitario_netto : $prezzo_unitario_netto * (1 + ($iva_percentuale / 100));

            // Calcola totale_riga_netto e totale_riga_lordo
            $totale_riga_netto = $quantita * $prezzo_unitario_netto;
            $totale_riga_lordo = $quantita * $prezzo_unitario_lordo;

            // Log per debugging dettagli prodotto
            error_log("PHP_DEBUG: Dettaglio prodotto riga " . ($index + 1) . ": Prodotto ID: {$prodotto_id}, Descrizione: {$descrizione_prodotto}, Quantità: {$quantita}, UM: {$unita_misura}, Prezzo Netto: {$prezzo_unitario_netto}, IVA%: {$iva_percentuale}, Prezzo Lordo Unitario: {$prezzo_unitario_lordo}, Totale Riga Netto: {$totale_riga_netto}, Totale Riga Lordo: {$totale_riga_lordo}, Senza IVA: " . ($prodotto_senza_iva ? 'Si' : 'No'));

            // Questa parte è per l'aggiornamento del magazzino se la fattura è 'Registrata'.
            if (!empty($prodotto_id) && $stato_fattura === 'Registrata') {
                $stmt_update_stock = $conn->prepare("UPDATE prodotti SET quantita = quantita + ? WHERE id = ?");
                if ($stmt_update_stock === false) {
                    throw new mysqli_sql_exception("Errore nella preparazione della query di aggiornamento magazzino: " . $conn->error);
                }
                error_log("PHP_DEBUG: Binding parameters for UPDATE prodotti (stock): Types='ii', Values=" . json_encode([$quantita, $prodotto_id]));
                $stmt_update_stock->bind_param('ii', $quantita, $prodotto_id);
                $exec_success_stock = $stmt_update_stock->execute();
                if ($exec_success_stock === false) {
                    throw new mysqli_sql_exception("Errore nell'esecuzione dell'aggiornamento magazzino per prodotto ID {$prodotto_id}: " . $stmt_update_stock->error);
                }
                $stmt_update_stock->close();
                error_log("PHP_DEBUG: Stock updated for product ID: " . $prodotto_id);
            }


            // Inserimento Dettaglio Fattura
            $stmt_dettaglio = $conn->prepare("INSERT INTO dettagli_fattura (fattura_id, prodotto_id, descrizione_prodotto, quantita, unita_misura, prezzo_unitario_netto, iva_percentuale, prezzo_unitario_lordo, totale_riga_netto, totale_riga_lordo, prodotto_senza_iva) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_dettaglio === false) {
                throw new mysqli_sql_exception("Errore nella preparazione della query di inserimento dettaglio fattura: " . $conn->error);
            }
            $prodotto_senza_iva_int = $prodotto_senza_iva ? 1 : 0;
            // 'iisdsdddddi' -> int, int, string, decimal, string, decimal, decimal, decimal, decimal, decimal, tinyint
            error_log("PHP_DEBUG: Binding parameters for INSERT dettagli_fattura: Types='iisdsdddddi', Values=" . json_encode([$id_fattura, $prodotto_id, $descrizione_prodotto, $quantita, $unita_misura, $prezzo_unitario_netto, $iva_percentuale, $prezzo_unitario_lordo, $totale_riga_netto, $totale_riga_lordo, $prodotto_senza_iva_int]));

            $stmt_dettaglio->bind_param('iisdsdddddi', $id_fattura, $prodotto_id, $descrizione_prodotto, $quantita, $unita_misura, $prezzo_unitario_netto, $iva_percentuale, $prezzo_unitario_lordo, $totale_riga_netto, $totale_riga_lordo, $prodotto_senza_iva_int);
            $exec_success_detail = $stmt_dettaglio->execute();
            if ($exec_success_detail === false) {
                throw new mysqli_sql_exception("Errore nell'esecuzione dell'inserimento dettaglio fattura per prodotto ID {$prodotto_id}: " . $stmt_dettaglio->error);
            }
            error_log("PHP_DEBUG: Dettaglio fattura inserito con successo per riga " . ($index + 1));
            $stmt_dettaglio->close();

            // Aggiorna i totali della fattura
            // Questi totali sono per la tabella `fatture`, non per i totali di riga nella tabella `dettagli_fattura`
            $totale_imponibile += $totale_riga_netto;
            $totale_iva += ($totale_riga_lordo - $totale_riga_netto);
            $totale_lordo += $totale_riga_lordo;
        }

        // Aggiorna i totali finali nella tabella fatture
        $stmt_update_totals = $conn->prepare("UPDATE fatture SET totale_imponibile = ?, totale_iva = ?, totale_lordo = ? WHERE id = ?");
        if ($stmt_update_totals === false) {
            throw new mysqli_sql_exception("Errore nella preparazione della query di aggiornamento totali fattura: " . $conn->error);
        }
        error_log("PHP_DEBUG: Binding parameters for UPDATE fatture (totals): Types='dddi', Values=" . json_encode([$totale_imponibile, $totale_iva, $totale_lordo, $id_fattura]));
        $stmt_update_totals->bind_param('dddi', $totale_imponibile, $totale_iva, $totale_lordo, $id_fattura);
        $exec_success_totals = $stmt_update_totals->execute();
        if ($exec_success_totals === false) {
            throw new mysqli_sql_exception("Errore nell'esecuzione dell'aggiornamento totali fattura per fattura ID {$id_fattura}: " . $stmt_update_totals->error);
        }
        $stmt_update_totals->close();

        // Commit della transazione
        mysqli_commit($conn);
        error_log("PHP_DEBUG: Database transaction committed successfully.");

        // Imposta il messaggio di successo in sessione e reindirizza
        $_SESSION['message'] = 'Fattura e dettagli salvati con successo!';
        $_SESSION['isError'] = false;
        header("Location: visualizza_fatture.php"); // Reindirizza alla pagina di visualizzazione
        exit(); // Termina lo script dopo il reindirizzamento

    } catch (mysqli_sql_exception $e) {
        // Rollback della transazione
        mysqli_rollback($conn);
        $errorMessage = "Errore database durante il salvataggio: " . $e->getMessage();
        $_SESSION['message'] = $errorMessage;
        $_SESSION['isError'] = true;
        error_log("ERRORE SALVATAGGIO FATTURA (SQL): " . $e->getMessage() . " - Stack: " . $e->getTraceAsString()); // Log completo dell'errore SQL
        // Reindirizza alla pagina precedente o a se stessa, mantenendo l'ID della fattura se presente
        $redirect_url = 'gestione_fatture.php';
        if ($invoice_id_to_update) {
            $redirect_url .= '?id=' . $invoice_id_to_update;
        }
        header("Location: " . $redirect_url);
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn); // Rollback anche per errori non SQL
        $errorMessage = "Errore generale durante il salvataggio: " . $e->getMessage();
        $_SESSION['message'] = $errorMessage;
        $_SESSION['isError'] = true;
        error_log("ERRORE GENERALE SALVATAGGIO FATTURA: " . $e->getMessage() . " - Stack: " . $e->getTraceAsString()); // Log completo dell'errore generale
        // Reindirizza alla pagina precedente o a se stessa, mantenendo l'ID della fattura se presente
        $redirect_url = 'gestione_fatture.php';
        if ($invoice_id_to_update) {
            $redirect_url .= '?id=' . $invoice_id_to_update;
        }
        header("Location: " . $redirect_url);
        exit();
    }
}

// --- RECUPERO DATI PER LE SELECT E AUTOCONFIGURAZIONE JS (SEMPPRE ESEGUITO) ---
$fornitori = [];
try {
    // Seleziona anche gli altri campi dal DB per il popolamento futuro o debug.
    // Anche se solo 'id' e 'ragione_sociale' vengono usati per l'autocomplete iniziale,
    // è buona pratica recuperarli tutti se la struttura lo prevede.
    $result_fornitori = $conn->query("SELECT id, nome, ragione_sociale, partita_iva, codice_fiscale, indirizzo, citta, cap, provincia, paese, telefono, email FROM fornitori ORDER BY ragione_sociale");
    if ($result_fornitori) {
        $fornitori = $result_fornitori->fetch_all(MYSQLI_ASSOC);
        $result_fornitori->free();
    }
} catch (mysqli_sql_exception $e) {
    // In questo caso, non reindirizziamo ma usiamo showMessage se la pagina si sta caricando normalmente
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Errore nel caricamento fornitori: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', true); });</script>";
    error_log("Errore nel caricamento fornitori: " . $e->getMessage());
}

$prodotti_esistenti = [];
try {
    // Aggiungi prezzo_vendita1 e prezzo_vendita2 al recupero prodotti
    $result_prodotti = $conn->query("SELECT id, nome, categoria, prezzo_acquisto, quantita, barcode, prezzo_vendita1, prezzo_vendita2 FROM prodotti ORDER BY nome");
    if ($result_prodotti) {
        $prodotti_raw = $result_prodotti->fetch_all(MYSQLI_ASSOC);
        $result_prodotti->free();

        foreach($prodotti_raw as $p) {
            $prodotti_esistenti[] = [
                'id' => $p['id'],
                'name' => $p['nome'],
                'category' => $p['categoria'],
                'priceNet' => (float)$p['prezzo_acquisto'],
                'code' => $p['barcode'],
                'um' => 'pz',
                'priceSale1' => (float)($p['prezzo_vendita1'] ?? 0.00), // Gestisci NULL con 0.00
                'priceSale2' => (float)($p['prezzo_vendita2'] ?? 0.00)  // Gestisci NULL con 0.00
            ];
        }
    }

} catch (mysqli_sql_exception $e) {
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Errore nel caricamento prodotti: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', true); });</script>";
    error_log("Errore nel caricamento prodotti: " . $e->getMessage());
}

$categorie_prodotti = [];
try {
    $result_categorie = $conn->query("SELECT id, nome FROM categorie ORDER BY nome");
    if ($result_categorie) {
        $raw_categories = $result_categorie->fetch_all(MYSQLI_ASSOC);
        foreach ($raw_categories as $cat) {
            $categorie_prodotti[] = [
                'id_categoria' => $cat['id'],
                'nome_categoria' => $cat['nome']
            ];
        }
        $result_categorie->free();
    }
} catch (mysqli_sql_exception $e) {
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Errore nel caricamento categorie: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', true); });</script>";
    error_log("Errore nel caricamento categorie: " . $e->getMessage());
}

// Rimosso $display_user_id in quanto non più usato nella UI
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Fatture di Acquisto</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icone (Heroicons via CDN) -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <style>
        /* UI/UX IMPROVEMENT: Definizione di una palette di colori e stili base */
        :root {
            --theme-primary: #22c55e; /* Verde brillante */
            --theme-primary-hover: #16a34a;
            --theme-secondary: #64748b;
            --theme-secondary-hover: #475569;
            --theme-danger: #ef4444;
            --theme-danger-hover: #dc2626;
            --theme-background: #f1f5f9; /* Grigio chiarissimo */
            --theme-card-background: #ffffff;
            --theme-text-primary: #1e293b;
            --theme-text-secondary: #64748b;
            --theme-border: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--theme-background);
            color: var(--theme-text-primary);
            padding-top: 80px; /* Spazio per la top-bar fissa */
        }

        /* UI/UX IMPROVEMENT: Stile moderno per la top-bar */
        .top-bar {
            background-color: var(--theme-card-background);
            color: var(--theme-text-primary);
            padding: 0 2rem;
            height: 80px;
            width: 100%;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            border-bottom: 1px solid var(--theme-border);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .logo {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--theme-primary);
            text-decoration: none;
        }
        nav ul { list-style: none; margin: 0; padding: 0; display: flex; gap: 0.5rem; }
        nav ul li a, nav ul li button {
            color: var(--theme-text-secondary);
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease-in-out;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 2px solid transparent;
        }
        nav ul li a:hover, nav ul li button:hover {
            color: var(--theme-primary);
            background-color: #e2e8f0;
        }
        nav ul li a.active-link { /* Stile per il link attivo */
            color: var(--theme-primary);
            font-weight: 600;
            border-bottom: 2px solid var(--theme-primary);
            border-radius: 0;
        }
        /* Dropdown (Stili base, potrebbero richiedere JS per una migliore interazione on click) */
        nav ul li ul.dropdown {
            display: none; position: absolute; background-color: white; min-width: 200px;
            border-radius: 0.5rem; box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            padding: 0.5rem; margin-top: 0.5rem; list-style: none; z-index: 1001;
        }
        nav ul li:hover > ul.dropdown { display: block; }
        nav ul li ul.dropdown li a { width: 100%; }

        /* UI/UX IMPROVEMENT: Stili per i contenitori principali e le card */
        .page-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .card {
            background-color: var(--theme-card-background);
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--theme-border);
        }
        .card-header {
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--theme-border);
        }
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--theme-text-primary);
        }

        /* UI/UX IMPROVEMENT: Stili per form, input e bottoni */
        input, select, textarea {
            padding: 0.75rem;
            border: 1px solid var(--theme-border);
            border-radius: 0.5rem;
            width: 100%;
            box-sizing: border-box;
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--theme-primary);
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
            background-color: white;
        }
        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--theme-text-secondary);
            margin-bottom: 0.5rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-primary { background-color: var(--theme-primary); color: white; }
        .btn-primary:hover { background-color: var(--theme-primary-hover); }
        .btn-secondary { background-color: var(--theme-secondary); color: white; }
        .btn-secondary:hover { background-color: var(--theme-secondary-hover); }
        .btn-danger { background-color: var(--theme-danger); color: white; }
        .btn-danger:hover { background-color: var(--theme-danger-hover); }
        .btn-outline { background-color: transparent; color: var(--theme-secondary); border: 1px solid var(--theme-border); }
        .btn-outline:hover { background-color: #f1f5f9; color: var(--theme-text-primary); }

        /* UI/UX IMPROVEMENT: Stile migliorato per la tabella prodotti */
        .product-table { min-width: 100%; border-collapse: separate; border-spacing: 0; }
        .product-table th {
            padding: 1rem; text-align: left;
            background-color: #f8fafc;
            font-weight: 600; color: var(--theme-text-secondary);
            border-bottom: 1px solid var(--theme-border);
        }
        .product-table th:first-child { border-top-left-radius: 0.75rem; }
        .product-table th:last-child { border-top-right-radius: 0.75rem; }
        .product-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--theme-border);
            vertical-align: middle;
        }
        .product-table tbody tr:last-child td { border-bottom: none; }
        .product-table tbody tr:hover { background-color: #f8fafc; }
        .product-table input, .product-table select { padding: 0.5rem; }
        .product-table .remove-line-btn {
            background: transparent; border: none; color: var(--theme-text-secondary);
            padding: 0.25rem; border-radius: 99px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .product-table .remove-line-btn:hover { background-color: #fee2e2; color: var(--theme-danger); }

        /* UI/UX IMPROVEMENT: Stili per Autocomplete e Modali */
        .autocomplete-list-overlay {
            list-style: none; padding: 0.5rem; margin-top: 0.25rem;
            background-color: white; border: 1px solid var(--theme-border);
            border-radius: 0.5rem; max-height: 200px; overflow-y: auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 10000;
        }
        .autocomplete-list-overlay li {
            padding: 0.5rem 0.75rem; cursor: pointer; border-radius: 0.375rem;
        }
        .autocomplete-list-overlay li:hover { background-color: #f1f5f9; color: var(--theme-primary); }
        .modal {
            display: none; /* Default state: hidden. JS will change this to 'flex' to show it. */
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(30, 41, 59, 0.5);
            /* These flexbox properties will center the content when the modal is displayed */
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: var(--theme-card-background);
            padding: 2rem; /* Reduced padding */
            border-radius: 0.5rem; /* Less rounded corners */
            width: 90%;
            max-width: 550px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            position: relative;
            max-height: 90vh; /* Add max-height to prevent overflow */
            overflow-y: auto; /* Add vertical scroll if content is too long */
        }
        #addSupplierModal .modal-content { max-width: 750px; }
        .close-button {
            position: absolute; top: 1rem; right: 1rem; font-size: 1.5rem;
            cursor: pointer; color: var(--theme-text-secondary);
        }
        .close-button:hover { color: var(--theme-text-primary); }

        /* UI/UX IMPROVEMENT: Stile per il Message Box */
        .message-box {
            position: fixed; top: 90px; /* Sotto la top-bar */
            left: 50%; transform: translateX(-50%);
            background-color: var(--theme-primary); color: white;
            padding: 1rem 1.5rem; border-radius: 0.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1100; font-size: 1rem; font-weight: 500;
            display: none; animation: fadeIn 0.5s ease-out forwards, fadeOut 0.5s forwards 3s;
        }
        .message-box.error { background-color: var(--theme-danger); }
        @keyframes fadeIn {
            from { opacity: 0; transform: translate(-50%, -20px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }

    </style>
</head>
<body>
    <?php include 'header.php'; // Includi la barra di navigazione ?>

    <div class="page-container">
        <?php echo $message; // Mostra messaggi di sistema ?>

        <form action="gestione_fatture.php" method="POST" enctype="multipart/form-data" id="invoiceForm">
            <!-- UI/UX IMPROVEMENT: Header di pagina con titolo e azioni principali -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <?php echo $invoice_to_edit ? 'Modifica Fattura' : 'Nuova Fattura di Acquisto'; ?>
                    </h1>
                    <p class="text-gray-500 mt-1">
                        <?php echo $invoice_to_edit ? 'Aggiorna i dettagli della fattura e i prodotti.' : 'Compila i campi per registrare una nuova fattura.'; ?>
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <button type="button" id="cancelBtn" class="btn btn-outline">Annulla</button>
                    <button type="submit" form="invoiceForm" id="saveInvoiceBtn" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                          <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l7-7a1 1 0 10-1.414-1.414L10 12.586l-2.293-2.293z" />
                          <path fill-rule="evenodd" d="M2 5a3 3 0 013-3h10a3 3 0 013 3v10a3 3 0 01-3 3H5a3 3 0 01-3-3V5zm3-1a1 1 0 00-1 1v10a1 1 0 001 1h10a1 1 0 001-1V5a1 1 0 00-1-1H5z" clip-rule="evenodd" />
                        </svg>
                        <span>Salva Fattura</span>
                    </button>
                </div>
            </div>

            <!-- Sezione Fattura Generale -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Dettagli Fattura</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <label for="invoiceNumber">Numero Fattura</label>
                        <input type="text" id="invoiceNumber" name="invoiceNumber" placeholder="Es. 2024/123" required value="<?php echo htmlspecialchars($invoice_to_edit['numero_fattura'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div>
                        <label for="invoiceDate">Data Fattura</label>
                        <input type="date" id="invoiceDate" name="invoiceDate" required value="<?php echo htmlspecialchars($invoice_to_edit['data_fattura'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="lg:col-span-2">
                        <label for="supplierName">Fornitore</label>
                        <div class="flex items-center space-x-2">
                            <input type="text" id="supplierName" placeholder="Cerca o aggiungi fornitore" class="flex-grow" value="<?php echo htmlspecialchars($invoice_to_edit['nome_fornitore'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" id="supplierId" name="supplierId" required value="<?php echo htmlspecialchars($invoice_to_edit['fornitore_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="button" id="addSupplierBtn" class="btn btn-outline text-sm px-3 py-2">Nuovo</button>
                        </div>
                    </div>

                    <div>
                        <label for="invoiceStatus">Stato Fattura</label>
                        <select id="invoiceStatus" name="invoiceStatus" required>
                            <option value="Da Verificare" <?php echo (isset($invoice_to_edit['stato']) && $invoice_to_edit['stato'] == 'Da Verificare') ? 'selected' : ''; ?>>Da Verificare</option>
                            <option value="Registrata" <?php echo (isset($invoice_to_edit['stato']) && $invoice_to_edit['stato'] == 'Registrata') ? 'selected' : ''; ?>>Registrata</option>
                            <option value="Pagata" <?php echo (isset($invoice_to_edit['stato']) && $invoice_to_edit['stato'] == 'Pagata') ? 'selected' : ''; ?>>Pagata</option>
                        </select>
                    </div>

                    <div class="lg:col-span-3">
                        <label for="invoiceAttachment">Allegato Fattura (.pdf, .jpg, .png)</label>
                        <input type="file" id="invoiceAttachment" name="invoiceAttachment" accept=".pdf, .jpg, .jpeg, .png" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100 cursor-pointer">
                        <p id="attachmentName" class="text-sm text-gray-600 mt-2">
                            <?php if (isset($invoice_to_edit['allegato_url']) && !empty($invoice_to_edit['allegato_url'])): ?>
                                File attuale: <a href="<?php echo htmlspecialchars($invoice_to_edit['allegato_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="text-green-600 hover:underline"><?php echo basename($invoice_to_edit['allegato_url']); ?></a>
                            <?php endif; ?>
                        </p>
                        <?php if ($invoice_to_edit): ?>
                            <input type="hidden" name="invoiceId" value="<?php echo htmlspecialchars($invoice_to_edit['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sezione Dettaglio Prodotti -->
            <div class="card">
                 <div class="card-header flex justify-between items-center">
                    <h2 class="card-title">Dettaglio Prodotti</h2>
                    <div class="flex items-center space-x-3">
                         <button type="button" id="addNewProductBtn" class="btn btn-outline text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                             <span>Nuovo Prodotto in Catalogo</span>
                         </button>
                         <button type="button" id="addProductLineBtn" class="btn btn-primary text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>
                             <span>Aggiungi Riga</span>
                         </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th class="w-2/5">Prodotto</th>
                                <th>Categoria</th>
                                <th>Qtà</th>
                                <th>UM</th>
                                <th>Prezzo Netto</th>
                                <th>IVA</th>
                                <th>Es.</th>
                                <th>Prezzo Lordo</th>
                                <th>Totale Riga</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="productLines">
                            <!-- Le righe dei prodotti verranno aggiunte qui via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sezione Riepilogo e Azioni Finali -->
             <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                 <div class="lg:col-span-2 card">
                    <div class="card-header">
                        <h2 class="card-title">Riepilogo Totali</h2>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center sm:text-right">
                        <div>
                            <label>Totale Imponibile</label>
                            <p id="totalNet" class="text-xl font-semibold text-gray-800">0.00 €</p>
                        </div>
                        <div>
                            <label>Totale IVA</label>
                            <p id="totalVATP" class="text-xl font-semibold text-gray-800">0.00 €</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <label>Totale Fattura</label>
                            <p id="totalGrossP" class="text-3xl font-bold text-green-600">0.00 €</p>
                        </div>
                    </div>
                 </div>
                 <!-- Spazio per azioni o altre informazioni -->
             </div>
            <textarea id="productLinesData" name="productLinesData" class="hidden"></textarea>
        </form>
    </div>

    <!-- Modale Aggiungi Fornitore -->
    <div id="addSupplierModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeSupplierModal">&times;</span>
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Aggiungi Nuovo Fornitore</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="newSupplierName">Nome/Ragione Sociale</label>
                    <input type="text" id="newSupplierName" required>
                </div>
                <div>
                    <label for="newSupplierPartitaIva">Partita IVA</label>
                    <input type="text" id="newSupplierPartitaIva">
                </div>
                <div>
                    <label for="newSupplierCodiceFiscale">Codice Fiscale</label>
                    <input type="text" id="newSupplierCodiceFiscale">
                </div>
                <div class="sm:col-span-2">
                    <label for="newSupplierAddress">Indirizzo</label>
                    <input type="text" id="newSupplierAddress">
                </div>
                <div>
                    <label for="newSupplierCity">Città</label>
                    <input type="text" id="newSupplierCity">
                </div>
                <div>
                    <label for="newSupplierCap">CAP</label>
                    <input type="text" id="newSupplierCap">
                </div>
                <div>
                    <label for="newSupplierProvince">Provincia</label>
                    <input type="text" id="newSupplierProvince">
                </div>
                <div>
                    <label for="newSupplierCountry">Paese</label>
                    <input type="text" id="newSupplierCountry">
                </div>
                <div>
                    <label for="newSupplierPhone">Telefono</label>
                    <input type="text" id="newSupplierPhone">
                </div>
                <div>
                    <label for="newSupplierEmail">Email</label>
                    <input type="email" id="newSupplierEmail">
                </div>
                <div class="sm:col-span-2 flex justify-end mt-4">
                    <button type="button" id="saveNewSupplierBtn" class="btn btn-primary">Salva Fornitore</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale Aggiungi Prodotto -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeProductModal">&times;</span>
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Aggiungi Nuovo Prodotto al Catalogo</h3>
            <div class="space-y-4">
                <div>
                    <label for="newProductName">Nome Prodotto</label>
                    <input type="text" id="newProductName" required>
                </div>
                <div>
                    <label for="newProductCode">Codice (Barcode)</label>
                    <input type="text" id="newProductCode">
                </div>
                <div>
                    <label for="newProductCategory">Categoria</label>
                    <select id="newProductCategory" required>
                        <option value="">Seleziona Categoria</option>
                        <?php foreach ($categorie_prodotti as $cat) : ?>
                            <option value="<?php echo htmlspecialchars($cat['id_categoria']); ?>"><?php echo htmlspecialchars($cat['nome_categoria']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="newProductPriceNet">Prezzo Acquisto Netto</label>
                    <input type="number" step="0.01" id="newProductPriceNet" min="0" required>
                </div>
                <div>
                    <label for="newProductPriceSale1">Prezzo Vendita 1</label>
                    <input type="number" step="0.01" id="newProductPriceSale1" min="0">
                </div>
                <div>
                    <label for="newProductPriceSale2">Prezzo Vendita 2</label>
                    <input type="number" step="0.01" id="newProductPriceSale2" min="0">
                </div>
                <div>
                    <label for="newProductQuantity">Quantità Iniziale (Magazzino)</label>
                    <input type="number" step="1" id="newProductQuantity" min="0" required>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" id="saveNewProductBtn" class="btn btn-primary">Salva Prodotto</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Box for notifications -->
    <div id="messageBox" class="message-box"></div>

    <script>
        // Data passed from PHP to JavaScript
        const initialFornitori = <?php echo json_encode($fornitori); ?>;
        const initialProdottiEsistenti = <?php echo json_encode($prodotti_esistenti); ?>;
        const initialCategorieProdotti = <?php echo json_encode($categorie_prodotti); ?>;
        const invoiceToEditData = <?php echo json_encode($invoice_to_edit); ?>;
        const invoiceDetailsToLoad = <?php echo json_encode($invoice_details_to_edit); ?>;

        // --- Data ---
        let suppliers = initialFornitori;
        let products = initialProdottiEsistenti;
        let categories = initialCategorieProdotti;

        console.log("Prodotti caricati da PHP:", initialProdottiEsistenti);
        console.log("Categorie caricate da PHP:", categories);

        // Mock data for autocomplete (placeholder)
        const mockCities = [ { name: 'Roma' }, { name: 'Milano' }, { name: 'Napoli' } ];
        const mockCaps = [ { code: '00100' }, { code: '20121' }, { code: '80134' } ];

        let currentInvoice = {
            invoiceNumber: '', invoiceDate: '', supplierId: '', supplierName: '',
            status: 'Da Verificare', attachment: null, productLines: []
        };

        if (invoiceToEditData) {
            currentInvoice.invoiceNumber = invoiceToEditData.numero_fattura || '';
            currentInvoice.invoiceDate = invoiceToEditData.data_fattura || '';
            currentInvoice.supplierId = invoiceToEditData.fornitore_id || '';
            currentInvoice.supplierName = invoiceToEditData.nome_fornitore || '';
            currentInvoice.status = invoiceToEditData.stato || 'Da Verificare';
            let tempLineCounter = 0;
            currentInvoice.productLines = invoiceDetailsToLoad.map(detail => ({
                tempId: 'line-' + tempLineCounter++,
                productId: detail.prodotto_id || '',
                productName: detail.descrizione_prodotto || '',
                category: categories.find(cat => cat.id_categoria == detail.categoria)?.nome_categoria || detail.categoria || '',
                quantity: parseFloat(detail.quantita) || 0,
                unitMeasure: detail.unita_misura || 'pz',
                priceNet: parseFloat(detail.prezzo_unitario_netto) || 0,
                vatRate: parseFloat(detail.iva_percentuale) || 0,
                isVatExempt: detail.prodotto_senza_iva == 1,
                priceGross: parseFloat(detail.prezzo_unitario_lordo) || 0,
                lineTotal: parseFloat(detail.totale_riga_lordo) || 0,
                _previousVatRate: parseFloat(detail.iva_percentuale) || 0
            }));
            console.log("Current Invoice loaded from PHP:", currentInvoice);
        }

        // --- DOM Elements ---
        const invoiceForm = document.getElementById('invoiceForm');
        const invoiceNumberInput = document.getElementById('invoiceNumber');
        const invoiceDateInput = document.getElementById('invoiceDate');
        const supplierNameInput = document.getElementById('supplierName');
        const supplierIdInput = document.getElementById('supplierId');
        const addSupplierBtn = document.getElementById('addSupplierBtn');
        const invoiceStatusSelect = document.getElementById('invoiceStatus');
        const invoiceAttachmentInput = document.getElementById('invoiceAttachment');
        const attachmentNameP = document.getElementById('attachmentName');
        const productLinesBody = document.getElementById('productLines');
        const addProductLineBtn = document.getElementById('addProductLineBtn');
        const addNewProductBtn = document.getElementById('addNewProductBtn');
        const totalNetP = document.getElementById('totalNet');
        const totalVATP = document.getElementById('totalVATP');
        const totalGrossP = document.getElementById('totalGrossP');
        const cancelBtn = document.getElementById('cancelBtn');
        const saveInvoiceBtn = document.getElementById('saveInvoiceBtn');
        const productLinesDataInput = document.getElementById('productLinesData');
        const addSupplierModal = document.getElementById('addSupplierModal');
        const closeSupplierModalBtn = document.getElementById('closeSupplierModal');
        const newSupplierNameInput = document.getElementById('newSupplierName');
        const newSupplierPartitaIvaInput = document.getElementById('newSupplierPartitaIva');
        const newSupplierCodiceFiscaleInput = document.getElementById('newSupplierCodiceFiscale');
        const newSupplierAddressInput = document.getElementById('newSupplierAddress');
        const newSupplierCityInput = document.getElementById('newSupplierCity');
        const newSupplierCapInput = document.getElementById('newSupplierCap');
        const newSupplierProvinceInput = document.getElementById('newSupplierProvince');
        const newSupplierCountryInput = document.getElementById('newSupplierCountry');
        const newSupplierPhoneInput = document.getElementById('newSupplierPhone');
        const newSupplierEmailInput = document.getElementById('newSupplierEmail');
        const saveNewSupplierBtn = document.getElementById('saveNewSupplierBtn');
        const addProductModal = document.getElementById('addProductModal');
        const closeProductModalBtn = document.getElementById('closeProductModal');
        const newProductNameInput = document.getElementById('newProductName');
        const newProductCodeInput = document.getElementById('newProductCode');
        const newProductCategorySelect = document.getElementById('newProductCategory');
        const newProductPriceNetInput = document.getElementById('newProductPriceNet');
        const newProductPriceSale1Input = document.getElementById('newProductPriceSale1');
        const newProductPriceSale2Input = document.getElementById('newProductPriceSale2');
        const newProductQuantityInput = document.getElementById('newProductQuantity');
        const saveNewProductBtn = document.getElementById('saveNewProductBtn');
        const messageBox = document.getElementById('messageBox');

        // --- Utility Functions ---
        function showMessage(message, isError = false) {
            messageBox.textContent = message;
            messageBox.className = 'message-box'; // Reset classes
            if (isError) messageBox.classList.add('error');
            messageBox.style.animation = 'none';
            void messageBox.offsetWidth;
            messageBox.style.animation = null;
            messageBox.style.display = 'block';
        }

        function formatCurrency(value) {
            return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(value);
        }

        function updateTotals() {
            let totalNet = 0, totalVAT = 0, totalGross = 0;
            currentInvoice.productLines.forEach(line => {
                const quantity = parseFloat(line.quantity || 0);
                const priceNet = parseFloat(line.priceNet || 0);
                const vatRate = parseFloat(line.vatRate || 0);
                const lineNet = quantity * priceNet;
                const lineVAT = lineNet * (vatRate / 100);
                const lineGross = lineNet + lineVAT;
                totalNet += lineNet;
                totalVAT += lineVAT;
                totalGross += lineGross;
                line.priceGross = (quantity > 0) ? (lineGross / quantity).toFixed(2) : '0.00';
                line.lineTotal = lineGross.toFixed(2);
            });
            totalNetP.textContent = formatCurrency(totalNet);
            totalVATP.textContent = formatCurrency(totalVAT);
            totalGrossP.textContent = formatCurrency(totalGross);
        }

        function setupAutocomplete(inputElement, dataArray, displayProperty, selectCallback) {
            let currentAutocompleteList = null;
            const hideAutocomplete = () => { if (currentAutocompleteList) { currentAutocompleteList.remove(); currentAutocompleteList = null; } };

            inputElement.addEventListener('input', () => {
                hideAutocomplete();
                const searchTerm = inputElement.value.toLowerCase();
                if (searchTerm.length < 2) return;

                const filteredData = dataArray.filter(item => displayProperty(item).toLowerCase().includes(searchTerm));
                if (filteredData.length === 0) return;

                const ul = document.createElement('ul');
                ul.className = 'autocomplete-list-overlay';
                filteredData.forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = displayProperty(item);
                    li.dataset.item = JSON.stringify(item);
                    li.addEventListener('click', (event) => {
                        event.stopPropagation();
                        selectCallback(item);
                        hideAutocomplete();
                    });
                    ul.appendChild(li);
                });

                document.body.appendChild(ul);
                const inputRect = inputElement.getBoundingClientRect();
                ul.style.position = 'absolute';
                ul.style.top = `${inputRect.bottom + window.scrollY}px`;
                ul.style.left = `${inputRect.left + window.scrollX}px`;
                ul.style.width = `${inputRect.width}px`;
                currentAutocompleteList = ul;
            });

            inputElement.addEventListener('blur', () => setTimeout(() => { if (currentAutocompleteList && !currentAutocompleteList.contains(document.activeElement)) hideAutocomplete(); }, 150));
            document.addEventListener('click', (event) => { if (currentAutocompleteList && !inputElement.contains(event.target) && !currentAutocompleteList.contains(event.target)) hideAutocomplete(); });
            window.addEventListener('scroll', () => { if (currentAutocompleteList && inputElement === document.activeElement) { const rect = inputElement.getBoundingClientRect(); currentAutocompleteList.style.top = `${rect.bottom + window.scrollY}px`; currentAutocompleteList.style.left = `${rect.left + window.scrollX}px`; } });
            window.addEventListener('resize', () => { if (currentAutocompleteList) { const rect = inputElement.getBoundingClientRect(); currentAutocompleteList.style.top = `${rect.bottom + window.scrollY}px`; currentAutocompleteList.style.left = `${rect.left + window.scrollX}px`; currentAutocompleteList.style.width = `${rect.width}px`; } });
        }

        function populateCategorySelects() {
            newProductCategorySelect.innerHTML = '<option value="">Seleziona Categoria</option>';
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id_categoria;
                option.textContent = category.nome_categoria;
                newProductCategorySelect.appendChild(option);
            });
            renderProductLines();
        }

        // --- Modal Management ---
        const openModal = (modalId) => document.getElementById(modalId).style.display = 'flex';
        const closeModal = (modalId) => document.getElementById(modalId).style.display = 'none';

        addSupplierBtn.addEventListener('click', () => {
            newSupplierNameInput.value = ''; newSupplierPartitaIvaInput.value = ''; newSupplierCodiceFiscaleInput.value = '';
            newSupplierAddressInput.value = ''; newSupplierCityInput.value = ''; newSupplierCapInput.value = '';
            newSupplierProvinceInput.value = ''; newSupplierCountryInput.value = ''; newSupplierPhoneInput.value = '';
            newSupplierEmailInput.value = '';
            openModal('addSupplierModal');
        });
        closeSupplierModalBtn.addEventListener('click', () => closeModal('addSupplierModal'));
        saveNewSupplierBtn.addEventListener('click', async () => {
            const name = newSupplierNameInput.value.trim();
            if (!name) { showMessage("Nome/Ragione Sociale è obbligatorio.", true); return; }
            try {
                const formData = new FormData();
                formData.append('name', name);
                formData.append('partitaIva', newSupplierPartitaIvaInput.value.trim());
                formData.append('codiceFiscale', newSupplierCodiceFiscaleInput.value.trim());
                formData.append('address', newSupplierAddressInput.value.trim());
                formData.append('city', newSupplierCityInput.value.trim());
                formData.append('cap', newSupplierCapInput.value.trim());
                formData.append('province', newSupplierProvinceInput.value.trim());
                formData.append('country', newSupplierCountryInput.value.trim());
                formData.append('phone', newSupplierPhoneInput.value.trim());
                formData.append('email', newSupplierEmailInput.value.trim());
                formData.append('action', 'add_new_supplier');

                const response = await fetch('gestione_fatture.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const result = await response.json();

                if (result.success) {
                    const newSupplier = { id: result.id, ragione_sociale: result.ragione_sociale };
                    suppliers.push(newSupplier);
                    currentInvoice.supplierId = newSupplier.id;
                    currentInvoice.supplierName = newSupplier.ragione_sociale;
                    supplierNameInput.value = newSupplier.ragione_sociale;
                    supplierIdInput.value = newSupplier.id;
                    showMessage(`Fornitore "${name}" aggiunto e selezionato.`);
                    closeModal('addSupplierModal');
                } else { showMessage(`Errore: ${result.message}`, true); }
            } catch (error) { console.error("Errore aggiunta fornitore:", error); showMessage("Errore di comunicazione con il server.", true); }
        });

        addNewProductBtn.addEventListener('click', () => {
             newProductNameInput.value = ''; newProductCodeInput.value = ''; newProductCategorySelect.value = '';
             newProductPriceNetInput.value = ''; newProductPriceSale1Input.value = ''; newProductPriceSale2Input.value = '';
             newProductQuantityInput.value = '';
             populateCategorySelects();
             openModal('addProductModal');
        });
        closeProductModalBtn.addEventListener('click', () => closeModal('addProductModal'));
        saveNewProductBtn.addEventListener('click', async () => {
            const name = newProductNameInput.value.trim();
            const categoryId = newProductCategorySelect.value;
            const priceNet = newProductPriceNetInput.value.trim();
            const quantity = newProductQuantityInput.value.trim();
            if (!name || !categoryId || priceNet === '' || quantity === '') {
                showMessage("Nome, Categoria, Prezzo Netto e Quantità sono obbligatori.", true);
                return;
            }
            try {
                const formData = new FormData();
                formData.append('name', name);
                formData.append('code', newProductCodeInput.value.trim());
                formData.append('categoryId', categoryId);
                formData.append('priceNet', priceNet);
                formData.append('priceSale1', newProductPriceSale1Input.value.trim());
                formData.append('priceSale2', newProductPriceSale2Input.value.trim());
                formData.append('quantity', quantity);
                formData.append('action', 'add_new_product_to_catalog');

                const response = await fetch('gestione_fatture.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const result = await response.json();

                if (result.success) {
                    const newProduct = {
                        id: result.id_prodotto, name: result.nome_prodotto, code: result.codice_sku || '', um: result.unita_misura || 'pz',
                        category: categories.find(cat => cat.id_categoria == categoryId)?.nome_categoria || 'Sconosciuta',
                        priceNet: parseFloat(result.priceNet), priceSale1: parseFloat(result.priceSale1) || null, priceSale2: parseFloat(result.priceSale2) || null
                    };
                    products.push(newProduct);
                    showMessage(`Prodotto "${name}" aggiunto al catalogo.`);
                    closeModal('addProductModal');
                } else { showMessage(`Errore: ${result.message}`, true); }
            } catch (error) { console.error("Errore aggiunta prodotto:", error); showMessage("Errore di comunicazione con il server.", true); }
        });

        // --- Dynamic Product Line Management ---
        let lineCounter = currentInvoice.productLines.length;
        const addProductLine = (productData = {}) => {
            const newLine = {
                tempId: 'line-' + lineCounter++, productId: productData.id || '', productName: productData.name || '',
                category: productData.category || '', quantity: '', unitMeasure: productData.um || 'pz',
                priceNet: productData.priceNet !== undefined ? productData.priceNet : '', vatRate: productData.vatRate !== undefined ? productData.vatRate : '22',
                isVatExempt: productData.isVatExempt || false, priceGross: '', lineTotal: ''
            };
            currentInvoice.productLines.push(newLine);
            renderProductLines();
        };
        addProductLineBtn.addEventListener('click', () => addProductLine());

        const removeProductLine = (tempId) => {
            currentInvoice.productLines = currentInvoice.productLines.filter(line => line.tempId !== tempId);
            renderProductLines();
            updateTotals();
        };

        function renderProductLines() {
            productLinesBody.innerHTML = '';
            if (currentInvoice.productLines.length === 0) {
                 productLinesBody.innerHTML = `<tr><td colspan="10" class="text-center text-gray-500 py-8">Nessun prodotto aggiunto. Clicca su "Aggiungi Riga" per iniziare.</td></tr>`;
                 return;
            }
            currentInvoice.productLines.forEach((line, index) => {
                const row = document.createElement('tr');
                row.dataset.tempId = line.tempId;

                const categoryOptions = categories.map(cat => `<option value="${cat.nome_categoria}" ${line.category === cat.nome_categoria ? 'selected' : ''}>${cat.nome_categoria}</option>`).join('');

                row.innerHTML = `
                    <td class="relative"><input type="text" value="${line.productName}" placeholder="Cerca prodotto..." class="product-name-input w-full" data-index="${index}"></td>
                    <td><select class="product-category-select w-full" data-index="${index}"><option value="">Seleziona</option>${categoryOptions}</select></td>
                    <td><input type="number" step="any" value="${line.quantity}" class="quantity-input w-20 text-center" data-index="${index}" min="0"></td>
                    <td><input type="text" value="${line.unitMeasure}" class="um-input w-16 text-center" data-index="${index}"></td>
                    <td><input type="number" step="0.01" value="${line.priceNet}" class="price-net-input w-24 text-right" data-index="${index}" min="0"></td>
                    <td>
                        <select class="vat-rate-select w-20" data-index="${index}" ${line.isVatExempt ? 'disabled' : ''}>
                            <option value="0" ${line.vatRate == 0 ? 'selected' : ''}>0%</option> <option value="4" ${line.vatRate == 4 ? 'selected' : ''}>4%</option>
                            <option value="5" ${line.vatRate == 5 ? 'selected' : ''}>5%</option> <option value="10" ${line.vatRate == 10 ? 'selected' : ''}>10%</option>
                            <option value="22" ${line.vatRate == 22 ? 'selected' : ''}>22%</option>
                        </select>
                    </td>
                    <td class="text-center"><input type="checkbox" ${line.isVatExempt ? 'checked' : ''} class="vat-exempt-checkbox h-4 w-4" data-index="${index}"></td>
                    <td><input type="text" value="${formatCurrency(line.priceGross || 0)}" class="price-gross-input w-24 text-right bg-gray-100" data-index="${index}" readonly></td>
                    <td><input type="text" value="${formatCurrency(line.lineTotal || 0)}" class="line-total-input w-28 text-right bg-gray-100 font-semibold" data-index="${index}" readonly></td>
                    <td class="text-center">
                        <button type="button" class="remove-line-btn" data-temp-id="${line.tempId}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        </button>
                    </td>
                `;
                productLinesBody.appendChild(row);

                const productNameInput = row.querySelector('.product-name-input');
                setupAutocomplete(productNameInput, products, item => `${item.name} (${item.category})`, (selectedProduct) => {
                    const lineToUpdate = currentInvoice.productLines[index];
                    lineToUpdate.productId = selectedProduct.id;
                    lineToUpdate.productName = selectedProduct.name;
                    lineToUpdate.category = selectedProduct.category;
                    lineToUpdate.unitMeasure = selectedProduct.um || 'pz';
                    lineToUpdate.priceNet = selectedProduct.priceNet;
                    lineToUpdate.quantity = '';
                    renderProductLines(); // Re-render to update all fields in the row
                });
            });
            updateTotals();
            attachProductLineEventListeners();
        }

        function attachProductLineEventListeners() {
            productLinesBody.querySelectorAll('.remove-line-btn').forEach(btn => btn.onclick = (e) => removeProductLine(e.currentTarget.dataset.tempId));
            productLinesBody.querySelectorAll('input, select').forEach(input => {
                input.oninput = (e) => {
                    const index = parseInt(e.currentTarget.dataset.index);
                    const line = currentInvoice.productLines[index];
                    if (!line) return;
                    const prop = e.currentTarget.className.split(' ')[0].replace('-input', '').replace('-select', '').replace('-checkbox', '');
                    const value = e.currentTarget.type === 'checkbox' ? e.currentTarget.checked : e.currentTarget.value;
                    
                    const keyMap = { 'product-name': 'productName', 'product-category': 'category', 'quantity': 'quantity', 'um': 'unitMeasure', 'price-net': 'priceNet', 'vat-rate': 'vatRate', 'vat-exempt': 'isVatExempt' };
                    const modelKey = keyMap[prop];
                    if(modelKey) line[modelKey] = value;

                    if (modelKey === 'productName') line.productId = '';
                    if (modelKey === 'isVatExempt') {
                        const vatSelect = document.querySelector(`.vat-rate-select[data-index="${index}"]`);
                        if (line.isVatExempt) {
                            line._previousVatRate = line.vatRate; line.vatRate = 0;
                            if(vatSelect) { vatSelect.value = '0'; vatSelect.disabled = true; }
                        } else {
                            line.vatRate = line._previousVatRate || 22;
                            if(vatSelect) { vatSelect.value = line.vatRate.toString(); vatSelect.disabled = false; }
                        }
                    }
                    updateTotalsAndLineDisplay(index);
                };
            });
        }

        function updateTotalsAndLineDisplay(index) {
            const line = currentInvoice.productLines[index];
            if(!line) return;
            const quantity = parseFloat(line.quantity || 0);
            const priceNet = parseFloat(line.priceNet || 0);
            const vatRate = parseFloat(line.vatRate || 0);
            const lineNet = quantity * priceNet;
            const lineVAT = lineNet * (vatRate / 100);
            const lineGross = lineNet + lineVAT;
            line.priceGross = (quantity > 0) ? (lineGross / quantity) : 0;
            line.lineTotal = lineGross;
            
            const row = productLinesBody.querySelector(`[data-temp-id="${line.tempId}"]`);
            if (row) {
                row.querySelector('.price-gross-input').value = formatCurrency(line.priceGross);
                row.querySelector('.line-total-input').value = formatCurrency(line.lineTotal);
            }
            updateTotals();
        }

        // --- Main Event Listeners & Initialisation ---
        window.onload = () => {
            populateCategorySelects();
            if (!invoiceToEditData) addProductLine();
            setupAutocomplete(newSupplierCityInput, mockCities, item => item.name, item => newSupplierCityInput.value = item.name);
            setupAutocomplete(newSupplierCapInput, mockCaps, item => item.code, item => newSupplierCapInput.value = item.code);
            if (invoiceToEditData) {
                invoiceNumberInput.value = currentInvoice.invoiceNumber;
                invoiceDateInput.value = currentInvoice.invoiceDate;
                supplierNameInput.value = currentInvoice.supplierName;
                supplierIdInput.value = currentInvoice.supplierId;
                invoiceStatusSelect.value = currentInvoice.status;
            }
        };

        invoiceAttachmentInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            attachmentNameP.textContent = file ? `File selezionato: ${file.name}` : '';
        });

        invoiceForm.addEventListener('submit', (event) => {
            if (!supplierIdInput.value) {
                showMessage("Fornitore è obbligatorio.", true);
                event.preventDefault(); return;
            }
            if (currentInvoice.productLines.length === 0) {
                showMessage("Inserisci almeno una linea prodotto.", true);
                event.preventDefault(); return;
            }
            for (let i = 0; i < currentInvoice.productLines.length; i++) {
                const line = currentInvoice.productLines[i];
                if (!line.productName || line.quantity === '' || line.priceNet === '' || !line.category) {
                    showMessage(`Compila tutti i campi (Prodotto, Quantità, Prezzo Netto, Categoria) per la riga ${i + 1}.`, true);
                    event.preventDefault(); return;
                }
            }
            productLinesDataInput.value = JSON.stringify(currentInvoice.productLines);
        });

        cancelBtn.addEventListener('click', () => { if (confirm("Sei sicuro di voler annullare? Le modifiche non salvate andranno perse.")) window.location.href = 'visualizza_fatture.php'; });

        setupAutocomplete(supplierNameInput, suppliers, item => item.ragione_sociale, (selected) => {
            currentInvoice.supplierId = selected.id;
            currentInvoice.supplierName = selected.ragione_sociale;
            supplierNameInput.value = selected.ragione_sociale;
            supplierIdInput.value = selected.id;
        });

    </script>
</body>
</html>

