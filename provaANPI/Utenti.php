<?php
/////////////////
//             //
//    Romei    //
//             //
/////////////////
require_once 'Connessione.php';

class Utenti extends Connessione {
    /**
     * Costruttore della classe.
     *
     * Inizializza una nuova istanza della classe e imposta lo stato iniziale dell'oggetto.
     *
     * @return void
     */
    // Costruttore della classe
    public function __construct($datiAccesso = null) {
        if ($datiAccesso) {
            $this->connessione($datiAccesso);
        }
    }
    
    // Metodo per inserire un nuovo utente
    /**
     * Inserisce un nuovo utente nel database
     * @param string $cognome Cognome dell'utente
     * @param string $nome Nome dell'utente
     * @param string $dob Data di nascita (formato YYYY-MM-DD)
     * @param string $email Email dell'utente
     * @param int $livello Livello utente (default = 1)
     * @return bool True se inserimento riuscito, False altrimenti
     */
    public function inserisciUtente($cognome, $nome, $dob, $email, $livello = 1) {
        $this->cancellaMsgErr();
        
        // Sanitizzazione input
        $cognome = $this->sanitizzaStringa($cognome);
        $nome = $this->sanitizzaStringa($nome);
        $email = trim(strtolower($email));
        
        // Validazione input
        if (empty($cognome) || empty($nome) || empty($dob) || empty($email)) {
            $this->msgErrore = "Tutti i campi sono obbligatori";
            return false;
        }
        
        // Validazione nome e cognome (solo lettere, spazi, apostrofi)
        if (!$this->validaNome($cognome) || !$this->validaNome($nome)) {
            $this->msgErrore = "Nome e cognome possono contenere solo lettere, spazi e apostrofi";
            return false;
        }
        
        // Controllo lunghezza nome e cognome
        if (strlen($cognome) < 2 || strlen($cognome) > 50 || strlen($nome) < 2 || strlen($nome) > 50) {
            $this->msgErrore = "Nome e cognome devono essere tra 2 e 50 caratteri";
            return false;
        }
        
        // Verifica formato email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 70) {
            $this->msgErrore = "Formato email non valido o troppo lunga (max 70 caratteri)";
            return false;
        }
        
        // Verifica se l'email esiste già
        if ($this->emailEsiste($email)) {
            $this->msgErrore = "Email già esistente nel database";
            return false;
        }
        
        // Validazione data di nascita
        if (!$this->validaData($dob)) {
            $this->msgErrore = "Formato data non valido (utilizzare YYYY-MM-DD)";
            return false;
        }
        
        // Controllo età (non futura e ragionevole)
        $oggi = new DateTime();
        $dataNascita = DateTime::createFromFormat('Y-m-d', $dob);
        if ($dataNascita > $oggi) {
            $this->msgErrore = "La data di nascita non può essere futura";
            return false;
        }
        $eta = $oggi->diff($dataNascita)->y;
        if ($eta < 0 || $eta > 120) {
            $this->msgErrore = "Età non valida (deve essere tra 0 e 120 anni)";
            return false;
        }
        
        // Validazione livello
        if (!$this->validaLivello($livello)) {
            $this->msgErrore = "Livello utente non valido (deve essere un intero tra 1 e 5)";
            return false;
        }
        
