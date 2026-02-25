<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chiusura Cassa Gestionale Verde - Layout Orizzontale</title>
    <!-- Inclusione di Tailwind CSS per uno styling rapido e responsivo -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Stili personalizzati per il font Inter e altre personalizzazioni */
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Stili per evidenziare i campi di output */
        .output-field {
            /* Sfondo più chiaro, testo più scuro e audace per l'output */
            @apply bg-green-100 text-green-900 font-extrabold p-3 rounded-lg;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease-in-out; /* Add transition for a smoother effect */
        }
        .diff-positive {
            @apply text-green-700 font-bold;
        }
        .diff-negative {
            @apply text-red-700 font-bold; /* Red remains for shortfalls for clarity */
        }
        /* Hide arrows for number inputs, for a cleaner look */
        input[type="number"] {
            -moz-appearance: textfield; /* Firefox */
        }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none; /* Chrome, Safari, Edge */
            margin: 0;
        }

        /* Nuovi stili per le tabelle delle denominazioni */
        .denomination-section-container {
            @apply flex flex-col md:flex-row gap-6; /* Layout orizzontale su schermi medi e grandi, verticale su piccoli */
        }
        .denomination-table-wrapper {
            @apply flex-1 bg-gray-50 p-6 rounded-2xl shadow-inner border border-gray-200; /* Wrapper per ogni tabella */
        }
        .denomination-category-title {
            @apply text-xl font-bold text-gray-800 mb-4 pb-2 border-b-2 border-green-300;
        }
        .denomination-table {
            @apply w-full table-auto border-collapse;
        }
        .denomination-table th,
        .denomination-table td {
            @apply px-4 py-3 text-left; /* More padding */
        }
        .denomination-table thead th {
            @apply bg-green-200 text-green-800 font-bold uppercase text-sm rounded-t-lg; /* Green header */
        }
        .denomination-table tbody tr:nth-child(even) {
            @apply bg-white;
        }
        .denomination-table tbody tr:nth-child(odd) {
            @apply bg-gray-100;
        }
        .denomination-table tbody tr:hover {
            @apply bg-green-50 shadow-md; /* Hover effect */
            transition: background-color 0.1s ease-in-out, box-shadow 0.1s ease-in-out;
        }
        .denomination-value-display-cell {
            @apply font-semibold text-gray-700 text-lg;
        }
        .denomination-input-cell {
            @apply text-center;
        }
        .denomination-input {
            @apply w-24 text-center border border-gray-300 rounded-md shadow-inner text-lg focus:ring-green-500 focus:border-green-500 py-1.5; /* Larger input */
        }
        .denomination-subtotal-cell {
            @apply text-right;
        }
        .denomination-subtotal {
            @apply text-green-700 font-extrabold text-lg;
        }
    </style>
