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
      --brand-color: #22c55e;
      --brand-dark: #16a34a;
      --brand-light: #dcfce7;
      --text-dark: #1e293b;
      --text-light: #64748b;
      --text-muted: #94a3b8;
      --border-color: #e2e8f0;
      --bg-light: #f8fafc;
      --bg-white: #ffffff;
      --success-color: #22c55e;
      --danger-color: #ef4444;
      --warning-color: #f59e0b;
      --info-color: #3b82f6;
      --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
      --shadow-md: 0 4px 15px rgba(0, 0, 0, 0.08);
      --shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.12);
      --shadow-glow: 0 0 30px rgba(34, 197, 94, 0.2);
    }
    
    html {
        height: 100%;
    }

    body {
      font-family: 'Poppins', sans-serif;
      color: var(--text-dark);
      margin: 0;
      background: var(--bg-light);
      padding: 0;
      padding-top: 88px;
      min-height: 100vh;
      overflow-y: auto;
      overflow-x: visible;
      transition: padding-top 0.3s ease;
    }
    
    /* Quando l'header è compatto */
    body.header-scrolled {
      padding-top: 80px;
    }

    /* --- INIZIO MODIFICHE HEADER --- */
    .top-bar {
      background: linear-gradient(135deg, var(--brand-color) 0%, #15803d 50%, var(--brand-dark) 100%);
      color: white;
      padding: 0 30px;
      height: 72px;
      width: 100vw;
      box-sizing: border-box;
      display: flex;
      align-items: center;
      gap: 24px;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1000;
      box-shadow: 0 4px 20px rgba(34, 197, 94, 0.25);
      transition: all 0.3s ease;
      overflow: visible !important;
      backdrop-filter: blur(10px);
    }
    
    /* Header scrolled - più compatto */
    .top-bar.scrolled {
      height: 64px;
      box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
    }

    .logo {
      font-size: 26px;
      font-weight: 700;
      white-space: nowrap;
      color: white;
      text-decoration: none;
      cursor: pointer;
      letter-spacing: -0.5px;
      transition: transform 0.2s ease;
    }
    .logo:hover {
      transform: scale(1.02);
    }
    
    .search-container {
        position: relative;
        display: flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 50px;
        padding: 8px 18px;
        width: 100%;
        max-width: 420px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .search-container:focus-within {
        background: rgba(255, 255, 255, 0.25);
        border-color: rgba(255, 255, 255, 0.4);
        box-shadow: 0 0 20px rgba(255, 255, 255, 0.15);
        transform: scale(1.02);
    }

    .search-container i {
        color: rgba(255, 255, 255, 0.9);
        margin-right: 12px;
        font-size: 14px;
    }

    .search-bar {
        background: transparent;
        border: none;
        color: white;
        font-size: 14px;
        font-weight: 500;
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
      background-color: transparent;
      border: none;
      color: white;
      font-size: 14px;
      font-weight: 500;
      padding: 10px 18px;
      cursor: pointer;
      border-radius: 10px;
      user-select: none;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      height: fit-content;
      position: relative;
    }

    nav ul li button:hover,
    nav ul li a:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }
    
    /* Indicatore di pagina attiva */
    nav ul li a.active-link {
        background: rgba(255, 255, 255, 0.25);
        font-weight: 600;
    }
    nav ul li a.active-link::after {
        content: '';
        position: absolute;
        bottom: 4px;
        left: 50%;
        transform: translateX(-50%);
        width: 20px;
        height: 3px;
        background: white;
        border-radius: 2px;
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
      background: var(--bg-white);
      min-width: 220px;
      width: max-content;
      max-width: 280px;
      border-radius: 14px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
      padding: 10px;
      list-style: none;
      z-index: 2000;
      transform-origin: top;
      animation: dropdownIn 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: visible;
      height: auto;
      box-sizing: border-box;
      border: 1px solid var(--border-color);
    }
    @keyframes dropdownIn { 
      from { opacity: 0; transform: translateY(-10px) scale(0.95); } 
      to { opacity: 1; transform: translateY(0) scale(1); } 
    }
    
    /* MODIFICA: Rimosso :hover per la visualizzazione, ora gestito da JS con la classe .active */
    nav ul li.active > ul.dropdown { 
        display: block; 
    }

    nav ul li ul.dropdown li a, nav ul li ul.dropdown li button {
      padding: 12px 16px; 
      color: var(--text-dark); 
      background-color: transparent;
      width: 100%; 
      text-align: left; 
      border-radius: 10px; 
      font-size: 14px;
      font-weight: 500;
      border: none;
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      margin-bottom: 2px;
    }
    nav ul li ul.dropdown li a:hover, nav ul li ul.dropdown li button:hover {
      background: var(--brand-light);
      color: var(--brand-dark);
    }
    nav ul li ul.dropdown li.active > a,
    nav ul li ul.dropdown li.active > button {
      background-color: var(--brand-color);
      color: white;
      font-weight: 600;
      padding-left: 25px;
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
            width: max-content;
            max-width: 260px;
      border-radius: 8px;
      box-shadow: var(--shadow-lg); 
      padding: 8px 0; 
      list-style: none;
      z-index: 2100;
      overflow: visible;
      height: auto;
            box-sizing: border-box;
      transition: opacity 0.2s ease, visibility 0.2s ease;
    }
    
    /* Stile per i link nel submenu */
    nav ul li ul.dropdown li ul.submenu li a {
      padding: 10px 20px;
      color: var(--text-dark);
      display: block;
      text-decoration: none;
      font-size: 14px;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    nav ul li ul.dropdown li ul.submenu li a:hover {
      background: var(--brand-light);
      color: var(--brand-dark);
    }
    
    /* MODIFICA: Rimosso :hover per la visualizzazione, ora gestito da JS con la classe .active */
    nav ul li ul.dropdown li.active > ul.submenu { 
        display: block;
    }

    /* Menu Utente Migliorato */
    .user-menu-container {
        position: relative;
        display: flex;
        align-items: center;
        gap: 14px;
        margin-left: 24px;
        padding-left: 24px;
        border-left: 1px solid rgba(255, 255, 255, 0.25);
        cursor: pointer;
        padding: 8px 0 8px 24px;
        transition: all 0.2s ease;
    }
    .user-menu-container:hover {
        border-left-color: rgba(255, 255, 255, 0.5);
    }
    .user-greeting {
        color: white;
        font-weight: 500;
        font-size: 14px;
        white-space: nowrap;
        line-height: 1.3;
    }
    .user-greeting .user-name {
        font-weight: 700;
        display: block;
    }
    .user-greeting .user-role {
        font-size: 11px;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .user-icon-trigger {
        width: 42px;
        height: 42px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .user-menu-container:hover .user-icon-trigger {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.05);
    }
    
    .user-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 12px);
        right: 0;
        background: var(--bg-white);
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
        min-width: 260px;
        border: 1px solid var(--border-color);
        padding: 20px;
        animation: fadeInDropdown 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
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
    .user-dropdown-info { 
        font-size: 14px; 
        color: var(--text-dark); 
        margin-bottom: 16px;
        padding-bottom: 16px; 
        border-bottom: 1px solid var(--border-color); 
        text-align: center; 
    }
    .user-dropdown-info strong { 
        font-weight: 700; 
        display: block; 
        margin-bottom: 4px; 
        font-size: 16px;
        color: var(--text-dark);
    }
    .user-dropdown-info span { 
        display: inline-block; 
        color: var(--brand-color); 
        font-size: 12px;
        font-weight: 600;
        background: var(--brand-light);
        padding: 4px 12px;
        border-radius: 20px;
        margin-top: 8px;
    }
    .user-dropdown .logout-button { 
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%; 
        text-align: center;
        background: linear-gradient(135deg, var(--brand-color) 0%, var(--brand-dark) 100%); 
        color: white; 
        padding: 12px 16px;
        border-radius: 12px; 
        text-decoration: none; 
        font-size: 14px;
        font-weight: 600;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
    }
    .user-dropdown .logout-button:hover { 
        transform: translateY(-2px); 
        box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
    }

    @media (max-width: 1200px) {
        .user-greeting { display: none; } /* Nasconde il saluto su schermi più piccoli */
        .search-container { max-width: 250px; }
        .top-bar { gap: 15px; padding: 0 20px; }
    }
    
    @media (max-width: 992px) {
        .top-bar nav { display: none; } /* Nasconde la navigazione principale */
        .search-container { margin-left: auto; } /* Sposta la ricerca a destra */
        .user-menu-container { margin-left: 15px; padding-left: 15px; }
        
        /* Hamburger button */
        .hamburger-btn {
            display: flex !important;
            flex-direction: column;
            gap: 5px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
            margin-left: 10px;
        }
        .hamburger-btn span {
            width: 25px;
            height: 3px;
            background-color: white;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        .hamburger-btn.active span:nth-child(1) { transform: rotate(45deg) translate(8px, 8px); }
        .hamburger-btn.active span:nth-child(2) { opacity: 0; }
        .hamburger-btn.active span:nth-child(3) { transform: rotate(-45deg) translate(7px, -7px); }
        
        /* Menu mobile */
        nav.mobile-nav {
            display: flex !important;
            position: fixed;
            top: 80px;
            left: 0;
            width: 100%;
            background-color: var(--brand-color);
            flex-direction: column;
            max-height: calc(100vh - 80px);
            overflow-y: auto;
            z-index: 999;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        nav.mobile-nav.active {
            transform: translateX(0);
        }
        nav.mobile-nav ul {
            flex-direction: column;
            gap: 0;
        }
        nav.mobile-nav ul li {
            width: 100%;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        nav.mobile-nav ul li a,
        nav.mobile-nav ul li button {
            border-radius: 0;
            padding: 15px 20px;
        }
        nav.mobile-nav ul li ul.dropdown {
            position: static !important;
            width: 100% !important;
            min-width: auto !important;
            top: auto !important;
            left: auto !important;
            box-shadow: none;
            border-radius: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        nav.mobile-nav ul li.active > ul.dropdown {
            max-height: 500px;
        }
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
      border-radius: 20px;
      box-shadow: 0 25px 60px rgba(0,0,0,0.2);
      max-height: 90vh;
      height: 90vh;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      position: relative;
      animation: slideUpPopup 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .wizard-container { max-width: 900px; }
    .popup-content { max-width: 650px; height: auto; }

    @keyframes slideUpPopup { 
      from { opacity: 0; transform: translateY(30px) scale(0.95); } 
      to { opacity: 1; transform: translateY(0) scale(1); } 
    }

    .close-btn {
        position: absolute;
        top: 18px;
        right: 20px;
        width: 36px;
        height: 36px;
        background: rgba(255,255,255,0.2);
        border: none;
        border-radius: 50%;
        font-size: 1.4rem;
        color: white;
        cursor: pointer;
        z-index: 10;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    .close-btn:hover { 
      background: rgba(255,255,255,0.3);
      transform: rotate(90deg);
    }
    
    /* Header Wizard con Gradiente */
    .wizard-header, .popup-header {
      padding: 1.8rem 2.5rem;
      flex-shrink: 0;
      background: linear-gradient(135deg, var(--brand-color) 0%, #20c997 100%);
      color: white;
      border-radius: 20px 20px 0 0;
      position: relative;
      overflow: hidden;
    }
    .wizard-header::before, .popup-header::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -20%;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      pointer-events: none;
    }
    
    .wizard-header-content {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
      position: relative;
      z-index: 2;
    }
    
    .wizard-header-icon {
      width: 50px;
      height: 50px;
      background: rgba(255,255,255,0.2);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .wizard-header-icon svg {
      width: 28px;
      height: 28px;
      stroke: white;
      fill: none;
    }
    
    .wizard-header h1, .popup-header h2 {
        text-align: center; margin: 0; font-size: 1.5rem;
        font-weight: 700; color: white;
        letter-spacing: -0.3px;
    }
    
    .modal-header {
        padding: 1.2rem 1.5rem;
        background-color: var(--brand-color);
        color: white;
        border-radius: 12px 12px 0 0;
        margin: 0;
    }
    .modal-header h3 {
        text-align: center; margin: 0; font-size: 1.3rem;
        font-weight: 600; color: white;
    }
    
    .modal-content .close-button {
        color: rgba(255, 255, 255, 0.7);
        font-size: 2rem;
    }

    /* Stepper Navigation Moderno */
    .stepper-nav {
      display: flex;
      justify-content: center;
      padding: 0;
      background-color: var(--bg-white);
      border-bottom: 1px solid var(--border-color);
      flex-shrink: 0;
    }
    .stepper-wrapper {
      display: flex;
      align-items: center;
      padding: 1.5rem 2rem;
      max-width: 600px;
      width: 100%;
    }
    .step {
      display: flex; 
      align-items: center; 
      flex-direction: column;
      text-align: center; 
      position: relative; 
      flex: 1;
      cursor: pointer;
    }
    .step-bubble {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: var(--bg-light);
      border: 2px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      z-index: 2;
    }
    .step-bubble svg {
      width: 22px;
      height: 22px;
      stroke: var(--text-light);
      stroke-width: 2;
      fill: none;
      transition: all 0.3s ease;
    }
    .step-label { 
      font-size: 0.7rem;
      font-weight: 600;
      color: var(--text-light);
      margin-top: 8px;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    /* Step connector line */
    .step:not(:last-child)::after {
      content: '';
      position: absolute;
      top: 24px;
      left: calc(50% + 28px);
      width: calc(100% - 56px);
      height: 3px;
      background: var(--border-color);
      z-index: 1;
      transition: all 0.4s ease;
    }
    /* Active step */
    .step.active .step-bubble {
      background: #e8f5e9;
      border-color: var(--brand-color);
      box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.15);
    }
    .step.active .step-bubble svg { stroke: var(--brand-color); }
    .step.active .step-label { color: var(--brand-color); }
    /* Completed step */
    .step.completed .step-bubble {
      background: var(--brand-color);
      border-color: var(--brand-color);
    }
    .step.completed .step-bubble svg { stroke: white; }
    .step.completed .step-label { color: var(--brand-color); }
    .step.completed::after { background: var(--brand-color); }

    .wizard-body, .popup-body, .modal-body {
        padding: 2rem 2.5rem;
        background-color: #f8f9fa;
        overflow-y: auto;
        flex: 1;
        min-height: 0; /* Importante per scroll in flexbox */
    }
    .modal-content .modal-body {
        padding: 1.5rem;
    }
    
    /* Form che avvolge body e footer */
    .wizard-container form,
    .popup-content form {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        overflow: hidden;
    }
    
    /* Form Card per raggruppare campi */
    .form-card {
      background: white;
      border-radius: 14px;
      padding: 1.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      margin-bottom: 1rem;
    }
    .form-card-title {
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-light);
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .form-card-title svg {
      width: 16px;
      height: 16px;
      stroke: var(--brand-color);
    }
    
    .step-pane { display: none; animation: fadeInStep 0.4s ease; }
    .step-pane.active { display: block; }
    @keyframes fadeInStep { 
      from { opacity: 0; transform: translateX(20px); } 
      to { opacity: 1; transform: translateX(0); } 
    }

    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.25rem;
    }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group.full-width { grid-column: 1 / -1; }
    label { font-weight: 600; font-size: 0.85rem; color: #4a5568; margin-bottom: 0; }
    
    /* Input con icone */
    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    .input-wrapper .input-icon {
      position: absolute;
      left: 14px;
      width: 18px;
      height: 18px;
      stroke: var(--text-light);
      fill: none;
      pointer-events: none;
      transition: stroke 0.2s ease;
    }
    .input-wrapper input:focus ~ .input-icon {
      stroke: var(--brand-color);
    }
    
    input, select, textarea {
      width: 100%; 
      padding: 0.85rem 1rem;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      font-size: 0.95rem; 
      color: var(--text-dark);
      box-sizing: border-box; 
      transition: all 0.2s ease;
      background-color: white;
      font-family: inherit;
    }
    .input-wrapper input {
      padding-left: 44px;
    }
    input:focus, select:focus, textarea:focus {
      border-color: var(--brand-color); 
      outline: none; 
      box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
    }
    input::placeholder { color: #a0aec0; }
    
    .client-input-container {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .client-input-container input[type="text"] {
        flex-grow: 1;
    }

    /* Footer con Progress Bar */
    .wizard-footer, .popup-footer, .modal-footer {
      display: flex; 
      justify-content: space-between;
      align-items: center;
      padding: 1.25rem 2.5rem; 
      border-top: 1px solid var(--border-color);
      background-color: white; 
      flex-shrink: 0;
      gap: 1rem;
      border-radius: 0 0 20px 20px;
    }
    .modal-footer {
        justify-content: flex-end;
        border-radius: 0 0 12px 12px;
    }
    
    /* Progress bar nel footer */
    .wizard-progress-section {
      flex: 1;
      max-width: 280px;
      margin: 0 1.5rem;
    }
    .wizard-progress-bar {
      height: 6px;
      background: var(--border-color);
      border-radius: 3px;
      overflow: hidden;
    }
    .wizard-progress-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--brand-color), #20c997);
      border-radius: 3px;
      transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      width: 25%;
    }
    .wizard-progress-text {
      font-size: 0.7rem;
      color: var(--text-light);
      margin-top: 5px;
      text-align: center;
    }

    .wizard-btn, .popup-btn, .modal-footer button {
      padding: 0.85rem 2rem;
      font-size: 0.95rem; 
      font-weight: 600;
      font-family: inherit;
      border-radius: 10px;
      border: none;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .wizard-btn svg {
      width: 18px;
      height: 18px;
      stroke: currentColor;
      fill: none;
    }
    .wizard-btn.prev, .modal-footer .cancel-button { 
        background-color: #f1f5f9; 
        color: #64748b;
        border: 2px solid #e2e8f0;
    }
    .wizard-btn.prev:hover, .modal-footer .cancel-button:hover { 
        background-color: #e2e8f0; 
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

    /* ========== PRENOTAZIONE PRODOTTO MODAL STYLES ========== */
    .prenotazione-modal {
        width: 100%;
        max-width: 700px;
        background: white;
        border-radius: 24px;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.25);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        max-height: 92vh;
        animation: prenotazioneSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .prenotazione-modal form {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        overflow: hidden;
    }
    
    @keyframes prenotazioneSlideUp {
        from { opacity: 0; transform: translateY(40px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    
    .prenotazione-modal-header {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        padding: 1.75rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    
    .prenotazione-modal-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
        pointer-events: none;
    }
    
    .prenotazione-header-content {
        display: flex;
        align-items: center;
        gap: 1rem;
        position: relative;
        z-index: 2;
    }
    
    .prenotazione-header-icon {
        width: 52px;
        height: 52px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
    }
    
    .prenotazione-header-icon svg {
        width: 28px;
        height: 28px;
        stroke: white;
        fill: none;
    }
    
    .prenotazione-header-text h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        color: white;
        letter-spacing: -0.3px;
    }
    
    .prenotazione-header-text span {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.85);
    }
    
    .prenotazione-close-btn {
        width: 42px;
        height: 42px;
        background: rgba(255, 255, 255, 0.2);
        border: none;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        position: relative;
        z-index: 2;
    }
    
    .prenotazione-close-btn svg {
        width: 22px;
        height: 22px;
        stroke: white;
    }
    
    .prenotazione-close-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }
    
    .prenotazione-modal-body {
        padding: 1.5rem 2rem;
        overflow-y: auto;
        flex: 1;
        min-height: 0;
        background: #f8fafc;
    }
    
    .prenotazione-section {
        background: white;
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
    }
    
    .prenotazione-section:last-child {
        margin-bottom: 0;
    }
    
    .prenotazione-section-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .prenotazione-section-header svg {
        width: 18px;
        height: 18px;
        stroke: #22c55e;
    }
    
    .prenotazione-field {
        margin-bottom: 1rem;
    }
    
    .prenotazione-field:last-child {
        margin-bottom: 0;
    }
    
    .prenotazione-field.full-width {
        grid-column: 1 / -1;
    }
    
    .prenotazione-field label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 0.4rem;
    }
    
    .prenotazione-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .prenotazione-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    
    .prenotazione-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .prenotazione-input-wrapper svg {
        position: absolute;
        left: 14px;
        width: 18px;
        height: 18px;
        stroke: #94a3b8;
        pointer-events: none;
        transition: stroke 0.2s ease;
    }
    
    .prenotazione-input-wrapper input {
        width: 100%;
        padding: 0.75rem 0.75rem 0.75rem 44px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        background: #f8fafc;
    }
    
    .prenotazione-input-wrapper input:focus {
        outline: none;
        border-color: #22c55e;
        background: white;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
    }
    
    .prenotazione-input-wrapper input:focus + svg,
    .prenotazione-input-wrapper input:focus ~ svg {
        stroke: #22c55e;
    }
    
    .prenotazione-input-wrapper.currency {
        position: relative;
    }
    
    .prenotazione-input-wrapper.currency .currency-symbol {
        position: absolute;
        left: 14px;
        font-size: 1rem;
        font-weight: 600;
        color: #64748b;
        pointer-events: none;
    }
    
    .prenotazione-input-wrapper.currency input {
        padding-left: 36px;
    }
    
    /* Quantity Wrapper */
    .prenotazione-quantity-wrapper {
        display: flex;
        align-items: center;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        background: #f8fafc;
        transition: all 0.2s ease;
    }
    
    .prenotazione-quantity-wrapper:focus-within {
        border-color: #22c55e;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
    }
    
    .prenotazione-quantity-wrapper input {
        width: 100%;
        text-align: center;
        border: none;
        padding: 0.75rem 0.5rem;
        font-size: 1rem;
        font-weight: 600;
        background: transparent;
    }
    
    .prenotazione-quantity-wrapper input:focus {
        outline: none;
        box-shadow: none;
    }
    
    .qty-btn {
        width: 40px;
        height: 100%;
        padding: 0.75rem;
        background: #f1f5f9;
        border: none;
        font-size: 1.2rem;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .qty-btn:hover {
        background: #e2e8f0;
        color: #22c55e;
    }
    
    .qty-btn.minus { border-right: 1px solid #e2e8f0; }
    .qty-btn.plus { border-left: 1px solid #e2e8f0; }
    
    /* Payment Cards */
    .prenotazione-payment-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }
    
    .payment-card {
        padding: 1rem;
        border-radius: 12px;
        text-align: center;
        transition: all 0.2s ease;
    }
    
    .payment-card.total {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border: 2px solid #86efac;
    }
    
    .payment-card.deposit {
        background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
        border: 2px solid #fde047;
    }
    
    .payment-card.remaining {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border: 2px solid #93c5fd;
    }
    
    .payment-card-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    
    .payment-card.total .payment-card-label { color: #15803d; }
    .payment-card.deposit .payment-card-label { color: #a16207; }
    .payment-card.remaining .payment-card-label { color: #1d4ed8; }
    
    .payment-card-value {
        font-size: 1.3rem;
        font-weight: 700;
    }
    
    .payment-card.total .payment-card-value { color: #16a34a; }
    .payment-card.remaining .payment-card-value { color: #2563eb; }
    
    .payment-card-input {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    
    .payment-card-input span {
        font-size: 1rem;
        font-weight: 600;
        color: #a16207;
    }
    
    .payment-card-input input {
        width: 80px;
        padding: 0.4rem;
        border: 2px solid #fde047;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 700;
        text-align: center;
        background: white;
        color: #a16207;
    }
    
    .payment-card-input input:focus {
        outline: none;
        border-color: #eab308;
        box-shadow: 0 0 0 3px rgba(234, 179, 8, 0.2);
    }
    
    /* Client Search */
    .prenotazione-client-search {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        position: relative;
    }
    
    .prenotazione-client-search .prenotazione-input-wrapper {
        flex: 1;
    }
    
    .add-client-btn {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        border: none;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    }
    
    .add-client-btn svg {
        width: 22px;
        height: 22px;
        stroke: white;
    }
    
    .add-client-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4);
    }
    
    /* Textarea */
    .prenotazione-section textarea {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        font-family: inherit;
        resize: vertical;
        min-height: 80px;
        background: #f8fafc;
        transition: all 0.2s ease;
    }
    
    .prenotazione-section textarea:focus {
        outline: none;
        border-color: #22c55e;
        background: white;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
    }
    
    .prenotazione-section textarea::placeholder {
        color: #94a3b8;
    }
    
    /* Footer */
    .prenotazione-modal-footer {
        padding: 1.25rem 2rem;
        background: white;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }
    
    .prenotazione-btn-cancel {
        padding: 0.75rem 1.5rem;
        background: #f1f5f9;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
        font-family: inherit;
    }
    
    .prenotazione-btn-cancel svg {
        width: 18px;
        height: 18px;
    }
    
    .prenotazione-btn-cancel:hover {
        background: #e2e8f0;
        border-color: #cbd5e1;
    }
    
    .prenotazione-btn-submit {
        padding: 0.75rem 1.75rem;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        border: none;
        border-radius: 10px;
        font-size: 0.95rem;
        font-weight: 600;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
        box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35);
        font-family: inherit;
    }
    
    .prenotazione-btn-submit svg {
        width: 18px;
        height: 18px;
    }
    
    .prenotazione-btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(34, 197, 94, 0.45);
    }
    
    /* Responsive */
    @media (max-width: 700px) {
        .prenotazione-modal {
            max-width: 95%;
            max-height: 95vh;
            border-radius: 16px;
        }
        
        .prenotazione-modal-header {
            padding: 1.25rem 1.5rem;
        }
        
        .prenotazione-header-icon {
            width: 44px;
            height: 44px;
        }
        
        .prenotazione-header-text h2 {
            font-size: 1.2rem;
        }
        
        .prenotazione-modal-body {
            padding: 1rem 1.25rem;
        }
        
        .prenotazione-grid-2,
        .prenotazione-grid-3 {
            grid-template-columns: 1fr;
        }
        
        .prenotazione-payment-cards {
            grid-template-columns: 1fr;
        }
        
        .prenotazione-modal-footer {
            padding: 1rem 1.25rem;
            flex-direction: column;
        }
        
        .prenotazione-btn-cancel,
        .prenotazione-btn-submit {
            width: 100%;
            justify-content: center;
        }
    }
    /* ========== FINE PRENOTAZIONE PRODOTTO MODAL STYLES ========== */
    
    /* Pattern Lock Section */
    .pattern-section {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
      padding: 1.5rem;
      background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
      border-radius: 16px;
      border: 2px dashed #e2e8f0;
    }
    .pattern-section-header {
      text-align: center;
    }
    .pattern-section-header h4 {
      font-size: 1rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 4px;
    }
    .pattern-section-header p {
      font-size: 0.8rem;
      color: var(--text-light);
      margin: 0;
    }
    
    .pattern-lock {
        width: 180px; height: 180px; display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 20px; position: relative; user-select: none; touch-action: none;
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }
    #pattern-canvas { position: absolute; top: 0; left: 0; pointer-events: none; z-index: 1; }
    .pattern-dot {
        width: 100%; height: 100%; background: #e2e8f0; border-radius: 50%;
        border: 3px solid #cbd5e1; cursor: pointer; z-index: 2;
        transition: all 0.2s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .pattern-dot:hover { background-color: #d1fae5; border-color: var(--brand-color); }
    .pattern-dot.selected { background-color: var(--brand-color); border-color: var(--brand-dark); transform: scale(1.15); box-shadow: 0 4px 12px rgba(40,167,69,0.3); }
    
    .pattern-hint {
      font-size: 0.75rem;
      color: var(--text-light);
      text-align: center;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .pattern-hint svg { width: 14px; height: 14px; stroke: var(--text-light); }
    
    /* Telefono Chip Display */
    .telefono-chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, var(--brand-color) 0%, #20c997 100%);
      color: white;
      padding: 8px 14px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .telefono-chip svg { width: 16px; height: 16px; stroke: white; }
    
    /* Checkbox Wizard Style */
    .checkbox-wrapper-wizard {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 1rem;
      background: #f8fafc;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.2s ease;
      border: 2px solid transparent;
    }
    .checkbox-wrapper-wizard:hover { background: #f1f5f9; border-color: #e2e8f0; }
    .checkbox-wrapper-wizard input[type="checkbox"] {
      width: 20px;
      height: 20px;
      accent-color: var(--brand-color);
      cursor: pointer;
      flex-shrink: 0;
      margin-top: 2px;
    }
    .checkbox-wrapper-wizard .checkbox-label {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .checkbox-wrapper-wizard .checkbox-label span:first-child {
      font-weight: 600;
      font-size: 0.9rem;
      color: var(--text-dark);
    }
    .checkbox-wrapper-wizard .checkbox-label span:last-child {
      font-size: 0.8rem;
      color: var(--text-light);
    }
    
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
    
    /* Responsive Wizard */
    @media (max-width: 768px) {
        .wizard-container {
            width: 100%;
            height: 100%;
            max-height: 100vh;
            border-radius: 0;
        }
        .wizard-header {
            padding: 1rem 1.25rem;
            border-radius: 0;
        }
        .stepper-nav {
            gap: 0.5rem;
        }
        .step-label {
            display: none;
        }
        .step-bubble {
            width: 36px;
            height: 36px;
        }
        .step-bubble svg {
            width: 16px;
            height: 16px;
        }
        .wizard-body {
            padding: 1rem 1.25rem;
            min-height: 0;
        }
        .wizard-footer {
            padding: 1rem;
            flex-wrap: wrap;
            border-radius: 0;
            flex-shrink: 0;
        }
        .wizard-progress-section {
            order: 3;
            width: 100%;
            max-width: 100%;
            margin: 0.75rem 0 0 0;
        }
        .form-card {
            padding: 1rem;
        }
        .pattern-lock {
            width: 150px;
            height: 150px;
            padding: 15px;
        }
        #pattern-canvas {
            width: 180px !important;
            height: 180px !important;
        }
    }
    
    /* Tooltip per icone */
    .icon-tooltip {
        position: relative;
    }
    .icon-tooltip:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.8);
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 0.75rem;
        white-space: nowrap;
        z-index: 10;
    }

    /* Stili per il Modal/Popup (Nuovo Cliente) */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 2010;
        padding: 1rem;
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
        padding: 0;
        border-radius: 16px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        width: 95%;
        max-width: 480px;
        position: relative;
        animation: slideUpPopup 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        max-height: 85vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    .modal-header {
        padding: 1.5rem 2rem;
        background: linear-gradient(135deg, var(--brand-color) 0%, #20c997 100%);
        color: white;
        border-radius: 16px 16px 0 0;
        margin: 0;
        position: relative;
        flex-shrink: 0;
    }
    .modal-header h3 {
        text-align: center;
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        color: white;
    }
    .modal-header .close-button {
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        width: 32px;
        height: 32px;
        background: rgba(255,255,255,0.2);
        border: none;
        border-radius: 50%;
        font-size: 1.3rem;
        color: white;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    .modal-header .close-button:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-50%) rotate(90deg);
    }
    
    .modal-body {
        padding: 1.5rem 2rem;
        overflow-y: auto;
        flex: 1;
        min-height: 200px;
        background: #f8fafc;
    }
    
    /* Fix per il modal nuovo cliente */
    #new_client_modal_overlay .modal-content {
        max-height: 90vh;
        overflow: visible;
    }
    #new_client_modal_overlay .modal-body {
        display: block !important;
        overflow-y: auto !important;
        max-height: 60vh;
        visibility: visible !important;
        opacity: 1 !important;
    }
    #new_client_modal_overlay .tab-content {
        display: none !important;
        padding-top: 1rem;
        visibility: visible !important;
        opacity: 1 !important;
    }
    #new_client_modal_overlay .tab-content.active {
        display: block !important;
    }
    #new_client_modal_overlay .form-group {
        display: flex !important;
        flex-direction: column !important;
        margin-bottom: 1rem !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    #new_client_modal_overlay .form-group label {
        display: block !important;
        margin-bottom: 0.5rem !important;
        color: #374151 !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
    }
    #new_client_modal_overlay .form-group input,
    #new_client_modal_overlay .form-group textarea {
        display: block !important;
        width: 100% !important;
        padding: 0.75rem 1rem !important;
        border: 2px solid #e2e8f0 !important;
        border-radius: 10px !important;
        font-size: 0.95rem !important;
        background: white !important;
        color: #1f2937 !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    #new_client_modal_overlay .form-group input:focus,
    #new_client_modal_overlay .form-group textarea:focus {
        border-color: #22c55e !important;
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15) !important;
    }
    
    .modal-body .form-group {
        margin-bottom: 1rem;
    }
    .modal-body .form-group:last-child {
        margin-bottom: 0;
    }
    .modal-body label {
        display: block;
        font-weight: 600;
        font-size: 0.8rem;
        color: #64748b;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .modal-body input,
    .modal-body textarea {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        background: white;
    }
    .modal-body input:focus,
    .modal-body textarea:focus {
        border-color: var(--brand-color);
        outline: none;
        box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
    }
    .modal-body input::placeholder,
    .modal-body textarea::placeholder {
        color: #a0aec0;
    }
    
    .modal-footer {
        padding: 1.25rem 2rem;
        background: white;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        flex-shrink: 0;
        border-radius: 0 0 16px 16px;
    }
    .modal-footer .cancel-button,
    .modal-footer .save-button {
        padding: 0.75rem 1.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .modal-footer .cancel-button {
        background: #f1f5f9;
        color: #64748b;
        border: 2px solid #e2e8f0;
    }
    .modal-footer .cancel-button:hover {
        background: #e2e8f0;
    }
    .modal-footer .save-button {
        background: var(--brand-color);
        color: white;
    }
    .modal-footer .save-button:hover {
        background: var(--brand-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
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
        margin-bottom: 1.5rem;
        gap: 8px;
        background: white;
        padding: 6px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease-out;
        margin-top: 1rem;
    }

    .tab-content.active {
        display: block !important;
    }
    
    /* Stili tab buttons migliorati */
    .tab-buttons {
        display: flex;
        gap: 0.5rem;
        background: #f1f5f9;
        padding: 0.35rem;
        border-radius: 10px;
    }
    
    .tab-button {
        flex: 1;
        padding: 0.65rem 1rem;
        border: none;
        background: transparent;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .tab-button.active {
        background: white;
        color: #22c55e;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    
    .tab-button:hover:not(.active) {
        background: rgba(255, 255, 255, 0.5);
        color: #334155;
    }
    
    /* Assicura che i form-group dentro i tab siano visibili */
    .modal-body .tab-content .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 1rem;
    }
    .modal-body .tab-content .form-group label {
        display: block;
        font-weight: 600;
        font-size: 0.8rem;
        color: #64748b;
        margin-bottom: 0;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .modal-body .tab-content .form-group input,
    .modal-body .tab-content .form-group textarea {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        background: white;
        box-sizing: border-box;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
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

    /* ===== PERMUTA POPUP - DESIGN WIZARD MODERNO ===== */
    #permutaPopup .wizard-container {
        max-width: 1000px;
    }
    
    #permutaPopup .wizard-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }
    
    #permutaPopup .stepper-nav {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0;
        padding: 1.25rem 2rem;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e2e8f0;
    }
    
    #permutaPopup .step-permuta {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        flex: 1;
        max-width: 160px;
    }
    
    #permutaPopup .step-permuta:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 22px;
        left: calc(50% + 25px);
        width: calc(100% - 50px);
        height: 3px;
        background: #e2e8f0;
        transition: background 0.4s ease;
    }
    
    #permutaPopup .step-permuta.completed::after {
        background: linear-gradient(90deg, #28a745, #20c997);
    }
    
    #permutaPopup .step-bubble-permuta {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: white;
        border: 3px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        position: relative;
        z-index: 2;
        font-size: 1.1rem;
    }
    
    #permutaPopup .step-permuta.active .step-bubble-permuta {
        border-color: #28a745;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.2);
        transform: scale(1.1);
    }
    
    #permutaPopup .step-permuta.completed .step-bubble-permuta {
        border-color: #28a745;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
    }
    
    #permutaPopup .step-label-permuta {
        margin-top: 8px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: color 0.3s ease;
    }
    
    #permutaPopup .step-permuta.active .step-label-permuta,
    #permutaPopup .step-permuta.completed .step-label-permuta {
        color: #28a745;
    }
    
    #permutaPopup .permuta-step-pane {
        display: none;
        animation: fadeInStep 0.4s ease;
    }
    
    #permutaPopup .permuta-step-pane.active {
        display: block;
    }
    
    /* Form Card Permuta */
    #permutaPopup .form-card-permuta {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        border-left: 4px solid #28a745;
    }
    
    #permutaPopup .form-card-permuta.purple {
        border-left-color: #17a2b8;
    }
    
    #permutaPopup .form-card-permuta.green {
        border-left-color: #10b981;
    }
    
    #permutaPopup .form-card-title-permuta {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    #permutaPopup .form-card-title-permuta .icon {
        font-size: 1.2rem;
    }
    
    /* Valutazione Tecnica Moderna */
    #permutaPopup .test-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
    }
    
    #permutaPopup .test-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0.9rem 1rem;
        background: #f8fafc;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    
    #permutaPopup .test-item:hover {
        border-color: #cbd5e1;
        background: #f1f5f9;
    }
    
    #permutaPopup .test-item .test-icon {
        font-size: 1.3rem;
        width: 36px;
        text-align: center;
    }
    
    #permutaPopup .test-item .test-info {
        flex: 1;
    }
    
    #permutaPopup .test-item .test-name {
        font-weight: 600;
        font-size: 0.85rem;
        color: #334155;
        margin-bottom: 4px;
    }
    
    #permutaPopup .test-item select {
        padding: 0.4rem 0.6rem;
        font-size: 0.8rem;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background: white;
    }
    
    #permutaPopup .test-item input[type="text"] {
        padding: 0.4rem 0.6rem;
        font-size: 0.8rem;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        width: 100%;
        margin-top: 6px;
    }
    
    /* Badge esito */
    #permutaPopup .esito-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    
    #permutaPopup .esito-ok { background: #d1fae5; color: #065f46; }
    #permutaPopup .esito-warning { background: #fef3c7; color: #92400e; }
    #permutaPopup .esito-error { background: #fee2e2; color: #991b1b; }
    
    /* Riepilogo Calcoli Moderno */
    #permutaPopup .calcoli-summary {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 16px;
        padding: 1.5rem;
        border: 2px solid #e2e8f0;
    }
    
    #permutaPopup .calcolo-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px dashed #e2e8f0;
    }
    
    #permutaPopup .calcolo-row:last-child {
        border-bottom: none;
    }
    
    #permutaPopup .calcolo-row.highlight {
        background: white;
        margin: 0.5rem -1rem;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        border: none;
    }
    
    #permutaPopup .calcolo-row.total {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        margin: 1rem -1.5rem -1.5rem;
        padding: 1.25rem 1.5rem;
        border-radius: 0 0 14px 14px;
        font-size: 1.1rem;
        font-weight: 700;
    }
    
    #permutaPopup .calcolo-label {
        font-weight: 500;
        color: #64748b;
    }
    
    #permutaPopup .calcolo-value {
        font-weight: 700;
        color: #1e293b;
    }
    
    #permutaPopup .calcolo-row.total .calcolo-label,
    #permutaPopup .calcolo-row.total .calcolo-value {
        color: white;
    }
    
    /* Costi Ricondizionamento Moderni */
    #permutaPopup .costo-item-modern {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        padding: 0.75rem;
        background: white;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        margin-bottom: 0.75rem;
    }
    
    #permutaPopup .costo-item-modern input[type="text"] {
        flex: 2;
    }
    
    #permutaPopup .costo-item-modern input[type="number"] {
        flex: 1;
        max-width: 120px;
    }
    
    #permutaPopup .btn-remove-costo {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        border: none;
        background: #fee2e2;
        color: #dc2626;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    #permutaPopup .btn-remove-costo:hover {
        background: #fecaca;
    }
    
    #permutaPopup .btn-add-costo {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.6rem 1.2rem;
        background: #dbeafe;
        color: #1d4ed8;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    #permutaPopup .btn-add-costo:hover {
        background: #bfdbfe;
    }
    
    /* Footer Permuta */
    #permutaPopup .wizard-footer {
        background: white;
        border-radius: 0 0 20px 20px;
    }
    
    #permutaPopup .wizard-progress-fill {
        background: linear-gradient(90deg, #28a745, #20c997);
    }
    
    /* Responsive Permuta */
    @media (max-width: 768px) {
        #permutaPopup .stepper-nav {
            gap: 0.25rem;
            padding: 1rem 0.5rem;
        }
        
        #permutaPopup .step-bubble-permuta {
            width: 36px;
            height: 36px;
            font-size: 1rem;
        }
        
        #permutaPopup .step-label-permuta {
            font-size: 0.7rem;
        }
        
        #permutaPopup .test-grid {
            grid-template-columns: 1fr;
        }
        
        #permutaPopup .costo-item-modern {
            flex-wrap: wrap;
        }
        
        #permutaPopup .costo-item-modern input[type="text"],
        #permutaPopup .costo-item-modern input[type="number"] {
            flex: 1 1 100%;
            max-width: none;
        }
    }

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

    /* ===== TOAST NOTIFICATIONS ===== */
    .toast-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 99999;
    }
    .toast {
        padding: 12px 20px;
        border-radius: 8px;
        color: #fff;
        background: #2ecc71;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        opacity: 0;
        transform: translateY(20px);
        animation: slideUp 0.4s forwards, fadeOutToast 0.4s forwards 3s;
        font-size: 15px;
        font-weight: 500;
        margin-top: 10px;
    }
    .toast.success { background: #2ecc71; }
    .toast.error { background: #e74c3c; }
    .toast.warning { background: #f39c12; color: #333; }
    .toast.info { background: #3498db; }
    @keyframes slideUp { to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeOutToast { to { opacity: 0; transform: translateY(20px); } }

    /* ===== CONFIRMATION MODAL ===== */
    .confirm-modal { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.8); background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); padding: 2rem; z-index: 3001; max-width: 400px; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
    .confirm-modal.show { opacity: 1; visibility: visible; transform: translate(-50%, -50%) scale(1); }
    .confirm-modal-backdrop { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 3000; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
    .confirm-modal-backdrop.show { opacity: 1; visibility: visible; }
    .confirm-title { font-size: 1.3rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.5rem; }
    .confirm-message { color: var(--text-light); margin-bottom: 2rem; line-height: 1.5; }
    .confirm-actions { display: flex; gap: 1rem; justify-content: flex-end; }
    .confirm-actions button { padding: 0.8rem 1.8rem; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
    .confirm-actions .btn-cancel { background-color: var(--border-color); color: var(--text-dark); }
    .confirm-actions .btn-cancel:hover { background-color: #d1d9e0; transform: translateY(-2px); }
    .confirm-actions .btn-confirm { background-color: #e74c3c; color: white; }
    .confirm-actions .btn-confirm:hover { background-color: #c0392b; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3); }

    /* ===== TABELLE MODERNE (solo per tabelle con classe .data-table) ===== */
    .data-table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: var(--shadow-md); }
    .data-table thead { background: linear-gradient(135deg, var(--brand-color) 0%, #1f8e3c 100%); color: white; }
    .data-table th { padding: 1.2rem; text-align: left; font-weight: 600; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px; cursor: pointer; user-select: none; position: relative; white-space: nowrap; }
    .data-table th:hover { background-color: rgba(0, 0, 0, 0.1); }
    .data-table th.sortable::after { content: ' ↕'; font-size: 0.8rem; opacity: 0.6; }
    .data-table th.sort-asc::after { content: ' ↑'; opacity: 1; }
    .data-table th.sort-desc::after { content: ' ↓'; opacity: 1; }
    .data-table tbody tr { border-bottom: 1px solid var(--border-color); transition: background-color 0.2s ease; }
    .data-table tbody tr:nth-child(even) { background-color: #f9fafb; }
    .data-table tbody tr:hover { background-color: #f0f7ff; }
    .data-table tbody tr:last-child { border-bottom: none; }
    .data-table td { padding: 1rem 1.2rem; color: var(--text-dark); font-size: 0.95rem; }
    table td.actions { display: flex; gap: 8px; justify-content: center; }
    table .action-btn { padding: 0.5rem 0.8rem; border-radius: 6px; border: none; cursor: pointer; font-size: 0.85rem; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 4px; }
    table .action-btn.edit { background-color: #3498db; color: white; }
    table .action-btn.edit:hover { background-color: #2980b9; transform: scale(1.05); }
    table .action-btn.delete { background-color: #e74c3c; color: white; }
    table .action-btn.delete:hover { background-color: #c0392b; transform: scale(1.05); }
    table .action-btn.view { background-color: #95a5a6; color: white; }
    table .action-btn.view:hover { background-color: #7f8c8d; transform: scale(1.05); }

    /* ===== PAGINATION MODERNA ===== */
    .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem; flex-wrap: wrap; }
    .pagination a, .pagination span { padding: 0.6rem 1rem; border-radius: 6px; border: 1px solid var(--border-color); text-decoration: none; color: var(--text-dark); transition: all 0.2s ease; font-weight: 500; }
    .pagination a:hover { background-color: var(--brand-color); color: white; border-color: var(--brand-color); }
    .pagination .active { background-color: var(--brand-color); color: white; border-color: var(--brand-color); }

    /* ===== EMPTY STATES ===== */
    .empty-state { text-align: center; padding: 3rem 2rem; color: var(--text-light); }
    .empty-state-icon { font-size: 4rem; color: var(--border-color); margin-bottom: 1rem; opacity: 0.7; }
    .empty-state-title { font-size: 1.3rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.5rem; }
    .empty-state-message { font-size: 1rem; color: var(--text-light); margin-bottom: 2rem; line-height: 1.6; }
    .empty-state-action { display: inline-block; padding: 0.9rem 1.8rem; background-color: var(--brand-color); color: white; border-radius: 8px; text-decoration: none; transition: all 0.2s ease; cursor: pointer; border: none; font-weight: 600; }
    .empty-state-action:hover { background-color: var(--brand-dark); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3); }

    /* ===== LOADING SPINNERS ===== */
    .spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid rgba(40, 167, 69, 0.2); border-top-color: var(--brand-color); border-radius: 50%; animation: spin 0.8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .loading-state { display: flex; align-items: center; justify-content: center; gap: 1rem; padding: 2rem; color: var(--text-light); font-weight: 500; }
    .skeleton { background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: loading 1.5s infinite; }
    @keyframes loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

    /* ===== BREADCRUMBS ===== */
    .breadcrumbs { display: flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem; background-color: var(--bg-light); border-radius: 8px; margin-bottom: 2rem; flex-wrap: wrap; font-size: 0.95rem; }
    .breadcrumb-item { display: flex; align-items: center; gap: 0.5rem; color: var(--text-light); }
    .breadcrumb-item.active { color: var(--text-dark); font-weight: 600; }
    .breadcrumb-item a { color: var(--brand-color); text-decoration: none; transition: color 0.2s ease; }
    .breadcrumb-item a:hover { color: var(--brand-dark); text-decoration: underline; }
    .breadcrumb-separator { color: var(--text-light); opacity: 0.5; }

    /* ===== DARK MODE ===== */
    @media (prefers-color-scheme: dark) {
        :root.dark-mode { --text-dark: #e8e8e8; --text-light: #b0b0b0; --bg-light: #1a1a1a; --bg-white: #242424; --border-color: #333333; }
        body.dark-mode { background-color: #0d0d0d; color: var(--text-dark); }
        .dark-mode table { background-color: var(--bg-white); }
        .dark-mode table tbody tr:nth-child(even) { background-color: #2a2a2a; }
        .dark-mode table tbody tr:hover { background-color: #333333; }
        .dark-mode .toast { background-color: var(--bg-white); border-color: var(--border-color); }
        .dark-mode .confirm-modal { background-color: var(--bg-white); }
    }
    .dark-mode-toggle { background: none; border: none; cursor: pointer; font-size: 1.4rem; color: white; transition: transform 0.2s ease; padding: 8px; }
    .dark-mode-toggle:hover { transform: scale(1.2); }

    /* ===== CARD COMPONENTS ===== */
    .card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-md); transition: all 0.3s ease; }
    .card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); }
    .card-header { font-size: 1.2rem; font-weight: 600; color: var(--text-dark); margin-bottom: 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 1rem; }
    .card-body { color: var(--text-dark); }

    /* ===== BADGES ===== */
    .badge { display: inline-block; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; white-space: nowrap; }
    .badge-success { background-color: #d4edda; color: #155724; }
    .badge-danger { background-color: #f8d7da; color: #721c24; }
    .badge-warning { background-color: #fff3cd; color: #856404; }
    .badge-info { background-color: #d1ecf1; color: #0c5460; }
    .badge-primary { background-color: #d6d8db; color: var(--text-dark); }

    /* ===== ACCESSIBILITY ===== */
    :focus-visible { outline: 2px solid var(--brand-color); outline-offset: 2px; }
    a:focus-visible, button:focus-visible { outline: 2px solid var(--brand-color); outline-offset: 4px; }
    input:focus-visible, select:focus-visible, textarea:focus-visible { outline: none; border-color: var(--brand-color); box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.15); }

    /* ===== PRINT STYLES ===== */
    @media print {
        .top-bar, nav, .user-menu-container, .hamburger-btn, .toast-container, .confirm-modal, .confirm-modal-backdrop, .action-btn, .pagination, .dark-mode-toggle { display: none !important; }
        body { padding-top: 0; background: white; }
        .wizard-container, .popup-content, .modal-content { box-shadow: none; page-break-inside: avoid; max-width: 100%; max-height: 100%; }
        table { box-shadow: none; border: 1px solid #000; }
        table th, table td { border: 1px solid #000; }
        .card { box-shadow: none; border: 1px solid #000; page-break-inside: avoid; }
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        body { padding-top: 100px; }
        table { font-size: 0.85rem; overflow-x: auto; display: block; }
        table thead { display: none; }
        table tbody, table tr, table td { display: block; width: 100%; }
        table tr { border-bottom: 2px solid var(--border-color); margin-bottom: 1rem; }
        table td { padding: 0.5rem 0 !important; border-bottom: 1px dotted var(--border-color); }
        table td::before { content: attr(data-label); font-weight: 600; color: var(--text-dark); display: block; margin-bottom: 0.5rem; }
        .toast-container { right: 10px; left: 10px; }
        .toast { min-width: auto; max-width: 100%; }
        .breadcrumbs { padding: 0.8rem 1rem; font-size: 0.85rem; }
        .card { padding: 1rem; }
        .empty-state-icon { font-size: 3rem; }
    }

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
  
  <!-- Hamburger Button per Mobile -->
  <button class="hamburger-btn" id="hamburgerBtn">
    <span></span>
    <span></span>
    <span></span>
  </button>

  <nav class="mobile-nav">
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
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            Costi e Preventivo
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="costo_preventivato">Costo Preventivato (€)</label>
                                <div class="input-wrapper">
                                    <input type="number" id="costo_preventivato" name="costo_preventivato" step="0.01" min="0" placeholder="0.00">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="costo_effettivo">Costo Effettivo (€)</label>
                                <div class="input-wrapper">
                                    <input type="number" id="costo_effettivo" name="costo_effettivo" step="0.01" min="0" placeholder="0.00">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
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
<div id="new_client_modal_overlay" class="modal-overlay">
    <div class="modal-content" style="max-height: 90vh; overflow: hidden;">
        <div class="modal-header">
            <h3>Aggiungi Nuovo Cliente</h3>
            <button type="button" class="close-button" id="close_new_client_modal_btn">&times;</button>
        </div>
        <div class="modal-body" style="display: block !important; padding: 1.5rem; overflow-y: auto; max-height: calc(90vh - 180px);">
            <div class="tab-buttons" style="display: flex; gap: 0.5rem; background: #f1f5f9; padding: 0.35rem; border-radius: 10px; margin-bottom: 1rem;">
                <button type="button" class="tab-button active" data-tab="personal_data_tab" style="flex: 1; padding: 0.65rem 1rem; border: none; background: white; border-radius: 8px; font-size: 0.85rem; font-weight: 600; color: #22c55e; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">Dati Personali</button>
                <button type="button" class="tab-button" data-tab="company_data_tab" style="flex: 1; padding: 0.65rem 1rem; border: none; background: transparent; border-radius: 8px; font-size: 0.85rem; font-weight: 600; color: #64748b; cursor: pointer;">Dati Aziendali</button>
            </div>

            <div id="personal_data_tab" class="tab-content active" style="display: block !important;">
                <div style="display: flex; flex-direction: column; margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.85rem;">Nome: *</label>
                    <input type="text" id="modal_nuovo_cliente_nome" placeholder="Nome" required style="display: block; width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; background: white; box-sizing: border-box;">
                </div>
                <div style="display: flex; flex-direction: column; margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.85rem;">Cognome: *</label>
                    <input type="text" id="modal_nuovo_cliente_cognome" placeholder="Cognome" required style="display: block; width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; background: white; box-sizing: border-box;">
                </div>
                <div style="display: flex; flex-direction: column; margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.85rem;">Telefono:</label>
                    <input type="text" id="modal_nuovo_cliente_telefono" placeholder="Es: 3331234567" style="display: block; width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; background: white; box-sizing: border-box;">
                </div>
                <div style="display: flex; flex-direction: column; margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.85rem;">Email:</label>
                    <input type="email" id="modal_nuovo_cliente_email" placeholder="nome@esempio.com" style="display: block; width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; background: white; box-sizing: border-box;">
                </div>
                <div style="display: flex; flex-direction: column; margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.85rem;">Indirizzo:</label>
                    <input type="text" id="modal_nuovo_cliente_indirizzo" placeholder="Via Roma, 1" style="display: block; width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; background: white; box-sizing: border-box;">
                </div>
                <div style="display: flex; flex-direction: column; margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.85rem;">Città:</label>
                    <input type="text" id="modal_nuovo_cliente_citta" placeholder="Roma" style="display: block; width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; background: white; box-sizing: border-box;">
                </div>
                <div style="display: flex; flex-direction: column; margin-bottom: 0;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.85rem;">Note:</label>
                    <textarea id="modal_nuovo_cliente_note" rows="2" placeholder="Note aggiuntive sul cliente" style="display: block; width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; background: white; box-sizing: border-box; resize: vertical;"></textarea>
                </div>
            </div>

            <div id="company_data_tab" class="tab-content" style="display: none;">
                <div style="display: flex; flex-direction: column; margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.85rem;">Ragione Sociale:</label>
                    <input type="text" id="modal_nuovo_cliente_ragione_sociale" placeholder="Nome S.p.A." style="display: block; width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; background: white; box-sizing: border-box;">
                </div>
                <div style="display: flex; flex-direction: column; margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.85rem;">Partita IVA:</label>
                    <input type="text" id="modal_nuovo_cliente_partita_iva" placeholder="IT12345678901" style="display: block; width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; background: white; box-sizing: border-box;">
                </div>
                <div style="display: flex; flex-direction: column; margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.85rem;">Indirizzo Azienda:</label>
                    <input type="text" id="modal_nuovo_cliente_indirizzo_azienda" placeholder="Via dell'Industria, 5" style="display: block; width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; background: white; box-sizing: border-box;">
                </div>
                <div style="display: flex; flex-direction: column; margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.85rem;">Città Azienda:</label>
                    <input type="text" id="modal_nuovo_cliente_citta_azienda" placeholder="Milano" style="display: block; width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; background: white; box-sizing: border-box;">
                </div>
                <div style="display: flex; flex-direction: column; margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.85rem;">Telefono Azienda:</label>
                    <input type="text" id="modal_nuovo_cliente_telefono_azienda" placeholder="Es: 0212345678" style="display: block; width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; background: white; box-sizing: border-box;">
                </div>
                <div style="display: flex; flex-direction: column; margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.85rem;">Email Azienda:</label>
                    <input type="email" id="modal_nuovo_cliente_email_azienda" placeholder="info@azienda.com" style="display: block; width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; background: white; box-sizing: border-box;">
                </div>
                <div style="display: flex; flex-direction: column; margin-bottom: 0;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.85rem;">Note Azienda:</label>
                    <textarea id="modal_nuovo_cliente_note_azienda" rows="2" placeholder="Note aggiuntive sull'azienda" style="display: block; width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; background: white; box-sizing: border-box; resize: vertical;"></textarea>
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
            tabContents.forEach(c => {
                c.classList.remove('active');
                c.style.display = 'none';
            });
            tabButtons.forEach(b => b.classList.remove('active'));
            const targetTab = modalOverlay.querySelector(`#${tabId}`);
            targetTab.classList.add('active');
            targetTab.style.display = 'block';
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

        const openBtn = document.getElementById('openNuovaPermutaPopupBtn');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
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
</body>
</html>


