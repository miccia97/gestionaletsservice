<?php
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Avvia la sessione PHP all'inizio dello script (se necessario, per messaggi o autenticazione)
session_start();

// Includi il file di connessione al database MySQL
if (!file_exists('db.php')) {
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: Il file db.php non è stato trovato!</div>";
    exit;
}
require_once 'db.php';

// Controlla subito se c'è stato un errore di connessione al database da db.php
if (isset($db_connection_error) && $db_connection_error !== null) {
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: La connessione al database non è stata stabilita correttamente! " . htmlspecialchars($db_connection_error, ENT_QUOTES, 'UTF-8') . "</div>";
    exit;
}

$reservation_id = $_GET['id'] ?? null;
$reservation_details = null;
$client_details = null;

if ($reservation_id === null || !is_numeric($reservation_id)) {
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>ID prenotazione non fornito o non valido.</div>";
    exit;
}

try {
    // 1. Recupera i dettagli della prenotazione
    $stmt_reservation = $conn->prepare("SELECT * FROM prenotazioni_prodotti WHERE id = ?");
    if ($stmt_reservation === false) {
        throw new mysqli_sql_exception("Errore nella preparazione della query di prenotazione: " . $conn->error);
    }
    $stmt_reservation->bind_param('i', $reservation_id);
    $stmt_reservation->execute();
    $result_reservation = $stmt_reservation->get_result();
    $reservation_details = $result_reservation->fetch_assoc();
    $stmt_reservation->close();

    if (!$reservation_details) {
        echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Prenotazione con ID " . htmlspecialchars($reservation_id) . " non trovata.</div>";
        exit;
    }

    // 2. Recupera i dettagli del cliente (se un client_id è presente)
    if (!empty($reservation_details['client_id'])) {
        $stmt_client = $conn->prepare("SELECT * FROM clienti_nuovo WHERE id = ?");
        if ($stmt_client === false) {
            throw new mysqli_sql_exception("Errore nella preparazione della query del cliente: " . $conn->error);
        }
        $stmt_client->bind_param('i', $reservation_details['client_id']);
        $stmt_client->execute();
        $result_client = $stmt_client->get_result();
        $client_details = $result_client->fetch_assoc();
        $stmt_client->close();
    }

} catch (mysqli_sql_exception $e) {
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore database: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
    error_log("Errore Stampa Prenotazione (SQL): " . $e->getMessage() . " - Stack: " . $e->getTraceAsString());
    exit;
} catch (Exception $e) {
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore generico: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
    error_log("Errore Stampa Prenotazione (Generale): " . $e->getMessage() . " - Stack: " . $e->getTraceAsString());
    exit;
}

// Funzione helper per formattare la valuta
function formatCurrency($value) {
    return number_format($value, 2, ',', '.') . ' €';
}

