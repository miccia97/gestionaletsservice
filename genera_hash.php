<?php
// Definisci la password che vuoi usare per il tuo amministratore
// Scegli una password FORTE e NON FACILE da indovinare!
$password_chiaro = "LaTuaPasswordSuperSegreta123!"; 

// Genera l'hash della password usando PASSWORD_DEFAULT
// Questo è il metodo più sicuro e raccomandato
$hashed_password = password_hash($password_chiaro, PASSWORD_DEFAULT);

echo "La password in chiaro che hai scelto è: " . htmlspecialchars($password_chiaro) . "<br>";
echo "L'hash della password generato è: <strong>" . htmlspecialchars($hashed_password) . "</strong><br><br>";
echo "Copia l'hash (la stringa in grassetto) e incollala nel campo 'password' della tabella 'utenti' in PHPMyAdmin per il tuo utente amministratore.";
?>