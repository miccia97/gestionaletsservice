<?php
session_start();
include 'db.php';

// Query vendite base (JOIN corretta sulla tabella clienti_nuovo)
$sql = "SELECT v.id, v.id_cliente, v.data_vendita, v.totale,
               c.nome AS cliente_nome, c.cognome AS cliente_cognome
        FROM vendite v
        LEFT JOIN clienti_nuovo c ON v.id_cliente = c.id
        ORDER BY v.data_vendita DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Vendite - Gestionale</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/header-styles.css?v=1">
<style>
    :root {
      --accent-color: #2563eb;
      --bg-color: #f9fafb;
      --text-color: #111827;
      --muted-text: #6b7280;
      --border-color: #e5e7eb;
      --radius: 12px;
      --shadow: rgba(0, 0, 0, 0.1) 0 4px 12px;
  }

  body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: var(--bg-color);
      color: var(--text-color);
      padding: 2rem;
  }

  h1 {
      text-align: center;
      margin-bottom: 30px;
      font-weight: 700;
      color: #27ae60;
      text-shadow: 0 1px 3px rgba(39, 174, 96, 0.5);
  }

  .table-wrapper {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      max-width: 1100px;
      margin: 0 auto;
  }

  .table-head,
  .table-row {
      display: grid;
      grid-template-columns: 40px 1fr 1.5fr 120px 140px;
      align-items: center;
      padding: 0.75rem 1rem;
      border-radius: var(--radius);
      column-gap: 15px;
  }

  .table-head {
      font-weight: 600;
      color: white;
      background: linear-gradient(135deg, #27ae60, #1e8449);
  }

  .table-row {
      background: #fff;
      box-shadow: var(--shadow);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      cursor: default;
  }

  .table-row:hover {
      box-shadow: rgba(0, 0, 0, 0.15) 0 6px 16px;
  }

  .table-row:nth-child(odd) {
      background: #f0fdf4;
  }

  .text-right {
      text-align: right;
      font-variant-numeric: tabular-nums;
  }

  .btn-actions {
      background: linear-gradient(135deg, #27ae60, #1e8449);
      color: white;
      border: none;
      padding: 10px 0;
      border-radius: var(--radius);
      font-weight: 700;
      font-size: 0.95rem;
      cursor: pointer;
      box-shadow: 0 8px 18px rgba(39, 174, 96, 0.6);
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
      width: 130px;
      user-select: none;
      text-align: center;
      line-height: 1.2;
  }

  .btn-actions:hover {
      background: linear-gradient(135deg, #229954, #196f3d);
      box-shadow: 0 12px 26px rgba(34, 153, 84, 0.75);
  }

  .btn-actions svg {
      display: none;
  }

  .empty-row {
      text-align: center;
      padding: 30px 0;
      font-style: italic;
      color: var(--muted-text);
      grid-column: 1 / -1;
  }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<h1>Vendite Effettuate</h1>

<div class="table-wrapper">
  <div class="table-head">
    <div>ID</div>
    <div>Cliente</div>
    <div>Data</div>
    <div class="text-right">Totale (€)</div>
    <div>Azioni</div>
  </div>

  <?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <div class="table-row">
        <div><?= htmlspecialchars($row['id']) ?></div>
        <div>
          <?= htmlspecialchars($row['id_cliente']) ?> -
          <?= htmlspecialchars(trim($row['cliente_nome'] . ' ' . $row['cliente_cognome'])) ?: '<em>Non definito</em>' ?>
        </div>
        <div><?= date('d/m/Y H:i', strtotime($row['data_vendita'])) ?></div>
        <div class="text-right"><?= number_format($row['totale'], 2, ',', '.') ?></div>
        <div>
          <form action="dettagli_vendita.php" method="GET" style="margin:0;">
            <input type="hidden" name="id_vendita" value="<?= $row['id'] ?>">
            <button type="submit" class="btn-actions">Dettagli</button>
          </form>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="empty-row">Nessuna vendita trovata.</div>
  <?php endif; ?>
</div>

</body>
</html>
