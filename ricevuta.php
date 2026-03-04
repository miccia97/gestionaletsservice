<?php
include 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("ID riparazione non valido");
}

// Preleva riparazione + cliente
$sql = "SELECT r.*, c.nome AS cliente_nome, c.cognome AS cliente_cognome
        FROM riparazioni r
        LEFT JOIN clienti_nuovo c ON r.cliente_id = c.id
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("Riparazione non trovata");
}
$r = $res->fetch_assoc();

// Dati formattati
$dataOggi = date("d/m/Y");
$cliente  = trim(($r['cliente_nome'] ?? '') . ' ' . ($r['cliente_cognome'] ?? '')) ?: 'N/D';
$costo    = isset($r['costo_effettivo']) ? floatval($r['costo_effettivo']) : 0.0;
$diagnosi = $r['diagnosi'] ?? '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Ricevuta Riparazione #<?php echo htmlspecialchars($id); ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-dHw6qILqo+K8fP5h6vv0e8KraXpWcS0Xqp69g0JytjKIPdX6v+ziM2qgFt0Oht6ujxYkdOi0Zr4Pps11YTL+Sw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');
    * { box-sizing: border-box; margin:0; padding:0 }
    body {
      font-family: 'Inter', sans-serif;
      background: #eef2f5;
      display: flex; justify-content: center; padding: 2rem 1rem;
    }
    .receipt {
      background: white;
      width: 600px;
      border-radius: 8px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      overflow: hidden;
      animation: pop 0.4s ease;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .receipt:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
    .header {
      background: linear-gradient(135deg,#27ae60,#1e8449);
      color: #fff;
      text-align: center;
      padding: 1.5rem;
    }
    .header h1 {
      font-size: 1.75rem; font-weight:600; margin-bottom:0.5rem;
    }
    .header .date {
      font-size: 1rem; opacity: 0.9;
    }
    .body {
      padding: 2rem; font-size: 1rem; color: #333;
    }
    .line {
      display:flex; justify-content: space-between; margin-bottom:1rem;
      align-items: center;
    }
    .line .label {
      font-weight:500; color:#555;
      display: flex; align-items: center; gap: 8px;
      transition: color 0.3s;
    }
    .line .label i {
      color: #27ae60;
      transition: transform 0.3s;
    }
    .line:hover .label {
      color: #27ae60;
    }
    .line:hover .label i {
      transform: scale(1.2);
    }
    .line .value {
      font-weight:600; color:#222;
    }
    .desc-label {
      font-weight:500; margin-top:1.5rem; color:#555;
      display: flex; align-items: center; gap: 8px;
    }
    .desc-label i {
      color: #27ae60;
    }
    .desc {
      background:#f7f9fa; padding:1rem; border-radius:4px;
      font-size:0.95rem; color:#444; margin-top:0.5rem;
      line-height:1.4; max-height: 250px; overflow-y: auto;
      box-shadow: inset 0 2px 6px rgba(0,0,0,0.05);
    }
    .total {
      margin-top:2rem; font-size:1.25rem;
      font-weight:700; text-align:right; color:#27ae60;
      display: flex; justify-content: flex-end; align-items: center; gap: 6px;
    }
    .total i {
      font-size: 1.2rem; color: #27ae60;
    }
    .footer {
      text-align:center; padding:1rem;
      background:#f0f4f8; font-size:0.85rem; color:#777;
    }
    @keyframes pop {
      from {opacity:0; transform: scale(0.95)}
      to   {opacity:1; transform: scale(1)}
    }
  </style>
</head>
<body>

  <div class="receipt">
    <div class="header">
      <h1><i class="fas fa-receipt"></i> Ricevuta #<?php echo htmlspecialchars($id); ?></h1>
      <div class="date"><i class="fas fa-calendar-alt"></i> <?php echo $dataOggi; ?></div>
    </div>
    <div class="body">
      <div class="line">
        <div class="label"><i class="fas fa-user"></i> Cliente</div>
        <div class="value"><?php echo htmlspecialchars($cliente); ?></div>
      </div>
      <div class="line">
        <div class="label"><i class="fas fa-hashtag"></i> Riparazione ID</div>
        <div class="value"><?php echo htmlspecialchars($id); ?></div>
      </div>
      <div class="line">
        <div class="label"><i class="fas fa-euro-sign"></i> Costo Effettivo</div>
        <div class="value">&euro; <?php echo number_format($costo,2,',','.'); ?></div>
      </div>

      <div class="desc-label"><i class="fas fa-stethoscope"></i> Diagnosi</div>
      <div class="desc"><?php echo nl2br(htmlspecialchars($diagnosi)); ?></div>

      <div class="total"><i class="fas fa-money-bill-wave"></i> € <?php echo number_format($costo,2,',','.'); ?></div>
    </div>
    <div class="footer">
      Grazie per aver scelto il nostro servizio!
    </div>
  </div>

</body>
</html>
