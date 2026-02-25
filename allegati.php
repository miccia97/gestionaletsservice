<?php
include 'db.php';

$ripId = isset($_GET['riparazione_id']) ? intval($_GET['riparazione_id']) : 0;
if ($ripId <= 0) {
    die("ID riparazione non valido");
}

// Recupera il nome cliente per contestualizzare (opzionale)
$stmt0 = $conn->prepare("SELECT cliente_id FROM riparazioni WHERE id = ?");
$stmt0->bind_param("i", $ripId);
$stmt0->execute();
$res0 = $stmt0->get_result();
if ($res0->num_rows === 0) {
    die("Riparazione non trovata");
}
$r0 = $res0->fetch_assoc();

// Recupera allegati
$stmt = $conn->prepare("SELECT id, nome_file, percorso_file, data_upload FROM allegati WHERE riparazione_id = ? ORDER BY data_upload DESC");
$stmt->bind_param("i", $ripId);
$stmt->execute();
$allegati = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Allegati Riparazione #<?php echo htmlspecialchars($ripId); ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-dHw6qILqo+K8fP5h6vv0e8KraXpWcS0Xqp69g0JytjKIPdX6v+ziM2qgFt0Oht6ujxYkdOi0Zr4Pps11YTL+Sw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');
    * { box-sizing: border-box; margin:0; padding:0 }
    body {
      font-family: 'Inter', sans-serif;
      background: #f0f2f5;
      color: #333;
      display: flex; justify-content: center; padding: 2rem 1rem;
    }
    .container {
      width: 700px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      overflow: hidden;
      animation: pop 0.4s ease;
    }
    header {
      background: linear-gradient(135deg,#27ae60,#1e8449);
      color: #fff;
      padding: 1.5rem;
      text-align: center;
      font-size: 1.5rem;
      font-weight: 600;
    }
    .list {
      list-style: none;
    }
    .item {
      display: flex;
      align-items: center;
      padding: 1rem 1.5rem;
      border-bottom: 1px solid #eee;
      transition: background 0.2s;
    }
    .item:last-child { border-bottom: none; }
    .item:hover { background: #f9f9f9; }
    .icon {
      font-size: 1.5rem;
      color: #27ae60;
      margin-right: 1rem;
      flex-shrink: 0;
    }
    .details {
      flex: 1;
    }
    .details .name {
      font-weight: 500;
      margin-bottom: 0.25rem;
    }
    .details .date {
      font-size: 0.85rem;
      color: #666;
    }
    .actions {
      margin-left: 1rem;
    }
    .btn {
      background: linear-gradient(135deg,#27ae60,#1e8449);
      color: #fff;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      text-decoration: none;
      font-size: 0.9rem;
      transition: transform 0.2s, box-shadow 0.2s;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 14px rgba(0,0,0,0.15);
    }
    @keyframes pop {
      from {opacity:0; transform: scale(0.95)} to {opacity:1; transform: scale(1)}
    }
    .no-files {
      padding: 2rem;
      text-align: center;
      color: #777;
    }
  </style>
</head>
<body>

  <div class="container">
    <header><i class="fas fa-paperclip"></i> Allegati Riparazione #<?php echo htmlspecialchars($ripId); ?></header>

    <?php if ($allegati->num_rows === 0): ?>
      <div class="no-files">Nessun allegato disponibile.</div>
    <?php else: ?>
      <ul class="list">
        <?php while ($a = $allegati->fetch_assoc()): ?>
          <li class="item">
            <i class="fas fa-file-alt icon"></i>
            <div class="details">
              <div class="name"><?php echo htmlspecialchars($a['nome_file']); ?></div>
              <div class="date"><i class="fas fa-clock"></i> <?php echo date("d/m/Y H:i", strtotime($a['data_upload'])); ?></div>
            </div>
            <div class="actions">
              <a href="<?php echo htmlspecialchars($a['percorso_file']); ?>" class="btn" download><i class="fas fa-download"></i> Scarica</a>
            </div>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php endif; ?>
  </div>

</body>
</html>
