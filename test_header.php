<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Test Header - Gestionale</title>
        <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }
        .content-area {
            padding: 100px 20px 20px 20px;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .test-section h2 {
            color: #28a745;
            margin-bottom: 10px;
        }
        .test-section p {
            color: #666;
            line-height: 1.6;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Include header -->
    <?php include 'header.php'; ?>
    
    <div class="content-area">
        <div class="test-section">
            <h2>✓ Header Caricato Correttamente</h2>
            <p>Se vedi il menu in alto, significa che l'header.php è stato caricato correttamente.</p>
        </div>
        
        <div class="test-section">
            <h2>Caratteristiche Implementate</h2>
            <div class="info-box">
                <strong>✓ Dropdown Menu</strong><br>
                Clicca sui menu dropdown per vedere i sottomenu posizionati correttamente
            </div>
            <div class="info-box">
                <strong>✓ Submenu Positioning</strong><br>
                I sottomenu appaiono a destra degli elementi del dropdown
            </div>
            <div class="info-box">
                <strong>✓ Mobile Responsive</strong><br>
                Ridimensiona la finestra sotto i 992px per vedere il menu hamburger
            </div>
            <div class="info-box">
                <strong>✓ Hover Effects</strong><br>
                Passa il mouse sulle voci di menu per vedere l'effetto hover animato
            </div>
            <div class="info-box">
                <strong>✓ Keyboard Support</strong><br>
                Premi Escape per chiudere i menu
            </div>
            <div class="info-box">
                <strong>✓ Click Outside Handling</strong><br>
                Clicca fuori dal menu per chiuderlo automaticamente
            </div>
        </div>
        
        <div class="test-section">
            <h2>Cosa Testare</h2>
            <ol style="padding-left: 20px; color: #666;">
                <li style="margin: 10px 0;">
                    <strong>Desktop (>992px):</strong> Clicca sui dropdown menu, verifica che i sottomenu appaiano a destra
                </li>
                <li style="margin: 10px 0;">
                    <strong>Tablet/Mobile (<992px):</strong> Verifica che il pulsante hamburger compaia e funzioni
                </li>
                <li style="margin: 10px 0;">
                    <strong>Hover Effects:</strong> Verifica che i menu items cambino colore al passaggio del mouse
                </li>
                <li style="margin: 10px 0;">
                    <strong>Escape Key:</strong> Premi Escape per chiudere i menu aperti
                </li>
                <li style="margin: 10px 0;">
                    <strong>Click Outside:</strong> Clicca fuori dal menu per vederlo chiudersi
                </li>
                <li style="margin: 10px 0;">
                    <strong>Mobile Menu:</strong> Sul mobile, clicca sui link per vedere il menu chiudersi
                </li>
            </ol>
        </div>
        
        <div class="test-section">
            <h2>Note Implementative</h2>
            <p>
                <strong>CSS Media Queries:</strong><br>
                • @992px: Mostra il pulsante hamburger, nasconde il nav principale<br>
                • @768px: Aggiustamenti ulteriori per schermi molto piccoli
            </p>
            <p style="margin-top: 10px;">
                <strong>JavaScript:</strong><br>
                • Toggle del hamburger button con animazione<br>
                • Chiusura automatica del menu su click di link<br>
                • Click-outside per chiudere menu<br>
                • Supporto completo dei dropdown e submenu
            </p>
        </div>
    </div>
</body>
</html>
