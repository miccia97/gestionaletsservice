<?php
// salva_permuta.php
// Questo script gestisce il salvataggio dei dati della permuta nel database.

header('Content-Type: application/json'); // La risposta sarà JSON

// Configurazione del database
// *** SOSTITUISCI QUESTI VALORI CON LE TUE CREDENZIALI REALI DEL DATABASE MYSQL ***
$servername = "localhost";
$username = "root"; // Esempio: "root"
$password = "";     // Esempio: "" (spesso vuota per root su XAMPP/MAMP)
$dbname = "gestionale_tsservice"; // Esempio: "my_gestionale_db"

// Connessione al database
$conn = new mysqli($servername, $username, $password, $dbname);

// Controlla la connessione
if ($conn->connect_error) {
    error_log("Errore di connessione al database in salva_permuta.php: " . $conn->connect_error);
    echo json_encode(["status" => "error", "message" => "Impossibile connettersi al database. Contatta l'amministratore.", "details" => $conn->connect_error]);
    exit();
}

// Inizia una transazione per assicurare l'integrità dei dati
$conn->begin_transaction();

try {
    // --- Generazione Numero Progressivo Permuta ---
    // Recupera l'ultimo numero progressivo e incrementa
    $sql_get_last_id = "SELECT MAX(progressivo) AS last_id FROM permute_nuovo"; // Usiamo 'progressivo' come da DB
    $result_last_id = $conn->query($sql_get_last_id);
    if (!$result_last_id) {
        throw new Exception("Errore nel recupero dell'ultimo ID progressivo: " . $conn->error);
    }
    $row_last_id = $result_last_id->fetch_assoc();
    // Il progressivo nel DB è varchar, quindi gestiamo il caso che sia una stringa numerica
    $next_progressive_number = ($row_last_id['last_id'] ? (int)filter_var($row_last_id['last_id'], FILTER_SANITIZE_NUMBER_INT) + 1 : 1);
    // Formatting per la colonna progressivo (VARCHAR)
    $formatted_progressive_number = "PMT-" . str_pad($next_progressive_number, 5, '0', STR_PAD_LEFT);


    // --- Raccolta Dati dal Form ---
    // Dettagli Generali
    $data_permuta = $_POST['data_permuta'] ?? ''; // Sarà mappato a colonna 'data'
    // Il cliente_id non è presente nella tabella permute_nuovo, quindi non lo useremo per l'INSERT
    // $cliente_id = isset($_POST['cliente_id']) && $_POST['cliente_id'] !== '' ? (int)$_POST['cliente_id'] : null;
    $cliente_display_name = $_POST['cliente'] ?? ''; // Mappato a colonna 'cliente'
    $telefono_cliente = $_POST['telefono_cliente'] ?? ''; // Mappato a colonna 'telefono'
    $stato_permuta = $_POST['stato_permuta'] ?? 'In Trattativa'; // Mappato a colonna 'status'

    // Il Tuo Prodotto (Ceduto al Cliente)
    $tuo_modello = $_POST['tuo_modello'] ?? ''; // Mappato a colonna 'modello_nuovo'
    $tuo_imei = $_POST['tuo_imei'] ?? ''; // Mappato a colonna 'imei_nuovo'
    $tuo_valore_vendita = filter_var($_POST['tuo_valore_vendita'] ?? 0, FILTER_VALIDATE_FLOAT); // Mappato a colonna 'prezzo_nuovo'
    $tuo_note = $_POST['tuo_note'] ?? ''; // Mappato a colonna 'note_nuovo'

    // Prodotto del Cliente (Ricevuto in Permuta)
    $cliente_modello = $_POST['cliente_modello'] ?? ''; // Mappato a colonna 'modello_usato'
    $cliente_imei = $_POST['cliente_imei'] ?? ''; // Mappato a colonna 'imei_usato'
    $cliente_note = $_POST['cliente_note'] ?? ''; // Mappato a colonna 'note_usato'
    $cliente_valore_permuta = filter_var($_POST['cliente_valore_permuta'] ?? 0, FILTER_VALIDATE_FLOAT); // Mappato a colonna 'prezzo_permuta'

    // Valutazione Tecnica del Dispositivo - Consolidiamo in JSON per 'test_ok'
    $test_data = [
        'display' => ['esito' => $_POST['test_display_esito'] ?? '', 'note' => $_POST['test_display_note'] ?? ''],
        'touch' => ['esito' => $_POST['test_touch_esito'] ?? '', 'note' => $_POST['test_touch_note'] ?? ''],
        'batteria' => ['esito' => $_POST['test_batteria_esito'] ?? '', 'note' => $_POST['test_batteria_note'] ?? ''],
        'cam_post' => ['esito' => $_POST['test_cam_post_esito'] ?? '', 'note' => $_POST['test_cam_post_note'] ?? ''],
        'cam_ant' => ['esito' => $_POST['test_cam_ant_esito'] ?? '', 'note' => $_POST['test_cam_ant_note'] ?? ''],
        'audio' => ['esito' => $_POST['test_audio_esito'] ?? '', 'note' => $_POST['test_audio_note'] ?? ''],
        'mic' => ['esito' => $_POST['test_mic_esito'] ?? '', 'note' => $_POST['test_mic_note'] ?? ''],
        'wifi' => ['esito' => $_POST['test_wifi_esito'] ?? '', 'note' => $_POST['test_wifi_note'] ?? ''],
        'bt' => ['esito' => $_POST['test_bt_esito'] ?? '', 'note' => $_POST['test_bt_note'] ?? ''],
        'ricarica' => ['esito' => $_POST['test_ricarica_esito'] ?? '', 'note' => $_POST['test_ricarica_note'] ?? ''],
        'tasti' => ['esito' => $_POST['test_tasti_esito'] ?? '', 'note' => $_POST['test_tasti_note'] ?? ''],
        'sensori' => ['esito' => $_POST['test_sensori_esito'] ?? '', 'note' => $_POST['test_sensori_note'] ?? ''],
        'sblocco_bio' => ['esito' => $_POST['test_sblocco_bio_esito'] ?? '', 'note' => $_POST['test_sblocco_bio_note'] ?? ''],
        'reset_fabbrica' => ['esito' => (isset($_POST['test_reset_fabbrica']) ? 'Si' : 'No'), 'note' => $_POST['test_reset_fabbrica_note'] ?? ''],
        'accounts' => ['esito' => $_POST['test_accounts_esito'] ?? '', 'note' => $_POST['test_accounts_note'] ?? ''],
        'altro' => ['esito' => $_POST['test_altro_esito'] ?? '', 'note' => $_POST['test_altro_note'] ?? '']
    ];
    $test_ok_json = json_encode($test_data); // Mappato a colonna 'test_ok'

    // Costi di Ricondizionamento (array di descrizioni e importi)
    $costo_descrizioni = $_POST['costo_descrizione'] ?? [];
    $costo_importi = $_POST['costo_importo'] ?? [];
    // Converti l'array di costi in JSON se necessario per una colonna separata, altrimenti usa solo il totale
    // Non c'è una colonna esplicita per questo JSON nella tua tabella, useremo solo il totale
    // $costi_ricondizionamento_json = json_encode(array_map(null, $costo_descrizioni, $costo_importi));

    // Calcoli Finali (valori inviati dal client, ma idealmente ricalcolati anche lato server per sicurezza)
    $totale_costi_ricondizionamento = filter_var($_POST['totale_costi_ricondizionamento_val'] ?? 0, FILTER_VALIDATE_FLOAT); // Mappato a colonna 'costo_riparazione'
    // $valore_netto_ricevuto non ha una colonna corrispondente, è un valore intermedio
    // $valore_netto_ricevuto = filter_var($_POST['valore_netto_ricevuto_val'] ?? 0, FILTER_VALIDATE_FLOAT);
    $conguaglio_cliente = filter_var($_POST['conguaglio_cliente_val'] ?? 0, FILTER_VALIDATE_FLOAT); // Mappato a colonna 'differenza'

    // Nuovi campi aggiunti dal form
    $costo_accessori = filter_var($_POST['costo_accessori_input'] ?? 0, FILTER_VALIDATE_FLOAT); // Mappato a colonna 'costo_accessori'
    $costo_prodotto = filter_var($_POST['costo_prodotto_input'] ?? 0, FILTER_VALIDATE_FLOAT); // Mappato a colonna 'costo_prodotto'
    $prezzo_vendita_finale = filter_var($_POST['prezzo_vendita_input'] ?? 0, FILTER_VALIDATE_FLOAT); // Mappato a colonna 'prezzo_vendita'
    $note_generali_aggiuntive = $_POST['note_generali_input'] ?? ''; // Mappato a colonna 'note_generali'

    // Gestione Caricamento File (Simulato) ---
    $upload_dir = "uploads/"; // Directory di destinazione per gli upload (deve esistere e avere permessi di scrittura)
    $tuo_foto_paths = [];
    $cliente_foto_paths = [];

    // Gestione foto del "Tuo Prodotto"
    if (isset($_FILES['tuo_foto']) && !empty($_FILES['tuo_foto']['name'][0])) {
        foreach ($_FILES['tuo_foto']['name'] as $key => $name) {
            $tmp_name = $_FILES['tuo_foto']['tmp_name'][$key];
            // In un ambiente reale, qui faresti move_uploaded_file()
            // Per questa simulazione, generiamo solo un percorso fittizio
            $simulated_path = $upload_dir . uniqid() . "_" . basename($name);
            $tuo_foto_paths[] = $simulated_path;
        }
    }

    // Gestione foto del "Prodotto del Cliente"
    if (isset($_FILES['cliente_foto']) && !empty($_FILES['cliente_foto']['name'][0])) {
        foreach ($_FILES['cliente_foto']['name'] as $key => $name) {
            $tmp_name = $_FILES['cliente_foto']['tmp_name'][$key];
            // In un ambiente reale, qui faresti move_uploaded_file()
            // Per questa simulazione, generiamo solo un percorso fittizio
            $simulated_path = $upload_dir . uniqid() . "_" . basename($name);
            $cliente_foto_paths[] = $simulated_path;
        }
    }

    // Converti gli array di percorsi in stringhe JSON per il salvataggio nel database
    $tuo_foto_paths_json = json_encode($tuo_foto_paths);
    $cliente_foto_paths_json = json_encode($cliente_foto_paths);


    // --- Inserimento nel Database ---
    // Modificato INSERT INTO permute_nuovo e nomi delle colonne per rispecchiare la tabella
    $sql_insert_permuta = "INSERT INTO permute_nuovo (
        progressivo, data, cliente, telefono, status,
        modello_nuovo, imei_nuovo, note_nuovo, prezzo_nuovo,
        modello_usato, imei_usato, note_usato, prezzo_permuta,
        costo_riparazione, costo_accessori, costo_prodotto, differenza, prezzo_vendita,
        test_ok, note_generali, foto_ceduto_paths, foto_ricevuto_paths
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";


    $stmt = $conn->prepare($sql_insert_permuta);

    if (!$stmt) {
        throw new Exception("Errore nella preparazione della query SQL: " . $conn->error);
    }

    // Stringa dei tipi per bind_param (22 caratteri totali):
    // s (progressivo)
    // s (data)
    // s (cliente)
    // s (telefono)
    // s (status)
    // s (modello_nuovo)
    // s (imei_nuovo)
    // s (note_nuovo)
    // d (prezzo_nuovo)
    // s (modello_usato)
    // s (imei_usato)
    // s (note_usato)
    // d (prezzo_permuta)
    // d (costo_riparazione)
    // d (costo_accessori)
    // d (costo_prodotto)
    // d (differenza)
    // d (prezzo_vendita)
    // s (test_ok - JSON string)
    // s (note_generali)
    // s (foto_ceduto_paths)
    // s (foto_ricevuto_paths)
    $type_string_for_bind = "sssssssdsdsssddddddssss"; // 22 caratteri, senza spazi

    // Debugging: Log counts before binding
    error_log("DEBUG: SQL_INSERT_PERMUTA: " . $sql_insert_permuta); // Log the full SQL query

    $columns_in_insert = 0;
    $column_list_debug = '';
    if (preg_match('/INSERT INTO permute_nuovo \((.*?)\) VALUES/', $sql_insert_permuta, $matches)) {
        $column_list_debug = $matches[1];
        $columns_in_insert = count(explode(', ', $column_list_debug));
    }
    error_log("DEBUG: Extracted column list: '" . $column_list_debug . "'");
    error_log("DEBUG: Calculated columns in INSERT: " . $columns_in_insert);

    $type_string_length = strlen($type_string_for_bind);
    $num_bound_variables = count([
        $formatted_progressive_number, $data_permuta, $cliente_display_name, $telefono_cliente, $stato_permuta,
        $tuo_modello, $tuo_imei, $tuo_note, $tuo_valore_vendita,
        $cliente_modello, $cliente_imei, $cliente_note, $cliente_valore_permuta,
        $totale_costi_ricondizionamento, $costo_accessori, $costo_prodotto, $conguaglio_cliente, $prezzo_vendita_finale,
        $test_ok_json, $note_generali_aggiuntive, $tuo_foto_paths_json, $cliente_foto_paths_json
    ]);

    error_log("DEBUG: bind_param type string: '" . $type_string_for_bind . "'"); // Log the type string
    error_log("DEBUG: bind_param type string length: " . $type_string_length);
    error_log("DEBUG: Number of variables bound (counted from array): " . $num_bound_variables);

    if ($columns_in_insert !== $num_bound_variables || $type_string_length !== $num_bound_variables) {
        error_log("DEBUG: Mismatch detected in counts before binding parameters! SQL Columns: $columns_in_insert, Bound Variables: $num_bound_variables, Type String Length: $type_string_length");
        throw new Exception("Mismatch in column/value count or type string length. Check server logs for details.");
    }

    $stmt->bind_param($type_string_for_bind,
        $formatted_progressive_number, $data_permuta, $cliente_display_name, $telefono_cliente, $stato_permuta,
        $tuo_modello, $tuo_imei, $tuo_note, $tuo_valore_vendita,
        $cliente_modello, $cliente_imei, $cliente_note, $cliente_valore_permuta,
        $totale_costi_ricondizionamento, $costo_accessori, $costo_prodotto, $conguaglio_cliente, $prezzo_vendita_finale,
        $test_ok_json, $note_generali_aggiuntive, $tuo_foto_paths_json, $cliente_foto_paths_json
    );

    if (!$stmt->execute()) {
        throw new Exception("Errore nell'esecuzione della query INSERT: " . $stmt->error);
    }

    $conn->commit(); // Conferma la transazione
    echo json_encode(["status" => "success", "message" => "Permuta salvata con successo!", "new_id" => $formatted_progressive_number]);

} catch (Exception $e) {
    $conn->rollback(); // Annulla la transazione in caso di errore
    error_log("Errore durante il salvataggio della permuta: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Errore durante il salvataggio della permuta: " . $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>
