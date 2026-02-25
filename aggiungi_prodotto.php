<?php
session_start();
include 'db.php';

$categorie = [
    'Smartphone', 'Tablet', 'Assistenza', 'Accessori Telefonia', 'Usato', 
    'Pezzi Assistenza', 'Consolle & Giochi', 'Computer', 'Audio', 'Manga', 
    'Action Figure', 'Funko Pop', 'Gadget Pers.', 'SIM', "Carte Collezionabili"
];

$sottocategorie = [];
$res = $conn->query("SELECT nome, categoria_nome FROM sottocategorie ORDER BY categoria_nome, nome ASC");
while ($r = $res->fetch_assoc()) {
    $cat = $r['categoria_nome'];
    if (!isset($sottocategorie[$cat])) $sottocategorie[$cat] = [];
    $sottocategorie[$cat][] = $r['nome'];
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $imei = trim($_POST['imei'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $sottocategoria = trim($_POST['sottocategoria'] ?? '');
    $prezzo_vendita1 = floatval($_POST['prezzo_vendita1'] ?? 0);
    $prezzo_vendita2 = floatval($_POST['prezzo_vendita2'] ?? 0);
    $prezzo_acquisto = floatval($_POST['prezzo_acquisto'] ?? 0);
    $quantita = intval($_POST['quantita'] ?? 0);

    if ($nome === '') $errors[] = "Il campo nome è obbligatorio.";
    if ($categoria === '' || !in_array($categoria, $categorie)) $errors[] = "Categoria non valida.";
    if ($quantita < 0) $errors[] = "Quantità non valida.";
    if ($prezzo_vendita1 < 0) $errors[] = "Prezzo vendita 1 non valido.";
    if ($prezzo_vendita2 < 0) $errors[] = "Prezzo vendita 2 non valido.";
    if ($prezzo_acquisto < 0) $errors[] = "Prezzo acquisto non valido.";

    $img_nome_file = null;
    if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['immagine'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = "Il file immagine deve essere JPG, PNG o GIF.";
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = "L'immagine non deve superare 2MB.";
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $img_nome_file = uniqid('prod_') . '.' . $ext;
            $upload_dir = __DIR__ . '/images/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            if (!move_uploaded_file($file['tmp_name'], $upload_dir . $img_nome_file)) {
                $errors[] = "Errore durante il caricamento dell'immagine.";
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO prodotti 
            (nome, barcode, imei, categoria, sottocategoria, prezzo_vendita1, prezzo_vendita2, prezzo_acquisto, quantita, immagine) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssddis", 
            $nome, $barcode, $imei, $categoria, $sottocategoria, 
            $prezzo_vendita1, $prezzo_vendita2, $prezzo_acquisto, $quantita, $img_nome_file);
        if ($stmt->execute()) {
            $success = true;
            // reset campi dopo successo
            $_POST = [];
        } else {
            $errors[] = "Errore nel salvataggio dati: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Aggiungi Prodotto</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap');

  body {
    font-family: 'Montserrat', sans-serif;
    background: #f0f5f1;
    margin: 0; padding: 0;
    display: flex;
    justify-content: center;
    min-height: 100vh;
    align-items: center;
  }
  main {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    padding: 40px 50px;
    max-width: 600px;
    width: 90vw;
  }
  h1 {
    color: #2e7d32;
    font-weight: 600;
    margin-bottom: 25px;
    text-align: center;
    letter-spacing: 1.2px;
  }
  form label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
  }
  form input[type=text],
  form input[type=number],
  form select,
  form input[type=file] {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #b3d6a4;
    border-radius: 10px;
    font-size: 15px;
    color: #2f3e46;
    margin-bottom: 20px;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
  }
  form input[type=text]:focus,
  form input[type=number]:focus,
  form select:focus,
  form input[type=file]:focus {
    outline: none;
    border-color: #2e7d32;
    box-shadow: 0 0 8px rgba(46,125,50,0.4);
  }
  button[type=submit] {
    background: #2e7d32;
    border: none;
    color: white;
    font-weight: 700;
    font-size: 18px;
    padding: 15px;
    border-radius: 14px;
    width: 100%;
    cursor: pointer;
    transition: background-color 0.25s ease;
    box-shadow: 0 4px 12px rgba(46,125,50,0.5);
  }
  button[type=submit]:hover {
    background: #276028;
  }
  .messages {
    margin-bottom: 30px;
    max-width: 600px;
  }
  .success {
    background-color: #c8e6c9;
    color: #2e7d32;
    padding: 14px 20px;
    border-radius: 12px;
    font-weight: 600;
    box-shadow: 0 3px 10px rgba(46,125,50,0.3);
  }
  .error {
    background-color: #ffcdd2;
    color: #b71c1c;
    padding: 14px 20px;
    border-radius: 12px;
    font-weight: 600;
    box-shadow: 0 3px 10px rgba(183,28,28,0.3);
  }
  .error ul {
    margin: 0; padding-left: 20px;
  }
  @media (max-width: 480px) {
    main {
      padding: 30px 20px;
    }
  }
</style>
<script>
  const sottocategorie = <?php echo json_encode($sottocategorie); ?>;
  function aggiornaSottocategorie() {
    const catSel = document.getElementById('categoria');
    const subcatSel = document.getElementById('sottocategoria');
    const cat = catSel.value;
    subcatSel.innerHTML = '<option value="">-- Nessuna --</option>';
    if (cat in sottocategorie) {
      sottocategorie[cat].forEach(sc => {
        const opt = document.createElement('option');
        opt.value = sc;
        opt.textContent = sc;
        subcatSel.appendChild(opt);
      });
    }
  }
  window.addEventListener('DOMContentLoaded', () => {
    document.getElementById('categoria').addEventListener('change', aggiornaSottocategorie);
    aggiornaSottocategorie();
  });
</script>
</head>
<body>
<main>
  <h1>Aggiungi Nuovo Prodotto</h1>

  <div class="messages" role="alert" aria-live="polite" aria-atomic="true">
    <?php if ($success): ?>
      <div class="success">Prodotto aggiunto con successo!</div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="error">
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>

  <form method="POST" enctype="multipart/form-data" novalidate>
    <label for="nome">Nome Prodotto *</label>
    <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($_POST['nome'] ?? '') ?>" />

    <label for="barcode">Barcode</label>
    <input type="text" id="barcode" name="barcode" value="<?php echo htmlspecialchars($_POST['barcode'] ?? '') ?>" />

    <label for="imei">IMEI</label>
    <input type="text" id="imei" name="imei" value="<?php echo htmlspecialchars($_POST['imei'] ?? '') ?>" />

    <label for="categoria">Categoria *</label>
    <select id="categoria" name="categoria" required>
      <option value="">-- Seleziona Categoria --</option>
      <?php foreach ($categorie as $cat): ?>
        <option value="<?php echo htmlspecialchars($cat); ?>" <?php if (($_POST['categoria'] ?? '') === $cat) echo 'selected'; ?>><?php echo htmlspecialchars($cat); ?></option>
      <?php endforeach; ?>
    </select>

    <label for="sottocategoria">Sottocategoria</label>
    <select id="sottocategoria" name="sottocategoria">
      <option value="">-- Nessuna --</option>
    </select>

    <label for="prezzo_vendita1">Prezzo vendita 1 (€) *</label>
    <input type="number" step="0.01" min="0" id="prezzo_vendita1" name="prezzo_vendita1" required value="<?php echo htmlspecialchars($_POST['prezzo_vendita1'] ?? ''); ?>" />

    <label for="prezzo_vendita2">Prezzo vendita 2 (€)</label>
    <input type="number" step="0.01" min="0" id="prezzo_vendita2" name="prezzo_vendita2" value="<?php echo htmlspecialchars($_POST['prezzo_vendita2'] ?? ''); ?>" />

    <label for="prezzo_acquisto">Prezzo acquisto (€)</label>
    <input type="number" step="0.01" min="0" id="prezzo_acquisto" name="prezzo_acquisto" value="<?php echo htmlspecialchars($_POST['prezzo_acquisto'] ?? ''); ?>" />

    <label for="quantita">Quantità *</label>
    <input type="number" id="quantita" name="quantita" min="0" value="<?php echo htmlspecialchars($_POST['quantita'] ?? '0'); ?>" required />

    <label for="immagine">Immagine Prodotto (JPG, PNG, GIF max 2MB)</label>
    <input type="file" id="immagine" name="immagine" accept=".jpg,.jpeg,.png,.gif" />

    <button type="submit">Aggiungi Prodotto</button>
  </form>
</main>
</body>
</html>
