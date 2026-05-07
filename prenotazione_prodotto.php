<?php
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Avvia la sessione PHP all'inizio dello script
session_start();

// Includi il file di connessione al database MySQL
if (!file_exists('db.php')) {
    echo "<div class='message-box error' style='display:block; position:static; margin-bottom: 1rem;'>Errore critico: Il file db.php non è stato trovato!</div>";
    exit;
}
require_once 'db.php';

// Controlla subito se c'è stato un errore di connessione al database da db.php
if (isset($db_connection_error) && $db_connection_error !== null) {
    // Se la richiesta è AJAX, restituisci JSON con l'errore
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Errore di connessione al database per richiesta AJAX: ' . $db_connection_error
        ]);
        exit;
    } else {
        $_SESSION['message'] = 'Errore critico: La connessione al database non è stata stabilita correttamente! ' . htmlspecialchars($db_connection_error, ENT_QUOTES, 'UTF-8');
        $_SESSION['isError'] = true;
    }
}

// Inizializza la variabile message per l'uso nel HTML
$message = '';

// Controlla se ci sono messaggi da visualizzare dalla sessione
if (isset($_SESSION['message'])) {
    $sessionMessage = $_SESSION['message'];
    $sessionIsError = $_SESSION['isError'] ?? false;
    $message = "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('" . addslashes($sessionMessage) . "', " . ($sessionIsError ? 'true' : 'false') . "); });</script>";
    unset($_SESSION['message']);
    unset($_SESSION['isError']);
}

// --- LOGICA PER GESTIRE LE RICHIESTE AJAX ---
// La logica di aggiunta cliente è stata spostata in add_cliente.php
// Se il tuo add_cliente.php è configurato per ricevere POST da fetch,
// questo blocco non dovrebbe più essere necessario qui, a meno che non ci siano altre azioni AJAX.
// Per questo esempio, lo lascio vuoto per chiarezza, supponendo che add_cliente.php gestisca l'inserimento.

/*
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'add_new_client':
            // Questa logica ora dovrebbe essere in add_cliente.php
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Azione AJAX non riconosciuta.']);
            exit;
    }
}
*/

