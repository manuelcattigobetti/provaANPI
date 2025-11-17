<?php
/////////////////
//             //
//   ins_utente.php  
//   Script per l'inserimento di nuovi utenti nel database
//             //
/////////////////

/**
 * Questo script riceve i dati dal form_utente.php via POST,
 * li valida e li inserisce nella tabella 'utenti' usando
 * i metodi della classe Utenti.php
 */

// ====== INCLUSIONI ======
// session_manager.php: gestisce l'avvio e la configurazione della sessione PHP
require_once 'session_manager.php';

// dati_accesso.php: contiene l'array $datiAccesso con credenziali del database
// (host, user, password, nome database)
require_once 'dati_accesso.php';

// Utenti.php: classe che estende Connessione e fornisce metodi per gestire la tabella utenti
require_once 'Utenti.php';

// ClasseGen.php: classe di utilità con metodi di validazione (es. ctrlNomCog)
require_once 'ClasseGen.php';

// ====== CONTROLLO METODO HTTP ======
// Verifica che la richiesta sia POST (per sicurezza, evita accessi diretti tramite GET)
// $_SERVER['REQUEST_METHOD']: variabile superglobale che contiene il metodo HTTP usato
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Log dell'accesso con metodo errato
    $method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    app_log_error("Metodo non consentito: $method da $ip", basename(__FILE__));
    echo "<script>alert('Metodo non consentito');</script>";
    // header('Refresh:2; URL=...'): reindirizza dopo 2 secondi
    header('Refresh:2; URL=form_utente.php');
    exit(); // Interrompe l'esecuzione dello script
}

// ====== CONTROLLO SESSIONE ======
// Verifica che l'utente sia autenticato (email presente in $_SESSION)
// isset(): controlla se una variabile è definita e non è NULL
if (!isset($_SESSION['email'])) {
    // Log sessione assente/non valida
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    app_log_error("Sessione non valida o scaduta da $ip", basename(__FILE__));
    echo "<script>alert('Sessione non valida. Effettua nuovamente il login.');</script>";
    header('Refresh:2; URL=login1.php');
    exit();
}

// ====== LETTURA DATI POST ======
// isset($_POST['campo']): verifica se il campo è stato inviato via POST
// trim(): rimuove spazi bianchi all'inizio e alla fine della stringa
// Operatore ternario ?: fornisce stringa vuota come fallback se campo non presente
$cognome = isset($_POST['cognome']) ? trim($_POST['cognome']) : '';
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$dob = isset($_POST['dob']) ? trim($_POST['dob']) : '';  // dob = date of birth (data di nascita)
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// ====== VALIDAZIONE EMAIL SESSIONE ======
// Controllo di sicurezza: l'email del form deve corrispondere a quella della sessione
// Previene tentativi di inserire dati per altri utenti
if ($email !== $_SESSION['email']) {
    // Log incongruenza email form vs sessione
    $sess = $_SESSION['email'] ?? '-';
    app_log_error("Email non corrispondente: form='$email' session='$sess'", basename(__FILE__));
    echo "<script>alert('Errore: l\\'email non corrisponde alla sessione.');</script>";
    header('Refresh:2; URL=form_utente.php');
    exit();
}

// ====== VALIDAZIONE CAMPI OBBLIGATORI ======
// empty(): restituisce true se la variabile è vuota, "", 0, NULL, FALSE o non definita
// Verifica che tutti i campi abbiano un valore
if (empty($cognome) || empty($nome) || empty($dob) || empty($email)) {
    // Log dettagliato campi mancanti
    $missing = [];
    if (empty($cognome)) $missing[] = 'cognome';
    if (empty($nome)) $missing[] = 'nome';
    if (empty($dob)) $missing[] = 'dob';
    if (empty($email)) $missing[] = 'email';
    app_log_error('Campi obbligatori mancanti: ' . implode(', ', $missing), basename(__FILE__));
    echo "<script>alert('Errore: tutti i campi sono obbligatori.');</script>";
    header('Refresh:2; URL=form_utente.php');
    exit();
}

// ====== VALIDAZIONE SINTASSI COGNOME E NOME ======
// Usa il metodo valida_nome() della classe ClasseGen per validare sintassi
// Questo metodo:
// - Accetta solo lettere Unicode (anche accentate), spazi, apostrofi e trattini
// - Rimuove spazi multipli e normalizza apostrofi tipografici
// - Capitalizza ogni parola (prima lettera maiuscola) mantenendo UTF-8
// - Restituisce stringa formattata se valida, false se invalida
$validatore = new ClasseGen();

// Valida cognome
// valida_nome($input, $maiuscolo): secondo parametro true = capitalizza (default)
$cognomeValidato = $validatore->valida_nome($cognome, true);
if ($cognomeValidato === false) {
    // valida_nome() ha restituito false: sintassi non valida
    app_log_error("Validazione cognome fallita per input: '$cognome'", basename(__FILE__));
    echo "<script>alert('Errore cognome: deve contenere solo lettere (anche accentate), spazi, apostrofi e trattini.');</script>";
    header('Refresh:3; URL=form_utente.php');
    exit();
}

// Valida nome
$nomeValidato = $validatore->valida_nome($nome, true);
if ($nomeValidato === false) {
    // valida_nome() ha restituito false: sintassi non valida
    app_log_error("Validazione nome fallita per input: '$nome'", basename(__FILE__));
    echo "<script>alert('Errore nome: deve contenere solo lettere (anche accentate), spazi, apostrofi e trattini.');</script>";
    header('Refresh:3; URL=form_utente.php');
    exit();
}

// Se arriva qui, cognome e nome sono sintatticamente corretti
// Usa le versioni validate (formattate con capitalizzazione corretta e apostrofi normalizzati)
$cognome = $cognomeValidato;
$nome = $nomeValidato;

