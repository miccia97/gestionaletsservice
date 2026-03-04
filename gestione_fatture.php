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
error_log("PHP_DEBUG: Script gestione_fatture.php started execution.");


// Controlla subito se c'è stato un errore di connessione al database da db.php
if (isset($db_connection_error) && $db_connection_error !== null) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Errore di connessione al database per richiesta AJAX: ' . $db_connection_error
        ]);
        exit;
    } else {
        $_SESSION['message'] = 'Errore critico: La connessione al database non è stata stabilita correttamente! ' . htmlspecialchars($db_connection_error, ENT_QUOTES, 'UTF-8');
        $_SESSION['isError'] = true;
    }
}

// Inizializza la variabile message per l'uso nel HTML
$message = '';

// Controlla se ci sono messaggi da visualizzare dalla sessione
if (isset($_SESSION['message'])) {
    $sessionMessage = $_SESSION['message'];
    $sessionIsError = $_SESSION['isError'] ?? false;
    $message = "<script>document.addEventListener('DOMContentLoaded', function() { showToast('" . addslashes($sessionMessage) . "', " . ($sessionIsError ? "'error'" : "'success'") . "); });</script>";
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


// --- LOGICA PER GESTIRE LE RICHIESTE AJAX DEL MODAL ---
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'add_new_product_to_catalog':
            $name = $_POST['name'] ?? '';
            $code = $_POST['code'] ?? '';
            $categoryId = $_POST['categoryId'] ?? '';
            $priceNet = $_POST['priceNet'] ?? '';
            $quantity = $_POST['quantity'] ?? '';
            $priceSale1 = $_POST['priceSale1'] ?? null;
            $priceSale2 = $_POST['priceSale2'] ?? null;

            if (empty($name) || empty($categoryId) || !is_numeric($priceNet) || $priceNet < 0 || !is_numeric($quantity) || $quantity < 0) {
                echo json_encode(['success' => false, 'message' => 'Tutti i campi obbligatori (Nome, Categoria, Prezzo Acquisto Netto, Quantità Iniziale) devono essere validi e compilati.']);
                exit;
            }

            if ($priceSale1 !== null && $priceSale1 !== '' && !is_numeric($priceSale1)) {
                echo json_encode(['success' => false, 'message' => 'Prezzo Vendita 1 deve essere un valore numerico valido.']);
                exit;
            }
            if ($priceSale2 !== null && $priceSale2 !== '' && !is_numeric($priceSale2)) {
                echo json_encode(['success' => false, 'message' => 'Prezzo Vendita 2 deve essere un valore numerico valido.']);
                exit;
            }

            try {
                $stmt_get_cat_name = $conn->prepare("SELECT nome FROM categorie WHERE id = ?");
                if ($stmt_get_cat_name === false) {
                    echo json_encode(['success' => false, 'message' => 'Errore nella preparazione della query categoria: ' . $conn->error]);
                    exit;
                }
                $stmt_get_cat_name->bind_param('i', $categoryId);
                $stmt_get_cat_name->execute();
                $result_cat_name = $stmt_get_cat_name->get_result();
                $cat_row = $result_cat_name->fetch_assoc();
                $categoryName = $cat_row ? $cat_row['nome'] : null;
                $stmt_get_cat_name->close();

                if ($categoryName === null) {
                     echo json_encode(['success' => false, 'message' => 'Categoria selezionata non valida.']);
                     exit;
                }

                $stmt_insert_new_product = $conn->prepare("INSERT INTO prodotti (nome, categoria, quantita, prezzo_acquisto, barcode, prezzo_vendita1, prezzo_vendita2) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt_insert_new_product === false) {
                    echo json_encode(['success' => false, 'message' => 'Errore nella preparazione della query inserimento prodotto: ' . $conn->error]);
                    exit;
                }
                $priceSale1_val = ($priceSale1 === null || $priceSale1 === '') ? 0.00 : (float)$priceSale1;
                $priceSale2_val = ($priceSale2 === null || $priceSale2 === '') ? 0.00 : (float)$priceSale2;

                $stmt_insert_new_product->bind_param('ssidsdd', $name, $categoryName, $quantity, $priceNet, $code, $priceSale1_val, $priceSale2_val);
                if ($stmt_insert_new_product->execute() === false) {
                    echo json_encode(['success' => false, 'message' => 'Errore nell\'esecuzione inserimento prodotto: ' . $stmt_insert_new_product->error]);
                    exit;
                }
                $new_product_id = $conn->insert_id;
                $stmt_insert_new_product->close();

                echo json_encode([
                    'success' => true,
                    'id_prodotto' => $new_product_id,
                    'nome_prodotto' => $name,
                    'codice_sku' => $code,
                    'unita_misura' => 'pz',
                    'category' => $categoryName,
                    'priceNet' => (float)$priceNet,
                    'priceSale1' => (float)$priceSale1_val,
                    'priceSale2' => (float)$priceSale2_val
                ]);
                exit;
            } catch (mysqli_sql_exception $e) {
                error_log("ERRORE AGGIUNTA PRODOTTO (SQL): " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Errore database durante l\'aggiunta del prodotto: ' . $e->getMessage()]);
                exit;
            }
            break;

        case 'add_new_supplier':
            $name = $_POST['name'] ?? '';
            $partitaIva = $_POST['partitaIva'] ?? null;
            $codiceFiscale = $_POST['codiceFiscale'] ?? null;
            $indirizzo = $_POST['address'] ?? null;
            $citta = $_POST['city'] ?? null;
            $cap = $_POST['cap'] ?? null;
            $provincia = $_POST['province'] ?? null;
            $paese = $_POST['country'] ?? null;
            $telefono = $_POST['phone'] ?? null;
            $email = $_POST['email'] ?? null;

            error_log("Dati ricevuti per nuovo fornitore: " . print_r($_POST, true));

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Nome/Ragione Sociale del fornitore è obbligatorio.']);
                exit;
            }

            try {
                $stmt_insert_supplier = $conn->prepare("INSERT INTO fornitori (nome, ragione_sociale, partita_iva, codice_fiscale, indirizzo, citta, cap, provincia, paese, telefono, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt_insert_supplier === false) {
                    error_log("Errore nella preparazione query inserimento fornitore: " . $conn->error);
                    echo json_encode(['success' => false, 'message' => 'Errore nella preparazione query inserimento fornitore: ' . $conn->error]);
                    exit;
                }
                error_log("Parametri bind fornitore: " . json_encode([$name, $name, $partitaIva, $codiceFiscale, $indirizzo, $citta, $cap, $provincia, $paese, $telefono, $email]));

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
                    'ragione_sociale' => $name
                ]);
                exit;
            } catch (mysqli_sql_exception $e) {
                error_log("ERRORE AGGIUNTA FORNITORE (SQL): " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Errore database durante l\'aggiunta del fornitore: ' . $e->getMessage()]);
                exit;
            }
            break;

        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Azione AJAX non riconosciuta.']);
            exit;
    }
}


