<?php
// Connessione al database
$host = 'localhost';
$db   = 'gestionale_tsservice';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Helper function to generate a random alphanumeric code
function generate_random_code($length = 12) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $random_code = '';
    for ($i = 0; $i < $length; $i++) {
        $random_code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $random_code;
}

// Inizializza la query SQL di base
$sql = "SELECT * FROM buoni_regalo";
$search_query = '';

// Gestione della ricerca
if (isset($_GET['search_query']) && !empty($_GET['search_query'])) {
    $search_query = $conn->real_escape_string($_GET['search_query']);
    // Aggiungi la clausola WHERE per filtrare i risultati
    $sql .= " WHERE nome LIKE '%$search_query%' 
              OR destinatario LIKE '%$search_query%' 
              OR note LIKE '%$search_query%'";
}

// Aggiungi l'ordinamento
$sql .= " ORDER BY data_creazione DESC";

$result = $conn->query($sql);

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
                $cliente_id = (int)$_POST['cliente_id'];
                $modello = $conn->real_escape_string($_POST['modello']);
                $imei = $conn->real_escape_string($_POST['imei']);
                $codice_sblocco = $conn->real_escape_string($_POST['codice_sblocco']);
                $account = $conn->real_escape_string($_POST['account']);
                $diagnosi = $conn->real_escape_string($_POST['diagnosi']);
                $salva_dati = isset($_POST['salva_dati']) ? 1 : 0;
                $costo_preventivato = !empty($_POST['costo_preventivato']) ? (float)$_POST['costo_preventivato'] : 'NULL';
                $costo_effettivo = !empty($_POST['costo_effettivo']) ? (float)$_POST['costo_effettivo'] : 'NULL';
                $hardware_ritirato = $conn->real_escape_string($_POST['hardware_ritirato']);
                $dispositivo_sostitutivo = $conn->real_escape_string($_POST['dispositivo_sostitutivo']);
                $stato = $conn->real_escape_string($_POST['stato']);
                $codice_sblocco_grafico = isset($_POST['codice_sblocco_grafico']) ? $conn->real_escape_string($_POST['codice_sblocco_grafico']) : '';
                
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
                $initial_code = $conn->real_escape_string($_POST['codice_buono']); // Codice dal client-side
                $valore_buono = !empty($_POST['valore']) ? (float)$_POST['valore'] : 'NULL';
                $destinatario_buono = !empty($_POST['destinatario']) ? "'" . $conn->real_escape_string($_POST['destinatario']) . "'" : 'NULL';
                $note_buono = !empty($_POST['mittente_note']) ? "'" . $conn->real_escape_string($_POST['mittente_note']) . "'" : 'NULL';
                $data_scadenza_buono = !empty($_POST['data_scadenza']) ? "'" . $conn->real_escape_string($_POST['data_scadenza']) . "'" : 'NULL';
                $stato_buono = $conn->real_escape_string($_POST['stato_buono']);

                $max_retries = 5; // Massimo tentativi per generare un codice unico
                $unique_code_found = false;
                $nome_buono = $initial_code; // Inizia con il codice ricevuto dal client

                for ($i = 0; $i < $max_retries; $i++) {
                    // Verifica se il codice attuale esiste già nel database
                    $check_sql = "SELECT COUNT(*) FROM buoni_regalo WHERE nome = '$nome_buono'";
                    $check_result = $conn->query($check_sql);
                    
                    if ($check_result && $check_result->fetch_row()[0] == 0) {
                        // Codice unico trovato!
                        $unique_code_found = true;
                        break;
                    } else {
                        // Codice esistente, genera un nuovo codice per il prossimo tentativo
                        $nome_buono = generate_random_code(); // Genera un nuovo codice casuale
                    }
                }

                if (!$unique_code_found) {
                    $gift_card_feedback_message = "<div class='feedback error'>Errore: Impossibile generare un codice buono unico dopo " . $max_retries . " tentativi. Riprova.</div>";
                    break; // Esci dallo switch, non procedere con l'inserimento
                }
                
                // Se un codice unico è stato trovato, procedi con l'inserimento
                $sql_insert_buono = "INSERT INTO buoni_regalo (
                    nome, valore, data_scadenza, destinatario, note, stato, data_creazione
                ) VALUES (
                    '$nome_buono', $valore_buono, $data_scadenza_buono, $destinatario_buono, $note_buono, '$stato_buono', NOW()
                )";
        
                try {
                    if ($conn->query($sql_insert_buono) === TRUE) {
                        $new_buono_id = $conn->insert_id; // Get the ID of the newly inserted buono
                        $gift_card_feedback_message = "<div class='feedback success'>Buono regalo creato con successo! Stampa in corso...</div>";
                        // Add JavaScript to redirect to the print page after showing the message
                        echo "<script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    showMessage('Buono regalo creato con successo! Preparazione per la stampa.', false);
                                    setTimeout(() => { 
                                        window.location.href = 'stampa_buono.php?id=" . $new_buono_id . "'; 
                                    }, 1000); // Redirect after 1 second
                                });
                              </script>";
                    } else {
                        // Questa parte dovrebbe essere raggiunta solo per errori non di duplicazione,
                        // dato che la logica di retry e il catch sottostante gestiscono le duplicazioni.
                        $gift_card_feedback_message = "<div class='feedback error'>Errore durante la creazione del buono: " . $conn->error . "</div>";
                    }
                } catch (mysqli_sql_exception $e) {
                    // Cattura specificamente l'eccezione di duplicazione (codice 1062 per MySQL)
                    if ($e->getCode() == 1062) {
                        $gift_card_feedback_message = "<div class='feedback error'>Errore: Il codice buono '$nome_buono' esiste già. Riprova o contatta il supporto.</div>";
                    } else {
                        $gift_card_feedback_message = "<div class='feedback error'>Errore database durante la creazione del buono: " . $e->getMessage() . "</div>";
                    }
                }
                break;

            case 'permuta':
                // Logica per il form di permuta
                $numero_progressivo = (int)$_POST['numero_progressivo'];
                $data_permuta_form = $conn->real_escape_string($_POST['data_permuta']); // Mappa a 'data' nel DB
                $cliente_id = (int)$_POST['cliente_id'];
                $cliente_display_name = $conn->real_escape_string($_POST['cliente_display']); // Nuovo: per la colonna 'cliente'
                $telefono_cliente_var = $conn->real_escape_string($_POST['telefono_cliente']); // Mappa a 'telefono_cliente' nel DB
                $stato_permuta_var = $conn->real_escape_string($_POST['stato_permuta']); // Mappa a 'status' nel DB
                $tuo_modello_var = $conn->real_escape_string($_POST['tuo_modello']); // Mappa a 'modello_nuovo'
                $tuo_imei_var = $conn->real_escape_string($_POST['tuo_imei']); // Mappa a 'imei_nuovo'
                $tuo_valore_vendita_var = !empty($_POST['tuo_valore_vendita']) ? (float)$_POST['tuo_valore_vendita'] : 'NULL'; // Mappa a 'prezzo_nuovo'
                $tuo_note_var = $conn->real_escape_string($_POST['tuo_note']); // Mappa a 'note_nuovo'
                $cliente_modello_var = $conn->real_escape_string($_POST['cliente_modello']); // Mappa a 'modello_usato'
                $cliente_imei_var = $conn->real_escape_string($_POST['cliente_imei']); // Mappa a 'imei_usato'
                $cliente_note_var = $conn->real_escape_string($_POST['cliente_note']); // Mappa a 'note_usato'
                $cliente_valore_permuta_var = !empty($_POST['cliente_valore_permuta']) ? (float)$_POST['cliente_valore_permuta'] : 'NULL'; // Mappa a 'prezzo_permuta'
                $totale_costi_ricondizionamento_val_var = !empty($_POST['totale_costi_ricondizionamento_val']) ? (float)$_POST['totale_costi_ricondizionamento_val'] : 'NULL'; // Mappa a 'costo_riparazione'
                $costo_accessori_input_var = !empty($_POST['costo_accessori_input']) ? (float)$_POST['costo_accessori_input'] : 'NULL'; // Mappa a 'costo_accessori'
                $costo_prodotto_input_var = !empty($_POST['costo_prodotto_input']) ? (float)$_POST['costo_prodotto_input'] : 'NULL'; // Mappa a 'costo_prodotto'
                $prezzo_vendita_input_var = !empty($_POST['prezzo_vendita_input']) ? (float)$_POST['prezzo_vendita_input'] : 'NULL'; // Mappa a 'prezzo_vendita'
                $valore_netto_ricevuto_val_var = !empty($_POST['valore_netto_ricevuto_val']) ? (float)$_POST['valore_netto_ricevuto_val'] : 'NULL'; // Mappa a 'differenza'
                $note_generali_input_var = $conn->real_escape_string($_POST['note_generali_input']); // Mappa a 'note_generali'

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
        
                if ($conn->query($sql_insert_permuta) === TRUE) {
                    $new_permuta_id = $conn->insert_id;
                    $permuta_feedback_message = "<div class='feedback success'>Permuta salvata con successo! Numero Permuta: PMT-" . str_pad($new_permuta_id, 5, '0', STR_PAD_LEFT) . " Il popup si chiuderà tra 1 secondo.</div>";
                } else {
                    $permuta_feedback_message = "<div class='feedback error'>Errore durante il salvataggio della permuta: " . $conn->error . "</div>";
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
$clienti = []; // Per la select di riparazione
$mappaTelefoni = []; // Per la select di riparazione
$sql_clienti = "SELECT id, nome, cognome, telefono FROM clienti_nuovo ORDER BY cognome, nome";
$result_clienti_select = $conn->query($sql_clienti);
if ($result_clienti_select && $result_clienti_select->num_rows > 0) {
    while ($row = $result_clienti_select->fetch_assoc()) {
        $clienti[] = $row;
        $mappaTelefoni[(int)$row['id']] = htmlspecialchars((string)($row['telefono'] ?? '')); // Aggiunta gestione null
    }
}
$result_clienti_select->free();

// Per l'autocomplete generale dei clienti (usato da permuta e prenotazione)
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

// Prodotti esistenti per autocomplete (per permuta, non più per prenotazione diretta)
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
                'priceNet' => (float)$p['prezzo_acquisto'],
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

// Chiusura della connessione al database
// $conn->close(); // Rimosso per evitare "mysqli object is already closed"

?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Elenco Buoni Regalo - TS Service</title>
<style>
  /* Gli stili globali per body e font-family sono già nel header.php */
  body {
    padding: 2rem;
  }

  .main-container {
      max-width: 1200px;
      margin: 0 auto;
  }

  h1 {
    color: var(--text-dark);
    text-align: left;
    margin-bottom: 25px;
    font-weight: 700;
  }

  .controls-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    gap: 20px;
    flex-wrap: wrap;
    padding: 15px;
    background-color: var(--bg-white);
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
  }

  .filter-container {
    display: inline-flex;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid var(--border-color);
  }

  .filter-btn {
    background-color: transparent;
    color: var(--text-light);
    border: none;
    padding: 8px 16px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.2s ease;
    border-right: 1px solid var(--border-color);
  }
  .filter-btn:last-child {
      border-right: none;
  }

  .filter-btn:hover {
    background-color: #f0f0f0;
    color: var(--text-dark);
  }
  .filter-btn.active {
    background-color: var(--brand-color);
    color: white;
  }


  .search-container {
    display: flex;
    gap: 10px;
    flex-grow: 1;
    max-width: 400px;
  }

  .search-container input[type="text"] {
    flex-grow: 1;
    padding: 10px 15px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    font-size: 1rem;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
    transition: border-color 0.2s ease;
  }

  .search-container input[type="text"]:focus {
    border-color: var(--brand-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
  }

  .search-container button {
    background-color: var(--brand-color);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
  }

  .search-container button:hover {
    background-color: var(--brand-dark);
    transform: translateY(-1px);
    box-shadow: 0 5px 12px rgba(0,0,0,0.15);
  }

  .cards-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
    margin: 0 auto;
  }

  .gift-card-item {
    background: var(--bg-white);
    border-radius: 20px;
    box-shadow: var(--shadow-md);
    padding: 25px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid var(--border-color);
  }

  .gift-card-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
  }

  .card-header .code-value {
      display: flex;
      flex-direction: column;
      gap: 5px;
  }

  .card-header .code {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--brand-dark);
  }

  .card-header .value {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--brand-color);
  }
  
  .status-badge {
      font-size: 0.8rem;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 20px;
      color: white;
      text-transform: uppercase;
  }
  .status-attivo { background-color: var(--brand-color); }
  .status-usato { background-color: #6c757d; }
  .status-scaduto { background-color: #dc3545; }


  .card-body {
    flex-grow: 1;
    margin-bottom: 20px;
  }

  .card-detail {
    display: flex;
    justify-content: space-between;
    font-size: 0.95rem;
    margin-bottom: 8px;
  }

  .card-detail strong {
    color: var(--text-dark);
  }

  .card-detail span {
    color: var(--text-light);
  }

  .card-notes {
    font-style: italic;
    font-size: 0.85rem;
    color: var(--text-light);
    margin-top: 10px;
    word-break: break-word;
  }

  .card-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding-top: 15px;
    border-top: 1px dashed var(--border-color);
    gap: 10px;
  }

  .btn-edit, .btn-print {
    background: var(--brand-color);
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: background-color 0.25s ease, transform 0.2s ease, box-shadow 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
  }
  .btn-edit:hover, .btn-print:hover {
    background-color: var(--brand-dark);
    transform: translateY(-1px);
    box-shadow: 0 5px 12px rgba(0,0,0,0.15);
    text-decoration: none;
    color: white;
  }

  .no-data {
    text-align: center;
    font-style: italic;
    color: var(--text-light);
    margin-top: 40px;
    font-size: 1.1rem;
    grid-column: 1 / -1; /* Occupa tutta la larghezza della griglia */
  }

.back-link {
  display: block;
  width: fit-content;
  margin: 30px auto 0;
  text-align: center;
  font-weight: 600;
  color: white;
  background: linear-gradient(135deg, var(--brand-color), var(--brand-dark));
  padding: 12px 20px;
  border-radius: 20px;
  text-decoration: none;
  font-size: 1.1rem;
  box-shadow: 0 4px 12px rgba(39, 174, 96, 0.6);
  transition: background 0.3s ease, box-shadow 0.3s ease, transform 0.2s ease;
}

.back-link:hover {
  background: linear-gradient(135deg, var(--brand-dark), var(--brand-color));
  box-shadow: 0 6px 16px rgba(30, 126, 52, 0.8);
  transform: translateY(-2px);
  text-decoration: none;
  color: white;
}

/* --- POPUP MODIFICA BUONO --- */
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

.popup-content {
  width: 100%;
  background-color: var(--bg-white);
  border-radius: 16px;
  box-shadow: var(--shadow-lg);
  max-height: 95vh;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  position: relative;
  animation: scaleInPopup 0.4s ease;
  max-width: 700px;
}

@keyframes scaleInPopup { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }

.close-btn {
    position: absolute;
    top: 15px;
    right: 25px;
    background: transparent;
    border: none;
    font-size: 2.5rem;
    color: #bdc3c7;
    cursor: pointer;
    z-index: 10;
    transition: color 0.2s ease, transform 0.2s ease;
    line-height: 1;
}
.close-btn:hover { color: var(--text-dark); transform: rotate(90deg); }

.popup-header {
  padding: 1.5rem 2.5rem;
  border-bottom: 1px solid var(--border-color);
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 15px;
}

.popup-header .icon {
    width: 32px;
    height: 32px;
    color: var(--brand-color);
}

.popup-header h2 { font-size: 1.6rem; text-align: left; margin: 0; font-weight: 600; color: var(--text-dark); }

.popup-body {
    padding: 2.5rem;
    background-color: var(--bg-light);
}

.form-grid { 
    display: grid;
    grid-template-columns: repeat(2, 1fr);
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

input[readonly] {
    background-color: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
}

.popup-footer {
  display: flex; justify-content: flex-end;
  padding: 1.5rem 2.5rem; border-top: 1px solid var(--border-color);
  background-color: #fdfdfd; flex-shrink: 0;
  gap: 1rem;
}
.popup-btn {
  padding: 0.8rem 1.5rem;
  font-size: 1rem; font-weight: 600;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 3px 8px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-width: 150px;
}
.popup-btn .spinner-icon {
    display: none;
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.5);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
.popup-btn.loading .spinner-icon {
    display: inline-block;
}
.popup-btn.loading .btn-text {
    display: none;
}
.popup-btn.submit { 
    background-color: var(--brand-color); 
    color: white;
}
.popup-btn.submit:hover {
    background-color: var(--brand-dark);
    transform: translateY(-2px);
    box-shadow: 0 5px 12px rgba(0,0,0,0.15);
}
.popup-btn:disabled { 
    background: #ecf0f1; 
    color: #c0c0c0; 
    cursor: not-allowed; 
    box-shadow: none;
    transform: none;
}

.form-group.value-group input {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--brand-color);
    text-align: center;
}

/* Message Box for notifications */
.message-box {
    position: fixed;
    top: 1rem;
    left: 50%;
    transform: translateX(-50%) translateY(-20px);
    background-color: #4CAF50;
    color: white;
    padding: 1.25rem 1.75rem;
    border-radius: 0.75rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    z-index: 2500;
    font-size: 1.1rem;
    font-weight: bold;
    border: 2px solid white;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.5s ease-out, transform 0.5s ease-out;
    text-align: center;
}
.message-box.error {
    background-color: #f44336;
    border-color: #ff9999;
}
.message-box.show {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
}

@media (max-width: 768px) {
    .controls-container {
        flex-direction: column;
        align-items: stretch;
    }
    .cards-container {
        grid-template-columns: 1fr;
        padding: 0 15px;
    }
    .gift-card-item {
        padding: 20px;
    }
    .card-header .code, .card-header .value {
        font-size: 1.2rem;
    }
    .card-detail {
        flex-direction: column;
        align-items: flex-start;
        margin-bottom: 10px;
    }
    .card-detail span {
        margin-top: 2px;
    }

    .popup-content {
        padding: 1rem;
    }
    .popup-header, .popup-body, .popup-footer {
        padding: 1rem 1.5rem;
    }
    .form-grid {
        gap: 1rem;
    }
    .popup-btn {
        padding: 0.7rem 1.5rem;
        font-size: 0.9rem;
    }
}
</style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="main-container">
<h1>Elenco Buoni Regalo</h1>

<div class="controls-container">
    <div class="filter-container">
        <button class="filter-btn active" data-filter="tutti">Tutti</button>
        <button class="filter-btn" data-filter="Attivo">Attivi</button>
        <button class="filter-btn" data-filter="Usato">Usati</button>
        <button class="filter-btn" data-filter="Scaduto">Scaduti</button>
    </div>
    <form method="GET" action="" class="search-container">
        <input type="text" name="search_query" placeholder="Cerca per codice, destinatario..." value="<?= htmlspecialchars($search_query) ?>">
        <button type="submit">Cerca</button>
    </form>
</div>

<div class="cards-container">
<?php if ($result && $result->num_rows > 0): ?>
  <?php while($row = $result->fetch_assoc()): 
    $stato_class = 'status-' . strtolower(htmlspecialchars($row['stato']));
  ?>
  <div class="gift-card-item" data-stato="<?= htmlspecialchars($row['stato']) ?>">
    <div class="card-header">
      <div class="code-value">
        <span class="code"><?= htmlspecialchars($row['nome']) ?></span>
        <span class="value"><?= number_format($row['valore'], 2, ',', '.') ?> &euro;</span>
      </div>
      <span class="status-badge <?= $stato_class ?>"><?= htmlspecialchars($row['stato']) ?></span>
    </div>
    <div class="card-body">
      <div class="card-detail">
        <strong>ID:</strong> <span><?= htmlspecialchars($row['id']) ?></span>
      </div>
      <div class="card-detail">
        <strong>Data Scadenza:</strong> 
        <span class="dynamic-expiry" data-expiry-date="<?= htmlspecialchars($row['data_scadenza']) ?>"><?= htmlspecialchars($row['data_scadenza']) ?: '-' ?></span>
      </div>
      <div class="card-detail">
        <strong>Destinatario:</strong> <span><?= htmlspecialchars($row['destinatario']) ?: '-' ?></span>
      </div>
      <div class="card-detail">
        <strong>Data Creazione:</strong> <span><?= htmlspecialchars($row['data_creazione']) ?></span>
      </div>
      <?php if (!empty($row['note'])): ?>
        <div class="card-notes">
          <strong>Note:</strong> <br> <?= nl2br(htmlspecialchars($row['note'])) ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="card-footer">
      <button class="btn-edit open-edit-buono-popup-btn" 
              data-id="<?= htmlspecialchars($row['id']) ?>" 
              data-nome="<?= htmlspecialchars($row['nome']) ?>" 
              data-valore="<?= htmlspecialchars($row['valore']) ?>" 
              data-destinatario="<?= htmlspecialchars($row['destinatario']) ?>" 
              data-data_scadenza="<?= htmlspecialchars($row['data_scadenza']) ?>" 
              data-note="<?= htmlspecialchars($row['note']) ?>"
              data-stato="<?= htmlspecialchars($row['stato']) ?>"
              title="Modifica Buono">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/><path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/></svg>
              Modifica</button>
      <button class="btn-print" 
              data-id="<?= htmlspecialchars($row['id']) ?>"
              title="Stampa Buono">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
              Stampa</button>
    </div>
  </div>
  <?php endwhile; ?>
<?php else: ?>
  <p class="no-data">Nessun buono regalo trovato.</p>
<?php endif; ?>
</div>

<a href="homepage.php" class="back-link" role="button" aria-label="Torna alla Home">← Torna alla Home</a>
</div>
<!-- Nuovo popup per la Modifica Buono Regalo -->
<div class="popup-overlay" id="editBuonoRegaloPopup">
    <div class="popup-content">
        <button type="button" class="close-btn" id="close-edit-buono-regalo-popup-btn">&times;</button>
        <div class="popup-header">
            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6C4.9 2 4 2.9 4 4V20C4 21.1 4.9 22 6 22H18C19.1 22 20 21.1 20 20V8L14 2ZM18 20H6V4H13V9H18V20Z"/></svg>
            </div>
            <h2>Modifica Buono Regalo</h2>
        </div>
        
        <form id="edit-buono-regalo-form">
            <input type="hidden" name="id" id="edit_buono_id">
            <div class="popup-body">
                <div class="form-grid">
                    <div class="form-group value-group">
                        <label for="edit_buono_valore">Valore Buono (€)</label>
                        <input type="number" id="edit_buono_valore" name="valore" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_buono_stato">Stato</label>
                        <select id="edit_buono_stato" name="stato_buono">
                            <option value="Attivo">Attivo</option>
                            <option value="Usato">Usato</option>
                            <option value="Scaduto">Scaduto</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="edit_buono_codice">Codice Buono (non modificabile)</label>
                        <input type="text" id="edit_buono_codice" name="codice_buono" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_buono_destinatario">Destinatario</label>
                        <input type="text" id="edit_buono_destinatario" name="destinatario" placeholder="Nome (opzionale)">
                    </div>
                    <div class="form-group">
                        <label for="edit_buono_data_scadenza">Data Scadenza</label>
                        <input type="date" id="edit_buono_data_scadenza" name="data_scadenza">
                    </div>
                    <div class="form-group full-width">
                        <label for="edit_buono_mittente_note">Note</label>
                        <textarea id="edit_buono_mittente_note" name="mittente_note" rows="3" placeholder="Note aggiuntive..."></textarea>
                    </div>
                </div>
            </div>

            <div class="popup-footer">
                <button type="submit" class="popup-btn submit" id="edit-buono-submit-btn">
                    <span class="btn-text">Salva Modifiche</span>
                    <span class="spinner-icon"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Message Box for notifications (globale) -->
<div id="messageBox" class="message-box"></div>

<script>
    // --- Utility Functions (globali) ---
    /**
     * Mostra un messaggio all'utente (successo o errore).
     * @param {string} message - Il testo del messaggio.
     * @param {boolean} isError - True se è un messaggio di errore, false altrimenti.
     */
    window.showMessage = function(message, isError = false) {
        const messageBox = document.getElementById('messageBox');
        if (!messageBox) {
            console.error('MessageBox element not found!');
            return;
        }

        // Pulisci qualsiasi timeout di nascondimento esistente
        if (messageBox.hideTimeout) {
            clearTimeout(messageBox.hideTimeout);
        }

        messageBox.textContent = message;
        messageBox.classList.remove('error'); // Rimuovi sempre prima
        if (isError) {
            messageBox.classList.add('error');
        }

        // Mostra il messaggio
        messageBox.classList.add('show');
        messageBox.style.visibility = 'visible'; // Assicurati la visibilità per la transizione

        // Nascondi dopo un ritardo
        messageBox.hideTimeout = setTimeout(() => {
            messageBox.classList.remove('show');
            // Dopo la transizione, imposta la visibilità su hidden
            messageBox.addEventListener('transitionend', function handler() {
                messageBox.style.visibility = 'hidden';
                messageBox.removeEventListener('transitionend', handler);
            }, { once: true });
        }, 3000); // Visibile per 3 secondi
    }

    /**
     * Formatta un numero come valuta.
     * @param {number} value - Il numero da formattare.
     * @returns {string} - Il valore formattato.
     */
    function formatCurrency(value) {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR'
        }).format(value);
    }
    
    /**
     * Aggiorna le date di scadenza per mostrare un testo dinamico (es. "Scade tra 5 giorni").
     */
    function updateDynamicExpiries() {
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Normalizza per confronti basati solo sulla data

        document.querySelectorAll('.dynamic-expiry').forEach(span => {
            const expiryDateStr = span.dataset.expiryDate;
            
            // Se non c'è data di scadenza o è invalida
            if (!expiryDateStr || expiryDateStr === '0000-00-00') {
                span.textContent = 'Nessuna scadenza';
                return;
            }

            const expiryDate = new Date(expiryDateStr);
            expiryDate.setHours(0, 0, 0, 0); // Normalizza anche la data di scadenza

            const diffTime = expiryDate.getTime() - today.getTime();
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            let dynamicText = '';
            span.style.fontWeight = 'normal';
            span.style.color = 'var(--text-light)';

            if (diffDays > 0) {
                dynamicText = `Scade tra ${diffDays} giorni`;
                if (diffDays <= 7) { // Evidenzia se scade a breve
                    span.style.color = 'var(--warning-color)';
                    span.style.fontWeight = '600';
                }
            } else if (diffDays === 0) {
                dynamicText = 'Scade oggi';
                span.style.color = 'var(--warning-color)';
                span.style.fontWeight = '600';
            } else {
                dynamicText = `Scaduto da ${Math.abs(diffDays)} giorni`;
                span.style.color = 'var(--danger-color)';
                span.style.fontWeight = '600';
            }
            
            span.textContent = dynamicText;
        });
    }

    // --- Script per Filtri e Popup ---
    (() => {
        const popup = document.getElementById('editBuonoRegaloPopup');
        const closeBtn = document.getElementById('close-edit-buono-regalo-popup-btn');
        const buonoRegaloEditForm = document.getElementById('edit-buono-regalo-form');
        const filterButtons = document.querySelectorAll('.filter-btn');
        const giftCards = document.querySelectorAll('.gift-card-item');
        const noDataMessage = document.querySelector('.no-data');

        // Campi del form di modifica
        const buonoIdInput = document.getElementById('edit_buono_id');
        const buonoValoreInput = document.getElementById('edit_buono_valore');
        const buonoCodiceInput = document.getElementById('edit_buono_codice');
        const buonoDestinatarioInput = document.getElementById('edit_buono_destinatario');
        const buonoDataScadenzaInput = document.getElementById('edit_buono_data_scadenza');
        const buonoMittenteNoteInput = document.getElementById('edit_buono_mittente_note');
        const buonoStatoSelect = document.getElementById('edit_buono_stato');
        const submitBtn = document.getElementById('edit-buono-submit-btn');

        const openPopup = () => popup.classList.add('visible');
        const closePopup = () => popup.classList.remove('visible');
        
        closeBtn.addEventListener('click', closePopup);
        
        popup.addEventListener('click', (e) => {
            if (e.target === popup) closePopup();
        });

        document.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.open-edit-buono-popup-btn');
            const printBtn = e.target.closest('.btn-print');

            if (editBtn) {
                e.preventDefault();
                buonoIdInput.value = editBtn.dataset.id;
                buonoValoreInput.value = parseFloat(editBtn.dataset.valore).toFixed(2);
                buonoCodiceInput.value = editBtn.dataset.nome;
                buonoDestinatarioInput.value = editBtn.dataset.destinatario === '-' ? '' : editBtn.dataset.destinatario;
                buonoDataScadenzaInput.value = editBtn.dataset.data_scadenza === '-' ? '' : editBtn.dataset.data_scadenza;
                buonoMittenteNoteInput.value = editBtn.dataset.note === '-' ? '' : editBtn.dataset.note;
                buonoStatoSelect.value = editBtn.dataset.stato;
                openPopup();
            } else if (printBtn) {
                e.preventDefault();
                const buonoId = printBtn.dataset.id;
                if (buonoId) {
                    window.open('stampa_buono.php?id=' + buonoId, '_blank');
                }
            }
        });

        buonoRegaloEditForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            if (!data.valore || parseFloat(data.valore) <= 0) {
                showMessage('Il valore del buono non può essere vuoto o minore/uguale a zero.', true);
                return;
            }

            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            try {
                const response = await fetch('update_buono.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (response.ok && result.success) {
                    showMessage(result.message || 'Buono regalo aggiornato con successo!', false);
                    closePopup();
                    setTimeout(() => location.reload(), 500); 
                } else {
                    showMessage(result.message || 'Errore durante l\'aggiornamento.', true);
                }
            } catch (error) {
                showMessage('Errore di comunicazione. Riprova.', true);
            } finally {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        });

        // Logica per i filtri
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                const filter = button.dataset.filter;
                
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                let visibleCards = 0;
                giftCards.forEach(card => {
                    const cardState = card.dataset.stato;
                    if (filter === 'tutti' || cardState === filter) {
                        card.style.display = 'flex';
                        visibleCards++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (noDataMessage) {
                    noDataMessage.style.display = visibleCards > 0 ? 'none' : 'block';
                }
            });
        });

    })();

    document.addEventListener('DOMContentLoaded', function() {
        // Questa parte gestisce solo i messaggi da sessione,
        // che vengono inseriti inline dallo script PHP
        <?php echo $message_from_session; ?>
        updateDynamicExpiries();
    });

</script>
</body>
</html>

