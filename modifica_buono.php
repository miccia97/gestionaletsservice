<?php
$host = 'localhost';
$db = 'gestionale_tsservice';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Controllo se l'ID è stato passato
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID buono non valido.");
}

$id = intval($_GET['id']);
$error = '';
$success = '';

// Se il form è stato inviato (metodo POST), aggiorno il record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $conn->real_escape_string($_POST['nome']);
    $valore = floatval($_POST['valore']);
    $data_scadenza = $conn->real_escape_string($_POST['data_scadenza']);
    $destinatario = $conn->real_escape_string($_POST['destinatario']);
    $note = $conn->real_escape_string($_POST['note']);

    $sql_update = "UPDATE buoni_regalo SET
        nome = '$nome',
        valore = $valore,
        data_scadenza = '$data_scadenza',
        destinatario = '$destinatario',
        note = '$note'
        WHERE id = $id";

    if ($conn->query($sql_update) === TRUE) {
        $success = "Buono regalo aggiornato con successo.";
    } else {
        $error = "Errore durante l'aggiornamento: " . $conn->error;
    }
}

// Recupero i dati aggiornati o esistenti
$sql = "SELECT * FROM buoni_regalo WHERE id = $id";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    die("Buono regalo non trovato.");
}

$buono = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Modifica Buono Regalo - TS Service</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f7f9fa;
    margin: 40px 20px 60px;
    color: #333;
  }
  main {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px 30px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 6px 20px rgb(0 0 0 / 0.1);
  }
  h1 {
    color: #28a745;
    text-align: center;
    margin-bottom: 30px;
    font-weight: 600;
  }
  form label {
    display: block;
    margin-top: 15px;
    font-weight: 600;
  }
  form input[type="text"],
  form input[type="number"],
  form input[type="date"],
  form textarea {
    width: 100%;
    padding: 10px 12px;
    margin-top: 6px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
    font-family: inherit;
    box-sizing: border-box;
  }
  form textarea {
    resize: vertical;
    min-height: 80px;
  }
  form button {
    margin-top: 25px;
   background: linear-gradient(135deg, #27ae60, #1e8449);
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.1rem;
    font-weight: 700;
    transition: background-color 0.3s ease;
  }
  form button:hover {
    background-color: #1e7e34;
  }
  .message {
    margin-top: 20px;
    padding: 15px;
    border-radius: 6px;
    font-weight: 600;
    text-align: center;
  }
  .error {
    background-color: #f8d7da;
    color: #842029;
  }
  .success {
    background-color: #d1e7dd;
    color: #0f5132;
  }
  a.back-link {
    display: inline-block;
    margin-top: 25px;
    text-decoration: none;
    color: #28a745;
    font-weight: 600;
    font-size: 1rem;
  }
  a.back-link:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>

<main>
  <h1>Modifica Buono Regalo</h1>

  <?php if ($error): ?>
  <div class="message error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
  <div class="message success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <label for="nome">Nome Buono</label>
    <input type="text" id="nome" name="nome" required value="<?= htmlspecialchars($buono['nome']) ?>" />

    <label for="valore">Valore (€)</label>
    <input type="number" step="0.01" min="0" id="valore" name="valore" required value="<?= htmlspecialchars($buono['valore']) ?>" />

    <label for="data_scadenza">Data Scadenza</label>
    <input type="date" id="data_scadenza" name="data_scadenza" value="<?= htmlspecialchars($buono['data_scadenza']) ?>" />

    <label for="destinatario">Destinatario</label>
    <input type="text" id="destinatario" name="destinatario" value="<?= htmlspecialchars($buono['destinatario']) ?>" />

    <label for="note">Note</label>
    <textarea id="note" name="note"><?= htmlspecialchars($buono['note']) ?></textarea>

    <button type="submit">Salva Modifiche</button>
  </form>

  <a href="visualizza_buoni.php" class="back-link">← Torna all'elenco Buoni Regalo</a>
</main>

</body>
</html>

<?php
$conn->close();
?>
