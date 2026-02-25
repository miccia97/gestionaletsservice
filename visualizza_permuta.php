<?php
$mysqli = new mysqli('localhost', 'root', '', 'gestionale_tsservice');

$id = intval($_GET['progressivo'] ?? 0);
if ($id <= 0) {
    die('ID non valido');
}

$query = $mysqli->prepare("SELECT * FROM permute_nuovo WHERE progressivo = ?");
$query->bind_param('i', $id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die('Nessuna permuta trovata.');
}

$permuta = $result->fetch_assoc();
$query->close();
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Dettaglio Permuta #<?php echo htmlspecialchars($permuta['progressivo']); ?></title>
<style>
  body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    background: #f0f2f5;
    color: #2c3e50;
  }

  header {
    background: linear-gradient(135deg, #4CAF50, #388E3C);
    color: #fff;
    text-align: center;
    padding: 1.75rem 1rem;
    font-size: 2rem;
    font-weight: 700;
    box-shadow: 0 6px 12px rgb(0 0 0 / 0.12);
    letter-spacing: 0.05em;
    user-select: none;
  }

  .container {
    max-width: 900px;
    margin: 2.5rem auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 12px 24px rgb(0 0 0 / 0.08);
    overflow: hidden;
    padding: 2rem 2.5rem;
    animation: fadeInUp 0.5s ease forwards;
  }

  table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 0.7rem;
  }

  th {
    text-align: left;
    padding: 1rem 1.25rem 1rem 0;
    font-weight: 600;
    color: #27ae60;
    font-size: 1.05rem;
    text-transform: capitalize;
    width: 30%;
  }

  td {
    background: #e9f5ee;
    padding: 1rem 1.25rem;
    border-radius: 8px;
    vertical-align: top;
    font-size: 1rem;
    line-height: 1.4;
    box-shadow: inset 2px 2px 6px rgb(0 0 0 / 0.05);
    word-break: break-word;
  }

  tr:hover td {
    background: #d4ecd9;
    box-shadow: inset 4px 4px 12px rgb(0 0 0 / 0.08);
    transition: background 0.3s ease, box-shadow 0.3s ease;
  }

  .buttons {
    margin-top: 2.5rem;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
  }

  .btn {
    background: linear-gradient(135deg, #43a047, #2e7d32);
    color: #fff;
    padding: 0.75rem 1.8rem;
    border-radius: 30px;
    font-weight: 600;
    font-size: 1rem;
    text-decoration: none;
    box-shadow: 0 8px 15px rgb(67 160 71 / 0.3);
    cursor: pointer;
    transition:
      background 0.3s ease,
      box-shadow 0.3s ease,
      transform 0.15s ease;
    user-select: none;
  }

  .btn:hover {
    background: linear-gradient(135deg, #66bb6a, #388e3c);
    box-shadow: 0 12px 20px rgb(102 187 106 / 0.45);
    transform: translateY(-3px);
  }

  .btn:active {
    transform: translateY(-1px);
    box-shadow: 0 6px 10px rgb(67 160 71 / 0.25);
  }

  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    } to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Responsive */
  @media (max-width: 600px) {
    .container {
      padding: 1.5rem 1.5rem;
      margin: 1.5rem;
    }

    table, th, td {
      display: block;
      width: 100%;
    }

    th {
      background: #27ae60;
      color: white;
      padding: 0.75rem 1rem;
      border-radius: 8px 8px 0 0;
    }

    td {
      background: #e9f5ee;
      margin-bottom: 1.5rem;
      border-radius: 0 0 8px 8px;
      box-shadow: none;
    }

    tr {
      margin-bottom: 1rem;
      border-bottom: none;
      display: block;
    }

    .buttons {
      flex-direction: column;
      gap: 0.8rem;
      align-items: stretch;
    }

    .btn {
      width: 100%;
      text-align: center;
    }
  }
</style>
</head>
<body>
<header>
  Dettaglio Permuta #<?php echo htmlspecialchars($permuta['progressivo']); ?>
</header>

<div class="container">
  <table>
    <tbody>
      <tr>
        <th>Data</th>
        <td><?php echo htmlspecialchars($permuta['data']); ?></td>
      </tr>
      <tr>
        <th>Cliente</th>
        <td><?php echo htmlspecialchars($permuta['cliente']); ?></td>
      </tr>
      <tr>
        <th>Modello Nuovo</th>
        <td><?php echo htmlspecialchars($permuta['modello_nuovo']); ?></td>
      </tr>
      <tr>
        <th>Modello Usato</th>
        <td><?php echo htmlspecialchars($permuta['modello_usato']); ?></td>
      </tr>
      <tr>
        <th>Prezzo Nuovo</th>
        <td><?php echo number_format($permuta['prezzo_nuovo'], 2, ',', '.'); ?> €</td>
      </tr>
      <tr>
        <th>Prezzo Permuta</th>
        <td><?php echo number_format($permuta['prezzo_permuta'], 2, ',', '.'); ?> €</td>
      </tr>
      <tr>
        <th>Status</th>
        <td><?php echo htmlspecialchars($permuta['status']); ?></td>
      </tr>
      <tr>
        <th>Note</th>
        <td><?php echo nl2br(htmlspecialchars($permuta['note'])); ?></td>
      </tr>
    </tbody>
  </table>

  <div class="buttons">
    <a href="visualizza_permute.php" class="btn">&larr; Torna alle permute</a>
  </div>
</div>
</body>
</html>
