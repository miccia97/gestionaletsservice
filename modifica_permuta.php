<?php
include 'db.php';

// Recupera l'ID della permuta da modificare
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("ID permuta non valido.");
}

// Recupera i dati della permuta
$sql = "SELECT * FROM permute WHERE id = $id";
$result = mysqli_query($conn, $sql);
$permuta = mysqli_fetch_assoc($result);

if (!$permuta) {
    die("Permuta non trovata.");
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Permuta</title>
    <link rel="stylesheet" href="stile.css">
    <style>
        .contenitore {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            border: 1px solid #ccc;
            background: #fff;
            border-radius: 10px;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #218838;
        }

        .annulla {
            background-color: #dc3545;
            margin-left: 10px;
        }

        .annulla:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="contenitore">
        <h2>Modifica Permuta</h2>
        <form action="salva_modifica_permuta.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $permuta['id']; ?>">

            <label for="modello">Modello</label>
            <input type="text" name="modello" value="<?php echo htmlspecialchars($permuta['modello']); ?>">

            <label for="imei">IMEI</label>
            <input type="text" name="imei" value="<?php echo htmlspecialchars($permuta['imei']); ?>">

            <label for="cliente">Cliente</label>
            <input type="text" name="cliente" value="<?php echo htmlspecialchars($permuta['cliente']); ?>">

            <label for="valutazione">Valutazione (€)</label>
            <input type="text" name="valutazione" value="<?php echo htmlspecialchars($permuta['valutazione']); ?>">

            <label for="note">Note</label>
            <textarea name="note" rows="4"><?php echo htmlspecialchars($permuta['note']); ?></textarea>

            <button type="submit">Salva Modifiche</button>
            <a href="permute.php"><button type="button" class="annulla">Annulla</button></a>
        </form>
    </div>
</body>
</html>
