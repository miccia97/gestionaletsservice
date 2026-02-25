<?php
// export_data.php
// Questo script gestisce l'esportazione dei dati in formato CSV.

// Abilita la visualizzazione degli errori per il debug (RIMUOVI IN PRODUZIONE!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Includi il file di connessione al database
// Assicurati che il percorso a 'db.php' sia corretto rispetto a questo file.
include 'db.php'; 

// Ottieni il tipo di esportazione dalla query string (es. ?type=products)
$export_type = $_GET['type'] ?? 'products'; // Default: esporta prodotti

try {
    // Imposta il nome del file per il download
    $filename = "export_{$export_type}_" . date('Ymd_His') . ".csv";

    // Imposta gli header HTTP per forzare il download del file CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Apri un output stream per scrivere il CSV
    $output = fopen('php://output', 'w');

    // Scrivi l'intestazione CSV e recupera i dati in base al tipo di esportazione
    switch ($export_type) {
        case 'products':
            // Intestazioni per i prodotti
            fputcsv($output, ['ID', 'Nome', 'Barcode', 'IMEI', 'Prezzo Vendita 1', 'Prezzo Vendita 2', 'Quantita', 'Categoria', 'Sottocategoria', 'Immagine', 'Descrizione', 'Prezzo Acquisto', 'Tipo Prodotto', 'Data Creazione']);
            // Query SQL per i prodotti
            $sql = "SELECT id, nome, barcode, imei, prezzo_vendita1, prezzo_vendita2, quantita, categoria, sottocategoria, immagine, descrizione, prezzo_acquisto, tipo_prodotto, data_creazione FROM prodotti";
            break;
        case 'categories':
            // Intestazioni per le categorie principali
            fputcsv($output, ['ID', 'Nome', 'Parent ID', 'Ordine Visualizzazione', 'Data Creazione', 'Ultimo Aggiornamento']);
            $sql = "SELECT id, nome, parent_category_id, display_order, created_at, updated_at FROM categorie";
            break;
        case 'subcategories':
            // Intestazioni per le sottocategorie
            fputcsv($output, ['ID', 'Nome', 'Parent ID', 'Ordine Visualizzazione', 'Data Creazione', 'Ultimo Aggiornamento']);
            $sql = "SELECT id, nome, parent_category_id, display_order, created_at, updated_at FROM sottocategorie";
            break;
        // Puoi aggiungere altri casi qui per esportare altri tipi di dati (es. 'sales', 'customers')
        // case 'sales':
        //     fputcsv($output, ['ID Vendita', 'Data', 'Totale', 'Cliente']);
        //     $sql = "SELECT id, sale_date, total_amount, customer_name FROM sales";
        //     break;
        default:
            // Caso di default se il tipo di esportazione non è riconosciuto
            fputcsv($output, ['Errore', 'Tipo di esportazione non valido']);
            fclose($output); // Chiudi il file e termina lo script
            exit();
    }

    // Esegui la query SQL
    $result = $conn->query($sql);

    if ($result) {
        // Scrivi ogni riga di dati nel file CSV
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    } else {
        // Logga l'errore se la query fallisce
        error_log("Errore durante l'esecuzione della query di esportazione per tipo {$export_type}: " . $conn->error);
        // Scrivi un messaggio di errore nel CSV se possibile
        fputcsv($output, ['Errore', 'Impossibile recuperare i dati: ' . $conn->error]);
    }

    // Chiudi l'output stream
    fclose($output);

} catch (Exception $e) {
    // Gestione delle eccezioni generali
    error_log("Eccezione in export_data.php: " . $e->getMessage());
    // Se gli header sono già stati inviati, non possiamo cambiarli,
    // quindi scriviamo l'errore direttamente nel CSV.
    if (!headers_sent()) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="export_error.csv"');
    }
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Errore', 'Si è verificato un errore durante l\'esportazione: ' . $e->getMessage()]);
    fclose($output);
} finally {
    // Chiudi la connessione al database
    if ($conn) {
        $conn->close();
    }
}
?>