// Funzione per generare il blocco dei dettagli della prenotazione e del cliente
function generateReservationBlock($type, $reservation_details, $client_details) {
    ob_start(); // Inizia a catturare l'output
    ?>
    <div class="p-8">
        <div class="flex justify-between items-center mb-6">
            <!-- Company Logo/Name and Contact Info -->
            <div class="text-xl font-bold text-gray-800">
                TS SERVICE
                <div class="text-sm font-normal text-gray-600 mt-1">
                    Contrada Castromurro - 217<br>
                    87021 BELVEDERE M.MO (CS)<br>
                    Tel. 3420330279
                </div>
                <!-- Oppure un'immagine: <img src="https://placehold.co/150x50/e0e0e0/000000?text=LOGO" alt="Logo Azienda" class="h-12"> -->
            </div>
            <div class="text-right">
                <!-- Rimosse le scritte "Copia Cliente" e "Copia Azienda" -->
                <h1 class="text-2xl font-bold text-gray-800 mb-1"></h1>
                <!-- Rimossa la Data Stampa -->
            </div>
        </div>

        <div class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-700 mb-3">Dettagli Prenotazione #<?php echo htmlspecialchars($reservation_details['id']); ?></h2>
            <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                <div><span class="font-medium text-gray-600">Prodotto:</span> <span class="text-gray-800"><?php echo htmlspecialchars($reservation_details['product_name']); ?></span></div>
                <div><span class="font-medium text-gray-600">Data Prenotazione:</span> <span class="text-gray-800"><?php echo htmlspecialchars(date('d/m/Y', strtotime($reservation_details['reservation_date']))); ?></span></div>
                <div><span class="font-medium text-gray-600">Quantità:</span> <span class="text-gray-800"><?php echo htmlspecialchars($reservation_details['quantity']); ?></span></div>
                <div><span class="font-medium text-gray-600">Prezzo Unitario:</span> <span class="text-gray-800"><?php echo formatCurrency($reservation_details['unit_price']); ?></span></div>
                <div class="col-span-2"><span class="font-medium text-gray-600">Prezzo Totale:</span> <span class="text-gray-800 font-bold text-lg"><?php echo formatCurrency($reservation_details['product_total_price']); ?></span></div>
                <div><span class="font-medium text-gray-600">Acconto Versato:</span> <span class="text-gray-800"><?php echo formatCurrency($reservation_details['deposit_amount']); ?></span></div>
                <div><span class="font-medium text-gray-600">Totale da Dare:</span> <span class="text-blue-700 font-bold text-lg"><?php echo formatCurrency($reservation_details['remaining_amount']); ?></span></div>
                <!-- Rimossa la riga per lo Stato -->
            </div>
        </div>

        <div class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-700 mb-3">Dettagli Cliente</h2>
            <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                <div><span class="font-medium text-gray-600">Nome:</span> <span class="text-gray-800"><?php echo htmlspecialchars($reservation_details['customer_name']); ?></span></div>
                <?php if (!empty($reservation_details['customer_phone'])): ?>
                    <div><span class="font-medium text-gray-600">Telefono:</span> <span class="text-gray-800"><?php echo htmlspecialchars($reservation_details['customer_phone']); ?></span></div>
                <?php endif; ?>
                <?php if (!empty($reservation_details['customer_secondary_phone'])): ?>
                    <div><span class="font-medium text-gray-600">Tel. Secondario:</span> <span class="text-gray-800"><?php echo htmlspecialchars($reservation_details['customer_secondary_phone']); ?></span></div>
                <?php endif; ?>
                <?php if (!empty($reservation_details['customer_email'])): ?>
                    <div><span class="font-medium text-gray-600">Email:</span> <span class="text-gray-800"><?php echo htmlspecialchars($reservation_details['customer_email']); ?></span></div>
                <?php endif; ?>
                <?php if ($client_details): // Dettagli dal record cliente se esistente ?>
                    <?php if (!empty($client_details['ragione_sociale'])): ?>
                        <div class="col-span-2"><span class="font-medium text-gray-600">Ragione Sociale:</span> <span class="text-gray-800"><?php echo htmlspecialchars($client_details['ragione_sociale']); ?></span></div>
                    <?php endif; ?>
                    <?php if (!empty($client_details['partita_iva'])): ?>
                        <div><span class="font-medium text-gray-600">Partita IVA:</span> <span class="text-gray-800"><?php echo htmlspecialchars($client_details['partita_iva']); ?></span></div>
                    <?php endif; ?>
                    <?php if (!empty($client_details['indirizzo']) || !empty($client_details['citta'])): ?>
                        <div class="col-span-2"><span class="font-medium text-gray-600">Indirizzo:</span> <span class="text-gray-800"><?php echo htmlspecialchars(trim($client_details['indirizzo'] . (!empty($client_details['indirizzo']) && !empty($client_details['citta']) ? ', ' : '') . $client_details['citta'])); ?></span></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($reservation_details['notes'])): ?>
            <div class="notes-section p-4 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-sm font-medium text-gray-700 mb-2">Note Aggiuntive:</p>
                <p class="text-sm text-gray-800 leading-relaxed"><?php echo nl2br(htmlspecialchars($reservation_details['notes'])); ?></p>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean(); // Restituisci l'output catturato
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Stampa Prenotazione #<?php echo htmlspecialchars($reservation_details['id']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5; /* Light gray background */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Align to top for longer content */
            min-height: 100vh;
        }
        .print-container {
            width: 21cm; /* A4 width */
            min-height: 29.7cm; /* A4 height, will expand if content is longer */
            background-color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); /* More pronounced shadow */
            display: flex;
            flex-direction: column;
            margin: 2cm auto; /* Centered with top/bottom margin */
        }

        .half-page {
            width: 100%;
            height: 14.85cm; /* Exactly half of A4 height */
            box-sizing: border-box;
            border-bottom: 1px dashed #cbd5e0; /* Light dashed line for cutting/folding */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .half-page:last-child {
            border-bottom: none; /* No border for the last half */
        }

        .notes-section {
            margin-top: 1rem; /* Adjust if needed */
        }

        /* Specific styles for printing */
        @media print {
            body {
                background-color: white;
                display: block;
                margin: 0;
                padding: 0;
                min-height: auto;
            }
            .print-container {
                width: 21cm;
                height: 29.7cm; /* Enforce A4 size for print */
                box-shadow: none;
                margin: 0;
                padding: 0;
                overflow: hidden; /* Hide scrollbars if content overflows print area */
            }
            .half-page {
                height: 14.85cm; /* Exactly half A4 for print */
                border-bottom: 1px dashed #a0aec0; /* Slightly darker dashed line for print */
                page-break-after: avoid; /* Prevent page break within a half-page */
            }
            .half-page:last-child {
                border-bottom: none;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Prima Metà: Copia per il Cliente -->
        <div class="half-page">
            <?php echo generateReservationBlock('client', $reservation_details, $client_details); ?>
        </div>

        <!-- Linea di taglio/piega visibile solo a schermo -->
        <div class="text-center text-xs text-gray-500 py-1 no-print bg-gray-100 border-t border-b border-gray-300">
            &mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;
        </div>

        <!-- Seconda Metà: Copia per l'Azienda -->
        <div class="half-page">
            <?php echo generateReservationBlock('company', $reservation_details, $client_details); ?>
        </div>
    </div>

    <!-- Pulsante di stampa visibile solo a schermo -->
    <button onclick="window.print()" class="no-print fixed bottom-6 right-6 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow-xl transition transform hover:scale-105">
        Stampa Prenotazione
    </button>
</body>
</html>
