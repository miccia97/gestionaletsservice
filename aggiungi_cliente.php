<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('db.php');

// Se il modulo è stato inviato
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recupera i dati del modulo
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $indirizzo = $_POST['indirizzo'];

    // Query per inserire i dati nel database
    $query = "INSERT INTO clienti (nome, cognome, telefono, email, indirizzo) 
              VALUES ('$nome', '$cognome', '$telefono', '$email', '$indirizzo')";

    if ($conn->query($query) === TRUE) {
        echo "Cliente aggiunto con successo!";
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
    <title>Aggiungi Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Aggiungi Cliente</h1>
        <form method="POST" action="test_post.php">

            <input type="text" name="nome" placeholder="Nome" class="form-control" required><br>
            <input type="text" name="cognome" placeholder="Cognome" class="form-control"><br>
            <input type="text" name="telefono" placeholder="Telefono" class="form-control"><br>
            <input type="email" name="email" placeholder="Email" class="form-control"><br>
            <textarea name="indirizzo" placeholder="Indirizzo" class="form-control"></textarea><br>
            <button type="submit" class="btn btn-primary">Salva Cliente</button>
        </form>
    </div>
</body>
</html>
