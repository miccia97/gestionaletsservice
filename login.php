<?php
session_start(); // Avvia la sessione PHP all'inizio di ogni pagina che la usa

// Controlla se c'è un messaggio di errore di login nella sessione
$login_error_message = '';
if (isset($_SESSION['login_error'])) {
    $login_error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']); // Rimuovi il messaggio dopo averlo mostrato
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Accedi al Gestionale</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Stili Generali del Body */
        body {
            font-family: 'Inter', sans-serif; /* Utilizzo del font Inter */
            margin: 0;
            padding: 0;
            /* Sfondo dinamico con gradiente radiale animato */
            background: radial-gradient(circle at top left, #dcfce7 0%, #f0fdf4 50%, #dcfce7 100%);
            background-size: 200% 200%; /* Ingrandisce lo sfondo per l'animazione */
            animation: backgroundPan 20s linear infinite alternate; /* Animazione lenta dello sfondo */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Altezza minima per centrare verticalmente */
            color: #333;
            overflow: hidden; /* Nasconde overflow causato da animazioni iniziali */
            position: relative; /* Per elementi di sfondo aggiuntivi */
        }

        /* Animazione dello sfondo */
        @keyframes backgroundPan {
            0% { background-position: 0% 0%; }
            100% { background-position: 100% 100%; }
        }

        /* Contenitore Principale del Modulo di Login */
        .login-container {
            background-color: rgba(255, 255, 255, 0.95); /* Sfondo bianco quasi opaco, leggermente traslucido */
            padding: 50px; /* Più padding */
            border-radius: 25px; /* Angoli ancora più arrotondati */
            box-shadow: 0 20px 50px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.05); /* Ombra più profonda e un bordo sottile */
            width: 100%;
            max-width: 450px; /* Larghezza leggermente aumentata */
            text-align: center;
            box-sizing: border-box;
            animation: slideInUp 0.9s ease-out forwards; /* Animazione di apparizione più fluida */
            opacity: 0; /* Inizialmente invisibile per l'animazione */
            position: relative;
            z-index: 1; /* Assicura che sia sopra gli elementi di sfondo */
            backdrop-filter: blur(5px); /* Effetto vetro smerigliato (se supportato) */
            -webkit-backdrop-filter: blur(5px); /* Per compatibilità Safari */
        }

        /* Animazione di apparizione (dal basso verso l'alto) */
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Stile per il Logo */
        .login-logo {
            max-width: 150px; /* Dimensione leggermente maggiore del logo */
            height: auto;
            display: block;
            margin: 0 auto 35px auto; /* Centra orizzontalmente e aggiunge più margine sotto */
            border-radius: 50%; /* Mantiene il logo circolare */
            box-shadow: 0 8px 20px rgba(0,0,0,0.15); /* Ombra più pronunciata per il logo */
            transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94); /* Curva di transizione più fluida */
        }
        .login-logo:hover {
            transform: scale(1.08) rotate(2deg); /* Piccolo effetto di ingrandimento e rotazione all'hover */
        }

        /* Titolo della Pagina di Login */
        h2 {
            color: #10b981; /* Un verde più brillante e moderno */
            margin-bottom: 40px; /* Più spazio sotto il titolo */
            font-size: 36px; /* Dimensione del font aumentata */
            font-weight: 800; /* Font più spesso */
            letter-spacing: -0.8px; /* Leggera spaziatura tra le lettere */
            text-shadow: 1px 1px 2px rgba(0,0,0,0.05); /* Sottile ombra per il testo */
        }

        /* Stile per gli Input di Testo e Password */
        .input-group {
            margin-bottom: 30px; /* Più spazio tra i gruppi di input */
            text-align: left;
            position: relative;
        }

        .input-group label {
            position: absolute;
            top: 18px; /* Posizione iniziale dell'etichetta */
            left: 20px; /* Più spazio a sinistra */
            color: #888;
            font-size: 17px; /* Dimensione leggermente aumentata */
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94); /* Animazione più fluida */
            background-color: #fff; /* Sfondo per evitare che il testo si sovrapponga */
            padding: 0 5px; /* Padding per lo sfondo dell'etichetta */
            margin-left: -5px; /* Sposta il padding a sinistra */
            z-index: 2; /* Assicura che l'etichetta sia sopra il bordo dell'input */
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 18px 20px; /* Più padding per gli input */
            border: 2px solid #e0e0e0; /* Bordo leggermente più spesso */
            border-radius: 12px; /* Angoli ancora più arrotondati per gli input */
            box-sizing: border-box;
            font-size: 18px; /* Dimensione del font aumentata */
            color: #333;
            background-color: #fcfcfc; /* Sfondo leggermente grigio per gli input */
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            padding-top: 30px; /* Spazio extra per l'etichetta flottante */
            position: relative; /* Per z-index */
            z-index: 1;
        }

        /* Effetto per l'etichetta quando l'input è focus o ha contenuto */
        input[type="text"]:focus + label,
        input[type="password"]:focus + label,
        input[type="text"]:not(:placeholder-shown) + label,
        input[type="password"]:not(:placeholder-shown) + label {
            top: -10px; /* Sposta l'etichetta fuori dall'input */
            font-size: 13px; /* Rimpicciolisci il font */
            color: #10b981; /* Cambia colore */
            background-color: #fff; /* Sfondo dell'etichetta */
            padding: 0 8px; /* Più padding per lo sfondo */
            margin-left: -8px; /* Sposta il padding a sinistra */
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #10b981; /* Bordo verde al focus */
            box-shadow: 0 0 0 5px rgba(16, 185, 129, 0.25); /* Bagliore verde più pronunciato e diffuso */
            background-color: #fff; /* Sfondo bianco al focus */
        }

        /* Stile per il Pulsante di Accesso */
        button[type="submit"] {
            background: linear-gradient(45deg, #10b981, #059669); /* Sfumatura verde per il pulsante */
            color: white;
            padding: 20px 30px; /* Più padding per un pulsante più grande */
            border: none;
            border-radius: 12px; /* Angoli più arrotondati */
            cursor: pointer;
            font-size: 22px; /* Dimensione del font aumentata */
            font-weight: 700;
            width: 100%;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94); /* Transizione più fluida */
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4); /* Ombra più forte */
            letter-spacing: 0.8px;
            text-transform: uppercase; /* Testo in maiuscolo */
        }

        button[type="submit"]:hover {
            background: linear-gradient(45deg, #059669, #10b981); /* Inverte la sfumatura all'hover */
            transform: translateY(-5px) scale(1.02); /* Leggero sollevamento e ingrandimento */
            box-shadow: 0 12px 25px rgba(16, 185, 129, 0.5);
        }

        button[type="submit"]:active {
            transform: translateY(0);
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
            background: linear-gradient(45deg, #10b981, #059669); /* Ritorna alla sfumatura originale */
        }

        /* Stile per i Messaggi di Errore */
        .error-message {
            color: #ef4444; /* Rosso più moderno */
            background-color: #fee2e2; /* Sfondo rosso chiaro */
            border: 1px solid #fca5a5;
            padding: 15px; /* Più padding */
            border-radius: 10px; /* Angoli arrotondati */
            margin-bottom: 30px;
            font-size: 16px;
            animation: slideInError 0.5s ease-out;
            font-weight: 500;
        }

        /* Stile per l'icona "mostra password" */
        .toggle-password {
            position: absolute;
            right: 20px; /* Posiziona a destra dell'input */
            top: 50%; /* Centra verticalmente */
            transform: translateY(-50%); /* Regola per il centraggio esatto */
            cursor: pointer;
            color: #a0a0a0; /* Colore più tenue per l'icona */
            transition: color 0.2s ease;
            z-index: 3; /* Assicura che sia cliccabile sopra l'input e l'etichetta */
            padding: 5px; /* Aggiunge padding per un'area cliccabile più grande */
        }

        .toggle-password svg {
            width: 26px; /* Dimensione delle icone SVG leggermente maggiore */
            height: 26px;
            stroke: currentColor; /* Usa il colore del parent */
            stroke-width: 2;
            transition: stroke 0.2s ease;
        }

        .toggle-password:hover svg {
            stroke: #555; /* Colore più scuro all'hover */
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .login-container {
                padding: 35px 25px;
                margin: 20px;
                border-radius: 18px;
            }
            h2 {
                font-size: 30px;
                margin-bottom: 30px;
            }
            .login-logo {
                max-width: 120px;
                margin-bottom: 25px;
            }
            input[type="text"],
            input[type="password"] {
                padding: 15px 15px;
                padding-top: 25px;
                font-size: 16px;
            }
            .input-group label {
                font-size: 15px;
                top: 15px;
                left: 15px;
            }
            input[type="text"]:focus + label,
            input[type="password"]:focus + label,
            input[type="text"]:not(:placeholder-shown) + label,
            input[type="password"]:not(:placeholder-shown) + label {
                top: -8px;
                font-size: 11px;
            }
            button[type="submit"] {
                padding: 16px 20px;
                font-size: 19px;
            }
            .error-message {
                padding: 12px;
                font-size: 14px;
            }
            .toggle-password svg {
                width: 22px;
                height: 22px;
            }
            .toggle-password {
                right: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo del Gestionale -->
        <img src="images/LOGO PNG2.png" alt="Logo Gestionale" class="login-logo">
        
        <h2>Accedi al Gestionale</h2>

        <?php
        // Mostra il messaggio di errore se presente
        if (!empty($login_error_message)) {
            echo '<p class="error-message">' . htmlspecialchars($login_error_message) . '</p>';
        }
        ?>

        <form action="process_login.php" method="POST">
            <div class="input-group">
                <input type="text" id="username" name="username" placeholder=" " required>
                <label for="username">Nome Utente</label>
            </div>
            <div class="input-group">
                <input type="password" id="password" name="password" placeholder=" " required>
                <label for="password">Password</label>
                <!-- Icone SVG per mostrare/nascondere la password -->
                <span class="toggle-password" id="togglePassword">
                    <!-- Icona "occhio" (visibile di default) -->
                    <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <!-- Icona "occhio sbarrato" (nascosta di default) -->
                    <svg id="eye-off-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.54 18.54 0 0 1 2.21-3.02m4.65-4.65A10.07 10.07 0 0 1 12 4c7 0 11 8 11 8a18.54 18.54 0 0 1-2.21 3.02M15 12a3 3 0 1 1-6 0"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                </span>
            </div>
            <button type="submit">Accedi</button>
        </form>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');
        const eyeOffIcon = document.getElementById('eye-off-icon');

        togglePassword.addEventListener('click', function (e) {
            // Toggle the type attribute of the password input
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle the visibility of the eye icons
            if (type === 'password') {
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            } else {
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            }
        });
    </script>
</body>
</html>
