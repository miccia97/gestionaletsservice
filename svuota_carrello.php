<?php
session_start();
unset($_SESSION['carrello']); // Svuota il carrello
header('Location: index.php'); // Torna alla homepage o a una pagina specifica
exit;
