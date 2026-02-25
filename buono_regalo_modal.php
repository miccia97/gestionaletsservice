<!-- buono_regalo_modal.php: Modale per la creazione di un nuovo buono regalo -->
<!-- Questo file contiene l'HTML e lo JavaScript per un popup modale. -->
<!-- DEVE ESSERE INCLUSO IN UNA PAGINA PHP GIA' FUNZIONANTE (es. prenotazione_prodotto.php) -->
<!-- che si occupa di session_start(), connessione DB, e include Tailwind CSS. -->

<!-- Assicurati che Tailwind CSS sia incluso nella pagina principale (ESSENZIALE PER LO STILE) -->
<!-- Esempio nella pagina principale (nel <head>): -->
<!-- <script src="https://cdn.tailwindcss.com"></script> -->
<!-- <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"> -->
<!-- Se usi Font Awesome per icone (es. per un pulsante "Genera Codice"), includilo qui: -->
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> -->

<div id="giftCardModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 hidden z-50">
    <div class="bg-white p-5 rounded-xl shadow-2xl w-11/12 max-w-lg relative max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b border-gray-200 pb-4 mb-5">
            <h3 class="text-blue-600 text-xl font-bold">Crea Buono Regalo</h3>
            <button type="button" class="text-gray-500 hover:text-red-500 text-3xl transition-colors duration-200" onclick="closeGiftCardModal()">&times;</button>
        </div>
        <div class="p-0">
            <form id="giftCardForm" class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                <!-- Codice Buono -->
                <div class="col-span-full">
                    <label for="giftCardCode" class="block font-semibold text-gray-700 mb-2">Codice Buono:</label>
                    <div class="flex items-center gap-2">
                        <input type="text" id="giftCardCode" name="codice_buono" placeholder="Es: GCF-12345" required class="flex-grow p-3 border border-gray-300 rounded-lg text-base box-border transition duration-300 ease-in-out focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <button type="button" id="generateCodeBtn" class="bg-blue-500 text-white px-4 py-2.5 rounded-lg font-semibold text-sm shadow-md hover:bg-blue-600 transition duration-200">
                            Genera
                        </button>
                    </div>
                </div>

                <!-- Valore Buono -->
                <div>
                    <label for="giftCardValue" class="block font-semibold text-gray-700 mb-2">Valore Buono (€):</label>
                    <input type="number" step="0.01" id="giftCardValue" name="valore_buono" min="0" value="0.00" required class="w-full p-3 border border-gray-300 rounded-lg text-base box-border transition duration-300 ease-in-out focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>

                <!-- Data Emissione -->
                <div>
                    <label for="issueDate" class="block font-semibold text-gray-700 mb-2">Data Emissione:</label>
                    <input type="date" id="issueDate" name="data_emissione" required class="w-full p-3 border border-gray-300 rounded-lg text-base box-border transition duration-300 ease-in-out focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>

                <!-- Data Scadenza -->
                <div>
                    <label for="expiryDate" class="block font-semibold text-gray-700 mb-2">Data Scadenza (Opzionale):</label>
                    <input type="date" id="expiryDate" name="data_scadenza" class="w-full p-3 border border-gray-300 rounded-lg text-base box-border transition duration-300 ease-in-out focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                
                <!-- Stato Buono -->
                <div>
                    <label for="giftCardStatus" class="block font-semibold text-gray-700 mb-2">Stato:</label>
                    <select id="giftCardStatus" name="stato" required class="w-full p-3 border border-gray-300 rounded-lg text-base box-border transition duration-300 ease-in-out focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="Attivo">Attivo</option>
                        <option value="Utilizzato">Utilizzato</option>
                        <option value="Scaduto">Scaduto</option>
                    </select>
                </div>

                <!-- Destinatario Nome -->
                <div>
                    <label for="recipientName" class="block font-semibold text-gray-700 mb-2">Nome Destinatario (Opzionale):</label>
                    <input type="text" id="recipientName" name="destinatario_nome" placeholder="Nome del destinatario" class="w-full p-3 border border-gray-300 rounded-lg text-base box-border transition duration-300 ease-in-out focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>

                <!-- Destinatario Email -->
                <div>
                    <label for="recipientEmail" class="block font-semibold text-gray-700 mb-2">Email Destinatario (Opzionale):</label>
                    <input type="email" id="recipientEmail" name="destinatario_email" placeholder="email@esempio.com" class="w-full p-3 border border-gray-300 rounded-lg text-base box-border transition duration-300 ease-in-out focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>

                <!-- Campo Cliente Associato (con Autocompletamento) -->
                <div class="col-span-full relative">
                    <label for="associatedClientName" class="block font-semibold text-gray-700 mb-2">Cliente Associato (Opzionale):</label>
                    <input type="text" id="associatedClientName" name="associatedClientName" placeholder="Cerca o digita nome cliente" class="w-full p-3 border border-gray-300 rounded-lg text-base box-border transition duration-300 ease-in-out focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <input type="hidden" id="associatedClientId" name="cliente_id">
                    <div id="clientAutocompleteListGiftCard" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 hidden max-h-48 overflow-y-auto"></div>
                </div>

                <!-- Messaggio (Opzionale) -->
                <div class="col-span-full">
                    <label for="giftCardMessage" class="block font-semibold text-gray-700 mb-2">Messaggio (Opzionale):</label>
                    <textarea id="giftCardMessage" name="messaggio" rows="3" placeholder="Messaggio personalizzato per il buono regalo" class="w-full p-3 border border-gray-300 rounded-lg text-base box-border transition duration-300 ease-in-out focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"></textarea>
                </div>

                <!-- Note -->
                <div class="col-span-full">
                    <label for="giftCardNotes" class="block font-semibold text-gray-700 mb-2">Note (Opzionale):</label>
                    <textarea id="giftCardNotes" name="note" rows="3" placeholder="Note interne sul buono regalo" class="w-full p-3 border border-gray-300 rounded-lg text-base box-border transition duration-300 ease-in-out focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"></textarea>
                </div>

                <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-gray-200 col-span-full">
                    <button type="button" class="bg-gray-600 text-white px-5 py-2.5 rounded-lg font-semibold cursor-pointer transition duration-200 shadow-md hover:bg-gray-700 hover:-translate-y-px" onclick="closeGiftCardModal()">Annulla</button>
                    <button type="submit" class="bg-green-600 text-white px-5 py-2.5 rounded-lg font-semibold cursor-pointer transition duration-200 shadow-md hover:bg-green-700 hover:-translate-y-px">Salva Buono</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // URL dell'API per la creazione dei buoni regalo
    const GIFT_CARD_API_URL = 'add_buono_regalo.php'; 
    // URL dell'API per recuperare i clienti (dovrebbe essere lo stesso della pagina principale se usato per autocomplete)
    const CLIENT_API_URL_FOR_GIFT_CARD = 'api.php?get=clients_full'; // Nuovo endpoint per tutti i dettagli del cliente, se necessario


    const giftCardModal = document.getElementById('giftCardModal');
    const giftCardForm = document.getElementById('giftCardForm');
    const giftCardCodeInput = document.getElementById('giftCardCode');
    const generateCodeBtn = document.getElementById('generateCodeBtn');
    const giftCardValueInput = document.getElementById('giftCardValue');
    const issueDateInput = document.getElementById('issueDate');
    const expiryDateInput = document.getElementById('expiryDate');
    const giftCardStatusSelect = document.getElementById('giftCardStatus');
    const recipientNameInput = document.getElementById('recipientName');
    const recipientEmailInput = document.getElementById('recipientEmail');
    const giftCardMessageInput = document.getElementById('giftCardMessage');
    const giftCardNotesInput = document.getElementById('giftCardNotes');

    const associatedClientNameInput = document.getElementById('associatedClientName');
    const associatedClientIdInput = document.getElementById('associatedClientId');
    const clientAutocompleteListGiftCard = document.getElementById('clientAutocompleteListGiftCard');

    let allClientsForGiftCard = []; // Lista di clienti per l'autocomplete


    /**
     * Mostra un messaggio di feedback all'utente.
     * Questa funzione si aspetta che `window.showMessage` sia definita nella pagina principale.
     * Se non lo è, usa un fallback `alert()`.
     * @param {string} message Il testo del messaggio.
     * @param {boolean} isError True se è un messaggio di errore, false altrimenti.
     */
    function showMessage(message, isError = false) {
        if (typeof window.showMessage === 'function') {
            window.showMessage(message, isError);
        } else {
            console.log(`[Message ${isError ? 'ERROR' : 'SUCCESS'}]: ${message}`);
            alert(message);
        }
    }

    /**
     * Genera un codice alfanumerico casuale per il buono regalo.
     * @param {number} length La lunghezza del codice.
     * @returns {string} Il codice generato.
     */
    function generateRandomCode(length = 12) {
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let result = '';
        const charactersLength = characters.length;
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        return result;
    }

    /**
     * Funzione per aprire il modale del buono regalo.
     */
    window.openGiftCardModal = async function() {
        giftCardModal.classList.remove('hidden');
        giftCardModal.classList.add('flex');
        giftCardForm.reset(); // Resetta il form ad ogni apertura

        // Imposta la data di emissione di default alla data odierna
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        issueDateInput.value = `${yyyy}-${mm}-${dd}`;
        
        giftCardCodeInput.value = generateRandomCode(12); // Genera un codice di default
        giftCardStatusSelect.value = 'Attivo'; // Stato predefinito

        // Carica i clienti per l'autocomplete (se non già caricati o se vuoi forzare l'aggiornamento)
        await loadClientsForGiftCard();
        // Inizializza o rinizializza l'autocomplete per il cliente associato
        setupAutocompleteForGiftCardClient();
    }

    /**
     * Funzione per chiudere il modale del buono regalo.
     */
    window.closeGiftCardModal = function() {
        giftCardModal.classList.add('hidden');
        giftCardModal.classList.remove('flex');
        giftCardForm.reset();
        clientAutocompleteListGiftCard.classList.add('hidden'); // Nasconde la lista autocomplete
    }

    /**
     * Carica la lista dei clienti per l'autocomplete.
     * Questa funzione presuppone che 'api.php?get=clients_full' restituisca un array di oggetti cliente.
     */
    async function loadClientsForGiftCard() {
        try {
            // Se `window.clients` esiste già e ha dati, riutilizzali per evitare fetch multiple.
            // Altrimenti, fai una fetch.
            if (window.clients && window.clients.length > 0) {
                allClientsForGiftCard = window.clients;
                console.log('[GiftCardModal DEBUG] Clienti riutilizzati da window.clients:', allClientsForGiftCard);
                return;
            }

            const response = await fetch(CLIENT_API_URL_FOR_GIFT_CARD);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const clientsData = await response.json();
            allClientsForGiftCard = clientsData.map(c => {
                let displayName = '';
                if (c.ragione_sociale && c.ragione_sociale.trim() !== '') {
                    displayName = c.ragione_sociale;
                } else {
                    displayName = `${c.nome || ''} ${c.cognome || ''}`.trim();
                }
                if (displayName === '') {
                    displayName = `Cliente ID: ${c.id}`;
                }
                return {
                    id: c.id,
                    nome: c.nome,
                    cognome: c.cognome,
                    ragione_sociale: c.ragione_sociale,
                    display_name: displayName,
                    // Includi altri campi rilevanti se li usi per popolare i dettagli del cliente associato
                };
            });
            console.log('[GiftCardModal DEBUG] Clienti caricati per autocomplete:', allClientsForGiftCard);
        } catch (error) {
            console.error('Errore durante il caricamento dei clienti per il buono regalo:', error);
            showMessage('Errore durante il caricamento della lista clienti per il buono regalo.', true);
        }
    }

    /**
     * Configura l'autocomplete per il campo cliente associato.
     */
    function setupAutocompleteForGiftCardClient() {
        associatedClientNameInput.addEventListener('input', () => {
            const searchTerm = associatedClientNameInput.value.toLowerCase().trim();
            clientAutocompleteListGiftCard.innerHTML = '';
            
            if (searchTerm.length < 2) {
                clientAutocompleteListGiftCard.classList.add('hidden');
                associatedClientIdInput.value = ''; // Resetta l'ID del cliente se la ricerca è troppo breve
                return;
            }

            const filteredClients = allClientsForGiftCard.filter(client =>
                client.display_name.toLowerCase().includes(searchTerm)
            );

            if (filteredClients.length === 0) {
                clientAutocompleteListGiftCard.classList.add('hidden');
                return;
            }

            clientAutocompleteListGiftCard.classList.remove('hidden');
            filteredClients.forEach(client => {
                const div = document.createElement('div');
                div.textContent = client.display_name;
                div.classList.add('p-2', 'cursor-pointer', 'hover:bg-gray-100', 'border-b', 'border-gray-100');
                div.addEventListener('click', () => {
                    associatedClientNameInput.value = client.display_name;
                    associatedClientIdInput.value = client.id;
                    clientAutocompleteListGiftCard.classList.add('hidden');
                    console.log('[GiftCardModal DEBUG] Cliente selezionato:', client);
                });
                clientAutocompleteListGiftCard.appendChild(div);
            });
        });

        // Nascondi la lista se si clicca fuori
        document.addEventListener('click', (event) => {
            if (!associatedClientNameInput.contains(event.target) && !clientAutocompleteListGiftCard.contains(event.target)) {
                clientAutocompleteListGiftCard.classList.add('hidden');
            }
        });
    }

    // Listener per il pulsante "Genera Codice"
    generateCodeBtn.addEventListener('click', () => {
        giftCardCodeInput.value = generateRandomCode(12);
    });

    // Gestione della sottomissione del form per la creazione del buono regalo
    giftCardForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const giftCardData = {
            codice_buono: giftCardCodeInput.value.trim(),
            valore_buono: parseFloat(giftCardValueInput.value),
            data_emissione: issueDateInput.value,
            data_scadenza: expiryDateInput.value || null, // Invia null se vuoto
            destinatario_nome: recipientNameInput.value.trim() || null,
            destinatario_email: recipientEmailInput.value.trim() || null,
            messaggio: giftCardMessageInput.value.trim() || null,
            stato: giftCardStatusSelect.value,
            note: giftCardNotesInput.value.trim() || null,
            cliente_id: associatedClientIdInput.value || null // Assicurati che sia un numero o null
        };

        // Validazione minima
        if (!giftCardData.codice_buono || giftCardData.valore_buono <= 0 || !giftCardData.data_emissione) {
            showMessage('Codice Buono, Valore Buono e Data Emissione sono obbligatori.', true);
            return;
        }
        if (giftCardData.destinatario_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(giftCardData.destinatario_email)) {
            showMessage('Inserisci un indirizzo email del destinatario valido.', true);
            recipientEmailInput.focus();
            return;
        }

        console.log('[GiftCardModal DEBUG] Dati buono regalo inviati:', giftCardData);

        try {
            const response = await fetch(GIFT_CARD_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(giftCardData)
            });

            const result = await response.json();
            console.log('[GiftCardModal DEBUG] Risposta dal server:', result);

            if (response.ok && result.status === 'success') {
                showMessage(result.message, false);
                closeGiftCardModal();
                // Potresti voler ricaricare la lista dei buoni regalo nella pagina principale qui
                // Esempio: if (typeof window.loadGiftCards === 'function') { window.loadGiftCards(); }
            } else {
                showMessage('Errore nel salvataggio del buono regalo: ' + (result.message || 'Errore sconosciuto.'), true);
            }
        } catch (error) {
            console.error('Errore nella richiesta di salvataggio buono regalo:', error);
            showMessage('Si è verificato un errore durante il salvataggio del buono regalo. Riprova più tardi.', true);
        }
    });

    // Inizializza la data di emissione e il codice random all'apertura del modale
    // (Questa parte verrà gestita dalla funzione openGiftCardModal chiamata esternamente)
    document.addEventListener('DOMContentLoaded', () => {
        // Questi listener sono importanti anche se la data e il codice sono pre-impostati all'apertura
        // Assicurano che il generatore di codice funzioni e che l'autocomplete sia pronto.
        generateCodeBtn.addEventListener('click', () => {
            giftCardCodeInput.value = generateRandomCode(12);
        });
        setupAutocompleteForGiftCardClient(); // Assicurati che sia pronto al caricamento
    });
</script>
