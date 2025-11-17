<?php
/**
 * session_manager.php
 */

/**
 * CONFIGURAZIONE SICURA DEI COOKIE DI SESSIONE
 * 
 * Questa configurazione imposta parametri di sicurezza avanzati per i cookie di sessione
 * per proteggere l'applicazione da attacchi comuni come XSS, CSRF e session hijacking.
 */

session_set_cookie_params([
    /**
     * 'lifetime' => 1800
     * 
     * Durata di vita del cookie di sessione in secondi.
     * - 1800 secondi = 30 minuti
     * - Dopo questo periodo il cookie scade automaticamente
     * - Equivale a session.gc_maxlifetime ma a livello client
     * - Importante per sicurezza: sessioni brevi riducono rischio di hijacking
     */
    'lifetime' => 1800,                        // 30 minuti - bilancia sicurezza e usabilità

    /**
     * 'path' => '/'
     * 
     * Percorso sul server in cui il cookie sarà disponibile.
     * - '/' significa tutto il dominio
     * - Se impostato a '/admin', il cookie sarebbe disponibile solo in /admin/*
     * - Con '/' il cookie è accessibile da tutte le pagine del sito
     */
    'path'     => '/',

    /**
     * 'secure' => isset($_SERVER['HTTPS'])
     * 
     * Indica se il cookie deve essere trasmesso solo tramite connessioni HTTPS sicure.
     * - true: cookie inviato solo su HTTPS
     * - false: cookie inviato sia su HTTP che HTTPS
     * - isset($_SERVER['HTTPS']) rileva automaticamente se è in uso HTTPS
     * - Previene attacchi di sniffing su connessioni non cifrate
     */
    'secure'   => isset($_SERVER['HTTPS']),    // Solo HTTPS se disponibile - crittografia SSL/TLS

    /**
     * 'httponly' => true
     * 
     * Impedisce l'accesso al cookie via JavaScript.
     * - true: cookie non accessibile via document.cookie
     * - false: cookie accessibile via JavaScript
     * - Protegge da XSS (Cross-Site Scripting) che potrebbero rubare la sessione
     * - Il cookie può essere usato solo dal browser nelle richieste HTTP
     */
    'httponly' => true,                        // Impedisce lettura cookie via JS - anti XSS

    /**
     * 'samesite' => 'Lax'
     * 
     * Controlla quando i cookie vengono inviati in richieste cross-site.
     * 
     * Valori possibili:
     * - 'Strict': Mai inviato in richieste cross-site (massima sicurezza)
     * - 'Lax': Invio consentito per navigazione normale (click link) ma non per form POST
     * - 'None': Sempre inviato (richiede 'secure' => true)
     * 
     * 'Lax' offre buon equilibrio tra sicurezza e funzionalità:
     * - Previene attacchi CSRF (Cross-Site Request Forgery)
     * - Permette ancora navigazione normale tra siti
     */
    'samesite' => 'Lax'                        // Previene attacchi CSRF - equilibrio sicurezza/usabilità
]);

/**
 * CONFIGURAZIONE SERVER-SIDE DELLE SESSIONI
 * 
 * Imposta il timeout delle sessioni lato server.
 * Questo è complementare al lifetime del cookie ma agisce sul server.
 */

/**
 * ini_set('session.gc_maxlifetime', 1800)
 * 
 * Tempo massimo (in secondi) prima che una sessione venga considerata "spazzatura" (garbage).
 * 
 * Funzionamento:
 * - Dopo 1800 secondi (30 minuti) di inattività, la sessione viene marcata per la cancellazione
 * - Il garbage collector PHP rimuoverà queste sessioni durante le prossime esecuzioni
 * - Questo è indipendente dal cookie lifetime che controlla il client
 * 
 * Importante:
 * - Deve essere uguale o maggiore del cookie lifetime per coerenza
 * - Se minore, il server potrebbe cancellare sessioni valide per il client
 * - Se maggiore, sessioni potrebbero persistere più del necessario
 */
