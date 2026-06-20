<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

// Envoi email via Gmail SMTP (smtp_gmail.inc.php)
// Sur OVH : remplacer par include('brevo_api.inc.php') et EnvoyerEmailBrevo()
include('smtp_gmail.inc.php');

// Empecher l'acces si deja connecte
if (isset($Joueur) && is_object($Joueur)) {
	header('Location: index.php?Page=accueil');
	return;
}

$MessageConfirmation = "";
$MessageErreur = "";
$Identifiant = "";

// Traitement du formulaire
if (isset($_POST['DemandeReset']) && $_POST['DemandeReset'] == 'o') {

	// Nettoyage opportuniste des anciens tokens
	NettoyerTokensExpires();

	$Identifiant = isset($_POST['Identifiant']) ? trim($_POST['Identifiant']) : '';

	// Validation du champ
	if (!$Identifiant) {
		$MessageErreur = "Veuillez saisir votre pseudonyme ou votre adresse email.";
	} else {

		// Rechercher le membre
		$Membre = RechercherMembreParIdentifiant($Identifiant);

		if ($Membre && $Membre['Email']) {

			$Pseudo = $Membre['Pseudonyme'];
			$Email = $Membre['Email'];

			// Verifier le nombre de demandes recentes (anti-spam)
			$nbDemandes = CompterDemandesRecentes($Pseudo, 1);

			if ($nbDemandes >= $LimiteDemandesParHeure) {
				// Limiter les demandes mais ne pas reveler qu'on bloque
				// Message generique pour eviter l'enumeration
				$MessageConfirmation = "Si votre compte existe et possede une adresse email valide, vous recevrez un lien de reinitialisation dans quelques minutes.";
			} else {

				// Generer le token securise
				$Token = GenererTokenReset($Pseudo);

				// Dates
				$DateCreation = date('Y-m-d H:i:s');
				$timestampExpiration = time() + ($DureeValiditeTokenHeures * 3600);
				$DateExpiration = date('Y-m-d H:i:s', $timestampExpiration);

				// IP du demandeur
				$IpDemande = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
				$IpDemande = mysql_real_escape_string($IpDemande);
				$PseudoEscaped = mysql_real_escape_string($Pseudo);

				// Inserer le token en base
				$query = "INSERT INTO NPVB_PasswordReset
				          (Token, Pseudonyme, DateCreation, DateExpiration, Utilise, IpDemande)
				          VALUES
				          ('$Token', '$PseudoEscaped', '$DateCreation', '$DateExpiration', 'n', '$IpDemande')";

				if (mysql_query($query, $sdblink)) {

					// Preparer l'email avec substitution des variables
					$CorpsEmail = str_replace('$Token', $Token, $CorpsMailDemandeReset);
					$CorpsEmail = str_replace('$DureeValiditeTokenHeures', $DureeValiditeTokenHeures, $CorpsEmail);

					// Envoyer l'email via Brevo API
					if (EnvoyerEmailGmail($Email, $SujetMailDemandeReset, $CorpsEmail, $config['smtp_from'], $config['club_sigle'])) {
						// Succes - Message generique pour ne pas reveler l'existence du compte
						$MessageConfirmation = "Si votre compte existe et possede une adresse email valide, vous recevrez un lien de reinitialisation dans quelques minutes.";
					} else {
						// Erreur d'envoi - Ne pas reveler l'erreur technique pour des raisons de securite
						$MessageConfirmation = "Si votre compte existe et possede une adresse email valide, vous recevrez un lien de reinitialisation dans quelques minutes.";
					}
				} else {
					// Erreur BDD - Message generique
					$MessageConfirmation = "Si votre compte existe et possede une adresse email valide, vous recevrez un lien de reinitialisation dans quelques minutes.";
				}
			}
		} else {
			// Compte introuvable ou sans email - Message generique pour eviter l'enumeration
			$MessageConfirmation = "Si votre compte existe et possede une adresse email valide, vous recevrez un lien de reinitialisation dans quelques minutes.";
		}

		// Effacer le champ apres soumission pour eviter la resoumission
		$Identifiant = "";
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<title><?= htmlspecialchars($config['club_sigle']) ?> - Mot de passe oubli&eacute;</title>
	<link rel="stylesheet" type="text/css" href="Feuilles de style/motdepasseoublie.css" />
</head>
<body>

<div id="conteneur">

	<div id="header">
		<h1><?= htmlspecialchars($config['club_nom']) ?></h1>
		<h2>R&eacute;initialisation de mot de passe</h2>
	</div>

	<div id="contenu">

		<?
		if ($MessageConfirmation) {
			?>
			<div class="message-confirmation">
				<h3>Demande enregistr&eacute;e</h3>
				<p><?= $MessageConfirmation ?></p>
				<p class="note">
					<strong>Important :</strong> V&eacute;rifiez &eacute;galement votre dossier de courriers ind&eacute;sirables (spam).
				</p>
				<p>
					<a href="index.php" class="bouton">Retour &agrave; la page de connexion</a>
				</p>
			</div>
			<?
		} else {
			?>
			<div class="explication">
				<p>
					Vous avez oubli&eacute; votre mot de passe ? Pas de probl&egrave;me !
				</p>
				<p>
					Saisissez votre <strong>pseudonyme</strong> ou votre <strong>adresse email</strong> ci-dessous.
					Vous recevrez un lien pour r&eacute;initialiser votre mot de passe.
				</p>
			</div>

			<? if ($MessageErreur) { ?>
			<div class="message-erreur">
				<?= $MessageErreur ?>
			</div>
			<? } ?>

			<form method="post" action="index.php?Page=motdepasseoublie" class="formulaire-reset">
				<input type="hidden" name="DemandeReset" value="o" />

				<div class="champ">
					<label for="Identifiant">Pseudonyme ou Email :</label>
					<input
						type="text"
						id="Identifiant"
						name="Identifiant"
						value="<?= htmlspecialchars($Identifiant) ?>"
						size="40"
						maxlength="80"
						class="input-texte"
					/>
				</div>

				<div class="actions">
					<input type="submit" value="Recevoir le lien de r&eacute;initialisation" class="bouton-principal" />
					<a href="index.php" class="lien-retour">Retour &agrave; la page de connexion</a>
				</div>
			</form>

			<div class="aide">
				<h3>Besoin d'aide ?</h3>
				<ul>
					<li>Si vous n'avez pas renseign&eacute; d'adresse email, contactez un administrateur du club.</li>
					<li>Le lien de r&eacute;initialisation est valable pendant <strong><?= $DureeValiditeTokenHeures ?> heures</strong>.</li>
					<li>Pour toute question : <a href="mailto:<?= htmlspecialchars($config['club_email']) ?>"><?= htmlspecialchars($config['club_email']) ?></a></li>
				</ul>
			</div>
			<?
		}
		?>

	</div>

	<div id="footer">
		<p>
			&copy; 2026 <?= htmlspecialchars($config['club_nom']) ?> -
			<a href="index.php">Retour &agrave; l'accueil</a>
		</p>
	</div>

</div>

</body>
</html>
