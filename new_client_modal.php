<!-- new_client_creation_modal.html: Modale per la creazione di un nuovo cliente (versione standalone) -->
<!-- Questo file contiene l'HTML e lo JavaScript per un popup modale. -->
<!-- Può essere incluso in qualsiasi pagina dove è necessario creare un nuovo cliente. -->

<!-- Importa Font Awesome per l'icona 'plus-circle' se non già incluso nella pagina principale -->
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> -->

<style>
    /* Stili per il Modal/Popup */
    .modal-overlay {
        display: none; /* Nascosto di default */
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6); /* Sfondo semi-trasparente */
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .modal-content {
        background: #ffffff;
        padding: 20px; /* Ridotto il padding */
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        width: 90%;
        max-width: 550px; /* Larghezza massima del popup ridotta */
        position: relative;
        animation: fadeIn 0.3s ease-out;
        max-height: 90vh; /* Altezza massima per scorrimento su schermi piccoli */
        overflow-y: auto; /* Abilita lo scroll se il contenuto è troppo lungo */
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 15px;
        margin-bottom: 20px; /* Ridotto il margine inferiore */
    }

    .modal-header h3 {
        margin: 0;
        color: #007bff;
        font-size: 1.4em; /* Dimensione ridotta */
        font-weight: 700;
    }

    .close-button {
        background: none;
        border: none;
        font-size: 1.6em; /* Dimensione ridotta */
        color: #6c757d;
        cursor: pointer;
        transition: color 0.2s ease;
    }

    .close-button:hover {
        color: #dc3545;
    }

    .modal-body .form-group {
        margin-bottom: 15px; /* Ridotto il margine inferiore */
    }

    .modal-body label {
        display: block; /* Assicura che la label sia su una nuova riga */
        font-weight: 600;
        color: #475569;
        margin-bottom: 8px;
    }

    .modal-body input[type="text"],
    .modal-body input[type="email"],
    .modal-body input[type="tel"], /* Aggiunto per input type="tel" */
    .modal-body textarea {
        width: 100%;
        padding: 10px 12px; /* Ridotto il padding */
        border: 1px solid #cbd5e0;
        border-radius: 8px;
        font-size: 0.95em; /* Dimensione del font leggermente ridotta */
        box-sizing: border-box;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .modal-body input[type="text"]:focus,
    .modal-body input[type="email"]:focus,
    .modal-body input[type="tel"]:focus, /* Aggiunto per input type="tel" */
    .modal-body textarea:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
        outline: none;
    }
    
    .modal-body h4 {
        margin-top: 25px; /* Ridotto il margine superiore */
        margin-bottom: 10px; /* Ridotto il margine inferiore */
        color: #007bff;
        border-bottom: 1px solid #e0f2ff;
        padding-bottom: 5px; /* Ridotto il padding */
        font-size: 1.1em; /* Dimensione del font leggermente ridotta */
        font-weight: 600;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px; /* Ridotto il gap */
        margin-top: 20px; /* Ridotto il margine superiore */
        padding-top: 15px; /* Ridotto il padding */
        border-top: 1px solid #e2e8f0;
    }

    .modal-footer button {
        padding: 10px 20px; /* Ridotto il padding */
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.9em; /* Dimensione del font leggermente ridotta */
        font-weight: 600;
        transition: all 0.2s ease;
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
    }

    .modal-footer .save-button {
        background-color: #28a745;
        color: white;
    }

    .modal-footer .save-button:hover {
        background-color: #218838;
        transform: translateY(-1px);
    }

    .modal-footer .cancel-button {
        background-color: #6c757d;
        color: white;
    }

    .modal-footer .cancel-button:hover {
        background-color: #5a6268;
        transform: translateY(-1px);
    }

    /* Stili per i tab */
    .tab-buttons {
        display: flex;
        justify-content: center;
        margin-bottom: 20px; /* Ridotto il margine inferiore */
        gap: 8px; /* Ridotto il gap */
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 5px;
    }

    .tab-button {
        background-color: #e9ecef;
        border: 1px solid #dee2e6;
        padding: 10px 15px; /* Ridotto il padding */
        border-radius: 8px 8px 0 0;
        cursor: pointer;
        font-size: 0.9em; /* Dimensione del font leggermente ridotta */
        font-weight: 600;
        color: #495057;
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        white-space: nowrap; /* Evita che il testo vada a capo */
    }

    .tab-button.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
        border-bottom-color: transparent; /* Nasconde il bordo inferiore della scheda attiva */
    }

    .tab-button:hover:not(.active) {
        background-color: #e2f0ff;
        color: #0056b3;
    }

    .tab-content {
        display: none; /* Nasconde tutte le schede di contenuto di default */
        padding-top: 10px; /* Ridotto il padding */
        animation: fadeIn 0.3s ease-out; /* Animazione per la comparsa */
    }

    .tab-content.active {
        display: block; /* Mostra solo la scheda attiva */
    }

    /* Animazioni */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(-20px); }
    }

    /* Responsive grid per i campi del form nel modal */
    .modal-body form.grid-cols-2 > div {
        display: block; /* Per assicurare che i div siano elementi blocco */
    }
    @media (min-width: 640px) { /* sm breakpoint per Tailwind */
        .modal-body form.grid-cols-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.5rem; /* gap-x-6 (24px) e gap-y-4 (16px) */
        }
        .modal-body form.grid-cols-2 .col-span-full {
            grid-column: span 2 / span 2;
        }
    }

