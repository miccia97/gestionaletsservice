<?php
session_start();
$_SESSION['user_id'] = 3;
$_SESSION['username'] = 'miccia';
$_SESSION['user_name'] = 'Stefano';
$_SESSION['role'] = 'Amministratore';
$_SESSION['loggedin'] = true;

// Quick redirect to the real page
header('Location: storico_riparazioni.php');
