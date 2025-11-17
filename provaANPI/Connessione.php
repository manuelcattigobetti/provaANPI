<?php
/////////////////
//             //
//    Romei    //
//             //
/////////////////
class Connessione{
// attributi ************************************************************
    protected $conn; // variabile accessibile dalle classi figlie
    protected $msgErrore = ""; // memorizza in una stringa tutti gli errori dei metodi


// metodi ***************************************************************
/*
permette di visualizzare i messaggi restituiti dai metodi della classe
return elenco: stringa con tutti gli errori
*/
public function msgErr(){
        return $this->msgErrore;
    }

/*
permette di cancellare i messaggi d'errore della classe
return: nessuno
*/
public function cancellaMsgErr(){
        $this->msgErrore = "";
    }

/** 
*apre connessione con database per scrivere, leggere, modificare e cancellare record e campi
* @param : array con dati di connessione del database utilizzato
* aggiorna attributo 'connOOP' con dati di connessione
* @return: nessuno
*/
public function connessione($datiAccesso){
    #var_dump($datiAccesso);
    $this->conn = new mysqli($datiAccesso[0], $datiAccesso[1], $datiAccesso[2], $datiAccesso[3]);
    if ($this->conn->connect_error) 
    {
        die('Connessione mysqli OOP fallita: (' . $this->conn->connect_errno . ') '
            . $this->conn->connect_error);
    } else {
        #echo '<br>-- OOP -- ' . $this->connOOP->host_info . "<br>"; // da decommentare per controllare se la connessione con database funziona
        $this->conn->set_charset("utf8"); // impostazione set di caratteri
    }
} 

/*
chiude la connessione con il database
return: nessuno
*/
public function chiudiConn(){
    $this->conn->close();  // chiude  connessione con il database
}

} // fine classe Connessione
?>