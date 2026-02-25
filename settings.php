<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impostazioni Gestionale</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --brand-primary: #22c55e; /* Green */
            --brand-primary-dark: #16a34a; /* Darker Green */
            --brand-red: #ef4444;
            --brand-red-dark: #dc2626;
            --bg-page: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --card-bg: #ffffff;
            --card-radius: 0.75rem;
            --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-primary);
            padding-top: 80px; /* Spazio per header fisso */
        }
        .page-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .page-header h1 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 2rem;
        }
        
        /* Main Settings Card */
        .settings-card {
            background-color: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
        }

        /* Tab Navigation */
        .tab-nav {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            padding: 0 2rem;
        }
        .tab-button {
            padding: 1rem 1.25rem;
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 600;
            color: var(--text-secondary);
            border-bottom: 3px solid transparent;
            margin-bottom: -1px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .tab-button:hover {
            color: var(--brand-primary);
        }
        .tab-button.active {
            color: var(--brand-primary);
            border-color: var(--brand-primary);
        }

        /* Tab Content */
        .tab-content-container {
            padding: 2.5rem;
        }
        .tab-panel {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        .tab-panel.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .section-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
        }
        .section-header p {
            color: var(--text-secondary);
            margin-top: 0.25rem;
            max-width: 80ch;
        }

        /* Input group with icon */
        .input-group {
            margin-bottom: 1.5rem;
        }
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-secondary);
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper .icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }
        .input-group input, .input-group select {
            width: 100%;
            padding: 0.75rem;
            padding-left: 2.5rem; /* Space for icon */
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }
         .input-group select {
            padding-left: 0.75rem;
        }
        .input-group input:focus, .input-group select:focus {
            outline: none;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
            background-color: white;
        }
        .section-footer {
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
            margin-top: 2rem;
            display: flex;
            justify-content: flex-end;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .btn-primary { background-color: var(--brand-primary); color: white; }
        .btn-primary:hover:not(:disabled) { background-color: var(--brand-primary-dark); }
        .btn-danger { background-color: var(--brand-red); color: white; }
        .btn-danger:hover:not(:disabled) { background-color: var(--brand-red-dark); }

        /* Danger Zone */
        .danger-zone {
            border: 2px solid var(--brand-red);
            border-radius: var(--card-radius);
            padding: 1.5rem;
            margin-top: 2.5rem;
        }
        .danger-zone .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .danger-zone .item-text h3 { color: var(--brand-red); font-weight: 700; font-size: 1.1rem; }
        .danger-zone .item-text p { color: var(--text-secondary); margin-top: 0.25rem; }

        /* Toggle Switch */
        .toggle-switch { position: relative; display: inline-block; width: 52px; height: 28px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 28px; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--brand-primary); }
        input:checked + .slider:before { transform: translateX(24px); }

        /* Dark Mode */
        body.dark-mode { background-color: #1a202c; color: #e2e8f0; }
        body.dark-mode .settings-card, body.dark-mode .tab-button:hover { background-color: #2d3748; }
        body.dark-mode .tab-nav { border-color: #4a5568; }
        body.dark-mode .tab-button { color: #a0aec0; }
        body.dark-mode .tab-button.active { color: var(--brand-primary); background-color: transparent; }
        body.dark-mode h1, body.dark-mode h2 { color: #f7fafc; }
        body.dark-mode .input-group label, body.dark-mode p { color: #a0aec0; }
        body.dark-mode .input-group input, body.dark-mode .input-group select {
            background-color: #1a202c; border-color: #4a5568; color: #e2e8f0;
        }
        body.dark-mode .input-group input:focus, body.dark-mode .input-group select:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.4);
        }
        body.dark-mode .section-footer { border-color: #4a5568; }
        body.dark-mode .danger-zone { border-color: #fca5a5; }
        body.dark-mode .danger-zone .item-text h3 { color: #fca5a5; }

        @media (max-width: 768px) {
            .tab-nav { overflow-x: auto; }
            .tab-button { flex-shrink: 0; }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <h1>Impostazioni</h1>
        </div>

        <div class="settings-card">
            <nav class="tab-nav">
                <button data-tab="info" class="tab-button active"><i class="fas fa-store"></i> <span>Informazioni</span></button>
                <button data-tab="appearance" class="tab-button"><i class="fas fa-paint-brush"></i> <span>Aspetto</span></button>
                <button data-tab="security" class="tab-button"><i class="fas fa-shield-alt"></i> <span>Sicurezza</span></button>
                <button data-tab="data" class="tab-button"><i class="fas fa-database"></i> <span>Dati</span></button>
            </nav>

            <div class="tab-content-container">
                <!-- Sezione Informazioni Negozio -->
                <div id="info-panel" class="tab-panel active">
                    <div class="section-header mb-8">
                        <h2>Informazioni Negozio</h2>
                        <p>Questi dettagli appariranno su ricevute, fatture e altri documenti ufficiali del tuo negozio.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8">
                        <div class="input-group">
                            <label for="shopName">Nome Negozio:</label>
                             <div class="input-wrapper"><i class="fas fa-store icon"></i><input type="text" id="shopName" placeholder="Nome del tuo negozio"></div>
                        </div>
                        <div class="input-group">
                            <label for="shopVAT">Partita IVA / C.F.:</label>
                            <div class="input-wrapper"><i class="fas fa-id-card icon"></i><input type="text" id="shopVAT" placeholder="Partita IVA o Codice Fiscale"></div>
                        </div>
                        <div class="input-group md:col-span-2">
                            <label for="shopAddress">Indirizzo:</label>
                             <div class="input-wrapper"><i class="fas fa-map-marker-alt icon"></i><input type="text" id="shopAddress" placeholder="Via, numero civico, città, CAP"></div>
                        </div>
                        <div class="input-group">
                            <label for="shopPhone">Telefono:</label>
                             <div class="input-wrapper"><i class="fas fa-phone icon"></i><input type="tel" id="shopPhone" placeholder="Numero di telefono"></div>
                        </div>
                        <div class="input-group">
                            <label for="shopEmail">Email:</label>
                            <div class="input-wrapper"><i class="fas fa-envelope icon"></i><input type="email" id="shopEmail" placeholder="Indirizzo email"></div>
                        </div>
                    </div>
                    <div class="section-footer">
                        <button id="saveShopInfoBtn" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>
                            <span>Salva Informazioni</span>
                        </button>
                    </div>
                </div>

                <!-- Sezione Preferenze di Visualizzazione -->
                <div id="appearance-panel" class="tab-panel">
                    <div class="section-header mb-8">
                        <h2>Aspetto</h2>
                        <p>Personalizza l'interfaccia del gestionale per adattarla alle tue preferenze di lavoro.</p>
                    </div>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <div class="pr-4">
                               <label for="darkModeToggle" class="font-medium text-base">Modalità Scura</label>
                               <p class="text-sm text-gray-500 dark:text-gray-400">Riduci l'affaticamento degli occhi in condizioni di scarsa illuminazione.</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="darkModeToggle">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="input-group">
                            <label for="itemsPerPage">Elementi per pagina (nelle tabelle):</label>
                            <select id="itemsPerPage" class="w-full md:w-1/3">
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="section-footer">
                        <button id="saveDisplayPrefsBtn" class="btn btn-primary">
                             <i class="fas fa-save mr-2"></i>
                            <span>Salva Preferenze</span>
                        </button>
                    </div>
                </div>
                
                 <!-- Sezione Sicurezza -->
                <div id="security-panel" class="tab-panel">
                    <div class="section-header mb-8">
                        <h2>Sicurezza</h2>
                        <p>Gestisci la tua password e le opzioni di accesso per proteggere il tuo account.</p>
                    </div>
                     <div class="space-y-6">
                        <div class="flex items-center justify-between p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <div>
                                <h3 class="font-medium text-base">Cambia Password</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Si consiglia di utilizzare una password lunga e complessa.</p>
                            </div>
                            <button class="btn btn-primary" onclick="changePassword()">Cambia</button>
                        </div>
                         <div class="flex items-center justify-between p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <div>
                                <h3 class="font-medium text-base">Autenticazione a due fattori (2FA)</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Aggiungi un ulteriore livello di sicurezza al tuo account.</p>
                            </div>
                            <button class="btn btn-primary" onclick="setup2FA()">Abilita</button>
                        </div>
                    </div>
                </div>

                <!-- Sezione Gestione Dati -->
                <div id="data-panel" class="tab-panel">
                    <div class="section-header mb-8">
                        <h2>Gestione Dati</h2>
                        <p>Esporta o importa i dati del tuo gestionale per backup o analisi esterne.</p>
                    </div>
                    <div class="flex items-center justify-between p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                         <div>
                            <h3 class="font-medium text-base">Esporta Dati</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Scarica un file CSV con tutti i tuoi prodotti e le giacenze.</p>
                        </div>
                        <button id="exportDataBtn" class="btn btn-primary"><i class="fas fa-file-csv mr-2"></i> Esporta</button>
                    </div>
                    
                    <div class="danger-zone">
                        <div class="item">
                            <div class="item-text">
                                <h3>Zona Pericolo</h3>
                                <p>Queste azioni sono irreversibili. Usare con cautela.</p>
                            </div>
                            <button class="btn btn-danger" onclick="deleteAllData()">Elimina Tutti i Dati</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Setup Tab Navigation
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabPanels = document.querySelectorAll('.tab-panel');

            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    
                    tabPanels.forEach(panel => {
                        if (panel.id === `${button.dataset.tab}-panel`) {
                            panel.classList.add('active');
                        } else {
                            panel.classList.remove('active');
                        }
                    });
                });
            });

            loadSettings();

            document.getElementById('saveShopInfoBtn').addEventListener('click', saveShopInfo);
            document.getElementById('saveDisplayPrefsBtn').addEventListener('click', saveDisplayPreferences);
            document.getElementById('exportDataBtn').addEventListener('click', exportData);
            document.getElementById('darkModeToggle').addEventListener('change', function() {
                applyDarkMode(this.checked);
            });
        });

        function showNotification(message, icon = 'success') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icon,
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }

        function applyDarkMode(isDarkMode) {
            document.body.classList.toggle('dark-mode', isDarkMode);
        }

        async function loadSettings() {
            try {
                // Simulating a fetch call
                const settings = JSON.parse(localStorage.getItem('appSettings')) || {};
                
                document.getElementById('shopName').value = settings.shopName || '';
                document.getElementById('shopAddress').value = settings.shopAddress || '';
                document.getElementById('shopPhone').value = settings.shopPhone || '';
                document.getElementById('shopEmail').value = settings.shopEmail || '';
                document.getElementById('shopVAT').value = settings.shopVAT || '';
                
                const darkModeEnabled = settings.darkMode === true;
                document.getElementById('darkModeToggle').checked = darkModeEnabled;
                applyDarkMode(darkModeEnabled);

                document.getElementById('itemsPerPage').value = settings.itemsPerPage || '10';
                
            } catch (error) {
                console.error("Errore caricamento impostazioni:", error);
                showNotification("Errore nel caricamento delle impostazioni.", "error");
            }
        }
        
        async function setButtonLoading(button, isLoading) {
            const span = button.querySelector('span');
            const icon = button.querySelector('i');
            if (isLoading) {
                button.disabled = true;
                if(span) span.textContent = 'Salvataggio...';
                if(icon) icon.className = 'fas fa-spinner fa-spin mr-2';
            } else {
                button.disabled = false;
                if(span) span.textContent = button.dataset.originalText;
                if(icon) icon.className = button.dataset.originalIcon;
            }
        }
        
        async function saveData(settingsData, button) {
            const span = button.querySelector('span');
            const icon = button.querySelector('i');
            if (span) button.dataset.originalText = span.textContent;
            if (icon) button.dataset.originalIcon = icon.className;
            
            setButtonLoading(button, true);
            
            try {
                // Simulating a save call
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                let currentSettings = JSON.parse(localStorage.getItem('appSettings')) || {};
                const newSettings = { ...currentSettings, ...settingsData };
                localStorage.setItem('appSettings', JSON.stringify(newSettings));

                showNotification("Impostazioni salvate con successo!", "success");

            } catch (error) {
                console.error("Errore salvataggio:", error);
                showNotification("Errore nel salvataggio. Controlla la console.", "error");
            } finally {
                setButtonLoading(button, false);
            }
        }

        function saveShopInfo() {
            const settingsData = {
                shopName: document.getElementById('shopName').value,
                shopAddress: document.getElementById('shopAddress').value,
                shopPhone: document.getElementById('shopPhone').value,
                shopEmail: document.getElementById('shopEmail').value,
                shopVAT: document.getElementById('shopVAT').value
            };
            saveData(settingsData, document.getElementById('saveShopInfoBtn'));
        }

        function saveDisplayPreferences() {
            const displayData = {
                darkMode: document.getElementById('darkModeToggle').checked,
                itemsPerPage: parseInt(document.getElementById('itemsPerPage').value)
            };
            saveData(displayData, document.getElementById('saveDisplayPrefsBtn'));
        }

        function exportData() {
            showNotification("Esportazione dati in corso...", "info");
            // window.location.href = 'export_data.php?type=products';
        }
        
        // --- Placeholder functions ---
        function changePassword() {
            showNotification("Funzione non implementata in questa demo.", "info");
        }
        function setup2FA() {
             showNotification("Funzione non implementata in questa demo.", "info");
        }
        function deleteAllData() {
             Swal.fire({
                title: 'Sei assolutamente sicuro?',
                text: "Questa azione eliminerà tutti i dati e non potrà essere annullata!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--brand-red)',
                cancelButtonColor: 'var(--text-secondary)',
                confirmButtonText: 'Sì, elimina tutto!',
                cancelButtonText: 'Annulla'
            }).then((result) => {
                if (result.isConfirmed) {
                    showNotification("Azione non implementata in questa demo.", "info");
                }
            })
        }
    </script>
</body>
</html>

