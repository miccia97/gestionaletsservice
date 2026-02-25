<?php
include 'db.php';

$ripId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($ripId <= 0) {
    die("ID riparazione non valido");
}

// Recupera riparazione + cliente + email
$sql = "SELECT r.*, c.nome AS cliente_nome, c.cognome AS cliente_cognome, c.email 
        FROM riparazioni r
        LEFT JOIN clienti_nuovo c ON r.cliente_id = c.id
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ripId);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("Riparazione non trovata");
}
$r = $res->fetch_assoc();

// Prepara valori
$cliente     = trim(($r['cliente_nome'] ?? '') . ' ' . ($r['cliente_cognome'] ?? '')) ?: 'Cliente';
$emailTo     = filter_var($r['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';
$subject     = "Dettagli Riparazione #{$ripId}";
$costo       = number_format(floatval($r['costo_effettivo'] ?? 0), 2, ',', '.');
$diagnosi    = nl2br(htmlspecialchars($r['diagnosi'] ?? ''));
$dataRipara  = htmlspecialchars($r['data'] ?? date("d/m/Y"));
$descrizione = nl2br(htmlspecialchars($r['descrizione'] ?? ''));

// Gestione invio
$sent = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to      = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $subject = substr($_POST['subject'], 0, 100);
    $message = $_POST['message'];
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: no-reply@tuodominio.it\r\n";

    if ($to) {
        if (mail($to, $subject, $message, $headers)) {
            $sent = true;
        } else {
            $error = "Errore nell'invio dell'email.";
        }
    } else {
        $error = "Indirizzo email non valido.";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Invia Email Riparazione #<?php echo $ripId; ?></title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#f0f2f5;color:#333;display:flex;justify-content:center;padding:2rem;}
    .card{background:#fff;width:600px;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,0.1);overflow:hidden;}
    header{background:linear-gradient(135deg,#27ae60,#1e8449);color:#fff;padding:1.5rem;text-align:center;font-size:1.5rem;}
    .body{padding:2rem;}
    label{display:block;margin-top:1rem;font-weight:600;color:#555;}
    input[type=email], input[type=text], textarea{
      width:100%;padding:.75rem;border:1px solid #ccc;border-radius:4px;margin-top:.5rem;
      font-size:1rem;font-family:inherit;
    }
    textarea{height:200px;resize:vertical;}
    .btn{
      display:inline-block;background:linear-gradient(135deg,#27ae60,#1e8449);
      color:#fff;padding:.75rem 2rem;border-radius:30px;font-weight:600;
      margin-top:1.5rem;box-shadow:0 6px 16px rgba(39,174,96,0.3);
      text-decoration:none;cursor:pointer;
    }
    .btn:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(39,174,96,0.4);}
    .alert{margin-top:1rem;padding:.75rem 1rem;border-radius:4px;}
    .success{background:#e8f7e4;color:#2e7d32;}
    .error{background:#fdecea;color:#c0392b;}
  </style>
</head>
<body>

<div class="card">
  <header>Invia Email Riparazione #<?php echo $ripId; ?></header>
  <div class="body">
    <?php if ($sent): ?>
      <div class="alert success">Email inviata con successo a <?php echo htmlspecialchars($to); ?>!</div>
    <?php elseif($error): ?>
      <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
      <label for="email">A:</label>
      <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($emailTo); ?>">

      <label for="subject">Oggetto:</label>
      <input type="text" id="subject" name="subject" required value="<?php echo htmlspecialchars($subject); ?>">

      <label for="message">Messaggio (HTML):</label>
      <textarea id="message" name="message"><?php
echo <<<HTML
<p>Buongiorno <strong>{$cliente}</strong>,</p>
<p>In allegato trovi i dettagli della riparazione <strong>#{$ripId}</strong> eseguita in data {$dataRipara}.</p>
<ul>
  <li><strong>Costo Effettivo:</strong> € {$costo}</li>
</ul>
<p><strong>Diagnosi:</strong></p>
<div style="padding:10px;background:#f7f9fa;border-radius:4px;">{$diagnosi}</div>
<p>Grazie per averci scelto!</p>
HTML;
?></textarea>

      <button type="submit" class="btn">Invia Email</button>
    </form>
  </div>
</div>

</body>
</html>
