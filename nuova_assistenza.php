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
      --brand-color: #28a745;
      --brand-dark: #218838;
      --brand-light: #e8f5e9;
      --text-dark: #1a1a2e;
      --text-medium: #4a5568;
      --text-light: #718096;
      --border-color: #e2e8f0;
      --bg-light: #f7fafc;
      --bg-white: #ffffff;
      --success-color: #28a745;
      --warning-color: #f59e0b;
      --danger-color: #ef4444;
      --info-color: #3b82f6;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
      --shadow-md: 0 4px 15px rgba(0,0,0,0.1);
      --shadow-lg: 0 15px 40px rgba(0,0,0,0.15);
    }
    
    * {
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      color: var(--text-dark);
      margin: 0;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      padding: 2rem;
    }

    .placeholder-content { 
      text-align: center; 
      color: white;
      padding: 2rem;
    }
    .placeholder-content h1 {
      font-size: 2rem;
      margin-bottom: 1rem;
    }

    /* === POPUP OVERLAY === */
    .popup-overlay {
        position: fixed;
        inset: 0;
        background: linear-gradient(135deg, rgba(26,26,46,0.8) 0%, rgba(22,33,62,0.9) 100%);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 1rem;
        animation: fadeInOverlay 0.3s ease;
    }
    
    @keyframes fadeInOverlay { from { opacity: 0; } to { opacity: 1; } }

    /* === WIZARD CONTAINER === */
    .wizard-container {
      width: 100%;
      max-width: 900px;
      background: var(--bg-white);
      border-radius: 24px;
      box-shadow: var(--shadow-lg);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      max-height: 90vh;
      position: relative;
      animation: slideUpPopup 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    @keyframes slideUpPopup { 
      from { opacity: 0; transform: translateY(30px) scale(0.95); } 
      to { opacity: 1; transform: translateY(0) scale(1); } 
    }

    /* === CLOSE BUTTON === */
    .close-btn {
        position: absolute;
        top: 20px;
        right: 25px;
        width: 36px;
        height: 36px;
        background: rgba(255,255,255,0.2);
        border: none;
        border-radius: 50%;
        font-size: 1.5rem;
        color: white;
        cursor: pointer;
        z-index: 10;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    .close-btn:hover { 
      background: rgba(255,255,255,0.3);
      transform: rotate(90deg);
    }
    
    /* === WIZARD HEADER === */
    .wizard-header {
      background: linear-gradient(135deg, var(--brand-color) 0%, #20c997 100%);
      padding: 2rem 2.5rem;
      position: relative;
      overflow: hidden;
    }
    .wizard-header::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -20%;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      pointer-events: none;
    }
    .wizard-header::after {
      content: '';
      position: absolute;
      bottom: -30%;
      left: -10%;
      width: 200px;
      height: 200px;
      background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
      pointer-events: none;
    }
    
    .header-content {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
      position: relative;
      z-index: 2;
    }
    
    .header-icon {
      width: 50px;
      height: 50px;
      background: rgba(255,255,255,0.2);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .header-icon svg {
      width: 28px;
      height: 28px;
      stroke: white;
    }
    
    .wizard-header h1 {
      margin: 0;
      font-size: 1.6rem;
      font-weight: 700;
      color: white;
      letter-spacing: -0.5px;
    }

    /* === STEPPER NAVIGATION === */
    .stepper-nav {
      display: flex;
      justify-content: center;
      padding: 0;
      background: var(--bg-white);
      border-bottom: 1px solid var(--border-color);
    }
    
    .stepper-wrapper {
      display: flex;
      align-items: center;
      padding: 1.5rem 2rem;
      gap: 0;
      max-width: 600px;
      width: 100%;
    }
    
    .step {
      display: flex;
      align-items: center;
      flex-direction: column;
      text-align: center;
      position: relative;
      flex: 1;
      cursor: pointer;
    }
    
    .step-bubble {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: var(--bg-light);
      border: 2px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      z-index: 2;
    }
    .step-bubble svg {
      width: 22px;
      height: 22px;
      stroke: var(--text-light);
      stroke-width: 2;
      transition: all 0.3s ease;
    }
    
    .step-label { 
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--text-light);
      margin-top: 8px;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* Step connector line */
    .step:not(:last-child)::after {
      content: '';
      position: absolute;
      top: 24px;
      left: calc(50% + 30px);
      width: calc(100% - 60px);
      height: 3px;
      background: var(--border-color);
      z-index: 1;
      transition: all 0.4s ease;
    }
    
    /* Active step */
    .step.active .step-bubble {
      background: var(--brand-light);
      border-color: var(--brand-color);
      box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.15);
    }
    .step.active .step-bubble svg { stroke: var(--brand-color); }
    .step.active .step-label { color: var(--brand-color); }
    
    /* Completed step */
    .step.completed .step-bubble {
      background: var(--brand-color);
      border-color: var(--brand-color);
    }
    .step.completed .step-bubble svg { stroke: white; }
    .step.completed .step-label { color: var(--brand-color); }
    .step.completed::after { background: var(--brand-color); }

    /* === WIZARD BODY === */
    .wizard-body {
      padding: 2rem 2.5rem;
      overflow-y: auto;
      flex: 1;
      background: var(--bg-light);
    }
    
    .step-pane { 
      display: none;
      animation: fadeInStep 0.4s ease;
    }
    .step-pane.active { display: block; }
    
    @keyframes fadeInStep { 
      from { opacity: 0; transform: translateX(20px); } 
      to { opacity: 1; transform: translateX(0); } 
    }

    /* === FORM STYLES === */
    .form-card {
      background: white;
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: var(--shadow-sm);
      margin-bottom: 1rem;
    }
    
    .form-card-title {
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-light);
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .form-card-title svg {
      width: 16px;
      height: 16px;
      stroke: var(--brand-color);
    }

    .form-grid { 
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.25rem;
    }
    
    .form-group { 
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .form-group.full-width { grid-column: 1 / -1; }
    
    label { 
      font-weight: 600;
      font-size: 0.85rem;
      color: var(--text-medium);
    }
    
    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    .input-wrapper .input-icon {
      position: absolute;
      left: 14px;
      width: 18px;
      height: 18px;
      stroke: var(--text-light);
      pointer-events: none;
      transition: stroke 0.2s ease;
    }
    .input-wrapper input:focus + .input-icon,
    .input-wrapper select:focus + .input-icon {
      stroke: var(--brand-color);
    }
    
    input, select, textarea {
      width: 100%;
      padding: 0.85rem 1rem;
      padding-left: 44px;
      border: 2px solid var(--border-color);
      border-radius: 10px;
      font-size: 0.95rem;
      font-family: inherit;
      color: var(--text-dark);
      background: white;
      transition: all 0.2s ease;
    }
    input:not([type="checkbox"]):focus,
    select:focus,
    textarea:focus {
      border-color: var(--brand-color);
      outline: none;
      box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
    }
    
    input::placeholder { color: var(--text-light); }
    
    textarea {
      padding-left: 1rem;
      resize: vertical;
      min-height: 100px;
    }
    
    /* Select without icon */
    select.no-icon { padding-left: 1rem; }
    
    /* Readonly input */
    input[readonly] {
      background: var(--bg-light);
      cursor: not-allowed;
    }

    /* === CLIENTE INPUT SPECIAL === */
    .cliente-search-wrapper {
      position: relative;
    }
    
    .cliente-input-row {
      display: flex;
      gap: 10px;
      align-items: stretch;
    }
    
    .cliente-input-row .input-wrapper {
      flex: 1;
    }
    
    .add-cliente-btn {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      background: var(--brand-color);
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      flex-shrink: 0;
    }
    .add-cliente-btn:hover {
      background: var(--brand-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }
    .add-cliente-btn svg {
      width: 22px;
      height: 22px;
      stroke: white;
    }
    
    /* Telefono chip */
    .telefono-display {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 0.85rem 1rem;
      background: var(--bg-light);
      border: 2px dashed var(--border-color);
      border-radius: 10px;
      min-height: 52px;
    }
    .telefono-display .phone-icon {
      width: 20px;
      height: 20px;
      stroke: var(--text-light);
    }
    .telefono-display .phone-number {
      font-size: 0.95rem;
      color: var(--text-medium);
      font-weight: 500;
    }
    .telefono-display.has-phone {
      background: var(--brand-light);
      border-color: var(--brand-color);
      border-style: solid;
    }
    .telefono-display.has-phone .phone-icon {
      stroke: var(--brand-color);
    }
    .telefono-display.has-phone .phone-number {
      color: var(--brand-dark);
      font-weight: 600;
    }

    /* === PATTERN LOCK === */
    .pattern-section {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 1.5rem;
      background: var(--bg-light);
      border-radius: 12px;
    }
    
    .pattern-lock {
      width: 200px;
      height: 200px;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 25px;
      position: relative;
      user-select: none;
      touch-action: none;
      background: white;
      padding: 20px;
      border-radius: 16px;
      box-shadow: var(--shadow-sm);
    }
    
    #pattern-canvas {
      position: absolute;
      top: 20px;
      left: 20px;
      pointer-events: none;
      z-index: 1;
    }
    
    .pattern-dot {
      width: 100%;
      height: 100%;
      background: var(--bg-light);
      border-radius: 50%;
      border: 3px solid var(--border-color);
      cursor: pointer;
      z-index: 2;
      transition: all 0.2s ease;
    }
    .pattern-dot:hover {
      border-color: var(--brand-color);
      background: var(--brand-light);
    }
    .pattern-dot.selected {
      background: var(--brand-color);
      border-color: var(--brand-dark);
      transform: scale(1.1);
    }
    
    .pattern-hint {
      margin-top: 1rem;
      font-size: 0.8rem;
      color: var(--text-light);
    }

    /* === CHECKBOX STYLE === */
    .checkbox-wrapper {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 1rem;
      background: var(--bg-light);
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.2s ease;
      border: 2px solid transparent;
    }
    .checkbox-wrapper:hover {
      background: var(--brand-light);
    }
    .checkbox-wrapper.checked {
      background: var(--brand-light);
      border-color: var(--brand-color);
    }
    .checkbox-wrapper input[type="checkbox"] {
      width: 20px;
      height: 20px;
      padding: 0;
      accent-color: var(--brand-color);
      cursor: pointer;
    }
    .checkbox-wrapper label {
      cursor: pointer;
      margin: 0;
    }

    /* === WIZARD FOOTER === */
    .wizard-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.25rem 2.5rem;
      background: white;
      border-top: 1px solid var(--border-color);
    }
    
    /* Progress bar */
    .progress-section {
      flex: 1;
      max-width: 300px;
      margin: 0 2rem;
    }
    .progress-bar {
      height: 6px;
      background: var(--border-color);
      border-radius: 3px;
      overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--brand-color), #20c997);
      border-radius: 3px;
      transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .progress-text {
      font-size: 0.75rem;
      color: var(--text-light);
      margin-top: 6px;
      text-align: center;
    }
    
    /* Buttons */
    .wizard-btn {
      padding: 0.85rem 2rem;
      font-size: 0.95rem;
      font-weight: 600;
      font-family: inherit;
      border-radius: 10px;
      border: none;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .wizard-btn svg {
      width: 18px;
      height: 18px;
    }
    
    .wizard-btn.prev {
      background: var(--bg-light);
      color: var(--text-medium);
      border: 2px solid var(--border-color);
    }
    .wizard-btn.prev:hover {
      background: var(--border-color);
    }
    .wizard-btn.prev svg {
      stroke: var(--text-medium);
    }
    
    .wizard-btn.next,
    .wizard-btn.submit {
      background: linear-gradient(135deg, var(--brand-color) 0%, #20c997 100%);
      color: white;
      box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }
    .wizard-btn.next:hover,
    .wizard-btn.submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }
    .wizard-btn.next svg,
    .wizard-btn.submit svg {
      stroke: white;
    }
    
    .wizard-btn:disabled {
      background: var(--border-color);
      color: var(--text-light);
      cursor: not-allowed;
      box-shadow: none;
      transform: none;
    }
    
    /* === FEEDBACK MESSAGES === */
    .feedback {
      padding: 1rem 1.5rem;
      margin-bottom: 1.5rem;
      border-radius: 12px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .feedback svg {
      width: 24px;
      height: 24px;
      flex-shrink: 0;
    }
    .feedback.success {
      background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
      color: #065f46;
    }
    .feedback.success svg { stroke: #065f46; }
    .feedback.error {
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      color: #991b1b;
    }
    .feedback.error svg { stroke: #991b1b; }

    /* === RESPONSIVE === */
    @media (max-width: 768px) {
      body { padding: 0.5rem; }
      
      .wizard-container {
        border-radius: 16px;
        max-height: 95vh;
      }
      
      .wizard-header { padding: 1.5rem; }
      .wizard-header h1 { font-size: 1.3rem; }
      
      .stepper-wrapper { padding: 1rem; gap: 0; }
      .step-bubble { width: 40px; height: 40px; }
      .step-bubble svg { width: 18px; height: 18px; }
      .step-label { font-size: 0.65rem; }
      .step:not(:last-child)::after {
        top: 20px;
        left: calc(50% + 24px);
        width: calc(100% - 48px);
      }
      
      .wizard-body { padding: 1.5rem; }
      .form-card { padding: 1.25rem; }
      .form-grid { gap: 1rem; }
      
      .wizard-footer {
        flex-direction: column;
        gap: 1rem;
        padding: 1rem 1.5rem;
      }
      .progress-section {
        order: -1;
        max-width: 100%;
        margin: 0;
        width: 100%;
      }
      .wizard-btn {
        width: 100%;
        justify-content: center;
      }
    }
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
        
        <!-- HEADER -->
        <div class="wizard-header">
            <div class="header-content">
                <div class="header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                    </svg>
                </div>
                <h1>Nuova Scheda di Riparazione</h1>
            </div>
        </div>

        <!-- STEPPER -->
        <div class="stepper-nav">
            <div class="stepper-wrapper">
                <div class="step active" data-step="1">
                    <div class="step-bubble">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="step-label">Cliente</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-bubble">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                            <line x1="12" y1="18" x2="12.01" y2="18"></line>
                        </svg>
                    </div>
                    <div class="step-label">Dispositivo</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-bubble">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <div class="step-label">Sblocco</div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-bubble">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                        </svg>
                    </div>
                    <div class="step-label">Laboratorio</div>
                </div>
            </div>
        </div>
        
        <form method="POST" action="" id="riparazione-form">
            <div class="wizard-body">
                <?php if(!empty($feedback_message)): ?>
                    <div class="feedback <?= strpos($feedback_message, 'success') !== false ? 'success' : 'error' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if(strpos($feedback_message, 'success') !== false): ?>
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            <?php else: ?>
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            <?php endif; ?>
                        </svg>
                        <?= strip_tags($feedback_message, '<div>') ?>
                    </div>
                <?php endif; ?>
                
                <!-- Step 1: Cliente -->
                <div class="step-pane active" data-step="1">
                    <div class="form-card">
                        <div class="form-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Dati Cliente
                        </div>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="cliente">Seleziona Cliente *</label>
                                <div class="cliente-input-row">
                                    <div class="input-wrapper">
                                        <input type="text" id="cliente-search" placeholder="Cerca o seleziona cliente..." autocomplete="off">
                                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        </svg>
                                    </div>
                                    <button type="button" class="add-cliente-btn" onclick="window.location.href='add_cliente.php'" title="Aggiungi nuovo cliente">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="5" x2="12" y2="19"></line>
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                        </svg>
                                    </button>
                                </div>
                                <select id="cliente" name="cliente_id" required style="display:none;">
                                    <option value="">-- Seleziona --</option>
                                    <?php foreach ($clienti as $cliente): ?>
                                    <option value="<?php echo (int)$cliente['id']; ?>"><?php echo htmlspecialchars($cliente['cognome'] . ' ' . $cliente['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="cliente-suggestions" style="display:none; position:absolute; top:100%; left:0; right:60px; background:white; border:2px solid var(--brand-color); border-radius:10px; max-height:200px; overflow-y:auto; z-index:100; box-shadow: var(--shadow-md);"></div>
                            </div>
                            <div class="form-group full-width">
                                <label>Telefono</label>
                                <div class="telefono-display" id="telefono-display">
                                    <svg class="phone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    </svg>
                                    <span class="phone-number" id="telefono-text">Seleziona un cliente...</span>
                                </div>
                                <input type="hidden" id="telefono" name="telefono">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Dispositivo -->
                <div class="step-pane" data-step="2">
                    <div class="form-card">
                        <div class="form-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                                <line x1="12" y1="18" x2="12.01" y2="18"></line>
                            </svg>
                            Informazioni Dispositivo
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="modello">Modello Dispositivo *</label>
                                <div class="input-wrapper">
                                    <input type="text" id="modello" name="modello" required autocomplete="off" placeholder="es. iPhone 14 Pro">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                                        <line x1="12" y1="18" x2="12.01" y2="18"></line>
                                    </svg>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="imei">IMEI / Seriale</label>
                                <div class="input-wrapper">
                                    <input type="text" id="imei" name="imei" autocomplete="off" placeholder="Inserisci IMEI o seriale">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="4" rx="1"></rect>
                                        <path d="M3 8h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="form-group full-width">
                                <label for="diagnosi">Diagnosi / Problema riscontrato</label>
                                <textarea id="diagnosi" name="diagnosi" rows="4" placeholder="Descrivi il problema segnalato dal cliente..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Sblocco -->
                <div class="step-pane" data-step="3">
                    <div class="form-card">
                        <div class="form-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            Codici di Sblocco
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="codice_sblocco">Codice PIN / Password</label>
                                <div class="input-wrapper">
                                    <input type="text" id="codice_sblocco" name="codice_sblocco" autocomplete="off" placeholder="es. 123456">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="account">Account (Google, iCloud...)</label>
                                <div class="input-wrapper">
                                    <input type="text" id="account" name="account" autocomplete="off" placeholder="es. email@esempio.com">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="4"></circle>
                                        <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="form-group full-width">
                                <label>Codice Sblocco Grafico (Pattern)</label>
                                <div class="pattern-section">
                                    <div id="pattern-lock" class="pattern-lock">
                                        <canvas id="pattern-canvas" width="160" height="160"></canvas>
                                        <div class="pattern-dot" data-dot="1"></div>
                                        <div class="pattern-dot" data-dot="2"></div>
                                        <div class="pattern-dot" data-dot="3"></div>
                                        <div class="pattern-dot" data-dot="4"></div>
                                        <div class="pattern-dot" data-dot="5"></div>
                                        <div class="pattern-dot" data-dot="6"></div>
                                        <div class="pattern-dot" data-dot="7"></div>
                                        <div class="pattern-dot" data-dot="8"></div>
                                        <div class="pattern-dot" data-dot="9"></div>
                                    </div>
                                    <input type="hidden" id="unlock-pattern" name="codice_sblocco_grafico" />
                                    <p class="pattern-hint">Disegna il pattern trascinando sui punti</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 4: Laboratorio -->
                <div class="step-pane" data-step="4">
                    <div class="form-card">
                        <div class="form-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            Costi e Preventivo
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="costo_preventivato">Costo Preventivato (€)</label>
                                <div class="input-wrapper">
                                    <input type="number" id="costo_preventivato" name="costo_preventivato" step="0.01" min="0" placeholder="0.00">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="costo_effettivo">Costo Effettivo (€)</label>
                                <div class="input-wrapper">
                                    <input type="number" id="costo_effettivo" name="costo_effettivo" step="0.01" min="0" placeholder="0.00">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <div class="form-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                            </svg>
                            Dettagli Lavorazione
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="hardware_ritirato">Hardware ritirato</label>
                                <div class="input-wrapper">
                                    <input type="text" id="hardware_ritirato" name="hardware_ritirato" placeholder="es. Caricatore, cover...">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="9 11 12 14 22 4"></polyline>
                                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="dispositivo_sostitutivo">Dispositivo sostitutivo</label>
                                <div class="input-wrapper">
                                    <input type="text" id="dispositivo_sostitutivo" name="dispositivo_sostitutivo" placeholder="Lasciato al cliente?">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="17 1 21 5 17 9"></polyline>
                                        <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                                        <polyline points="7 23 3 19 7 15"></polyline>
                                        <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="stato">Stato lavorazione</label>
                                <select id="stato" name="stato" class="no-icon">
                                    <option value="In attesa">⏳ In attesa</option>
                                    <option value="In lavorazione">🔧 In lavorazione</option>
                                    <option value="Completata">✅ Completata</option>
                                    <option value="In attesa di ricambi">📦 In attesa di ricambi</option>
                                    <option value="Non riparabile">❌ Non riparabile</option>
                                    <option value="Consegnata">📤 Consegnata</option>
                                    <option value="Annullata">🚫 Annullata</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <label class="checkbox-wrapper" onclick="this.classList.toggle('checked')">
                                    <input type="checkbox" id="salva_dati" name="salva_dati" value="1">
                                    <span>Richiesto salvataggio dati</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wizard-footer">
                <button type="button" class="wizard-btn prev" id="prev-btn" style="display: none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Indietro
                </button>
                
                <div class="progress-section">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill" style="width: 25%"></div>
                    </div>
                    <div class="progress-text" id="progress-text">Step 1 di 4</div>
                </div>
                
                <button type="button" class="wizard-btn next" id="next-btn">
                    Avanti
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
                <button type="submit" class="wizard-btn submit" id="submit-btn" style="display: none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Salva Scheda
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // === POPUP MANAGEMENT ===
    (() => {
        const popup = document.getElementById('popup');
        const openBtn = document.getElementById('open-popup-btn');
        const closeBtn = document.getElementById('close-popup-btn');

        const openPopup = () => popup.style.display = 'flex';
        const closePopup = () => popup.style.display = 'none';

        openBtn.addEventListener('click', openPopup);
        closeBtn.addEventListener('click', closePopup);
        
        popup.addEventListener('click', (e) => {
            if (e.target === popup) closePopup();
        });

        <?php if (strpos($feedback_message, 'success') !== false): ?>
            setTimeout(() => closePopup(), 3000);
        <?php endif; ?>

        <?php if (!empty($feedback_message)): ?>
            openPopup();
        <?php else: ?>
            closePopup();
        <?php endif; ?>
    })();

    // === WIZARD NAVIGATION ===
    (() => {
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const submitBtn = document.getElementById('submit-btn');
        const stepPanes = Array.from(document.querySelectorAll('.step-pane'));
        const stepsNav = Array.from(document.querySelectorAll('.step'));
        const progressFill = document.getElementById('progress-fill');
        const progressText = document.getElementById('progress-text');
        let currentStep = 1;
        const totalSteps = stepPanes.length;

        function updateProgress(step) {
            const percent = (step / totalSteps) * 100;
            progressFill.style.width = percent + '%';
            progressText.textContent = `Step ${step} di ${totalSteps}`;
        }

        function showStep(stepNumber) {
            stepPanes.forEach(pane => pane.classList.toggle('active', parseInt(pane.dataset.step) === stepNumber));
            stepsNav.forEach(step => {
                const stepNum = parseInt(step.dataset.step);
                step.classList.remove('active', 'completed');
                if (stepNum === stepNumber) {
                    step.classList.add('active');
                } else if (stepNum < stepNumber) {
                    step.classList.add('completed');
                }
            });
            prevBtn.style.display = stepNumber > 1 ? 'flex' : 'none';
            nextBtn.style.display = stepNumber < totalSteps ? 'flex' : 'none';
            submitBtn.style.display = stepNumber === totalSteps ? 'flex' : 'none';
            currentStep = stepNumber;
            updateProgress(stepNumber);
        }

        function validateStep(stepNumber) {
            const currentPane = document.querySelector(`.step-pane[data-step="${stepNumber}"]`);
            const requiredInputs = currentPane.querySelectorAll('[required]');
            for (const input of requiredInputs) {
                if (!input.value) {
                    input.style.borderColor = '#ef4444';
                    input.focus();
                    setTimeout(() => { input.style.borderColor = ''; }, 2000);
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
            if (currentStep > 1) showStep(currentStep - 1);
        });
        
        // Allow clicking on steps to navigate
        stepsNav.forEach(step => {
            step.addEventListener('click', () => {
                const targetStep = parseInt(step.dataset.step);
                if (targetStep < currentStep || validateStep(currentStep)) {
                    showStep(targetStep);
                }
            });
        });
        
        updateProgress(1);
    })();
    
    // === CLIENTE AUTOCOMPLETE ===
    (() => {
        const telefoni = <?php echo json_encode($mappaTelefoni); ?>;
        const clienti = <?php echo json_encode(array_map(function($c) { 
            return ['id' => $c['id'], 'nome' => $c['cognome'] . ' ' . $c['nome']]; 
        }, $clienti)); ?>;
        
        const searchInput = document.getElementById('cliente-search');
        const selectCliente = document.getElementById('cliente');
        const suggestions = document.getElementById('cliente-suggestions');
        const telefonoDisplay = document.getElementById('telefono-display');
        const telefonoText = document.getElementById('telefono-text');
        const telefonoHidden = document.getElementById('telefono');
        
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            suggestions.innerHTML = '';
            
            if (query.length < 1) {
                suggestions.style.display = 'none';
                return;
            }
            
            const matches = clienti.filter(c => c.nome.toLowerCase().includes(query)).slice(0, 8);
            
            if (matches.length === 0) {
                suggestions.style.display = 'none';
                return;
            }
            
            matches.forEach(cliente => {
                const div = document.createElement('div');
                div.style.cssText = 'padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #eee; transition: background 0.2s;';
                div.innerHTML = `<span style="font-weight:500;">${cliente.nome}</span>`;
                div.addEventListener('mouseenter', () => div.style.background = '#e8f5e9');
                div.addEventListener('mouseleave', () => div.style.background = 'white');
                div.addEventListener('click', () => {
                    searchInput.value = cliente.nome;
                    selectCliente.value = cliente.id;
                    suggestions.style.display = 'none';
                    
                    // Update telefono display
                    const tel = telefoni[cliente.id] || '';
                    telefonoHidden.value = tel;
                    if (tel) {
                        telefonoText.textContent = tel;
                        telefonoDisplay.classList.add('has-phone');
                    } else {
                        telefonoText.textContent = 'Nessun telefono';
                        telefonoDisplay.classList.remove('has-phone');
                    }
                });
                suggestions.appendChild(div);
            });
            
            suggestions.style.display = 'block';
        });
        
        // Hide suggestions on click outside
        document.addEventListener('click', (e) => {
            if (!suggestions.contains(e.target) && e.target !== searchInput) {
                suggestions.style.display = 'none';
            }
        });
        
        // Also handle direct select change (for compatibility)
        selectCliente.addEventListener('change', () => {
            const idCliente = selectCliente.value;
            const tel = idCliente && telefoni[idCliente] ? telefoni[idCliente] : '';
            telefonoHidden.value = tel;
            if (tel) {
                telefonoText.textContent = tel;
                telefonoDisplay.classList.add('has-phone');
            } else {
                telefonoText.textContent = 'Seleziona un cliente...';
                telefonoDisplay.classList.remove('has-phone');
            }
        });
    })();

    // === PATTERN LOCK ===
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
        
        const clearPattern = () => {
            pattern = [];
            inputHidden.value = '';
            dots.forEach(dot => dot.classList.remove('selected'));
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        };
        
        const drawLines = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            if (pattern.length < 2) return;
            ctx.strokeStyle = '#28a745';
            ctx.lineWidth = 4;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.beginPath();
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
            const x = clientX - rect.left;
            const y = clientY - rect.top;
            return dots.find(dot => 
                Math.sqrt(Math.pow(x - (dot.offsetLeft + dot.offsetWidth/2), 2) + 
                Math.pow(y - (dot.offsetTop + dot.offsetHeight/2), 2)) < dot.offsetWidth / 2
            );
        };
        
        const onPointerDown = (e) => { e.preventDefault(); clearPattern(); isDrawing = true; };
        const onPointerMove = (e) => {
            if (!isDrawing) return;
            e.preventDefault();
            const dot = getDotFromEvent(e);
            if (dot) {
                const dotNumber = parseInt(dot.dataset.dot, 10);
                if (!pattern.includes(dotNumber)) {
                    pattern.push(dotNumber);
                    dot.classList.add('selected');
                    inputHidden.value = pattern.join('');
                    drawLines();
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
