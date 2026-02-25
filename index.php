<?php
session_start(); // importante per il carrello

// Connessione al database
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'gestionale_tsservice';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Prendo i valori di filtro dalla query string, se esistono
$categoriaSelezionata = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$nomeProdotto = isset($_GET['nomeProdotto']) ? $_GET['nomeProdotto'] : '';
$barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
$imei = isset($_GET['imei']) ? $_GET['imei'] : '';

// Costruisco la query dinamicamente
$query = "SELECT id, nome, descrizione, prezzo_vendita1, prezzo_vendita2, prezzo_acquisto, quantita, categoria, immagine FROM prodotti WHERE 1=1 ";
$params = [];
$types = "";

if ($categoriaSelezionata !== '') {
    $query .= " AND categoria = ? ";
    $params[] = $categoriaSelezionata;
    $types .= "s";
}
if ($nomeProdotto !== '') {
    $query .= " AND nome LIKE ? ";
    $params[] = "%" . $nomeProdotto . "%";
    $types .= "s";
}
if ($barcode !== '') {
    $query .= " AND barcode LIKE ? ";
    $params[] = "%" . $barcode . "%";
    $types .= "s";
}
if ($imei !== '') {
    $query .= " AND imei LIKE ? ";
    $params[] = "%" . $imei . "%";
    $types .= "s";
}

$stmt = $conn->prepare($query);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Recupero dati carrello da sessione
$carrello = isset($_SESSION['carrello']) ? $_SESSION['carrello'] : [];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>TS SERVICE - Gestionale</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
    /* Solo per aggiunta riepilogo carrello a destra */
    .main-content {
        display: flex;
        gap: 10px;
    }
    .sidebar {
        flex: 0 0 150px;
    }
    .main-panel {
        flex: 1;
        display: flex;
        gap: 15px;
    }
    .product-grid {
        flex: 3;
        display: grid;
        grid-template-columns: repeat(5,1fr,);
        gap: 10px;
    }
.cart-summary {
    flex: 1;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    padding: 10px;
    width: 250px;
    box-sizing: border box;
    max-height: 600px;
    overflow-y: auto;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #333;
    margin: 0 auto;
}

.cart-summary h2 {
    margin-bottom: 8px;
    font-size: 1.5em;
    color: #2c3e50;
    border-bottom: 2px solid #27ae60;
    padding-bottom: 8px;
    font-weight: 700;
    text-align: center;
}

.cart-item {
    background: #f5f8fa;
    margin-bottom: 12px;
    padding: 12px 15px;
    border-radius: 10px;
    box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cart-item:hover {
    background: #e9f5ef;
}

.cart-header {
    font-size: 1em;
    font-weight: 600;
    margin-bottom: 8px;
    color: #34495e;
}

.cart-details {
    font-size: 0.95em;
    color: #555;
}

.cart-total-box {
    background: #27ae60;
    color: white;
    border-radius: 12px;
    padding: 15px 20px;
    margin-top: 20px;
    box-shadow: 0 4px 8px rgba(39, 174, 96, 0.5);
    text-align: center;
    font-size: 1.4em;
    font-weight: 700;
}

.cart-total {
    margin: 0;
}

.cart-summary table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
}

.cart-summary th {
    text-align: left;
    padding: 10px 8px;
    color: #27ae60;
    font-weight: 700;
    border-bottom: 2px solid #27ae60;
}

.cart-summary td {
    background: #fefefe;
    padding: 12px 8px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    vertical-align: middle;
}

.cart-summary button {
    background: transparent;
    border: none;
    color: #e74c3c;
    font-size: 1.2em;
    cursor: pointer;
    transition: color 0.3s ease;
}

.cart-summary button:hover {
    color: #c0392b;
}


    </style>
</head>
<body>
    <?php include 'header.php'; ?>

