<?php
// Impostazioni per mostrare tutti gli errori (utile in fase di sviluppo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- 1. Connessione al Database ---
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "gestionale_tsservice";

$conn = new mysqli($host, $user, $pass, $db_name);
if ($conn->connect_error) {
    die("Errore di connessione al database. Si prega di contattare l'amministratore.");
}
$conn->set_charset("utf8mb4");

// Variabile per i messaggi di feedback
$feedback_message = '';

// --- 2. Gestione dell'invio del form (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanifica e raccoglie tutti i dati dal form
    $cliente_id = (int)$_POST['cliente_id'];
    $modello = $conn->real_escape_string($_POST['modello']);
    $imei = $conn->real_escape_string($_POST['imei']);
    $codice_sblocco = $conn->real_escape_string($_POST['codice_sblocco']);
    $codice_sblocco_grafico = $conn->real_escape_string($_POST['codice_sblocco_grafico']);
    $account = $conn->real_escape_string($_POST['account']);
    $diagnosi = $conn->real_escape_string($_POST['diagnosi']);
    $salva_dati = isset($_POST['salva_dati']) ? 1 : 0;
    $costo_preventivato = !empty($_POST['costo_preventivato']) ? (float)$_POST['costo_preventivato'] : 'NULL';
    $costo_effettivo = !empty($_POST['costo_effettivo']) ? (float)$_POST['costo_effettivo'] : 'NULL';
    $hardware_ritirato = $conn->real_escape_string($_POST['hardware_ritirato']);
    $dispositivo_sostitutivo = $conn->real_escape_string($_POST['dispositivo_sostitutivo']);
    $stato = $conn->real_escape_string($_POST['stato']);
    
    // Query di inserimento COMPLETA
    $sql_insert = "INSERT INTO riparazioni (
        cliente_id, modello, imei, codice_sblocco, codice_sblocco_grafico, 
        account_collegati, diagnosi, salvataggio_dati, costo_preventivato, 
        costo_effettivo, hardware_ritirato, dispositivo_sostitutivo, stato, data_creazione
    ) VALUES (
        '$cliente_id', '$modello', '$imei', '$codice_sblocco', '$codice_sblocco_grafico',
        '$account', '$diagnosi', '$salva_dati', $costo_preventivato, 
        $costo_effettivo, '$hardware_ritirato', '$dispositivo_sostitutivo', '$stato', NOW()
    )";

    if ($conn->query($sql_insert) === TRUE) {
        $feedback_message = "<div class='feedback success'>Scheda di riparazione salvata con successo! Il popup si chiuderà tra 3 secondi.</div>";
    } else {
        $feedback_message = "<div class='feedback error'>Errore durante il salvataggio: " . $conn->error . "</div>";
    }
}