// --- LOGICA DI ELABORAZIONE DEL FORM DI PRENOTAZIONE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) { // Assicurati che non sia una richiesta AJAX di azione
    $product_id = null; // Il prodotto è digitato liberamente, quindi non avrà un ID da `prodotti`
    $product_name = $_POST['productName'] ?? ''; // Nome del prodotto dal box di testo libero
    $quantity = $_POST['quantity'] ?? 0;
    $unit_price = $_POST['unitPrice'] ?? 0; // Nuovo campo: Prezzo unitario del prodotto
    $client_id = $_POST['clientId'] ?? null; // ID del cliente selezionato (potrebbe essere NULL)
    $customer_name = $_POST['customerName'] ?? '';
    $customer_phone = $_POST['customerPhone'] ?? null;
    $customer_secondary_phone = $_POST['customerSecondaryPhone'] ?? null; // Campo telefono secondario
    $customer_email = $_POST['customerEmail'] ?? null;
    $reservation_date = $_POST['reservationDate'] ?? '';
    $notes = $_POST['notes'] ?? null;
    $product_total_price = $_POST['productTotalPrice'] ?? 0; // Totale calcolato (non ricalcolato qui, preso dal JS)
    $deposit_amount = $_POST['depositAmount'] ?? 0;         // Acconto (preso dal JS)
    $remaining_amount = $_POST['remainingAmount'] ?? 0;     // Saldo (preso dal JS)
    $status = 'Pending'; // Stato predefinito

    // Validazione base
    if (empty($product_name) || !is_numeric($quantity) || $quantity <= 0 || !is_numeric($unit_price) || $unit_price <= 0 || empty($customer_name) || empty($reservation_date)) {
        $_SESSION['message'] = 'Tutti i campi obbligatori (Prodotto, Quantità, Prezzo Unitario, Nome Cliente, Data Prenotazione) devono essere validi e compilati.';
        $_SESSION['isError'] = true;
        header("Location: prenotazione_prodotto.php");
        exit();
    }

    try {
        mysqli_begin_transaction($conn);

        // NESSUN CONTROLLO E AGGIORNAMENTO MAGAZZINO SULLA TABELLA PRODOTTI
        // perché il prodotto è un testo libero e non necessariamente a catalogo.

        // Inserisci la nuova prenotazione nella tabella `prenotazioni_prodotti`
        // Ho aggiunto 'unit_price' nella lista delle colonne
        // `product_id` è lasciato a NULL in quanto il prodotto è un testo libero
        $stmt_insert_reservation = $conn->prepare("INSERT INTO prenotazioni_prodotti (product_id, product_name, unit_price, quantity, client_id, customer_name, customer_phone, customer_secondary_phone, customer_email, reservation_date, notes, product_total_price, deposit_amount, remaining_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_insert_reservation === false) {
            throw new mysqli_sql_exception("Errore nella preparazione della query di inserimento prenotazione: " . $conn->error);
        }
        // Tipi: isiissssssddds (int, string, decimal, int, int, string, string, string, string, string, string, decimal, decimal, decimal, string)
        // Nota: Il primo 'i' è per product_id, che sarà null, ma bind_param lo gestisce.
        $stmt_insert_reservation->bind_param('isdisssssssddds',
            $product_id, // Sarà NULL
            $product_name,
            $unit_price,
            $quantity,
            $client_id,
            $customer_name,
            $customer_phone,
            $customer_secondary_phone,
            $customer_email,
            $reservation_date,
            $notes,
            $product_total_price,
            $deposit_amount,
            $remaining_amount,
            $status
        );
        if ($stmt_insert_reservation->execute() === false) {
            throw new mysqli_sql_exception("Errore nell'esecuzione dell'inserimento prenotazione: " . $stmt_insert_reservation->error);
        }
        
        // RECUPERA L'ID DELLA PRENOTAZIONE APPENA CREATA
        $id_prenotazione_appena_salvata = $conn->insert_id;
        
        $stmt_insert_reservation->close();

        mysqli_commit($conn);
        
        $_SESSION['message'] = 'Prenotazione creata con successo! Preparazione per la stampa.';
        $_SESSION['isError'] = false;
        // REINDIRIZZA ALLA PAGINA DI STAMPA CON L'ID DELLA PRENOTAZIONE
        header("Location: stampa_prenotazione.php?id=" . $id_prenotazione_appena_salvata);
        exit();

    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($conn);
        $errorMessage = "Errore database durante il salvataggio della prenotazione: " . $e->getMessage();
        $_SESSION['message'] = $errorMessage;
        $_SESSION['isError'] = true;
        error_log("ERRORE SALVATAGGIO PRENOTAZIONE (SQL): " . $e->getMessage() . " - Stack: " . $e->getTraceAsString());
        header("Location: prenotazione_prodotto.php");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $errorMessage = "Errore generale durante il salvataggio della prenotazione: " . $e->getMessage();
        $_SESSION['message'] = $errorMessage;
        $_SESSION['isError'] = true;
        error_log("ERRORE GENERALE SALVATAGGIO PRENOTAZIONE: " . $e->getMessage() . " - Stack: " . $e->getTraceAsString());
        header("Location: prenotazione_prodotto.php");
        exit();
    }
}

// --- RECUPERO DATI PER LE SELECT E AUTOCONFIGURAZIONE JS (SEMPRE ESEGUITO) ---
// I prodotti esistenti non sono più usati per l'autocompletamento del nome prodotto,
// ma potrebbero servire per altre logiche in futuro. Li teniamo per ora,
// ma l'autocomplete `productName` non li userà.
$prodotti_esistenti = [];
try {
    $result_prodotti = $conn->query("SELECT id, nome, categoria, quantita, prezzo_acquisto, barcode, prezzo_vendita1, prezzo_vendita2 FROM prodotti ORDER BY nome");
    if ($result_prodotti) {
        $prodotti_raw = $result_prodotti->fetch_all(MYSQLI_ASSOC);
        $result_prodotti->free();

        foreach($prodotti_raw as $p) {
            $prodotti_esistenti[] = [
                'id' => $p['id'],
                'name' => $p['nome'],
                'category' => $p['categoria'],
                'current_stock' => (int)$p['quantita'],
                'priceNet' => (float)$p['prezzo_acquisto'],
                'priceSale1' => (float)($p['prezzo_vendita1'] ?? 0.00),
                'priceSale2' => (float)($p['prezzo_vendita2'] ?? 0.00),
                'code' => $p['barcode'],
                'um' => 'pz'
            ];
        }
    }
} catch (mysqli_sql_exception $e) {
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Errore nel caricamento prodotti: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', true); });</script>";
    error_log("Errore nel caricamento prodotti: " . $e->getMessage());
}

