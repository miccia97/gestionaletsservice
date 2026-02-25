<?php
header('Content-Type: application/json');
session_start();
require_once 'db.php';

$response = ['success' => false, 'message' => 'Azione non valida.'];

if (!isset($conn)) {
    $response['message'] = 'Connessione al database non riuscita.';
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'save') {
        $id = $_POST['id'] ?? null;
        $cliente_id = $_POST['cliente_id'];
        $titolo = $_POST['titolo'];
        $descrizione = $_POST['descrizione'];
        $stato = $_POST['stato'];
        $priorita = $_POST['priorita'];

        if (empty($cliente_id) || empty($titolo)) {
            throw new Exception("Cliente e Titolo sono campi obbligatori.");
        }

        if (!empty($id)) { // Update
            $stmt = $conn->prepare("UPDATE tickets SET cliente_id=?, titolo=?, descrizione=?, stato=?, priorita=? WHERE id=?");
            $stmt->bind_param("issssi", $cliente_id, $titolo, $descrizione, $stato, $priorita, $id);
        } else { // Insert
            $stmt = $conn->prepare("INSERT INTO tickets (cliente_id, titolo, descrizione, stato, priorita) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $cliente_id, $titolo, $descrizione, $stato, $priorita);
        }

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Ticket salvato con successo.';
        } else {
            throw new Exception("Errore durante il salvataggio: " . $stmt->error);
        }
        $stmt->close();

    } elseif ($action === 'update_status') {
        $id = $_POST['id'] ?? null;
        $stato = $_POST['stato'] ?? null;
        
        if (empty($id) || empty($stato)) {
            throw new Exception("ID o nuovo stato non forniti.");
        }

        $stmt = $conn->prepare("UPDATE tickets SET stato = ? WHERE id = ?");
        $stmt->bind_param("si", $stato, $id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Stato del ticket aggiornato.';
        } else {
            throw new Exception("Errore durante l'aggiornamento dello stato: " . $stmt->error);
        }
        $stmt->close();
    }

} catch (Exception $e) {
    http_response_code(400); // Bad Request
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);