// --- LOGICA DI ELABORAZIONE DEL FORM (SALVATAGGIO FATTURA COMPLETA) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    error_log("PHP: Received POST request for invoice submission.");
    error_log("PHP: Raw POST data: " . print_r($_POST, true));
    error_log("PHP: Files data: " . print_r($_FILES, true));

    $invoice_id_to_update = $_POST['invoiceId'] ?? null;
    $numero_fattura = $_POST['invoiceNumber'] ?? '';
    $data_fattura = $_POST['invoiceDate'] ?? '';
    $id_fornitore = $_POST['supplierId'] ?? '';
    $stato_fattura = $_POST['invoiceStatus'] ?? 'Da Verificare';

    if (empty($id_fornitore) || !is_numeric($id_fornitore) || (int)$id_fornitore <= 0) {
        $_SESSION['message'] = 'Errore: Fornitore non selezionato o ID fornitore non valido.';
        $_SESSION['isError'] = true;
        error_log("ERRORE SALVATAGGIO FATTURA: Fornitore ID non valido: " . $id_fornitore);
        $redirect_url = 'gestione_fatture.php';
        if ($invoice_id_to_update) {
            $redirect_url .= '?id=' . $invoice_id_to_update;
        }
        header("Location: " . $redirect_url);
        exit();
    }
    $id_fornitore_int = (int)$id_fornitore;

    $totale_imponibile = 0;
    $totale_iva = 0;
    $totale_lordo = 0;

    $percorso_allegato = null;
    if ($invoice_id_to_update && isset($invoice_to_edit['allegato_url'])) {
        $percorso_allegato = $invoice_to_edit['allegato_url'];
    }

    if (isset($_FILES['invoiceAttachment']) && $_FILES['invoiceAttachment']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/fatture/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = uniqid('fattura_') . '_' . basename($_FILES['invoiceAttachment']['name']);
        $percorso_allegato = $upload_dir . $file_name;

        if (!move_uploaded_file($_FILES['invoiceAttachment']['tmp_name'], $percorso_allegato)) {
            $_SESSION['message'] = 'Errore nel caricamento del file allegato.';
            $_SESSION['isError'] = true;
            $percorso_allegato = null;
        }
    }

    // Assicura che la colonna giacenza_aggiornata esista nella tabella fatture
    // (eseguito PRIMA della transazione per evitare conflitti DDL)
    try {
        $col_check = $conn->query("SHOW COLUMNS FROM fatture LIKE 'giacenza_aggiornata'");
        if ($col_check && $col_check->num_rows === 0) {
            $conn->query("ALTER TABLE fatture ADD COLUMN giacenza_aggiornata TINYINT(1) NOT NULL DEFAULT 0");
            error_log("PHP_DEBUG: Added column giacenza_aggiornata to fatture table.");
        }
    } catch (Exception $e) {
        error_log("PHP_DEBUG: giacenza_aggiornata column check/add: " . $e->getMessage());
    }

    try {
        mysqli_begin_transaction($conn);
        error_log("PHP_DEBUG: Starting database transaction.");

        $id_fattura = 0;

        if ($invoice_id_to_update) {
            error_log("PHP_DEBUG: Updating existing invoice with ID: " . $invoice_id_to_update);
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
            $id_fattura = $invoice_id_to_update;
            $stmt_fattura->close();
            error_log("PHP_DEBUG: Invoice updated successfully. ID: " . $id_fattura);

            $stmt_delete_details = $conn->prepare("DELETE FROM dettagli_fattura WHERE fattura_id = ?");
            if ($stmt_delete_details === false) {
                throw new mysqli_sql_exception("Errore nella preparazione della query di eliminazione dettagli: " . $conn->error);
            }

            // --- STORNO GIACENZA: solo se la giacenza era stata effettivamente caricata ---
            $giacenza_era_aggiornata = false;
            $stmt_check_flag = $conn->prepare("SELECT giacenza_aggiornata FROM fatture WHERE id = ?");
            if ($stmt_check_flag) {
                $stmt_check_flag->bind_param('i', $id_fattura);
                $stmt_check_flag->execute();
                $result_flag = $stmt_check_flag->get_result();
                $flag_row = $result_flag->fetch_assoc();
                $giacenza_era_aggiornata = ($flag_row && (int)$flag_row['giacenza_aggiornata'] === 1);
                $stmt_check_flag->close();
            }
            error_log("PHP_DEBUG: giacenza_aggiornata flag for invoice {$id_fattura}: " . ($giacenza_era_aggiornata ? 'YES' : 'NO'));

            if ($giacenza_era_aggiornata) {
                $stmt_old_details = $conn->prepare("SELECT prodotto_id, quantita FROM dettagli_fattura WHERE fattura_id = ? AND prodotto_id IS NOT NULL AND prodotto_id > 0");
                if ($stmt_old_details) {
                    $stmt_old_details->bind_param('i', $id_fattura);
                    $stmt_old_details->execute();
                    $result_old = $stmt_old_details->get_result();
                    while ($old_row = $result_old->fetch_assoc()) {
                        $old_qty = (float)$old_row['quantita'];
                        $old_pid = (int)$old_row['prodotto_id'];
                        if ($old_qty > 0 && $old_pid > 0) {
                            $stmt_revert = $conn->prepare("UPDATE prodotti SET quantita = quantita - ? WHERE id = ?");
                            if ($stmt_revert) {
                                $stmt_revert->bind_param('di', $old_qty, $old_pid);
                                $stmt_revert->execute();
                                $stmt_revert->close();
                                error_log("PHP_DEBUG: Stock reverted for product ID {$old_pid}: -{$old_qty}");
                            }
                        }
                    }
                    $stmt_old_details->close();
                }
            } else {
                error_log("PHP_DEBUG: Skipping stock revert — stock was never loaded for this invoice.");
            }

            error_log("PHP_DEBUG: Deleting old details for fattura_id: " . $id_fattura);
            $stmt_delete_details->bind_param('i', $id_fattura);
            $stmt_delete_details->execute();
            $stmt_delete_details->close();
            error_log("PHP_DEBUG: Old invoice details deleted for invoice ID: " . $id_fattura);

        } else {
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

        if ($id_fattura === 0) {
            throw new Exception("Nessun ID fattura valido per l'operazione.");
        }
        error_log("PHP_DEBUG: Invoice ID for details processing: " . $id_fattura);

        $product_lines_json = $_POST['productLinesData'] ?? '[]';
        $product_lines = json_decode($product_lines_json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Errore nella decodifica dei dati delle linee prodotto: " . json_last_error_msg() . " JSON ricevuto: " . $product_lines_json);
        }
        error_log("PHP_DEBUG: Decoded product lines for insertion: " . print_r($product_lines, true));

        foreach ($product_lines as $index => $line) {
            $prodotto_id = $line['productId'] ?? null;
            $descrizione_prodotto = $line['productName'] ?? '';
            $quantita = floatval(str_replace(',', '.', $line['quantity'] ?? 0));
            $unita_misura = $line['unitMeasure'] ?? 'pz';
            $prezzo_unitario_netto = floatval(str_replace(',', '.', $line['priceNet'] ?? 0));
            $iva_percentuale = floatval($line['vatRate'] ?? 22);
            $prodotto_senza_iva = $line['isVatExempt'] ?? false;

            // Se productId è vuoto ma c'è un nome prodotto, cerca il prodotto nel DB
            if (empty($prodotto_id) && !empty($descrizione_prodotto)) {
                $stmt_find = $conn->prepare("SELECT id FROM prodotti WHERE nome = ? LIMIT 1");
                if ($stmt_find) {
                    $stmt_find->bind_param('s', $descrizione_prodotto);
                    $stmt_find->execute();
                    $result_find = $stmt_find->get_result();
                    $found_row = $result_find->fetch_assoc();
                    if ($found_row) {
                        $prodotto_id = $found_row['id'];
                        error_log("PHP_DEBUG: Product ID resolved from name '{$descrizione_prodotto}': {$prodotto_id}");
                    }
                    $stmt_find->close();
                }
            }

            $prezzo_unitario_lordo = $prodotto_senza_iva ? $prezzo_unitario_netto : $prezzo_unitario_netto * (1 + ($iva_percentuale / 100));
            $totale_riga_netto = $quantita * $prezzo_unitario_netto;
            $totale_riga_lordo = $quantita * $prezzo_unitario_lordo;

            error_log("PHP_DEBUG: Dettaglio prodotto riga " . ($index + 1) . ": Prodotto ID: {$prodotto_id}, Descrizione: {$descrizione_prodotto}, Quantità: {$quantita}, UM: {$unita_misura}, Prezzo Netto: {$prezzo_unitario_netto}, IVA%: {$iva_percentuale}, Prezzo Lordo Unitario: {$prezzo_unitario_lordo}, Totale Riga Netto: {$totale_riga_netto}, Totale Riga Lordo: {$totale_riga_lordo}, Senza IVA: " . ($prodotto_senza_iva ? 'Si' : 'No'));

            // Aggiorna giacenza magazzino per tutti i prodotti con ID valido
            if (!empty($prodotto_id) && (int)$prodotto_id > 0 && $quantita > 0) {
                $stmt_update_stock = $conn->prepare("UPDATE prodotti SET quantita = quantita + ? WHERE id = ?");
                if ($stmt_update_stock === false) {
                    throw new mysqli_sql_exception("Errore nella preparazione della query di aggiornamento magazzino: " . $conn->error);
                }
                $prodotto_id_int = (int)$prodotto_id;
                error_log("PHP_DEBUG: Updating stock for product ID {$prodotto_id_int}: +{$quantita}");
                $stmt_update_stock->bind_param('di', $quantita, $prodotto_id_int);
                $exec_success_stock = $stmt_update_stock->execute();
                if ($exec_success_stock === false) {
                    throw new mysqli_sql_exception("Errore nell'esecuzione dell'aggiornamento magazzino per prodotto ID {$prodotto_id}: " . $stmt_update_stock->error);
                }
                $stmt_update_stock->close();
                error_log("PHP_DEBUG: Stock updated for product ID: " . $prodotto_id . " (+{$quantita})");
            } else {
                error_log("PHP_DEBUG: Stock NOT updated for row " . ($index + 1) . " - prodotto_id=" . var_export($prodotto_id, true) . ", quantita={$quantita}");
            }

            $stmt_dettaglio = $conn->prepare("INSERT INTO dettagli_fattura (fattura_id, prodotto_id, descrizione_prodotto, quantita, unita_misura, prezzo_unitario_netto, iva_percentuale, prezzo_unitario_lordo, totale_riga_netto, totale_riga_lordo, prodotto_senza_iva) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_dettaglio === false) {
                throw new mysqli_sql_exception("Errore nella preparazione della query di inserimento dettaglio fattura: " . $conn->error);
            }
            $prodotto_senza_iva_int = $prodotto_senza_iva ? 1 : 0;
            error_log("PHP_DEBUG: Binding parameters for INSERT dettagli_fattura: Types='iisdsdddddi', Values=" . json_encode([$id_fattura, $prodotto_id, $descrizione_prodotto, $quantita, $unita_misura, $prezzo_unitario_netto, $iva_percentuale, $prezzo_unitario_lordo, $totale_riga_netto, $totale_riga_lordo, $prodotto_senza_iva_int]));

            $stmt_dettaglio->bind_param('iisdsdddddi', $id_fattura, $prodotto_id, $descrizione_prodotto, $quantita, $unita_misura, $prezzo_unitario_netto, $iva_percentuale, $prezzo_unitario_lordo, $totale_riga_netto, $totale_riga_lordo, $prodotto_senza_iva_int);
            $exec_success_detail = $stmt_dettaglio->execute();
            if ($exec_success_detail === false) {
                throw new mysqli_sql_exception("Errore nell'esecuzione dell'inserimento dettaglio fattura per prodotto ID {$prodotto_id}: " . $stmt_dettaglio->error);
            }
            error_log("PHP_DEBUG: Dettaglio fattura inserito con successo per riga " . ($index + 1));
            $stmt_dettaglio->close();

            $totale_imponibile += $totale_riga_netto;
            $totale_iva += ($totale_riga_lordo - $totale_riga_netto);
            $totale_lordo += $totale_riga_lordo;
        }

        $stmt_update_totals = $conn->prepare("UPDATE fatture SET totale_imponibile = ?, totale_iva = ?, totale_lordo = ?, giacenza_aggiornata = 1 WHERE id = ?");
        if ($stmt_update_totals === false) {
            throw new mysqli_sql_exception("Errore nella preparazione della query di aggiornamento totali fattura: " . $conn->error);
        }
        error_log("PHP_DEBUG: Binding parameters for UPDATE fatture (totals + giacenza_aggiornata=1): Types='dddi', Values=" . json_encode([$totale_imponibile, $totale_iva, $totale_lordo, $id_fattura]));
        $stmt_update_totals->bind_param('dddi', $totale_imponibile, $totale_iva, $totale_lordo, $id_fattura);
        $exec_success_totals = $stmt_update_totals->execute();
        if ($exec_success_totals === false) {
            throw new mysqli_sql_exception("Errore nell'esecuzione dell'aggiornamento totali fattura per fattura ID {$id_fattura}: " . $stmt_update_totals->error);
        }
        $stmt_update_totals->close();

        mysqli_commit($conn);
        error_log("PHP_DEBUG: Database transaction committed successfully.");

        $_SESSION['message'] = 'Fattura e dettagli salvati con successo!';
        $_SESSION['isError'] = false;
        header("Location: visualizza_fatture.php");
        exit();

    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($conn);
        $errorMessage = "Errore database durante il salvataggio: " . $e->getMessage();
        $_SESSION['message'] = $errorMessage;
        $_SESSION['isError'] = true;
        error_log("ERRORE SALVATAGGIO FATTURA (SQL): " . $e->getMessage() . " - Stack: " . $e->getTraceAsString());
        $redirect_url = 'gestione_fatture.php';
        if ($invoice_id_to_update) {
            $redirect_url .= '?id=' . $invoice_id_to_update;
        }
        header("Location: " . $redirect_url);
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $errorMessage = "Errore generale durante il salvataggio: " . $e->getMessage();
        $_SESSION['message'] = $errorMessage;
        $_SESSION['isError'] = true;
        error_log("ERRORE GENERALE SALVATAGGIO FATTURA: " . $e->getMessage() . " - Stack: " . $e->getTraceAsString());
        $redirect_url = 'gestione_fatture.php';
        if ($invoice_id_to_update) {
            $redirect_url .= '?id=' . $invoice_id_to_update;
        }
        header("Location: " . $redirect_url);
        exit();
    }
}