$clienti_esistenti = [];
try {
    // Seleziona 'telefono' invece di 'telefono_principale' e 'telefono_secondario' da clienti_nuovo
    $result_clienti = $conn->query("SELECT id, nome, cognome, ragione_sociale, telefono, email FROM clienti_nuovo ORDER BY nome, cognome");
    if ($result_clienti) {
        $clienti_raw = $result_clienti->fetch_all(MYSQLI_ASSOC);
        $result_clienti->free();

        foreach($clienti_raw as $c) {
            $displayName = '';
            if (!empty($c['ragione_sociale'])) {
                $displayName = $c['ragione_sociale'];
            } else {
                $displayName = trim($c['nome'] . ' ' . $c['cognome']);
            }
            if (empty($displayName)) { // Fallback if both name/surname and company are empty
                $displayName = 'ID Cliente: ' . $c['id'];
            }

            $clienti_esistenti[] = [
                'id' => $c['id'],
                'nome' => $c['nome'],
                'cognome' => $c['cognome'],
                'ragione_sociale' => $c['ragione_sociale'],
                'telefono_principale' => $c['telefono'], // Mappa il campo 'telefono' del DB a 'telefono_principale' in JS
                'telefono_secondario' => null, // Non c'è un campo telefono secondario in clienti_nuovo
                'email' => $c['email'],
                'display_name' => $displayName
            ];
        }
    }
} catch (mysqli_sql_exception $e) {
    $message .= "<script>document.addEventListener('DOMContentLoaded', function() { showMessage('Errore nel caricamento clienti: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "', true); });</script>";
    error_log("Errore nel caricamento clienti: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Prenotazione Prodotto</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=2">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
        <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        /* Header styles - gestiti da header-styles.css */

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="email"],
        input[type="tel"],
        select,
        textarea {
            padding: 0.625rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            width: 100%;
            box-sizing: border-box;
            background-color: #f9fafb;
        }
        button {
            padding: 0.625rem 1.25rem;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .btn-primary {
            background-color: #1a73e8;
            color: white;
        }
        .btn-primary:hover {
            background-color: #155cb7;
        }
        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #4b5563;
        }
        .autocomplete-list {
            position: absolute;
            background-color: white;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
            width: calc(100% - 2rem); /* Adjust width to match input field */
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        .autocomplete-list div {
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            border-bottom: 1px solid #e5e7eb;
        }
        .autocomplete-list div:last-child {
            border-bottom: none;
        }
        .autocomplete-list div:hover {
            background-color: #f3f4f6;
            color: #1a73e8;
            font-weight: 500;
        }

        /* Message Box for notifications */
        .message-box {
            position: fixed;
            top: 1rem;
            left: 50%; /* Centered horizontally */
            transform: translateX(-50%); /* Adjust for its own width */
            background-color: #4CAF50; /* Green */
            color: white;
            padding: 1.25rem 1.75rem; /* Increased padding */
            border-radius: 0.75rem; /* Slightly more rounded */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); /* Stronger shadow */
            z-index: 1100; /* Z-index aumentato per apparire sopra la top-bar */
            font-size: 1.1rem; /* Larger font size */
            font-weight: bold; /* Bold text */
            border: 2px solid white; /* White border */
            display: none; /* Hidden by default */
            animation: fadeIn 0.5s ease-out forwards, fadeOut 0.5s forwards 2.5s; /* Fade in then fade out */
            text-align: center; /* Center text within the box */
        }
        .message-box.error {
            background-color: #f44336; /* Red */
            border-color: #ff9999; /* Lighter red border for error */
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-50%) translateY(-20px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; // Includi la barra di navigazione ?>
    <!-- Modal nuovo cliente ora incluso automaticamente in header.php -->

    <div class="container">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Prenotazione Prodotto</h1>

        <?php echo $message; // Mostra messaggi di sistema ?>

        <div class="bg-gray-50 p-6 rounded-lg mb-6 shadow-sm">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Dettagli Prenotazione</h2>
            <form action="prenotazione_prodotto.php" method="POST" id="reservationForm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Campo Prodotto (Testo Libero) -->
                    <div class="md:col-span-2">
                        <label for="productName" class="block text-sm font-medium text-gray-700 mb-1">Nome Prodotto da Ordinare</label>
                        <input type="text" id="productName" name="productName" placeholder="Es. Smartphone Modello X, Servizio di Riparazione Y" class="focus:ring-blue-500 focus:border-blue-500" required>
                        <input type="hidden" id="productId" name="productId" value="">
                        <p id="productStockInfo" class="text-xs text-gray-500 mt-1 hidden"></p>
                    </div>

                    <!-- Prezzo Unitario Prodotto -->
                    <div>
                        <label for="unitPrice" class="block text-sm font-medium text-gray-700 mb-1">Prezzo Unitario Prodotto (€)</label>
                        <input type="number" step="0.01" id="unitPrice" name="unitPrice" min="0" value="0.00" class="focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <!-- Quantità -->
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantità</label>
                        <input type="number" id="quantity" name="quantity" min="1" value="1" class="focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <!-- Prezzo Totale Prodotto (visualizzato, non editabile) -->
                    <div>
                        <label for="productTotalPriceDisplay" class="block text-sm font-medium text-gray-700 mb-1">Prezzo Totale Prodotto</label>
                        <input type="text" id="productTotalPriceDisplay" class="bg-gray-200 cursor-not-allowed" value="0.00 €" readonly>
                        <input type="hidden" id="productTotalPrice" name="productTotalPrice">
                    </div>

                    <!-- Acconto Versato -->
                    <div>
                        <label for="depositAmount" class="block text-sm font-medium text-gray-700 mb-1">Acconto Versato</label>
                        <input type="number" step="0.01" id="depositAmount" name="depositAmount" min="0" value="0.00" class="focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Totale da Dare (visualizzato, non editabile) -->
                    <div>
                        <label for="remainingAmountDisplay" class="block text-sm font-medium text-gray-700 mb-1">Totale da Dare</label>
                        <input type="text" id="remainingAmountDisplay" class="bg-gray-200 cursor-not-allowed text-blue-700 font-semibold" value="0.00 €" readonly>
                        <input type="hidden" id="remainingAmount" name="remainingAmount">
                    </div>

                    <!-- Data Prenotazione -->
                    <div>
                        <label for="reservationDate" class="block text-sm font-medium text-gray-700 mb-1">Data Prenotazione</label>
                        <input type="date" id="reservationDate" name="reservationDate" class="focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <!-- Campo Cliente -->
                    <div class="relative md:col-span-2">
                        <label for="customerName" class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                        <div class="flex items-center space-x-2">
                            <input type="text" id="customerName" name="customerName" placeholder="Cerca o aggiungi cliente" class="flex-grow focus:ring-blue-500 focus:border-blue-500" required>
                            <input type="hidden" id="clientId" name="clientId">
                            <button type="button" id="addClientBtn" class="btn-primary flex-shrink-0 text-sm px-3 py-2">Aggiungi</button>
                        </div>
                        <div id="clientAutocompleteList" class="autocomplete-list hidden"></div>
                    </div>

                    <!-- Telefono Cliente (Principale) -->
                    <div>
                        <label for="customerPhone" class="block text-sm font-medium text-gray-700 mb-1">Telefono Cliente (Principale)</label>
                        <input type="tel" id="customerPhone" name="customerPhone" placeholder="Es. +39 123 4567890" class="focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Telefono Cliente (Secondario) -->
                    <div>
                        <label for="customerSecondaryPhone" class="block text-sm font-medium text-gray-700 mb-1">Telefono Cliente (Secondario)</label>
                        <input type="tel" id="customerSecondaryPhone" name="customerSecondaryPhone" placeholder="Numero secondario" class="focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Email Cliente -->
                    <div>
                        <label for="customerEmail" class="block text-sm font-medium text-gray-700 mb-1">Email Cliente</label>
                        <input type="email" id="customerEmail" name="customerEmail" placeholder="Es. nome@esempio.com" class="focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Note -->
                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Aggiungi note sulla prenotazione..." class="focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" id="cancelReservationBtn" class="btn-secondary">Annulla</button>
                    <button type="submit" class="btn-primary">Salva Prenotazione</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Message Box for notifications -->
    <div id="messageBox" class="message-box"></div>

    <script>
        const initialProdottiEsistenti = <?php echo json_encode($prodotti_esistenti); ?>;
        const initialClientiEsistenti = <?php echo json_encode($clienti_esistenti); ?>;

        let products = initialProdottiEsistenti;
        let clients = initialClientiEsistenti;

        // DOM Elements
        const productNameInput = document.getElementById('productName');
        const productIdInput = document.getElementById('productId');
        const unitPriceInput = document.getElementById('unitPrice');
        const quantityInput = document.getElementById('quantity');
        const productTotalPriceDisplay = document.getElementById('productTotalPriceDisplay');
        const productTotalPriceInput = document.getElementById('productTotalPrice');
        const depositAmountInput = document.getElementById('depositAmount');
        const remainingAmountDisplay = document.getElementById('remainingAmountDisplay');
        const remainingAmountInput = document.getElementById('remainingAmount');
        const reservationDateInput = document.getElementById('reservationDate');
        const customerNameInput = document.getElementById('customerName');
        const clientIdInput = document.getElementById('clientId');
        const clientAutocompleteList = document.getElementById('clientAutocompleteList');
        const addClientBtn = document.getElementById('addClientBtn'); // Pulsante "Aggiungi" cliente
        const customerPhoneInput = document.getElementById('customerPhone');
        const customerSecondaryPhoneInput = document.getElementById('customerSecondaryPhone');
        const customerEmailInput = document.getElementById('customerEmail');
        const notesInput = document.getElementById('notes');

        const reservationForm = document.getElementById('reservationForm');
        const cancelReservationBtn = document.getElementById('cancelReservationBtn');
        const messageBox = document.getElementById('messageBox');

        let selectedClient = null;


        // --- Utility Functions ---

        /**
         * Displays a message to the user (success or error).
         * This function is expected to be global as it's called from other scripts (like the client modal).
         * @param {string} message - The message text.
         * @param {boolean} isError - True if it's an error message, false otherwise.
         */
        window.showMessage = function(message, isError = false) {
            messageBox.textContent = message;
            messageBox.classList.remove('hidden', 'error');
            // Remove previous animation classes to restart animation
            messageBox.style.animation = 'none';
            void messageBox.offsetWidth; // Trigger reflow
            messageBox.style.animation = null;

            if (isError) {
                messageBox.classList.add('error');
            } else {
                messageBox.classList.remove('error');
            }
            messageBox.style.display = 'block'; // Make sure it's visible
        }

        /**
         * Formats a number as currency.
         * @param {number} value - The number to format.
         * @returns {string} - The formatted value.
         */
        function formatCurrency(value) {
            return new Intl.NumberFormat('it-IT', {
                style: 'currency',
                currency: 'EUR'
            }).format(value);
        }

        /**
         * Handles autocomplete for input fields.
         * @param {HTMLInputElement} inputElement - The input element.
         * @param {HTMLElement} listElement - The list element for suggestions.
         * @param {Array<Object>} dataArray - The data array (e.g., clients).
         * @param {Function} displayProperty - Function to get the property to display (e.g., item => item.display_name).
         * @param {Function} selectCallback - Function to call when an item is selected.
         */
        function setupAutocomplete(inputElement, listElement, dataArray, displayProperty, selectCallback) {
            inputElement.addEventListener('input', () => {
                const searchTerm = inputElement.value.toLowerCase();
                listElement.innerHTML = '';
                listElement.classList.remove('hidden');

                if (searchTerm.length < 2) {
                    listElement.classList.add('hidden');
                    return;
                }

                const filteredData = dataArray.filter(item =>
                    displayProperty(item).toLowerCase().includes(searchTerm)
                );

                if (filteredData.length === 0) {
                    listElement.classList.add('hidden');
                    return;
                }

                filteredData.forEach(item => {
                    const div = document.createElement('div');
                    div.textContent = displayProperty(item);
                    div.addEventListener('click', () => {
                        selectCallback(item);
                        listElement.classList.add('hidden');
                    });
                    listElement.appendChild(div);
                });
            });

            document.addEventListener('click', (event) => {
                if (!inputElement.contains(event.target) && !listElement.contains(event.target)) {
                    listElement.classList.add('hidden');
                }
            });
        }

        /**
         * Calculates and updates total product price, deposit, and remaining amount.
         */
        function updateFinancialTotals() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const unitPrice = parseFloat(unitPriceInput.value) || 0;
            const deposit = parseFloat(depositAmountInput.value) || 0;

            const totalProductPrice = quantity * unitPrice;
            const remaining = totalProductPrice - deposit;

            productTotalPriceDisplay.value = formatCurrency(totalProductPrice);
            productTotalPriceInput.value = totalProductPrice.toFixed(2); // For PHP POST

            remainingAmountDisplay.value = formatCurrency(remaining);
            remainingAmountInput.value = remaining.toFixed(2); // For PHP POST
        }


        // --- Event Listeners ---
        addClientBtn.addEventListener('click', () => {
            // Chiama la funzione globale per aprire il modale esterno
            if (typeof openNewClientModal === 'function') {
                openNewClientModal();
            } else {
                console.error("Funzione openNewClientModal non trovata. Assicurati che il file del modale cliente sia incluso correttamente.");
                showMessage("Errore: Impossibile aprire il modale di aggiunta cliente. Contatta l'amministratore.", true);
            }
        });

        window.onload = () => {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            reservationDateInput.value = `${year}-${month}-${day}`;

            setupAutocomplete(
                customerNameInput,
                clientAutocompleteList,
                clients,
                item => item.display_name,
                (client) => {
                    selectedClient = client;
                    customerNameInput.value = client.display_name;
                    clientIdInput.value = client.id;
                    customerPhoneInput.value = client.telefono_principale || '';
                    customerSecondaryPhoneInput.value = client.telefono_secondario || '';
                    customerEmailInput.value = client.email || '';
                }
            );

            unitPriceInput.addEventListener('input', updateFinancialTotals);
            quantityInput.addEventListener('input', updateFinancialTotals);
            depositAmountInput.addEventListener('input', updateFinancialTotals);

            quantityInput.addEventListener('input', () => {
                if (parseInt(quantityInput.value) < 1) {
                    quantityInput.value = 1;
                }
            });
            unitPriceInput.addEventListener('input', () => {
                if (parseFloat(unitPriceInput.value) < 0) {
                    unitPriceInput.value = 0;
                }
            });

            updateFinancialTotals();
        };

        // Form submission handler
        reservationForm.addEventListener('submit', (event) => {
            if (!productNameInput.value.trim() || !quantityInput.value || parseInt(quantityInput.value) <= 0 || !unitPriceInput.value || parseFloat(unitPriceInput.value) <= 0 || !customerNameInput.value.trim() || !reservationDateInput.value) {
                showMessage("Per favore, compila tutti i campi obbligatori (Prodotto, Quantità, Prezzo Unitario, Nome Cliente, Data Prenotazione) e assicurati che i valori siano validi.", true);
                event.preventDefault();
                return;
            }
            showMessage("Invio della prenotazione in corso...");
        });

        cancelReservationBtn.addEventListener('click', () => {
            if (confirm("Sei sicuro di voler annullare? Tutte le modifiche non salvate andranno perse.")) {
                reservationForm.reset();
                productStockInfo.textContent = '';

                customerNameInput.value = '';
                clientIdInput.value = '';
                customerPhoneInput.value = '';
                customerSecondaryPhoneInput.value = '';
                customerEmailInput.value = '';
                selectedClient = null;

                unitPriceInput.value = '0.00';
                quantityInput.value = '1';
                depositAmountInput.value = '0.00';
                updateFinancialTotals();

                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                reservationDateInput.value = `${year}-${month}-${day}`;

                showMessage("Form di prenotazione annullato.");
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('nav ul li a');

            navLinks.forEach(link => {
                const linkPath = link.getAttribute('href');
                const linkFileName = linkPath.split('/').pop();
                if (linkFileName === currentPath) {
                    link.classList.add('active-link');
                }
            });
        });
    </script>
</body>
</html>