        // Preparazione query con prepared statement
        $sql = "INSERT INTO utenti (cognome, nome, dob, email, livello) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->msgErrore = "Errore nella preparazione della query: " . $this->conn->error;
            return false;
        }
        
        // Binding parametri
        $stmt->bind_param("ssssi", $cognome, $nome, $dob, $email, $livello);
        
        // Esecuzione query
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $this->msgErrore = "Errore durante l'inserimento: " . $stmt->error;
            $stmt->close();
            return false;
        }
    }
    
    // Metodo per verificare se un'email esiste già
    /**
     * Verifica se un'email è già presente nel database
     * @param string $email Email da verificare
     * @return bool True se esiste, False altrimenti
     */
    public function emailEsiste($email) {
        $this->cancellaMsgErr();
        
        $email = trim(strtolower($email));
        
        if (empty($email)) {
            $this->msgErrore = "Email non specificata";
            return false;
        }
        
        $sql = "SELECT IDUtente FROM utenti WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->msgErrore = "Errore nella preparazione della query: " . $this->conn->error;
            return false;
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        $esiste = $stmt->num_rows > 0;
        
        $stmt->close();
        return $esiste;
    }
    
    // Metodo per leggere tutti i dati di un utente specificando l'email
    /**
     * Recupera tutti i dati di un utente tramite email
     * @param string $email Email dell'utente da cercare
     * @return array|false Array con i dati dell'utente o False se non trovato
     */
    public function leggiUtentePerEmail($email) {
        $this->cancellaMsgErr();
        
        $email = trim(strtolower($email));
        
        if (empty($email)) {
            $this->msgErrore = "Email non specificata";
            return false;
        }
        
        $sql = "SELECT IDUtente, cognome, nome, dob, email, livello FROM utenti WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->msgErrore = "Errore nella preparazione della query: " . $this->conn->error;
            return false;
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->msgErrore = "Utente non trovato";
            $stmt->close();
            return false;
        }
        
        $utente = $result->fetch_assoc();
        $stmt->close();
        
        return $utente;
    }
    
    // Metodo per leggere tutti gli utenti
    /**
     * Recupera tutti gli utenti dal database
     * @return array|false Array di utenti o False in caso di errore
     */
    public function leggiTuttiUtenti() {
        $this->cancellaMsgErr();
        
        $sql = "SELECT IDUtente, cognome, nome, dob, email, livello FROM utenti ORDER BY cognome, nome";
        $result = $this->conn->query($sql);
        
        if (!$result) {
            $this->msgErrore = "Errore nella query: " . $this->conn->error;
            return false;
        }
        
        $utenti = [];
        while ($row = $result->fetch_assoc()) {
            $utenti[] = $row;
        }
        
        return $utenti;
    }
    
    // Metodo per aggiornare un utente
    /**
     * Aggiorna i dati di un utente
     * @param int $idUtente ID dell'utente da aggiornare
     * @param string $cognome Nuovo cognome
     * @param string $nome Nuovo nome
     * @param string $dob Nuova data di nascita
     * @param string $email Nuova email
     * @param int $livello Nuovo livello
     * @return bool True se aggiornamento riuscito, False altrimenti
     */
    public function aggiornaUtente($idUtente, $cognome, $nome, $dob, $email, $livello = 1) {
        $this->cancellaMsgErr();
        
        // Validazione ID utente
        if (!$this->validaID($idUtente)) {
            $this->msgErrore = "ID utente non valido";
            return false;
        }
        
        // Verifica che l'utente esista
        $utenteEsistente = $this->leggiUtentePerID($idUtente);
        if (!$utenteEsistente) {
            $this->msgErrore = "Utente non trovato";
            return false;
        }
        
        // Sanitizzazione input
        $cognome = $this->sanitizzaStringa($cognome);
        $nome = $this->sanitizzaStringa($nome);
        $email = trim(strtolower($email));
        
        // Validazione input
        if (empty($cognome) || empty($nome) || empty($dob) || empty($email)) {
            $this->msgErrore = "Tutti i campi sono obbligatori";
            return false;
        }
        
        // Validazione nome e cognome (solo lettere, spazi, apostrofi)
        if (!$this->validaNome($cognome) || !$this->validaNome($nome)) {
            $this->msgErrore = "Nome e cognome possono contenere solo lettere, spazi e apostrofi";
            return false;
        }
        
        // Controllo lunghezza nome e cognome
        if (strlen($cognome) < 2 || strlen($cognome) > 50 || strlen($nome) < 2 || strlen($nome) > 50) {
            $this->msgErrore = "Nome e cognome devono essere tra 2 e 50 caratteri";
            return false;
        }
        
        // Verifica formato email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            $this->msgErrore = "Formato email non valido o troppo lunga (max 100 caratteri)";
            return false;
        }
        
        // Se l'email è cambiata, verifica che non esista già
        if ($utenteEsistente['email'] !== $email && $this->emailEsiste($email)) {
            $this->msgErrore = "La nuova email è già utilizzata da un altro utente";
            return false;
        }
        
        // Validazione data di nascita
        if (!$this->validaData($dob)) {
            $this->msgErrore = "Formato data non valido (utilizzare YYYY-MM-DD)";
            return false;
        }
        
        // Controllo età (non futura e ragionevole)
        $oggi = new DateTime();
        $dataNascita = DateTime::createFromFormat('Y-m-d', $dob);
        if ($dataNascita > $oggi) {
            $this->msgErrore = "La data di nascita non può essere futura";
            return false;
        }
        $eta = $oggi->diff($dataNascita)->y;
        if ($eta < 0 || $eta > 120) {
            $this->msgErrore = "Età non valida (deve essere tra 0 e 120 anni)";
            return false;
        }
        
        // Validazione livello
        if (!$this->validaLivello($livello)) {
            $this->msgErrore = "Livello utente non valido (deve essere un intero tra 1 e 5)";
            return false;
        }
        
        $sql = "UPDATE utenti SET cognome = ?, nome = ?, dob = ?, email = ?, livello = ? WHERE IDUtente = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->msgErrore = "Errore nella preparazione della query: " . $this->conn->error;
            return false;
        }
        
        $stmt->bind_param("ssssii", $cognome, $nome, $dob, $email, $livello, $idUtente);
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $this->msgErrore = "Errore durante l'aggiornamento: " . $stmt->error;
            $stmt->close();
            return false;
        }
    }
    
    // Metodo per eliminare un utente
    /**
     * Elimina un utente dal database
     * @param int $idUtente ID dell'utente da eliminare
     * @return bool True se eliminazione riuscita, False altrimenti
     */
    public function eliminaUtente($idUtente) {
        $this->cancellaMsgErr();
        
        // Validazione ID utente
        if (!$this->validaID($idUtente)) {
            $this->msgErrore = "ID utente non valido";
            return false;
        }
        
        // Verifica che l'utente esista
        $utenteEsistente = $this->leggiUtentePerID($idUtente);
        if (!$utenteEsistente) {
            $this->msgErrore = "Utente non trovato";
            return false;
        }
        
        $sql = "DELETE FROM utenti WHERE IDUtente = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->msgErrore = "Errore nella preparazione della query: " . $this->conn->error;
            return false;
        }
        
        $stmt->bind_param("i", $idUtente);
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $this->msgErrore = "Errore durante l'eliminazione: " . $stmt->error;
            $stmt->close();
            return false;
        }
    }
    
    // Metodo privato per leggere utente per ID (uso interno)
    private function leggiUtentePerID($idUtente) {
        $sql = "SELECT IDUtente, cognome, nome, dob, email, livello FROM utenti WHERE IDUtente = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $idUtente);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return false;
        }
        
        $utente = $result->fetch_assoc();
        $stmt->close();
        
        return $utente;
    }
    
    // Metodo privato per validare la data
    private function validaData($data) {
        $d = DateTime::createFromFormat('Y-m-d', $data);
        return $d && $d->format('Y-m-d') === $data;
    }
    
    // Metodo privato per sanitizzare stringa
    private function sanitizzaStringa($stringa) {
        return trim(strip_tags($stringa));
    }
    
    // Metodo privato per validare nome
    private function validaNome($nome) {
        return preg_match("/^[a-zA-Z\s']+$/", $nome);
    }
    
    // Metodo privato per validare livello
    private function validaLivello($livello) {
        return is_int($livello) && $livello >= 1 && $livello <= 5;
    }
    
    // Metodo privato per validare ID utente
    private function validaID($id) {
        return is_int($id) && $id > 0;
    }
} // fine classe Utenti
?>