// ====== CONNESSIONE DATABASE ======
// Crea un'istanza della classe Utenti (che estende Connessione)
// new Utenti(): istanzia l'oggetto senza parametri (costruttore ereditato è vuoto)
$utenti = new Utenti();

// connessione($datiAccesso): metodo ereditato da Connessione che apre la connessione mysqli
// $datiAccesso è l'array con [host, user, password, database]
$utenti->connessione($datiAccesso);

// ====== INSERIMENTO NEL DATABASE ======
// inserisciUtente(): metodo della classe Utenti che:
// - Sanitizza e valida i dati (nome, cognome, email, data)
// - Verifica che l'email non esista già nel database
// - Inserisce il record nella tabella 'utenti' usando prepared statement (sicuro contro SQL injection)
// - Il quinto parametro (livello) è opzionale, di default = 1 (utente standard)
// Restituisce: true se inserimento riuscito, false se errore
if ($utenti->inserisciUtente($cognome, $nome, $dob, $email)) {
    
    // ====== INSERIMENTO RIUSCITO ======
    // Mostra pagina di successo con riepilogo dati inseriti
    // htmlspecialchars(): converte caratteri speciali in entità HTML per prevenire XSS
    echo "<!DOCTYPE html>
<html lang='it'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Inserimento Riuscito</title>
    <link rel='stylesheet' href='STYLE/styles.css'>
</head>
<body>
    <!-- .container: wrapper per centrare il contenuto (definito in styles.css) -->
    <div class='container'>
        <!-- .form-card: card visiva con bordo e ombra -->
        <div class='form-card' role='region'>
            <!-- style inline: sovrascrive CSS per colorare il titolo con variabile --success (verde) -->
            <h2 class='form-title' style='color: var(--success);'>✓ Utente inserito con successo!</h2>
            <p>I tuoi dati sono stati memorizzati correttamente.</p>
            <!-- Lista non ordinata con riepilogo dati -->
            <ul style='text-align: left; margin: 20px auto; max-width: 400px;'>
                <li><strong>Cognome:</strong> " . htmlspecialchars($cognome) . "</li>
                <li><strong>Nome:</strong> " . htmlspecialchars($nome) . "</li>
                <li><strong>Data di nascita:</strong> " . htmlspecialchars(date('d-m-Y', strtotime($dob))) . "</li>
                <li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>
            </ul>
            <!-- Link stilizzato come bottone per tornare alla home -->
            <a href='home.php' class='btn btn-primary' style='text-decoration: none; margin-top: 10px;'>Vai alla Home</a>
        </div>
    </div>
</body>
</html>";

} else {
    
    // ====== INSERIMENTO FALLITO ======
    // msgErr(): metodo ereditato da Connessione che restituisce il messaggio di errore
    // memorizzato nella proprietà protetta $msgErrore
    $errore = $utenti->msgErr();
    
    // ====== GESTIONE SPECIFICA PER EMAIL GIÀ ESISTENTE ======
    // Se l'errore è "email già esistente", reindirizza a login1.php
    // Motivo: l'email è readonly nel form e non può essere modificata,
    // quindi l'utente deve rifare il login con un'altra email
    if (strpos($errore, 'già esistente') !== false || strpos($errore, 'già presente') !== false) {
        // Log specifico per email duplicata
        app_log_error("Email già registrata: $email - errore: $errore", basename(__FILE__));
        echo "<!DOCTYPE html>
<html lang='it'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Email già registrata</title>
    <link rel='stylesheet' href='STYLE/styles.css'>
</head>
<body>
    <div class='container'>
        <div class='form-card' role='region'>
            <!-- Titolo colorato con variabile --danger (rosso) -->
            <h2 class='form-title' style='color: var(--danger);'>⚠ Email già registrata</h2>
            <p>L'email <strong>" . htmlspecialchars($email) . "</strong> è già presente nel sistema.</p>
            <p>Poiché l'email non può essere modificata, devi effettuare nuovamente il login con un indirizzo email diverso.</p>
            <!-- Reindirizza a login1.php perché l'utente non può modificare l'email -->
            <a href='login1.php' class='btn btn-primary' style='text-decoration: none; margin-top: 10px;'>Torna al Login</a>
        </div>
    </div>
</body>
</html>";
    } else {
        // ====== ALTRI ERRORI ======
        // Per errori di validazione (cognome, nome, data, ecc.) può tornare al form
        app_log_error("Errore inserimento utente: $errore | input: cognome='$cognome', nome='$nome', dob='$dob', email='$email'", basename(__FILE__));
        echo "<!DOCTYPE html>
<html lang='it'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Errore Inserimento</title>
    <link rel='stylesheet' href='STYLE/styles.css'>
</head>
<body>
    <div class='container'>
        <div class='form-card' role='region'>
            <!-- Titolo colorato con variabile --danger (rosso) -->
            <h2 class='form-title' style='color: var(--danger);'>✗ Errore durante l'inserimento</h2>
            <!-- htmlspecialchars() protegge da eventuali caratteri pericolosi nel messaggio -->
            <p><strong>Dettaglio errore:</strong> " . htmlspecialchars($errore) . "</p>
            <!-- Per altri errori (validazione dati) può tornare al form per correggere -->
            <a href='form_utente.php' class='btn btn-primary' style='text-decoration: none; margin-top: 10px;'>Torna al Form</a>
        </div>
    </div>
</body>
</html>";
    }
}

// ====== CHIUSURA CONNESSIONE ======
// chiudiConn(): metodo ereditato da Connessione che chiude la connessione mysqli
// Buona pratica: rilasciare le risorse quando non più necessarie
$utenti->chiudiConn();
?>