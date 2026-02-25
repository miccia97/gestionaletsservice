<?php
// update_buono.php

// Abilita la visualizzazione degli errori per il debugging (rimuovere in produzione)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Connessione al database
$host = 'localhost';
$db   = 'gestionale_tsservice';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Errore di connessione al database: ' . $conn->connect_error]);
    exit;
}

// Prende i dati JSON dal corpo della richiesta
$data = json_decode(file_get_contents('php://input'), true);

// Verifica che i dati necessari siano presenti
if (!isset($data['id'], $data['valore'], $data['codice_buono'], $data['stato_buono'])) {
    echo json_encode(['success' => false, 'message' => 'Dati insufficienti per l\'aggiornamento.']);
    $conn->close();
    exit;
}

// Sanifica e valida i dati
$id = (int)$data['id'];
$valore = !empty($data['valore']) ? (float)$data['valore'] : 'NULL';
$codice_buono = $conn->real_escape_string($data['codice_buono']);
$destinatario = !empty($data['destinatario']) ? "'" . $conn->real_escape_string($data['destinatario']) . "'" : 'NULL';
$data_scadenza = !empty($data['data_scadenza']) ? "'" . $conn->real_escape_string($data['data_scadenza']) . "'" : 'NULL';
$note = !empty($data['mittente_note']) ? "'" . $conn->real_escape_string($data['mittente_note']) . "'" : 'NULL';
$stato = $conn->real_escape_string($data['stato_buono']);

// Costruisce la query di UPDATE
$sql = "UPDATE buoni_regalo SET
            nome = '$codice_buono',
            valore = $valore,
            destinatario = $destinatario,
            data_scadenza = $data_scadenza,
            note = $note,
            stato = '$stato'
        WHERE id = $id";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true, 'message' => 'Buono regalo aggiornato con successo!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento: ' . $conn->error]);
}

$conn->close();
?>