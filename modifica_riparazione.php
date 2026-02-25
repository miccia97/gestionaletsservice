<?php
$conn = new mysqli("localhost", "root", "", "gestionale_tsservice");
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    die("ID riparazione mancante.");
}
$id = (int)$_GET['id'];

// Salvataggio nuovo stato se inviato il form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuovo_stato'])) {
    $nuovo_stato = $conn->real_escape_string($_POST['nuovo_stato']);
    $commento = $conn->real_escape_string($_POST['commento']);
    $data_ora = date('Y-m-d H:i:s'); // ora corrente

    $sql_insert = "INSERT INTO stati (riparazione_id, stato, data_ora, commento) VALUES ($id, '$nuovo_stato', '$data_ora', '$commento')";
    if ($conn->query($sql_insert) === TRUE) {
        echo "<p style='color:green;'>Nuovo stato aggiunto con successo.</p>";
    } else {
        echo "<p style='color:red;'>Errore durante l'inserimento: " . $conn->error . "</p>";
    }
}

// Recupero dati riparazione + cliente
$sql = "
    SELECT r.*, c.nome, c.cognome
    FROM riparazioni r
    LEFT JOIN clienti_nuovo c ON r.cliente_id = c.id
    WHERE r.id = $id
";

$result = $conn->query($sql);
if ($result->num_rows === 0) {
    die("Riparazione non trovata.");
}
$riparazione = $result->fetch_assoc();

// Recupero storico stati
$sql_stati = "
    SELECT stato, data_ora, commento 
    FROM stati 
    WHERE riparazione_id = $id 
    ORDER BY data_ora ASC
";
$result_stati = $conn->query($sql_stati);
// Supponendo che $id sia l'id della riparazione già definito
$sql_ricambi = "
    SELECT codice, descrizione, quantita, prezzo
    FROM ricambi
    WHERE riparazione_id = $id
";

$result
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Dettagli Riparazione - Modern</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');

  * {
    box-sizing: border-box;
  }
  body {
    font-family: 'Inter', sans-serif;
    background: #f0f2f5;
    margin: 0;
    padding: 30px;
    color: #222;
  }
  

  h1 {
    font-weight: 600;
    margin-bottom: 30px;
    text-align: center;
    color: #222;
  }

  .tabs {
    max-width: 900px;
    margin: 0 auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 12px 30px rgb(0 0 0 / 0.1);
    overflow: hidden;
  }

  .tab-menu {
    display: flex;
    background: #fafafa;
    border-bottom: 2px solid #e1e4eb;
  }

  .tab-menu button {
    flex: 1;
    padding: 18px 0;
    background: none;
    border: none;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    color: #666;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
  }

  .tab-menu button svg {
    width: 18px;
    height: 18px;
    fill: #666;
    transition: fill 0.3s ease;
  }

  .tab-menu button.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
  }
  .tab-menu button.active svg {
    fill: #3b82f6;
  }
  .tab-menu button:hover:not(.active) {
    color: #3b82f6;
  }
  .tab-menu button:hover:not(.active) svg {
    fill: #3b82f6;
  }

  .tab-content {
    padding: 30px 40px;
    display: none;
    animation: fadeIn 0.3s ease forwards;
  }
  .tab-content.active {
    display: block;
  }

  @keyframes fadeIn {
    from {opacity: 0; transform: translateY(10px);}
    to {opacity: 1; transform: translateY(0);}
  }

  form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #444;
  }
  form input[type="text"],
  form input[type="number"],
  form select,
  form textarea {
    width: 100%;
    padding: 12px 14px;
    font-size: 1rem;
    border: 1.8px solid #d1d5db;
    border-radius: 8px;
    outline-offset: 2px;
    outline-color: transparent;
    transition: outline-color 0.3s ease;
    resize: vertical;
    font-family: 'Inter', sans-serif;
  }
  form input[type="text"]:focus,
  form input[type="number"]:focus,
  form select:focus,
  form textarea:focus {
    outline-color: #3b82f6;
    border-color: #3b82f6;
  }

  form input[type="submit"] {
    margin-top: 28px;
    background: #3b82f6;
    color: white;
    border: none;
    padding: 14px 32px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    box-shadow: 0 6px 12px rgb(59 130 246 / 0.35);
    transition: background-color 0.3s ease;
  }
  form input[type="submit"]:hover {
    background: #2563eb;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 0.95rem;
  }
  table th, table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1.2px solid #e5e7eb;
    vertical-align: top;
  }
  table th {
    background: #f9fafb;
    font-weight: 700;
    color: #555;
  }

  .img-thumb {
    width: 120px;
    height: 80px;
    object-fit: cover;
    margin: 8px 12px 8px 0;
    border-radius: 10px;
    border: 1.5px solid #ddd;
    box-shadow: 0 1px 4px rgb(0 0 0 / 0.08);
    transition: box-shadow 0.3s ease;
  }
  .img-thumb:hover {
    box-shadow: 0 6px 20px rgb(59 130 246 / 0.5);
    border-color: #3b82f6;
  }

  .images-container {
    display: flex;
    flex-wrap: wrap;
  }