// --- RECUPERO DATI PER LE SELECT ---
$fornitori = [];
try {
    $result_fornitori = $conn->query("SELECT id, nome, ragione_sociale, partita_iva, codice_fiscale, indirizzo, citta, cap, provincia, paese, telefono, email FROM fornitori ORDER BY ragione_sociale");
    if ($result_fornitori) {
        $fornitori = $result_fornitori->fetch_all(MYSQLI_ASSOC);
        $result_fornitori->free();
    }
} catch (mysqli_sql_exception $e) {
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showToast('Errore nel caricamento fornitori: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', 'error'); });</script>";
    error_log("Errore nel caricamento fornitori: " . $e->getMessage());
}

$prodotti_esistenti = [];
try {
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
                'priceSale1' => (float)($p['prezzo_vendita1'] ?? 0.00),
                'priceSale2' => (float)($p['prezzo_vendita2'] ?? 0.00)
            ];
        }
    }

} catch (mysqli_sql_exception $e) {
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showToast('Errore nel caricamento prodotti: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', 'error'); });</script>";
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
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showToast('Errore nel caricamento categorie: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', 'error'); });</script>";
    error_log("Errore nel caricamento categorie: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $invoice_to_edit ? 'Modifica Fattura' : 'Nuova Fattura'; ?> | TS Service</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=1">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
                    <style>
/* ======================================= */
/*        PREMIUM DESIGN SYSTEM            */
/* ======================================= */
:root {
    --primary: #22c55e;
    --primary-dark: #16a34a;
    --primary-light: #dcfce7;
    --primary-glow: rgba(34, 197, 94, 0.4);
    --blue: #3b82f6;
    --blue-dark: #2563eb;
    --blue-light: #dbeafe;
    --green: #22c55e;
    --green-dark: #16a34a;
    --green-light: #dcfce7;
    --secondary: #8b5cf6;
    --secondary-light: #ede9fe;
    --success: #10b981;
    --warning: #f59e0b;
    --warning-light: #fef3c7;
    --danger: #ef4444;
    --danger-dark: #dc2626;
    --danger-light: #fee2e2;
    --info: #06b6d4;
    --info-light: #ecfeff;
    --bg-page: #f8fafc;
    --bg-card: #ffffff;
    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;
    --border-color: #e2e8f0;
    --border-light: #f1f5f9;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-md: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition: 200ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-spring: 500ms cubic-bezier(0.34, 1.56, 0.64, 1);
    --radius-sm: 0.375rem;
    --radius: 0.5rem;
    --radius-md: 0.75rem;
    --radius-lg: 1rem;
    --radius-xl: 1.5rem;
}
.hidden { display: none !important; }
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: linear-gradient(135deg, var(--bg-page) 0%, #e2e8f0 100%);
    min-height: 100vh; color: var(--text-primary);
    padding-top: 80px; line-height: 1.6; overflow-x: hidden;
}

/* ======================================= */
/*           FLOATING PARTICLES            */
/* ======================================= */
.particles-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; overflow: hidden; }
.particle { position: absolute; border-radius: 50%; opacity: 0.12; animation: floatParticle 20s infinite ease-in-out; }
.particle:nth-child(1) { width: 300px; height: 300px; background: var(--primary); top: -100px; left: -100px; animation-delay: 0s; }
.particle:nth-child(2) { width: 200px; height: 200px; background: var(--blue); top: 50%; right: -50px; animation-delay: -5s; }
.particle:nth-child(3) { width: 150px; height: 150px; background: var(--secondary); bottom: 10%; left: 20%; animation-delay: -10s; }
.particle:nth-child(4) { width: 100px; height: 100px; background: var(--warning); top: 30%; left: 60%; animation-delay: -15s; }
@keyframes floatParticle {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(30px, -30px) scale(1.05); }
    50% { transform: translate(-20px, 20px) scale(0.95); }
    75% { transform: translate(15px, 15px) scale(1.02); }
}

