<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Carico Merce</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f2f2f2;
      margin: 0; padding: 0;
    }
    header {
      background: #28a745;
      color: white;
      padding: 15px 30px;
      font-size: 1.5rem;
      text-align: center;
      border-bottom: 3px solid #1e7e34;
    }
    main {
      max-width: 900px;
      margin: 30px auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 12px rgba(0,0,0,0.1);
    }
    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
    }
    input, textarea {
      width: 100%;
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #ccc;
      margin-top: 5px;
      box-sizing: border-box;
      font-size: 1rem;
    }
    textarea {
      resize: vertical;
      min-height: 60px;
    }
    button {
      background: #28a745;
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 6px;
      margin-top: 20px;
      cursor: pointer;
      font-size: 1rem;
      transition: background 0.3s ease;
    }
    button:hover {
      background: #218838;
    }
    .prodotto {
      margin-top: 30px;
      padding: 20px;
      border: 1px solid #ccc;
      border-radius: 8px;
      background: #f9f9f9;
    }
    .flex-row {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }
    .flex-item {
      flex: 1 1 150px;
      min-width: 150px;
    }
  </style>
</head>
<body>
  <header>Carico Merce</header>
  <main>
    <form action="salva_carico.php" method="post">
      <label for="numero_documento">Numero Documento / Fattura:</label>
      <input type="text" id="numero_documento" name="numero_documento" required />

      <label for="data_carico">Data Carico:</label>
      <input type="date" id="data_carico" name="data_carico" required value="<?php echo date('Y-m-d'); ?>" />

      <label for="fornitore">Fornitore:</label>
      <input type="text" id="fornitore" name="fornitore" required />

      <label for="note">Note (opzionale):</label>
      <textarea id="note" name="note" placeholder="Inserisci eventuali note..."></textarea>

      <hr />

      <h3>Prodotti</h3>
      <div id="prodotti-container">
        <div class="prodotto">
          <div class="flex-row">
            <div class="flex-item">
              <label>Nome Prodotto:</label>
              <input type="text" name="prodotti[0][nome]" required />
            </div>
            <div class="flex-item">
              <label>Categoria:</label>
              <input type="text" name="prodotti[0][categoria]" />
            </div>
            <div class="flex-item">
              <label>Quantità:</label>
              <input type="number" name="prodotti[0][quantita]" min="1" required />
            </div>
            <div class="flex-item">
              <label>Prezzo Acquisto:</label>
              <input type="number" step="0.01" name="prodotti[0][prezzo_acquisto]" required />
            </div>
            <div class="flex-item">
              <label>Prezzo Vendita:</label>
              <input type="number" step="0.01" name="prodotti[0][prezzo_vendita]" required />
            </div>
            <div class="flex-item">
              <label>Barcode / IMEI:</label>
              <input type="text" name="prodotti[0][barcode]" />
            </div>
          </div>
        </div>
      </div>

      <button type="button" onclick="aggiungiProdotto()">+ Aggiungi Prodotto</button>
      <button type="submit">Salva Carico</button>
    </form>
  </main>

  <script>
    let index = 1;
    function aggiungiProdotto() {
      const container = document.getElementById('prodotti-container');
      const div = document.createElement('div');
      div.classList.add('prodotto');
      div.innerHTML = `
        <div class="flex-row">
          <div class="flex-item">
            <label>Nome Prodotto:</label>
            <input type="text" name="prodotti[${index}][nome]" required />
          </div>
          <div class="flex-item">
            <label>Categoria:</label>
            <input type="text" name="prodotti[${index}][categoria]" />
          </div>
          <div class="flex-item">
            <label>Quantità:</label>
            <input type="number" name="prodotti[${index}][quantita]" min="1" required />
          </div>
          <div class="flex-item">
            <label>Prezzo Acquisto:</label>
            <input type="number" step="0.01" name="prodotti[${index}][prezzo_acquisto]" required />
          </div>
          <div class="flex-item">
            <label>Prezzo Vendita:</label>
            <input type="number" step="0.01" name="prodotti[${index}][prezzo_vendita]" required />
          </div>
          <div class="flex-item">
            <label>Barcode / IMEI:</label>
            <input type="text" name="prodotti[${index}][barcode]" />
          </div>
        </div>
      `;
      container.appendChild(div);
      index++;
    }
  </script>
</body>
</html>
