<?php
// stampa_permuta.php
// Questo script genera la scheda di permuta formattata per la stampa,
// allineandosi allo stile del gestionale per le schede di riparazione.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurazione della connessione al database (PDO per coerenza con l'artefatto fornito)
$host = 'localhost';
$dbname = 'gestionale_tsservice';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Imposta fetch_assoc di default
} catch (PDOException $e) {
    // In caso di errore di connessione, mostra un messaggio e termina
    die("Errore connessione DB: " . $e->getMessage());
}

// Recupera l'ID della permuta dall'URL, con validazione
$id_permuta = isset($_GET['id_permuta']) ? intval($_GET['id_permuta']) : 0;

if ($id_permuta <= 0) {
    die('ID permuta mancante o non valido.');
}

// Query per recuperare tutti i dati della permuta
$stmt = $pdo->prepare("SELECT * FROM permute_nuovo WHERE id = ?");
$stmt->execute([$id_permuta]);
$permuta = $stmt->fetch();

if (!$permuta) {
    die('Permuta non trovata.');
}

// --- Funzioni Helper per formattazione e visualizzazione ---

/**
 * Formatta una data da 'YYYY-MM-DD HH:MM:SS' o 'YYYY-MM-DD' a 'DD/MM/YYYY'.
 *
 * @param string|null $date La data da formattare.
 * @return string La data formattata o '-'.
 */
function formatDate($date) {
    if (!$date || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return '-';
    }
    // Verifica se è un formato datetime (contiene spazi) o solo data
    if (strpos($date, ' ') !== false) {
        // È un datetime, prendi solo la parte della data
        $date = explode(' ', $date)[0];
    }
    return date('d/m/Y', strtotime($date));
}

/**
 * Formatta un valore numerico come prezzo in Euro.
 *
 * @param mixed $price Il valore del prezzo.
 * @return string Il prezzo formattato o '-'.
 */
function formatPrice($price) {
    if ($price === null || $price === '' || !is_numeric($price)) {
        return '-';
    }
    return number_format(floatval($price), 2, ',', '.') . " €";
}

/**
 * Rimuove i caratteri HTML speciali per prevenire XSS.
 *
 * @param string|null $text Il testo da sanificare.
 * @return string Il testo sanificato.
 */
