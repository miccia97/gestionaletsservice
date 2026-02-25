<?php
// api.php: Script PHP per la gestione dell'inventario

// Abilita la visualizzazione degli errori e il reporting per il debug (RIMUOVERE IN PRODUZIONE!)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Abilita i CORS per permettere richieste da domini diversi (utile in fase di sviluppo)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Gestisce le richieste OPTIONS (preflight requests)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configurazione del database MySQL
$dbHost = 'localhost'; // Indirizzo del server del database
$dbName = 'gestionale_tsservice'; // Nome del tuo database
$dbUser = 'root'; // Utente del database
$dbPass = ''; // Password del database

$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
$pdo = null;

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    // Registra l'errore per il debug e lo restituisce al frontend
    error_log("Errore di connessione al database: " . $e->getMessage()); // Questo scriverà nel log degli errori di PHP
    echo json_encode(['success' => false, 'message' => 'Errore di connessione al database. Controlla le credenziali o lo stato del server DB. Dettaglio: ' . $e->getMessage()]);
    exit();
}

// --- Funzioni per la gestione delle immagini ---
$uploadDir = 'uploads/'; // Assicurati che questa cartella esista e abbia permessi di scrittura (es. 0777)
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/**
 * Gestisce l'upload di un file immagine.
 * Elimina l'immagine vecchia se fornita e se ne carica una nuova.
 * @param array $file Il $_FILES array per l'immagine.
 * @param string|null $currentImageName Il nome del file dell'immagine corrente nel DB.
 * @return string|false Il nome del nuovo file caricato o false in caso di errore/nessun upload.
 */
function handleImageUpload($file, $currentImageName = null) {
    global $uploadDir; // Accede alla variabile globale $uploadDir

    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $fileName = uniqid() . '_' . basename($file['name']);
        $targetFilePath = $uploadDir . $fileName;

        $moveResult = move_uploaded_file($file['tmp_name'], $targetFilePath);
        if ($moveResult) {
            // Se c'era un'immagine precedente e non è la stessa, eliminala
            if ($currentImageName && file_exists($uploadDir . $currentImageName) && $currentImageName !== $fileName) {
                unlink($uploadDir . $currentImageName);
                error_log("Old image removed: " . $uploadDir . $currentImageName);
            }
            error_log("New image uploaded successfully to: " . $targetFilePath);
            return $fileName; // Ritorna il nome del file salvato
        } else {
            $lastError = error_get_last();
            error_log("Failed to move uploaded file from " . $file['tmp_name'] . " to " . $targetFilePath . ". Error: " . ($lastError ? $lastError['message'] : 'Unknown error. Check file permissions.'));
            return false;
        }
    }
    error_log("No file uploaded or upload error: " . ($file['error'] ?? 'No file provided'));
    return false; // Nessun file caricato o errore
}

/**
 * Elimina un file immagine dal server.
 * @param string|null $imageName Il nome del file dell'immagine da eliminare.
 * @return bool True se eliminato con successo, false altrimenti.
 */
function deleteImage($imageName) {
    global $uploadDir; // Accede alla variabile globale $uploadDir

    if ($imageName && file_exists($uploadDir . $imageName)) {
        if (unlink($uploadDir . $imageName)) {
            error_log("Image deleted: " . $uploadDir . $imageName);
            return true;
        } else {
            error_log("Failed to delete image: " . $uploadDir . $imageName . " (Error: " . error_get_last()['message'] . ")");
            return false;
        }
    }
    return false;
}
// --- Fine funzioni gestione immagini ---


$method = $_SERVER['REQUEST_METHOD'];

// Per le richieste PUT simulate tramite POST, utilizziamo $_POST e $_FILES
// Altrimenti, per POST/GET normali, possiamo usare $_GET o json_decode
$requestData = [];
if ($method === 'POST') {
    // Se è una POST e c'è il campo _method='PUT', la gestiamo come PUT
    if (isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
        $method = 'PUT';
        $requestData = $_POST; // Dati dal FormData sono in $_POST
    } else {
        // Per POST normale (es. aggiunta), controlla sia $_POST che php://input
        // Preferiamo $_POST se il Content-Type è form-data o url-encoded
        if (!empty($_POST)) {
            $requestData = $_POST;
        } else {
            // Fallback per JSON body
            $requestData = json_decode(file_get_contents('php://input'), true);
        }
    }
} elseif ($method === 'GET' || $method === 'DELETE') {
    $requestData = $_GET; // Per GET e DELETE, i parametri sono nella query string
} else {
    // Per altri metodi come PUT (se non simulati via POST), i dati potrebbero arrivare via php://input
    $requestData = json_decode(file_get_contents('php://input'), true);
}


