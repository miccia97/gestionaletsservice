<?php
include('db.php');

// Se arriva l'ID tramite GET, recupera i dati del prodotto
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM prodotti WHERE id = $id");
    $prodotto = $result->fetch_assoc();
}

// Se il form è stato inviato (POST), aggiorna il prodotto
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $descrizione = $_POST['descrizione'];
    $prezzo = $_POST['prezzo'];
    $quantita = $_POST['quantita'];
    $categoria = $_POST['categoria'];

    $query = "UPDATE prodotti SET 
                nome='$nome',
                descrizione='$descrizione',
                prezzo='$prezzo',
                quantita='$quantita',
                categoria='$categoria'
              WHERE id=$id";

    if ($conn->query($query) === TRUE) {
        echo "Prodotto aggiornato con successo.";
        echo "<br><a href='prodotti.php'>Torna all'elenco</a>";
        exit;
    } else {
        echo "Errore nell'aggiornamento: " . $conn->error;
    }
}
?>

<h1>Modifica Prodotto</h1>
<form method="POST" action="modifica_prodotto.php">
    <input type="hidden" name="id" value="<?= $prodotto['id'] ?>">
    <input type="text" name="nome" value="<?= $prodotto['nome'] ?>" placeholder="Nome" required><br>
    <textarea name="descrizione" placeholder="Descrizione"><?= $prodotto['descrizione'] ?></textarea><br>
    <input type="number" name="prezzo" step="0.01" value="<?= $prodotto['prezzo'] ?>" placeholder="Prezzo" required><br>
    <input type="number" name="quantita" value="<?= $prodotto['quantita'] ?>" placeholder="Quantità" required><br>
    <input type="text" name="categoria" value="<?= $prodotto['categoria'] ?>" placeholder="Categoria" required><br>
    <button type="submit">Salva modifiche</button>
</form>
