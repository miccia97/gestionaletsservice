<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Gestionale Negozio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Barra di navigazione -->
            <nav class="col-md-2 d-none d-md-block bg-light sidebar">
                <div class="sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="clienti.php">
                                Gestisci Clienti
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="aggiungi_vendita.php">
                                Aggiungi Vendita
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="magazzino.php">
                                Gestione Magazzino
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenuto principale -->
            <main role="main" class="col-md-9 ms-sm-auto col-lg-10 px-4">
                <h1 class="my-4">Benvenuto nel Gestionale del Negozio</h1>
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Clienti</h5>
                                <p class="card-text">Numero totale di clienti registrati.</p>
                                <a href="clienti.php" class="btn btn-primary">Visualizza</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Vendite</h5>
                                <p class="card-text">Numero di vendite effettuate.</p>
                                <a href="vendite.php" class="btn btn-primary">Visualizza</a>
                            </div>
                        </div>
                    </div>
                    <!-- Puoi aggiungere altri blocchi per altre statistiche -->
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
