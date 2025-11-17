<?php
include_once 'session_manager.php';

// Se disponibile, logga evento di disconnessione con snapshot salvato
if (!empty($_SESSION['user_snapshot'])) {
    app_log_user($_SESSION['user_snapshot'], 'DISCONNESSIONE');
}

// Distrugge la sessione e redireziona alla home
session_unset();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');

header('Location: index.html');
exit;
?>