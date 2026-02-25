<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('db.php');
function normalizza($str) {
    return strtolower(preg_replace('/[^a-z0-9]+/', '_', $str));
}

$categorie = [];
$result = $conn->query("SELECT nome FROM categorie ORDER BY nome ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categorie[] = $row['nome'];
    }
    $result->free();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim(htmlspecialchars($_POST['nome'] ?? ''));
    $categoria_nome = trim(htmlspecialchars($_POST['categoria'] ?? ''));

    $errors = [];

    if (!$nome) $errors[] = "Il nome della sottocategoria è obbligatorio.";
    if (!$categoria_nome) $errors[] = "La categoria è obbligatoria.";

    if (empty($errors)) {
        // Controlla se la sottocategoria esiste già per quella categoria (usando nome)
        $stmt_check = $conn->prepare("SELECT id FROM sottocategorie WHERE nome = ? AND categoria_nome = ?");
        $stmt_check->bind_param("ss", $nome, $categoria_nome);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $errors[] = "Questa sottocategoria esiste già per la categoria selezionata.";
        }
        $stmt_check->close();
    }

    if (empty($errors)) {
        // Inserisci sottocategoria con nome categoria
        $stmt = $conn->prepare("INSERT INTO sottocategorie (nome, categoria_nome) VALUES (?, ?)");
        $stmt->bind_param("ss", $nome, $categoria_nome);
        if ($stmt->execute()) {
            $success_msg = "Sottocategoria aggiunta con successo!";
            $_POST = [];
        } else {
            $errors[] = "Errore database: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Aggiungi Sottocategoria - Gestionale</title>
<style>
/* Stili come prima */
body { font-family: Arial, sans-serif; background:#f5f7fa; padding:20px; }
.container { max-width: 400px; margin: 0 auto; background:#fff; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}
h1 { text-align:center; margin-bottom:20px; }
label { display:block; margin-top:15px; font-weight:bold; }
input, select { width: 100%; padding:8px; margin-top:5px; border-radius:5px; border:1px solid #ccc; }
button { margin-top:20px; width:100%; padding:12px; background:#2563eb; color:#fff; border:none; border-radius:8px; font-weight:bold; cursor:pointer; }
button:hover { background:#1d4ed8; }
.error { background:#fdd; padding:10px; margin-bottom:15px; border-radius:5px; color:#900; }
.success { background:#dfd; padding:10px; margin-bottom:15px; border-radius:5px; color:#060; }
</style>
</head>
<body>
<div class="container">
    <h1>Aggiungi Sottocategoria</h1>

    <?php if (!empty($errors)): ?>
        <div class="error" role="alert">
            <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= $e ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif (!empty($success_msg)): ?>
        <div class="success" role="status"><?= $success_msg ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
        <label for="nome">Nome sottocategoria *</label>
        <input type="text" id="nome" name="nome" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">

        <label for="categoria">Categoria *</label>
        <select id="categoria" name="categoria" required>
            <option value="">Seleziona categoria</option>
            <?php foreach ($categorie as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= (($_POST['categoria'] ?? '') === $cat) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Aggiungi Sottocategoria</button>
    </form>
</div>
</body>
</html>