</head>
<!-- Background with green gradient -->
<body class="bg-gradient-to-br from-green-700 via-emerald-600 to-teal-500 min-h-screen flex items-center justify-center p-6">

    <div class="bg-white p-10 rounded-3xl shadow-2xl w-full max-w-lg border-b-8 border-green-500">
        <!-- Logo -->
        <div class="flex justify-center mb-6">
            <img src="images/logo.png" alt="Logo Gestionale" class="h-24 w-24 object-contain rounded-full shadow-lg border-2 border-green-300"
                 onerror="this.onerror=null; this.src='https://placehold.co/96x96/e0ffe0/008000?text=LOGO'">
        </div>
        <h1 class="text-4xl font-extrabold text-center text-gray-900 mb-8 tracking-tight">Chiusura Cassa</h1>

        <div class="mb-8 text-center text-md text-gray-700">
            <p class="font-bold">Data e Ora Attuale:</p>
            <!-- Current date and time, now in a darker green -->
            <p id="currentDateTime" class="font-medium text-lg text-green-700"></p>
        </div>

        <form id="cashCloseForm" class="space-y-6">
            <!-- Initial Cash Fund -->
            <div>
                <label for="initialCash" class="block text-sm font-medium text-gray-700 mb-2">Fondo Cassa Iniziale (€)</label>
                <input type="number" id="initialCash" value="100.00" step="0.01" class="w-full p-3 border border-gray-300 rounded-xl shadow-sm focus:ring-green-500 focus:border-green-500 text-lg placeholder-gray-400">
            </div>

            <!-- Total Cash Receipts -->
            <div>
                <label for="cashReceipts" class="block text-sm font-medium text-gray-700 mb-2">Totale Incassi Contanti (€)</label>
                <input type="number" id="cashReceipts" value="0.00" step="0.01" class="w-full p-3 border border-gray-300 rounded-xl shadow-sm focus:ring-green-500 focus:border-green-500 text-lg placeholder-gray-400">
            </div>

            <!-- Total Cash Payments -->
            <div>
                <label for="cashPayments" class="block text-sm font-medium text-gray-700 mb-2">Totale Pagamenti Contanti (€)</label>
                <input type="number" id="cashPayments" value="0.00" step="0.01" class="w-full p-3 border border-gray-300 rounded-xl shadow-sm focus:ring-green-500 focus:border-green-500 text-lg placeholder-gray-400">
            </div>

            <hr class="my-8 border-t-2 border-gray-200">

            <!-- Theoretical Cash Total (Output) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Totale Cassa Teorico (€)</label>
                <p id="theoreticalCash" class="output-field text-2xl">0.00</p>
            </div>

            <!-- Actual Cash (counted by denomination - HORIZONTAL TABLES) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Contante Effettivo (Conteggiato per Denominazione)</label>
                <div class="denomination-section-container">
                    <!-- Denomination Table for Banknotes -->
                    <div class="denomination-table-wrapper">
                        <h3 class="denomination-category-title">Banconote</h3>
                        <table class="denomination-table">
                            <thead>
                                <tr>
                                    <th>Denominazione</th>
                                    <th class="text-center">Quantità</th>
                                    <th class="text-right">Subtotale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="denomination-value-display-cell">€50</td>
                                    <td class="denomination-input-cell">
                                        <input type="number" data-value="50" class="denomination-input" value="0" min="0">
                                    </td>
                                    <td class="denomination-subtotal-cell">
                                        <span class="denomination-subtotal" id="subtotal-50">€0.00</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="denomination-value-display-cell">€20</td>
                                    <td class="denomination-input-cell">
                                        <input type="number" data-value="20" class="denomination-input" value="0" min="0">
                                    </td>
                                    <td class="denomination-subtotal-cell">
                                        <span class="denomination-subtotal" id="subtotal-20">€0.00</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="denomination-value-display-cell">€10</td>
                                    <td class="denomination-input-cell">
                                        <input type="number" data-value="10" class="denomination-input" value="0" min="0">
                                    </td>
                                    <td class="denomination-subtotal-cell">
                                        <span class="denomination-subtotal" id="subtotal-10">€0.00</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="denomination-value-display-cell">€5</td>
                                    <td class="denomination-input-cell">
                                        <input type="number" data-value="5" class="denomination-input" value="0" min="0">
                                    </td>
                                    <td class="denomination-subtotal-cell">
                                        <span class="denomination-subtotal" id="subtotal-5">€0.00</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Denomination Table for Coins -->
                    <div class="denomination-table-wrapper">
                        <h3 class="denomination-category-title">Monete</h3>
                        <table class="denomination-table">
                            <thead>
                                <tr>
                                    <th>Denominazione</th>
                                    <th class="text-center">Quantità</th>
                                    <th class="text-right">Subtotale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="denomination-value-display-cell">€2</td>
                                    <td class="denomination-input-cell">
                                        <input type="number" data-value="2" class="denomination-input" value="0" min="0">
                                    </td>
                                    <td class="denomination-subtotal-cell">
                                        <span class="denomination-subtotal" id="subtotal-2">€0.00</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="denomination-value-display-cell">€1</td>
                                    <td class="denomination-input-cell">
                                        <input type="number" data-value="1" class="denomination-input" value="0" min="0">
                                    </td>
                                    <td class="denomination-subtotal-cell">
                                        <span class="denomination-subtotal" id="subtotal-1">€0.00</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="denomination-value-display-cell">€0.50</td>
                                    <td class="denomination-input-cell">
                                        <input type="number" data-value="0.50" class="denomination-input" value="0" min="0" step="1">
                                    </td>
                                    <td class="denomination-subtotal-cell">
                                        <span class="denomination-subtotal" id="subtotal-0_50">€0.00</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="denomination-value-display-cell">€0.20</td>
                                    <td class="denomination-input-cell">
                                        <input type="number" data-value="0.20" class="denomination-input" value="0" min="0" step="1">
                                    </td>
                                    <td class="denomination-subtotal-cell">
                                        <span class="denomination-subtotal" id="subtotal-0_20">€0.00</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="denomination-value-display-cell">€0.10</td>
                                    <td class="denomination-input-cell">
                                        <input type="number" data-value="0.10" class="denomination-input" value="0" min="0" step="1">
                                    </td>
                                    <td class="denomination-subtotal-cell">
                                        <span class="denomination-subtotal" id="subtotal-0_10">€0.00</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p id="actualCashDisplay" class="output-field text-2xl mt-6">0.00</p>
                <input type="hidden" id="actualCashTotal" value="0.00"> <!-- Hidden field to store total actual cash -->
            </div>


            <!-- Difference (Output) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Differenza (€)</label>
                <p id="difference" class="output-field text-2xl">0.00</p>
            </div>

            <!-- Confirmation button -->
            <button type="button" id="confirmButton" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-4 rounded-xl transition duration-300 ease-in-out transform hover:scale-105 shadow-xl text-lg mt-8">
                Calcola & Conferma Chiusura
            </button>
        </form>

        <!-- Message Box for user feedback -->
        <div id="messageBox" class="mt-8 p-5 rounded-xl hidden" role="alert">
            <p id="messageText" class="font-semibold text-lg"></p>
        </div>
    </div>

    <script>
        // Function to display a custom message (replaces alert())
        function showMessage(text, type = 'info') {
            const messageBox = document.getElementById('messageBox');
            const messageText = document.getElementById('messageText');
            messageText.textContent = text;
            messageBox.className = 'mt-8 p-5 rounded-xl'; // Reset classes

            if (type === 'success') {
                messageBox.classList.add('bg-green-100', 'text-green-800', 'border', 'border-green-400');
            } else if (type === 'error') {
                messageBox.classList.add('bg-red-100', 'text-red-800', 'border', 'border-red-400');
            } else { // info or default
                messageBox.classList.add('bg-blue-100', 'text-blue-800', 'border', 'border-blue-400');
            }
            messageBox.classList.remove('hidden');
        }

        // Function to hide the message
        function hideMessage() {
            document.getElementById('messageBox').classList.add('hidden');
        }

        // Get DOM elements
        const initialCashInput = document.getElementById('initialCash');
        const cashReceiptsInput = document.getElementById('cashReceipts');
        const cashPaymentsInput = document.getElementById('cashPayments');
        const theoreticalCashDisplay = document.getElementById('theoreticalCash');

        const denominationInputs = document.querySelectorAll('.denomination-input'); // Select all denomination inputs
        const actualCashDisplay = document.getElementById('actualCashDisplay'); // Field to display total actual cash
        const actualCashTotalHidden = document.getElementById('actualCashTotal'); // Hidden field to store total actual cash

        const differenceDisplay = document.getElementById('difference');
        const confirmButton = document.getElementById('confirmButton');
        const currentDateTimeSpan = document.getElementById('currentDateTime');

        // Function to update current date and time
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            currentDateTimeSpan.textContent = now.toLocaleDateString('it-IT', options);
        }

        // Call the function on load and then every second to keep it updated
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Function to calculate total actual cash from denominations
        function calculateActualCashFromDenominations() {
            let totalActualCash = 0;
            denominationInputs.forEach(input => {
                const value = parseFloat(input.dataset.value); // Value of the banknote/coin
                const count = parseInt(input.value) || 0; // Quantity entered
                const subtotal = value * count;

                // Update the subtotal for each denomination
                // For decimal values, use an ID that replaces the dot with an underscore
                const subtotalId = `subtotal-${String(value).replace('.', '_')}`;
                const subtotalElement = document.getElementById(subtotalId);
                if (subtotalElement) {
                    subtotalElement.textContent = `€${subtotal.toFixed(2)}`;
                }

                totalActualCash += subtotal;
            });
            actualCashDisplay.textContent = totalActualCash.toFixed(2);
            actualCashTotalHidden.value = totalActualCash.toFixed(2); // Update the hidden value
        }


        // Main function to calculate cash close
        function calculateCashClose() {
            hideMessage(); // Hide any previous messages

            const initialCash = parseFloat(initialCashInput.value) || 0;
            const cashReceipts = parseFloat(cashReceiptsInput.value) || 0;
            const cashPayments = parseFloat(cashPaymentsInput.value) || 0;
            
            calculateActualCashFromDenominations(); // First calculate the actual total from denominations
            const actualCash = parseFloat(actualCashTotalHidden.value) || 0; // Get the value from the hidden field

            // Calculate theoretical cash total
            const theoreticalCash = initialCash + cashReceipts - cashPayments;
            theoreticalCashDisplay.textContent = theoreticalCash.toFixed(2); // Format to 2 decimal places

            // Calculate the difference
            const difference = actualCash - theoreticalCash;
            differenceDisplay.textContent = difference.toFixed(2);

            // Apply different styles to the difference based on its value
            differenceDisplay.classList.remove('diff-positive', 'diff-negative');
            if (difference > 0) {
                differenceDisplay.classList.add('diff-positive');
                showMessage(`Attenzione: Eccedenza di cassa di € ${difference.toFixed(2)}`, 'info');
            } else if (difference < 0) {
                differenceDisplay.classList.add('diff-negative');
                showMessage(`Attenzione: Ammanco di cassa di € ${Math.abs(difference).toFixed(2)}`, 'error');
            } else {
                showMessage("Chiusura cassa perfetta! Nessuna differenza.", 'success');
            }
        }

        // Add event listeners to recalculate when values change
        initialCashInput.addEventListener('input', calculateCashClose);
        cashReceiptsInput.addEventListener('input', calculateCashClose);
        cashPaymentsInput.addEventListener('input', calculateCashClose);
        
        // Add event listeners for each denomination input
        denominationInputs.forEach(input => {
            input.addEventListener('input', calculateCashClose);
        });

        // Add event listener for the confirmation button
        confirmButton.addEventListener('click', () => {
            calculateCashClose(); // Perform final calculation
            // Here you could add logic to save data to a database
            // or generate a PDF report, etc.
            // For this example, we just show the message.
            console.log("Dati chiusura cassa:", {
                initialCash: parseFloat(initialCashInput.value),
                cashReceipts: parseFloat(cashReceiptsInput.value),
                cashPayments: parseFloat(cashPaymentsInput.value),
                theoreticalCash: parseFloat(theoreticalCashDisplay.textContent),
                actualCashByDenominations: Array.from(denominationInputs).map(input => ({
                    value: parseFloat(input.dataset.value),
                    count: parseInt(input.value) || 0
                })),
                actualCashTotal: parseFloat(actualCashTotalHidden.value),
                difference: parseFloat(differenceDisplay.textContent)
            });
        });

        // Perform initial calculation on page load
        document.addEventListener('DOMContentLoaded', calculateCashClose);
    </script>
</body>
</html>