/* ======================================= */
/*              TOAST SYSTEM               */
/* ======================================= */
.toast-container {
    position: fixed; top: 100px; right: 24px; z-index: 10000;
    display: flex; flex-direction: column; gap: 12px; pointer-events: none;
}
.toast {
    background: var(--bg-card); border-radius: var(--radius-lg);
    padding: 16px 20px; box-shadow: var(--shadow-lg);
    display: flex; align-items: center; gap: 12px;
    min-width: 320px; max-width: 450px; pointer-events: auto;
    transform: translateX(120%); opacity: 0;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    border-left: 4px solid var(--primary);
}
.toast.show { transform: translateX(0); opacity: 1; }
.toast.toast-success { border-left-color: var(--success); }
.toast.toast-error { border-left-color: var(--danger); }
.toast-icon {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 1rem;
}
.toast-success .toast-icon { background: var(--green-light); color: var(--green); }
.toast-error .toast-icon { background: var(--danger-light); color: var(--danger); }
.toast-content { flex: 1; }
.toast-title { font-weight: 600; font-size: 0.92rem; color: var(--text-primary); }
.toast-close {
    background: none; border: none; color: var(--text-muted); cursor: pointer;
    padding: 4px; border-radius: var(--radius-sm); transition: all var(--transition);
}
.toast-close:hover { background: var(--border-light); color: var(--text-primary); }

/* ======================================= */
/*              MAIN LAYOUT                */
/* ======================================= */
.main-container {
    max-width: 1500px; margin: 0 auto; padding: 24px 32px;
    position: relative; z-index: 1;
}

/* ======================================= */
/*             PAGE HEADER                 */
/* ======================================= */
.page-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 28px; flex-wrap: wrap; gap: 20px;
    animation: fadeInUp 0.5s ease-out;
}
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.page-title {
    font-size: 2.2rem; font-weight: 800; color: var(--text-primary);
    display: flex; align-items: center; gap: 16px;
    letter-spacing: -0.02em;
}
.page-title-icon {
    width: 54px; height: 54px; border-radius: var(--radius-lg); position: relative;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3);
    transition: transform var(--transition-spring);
}
.page-title-icon:hover { transform: scale(1.08) rotate(-5deg); }
.page-title-icon i { font-size: 1.3rem; color: #fff; }
.page-subtitle { color: var(--text-secondary); font-size: 0.95rem; font-weight: 400; margin-top: 4px; }

.header-actions { display: flex; gap: 12px; align-items: center; }
.btn-cancel {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 24px; border: 1px solid var(--border-color); border-radius: var(--radius-md);
    font-size: 0.9rem; font-weight: 600; font-family: 'Inter', sans-serif;
    cursor: pointer; text-decoration: none; color: var(--text-secondary);
    background: var(--bg-card); transition: all var(--transition);
}
.btn-cancel:hover { background: var(--border-light); color: var(--text-primary); border-color: var(--text-muted); }

.btn-save {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 12px 28px; border: none; border-radius: var(--radius-md);
    font-size: 0.92rem; font-weight: 700; font-family: 'Inter', sans-serif;
    cursor: pointer; color: #fff;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    box-shadow: 0 4px 20px rgba(34, 197, 94, 0.35);
    transition: all var(--transition); position: relative; overflow: hidden;
}
.btn-save::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent);
    opacity: 0; transition: opacity var(--transition);
}
.btn-save:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(34, 197, 94, 0.45); }
.btn-save:hover::before { opacity: 1; }
.btn-save i { font-size: 0.85rem; }

/* ======================================= */
/*              CARDS                      */
/* ======================================= */
.card {
    background: var(--bg-card); border-radius: var(--radius-xl);
    box-shadow: var(--shadow); border: 1px solid var(--border-color);
    padding: 28px; margin-bottom: 24px; position: relative;
    overflow: hidden;
    animation: fadeInUp 0.5s ease-out both;
}
.card:nth-child(2) { animation-delay: 0.1s; }
.card:nth-child(3) { animation-delay: 0.15s; }
.card:nth-child(4) { animation-delay: 0.2s; }

.card-header {
    padding-bottom: 16px; margin-bottom: 24px;
    border-bottom: 1px solid var(--border-color);
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 12px;
}
.card-title {
    font-size: 1.15rem; font-weight: 700; color: var(--text-primary);
    display: flex; align-items: center; gap: 10px;
}
.card-title i { color: var(--primary); font-size: 1rem; }

/* ======================================= */
/*            FORM CONTROLS                */
/* ======================================= */
.form-grid {
    display: grid; gap: 20px;
}
.form-grid-4 { grid-template-columns: repeat(4, 1fr); }
.form-grid-2 { grid-template-columns: repeat(2, 1fr); }
.form-col-span-2 { grid-column: span 2; }
.form-col-span-3 { grid-column: span 3; }

.form-group { position: relative; }
.form-label {
    display: block; font-size: 0.82rem; font-weight: 600;
    color: var(--text-primary); margin-bottom: 8px;
    text-transform: uppercase; letter-spacing: 0.03em;
}
.form-input, .form-select {
    width: 100%; padding: 12px 14px;
    border: 1px solid var(--border-color); border-radius: var(--radius);
    font-size: 0.9rem; font-family: 'Inter', sans-serif;
    color: var(--text-primary); background: #f8fafc;
    outline: none; transition: all var(--transition);
    line-height: 1.5;
}
.form-input:focus, .form-select:focus {
    border-color: var(--primary); background: #fff;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
}
.form-input::placeholder { color: var(--text-muted); }

.form-select {
    appearance: none; -webkit-appearance: none; cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center;
    padding-right: 36px;
}

.form-file-input {
    width: 100%; padding: 10px 14px;
    border: 2px dashed var(--border-color); border-radius: var(--radius);
    font-size: 0.88rem; font-family: 'Inter', sans-serif;
    color: var(--text-secondary); background: #f8fafc;
    cursor: pointer; transition: all var(--transition);
}
.form-file-input:hover { border-color: var(--primary); background: var(--primary-light); }
.form-file-input::file-selector-button {
    padding: 8px 16px; border-radius: var(--radius-sm);
    border: none; background: var(--bg-card); color: var(--text-primary);
    font-weight: 600; font-size: 0.82rem; cursor: pointer;
    margin-right: 12px; border: 1px solid var(--border-color);
    transition: all var(--transition);
}
.form-file-input::file-selector-button:hover {
    background: var(--text-primary); color: #fff;
}

.form-hint {
    font-size: 0.8rem; color: var(--text-muted); margin-top: 6px;
}
.form-hint a { color: var(--primary); text-decoration: none; font-weight: 600; }
.form-hint a:hover { text-decoration: underline; }

.input-with-btn {
    display: flex; gap: 10px; align-items: flex-end;
}
.input-with-btn .form-group { flex: 1; }

.btn-outline-sm {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 12px 18px; border: 1px solid var(--border-color); border-radius: var(--radius);
    font-size: 0.85rem; font-weight: 600; font-family: 'Inter', sans-serif;
    cursor: pointer; background: var(--bg-card); color: var(--text-secondary);
    transition: all var(--transition); white-space: nowrap; height: fit-content;
}
.btn-outline-sm:hover { background: var(--border-light); color: var(--text-primary); border-color: var(--text-muted); }

/* ======================================= */
/*          PRODUCT TABLE                  */
/* ======================================= */
.product-actions { display: flex; gap: 10px; }
.btn-add-line {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; border: none; border-radius: var(--radius);
    font-size: 0.85rem; font-weight: 700; font-family: 'Inter', sans-serif;
    cursor: pointer; color: #fff;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    box-shadow: 0 2px 10px rgba(34, 197, 94, 0.25);
    transition: all var(--transition);
}
.btn-add-line:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(34, 197, 94, 0.35); }
.btn-add-line i { font-size: 0.8rem; }

.btn-new-catalog {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; border: 1px solid var(--border-color); border-radius: var(--radius);
    font-size: 0.85rem; font-weight: 600; font-family: 'Inter', sans-serif;
    cursor: pointer; background: var(--bg-card); color: var(--text-secondary);
    transition: all var(--transition);
}
.btn-new-catalog:hover { background: var(--border-light); color: var(--text-primary); }
.btn-new-catalog i { font-size: 0.8rem; }

.table-scroll { overflow-x: auto; margin: 0 -28px; padding: 0 28px; }
.table-scroll::-webkit-scrollbar { height: 6px; }
.table-scroll::-webkit-scrollbar-track { background: transparent; }
.table-scroll::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.1); border-radius: 10px; }

.product-table { width: 100%; border-collapse: collapse; min-width: 1000px; }
.product-table th {
    padding: 12px 14px; text-align: left; font-size: 0.72rem;
    font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;
    color: var(--text-secondary); background: #f8fafc;
    border-bottom: 2px solid var(--border-color);
    white-space: nowrap;
}
.product-table th.text-right { text-align: right; }
.product-table th.text-center { text-align: center; }

.product-table td {
    padding: 10px 14px; font-size: 0.88rem;
    border-bottom: 1px solid var(--border-light);
    color: var(--text-primary); vertical-align: middle;
}
.product-table tbody tr:last-child td { border-bottom: none; }
.product-table tbody tr:hover { background: rgba(34, 197, 94, 0.04); }

