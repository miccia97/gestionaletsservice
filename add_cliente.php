<?php
// add_cliente.php
// Gestisce l'aggiunta di un nuovo cliente al database 'clienti_nuovo'

header('Content-Type: application/json');

// Usa il file db.php centralizzato
require_once 'db.php';

// Ottieni i dati JSON inviati dalla richiesta POST
$input = file_get_contents('php://input');
$data = json_decode($input, true); // Decodifica il JSON in un array associativo

// Verifica se i dati obbligatori sono presenti (nome e cognome)
if (isset($data['nome']) && isset($data['cognome'])) {
    // Raccogli tutti i dati inviati, impostando a null se non presenti
    $nome = $data['nome'];
    $cognome = $data['cognome'];
    $email = $data['email'] ?? null;
    $telefono = $data['telefono'] ?? null;
    $indirizzo = $data['indirizzo'] ?? null;
    $citta = $data['citta'] ?? null;
    $note = $data['note'] ?? null;
    $partita_iva = $data['partita_iva'] ?? null;
    $ragione_sociale = $data['ragione_sociale'] ?? null;
    $indirizzo_azienda = $data['indirizzo_azienda'] ?? null;
    $citta_azienda = $data['citta_azienda'] ?? null;
    $telefono_azienda = $data['telefono_azienda'] ?? null;
    $email_azienda = $data['email_azienda'] ?? null;
    $note_azienda = $data['note_azienda'] ?? null;

    // Prepara l'istruzione SQL INSERT per la tabella 'clienti_nuovo'
    // Includi tutti i campi disponibili nella tabella
    $sql = "INSERT INTO clienti_nuovo (
                nome, cognome, email, telefono, indirizzo, citta, note,
                partita_iva, ragione_sociale, indirizzo_azienda, citta_azienda,
                telefono_azienda, email_azienda, note_azienda
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepara lo statement (per prevenire SQL Injection)
    if ($stmt = $conn->prepare($sql)) {
        // Collega i parametri
        // 'ssssssssssssss' indica che ci sono 14 parametri e tutti sono stringhe (s)
        $stmt->bind_param("ssssssssssssss",
            $nome,
            $cognome,
            $email,
            $telefono,
            $indirizzo,
            $citta,
            $note,
            $partita_iva,
            $ragione_sociale,
            $indirizzo_azienda,
            $citta_azienda,
            $telefono_azienda,
            $email_azienda,
            $note_azienda
        );

        // Esegui lo statement
        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            echo json_encode(["status" => "success", "message" => "Cliente '$nome $cognome' aggiunto con successo!", "id" => $newId]);
        } else {
            // Registra l'errore e restituisce una risposta JSON in caso di fallimento
            error_log("Errore nell'esecuzione della query INSERT in add_cliente.php: " . $stmt->error);
            echo json_encode(["status" => "error", "message" => "Errore durante l'aggiunta del cliente: " . $stmt->error]);
        }

        // Chiudi lo statement
        $stmt->close();
    } else {
        // Errore nella preparazione dell'istruzione SQL
        error_log("Errore nella preparazione della query SQL in add_cliente.php: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Errore interno del server durante la preparazione della query.", "details" => $conn->error]);
    }
} else {
    // Dati obbligatori mancanti nella richiesta
    echo json_encode(["status" => "error", "message" => "Dati cliente obbligatori (nome e cognome) mancanti nella richiesta."]);
}

// Chiudi la connessione al database (gestita da db.php)
// $conn->close();
?>