<div class="main-content">
    <aside class="sidebar">
        <a href="index.php"><button class="reset-btn">Reset</button></a>
        <?php
        $categorie = ['Smartphone', 'Tablet', 'Assistenza', 'Accessori Telefonia', 'Usato', 'Pezzi Assistenza', 'Consolle & Giochi', 'Computer', 'Audio', 'Manga', 'Action Figure', 'Funko Pop', 'Gadget Pers.', 'SIM'];
        foreach ($categorie as $cat) {
            echo '<a href="?categoria=' . urlencode($cat) . '"><button class="category-btn">' . htmlspecialchars($cat) . '</button></a>';
        }
        ?>
    </aside>

    <div class="main-panel">
        <div style="flex:1">
            <section class="input-section">
                <form method="GET" action="index.php" id="filterForm">
                    <div class="input-row">
                        <div class="input-group">
                            <label for="nomeProdotto">Nome Prodotto</label>
                            <input type="text" id="nomeProdotto" name="nomeProdotto" value="<?= htmlspecialchars($nomeProdotto) ?>" placeholder="Inserisci qui">
                        </div>
                        <div class="input-group">
                            <label for="barcode">Barcode</label>
                            <input type="text" id="barcode" name="barcode" value="<?= htmlspecialchars($barcode) ?>" placeholder="Inserisci qui">
                        </div>
                        <div class="input-group">
                            <label for="imei">IMEI</label>
                            <input type="text" id="imei" name="imei" value="<?= htmlspecialchars($imei) ?>" placeholder="Inserisci qui">
                        </div>
                    </div>
                    <?php if ($categoriaSelezionata): ?>
                        <input type="hidden" name="categoria" value="<?= htmlspecialchars($categoriaSelezionata) ?>">
                        <p>Categoria selezionata: <strong><?= htmlspecialchars($categoriaSelezionata) ?></strong></p>
                    <?php endif; ?>
                </form>
            </section>

            <div class="product-grid">
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $id = $row['id'];
                        echo '<div class="product-card" data-id="' . $id . '">';
                        echo '<img src="images/' . htmlspecialchars($row['immagine']) . '" alt="Foto Prodotto">';
                        echo '<strong>' . htmlspecialchars($row['nome']) . '</strong>';
                        echo '<div class="prezzi">';
                        echo '<label><input type="checkbox" class="prezzo1"> €' . number_format($row['prezzo_vendita1'], 2) . '</label>';
                        echo '<label><input type="checkbox" class="prezzo2"> €' . number_format($row['prezzo_vendita2'], 2) . '</label>';
                        echo '</div>';
                        echo '<div class="giacenza">Giacenza: ' . intval($row['quantita']) . '</div>';
                        echo '<div class="cart-controls">';
                        echo '<button class="freccia-down">−</button>';
                        echo '<input type="number" value="1" min="1" max="' . intval($row['quantita']) . '" class="quantita-input">';
                        echo '<button class="freccia-up">+</button>';
                        echo '<button class="carrello-btn" title="Aggiungi al carrello" aria-label="Aggiungi al carrello">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="green" viewBox="0 0 24 24">
                                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 
                                    0c-1.1 0-1.99.9-1.99 2S15.9 22 17 22s2-.9 2-2-.9-2-2-2zM7.16 
                                    14l.84-2h7.45c.75 0 1.41-.41 1.75-1.03L21 4H5.21l-.94-2H0v2h2l3.6 
                                    7.59-1.35 2.44C3.52 14.37 4.48 16 6 16h12v-2H7.16z"/>
                                </svg>
                              </button>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Nessun prodotto trovato.</p>';
                }
                $conn->close();
                ?>
            </div>
        </div>

        <!-- Riepilogo carrello -->
<aside class="cart-summary" aria-label="Riepilogo Carrello">

<h2>Anteprima</h2>

<?php if (empty($carrello)): ?>
    <p>Il carrello è vuoto.</p>
<?php else: 
    $totaleCarrello = 0;
?>

