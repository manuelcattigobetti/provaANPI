<?php
/////////////////
//             //
//    Romei    //
//             //
/////////////////
/**
 * Classe di utilità per la gestione di validazioni e operazioni comuni
 */
class ClasseGen {
    
    const ANNIMAX = 120;
    const ANNIMIN = 18;
    const MAGG = 18;

    protected $msgErrore = "";

    /**
     * Visualizza i messaggi di errore tramite alert JavaScript e restituisce la stringa
     * 
     * @return string Stringa contenente tutti gli errori accumulati
     */
    public function msgErr() {
        ?><script>alert(<?= $this->msgErrore; ?>)</script><?php
        return $this->msgErrore;
    }

    /**
     * Cancella tutti i messaggi di errore accumulati
     * 
     * @return void
     */
    public function cancellaMsgErr() {
        $this->msgErrore = "";
    }

    /**
     * Controlla e formatta stringhe per cognomi e nomi
     * 
     * Rimuove spazi superflui, verifica lunghezza (2-50 caratteri) e caratteri validi (alfabetici e accentati)
     * Restituisce la stringa in maiuscolo se valida, altrimenti stringa vuota e aggiorna errore
     * 
     * @param string $str Stringa da controllare
     * @return string Stringa formattata in maiuscolo o stringa vuota in caso di errore
     */
    public function ctrlNomCog($str) {
        $str = trim($str);
        if (!preg_match('/^[a-zA-Zèòàùì ]{2,50}$/', $str)) {
            $this->msgErrore .= "-> Errore sintattico inserimento cognome o nome";
            return "";
        } else {
            $str = $this->sistemaAccentate($str);
            return $str;
        }
    }

    /**
     * Controlla e formatta una data con limiti di età
     * 
     * Verifica l'esistenza della data e controlla che rientri nei limiti di età definiti (ANNIMIN-ANNIMAX)
     * 
     * @param int $gio Giorno della data
     * @param int $mes Mese della data
     * @param int $ann Anno della data
     * @param int $strTS Formato di ritorno: 0 per stringa 'gg/mm/aaaa', 1 per timestamp
     * @return string|int Data formattata come stringa o timestamp, stringa vuota in caso di errore
     */
    public function sistemaDataLim($gio, $mes, $ann, $strTS = 0) {
        $anni = date('Y') - $ann;
        if (checkdate($mes, $gio, $ann)) {
            if (strlen($gio) == 1) {
                $gio = "0" . $gio;
            }

            if (strlen($mes) == 1) {
                $mes = "0" . $mes;
            }

            if ($anni <= self::ANNIMAX && $anni > self::ANNIMIN) {
                $ann = $anni;
            } else {
                $this->msgErrore .= "-> Errore anno esterno ai limiti. ";
                return "";
            }
            
            if ($strTS === 0)
                return $gio . "/" . $mes . "/" . $ann;
            else
                return mktime(0, 0, 0, $mes, $gio, $ann);
        } else {
            $this->msgErrore .= "-> Errore, data non esistente. ";
            return "";
        }
    }

    /**
     * Controlla e formatta una data senza limiti di età
     * 
     * Verifica l'esistenza della data e la restituisce nel formato richiesto
     * 
     * @param int $gio Giorno della data
     * @param int $mes Mese della data
     * @param int $ann Anno della data
     * @param int $strTS Formato di ritorno: 0 per stringa 'gg/mm/aaaa', 1 per timestamp
     * @return string|int Data formattata come stringa o timestamp, stringa vuota in caso di errore
     */
    public function sistemaData($gio, $mes, $ann, $strTS = 0) {
        if (checkdate($mes, $gio, $ann)) {
            if (strlen($gio) == 1) {
                $gio = "0" . $gio;
            }

            if (strlen($mes) == 1) {
                $mes = "0" . $mes;
            }
            
            if ($strTS === 0)
                return $gio . "/" . $mes . "/" . $ann;
            else
                return mktime(0, 0, 0, $mes, $gio, $ann);
        } else {
            $this->msgErrore .= "-> Errore, data non esistente. ";
            return "";
        }
    }

    /**
     * Controlla la validità sintattica di un Codice Fiscale italiano
     * 
     * @param string $CF Codice Fiscale da validare
     * @return string Codice Fiscale in maiuscolo se valido, stringa vuota in caso di errore
     */
    public function controllaCFIT($CF) {
        if (preg_match('/[a-z]{6}[0-9]{2}[abcdehlmprst]{1}[0-9]{2}[a-z]{1}[0-9]{3}[a-z]{1}/i', $CF)) {
            return strtoupper($CF);
        } else {
            $this->msgErrore .= "-> Errore del codice fiscale. ";
            return "";
        }
    }

