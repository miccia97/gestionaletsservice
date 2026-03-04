<?php
include('db.php');

$query = "SELECT * FROM clienti";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Gestione Clienti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Lista Clienti</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Cognome</th>
                    <th>Telefono</th>
                    <th>Email</th>
                    <th>Indirizzo</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['nome'] ?></td>
                    <td><?= $row['cognome'] ?></td>
                    <td><?= $row['telefono'] ?></td>
                    <td><?= $row['email'] ?></td>
                    <td><?= $row['indirizzo'] ?></td>
                    <td>
                        <a href="modifica_cliente.php?id=<?= $row['id'] ?>" class="btn btn-warning">Modifica</a>
                        <a href="elimina_cliente.php?id=<?= $row['id'] ?>" class="btn btn-danger">Elimina</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