// --- 3. Recupero dati per popolare il form (Clienti) ---
$clienti = [];
$mappaTelefoni = [];
$sql_clienti = "SELECT id, nome, cognome, telefono FROM clienti_nuovo ORDER BY cognome, nome";
$result_clienti = $conn->query($sql_clienti);
if ($result_clienti && $result_clienti->num_rows > 0) {
    while ($row = $result_clienti->fetch_assoc()) {
        $clienti[] = $row;
        $mappaTelefoni[(int)$row['id']] = htmlspecialchars($row['telefono']);
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nuova Scheda Riparazione - Popup</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --brand-color: #3498db;
      --brand-dark: #2980b9;
      --text-dark: #34495e;
      --text-light: #7f8c8d;
      --border-color: #ecf0f1;
      --bg-light: #f7f9fc;
      --bg-white: #ffffff;
      --success-color: #2ecc71;
      --shadow-md: 0 5px 20px rgba(0, 0, 0, 0.08);
      --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      color: var(--text-dark);
      margin: 0;
      /* Sfondo di esempio per mostrare l'effetto popup */
      background: linear-gradient(to right, #ece9e6, #ffffff);
      padding: 2rem;
    }

    .placeholder-content { text-align: center; }

    /* Stile per l'overlay del popup */
    .popup-overlay {
        position: fixed;
        inset: 0; /* Equivalente a top:0, right:0, bottom:0, left:0 */
        background-color: rgba(52, 73, 94, 0.6); /* Sfondo scuro semi-trasparente */
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 1rem;
        animation: fadeInOverlay 0.3s ease;
    }
    
    @keyframes fadeInOverlay { from { opacity: 0; } to { opacity: 1; } }

    .wizard-container {
      width: 100%;
      max-width: 850px;
      background-color: var(--bg-white);
      border-radius: 16px;
      box-shadow: var(--shadow-lg);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      max-height: 95vh;
      position: relative; /* Necessario per posizionare il pulsante di chiusura */
      animation: scaleInPopup 0.4s ease;
    }
     @keyframes scaleInPopup { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }

    .close-btn {
        position: absolute;
        top: 10px;
        right: 20px;
        background: transparent;
        border: none;
        font-size: 2rem;
        color: #bdc3c7;
        cursor: pointer;
        z-index: 10;
        transition: color 0.2s ease;
    }
    .close-btn:hover { color: var(--text-dark); }
    
    .wizard-header {
      padding: 1.5rem 2.5rem;
      border-bottom: 1px solid var(--border-color);
      flex-shrink: 0;
    }
    
    .wizard-header h1 {
        text-align: center; margin: 0; font-size: 1.8rem;
        font-weight: 600; color: var(--text-dark);
    }

    .stepper-nav {
      display: flex; justify-content: space-between;
      padding: 1.5rem 2.5rem; border-bottom: 1px solid var(--border-color);
      background-color: #fafafa; flex-shrink: 0;
    }
    .step {
      display: flex; align-items: center; flex-direction: column;
      text-align: center; position: relative; flex: 1;
    }
    .step-icon {
      width: 40px; height: 40px; border-radius: 50%;
      background-color: var(--border-color); color: var(--text-light);
      display: flex; align-items: center; justify-content: center;
      font-weight: 600; transition: all 0.3s ease;
      border: 3px solid var(--border-color); z-index: 2;
    }
    .step-label { font-size: 0.8rem; font-weight: 500; color: var(--text-light); margin-top: 0.5rem; transition: all 0.3s ease; }
    .step.active .step-icon, .step.completed .step-icon { background-color: var(--brand-color); border-color: var(--brand-color); color: white; }
    .step.completed .step-icon { background-color: var(--success-color); border-color: var(--success-color); }
    .step.active .step-label { color: var(--brand-color); font-weight: 600; }
    .step.completed .step-label { color: var(--success-color); }
    .step:not(:last-child)::after {
      content: ''; position: absolute; top: 20px; left: 50%;
      width: 100%; height: 3px; background-color: var(--border-color);
      z-index: 1; transition: background-color 0.3s ease;
    }
    .step.completed::after { background-color: var(--success-color); }

    .wizard-body {
        padding: 2rem 2.5rem;
        overflow-y: auto; /* Permette lo scrolling se il contenuto è troppo */
    }
    
    .step-pane { display: none; animation: fadeIn 0.5s ease; }
    .step-pane.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
    .form-group.full-width { grid-column: 1 / -1; }
    label { font-weight: 500; font-size: 0.9rem; color: var(--text-dark); }
    input, select, textarea {
      width: 100%; padding: 0.75rem 1rem; border: 1px solid #dcdfe6;
      border-radius: 8px; font-size: 1rem; color: var(--text-dark);
      box-sizing: border-box; transition: all 0.2s ease;
    }
    input:focus, select:focus, textarea:focus {
      border-color: var(--brand-color); outline: none; box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    }
    .cliente-input { display: flex; gap: 0.75rem; }

    .wizard-footer {
      display: flex; justify-content: space-between;
      padding: 1.5rem 2.5rem; border-top: 1px solid var(--border-color);
      background-color: #fdfdfd; flex-shrink: 0;
    }
    .wizard-btn {
      padding: 0.7rem 2rem; font-size: 1rem; font-weight: 600;
      border-radius: 8px; border: none; cursor: pointer;
      transition: all 0.2s ease;
    }
    .wizard-btn.prev { background-color: #ecf0f1; color: var(--text-light); }
    .wizard-btn.next, .wizard-btn.submit { background-color: var(--brand-color); color: white; }
    .wizard-btn:disabled { background-color: #ecf0f1; cursor: not-allowed; }
    
    .pattern-lock {
        width: 180px; height: 180px; display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 20px; position: relative; user-select: none; touch-action: none;
    }
    #pattern-canvas { position: absolute; top: 0; left: 0; pointer-events: none; z-index: 1; }
    .pattern-dot {
        width: 100%; height: 100%; background: #ecf0f1; border-radius: 50%;
        border: 1px solid #dcdfe6; cursor: pointer; z-index: 2;
    }
    .pattern-dot.selected { background-color: var(--brand-color); border-color: var(--brand-dark); }
    
    .feedback { padding: 1rem 1.5rem; margin: 0 0 1.5rem 0; border-radius: 8px; font-weight: 500; border: 1px solid transparent; }
    .feedback.success { background-color: #eafaf1; border-color: #b7e1c7; color: #155724; }
    .feedback.error { background-color: #fbebee; border-color: #f5c6cb; color: #721c24; }
  </style>
</head>
<body>

<div class="placeholder-content">
    <h1>Contenuto Pagina Principale</h1>
    <p>Questa è l'area sottostante al popup, che viene oscurata.</p>
    <button type="button" class="wizard-btn next" id="open-popup-btn">Apri Scheda Riparazione</button>
</div>


<div class="popup-overlay" id="popup">
    <div class="wizard-container">
        <button type="button" class="close-btn" id="close-popup-btn">&times;</button>
        <div class="wizard-header">
            <h1>Nuova Scheda di Riparazione</h1>
        </div>

        <div class="stepper-nav">
            <div class="step active" data-step="1"><div class="step-icon">1</div><div class="step-label">Cliente</div></div>
            <div class="step" data-step="2"><div class="step-icon">2</div><div class="step-label">Dispositivo</div></div>
            <div class="step" data-step="3"><div class="step-icon">3</div><div class="step-label">Sblocco</div></div>
            <div class="step" data-step="4"><div class="step-icon">4</div><div class="step-label">Laboratorio</div></div>
        </div>
        
        <form method="POST" action="" id="riparazione-form">
            <div class="wizard-body">
                <?php if(!empty($feedback_message)) echo $feedback_message; ?>
                <!-- Step 1: Cliente -->
                <div class="step-pane active" data-step="1">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="cliente">Seleziona Cliente *</label>
                            <div class="cliente-input">
                                <select id="cliente" name="cliente_id" required>
                                    <option value="">-- Seleziona un cliente esistente --</option>
                                    <?php foreach ($clienti as $cliente): ?>
                                    <option value="<?php echo (int)$cliente['id']; ?>"><?php echo htmlspecialchars($cliente['cognome'] . ' ' . $cliente['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="wizard-btn" style="padding: 0.7rem 1rem;" onclick="window.location.href='nuovo_cliente.php'">+</button>
                            </div>
                        </div>
                        <div class="form-group full-width">
                            <label for="telefono">Telefono</label>
                            <input type="text" id="telefono" name="telefono" readonly placeholder="Il telefono apparirà qui...">
                        </div>
                    </div>
                </div>

                <!-- Step 2: Dispositivo -->
                <div class="step-pane" data-step="2">
                    <div class="form-grid">
                        <div class="form-group"><label for="modello">Modello Dispositivo *</label><input type="text" id="modello" name="modello" required autocomplete="off"></div>
                        <div class="form-group"><label for="imei">IMEI / Seriale</label><input type="text" id="imei" name="imei" autocomplete="off"></div>
                        <div class="form-group full-width"><label for="diagnosi">Diagnosi / Problema riscontrato</label><textarea id="diagnosi" name="diagnosi" rows="4"></textarea></div>
                    </div>
                </div>

                <!-- Step 3: Sblocco -->
                <div class="step-pane" data-step="3">
                     <div class="form-grid">
                        <div class="form-group">
                            <label for="codice_sblocco">Codice Sblocco (PIN / Password)</label><input type="text" id="codice_sblocco" name="codice_sblocco" autocomplete="off">
                            <label for="account" style="margin-top: 1.5rem;">Account collegati (Google, iCloud, etc.)</label><input type="text" id="account" name="account" autocomplete="off">
                        </div>
                        <div class="form-group" style="align-items:center;"><label>Codice Sblocco Grafico (Pattern)</label><div id="pattern-lock" class="pattern-lock"><canvas id="pattern-canvas" width="180" height="180"></canvas><div class="pattern-dot" data-dot="1"></div><div class="pattern-dot" data-dot="2"></div><div class="pattern-dot" data-dot="3"></div><div class="pattern-dot" data-dot="4"></div><div class="pattern-dot" data-dot="5"></div><div class="pattern-dot" data-dot="6"></div><div class="pattern-dot" data-dot="7"></div><div class="pattern-dot" data-dot="8"></div><div class="pattern-dot" data-dot="9"></div></div><input type="hidden" id="unlock-pattern" name="codice_sblocco_grafico" /></div>
                    </div>
                </div>
                
                <!-- Step 4: Laboratorio -->
                <div class="step-pane" data-step="4">
                    <div class="form-grid">
                        <div class="form-group"><label for="costo_preventivato">Costo Preventivato (€)</label><input type="number" id="costo_preventivato" name="costo_preventivato" step="0.01" min="0"></div>
                        <div class="form-group"><label for="costo_effettivo">Costo Effettivo (€)</label><input type="number" id="costo_effettivo" name="costo_effettivo" step="0.01" min="0"></div>
                        <div class="form-group full-width"><label for="hardware_ritirato">Hardware ritirato</label><input type="text" id="hardware_ritirato" name="hardware_ritirato"></div>
                        <div class="form-group"><label for="dispositivo_sostitutivo">Dispositivo sostitutivo</label><input type="text" id="dispositivo_sostitutivo" name="dispositivo_sostitutivo"></div>
                        <div class="form-group"><label for="stato">Stato lavorazione</label><select id="stato" name="stato"><option value="In attesa">In attesa</option><option value="In lavorazione">In lavorazione</option><option value="Completata">Completata</option><option value="In attesa di ricambi">In attesa di ricambi</option><option value="Non riparabile">Non riparabile</option><option value="Consegnata">Consegnata</option><option value="Annullata">Annullata</option></select></div>
                        <div class="form-group" style="flex-direction: row; align-items:center;"><input type="checkbox" id="salva_dati" name="salva_dati" value="1" style="width:auto;margin-right:10px;"><label for="salva_dati">Richiesto salvataggio dati</label></div>
                    </div>
                </div>
            </div>

            <div class="wizard-footer">
                <button type="button" class="wizard-btn prev" id="prev-btn" style="display: none;">Indietro</button>
                <button type="button" class="wizard-btn next" id="next-btn">Avanti</button>
                <button type="submit" class="wizard-btn submit" id="submit-btn" style="display: none;">Salva Scheda</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Script per la gestione del Popup
    (() => {
        const popup = document.getElementById('popup');
        const openBtn = document.getElementById('open-popup-btn');
        const closeBtn = document.getElementById('close-popup-btn');

        const openPopup = () => popup.style.display = 'flex';
        const closePopup = () => popup.style.display = 'none';

        openBtn.addEventListener('click', openPopup);
        closeBtn.addEventListener('click', closePopup);
        
        // Chiude il popup se si clicca sull'overlay esterno
        popup.addEventListener('click', (e) => {
            if (e.target === popup) {
                closePopup();
            }
        });

        // Se PHP ha mostrato un messaggio di successo, chiudi il popup dopo 3 secondi
        <?php if (strpos($feedback_message, 'success') !== false): ?>
            setTimeout(() => {
                // Idealmente qui si ricaricherebbe la pagina o si farebbe un redirect.
                // Per ora, nascondiamo semplicemente il popup.
                closePopup();
            }, 3000);
        <?php endif; ?>

        // Se c'è un messaggio di feedback (errore o successo), il popup deve essere visibile
        <?php if (!empty($feedback_message)): ?>
            openPopup();
        <?php else: ?>
            // Nascondi il popup al caricamento iniziale se non ci sono messaggi
            closePopup();
        <?php endif; ?>

    })();

    // Script per il Wizard
    (() => {
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const submitBtn = document.getElementById('submit-btn');
        const stepPanes = Array.from(document.querySelectorAll('.step-pane'));
        const stepsNav = Array.from(document.querySelectorAll('.step'));
        let currentStep = 1;
        const totalSteps = stepPanes.length;

        function showStep(stepNumber) {
            stepPanes.forEach(pane => pane.classList.toggle('active', parseInt(pane.dataset.step) === stepNumber));
            stepsNav.forEach(step => {
                const stepNum = parseInt(step.dataset.step);
                step.classList.toggle('active', stepNum === stepNumber);
                if (!step.classList.contains('completed')) {
                   step.classList.toggle('completed', stepNum < stepNumber);
                }
            });
            prevBtn.style.display = stepNumber > 1 ? 'inline-block' : 'none';
            nextBtn.style.display = stepNumber < totalSteps ? 'inline-block' : 'none';
            submitBtn.style.display = stepNumber === totalSteps ? 'inline-block' : 'none';
            currentStep = stepNumber;
        }

        function validateStep(stepNumber) {
            const currentPane = document.querySelector(`.step-pane[data-step="${stepNumber}"]`);
            const requiredInputs = currentPane.querySelectorAll('[required]');
            for (const input of requiredInputs) {
                if (!input.value) {
                    // Sostituiamo l'alert con un feedback visivo più moderno
                    input.style.borderColor = '#e74c3c';
                    input.focus();
                    setTimeout(() => { input.style.borderColor = '#dcdfe6'; }, 2000);
                    return false;
                }
            }
            return true;
        }

        nextBtn.addEventListener('click', () => {
            if (validateStep(currentStep) && currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        });

        prevBtn.addEventListener('click', () => {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        });
        
        // Non inizializzare più qui, viene gestito dallo script del popup
    })();
    
    // Script per auto-compilare il telefono del cliente
    (() => {
        const telefoni = <?php echo json_encode($mappaTelefoni); ?>;
        const selectCliente = document.getElementById('cliente');
        const inputTelefono = document.getElementById('telefono');
        selectCliente.addEventListener('change', () => {
            const idCliente = selectCliente.value;
            inputTelefono.value = idCliente && telefoni[idCliente] ? telefoni[idCliente] : '';
        });
    })();

    // Script per il Pattern Lock
    (() => {
      const patternLock = document.getElementById('pattern-lock');
      if (!patternLock) return;
      const dots = Array.from(patternLock.querySelectorAll('.pattern-dot'));
      const inputHidden = document.getElementById('unlock-pattern');
      const canvas = document.getElementById('pattern-canvas');
      const ctx = canvas.getContext('2d');
      let pattern = [];
      let isDrawing = false;
      const dotCenters = dots.map(dot => ({
          x: dot.offsetLeft + dot.offsetWidth / 2,
          y: dot.offsetTop + dot.offsetHeight / 2,
      }));
      const clearPattern = () => { pattern = []; inputHidden.value = ''; dots.forEach(dot => dot.classList.remove('selected')); ctx.clearRect(0, 0, canvas.width, canvas.height); };
      const drawLines = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (pattern.length < 2) return;
        ctx.strokeStyle = 'var(--brand-color)'; ctx.lineWidth = 4;
        ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.beginPath();
        for (let i = 0; i < pattern.length; i++) {
          const { x, y } = dotCenters[pattern[i] - 1];
          i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
        }
        ctx.stroke();
      };
      const getDotFromEvent = (e) => {
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        const rect = patternLock.getBoundingClientRect();
        const x = clientX - rect.left; const y = clientY - rect.top;
        return dots.find(dot => Math.sqrt(Math.pow(x - (dot.offsetLeft + dot.offsetWidth/2), 2) + Math.pow(y - (dot.offsetTop + dot.offsetHeight/2), 2)) < dot.offsetWidth / 2);
      };
      const onPointerDown = (e) => { e.preventDefault(); clearPattern(); isDrawing = true; };
      const onPointerMove = (e) => {
        if (!isDrawing) return; e.preventDefault();
        const dot = getDotFromEvent(e);
        if (dot) {
          const dotNumber = parseInt(dot.dataset.dot, 10);
          if (!pattern.includes(dotNumber)) {
            pattern.push(dotNumber); dot.classList.add('selected');
            inputHidden.value = pattern.join(''); drawLines();
          }
        }
      };
      const onPointerUp = () => { isDrawing = false; };
      patternLock.addEventListener('mousedown', onPointerDown);
      window.addEventListener('mousemove', onPointerMove);
      window.addEventListener('mouseup', onPointerUp);
      patternLock.addEventListener('touchstart', onPointerDown, { passive: false });
      window.addEventListener('touchmove', onPointerMove, { passive: false });
      window.addEventListener('touchend', onPointerUp);
    })();
  </script>
</body>
</html>
