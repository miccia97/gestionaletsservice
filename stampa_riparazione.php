<?php
// stampa_riparazione.php

// === CONFIGURAZIONE E CONNESSIONE DATABASE ===
// Assumi che 'db.php' contenga la logica per connettersi al tuo database
// e che la variabile di connessione sia $conn.
// Esempio:
// db.php
// <?php
// $servername = "localhost";
// $username = "tuo_utente";
// $password = "tua_password";
// $dbname = "nome_tuo_database";
//
// $conn = new mysqli($servername, $username, $password, $dbname);
//
// if ($conn->connect_error) {
//     die("Connessione al database fallita: " . $conn->connect_error);
// }
// ? >
include 'db.php';

// Recupera l'ID della riparazione dalla query string (es. stampa_riparazione.php?id=123)
// Utilizza (int) per convertire in intero e prevenire SQL Injection
$id_riparazione = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verifica se l'ID della riparazione è valido
if ($id_riparazione <= 0) {
    echo "ID riparazione non valido.";
    exit; // Termina lo script se l'ID non è valido
}

// Query SQL per recuperare i dettagli della riparazione e del cliente associato
// LEFT JOIN è usato per recuperare i dati del cliente dalla tabella 'clienti_nuovo'
// basandosi sull'ID del cliente presente nella tabella 'riparazioni'.
$sql = "SELECT r.*, c.nome, c.cognome, c.telefono
        FROM riparazioni AS r
        LEFT JOIN clienti_nuovo AS c ON r.cliente_id = c.id
        WHERE r.id = $id_riparazione";

$result = $conn->query($sql); // Esegue la query sul database

// Verifica se la query ha avuto successo e ha restituito almeno una riga
if ($result && $result->num_rows > 0) {
    $riparazione = $result->fetch_assoc(); // Estrae la riga come array associativo
} else {
    echo "Riparazione con ID #$id_riparazione non trovata."; // Messaggio se la riparazione non esiste
    exit; // Termina lo script
}

$conn->close(); // Chiude la connessione al database, è buona norma farlo quando non serve più

// === FUNZIONI PER LA GENERAZIONE HTML ===

/**
 * Funzione per generare la visualizzazione di un pattern di sblocco grafico.
 * Ora modificata per mostrare il pattern come testo numerico.
 *
 * @param string $pattern_string Una stringa di numeri separati da virgole (es. "1,5,9").
 * @return string HTML che rappresenta il pattern come testo, o un placeholder se vuoto.
 */
function render_pattern_lock($pattern_string) {
    // Se la stringa del pattern è vuota o non valida, restituisce un placeholder
    if (empty($pattern_string)) {
        return '<span class="text-sm italic text-gray-500">Nessun pattern</span>';
    }

    // Restituisce direttamente la stringa del pattern come testo
    return '<span class="text-sm font-medium text-gray-800">' . htmlspecialchars($pattern_string) . '</span>';
}

/**
 * Genera la struttura HTML completa di una singola scheda di assistenza per la stampa.
 * Questa funzione può essere chiamata due volte per ottenere due copie sulla stessa pagina.
 *
 * @param array $riparazione Array associativo contenente tutti i dettagli della riparazione.
 * @param int $id_riparazione ID della riparazione corrente.
 * @param string $copy_label Etichetta per identificare la copia (es. 'COPIA CLIENTE', 'COPIA UFFICIO').
 * @return string HTML completo della scheda di assistenza.
 */
