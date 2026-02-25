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

// --- Recupero ID prodotto dall'URL ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID prodotto non valido o non fornito.");
}
$prodotto_id = (int)$_GET['id'];

// --- Recupero Dati del Prodotto ---
$stmt_prodotto = $conn->prepare("SELECT * FROM prodotti WHERE id = ?");
$stmt_prodotto->bind_param("i", $prodotto_id);
$stmt_prodotto->execute();
$result_prodotto = $stmt_prodotto->get_result();

if ($result_prodotto->num_rows === 0) {
    die("Prodotto non trovato.");
}
$prodotto = $result_prodotto->fetch_assoc();
$stmt_prodotto->close();


// Recupera le informazioni dell'utente dalla sessione per l'header
$current_user_name = $_SESSION['user_name'] ?? 'Ospite';
$current_user_role = $_SESSION['role'] ?? 'N/D';

?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dettaglio Prodotto - <?php echo htmlspecialchars($prodotto['nome']); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    /* Copia qui gli stili CSS da index.html per mantenere la coerenza */
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
    /* Stili Header (copiati da index.html) */
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
    
    /* Stili per la pagina Dettaglio Prodotto */
    .main-container {
        padding: 2rem;
        max-width: 900px;
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
        background-color: #6c757d;
        color: white;
        border: none;
        padding: 0.8rem 1.5rem;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.2s ease;
    }
    .action-buttons a:hover { background-color: #5a6268; }
    
    .product-card {
        background-color: var(--bg-white);
        border-radius: 12px;
        padding: 2rem;
        box-shadow: var(--shadow-md);
    }
    .product-card h2 {
        font-size: 1.5rem; color: var(--brand-color);
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 0.8rem; margin-top: 0; margin-bottom: 1.5rem;
    }
    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
    }
    .price-display {
        color: var(--brand-color);
        font-size: 1.5rem !important;
    }

  </style>
</head>
<body>

<!-- Header -->
<header class="top-bar">
  <a href="index.php" class="logo">TS SERVICE</a>
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
        <h1>Dettaglio Prodotto</h1>
        <div class="action-buttons">
            <a href="inventario.php"><i class="fas fa-arrow-left"></i> Torna all'Inventario</a>
        </div>
    </div>

    <div class="product-card">
        <h2><?php echo htmlspecialchars($prodotto['nome']); ?></h2>
        <div class="details-grid">
            <div class="detail-item"><label>Categoria</label><span><?php echo htmlspecialchars($prodotto['categoria'] ?: 'N/D'); ?></span></div>
            <div class="detail-item"><label>Quantità in Magazzino</label><span><?php echo htmlspecialchars($prodotto['quantita']); ?> pz</span></div>
            <div class="detail-item"><label>Barcode / Seriale</label><span><?php echo htmlspecialchars($prodotto['barcode'] ?: 'N/D'); ?></span></div>
            <div class="detail-item"><label>Prezzo di Acquisto</label><span class="price-display"><?php echo number_format($prodotto['prezzo_acquisto'], 2, ',', '.'); ?> €</span></div>
            <div class="detail-item"><label>Prezzo di Vendita 1</label><span class="price-display"><?php echo number_format($prodotto['prezzo_vendita1'], 2, ',', '.'); ?> €</span></div>
            <div class="detail-item"><label>Prezzo di Vendita 2</label><span class="price-display"><?php echo number_format($prodotto['prezzo_vendita2'], 2, ',', '.'); ?> €</span></div>
            <div class="detail-item"><label>Data Creazione</label><span><?php echo date('d/m/Y H:i', strtotime($prodotto['data_creazione'])); ?></span></div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