</style>
</head>
<body>

<h1>Dettagli Riparazione</h1>

<div class="tabs" role="tabpanel">
  <nav class="tab-menu" role="tablist" aria-label="Sezioni Dettaglio Riparazione">
    <button class="active" role="tab" aria-selected="true" aria-controls="dati" id="tab-dati" onclick="openTab(event, 'dati')">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 13h2v-2H3v2zm4 0h14v-2H7v2zm0-7h14V4H7v2zm0 14h14v-2H7v2zm-4 0h2v-2H3v2z"/></svg> Dati Riparazione
    </button>
    <button role="tab" aria-selected="false" aria-controls="ricambi" id="tab-ricambi" onclick="openTab(event, 'ricambi')">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 7H3v10h18V7zm-2 8H5v-6h14v6zm-3-9v1H8V6h8z"/></svg> Ricambi
    </button>
    <button role="tab" aria-selected="false" aria-controls="stati" id="tab-stati" onclick="openTab(event, 'stati')">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg> Stati
    </button>
    <button role="tab" aria-selected="false" aria-controls="immagini" id="tab-immagini" onclick="openTab(event, 'immagini')">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 19V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2zm-11-3l2.5 3 3.5-4.5 4.5 6H5l5-6z"/></svg> Immagini
    </button>
  </nav>

  <section id="dati" class="tab-content active" role="tabpanel" aria-labelledby="tab-dati">
  <form style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; max-width: 900px; margin: auto; font-family: Arial, sans-serif; font-size: 13px;">
  
    <label for="cliente" style="font-weight: 600; margin-bottom: 2px;">Cliente</label>
    <input type="text" id="cliente" name="cliente" value="<?php echo htmlspecialchars($riparazione['nome'] . ' ' . $riparazione['cognome']); ?>" style="padding: 4px 6px; font-size: 13px;" />
    
    <label for="telefono" style="font-weight: 600; margin-bottom: 2px;">Telefono</label>
    <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($riparazione['telefono']); ?>" style="padding: 4px 6px; font-size: 13px;" />
    
    <label for="modello" style="font-weight: 600; margin-bottom: 2px;">Modello</label>
    <input type="text" id="modello" name="modello" value="<?php echo htmlspecialchars($riparazione['modello']); ?>" style="padding: 4px 6px; font-size: 13px;" />
    
    <label for="imei" style="font-weight: 600; margin-bottom: 2px;">IMEI</label>
    <input type="text" id="imei" name="imei" value="<?php echo htmlspecialchars($riparazione['imei']); ?>" style="padding: 4px 6px; font-size: 13px;" />
    
    <label for="codice_sblocco" style="font-weight: 600; margin-bottom: 2px;">Codice Sblocco</label>
    <input type="text" id="codice_sblocco" name="codice_sblocco" value="<?php echo htmlspecialchars($riparazione['codice_sblocco']); ?>" style="padding: 4px 6px; font-size: 13px;" />
    
    <label for="account" style="font-weight: 600; margin-bottom: 2px;">Account</label>
    <input type="text" id="account" name="account" value="<?php echo htmlspecialchars($riparazione['account']); ?>" style="padding: 4px 6px; font-size: 13px;" />
    
    <label for="costo_preventivato" style="font-weight: 600; margin-bottom: 2px;">Costo Preventivato</label>
    <input type="number" step="0.01" id="costo_preventivato" name="costo_preventivato" value="<?php echo htmlspecialchars($riparazione['costo_preventivato']); ?>" style="padding: 4px 6px; font-size: 13px;" />
    
    <label for="costo_effettivo" style="font-weight: 600; margin-bottom: 2px;">Costo Effettivo</label>
    <input type="number" step="0.01" id="costo_effettivo" name="costo_effettivo" value="<?php echo htmlspecialchars($riparazione['costo_effettivo']); ?>" style="padding: 4px 6px; font-size: 13px;" />
    
    <label for="hardware_ritirato" style="font-weight: 600; margin-bottom: 2px;">Hardware Ritirato</label>
    <input type="text" id="hardware_ritirato" name="hardware_ritirato" value="<?php echo htmlspecialchars($riparazione['hardware_ritirato']); ?>" style="padding: 4px 6px; font-size: 13px;" />
    
    <label for="dispositivo_sostitutivo" style="font-weight: 600; margin-bottom: 2px;">Dispositivo Sostitutivo</label>
    <input type="text" id="dispositivo_sostitutivo" name="dispositivo_sostitutivo" value="<?php echo htmlspecialchars($riparazione['dispositivo_sostitutivo']); ?>" style="padding: 4px 6px; font-size: 13px;" />
    
    <label for="diagnosi" style="grid-column: span 2; font-weight: 600; margin-bottom: 2px;">Diagnosi</label>
    <textarea id="diagnosi" name="diagnosi" rows="3" style="width: 202%; padding: 4px 6px; font-size: 13px;"><?php echo htmlspecialchars($riparazione['diagnosi']); ?></textarea>
    <input type="submit" value="Salva Dati" style="grid-column: span 2; padding: 20px 50px; font-weight: 600; cursor: pointer; font-size: 14px; margin-top: 6px;" />
  </form>