function scheda_assistenza($riparazione, $id_riparazione, $copy_label = '') {
    // Estrae i valori dall'array $riparazione, fornendo un fallback vuoto se il campo non esiste
    // Questo previene errori se un campo non è presente nel database
    $nome_cliente = htmlspecialchars($riparazione['nome'] ?? '') . ' ' . htmlspecialchars($riparazione['cognome'] ?? '');
    $telefono = htmlspecialchars($riparazione['telefono'] ?? '');
    $modello = htmlspecialchars($riparazione['modello'] ?? '');
    $imei = htmlspecialchars($riparazione['imei'] ?? '');
    $codice_sblocco = htmlspecialchars($riparazione['codice_sblocco'] ?? 'N/D');
    $codice_sblocco_grafico = htmlspecialchars($riparazione['codice_sblocco_grafico'] ?? '');
    $account = htmlspecialchars($riparazione['account'] ?? 'Nessuno');
    // Per il booleano 'salva_dati', mostra 'Sì' o 'No'
    $salva_dati = ($riparazione['salva_dati'] ?? 0) ? 'Sì' : 'No';
    $diagnosi = nl2br(htmlspecialchars($riparazione['diagnosi'] ?? 'Nessuna diagnosi iniziale.')); // nl2br per i salti di riga
    $costo_preventivato = number_format($riparazione['costo_preventivato'] ?? 0, 2, ',', '.');
    $costo_effettivo = number_format($riparazione['costo_effettivo'] ?? 0, 2, ',', '.');
    $hardware_ritirato = nl2br(htmlspecialchars($riparazione['hardware_ritirato'] ?? 'Nessuno'));
    $dispositivo_sostitutivo = htmlspecialchars($riparazione['dispositivo_sostitutivo'] ?? 'Nessuno');

    // Determina il testo e la classe CSS per il badge dello stato di riparazione
    $stato_riparazione_text = htmlspecialchars($riparazione['stato_riparazione'] ?? 'In Lavorazione');
    $stato_riparazione_class = strtolower(str_replace(' ', '-', $stato_riparazione_text)); // Converte "In Lavorazione" in "in-lavorazione"

    // Inizia a catturare l'output HTML
    ob_start();
    ?>
    <div class="scheda border border-gray-200 rounded-lg p-6">
      <div class="flex justify-between items-start pb-4 border-b-2 border-green-600 mb-6">
        <div class="flex items-center gap-4">
          <!-- Logo aziendale -->
          <!-- Assicurati che 'logo.png' sia nella cartella 'images/' relativa a questo file PHP. -->
          <!-- Se il logo non dovesse caricarsi, verrà visualizzato un placeholder grigio con testo 'LOGO'. -->
          <img src="images/logo.png" alt="Logo TS Service" class="w-24 h-10 object-contain" onerror="this.onerror=null;this.src='https://placehold.co/96x40/cccccc/333333?text=LOGO';">
          <div>
            <h2 class="text-xl font-bold text-green-800">TS SERVICE</h2>
            <p class="text-xs text-gray-600">Contrada Castromurro - 217</p>
            <p class="text-xs text-gray-600">87021 BELVEDERE M.MO (CS)</p>
            <p class="text-xs text-gray-600">Tel. 3420330279</p>
            <p class="text-xs text-gray-600">Email: info@tsservice.it</p>
          </div>
        </div>
        <div class="text-right">
          <h1 class="text-2xl font-extrabold text-gray-800 mb-1">SCHEDA DI ASSISTENZA</h1>
          <p class="text-lg font-bold text-gray-700">Riparazione #<span class="text-green-600"><?php echo $id_riparazione; ?></span></p>
          <p class="text-sm text-gray-600">Data emissione: <?php echo date('d/m/Y'); ?></p>
          <p class="text-sm text-gray-600">Stato Riparazione: <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium status-<?php echo $stato_riparazione_class; ?>"><?php echo $stato_riparazione_text; ?></span></p>
          <?php if (!empty($copy_label)): ?>
            <p class="text-base font-bold mt-2 text-green-700 uppercase"><?php echo $copy_label; ?></p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Sezione Dati Cliente -->
      <div class="mb-6">
        <h3 class="text-sm font-semibold bg-gray-100 border-l-4 border-green-600 px-4 py-2 mb-4">DATI CLIENTE</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="col-span-2 p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Nome Cliente:</strong>
            <span class="text-sm font-medium"><?php echo $nome_cliente; ?></span>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Telefono:</strong>
            <span class="text-sm font-medium"><?php echo $telefono; ?></span>
          </div>
        </div>
      </div>

      <!-- Sezione Dettagli Dispositivo -->
      <div class="mb-6">
        <h3 class="text-sm font-semibold bg-gray-100 border-l-4 border-green-600 px-4 py-2 mb-4">DETTAGLI DISPOSITIVO</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Modello:</strong>
            <span class="text-sm font-medium"><?php echo $modello; ?></span>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">IMEI:</strong>
            <span class="text-sm font-medium"><?php echo $imei; ?></span>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Codice Sblocco:</strong>
            <span class="text-sm font-medium"><?php echo $codice_sblocco; ?></span>
          </div>
          <!-- Visualizzazione del codice sblocco grafico (ora numerico) -->
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50 flex flex-col justify-start items-start">
            <strong class="block text-xs text-gray-600 mb-1">Codice Sblocco Grafico:</strong>
            <?php echo render_pattern_lock($codice_sblocco_grafico); ?>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Account Collegati:</strong>
            <span class="text-sm font-medium"><?php echo $account; ?></span>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Salvataggio Dati:</strong>
            <span class="text-sm font-medium"><?php echo $salva_dati; ?></span>
          </div>
        </div>
      </div>

      <!-- Sezione Diagnosi Iniziale -->
      <div class="p-4 border border-gray-300 rounded-lg bg-yellow-50 mb-6">
        <strong class="block text-sm text-gray-700 mb-2">Diagnosi Iniziale:</strong>
        <p class="text-sm text-gray-800 leading-relaxed"><?php echo $diagnosi; ?></p>
      </div>

      <!-- Sezione Costi e Hardware -->
      <div class="mb-6">
        <h3 class="text-sm font-semibold bg-gray-100 border-l-4 border-green-600 px-4 py-2 mb-4">COSTI E HARDWARE</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Costo Preventivato:</strong>
            <span class="text-sm font-medium">€ <?php echo $costo_preventivato; ?></span>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Costo Effettivo:</strong>
            <span class="text-sm font-medium">€ <?php echo $costo_effettivo; ?></span>
          </div>
        </div>
      </div>

      <!-- Sezione Hardware Ritirato -->
      <div class="p-4 border border-gray-300 rounded-lg bg-yellow-50 mb-6">
        <strong class="block text-sm text-gray-700 mb-2">Hardware Ritirato (Accessori):</strong>
        <p class="text-sm text-gray-800 leading-relaxed"><?php echo $hardware_ritirato; ?></p>
      </div>

      <div class="p-3 border border-gray-200 rounded-md bg-gray-50 mb-6">
        <strong class="block text-xs text-gray-600 mb-1">Dispositivo Sostitutivo:</strong>
        <span class="text-sm font-medium"><?php echo $dispositivo_sostitutivo; ?></span>
      </div>

      <!-- Sezione Note e Consenso -->
      <div class="mt-8 pt-4 border-t border-dashed border-gray-300 text-xs text-gray-700 leading-relaxed">
        <h3 class="text-sm font-semibold bg-gray-100 border-l-4 border-green-600 px-4 py-2 mb-4">NOTE E CONSENSO</h3>
        <p class="mb-2">Il cliente dichiara di aver preso visione e accettato le condizioni generali di assistenza tecnica esposte nel punto vendita. In nessun caso il punto vendita potrà essere ritenuto responsabile dei tempi necessari alla riparazione degli apparati in garanzia e dei contenuti al loro interno. Dichiaro il consenso al trattamento dei dati personali secondo il Regolamento Generale sulla Protezione dei Dati (GDPR) - Regolamento UE 2016/679.</p>
        <p>Qualsiasi reclamo o richiesta di risarcimento danni per la riparazione o per il dispositivo in assistenza dovrà essere presentato entro 7 giorni lavorativi dalla data di ritiro.</p>
      </div>

      <!-- Sezione Firme -->
      <div class="flex justify-around mt-10 pt-4 border-t border-gray-200">
        <div class="flex flex-col items-center flex-1 mx-4">
          <div class="border-b border-gray-700 w-3/4 mb-2"></div>
          <span class="text-xs text-gray-600">Firma Cliente per Accettazione</span>
        </div>
        <div class="flex flex-col items-center flex-1 mx-4">
          <div class="border-b border-gray-700 w-3/4 mb-2"></div>
          <span class="text-xs text-gray-600">Firma Tecnico/Addetto</span>
        </div>
      </div>
    </div>
    <?php
    // Restituisce il contenuto del buffer
    return ob_get_clean();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stampa Scheda Riparazione #<?php echo $id_riparazione; ?></title>
<!-- Carica Tailwind CSS dal CDN per uno styling rapido e responsivo -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Carica il font Inter da Google Fonts per un aspetto pulito e moderno -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&display=swap" rel="stylesheet">
<style>
  /* Stili di base per il body, impostando il font predefinito */
  body {
    font-family: 'Inter', sans-serif;
    color: #1A202C; /* Colore del testo scuro */
    line-height: 1.5; /* Altezza della linea per una migliore leggibilità */
  }

  /* Contenitore principale della pagina (simula un foglio A4) */
  /* Questo stile non è più strettamente necessario per due pagine separate,
     ma lo mantengo per coerenza con le versioni precedenti. */
  .print-container {
    width: 210mm; /* Larghezza A4 */
    /* L'altezza non è più fissa, perché le schede vanno su pagine diverse */
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 15mm; /* Padding generale per il foglio A4, più contenuto */
    display: flex;
    flex-direction: column;
    /* Rimuovi justify-content e gap che non servono per pagine separate */
    position: relative; /* Mantenuto per posizionamento assoluto se servisse */
    overflow: visible; /* Permette al contenuto di scorrere su più pagine */
  }

  /* Custom shadows for green elements */
  .shadow-md-green {
    box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.1), 0 2px 4px -1px rgba(16, 185, 129, 0.06);
  }

  /* Stili per i badge di stato della riparazione */
  .status-in-lavorazione { background-color: #fef3c7; color: #92400e; } /* Giallo chiaro */
  .status-completata { background-color: #d1fae5; color: #065f46; } /* Verde chiaro */
  .status-in-attesa-ricambi { background-color: #fee2e2; color: #991b1b; } /* Rosso chiaro */
  .status-pronta-al-ritiro { background-color: #dbeafe; color: #1e40af; } /* Blu chiaro */

  /* Stili specifici per la stampa */
  @media print {
    body {
      margin: 0;
      padding: 0;
      background: none; /* Rimuove lo sfondo in stampa */
      -webkit-print-color-adjust: exact; /* Assicurati che i colori di sfondo degli elementi siano stampati */
      print-color-adjust: exact;
      font-size: 8pt; /* Dimensione base del font per la stampa */
    }
    .print-container {
      width: 210mm; /* Larghezza A4 */
      /* Altezza non fissa, gestita dal browser con i page-break */
      margin: 0;
      box-shadow: none; /* Rimuove l'ombra in stampa */
      border-radius: 0; /* Rimuove i bordi arrotondati in stampa */
      padding: 10mm; /* Padding per la stampa, sufficiente per i margini A4 */
      display: block; /* Usa display block per flow normale tra pagine */
      overflow: visible; /* Permette al contenuto di andare su nuove pagine */
    }
    .scheda {
        box-shadow: none;
        border: none; /* Rimuovi bordi e ombre per la stampa */
        padding: 0; /* Rimuovi padding sulla scheda in stampa, gestito dal container */
        /* Rimuovi l'altezza fissa, le schede si espandono per il loro contenuto */
        height: auto; /* Lascia che l'altezza sia determinata dal contenuto */
        overflow: visible; /* Permette al contenuto di fluire liberamente */
        margin-bottom: 10mm; /* Spazio tra le schede in stampa */
    }
    /* Forza una nuova pagina per la seconda scheda */
    .page-break-before {
        page-break-before: always;
        margin-top: 10mm; /* Aggiungi un po' di margine superiore all'inizio della nuova pagina */
    }

    /* Regolazioni più fini dei font e spazi per la stampa */
    .scheda * {
      line-height: 1.2 !important; /* Riduci line-height per compattezza */
      margin-bottom: 0.1rem !important; /* Riduci margini ovunque */
      padding-top: 0.1rem !important; /* Riduci padding ovunque */
      padding-bottom: 0.1rem !important; /* Riduci padding ovunque */
    }
    .scheda .text-2xl { font-size: 13pt !important; }
    .scheda .text-xl { font-size: 11.5pt !important; }
    .scheda .text-lg { font-size: 10.5pt !important; }
    .scheda .text-base { font-size: 9.5pt !important; }
    .scheda .text-sm { font-size: 8.5pt !important; }
    .scheda .text-xs { font-size: 7.5pt !important; }
    .scheda .text-xxs { font-size: 6.5pt !important; }

    .scheda .mb-6 { margin-bottom: 0.4rem !important; }
    .scheda .mb-4 { margin-bottom: 0.2rem !important; }
    .scheda .mt-8 { margin-top: 0.5rem !important; }
    .scheda .mt-10 { margin-top: 0.7rem !important; }
    .scheda .gap-4 { gap: 0.4rem !important; }
    .scheda .pb-4 { padding-bottom: 0.2rem !important; }
    .scheda .mb-1 { margin-bottom: 0.1rem !important; }
    .scheda .mb-2 { margin-bottom: 0.2rem !important; }
    .scheda .px-2\.5 { padding-left: 0.3rem !important; padding-right: 0.3rem !important; }
    .scheda .py-0\.5 { padding-top: 0.05rem !important; padding-bottom: 0.05rem !important; }

    /* Rimuovi completamente gli stili del pattern lock grafico */
    .pattern-lock { display: inline !important; } /* Make it inline for text */
    .pattern-lock .dot { display: none !important; } /* Hide the dots */

    /* Rimuovi la linea di divisione */
    .linea-divisione {
      display: none;
    }
    /* Nasconde il pulsante di stampa quando si stampa la pagina */
    .print-button {
      display: none;
    }
  }
</style>
</head>
<!-- Il body della pagina, centrato e con padding -->
<body class="bg-gray-50 flex justify-center items-center py-8">

  <!-- Contenitore principale per le schede di riparazione -->
  <div class="print-container w-full max-w-4xl bg-white rounded-lg shadow-xl">

    <!-- Pulsante di stampa visibile solo a schermo, nascosto in stampa -->
    <div class="flex justify-end p-4 print:hidden">
      <button onclick="window.print()" class="print-button bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg shadow-md transition duration-300 ease-in-out">
        Stampa Scheda
      </button>
    </div>

    <?php
      // Genera la prima copia della scheda di assistenza
      echo scheda_assistenza($riparazione, $id_riparazione, 'COPIA CLIENTE');
    ?>

    <?php
      // Genera la seconda copia della scheda di assistenza su una nuova pagina
      // Applica la classe page-break-before a questo contenitore per forzare la nuova pagina
      echo '<div class="page-break-before">';
      echo scheda_assistenza($riparazione, $id_riparazione, 'COPIA UFFICIO');
      echo '</div>';
    ?>

  </div>

  <script>
    // Funzione eseguita quando la pagina è completamente caricata
    window.onload = function() {
      // Avvia la finestra di dialogo di stampa del browser
      window.print();
    };
  </script>

</body>
</html>
