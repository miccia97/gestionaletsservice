<?php
echo "<h1>Ricevuto POST:</h1>";
echo "<pre>";
var_dump($_POST);
echo "</pre>";

// Verifica e mostra i dati
if (isset($_POST['id_cliente']) && isset($_POST['nome_cliente'])) {
    echo "<p>ID Cliente: " . htmlspecialchars($_POST['id_cliente']) . "</p>";
    echo "<p>Nome Cliente: " . htmlspecialchars($_POST['nome_cliente']) . "</p>";
} else {
    echo "<p>Dati mancanti</p>";
}
?>
