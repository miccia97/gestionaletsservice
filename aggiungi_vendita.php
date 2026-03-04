<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('db.php'); // Connessione al database

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Variabili per la vendita
    $cliente_id = $_POST['cliente_id']; // ID cliente
    $data_vendita = date('Y-m-d H:i:s'); // Data e ora della vendita

    // Calcola il totale della vendita
    $totale = 0;
    foreach ($_POST['prodotti'] as $prodotto_id => $quantita) {
        if ($quantita > 0) {
            $prezzo_unitario = $_POST['prezzi'][$prodotto_id];  // Prezzo unitario del prodotto
            $totale += $prezzo_unitario * $quantita;  // Somma al totale
        }
    }

    // Query per inserire la vendita nella tabella 'vendite' con il totale
    $query_vendita = "INSERT INTO vendite (cliente_id, data_vendita, totale) 
                      VALUES ('$cliente_id', '$data_vendita', '$totale')";
    if ($conn->query($query_vendita) === TRUE) {
        // Recupera l'ID dell'ultima vendita inserita
        $vendita_id = $conn->insert_id;

        // Aggiungi i dettagli della vendita (prodotti e quantità)
        foreach ($_POST['prodotti'] as $prodotto_id => $quantita) {
            if ($quantita > 0) { // Verifica che la quantità sia maggiore di zero
                $prezzo_unitario = $_POST['prezzi'][$prodotto_id];  // Prezzo unitario del prodotto
                // Query per inserire i dettagli della vendita
                $query_dettagli = "INSERT INTO vendite_dettagli (id_vendita, id_prodotto, quantita, prezzo_unitario) 
                                   VALUES ('$vendita_id', '$prodotto_id', '$quantita', '$prezzo_unitario')";
                if (!$conn->query($query_dettagli)) {
                    echo "Errore nell'inserimento dei dettagli della vendita: " . $conn->error;
                }
            }
        }
        echo "Vendita registrata con successo!";
        echo "<br><a href='clienti.php'>Vai alla lista clienti</a>";
    } else {
        echo "Errore nell'inserimento della vendita: " . $conn->error;
    }
}
?>

<h1>Registra Vendita</h1>
<form method="POST" action="aggiungi_vendita.php">
    <label for="cliente_id">Cliente:</label>
    <select name="cliente_id" required>
        <?php
        // Recupera i clienti dal database
        $query_clienti = "SELECT * FROM clienti";
        $result_clienti = $conn->query($query_clienti);
        while ($cliente = $result_clienti->fetch_assoc()) {
            echo "<option value='" . $cliente['id'] . "'>" . $cliente['nome'] . " " . $cliente['cognome'] . "</option>";
        }
        ?>
    </select><br>

    <h3>Prodotti da acquistare:</h3>
    <?php
    // Recupera i prodotti dal database
    $query_prodotti = "SELECT * FROM prodotti";
    $result_prodotti = $conn->query($query_prodotti);
    while ($prodotto = $result_prodotti->fetch_assoc()) {
        ?>
        <label for="prodotti[<?= $prodotto['id'] ?>]"><?= $prodotto['nome'] ?> (<?= $prodotto['prezzo'] ?> €):</label>
        <input type="number" name="prodotti[<?= $prodotto['id'] ?>]" min="0" value="0"><br>
        <input type="hidden" name="prezzi[<?= $prodotto['id'] ?>]" value="<?= $prodotto['prezzo'] ?>">
        <?php
    }
    ?>

    <button type="submit">Registra Vendita</button>
</form>
