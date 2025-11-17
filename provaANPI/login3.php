<?php
include_once('session_manager.php');
include_once('dati_accesso.php');
require_once 'Utenti.php';

// ====== DEBUG ======
$debug = true; // imposta a false in produzione
if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Validazione parametri di sessione
if (!isset($_SESSION['email']) || !isset($_SESSION['ts'])) {
    app_log_error('Sessione non valida o scaduta in login3.php');
    echo "<script>alert('Sessione non valida o scaduta.');</script>";
    header('Refresh:4; url=login1.php');
    exit;
}

// Timeout 180s dal token
if (time() - $_SESSION['ts'] >= 180) {
    app_log_error('Timeout conferma oltre 180s in login3.php per ' . ($_SESSION['email'] ?? '-'));
    echo "<script>alert('Link scaduto. Richiedi un nuovo accesso.');</script>";
    header('Refresh:4; url=login1.php');
    exit;
}

// Verifica token check
$check = $_GET['check'] ?? '';
if (!isset($_SESSION['check']) || $_SESSION['check'] !== $check) {
    app_log_error('Check token non valido in login3.php');
    echo "<script>alert('Operazione non valida: il parametro di controllo non corrisponde.');</script>";
    header('Refresh:5; url=login1.php');
    exit;
}

// Connessione DB e redirect in base all'esistenza utente
$email = $_SESSION['email'];
$utenti = new Utenti($datiAccesso);

if ($utenti->emailEsiste($email)) {
    // Utente già presente: vai a home
    $utenti->chiudiConn();
    header('Location: home.php');
    exit;
} else {
    // Utente non presente: vai a completare i dati
    $utenti->chiudiConn();
    header('Location: form_utente.php');
    exit;
}
?>