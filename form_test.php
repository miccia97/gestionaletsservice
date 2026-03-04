<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Test Form Invia Cliente</title>
</head>
<body>

<form action="ricevi_test.php" method="POST" id="form-vendita">
  <label for="clienteInput">Nome Cliente:</label><br>
  <input type="text" id="clienteInput" name="nome_cliente" placeholder="Inserisci nome cliente" required><br><br>

  <label for="idCliente">ID Cliente:</label><br>
  <input type="number" id="idCliente" name="id_cliente" placeholder="Inserisci ID cliente" required><br><br>

  <button type="submit">Invia</button>
</form>

</body>
</html>
