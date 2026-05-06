<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) { session_start(); }
if (!headers_sent()) { ob_start('ob_gzhandler'); }
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



// Includi il file di connessione al database MySQL
// Supponiamo che db.php si connetta e gestisca l'errore di connessione impostando $db_connection_error
if (!file_exists('db.php')) {
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: Il file db.php non è stato trovato!</div>";
    exit;
}
require_once 'db.php';

// Controlla subito se c'è stato un errore di connessione al database da db.php
if (isset($db_connection_error) && $db_connection_error !== null) {
    // Se la richiesta è AJAX, restituisci JSON con l'errore
    // MODIFICA QUI: Aggiunto ?? '' a $_SERVER['HTTP_X_REQUESTED_WITH'] per evitare null in strtolower()
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest' || isset($_POST['form_type'])) { 
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Errore di connessione al database per richiesta POST: ' . $db_connection_error
        ]);
        exit;
    } else {
        $_SESSION['message'] = 'Errore critico: La connessione al database non è stata stabilita correttamente! ' . htmlspecialchars($db_connection_error, ENT_QUOTES, 'UTF-8');
        $_SESSION['isError'] = true;
    }
}

// Variabili per i messaggi di feedback dei vari form
$feedback_message = ''; // Riparazione
$gift_card_feedback_message = ''; // Buono Regalo
$permuta_feedback_message = ''; // Permuta
$prenotazione_feedback_message = ''; // Prenotazione Prodotto

// Inizializza la variabile message per l'uso nel HTML (per messaggi da sessione)
$message_from_session = '';
// Controlla se ci sono messaggi da visualizzare dalla sessione
if (isset($_SESSION['message'])) {
    $sessionMessage = $_SESSION['message'];
    $sessionIsError = $_SESSION['isError'] ?? false;
    $message_from_session = "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('" . addslashes($sessionMessage) . "', " . ($sessionIsError ? 'true' : 'false') . "); });</script>";
    unset($_SESSION['message']);
    unset($_SESSION['isError']);
}

// --- 2. Gestione dell'invio dei form (POST) ---
$new_riparazione_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Distinguere tra i form inviati tramite il campo nascosto 'form_type'
    if (isset($_POST['form_type'])) {
        switch ($_POST['form_type']) {
            case 'riparazione':
                // Logica per il form di riparazione
                $cliente_id = (int)($_POST['cliente_id'] ?? 0); // Default a 0 se null
                $modello = $conn->real_escape_string($_POST['modello'] ?? '');
                $imei = $conn->real_escape_string($_POST['imei'] ?? '');
                $codice_sblocco = $conn->real_escape_string($_POST['codice_sblocco'] ?? '');
                $account = $conn->real_escape_string($_POST['account'] ?? '');
                $diagnosi = $conn->real_escape_string($_POST['diagnosi'] ?? '');
                $salva_dati = isset($_POST['salva_dati']) ? 1 : 0;
                $costo_preventivato = !empty($_POST['costo_preventivato']) ? (float)$_POST['costo_preventivato'] : 'NULL';
                $costo_effettivo = !empty($_POST['costo_effettivo']) ? (float)$_POST['costo_effettivo'] : 'NULL';
                $hardware_ritirato = $conn->real_escape_string($_POST['hardware_ritirato'] ?? '');
                $dispositivo_sostitutivo = $conn->real_escape_string($_POST['dispositivo_sostitutivo'] ?? '');
                $stato = $conn->real_escape_string($_POST['stato'] ?? '');
                $codice_sblocco_grafico = $conn->real_escape_string($_POST['codice_sblocco_grafico'] ?? '');
                
                $sql_insert = "INSERT INTO riparazioni (
                    cliente_id, modello, imei, codice_sblocco, codice_sblocco_grafico, 
                    account, diagnosi, salva_dati, costo_preventivato, 
                    costo_effettivo, hardware_ritirato, dispositivo_sostitutivo, stato, data_creazione
                ) VALUES (
                    '$cliente_id', '$modello', '$imei', '$codice_sblocco', '$codice_sblocco_grafico',
                    '$account', '$diagnosi', '$salva_dati', $costo_preventivato, 
                    $costo_effettivo, '$hardware_ritirato', '$dispositivo_sostitutivo', '$stato', NOW()
                )";
        
                if ($conn->query($sql_insert) === TRUE) {
                    $new_riparazione_id = $conn->insert_id;
                    $feedback_message = "<div class='feedback success'>Scheda di riparazione salvata con successo! <a href='stampa_riparazione.php?id=" . $new_riparazione_id . "' target='_blank' style='color:#fff;text-decoration:underline;font-weight:600;margin-left:8px;'>🖨️ Stampa</a></div>";
                } else {
                    $feedback_message = "<div class='feedback error'>Errore durante il salvataggio: " . $conn->error . "</div>";
                }
                break;

            case 'buono_regalo':
                // Logica per il form del buono regalo
                $nome_buono = $conn->real_escape_string($_POST['codice_buono'] ?? '');
                $valore_buono = !empty($_POST['valore']) ? (float)$_POST['valore'] : 'NULL';
                $destinatario_buono = !empty($_POST['destinatario']) ? "'" . $conn->real_escape_string($_POST['destinatario']) . "'" : 'NULL';
                $note_buono = !empty($_POST['mittente_note']) ? "'" . $conn->real_escape_string($_POST['mittente_note']) . "'" : 'NULL';
                $data_scadenza_buono = !empty($_POST['data_scadenza']) ? "'" . $conn->real_escape_string($_POST['data_scadenza']) . "'" : "'" . date('Y-m-d', strtotime('+1 year')) . "'";
                $stato_buono = $conn->real_escape_string($_POST['stato_buono'] ?? '');
        
                $sql_insert_buono = "INSERT INTO buoni_regalo (
                    nome, valore, data_scadenza, destinatario, note, stato, data_creazione
                ) VALUES (
                    '$nome_buono', $valore_buono, $data_scadenza_buono, $destinatario_buono, $note_buono, '$stato_buono', NOW()
                )";
        
                if ($conn->query($sql_insert_buono) === TRUE) {
                    $gift_card_feedback_message = "<div class='feedback success'>Buono regalo creato con successo! Il popup si chiuderà tra 1 secondo.</div>";
                } else {
                    $gift_card_feedback_message = "<div class='feedback error'>Errore durante la creazione del buono: " . $conn->error . "</div>";
                }
                break;

            case 'permuta':
                // Logica per il form di permuta
                $numero_progressivo = (int)($_POST['numero_progressivo'] ?? 0);
                $data_permuta_form = $conn->real_escape_string($_POST['data_permuta'] ?? ''); // Mappa a 'data' nel DB
                $cliente_id = (int)($_POST['cliente_id'] ?? 0);
                $cliente_display_name = $conn->real_escape_string($_POST['cliente_display'] ?? ''); // Nuovo: per la colonna 'cliente'
                $telefono_cliente_var = $conn->real_escape_string($_POST['telefono_cliente'] ?? ''); // Mappa a 'telefono_cliente' nel DB
                $stato_permuta_var = $conn->real_escape_string($_POST['stato_permuta'] ?? ''); // Mappa a 'status' nel DB
                $tuo_modello_var = $conn->real_escape_string($_POST['tuo_modello'] ?? ''); // Mappa a 'modello_nuovo'
                $tuo_imei_var = $conn->real_escape_string($_POST['tuo_imei'] ?? ''); // Mappa a 'imei_nuovo'
                $tuo_valore_vendita_var = !empty($_POST['tuo_valore_vendita']) ? (float)$_POST['tuo_valore_vendita'] : 'NULL'; // Mappa a 'prezzo_nuovo'
                $tuo_note_var = $conn->real_escape_string($_POST['tuo_note'] ?? ''); // Mappa a 'note_nuovo'
                $cliente_modello_var = $conn->real_escape_string($_POST['cliente_modello'] ?? ''); // Mappa a 'modello_usato'
                $cliente_imei_var = $conn->real_escape_string($_POST['cliente_imei'] ?? ''); // Mappa a 'imei_usato'
                $cliente_note_var = $conn->real_escape_string($_POST['cliente_note'] ?? ''); // Mappa a 'note_usato'
                $cliente_valore_permuta_var = !empty($_POST['cliente_valore_permuta']) ? (float)$_POST['cliente_valore_permuta'] : 'NULL'; // Mappa a 'prezzo_permuta'
                $totale_costi_ricondizionamento_val_var = !empty($_POST['totale_costi_ricondizionamento_val']) ? (float)$_POST['totale_costi_ricondizionamento_val'] : 'NULL'; // Mappa a 'costo_riparazione'
                $costo_accessori_input_var = !empty($_POST['costo_accessori_input']) ? (float)$_POST['costo_accessori_input'] : 'NULL'; // Mappa a 'costo_accessori'
                $costo_prodotto_input_var = !empty($_POST['costo_prodotto_input']) ? (float)$_POST['costo_prodotto_input'] : 'NULL'; // Mappa a 'costo_prodotto'
                $prezzo_vendita_input_var = !empty($_POST['prezzo_vendita_input']) ? (float)$_POST['prezzo_vendita_input'] : 'NULL'; // Mappa a 'prezzo_vendita'
                $valore_netto_ricevuto_val_var = !empty($_POST['valore_netto_ricevuto_val']) ? (float)$_POST['valore_netto_ricevuto_val'] : 'NULL'; // Mappa a 'differenza'
                $note_generali_input_var = $conn->real_escape_string($_POST['note_generali_input'] ?? ''); // Mappa a 'note_generali'

                // JSON data for 'test_ok'
                $valutazione_tecnica = [];
                $valutazione_campi = [
                    'display', 'touch', 'batteria', 'cam_post', 'cam_ant', 'audio', 'mic',
                    'wifi', 'bt', 'ricarica', 'tasti', 'sensori', 'sblocco_bio', 'reset_fabbrica', 'accounts', 'altro'
                ];
                foreach ($valutazione_campi as $campo) {
                    $esito_key = "test_{$campo}_esito";
                    $note_key = "test_{$campo}_note";
                    $valutazione_tecnica[$campo] = [
                        'esito' => isset($_POST[$esito_key]) ? $conn->real_escape_string($_POST[$esito_key]) : '',
                        'note' => isset($_POST[$note_key]) ? $conn->real_escape_string($_POST[$note_key]) : ''
                    ];
                }
                $valutazione_json = json_encode($valutazione_tecnica); // Mappa a 'test_ok'

                // Photo paths for 'foto_ceduto_paths'
                $tuo_foto_paths = [];
                if (isset($_FILES['tuo_foto']) && is_array($_FILES['tuo_foto']['name'])) {
                    foreach ($_FILES['tuo_foto']['name'] as $key => $name) {
                        if ($_FILES['tuo_foto']['error'][$key] == UPLOAD_ERR_OK) {
                            $tmp_name = $_FILES['tuo_foto']['tmp_name'][$key];
                            $upload_dir = 'uploads/'; // Assicurati che questa cartella esista e sia scrivibile
                            $filename = uniqid('tuo_foto_') . '_' . basename($name);
                            // move_uploaded_file($tmp_name, $upload_dir . $filename); // In un ambiente reale, esegui il move
                            $tuo_foto_paths[] = $upload_dir . $filename; // Salva il percorso fittizio
                        }
                    }
                }
                $tuo_foto_json = json_encode($tuo_foto_paths); // Mappa a 'foto_ceduto_paths'

                // Photo paths for 'foto_ricevuto_paths'
                $cliente_foto_paths = [];
                if (isset($_FILES['cliente_foto']) && is_array($_FILES['cliente_foto']['name'])) {
                    foreach ($_FILES['cliente_foto']['name'] as $key => $name) {
                        if ($_FILES['cliente_foto']['error'][$key] == UPLOAD_ERR_OK) {
                            $tmp_name = $_FILES['cliente_foto']['tmp_name'][$key];
                            $upload_dir = 'uploads/';
                            $filename = uniqid('cliente_foto_') . '_' . basename($name);
                            // move_uploaded_file($tmp_name, $upload_dir . $filename);
                            $cliente_foto_paths[] = $upload_dir . $filename;
                        }
                    }
                }
                $cliente_foto_json = json_encode($cliente_foto_paths); // Mappa a 'foto_ricevuto_paths'

                // Inizia la transazione per garantire l'atomicità
                mysqli_begin_transaction($conn);

                try {
                    $sql_insert_permuta = "INSERT INTO permute_nuovo (
                        progressivo, data, cliente_id, telefono_cliente, cliente, status,
                        modello_nuovo, imei_nuovo, prezzo_nuovo, note_nuovo, foto_ceduto_paths,
                        modello_usato, imei_usato, note_usato, prezzo_permuta, foto_ricevuto_paths,
                        test_ok, costo_riparazione,
                        costo_accessori, costo_prodotto, prezzo_vendita, differenza, note_generali, created_at
                    ) VALUES (
                        '$numero_progressivo', '$data_permuta_form', '$cliente_id', '$telefono_cliente_var', '$cliente_display_name', '$stato_permuta_var',
                        '$tuo_modello_var', '$tuo_imei_var', $tuo_valore_vendita_var, '$tuo_note_var', '$tuo_foto_json',
                        '$cliente_modello_var', '$cliente_imei_var', '$cliente_note_var', $cliente_valore_permuta_var, '$cliente_foto_json',
                        '$valutazione_json', $totale_costi_ricondizionamento_val_var,
                        $costo_accessori_input_var, $costo_prodotto_input_var, $prezzo_vendita_input_var, $valore_netto_ricevuto_val_var, '$note_generali_input_var', NOW()
                    )";
            
                    if ($conn->query($sql_insert_permuta) === FALSE) {
                        throw new Exception("Errore durante il salvataggio della permuta: " . $conn->error);
                    }
                    $new_permuta_id = $conn->insert_id;

                    // --- Inserimento del prodotto permutato nella tabella 'prodotti' ---
                    $nome_prodotto_permutato = 'PMT-' . $conn->real_escape_string($_POST['cliente_modello'] ?? '');
                    // MODIFICA QUI: Categoria cambiata da 'Permuta' a 'Usato'
                    $categoria_prodotto_permutato = 'Usato';
                    $quantita_prodotto_permutato = 1;
                    $prezzo_acquisto_prodotto_permutato = $cliente_valore_permuta_var !== 'NULL' ? (float)$cliente_valore_permuta_var : 0.00;
                    
                    // Calcola il prezzo di vendita stimato per il prodotto permutato
                    $prezzo_vendita_prodotto_permutato_calc = $prezzo_acquisto_prodotto_permutato + ($totale_costi_ricondizionamento_val_var !== 'NULL' ? (float)$totale_costi_ricondizionamento_val_var : 0);
                    $prezzo_vendita_prodotto_permutato = number_format($prezzo_vendita_prodotto_permutato_calc, 2, '.', ''); // Formatta a 2 decimali

                    $sql_insert_prodotto_permutato = "INSERT INTO prodotti (
                        nome, categoria, quantita, prezzo_acquisto, prezzo_vendita1, barcode, data_creazione
                    ) VALUES (
                        '$nome_prodotto_permutato', '$categoria_prodotto_permutato', '$quantita_prodotto_permutato', 
                        '$prezzo_acquisto_prodotto_permutato', '$prezzo_vendita_prodotto_permutato', NULL, NOW()
                    )";

                    if ($conn->query($sql_insert_prodotto_permutato) === FALSE) {
                        throw new Exception("Errore durante il salvataggio del prodotto permutato nel magazzino: " . $conn->error);
                    }

                    // Se tutto va bene, esegui il commit
                    mysqli_commit($conn);
                    $permuta_feedback_message = "<div class='feedback success'>Permuta salvata con successo! Prodotto permutato aggiunto al magazzino. Numero Permuta: PMT-" . str_pad($new_permuta_id, 5, '0', STR_PAD_LEFT) . " Il popup si chiuderà tra 1 secondo.</div>";

                } catch (Exception $e) {
                    // Se qualcosa va storto, esegui il rollback
                    mysqli_rollback($conn);
                    $permuta_feedback_message = "<div class='feedback error'>Errore durante il salvataggio della permuta: " . $e->getMessage() . "</div>";
                    error_log("ERRORE SALVATAGGIO PERMUTA: " . $e->getMessage()); // Log dell'errore per debugging
                }
                break;

            case 'prenotazione_prodotto':
                // Logica per il form di prenotazione prodotto
                $product_id = null; // Il prodotto è digitato liberamente, quindi non avrà un ID da `prodotti`
                $product_name = $_POST['productName'] ?? '';
                $quantity = $_POST['quantity'] ?? 0;
                $unit_price = $_POST['unitPrice'] ?? 0;
                $client_id_prenotazione = $_POST['clientId'] ?? null; // ID del cliente selezionato nel form prenotazione
                $customer_name = $_POST['customerName'] ?? '';
                $customer_phone = $_POST['customerPhone'] ?? null;
                $customer_secondary_phone = $_POST['customerSecondaryPhone'] ?? null;
                $customer_email = $_POST['customerEmail'] ?? null;
                $reservation_date = $_POST['reservationDate'] ?? '';
                $notes = $_POST['notes'] ?? null;
                $product_total_price = $_POST['productTotalPrice'] ?? 0;
                $deposit_amount = $_POST['depositAmount'] ?? 0;
                $remaining_amount = $_POST['remainingAmount'] ?? 0;
                $status = 'Pending';
            
                if (empty($product_name) || !is_numeric($quantity) || $quantity <= 0 || !is_numeric($unit_price) || $unit_price <= 0 || empty($customer_name) || empty($reservation_date)) {
                    $prenotazione_feedback_message = "<div class='feedback error'>Tutti i campi obbligatori (Prodotto, Quantità, Prezzo Unitario, Nome Cliente, Data Prenotazione) devono essere validi e compilati.</div>";
                    break; // Esci dallo switch, il feedback verrà mostrato nel popup
                }
            
                try {
                    mysqli_begin_transaction($conn);
            
                    $stmt_insert_reservation = $conn->prepare("INSERT INTO prenotazioni_prodotti (product_id, product_name, unit_price, quantity, client_id, customer_name, customer_phone, customer_secondary_phone, customer_email, reservation_date, notes, product_total_price, deposit_amount, remaining_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt_insert_reservation === false) {
                        throw new mysqli_sql_exception("Errore nella preparazione della query di inserimento prenotazione: " . $conn->error);
                    }
                    // 'isdisssssssddds'
                    $stmt_insert_reservation->bind_param('isdisssssssddds',
                        $product_id,
                        $product_name,
                        $unit_price,
                        $quantity,
                        $client_id_prenotazione, // Usa l'ID cliente specifico per la prenotazione
                        $customer_name,
                        $customer_phone,
                        $customer_secondary_phone,
                        $customer_email,
                        $reservation_date,
                        $notes,
                        $product_total_price,
                        $deposit_amount,
                        $remaining_amount,
                        $status
                    );
                    if ($stmt_insert_reservation->execute() === false) {
                        throw new mysqli_sql_exception("Errore nell'esecuzione dell'inserimento prenotazione: " . $stmt_insert_reservation->error);
                    }
                    
                    $id_prenotazione_appena_salvata = $conn->insert_id;
                    $stmt_insert_reservation->close();
            
                    mysqli_commit($conn);
                    
                    $prenotazione_feedback_message = "<div class='feedback success'>Prenotazione creata con successo! Stampa in corso...</div>";
                    // Reindirizza alla pagina di stampa solo dopo aver mostrato il messaggio di successo nel popup
                    // Questo renderà il popup e poi redirigerà, dando all'utente un feedback visivo immediato.
                    echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                showMessage('Prenotazione creata con successo! Preparazione per la stampa.', false);
                                setTimeout(() => { 
                                    window.location.href = 'stampa_prenotazione.php?id=" . $id_prenotazione_appena_salvata . "'; 
                                }, 1000);
                            });
                          </script>";
                } catch (mysqli_sql_exception $e) {
                    mysqli_rollback($conn);
                    $prenotazione_feedback_message = "<div class='feedback error'>Errore database: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
                    error_log("ERRORE SALVATAGGIO PRENOTAZIONE (SQL): " . $e->getMessage() . " - Stack: " . $e->getTraceAsString());
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $prenotazione_feedback_message = "<div class='feedback error'>Errore generale: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
                    error_log("ERRORE GENERALE SALVATAGGIO PRENOTAZIONE: " . $e->getMessage() . " - Stack: " . $e->getTraceAsString());
                }
                break;
        }
    }
}