.product-table .cell-input {
    width: 100%; padding: 9px 10px;
    border: 1px solid var(--border-color); border-radius: var(--radius-sm);
    font-size: 0.85rem; font-family: 'Inter', sans-serif;
    color: var(--text-primary); background: #f8fafc;
    outline: none; transition: all var(--transition);
}
.product-table .cell-input:focus {
    border-color: var(--primary); background: #fff;
    box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.12);
}
.product-table .cell-input.readonly {
    background: var(--border-light); color: var(--text-secondary);
    font-weight: 600; cursor: default;
}
.product-table .cell-select {
    padding: 9px 28px 9px 10px; border: 1px solid var(--border-color);
    border-radius: var(--radius-sm); font-size: 0.85rem;
    font-family: 'Inter', sans-serif; color: var(--text-primary);
    background: #f8fafc; outline: none; cursor: pointer;
    appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 8px center;
    transition: all var(--transition);
}
.product-table .cell-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.12);
}
.product-table .cell-checkbox {
    width: 18px; height: 18px; cursor: pointer;
    accent-color: var(--primary);
}
.product-table .remove-btn {
    width: 34px; height: 34px; border-radius: 50%; border: none;
    background: transparent; cursor: pointer; font-size: 0.95rem;
    color: var(--text-muted); display: flex; align-items: center;
    justify-content: center; transition: all var(--transition);
}
.product-table .remove-btn:hover { color: var(--danger); background: var(--danger-light); }

.empty-row td {
    text-align: center; padding: 40px 20px !important;
    color: var(--text-muted); font-size: 0.9rem;
}

/* ======================================= */
/*           TOTALS CARD                   */
/* ======================================= */
.totals-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
}
.total-item { text-align: right; }
.total-label {
    font-size: 0.78rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.04em; color: var(--text-secondary); margin-bottom: 6px;
}
.total-value {
    font-size: 1.3rem; font-weight: 700; color: var(--text-primary);
}
.total-item.highlight {
    background: var(--primary-light); padding: 20px; border-radius: var(--radius-md);
    margin: -8px; text-align: right;
}
.total-item.highlight .total-label { color: var(--primary-dark); }
.total-item.highlight .total-value { font-size: 1.8rem; font-weight: 800; color: var(--primary-dark); }

/* ======================================= */
/*              MODALS                     */
/* ======================================= */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(30, 41, 59, 0.5);
    display: flex; justify-content: center; align-items: center;
    z-index: 5000; opacity: 0; visibility: hidden;
    pointer-events: none;
    transition: opacity 0.3s ease;
}
.modal-overlay.show { opacity: 1; visibility: visible; pointer-events: auto; }

.modal-content {
    background: var(--bg-card); border-radius: var(--radius-xl);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    width: 90%; max-height: 90vh; overflow-y: auto;
    transform: scale(0.95); transition: transform 0.3s ease;
}
.modal-content::-webkit-scrollbar { width: 6px; }
.modal-content::-webkit-scrollbar-track { background: transparent; }
.modal-content::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.1); border-radius: 10px; }
.modal-overlay.show .modal-content { transform: scale(1); }

.modal-header {
    padding: 24px 28px; border-bottom: 1px solid var(--border-color);
    display: flex; align-items: center; justify-content: space-between;
}
.modal-header h2 {
    font-size: 1.2rem; font-weight: 700; color: var(--text-primary);
    display: flex; align-items: center; gap: 10px;
}
.modal-header h2 i { font-size: 1rem; color: var(--primary); }
.modal-close {
    width: 36px; height: 36px; border-radius: var(--radius);
    border: none; background: var(--border-light); cursor: pointer;
    color: var(--text-muted); font-size: 1rem;
    display: flex; align-items: center; justify-content: center;
    transition: all var(--transition);
}
.modal-close:hover { background: var(--danger-light); color: var(--danger); }

.modal-body { padding: 28px; }
.modal-footer {
    padding: 20px 28px; border-top: 1px solid var(--border-color);
    display: flex; justify-content: flex-end; gap: 12px;
}

