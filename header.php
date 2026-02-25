<?php
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
                    $feedback_message = "<div class='feedback success'>Scheda di riparazione salvata con successo! Il popup si chiuderà tra 1 secondo.</div>";
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
                $data_scadenza_buono = !empty($_POST['data_scadenza']) ? "'" . $conn->real_escape_string($_POST['data_scadenza']) . "'" : 'NULL';
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
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gestione TS Service</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --brand-color: #28a745;
      --brand-dark: #218838;
      --text-dark: #34495e;
      --text-light: #7f8c8d;
      --border-color: #ecf0f1;
      --bg-light: #f7f9fc;
      --bg-white: #ffffff;
      --success-color: #2ecc71;
      --shadow-md: 0 4px 15px rgba(0, 0, 0, 0.07); /* Ombra più soffusa */
      --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    html {
        height: 100%;
    }

    body {
      font-family: 'Poppins', sans-serif;
      color: var(--text-dark);
      margin: 0;
      background: var(--bg-light); /* Sfondo più pulito */
      padding: 0;
      padding-top: 80px; /* Ridotto per header più compatto */
      min-height: 100vh;
      overflow-y: auto;
      overflow-x: visible;
    }

    /* --- INIZIO MODIFICHE HEADER --- */
    .top-bar {
      background-color: var(--brand-color); /* MODIFICA: Ripristinato colore solido */
      color: white;
      padding: 0 30px; /* Padding solo orizzontale */
      height: 80px; /* Altezza fissa per l'header */
      width: 100vw;
      box-sizing: border-box;
      display: flex;
      align-items: center;
      gap: 20px; /* Gap ridotto */
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1000;
      box-shadow: var(--shadow-md); /* Ombra più moderna */
      transition: all 0.3s ease;
      overflow: visible !important;
    }

    .logo {
      font-size: 28px; /* Dimensione ridotta per un look più fine */
      font-weight: 700; /* Leggermente più bold */
      white-space: nowrap;
      color: white;
      text-decoration: none;
      cursor: pointer;
      text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
    }
    
    .search-container {
        position: relative; /* Necessario per il posizionamento del dropdown dei risultati */
        display: flex;
        align-items: center;
        background-color: rgba(255, 255, 255, 0.15);
        border-radius: 25px;
        padding: 5px 15px;
        width: 100%;
        max-width: 400px; /* Larghezza massima per la barra di ricerca */
        transition: background-color 0.3s ease;
    }
    
    .search-container:focus-within {
        background-color: rgba(255, 255, 255, 0.3);
    }

    .search-container i {
        color: rgba(255, 255, 255, 0.8);
        margin-right: 10px;
    }

    .search-bar {
        background: transparent;
        border: none;
        color: white;
        font-size: 15px;
        outline: none;
        width: 100%;
    }
    
    .search-bar::placeholder {
        color: rgba(255, 255, 255, 0.7);
        font-weight: 400;
    }

    /* NUOVO: Stili per il dropdown dei risultati di ricerca */
    .search-results-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        width: 100%;
        background-color: var(--bg-white);
        border-radius: 8px;
        box-shadow: var(--shadow-lg);
        max-height: 300px;
        overflow-y: auto;
        z-index: 1001;
    }
    .search-results-dropdown a {
        display: block;
        padding: 12px 20px;
        color: var(--text-dark);
        text-decoration: none;
        transition: background-color 0.2s ease;
    }
    .search-results-dropdown a:hover {
        background-color: var(--bg-light);
    }
     .search-results-dropdown a.no-results:hover {
        background-color: transparent;
        cursor: default;
    }
    .search-results-dropdown .result-category {
        padding: 8px 20px;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-light);
        background-color: #f8f9fa;
        border-bottom: 1px solid var(--border-color);
    }

    nav {
        margin-left: auto; /* Sposta la navigazione a destra */
        overflow: visible !important;
    }

    nav ul {
      list-style: none;
      margin: 0;
      padding: 0;
      display: flex;
      gap: 5px; /* Gap ridotto tra i pulsanti */
      height: 100%;
      overflow: visible !important;
    }

    nav ul li {
      position: relative;
      display: flex; /* Allinea il contenuto verticalmente */
      align-items: center;
      overflow: visible !important;
    }

    nav ul li button,
    nav ul li a {
      background-color: transparent; /* Sfondo trasparente di default */
      border: none;
      color: white; /* Testo bianco */
      font-size: 15px; /* Dimensione del font ridotta */
      font-weight: 500;
      padding: 10px 20px;
      cursor: pointer;
      border-radius: 8px; /* Bordi arrotondati */
      user-select: none;
      text-decoration: none;
      display: flex; /* Per allineare icona e testo */
      align-items: center;
      gap: 8px; /* Spazio tra icona e testo */
      white-space: nowrap;
      transition: background-color 0.2s ease, color 0.2s ease;
      height: fit-content;
    }

    nav ul li button:hover,
    nav ul li a:hover {
        background-color: rgba(255, 255, 255, 0.15); /* Sfondo semi-trasparente al hover */
    }
    
    /* Indicatore di pagina attiva */
    nav ul li a.active-link {
        background-color: rgba(255, 255, 255, 0.2);
        font-weight: 600;
        box-shadow: inset 0 -3px 0 0 white; /* Sottolineatura per la pagina attiva */
    }

    button.no-arrow::after {
      content: "";
    }

    nav ul li.has-dropdown > button::after,
    nav ul li.has-dropdown > a::after {
      content: "\25BC";
      font-size: 10px;
      color: rgba(255, 255, 255, 0.8); /* Freccia più chiara */
      margin-left: 8px;
    }

    /* Stili dropdown */
    nav ul li ul.dropdown {
      display: none;
      position: fixed;
      background-color: var(--bg-white);
      min-width: 220px;
      border-radius: 8px;
      box-shadow: var(--shadow-lg);
      padding: 8px 0;
      list-style: none;
      z-index: 2000;
      transform-origin: top;
      animation: scaleYIn 0.3s ease;
      overflow: visible;
      height: auto;
      width: auto;
    }
     @keyframes scaleYOut { from { opacity: 0; transform: scaleY(0.8); } to { opacity: 1; transform: scaleY(1); } }
     @keyframes scaleYIn { from { opacity: 0; transform: scaleY(0.8); } to { opacity: 1; transform: scaleY(1); } }
    
    /* MODIFICA: Rimosso :hover per la visualizzazione, ora gestito da JS con la classe .active */
    nav ul li.active > ul.dropdown { 
        display: block; 
    }

    nav ul li ul.dropdown li a, nav ul li ul.dropdown li button {
      padding: 12px 20px; color: var(--text-dark); background-color: transparent;
      width: 100%; text-align: left; border-radius: 0; font-size: 15px;
    }
    nav ul li ul.dropdown li a:hover, nav ul li ul.dropdown li button:hover {
      background-color: var(--brand-color); color: white;
    }
    nav ul li ul.dropdown li.has-submenu > a::after {
      content: " \25B6"; float: right; font-size: 10px;
      margin-left: 10px; color: var(--text-light);
    }
    nav ul li ul.dropdown li ul.submenu {
      display: none; 
      position: fixed; 
      background-color: var(--bg-white); 
      min-width: 200px; 
      border-radius: 8px;
      box-shadow: var(--shadow-lg); 
      padding: 8px 0; 
      list-style: none;
      z-index: 2100; 
      transform-origin: top; 
      animation: scaleYIn 0.3s ease;
      overflow: visible;
      height: auto;
      width: auto;
    }
    @keyframes scaleXIn { from { opacity: 0; transform: scaleX(0.8); } to { opacity: 1; transform: scaleX(1); } }
    
    /* MODIFICA: Rimosso :hover per la visualizzazione, ora gestito da JS con la classe .active */
    nav ul li ul.dropdown li.active > ul.submenu { 
        display: block; 
    }

    /* Menu Utente Migliorato */
    .user-menu-container {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-left: 20px;
        padding-left: 20px;
        border-left: 1px solid rgba(255, 255, 255, 0.2);
        cursor: pointer;
    }
    .user-greeting {
        color: white;
        font-weight: 500;
        font-size: 15px;
        white-space: nowrap;
    }
    .user-greeting .user-name {
        font-weight: 600;
    }
    .user-icon-trigger {
        font-size: 28px;
        color: white;
        transition: transform 0.2s ease;
    }
    .user-menu-container:hover .user-icon-trigger {
        transform: scale(1.1);
    }
    
    .user-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 15px); /* Più spazio dall'header */
        right: 0;
        background-color: var(--bg-white);
        border-radius: 8px;
        box-shadow: var(--shadow-lg);
        min-width: 240px; /* Più largo */
        padding: 15px;
        animation: fadeInDropdown 0.3s ease-out forwards;
        transform-origin: top right;
        opacity: 0;
        visibility: hidden;
        z-index: 1001;
    }
    .user-menu-container.active .user-dropdown { display: block; opacity: 1; visibility: visible; }
    @keyframes fadeInDropdown {
        from { opacity: 0; transform: translateY(-10px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .user-dropdown-info { font-size: 15px; color: var(--text-dark); margin-bottom: 10px;
        padding-bottom: 10px; border-bottom: 1px solid var(--border-color); text-align: center; }
    .user-dropdown-info strong { font-weight: 600; display: block; margin-bottom: 3px; font-size: 16px; }
    .user-dropdown-info span { display: block; color: var(--text-light); font-size: 13px; }
    .user-dropdown .logout-button { display: block; width: 100%; text-align: center;
        background-color: var(--brand-color); color: white; padding: 10px 15px;
        border-radius: 5px; text-decoration: none; font-size: 14px;
        transition: background-color 0.2s ease, transform 0.2s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .user-dropdown .logout-button:hover { background-color: var(--brand-dark); transform: translateY(-1px); }

    @media (max-width: 1200px) {
        .user-greeting { display: none; } /* Nasconde il saluto su schermi più piccoli */
        .search-container { max-width: 250px; }
        .top-bar { gap: 15px; padding: 0 20px; }
    }
    
    @media (max-width: 992px) {
        .top-bar nav { display: none; } /* Nasconde la navigazione principale */
        .search-container { margin-left: auto; } /* Sposta la ricerca a destra */
        .user-menu-container { margin-left: 15px; padding-left: 15px; }
        /* Qui potresti aggiungere un hamburger menu per mobile */
    }
    
    @media (max-width: 768px) {
        .top-bar { height: auto; flex-direction: column; align-items: flex-start; padding: 15px; gap: 15px; }
        .search-container { width: 100%; max-width: none; margin-left: 0; }
        .user-menu-container { width: 100%; display: flex; justify-content: flex-end; margin-top: 10px;
            padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.2); margin-left: 0; padding-left: 0; border-left: none; }
        .user-greeting { display: block; } /* Ri-mostra il saluto in modalità mobile */
    }
    /* --- FINE MODIFICHE HEADER --- */


    /* Stili per il popup wizard e buono regalo */
    .popup-overlay {
        position: fixed;
        inset: 0;
        background-color: rgba(52, 73, 94, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 1rem;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease-out, visibility 0.3s ease-out;
    }
    
    .popup-overlay.visible {
        opacity: 1;
        visibility: visible;
    }

    .wizard-container, .popup-content {
      width: 100%;
      background-color: var(--bg-white);
      border-radius: 12px; /* Coerenza con il nuovo stile */
      box-shadow: var(--shadow-lg);
      max-height: 95vh;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      position: relative;
      animation: scaleInPopup 0.4s ease;
    }
    .wizard-container { max-width: 900px; }
    .popup-content { max-width: 650px; }

    @keyframes scaleInPopup { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }

    .close-btn {
        position: absolute;
        top: 15px;
        right: 15px; /* Avvicinato al bordo */
        background: transparent;
        border: none;
        font-size: 2rem; /* Leggermente più piccolo */
        color: rgba(255, 255, 255, 0.7); /* Adattato al nuovo header */
        cursor: pointer;
        z-index: 10;
        transition: color 0.2s ease, transform 0.2s ease;
        line-height: 1;
    }
    .close-btn:hover { color: white; transform: rotate(90deg); }
    
    /* MODIFICA: Stile popup header unificato */
    .wizard-header, .popup-header, .modal-header {
      padding: 1.2rem 2rem;
      border-bottom: 1px solid var(--border-color);
      flex-shrink: 0;
      background-color: var(--brand-color); /* MODIFICA: Ripristinato colore solido */
      color: white;
      text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
      border-radius: 12px 12px 0 0;
      margin: 0;
    }
    
    .wizard-header h1, .popup-header h2, .modal-header h3 {
        text-align: center; margin: 0; font-size: 1.5rem;
        font-weight: 600; color: white;
    }
    
    .modal-content .modal-header {
        padding: 1.2rem 1.5rem;
        margin: 0;
    }
    
    .modal-content .close-button {
        color: rgba(255, 255, 255, 0.7);
        font-size: 2rem;
    }

    .stepper-nav {
      display: flex; justify-content: space-between;
      padding: 1.5rem 2.5rem; border-bottom: 1px solid var(--border-color);
      background-color: var(--bg-light);
      flex-shrink: 0;
    }
    .step {
      display: flex; align-items: center; flex-direction: column;
      text-align: center; position: relative; flex: 1;
    }
    .step-icon {
      width: 45px; height: 45px; border-radius: 50%;
      background-color: var(--border-color); color: var(--text-light);
      display: flex; align-items: center; justify-content: center;
      font-weight: 600; transition: all 0.3s ease;
      border: 3px solid var(--border-color); z-index: 2;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .step-label { font-size: 0.85rem; font-weight: 500; color: var(--text-light); margin-top: 0.6rem; transition: all 0.3s ease; }
    .step.active .step-icon, .step.completed .step-icon { background-color: var(--brand-color); border-color: var(--brand-color); color: white; }
    .step.completed .step-icon { background-color: var(--success-color); border-color: var(--success-color); }
    .step.active .step-label { color: var(--brand-color); font-weight: 600; }
    .step.completed .step-label { color: var(--success-color); }
    .step:not(:last-child)::after {
      content: ''; position: absolute; top: 22px; left: 50%;
      width: 100%; height: 3px; background-color: var(--border-color);
      z-index: 1; transition: background-color 0.3s ease;
    }
    .step.completed::after { background-color: var(--success-color); }

    .wizard-body, .popup-body, .modal-body {
        padding: 2.5rem;
        background-color: #ffffff; /* Sfondo bianco per il corpo */
    }
    .modal-content .modal-body {
        padding: 1.5rem;
    }
    
    .step-pane { display: none; animation: fadeIn 0.5s ease; }
    .step-pane.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.8rem;
    }
    .form-group { display: flex; flex-direction: column; gap: 0.6rem; }
    .form-group.full-width { grid-column: 1 / -1; }
    label { font-weight: 500; font-size: 0.95rem; color: var(--text-dark); margin-bottom: 0.2rem; }
    input, select, textarea {
      width: 100%; padding: 0.85rem 1.1rem;
      border: 1px solid #dcdfe6;
      border-radius: 10px;
      font-size: 1rem; color: var(--text-dark);
      box-sizing: border-box; transition: all 0.2s ease;
      background-color: white;
    }
    input:focus, select:focus, textarea:focus {
      border-color: var(--brand-color); outline: none; box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.2);
    }
    .client-input-container {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .client-input-container input[type="text"] {
        flex-grow: 1;
    }

    .wizard-footer, .popup-footer, .modal-footer {
      display: flex; 
      justify-content: space-between;
      padding: 1.5rem 2.5rem; 
      border-top: 1px solid var(--border-color);
      background-color: #fdfdfd; 
      flex-shrink: 0;
      gap: 1rem;
      border-radius: 0 0 12px 12px;
    }
    .modal-footer {
        justify-content: flex-end;
    }

    .wizard-btn, .popup-btn, .modal-footer button {
      padding: 0.8rem 2.2rem;
      font-size: 1.05rem; 
      font-weight: 600;
      border-radius: 10px;
      border: none;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    }
    .wizard-btn.prev, .modal-footer .cancel-button { 
        background-color: #e0e6eb; 
        color: var(--text-dark); 
    }
    .wizard-btn.prev:hover, .modal-footer .cancel-button:hover { 
        background-color: #d1d9e0; 
    }
    .wizard-btn.next, .wizard-btn.submit, .popup-btn.submit, .modal-footer .save-button { 
        background-color: var(--brand-color); 
        color: white;
    }
    .wizard-btn.next:hover, .wizard-btn.submit:hover, .popup-btn.submit:hover, .modal-footer .save-button:hover {
        background-color: var(--brand-dark);
        transform: translateY(-2px);
        box-shadow: 0 5px 12px rgba(0,0,0,0.15);
    }
    .wizard-btn:disabled, .popup-btn:disabled { 
        background: #ecf0f1; 
        color: #c0c0c0; 
        cursor: not-allowed; 
        box-shadow: none;
        transform: none;
        border: none;
    }
    
    .pattern-lock {
        width: 180px; height: 180px; display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 20px; position: relative; user-select: none; touch-action: none;
    }
    #pattern-canvas { position: absolute; top: 0; left: 0; pointer-events: none; z-index: 1; }
    .pattern-dot {
        width: 100%; height: 100%; background: #ecf0f1; border-radius: 50%;
        border: 1px solid #dcdfe6; cursor: pointer; z-index: 2;
        transition: all 0.2s ease;
    }
    .pattern-dot.selected { background-color: var(--brand-color); border-color: var(--brand-dark); transform: scale(1.1); }
    
    .feedback { padding: 1rem 1.5rem; margin: 0 0 1.5rem 0; border-radius: 8px; font-weight: 500; border: 1px solid transparent; }
    .feedback.success { background-color: #eafaf1; border-color: #b7e1c7; color: #155724; }
    .feedback.error { background-color: #fbebee; border-color: #f5c6cb; color: #721c24; }

    .code-input-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .code-input-group input {
        flex-grow: 1;
        border-right: none;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .copy-btn {
        background-color: var(--brand-color);
        color: white;
        border: none;
        padding: 0.85rem 1.2rem;
        border-radius: 10px;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        cursor: pointer;
        transition: background-color 0.2s ease, transform 0.2s ease;
        font-size: 1rem;
        box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    }
    .copy-btn:hover {
        background-color: var(--brand-dark);
        transform: translateY(-1px);
    }
    .copy-btn:active {
        transform: translateY(0);
        box-shadow: none;
    }
    .copy-message {
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        font-size: 0.85rem;
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
        pointer-events: none;
        z-index: 2001;
    }
    .copy-message.show {
        opacity: 1;
    }

    /* Stili per il Modal/Popup (Nuovo Cliente) */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 2010;
        padding: 0.8rem;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease-out, visibility 0.3s ease-out;
    }
    .modal-overlay.visible {
        opacity: 1;
        visibility: visible;
    }
    .modal-content {
        background: var(--bg-white);
        padding: 0; /* Rimosso padding per far estendere header/footer */
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        width: 95%;
        max-width: 450px;
        position: relative;
        animation: scaleInPopup 0.4s ease;
        max-height: 90vh;
        overflow: hidden; /* Nasconde lo scroll del contenitore principale */
        display: flex;
        flex-direction: column;
    }
     .modal-body {
        overflow-y: auto; /* Abilita lo scroll solo per il corpo del modale */
    }

    .add-client-icon {
        font-size: 1.5em;
        color: var(--brand-color);
        cursor: pointer;
        transition: color 0.2s ease, transform 0.2s ease;
        padding: 8px;
        border-radius: 50%;
        background-color: #e6ffed;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
    }

    .add-client-icon:hover {
        color: var(--brand-dark);
        transform: scale(1.1);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
    }

    .tab-buttons {
        display: flex;
        justify-content: center;
        margin-bottom: 1.2rem;
        gap: 6px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 5px;
    }

    .tab-button {
        background-color: #e9ecef;
        border: 1px solid #dee2e6;
        padding: 8px 12px;
        border-radius: 6px 6px 0 0;
        cursor: pointer;
        font-size: 0.85em;
        font-weight: 600;
        color: #495057;
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        white-space: nowrap;
    }

    .tab-button.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
        border-bottom-color: transparent;
    }

    .tab-button:hover:not(.active) {
        background-color: #e2f0ff;
        color: #0056b3;
    }

    .tab-content {
        display: none;
        padding-top: 0.8rem;
        animation: fadeIn 0.3s ease-out;
    }

    .tab-content.active {
        display: block;
    }

    .product-input-wrapper, .client-input-wrapper {
        position: relative;
    }

    .autocomplete-list {
        position: absolute;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #cbd5e0;
        border-radius: 8px;
        background-color: white;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        z-index: 10;
        display: none;
        margin-top: 5px;
    }

    .product-suggestion-item, .client-suggestion-item {
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f0f4f8;
        transition: background-color 0.2s ease;
        font-size: 0.95em;
        color: #334155;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .product-suggestion-item:last-child, .client-suggestion-item:last-child {
        border-bottom: none;
    }

    .product-suggestion-item:hover, .client-suggestion-item:hover {
        background-color: #e2f0ff;
    }

    .product-suggestion-item .model-name, .client-suggestion-item .client-name {
        font-weight: 600;
        color: #007bff;
    }

    .product-suggestion-item .imei-info, .client-suggestion-item .phone-info {
        font-size: 0.8em;
        color: #6c757d;
        margin-left: 10px;
        white-space: nowrap;
    }

    #permutaPopup fieldset {
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        background-color: var(--bg-white);
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        gap: 1.5rem;
    }

    #permutaPopup legend {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--brand-color);
        padding: 0 0.8rem;
        margin-bottom: 0.5rem;
        background-color: var(--bg-white);
        border-radius: 5px;
    }

    #permutaPopup .wizard-body > .form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    #permutaPopup fieldset > .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
    }

    @media (min-width: 768px) {
        #permutaPopup fieldset {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.8rem;
        }

        #permutaPopup .wizard-body > .form-grid fieldset {
            grid-column: 1 / -1;
        }
        
        #permutaPopup fieldset > .form-group,
        #permutaPopup fieldset > h4,
        #permutaPopup fieldset > .table-responsive,
        #permutaPopup fieldset > #costi_ricondizionamento_container_permuta,
        #permutaPopup fieldset > #add_costo_btn_permuta,
        #permutaPopup fieldset > .summary-line {
            grid-column: 1 / -1;
        }
    }

    #permutaPopup .table-responsive {
        width: 100%;
        overflow-x: auto;
        margin-top: 1rem;
        margin-bottom: 2rem;
    }

    #permutaPopup table {
        width: 100%;
        min-width: 600px;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    #permutaPopup table th,
    #permutaPopup table td {
        border: 1px solid #e0e6eb;
        padding: 0.8rem;
        text-align: left;
        vertical-align: top;
    }

    #permutaPopup table th {
        background-color: var(--brand-color);
        color: white;
        font-weight: 600;
        white-space: nowrap;
    }

    #permutaPopup table tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    #permutaPopup table select,
    #permutaPopup table input[type="text"] {
        width: 100%;
        padding: 0.5rem;
        border-radius: 5px;
        border: 1px solid #dcdfe6;
        font-size: 0.9rem;
        box-sizing: border-box;
    }

    #permutaPopup table input[type="checkbox"] {
        width: auto;
        margin-right: 0.5rem;
    }

    #costi_ricondizionamento_container_permuta {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-top: 1rem;
        margin-bottom: 1.5rem;
    }

    .costo-item {
        display: flex;
        gap: 0.8rem;
        align-items: center;
    }

    .costo-item input[type="text"] { flex-grow: 2; }
    .costo-item input[type="number"] { flex-grow: 1; max-width: 120px; }

    .remove-costo-btn {
        background-color: #dc3545; color: white; border: none; padding: 0.5rem 1rem;
        border-radius: 8px; cursor: pointer; font-size: 0.9rem; transition: background-color 0.2s ease;
    }
    .remove-costo-btn:hover { background-color: #c82333; }

    #add_costo_btn_permuta {
        background-color: #007bff; color: white; border: none; padding: 0.8rem 1.5rem;
        border-radius: 8px; cursor: pointer; font-size: 1rem; margin-top: 1rem;
        transition: background-color 0.2s ease;
    }
    #add_costo_btn_permuta:hover { background-color: #0056b3; }

    .summary-line {
        display: flex; justify-content: space-between; padding: 0.8rem 0;
        border-bottom: 1px dashed #e2e8f0; font-size: 1.05rem; font-weight: 500; color: var(--text-dark);
    }
    .summary-line:last-of-type { border-bottom: none; }
    .summary-line label { font-weight: 600; color: #475569; }
    .summary-line.highlight {
        background-color: #e6ffed; border-radius: 5px; padding: 0.8rem; margin-top: 1rem;
    }
    .summary-line.total {
        font-size: 1.2rem; font-weight: 700; color: #1e8449;
        border-top: 2px solid var(--brand-color); padding-top: 1.2rem; margin-top: 1.5rem;
    }
    
    .form-actions {
        display: flex; justify-content: flex-end; gap: 1rem; padding-top: 1.5rem;
        border-top: 1px solid var(--border-color); background-color: #fdfdfd; flex-shrink: 0;
    }

    .form-actions button {
        padding: 0.8rem 2.2rem; font-size: 1.05rem; font-weight: 600; border-radius: 10px;
        border: none; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    }

    .form-actions button[type="submit"] {
        background-color: var(--brand-color);
        color: white;
    }
    .form-actions button[type="submit"]:hover {
        background-color: var(--brand-dark);
        transform: translateY(-2px);
        box-shadow: 0 5px 12px rgba(0,0,0,0.15);
    }
    .form-actions button#annulla_permuta_btn { background-color: #e0e6eb; color: var(--text-dark); }
    .form-actions button#annulla_permuta_btn:hover { background-color: #d1d9e0; }
    .form-actions button#stampa_riepilogo_btn_permuta { background-color: #17a2b8; color: white; }
    .form-actions button#stampa_riepilogo_btn_permuta:hover { background-color: #138496; }

    .image-preview {
        display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;
    }
    .image-preview img {
        width: 80px; height: 80px; object-fit: cover; border-radius: 8px;
        border: 1px solid var(--border-color); box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .message-box {
        position: fixed; top: 1rem; left: 50%;
        transform: translateX(-50%) translateY(-20px);
        background-color: #4CAF50; color: white;
        padding: 1.25rem 1.75rem; border-radius: 0.75rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        z-index: 2500; font-size: 1.1rem; font-weight: bold;
        border: 2px solid white; opacity: 0; visibility: hidden;
        transition: opacity 0.5s ease-out, transform 0.5s ease-out;
        text-align: center;
    }
    .message-box.error { background-color: #f44336; border-color: #ff9999; }
    .message-box.show { opacity: 1; visibility: visible; transform: translateX(-50%) translateY(0); }

  </style>
</head>
<body>

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

  <nav>
    <ul>
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
        <div class="wizard-header">
            <h1>Nuova Scheda di Riparazione</h1>
            <button type="button" class="close-btn" id="close-riparazione-popup-btn">&times;</button>
        </div>

        <div class="stepper-nav">
            <div class="step active" data-step="1"><div class="step-icon">1</div><div class="step-label">Cliente</div></div>
            <div class="step" data-step="2"><div class="step-icon">2</div><div class="step-label">Dispositivo</div></div>
            <div class="step" data-step="3"><div class="step-icon">3</div><div class="step-label">Sblocco</div></div>
            <div class="step" data-step="4"><div class="step-icon">4</div><div class="step-label">Laboratorio</div></div>
        </div>
        
        <form method="POST" action="" id="riparazione-form">
            <input type="hidden" name="form_type" value="riparazione">
            <div class="wizard-body">
                <?php if(!empty($feedback_message)) echo $feedback_message; ?>
                <!-- Step 1: Cliente -->
                <div class="step-pane active" data-step="1">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="cliente_riparazione_autocomplete">Seleziona Cliente *</label>
                            <div class="client-input-container">
                                <input type="text" id="cliente_riparazione_autocomplete" name="cliente_display_riparazione" placeholder="Cerca o seleziona cliente" autocomplete="off" required>
                                <input type="hidden" id="cliente_id_riparazione" name="cliente_id">
                                <div id="cliente_suggestions_riparazione" class="autocomplete-list"></div>
                                <i class="fas fa-plus-circle add-client-icon" id="open_new_client_modal_btn_riparazione" title="Aggiungi nuovo cliente"></i>
                            </div>
                        </div>
                        <div class="form-group full-width">
                            <label for="telefono_riparazione_display">Telefono</label>
                            <input type="text" id="telefono_riparazione_display" name="telefono_display" readonly placeholder="Il telefono apparirà qui...">
                        </div>
                    </div>
                </div>

                <!-- Step 2: Dispositivo -->
                <div class="step-pane" data-step="2">
                    <div class="form-grid">
                        <div class="form-group"><label for="modello">Modello Dispositivo *</label><input type="text" id="modello" name="modello" required autocomplete="off"></div>
                        <div class="form-group"><label for="imei_riparazione">IMEI / Seriale</label><input type="text" id="imei_riparazione" name="imei" autocomplete="off"></div>
                        <div class="form-group full-width"><label for="diagnosi">Diagnosi / Problema riscontrato</label><textarea id="diagnosi" name="diagnosi" rows="4" required></textarea></div>
                    </div>
                </div>

                <!-- Step 3: Sblocco -->
                <div class="step-pane" data-step="3">
                     <div class="form-grid">
                        <div class="form-group">
                            <label for="codice_sblocco">Codice Sblocco (PIN / Password)</label><input type="text" id="codice_sblocco" name="codice_sblocco" autocomplete="off">
                            <label for="account" style="margin-top: 1.5rem;">Account collegati (Google, iCloud, etc.)</label><input type="text" id="account" name="account" autocomplete="off">
                        </div>
                        <div class="form-group" style="align-items:center;"><label>Codice Sblocco Grafico (Pattern)</label><div id="pattern-lock" class="pattern-lock"><canvas id="pattern-canvas" width="180" height="180"></canvas><div class="pattern-dot" data-dot="1"></div><div class="pattern-dot" data-dot="2"></div><div class="pattern-dot" data-dot="3"></div><div class="pattern-dot" data-dot="4"></div><div class="pattern-dot" data-dot="5"></div><div class="pattern-dot" data-dot="6"></div><div class="pattern-dot" data-dot="7"></div><div class="pattern-dot" data-dot="8"></div><div class="pattern-dot" data-dot="9"></div></div><input type="hidden" id="unlock-pattern" name="codice_sblocco_grafico" /></div>
                    </div>
                </div>
                
                <!-- Step 4: Laboratorio -->
                <div class="step-pane" data-step="4">
                    <div class="form-grid">
                        <div class="form-group"><label for="costo_preventivato">Costo Preventivato (€)</label><input type="number" id="costo_preventivato" name="costo_preventivato" step="0.01" min="0"></div>
                        <div class="form-group"><label for="costo_effettivo">Costo Effettivo (€)</label><input type="number" id="costo_effettivo" name="costo_effettivo" step="0.01" min="0"></div>
                        <div class="form-group full-width"><label for="hardware_ritirato">Hardware ritirato</label><input type="text" id="hardware_ritirato" name="hardware_ritirato"></div>
                        <div class="form-group"><label for="dispositivo_sostitutivo">Dispositivo sostitutivo</label><input type="text" id="dispositivo_sostitutivo" name="dispositivo_sostitutivo"></div>
                        <div class="form-group"><label for="stato">Stato lavorazione</label><select id="stato" name="stato"><option value="In attesa">In attesa</option><option value="In lavorazione">In lavorazione</option><option value="Completata">Completata</option><option value="In attesa di ricambi">In attesa di ricambi</option><option value="Non riparabile">Non riparabile</option><option value="Consegnata">Consegnata</option><option value="Annullata">Annullata</option></select></div>
                        <div class="form-group" style="flex-direction: row; align-items:center;"><input type="checkbox" id="salva_dati" name="salva_dati" value="1" style="width:auto;margin-right:10px;"><label for="salva_dati">Richiesto salvataggio dati</label></div>
                    </div>
                </div>
            </div>

            <div class="wizard-footer">
                <button type="button" class="wizard-btn prev" id="prev-btn" style="display: none;">Indietro</button>
                <button type="button" class="wizard-btn next" id="next-btn">Avanti</button>
                <button type="submit" class="wizard-btn submit" id="submit-riparazione-btn" style="display: none;">Salva Scheda</button>
            </div>
        </form>
    </div>
</div>

<!-- Nuovo popup per il Buono Regalo -->
<div class="popup-overlay" id="buonoRegaloPopup">
    <div class="popup-content">
        <div class="popup-header">
            <h2>Nuovo Buono Regalo</h2>
            <button type="button" class="close-btn" id="close-buono-regalo-popup-btn">&times;</button>
        </div>
        
        <form method="POST" action="" id="buono-regalo-form">
            <input type="hidden" name="form_type" value="buono_regalo">
            <div class="popup-body">
                <?php if(!empty($gift_card_feedback_message)) echo $gift_card_feedback_message; ?>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="buono_valore">Valore Buono (€) *</label>
                        <input type="number" id="buono_valore" name="valore" step="0.01" min="0" required>
                    </div>
                    <div class="form-group full-width">
                        <label for="buono_codice">Codice Buono *</label>
                        <div class="code-input-group">
                            <input type="text" id="buono_codice" name="codice_buono" readonly required placeholder="Generato automaticamente...">
                            <button type="button" id="copy-code-btn" class="copy-btn" title="Copia codice">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div id="copy-message" class="copy-message">Copiato!</div>
                    </div>
                    <div class="form-group">
                        <label for="buono_destinatario">Destinatario</label>
                        <input type="text" id="buono_destinatario" name="destinatario" placeholder="Nome del destinatario (opzionale)">
                    </div>
                    <div class="form-group">
                        <label for="buono_data_scadenza">Data Scadenza</label>
                        <input type="date" id="buono_data_scadenza" name="data_scadenza">
                    </div>
                    <div class="form-group full-width">
                        <label for="buono_mittente_note">Mittente / Note</label>
                        <textarea id="buono_mittente_note" name="mittente_note" rows="3" placeholder="Chi ha fatto il regalo o note aggiuntive..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="buono_stato">Stato</label>
                        <select id="buono_stato" name="stato_buono">
                            <option value="Attivo">Attivo</option>
                            <option value="Usato">Usato</option>
                            <option value="Scaduto">Scaduto</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="popup-footer">
                <button type="submit" class="popup-btn submit">Crea Buono</button>
            </div>
        </form>
    </div>
</div>

<!-- Nuovo popup per la Gestione Permuta -->
<div class="popup-overlay" id="permutaPopup">
    <div class="wizard-container">
        <div class="wizard-header">
            <h1>Gestione Nuova Permuta</h1>
            <button type="button" class="close-btn" id="close-permuta-popup-btn">&times;</button>
        </div>
        
        <form method="POST" action="" id="permuta-form" enctype="multipart/form-data">
            <input type="hidden" name="form_type" value="permuta">
            <div class="wizard-body">
                <?php if(!empty($permuta_feedback_message)) echo $permuta_feedback_message; ?>

                <div class="form-grid">
                    <!-- 1. Dettagli Generali della Permuta -->
                    <fieldset>
                        <legend>1. Dettagli Generali</legend>
                        <div class="form-group">
                            <label for="numero_permuta">Numero Permuta:</label>
                            <input type="text" id="numero_permuta" name="numero_permuta_display" value="Automatico al Salvataggio" readonly>
                            <input type="hidden" id="numero_progressivo" name="numero_progressivo">
                        </div>
                        <div class="form-group">
                            <label for="data_permuta">Data Permuta:</label>
                            <input type="date" id="data_permuta" name="data_permuta" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="cliente_permuta">Cliente:</label>
                            <div class="client-input-container">
                                <input type="text" id="cliente_permuta" name="cliente_display" placeholder="Cerca o seleziona cliente" autocomplete="off" required>
                                <input type="hidden" id="cliente_id_permuta" name="cliente_id">
                                <div id="client_suggestions_permuta" class="autocomplete-list"></div>
                                <i class="fas fa-plus-circle add-client-icon" id="open_new_client_modal_btn_permuta" title="Aggiungi nuovo cliente"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="telefono_cliente_permuta">Telefono Cliente:</label>
                            <input type="text" id="telefono_cliente_permuta" name="telefono_cliente" placeholder="Es: 3331234567" pattern="[0-9]{10,15}" title="Inserisci un numero di telefono valido (10-15 cifre)">
                        </div>
                        <div class="form-group">
                            <label for="stato_permuta">Stato Permuta:</label>
                            <select id="stato_permuta" name="stato_permuta" required>
                                <option value="In Trattativa">In Trattativa</option>
                                <option value="Accettata">Accettata</option>
                                <option value="Rifiutata">Rifiutata</option>
                                <option value="Completata">Completata</option>
                                <option value="Annullata">Annullata</option>
                            </select>
                        </div>
                    </fieldset>

                    <!-- 2. Il Tuo Prodotto (Ceduto al Cliente) -->
                    <fieldset>
                        <legend>2. Prodotto Ceduto</legend>
                        <div class="form-group">
                            <label for="tuo_modello_permuta">Modello:</label>
                            <div class="product-input-wrapper">
                                <input type="text" id="tuo_modello_permuta" name="tuo_modello" placeholder="Cerca o inserisci modello" autocomplete="off" required>
                                <div id="product_suggestions_permuta" class="autocomplete-list"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="tuo_imei_permuta">IMEI / Seriale:</label>
                            <input type="text" id="tuo_imei_permuta" name="tuo_imei">
                        </div>
                        <div class="form-group">
                            <label for="tuo_valore_vendita_permuta">Valore di Vendita (€):</label>
                            <input type="number" id="tuo_valore_vendita_permuta" name="tuo_valore_vendita" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="tuo_note_permuta">Note Prodotto Ceduto:</label>
                            <textarea id="tuo_note_permuta" name="tuo_note" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="tuo_foto_permuta">Allegati / Foto:</label>
                            <input type="file" id="tuo_foto_permuta" name="tuo_foto[]" multiple accept="image/*">
                            <div id="tuo_foto_preview_permuta" class="image-preview"></div>
                        </div>
                    </fieldset>

                    <!-- 3. Prodotto del Cliente (Ricevuto in Permuta) -->
                    <fieldset>
                        <legend>3. Prodotto Ricevuto</legend>
                        <div class="form-group">
                            <label for="cliente_modello_permuta">Modello:</label>
                            <input type="text" id="cliente_modello_permuta" name="cliente_modello" required>
                        </div>
                        <div class="form-group">
                            <label for="cliente_imei_permuta">IMEI / Seriale:</label>
                            <input type="text" id="cliente_imei_permuta" name="cliente_imei">
                        </div>
                        <div class="form-group">
                            <label for="cliente_note_permuta">Note Generali Prodotto Ricevuto:</label>
                            <textarea id="cliente_note_permuta" name="cliente_note" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="cliente_valore_permuta_main">Valore Permuta Proposto (€):</label>
                            <input type="number" id="cliente_valore_permuta_main" name="cliente_valore_permuta" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="cliente_foto_permuta">Allegati / Foto:</label>
                            <input type="file" id="cliente_foto_permuta" name="cliente_foto[]" multiple accept="image/*">
                            <div id="cliente_foto_preview_permuta" class="image-preview"></div>
                        </div>

                        <h4>Valutazione Tecnica</h4>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Componente</th>
                                        <th>Esito</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Display</td>
                                        <td>
                                            <select name="test_display_esito">
                                                <option value="Funzionante">Funzionante</option>
                                                <option value="Danneggiato">Danneggiato</option>
                                                <option value="Guasto">Guasto</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="test_display_note"></td>
                                    </tr>
                                    <td>Touchscreen</td>
                                    <td>
                                        <select name="test_touch_esito">
                                            <option value="Funzionante">Funzionante</option>
                                            <option value="Parzialmente Funzionante">Parzialmente Funzionante</option>
                                            <option value="Non Funzionante">Non Funzionante</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="test_touch_note"></td>
                                </tr>
                                <tr>
                                    <td>Batteria</td>
                                    <td>
                                        <select name="test_batteria_esito">
                                            <option value="Ottima">Ottima</option>
                                            <option value="Buona">Buona</option>
                                            <option value="Scarso">Scarso</option>
                                            <option value="Da Sostituire">Da Sostituire</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="test_batteria_note" placeholder="% salute, cicli"></td>
                                </tr>
                                <tr>
                                    <td>Fotocamera Post.</td>
                                    <td>
                                        <select name="test_cam_post_esito">
                                            <option value="Funzionante">Funzionante</option>
                                            <option value="Difettosa">Difettosa</option>
                                            <option value="Non Funzionante">Non Funzionante</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="test_cam_post_note"></td>
                                </tr>
                                <tr>
                                    <td>Fotocamera Ant.</td>
                                    <td>
                                        <select name="test_cam_ant_esito">
                                            <option value="Funzionante">Funzionante</option>
                                            <option value="Difettosa">Difettosa</option>
                                            <option value="Non Funzionante">Non Funzionante</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="test_cam_ant_note"></td>
                                </tr>
                                <tr>
                                    <td>Audio</td>
                                    <td>
                                        <select name="test_audio_esito">
                                            <option value="Funzionante">Funzionante</option>
                                            <option value="Distorto">Distorto</option>
                                            <option value="Non Funzionante">Non Funzionante</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="test_audio_note"></td>
                                </tr>
                                <tr>
                                    <td>Microfono</td>
                                    <td>
                                        <select name="test_mic_esito">
                                            <option value="Funzionante">Funzionante</option>
                                            <option value="Difettoso">Difettoso</option>
                                            <option value="Non Funzionante">Non Funzionante</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="test_mic_note"></td>
                                </tr>
                                 <tr>
                                    <td>Wi-Fi</td>
                                    <td>
                                        <select name="test_wifi_esito">
                                            <option value="Funzionante">Funzionante</option>
                                            <option value="Instabile">Instabile</option>
                                            <option value="Non Funzionante">Non Funzionante</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="test_wifi_note"></td>
                                </tr>
                                <tr>
                                    <td>Bluetooth</td>
                                    <td>
                                        <select name="test_bt_esito">
                                            <option value="Funzionante">Funzionante</option>
                                            <option value="Instabile">Instabile</option>
                                            <option value="Non Funzionante">Non Funzionante</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="test_bt_note"></td>
                                </tr>
                                <tr>
                                    <td>Ricarica</td>
                                    <td>
                                        <select name="test_ricarica_esito">
                                            <option value="Funzionante">Funzionante</option>
                                            <option value="Difettosa">Difettosa</option>
                                            <option value="Non Funzionante">Non Funzionante</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="test_ricarica_note"></td>
                                </tr>
                                <tr>
                                    <td>Tasti Fisici</td>
                                    <td>
                                        <select name="test_tasti_esito">
                                            <option value="Funzionante">Funzionante</option>
                                            <option value="Bloccati">Bloccati</
                                            <option value="Non Funzionante">Non Funzionante</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="test_tasti_note"></td>
                                </tr>
                                <tr>
                                    <td>Sensori</td>
                                    <td>
                                        <select name="test_sensori_esito">
                                            <option value="Funzionante">Funzionante</option>
                                            <option value="Parzialmente Funzionante">Parzialmente Funzionante</option>
                                            <option value="Non Funzionante">Non Funzionante</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="test_sensori_note"></td>
                                </tr>
                                <tr>
                                    <td>Sblocco Biometrico</td>
                                    <td>
                                        <select name="test_sblocco_bio_esito">
                                            <option value="Funzionante">Funzionante</option>
                                            <option value="Non Funzionante">Non Funzionante</option>
                                            <option value="Non Applicabile">Non Applicabile</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="test_sblocco_bio_note"></td>
                                </tr>
                                 <tr>
                                    <td>Reset Fabbrica</td>
                                    <td>
                                        <input type="checkbox" name="test_reset_fabbrica" value="Si"> Sì
                                    </td>
                                    <td><input type="text" name="test_reset_fabbrica_note"></td>
                                </tr>
                                 <tr>
                                    <td>Account</td>
                                    <td>
                                        <select name="test_accounts_esito">
                                            <option value="Liberi">Liberi</option>
                                            <option value="Presenti">Presenti</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="test_accounts_note" placeholder="Es: Google, iCloud..."></td>
                                </tr>
                                 <tr>
                                    <td>Altro</td>
                                    <td>
                                        <select name="test_altro_esito">
                                            <option value="N/A">N/A</option>
                                            <option value="Funzionante">Funzionante</option>
                                            <option value="Difettoso">Difettoso</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="test_altro_note"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </fieldset>

                <!-- 4. Calcoli e Conguaglio Finale -->
                <fieldset>
                    <legend>4. Calcoli Finali</legend>
                    <h4>Costi di Ricondizionamento</h4>
                    <div id="costi_ricondizionamento_container_permuta">
                        <div class="costo-item">
                            <input type="text" name="costo_descrizione[]" placeholder="Descrizione Costo">
                            <input type="number" name="costo_importo[]" step="0.01" min="0" class="costo-importo" value="0">
                            <button type="button" class="remove-costo-btn">Rimuovi</button>
                        </div>
                    </div>
                    <button type="button" id="add_costo_btn_permuta">Aggiungi Costo</button>

                    <div class="summary-line">
                        <label>Totale Costi Ricondizionamento:</label>
                        <span id="totale_costi_ricondizionamento_permuta">€ 0.00</span>
                        <input type="hidden" id="totale_costi_ricondizionamento_val_permuta" name="totale_costi_ricondizionamento_val">
                    </div>

                    <div class="form-group">
                        <label for="costo_accessori_input_permuta">Costo Accessori (€):</label>
                        <input type="number" id="costo_accessori_input_permuta" name="costo_accessori_input" step="0.01" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="costo_prodotto_input_permuta">Costo Prodotto (€):</label>
                        <input type="number" id="costo_prodotto_input_permuta" name="costo_prodotto_input" step="0.01" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="prezzo_vendita_input_permuta">Prezzo Vendita Finale (€):</label>
                        <input type="number" id="prezzo_vendita_input_permuta" name="prezzo_vendita_input" step="0.01" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="note_generali_input_permuta">Note Aggiuntive:</label>
                        <textarea id="note_generali_input_permuta" name="note_generali_input" rows="3" placeholder="Aggiungi qui eventuali note generali..."></textarea>
                    </div>
                    
                    <div class="summary-line">
                        <label>Valore Vendita Prodotto Ceduto:</label>
                        <span id="valore_vendita_ceduto_permuta">€ 0.00</span>
                    </div>
                    <div class="summary-line">
                        <label>Valore Permuta (Iniziale):</label>
                        <span id="valore_permuta_ricevuto_permuta">€ 0.00</span>
                    </div>
                    <div class="summary-line highlight">
                        <label>Valore Netto Prodotto Ricevuto:</label>
                        <span id="valore_netto_ricevuto_permuta">€ 0.00</span>
                        <input type="hidden" id="valore_netto_ricevuto_val_permuta" name="valore_netto_ricevuto_val">
                    </div>
                    <div class="summary-line total">
                        <label>Conguaglio Cliente:</label>
                        <span id="conguaglio_cliente_permuta">€ 0.00</span>
                        <input type="hidden" id="conguaglio_cliente_val_permuta" name="conguaglio_cliente_val">
                    </div>
                </fieldset>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="salva_permuta">Salva Permuta</button>
                <button type="button" id="annulla_permuta_btn">Annulla</button>
                <button type="button" id="stampa_riepilogo_btn_permuta">Stampa Riepilogo</button>
            </div>
        </form>
    </div>
</div>

<!-- Nuovo popup per la Prenotazione Prodotto -->
<div class="popup-overlay" id="prenotazioneProdottoPopup">
    <div class="popup-content">
        <div class="popup-header">
            <h2>Nuova Prenotazione Prodotto</h2>
            <button type="button" class="close-btn" id="close-prenotazione-prodotto-popup-btn">&times;</button>
        </div>
        
        <form action="" method="POST" id="prenotazione-prodotto-form">
            <input type="hidden" name="form_type" value="prenotazione_prodotto">
            <div class="popup-body">
                <?php if(!empty($prenotazione_feedback_message)) echo $prenotazione_feedback_message; ?>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="productName_pp">Nome Prodotto da Ordinare *</label>
                        <input type="text" id="productName_pp" name="productName" placeholder="Es. Smartphone Modello X" required>
                        <input type="hidden" id="productId_pp" name="productId" value="">
                    </div>

                    <div class="form-group">
                        <label for="unitPrice_pp">Prezzo Unitario (€) *</label>
                        <input type="number" step="0.01" id="unitPrice_pp" name="unitPrice" min="0" value="0.00" required>
                    </div>

                    <div class="form-group">
                        <label for="quantity_pp">Quantità *</label>
                        <input type="number" id="quantity_pp" name="quantity" min="1" value="1" required>
                    </div>

                    <div class="form-group">
                        <label for="productTotalPriceDisplay_pp">Prezzo Totale</label>
                        <input type="text" id="productTotalPriceDisplay_pp" value="€ 0,00" readonly>
                        <input type="hidden" id="productTotalPrice_pp" name="productTotalPrice">
                    </div>

                    <div class="form-group">
                        <label for="depositAmount_pp">Acconto Versato</label>
                        <input type="number" step="0.01" id="depositAmount_pp" name="depositAmount" min="0" value="0.00">
                    </div>

                    <div class="form-group">
                        <label for="remainingAmountDisplay_pp">Totale da Dare</label>
                        <input type="text" id="remainingAmountDisplay_pp" value="€ 0,00" readonly>
                        <input type="hidden" id="remainingAmount_pp" name="remainingAmount">
                    </div>

                    <div class="form-group">
                        <label for="reservationDate_pp">Data Prenotazione *</label>
                        <input type="date" id="reservationDate_pp" name="reservationDate" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="customerName_pp">Cliente *</label>
                        <div class="client-input-container">
                            <input type="text" id="customerName_pp" name="customerName" placeholder="Cerca o aggiungi cliente" autocomplete="off" required>
                            <input type="hidden" id="clientId_pp" name="clientId">
                            <div id="clientAutocompleteList_pp" class="autocomplete-list"></div>
                            <i class="fas fa-plus-circle add-client-icon" id="addClientBtn_pp" title="Aggiungi nuovo cliente"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="customerPhone_pp">Tel. Principale</label>
                        <input type="tel" id="customerPhone_pp" name="customerPhone" placeholder="Es. +39 123 4567890">
                    </div>

                    <div class="form-group">
                        <label for="customerSecondaryPhone_pp">Tel. Secondario</label>
                        <input type="tel" id="customerSecondaryPhone_pp" name="customerSecondaryPhone">
                    </div>

                    <div class="form-group">
                        <label for="customerEmail_pp">Email</label>
                        <input type="email" id="customerEmail_pp" name="customerEmail" placeholder="Es. nome@esempio.com">
                    </div>

                    <div class="form-group full-width">
                        <label for="notes_pp">Note</label>
                        <textarea id="notes_pp" name="notes" rows="3" placeholder="Aggiungi note sulla prenotazione..."></textarea>
                    </div>
                </div>
            </div>

            <div class="popup-footer">
                <button type="button" id="cancelReservationBtn_pp" class="popup-btn prev">Annulla</button>
                <button type="submit" class="popup-btn submit">Salva Prenotazione</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal per l'aggiunta di un nuovo cliente -->
<div id="new_client_modal_overlay" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Aggiungi Nuovo Cliente</h3>
            <button type="button" class="close-button" id="close_new_client_modal_btn">&times;</button>
        </div>
        <div class="modal-body">
            <div class="tab-buttons">
                <button type="button" class="tab-button active" data-tab="personal_data_tab">Dati Personali</button>
                <button type="button" class="tab-button" data-tab="company_data_tab">Dati Aziendali</button>
            </div>

            <div id="personal_data_tab" class="tab-content active">
                <div class="form-group">
                    <label for="modal_nuovo_cliente_nome">Nome:</label>
                    <input type="text" id="modal_nuovo_cliente_nome" placeholder="Nome" required>
                </div>
                <div class="form-group">
                    <label for="modal_nuovo_cliente_cognome">Cognome:</label>
                    <input type="text" id="modal_nuovo_cliente_cognome" placeholder="Cognome" required>
                </div>
                <div class="form-group">
                    <label for="modal_nuovo_cliente_telefono">Telefono:</label>
                    <input type="text" id="modal_nuovo_cliente_telefono" placeholder="Es: 3331234567" pattern="[0-9]{10,15}">
                </div>
                <div class="form-group">
                    <label for="modal_nuovo_cliente_email">Email:</label>
                    <input type="email" id="modal_nuovo_cliente_email" placeholder="nome@esempio.com">
                </div>
                <div class="form-group">
                    <label for="modal_nuovo_cliente_indirizzo">Indirizzo:</label>
                    <input type="text" id="modal_nuovo_cliente_indirizzo" placeholder="Via Roma, 1">
                </div>
                <div class="form-group">
                    <label for="modal_nuovo_cliente_citta">Città:</label>
                    <input type="text" id="modal_nuovo_cliente_citta" placeholder="Roma">
                </div>
                <div class="form-group">
                    <label for="modal_nuovo_cliente_note">Note:</label>
                    <textarea id="modal_nuovo_cliente_note" rows="3" placeholder="Note aggiuntive sul cliente"></textarea>
                </div>
            </div>

            <div id="company_data_tab" class="tab-content">
                <div class="form-group">
                    <label for="modal_nuovo_cliente_ragione_sociale">Ragione Sociale:</label>
                    <input type="text" id="modal_nuovo_cliente_ragione_sociale" placeholder="Nome S.p.A.">
                </div>
                <div class="form-group">
                    <label for="modal_nuovo_cliente_partita_iva">Partita IVA:</label>
                    <input type="text" id="modal_nuovo_cliente_partita_iva" placeholder="IT12345678901">
                </div>
                <div class="form-group">
                    <label for="modal_nuovo_cliente_indirizzo_azienda">Indirizzo Azienda:</label>
                    <input type="text" id="modal_nuovo_cliente_indirizzo_azienda" placeholder="Via dell'Industria, 5">
                </div>
                <div class="form-group">
                    <label for="modal_nuovo_cliente_citta_azienda">Città Azienda:</label>
                    <input type="text" id="modal_nuovo_cliente_citta_azienda" placeholder="Milano">
                </div>
                <div class="form-group">
                    <label for="modal_nuovo_cliente_telefono_azienda">Telefono Azienda:</label>
                    <input type="text" id="modal_nuovo_cliente_telefono_azienda" placeholder="Es: 0212345678" pattern="[0-9]{10,15}">
                </div>
                <div class="form-group">
                    <label for="modal_nuovo_cliente_email_azienda">Email Azienda:</label>
                    <input type="email" id="modal_nuovo_cliente_email_azienda" placeholder="info@azienda.com">
                </div>
                <div class="form-group">
                    <label for="modal_nuovo_cliente_note_azienda">Note Azienda:</label>
                    <textarea id="modal_nuovo_cliente_note_azienda" rows="3" placeholder="Note aggiuntive sull'azienda"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="cancel-button" id="cancel_new_client_modal_btn">Annulla</button>
            <button type="button" class="save-button" id="save_new_client_btn">Salva Cliente</button>
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
        }

        function showTab(tabId) {
            tabContents.forEach(c => c.classList.remove('active'));
            tabButtons.forEach(b => b.classList.remove('active'));
            modalOverlay.querySelector(`#${tabId}`).classList.add('active');
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
        const openBtn = document.getElementById(openBtnId);
        const closeBtn = popup.querySelector('.close-btn');

        const openPopup = () => {
            popup.style.display = 'flex';
            setTimeout(() => popup.classList.add('visible'), 10);
        };
        const closePopup = () => {
            popup.classList.remove('visible');
            popup.addEventListener('transitionend', () => popup.style.display = 'none', { once: true });
        };

        if (openBtn) openBtn.addEventListener('click', openPopup);
        if (closeBtn) closeBtn.addEventListener('click', closePopup);
        popup.addEventListener('click', (e) => {
            if (e.target === popup) closePopup();
        });
        
        // Gestione dei pulsanti "Aggiungi Cliente" all'interno dei popup
        const addClientBtn = popup.querySelector('.add-client-icon');
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
        let currentStep = 1;

        window.showRiparazioneStep = (stepNumber) => {
            currentStep = stepNumber;
            steps.forEach(s => s.classList.toggle('active', parseInt(s.dataset.step) === currentStep));
            navSteps.forEach(ns => {
                const stepNum = parseInt(ns.dataset.step);
                ns.classList.toggle('active', stepNum === currentStep);
                ns.classList.toggle('completed', stepNum < currentStep);
            });
            prevBtn.style.display = currentStep > 1 ? 'inline-block' : 'none';
            nextBtn.style.display = currentStep < steps.length ? 'inline-block' : 'none';
            submitBtn.style.display = currentStep === steps.length ? 'inline-block' : 'none';
        };

        document.getElementById('openNuovaAssistenzaPopupBtn').addEventListener('click', () => {
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
                setTimeout(riparazionePopup.closePopup, 1000);
            <?php endif; ?>
        <?php endif; ?>
    })();

    // --- Logica Specifica Autocomplete ---
    setupAutocomplete(
        document.getElementById('cliente_riparazione_autocomplete'),
        document.getElementById('cliente_suggestions_riparazione'),
        globalClientsData, item => item.display_name,
        client => {
            document.getElementById('cliente_id_riparazione').value = client.id;
            document.getElementById('telefono_riparazione_display').value = client.telefono_principale || '';
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
        document.getElementById('openBuonoRegaloPopupBtn').addEventListener('click', () => {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < 12; i++) code += chars.charAt(Math.floor(Math.random() * chars.length));
            document.getElementById('buono_codice').value = code;
        });
        document.getElementById('copy-code-btn').addEventListener('click', () => {
            const input = document.getElementById('buono_codice');
            const tempTextarea = document.createElement('textarea');
            tempTextarea.value = input.value;
            document.body.appendChild(tempTextarea);
            tempTextarea.select();
            document.execCommand('copy');
            document.body.removeChild(tempTextarea);
            showMessage('Codice copiato!', false);
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
            permutaForm.addEventListener('input', () => {
                const tuoValore = parseFloat(permutaForm.tuo_valore_vendita.value) || 0;
                const clienteValore = parseFloat(permutaForm.cliente_valore_permuta.value) || 0;
                let totaleCosti = 0;
                permutaForm.querySelectorAll('.costo-importo').forEach(i => totaleCosti += parseFloat(i.value) || 0);
                const valoreNetto = clienteValore - totaleCosti;
                const conguaglio = tuoValore - valoreNetto;
                document.getElementById('valore_vendita_ceduto_permuta').textContent = formatCurrency(tuoValore);
                document.getElementById('valore_permuta_ricevuto_permuta').textContent = formatCurrency(clienteValore);
                document.getElementById('totale_costi_ricondizionamento_permuta').textContent = formatCurrency(totaleCosti);
                document.getElementById('valore_netto_ricevuto_permuta').textContent = formatCurrency(valoreNetto);
                document.getElementById('conguaglio_cliente_permuta').textContent = formatCurrency(conguaglio);
                permutaForm.totale_costi_ricondizionamento_val.value = totaleCosti.toFixed(2);
                permutaForm.valore_netto_ricevuto_val.value = valoreNetto.toFixed(2);
                permutaForm.conguaglio_cliente_val.value = conguaglio.toFixed(2);
            });
            document.getElementById('add_costo_btn_permuta').addEventListener('click', () => {
                const container = document.getElementById('costi_ricondizionamento_container_permuta');
                const newItem = document.createElement('div');
                newItem.className = 'costo-item';
                newItem.innerHTML = `<input type="text" name="costo_descrizione[]" placeholder="Descrizione Costo"><input type="number" name="costo_importo[]" step="0.01" min="0" class="costo-importo" value="0"><button type="button" class="remove-costo-btn">Rimuovi</button>`;
                container.appendChild(newItem);
            });
            permutaForm.addEventListener('click', e => {
                if (e.target.classList.contains('remove-costo-btn')) {
                    e.target.parentElement.remove();
                    permutaForm.dispatchEvent(new Event('input'));
                }
            });
        }
        if (prenotazioneForm) {
            prenotazioneForm.addEventListener('input', () => {
                const quantity = parseFloat(prenotazioneForm.quantity.value) || 0;
                const unitPrice = parseFloat(prenotazioneForm.unitPrice.value) || 0;
                const deposit = parseFloat(prenotazioneForm.depositAmount.value) || 0;
                const total = quantity * unitPrice;
                const remaining = total - deposit;
                document.getElementById('productTotalPriceDisplay_pp').value = formatCurrency(total);
                document.getElementById('productTotalPrice_pp').value = total.toFixed(2);
                document.getElementById('remainingAmountDisplay_pp').value = formatCurrency(remaining);
                document.getElementById('remainingAmount_pp').value = remaining.toFixed(2);
            });
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
        // ... (Logica Pattern Lock esistente, è corretta) ...
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
            if (!submenu) return;
            
            const rect = submenuParentLi.getBoundingClientRect();
            submenu.style.top = (rect.top) + 'px';
            submenu.style.left = (rect.right + 10) + 'px';
        };
        
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const parentLi = this.parentElement;
                const wasActive = parentLi.classList.contains('active');
                const parentMenu = this.closest('ul');
                parentMenu.querySelectorAll('.active').forEach(item => {
                    if (item !== parentLi) item.classList.remove('active');
                });
                if (!wasActive) {
                    parentLi.classList.add('active');
                    if (this.closest('nav > ul')) {
                        // È un dropdown principale
                        positionDropdown(this);
                    } else if (this.closest('ul.dropdown')) {
                        // È un submenu dentro un dropdown
                        positionSubmenu(parentLi);
                    }
                }
                else parentLi.classList.remove('active');
            });
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

        // --- NUOVO: Logica per Barra di Ricerca Globale ---
        const searchBar = document.querySelector('.search-bar');
        const searchResultsDropdown = document.getElementById('searchResultsDropdown');
        if (searchBar) {
            searchBar.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                if (query.length < 2) {
                    searchResultsDropdown.style.display = 'none';
                    return;
                }
                const filteredClients = globalClientsData.filter(c => c.display_name.toLowerCase().includes(query));
                const filteredProducts = globalProductsData.filter(p => p.name.toLowerCase().includes(query));
                searchResultsDropdown.innerHTML = '';
                if (filteredClients.length > 0) {
                    searchResultsDropdown.innerHTML += `<div class="result-category">Clienti</div>`;
                    filteredClients.forEach(c => {
                        searchResultsDropdown.innerHTML += `<a href="#" data-id="${c.id}" data-type="client">${c.display_name}</a>`;
                    });
                }
                if (filteredProducts.length > 0) {
                    searchResultsDropdown.innerHTML += `<div class="result-category">Prodotti</div>`;
                    filteredProducts.forEach(p => {
                        searchResultsDropdown.innerHTML += `<a href="#" data-id="${p.id}" data-type="product">${p.name}</a>`;
                    });
                }
                if (searchResultsDropdown.innerHTML === '') {
                    searchResultsDropdown.innerHTML = `<a href="#" class="no-results">Nessun risultato</a>`;
                }
                searchResultsDropdown.style.display = 'block';
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

        // Gestione Menu Utente
        const userMenuContainer = document.getElementById('userMenuContainer');
        if(userMenuContainer) userMenuContainer.addEventListener('click', e => {
            e.stopPropagation();
            userMenuContainer.classList.toggle('active');
        });
    });

</script>
</body>
</html>


