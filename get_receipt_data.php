<?php
// get_receipt_data.php

// Abilita il reporting degli errori per il debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Imposta l'header per indicare che la risposta sarà in formato JSON
header('Content-Type: application/json');

// Include il file di connessione al database
// Assicurati che 'db.php' si trovi nella stessa directory o che il percorso sia corretto.
if (!file_exists('db.php')) {
    echo json_encode(['success' => false, 'message' => 'Errore: File di configurazione del database (db.php) non trovato.']);
    exit;
}
require_once 'db.php';

// Controlla se la connessione al database è stata stabilita correttamente
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Errore di connessione al database: ' . ($conn->connect_error ?? 'Sconosciuto')]);
    exit;
}

// Recupera l'ID della riparazione dal corpo della richiesta POST
// Utilizza filter_input per una validazione e sanificazione sicura
$repairId = filter_input(INPUT_POST, 'repair_id', FILTER_VALIDATE_INT);

// Validazione dei dati ricevuti
if ($repairId === false || $repairId === null) {
    echo json_encode(['success' => false, 'message' => 'ID riparazione non valido.']);
    $conn->close();
    exit;
}

// Inizializza l'array per i dati della ricevuta
$receiptData = [
    'repair' => null,
    'client' => null,
    'movements' => []
];

try {
    // 1. Recupera i dettagli della riparazione e i dati del cliente associato
    // È importante recuperare l'IMEI se presente nella tabella 'riparazioni'
    // Rimossa la colonna 'c.cap' perché non esiste nel database
    $sql_repair = "SELECT r.*, c.nome, c.cognome, c.telefono AS client_telefono, c.indirizzo, c.citta, c.email
                   FROM riparazioni r
                   LEFT JOIN clienti_nuovo c ON r.cliente_id = c.id
                   WHERE r.id = ?";
    $stmt_repair = $conn->prepare($sql_repair);
    if ($stmt_repair === false) {
        throw new Exception("Errore nella preparazione della query riparazione: " . $conn->error);
    }
    $stmt_repair->bind_param("i", $repairId);
    $stmt_repair->execute();
    $result_repair = $stmt_repair->get_result();
    $repair = $result_repair->fetch_assoc();
    $stmt_repair->close();

    // Se la riparazione non è stata trovata, restituisci un errore
    if (!$repair) {
        echo json_encode(['success' => false, 'message' => 'Riparazione non trovata.']);
        $conn->close();
        exit;
    }

    // Popola i dati della riparazione, assicurandoti che 'imei' sia sempre presente (anche se null)
    $receiptData['repair'] = [
        'id' => $repair['id'],
        'diagnosi' => $repair['diagnosi'] ?? '',
        'modello' => $repair['modello'] ?? '',
        'data_creazione' => $repair['data_creazione'] ?? '',
        'stato' => $repair['stato'] ?? '',
        'costo_effettivo' => $repair['costo_effettivo'] ?? 0,
        'imei' => $repair['imei'] ?? 'N/D' // Aggiunto IMEI
    ];

    // Estrai i dati del cliente separatamente per chiarezza nel JSON di risposta
    $receiptData['client'] = [
        'nome' => $repair['nome'] ?? '',
        'cognome' => $repair['cognome'] ?? '',
        'telefono' => $repair['client_telefono'] ?? '', // Usa client_telefono per evitare conflitti con telefono della riparazione
        'indirizzo' => $repair['indirizzo'] ?? '',
        'citta' => $repair['citta'] ?? '',
        'cap' => '' ?? '', // Impostato a stringa vuota, dato che la colonna è stata rimossa dalla query
        'email' => $repair['email'] ?? ''
    ];


    // 2. Recupera i movimenti di magazzino associati a questa riparazione
    $sql_movements = "SELECT ram.prodotto_id, ram.quantita_movimentata, p.nome AS product_name
                      FROM riparazioni_articoli_movimenti ram
                      JOIN prodotti p ON ram.prodotto_id = p.id
                      WHERE ram.riparazione_id = ? AND ram.tipo_movimento = 'scarico_riparazione'
                      ORDER BY ram.data_movimento ASC"; // Ordina per data per una lista coerente
    $stmt_movements = $conn->prepare($sql_movements);
    if ($stmt_movements === false) {
        throw new Exception("Errore nella preparazione della query movimenti: " . $conn->error);
    }
    $stmt_movements->bind_param("i", $repairId);
    $stmt_movements->execute();
    $result_movements = $stmt_movements->get_result();
    $receiptData['movements'] = $result_movements->fetch_all(MYSQLI_ASSOC);
    $stmt_movements->close();

    // Invia la risposta JSON con i dati della ricevuta
    echo json_encode(['success' => true, 'data' => $receiptData]);

} catch (Exception $e) {
    // Logga l'errore per il debug lato server
    error_log("Errore in get_receipt_data.php: " . $e->getMessage());
    // Restituisci un messaggio di errore generico al client
    echo json_encode(['success' => false, 'message' => 'Errore del server durante il recupero dei dati: ' . $e->getMessage()]);
} finally {
    // Chiudi la connessione al database
    if (isset($conn)) {
        $conn->close();
    }
}
?>
