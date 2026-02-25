<?php
// get_clienti.php
// Questo script gestisce la ricerca di clienti nel database
// e restituisce i risultati in formato JSON per l'autocompletamento.

header('Content-Type: application/json'); // Indica che la risposta sarà JSON

// Configurazione del database
// *** SOSTITUISCI QUESTI VALORI CON LE TUE CREDENZIALI REALI DEL DATABASE MYSQL ***
$servername = "localhost";
$username = "root"; // Esempio: "root"
$password = "";     // Esempio: "" (spesso vuota per root su XAMPP/MAMP)
$dbname = "gestionale_tsservice"; // Esempio: "my_gestionale_db"

// Connessione al database
$conn = new mysqli($servername, $username, $password, $dbname);

// Controlla la connessione
if ($conn->connect_error) {
    // In caso di errore, registra nel log e restituisce una risposta JSON
    error_log("Errore di connessione al database in get_clienti.php: " . $conn->connect_error);
    echo json_encode(["status" => "error", "message" => "Impossibile connettersi al database. Contatta l'amministratore.", "details" => $conn->connect_error]);
    exit();
}

// Ottieni il termine di ricerca dalla query string (es. ?q=rossi)
$search_query = $_GET['q'] ?? ''; // Termine di ricerca

$clients = []; // Array per i risultati

if (!empty($search_query)) {
    // Prepara la query SQL per cercare clienti che contengono il termine di ricerca
    // nelle colonne 'nome' o 'cognome'. Selezioniamo 'nome', 'cognome' e 'telefono'
    // per poterli combinare in 'nome_cliente' e passare il telefono.
    // Usiamo CONCAT_WS per combinare nome e cognome in un'unica stringa.
    $sql = "SELECT id, CONCAT_WS(' ', nome, cognome) AS nome_cliente, telefono FROM clienti_nuovo WHERE nome LIKE ? OR cognome LIKE ? OR CONCAT_WS(' ', nome, cognome) LIKE ? LIMIT 10";

    if ($stmt = $conn->prepare($sql)) {
        // Collega i parametri con un wildcard % per la ricerca parziale
        $search_param = '%' . $search_query . '%';
        $stmt->bind_param("sss", $search_param, $search_param, $search_param);

        // Esegui lo statement
        if ($stmt->execute()) {
            $result = $stmt->get_result(); // Ottieni i risultati
            while ($row = $result->fetch_assoc()) {
                $clients[] = [
                    'id' => $row['id'],
                    'nome_cliente' => $row['nome_cliente'], // Nome completo (Nome + Cognome)
                    'telefono' => $row['telefono']
                ];
            }
        } else {
            error_log("Errore nell'esecuzione della query SELECT in get_clienti.php: " . $stmt->error);
            // In caso di errore di esecuzione, restituisce un array vuoto
        }
        $stmt->close();
    } else {
        error_log("Errore nella preparazione della query SQL in get_clienti.php: " . $conn->error);
        // In caso di errore di preparazione, restituisce un array vuoto
    }
}

// Restituisce i clienti trovati (o un array vuoto) in formato JSON
echo json_encode($clients);

// Chiudi la connessione al database
$conn->close();
?>