    /**
     * Controlla la validità sintattica di una Partita IVA italiana
     * 
     * @param string $PI Partita IVA da validare (11 cifre)
     * @return string Partita IVA se valida, stringa vuota in caso di errore
     */
    public function controllaPI($PI) {
        if (preg_match('/^[0-9]{11}$/', $PI)) {
            return strtoupper($PI);
        } else {
            $this->msgErrore .= "-> Errore della partita iva. ";
            return "";
        }
    }

    /**
     * Controlla la validità sintattica di un numero telefonico italiano
     * 
     * @param string $tel Numero di telefono da validare (10 cifre)
     * @return string Numero di telefono se valido, stringa vuota in caso di errore
     */
    public function controllaTel($tel) {
        if (preg_match('/^[0-9]{10}$/', $tel)) {
            return $tel;
        } else {
            $this->msgErrore .= "-> Errore numero telefonico inserito. ";
            return "";
        }
    }

    /**
     * Verifica la sintassi di un indirizzo email
     * 
     * @param string $email Indirizzo email da validare
     * @return string Email in minuscolo e senza accenti se valida, stringa vuota in caso di errore
     */
    public function controllaEmail($email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->eliminaAccentiAllMinuscolo($email);
        } else {
            $this->msgErrore .= "-> Errore sintattico email inserita. ";
            return "";
        }
    }

    /**
     * Controlla se un indirizzo email appartiene a un dominio specifico
     * 
     * @param string $email Indirizzo email da verificare
     * @param string $dominio Dominio da controllare
     * @return bool True se l'email appartiene al dominio, False altrimenti
     */
    public function controlloDominioEmail($email, $dominio) {
        $parti = explode("@", $email);
        if ($parti[1] === $dominio) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Controlla e valida un valore numerico in base al tipo specificato
     * 
     * @param mixed $num Valore da controllare
     * @param int $tipo Tipo di numero: 1=booleano, 2=intero, 3=float
     * @return mixed Valore validato o NULL in caso di errore
     */
    public function controlloNumero($num, $tipo) {
        // Controlla numeri booleani
        if ($tipo === 1) {
            $num = filter_var($num, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            return $num;
        }
        
        // Pulisce e controlla numeri interi
        if ($tipo === 2) {
            $num = filter_var($num, FILTER_SANITIZE_NUMBER_INT);
            if (filter_var($num, FILTER_VALIDATE_INT)) {
                return $num;
            }
        }
        
        // Controlla numeri reali
        if ($tipo === 3) {
            $num = filter_var($num, FILTER_VALIDATE_FLOAT);
            return $num;
        }
        
        return NULL;
    }

    /**
     * Formatta una stringa rimuovendo spazi e convertendo in maiuscolo con gestione accenti
     * 
     * @param string $stringa Stringa da formattare
     * @return string Stringa formattata in maiuscolo senza spazi superflui
     */
    public function sistemaAccentate($stringa) {
        $stringa = trim($stringa);
        $accMin = array('à', 'á', 'è', 'é', 'ì', 'í', 'ò', 'ó', 'ù', 'ú');
        $accMai = array('À', 'Á', 'È', 'É', 'Ì', 'Í', 'Ò', 'Ó', 'Ù', 'Ú');
        return strtoupper(str_replace($accMin, $accMai, $stringa));
    }

    /**
     * Elimina gli accenti e converte tutta la stringa in minuscolo
     * 
     * Utile per la normalizzazione di email prima della memorizzazione
     * 
     * @param string $stringa Stringa da processare
     * @return string Stringa in minuscolo senza accenti
     */
    public function eliminaAccentiAllMinuscolo($stringa) {
        $stringa = trim($stringa);
        $accMin = array('à', 'á', 'è', 'é', 'ì', 'í', 'ò', 'ó', 'ù', 'ú', 'À', 'Á', 'È', 'É', 'Ì', 'Í', 'Ò', 'Ó', 'Ù', 'Ú');
        $accMai = array('a', 'a', 'e', 'e', 'i', 'i', 'o', 'o', 'u', 'u', 'a', 'a', 'e', 'e', 'i', 'i', 'o', 'o', 'u', 'u');
        return strtolower(str_replace($accMin, $accMai, $stringa));
    }

    /**
     * Determina se una persona è maggiorenne o minorenne in base alla data di nascita
     * 
     * @param int $gg Giorno di nascita
     * @param int $mm Mese di nascita
     * @param int $aaaa Anno di nascita
     * @return string 'MAG' se maggiorenne, 'MIN' se minorenne
     */
    public function magMin($gg, $mm, $aaaa) {
        $a = date('Y');
        $m = date('m');
        $g = date('d');
        $aMin = $a - self::MAGG;
        
        if ($aaaa < $aMin) {
            return "MAG";
        } else if ($aaaa == $aMin) {
            if ($mm < $m) {
                return "MAG";
            } else if ($mm == $m) {
                if ($gg <= $g) {
                    return "MAG";
                } else {
                    return "MIN";
                }
            } else {
                return "MIN";
            }
        } else {
            return "MIN";
        }
    }

    /**
     * Crittografa una stringa (tipicamente password) usando l'algoritmo predefinito
     * 
     * @param string $psw Password o stringa da crittografare
     * @return string Stringa crittografata di 60 caratteri
     */
    public function crittografaPsw($psw) {
        $psw = password_hash($psw, PASSWORD_DEFAULT);
        return $psw;
    }

    /**
     * Verifica se una stringa corrisponde alla versione crittografata
     * 
     * @param string $psw Stringa in chiaro da verificare
     * @param string $pswDB Stringa crittografata dal database
     * @return bool True se la stringa corrisponde, False altrimenti
     */
    public function controlloPswCritt($psw, $pswDB) {
        if (password_verify($psw, $pswDB)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Genera un codice OTP (One-Time Password) di lunghezza specificata
     * 
     * @param int $numCaratteri Lunghezza del codice OTP (massimo 32 caratteri)
     * @return string Codice OTP generato
     */
    public function generaOTP($numCaratteri) {
        if ($numCaratteri > 32)
            $numCaratteri = 32;
        $strRandom = md5(microtime());
        return substr($strRandom, 0, $numCaratteri);
    }


/*
valida nomi e cognomi
accetta spazi, apostrofi, trattini "-" e lettere sia minuscole che maiuscole e accentate
*/
/**
 * Valida e normalizza un nome o cognome.
 *
 * - Accetta solo lettere (anche accentate), spazi, apostrofi e trattini.
 * - Rimuove spazi multipli e apostrofi tipografici.
 * - Restituisce la stringa pulita e formattata oppure false se non valida.
 *
 * @param string $input      Nome o cognome da validare
 * @param bool   $maiuscolo  true = capitalizza (Default), false = minuscolo
 * @return string|false      Stringa pulita se valida, false se non valida
 */
function valida_nome(string $input, bool $maiuscolo = true)
{
/* spiegazione parti di codice
^ e $	Inizio e fine della stringa (serve per evitare caratteri extra)
\p{L}	Qualsiasi lettera Unicode (copre lettere accentate e alfabeti latini)
\s	Spazi
'	Apostrofo (')
-	Trattino (-)
[ ... ]+	Uno o più caratteri ammessi
u	Modalità Unicode, indispensabile per accenti
trim()	elimina spazi iniziali/finali
str_replace('’', "'", $input)	uniforma l’apostrofo
preg_replace('/\s+/', ' ', ...)	riduce spazi doppi
preg_match("/^[\p{L}\s'-]+$/u", ...)	valida lettere e simboli ammessi
mb_convert_case()	capitalizza parole in UTF-8
mb_strtolower()	converte in minuscolo UTF-8
*/
    // Rimuove spazi iniziali/finali
    $input = trim($input);

    // Sostituisce apostrofi tipografici (’ -> ')
    $input = str_replace('’', "'", $input);

    // Riduce spazi multipli a singolo spazio
    $input = preg_replace('/\s+/', ' ', $input);

    // Controlla validità con regex Unicode
    if (!preg_match("/^[\p{L}\s'-]+$/u", $input)) {
        return false;
    }

    // Normalizza maiuscole/minuscole
    if ($maiuscolo) {
        // Capitalizza ogni parola, mantiene apostrofi e trattini
        $input = mb_convert_case($input, MB_CASE_TITLE, "UTF-8");
    } else {
        $input = mb_strtolower($input, "UTF-8");
    }

    return $input;
}

/**
 * Sanifica stringhe o array di stringhe per output HTML.
 *
 * - Converte i caratteri speciali in entità HTML sicure (&, <, >, ", ')
 * - Previene attacchi XSS se il contenuto proviene dal database
 *
 * @param string|array|null $data  Stringa o array da sanificare
 * @return string|array|null       Dati sanificati
 */
function sanifica_output($data)
{
/*
ENT_QUOTES	Converte sia apici singoli che doppi
ENT_SUBSTITUTE	Sostituisce caratteri non validi in UTF-8
'UTF-8'	Encoding corretto (essenziale in ambienti multilingua)
*/
    if (is_array($data)) {
        // Se è un array → applica ricorsivamente a ogni elemento
        return array_map('sanifica_output', $data);
    }

    if (is_string($data)) {
        // Converte in entità HTML sicure
        return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // Se è null o altro tipo allora restituisci com’è
    return $data;
}


} // chiusura classeGen