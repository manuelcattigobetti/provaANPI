<?php
include_once('session_manager.php');
include_once('ClasseGen.php');
include_once('Utenti.php');
include_once('dati_accesso.php');

// Protezione pagina: verifica se l'utente è loggato
// Controlla se esiste la sessione email
$email = $_SESSION['email'] ?? '';

// Se l'email non esiste nella sessione, l'utente non è loggato
if (empty($email)) {
	// Mostra un alert JavaScript e reindirizza alla home
	echo '<script>
		alert("Accesso negato! Devi effettuare il login per accedere a questa pagina.");
		window.location.href = "index.html";
	</script>';
	exit(); // Ferma l'esecuzione dello script PHP
}

// L'utente è loggato, procede con il caricamento dei dati
$utente = null;
if ($email) {
	$utenti = new Utenti($datiAccesso);
	$utente = $utenti->leggiUtentePerEmail($email);
}

$cognome = $utente['cognome'] ?? '';
$nome = $utente['nome'] ?? '';
$dob = $utente['dob'] ?? '';
$livello = $utente['livello'] ?? 1;

$magMin = '';
if ($dob) {
	$data = explode('-', $dob); // formato YYYY-MM-DD
	if (count($data) === 3) {
		$gen = new ClasseGen();
		$magMin = $gen->magMin((int)$data[2], (int)$data[1], (int)$data[0]);
	}
}