<!-- FORM che invia i dati a carrello.php -->
<form id="formConfermaCarrello" method="POST" action="carrello.php">

    <!-- Campo hidden che conterrà i dati JSON -->
    <input type="hidden" name="carrello_json" id="carrello_json" value="">

    <table style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color:#ddd;">
                <th style="text-align:left; padding:8px; border-bottom:1px solid #ccc;">Nome Prodotto</th>
                <th style="text-align:center; padding:8px; border-bottom:1px solid #ccc;">Quantità</th>
                <th style="text-align:right; padding:8px; border-bottom:1px solid #ccc;">Prezzo (€)</th>
                <th style="width:30px;"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($carrello as $index => $item): 
                $subtotal = $item['prezzo'] * $item['quantita'];
                $totaleCarrello += $subtotal;
            ?>
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:8px;"><?= htmlspecialchars($item['nome']) ?></td>
                <td style="text-align:center; padding:8px;"><?= intval($item['quantita']) ?></td>
                <td style="text-align:right; padding:8px;"><?= number_format($item['prezzo'], 2) ?></td>
                <td style="text-align:center; padding:8px;">
                    <!-- Puoi mantenere il form per rimuovere item, opzionale -->
                    <form method="POST" action="rimuovi_carrello.php" style="margin:0;">
                        <input type="hidden" name="index" value="<?= $index ?>">
                        <button type="submit" title="Rimuovi" style="background:none; border:none; color:red; font-weight:bold; cursor:pointer;">✖</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="padding:8px; font-weight:bold; text-align:right;">Totale Carrello:</td>
                <td style="padding:8px; font-weight:bold; text-align:right;">€<?= number_format($totaleCarrello, 2) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Pulsante di submit del form -->
    <button type="submit" id="btnConfermaCarrello" style="
        padding: 14px 32px;
        background: linear-gradient(135deg, #00c853, #64dd17);
        color: white;
        border: none;
        border-radius: 40px;
        font-weight: bold;
        font-size: 16px;
        cursor: pointer;
        box-shadow: 0 0 0 rgba(0, 200, 83, 0.4);
        transition: all 0.3s ease;
        float: right;
        margin-top: 10px;
        letter-spacing: 1px;
    ">
        ✅ Conferma Carrello
    </button>

</form>

<!-- Pulsante Svuota Carrello rimane fuori dal form -->
<button id="btnSvuotaCarrello" style="
    padding: 18px 40px;
    background: linear-gradient(45deg, #dc3545, #ff6f61);
    color: white;
    border: none;
    border-radius: 40px;
    font-weight: bold;
    font-size: 16px;
    box-shadow: 0 4px 10px rgba(220, 53, 69, 0.5);
    cursor: pointer;
    transition: box-shadow 0.3s ease, transform 0.2s;
    float: left;
    margin-top: 10px;
    letter-spacing: 1px;
">
    Svuota Carrello
</button>

<script>
    // Aggiungo animazioni pulsante conferma
    const btn = document.getElementById('btnConfermaCarrello');
    btn.addEventListener('mouseenter', () => {
        btn.style.boxShadow = '0 0 15px rgba(0, 200, 83, 0.8)';
        btn.style.transform = 'scale(1.03)';
    });
    btn.addEventListener('mouseleave', () => {
        btn.style.boxShadow = '0 0 0 rgba(0, 200, 83, 0.4)';
        btn.style.transform = 'scale(1)';
    });

    // Quando invii il form, serializzo i dati carrello dentro carrello_json
    document.getElementById('formConfermaCarrello').addEventListener('submit', function(e) {
        // Prendi carrello da localStorage o da dove tieni i dati in JS
        const carrello = JSON.parse(localStorage.getItem('carrello') || '[]');

        if(carrello.length === 0) {
            alert("Il carrello è vuoto.");
            e.preventDefault(); // blocca invio
            return;
        }
        // Serializzo carrello in input hidden
        document.getElementById('carrello_json').value = JSON.stringify(carrello);
    });

    // Pulsante Svuota Carrello
    const btnSvuota = document.getElementById('btnSvuotaCarrello');
    btnSvuota.addEventListener('mouseenter', () => {
        btnSvuota.style.boxShadow = '0 6px 15px rgba(220, 53, 69, 0.8)';
        btnSvuota.style.transform = 'scale(1.03)';
    });
    btnSvuota.addEventListener('mouseleave', () => {
        btnSvuota.style.boxShadow = '0 4px 10px rgba(220, 53, 69, 0.5)';
        btnSvuota.style.transform = 'scale(1)';
    });
    btnSvuota.addEventListener('click', () => {
        if(confirm("Sei sicuro di voler svuotare tutto il carrello?")) {
            window.location.href = 'svuota_carrello.php';
        }
    });
</script>

<?php endif; ?>
</aside>



    </div>
</div>

<script>
const form = document.getElementById('filterForm');
let timeout = null;
let isSubmitting = false;

form.querySelectorAll('input[type="text"]').forEach(input => {
  input.addEventListener('input', () => {
    clearTimeout(timeout);
    if (isSubmitting) return;

    timeout = setTimeout(() => {
      isSubmitting = true;
      form.submit();
    }, 500);
  });
});

// Quantità +/-
document.querySelectorAll('.cart-controls').forEach(control => {
    const input = control.querySelector('.quantita-input');
    const btnUp = control.querySelector('.freccia-up');
    const btnDown = control.querySelector('.freccia-down');

    const maxQuantity = parseInt(input.max, 10);

    btnUp.addEventListener('click', () => {
        let current = parseInt(input.value) || 1;
        if (current < maxQuantity) input.value = current + 1;
    });

    btnDown.addEventListener('click', () => {
        let current = parseInt(input.value) || 1;
        if (current > 1) input.value = current - 1;
    });
});

// Solo un prezzo selezionabile
document.querySelectorAll('.product-card').forEach(card => {
    const prezzo1 = card.querySelector('.prezzo1');
    const prezzo2 = card.querySelector('.prezzo2');

    prezzo1.addEventListener('change', () => {
        if (prezzo1.checked) prezzo2.checked = false;
    });
    prezzo2.addEventListener('change', () => {
        if (prezzo2.checked) prezzo1.checked = false;
    });

    // Aggiunta al carrello con AJAX
    card.querySelector('.carrello-btn').addEventListener('click', () => {
        const idProdotto = card.getAttribute('data-id');
        const quantita = parseInt(card.querySelector('.quantita-input').value) || 1;
        let prezzoSelezionato = 0;
        if (prezzo1.checked) {
            prezzoSelezionato = parseFloat(prezzo1.parentElement.textContent.replace(/[^\d.,]/g, '').replace(',', '.'));
        } else if (prezzo2.checked) {
            prezzoSelezionato = parseFloat(prezzo2.parentElement.textContent.replace(/[^\d.,]/g, '').replace(',', '.'));
        } else {
            alert('Seleziona un prezzo per aggiungere al carrello.');
            return;
        }

        // Dati da inviare
        const formData = new FormData();
        formData.append('id', idProdotto);
        formData.append('quantita', quantita);
        formData.append('prezzo', prezzoSelezionato);

        fetch('aggiungi_carrello.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json())
          .then(data => {
            if (data.success) {
                alert('Prodotto aggiunto al carrello');
                window.location.reload(); // ricarica per aggiornare riepilogo
            } else {
                alert('Errore: ' + data.message);
            }
        }).catch(() => {
            alert('Errore di rete, riprova.');
        });
    });
});
</script>
<form id="formCarrello" method="POST" action="carrello.php">
  <input type="hidden" name="carrello_json" id="carrello_json" value="">
  
  <button type="submit" id="btnConfermaCarrello" style="..." ✅ Conferma Carrello
        padding: 14px 32px;
        background: linear-gradient(135deg, #00c853, #64dd17);
        color: white;
        border: none;
        border-radius: 40px;
        font-weight: bold;
        font-size: 16px;
        cursor: pointer;
        box-shadow: 0 0 0 rgba(0, 200, 83, 0.4);
        transition: all 0.3s ease;
        float: right;
        margin-top: 10px;
        letter-spacing: 1px;
    ">
  </button>
</form>

<script>
// Funzioni di gestione carrello in localStorage
function caricaCarrello() {
  return JSON.parse(localStorage.getItem('carrello') || '[]');
}
function salvaCarrello(carrello) {
  localStorage.setItem('carrello', JSON.stringify(carrello));
}

// Quando si invia il form, aggiorna il campo nascosto col JSON del carrello
document.getElementById('formCarrello').addEventListener('submit', function(e){
  const carrello = caricaCarrello();
  if(carrello.length === 0){
    alert("Il carrello è vuoto, aggiungi qualche prodotto prima di confermare.");
    e.preventDefault();
    return;
  }
  document.getElementById('carrello_json').value = JSON.stringify(carrello);
});


// Qui metti le tue funzioni JS per aggiungere/rimuovere prodotti, esempio:
function aggiungiAlCarrello(idProdotto){
  let carrello = caricaCarrello();
  const index = carrello.findIndex(item => item.id_prodotto === idProdotto);
  if(index === -1){
    carrello.push({id_prodotto: idProdotto, quantita: 1});
  } else {
    carrello[index].quantita++;
  }
  salvaCarrello(carrello);
  // aggiorna la visualizzazione se vuoi
}
</script>

</body>
</html>


