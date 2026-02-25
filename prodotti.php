<?php
include('db.php'); // Connessione al database

// Recupera tutti i prodotti dal database
$query = "SELECT nome, descrizione, prezzo_vendita1, prezzo_vendita2, quantita, categoria, immagine FROM prodotti";
$result = $conn->query($query);

// Verifica se ci sono prodotti
if ($result->num_rows > 0) {
    // Aggiungi un po' di CSS per stilizzare la tabella
    echo "<style>
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            th, td {
                padding: 12px;
                text-align: left;
                border: 1px solid #ddd;
            }
            th {
                background-color: #f2f2f2;
            }
            td img {
                max-width: 100px;
                height: auto;
                display: block;
                margin: 0 auto;
            }
            .no-products {
                font-size: 18px;
                color: red;
                text-align: center;
            }
          </style>";

    echo "<table>";
    echo "<thead><tr>
            <th>Nome</th>
            <th>Descrizione</th>
            <th>Prezzo di vendita 1 (€)</th>
            <th>Prezzo di vendita 2 (€)</th>
            <th>Quantità</th>
            <th>Categoria</th>
            <th>Immagine</th>
          </tr></thead>";
    echo "<tbody>";

    // Itera attraverso i risultati e visualizza ogni prodotto
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
        echo "<td>" . htmlspecialchars($row['descrizione']) . "</td>";
        echo "<td>" . number_format($row['prezzo_vendita1'], 2) . " €</td>"; // Prezzo di vendita 1
        echo "<td>" . number_format($row['prezzo_vendita2'], 2) . " €</td>"; // Prezzo di vendita 2
        echo "<td>" . htmlspecialchars($row['quantita']) . "</td>";
        echo "<td>" . htmlspecialchars($row['categoria']) . "</td>";
        echo "<td><img src='" . htmlspecialchars($row['immagine']) . "' alt='Immagine prodotto'></td>";
        echo "</tr>";
    }

    echo "</tbody></table>";
} else {
    echo "<p class='no-products'>❌ Nessun prodotto trovato.</p>";
}

$conn->close(); // Chiude la connessione al database
?>
