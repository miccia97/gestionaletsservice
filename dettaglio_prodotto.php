<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();
include 'auth_check.php';

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

?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Dettaglio Prodotto - <?php echo htmlspecialchars($prodotto['nome']); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=<?php echo time(); ?>">
  <style>
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

<?php include 'header.php'; ?>

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

</body>
</html>