$livelloTesto = ($livello == 5) ? 'UTENTE AMMINISTRATORE' : 'UTENTE GIOCATORE';
// Logging connessione utente (solo prima volta nella sessione)
if ($utente && empty($_SESSION['logged_user_logged'])) {
	app_log_user($utente, 'CONNESSIONE');
	$_SESSION['logged_user_logged'] = true;
	// Salva snapshot per logout
	$_SESSION['user_snapshot'] = [
		'IDUtente' => $utente['IDUtente'] ?? null,
		'cognome' => $cognome,
		'nome' => $nome,
		'email' => $email,
		'livello' => $livello
	];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Home Utente</title>
	<link rel="stylesheet" href="STYLE/styles.css">

</head>
<body>
	<!-- Barra nera superiore con informazioni utente -->
	<nav class="top-bar">
		<!-- Contenitore informazioni utente disposte in orizzontale -->
		<div class="user-info">
			<!-- Cognome dell'utente con protezione XSS tramite htmlspecialchars -->
			<span><strong>Cognome:</strong> <?= htmlspecialchars($cognome) ?></span>
			
			<!-- Nome dell'utente con protezione XSS -->
			<span><strong>Nome:</strong> <?= htmlspecialchars($nome) ?></span>
			
			<!-- Email dell'utente con protezione XSS -->
			<span><strong>Email:</strong> <?= htmlspecialchars($email) ?></span>
			
			<!-- Età: scritta verde per maggiorenne, rossa per minorenne -->
			<span>
				<?php if ($magMin === 'MAG'): ?>
					<strong class="status-maggiorenne">Maggiorenne</strong>
				<?php elseif ($magMin === 'MIN'): ?>
					<strong class="status-minorenne">Minorenne</strong>
				<?php else: ?>
					<span class="status-eta-nd">-</span>
				<?php endif; ?>
			</span>
			
			<!-- Livello utente (Giocatore o Amministratore) -->
			<span><strong>Livello:</strong> <?= $livelloTesto ?></span>
			
			<!-- Coordinate GPS (se disponibili e affidabili) -->
			<!-- Visualizzate solo se il browser supporta la geolocalizzazione -->
			<span class="gps-coords" id="gps-display">
				<strong>📍</strong> <span id="coords-text">Rilevamento...</span>
			</span>
			
			<!-- Data odierna con giorno della settimana e ora che si aggiorna -->
			<span id="datetime-display" class="datetime-display">
				<!-- JavaScript aggiornerà questo contenuto -->
			</span>
		</div>
		
		<!-- Link per tornare alla home page con icona casetta -->
		<!-- Utilizza emoji Unicode per massima compatibilità cross-browser -->
		<a href="index.html" class="home-link" title="Torna alla Home">🏠</a>
		<!-- Link logout con conferma JavaScript prima di uscire -->
		<a href="#" onclick="confermaLogout(event)" class="home-link" title="Logout">🚪</a>
	</nav>
	
	<!-- Script per rilevamento coordinate GPS e aggiornamento data/ora -->
	<script>
		/**
		 * Funzione per confermare il logout prima di procedere
		 * @param {Event} event - Evento click per prevenire navigazione diretta
		 */
		function confermaLogout(event) {
			event.preventDefault(); // Previene navigazione immediata
			
			// Mostra finestra di conferma
			const conferma = confirm("Sei sicuro di voler uscire?");
			
			// Se l'utente conferma, procede con il logout
			if (conferma) {
				window.location.href = 'logout.php';
			}
			// Se annulla, non fa nulla e rimane sulla pagina
		}
		
	
		/**
		 * Funzione per aggiornare data e ora in tempo reale
		 * Formato: gg-mm-aaaa GiornoSettimana hh:mm
		 */
		function updateDateTime() {
			const now = new Date();
			
			// Estrae giorno, mese, anno
			const day = String(now.getDate()).padStart(2, '0'); // giorno con zero iniziale
			const month = String(now.getMonth() + 1).padStart(2, '0'); // mese (0-11, quindi +1)
			const year = now.getFullYear();
			
			// Array dei giorni della settimana in italiano
			const daysOfWeek = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
			const dayName = daysOfWeek[now.getDay()]; // 0=Domenica, 6=Sabato
			
			// Estrae ore e minuti
			const hours = String(now.getHours()).padStart(2, '0'); // ore con zero iniziale
			const minutes = String(now.getMinutes()).padStart(2, '0'); // minuti con zero iniziale
			
			// Compone la stringa finale: gg-mm-aaaa GiornoSettimana hh:mm
			const dateTimeString = `${day}-${month}-${year} ${dayName} ${hours}:${minutes}`;
			
			// Aggiorna il contenuto dell'elemento HTML
			document.getElementById('datetime-display').textContent = dateTimeString;
		}
		
		// Avvia l'aggiornamento immediato
		updateDateTime();
		
		// Aggiorna ogni 1 secondo (1000ms) per mantenere l'ora sincronizzata
		setInterval(updateDateTime, 1000);
		
		/**
		 * Rilevamento coordinate GPS tramite Geolocation API
		 * Funziona su smartphone (Android/iPhone) e desktop se il browser supporta l'API
		 * La precisione dipende da: GPS attivo, connessione, permessi utente
		 */
		
		// Verifica se il browser supporta la Geolocation API
		if ('geolocation' in navigator) {
			// Mostra il contenitore delle coordinate
			const gpsDisplay = document.getElementById('gps-display');
			const coordsText = document.getElementById('coords-text');
			
			// Opzioni per la richiesta di geolocalizzazione
			const options = {
				enableHighAccuracy: true, // richiede GPS ad alta precisione (migliore su mobile)
				timeout: 10000, // timeout di 10 secondi
				maximumAge: 0 // non usa coordinate in cache, richiede posizione aggiornata
			};
			
			// Funzione chiamata in caso di successo nel rilevamento
			function success(position) {
				// Estrae latitudine e longitudine dalla risposta
				const latitude = position.coords.latitude.toFixed(6); // 6 decimali = precisione ~10cm
				const longitude = position.coords.longitude.toFixed(6);
				const accuracy = position.coords.accuracy.toFixed(0); // precisione in metri
				
				// Aggiorna il testo con le coordinate e la precisione, con label chiare
				coordsText.textContent = `Latitudine: ${latitude}, Longitudine: ${longitude} (±${accuracy}m)`;
				
				// Mostra il contenitore
				gpsDisplay.style.display = 'inline-flex';
				
				// Log per debug (solo in console sviluppatore)
				console.log('GPS:', {
					lat: latitude,
					lng: longitude,
					accuracy: accuracy + 'm',
					timestamp: new Date(position.timestamp)
				});
			}
			
			// Funzione chiamata in caso di errore nel rilevamento
			function error(err) {
				console.warn('Errore GPS:', err.message);
				
				// Mostra messaggio di errore appropriato
				if (err.code === 1) {
					// Permesso negato dall'utente
					coordsText.textContent = 'GPS negato';
				} else if (err.code === 2) {
					// Posizione non disponibile
					coordsText.textContent = 'GPS non disponibile';
				} else if (err.code === 3) {
					// Timeout
					coordsText.textContent = 'GPS timeout';
				}
				
				// Mostra comunque il messaggio (opzionale, puoi commentare per nascondere errori)
				gpsDisplay.style.display = 'inline-flex';
				
				// Nasconde dopo 3 secondi in caso di errore
				setTimeout(() => {
					gpsDisplay.style.display = 'none';
				}, 3000);
			}
			
			// Avvia il rilevamento della posizione
			navigator.geolocation.getCurrentPosition(success, error, options);
			
			// Opzionale: aggiorna periodicamente la posizione ogni 30 secondi
			// Decommenta le righe sotto se vuoi aggiornamento continuo
			/*
			setInterval(() => {
				navigator.geolocation.getCurrentPosition(success, error, options);
			}, 30000);
			*/
		}
	</script>
	<div class="container">
		<div class="form-card" role="region">
			<h1 class="form-title">HOME PAGE</h1>
			<!-- ...contenuto della home... -->
		</div>
	</div>
</body>
</html>