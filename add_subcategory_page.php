<?php
// add_subsubcategory_page.php: Pagina per aggiungere nuove sottosottocategorie
if (session_status() === PHP_SESSION_NONE) { session_start(); }

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php'; // Includi il file di connessione al database

// Recupera tutte le sottocategorie esistenti per popolare il dropdown
$subcategories = [];
try {
    $stmt = $conn->prepare("SELECT id, nome, parent_category_id FROM sottocategorie ORDER BY nome ASC");
    $stmt->execute();
    $subcategories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    // Logga l'errore, ma non bloccare la visualizzazione della pagina
    error_log("Errore nel recupero delle sottocategorie: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Aggiungi Sottosottocategoria</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=1">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
        <style>
        /* Stili generali, coerenti con la pagina di gestione categorie */
        body {
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Allinea gli elementi in alto */
            min-height: 100vh;
            margin: 0;
            background-color: #f4f7f6; /* Sfondo simile alla homepage */
            padding: 2rem;
            box-sizing: border-box;
        }
        .main-container {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            max-width: 600px; /* Larghezza più contenuta per un singolo modulo */
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .form-section {
            padding: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #fcfcfc;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        h1 {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #28a745;
            color: #28a745;
        }
        h2 {
            font-size: 1.75rem;
            margin-bottom: 1rem;
            color: #444;
        }

        /* Stile per input e select */
        .form-section input[type="text"],
        .form-section select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            width: 100%;
            box-sizing: border-box;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .form-section input[type="text"]:focus,
        .form-section select:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
        }

        /* Stile per il pulsante */
        #addBtn {
            background-color: #28a745;
            color: white;
            font-weight: bold;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        #addBtn:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        
        /* Modal styles (coerenti con gli altri modal) */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }
        .modal.active {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 400px;
            width: 90%;
            transform: translateY(-20px);
            transition: transform 0.3s ease-in-out;
            position: relative;
        }
        .modal.active .modal-content {
            transform: translateY(0);
        }
        .close-button {
            position: absolute;
            top: 10px;
            right: 15px;
            color: #888;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .close-button:hover,
        .close-button:focus {
            color: #333;
        }
        #messageText {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #333;
        }
        #messageText.text-red-700 { color: #dc3545; }
        #messageText.text-green-700 { color: #28a745; }
    </style>
</head>
<body>
    <?php include 'header.php'; // Assicurati che header.php includa il tuo menu di navigazione ?>

    <div class="main-container">
        <h1 class="text-4xl font-bold text-center text-gray-800 mb-6 rounded-lg">Aggiungi Sottosottocategoria</h1>

        <!-- Form Section -->
        <div class="form-section">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Dettagli Sottosottocategoria</h2>
            <div class="mb-4">
                <label for="subSubCategoryName" class="block text-gray-700 text-sm font-bold mb-2">Nome Sottosottocategoria:</label>
                <input type="text" id="subSubCategoryName" placeholder="Es. iPhone 15 Pro Max"
                       class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
            </div>
            <div class="mb-6">
                <label for="parentSubCategory" class="block text-gray-700 text-sm font-bold mb-2">Sottocategoria Genitore:</label>
                <select id="parentSubCategory"
                        class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
                    <option value="">Seleziona una sottocategoria...</option>
                    <?php foreach ($subcategories as $subcat): ?>
                        <option value="<?php echo htmlspecialchars($subcat['id']); ?>">
                            <?php echo htmlspecialchars($subcat['nome']); ?>
                            <?php 
                                // Opzionale: Aggiungi il nome della categoria principale per chiarezza
                                // Questo richiede un'altra query o una pre-mappatura in PHP
                                // Per ora, mostriamo solo il nome della sottocategoria
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button id="addBtn"
                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-200 ease-in-out">
                Aggiungi Sottosottocategoria
            </button>
        </div>
    </div>

    <!-- Custom Message Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <p id="messageText" class="text-lg font-semibold mb-4"></p>
        </div>
    </div>

    <script>
        // Funzione per mostrare un messaggio personalizzato
        function showMessage(message, type = 'info') {
            const modal = document.getElementById('messageModal');
            const messageText = document.getElementById('messageText');

            messageText.textContent = message;
            modal.classList.add('active');
            
            messageText.className = 'text-lg font-semibold mb-4'; // Resetta le classi
            if (type === 'error') {
                messageText.classList.add('text-red-700');
            } else if (type === 'success') {
                messageText.classList.add('text-green-700');
            } else {
                messageText.classList.add('text-gray-700');
            }
        }

        // Pulsante di chiusura per il modal
        document.addEventListener('DOMContentLoaded', () => {
            const closeButton = document.querySelector('.close-button');
            if (closeButton) {
                closeButton.onclick = () => {
                    document.getElementById('messageModal').classList.remove('active');
                };
            }
            window.onclick = (event) => {
                const modal = document.getElementById('messageModal');
                if (event.target == modal) {
                    modal.classList.remove('active');
                }
            };

            // Collega il listener di eventi al pulsante di aggiunta
            document.getElementById('addBtn').addEventListener('click', addSubSubCategory);
        });

        async function addSubSubCategory() {
            const subSubCategoryNameInput = document.getElementById('subSubCategoryName');
            const parentSubCategorySelect = document.getElementById('parentSubCategory');

            const name = subSubCategoryNameInput.value.trim();
            const parentId = parentSubCategorySelect.value ? parseInt(parentSubCategorySelect.value) : null;

            if (!name) {
                showMessage("Il nome della sottosottocategoria non può essere vuoto.", 'error');
                return;
            }
            if (parentId === null) {
                showMessage("Devi selezionare una sottocategoria genitore.", 'error');
                return;
            }

            try {
                // Invia i dati a add_category.php, specificando il tipo di genitore come 'sub_category'
                // Questo indicherà al backend di inserire nella tabella 'sottosottocategorie'
                const response = await fetch('add_category.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        nome: name, 
                        parent_id: parentId, 
                        parent_type: 'sub_category' // Indica che il genitore è una sottocategoria
                    })
                });

                if (!response.ok) {
                    // Leggi la risposta di errore completa dal server
                    const errorText = await response.text();
                    console.error("Errore HTTP durante l'aggiunta della sottosottocategoria:", errorText); // Log dettagliato
                    throw new Error(`HTTP error! status: ${response.status}. Response: ${errorText}`);
                }
                const result = await response.json();

                if (result.success) {
                    showMessage(result.message, 'success');
                    // Pulisci il form dopo l'aggiunta
                    subSubCategoryNameInput.value = '';
                    parentSubCategorySelect.value = '';
                } else {
                    showMessage(`Errore: ${result.message}`, 'error');
                }
            } catch (e) {
                console.error("Errore nell'aggiunta della sottosottocategoria:", e);
                showMessage("Errore durante l'aggiunta della sottosottocategoria. Controlla la console e il backend PHP.", 'error');
            }
        }
    </script>
</body>
</html>