function escapeHtml($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

/**
 * Restituisce le classi CSS per il badge dello stato della permuta.
 *
 * @param string|null $status Lo stato della permuta.
 * @return string Le classi CSS.
 */
function getPermutaStatusClasses($status) {
    if (empty($status)) return 'in-trattativa'; // Default
    return strtolower(str_replace(' ', '-', $status));
}

/**
 * Genera la struttura HTML di una singola scheda di permuta per la stampa.
 *
 * @param array $permuta L'array associativo con i dati della permuta.
 * @param string $logoPath Percorso del file logo.
 * @return string L'HTML della scheda.
 */
function renderPermutaScheda($permuta, $logoPath = 'images/logo.png') { // Percorso aggiornato a images/logo.png
    ob_start();
    
    // Stores parsed test data: ['Test Name' => ['result_bool' => bool, 'display_esito' => 'string', 'item_note' => 'string']]
    $parsedTests = [];
    $noteGenerali = escapeHtml($permuta['note_generali'] ?? ''); // Initialize general notes

    // --- Centralized Test Parsing Logic ---
    
    // 1. Attempt to parse 'tabella_test_json' (preferred structured data)
    $json_test_data = json_decode($permuta['tabella_test_json'] ?? '', true);
    
    // Check if JSON decoding was successful and it has the expected 'testOK' structure
    if (json_last_error() === JSON_ERROR_NONE && is_array($json_test_data) && isset($json_test_data['testOK']) && is_array($json_test_data['testOK'])) {
        foreach ($json_test_data['testOK'] as $testName => $testResultDetails) {
            $result_bool = false; // Default test result
            $display_esito = 'KO';
            $item_note = '';

            // Case 1: Test result is an array (e.g., {"esito":"Funzionante", "note":"Perfetto"})
            if (is_array($testResultDetails)) {
                $esito_value = (string)($testResultDetails['esito'] ?? '');
                $item_note = (string)($testResultDetails['note'] ?? '');
                $result_bool = (stripos($esito_value, 'Funzionante') !== false || stripos($esito_value, 'OK') !== false || stripos($esito_value, 'Ottima') !== false || stripos($esito_value, 'Liberi') !== false);
            }
            // Case 2: Test result is a simple boolean
            else if (is_bool($testResultDetails)) {
                $result_bool = $testResultDetails;
            }
            // Case 3: Test result is a string (e.g., "OK", "KO", or even "{\"esito\":\"Funzionante\"}")
            else if (is_string($testResultDetails)) {
                // Try to decode if it's a JSON string representing a result
                $inner_json = json_decode($testResultDetails, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($inner_json) && isset($inner_json['esito'])) {
                    $esito_value = (string)($inner_json['esito']);
                    $item_note = (string)($inner_json['note'] ?? '');
                    $result_bool = (stripos($esito_value, 'Funzionante') !== false || stripos($esito_value, 'OK') !== false || stripos($esito_value, 'Ottima') !== false || stripos($esito_value, 'Liberi') !== false);
                } else {
                    // Otherwise, infer from text (e.g., "OK', 'KO', '80%')
                    $result_bool = !(stripos($testResultDetails, 'KO') !== false || stripos($testResultDetails, 'NON FUNZIONANTE') !== false || stripos($testResultDetails, 'Difettosa') !== false);
                    $item_note = $testResultDetails; // Use the raw string as a note if no specific note found
                }
            }

            $display_esito = $result_bool ? 'OK' : 'KO';
            
            $parsedTests[escapeHtml($testName)] = [
                'result_bool' => $result_bool,
                'display_esito' => $display_esito,
                'item_note' => escapeHtml($item_note)
            ];
        }
        // If structured data also has 'noteGenerali', it overrides the raw one for this section
        if (isset($json_test_data['noteGenerali'])) {
            $noteGenerali = escapeHtml($json_test_data['noteGenerali']);
        }
    } else {
        // Fallback: If 'tabella_test_json' is empty or invalid, try parsing 'test_ok' (more robust for malformed strings)
        if (!empty($permuta['test_ok'])) {
            $raw_test_string = $permuta['test_ok'];
            
            // Regex to find patterns like "key":{...} or "key":"value" or "key":true/false,
            // trying to capture test name, its main value/object, and an optional note that follows "note": "..."
            // This is complex due to the highly malformed input previously provided by the user.
            // It tries to find a key-value pair, and then an optional "note" after that value block.
            preg_match_all('/"([^"]+)"\s*:\s*({.+?\}|"[^"]*"|true|false)\s*(?:,\s*"note"\s*:\s*"(.*?)")?/i', $raw_test_string, $matches, PREG_SET_ORDER);

            if (!empty($matches)) {
                foreach ($matches as $match) {
                    $testName = trim($match[1]);
                    $testValueRaw = trim($match[2]);
                    $extractedNote = isset($match[3]) ? trim($match[3]) : '';
                    
                    $result_bool = false; // Default result
                    $display_esito = 'KO';
                    $item_note = escapeHtml($extractedNote); // Start with extracted note

                    if (strpos($testValueRaw, '{') === 0) { // It's a JSON object like {"esito":"Funzionante"}
                        $inner_json_value = json_decode($testValueRaw, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($inner_json_value)) {
                            if (isset($inner_json_value['esito'])) {
                                $esito_value = (string)($inner_json_value['esito']);
                                $result_bool = (stripos($esito_value, 'Funzionante') !== false || stripos($esito_value, 'OK') !== false || stripos($esito_value, 'Ottima') !== false || stripos($esito_value, 'Liberi') !== false);
                                // If no explicit extracted note, use the esito value as the primary info for notes column
                                if (empty($item_note)) {
                                    $item_note = escapeHtml($esito_value);
                                }
                            }
                            // Also try to get a 'note' directly from the inner_json_value if present
                            if (isset($inner_json_value['note']) && empty($extractedNote)) {
                                $item_note = escapeHtml((string)$inner_json_value['note']);
                            }
                        }
                    } else if (strtolower($testValueRaw) === 'true') { // Boolean true as string
                        $result_bool = true;
                        if (empty($item_note)) $item_note = 'Valore Vero';
                    } else if (strtolower($testValueRaw) === 'false') { // Boolean false as string
                        $result_bool = false;
                        if (empty($item_note)) $item_note = 'Valore Falso';
                    } else { // Direct string value, e.g., "Funzionante", "Difettosa", "80%"
                        $result_bool = !(stripos($testValueRaw, 'KO') !== false || stripos($testValueRaw, 'Difettosa') !== false || stripos($testValueRaw, 'NON FUNZIONANTE') !== false);
                        if (empty($item_note)) {
                            $item_note = escapeHtml(trim($testValueRaw, '"')); // Remove quotes if present
                        }
                    }
                    
                    $display_esito = $result_bool ? 'OK' : 'KO';

                    // Cleanup the test name: remove specific prefixes, JSON characters, and normalize spaces
                    $cleanTestName = preg_replace('/^(display|touch|batteria|cam_post|cam_ant|audio|mic|wifi|bt|ricarica|tasti|sensori|sblocco_bio|reset_fabbrica|accounts|altro)\s*$/i', '$1', $testName);
                    $cleanTestName = preg_replace('/[{}"\':,]+/', ' ', $cleanTestName); 
                    $cleanTestName = trim(preg_replace('/\s+/', ' ', $cleanTestName)); // Normalize multiple spaces to single space

                    if (!empty($cleanTestName)) {
                        $parsedTests[escapeHtml($cleanTestName)] = [
                            'result_bool' => $result_bool,
                            'display_esito' => $display_esito,
                            'item_note' => $item_note
                        ];
                    }
                }
            } else {
                // If regex for structured JSON-like parts fails, fall back to line-by-line parsing
                // This handles cases where test_ok is truly just plain text with line breaks/commas.
                $individual_test_strings = [];
                $temp_splits = explode("\n", $raw_test_string);
                if (count($temp_splits) <= 1 && strpos($raw_test_string, ';') !== false) {
                    $temp_splits = explode(';', $raw_test_string);
                }
                if (count($temp_splits) <= 1 && strpos($raw_test_string, ',') !== false && !preg_match('/(?:OK|KO|NON FUNZIONANTE)\s*,/i', $raw_test_string)) {
                    $temp_splits = explode(',', $raw_test_string);
                }

                foreach ($temp_splits as $item_string) {
                    $item_string = trim($item_string);
                    if (empty($item_string)) continue;

                    $testName = $item_string;
                    $result_bool = true; // Default to OK
                    $display_esito = 'OK';
                    $item_note = '';

                    // Try to extract status and any trailing note from the item string
                    if (preg_match('/^(.*?)(?:[.\-—:;]?\s*)?(OK|KO|NON FUNZIONANTE|Funzionante|Difettosa|Ottima|Liberi)\s*(?:[.\-—:;]?\s*(.*))?$/i', $item_string, $matches)) {
                        $testName = trim($matches[1]);
                        $status_text = strtoupper(trim($matches[2]));
                        $result_bool = (stripos($status_text, 'OK') !== false || stripos($status_text, 'Funzionante') !== false || stripos($status_text, 'Ottima') !== false || stripos($status_text, 'Liberi') !== false);
                        $display_esito = $result_bool ? 'OK' : 'KO';
                        if (isset($matches[3]) && !empty(trim($matches[3]))) {
                            $item_note = trim($matches[3]);
                        } else {
                            $item_note = $status_text; // If no specific note, use status text as note
                        }
                    } else if (stripos($item_string, 'KO') !== false || stripos($item_string, 'Difettosa') !== false || stripos($item_string, 'NON FUNZIONANTE') !== false) {
                        $result_bool = false;
                        $display_esito = 'KO';
                        // Use original string as note if no specific status was parsed out at the end
                        $item_note = $item_string;
                    } else {
                        // If no clear status, assume OK and use the string as the test name/note
                        $display_esito = 'OK';
                        $item_note = $item_string;
                    }
                    
                    // Cleanup for plain text string names
                    $testName = preg_replace('/[{}"\':,]+/', ' ', $testName); // Remove various punctuation
                    $testName = trim(preg_replace('/\s+/', ' ', $testName)); // Normalize spaces

                    if (empty($testName) && !empty($item_string)) {
                        $testName = $item_string; // Fallback to original if cleaned to empty
                    }
                    $parsedTests[escapeHtml($testName)] = [
                        'result_bool' => $result_bool,
                        'display_esito' => $display_esito,
                        'item_note' => escapeHtml($item_note)
                    ];
                }
            }
        }
    }

    // Calcoli per costo prodotto e differenza (se non salvati direttamente nel DB)
    $costo_prodotto_calc = floatval($permuta['prezzo_permuta'] ?? 0)
                          + floatval($permuta['costo_riparazione'] ?? 0)
                          + floatval($permuta['costo_accessori'] ?? 0);
    
    $differenza_calc = floatval($permuta['prezzo_nuovo'] ?? 0)
                      - floatval($permuta['prezzo_permuta'] ?? 0);

    ?>
    <div class="scheda border border-gray-200 rounded-lg p-6">
      <div class="flex justify-between items-start pb-4 border-b-2 border-[var(--brand-green)] mb-6">
        <div class="flex items-center gap-4">
          <!-- Area per il logo aziendale. Utilizza il logo.png fornito -->
          <!-- Aggiunto un fallback onerror per mostrare un segnaposto se l'immagine non viene caricata -->
          <img src="<?= escapeHtml($logoPath) ?>" alt="Logo Azienda" class="company-logo" onerror="this.onerror=null; this.src='https://placehold.co/96x40/4CAF50/FFFFFF?text=LOGO';">
          <div>
            <h2 class="text-xl font-bold text-[var(--brand-green-dark)]">TS SERVICE</h2>
            <p class="text-xs text-gray-600">Contrada Castromurro - 217</p>
            <p class="text-xs text-gray-600">87021 BELVEDERE M.MO (CS)</p>
            <p class="text-xs text-gray-600">Tel. 3420330279</p>
            <p class="text-xs text-gray-600">Email: info@tsservice.it</p>
          </div>
        </div>
        <div class="text-right">
          <h1 class="text-2xl font-extrabold text-gray-800 mb-1">SCHEDA DI PERMUTA</h1>
          <p class="text-lg font-bold text-gray-700">Permuta #<span class="text-[var(--brand-green)]"><?= escapeHtml(empty($permuta['progressivo']) ? ($permuta['id'] ?? 'N/D') : $permuta['progressivo']) ?></span></p>
          <p class="text-sm text-gray-600">Data emissione: <?= formatDate($permuta['data'] ?? $permuta['created_at']) ?></p>
          <p class="text-sm text-gray-600">Stato Permuta: <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium status-badge status-<?= getPermutaStatusClasses($permuta['status']) ?>"><?= escapeHtml($permuta['status'] ?? 'N/D') ?></span></p>
        </div>
      </div>

      <!-- Sezione Dati Cliente -->
      <div class="mb-6">
        <h3 class="text-sm font-semibold bg-[var(--brand-green-light)] border-l-4 border-[var(--brand-green)] px-4 py-2 mb-4 text-[var(--brand-green-text)]">DATI CLIENTE</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="col-span-2 p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Nome Cliente:</strong>
            <span class="text-sm font-medium"><?= escapeHtml($permuta['cliente'] ?? 'N/D') ?></span>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Telefono:</strong>
            <span class="text-sm font-medium"><?= escapeHtml($permuta['telefono'] ?? 'N/D') ?></span>
          </div>
        </div>
      </div>

      <!-- Sezione Dettagli Dispositivo Nuovo -->
      <div class="mb-6">
        <h3 class="text-sm font-semibold bg-[var(--brand-green-light)] border-l-4 border-[var(--brand-green)] px-4 py-2 mb-4 text-[var(--brand-green-text)]">DETTAGLI DISPOSITIVO NUOVO</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Modello:</strong>
            <span class="text-sm font-medium"><?= escapeHtml($permuta['modello_nuovo'] ?? 'N/D') ?></span>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">IMEI:</strong>
            <span class="text-sm font-medium"><?= escapeHtml($permuta['imei_nuovo'] ?? 'N/D') ?></span>
          </div>
          <div class="col-span-full p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Note:</strong>
            <span class="text-sm font-medium whitespace-pre-wrap"><?= escapeHtml($permuta['note_nuovo'] ?? 'N/D') ?></span>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Prezzo:</strong>
            <span class="text-sm font-medium"><?= formatPrice($permuta['prezzo_nuovo']) ?></span>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Costo Prodotto:</strong>
            <span class="text-sm font-medium"><?= formatPrice($permuta['costo_prodotto'] ?? $costo_prodotto_calc) ?></span>
          </div>
        </div>
      </div>

      <!-- Sezione Dettagli Dispositivo Usato (Permuta) -->
      <div class="mb-6">
        <h3 class="text-sm font-semibold bg-[var(--brand-green-light)] border-l-4 border-[var(--brand-green)] px-4 py-2 mb-4 text-[var(--brand-green-text)]">DETTAGLI DISPOSITIVO USATO (PERMUTA)</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Modello:</strong>
            <span class="text-sm font-medium"><?= escapeHtml($permuta['modello_usato'] ?? 'N/D') ?></span>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">IMEI:</strong>
            <span class="text-sm font-medium"><?= escapeHtml($permuta['imei_usato'] ?? 'N/D') ?></span>
          </div>
          <div class="col-span-full p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Note:</strong>
            <span class="text-sm font-medium whitespace-pre-wrap"><?= escapeHtml($permuta['note_usato'] ?? 'N/D') ?></span>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Prezzo Permuta:</strong>
            <span class="text-sm font-medium"><?= formatPrice($permuta['prezzo_permuta']) ?></span>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Costo Riparazione Usato:</strong>
            <span class="text-sm font-medium"><?= formatPrice($permuta['costo_riparazione']) ?></span>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Costo Accessori:</strong>
            <span class="text-sm font-medium"><?= formatPrice($permuta['costo_accessori']) ?></span>
          </div>
        </div>
      </div>

      <!-- Sezione Riepilogo Finanziario -->
      <div class="mb-6">
        <h3 class="text-sm font-semibold bg-[var(--brand-green-light)] border-l-4 border-[var(--brand-green)] px-4 py-2 mb-4 text-[var(--brand-green-text)]">RIEPILOGO FINANZIARIO</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Differenza da Pagare:</strong>
            <span class="text-sm font-medium"><?= formatPrice($permuta['differenza'] ?? $differenza_calc) ?></span>
          </div>
          <div class="p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Prezzo di Vendita Finale:</strong>
            <span class="text-sm font-medium"><?= formatPrice($permuta['prezzo_vendita']) ?></span>
          </div>
          <div class="col-span-full p-3 border border-gray-200 rounded-md bg-gray-50">
            <strong class="block text-xs text-gray-600 mb-1">Data Vendita (se applicabile):</strong>
            <span class="text-sm font-medium"><?= formatDate($permuta['data_vendita'] ?? '') ?></span>
          </div>
        </div>
      </div>

      <!-- Sezione Tabella Test e Note Generali -->
      <div class="mb-6">
        <h3 class="text-sm font-semibold bg-[var(--brand-green-light)] border-l-4 border-[var(--brand-green)] px-4 py-2 mb-4 text-[var(--brand-green-text)]">TEST EFFETTUATI</h3>
        <?php if (!empty($parsedTests)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-md">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Test</th>
                        <th scope="col" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Esito</th>
                        <th scope="col" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-sm">
                    <?php foreach ($parsedTests as $testName => $testDetails): ?>
                    <tr>
                        <td class="px-6 py-2 whitespace-nowrap"><?= $testName ?></td>
                        <td class="px-6 py-2 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $testDetails['result_bool'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $testDetails['display_esito'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-2 whitespace-pre-wrap"><?= !empty($testDetails['item_note']) ? $testDetails['item_note'] : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($noteGenerali !== ''): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-2 whitespace-pre-wrap"><strong>Note Generali:</strong> <?= $noteGenerali ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-sm text-gray-500 italic px-3">Nessun test dettagliato registrato.</p>
        <?php endif; ?>
      </div>

      <!-- Sezione Consenso e Disclaimer -->
      <div class="mt-8 pt-4 border-t border-dashed border-gray-300 text-xs text-gray-700 leading-relaxed">
        <h3 class="text-sm font-semibold bg-[var(--brand-green-light)] border-l-4 border-[var(--brand-green)] px-4 py-2 mb-4 text-[var(--brand-green-text)]">CONDIZIONI E CONSENSO</h3>
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
    return ob_get_clean(); // Restituisce l'HTML generato
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stampa Scheda Permuta #<?= escapeHtml(empty($permuta['progressivo']) ? ($permuta['id'] ?? 'N/D') : $permuta['progressivo']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
  /* Stili di base per il body, impostando il font predefinito */
  body {
    font-family: 'Inter', sans-serif;
    color: #1A202C; /* Colore del testo scuro */
    line-height: 1.5; /* Altezza della linea per una migliore leggibilità */
  }

  /* Definizione delle variabili di colore verde */
  :root {
      --brand-green: #28a745;        /* Base Green */
      --brand-green-dark: #1e8449;   /* Darker shade for gradients */
      --brand-green-light: #e0f2e8;  /* Very light green for backgrounds/hovers */
      --brand-green-text: #065f46;   /* Darker green for text on light backgrounds */
      --status-in-trattativa: #2563eb; /* Blue */
      --status-accettata: #f59e0b;     /* Orange */
      --status-rifiutata: #dc2626;     /* Red */
      --status-completata: #10b981;    /* Green */
      --status-annullata: #6b7280;     /* Grey */
  }

  /* Stili specifici per la stampa */
  @media print {
    body {
      margin: 0;
      padding: 0;
      background: none; /* Rimuove lo sfondo in stampa */
      /* Assicurati che i colori di sfondo degli elementi siano stampati */
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    /* Contenitore principale che simula la pagina A4 */
    .print-container {
      width: 210mm; /* Larghezza A4 */
      height: auto; /* Altezza automatica, non fissa */
      margin: 0;
      box-shadow: none; /* Rimuove l'ombra in stampa */
      border-radius: 0; /* Rimuove i bordi arrotondati in stampa */
      padding: 10mm; /* Padding standard per la stampa */
      display: block; /* Assicurati che sia un blocco per i page-break */
      overflow: hidden; /* Nasconde overflow se la scheda è leggermente più grande dell'A4 */
    }
    .scheda {
      box-shadow: none;
      border: none;
      padding: 0;
      margin-bottom: 0; /* Rimosso spazio aggiuntivo in stampa */
      break-inside: avoid; /* Evita che una scheda venga divisa su due pagine */
    }
    /* Rimosso .linea-divisione e la sua logica di page-break */
    /* Nasconde il pulsante di stampa quando si stampa la pagina */
    .print-button {
      display: none;
    }
    .company-logo {
        max-width: 100px;
        height: auto;
    }
    /* Stili per la tabella dei test in stampa */
    table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 10px;
        font-size: 0.8em; /* Più piccolo per la stampa */
    }
    th, td {
        border: 1px solid #ddd;
        padding: 5px 8px;
        text-align: left;
    }
    thead {
        background-color: #f2f2f2;
    }
    /* Status Badge in Print */
    .status-badge.status-in-trattativa { background-color: var(--status-in-trattativa); color: white; }
    .status-badge.status-accettata { background-color: var(--status-accettata); color: white; }
    .status-badge.status-rifiutata { background-color: var(--status-rifiutata); color: white; }
    .status-badge.status-completata { background-color: var(--status-completata); color: white; }
    .status-badge.status-annullata { background-color: var(--status-annullata); color: white; }
    .status-badge {
        font-size: 0.6em; /* Ancora più piccolo per il badge in stampa */
        padding: 2px 5px;
    }
  }

  /* Stili aggiuntivi per la visualizzazione a schermo e stampa */
  .company-logo {
      max-width: 96px; /* Larghezza del logo per la visualizzazione */
      height: auto;
      object-fit: contain;
  }
  /* Status Badge for Screen */
  .status-badge.status-in-trattativa { background-color: var(--status-in-trattativa); color: white; }
  .status-badge.status-accettata { background-color: var(--status-accettata); color: white; }
  .status-badge.status-rifiutata { background-color: var(--status-rifiutata); color: white; }
  .status-badge.status-completata { background-color: var(--status-completata); color: white; }
  .status-badge.status-annullata { background-color: var(--status-annullata); color: white; }
  .whitespace-pre-wrap { white-space: pre-wrap; } /* Per mantenere gli a capo nei textarea */
</style>
</head>
<body class="bg-gray-50 flex justify-center items-center py-8">

  <!-- Contenitore principale per le schede di permuta -->
  <div class="print-container w-full max-w-4xl bg-white rounded-lg shadow-xl p-8 flex flex-col gap-8">

    <!-- Pulsante di stampa visibile solo a schermo, nascosto in stampa -->
    <div class="flex justify-end mb-4 print:hidden">
      <button onclick="window.print()" class="print-button bg-[var(--brand-green)] hover:bg-[var(--brand-green-dark)] text-white font-semibold py-2 px-6 rounded-lg shadow-md transition duration-300 ease-in-out">
        Stampa Scheda
      </button>
    </div>

    <!-- Singola copia della scheda di permuta -->
    <?= renderPermutaScheda($permuta, 'images/logo.png') ?>

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
