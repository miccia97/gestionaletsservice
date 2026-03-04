<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('db.php');

// Recupera l'ID del cliente dalla query string
$id_cliente = $_GET['id'];

// Recupera i dati del cliente dal database
$query = "SELECT * FROM clienti WHERE id = $id_cliente";
$result = $conn->query($query);
$cliente = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recupera i nuovi valori dal modulo
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $indirizzo = $_POST['indirizzo'];

    // Query per aggiornare i dati del cliente
    $query = "UPDATE clienti SET nome = '$nome', cognome = '$cognome', telefono = '$telefono', email = '$email', indirizzo = '$indirizzo' WHERE id = $id_cliente";

    if ($conn->query($query) === TRUE) {
        echo "Cliente aggiornato con successo!";
        echo "<br><a href='clienti.php'>Vai alla lista clienti</a>";
    } else {
        echo "Errore: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Modifica Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Modifica Cliente</h1>
        <form method="POST" action="modifica_cliente.php?id=<?= $cliente['id'] ?>">
            <input type="text" name="nome" placeholder="Nome" class="form-control" value="<?= $cliente['nome'] ?>" required><br>
            <input type="text" name="cognome" placeholder="Cognome" class="form-control" value="<?= $cliente['cognome'] ?>"><br>
            <input type="text" name="telefono" placeholder="Telefono" class="form-control" value="<?= $cliente['telefono'] ?>"><br>
            <input type="email" name="email" placeholder="Email" class="form-control" value="<?= $cliente['email'] ?>"><br>
            <textarea name="indirizzo" placeholder="Indirizzo" class="form-control"><?= $cliente['indirizzo'] ?></textarea><br>
            <button type="submit" class="btn btn-primary">Salva Modifiche</button>
        </form>
    </div>
</body>
</html>