</section>

  </section>

  <section id="ricambi" class="tab-content" role="tabpanel" aria-labelledby="tab-ricambi">
   <h2>Ricambi Utilizzati</h2>
<?php if ($result_ricambi && $result_ricambi->num_rows > 0): ?>
    <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th>Codice</th>
                <th>Descrizione</th>
                <th>Quantità</th>
                <th>Prezzo (€)</th>
                <th>Totale (€)</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result_ricambi->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['codice']) ?></td>
                    <td><?= htmlspecialchars($row['descrizione']) ?></td>
                    <td><?= (int)$row['quantita'] ?></td>
                    <td><?= number_format($row['prezzo'], 2, ',', '.') ?></td>
                    <td><?= number_format($row['quantita'] * $row['prezzo'], 2, ',', '.') ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Nessun ricambio associato a questa riparazione.</p>
<?php endif; ?>
  <section id="stati" class="tab-content" role="tabpanel" aria-labelledby="tab-stati">
    <h2>Stati Riparazione</h2>
    <table>
      <thead>
        <tr>
          <th>Data</th>
          <th>Stato</th>
          <th>Note</th>
        </tr>
      </thead>
      <tbody>
        <!-- Stato inserito dinamicamente -->
      </tbody>
    </table>
  </section>

  <section id="immagini" class="tab-content" role="tabpanel" aria-labelledby="tab-immagini">
    <h2>Immagini Allegati</h2>
    <div class="images-container">
      <!-- Immagini riparazione caricate dinamicamente -->
    </div>
  </section>
</div>

<script>
  function openTab(event, tabId) {
    const tabs = document.querySelectorAll('.tab-content');
    const buttons = document.querySelectorAll('.tab-menu button');

    tabs.forEach(tab => {
      tab.classList.remove('active');
    });
    buttons.forEach(btn => {
      btn.classList.remove('active');
      btn.setAttribute('aria-selected', 'false');
    });

    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
    event.currentTarget.setAttribute('aria-selected', 'true');
  }
</script>

</body>
</html>
