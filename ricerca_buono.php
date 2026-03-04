<?php
require_once 'db.php';

$query = isset($_GET['q']) ? $_GET['q'] : '';
$sql = "SELECT id, codice_buono, importo FROM buono_spesa WHERE codice_buono LIKE ?";
$stmt = $conn->prepare($sql);
$searchTerm = "%$query%";
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$output = [];
while ($row = $result->fetch_assoc()) {
  $output[] = [
    'id' => $row['id'],
    'codice_buono' => $row['codice_buono'],
    'importo' => $row['importo']
  ];
}

echo json_encode($output);
?>