</style>

<div id="newClientModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Aggiungi Nuovo Cliente</h3>
            <button type="button" class="close-button" onclick="closeNewClientModal()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Tab Buttons -->
            <div class="tab-buttons">
                <button type="button" class="tab-button active" data-tab="personal_data_tab">Dati Personali</button>
                <button type="button" class="tab-button" data-tab="company_data_tab">Dati Aziendali</button>
            </div>

            <form id="newClientForm" class="grid grid-cols-1 sm:grid-cols-2">
                <!-- Tab Content: Dati Personali -->
                <div id="personal_data_tab" class="tab-content active col-span-full">
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_nome">Nome:</label>
                        <input type="text" id="modal_nuovo_cliente_nome" name="nome" placeholder="Nome" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_cognome">Cognome:</label>
                        <input type="text" id="modal_nuovo_cliente_cognome" name="cognome" placeholder="Cognome" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_telefono">Telefono:</label>
                        <input type="tel" id="modal_nuovo_cliente_telefono" name="telefono" placeholder="Es: 3331234567" pattern="[0-9]{10,15}" title="Inserisci un numero di telefono valido (10-15 cifre)">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_email">Email:</label>
                        <input type="email" id="modal_nuovo_cliente_email" name="email" placeholder="nome@esempio.com">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_indirizzo">Indirizzo:</label>
                        <input type="text" id="modal_nuovo_cliente_indirizzo" name="indirizzo" placeholder="Via Roma, 1">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_citta">Città:</label>
                        <input type="text" id="modal_nuovo_cliente_citta" name="citta" placeholder="Roma">
                    </div>
                    <div class="form-group col-span-full">
                        <label for="modal_nuovo_cliente_note">Note:</label>
                        <textarea id="modal_nuovo_cliente_note" name="note" rows="3" placeholder="Note aggiuntive sul cliente"></textarea>
                    </div>
                </div>

                <!-- Tab Content: Dati Aziendali -->
                <div id="company_data_tab" class="tab-content col-span-full">
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_ragione_sociale">Ragione Sociale:</label>
                        <input type="text" id="modal_nuovo_cliente_ragione_sociale" name="ragione_sociale" placeholder="Nome S.p.A.">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_partita_iva">Partita IVA:</label>
                        <input type="text" id="modal_nuovo_cliente_partita_iva" name="partita_iva" placeholder="IT12345678901">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_indirizzo_azienda">Indirizzo Azienda:</label>
                        <input type="text" id="modal_nuovo_cliente_indirizzo_azienda" name="indirizzo_azienda" placeholder="Via dell'Industria, 5">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_citta_azienda">Città Azienda:</label>
                        <input type="text" id="modal_nuovo_cliente_citta_azienda" name="citta_azienda" placeholder="Milano">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_telefono_azienda">Telefono Azienda:</label>
                        <input type="tel" id="modal_nuovo_cliente_telefono_azienda" name="telefono_azienda" placeholder="Es: 0212345678" pattern="[0-9]{10,15}" title="Inserisci un numero di telefono valido (10-15 cifre)">
                    </div>
                    <div class="form-group">
                        <label for="modal_nuovo_cliente_email_azienda">Email Azienda:</label>
                        <input type="email" id="modal_nuovo_cliente_email_azienda" name="email_azienda" placeholder="info@azienda.com">
                    </div>
                    <div class="form-group col-span-full">
                        <label for="modal_nuovo_cliente_note_azienda">Note Azienda:</label>
                        <textarea id="modal_nuovo_cliente_note_azienda" name="note_azienda" rows="3" placeholder="Note aggiuntive sull'azienda"></textarea>
                    </div>
                </div>

                <div class="modal-footer col-span-full">
                    <button type="button" class="cancel-button" onclick="closeNewClientModal()">Annulla</button>
                    <button type="submit" class="save-button" id="save_new_client_btn_standalone">Salva Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // URL dell'API per la creazione dei clienti
    const CLIENT_API_URL = 'add_cliente.php'; 

    // Elementi del Modal/Popup per nuovo cliente
    const newClientModal = document.getElementById('newClientModal');
    const newClientForm = document.getElementById('newClientForm');
    const saveNewClientBtn = document.getElementById('save_new_client_btn_standalone'); // ID modificato per evitare conflitti

    // Campi del modal per nuovo cliente (dati personali)
    const modalNuovoClienteNomeInput = document.getElementById('modal_nuovo_cliente_nome');
    const modalNuovoClienteCognomeInput = document.getElementById('modal_nuovo_cliente_cognome');
    const modalNuovoClienteTelefonoInput = document.getElementById('modal_nuovo_cliente_telefono');
    const modalNuovoClienteEmailInput = document.getElementById('modal_nuovo_cliente_email');
    const modalNuovoClienteIndirizzoInput = document.getElementById('modal_nuovo_cliente_indirizzo');
    const modalNuovoClienteCittaInput = document.getElementById('modal_nuovo_cliente_citta');
    const modalNuovoClienteNoteInput = document.getElementById('modal_nuovo_cliente_note');

    // Campi del modal per nuovo cliente (dati aziendali)
    const modalNuovoClienteRagioneSocialeInput = document.getElementById('modal_nuovo_cliente_ragione_sociale');
    const modalNuovoClientePartitaIvaInput = document.getElementById('modal_nuovo_cliente_partita_iva');
    const modalNuovoClienteIndirizzoAziendaInput = document.getElementById('modal_nuovo_cliente_indirizzo_azienda');
    const modalNuovoClienteCittaAziendaInput = document.getElementById('modal_nuovo_cliente_citta_azienda');
    const modalNuovoClienteTelefonoAziendaInput = document.getElementById('modal_nuovo_cliente_telefono_azienda');
    const modalNuovoClienteEmailAziendaInput = document.getElementById('modal_nuovo_cliente_email_azienda');
    const modalNuovoClienteNoteAziendaInput = document.getElementById('modal_nuovo_cliente_note_azienda');

    // Elementi per la gestione dei tab
    const tabButtons = document.querySelectorAll('#newClientModal .tab-button'); // Seleziona solo i bottoni all'interno del modal
    const tabContents = document.querySelectorAll('#newClientModal .tab-content'); // Seleziona solo i contenuti all'interno del modal

    /**
     * Mostra un messaggio di feedback all'utente.
     * Questa funzione dovrebbe essere definita nella pagina principale che include il modale,
     * o sarà usata una semplice `alert()` come fallback.
     * @param {string} message Il testo del messaggio.
     * @param {string} [type='success'] Il tipo di messaggio ('success', 'error', ecc.).
     */
    function showMessage(message, type = 'success') {
        // Implementazione di fallback se la funzione non esiste nella pagina principale
        if (typeof window.showMessage === 'function') {
            window.showMessage(message, type);
        } else {
            console.log(`[Message ${type.toUpperCase()}]: ${message}`);
            alert(message); // Fallback per avvisare l'utente
        }
    }

    /**
     * Funzione per aprire il modale di creazione nuovo cliente.
     */
    function openNewClientModal() {
        newClientModal.style.display = 'flex'; // Mostra il modal
        newClientForm.reset(); // Resetta il form ad ogni apertura
        showTab('personal_data_tab'); // Attiva il tab "Dati Personali" di default
        modalNuovoClienteNomeInput.focus(); // Metti il focus sul primo campo
    }

    /**
     * Funzione per chiudere il modale di creazione nuovo cliente.
     */
    function closeNewClientModal() {
        newClientModal.style.display = 'none'; // Nascondi il modal
        newClientForm.reset(); // Resetta il form alla chiusura
    }

    /**
     * Funzione per mostrare un tab specifico all'interno del modale.
     * @param {string} tabId L'ID del tab da mostrare (es. 'personal_data_tab', 'company_data_tab').
     */
    function showTab(tabId) {
        tabContents.forEach(content => {
            content.classList.remove('active');
        });
        tabButtons.forEach(button => {
            button.classList.remove('active');
        });

        document.getElementById(tabId).classList.add('active');
        document.querySelector(`#newClientModal .tab-button[data-tab="${tabId}"]`).classList.add('active');
    }

    // Listener per i pulsanti dei tab
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            showTab(tabId);
        });
    });

    // Gestione della sottomissione del form per la creazione di un nuovo cliente
    newClientForm.addEventListener('submit', async (event) => {
        event.preventDefault(); // Impedisce il ricaricamento della pagina

        // Raccogli i dati dal tab attualmente attivo
        let clientData = {};
        const activeTabId = document.querySelector('#newClientModal .tab-content.active').id;

        if (activeTabId === 'personal_data_tab') {
            clientData = { 
                nome: modalNuovoClienteNomeInput.value.trim(),
                cognome: modalNuovoClienteCognomeInput.value.trim(),
                email: modalNuovoClienteEmailInput.value.trim(),
                telefono: modalNuovoClienteTelefonoInput.value.trim(),
                indirizzo: modalNuovoClienteIndirizzoInput.value.trim(),
                citta: modalNuovoClienteCittaInput.value.trim(),
                note: modalNuovoClienteNoteInput.value.trim(),
                // Assicurati che i campi aziendali siano vuoti se si usa il tab personale
                ragione_sociale: '', partita_iva: '', indirizzo_azienda: '', citta_azienda: '',
                telefono_azienda: '', email_azienda: '', note_azienda: ''
            };

            if (!clientData.nome || !clientData.cognome) {
                showMessage('Nome e Cognome del cliente sono obbligatori.', 'error');
                modalNuovoClienteNomeInput.focus();
                return;
            }
            if (clientData.telefono && !/^[0-9]{10,15}$/.test(clientData.telefono)) {
                showMessage('Inserisci un numero di telefono personale valido (10-15 cifre, solo numeri).', 'error');
                modalNuovoClienteTelefonoInput.focus();
                return;
            }
            if (clientData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(clientData.email)) {
                showMessage('Inserisci un indirizzo email personale valido.', 'error');
                modalNuovoClienteEmailInput.focus();
                return;
            }

        } else if (activeTabId === 'company_data_tab') {
            clientData = {
                ragione_sociale: modalNuovoClienteRagioneSocialeInput.value.trim(),
                partita_iva: modalNuovoClientePartitaIvaInput.value.trim(),
                indirizzo_azienda: modalNuovoClienteIndirizzoAziendaInput.value.trim(),
                citta_azienda: modalNuovoClienteCittaAziendaInput.value.trim(),
                telefono_azienda: modalNuovoClienteTelefonoAziendaInput.value.trim(),
                email_azienda: modalNuovoClienteEmailAziendaInput.value.trim(),
                note_azienda: modalNuovoClienteNoteAziendaInput.value.trim(),
                // Assicurati che i campi personali siano vuoti se si usa il tab aziendale
                nome: '', cognome: '', email: '', telefono: '', indirizzo: '', citta: '', note: ''
            };

            if (!clientData.ragione_sociale && !clientData.partita_iva) {
                showMessage('Ragione Sociale o Partita IVA sono obbligatori per i dati aziendali.', 'error');
                modalNuovoClienteRagioneSocialeInput.focus();
                return;
            }
            if (clientData.telefono_azienda && !/^[0-9]{10,15}$/.test(clientData.telefono_azienda)) {
                showMessage('Inserisci un numero di telefono aziendale valido (10-15 cifre, solo numeri).', 'error');
                modalNuovoClienteTelefonoAziendaInput.focus();
                return;
            }
            if (clientData.email_azienda && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(clientData.email_azienda)) {
                showMessage('Inserisci un indirizzo email aziendale valido.', 'error');
                modalNuovoClienteEmailAziendaInput.focus();
                return;
            }
        }
        
        try {
            const response = await fetch(CLIENT_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(clientData)
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                showMessage(result.message, 'success');
                closeNewClientModal(); // Chiudi il modale
                
                // NOTA: Se la pagina chiamante necessita di aggiornare una lista clienti
                // o pre-selezionare il cliente appena creato, è responsabilità della
                // pagina chiamante gestire questa logica dopo la chiusura del modale.
                // Potresti definire una callback globale o un event listener personalizzato.
                // Esempio: `document.dispatchEvent(new CustomEvent('clientCreated', { detail: result.client }));`

            } else {
                showMessage('Errore nel salvataggio del cliente: ' + (result.message || 'Errore sconosciuto.'), 'error');
                console.error('Errore dal server:', result);
            }
        } catch (error) {
            console.error('Errore nella richiesta di salvataggio cliente:', error);
            showMessage('Si è verificato un errore durante il salvataggio del cliente. Riprova più tardi.', 'error');
        }
    });

    // Inizializza i tab alla fine del caricamento del DOM
    document.addEventListener('DOMContentLoaded', () => {
        showTab('personal_data_tab');
    });
</script>
