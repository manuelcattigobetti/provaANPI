<?php
include_once ('session_manager.php');
include_once ('ClasseGen.php');

// ====== CONFIGURAZIONE DEBUG ======
// Imposta a true per visualizzare errori e link di debug
// Utile in ambiente di sviluppo senza server email
$debug = true;

// Abilita visualizzazione errori se debug attivo
if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// ====== VERIFICA TOKEN CSRF ======
// verify_csrf_token(): controlla che il token POST corrisponda a quello in sessione
// Previene attacchi Cross-Site Request Forgery
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    app_log_error('Token CSRF mancante o errato in login2.php');
    echo "<script>alert('Richiesta non valida: token CSRF mancante o errato.');</script>";
    header('Refresh:5; url=login1.php'); // ritorna a login1.php dopo 5 sec se il token non è valido
    exit();
}

// ====== ELABORAZIONE EMAIL ======
// Procedi con l'elaborazione dei dati
$email = trim($_POST['email']) ?? '';

// Controllo se l'email è valida dal punto di vista sintattico
// controllaEmail(): metodo della classe ClasseGen che valida formato email
$gen = new ClasseGen();
if (!$gen->controllaEmail($email)) {
    app_log_error('Email non valida: ' . ($email ?: '-'));
    echo "<script>alert('Email non valida.');</script>";
    header('Refresh:5; url=login1.php');
    exit();
}

// ====== GENERAZIONE TOKEN CONFERMA ======
// Crea token random di 10 caratteri per link di conferma
// md5(microtime()): hash MD5 del timestamp microsecondi (sempre diverso)
$strRandom = md5(microtime());
$check = substr($strRandom, 0, 10);  // Prende primi 10 caratteri

// ====== PREPARAZIONE EMAIL ======
// Costruisce email con link di conferma che include il token check nella query string
$destinatario = $email;
$oggetto = "Link di conferma login";

// Genera URL dinamico basato sul server corrente (funziona sia in locale che remoto)
// $_SERVER['HTTP_HOST']: dominio corrente (es: localhost, istitutopierogobetti.it)
// $_SERVER['REQUEST_SCHEME']: protocollo (http o https)
// dirname($_SERVER['SCRIPT_NAME']): percorso della directory corrente
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
// Rimuove trailing slash se presente
$scriptPath = rtrim($scriptPath, '/');

// Costruisce URL completo relativo al dominio corrente
$linkConferma = $protocol . "://" . $host . $scriptPath . "/login3.php?check=" . $check;
$corpo = "Clicca sul link per confermare il login: " . $linkConferma;

// ====== INVIO EMAIL ======
// @mail(): operatore @ sopprime warning se server mail non disponibile
// Restituisce true se email accettata per l'invio, false altrimenti
$emailInviata = @mail($destinatario, $oggetto, $corpo);

// ====== SALVATAGGIO SESSIONE ======
// Salva in sessione il token check per confronto successivo in login3.php
$_SESSION['check'] = $check;
$_SESSION['email'] = $email;
$_SESSION['ts'] = time();  // timestamp per verificare timeout (180 secondi)
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica Email</title>
    <link rel="stylesheet" href="STYLE/styles.css">
</head>
<body>
    <div class="container">
        <div class="form-card" role="region">
            <h2 class="form-title">📧 Controlla la tua email</h2>
            <p>Abbiamo inviato un link di conferma all'indirizzo:</p>
            <p><strong><?php echo htmlspecialchars($email); ?></strong></p>
            <p>Clicca sul link nell'email per completare il login.</p>
            
            <?php
            // Mostra il bottone con il link diretto se:
            // 1. Debug è attivo ($debug = true)
            // 2. L'invio dell'email è fallito (server mail non disponibile)
            if ($debug || !$emailInviata) {
                echo "<hr style='margin: 20px 0; border: none; border-top: 1px solid var(--border);'>";
                
                if (!$emailInviata) {
                    echo "<p style='color: var(--danger); font-weight: 600;'>⚠️ Server email non disponibile</p>";
                }
                
                if ($debug) {
                    echo "<p style='color: var(--muted); font-size: 0.9rem;'>🔧 Modalità Debug attiva</p>";
                }
                
                echo "<p style='margin: 15px 0;'>Clicca sul bottone qui sotto per procedere:</p>";
                echo "<a href='" . htmlspecialchars($linkConferma) . "' class='btn btn-primary' style='text-decoration: none;'>
                        ✓ Conferma Login
                      </a>";
                
                if ($debug) {
                    echo "<p style='margin-top: 15px; font-size: 0.85rem; color: var(--muted);'>";
                    echo "<strong>Link generato:</strong><br>";
                    echo "<code style='background: #f1f5f9; padding: 5px 10px; border-radius: 5px; display: inline-block; margin-top: 5px; word-break: break-all;'>" 
                         . htmlspecialchars($linkConferma) . "</code>";
                    echo "</p>";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>