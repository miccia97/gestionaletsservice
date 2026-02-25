<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/db.php';

$query = $_GET['q'] ?? '';

if (!empty($query)) {
    $stmt = $conn->prepare("SELECT id, nome, cognome, telefono FROM clienti_nuovo WHERE nome LIKE ? OR cognome LIKE ? LIMIT 10");
    $like = "%$query%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

    $clienti = [];
    while ($row = $result->fetch_assoc()) {
        $clienti[] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'cognome' => $row['cognome'],
            'telefono' => $row['telefono']
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($clienti);
}
?>
