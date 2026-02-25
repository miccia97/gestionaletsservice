<?php
session_start();

if (isset($_POST['index']) && isset($_SESSION['carrello'])) {
    $index = intval($_POST['index']);
    
    if (array_key_exists($index, $_SESSION['carrello'])) {
        // Rimuove solo l'elemento specifico
        unset($_SESSION['carrello'][$index]);
        // Riordina gli indici per evitare "buchi"
        $_SESSION['carrello'] = array_values($_SESSION['carrello']);
    }
}

// Dopo la rimozione torna alla pagina precedente (ad esempio index.php)
header('Location: index.php');
exit;
?>