ini_set('session.gc_maxlifetime', 1800); // 30 minuti - timeout server-side


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timeout sessione
$timeout = 1800; // 30 min durata massima
if (isset($_SESSION['tsOld']) && (time() - $_SESSION['tsOld'] > $timeout)) {
    session_unset(); // libera tutte le variabili di sessione
    session_destroy(); // distrugge la sessione
    setcookie(session_name(), '', time() - 3600, '/'); // elimina cookie di sessione 
}
$_SESSION['tsOld'] = time();

/* ============================================
   FUNZIONI CSRF
   ============================================ */
/**
 * Genera un token CSRF univoco e lo salva in sessione.
 * @return string Token CSRF
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // 64 caratteri
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica la validità del token CSRF ricevuto dal form.
 * @param string|null $token Il token inviato dal form
 * @return bool True se valido, false altrimenti
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/* ============================================
   LOGGING APPLICATIVO
   ============================================ */
/**
 * Scrive un messaggio di errore in errori.log con timestamp e file sorgente.
 * @param string $message Descrizione dell'errore
 * @param string|null $file Nome file sorgente (opzionale)
 * @return void
 */
function app_log_error(string $message, ?string $file = null): void {
    $logDir = __DIR__;
    $logFile = $logDir . DIRECTORY_SEPARATOR . 'errori.log';
    $when = date('Y-m-d H:i:s');
    $src = $file ?? ($_SERVER['SCRIPT_NAME'] ?? 'CLI');
    $row = "[$when] [$src] $message" . PHP_EOL;
    @file_put_contents($logFile, $row, FILE_APPEND | LOCK_EX);
}

/**
 * Scrive un evento utente (CONNESSIONE/DISCONNESSIONE) in utenti.log.
 * @param array $utente Array con chiavi: IDUtente,cognome,nome,email,livello
 * @param string $evento Testo evento (es. CONNESSIONE, DISCONNESSIONE)
 * @return void
 */
function app_log_user(array $utente, string $evento = 'CONNESSIONE'): void {
    $logDir = __DIR__;
    $logFile = $logDir . DIRECTORY_SEPARATOR . 'utenti.log';
    $when = date('Y-m-d H:i:s');
    $id = $utente['IDUtente'] ?? '-';
    $cognome = $utente['cognome'] ?? '-';
    $nome = $utente['nome'] ?? '-';
    $email = $utente['email'] ?? '-';
    $livello = $utente['livello'] ?? '-';
    $row = "$evento | ID:$id | $cognome | $nome | $email | LV:$livello | $when" . PHP_EOL;
    @file_put_contents($logFile, $row, FILE_APPEND | LOCK_EX);
}

// Handler globale degli errori PHP
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Costruisce un messaggio chiaro con tipo errore, file e riga
    $types = [
        E_ERROR => 'E_ERROR', E_WARNING => 'E_WARNING', E_PARSE => 'E_PARSE', E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR', E_CORE_WARNING => 'E_CORE_WARNING', E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING', E_USER_ERROR => 'E_USER_ERROR', E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE', E_STRICT => 'E_STRICT', E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED', E_USER_DEPRECATED => 'E_USER_DEPRECATED'
    ];
    $type = $types[$errno] ?? 'E_UNKNOWN';
    app_log_error("$type: $errstr (file: $errfile, linea: $errline)", basename($errfile));
    // Restituisce false per lasciare anche il comportamento PHP predefinito se display_errors è attivo
    return false;
});

// Handler globale delle eccezioni non catturate
set_exception_handler(function ($ex) {
    $file = $ex->getFile();
    $line = $ex->getLine();
    app_log_error('Uncaught Exception: ' . $ex->getMessage() . " (file: $file, linea: $line)", basename($file));
});

// Log degli errori fatali a fine esecuzione
register_shutdown_function(function () {
    $last = error_get_last();
    if ($last && in_array($last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        app_log_error('Fatal error: ' . $last['message'] . " (file: {$last['file']}, linea: {$last['line']})", basename($last['file']));
    }
});
?>
