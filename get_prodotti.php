<?php
// get_prodotti.php
// Questo script gestisce la ricerca di prodotti nel database 'prodotti'
// e restituisce i risultati in formato JSON, allineato alla struttura della tabella prodotti fornita.

header('Content-Type: application/json'); // Indica che la risposta sarà JSON

// Configurazione del database
// *** SOSTITUISCI QUESTI VALORI CON LE TUE CREDENZIALI REALI DEL DATABASE MYSQL SE NECESSARIO ***
$servername = "localhost";
$username = "root"; // Esempio: "root"
$password = ""; // Esempio: "" (spesso vuota per root su XAMPP/MAMP)
$dbname = "gestionale_tsservice"; // Esempio: "my_gestionale_db"

// Connessione al database
$conn = new mysqli($servername, $username, $password, $dbname);

// Controlla la connessione
if ($conn->connect_error) {
    // In caso di errore, registra nel log e restituisce una risposta JSON
    error_log("Errore di connessione al database in get_prodotti.php: " . $conn->connect_error);
    echo json_encode(["status" => "error", "message" => "Impossibile connettersi al database. Contatta l'amministratore.", "details" => $conn->connect_error]);
    exit();
}

// Ottieni il termine di ricerca dalla query string (es. ?q=iphone)
$search_query = $_GET['q'] ?? ''; // Termine di ricerca

$products = []; // Array per i risultati

if (!empty($search_query)) {
    // Prepara la query SQL per cercare prodotti che contengono il termine di ricerca
    // La ricerca è fatta sulla colonna 'nome' della tabella 'prodotti'.
    // Selezioniamo anche 'id', 'imei', e 'prezzo_vendita1' per poterli mappare.
    $sql = "SELECT id, nome, imei, prezzo_vendita1 FROM prodotti WHERE nome LIKE ?";

    if ($stmt = $conn->prepare($sql)) {
        // Collega il parametro con un wildcard % per la ricerca parziale
        $search_param = '%' . $search_query . '%';
        $stmt->bind_param("s", $search_param);

        // Esegui lo statement
        if ($stmt->execute()) {
            $result = $stmt->get_result(); // Ottieni i risultati
            while ($row = $result->fetch_assoc()) {
                $products[] = [
                    'id_prodotto' => $row['id'],          // Mappa 'id' a 'id_prodotto' per compatibilità JS
                    'modello' => $row['nome'],            // Mappa 'nome' a 'modello' per compatibilità JS
                    'imei' => $row['imei'],
                    'prezzo_vendita' => $row['prezzo_vendita1'] // Mappa 'prezzo_vendita1' a 'prezzo_vendita' per compatibilità JS
                ];
            }
        } else {
            error_log("Errore nell'esecuzione della query SELECT in get_prodotti.php: " . $stmt->error);
            // In caso di errore di esecuzione, restituisce un array vuoto
        }
        $stmt->close();
    } else {
        error_log("Errore nella preparazione della query SQL in get_prodotti.php: " . $conn->error);
        // In caso di errore di preparazione, restituisce un array vuoto
    }
}

// Restituisce i prodotti trovati (o un array vuoto) in formato JSON
echo json_encode($products);

// Chiudi la connessione al database
$conn->close();
?>
