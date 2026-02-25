<?php
// --- ATTIVAZIONE DEBUGGING PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Includi il file di connessione al database
require_once 'db.php';

// Controlla se la connessione al database è fallita
if (isset($db_connection_error) && $db_connection_error !== null) {
    die("Errore critico di connessione al database: " . htmlspecialchars($db_connection_error));
}

// --- Recupero ID cliente dall'URL ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID cliente non valido o non fornito.");
}
$cliente_id = (int)$_GET['id'];

// --- Recupero Dati Anagrafici del Cliente ---
$stmt_cliente = $conn->prepare("SELECT * FROM clienti_nuovo WHERE id = ?");
$stmt_cliente->bind_param("i", $cliente_id);
$stmt_cliente->execute();
$result_cliente = $stmt_cliente->get_result();

if ($result_cliente->num_rows === 0) {
    die("Cliente non trovato.");
}
$cliente = $result_cliente->fetch_assoc();
$stmt_cliente->close();

// Costruisci il nome visualizzato del cliente
$display_name = trim($cliente['nome'] . ' ' . $cliente['cognome']);
if (!empty($cliente['ragione_sociale'])) {
    $display_name = $cliente['ragione_sociale'];
}

// --- Recupero Dati Correlati ---

// 1. Riparazioni
$riparazioni = [];
$stmt_riparazioni = $conn->prepare("SELECT id, modello, stato, data_creazione FROM riparazioni WHERE cliente_id = ? ORDER BY data_creazione DESC");
$stmt_riparazioni->bind_param("i", $cliente_id);
$stmt_riparazioni->execute();
$result_riparazioni = $stmt_riparazioni->get_result();
while ($row = $result_riparazioni->fetch_assoc()) {
    $riparazioni[] = $row;
}
$stmt_riparazioni->close();

// 2. Permute
$permute = [];
$stmt_permute = $conn->prepare("SELECT id, data, modello_nuovo, modello_usato, status FROM permute_nuovo WHERE cliente_id = ? ORDER BY data DESC");
$stmt_permute->bind_param("i", $cliente_id);
$stmt_permute->execute();
$result_permute = $stmt_permute->get_result();
while ($row = $result_permute->fetch_assoc()) {
    $permute[] = $row;
}
$stmt_permute->close();

// 3. Prenotazioni
$prenotazioni = [];
$stmt_prenotazioni = $conn->prepare("SELECT id, product_name, quantity, product_total_price, reservation_date, status FROM prenotazioni_prodotti WHERE client_id = ? ORDER BY reservation_date DESC");
$stmt_prenotazioni->bind_param("i", $cliente_id);
$stmt_prenotazioni->execute();
$result_prenotazioni = $stmt_prenotazioni->get_result();
while ($row = $result_prenotazioni->fetch_assoc()) {
    $prenotazioni[] = $row;
}
$stmt_prenotazioni->close();


// Recupera le informazioni dell'utente dalla sessione per l'header
$current_user_name = $_SESSION['user_name'] ?? 'Ospite';
$current_user_role = $_SESSION['role'] ?? 'N/D';