// --- 3. Recupero dati per popolare il form (Clienti e Prodotti per autocomplete) ---
// Per l'autocomplete generale dei clienti (usato da permuta, prenotazione e riparazione)
$clienti_esistenti = [];
try {
    $result_clienti_autocomplete = $conn->query("SELECT id, nome, cognome, ragione_sociale, telefono, email FROM clienti_nuovo ORDER BY nome, cognome");
    if ($result_clienti_autocomplete) {
        $clienti_raw = $result_clienti_autocomplete->fetch_all(MYSQLI_ASSOC);
        $result_clienti_autocomplete->free();

        foreach($clienti_raw as $c) {
            $displayName = '';
            if (!empty($c['ragione_sociale'])) {
                $displayName = $c['ragione_sociale'];
            } else {
                $displayName = trim($c['nome'] . ' ' . $c['cognome']);
            }
            if (empty($displayName)) {
                $displayName = 'ID Cliente: ' . $c['id'];
            }

            $clienti_esistenti[] = [
                'id' => $c['id'],
                'nome' => $c['nome'],
                'cognome' => $c['cognome'],
                'ragione_sociale' => $c['ragione_sociale'],
                'telefono_principale' => $c['telefono'],
                'telefono_secondario' => null, // Non presente nella query, assumo sia sempre null qui
                'email' => $c['email'],
                'display_name' => $displayName
            ];
        }
    }
} catch (mysqli_sql_exception $e) {
    error_log("Errore nel caricamento clienti per autocomplete: " . $e->getMessage());
}

// Prodotti esistenti per autocomplete (per permuta)
$prodotti_esistenti = [];
try {
    $result_prodotti = $conn->query("SELECT id, nome, categoria, quantita, prezzo_acquisto, barcode, prezzo_vendita1, prezzo_vendita2 FROM prodotti ORDER BY nome");
    if ($result_prodotti) {
        $prodotti_raw = $result_prodotti->fetch_all(MYSQLI_ASSOC);
        $result_prodotti->free();

        foreach($prodotti_raw as $p) {
            $prodotti_esistenti[] = [
                'id' => $p['id'],
                'name' => $p['nome'],
                'category' => $p['categoria'],
                'current_stock' => (int)$p['quantita'],
                'priceNet' => (float)($p['prezzo_acquisto'] ?? 0.00), // Coalescing per sicurezza
                'priceSale1' => (float)($p['prezzo_vendita1'] ?? 0.00),
                'priceSale2' => (float)($p['prezzo_vendita2'] ?? 0.00),
                'code' => $p['barcode'],
                'um' => 'pz'
            ];
        }
    }
} catch (mysqli_sql_exception $e) {
    error_log("Errore nel caricamento prodotti per autocomplete: " . $e->getMessage());
}

// Recupera le informazioni dell'utente dalla sessione
$current_user_name = $_SESSION['user_name'] ?? 'Ospite'; // Nome utente, default 'Ospite'
$current_user_role = $_SESSION['role'] ?? 'N/D'; // Ruolo utente, default 'N/D'

// Chiusura della connessione al database
// $conn->close(); // Rimosso per evitare "mysqli object is already closed"

?>

<!-- INIZIO HEADER MODIFICATO -->
<header class="top-bar">
  <a href="homepage.php" class="logo">TS SERVICE</a>

  <!-- Barra di Ricerca Globale -->
  <div class="search-container">
    <i class="fas fa-search"></i>
    <input type="search" class="search-bar" placeholder="Cerca cliente, prodotto, riparazione...">
    <!-- Contenitore per i risultati della ricerca -->
    <div class="search-results-dropdown" id="searchResultsDropdown"></div>
  </div>
  
  <!-- Hamburger Button per Mobile -->
  <button class="hamburger-btn" id="hamburgerBtn">
    <span></span>
    <span></span>
    <span></span>
  </button>

  <nav class="mobile-nav">
    <ul>
      <li><a href="dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a></li>
      <li><a href="#"><i class="fas fa-cash-register"></i> Vendita</a></li>

      <li class="has-dropdown">
        <button><i class="fas fa-tools"></i> Assistenza</button>
        <ul class="dropdown">
          <li><button id="openNuovaAssistenzaPopupBtn">Nuova assistenza</button></li>
          <li><a href="gestisci_ticket.php">Gestisci ticket</a></li>
          <li><a href="storico_riparazioni.php">Storico riparazioni</a></li>
        </ul>
      </li>

      <li class="has-dropdown">
        <button><i class="fas fa-file-alt"></i> Moduli</button>
        <ul class="dropdown">
          <li class="has-submenu">
            <a href="#">Permuta</a>
            <ul class="submenu">
              <li><button id="openNuovaPermutaPopupBtn">Crea</button></li>
              <li><a href="storico_permute.php">Visualizza</a></li>
            </ul>
          </li>
          <li class="has-submenu">
            <a href="#">Prenotazione prodotto</a>
            <ul class="submenu">
              <li><button id="openPrenotazioneProdottoPopupBtn">Crea</button></li>
              <li><a href="visualizza_prenotazioni.php">Visualizza</a></li>
            </ul>
          </li>
          <li class="has-submenu">
            <a href="#">Buono regalo</a>
            <ul class="submenu">
              <li><button id="openBuonoRegaloPopupBtn">Crea</button></li>
              <li><a href="visualizza_buoni.php">Visualizza</a></li>
            </ul>
          </li>
        </ul>
      </li>

      <li class="has-dropdown">
        <button><i class="fas fa-warehouse"></i> Magazzino</button>
        <ul class="dropdown">
          <li><a href="inventario.php">Inventario</a></li>
          <li><a href="reportistica.php">Report e Analisi</a></li>
          <li><a href="gestione_fornitori.php">Fornitori</a></li>
          <li><a href="gestione_resi.php">Resi e Rimborsi</a></li>
          <li class="has-submenu">
            <a href="#">Fatture</a>
            <ul class="submenu">
              <li><a href="gestione_fatture.php">Inserisci Fatture</a></li>
              <li><a href="visualizza_fatture.php">Visualizza Fatture</a></li>
            </ul>
          </li>
        </ul>
      </li>

      <li class="has-dropdown">
        <button><i class="fas fa-cogs"></i> Amministrazione</button>
        <ul class="dropdown">
          <li><a href="gestione_categorie.php">Categorie/Sottocategorie</a></li>
          <li><a href="settings.php">Impostazioni</a></li>
          <li><a href="gestione_utenti.php">Gestione Utenti</a></li>
          <li><a href="audit_log.php">Registro Attivit&agrave;</a></li>
        </ul>
      </li>
    </ul>
  </nav>

  <!-- Menu Utente Migliorato -->
  <div class="user-menu-container" id="userMenuContainer">
    <div class="user-greeting">Ciao, <span class="user-name"><?php echo htmlspecialchars(explode(' ', $current_user_name)[0]); ?></span></div>
    <span class="user-icon-trigger"><i class="fas fa-user-circle"></i></span>
    <div class="user-dropdown">
        <div class="user-dropdown-info">
            <strong><?php echo htmlspecialchars($current_user_name); ?></strong>
            <span><?php echo htmlspecialchars($current_user_role); ?></span>
        </div>
        <a href="logout.php" class="logout-button">Logout</a>
    </div>
  </div>
</header>
<!-- FINE HEADER MODIFICATO -->

