<?php
include('db.php');

// Recupera l'ID della vendita
$id_vendita = $_GET['id'];

// Recupera i dettagli della vendita
$query = "SELECT * FROM dettagli_vendita WHERE vendita_id = $id_vendita";
$result = $conn->query($query);
$vendita_query = "SELECT * FROM vendite WHERE id = $id_vendita";
$vendita_result = $conn->query($vendita_query);
$vendita = $vendita_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettagli Vendita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Dettagli della Vendita</h1>
        <p><strong>Cliente:</strong> <?= $vendita['cliente_id'] ?></p>
        <p><strong>Data Vendita:</strong> <?= $vendita['data_vendita'] ?></p>
        <p><strong>Totale:</strong> <?= $vendita['totale'] ?> €</p>

        <h2>Prodotti Venduti</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Prodotto</th>
                    <th>Quantità</th>
                    <th>Prezzo Unitario</th>
                    <th>Totale</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['prodotto_id'] ?></td>
                        <td><?= $row['quantita'] ?></td>
                        <td><?= $row['prezzo_unitario'] ?> €</td>
                        <td><?= $row['quantita'] * $row['prezzo_unitario'] ?> €</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="visualizza_vendite.php" class="btn btn-secondary">Torna alla lista vendite</a>
    </div>
</body>
</html>