?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Scheda Cliente - <?php echo htmlspecialchars($display_name); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --brand-color: #28a745;
      --brand-dark: #218838;
      --text-dark: #34495e;
      --text-light: #7f8c8d;
      --border-color: #ecf0f1;
      --bg-light: #f7f9fc;
      --bg-white: #ffffff;
      --shadow-md: 0 4px 15px rgba(0, 0, 0, 0.07);
      --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    body {
      font-family: 'Poppins', sans-serif; color: var(--text-dark);
      margin: 0; background: var(--bg-light); padding-top: 80px;
    }
    .top-bar {
      background-color: var(--brand-color); color: white; padding: 0 30px;
      height: 80px; width: 100vw; box-sizing: border-box; display: flex;
      align-items: center; gap: 20px; position: fixed; top: 0; left: 0;
      z-index: 1000; box-shadow: var(--shadow-md);
    }
    .logo { font-size: 28px; font-weight: 700; white-space: nowrap; color: white; text-decoration: none; }
    .user-menu-container { position: relative; display: flex; align-items: center; gap: 12px; margin-left: auto; padding-left: 20px; border-left: 1px solid rgba(255, 255, 255, 0.2); cursor: pointer; }
    .user-greeting { color: white; font-weight: 500; font-size: 15px; }
    .user-icon-trigger { font-size: 28px; color: white; }
    .user-dropdown { display: none; position: absolute; top: calc(100% + 15px); right: 0; background-color: var(--bg-white); border-radius: 8px; box-shadow: var(--shadow-lg); min-width: 240px; padding: 15px; z-index: 1001; }
    .user-menu-container.active .user-dropdown { display: block; }
    .user-dropdown-info { font-size: 15px; color: var(--text-dark); margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); text-align: center; }
    .user-dropdown .logout-button { display: block; width: 100%; text-align: center; background-color: var(--brand-color); color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none; }
    
    .main-container {
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    .page-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }
    .action-buttons a {
        color: white;
        border: none;
        padding: 0.8rem 1.5rem;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.2s ease;
        margin-left: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .action-buttons .back-button { background-color: #6c757d; }
    .action-buttons .back-button:hover { background-color: #5a6268; }
    .action-buttons .edit-button { background-color: var(--brand-color); }
    .action-buttons .edit-button:hover { background-color: var(--brand-dark); }
    
    .client-card {
        background-color: var(--bg-white);
        border-radius: 12px;
        padding: 2rem;
        box-shadow: var(--shadow-md);
        margin-bottom: 2rem;
    }
    .client-card h2 {
        font-size: 1.5rem; color: var(--brand-color);
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 0.8rem; margin-top: 0; margin-bottom: 1.5rem;
    }
    .details-section {
        margin-bottom: 1.5rem;
    }
    .details-section:last-child {
        margin-bottom: 0;
    }
    .details-section h3 {
        font-size: 1.2rem;
        color: var(--text-dark);
        margin-bottom: 1rem;
    }
    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    .detail-item {
        display: flex;
        flex-direction: column;
    }
    .detail-item label {
        font-size: 0.9rem;
        color: var(--text-light);
        margin-bottom: 0.3rem;
        font-weight: 500;
    }
    .detail-item span {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-dark);
        word-break: break-word;
    }

    .tabs-container {
        background-color: var(--bg-white);
        border-radius: 12px;
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }
    .tab-nav {
        display: flex;
        background-color: var(--bg-light);
        border-bottom: 1px solid var(--border-color);
    }
    .tab-nav-button {
        padding: 1rem 1.5rem;
        cursor: pointer;
        border: none;
        background: transparent;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-light);
        transition: color 0.2s ease, border-bottom 0.2s ease;
        border-bottom: 3px solid transparent;
    }
    .tab-nav-button.active {
        color: var(--brand-color);
        border-bottom: 3px solid var(--brand-color);
    }
    .tab-content {
        display: none;
        padding: 2rem;
    }
    .tab-content.active { display: block; }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }
    th {
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--text-light);
        text-transform: uppercase;
    }
    tr:last-child td { border-bottom: none; }
    .status-badge {
        padding: 0.3rem 0.8rem;
        border-radius: 15px;
        font-weight: 600;
        font-size: 0.8rem;
    }
    .status-badge.completata { background-color: #eafaf1; color: #155724; }
    .status-badge.in-lavorazione { background-color: #fff3cd; color: #856404; }
    .status-badge.in-attesa { background-color: #e2e3e5; color: #383d41; }
  </style>
</head>
<body>

<header class="top-bar">
  <a href="homepage.php" class="logo">TS SERVICE</a>
  <div class="user-menu-container" id="userMenuContainer">
    <div class="user-greeting">Ciao, <span class="user-name"><?php echo htmlspecialchars(explode(' ', $current_user_name)[0]); ?></span></div>
    <span class="user-icon-trigger"><i class="fas fa-user-circle"></i></span>
    <div class="user-dropdown">
        <div class="user-dropdown-info">
            <strong><?php echo htmlspecialchars($current_user_name); ?></strong>
            <span><?php echo htmlspecialchars($current_user_role); ?></span>
        </div>
        <a href="logout.php" class="logout-button">Logout</a>
    </div>
  </div>
</header>

<main class="main-container">
    <div class="page-header">
        <h1>Scheda Cliente</h1>
        <div class="action-buttons">
            <a href="index.php" class="back-button"><i class="fas fa-arrow-left"></i> Torna Indietro</a>
            <a href="modifica_cliente.php?id=<?php echo $cliente_id; ?>" class="edit-button"><i class="fas fa-pencil-alt"></i> Modifica Cliente</a>
        </div>
    </div>

    <div class="client-card">
        <h2><?php echo htmlspecialchars($display_name); ?></h2>
        
        <div class="details-section">
            <h3>Dati Personali</h3>
            <div class="details-grid">
                <div class="detail-item"><label>Nome</label><span><?php echo htmlspecialchars($cliente['nome'] ?: 'N/D'); ?></span></div>
                <div class="detail-item"><label>Cognome</label><span><?php echo htmlspecialchars($cliente['cognome'] ?: 'N/D'); ?></span></div>
                <div class="detail-item"><label>Telefono</label><span><?php echo htmlspecialchars($cliente['telefono'] ?: 'N/D'); ?></span></div>
                <div class="detail-item"><label>Email</label><span><?php echo htmlspecialchars($cliente['email'] ?: 'N/D'); ?></span></div>
                <div class="detail-item"><label>Indirizzo</label><span><?php echo htmlspecialchars($cliente['indirizzo'] ?: 'N/D'); ?></span></div>
                <div class="detail-item"><label>Città</label><span><?php echo htmlspecialchars($cliente['citta'] ?: 'N/D'); ?></span></div>
                <div class="detail-item" style="grid-column: 1 / -1;"><label>Note Personali</label><span><?php echo htmlspecialchars($cliente['note'] ?: 'Nessuna nota.'); ?></span></div>
            </div>
        </div>

        <?php if (!empty($cliente['ragione_sociale'])): ?>
        <div class="details-section">
            <h3>Dati Aziendali</h3>
            <div class="details-grid">
                <div class="detail-item"><label>Ragione Sociale</label><span><?php echo htmlspecialchars($cliente['ragione_sociale']); ?></span></div>
                <div class="detail-item"><label>Partita IVA</label><span><?php echo htmlspecialchars($cliente['partita_iva'] ?: 'N/D'); ?></span></div>
                <div class="detail-item"><label>Telefono Azienda</label><span><?php echo htmlspecialchars($cliente['telefono_azienda'] ?: 'N/D'); ?></span></div>
                <div class="detail-item"><label>Email Azienda</label><span><?php echo htmlspecialchars($cliente['email_azienda'] ?: 'N/D'); ?></span></div>
                <div class="detail-item"><label>Indirizzo Azienda</label><span><?php echo htmlspecialchars($cliente['indirizzo_azienda'] ?: 'N/D'); ?></span></div>
                <div class="detail-item"><label>Città Azienda</label><span><?php echo htmlspecialchars($cliente['citta_azienda'] ?: 'N/D'); ?></span></div>
                <div class="detail-item" style="grid-column: 1 / -1;"><label>Note Aziendali</label><span><?php echo htmlspecialchars($cliente['note_azienda'] ?: 'Nessuna nota.'); ?></span></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="tabs-container">
        <div class="tab-nav">
            <button class="tab-nav-button active" data-tab="riparazioni">Riparazioni (<?php echo count($riparazioni); ?>)</button>
            <button class="tab-nav-button" data-tab="permute">Permute (<?php echo count($permute); ?>)</button>
            <button class="tab-nav-button" data-tab="prenotazioni">Prenotazioni (<?php echo count($prenotazioni); ?>)</button>
        </div>
        
        <div id="riparazioni" class="tab-content active">
            <h3>Storico Riparazioni</h3>
            <table>
                <thead><tr><th>ID</th><th>Modello</th><th>Stato</th><th>Data</th><th>Azioni</th></tr></thead>
                <tbody>
                    <?php if (empty($riparazioni)): ?>
                        <tr><td colspan="5">Nessuna riparazione trovata per questo cliente.</td></tr>
                    <?php else: ?>
                        <?php foreach ($riparazioni as $item): ?>
                            <tr>
                                <td>#<?php echo $item['id']; ?></td>
                                <td><?php echo htmlspecialchars($item['modello']); ?></td>
                                <td><span class="status-badge <?php echo strtolower(str_replace(' ', '-', $item['stato'])); ?>"><?php echo htmlspecialchars($item['stato']); ?></span></td>
                                <td><?php echo date('d/m/Y', strtotime($item['data_creazione'])); ?></td>
                                <td><a href="dettaglio_riparazione.php?id=<?php echo $item['id']; ?>">Visualizza</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="permute" class="tab-content">
            <h3>Storico Permute</h3>
            <table>
                 <thead><tr><th>ID</th><th>Data</th><th>Prodotto Ceduto</th><th>Prodotto Ricevuto</th><th>Stato</th><th>Azioni</th></tr></thead>
                 <tbody>
                    <?php if (empty($permute)): ?>
                        <tr><td colspan="6">Nessuna permuta trovata per questo cliente.</td></tr>
                    <?php else: ?>
                        <?php foreach ($permute as $item): ?>
                            <tr>
                                <td>#<?php echo $item['id']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($item['data'])); ?></td>
                                <td><?php echo htmlspecialchars($item['modello_nuovo']); ?></td>
                                <td><?php echo htmlspecialchars($item['modello_usato']); ?></td>
                                <td><?php echo htmlspecialchars($item['status']); ?></td>
                                <td><a href="dettaglio_permuta.php?id=<?php echo $item['id']; ?>">Visualizza</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                 </tbody>
            </table>
        </div>

        <div id="prenotazioni" class="tab-content">
            <h3>Storico Prenotazioni</h3>
            <table>
                 <thead><tr><th>ID</th><th>Prodotto</th><th>Quantità</th><th>Totale</th><th>Data</th><th>Stato</th><th>Azioni</th></tr></thead>
                 <tbody>
                    <?php if (empty($prenotazioni)): ?>
                        <tr><td colspan="7">Nessuna prenotazione trovata per questo cliente.</td></tr>
                    <?php else: ?>
                        <?php foreach ($prenotazioni as $item): ?>
                            <tr>
                                <td>#<?php echo $item['id']; ?></td>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo number_format($item['product_total_price'], 2, ',', '.'); ?> €</td>
                                <td><?php echo date('d/m/Y', strtotime($item['reservation_date'])); ?></td>
                                <td><?php echo htmlspecialchars($item['status']); ?></td>
                                <td><a href="dettaglio_prenotazione.php?id=<?php echo $item['id']; ?>">Visualizza</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                 </tbody>
            </table>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestione Tab
    const tabButtons = document.querySelectorAll('.tab-nav-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            tabContents.forEach(content => content.classList.remove('active'));
            document.getElementById(button.dataset.tab).classList.add('active');
        });
    });

    const userMenuContainer = document.getElementById('userMenuContainer');
    if (userMenuContainer) {
        userMenuContainer.addEventListener('click', (event) => {
            event.stopPropagation();
            userMenuContainer.classList.toggle('active');
        });
        document.addEventListener('click', () => {
            userMenuContainer.classList.remove('active');
        });
    }
});
</script>

</body>
</html>