<!-- Qui inizia il blocco del popup/wizard della Nuova Scheda di Riparazione -->
<div class="popup-overlay" id="riparazionePopup">
    <div class="wizard-container">
        <button type="button" class="close-btn" id="close-riparazione-popup-btn">&times;</button>
        
        <!-- Header con icona -->
        <div class="wizard-header">
            <div class="wizard-header-content">
                <div class="wizard-header-icon">
                    <svg viewBox="0 0 24 24" stroke-width="2">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                    </svg>
                </div>
                <h1>Nuova Scheda di Riparazione</h1>
            </div>
        </div>

        <!-- Stepper con icone -->
        <div class="stepper-nav">
            <div class="stepper-wrapper">
                <div class="step active" data-step="1">
                    <div class="step-bubble">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="step-label">Cliente</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-bubble">
                        <svg viewBox="0 0 24 24">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                            <line x1="12" y1="18" x2="12.01" y2="18"></line>
                        </svg>
                    </div>
                    <div class="step-label">Dispositivo</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-bubble">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <div class="step-label">Sblocco</div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-bubble">
                        <svg viewBox="0 0 24 24">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                        </svg>
                    </div>
                    <div class="step-label">Laboratorio</div>
                </div>
            </div>
        </div>
        
        <form method="POST" action="" id="riparazione-form">
            <input type="hidden" name="form_type" value="riparazione">
            <div class="wizard-body">
                <?php if(!empty($feedback_message)) echo $feedback_message; ?>
                
                <!-- Step 1: Cliente -->
                <div class="step-pane active" data-step="1">
                    <div class="form-card">
                        <div class="form-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Dati Cliente
                        </div>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="cliente_riparazione_autocomplete">Seleziona Cliente *</label>
                                <div class="client-input-container">
                                    <input type="text" id="cliente_riparazione_autocomplete" name="cliente_display_riparazione" placeholder="Cerca o seleziona cliente..." autocomplete="off" required>
                                    <input type="hidden" id="cliente_id_riparazione" name="cliente_id">
                                    <div id="cliente_suggestions_riparazione" class="autocomplete-list"></div>
                                    <i class="fas fa-plus-circle add-client-icon" id="open_new_client_modal_btn_riparazione" title="Aggiungi nuovo cliente"></i>
                                </div>
                            </div>
                            <div class="form-group full-width">
                                <label>Telefono</label>
                                <div class="telefono-chip" id="telefono-chip-riparazione">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    </svg>
                                    <span id="telefono_riparazione_text">Seleziona un cliente...</span>
                                </div>
                                <input type="hidden" id="telefono_riparazione_display" name="telefono_display">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Dispositivo -->
                <div class="step-pane" data-step="2">
                    <div class="form-card">
                        <div class="form-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                                <line x1="12" y1="18" x2="12.01" y2="18"></line>
                            </svg>
                            Informazioni Dispositivo
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="modello">Modello Dispositivo *</label>
                                <div class="input-wrapper">
                                    <input type="text" id="modello" name="modello" required autocomplete="off" placeholder="es. iPhone 14 Pro">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                                        <line x1="12" y1="18" x2="12.01" y2="18"></line>
                                    </svg>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="imei_riparazione">IMEI / Seriale</label>
                                <div class="input-wrapper">
                                    <input type="text" id="imei_riparazione" name="imei" autocomplete="off" placeholder="Inserisci IMEI">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <rect x="3" y="4" width="18" height="4" rx="1"></rect>
                                        <path d="M3 8h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="form-group full-width">
                                <label for="diagnosi">Diagnosi / Problema riscontrato *</label>
                                <textarea id="diagnosi" name="diagnosi" rows="4" required placeholder="Descrivi il problema segnalato dal cliente..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Sblocco -->
                <div class="step-pane" data-step="3">
                    <div class="form-card">
                        <div class="form-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            Codici di Sblocco
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="codice_sblocco">Codice PIN / Password</label>
                                <div class="input-wrapper">
                                    <input type="text" id="codice_sblocco" name="codice_sblocco" autocomplete="off" placeholder="es. 123456">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="account">Account (Google, iCloud...)</label>
                                <div class="input-wrapper">
                                    <input type="text" id="account" name="account" autocomplete="off" placeholder="es. email@esempio.com">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="4"></circle>
                                        <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="form-group full-width">
                                <label>Codice Sblocco Grafico (Pattern)</label>
                                <div class="pattern-section">
                                    <div class="pattern-section-header">
                                        <h4>🔒 Pattern Lock</h4>
                                        <p>Trascinare per disegnare la sequenza</p>
                                    </div>
                                    <div id="pattern-lock" class="pattern-lock">
                                        <canvas id="pattern-canvas" width="220" height="220"></canvas>
                                        <div class="pattern-dot" data-dot="1"></div>
                                        <div class="pattern-dot" data-dot="2"></div>
                                        <div class="pattern-dot" data-dot="3"></div>
                                        <div class="pattern-dot" data-dot="4"></div>
                                        <div class="pattern-dot" data-dot="5"></div>
                                        <div class="pattern-dot" data-dot="6"></div>
                                        <div class="pattern-dot" data-dot="7"></div>
                                        <div class="pattern-dot" data-dot="8"></div>
                                        <div class="pattern-dot" data-dot="9"></div>
                                    </div>
                                    <input type="hidden" id="unlock-pattern" name="codice_sblocco_grafico" />
                                    <p class="pattern-hint">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M12 16v-4"></path>
                                            <path d="M12 8h.01"></path>
                                        </svg>
                                        Clicca su Reset per cancellare
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 4: Laboratorio -->
                <div class="step-pane" data-step="4">
                    <div class="form-card">
                        <div class="form-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17.5 7.5C16 5 13.5 3.5 10.5 3.5 6.4 3.5 3 7 3 12s3.4 8.5 7.5 8.5c3 0 5.5-1.5 7-4"></path>
                                <line x1="2" y1="10" x2="14" y2="10"></line>
                                <line x1="2" y1="14" x2="14" y2="14"></line>
                            </svg>
                            Costi e Preventivo
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="costo_preventivato">Costo Preventivato (€)</label>
                                <div class="input-wrapper">
                                    <input type="number" id="costo_preventivato" name="costo_preventivato" step="0.01" min="0" placeholder="0.00">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <path d="M17.5 7.5C16 5 13.5 3.5 10.5 3.5 6.4 3.5 3 7 3 12s3.4 8.5 7.5 8.5c3 0 5.5-1.5 7-4"></path>
                                        <line x1="2" y1="10" x2="14" y2="10"></line>
                                        <line x1="2" y1="14" x2="14" y2="14"></line>
                                    </svg>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="costo_effettivo">Costo Effettivo (€)</label>
                                <div class="input-wrapper">
                                    <input type="number" id="costo_effettivo" name="costo_effettivo" step="0.01" min="0" placeholder="0.00">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <path d="M17.5 7.5C16 5 13.5 3.5 10.5 3.5 6.4 3.5 3 7 3 12s3.4 8.5 7.5 8.5c3 0 5.5-1.5 7-4"></path>
                                        <line x1="2" y1="10" x2="14" y2="10"></line>
                                        <line x1="2" y1="14" x2="14" y2="14"></line>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <div class="form-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                            </svg>
                            Dettagli Lavorazione
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="hardware_ritirato">Hardware ritirato</label>
                                <input type="text" id="hardware_ritirato" name="hardware_ritirato" placeholder="es. Caricatore, cover...">
                            </div>
                            <div class="form-group">
                                <label for="dispositivo_sostitutivo">Dispositivo sostitutivo</label>
                                <input type="text" id="dispositivo_sostitutivo" name="dispositivo_sostitutivo" placeholder="Lasciato al cliente?">
                            </div>
                            <div class="form-group">
                                <label for="stato">Stato lavorazione</label>
                                <select id="stato" name="stato">
                                    <option value="In attesa">⏳ In attesa</option>
                                    <option value="In lavorazione">🔧 In lavorazione</option>
                                    <option value="Completata">✅ Completata</option>
                                    <option value="In attesa di ricambi">📦 In attesa ricambi</option>
                                    <option value="Non riparabile">❌ Non riparabile</option>
                                    <option value="Consegnata">📤 Consegnata</option>
                                    <option value="Annullata">🚫 Annullata</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <label class="checkbox-wrapper-wizard">
                                    <input type="checkbox" id="salva_dati" name="salva_dati" value="1">
                                    <div class="checkbox-label">
                                        <span>Salvataggio Dati</span>
                                        <span>Il cliente richiede backup dei dati</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wizard-footer">
                <button type="button" class="wizard-btn prev" id="prev-btn" style="display: none;">
                    <svg viewBox="0 0 24 24">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Indietro
                </button>
                
                <div class="wizard-progress-section">
                    <div class="wizard-progress-bar">
                        <div class="wizard-progress-fill" id="wizard-progress-fill"></div>
                    </div>
                    <div class="wizard-progress-text" id="wizard-progress-text">Step 1 di 4</div>
                </div>
                
                <button type="button" class="wizard-btn next" id="next-btn">
                    Avanti
                    <svg viewBox="0 0 24 24">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
                <button type="submit" class="wizard-btn submit" id="submit-riparazione-btn" style="display: none;">
                    <svg viewBox="0 0 24 24">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Salva Scheda
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Nuovo popup per il Buono Regalo - Design Moderno -->
<div class="popup-overlay" id="buonoRegaloPopup">
    <div class="popup-content">
        <button type="button" class="close-btn" id="close-buono-regalo-popup-btn">&times;</button>
        
        <!-- Header con icona regalo -->
        <div class="popup-header">
            <div class="wizard-header-content">
                <div class="wizard-header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 12v10H4V12"></path>
                        <path d="M2 7h20v5H2z"></path>
                        <path d="M12 22V7"></path>
                        <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
                        <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
                    </svg>
                </div>
                <h2>Nuovo Buono Regalo</h2>
            </div>
        </div>
        
        <form method="POST" action="" id="buono-regalo-form">
            <input type="hidden" name="form_type" value="buono_regalo">
            <div class="popup-body">
                <?php if(!empty($gift_card_feedback_message)) echo $gift_card_feedback_message; ?>
                
                <!-- Card: Dettagli Buono -->
                <div class="form-card">
                    <div class="form-card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 12v10H4V12"></path>
                            <path d="M2 7h20v5H2z"></path>
                            <path d="M12 22V7"></path>
                        </svg>
                        Dettagli Buono
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="buono_valore">Valore Buono (€) *</label>
                            <div class="input-wrapper">
                                <input type="number" id="buono_valore" name="valore" step="0.01" min="0" required placeholder="0.00">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17.5 7.5C16 5 13.5 3.5 10.5 3.5 6.4 3.5 3 7 3 12s3.4 8.5 7.5 8.5c3 0 5.5-1.5 7-4"></path>
                                    <line x1="2" y1="10" x2="14" y2="10"></line>
                                    <line x1="2" y1="14" x2="14" y2="14"></line>
                                </svg>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="buono_codice">Codice Buono *</label>
                            <div class="code-input-group">
                                <input type="text" id="buono_codice" name="codice_buono" readonly required placeholder="Generato automaticamente...">
                                <button type="button" id="copy-code-btn" class="copy-btn" title="Copia codice">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="buono_stato">Stato</label>
                            <select id="buono_stato" name="stato_buono">
                                <option value="Attivo">✅ Attivo</option>
                                <option value="Usato">📋 Usato</option>
                                <option value="Scaduto">⏰ Scaduto</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="buono_data_scadenza">Data Scadenza</label>
                            <input type="date" id="buono_data_scadenza" name="data_scadenza">
                        </div>
                    </div>
                </div>
                
                <!-- Card: Destinatario & Note -->
                <div class="form-card">
                    <div class="form-card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Destinatario & Note
                    </div>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="buono_destinatario">Destinatario</label>
                            <div class="input-wrapper">
                                <input type="text" id="buono_destinatario" name="destinatario" placeholder="Nome del destinatario (opzionale)">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                        </div>
                        <div class="form-group full-width">
                            <label for="buono_mittente_note">Mittente / Note</label>
                            <textarea id="buono_mittente_note" name="mittente_note" rows="3" placeholder="Chi ha fatto il regalo o note aggiuntive..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="popup-footer">
                <button type="button" class="wizard-btn prev" id="cancel-buono-regalo-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Annulla
                </button>
                <button type="submit" class="wizard-btn submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Crea Buono
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Nuovo popup per la Gestione Permuta - Design Wizard Moderno -->
<div class="popup-overlay" id="permutaPopup">
    <div class="wizard-container">
        <div class="wizard-header">
            <div class="wizard-header-content">
                <div class="wizard-header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 1l4 4-4 4"></path>
                        <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                        <path d="M7 23l-4-4 4-4"></path>
                        <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                    </svg>
                </div>
                <h1>Gestione Nuova Permuta</h1>
            </div>
            <button type="button" class="close-btn" id="close-permuta-popup-btn">&times;</button>
        </div>
        
        <!-- Stepper Navigation -->
        <div class="stepper-nav">
            <div class="step-permuta active" data-step="1">
                <div class="step-bubble-permuta">👤</div>
                <div class="step-label-permuta">Cliente</div>
            </div>
            <div class="step-permuta" data-step="2">
                <div class="step-bubble-permuta">📤</div>
                <div class="step-label-permuta">Ceduto</div>
            </div>
            <div class="step-permuta" data-step="3">
                <div class="step-bubble-permuta">📥</div>
                <div class="step-label-permuta">Ricevuto</div>
            </div>
            <div class="step-permuta" data-step="4">
                <div class="step-bubble-permuta">💰</div>
                <div class="step-label-permuta">Calcoli</div>
            </div>
        </div>
        
        <form method="POST" action="" id="permuta-form" enctype="multipart/form-data">
            <input type="hidden" name="form_type" value="permuta">
            <div class="wizard-body">
                <?php if(!empty($permuta_feedback_message)) echo $permuta_feedback_message; ?>

                <!-- Step 1: Dettagli Generali e Cliente -->
                <div class="permuta-step-pane active" data-step="1">
                    <div class="form-card-permuta">
                        <div class="form-card-title-permuta">
                            <span class="icon">📋</span> Dettagli Permuta
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="numero_permuta">Numero Permuta</label>
                                <input type="text" id="numero_permuta" name="numero_permuta_display" value="Automatico al Salvataggio" readonly style="background: #f1f5f9; color: #64748b;">
                                <input type="hidden" id="numero_progressivo" name="numero_progressivo">
                            </div>
                            <div class="form-group">
                                <label for="data_permuta">Data Permuta *</label>
                                <input type="date" id="data_permuta" name="data_permuta" required>
                            </div>
                            <div class="form-group">
                                <label for="stato_permuta">Stato</label>
                                <select id="stato_permuta" name="stato_permuta" required>
                                    <option value="In Trattativa">🔄 In Trattativa</option>
                                    <option value="Accettata">✅ Accettata</option>
                                    <option value="Rifiutata">❌ Rifiutata</option>
                                    <option value="Completata">🎉 Completata</option>
                                    <option value="Annullata">🚫 Annullata</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card-permuta purple">
                        <div class="form-card-title-permuta">
                            <span class="icon">👤</span> Dati Cliente
                        </div>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="cliente_permuta">Cliente *</label>
                                <div class="client-input-container">
                                    <input type="text" id="cliente_permuta" name="cliente_display" placeholder="Cerca o seleziona cliente..." autocomplete="off" required>
                                    <input type="hidden" id="cliente_id_permuta" name="cliente_id">
                                    <div id="client_suggestions_permuta" class="autocomplete-list"></div>
                                    <i class="fas fa-plus-circle add-client-icon" id="open_new_client_modal_btn_permuta" title="Aggiungi nuovo cliente"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="telefono_cliente_permuta">Telefono</label>
                                <input type="text" id="telefono_cliente_permuta" name="telefono_cliente" placeholder="Es: 3331234567">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Prodotto Ceduto (il tuo) -->
                <div class="permuta-step-pane" data-step="2">
                    <div class="form-card-permuta">
                        <div class="form-card-title-permuta">
                            <span class="icon">📤</span> Prodotto Ceduto al Cliente
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="tuo_modello_permuta">Modello *</label>
                                <div class="product-input-wrapper">
                                    <input type="text" id="tuo_modello_permuta" name="tuo_modello" placeholder="Cerca o inserisci modello..." autocomplete="off" required>
                                    <div id="product_suggestions_permuta" class="autocomplete-list"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="tuo_imei_permuta">IMEI / Seriale</label>
                                <input type="text" id="tuo_imei_permuta" name="tuo_imei" placeholder="Inserisci IMEI o seriale">
                            </div>
                            <div class="form-group">
                                <label for="tuo_valore_vendita_permuta">Valore di Vendita (€) *</label>
                                <input type="number" id="tuo_valore_vendita_permuta" name="tuo_valore_vendita" step="0.01" min="0" required placeholder="0.00">
                            </div>
                            <div class="form-group full-width">
                                <label for="tuo_note_permuta">Note Prodotto</label>
                                <textarea id="tuo_note_permuta" name="tuo_note" rows="2" placeholder="Eventuali note sul prodotto ceduto..."></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label for="tuo_foto_permuta">Foto / Allegati</label>
                                <input type="file" id="tuo_foto_permuta" name="tuo_foto[]" multiple accept="image/*">
                                <div id="tuo_foto_preview_permuta" class="image-preview"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Prodotto Ricevuto + Valutazione -->
                <div class="permuta-step-pane" data-step="3">
                    <div class="form-card-permuta purple">
                        <div class="form-card-title-permuta">
                            <span class="icon">📥</span> Prodotto Ricevuto dal Cliente
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="cliente_modello_permuta">Modello *</label>
                                <input type="text" id="cliente_modello_permuta" name="cliente_modello" required placeholder="es. iPhone 13 Pro">
                            </div>
                            <div class="form-group">
                                <label for="cliente_imei_permuta">IMEI / Seriale</label>
                                <input type="text" id="cliente_imei_permuta" name="cliente_imei" placeholder="Inserisci IMEI o seriale">
                            </div>
                            <div class="form-group">
                                <label for="cliente_valore_permuta_main">Valore Permuta Proposto (€) *</label>
                                <input type="number" id="cliente_valore_permuta_main" name="cliente_valore_permuta" step="0.01" min="0" required placeholder="0.00">
                            </div>
                            <div class="form-group full-width">
                                <label for="cliente_note_permuta">Note Generali</label>
                                <textarea id="cliente_note_permuta" name="cliente_note" rows="2" placeholder="Note sul prodotto ricevuto..."></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label for="cliente_foto_permuta">Foto / Allegati</label>
                                <input type="file" id="cliente_foto_permuta" name="cliente_foto[]" multiple accept="image/*">
                                <div id="cliente_foto_preview_permuta" class="image-preview"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card-permuta green">
                        <div class="form-card-title-permuta">
                            <span class="icon">🔍</span> Valutazione Tecnica
                        </div>
                        <div class="test-grid">
                            <div class="test-item">
                                <div class="test-icon">📱</div>
                                <div class="test-info">
                                    <div class="test-name">Display</div>
                                    <select name="test_display_esito">
                                        <option value="Funzionante">✅ Funzionante</option>
                                        <option value="Danneggiato">⚠️ Danneggiato</option>
                                        <option value="Guasto">❌ Guasto</option>
                                    </select>
                                    <input type="text" name="test_display_note" placeholder="Note...">
                                </div>
                            </div>
                            <div class="test-item">
                                <div class="test-icon">👆</div>
                                <div class="test-info">
                                    <div class="test-name">Touchscreen</div>
                                    <select name="test_touch_esito">
                                        <option value="Funzionante">✅ Funzionante</option>
                                        <option value="Parzialmente Funzionante">⚠️ Parziale</option>
                                        <option value="Non Funzionante">❌ Non Funz.</option>
                                    </select>
                                    <input type="text" name="test_touch_note" placeholder="Note...">
                                </div>
                            </div>
                            <div class="test-item">
                                <div class="test-icon">🔋</div>
                                <div class="test-info">
                                    <div class="test-name">Batteria</div>
                                    <select name="test_batteria_esito">
                                        <option value="Ottima">✅ Ottima</option>
                                        <option value="Buona">✅ Buona</option>
                                        <option value="Scarso">⚠️ Scarsa</option>
                                        <option value="Da Sostituire">❌ Da Sostituire</option>
                                    </select>
                                    <input type="text" name="test_batteria_note" placeholder="% salute, cicli...">
                                </div>
                            </div>
                            <div class="test-item">
                                <div class="test-icon">📷</div>
                                <div class="test-info">
                                    <div class="test-name">Fotocamera Post.</div>
                                    <select name="test_cam_post_esito">
                                        <option value="Funzionante">✅ Funzionante</option>
                                        <option value="Difettosa">⚠️ Difettosa</option>
                                        <option value="Non Funzionante">❌ Non Funz.</option>
                                    </select>
                                    <input type="text" name="test_cam_post_note" placeholder="Note...">
                                </div>
                            </div>
                            <div class="test-item">
                                <div class="test-icon">🤳</div>
                                <div class="test-info">
                                    <div class="test-name">Fotocamera Ant.</div>
                                    <select name="test_cam_ant_esito">
                                        <option value="Funzionante">✅ Funzionante</option>
                                        <option value="Difettosa">⚠️ Difettosa</option>
                                        <option value="Non Funzionante">❌ Non Funz.</option>
                                    </select>
                                    <input type="text" name="test_cam_ant_note" placeholder="Note...">
                                </div>
                            </div>
                            <div class="test-item">
                                <div class="test-icon">🔊</div>
                                <div class="test-info">
                                    <div class="test-name">Audio/Speaker</div>
                                    <select name="test_audio_esito">
                                        <option value="Funzionante">✅ Funzionante</option>
                                        <option value="Distorto">⚠️ Distorto</option>
                                        <option value="Non Funzionante">❌ Non Funz.</option>
                                    </select>
                                    <input type="text" name="test_audio_note" placeholder="Note...">
                                </div>
                            </div>
                            <div class="test-item">
                                <div class="test-icon">🎤</div>
                                <div class="test-info">
                                    <div class="test-name">Microfono</div>
                                    <select name="test_mic_esito">
                                        <option value="Funzionante">✅ Funzionante</option>
                                        <option value="Difettoso">⚠️ Difettoso</option>
                                        <option value="Non Funzionante">❌ Non Funz.</option>
                                    </select>
                                    <input type="text" name="test_mic_note" placeholder="Note...">
                                </div>
                            </div>
                            <div class="test-item">
                                <div class="test-icon">📶</div>
                                <div class="test-info">
                                    <div class="test-name">Wi-Fi</div>
                                    <select name="test_wifi_esito">
                                        <option value="Funzionante">✅ Funzionante</option>
                                        <option value="Instabile">⚠️ Instabile</option>
                                        <option value="Non Funzionante">❌ Non Funz.</option>
                                    </select>
                                    <input type="text" name="test_wifi_note" placeholder="Note...">
                                </div>
                            </div>
                            <div class="test-item">
                                <div class="test-icon">🔵</div>
                                <div class="test-info">
                                    <div class="test-name">Bluetooth</div>
                                    <select name="test_bt_esito">
                                        <option value="Funzionante">✅ Funzionante</option>
                                        <option value="Instabile">⚠️ Instabile</option>
                                        <option value="Non Funzionante">❌ Non Funz.</option>
                                    </select>
                                    <input type="text" name="test_bt_note" placeholder="Note...">
                                </div>
                            </div>
                            <div class="test-item">
                                <div class="test-icon">🔌</div>
                                <div class="test-info">
                                    <div class="test-name">Ricarica</div>
                                    <select name="test_ricarica_esito">
                                        <option value="Funzionante">✅ Funzionante</option>
                                        <option value="Difettosa">⚠️ Difettosa</option>
                                        <option value="Non Funzionante">❌ Non Funz.</option>
                                    </select>
                                    <input type="text" name="test_ricarica_note" placeholder="Note...">
                                </div>
                            </div>
                            <div class="test-item">
                                <div class="test-icon">🔘</div>
                                <div class="test-info">
                                    <div class="test-name">Tasti Fisici</div>
                                    <select name="test_tasti_esito">
                                        <option value="Funzionante">✅ Funzionante</option>
                                        <option value="Bloccati">⚠️ Bloccati</option>
                                        <option value="Non Funzionante">❌ Non Funz.</option>
                                    </select>
                                    <input type="text" name="test_tasti_note" placeholder="Note...">
                                </div>
                            </div>
                            <div class="test-item">
                                <div class="test-icon">🔒</div>
                                <div class="test-info">
                                    <div class="test-name">Sblocco Bio</div>
                                    <select name="test_sblocco_bio_esito">
                                        <option value="Funzionante">✅ Funzionante</option>
                                        <option value="Non Funzionante">❌ Non Funz.</option>
                                        <option value="Non Applicabile">➖ N/A</option>
                                    </select>
                                    <input type="text" name="test_sblocco_bio_note" placeholder="Note...">
                                </div>
                            </div>
                            <div class="test-item">
                                <div class="test-icon">🔄</div>
                                <div class="test-info">
                                    <div class="test-name">Reset Fabbrica</div>
                                    <select name="test_reset_fabbrica">
                                        <option value="Si">✅ Eseguito</option>
                                        <option value="No">❌ Non Eseguito</option>
                                    </select>
                                    <input type="text" name="test_reset_fabbrica_note" placeholder="Note...">
                                </div>
                            </div>
                            <div class="test-item">
                                <div class="test-icon">👤</div>
                                <div class="test-info">
                                    <div class="test-name">Account</div>
                                    <select name="test_accounts_esito">
                                        <option value="Liberi">✅ Liberi</option>
                                        <option value="Presenti">⚠️ Presenti</option>
                                    </select>
                                    <input type="text" name="test_accounts_note" placeholder="Google, iCloud...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Calcoli Finali -->
                <div class="permuta-step-pane" data-step="4">
                    <div class="form-card-permuta">
                        <div class="form-card-title-permuta">
                            <span class="icon">🛠️</span> Costi di Ricondizionamento
                        </div>
                        <div id="costi_ricondizionamento_container_permuta">
                            <div class="costo-item-modern">
                                <input type="text" name="costo_descrizione[]" placeholder="Descrizione costo...">
                                <input type="number" name="costo_importo[]" step="0.01" min="0" placeholder="0.00" class="costo-importo" value="0">
                                <button type="button" class="btn-remove-costo"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <button type="button" id="add_costo_btn_permuta" class="btn-add-costo">
                            <i class="fas fa-plus"></i> Aggiungi Costo
                        </button>
                    </div>
                    
                    <div class="form-card-permuta purple">
                        <div class="form-card-title-permuta">
                            <span class="icon">📊</span> Altri Costi e Prezzi
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="costo_accessori_input_permuta">Costo Accessori (€)</label>
                                <input type="number" id="costo_accessori_input_permuta" name="costo_accessori_input" step="0.01" min="0" value="0" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label for="costo_prodotto_input_permuta">Costo Prodotto (€)</label>
                                <input type="number" id="costo_prodotto_input_permuta" name="costo_prodotto_input" step="0.01" min="0" value="0" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label for="prezzo_vendita_input_permuta">Prezzo Vendita Finale (€)</label>
                                <input type="number" id="prezzo_vendita_input_permuta" name="prezzo_vendita_input" step="0.01" min="0" value="0" placeholder="0.00">
                            </div>
                            <div class="form-group full-width">
                                <label for="note_generali_input_permuta">Note Aggiuntive</label>
                                <textarea id="note_generali_input_permuta" name="note_generali_input" rows="2" placeholder="Eventuali note aggiuntive..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card-permuta green">
                        <div class="form-card-title-permuta">
                            <span class="icon">💰</span> Riepilogo Finale
                        </div>
                        <div class="calcoli-summary">
                            <div class="calcolo-row">
                                <span class="calcolo-label">📤 Valore Prodotto Ceduto</span>
                                <span class="calcolo-value" id="valore_vendita_ceduto_permuta">€ 0,00</span>
                            </div>
                            <div class="calcolo-row">
                                <span class="calcolo-label">📥 Valore Permuta Riconosciuto</span>
                                <span class="calcolo-value" id="valore_permuta_ricevuto_permuta" style="color: #10b981;">- € 0,00</span>
                            </div>
                            <div class="calcolo-row total">
                                <span class="calcolo-label">💵 Conguaglio Cliente</span>
                                <span class="calcolo-value" id="conguaglio_cliente_permuta">€ 0,00</span>
                                <input type="hidden" id="conguaglio_cliente_val_permuta" name="conguaglio_cliente_val">
                            </div>
                        </div>
                        
                        <div style="border-top: 2px dashed #e2e8f0; margin: 1rem -1.5rem; padding-top: 1rem;"></div>
                        <div class="form-card-title-permuta" style="margin-bottom: 1rem;">
                            <span class="icon">📊</span> Analisi Costi e Margine
                        </div>
                        <div class="calcoli-summary" style="background: #fefce8; border-color: #fde047;">
                            <div class="calcolo-row">
                                <span class="calcolo-label">🔧 Costi Ricondizionamento</span>
                                <span class="calcolo-value" id="totale_costi_ricondizionamento_permuta" style="color: #dc2626;">€ 0,00</span>
                                <input type="hidden" id="totale_costi_ricondizionamento_val_permuta" name="totale_costi_ricondizionamento_val">
                            </div>
                            <div class="calcolo-row">
                                <span class="calcolo-label">🎁 Costo Accessori</span>
                                <span class="calcolo-value" id="costo_accessori_display_permuta" style="color: #dc2626;">€ 0,00</span>
                            </div>
                            <div class="calcolo-row">
                                <span class="calcolo-label">📦 Costo Prodotto Ceduto</span>
                                <span class="calcolo-value" id="costo_prodotto_display_permuta" style="color: #dc2626;">€ 0,00</span>
                            </div>
                            <div class="calcolo-row highlight">
                                <span class="calcolo-label">💎 Valore Netto Ricevuto</span>
                                <span class="calcolo-value" id="valore_netto_ricevuto_permuta">€ 0,00</span>
                                <input type="hidden" id="valore_netto_ricevuto_val_permuta" name="valore_netto_ricevuto_val">
                            </div>
                            <div class="calcolo-row" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; margin: 0.5rem -1.5rem -1.5rem; padding: 1rem 1.5rem; border-radius: 0 0 14px 14px;">
                                <span class="calcolo-label" style="color: white;">💹 Margine Stimato</span>
                                <span class="calcolo-value" id="margine_permuta" style="color: white; font-size: 1.2rem;">€ 0,00</span>
                                <input type="hidden" id="margine_permuta_val" name="margine_permuta_val">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wizard-footer">
                <button type="button" class="wizard-btn prev" id="prev-btn-permuta" style="display: none;">
                    <svg viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Indietro
                </button>
                
                <div class="wizard-progress-section">
                    <div class="wizard-progress-bar">
                        <div class="wizard-progress-fill" id="wizard-progress-fill-permuta" style="width: 25%;"></div>
                    </div>
                    <div class="wizard-progress-text" id="wizard-progress-text-permuta">Step 1 di 4</div>
                </div>
                
                <button type="button" class="wizard-btn next" id="next-btn-permuta">
                    Avanti
                    <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </button>
                <button type="submit" name="salva_permuta" class="wizard-btn submit" id="submit-permuta-btn" style="display: none;">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Salva Permuta
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Nuovo popup per la Prenotazione Prodotto - REDESIGNED -->
<div class="popup-overlay" id="prenotazioneProdottoPopup">
    <div class="prenotazione-modal">
        <!-- Header con gradiente -->
        <div class="prenotazione-modal-header">
            <div class="prenotazione-header-content">
                <div class="prenotazione-header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                        <path d="M9 16l2 2 4-4"></path>
                    </svg>
                </div>
                <div class="prenotazione-header-text">
                    <h2>Nuova Prenotazione</h2>
                    <span>Prenota un prodotto per il cliente</span>
                </div>
            </div>
            <button type="button" class="prenotazione-close-btn" id="close-prenotazione-prodotto-popup-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        
        <form action="" method="POST" id="prenotazione-prodotto-form">
            <input type="hidden" name="form_type" value="prenotazione_prodotto">
            
            <div class="prenotazione-modal-body">
                <?php if(!empty($prenotazione_feedback_message)) echo $prenotazione_feedback_message; ?>
                
                <!-- Sezione Prodotto -->
                <div class="prenotazione-section">
                    <div class="prenotazione-section-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        <span>Dettagli Prodotto</span>
                    </div>
                    
                    <div class="prenotazione-field full-width">
                        <label for="productName_pp">Nome Prodotto da Ordinare</label>
                        <div class="prenotazione-input-wrapper">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                                <line x1="12" y1="18" x2="12.01" y2="18"></line>
                            </svg>
                            <input type="text" id="productName_pp" name="productName" placeholder="Es. iPhone 15 Pro Max 256GB" required>
                        </div>
                        <input type="hidden" id="productId_pp" name="productId" value="">
                    </div>

                    <div class="prenotazione-grid-3">
                        <div class="prenotazione-field">
                            <label for="unitPrice_pp">Prezzo Unitario</label>
                            <div class="prenotazione-input-wrapper currency">
                                <span class="currency-symbol">€</span>
                                <input type="number" step="0.01" id="unitPrice_pp" name="unitPrice" min="0" value="0.00" required>
                            </div>
                        </div>

                        <div class="prenotazione-field">
                            <label for="quantity_pp">Quantità</label>
                            <div class="prenotazione-quantity-wrapper">
                                <button type="button" class="qty-btn minus" onclick="adjustQuantityPP(-1)">−</button>
                                <input type="number" id="quantity_pp" name="quantity" min="1" value="1" required>
                                <button type="button" class="qty-btn plus" onclick="adjustQuantityPP(1)">+</button>
                            </div>
                        </div>

                        <div class="prenotazione-field">
                            <label for="reservationDate_pp">Data Prenotazione</label>
                            <div class="prenotazione-input-wrapper">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <input type="date" id="reservationDate_pp" name="reservationDate" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sezione Pagamento -->
                <div class="prenotazione-section">
                    <div class="prenotazione-section-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        <span>Riepilogo Pagamento</span>
                    </div>
                    
                    <div class="prenotazione-payment-cards">
                        <div class="payment-card total">
                            <div class="payment-card-label">Totale Ordine</div>
                            <div class="payment-card-value" id="productTotalPriceDisplay_pp">€ 0,00</div>
                            <input type="hidden" id="productTotalPrice_pp" name="productTotalPrice">
                        </div>
                        
                        <div class="payment-card deposit">
                            <div class="payment-card-label">Acconto Versato</div>
                            <div class="payment-card-input">
                                <span>€</span>
                                <input type="number" step="0.01" id="depositAmount_pp" name="depositAmount" min="0" value="0.00">
                            </div>
                        </div>
                        
                        <div class="payment-card remaining">
                            <div class="payment-card-label">Da Saldare</div>
                            <div class="payment-card-value" id="remainingAmountDisplay_pp">€ 0,00</div>
                            <input type="hidden" id="remainingAmount_pp" name="remainingAmount">
                        </div>
                    </div>
                </div>

                <!-- Sezione Cliente -->
                <div class="prenotazione-section">
                    <div class="prenotazione-section-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Dati Cliente</span>
                    </div>

                    <div class="prenotazione-field full-width">
                        <label for="customerName_pp">Cliente</label>
                        <div class="prenotazione-client-search">
                            <div class="prenotazione-input-wrapper">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text" id="customerName_pp" name="customerName" placeholder="Cerca cliente esistente o inserisci nuovo..." autocomplete="off" required>
                            </div>
                            <button type="button" class="add-client-btn" id="addClientBtn_pp" title="Aggiungi nuovo cliente">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                            <input type="hidden" id="clientId_pp" name="clientId">
                            <div id="clientAutocompleteList_pp" class="autocomplete-list"></div>
                        </div>
                    </div>

                    <div class="prenotazione-grid-2">
                        <div class="prenotazione-field">
                            <label for="customerPhone_pp">Telefono Principale</label>
                            <div class="prenotazione-input-wrapper">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                <input type="tel" id="customerPhone_pp" name="customerPhone" placeholder="333 1234567">
                            </div>
                        </div>

                        <div class="prenotazione-field">
                            <label for="customerSecondaryPhone_pp">Telefono Secondario</label>
                            <div class="prenotazione-input-wrapper">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                <input type="tel" id="customerSecondaryPhone_pp" name="customerSecondaryPhone" placeholder="Opzionale">
                            </div>
                        </div>
                    </div>

                    <div class="prenotazione-field full-width">
                        <label for="customerEmail_pp">Email</label>
                        <div class="prenotazione-input-wrapper">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            <input type="email" id="customerEmail_pp" name="customerEmail" placeholder="cliente@email.com">
                        </div>
                    </div>
                </div>

                <!-- Sezione Note -->
                <div class="prenotazione-section">
                    <div class="prenotazione-section-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        <span>Note Aggiuntive</span>
                    </div>
                    
                    <div class="prenotazione-field full-width">
                        <textarea id="notes_pp" name="notes" rows="3" placeholder="Inserisci eventuali note sulla prenotazione (colore, variante, richieste speciali...)"></textarea>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="prenotazione-modal-footer">
                <button type="button" id="cancelReservationBtn_pp" class="prenotazione-btn-cancel">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Annulla
                </button>
                <button type="submit" class="prenotazione-btn-submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Salva Prenotazione
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal per l'aggiunta di un nuovo cliente -->
<div id="new_client_modal_overlay" class="ncm-overlay">
    <div class="ncm-dialog">
        <!-- Decorative glow -->
        <div class="ncm-glow"></div>

        <!-- Hero Header -->
        <div class="ncm-hero">
            <div class="ncm-hero-bg">
                <div class="ncm-hero-orb ncm-hero-orb-1"></div>
                <div class="ncm-hero-orb ncm-hero-orb-2"></div>
                <div class="ncm-hero-orb ncm-hero-orb-3"></div>
            </div>
            <div class="ncm-hero-content">
                <div class="ncm-hero-icon">
                    <i class="fas fa-user-plus"></i>
                    <div class="ncm-hero-icon-ring"></div>
                </div>
                <div class="ncm-hero-text">
                    <h2>Nuovo Cliente</h2>
                    <p>Registra un nuovo cliente nell'anagrafica</p>
                </div>
            </div>
            <button type="button" class="ncm-close" id="close_new_client_modal_btn">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <!-- Step Navigation -->
        <div class="ncm-steps">
            <button type="button" class="tab-button ncm-step active" data-tab="personal_data_tab">
                <div class="ncm-step-num">1</div>
                <div class="ncm-step-info">
                    <span class="ncm-step-title">Dati Personali</span>
                    <span class="ncm-step-desc">Anagrafica e contatti</span>
                </div>
            </button>
            <div class="ncm-step-divider"><div class="ncm-step-line"></div></div>
            <button type="button" class="tab-button ncm-step" data-tab="company_data_tab">
                <div class="ncm-step-num">2</div>
                <div class="ncm-step-info">
                    <span class="ncm-step-title">Dati Aziendali</span>
                    <span class="ncm-step-desc">Società e sede</span>
                </div>
            </button>
        </div>

        <!-- Body -->
        <div class="ncm-body">
            <div id="personal_data_tab" class="tab-content active">
                <!-- Anagrafica Card -->
                <div class="ncm-card">
                    <div class="ncm-card-head">
                        <div class="ncm-card-icon green"><i class="fas fa-user"></i></div>
                        <div>
                            <h4>Informazioni Personali</h4>
                            <p>Nome e cognome del cliente</p>
                        </div>
                    </div>
                    <div class="ncm-row">
                        <div class="ncm-fg">
                            <label>Nome <span class="ncm-req">*</span></label>
                            <div class="ncm-iw">
                                <i class="fas fa-user"></i>
                                <input type="text" id="modal_nuovo_cliente_nome" placeholder="Mario" required>
                            </div>
                        </div>
                        <div class="ncm-fg">
                            <label>Cognome <span class="ncm-req">*</span></label>
                            <div class="ncm-iw">
                                <i class="fas fa-user"></i>
                                <input type="text" id="modal_nuovo_cliente_cognome" placeholder="Rossi" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contatti Card -->
                <div class="ncm-card">
                    <div class="ncm-card-head">
                        <div class="ncm-card-icon blue"><i class="fas fa-address-book"></i></div>
                        <div>
                            <h4>Contatti</h4>
                            <p>Recapiti e informazioni di contatto</p>
                        </div>
                    </div>
                    <div class="ncm-row">
                        <div class="ncm-fg">
                            <label>Telefono</label>
                            <div class="ncm-iw">
                                <i class="fas fa-phone"></i>
                                <input type="tel" id="modal_nuovo_cliente_telefono" placeholder="333 123 4567">
                            </div>
                        </div>
                        <div class="ncm-fg">
                            <label>Email</label>
                            <div class="ncm-iw">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="modal_nuovo_cliente_email" placeholder="mario@esempio.com">
                            </div>
                        </div>
                    </div>
                    <div class="ncm-row">
                        <div class="ncm-fg">
                            <label>Indirizzo</label>
                            <div class="ncm-iw">
                                <i class="fas fa-location-dot"></i>
                                <input type="text" id="modal_nuovo_cliente_indirizzo" placeholder="Via Roma, 1">
                            </div>
                        </div>
                        <div class="ncm-fg">
                            <label>Città</label>
                            <div class="ncm-iw">
                                <i class="fas fa-city"></i>
                                <input type="text" id="modal_nuovo_cliente_citta" placeholder="Roma">
                            </div>
                        </div>
                    </div>
                    <div class="ncm-row ncm-row-full">
                        <div class="ncm-fg">
                            <label>Note</label>
                            <div class="ncm-iw ncm-iw-ta">
                                <i class="fas fa-sticky-note"></i>
                                <textarea id="modal_nuovo_cliente_note" rows="2" placeholder="Annotazioni sul cliente..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="company_data_tab" class="tab-content">
                <!-- Dati Societari Card -->
                <div class="ncm-card">
                    <div class="ncm-card-head">
                        <div class="ncm-card-icon purple"><i class="fas fa-building"></i></div>
                        <div>
                            <h4>Dati Societari</h4>
                            <p>Ragione sociale e partita IVA</p>
                        </div>
                    </div>
                    <div class="ncm-row">
                        <div class="ncm-fg">
                            <label>Ragione Sociale</label>
                            <div class="ncm-iw">
                                <i class="fas fa-building"></i>
                                <input type="text" id="modal_nuovo_cliente_ragione_sociale" placeholder="Azienda S.r.l.">
                            </div>
                        </div>
                        <div class="ncm-fg">
                            <label>Partita IVA</label>
                            <div class="ncm-iw">
                                <i class="fas fa-id-card"></i>
                                <input type="text" id="modal_nuovo_cliente_partita_iva" placeholder="IT12345678901">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contatti Aziendali Card -->
                <div class="ncm-card">
                    <div class="ncm-card-head">
                        <div class="ncm-card-icon blue"><i class="fas fa-address-book"></i></div>
                        <div>
                            <h4>Contatti Aziendali</h4>
                            <p>Recapiti e sede dell'azienda</p>
                        </div>
                    </div>
                    <div class="ncm-row">
                        <div class="ncm-fg">
                            <label>Telefono</label>
                            <div class="ncm-iw">
                                <i class="fas fa-phone"></i>
                                <input type="tel" id="modal_nuovo_cliente_telefono_azienda" placeholder="02 1234567">
                            </div>
                        </div>
                        <div class="ncm-fg">
                            <label>Email</label>
                            <div class="ncm-iw">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="modal_nuovo_cliente_email_azienda" placeholder="info@azienda.com">
                            </div>
                        </div>
                    </div>
                    <div class="ncm-row">
                        <div class="ncm-fg">
                            <label>Indirizzo</label>
                            <div class="ncm-iw">
                                <i class="fas fa-location-dot"></i>
                                <input type="text" id="modal_nuovo_cliente_indirizzo_azienda" placeholder="Via dell'Industria, 5">
                            </div>
                        </div>
                        <div class="ncm-fg">
                            <label>Città</label>
                            <div class="ncm-iw">
                                <i class="fas fa-city"></i>
                                <input type="text" id="modal_nuovo_cliente_citta_azienda" placeholder="Milano">
                            </div>
                        </div>
                    </div>
                    <div class="ncm-row ncm-row-full">
                        <div class="ncm-fg">
                            <label>Note Azienda</label>
                            <div class="ncm-iw ncm-iw-ta">
                                <i class="fas fa-sticky-note"></i>
                                <textarea id="modal_nuovo_cliente_note_azienda" rows="2" placeholder="Annotazioni sull'azienda..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="ncm-footer">
            <button type="button" class="ncm-btn-cancel" id="cancel_new_client_modal_btn">
                <i class="fas fa-times"></i> Annulla
            </button>
            <button type="button" class="ncm-btn-save" id="save_new_client_btn">
                <i class="fas fa-check"></i> Salva Cliente
                <div class="ncm-btn-shine"></div>
            </button>
        </div>
    </div>
