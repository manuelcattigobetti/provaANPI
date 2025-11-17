<?php
include_once('session_manager.php');
include_once('dati_accesso.php');
include_once 'Utenti.php';
include_once 'ClasseGen.php';

// Verifica se l'email è presente nella sessione
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Accesso non autorizzato, l'email non è presente nella sessione. Per favore, effettua il login.');</script>";
    header('Refresh:5; URL=login1.php');
    exit();
}

$email = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <!-- lang="it": imposta la lingua del documento per accessibilità e SEO -->
    <meta charset="UTF-8"> <!-- set di caratteri UTF-8 permette lettere accentate italiane -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Responsività su dispositivi mobili -->
    <title>Form Utente</title> <!-- Titolo mostrato nella scheda del browser -->
    <link rel="stylesheet" href="STYLE/styles.css"> <!-- Collega il foglio di stile principale -->
</head>
<body>
    <!-- .container: wrapper che centra verticalmente e orizzontalmente il contenuto -->
    <div class="container">
        <!-- .form-card: card visiva con bordo e ombra; role="region" per annunciare area distinta, aria-labelledby lega l'heading -->
        <div class="form-card" role="region" aria-labelledby="form-title">
            <!-- id="form-title": usato da aria-labelledby per associare il titolo all'area -->
            <h2 id="form-title" class="form-title">Inserisci i tuoi dati</h2>

            <!-- form: action punta allo script di inserimento, method POST per inviare dati -->
            <!-- novalidate: disabilita la validazione HTML5 automatica (si può gestire lato server o personalizzata) -->
            <form action="ins_utente.php" method="POST" novalidate>
                <!-- .form-group: blocco logico che raggruppa label, input e testo di aiuto -->
                <div class="form-group">
                    <!-- label for="cognome": associa etichetta al campo input con id cognome -->
                    <label for="cognome">Cognome</label>
                    <!-- type="text": campo testuale -->
                    <!-- id/name: identificatore nel DOM e nome chiave nel POST -->
                    <!-- required: obbliga compilazione -->
                    <!-- autocomplete="family-name": suggerimenti del browser per cognomi -->
                    <!-- placeholder: testo guida iniziale -->
                    <input type="text" id="cognome" name="cognome" required autocomplete="family-name" placeholder="Es. Rossi">
                    <!-- .help-text: testo descrittivo secondario -->
                    <small class="help-text">Campo obbligatorio</small>
                </div>

                <div class="form-group">
                    <label for="nome">Nome</label>
                    <!-- autocomplete="given-name": suggerimenti per nome proprio -->
                    <input type="text" id="nome" name="nome" required autocomplete="given-name" placeholder="Es. Mario">
                    <small class="help-text">Campo obbligatorio</small>
                </div>

                <div class="form-group">
                    <label for="dob">Data di nascita</label>
                    <!-- type="date": selettore data (il formato dipende dal browser) -->
                    <!-- autocomplete="bday": indica che va inserita la data di nascita completa -->
                    <input type="date" id="dob" name="dob" required autocomplete="bday">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <!-- type="email": valida formato email a livello di input -->
                    <!-- value="<?php echo htmlspecialchars($email); ?>": precompila con valore sicuro dalla sessione -->
                    <!-- readonly: impedisce modifica utente, ma invia valore nel POST -->
                    <!-- class="readonly": applica stile differenziato -->
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly class="readonly">
                    <small class="help-text">Recuperata dalla sessione</small>
                </div>

                <!-- button type="submit": invia il form al server; classi .btn .btn-primary per stile -->
                <button type="submit" class="btn btn-primary">Memorizza l'utente</button>
            </form>
        </div>
    </div>
</body>
</html>