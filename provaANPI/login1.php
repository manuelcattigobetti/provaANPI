<?php
/////////////////
//             //
//   login1.php   //
//   Form di login per inserimento email //
//             //
/////////////////

/**
 * Pagina iniziale del flusso di login.
 * L'utente inserisce la propria email e viene generato un token CSRF per sicurezza.
 * Dopo l'invio, viene reindirizzato a login2.php che gestisce l'invio dell'email di conferma.
 */

// session_manager.php: gestisce sessione e funzioni CSRF
include_once('session_manager.php');

// Toggle debug locale
$debug = true; // Impostare a false in produzione
if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Verifica integrità minima ambiente
if (!function_exists('generate_csrf_token')) {
    app_log_error('Funzione generate_csrf_token mancante in login1.php');
    echo '<script>alert("Errore interno. Riprova più tardi.");</script>';
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <!-- lang="it": imposta la lingua del documento per accessibilità e SEO -->
    <meta charset="UTF-8"> <!-- set di caratteri UTF-8 per lettere accentate italiane -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Responsività su dispositivi mobili -->
    <title>Login - ANPI</title> <!-- Titolo mostrato nella scheda del browser -->
    <link rel="stylesheet" href="STYLE/styles.css"> <!-- Collega il foglio di stile principale -->
</head>
<body>
    <!-- .container: wrapper che centra verticalmente e orizzontalmente il contenuto -->
    <div class="container">
        <!-- .form-card: card visiva con bordo e ombra; role="region" per accessibilità -->
        <div class="form-card" role="region" aria-labelledby="login-title">
            <!-- id="login-title": usato da aria-labelledby per associare il titolo all'area -->
            <h2 id="login-title" class="form-title">🔐 Accedi</h2>
            <p class="intro-text">
                Inserisci la tua email per ricevere il link di accesso.
            </p>

            <!-- form: action punta a login2.php che gestisce validazione e invio email -->
            <!-- method="POST": invia dati in modo sicuro (non visibili nell'URL) -->
            <form method="POST" action="login2.php" novalidate>
                <!-- .form-group: blocco logico che raggruppa label, input e testo di aiuto -->
                <div class="form-group">
                    <!-- label for="email": associa etichetta al campo input con id email -->
                    <label for="email">Email</label>
                    <!-- type="email": valida formato email lato browser -->
                    <!-- id/name: identificatore nel DOM e nome chiave nel POST -->
                    <!-- required: campo obbligatorio -->
                    <!-- autocomplete="email": suggerimenti browser per email salvate -->
                    <!-- placeholder: testo guida iniziale -->
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="esempio@email.com" 
                           required 
                           autocomplete="email">
                    <!-- .help-text: testo descrittivo secondario -->
                    <small class="help-text">Ti invieremo un link di conferma via email</small>
                </div>

                <!-- Campo nascosto CSRF token per protezione contro attacchi Cross-Site Request Forgery -->
                <!-- generate_csrf_token(): funzione in session_manager.php che crea token univoco -->
                <!-- Il token viene verificato in login2.php prima di processare la richiesta -->
                <input type="hidden" 
                       id="csrf_token" 
                       name="csrf_token" 
                       value="<?= generate_csrf_token(); ?>">

                <!-- button type="submit": invia il form; classi .btn .btn-primary per stile -->
                <button type="submit" class="btn btn-primary">Invia Link di Accesso</button>
            </form>
        </div>
    </div>
</body>
</html>