</div>

<!-- Message Box for notifications (globale) -->
<div id="messageBox" class="message-box"></div>


<script>
    // Variabili globali per autocomplete
    const globalClientsData = <?php echo json_encode($clienti_esistenti); ?>;
    const globalProductsData = <?php echo json_encode($prodotti_esistenti); ?>;

    /**
     * Mostra un messaggio all'utente (successo o errore).
     */
    window.showMessage = function(message, isError = false) {
        const messageBox = document.getElementById('messageBox');
        if (!messageBox) return;
        if (messageBox.hideTimeout) clearTimeout(messageBox.hideTimeout);
        messageBox.textContent = message;
        messageBox.className = 'message-box';
        if (isError) messageBox.classList.add('error');
        messageBox.classList.add('show');
        messageBox.hideTimeout = setTimeout(() => {
            messageBox.classList.remove('show');
        }, 3000);
    }

    /**
     * Formatta un numero come valuta.
     */
    function formatCurrency(value) {
        return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(value);
    }

    /**
     * Gestisce l'autocompletamento per i campi di input.
     */
    function setupAutocomplete(inputElement, listElement, dataArray, displayProperty, selectCallback, inputHiddenId = null) {
        let currentSearchTimeout = null;
        inputElement.addEventListener('input', () => {
            const searchTerm = inputElement.value.toLowerCase();
            listElement.innerHTML = '';
            listElement.style.display = 'none';
            if (searchTerm.length < 2) {
                if (inputHiddenId) document.getElementById(inputHiddenId).value = '';
                return;
            }
            if (currentSearchTimeout) clearTimeout(currentSearchTimeout);
            currentSearchTimeout = setTimeout(() => {
                const filteredData = dataArray.filter(item => displayProperty(item).toLowerCase().includes(searchTerm));
                if (filteredData.length === 0) {
                    listElement.innerHTML = `<div class="client-suggestion-item" style="font-style: italic; color: var(--text-light); cursor: default;">Nessun risultato trovato.</div>`;
                    listElement.style.display = 'block';
                    return;
                }
                filteredData.forEach(item => {
                    const div = document.createElement('div');
                    div.classList.add('client-suggestion-item');
                    div.textContent = displayProperty(item);
                    div.addEventListener('click', () => {
                        selectCallback(item);
                        listElement.style.display = 'none';
                        if (inputHiddenId) document.getElementById(inputHiddenId).value = item.id;
                    });
                    listElement.appendChild(div);
                });
                listElement.style.display = 'block';
            }, 300);
        });
        inputElement.addEventListener('focus', () => {
            if (inputElement.value.length >= 2 && listElement.children.length > 0 && listElement.firstElementChild.textContent !== 'Nessun risultato trovato.') {
                listElement.style.display = 'block';
            }
        });
        document.addEventListener('click', (event) => {
            if (!inputElement.contains(event.target) && !listElement.contains(event.target)) {
                listElement.style.display = 'none';
            }
        });
    }

    // --- Gestione Modale Nuovo Cliente (Globale, condiviso) ---
    (() => {
        const modalOverlay = document.getElementById('new_client_modal_overlay');
        const closeBtn = document.getElementById('close_new_client_modal_btn');
        const cancelBtn = document.getElementById('cancel_new_client_modal_btn');
        const saveBtn = document.getElementById('save_new_client_btn');
        const tabButtons = modalOverlay.querySelectorAll('.tab-button');
        const tabContents = modalOverlay.querySelectorAll('.tab-content');
        const inputs = modalOverlay.querySelectorAll('input, textarea');

        function openModal() {
            modalOverlay.style.display = 'flex';
            setTimeout(() => modalOverlay.classList.add('visible'), 10);
            inputs.forEach(input => input.value = '');
            tabButtons[0].click();
            modalOverlay.querySelector('#modal_nuovo_cliente_nome').focus();
        }

        function closeModal() {
            modalOverlay.classList.remove('visible');
            modalOverlay.addEventListener('transitionend', () => {
                modalOverlay.style.display = 'none';
            }, { once: true });
            // Fallback se transitionend non scatta
            setTimeout(() => { if (modalOverlay.style.display !== 'none') modalOverlay.style.display = 'none'; }, 450);
        }

        function showTab(tabId) {
            tabContents.forEach(c => {
                c.classList.remove('active');
            });
            tabButtons.forEach(b => {
                b.classList.remove('active');
            });
            const targetTab = modalOverlay.querySelector(`#${tabId}`);
            targetTab.classList.add('active');
            modalOverlay.querySelector(`.tab-button[data-tab="${tabId}"]`).classList.add('active');
        }

        window.openNewClientModal = openModal;
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        tabButtons.forEach(b => b.addEventListener('click', () => showTab(b.dataset.tab)));

        saveBtn.addEventListener('click', async () => {
            const clientData = {
                nome: document.getElementById('modal_nuovo_cliente_nome').value.trim(),
                cognome: document.getElementById('modal_nuovo_cliente_cognome').value.trim(),
                telefono: document.getElementById('modal_nuovo_cliente_telefono').value.trim(),
                email: document.getElementById('modal_nuovo_cliente_email').value.trim(),
                indirizzo: document.getElementById('modal_nuovo_cliente_indirizzo').value.trim(),
                citta: document.getElementById('modal_nuovo_cliente_citta').value.trim(),
                note: document.getElementById('modal_nuovo_cliente_note').value.trim(),
                ragione_sociale: document.getElementById('modal_nuovo_cliente_ragione_sociale').value.trim(),
                partita_iva: document.getElementById('modal_nuovo_cliente_partita_iva').value.trim(),
                indirizzo_azienda: document.getElementById('modal_nuovo_cliente_indirizzo_azienda').value.trim(),
                citta_azienda: document.getElementById('modal_nuovo_cliente_citta_azienda').value.trim(),
                telefono_azienda: document.getElementById('modal_nuovo_cliente_telefono_azienda').value.trim(),
                email_azienda: document.getElementById('modal_nuovo_cliente_email_azienda').value.trim(),
                note_azienda: document.getElementById('modal_nuovo_cliente_note_azienda').value.trim(),
            };

            if (!clientData.nome || !clientData.cognome) {
                return showMessage('Nome e Cognome sono obbligatori.', true);
            }

            try {
                const response = await fetch('add_cliente.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(clientData)
                });
                const result = await response.json();
                if (response.ok && result.status === 'success') {
                    showMessage(result.message, false);
                    closeModal();
                    const newClient = {
                        id: result.id,
                        display_name: clientData.ragione_sociale || `${clientData.nome} ${clientData.cognome}`.trim(),
                        telefono_principale: clientData.telefono || clientData.telefono_azienda,
                        email: clientData.email || clientData.email_azienda,
                    };
                    globalClientsData.push(newClient);
                    // Aggiorna i campi dei form aperti, se necessario
                    if (document.getElementById('riparazionePopup').classList.contains('visible')) {
                        document.getElementById('cliente_riparazione_autocomplete').value = newClient.display_name;
                        document.getElementById('cliente_id_riparazione').value = newClient.id;
                        document.getElementById('telefono_riparazione_display').value = newClient.telefono_principale || '';
                    }
                    if (document.getElementById('permutaPopup').classList.contains('visible')) {
                        document.getElementById('cliente_permuta').value = newClient.display_name;
                        document.getElementById('cliente_id_permuta').value = newClient.id;
                        document.getElementById('telefono_cliente_permuta').value = newClient.telefono_principale || '';
                    }
                     if (document.getElementById('prenotazioneProdottoPopup').classList.contains('visible')) {
                        document.getElementById('customerName_pp').value = newClient.display_name;
                        document.getElementById('clientId_pp').value = newClient.id;
                        document.getElementById('customerPhone_pp').value = newClient.telefono_principale || '';
                        document.getElementById('customerEmail_pp').value = newClient.email || '';
                    }
                    // Aggiorna anche i campi del carrello se presenti
                    const carrelloClienteInput = document.getElementById('clienteInput');
                    const carrelloIdCliente = document.getElementById('idCliente');
                    if (carrelloClienteInput) carrelloClienteInput.value = newClient.display_name;
                    if (carrelloIdCliente) carrelloIdCliente.value = newClient.id;
                } else {
                    showMessage('Errore: ' + (result.message || 'Sconosciuto.'), true);
                }
            } catch (error) {
                showMessage('Errore di connessione durante il salvataggio.', true);
            }
        });
    })();

    // --- Script per la gestione dei Popup principali ---
    function initializePopup(popupId, openBtnId) {
        const popup = document.getElementById(popupId);
        if (!popup) return { openPopup: () => {}, closePopup: () => {} };
        const openBtn = document.getElementById(openBtnId);
        // Cerca close button con vari selettori
        const closeBtn = popup.querySelector('.close-btn') || popup.querySelector('[class*="close-btn"]') || popup.querySelector('[id*="close"]');
        // Cerca cancel button
        const cancelBtn = popup.querySelector('[class*="btn-cancel"]') || popup.querySelector('[id*="cancel"]');

        const openPopup = () => {
            popup.style.display = 'flex';
            setTimeout(() => popup.classList.add('visible'), 10);
        };
        const closePopup = () => {
            popup.classList.remove('visible');
            popup.addEventListener('transitionend', () => popup.style.display = 'none', { once: true });
            // Fallback se transitionend non scatta
            setTimeout(() => { if (popup.style.display !== 'none') popup.style.display = 'none'; }, 400);
        };

        if (openBtn) openBtn.addEventListener('click', openPopup);
        if (closeBtn) closeBtn.addEventListener('click', closePopup);
        if (cancelBtn) cancelBtn.addEventListener('click', closePopup);
        popup.addEventListener('click', (e) => {
            if (e.target === popup) closePopup();
        });
        
        // Gestione dei pulsanti "Aggiungi Cliente" all'interno dei popup
        const addClientBtn = popup.querySelector('.add-client-icon, .add-client-btn');
        if (addClientBtn) addClientBtn.addEventListener('click', () => window.openNewClientModal());

        return { openPopup, closePopup };
    }

    // Inizializzazione di tutti i popup
    const riparazionePopup = initializePopup('riparazionePopup', 'openNuovaAssistenzaPopupBtn');
    const buonoRegaloPopup = initializePopup('buonoRegaloPopup', 'openBuonoRegaloPopupBtn');
    const permutaPopup = initializePopup('permutaPopup', 'openNuovaPermutaPopupBtn');
    const prenotazionePopup = initializePopup('prenotazioneProdottoPopup', 'openPrenotazioneProdottoPopupBtn');

    // --- Logica Specifica per Wizard Riparazione ---
    (() => {
        const popup = document.getElementById('riparazionePopup');
        const prevBtn = popup.querySelector('#prev-btn');
        const nextBtn = popup.querySelector('#next-btn');
        const submitBtn = popup.querySelector('#submit-riparazione-btn');
        const steps = popup.querySelectorAll('.step-pane');
        const navSteps = popup.querySelectorAll('.stepper-nav .step');
        const progressFill = popup.querySelector('#wizard-progress-fill');
        const progressText = popup.querySelector('#wizard-progress-text');
        let currentStep = 1;

        window.showRiparazioneStep = (stepNumber) => {
            currentStep = stepNumber;
            steps.forEach(s => s.classList.toggle('active', parseInt(s.dataset.step) === currentStep));
            navSteps.forEach(ns => {
                const stepNum = parseInt(ns.dataset.step);
                ns.classList.toggle('active', stepNum === currentStep);
                ns.classList.toggle('completed', stepNum < currentStep);
            });
            prevBtn.style.display = currentStep > 1 ? 'flex' : 'none';
            nextBtn.style.display = currentStep < steps.length ? 'flex' : 'none';
            submitBtn.style.display = currentStep === steps.length ? 'flex' : 'none';
            
            // Aggiorna la progress bar
            const progressPercent = (currentStep / steps.length) * 100;
            if (progressFill) progressFill.style.width = progressPercent + '%';
            if (progressText) progressText.textContent = `Step ${currentStep} di ${steps.length}`;
            
            // Resize del canvas pattern lock quando step 3 diventa visibile
            if (currentStep === 3) {
                const patternCanvas = document.getElementById('pattern-canvas');
                const patternContainer = document.getElementById('pattern-lock');
                if (patternCanvas && patternContainer) {
                    requestAnimationFrame(() => {
                        patternCanvas.width = patternContainer.offsetWidth;
                        patternCanvas.height = patternContainer.offsetHeight;
                    });
                }
            }
        };

        // L'apertura del popup è gestita da initializePopup, qui aggiungiamo solo il reset
        const _openBtnRip = document.getElementById('openNuovaAssistenzaPopupBtn');
        if (_openBtnRip) _openBtnRip.addEventListener('click', () => {
             showRiparazioneStep(1);
             popup.querySelector('form').reset();
        });

        nextBtn.addEventListener('click', () => {
            const currentPane = popup.querySelector(`.step-pane[data-step="${currentStep}"]`);
            const requiredInputs = currentPane.querySelectorAll('[required]');
            for (const input of requiredInputs) {
                if (!input.value) {
                    return showMessage('Compila tutti i campi obbligatori.', true);
                }
            }
            showRiparazioneStep(currentStep + 1);
        });
        prevBtn.addEventListener('click', () => showRiparazioneStep(currentStep - 1));
        
        <?php if (!empty($feedback_message)): ?>
            riparazionePopup.openPopup();
            <?php if (strpos($feedback_message, 'success') !== false): ?>
                <?php if (!empty($new_riparazione_id)): ?>
                    // Stampa automatica via iframe nascosto (evita blocco popup)
                    (() => {
                        const printFrame = document.createElement('iframe');
                        printFrame.style.cssText = 'position:fixed;width:0;height:0;border:none;left:-9999px;top:-9999px;';
                        printFrame.src = 'stampa_riparazione.php?id=<?php echo (int)$new_riparazione_id; ?>';
                        document.body.appendChild(printFrame);
                        // La pagina stampa_riparazione.php chiama window.print() automaticamente al caricamento
                        // Rimuovi iframe dopo un tempo sufficiente per la stampa
                        printFrame.onload = () => {
                            setTimeout(() => printFrame.remove(), 10000);
                        };
                    })();
                <?php endif; ?>
                setTimeout(riparazionePopup.closePopup, 2000);
            <?php endif; ?>
        <?php endif; ?>
    })();

    // --- Logica Specifica per Wizard Permuta ---
    (() => {
        const popup = document.getElementById('permutaPopup');
        if (!popup) return;
        
        const prevBtn = popup.querySelector('#prev-btn-permuta');
        const nextBtn = popup.querySelector('#next-btn-permuta');
        const submitBtn = popup.querySelector('#submit-permuta-btn');
        const steps = popup.querySelectorAll('.permuta-step-pane');
        const navSteps = popup.querySelectorAll('.stepper-nav .step-permuta');
        const progressFill = popup.querySelector('#wizard-progress-fill-permuta');
        const progressText = popup.querySelector('#wizard-progress-text-permuta');
        let currentStep = 1;

        window.showPermutaStep = (stepNumber) => {
            currentStep = stepNumber;
            steps.forEach(s => s.classList.toggle('active', parseInt(s.dataset.step) === currentStep));
            navSteps.forEach(ns => {
                const stepNum = parseInt(ns.dataset.step);
                ns.classList.toggle('active', stepNum === currentStep);
                ns.classList.toggle('completed', stepNum < currentStep);
            });
            
            if (prevBtn) prevBtn.style.display = currentStep > 1 ? 'flex' : 'none';
            if (nextBtn) nextBtn.style.display = currentStep < steps.length ? 'flex' : 'none';
            if (submitBtn) submitBtn.style.display = currentStep === steps.length ? 'flex' : 'none';
            
            // Aggiorna la progress bar
            const progressPercent = (currentStep / steps.length) * 100;
            if (progressFill) progressFill.style.width = progressPercent + '%';
            if (progressText) progressText.textContent = `Step ${currentStep} di ${steps.length}`;
        };

        // L'apertura del popup è gestita da initializePopup, qui aggiungiamo solo il reset
        const _openBtnPerm = document.getElementById('openNuovaPermutaPopupBtn');
        if (_openBtnPerm) {
            _openBtnPerm.addEventListener('click', () => {
                showPermutaStep(1);
                const form = popup.querySelector('form');
                if (form) form.reset();
                // Imposta la data odierna
                const dataPermutaInput = document.getElementById('data_permuta');
                if (dataPermutaInput) {
                    const today = new Date().toISOString().split('T')[0];
                    dataPermutaInput.value = today;
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                const currentPane = popup.querySelector(`.permuta-step-pane[data-step="${currentStep}"]`);
                if (currentPane) {
                    const requiredInputs = currentPane.querySelectorAll('[required]');
                    for (const input of requiredInputs) {
                        if (!input.value) {
                            showMessage('Compila tutti i campi obbligatori prima di procedere.', true);
                            input.focus();
                            return;
                        }
                    }
                }
                showPermutaStep(currentStep + 1);
            });
        }
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => showPermutaStep(currentStep - 1));
        }
        
        // Click sugli step per navigare direttamente
        navSteps.forEach(ns => {
            ns.addEventListener('click', () => {
                const stepNum = parseInt(ns.dataset.step);
                // Permetti di andare solo a step completati o step corrente
                if (stepNum <= currentStep) {
                    showPermutaStep(stepNum);
                }
            });
        });
    })();

    // --- Logica Specifica Autocomplete ---
    setupAutocomplete(
        document.getElementById('cliente_riparazione_autocomplete'),
        document.getElementById('cliente_suggestions_riparazione'),
        globalClientsData, item => item.display_name,
        client => {
            document.getElementById('cliente_id_riparazione').value = client.id;
            document.getElementById('telefono_riparazione_display').value = client.telefono_principale || '';
            // Aggiorna il telefono chip
            const telefonoText = document.getElementById('telefono_riparazione_text');
            if (telefonoText) {
                telefonoText.textContent = client.telefono_principale || 'Nessun numero';
            }
        }
    );
    setupAutocomplete(
        document.getElementById('cliente_permuta'),
        document.getElementById('client_suggestions_permuta'),
        globalClientsData, item => item.display_name,
        client => {
            document.getElementById('cliente_id_permuta').value = client.id;
            document.getElementById('telefono_cliente_permuta').value = client.telefono_principale || '';
        }
    );
     setupAutocomplete(
        document.getElementById('customerName_pp'),
        document.getElementById('clientAutocompleteList_pp'),
        globalClientsData, item => item.display_name,
        client => {
            document.getElementById('clientId_pp').value = client.id;
            document.getElementById('customerPhone_pp').value = client.telefono_principale || '';
            document.getElementById('customerEmail_pp').value = client.email || '';
        }
    );
     setupAutocomplete(
        document.getElementById('tuo_modello_permuta'),
        document.getElementById('product_suggestions_permuta'),
        globalProductsData, item => item.name,
        product => {
            document.getElementById('tuo_imei_permuta').value = product.code || '';
            document.getElementById('tuo_valore_vendita_permuta').value = product.priceSale1.toFixed(2);
            document.querySelector('#permutaPopup form').dispatchEvent(new Event('input', { bubbles: true })); // Trigger calculation update
        }
    );
    
    // --- Logica per Buono Regalo ---
    (() => {
        const _openBtnBuono = document.getElementById('openBuonoRegaloPopupBtn');
        if (_openBtnBuono) _openBtnBuono.addEventListener('click', () => {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < 12; i++) code += chars.charAt(Math.floor(Math.random() * chars.length));
            const codiceInput = document.getElementById('buono_codice');
            if (codiceInput) codiceInput.value = code;
        });
        const _copyBtn = document.getElementById('copy-code-btn');
        if (_copyBtn) _copyBtn.addEventListener('click', () => {
            const input = document.getElementById('buono_codice');
            if (!input) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value).then(() => showMessage('Codice copiato!', false)).catch(() => showMessage('Errore copia', true));
            } else {
                const ta = document.createElement('textarea');
                ta.value = input.value;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                showMessage('Codice copiato!', false);
            }
        });
         <?php if (!empty($gift_card_feedback_message)): ?>
            buonoRegaloPopup.openPopup();
            <?php if (strpos($gift_card_feedback_message, 'success') !== false): ?>
                setTimeout(buonoRegaloPopup.closePopup, 1000);
            <?php endif; ?>
        <?php endif; ?>
    })();
    
    // --- Logica per Calcoli Permuta e Prenotazione ---
    (() => {
        const permutaForm = document.getElementById('permuta-form');
        const prenotazioneForm = document.getElementById('prenotazione-prodotto-form');
        if (permutaForm) {
            const calcolaPermuta = () => {
                // Valori base
                const prezzoVenditaCeduto = parseFloat(document.getElementById('tuo_valore_vendita_permuta').value) || 0;
                const valorePermuta = parseFloat(document.getElementById('cliente_valore_permuta_main').value) || 0;
                
                // Costi ricondizionamento (somma di tutti i costi inseriti)
                let costiRicondizionamento = 0;
                permutaForm.querySelectorAll('.costo-importo').forEach(i => costiRicondizionamento += parseFloat(i.value) || 0);
                
                // Altri costi
                const costoAccessori = parseFloat(document.getElementById('costo_accessori_input_permuta').value) || 0;
                const costoProdotto = parseFloat(document.getElementById('costo_prodotto_input_permuta').value) || 0;
                
                // CALCOLO CONGUAGLIO CLIENTE (semplice: prezzo ceduto - valore permuta)
                const conguaglioCliente = prezzoVenditaCeduto - valorePermuta;
                
                // CALCOLO VALORE NETTO RICEVUTO (permuta - costi ricondizionamento)
                const valoreNettoRicevuto = valorePermuta - costiRicondizionamento;
                
                // CALCOLO MARGINE STIMATO
                // Se rivendo il dispositivo ricevuto al prezzo di vendita finale, il margine è:
                // Margine = Conguaglio + Valore Netto Ricevuto - Costo Prodotto Ceduto - Costo Accessori
                // Oppure semplicemente: Prezzo Ceduto - Costo Prodotto - Costo Accessori + Valore Netto - (costi già detratti)
                // Formula semplificata: Margine = Conguaglio + ValoreNetto - CostoProdotto - CostoAccessori
                const margineStimato = conguaglioCliente + valoreNettoRicevuto - costoProdotto - costoAccessori;
                
                // Aggiorna la UI
                document.getElementById('valore_vendita_ceduto_permuta').textContent = formatCurrency(prezzoVenditaCeduto);
                document.getElementById('valore_permuta_ricevuto_permuta').textContent = '- ' + formatCurrency(valorePermuta);
                document.getElementById('conguaglio_cliente_permuta').textContent = formatCurrency(conguaglioCliente);
                
                document.getElementById('totale_costi_ricondizionamento_permuta').textContent = formatCurrency(costiRicondizionamento);
                document.getElementById('costo_accessori_display_permuta').textContent = formatCurrency(costoAccessori);
                document.getElementById('costo_prodotto_display_permuta').textContent = formatCurrency(costoProdotto);
                document.getElementById('valore_netto_ricevuto_permuta').textContent = formatCurrency(valoreNettoRicevuto);
                document.getElementById('margine_permuta').textContent = formatCurrency(margineStimato);
                
                // Colore margine (verde se positivo, rosso se negativo)
                const margineEl = document.getElementById('margine_permuta');
                if (margineStimato < 0) {
                    margineEl.parentElement.style.background = 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)';
                } else {
                    margineEl.parentElement.style.background = 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)';
                }
                
                // Salva nei campi hidden
                document.getElementById('totale_costi_ricondizionamento_val_permuta').value = costiRicondizionamento.toFixed(2);
                document.getElementById('valore_netto_ricevuto_val_permuta').value = valoreNettoRicevuto.toFixed(2);
                document.getElementById('conguaglio_cliente_val_permuta').value = conguaglioCliente.toFixed(2);
                if (document.getElementById('margine_permuta_val')) {
                    document.getElementById('margine_permuta_val').value = margineStimato.toFixed(2);
                }
            };
            
            permutaForm.addEventListener('input', calcolaPermuta);
            // Inizializza calcoli al caricamento
            calcolaPermuta();
            
            document.getElementById('add_costo_btn_permuta').addEventListener('click', () => {
                const container = document.getElementById('costi_ricondizionamento_container_permuta');
                const newItem = document.createElement('div');
                newItem.className = 'costo-item-modern';
                newItem.innerHTML = `
                    <input type="text" name="costo_descrizione[]" placeholder="Descrizione costo...">
                    <input type="number" name="costo_importo[]" step="0.01" min="0" placeholder="0.00" class="costo-importo" value="0">
                    <button type="button" class="btn-remove-costo"><i class="fas fa-times"></i></button>
                `;
                container.appendChild(newItem);
            });
            permutaForm.addEventListener('click', e => {
                if (e.target.classList.contains('btn-remove-costo') || e.target.closest('.btn-remove-costo')) {
                    const item = e.target.closest('.costo-item-modern');
                    if (item) {
                        item.remove();
                        permutaForm.dispatchEvent(new Event('input'));
                    }
                }
            });
        }
        if (prenotazioneForm) {
            // Funzione per aggiornare i totali della prenotazione
            const updatePrenotazioneTotals = () => {
                const quantity = parseFloat(prenotazioneForm.quantity.value) || 0;
                const unitPrice = parseFloat(prenotazioneForm.unitPrice.value) || 0;
                const deposit = parseFloat(prenotazioneForm.depositAmount.value) || 0;
                const total = quantity * unitPrice;
                const remaining = Math.max(0, total - deposit);
                
                // Aggiorna display (textContent per div, value per hidden input)
                const totalDisplay = document.getElementById('productTotalPriceDisplay_pp');
                const remainingDisplay = document.getElementById('remainingAmountDisplay_pp');
                
                if (totalDisplay) totalDisplay.textContent = formatCurrency(total);
                if (remainingDisplay) remainingDisplay.textContent = formatCurrency(remaining);
                
                document.getElementById('productTotalPrice_pp').value = total.toFixed(2);
                document.getElementById('remainingAmount_pp').value = remaining.toFixed(2);
            };
            
            prenotazioneForm.addEventListener('input', updatePrenotazioneTotals);
            
            // Funzione globale per i bottoni +/- quantità
            window.adjustQuantityPP = function(delta) {
                const qtyInput = document.getElementById('quantity_pp');
                if (qtyInput) {
                    let currentVal = parseInt(qtyInput.value) || 1;
                    currentVal = Math.max(1, currentVal + delta);
                    qtyInput.value = currentVal;
                    // Trigger input event per aggiornare i totali
                    prenotazioneForm.dispatchEvent(new Event('input'));
                }
            };
            
            // Inizializza calcoli
            updatePrenotazioneTotals();
        }
        <?php if (!empty($permuta_feedback_message)): ?>
            permutaPopup.openPopup();
            <?php if (strpos($permuta_feedback_message, 'success') !== false): ?>
                setTimeout(permutaPopup.closePopup, 1000);
            <?php endif; ?>
        <?php endif; ?>
        <?php if (!empty($prenotazione_feedback_message)): ?>
            prenotazionePopup.openPopup();
        <?php endif; ?>
    })();

    // --- Gestione Pattern Lock ---
    (() => {
        const container = document.getElementById('pattern-lock');
        if (!container) return;
        const canvas = document.getElementById('pattern-canvas');
        const ctx = canvas.getContext('2d');
        const hiddenInput = document.getElementById('unlock-pattern');
        const dots = container.querySelectorAll('.pattern-dot');
        
        let selectedDots = [];
        let isDrawing = false;
        let currentMousePos = null;

        // Calcola le posizioni centrali dei dot relativi al canvas
        const getDotCenter = (dot) => {
            const containerRect = container.getBoundingClientRect();
            const dotRect = dot.getBoundingClientRect();
            return {
                x: dotRect.left + dotRect.width / 2 - containerRect.left,
                y: dotRect.top + dotRect.height / 2 - containerRect.top
            };
        };

        // Trova il dot sotto le coordinate (relative alla pagina)
        const getDotAtPoint = (clientX, clientY) => {
            for (const dot of dots) {
                const rect = dot.getBoundingClientRect();
                const cx = rect.left + rect.width / 2;
                const cy = rect.top + rect.height / 2;
                const dist = Math.sqrt((clientX - cx) ** 2 + (clientY - cy) ** 2);
                if (dist < rect.width * 0.8) return dot;
            }
            return null;
        };

        const selectDot = (dot) => {
            const dotId = dot.dataset.dot;
            if (selectedDots.find(d => d.dataset.dot === dotId)) return;
            dot.classList.add('selected');
            selectedDots.push(dot);
            hiddenInput.value = selectedDots.map(d => d.dataset.dot).join('-');
        };

        const drawLines = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            if (selectedDots.length === 0) return;

            ctx.strokeStyle = '#2d6a4f';
            ctx.lineWidth = 4;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.shadowColor = 'rgba(45, 106, 79, 0.4)';
            ctx.shadowBlur = 6;

            ctx.beginPath();
            const first = getDotCenter(selectedDots[0]);
            ctx.moveTo(first.x, first.y);
            for (let i = 1; i < selectedDots.length; i++) {
                const pos = getDotCenter(selectedDots[i]);
                ctx.lineTo(pos.x, pos.y);
            }
            // Linea che segue il dito/mouse durante il disegno
            if (isDrawing && currentMousePos) {
                ctx.lineTo(currentMousePos.x, currentMousePos.y);
            }
            ctx.stroke();
        };

        const resetPattern = () => {
            selectedDots = [];
            isDrawing = false;
            currentMousePos = null;
            dots.forEach(d => d.classList.remove('selected'));
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hiddenInput.value = '';
        };

        // Resize canvas per adattarlo al contenitore
        const resizeCanvas = () => {
            canvas.width = container.offsetWidth;
            canvas.height = container.offsetHeight;
            drawLines();
        };

        // --- Event Handlers ---
        const getClientPos = (e) => {
            if (e.touches && e.touches.length > 0) {
                return { clientX: e.touches[0].clientX, clientY: e.touches[0].clientY };
            }
            return { clientX: e.clientX, clientY: e.clientY };
        };

        const getCanvasPos = (clientX, clientY) => {
            const rect = container.getBoundingClientRect();
            return { x: clientX - rect.left, y: clientY - rect.top };
        };

        const onStart = (e) => {
            const { clientX, clientY } = getClientPos(e);
            const dot = getDotAtPoint(clientX, clientY);
            if (!dot) return;
            e.preventDefault();
            resetPattern();
            isDrawing = true;
            selectDot(dot);
            drawLines();
        };

        const onMove = (e) => {
            if (!isDrawing) return;
            e.preventDefault();
            const { clientX, clientY } = getClientPos(e);
            currentMousePos = getCanvasPos(clientX, clientY);
            const dot = getDotAtPoint(clientX, clientY);
            if (dot) selectDot(dot);
            drawLines();
        };

        const onEnd = (e) => {
            if (!isDrawing) return;
            isDrawing = false;
            currentMousePos = null;
            drawLines(); // Ridisegna senza la linea che segue il mouse
            if (selectedDots.length < 2) resetPattern();
        };

        // Mouse events
        container.addEventListener('mousedown', onStart);
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onEnd);

        // Touch events
        container.addEventListener('touchstart', onStart, { passive: false });
        document.addEventListener('touchmove', onMove, { passive: false });
        document.addEventListener('touchend', onEnd);

        // Aggiungi pulsante Reset
        const hintEl = container.parentElement.querySelector('.pattern-hint');
        if (hintEl) {
            const resetBtn = document.createElement('button');
            resetBtn.type = 'button';
            resetBtn.textContent = 'Reset';
            resetBtn.style.cssText = 'margin-left:8px;padding:3px 12px;border:1px solid #cbd5e1;border-radius:6px;background:#f8f9fa;color:#495057;cursor:pointer;font-size:0.75rem;transition:all 0.2s;';
            resetBtn.addEventListener('mouseenter', () => { resetBtn.style.background = '#e9ecef'; });
            resetBtn.addEventListener('mouseleave', () => { resetBtn.style.background = '#f8f9fa'; });
            resetBtn.addEventListener('click', resetPattern);
            hintEl.appendChild(resetBtn);
        }

        // Inizializza canvas size
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
    })();

    // --- Gestione Globale ---
    document.addEventListener('DOMContentLoaded', () => {
        <?php echo $message_from_session; ?>
        
        // --- NUOVO: Logica per Dropdown Menu Header ---
        const dropdownToggles = document.querySelectorAll('nav .has-dropdown > button, nav .has-submenu > a');
        const closeAllDropdowns = (exceptThisOne = null) => {
            document.querySelectorAll('nav .has-dropdown.active, nav .has-submenu.active').forEach(activeItem => {
                if (exceptThisOne && activeItem.contains(exceptThisOne)) return;
                activeItem.classList.remove('active');
            });
        };
        
        const positionDropdown = (toggleElement) => {
            const parentLi = toggleElement.parentElement;
            const dropdown = parentLi.querySelector(':scope > ul.dropdown');
            if (!dropdown) return;
            
            const rect = toggleElement.getBoundingClientRect();
            dropdown.style.top = (rect.bottom + 10) + 'px';
            dropdown.style.left = (rect.left) + 'px';
        };
        
        const positionSubmenu = (submenuParentLi) => {
            const submenu = submenuParentLi.querySelector(':scope > ul.submenu');
            if (!submenu) {
                return;
            }
            
            const dropdown = submenuParentLi.closest('ul.dropdown');
            if (!dropdown) {
                return;
            }
            
            submenu.style.display = 'block';
            submenu.style.visibility = 'visible';
            submenu.style.opacity = '1';
            submenu.style.zIndex = '9999';
            
            const dropdownRect = dropdown.getBoundingClientRect();
            const parentItemRect = submenuParentLi.getBoundingClientRect();
            
            const offsetTopInsideDropdown = parentItemRect.top - dropdownRect.top;
            
            const leftPos = parentItemRect.right + 10;
            const topPos = dropdownRect.top + offsetTopInsideDropdown;
            
            submenu.style.left = leftPos + 'px';
            submenu.style.top = topPos + 'px';
            submenu.style.position = 'fixed';
        };
        
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const parentLi = this.parentElement;
                const wasActive = parentLi.classList.contains('active');
                
                // CHIUDI TUTTI GLI ALTRI DROPDOWN PRINCIPALI (di primo livello)
                const isTopLevelToggle = parentLi.classList.contains('has-dropdown') && parentLi.parentElement?.matches('nav > ul');
                if (isTopLevelToggle) {
                    document.querySelectorAll('nav > ul > li.has-dropdown.active').forEach(item => {
                        if (item !== parentLi) {
                            item.classList.remove('active');
                            const dropdown = item.querySelector(':scope > ul.dropdown');
                            if (dropdown) {
                                dropdown.style.display = 'none';
                            }
                        }
                    });
                }
                
                // Chiudi i submenu nello stesso dropdown
                const parentMenu = this.closest('ul');
                if (parentMenu) {
                    parentMenu.querySelectorAll('li.has-submenu.active').forEach(item => {
                        if (item !== parentLi) {
                            item.classList.remove('active');
                            const submenu = item.querySelector(':scope > ul.submenu');
                            if (submenu) {
                                submenu.style.display = 'none';
                            }
                        }
                    });
                }
                
                if (!wasActive) {
                    parentLi.classList.add('active');
                    if (this.closest('ul.dropdown')) {
                        // È un submenu dentro un dropdown
                        positionSubmenu(parentLi);
                    } else if (this.closest('nav > ul')) {
                        // È un dropdown principale
                        positionDropdown(this);
                    }
                }
                else {
                    parentLi.classList.remove('active');
                    // Nascondi il submenu
                    const submenu = parentLi.querySelector(':scope > ul.submenu');
                    if (submenu) {
                        submenu.style.display = 'none';
                    }
                }
            });
        });
        
        // Effetto scroll per header compatto
        window.addEventListener('scroll', () => {
            const topBar = document.querySelector('.top-bar');
            if (topBar) {
                if (window.scrollY > 50) {
                    topBar.classList.add('scrolled');
                } else {
                    topBar.classList.remove('scrolled');
                }
            }
        });
        
        // Ricalcola la posizione dei dropdown e submenu al resize
        window.addEventListener('resize', () => {
            document.querySelectorAll('nav .has-dropdown.active, nav .has-submenu.active').forEach(activeItem => {
                const toggle = activeItem.querySelector(':scope > button, :scope > a');
                if (toggle && activeItem.closest('nav > ul')) {
                    positionDropdown(toggle);
                } else if (activeItem.closest('ul.dropdown')) {
                    positionSubmenu(activeItem);
                }
            });
        });
        
        // Supporto per chiusura con tasto Escape
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                document.querySelectorAll('nav .has-dropdown.active, nav .has-submenu.active').forEach(activeItem => {
                    activeItem.classList.remove('active');
                    const submenu = activeItem.querySelector(':scope > ul.submenu');
                    if (submenu) {
                        submenu.style.display = 'none';
                    }
                });
            }
        });
        
        // Miglioramento: Chiudi i menu quando clicchi fuori
        document.addEventListener('click', function(event) {
            const navElement = document.querySelector('nav');
            if (navElement && !navElement.contains(event.target)) {
                document.querySelectorAll('nav .has-dropdown.active, nav .has-submenu.active').forEach(activeItem => {
                    activeItem.classList.remove('active');
                    const submenu = activeItem.querySelector(':scope > ul.submenu');
                    if (submenu) {
                        submenu.style.display = 'none';
                    }
                });
            }
        });

        // --- NUOVO: Logica per Barra di Ricerca Globale ---
        const searchBar = document.querySelector('.search-bar');
        const searchResultsDropdown = document.getElementById('searchResultsDropdown');
        if (searchBar) {
            let _searchDebounce = null;
            const escapeHtml = (str) => str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            searchBar.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                if (query.length < 2) {
                    searchResultsDropdown.style.display = 'none';
                    return;
                }
                if (_searchDebounce) clearTimeout(_searchDebounce);
                _searchDebounce = setTimeout(() => {
                    const filteredClients = globalClientsData.filter(c => c.display_name.toLowerCase().includes(query));
                    const filteredProducts = globalProductsData.filter(p => p.name.toLowerCase().includes(query));
                    let html = '';
                    if (filteredClients.length > 0) {
                        html += `<div class="result-category">Clienti</div>`;
                        filteredClients.forEach(c => {
                            html += `<a href="#" data-id="${c.id}" data-type="client">${escapeHtml(c.display_name)}</a>`;
                        });
                    }
                    if (filteredProducts.length > 0) {
                        html += `<div class="result-category">Prodotti</div>`;
                        filteredProducts.forEach(p => {
                            html += `<a href="#" data-id="${p.id}" data-type="product">${escapeHtml(p.name)}</a>`;
                        });
                    }
                    if (!html) {
                        html = `<a href="#" class="no-results">Nessun risultato</a>`;
                    }
                    searchResultsDropdown.innerHTML = html;
                    searchResultsDropdown.style.display = 'block';
                }, 200);
            });
        }
        
        // --- NUOVO: Gestione Click sui Risultati di Ricerca ---
        if (searchResultsDropdown) {
            searchResultsDropdown.addEventListener('click', function(event) {
                const target = event.target.closest('a');
                if (!target || !target.dataset.id) return;

                event.preventDefault();

                const id = target.dataset.id;
                const type = target.dataset.type;

                let redirectUrl = '';
                if (type === 'client') {
                    // Reindirizza alla pagina del cliente. Assumiamo si chiami 'scheda_cliente.php'
                    redirectUrl = `scheda_cliente.php?id=${id}`;
                } else if (type === 'product') {
                    // Reindirizza alla pagina del prodotto. Assumiamo si chiami 'dettaglio_prodotto.php'
                    redirectUrl = `dettaglio_prodotto.php?id=${id}`;
                }
                
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
            });
        }
        
        // --- Gestione Chiusura Elementi con Click Esterno ---
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.has-dropdown') && !event.target.closest('.has-submenu')) {
                closeAllDropdowns();
            }
            if (!event.target.closest('.search-container')) {
                if(searchResultsDropdown) searchResultsDropdown.style.display = 'none';
            }
            if (!event.target.closest('.user-menu-container')) {
                document.getElementById('userMenuContainer')?.classList.remove('active');
            }
        });

        // Gestione Hamburger Menu Mobile
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const mobileNav = document.querySelector('nav.mobile-nav');
        
        if (hamburgerBtn && mobileNav) {
            // Toggle hamburger menu
            hamburgerBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                hamburgerBtn.classList.toggle('active');
                mobileNav.classList.toggle('active');
            });
            
            // Chiudi menu quando clicchi su un link
            mobileNav.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function(event) {
                    const parentLi = this.closest('li');
                    const hasSubmenu = parentLi && parentLi.classList.contains('has-submenu') && parentLi.querySelector(':scope > ul.submenu');
                    if (hasSubmenu) {
                        event.stopPropagation();
                        return;
                    }
                    hamburgerBtn.classList.remove('active');
                    mobileNav.classList.remove('active');
                });
            });
            
            // Chiudi menu quando clicchi su un bottone (es. dropdown)
            mobileNav.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    // Se il bottone ha un submenu, non chiudere il menu
                    if (!this.parentElement.querySelector(':scope > ul.submenu')) {
                        e.stopPropagation();
                        // Se non è un dropdown, chiudi il menu
                        if (!this.parentElement.classList.contains('has-dropdown')) {
                            hamburgerBtn.classList.remove('active');
                            mobileNav.classList.remove('active');
                        }
                    }
                });
            });
        }
        
        // Chiudi hamburger menu quando clicchi fuori
        document.addEventListener('click', function(event) {
            if (hamburgerBtn && mobileNav) {
                if (!event.target.closest('nav') && !event.target.closest('.hamburger-btn')) {
                    hamburgerBtn.classList.remove('active');
                    mobileNav.classList.remove('active');
                }
            }
        });
        
        // Gestione Menu Utente
        const userMenuContainer = document.getElementById('userMenuContainer');
        if(userMenuContainer) userMenuContainer.addEventListener('click', e => {
            e.stopPropagation();
            userMenuContainer.classList.toggle('active');
        });
    });

    // ===== SISTEMA TOAST NOTIFICATIONS =====
    window.showToast = function(message, type = 'success') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        toast.addEventListener('animationend', function(e) {
            if (e.animationName === 'fadeOutToast') toast.remove();
        });
    };

    // ===== CONFIRMATION DIALOG =====
    window.showConfirmDialog = function(title, message, onConfirm, onCancel) {
        let backdrop = document.getElementById('confirm-backdrop');
        let modal = document.getElementById('confirm-modal');
        if (!backdrop) { backdrop = document.createElement('div'); backdrop.id = 'confirm-backdrop'; backdrop.className = 'confirm-modal-backdrop'; document.body.appendChild(backdrop); }
        if (!modal) { modal = document.createElement('div'); modal.id = 'confirm-modal'; modal.className = 'confirm-modal'; document.body.appendChild(modal); }
        modal.innerHTML = `<h3 class="confirm-title">${title}</h3><p class="confirm-message">${message}</p><div class="confirm-actions"><button class="btn-cancel">Annulla</button><button class="btn-confirm">Conferma</button></div>`;
        const btnCancel = modal.querySelector('.btn-cancel');
        const btnConfirm = modal.querySelector('.btn-confirm');
        const close = () => { backdrop.classList.remove('show'); modal.classList.remove('show'); setTimeout(() => { backdrop.style.display = 'none'; modal.style.display = 'none'; }, 300); };
        btnCancel.addEventListener('click', () => { close(); if (onCancel) onCancel(); });
        btnConfirm.addEventListener('click', () => { close(); if (onConfirm) onConfirm(); });
        backdrop.style.display = 'block';
        modal.style.display = 'block';
        setTimeout(() => { backdrop.classList.add('show'); modal.classList.add('show'); btnConfirm.focus(); }, 10);
        const handleEsc = (e) => { if (e.key === 'Escape') { close(); document.removeEventListener('keydown', handleEsc); } };
        document.addEventListener('keydown', handleEsc);
    };

    // ===== DARK MODE =====
    window.initDarkMode = function() {
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        const savedMode = localStorage.getItem('darkMode');
        if (savedMode === 'true' || (!savedMode && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark-mode');
            document.body.classList.add('dark-mode');
            if (darkModeToggle) darkModeToggle.setAttribute('aria-pressed', 'true');
        }
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', () => {
                const isDark = document.documentElement.classList.toggle('dark-mode');
                document.body.classList.toggle('dark-mode');
                localStorage.setItem('darkMode', isDark);
                darkModeToggle.setAttribute('aria-pressed', isDark);
            });
        }
    };

    // ===== SORTABLE TABLES =====
    window.makeSortableTable = function(tableSelector) {
        const table = document.querySelector(tableSelector);
        if (!table) return;
        const headers = table.querySelectorAll('th.sortable');
        headers.forEach((header, index) => {
            header.addEventListener('click', () => {
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const isAscending = header.classList.contains('sort-asc');
                headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
                header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
                rows.sort((a, b) => {
                    let aValue = a.children[index].textContent.trim();
                    let bValue = b.children[index].textContent.trim();
                    if (!isNaN(aValue) && !isNaN(bValue)) return isAscending ? bValue - aValue : aValue - bValue;
                    return isAscending ? bValue.localeCompare(aValue) : aValue.localeCompare(bValue);
                });
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    };

    // ===== LOADING STATE =====
    window.setLoadingState = function(button, isLoading = true) {
        const originalHTML = button.innerHTML;
        if (isLoading) {
            button.disabled = true;
            button.style.opacity = '0.6';
            button.innerHTML = '<i class="spinner" style="display: inline-block; margin-right: 8px;"></i> Caricamento...';
            button.setAttribute('data-original-html', originalHTML);
        } else {
            button.disabled = false;
            button.style.opacity = '1';
            button.innerHTML = button.getAttribute('data-original-html') || originalHTML;
        }
    };

    // Inizializza dark mode
    window.initDarkMode();

</script>


