<?php
include 'db.php';

// Recupera ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("ID riparazione non valido");
}

// Preleva stato e costo_effettivo
$stmt = $conn->prepare("SELECT stato, costo_effettivo FROM riparazioni WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Riparazione non trovata");
}
$r = $result->fetch_assoc();

$stato = strtolower($r['stato']);
$costo = isset($r['costo_effettivo']) ? floatval($r['costo_effettivo']) : 0.0;
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Verifica Pagamento</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
      height: 100%;
      font-family: 'Inter', sans-serif;
      background: #eef2f5;
      color: #333;
    }
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }
    .card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 12px 24px rgba(0,0,0,0.1);
      width: 360px;
      text-align: center;
      padding: 2.5rem 1.5rem;
      animation: popIn 0.4s ease both;
    }
    .icon {
      font-size: 72px;
      margin-bottom: 1rem;
      animation: pulse 1.8s infinite;
    }
    h1 {
      font-size: 1.5rem;
      font-weight: 600;
      color: #27ae60;
      margin-bottom: 0.5rem;
    }
    .cancelled h1 { color: #c0392b; }
    p {
      font-size: 1rem;
      color: #555;
      margin-bottom: 1.5rem;
    }
    .amount {
      font-size: 1.4rem;
      color: #c0392b;
      font-weight: 600;
      margin-bottom: 1.5rem;
    }
    .btn {
      display: inline-block;
      background: linear-gradient(135deg, #27ae60, #1e8449);
      color: #fff;
      text-decoration: none;
      padding: 0.75rem 2rem;
      border-radius: 30px;
      font-weight: 600;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      box-shadow: 0 6px 16px rgba(39,174,96,0.3);
    }
    .btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(39,174,96,0.4);
    }
    @keyframes popIn {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }
    @keyframes pulse {
      0%,100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }
  </style>
</head>
<body>

  <div class="card <?php echo $stato==='annullata'?'cancelled':'';?>">
    <?php if ($stato === 'consegnata'): ?>
      <div class="icon">✔️</div>
      <h1>Pagamento COMPLETO</h1>
      <p>La riparazione è stata consegnata e saldata.</p>

    <?php elseif ($stato === 'annullata'): ?>
      <div class="icon">🚫</div>
      <h1>Riparazione ANNULLATA</h1>
      <p>La riparazione non verrà eseguita e non richiede pagamento.</p>

    <?php else: ?>
      <div class="icon">💰</div>
      <h1>Pagamento PENDENTE</h1>
      <div class="amount">
        &euro; <?php echo number_format($costo,2,',','.'); ?>
      </div>
      <p>Importo da saldare per completare il servizio.</p>
    <?php endif; ?>

    <a href="storico_riparazioni.php" class="btn">Torna allo Storico</a>
  </div>

</body>
</html>
