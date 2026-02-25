<?php
session_start(); // Avvia la sessione PHP

// --- Includi il file di connessione al database all'inizio, sempre disponibile ---
include 'db.php';

// Variabile per il messaggio di accesso negato
$access_denied_html = '';

// --- Controllo degli Accessi ---
// 1. Controlla se l'utente è loggato
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Reindirizza al login se non loggato
    exit();
}

// 2. Controlla se l'utente ha il ruolo di "Amministratore"
$current_user_role = $_SESSION['role'] ?? 'Guest';
if ($current_user_role !== 'Amministratore') {
    // Invece di reindirizzare, imposta un messaggio di errore più carino
    $access_denied_html = '
        <div class="access-denied-message">
            <div class="icon-wrapper"><i class="fas fa-exclamation-triangle"></i></div>
            <h3>Accesso Negato</h3>
            <p>Non hai i permessi necessari per visualizzare questa pagina. Solo gli amministratori possono accedere a questa sezione.</p>
            <p>Se ritieni che ci sia un errore o hai bisogno di assistenza, contatta l\'amministratore del sistema.</p>
            <a href="homepage.php" class="back-to-home-button">Torna alla Home</a>
        </div>
    ';
    $utenti = []; // Assicura che la tabella sia vuota
} else {
    // Abilita la visualizzazione degli errori per il debug (RIMUOVERE IN PRODUZIONE!)
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // --- GESTIONE CHIAMATE AJAX (POST per CUD, GET per Read) ---
    header('Content-Type: application/json'); // Imposta l'header per tutte le risposte da questo blocco in poi

    // --- Logica per recuperare i dati di un singolo utente (per il modal di modifica) ---
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_user' && isset($_GET['id'])) {
        $user_id_to_fetch = intval($_GET['id']);
        $stmt_fetch = $conn->prepare("SELECT id, nome_utente, nome, email, ruolo, attivo FROM utenti WHERE id = ?");
        $stmt_fetch->bind_param("i", $user_id_to_fetch);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();

        if ($result_fetch->num_rows === 1) {
            echo json_encode(['success' => true, 'data' => $result_fetch->fetch_assoc()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
        }
        $stmt_fetch->close();
        exit();
    }
    
    // --- Logica per l'aggiunta o la modifica di un utente ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = $_POST['action'] ?? 'create'; // 'create' o 'edit'
        $user_id = $_POST['user_id'] ?? null; // Solo per l'azione 'edit'

        $nome_utente = trim($_POST['nome_utente'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password_chiaro = $_POST['password'] ?? '';
        $conferma_password = $_POST['conferma_password'] ?? '';
        $ruolo = trim($_POST['ruolo'] ?? '');
        $attivo = isset($_POST['attivo']) ? 1 : 0;
        
        $error_message = ''; // Inizializza il messaggio di errore

        // Validazione dei campi comuni
        if (empty($nome_utente) || empty($nome) || empty($email) || empty($ruolo)) {
            $error_message = "Tutti i campi obbligatori (Nome Utente, Nome Completo, Email, Ruolo) devono essere compilati.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "L'indirizzo email non è valido.";
        } else {
            // Logica specifica per CREATE e EDIT
            if ($action === 'create') {
                if (empty($password_chiaro) || empty($conferma_password)) {
                    $error_message = "La password e la conferma password sono obbligatorie.";
                } elseif ($password_chiaro !== $conferma_password) {
                    $error_message = "Le password non corrispondono.";
                } elseif (strlen($password_chiaro) < 8) {
                    $error_message = "La password deve essere di almeno 8 caratteri.";
                } else {
                    $stmt_check = $conn->prepare("SELECT id FROM utenti WHERE nome_utente = ? OR email = ?");
                    $stmt_check->bind_param("ss", $nome_utente, $email);
                    $stmt_check->execute();
                    if ($stmt_check->get_result()->num_rows > 0) {
                        $error_message = "Nome utente o email già esistenti.";
                    } else {
                        $hashed_password = password_hash($password_chiaro, PASSWORD_DEFAULT);
                        $stmt_insert = $conn->prepare("INSERT INTO utenti (nome_utente, nome, email, password, ruolo, attivo) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt_insert->bind_param("sssssi", $nome_utente, $nome, $email, $hashed_password, $ruolo, $attivo);

                        if (!$stmt_insert->execute()) {
                             $error_message = "Errore durante la creazione dell'utente.";
                        }
                        $stmt_insert->close();
                    }
                    $stmt_check->close();
                }
            } elseif ($action === 'edit') {
                 if (empty($user_id)) {
                    $error_message = "ID utente non fornito per la modifica.";
                } else {
                    $stmt_check = $conn->prepare("SELECT id FROM utenti WHERE (nome_utente = ? OR email = ?) AND id != ?");
                    $stmt_check->bind_param("ssi", $nome_utente, $email, $user_id);
                    $stmt_check->execute();
                    if ($stmt_check->get_result()->num_rows > 0) {
                        $error_message = "Nome utente o email già in uso da un altro utente.";
                    } else {
                        $update_password = false;
                        if (!empty($password_chiaro)) {
                            if ($password_chiaro !== $conferma_password) {
                                $error_message = "Le nuove password non corrispondono.";
                            } elseif (strlen($password_chiaro) < 8) {
                                 $error_message = "La nuova password deve essere di almeno 8 caratteri.";
                            } else {
                                $hashed_password = password_hash($password_chiaro, PASSWORD_DEFAULT);
                                $update_password = true;
                            }
                        }

                        if (empty($error_message)) {
                            $sql_update = "UPDATE utenti SET nome_utente = ?, nome = ?, email = ?, ruolo = ?, attivo = ? " . ($update_password ? ", password = ? " : "") . "WHERE id = ?";
                            $stmt_update = $conn->prepare($sql_update);
                            if ($update_password) {
                                $stmt_update->bind_param("ssssisi", $nome_utente, $nome, $email, $ruolo, $attivo, $hashed_password, $user_id);
                            } else {
                                $stmt_update->bind_param("ssssii", $nome_utente, $nome, $email, $ruolo, $attivo, $user_id);
                            }

                            if (!$stmt_update->execute()) {
                               $error_message = "Errore durante l'aggiornamento dell'utente.";
                            }
                            $stmt_update->close();
                        }
                    }
                    $stmt_check->close();
                }
            }
        }
        
        if (!empty($error_message)) {
            echo json_encode(['success' => false, 'message' => $error_message]);
        } else {
             echo json_encode(['success' => true, 'message' => "Utente " . ($action === 'create' ? 'creato' : 'aggiornato') . " con successo!"]);
        }
        exit(); // Termina lo script dopo aver inviato la risposta JSON
    }

    // Se la richiesta non è AJAX, carica la pagina HTML. Bisogna resettare l'header
    header('Content-Type: text/html; charset=UTF-8');

    // --- Recupera tutti gli utenti per la visualizzazione nella tabella ---
    $utenti = [];
    $result = $conn->query("SELECT id, nome_utente, nome, email, ruolo, attivo, data_creazione, ultimo_accesso FROM utenti ORDER BY nome_utente ASC");
    if ($result) {
        $utenti = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestione Utenti</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --brand-green: #22c55e;
            --brand-green-dark: #16a34a;
            --brand-blue: #3b82f6; /* Blu per azioni secondarie */
            --brand-red: #ef4444;
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
            padding-top: 80px;
        }
        .page-container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-header h1 { font-size: 2.25rem; font-weight: 800; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem;
            border-radius: 0.5rem; font-weight: 600; cursor: pointer; text-decoration: none;
            background-color: var(--brand-green); color: white; box-shadow: var(--card-shadow);
            transition: all 0.2s ease-in-out;
        }
        .btn-primary:hover { background-color: var(--brand-green-dark); transform: translateY(-2px); }
        .card { background-color: var(--card-bg); border-radius: var(--card-radius); box-shadow: var(--card-shadow); padding: 2rem; }
        
        /* Table Styles */
        .user-table { width: 100%; border-collapse: collapse; }
        .user-table thead th {
            padding: 0.8rem 1rem; text-align: left; background-color: #f8fafc;
            color: var(--text-secondary); font-weight: 600; font-size: 0.75rem;
            text-transform: uppercase; letter-spacing: 0.05em;
        }
        .user-table tbody td { padding: 1rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        .user-table tbody tr { transition: background-color 0.2s ease; }
        .user-table tbody tr:hover { background-color: #f8fafc; }
        .status-badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.75rem; border-radius: 9999px; font-weight: 500; font-size: 0.8rem; }
        .status-badge::before { content: ''; width: 8px; height: 8px; border-radius: 50%; }
        .status-active { background-color: #f0fdf4; color: #15803d; } .status-active::before { background-color: var(--brand-green); }
        .status-inactive { background-color: #fef2f2; color: #991b1b; } .status-inactive::before { background-color: var(--brand-red); }
        
        .action-buttons button { background: none; border: none; cursor: pointer; color: var(--text-secondary); padding: 0.5rem; border-radius: 50%; width: 2.25rem; height: 2.25rem; }
        .action-buttons button:hover { background-color: var(--border-color); color: var(--text-primary); }

        /* Modal Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(30, 41, 59, 0.5); display: flex; justify-content: center; align-items: center; z-index: 1000; opacity: 0; visibility: hidden; transition: opacity 0.3s ease; }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .modal-content { background-color: var(--card-bg); padding: 2rem; border-radius: var(--card-radius); box-shadow: var(--card-shadow); max-width: 600px; width: 90%; transform: scale(0.95); transition: transform 0.3s ease; }
        .modal-overlay.active .modal-content { transform: scale(1); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1.5rem; }
        .modal-header h2 { font-size: 1.5rem; font-weight: 600; }
        .close-modal-btn { background: none; border: none; font-size: 1.8rem; color: #9ca3af; cursor: pointer; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 1rem; border-top: 1px solid var(--border-color); padding-top: 1.25rem; margin-top: 1.25rem; }
        
        /* Floating Label Form */
        .form-group { position: relative; margin-bottom: 1.25rem; }
        .form-group label { position: absolute; top: 0.9rem; left: 1rem; color: var(--text-secondary); background: var(--card-bg); padding: 0 0.25rem; pointer-events: none; transition: all 0.2s ease; font-size: 1rem; }
        .form-group input, .form-group select { width: 100%; padding: 0.8rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background-color: var(--card-bg); }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--brand-green); box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2); }
        .form-group input:focus + label, .form-group input:not(:placeholder-shown) + label, .form-group select:valid + label { transform: translateY(-1.75rem) scale(0.85); left: 0.75rem; color: var(--brand-green); }
        
         /* Access Denied Message */
        .access-denied-message { text-align: center; padding: 3rem; background-color: #fffbeb; border: 1px solid #fde68a; border-radius: var(--card-radius); max-width: 700px; margin: 4rem auto; }
        .access-denied-message .icon-wrapper { font-size: 3rem; color: #f59e0b; margin-bottom: 1rem; }
        .access-denied-message h3 { font-size: 1.75rem; font-weight: 700; color: #b45309; }
        .access-denied-message p { color: #92400e; margin-top: 0.5rem; }
        .back-to-home-button { display: inline-block; margin-top: 1.5rem; padding: 0.75rem 1.5rem; background-color: var(--brand-green); color: white; border-radius: 0.5rem; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'header.php'; ?>

        <main class="page-container">
            <?php if (!empty($access_denied_html)): ?>
                <?php echo $access_denied_html; ?>
            <?php else: ?>
                <div class="page-header">
                    <h1>Gestione Utenti</h1>
                    <button class="btn-primary" onclick="openModal()"><i class="fas fa-plus mr-2"></i> Aggiungi Utente</button>
                </div>
                
                <div class="card">
                    <div class="overflow-x-auto">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome Utente</th>
                                    <th>Nome Completo</th>
                                    <th>Email</th>
                                    <th>Ruolo</th>
                                    <th>Stato</th>
                                    <th class="text-center">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($utenti as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['nome_utente']); ?></td>
                                        <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['ruolo']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $user['attivo'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $user['attivo'] ? 'Attivo' : 'Disattivo'; ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons text-center">
                                            <button onclick="openModal(<?php echo $user['id']; ?>)" title="Modifica Utente"><i class="fas fa-pencil-alt"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modale Aggiungi/Modifica Utente -->
    <div class="modal-overlay" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuovo Utente</h2>
                <button class="close-modal-btn" onclick="closeModal()" title="Chiudi">&times;</button>
            </div>
            <form id="userForm">
                <input type="hidden" name="action" id="form_action" value="create">
                <input type="hidden" name="user_id" id="edit_user_id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                    <div class="form-group">
                        <input type="text" id="nome_utente" name="nome_utente" placeholder=" " required>
                        <label for="nome_utente">Nome Utente</label>
                    </div>
                    <div class="form-group">
                        <input type="text" id="nome" name="nome" placeholder=" " required>
                        <label for="nome">Nome Completo</label>
                    </div>
                </div>
                <div class="form-group">
                    <input type="email" id="email" name="email" placeholder=" " required>
                    <label for="email">Email</label>
                </div>
                <div class="form-group">
                    <select id="ruolo" name="ruolo" required>
                        <option value="" disabled selected></option>
                        <option value="Amministratore">Amministratore</option>
                        <option value="Cassiere">Cassiere</option>
                        <option value="Magazziniere">Magazziniere</option>
                        <option value="Contabile">Contabile</option>
                        <option value="Dipendente">Dipendente</option>
                    </select>
                    <label for="ruolo">Ruolo</label>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                    <div class="form-group">
                        <input type="password" id="password" name="password" placeholder=" " autocomplete="new-password">
                        <label for="password">Password</label>
                    </div>
                    <div class="form-group">
                        <input type="password" id="conferma_password" name="conferma_password" placeholder=" " autocomplete="new-password">
                        <label for="conferma_password">Conferma Password</label>
                    </div>
                </div>
                 <div class="flex items-center gap-3 mb-5">
                    <input type="checkbox" id="attivo" name="attivo" class="h-5 w-5 rounded accent-green-600">
                    <label for="attivo" class="!relative !p-0 !bg-transparent !transform-none !text-base">Account Attivo</label>
                </div>

                <div class="modal-actions">
                    <button type="button" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600" onclick="closeModal()">Annulla</button>
                    <button type="submit" class="btn-primary px-4 py-2">Salva</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('userModal');
        const form = document.getElementById('userForm');
        const modalTitle = document.getElementById('modalTitle');
        
        function showNotification(message, icon = 'success') {
            Swal.fire({ toast: true, position: 'top-end', icon, title: message, showConfirmButton: false, timer: 3500, timerProgressBar: true });
        }

        async function openModal(userId = null) {
            form.reset();
            document.querySelectorAll('.form-group label').forEach(label => label.classList.remove('transform'));
            
            if (userId) {
                // Edit mode
                modalTitle.textContent = 'Modifica Utente';
                document.getElementById('form_action').value = 'edit';
                document.getElementById('edit_user_id').value = userId;
                document.getElementById('password').placeholder = "Nuova password (opzionale)";
                document.getElementById('conferma_password').placeholder = "Conferma nuova password";
                
                try {
                    const response = await fetch(`gestione_utenti.php?action=get_user&id=${userId}`);
                    const result = await response.json();
                    if(result.success) {
                        const user = result.data;
                        document.getElementById('nome_utente').value = user.nome_utente;
                        document.getElementById('nome').value = user.nome;
                        document.getElementById('email').value = user.email;
                        document.getElementById('ruolo').value = user.ruolo;
                        document.getElementById('attivo').checked = (user.attivo == 1);
                    } else {
                        showNotification(result.message, 'error');
                        return;
                    }
                } catch (error) {
                    showNotification('Errore nel caricamento dei dati utente.', 'error');
                    return;
                }

            } else {
                // Create mode
                modalTitle.textContent = 'Nuovo Utente';
                document.getElementById('form_action').value = 'create';
                document.getElementById('edit_user_id').value = '';
                document.getElementById('password').placeholder = " ";
                document.getElementById('conferma_password').placeholder = " ";
                document.getElementById('attivo').checked = true;
            }
            modal.classList.add('active');
        }

        function closeModal() {
            modal.classList.remove('active');
        }

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            
            try {
                const response = await fetch('gestione_utenti.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    closeModal();
                    // Ricarica la pagina per mostrare i cambiamenti. Un approccio più avanzato
                    // potrebbe aggiornare la tabella dinamicamente.
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('Si è verificato un errore di comunicazione.', 'error');
            }
        });

        // Chiudi il modal se si clicca sull'overlay
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>
</body>
</html>