switch ($method) {
    case 'GET':
        // Se è specificato un ID, restituisce un singolo articolo con tutti i suoi dettagli
        if (isset($requestData['id'])) {
            $id = $requestData['id'];
            try {
                // Recupera tutte le colonne dal database con i nomi italiani
                $stmt = $pdo->prepare("SELECT id, nome, descrizione, prezzo, prezzo_vendita1, prezzo_vendita2, prezzo_acquisto, quantita, categoria, tipo_prodotto, stato_prodotto, immagine, barcode, imei, sottocategoria, sottosottocategoria, data_creazione FROM prodotti WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $item = $stmt->fetch();

                if ($item) {
                    // Mappa i nomi delle colonne del DB (italiano) a nomi più "frontend-friendly" (inglese) per JavaScript
                    $item['name'] = $item['nome'];
                    $item['quantity'] = $item['quantita'];
                    $item['price'] = $item['prezzo'];
                    $item['description'] = $item['descrizione'];
                    $item['stato_prodottov'] = $item['stato_prodotto']; // Mappa 'stato_prodotto' a 'stato_prodottov' per JS
                    
                    // Rimuovi le chiavi originali italiane se non vuoi ridondanza nel JSON finale
                    unset($item['nome']);
                    unset($item['quantita']);
                    unset($item['prezzo']);
                    unset($item['descrizione']);
                    unset($item['stato_prodotto']); // Rimuovi anche questo dopo il mapping

                    echo json_encode($item);
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(['success' => false, 'message' => 'Articolo non trovato.']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                error_log("Errore GET singolo articolo: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Errore durante il recupero dell\'articolo. Dettaglio: ' . $e->getMessage()]);
            }
        } elseif (isset($requestData['get']) && $requestData['get'] === 'categories') {
            // Recupera le categorie dal database (esempio)
            try {
                $stmt = $pdo->query("SELECT DISTINCT categoria FROM prodotti WHERE categoria IS NOT NULL AND categoria != ''");
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($categories);
            } catch (PDOException $e) {
                http_response_code(500);
                error_log("Errore GET categorie: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Errore durante il recupero delle categorie.']);
            }
        } elseif (isset($requestData['get']) && $requestData['get'] === 'subcategories') {
            // Recupera le sottocategorie dal database, correlate alla categoria se fornita
            try {
                $sql = "SELECT DISTINCT sottocategoria FROM prodotti WHERE sottocategoria IS NOT NULL AND sottocategoria != ''";
                $params = [];
                if (isset($requestData['category_name']) && $requestData['category_name'] !== '') {
                    $sql .= " AND categoria = :category_name";
                    $params[':category_name'] = $requestData['category_name'];
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($subcategories);
            } catch (PDOException $e) {
                http_response_code(500);
                error_log("Errore GET sottocategorie: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Errore durante il recupero delle sottocategorie.']);
            }
        } elseif (isset($requestData['get']) && $requestData['get'] === 'subsubcategories') { // Nuovo endpoint per il terzo livello
            // Recupera le sottosottocategorie dal database, correlate a categoria e sottocategoria
            try {
                $sql = "SELECT DISTINCT sottosottocategoria FROM prodotti WHERE sottosottocategoria IS NOT NULL AND sottosottocategoria != ''";
                $params = [];
                if (isset($requestData['category_name']) && $requestData['category_name'] !== '') {
                    $sql .= " AND categoria = :category_name";
                    $params[':category_name'] = $requestData['category_name'];
                }
                if (isset($requestData['subcategory_name']) && $requestData['subcategory_name'] !== '') {
                    $sql .= " AND sottocategoria = :subcategory_name";
                    $params[':subcategory_name'] = $requestData['subcategory_name'];
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $subsubcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($subsubcategories);
            } catch (PDOException $e) {
                http_response_code(500);
                error_log("Errore GET sottosottocategorie: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Errore durante il recupero delle sottosottocategorie.']);
            }
        } else {
            // Altrimenti, restituisce tutti gli articoli con i dettagli completi
            try {
                $stmt = $pdo->query("SELECT id, nome, descrizione, prezzo, prezzo_vendita1, prezzo_vendita2, prezzo_acquisto, quantita, categoria, tipo_prodotto, stato_prodotto, immagine, barcode, imei, sottocategoria, sottosottocategoria, data_creazione FROM prodotti ORDER BY id DESC");
                $items = $stmt->fetchAll();
                // Mappa le colonne del DB (italiano) a nomi "frontend-friendly" (inglese) per ogni elemento
                foreach ($items as &$item) {
                    $item['name'] = $item['nome'];
                    $item['quantity'] = $item['quantita'];
                    $item['price'] = $item['prezzo'];
                    $item['description'] = $item['descrizione'];
                    $item['stato_prodottov'] = $item['stato_prodotto']; // Mappa 'stato_prodotto' a 'stato_prodottov' per JS

                    // Rimuovi le chiavi originali italiane se non vuoi ridondanza nel JSON finale
                    unset($item['nome']);
                    unset($item['quantita']);
                    unset($item['prezzo']);
                    unset($item['descrizione']);
                    unset($item['stato_prodotto']); // Rimuovi anche questo dopo il mapping
                }
                echo json_encode($items);
            } catch (PDOException $e) {
                http_response_code(500);
                error_log("Errore GET tutti gli articoli: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Errore durante il caricamento dell\'inventario. Dettaglio: ' . $e->getMessage()]);
            }
        }
        break;

    case 'POST':
        // Aggiungi un nuovo articolo
        
        // Validazione minima dei campi essenziali
        if (empty($requestData['name']) || !isset($requestData['quantity']) || (!isset($requestData['prezzo_vendita1']) && !isset($requestData['prezzo_vendita2']))) {
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Dati minimi (nome, quantità, prezzo di vendita) insufficienti per aggiungere l\'articolo.']);
            exit();
        }

        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && $_FILES['image']['size'] > 0) {
            $uploadedFileName = handleImageUpload($_FILES['image']);
            if ($uploadedFileName) {
                $imagePath = $uploadedFileName;
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Errore durante l\'upload dell\'immagine.']);
                exit();
            }
        }

        try {
            // Inserisce i dati nella tabella, mappando i nomi dal frontend (inglese) ai nomi del DB (italiano)
            $stmt = $pdo->prepare("INSERT INTO prodotti (nome, descrizione, prezzo, prezzo_vendita1, prezzo_vendita2, prezzo_acquisto, quantita, categoria, tipo_prodotto, stato_prodotto, immagine, barcode, imei, sottocategoria, sottosottocategoria) VALUES (:nome, :descrizione, :prezzo, :prezzo_vendita1, :prezzo_vendita2, :prezzo_acquisto, :quantita, :categoria, :tipo_prodotto, :stato_prodotto, :immagine, :barcode, :imei, :sottocategoria, :sottosottocategoria)");
            $stmt->execute([
                'nome' => $requestData['name'] ?? '', 
                'descrizione' => $requestData['description'] ?? '',
                'prezzo' => (float)($requestData['price'] ?? 0.0), 
                'prezzo_vendita1' => (float)($requestData['prezzo_vendita1'] ?? 0.0),
                'prezzo_vendita2' => (float)($requestData['prezzo_vendita2'] ?? 0.0),
                'prezzo_acquisto' => (float)($requestData['prezzo_acquisto'] ?? 0.0),
                'quantita' => (int)($requestData['quantity'] ?? 0),
                'categoria' => $requestData['categoria'] ?? '',
                'tipo_prodotto' => $requestData['tipo_prodotto'] ?? '',
                'stato_prodotto' => $requestData['stato_prodottov'] ?? '', 
                'immagine' => $imagePath, 
                'barcode' => $requestData['barcode'] ?? '',
                'imei' => $requestData['imei'] ?? '',
                'sottocategoria' => $requestData['sottocategoria'] ?? '',
                'sottosottocategoria' => $requestData['sottosottocategoria'] ?? '' // Nuovo campo
            ]);
            http_response_code(201); // Created
            echo json_encode(['success' => true, 'message' => 'Articolo aggiunto con successo.', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log("Errore POST articolo: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiunta dell\'articolo: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Modifica un articolo esistente
        $id = $_GET['id'] ?? null; 
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID articolo non fornito per l\'aggiornamento.']);
            exit();
        }
        
        // Validazione minima dei campi essenziali
        if (empty($requestData['name']) || !isset($requestData['quantity']) || (!isset($requestData['prezzo_vendita1']) && !isset($requestData['prezzo_vendita2']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dati minimi (nome, quantità, prezzo di vendita) insufficienti per aggiornare l\'articolo.']);
            exit();
        }

        // Recupera il nome dell'immagine corrente dal DB
        $currentImage = null;
        $stmt = $pdo->prepare("SELECT immagine FROM prodotti WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existingItem) {
            $currentImage = $existingItem['immagine'];
        }
        $imagePath = $currentImage; // Inizia con l'immagine esistente

        $remove_image = isset($requestData['remove_image']) && $requestData['remove_image'] === 'true';

        // Se l'utente ha richiesto di rimuovere l'immagine
        if ($remove_image) {
            deleteImage($currentImage); // Elimina fisicamente il file
            $imagePath = null; // Imposta il campo 'immagine' a NULL nel DB
            error_log("Remove image requested for ID: " . $id);
        }

        // Gestione del caricamento di una nuova immagine
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && $_FILES['image']['size'] > 0) {
            $uploadedFileName = handleImageUpload($_FILES['image'], $currentImage);
            if ($uploadedFileName) {
                $imagePath = $uploadedFileName;
                error_log("New image uploaded and saved for ID " . $id . ": " . $uploadedFileName);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Errore durante l\'upload della nuova immagine.']);
                exit();
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Gestisce altri errori di upload se un file è stato tentato ma non è andato a buon fine
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Errore nel file caricato: ' . $_FILES['image']['error']]);
            exit();
        }


        try {
            $stmt = $pdo->prepare("UPDATE prodotti SET 
                nome = :nome, 
                descrizione = :descrizione, 
                prezzo = :prezzo, 
                prezzo_vendita1 = :prezzo_vendita1, 
                prezzo_vendita2 = :prezzo_vendita2, 
                prezzo_acquisto = :prezzo_acquisto, 
                quantita = :quantita, 
                categoria = :categoria, 
                tipo_prodotto = :tipo_prodotto, 
                stato_prodotto = :stato_prodotto, 
                immagine = :immagine, 
                barcode = :barcode, 
                imei = :imei, 
                sottocategoria = :sottocategoria, 
                sottosottocategoria = :sottosottocategoria 
            WHERE id = :id");
            
            $stmt->execute([
                'nome' => $requestData['name'] ?? '',
                'descrizione' => $requestData['description'] ?? '',
                'prezzo' => (float)($requestData['price'] ?? 0.0),
                'prezzo_vendita1' => (float)($requestData['prezzo_vendita1'] ?? 0.0),
                'prezzo_vendita2' => (float)($requestData['prezzo_vendita2'] ?? 0.0),
                'prezzo_acquisto' => (float)($requestData['prezzo_acquisto'] ?? 0.0),
                'quantita' => (int)($requestData['quantity'] ?? 0),
                'categoria' => $requestData['categoria'] ?? '',
                'tipo_prodotto' => $requestData['tipo_prodotto'] ?? '',
                'stato_prodotto' => $requestData['stato_prodottov'] ?? '', 
                'immagine' => $imagePath, 
                'barcode' => $requestData['barcode'] ?? '',
                'imei' => $requestData['imei'] ?? '',
                'sottocategoria' => $requestData['sottocategoria'] ?? '',
                'sottosottocategoria' => $requestData['sottosottocategoria'] ?? '', // Nuovo campo
                'id' => $id
            ]);

            if ($stmt->rowCount() > 0) {
                http_response_code(200); // OK
                echo json_encode(['success' => true, 'message' => 'Articolo modificato con successo.']);
            } else {
                http_response_code(200); // OK (Nessun cambiamento, ma non è un errore)
                echo json_encode(['success' => true, 'message' => 'Articolo non trovato per la modifica o nessun cambiamento effettuato.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            error_log("Errore PUT articolo: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Errore nella modifica dell\'articolo. Dettaglio: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Elimina un articolo
        $id = $_GET['id'] ?? null; 
        
        if (isset($id)) {
            $id = intval($id); 
            
            // Prima di eliminare, recupera il nome dell'immagine per eliminarla dal disco
            $stmt = $pdo->prepare("SELECT immagine FROM prodotti WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $itemToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

            try {
                $stmt = $pdo->prepare("DELETE FROM prodotti WHERE id = :id");
                $stmt->execute(['id' => $id]);
                if ($stmt->rowCount() > 0) {
                    // Elimina il file immagine dal server
                    if ($itemToDelete && $itemToDelete['immagine']) {
                        deleteImage($itemToDelete['immagine']);
                    }
                    http_response_code(200); // OK
                    echo json_encode(['success' => true, 'message' => 'Articolo eliminato con successo.']);
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(['success' => false, 'message' => 'Articolo non trovato per l\'eliminazione.']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                error_log("Errore DELETE articolo: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Errore nell\'eliminazione dell\'articolo: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'ID articolo mancante per l\'eliminazione.']);
        }
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Metodo non consentito.']);
        break;
}