.btn-modal {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px; border: none; border-radius: var(--radius);
    font-size: 0.88rem; font-weight: 600; font-family: 'Inter', sans-serif;
    cursor: pointer; transition: all var(--transition);
}
.btn-modal-primary { background: var(--primary); color: #fff; }
.btn-modal-primary:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3); }
.btn-modal-secondary { background: #e2e8f0; color: #334155; }
.btn-modal-secondary:hover { background: #cbd5e1; }

/* ======================================= */
/*          AUTOCOMPLETE                   */
/* ======================================= */
.autocomplete-list-overlay {
    list-style: none; padding: 6px; margin-top: 4px;
    background: var(--bg-card); border: 1px solid var(--border-color);
    border-radius: var(--radius); max-height: 220px; overflow-y: auto;
    box-shadow: var(--shadow-lg); z-index: 10000;
}
.autocomplete-list-overlay li {
    padding: 10px 14px; cursor: pointer; border-radius: var(--radius-sm);
    font-size: 0.88rem; color: var(--text-primary);
    transition: background var(--transition-fast);
}
.autocomplete-list-overlay li:hover { background: var(--primary-light); color: var(--primary-dark); }

/* ======================================= */
/*           RESPONSIVE                    */
/* ======================================= */
@media (max-width: 1024px) {
    .form-grid-4 { grid-template-columns: repeat(2, 1fr); }
    .form-col-span-2 { grid-column: span 2; }
    .form-col-span-3 { grid-column: span 2; }
}
@media (max-width: 768px) {
    .main-container { padding: 16px; }
    .page-header { flex-direction: column; align-items: flex-start; }
    .page-title { font-size: 1.6rem; }
    .form-grid-4, .form-grid-2 { grid-template-columns: 1fr; }
    .form-col-span-2, .form-col-span-3 { grid-column: span 1; }
    .totals-grid { grid-template-columns: 1fr; }
    .card { padding: 20px; }
    .card-header { flex-direction: column; align-items: flex-start; }
    .product-actions { width: 100%; }
    .product-actions button { flex: 1; justify-content: center; }
}

@media print {
    .particles-container, .toast-container { display: none !important; }
    body { padding-top: 0; background: #fff; }
    .card { box-shadow: none; border: 1px solid #ddd; }
}
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <!-- Floating particles -->
    <div class="particles-container">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <main class="main-container">
        <?php echo $message; ?>

        <form action="gestione_fatture.php" method="POST" enctype="multipart/form-data" id="invoiceForm">

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">
                        <div class="page-title-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <?php echo $invoice_to_edit ? 'Modifica Fattura' : 'Nuova Fattura di Acquisto'; ?>
                    </h1>
                    <p class="page-subtitle">
                        <?php echo $invoice_to_edit ? 'Aggiorna i dettagli della fattura e i prodotti.' : 'Compila i campi per registrare una nuova fattura.'; ?>
                    </p>
                </div>
                <div class="header-actions">
                    <button type="button" id="cancelBtn" class="btn-cancel">Annulla</button>
                    <button type="submit" form="invoiceForm" id="saveInvoiceBtn" class="btn-save">
                        <i class="fas fa-check-square"></i> Salva Fattura
                    </button>
                </div>
            </div>

            <!-- Card: Dettagli Fattura -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-receipt"></i> Dettagli Fattura</h2>
                </div>
                <div class="form-grid form-grid-4">
                    <div class="form-group">
                        <label class="form-label" for="invoiceNumber">Numero Fattura</label>
                        <input type="text" id="invoiceNumber" name="invoiceNumber" class="form-input" placeholder="Es. 2024/123" required value="<?php echo htmlspecialchars($invoice_to_edit['numero_fattura'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="invoiceDate">Data Fattura</label>
                        <input type="date" id="invoiceDate" name="invoiceDate" class="form-input" required value="<?php echo htmlspecialchars($invoice_to_edit['data_fattura'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group form-col-span-2">
                        <label class="form-label" for="supplierName">Fornitore</label>
                        <div class="input-with-btn">
                            <div class="form-group" style="margin-bottom:0">
                                <input type="text" id="supplierName" class="form-input" placeholder="Cerca o aggiungi fornitore" value="<?php echo htmlspecialchars($invoice_to_edit['nome_fornitore'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" id="supplierId" name="supplierId" required value="<?php echo htmlspecialchars($invoice_to_edit['fornitore_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <button type="button" id="addSupplierBtn" class="btn-outline-sm">Nuovo</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="invoiceStatus">Stato Fattura</label>
                        <select id="invoiceStatus" name="invoiceStatus" class="form-select" required>
                            <option value="Da Verificare" <?php echo (isset($invoice_to_edit['stato']) && $invoice_to_edit['stato'] == 'Da Verificare') ? 'selected' : ''; ?>>Da Verificare</option>
                            <option value="Registrata" <?php echo (isset($invoice_to_edit['stato']) && $invoice_to_edit['stato'] == 'Registrata') ? 'selected' : ''; ?>>Registrata</option>
                            <option value="Pagata" <?php echo (isset($invoice_to_edit['stato']) && $invoice_to_edit['stato'] == 'Pagata') ? 'selected' : ''; ?>>Pagata</option>
                        </select>
                    </div>
                    <div class="form-group form-col-span-3">
                        <label class="form-label" for="invoiceAttachment">Allegato Fattura (.pdf, .jpg, .png)</label>
                        <input type="file" id="invoiceAttachment" name="invoiceAttachment" accept=".pdf, .jpg, .jpeg, .png" class="form-file-input">
                        <p id="attachmentName" class="form-hint">
                            <?php if (isset($invoice_to_edit['allegato_url']) && !empty($invoice_to_edit['allegato_url'])): ?>
                                File attuale: <a href="<?php echo htmlspecialchars($invoice_to_edit['allegato_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><?php echo basename($invoice_to_edit['allegato_url']); ?></a>
                            <?php endif; ?>
                        </p>
                        <?php if ($invoice_to_edit): ?>
                            <input type="hidden" name="invoiceId" value="<?php echo htmlspecialchars($invoice_to_edit['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Card: Dettaglio Prodotti -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-boxes-stacked"></i> Dettaglio Prodotti</h2>
                    <div class="product-actions">
                        <button type="button" id="addNewProductBtn" class="btn-new-catalog">
                            <i class="fas fa-plus"></i> Nuovo Prodotto in Catalogo
                        </button>
                        <button type="button" id="addProductLineBtn" class="btn-add-line">
                            <i class="fas fa-list-check"></i> Aggiungi Riga
                        </button>
                    </div>
                </div>
                <div class="table-scroll">
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Prodotto</th>
                                <th>Categoria</th>
                                <th>Qt&agrave;</th>
                                <th>UM</th>
                                <th>Prezzo Netto</th>
                                <th>IVA</th>
                                <th class="text-center">Es.</th>
                                <th class="text-right">Prezzo Lordo</th>
                                <th class="text-right">Totale Riga</th>
                                <th style="width: 44px;"></th>
                            </tr>
                        </thead>
                        <tbody id="productLines">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Card: Riepilogo Totali -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-calculator"></i> Riepilogo Totali</h2>
                </div>
                <div class="totals-grid">
                    <div class="total-item">
                        <div class="total-label">Totale Imponibile</div>
                        <div class="total-value" id="totalNet">0,00 &euro;</div>
                    </div>
                    <div class="total-item">
                        <div class="total-label">Totale IVA</div>
                        <div class="total-value" id="totalVATP">0,00 &euro;</div>
                    </div>
                    <div class="total-item highlight">
                        <div class="total-label">Totale Fattura</div>
                        <div class="total-value" id="totalGrossP">0,00 &euro;</div>
                    </div>
                </div>
            </div>

            <textarea id="productLinesData" name="productLinesData" class="hidden"></textarea>
        </form>
    </main>

    <!-- Modal: Aggiungi Fornitore -->
    <div id="addSupplierModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 750px;">
            <div class="modal-header">
                <h2><i class="fas fa-truck-field"></i> Aggiungi Nuovo Fornitore</h2>
                <button class="modal-close" id="closeSupplierModal"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group form-col-span-2">
                        <label class="form-label" for="newSupplierName">Nome / Ragione Sociale</label>
                        <input type="text" id="newSupplierName" class="form-input" required placeholder="Ragione sociale del fornitore">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newSupplierPartitaIva">Partita IVA</label>
                        <input type="text" id="newSupplierPartitaIva" class="form-input" placeholder="IT01234567890">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newSupplierCodiceFiscale">Codice Fiscale</label>
                        <input type="text" id="newSupplierCodiceFiscale" class="form-input" placeholder="RSSMRA80A01H501U">
                    </div>
                    <div class="form-group form-col-span-2">
                        <label class="form-label" for="newSupplierAddress">Indirizzo</label>
                        <input type="text" id="newSupplierAddress" class="form-input" placeholder="Via Roma, 1">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newSupplierCity">Citt&agrave;</label>
                        <input type="text" id="newSupplierCity" class="form-input" placeholder="Milano">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newSupplierCap">CAP</label>
                        <input type="text" id="newSupplierCap" class="form-input" placeholder="20100">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newSupplierProvince">Provincia</label>
                        <input type="text" id="newSupplierProvince" class="form-input" placeholder="MI">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newSupplierCountry">Paese</label>
                        <input type="text" id="newSupplierCountry" class="form-input" placeholder="Italia">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newSupplierPhone">Telefono</label>
                        <input type="text" id="newSupplierPhone" class="form-input" placeholder="+39 02 1234567">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newSupplierEmail">Email</label>
                        <input type="email" id="newSupplierEmail" class="form-input" placeholder="info@fornitore.it">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-modal-secondary" onclick="closeModalOverlay('addSupplierModal')">Annulla</button>
                <button type="button" id="saveNewSupplierBtn" class="btn-modal btn-modal-primary"><i class="fas fa-check"></i> Salva Fornitore</button>
            </div>
        </div>
    </div>

    <!-- Modal: Aggiungi Prodotto -->
    <div id="addProductModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 550px;">
            <div class="modal-header">
                <h2><i class="fas fa-box-open"></i> Aggiungi Nuovo Prodotto al Catalogo</h2>
                <button class="modal-close" id="closeProductModal"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-grid" style="grid-template-columns: 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="newProductName">Nome Prodotto</label>
                        <input type="text" id="newProductName" class="form-input" required placeholder="Nome del prodotto">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newProductCode">Codice (Barcode)</label>
                        <input type="text" id="newProductCode" class="form-input" placeholder="EAN / SKU">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newProductCategory">Categoria</label>
                        <select id="newProductCategory" class="form-select" required>
                            <option value="">Seleziona Categoria</option>
                            <?php foreach ($categorie_prodotti as $cat) : ?>
                                <option value="<?php echo htmlspecialchars($cat['id_categoria']); ?>"><?php echo htmlspecialchars($cat['nome_categoria']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newProductPriceNet">Prezzo Acquisto Netto</label>
                        <input type="number" step="0.01" id="newProductPriceNet" class="form-input" min="0" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newProductPriceSale1">Prezzo Vendita 1</label>
                        <input type="number" step="0.01" id="newProductPriceSale1" class="form-input" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newProductPriceSale2">Prezzo Vendita 2</label>
                        <input type="number" step="0.01" id="newProductPriceSale2" class="form-input" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newProductQuantity">Quantit&agrave; Iniziale (Magazzino)</label>
                        <input type="number" step="1" id="newProductQuantity" class="form-input" min="0" required placeholder="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-modal-secondary" onclick="closeModalOverlay('addProductModal')">Annulla</button>
                <button type="button" id="saveNewProductBtn" class="btn-modal btn-modal-primary"><i class="fas fa-check"></i> Salva Prodotto</button>
            </div>
        </div>
    </div>


    <script>
    // ========== v3.0 - INLINE HANDLERS - <?php echo date('Y-m-d H:i:s'); ?> ==========
    // ========== DATA FROM PHP ==========
    const initialFornitori = <?php echo json_encode($fornitori); ?>;
    const initialProdottiEsistenti = <?php echo json_encode($prodotti_esistenti); ?>;
    const initialCategorieProdotti = <?php echo json_encode($categorie_prodotti); ?>;
    const invoiceToEditData = <?php echo json_encode($invoice_to_edit); ?>;
    const invoiceDetailsToLoad = <?php echo json_encode($invoice_details_to_edit); ?>;

    let suppliers = initialFornitori;
    let products = initialProdottiEsistenti;
    let categories = initialCategorieProdotti;

    console.log("Prodotti caricati da PHP:", initialProdottiEsistenti);
    console.log("Categorie caricate da PHP:", categories);

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

    // ========== DOM ELEMENTS ==========
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
    const closeSupplierModalBtn = document.getElementById('closeSupplierModal');
    const newProductNameInput = document.getElementById('newProductName');
    const newProductCodeInput = document.getElementById('newProductCode');
    const newProductCategorySelect = document.getElementById('newProductCategory');
    const newProductPriceNetInput = document.getElementById('newProductPriceNet');
    const newProductPriceSale1Input = document.getElementById('newProductPriceSale1');
    const newProductPriceSale2Input = document.getElementById('newProductPriceSale2');
    const newProductQuantityInput = document.getElementById('newProductQuantity');
    const saveNewProductBtn = document.getElementById('saveNewProductBtn');
    const closeProductModalBtn = document.getElementById('closeProductModal');

    // ========== TOAST ==========
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        const iconClass = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
        toast.innerHTML = `
            <div class="toast-icon"><i class="fas ${iconClass}"></i></div>
            <div class="toast-content"><div class="toast-title">${message}</div></div>
            <button class="toast-close" onclick="this.parentElement.classList.remove('show'); setTimeout(()=>this.parentElement.remove(), 300);"><i class="fas fa-xmark"></i></button>
        `;
        container.appendChild(toast);
        requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }

    // Alias for backward compat with PHP-generated code
    function showMessage(msg, isError) {
        showToast(msg, isError ? 'error' : 'success');
    }

    // ========== GLOBAL ERROR HANDLER ==========
    window.addEventListener('error', function(e) {
        console.error('JS ERROR:', e.message, 'at', e.filename, 'line', e.lineno);
        showToast('[JS ERROR] ' + e.message, 'error');
    });

    // ========== MODALS ==========
    function openModalOverlay(id) { document.getElementById(id).classList.add('show'); }
    function closeModalOverlay(id) { document.getElementById(id).classList.remove('show'); }

    // Close modal on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('show'); });
    });

    // ========== UTILITY ==========
    function parseNumber(val) {
        if (typeof val === 'number') return val;
        if (!val && val !== 0) return 0;
        val = String(val).replace(',', '.');
        const n = parseFloat(val);
        return isNaN(n) ? 0 : n;
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(parseNumber(value));
    }

    // Escape HTML per evitare rotture nel template
    function escHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ========== AUTOCOMPLETE per Fornitore (rimane la versione semplice) ==========
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
                li.addEventListener('mousedown', (event) => {
                    event.preventDefault();
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
            ul.style.zIndex = '10000';
            currentAutocompleteList = ul;
        });

        inputElement.addEventListener('blur', () => setTimeout(() => hideAutocomplete(), 200));
        document.addEventListener('click', (event) => { if (currentAutocompleteList && !inputElement.contains(event.target) && !currentAutocompleteList.contains(event.target)) hideAutocomplete(); });
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

    // ========== SUPPLIER MODAL ==========
    addSupplierBtn.addEventListener('click', () => {
        newSupplierNameInput.value = ''; newSupplierPartitaIvaInput.value = ''; newSupplierCodiceFiscaleInput.value = '';
        newSupplierAddressInput.value = ''; newSupplierCityInput.value = ''; newSupplierCapInput.value = '';
        newSupplierProvinceInput.value = ''; newSupplierCountryInput.value = ''; newSupplierPhoneInput.value = '';
        newSupplierEmailInput.value = '';
        openModalOverlay('addSupplierModal');
    });
    closeSupplierModalBtn.addEventListener('click', () => closeModalOverlay('addSupplierModal'));
    saveNewSupplierBtn.addEventListener('click', async () => {
        const name = newSupplierNameInput.value.trim();
        if (!name) { showToast("Nome/Ragione Sociale è obbligatorio.", 'error'); return; }
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
                showToast(`Fornitore "${name}" aggiunto e selezionato.`, 'success');
                closeModalOverlay('addSupplierModal');
            } else { showToast(`Errore: ${result.message}`, 'error'); }
        } catch (error) { console.error("Errore aggiunta fornitore:", error); showToast("Errore di comunicazione con il server.", 'error'); }
    });

    // ========== PRODUCT MODAL ==========
    addNewProductBtn.addEventListener('click', () => {
        newProductNameInput.value = ''; newProductCodeInput.value = ''; newProductCategorySelect.value = '';
        newProductPriceNetInput.value = ''; newProductPriceSale1Input.value = ''; newProductPriceSale2Input.value = '';
        newProductQuantityInput.value = '';
        populateCategorySelects();
        openModalOverlay('addProductModal');
    });
    closeProductModalBtn.addEventListener('click', () => closeModalOverlay('addProductModal'));
    saveNewProductBtn.addEventListener('click', async () => {
        const name = newProductNameInput.value.trim();
        const categoryId = newProductCategorySelect.value;
        const priceNet = newProductPriceNetInput.value.trim();
        const quantity = newProductQuantityInput.value.trim();
        if (!name || !categoryId || priceNet === '' || quantity === '') {
            showToast("Nome, Categoria, Prezzo Netto e Quantità sono obbligatori.", 'error');
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
                showToast(`Prodotto "${name}" aggiunto al catalogo.`, 'success');
                closeModalOverlay('addProductModal');
            } else { showToast(`Errore: ${result.message}`, 'error'); }
        } catch (error) { console.error("Errore aggiunta prodotto:", error); showToast("Errore di comunicazione con il server.", 'error'); }
    });

    // ========== PRODUCT LINES v3.0 — INLINE HANDLERS ==========
    let lineCounter = currentInvoice.productLines.length;
    let _autocompleteCleanups = [];

    /* ---- Handler globali chiamati direttamente dagli attributi HTML oninput/onchange/onclick ---- */
    window._fieldInput = function(el, idx, field) {
        try {
            const line = currentInvoice.productLines[idx];
            if (!line) { console.warn('[_fieldInput] No line at index', idx); return; }
            const value = el.type === 'checkbox' ? el.checked : el.value;
            line[field] = value;
            console.log('[_fieldInput] idx=' + idx + ' field=' + field + ' value=' + value);

            if (field === 'productName') line.productId = '';

            if (field === 'isVatExempt') {
                const row = el.closest('tr');
                const vatSelect = row ? row.querySelector('select[data-field="vatRate"]') : null;
                if (line.isVatExempt) {
                    line._previousVatRate = line.vatRate;
                    line.vatRate = 0;
                    if (vatSelect) { vatSelect.value = '0'; vatSelect.disabled = true; }
                } else {
                    line.vatRate = line._previousVatRate || 22;
                    if (vatSelect) { vatSelect.value = String(line.vatRate); vatSelect.disabled = false; }
                }
            }

            recalcLine(idx);
        } catch(err) {
            console.error('[_fieldInput] ERROR:', err);
        }
    };

    window._removeRow = function(tempId) {
        currentInvoice.productLines = currentInvoice.productLines.filter(l => l.tempId !== tempId);
        renderProductLines();
    };

    const addProductLine = (productData = {}) => {
        const newLine = {
            tempId: 'line-' + lineCounter++, productId: productData.id || '', productName: productData.name || '',
            category: productData.category || '', quantity: '', unitMeasure: productData.um || 'pz',
            priceNet: productData.priceNet !== undefined ? productData.priceNet : '', vatRate: productData.vatRate !== undefined ? productData.vatRate : 22,
            isVatExempt: productData.isVatExempt || false, priceGross: 0, lineTotal: 0
        };
        currentInvoice.productLines.push(newLine);
        renderProductLines();
    };
    addProductLineBtn.addEventListener('click', () => addProductLine());

    // ----- Ricalcolo singola riga + totali globali -----
    function recalcLine(index) {
        const line = currentInvoice.productLines[index];
        if (!line) return;

        const qty = parseNumber(line.quantity);
        const net = parseNumber(line.priceNet);
        const vat = parseNumber(line.vatRate);
        const lineNet = qty * net;
        const lineVat = lineNet * (vat / 100);
        const lineGross = lineNet + lineVat;

        line.priceGross = (qty > 0) ? (lineGross / qty) : 0;
        line.lineTotal = lineGross;

        const row = productLinesBody.querySelector('tr[data-temp-id="' + line.tempId + '"]');
        if (row) {
            const pgEl = row.querySelector('[data-field-display="priceGross"]');
            const ltEl = row.querySelector('[data-field-display="lineTotal"]');
            if (pgEl) pgEl.value = formatCurrency(line.priceGross);
            if (ltEl) ltEl.value = formatCurrency(line.lineTotal);
        }

        recalcTotals();
    }

    function recalcTotals() {
        let totalNet = 0, totalVAT = 0, totalGross = 0;
        currentInvoice.productLines.forEach(line => {
            const qty = parseNumber(line.quantity);
            const net = parseNumber(line.priceNet);
            const vat = parseNumber(line.vatRate);
            const lineNet = qty * net;
            const lineVat = lineNet * (vat / 100);
            const lineGross = lineNet + lineVat;
            totalNet += lineNet;
            totalVAT += lineVat;
            totalGross += lineGross;
        });
        totalNetP.textContent = formatCurrency(totalNet);
        totalVATP.textContent = formatCurrency(totalVAT);
        totalGrossP.textContent = formatCurrency(totalGross);
    }

    // ----- Render completo delle righe con INLINE HANDLERS -----
    function renderProductLines() {
        try {
            _autocompleteCleanups.forEach(fn => { try { fn(); } catch(e){} });
            _autocompleteCleanups = [];
            productLinesBody.innerHTML = '';

            if (currentInvoice.productLines.length === 0) {
                productLinesBody.innerHTML = '<tr><td colspan="10" class="empty-row"><i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:8px;color:var(--text-muted);"></i>Nessun prodotto aggiunto. Clicca su "Aggiungi Riga" per iniziare.</td></tr>';
                recalcTotals();
                return;
            }

            currentInvoice.productLines.forEach((line, index) => {
                const row = document.createElement('tr');
                row.setAttribute('data-temp-id', line.tempId);

                const catOpts = categories.map(cat =>
                    '<option value="' + escHtml(cat.nome_categoria) + '"' + (line.category === cat.nome_categoria ? ' selected' : '') + '>' + escHtml(cat.nome_categoria) + '</option>'
                ).join('');

                const i = index;
                const tid = escHtml(line.tempId);

                row.innerHTML =
                    '<td><input type="text" value="' + escHtml(line.productName) + '" placeholder="Cerca prodotto..." class="cell-input" data-field="productName" data-index="' + i + '" oninput="window._fieldInput(this,' + i + ',\'productName\')"></td>' +
                    '<td><select class="cell-select" data-field="category" data-index="' + i + '" onchange="window._fieldInput(this,' + i + ',\'category\')"><option value="">Seleziona</option>' + catOpts + '</select></td>' +
                    '<td><input type="text" inputmode="decimal" value="' + escHtml(String(line.quantity)) + '" placeholder="0" class="cell-input" data-field="quantity" data-index="' + i + '" style="width:70px;text-align:center;" oninput="window._fieldInput(this,' + i + ',\'quantity\')"></td>' +
                    '<td><input type="text" value="' + escHtml(line.unitMeasure) + '" class="cell-input" data-field="unitMeasure" data-index="' + i + '" style="width:55px;text-align:center;" oninput="window._fieldInput(this,' + i + ',\'unitMeasure\')"></td>' +
                    '<td><input type="text" inputmode="decimal" value="' + escHtml(String(line.priceNet)) + '" placeholder="0.00" class="cell-input" data-field="priceNet" data-index="' + i + '" style="width:100px;text-align:right;" oninput="window._fieldInput(this,' + i + ',\'priceNet\')"></td>' +
                    '<td><select class="cell-select" data-field="vatRate" data-index="' + i + '"' + (line.isVatExempt ? ' disabled' : '') + ' style="width:80px;" onchange="window._fieldInput(this,' + i + ',\'vatRate\')">' +
                        '<option value="0"' + (parseNumber(line.vatRate) == 0 ? ' selected' : '') + '>0%</option>' +
                        '<option value="4"' + (parseNumber(line.vatRate) == 4 ? ' selected' : '') + '>4%</option>' +
                        '<option value="5"' + (parseNumber(line.vatRate) == 5 ? ' selected' : '') + '>5%</option>' +
                        '<option value="10"' + (parseNumber(line.vatRate) == 10 ? ' selected' : '') + '>10%</option>' +
                        '<option value="22"' + (parseNumber(line.vatRate) == 22 ? ' selected' : '') + '>22%</option>' +
                    '</select></td>' +
                    '<td style="text-align:center;"><input type="checkbox"' + (line.isVatExempt ? ' checked' : '') + ' class="cell-checkbox" data-field="isVatExempt" data-index="' + i + '" onchange="window._fieldInput(this,' + i + ',\'isVatExempt\')"></td>' +
                    '<td><input type="text" value="' + formatCurrency(line.priceGross) + '" class="cell-input readonly" data-field-display="priceGross" data-index="' + i + '" readonly tabindex="-1" style="width:110px;text-align:right;"></td>' +
                    '<td><input type="text" value="' + formatCurrency(line.lineTotal) + '" class="cell-input readonly" data-field-display="lineTotal" data-index="' + i + '" readonly tabindex="-1" style="width:120px;text-align:right;font-weight:700;"></td>' +
                    '<td style="text-align:center;"><button type="button" class="remove-btn" onclick="window._removeRow(\'' + tid + '\')" title="Rimuovi riga"><i class="fas fa-trash-can"></i></button></td>';

                productLinesBody.appendChild(row);

                // Autocomplete per il campo prodotto
                const nameInput = row.querySelector('[data-field="productName"]');
                if (nameInput) {
                    const cleanup = setupAutocompleteClean(nameInput, products,
                        function(item) { return item.name + ' (' + item.category + ')'; },
                        function(selectedProduct) {
                            var lineToUpdate = currentInvoice.productLines[index];
                            if (!lineToUpdate) return;
                            console.log('[AUTOCOMPLETE] Selected:', selectedProduct.name, 'index', index);

                            lineToUpdate.productId = selectedProduct.id;
                            lineToUpdate.productName = selectedProduct.name;
                            lineToUpdate.category = selectedProduct.category;
                            lineToUpdate.unitMeasure = selectedProduct.um || 'pz';
                            lineToUpdate.priceNet = selectedProduct.priceNet;
                            lineToUpdate.quantity = '';

                            var currentRow = productLinesBody.querySelector('tr[data-temp-id="' + lineToUpdate.tempId + '"]');
                            if (currentRow) {
                                var fName = currentRow.querySelector('[data-field="productName"]');
                                var fCat = currentRow.querySelector('[data-field="category"]');
                                var fQty = currentRow.querySelector('[data-field="quantity"]');
                                var fUm = currentRow.querySelector('[data-field="unitMeasure"]');
                                var fPrice = currentRow.querySelector('[data-field="priceNet"]');
                                if (fName) fName.value = selectedProduct.name;
                                if (fCat) fCat.value = selectedProduct.category;
                                if (fQty) { fQty.value = ''; fQty.focus(); }
                                if (fUm) fUm.value = lineToUpdate.unitMeasure;
                                if (fPrice) fPrice.value = selectedProduct.priceNet;
                            }
                            recalcLine(index);
                        }
                    );
                    _autocompleteCleanups.push(cleanup);
                }
            });

            recalcTotals();
            console.log('[renderProductLines] v3.0 inline handlers — Rendered', currentInvoice.productLines.length, 'rows');

        } catch(err) {
            console.error('[renderProductLines] ERROR:', err);
            alert('Errore rendering righe: ' + err.message);
        }
    }

    // ========== AUTOCOMPLETE CLEAN (con cleanup) ==========
    function setupAutocompleteClean(inputElement, dataArray, displayProperty, selectCallback) {
        let currentList = null;
        let destroyed = false;

        function hide() {
            if (currentList) { currentList.remove(); currentList = null; }
        }

        function onInput() {
            if (destroyed) return;
            hide();
            const term = inputElement.value.toLowerCase();
            if (term.length < 2) return;
            const filtered = dataArray.filter(item => displayProperty(item).toLowerCase().includes(term));
            if (filtered.length === 0) return;

            const ul = document.createElement('ul');
            ul.className = 'autocomplete-list-overlay';
            filtered.forEach(item => {
                const li = document.createElement('li');
                li.textContent = displayProperty(item);
                li.addEventListener('mousedown', (ev) => {
                    ev.preventDefault();
                    ev.stopPropagation();
                    selectCallback(item);
                    hide();
                });
                ul.appendChild(li);
            });
            document.body.appendChild(ul);
            const rect = inputElement.getBoundingClientRect();
            ul.style.position = 'absolute';
            ul.style.top = `${rect.bottom + window.scrollY}px`;
            ul.style.left = `${rect.left + window.scrollX}px`;
            ul.style.width = `${rect.width}px`;
            ul.style.zIndex = '10000';
            currentList = ul;
        }

        function onBlur() {
            setTimeout(() => hide(), 200);
        }

        function onDocClick(ev) {
            if (destroyed) return;
            if (currentList && !inputElement.contains(ev.target) && !currentList.contains(ev.target)) {
                hide();
            }
        }

        inputElement.addEventListener('input', onInput);
        inputElement.addEventListener('blur', onBlur);
        document.addEventListener('click', onDocClick);

        // Ritorna funzione di cleanup
        return function cleanup() {
            destroyed = true;
            hide();
            inputElement.removeEventListener('input', onInput);
            inputElement.removeEventListener('blur', onBlur);
            document.removeEventListener('click', onDocClick);
        };
    }

    // ========== INIT ==========
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
            showToast("Fornitore è obbligatorio.", 'error');
            event.preventDefault(); return;
        }
        if (currentInvoice.productLines.length === 0) {
            showToast("Inserisci almeno una linea prodotto.", 'error');
            event.preventDefault(); return;
        }
        for (let i = 0; i < currentInvoice.productLines.length; i++) {
            const line = currentInvoice.productLines[i];
            if (!line.productName || line.quantity === '' || line.priceNet === '' || !line.category) {
                showToast(`Compila tutti i campi (Prodotto, Quantità, Prezzo Netto, Categoria) per la riga ${i + 1}.`, 'error');
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
