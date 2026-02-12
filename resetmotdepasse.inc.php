<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

// Note: La verification de connexion est geree dans index.php avant l'envoi de contenu

$MessageConfirmation = "";
$MessageErreur = "";
$TokenValide = false;
$PseudonymeAssocie = "";

// Recuperer le token depuis l'URL
$Token = isset($_GET['Token']) ? trim($_GET['Token']) : '';

// Verifier le token
if ($Token) {
	$PseudonymeAssocie = VerifierTokenReset($Token);
	if ($PseudonymeAssocie) {
		$TokenValide = true;
	} else {
		$MessageErreur = "Ce lien de reinitialisation est invalide ou a expire. Les liens sont valables pendant " . $DureeValiditeTokenHeures . " heures.";
	}
} else {
	$MessageErreur = "Aucun token de reinitialisation fourni.";
}

// Traitement du formulaire de changement de mot de passe
if ($TokenValide && isset($_POST['ChangerMotDePasse']) && $_POST['ChangerMotDePasse'] == 'o') {

	$NouveauMotDePasse = isset($_POST['NouveauMotDePasse']) ? $_POST['NouveauMotDePasse'] : '';
	$ConfirmationMotDePasse = isset($_POST['ConfirmationMotDePasse']) ? $_POST['ConfirmationMotDePasse'] : '';

	// Validations
	if (!$NouveauMotDePasse) {
		$MessageErreur = "Le nouveau mot de passe est obligatoire.";
	} elseif (strlen($NouveauMotDePasse) < 4) {
		$MessageErreur = "Le mot de passe doit contenir au moins 4 caracteres.";
	} elseif (preg_match("/[^a-zA-Z0-9_*+()[\]=-]/", $NouveauMotDePasse)) {
		$MessageErreur = "Le format du mot de passe est incorrect. Caracteres autorises : lettres, chiffres, _ * + - = ( ) [ ]";
	} elseif ($NouveauMotDePasse !== $ConfirmationMotDePasse) {
		$MessageErreur = "Les deux mots de passe ne correspondent pas.";
	} else {

		// Verifier a nouveau le token (securite supplementaire)
		$PseudoVerif = VerifierTokenReset($Token);

		if (!$PseudoVerif) {
			$MessageErreur = "Le token a expire ou a deja ete utilise. Veuillez refaire une demande de reinitialisation.";
		} else {

			// Proteger les donnees
			$PseudoEscaped = mysql_real_escape_string($PseudoVerif);
			$MotDePasseEscaped = mysql_real_escape_string($NouveauMotDePasse);

			// Mettre a jour le mot de passe
			$query = "UPDATE NPVB_Joueurs
			          SET Password=OLD_PASSWORD('$MotDePasseEscaped')
			          WHERE Pseudonyme='$PseudoEscaped'
			          AND Etat='V'";

			if (mysql_query($query, $sdblink)) {

				// Marquer le token comme utilise
				MarquerTokenUtilise($Token);


				// Message de succes
				$MessageConfirmation = "Votre mot de passe a ete modifie avec succes !";
				$TokenValide = false; // Pour afficher la page de succes

			} else {
				$MessageErreur = "Erreur lors de la mise a jour du mot de passe. Veuillez reessayer ou contacter un administrateur.";
			}
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<title>NPVB - Nouveau mot de passe</title>
	<link rel="stylesheet" type="text/css" href="Feuilles de style/motdepasseoublie.css" />
</head>
<body>

<div id="conteneur">

	<div id="header">
		<h1>Nantes Plaisir du Volley Ball</h1>
		<h2>D&eacute;finir un nouveau mot de passe</h2>
	</div>

	<div id="contenu">

		<?
		if ($MessageConfirmation) {
			// Succes : mot de passe change
			?>
			<div class="message-confirmation">
				<h3>Mot de passe modifi&eacute; !</h3>
				<p><?= $MessageConfirmation ?></p>
				<p>
					</p>
				<p>
					Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
				</p>
				<p>
					<a href="index.php" class="bouton">Se connecter</a>
				</p>
			</div>
			<?
		} elseif (!$TokenValide) {
			// Token invalide ou expire
			?>
			<div class="message-erreur-important">
				<h3>Lien invalide</h3>
				<p><?= $MessageErreur ?></p>
				<p>
					<strong>Contactez un administrateur sur <a href="mailto:nantespvb@gmail.com">nantespvb@gmail.com</a> pour obtenir un nouveau lien.</strong><br/>
					<a href="index.php" class="lien-retour">Retour &agrave; la connexion</a>
				</p>
			</div>
			<?
		} else {
			// Formulaire de changement de mot de passe
			?>
			<div class="explication">
				<p>
					Compte : <strong><?= htmlspecialchars($PseudonymeAssocie) ?></strong>
				</p>
				<p>
					D&eacute;finissez votre nouveau mot de passe ci-dessous.
				</p>
			</div>

			<? if ($MessageErreur) { ?>
			<div class="message-erreur">
				<?= $MessageErreur ?>
			</div>
			<? } ?>

			<form method="post" action="index.php?Page=resetmotdepasse&Token=<?= htmlspecialchars($Token) ?>" class="formulaire-reset">
				<input type="hidden" name="ChangerMotDePasse" value="o" />

				<div class="champ">
					<label for="NouveauMotDePasse">Nouveau mot de passe :</label>
					<input
						type="password"
						id="NouveauMotDePasse"
						name="NouveauMotDePasse"
						size="30"
						maxlength="16"
						class="input-texte"
					/>
					<div class="aide-champ">
						Minimum 4 caracteres. Caracteres autorises : lettres, chiffres, _ * + - = ( ) [ ]
					</div>
				</div>

				<div class="champ">
					<label for="ConfirmationMotDePasse">Confirmer le mot de passe :</label>
					<input
						type="password"
						id="ConfirmationMotDePasse"
						name="ConfirmationMotDePasse"
						size="30"
						maxlength="16"
						class="input-texte"
					/>
				</div>

				<div class="actions">
					<input type="submit" value="Changer mon mot de passe" class="bouton-principal" />
					<a href="index.php" class="lien-retour">Annuler</a>
				</div>
			</form>

			<div class="aide">
				<h3>Conseils de s&eacute;curit&eacute;</h3>
				<ul>
					<li>Choisissez un mot de passe difficile &agrave; deviner</li>
					<li>Ne partagez jamais votre mot de passe</li>
					<li>Vous pourrez le modifier &agrave; tout moment depuis votre profil</li>
				</ul>
			</div>
			<?
		}
		?>

	</div>

	<div id="footer">
		<p>
			&copy; 2026 Nantes Plaisir du Volley Ball -
			<a href="index.php">Retour &agrave; l'accueil</a>
		</p>
	</div>

</div>

</body>
</html>
