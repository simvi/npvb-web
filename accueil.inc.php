<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

// ============================================================
// Contenu éditable de la page d'accueil (admins DieuToutPuissant)
// ============================================================
$estAdminAccueil = (isset($Joueur) && is_object($Joueur) && $Joueur->DieuToutPuissant == "o");

// Charge le contenu enregistré pour une clé, sinon retourne le texte par défaut
if (!function_exists('getContenuAccueil')) {
	function getContenuAccueil($cle, $defaut, $sdblink) {
		$res = mysql_query("SELECT contenu FROM NPVB_Contenu WHERE cle='".mysql_real_escape_string($cle, $sdblink)."'", $sdblink);
		if ($res && mysql_num_rows($res) > 0) {
			$row = mysql_fetch_object($res);
			return $row->contenu;
		}
		return $defaut;
	}
}

// Affiche une zone de texte, avec crayon + éditeur visuel si admin
if (!function_exists('rendreZoneAccueil')) {
	function rendreZoneAccueil($cle, $contenu, $estAdmin) {
		global $config;
		$id = htmlspecialchars($cle, ENT_QUOTES);
		echo '<div class="zone-accueil">';
		if ($estAdmin) {
			echo '<button type="button" class="crayon-accueil" onclick="editerAccueil(\''.$id.'\')" title="Modifier ce texte">&#9998;</button>';
			echo '<div class="editeur-accueil" id="editeur_'.$id.'" style="display:none">'
				.'<div class="editeur-toolbar">'
				.'<button type="button" onmousedown="return false" onclick="cmdAccueil(\'bold\')" title="Gras"><b>G</b></button>'
				.'<button type="button" onmousedown="return false" onclick="cmdAccueil(\'italic\')" title="Italique"><i>I</i></button>'
				.'<button type="button" onmousedown="return false" onclick="cmdAccueil(\'underline\')" title="Souligné"><u>S</u></button>'
				.'<span class="tb-sep"></span>'
				.'<button type="button" onmousedown="return false" onclick="cmdAccueil(\'justifyLeft\')" title="Aligner à gauche">&#8676;</button>'
				.'<button type="button" onmousedown="return false" onclick="cmdAccueil(\'justifyCenter\')" title="Centrer">&#8803;</button>'
				.'<button type="button" onmousedown="return false" onclick="cmdAccueil(\'justifyRight\')" title="Aligner à droite">&#8677;</button>'
				.'<span class="tb-sep"></span>'
				.'<button type="button" class="tb-swatch" style="background:#000000" onmousedown="return false" onclick="cmdAccueil(\'foreColor\',\'#000000\')" title="Noir"></button>'
				.'<button type="button" class="tb-swatch" style="background:'.$config['couleur_primaire'].'" onmousedown="return false" onclick="cmdAccueil(\'foreColor\',\''.$config['couleur_primaire'].'\')" title="Primaire"></button>'
				.'<button type="button" class="tb-swatch" style="background:'.$config['couleur_secondaire'].'" onmousedown="return false" onclick="cmdAccueil(\'foreColor\',\''.$config['couleur_secondaire'].'\')" title="Secondaire"></button>'
				.'<button type="button" class="tb-swatch" style="background:'.$config['couleur_danger'].'" onmousedown="return false" onclick="cmdAccueil(\'foreColor\',\''.$config['couleur_danger'].'\')" title="Danger"></button>'
				.'<button type="button" class="tb-swatch" style="background:'.$config['couleur_succes'].'" onmousedown="return false" onclick="cmdAccueil(\'foreColor\',\''.$config['couleur_succes'].'\')" title="Succès"></button>'
				.'<button type="button" class="tb-swatch" style="background:'.$config['couleur_alerte'].'" onmousedown="return false" onclick="cmdAccueil(\'foreColor\',\''.$config['couleur_alerte'].'\')" title="Alerte"></button>'
				.'<button type="button" class="tb-swatch" style="background:'.$config['couleur_accent'].'" onmousedown="return false" onclick="cmdAccueil(\'foreColor\',\''.$config['couleur_accent'].'\')" title="Accent"></button>'
				.'<button type="button" class="tb-swatch" style="background:'.$config['couleur_texte'].'" onmousedown="return false" onclick="cmdAccueil(\'foreColor\',\''.$config['couleur_texte'].'\')" title="Texte"></button>'
				.'<button type="button" class="tb-swatch tb-swatch-reset" onmousedown="return false" onclick="document.execCommand(\'removeFormat\',false,null)" title="Supprimer la couleur">&#10006;</button>'
				.'<span class="tb-sep"></span>'
				.'<button type="button" onmousedown="return false" onclick="cmdAccueil(\'formatBlock\',\'h3\')" title="Titre">Titre</button>'
				.'<button type="button" onmousedown="return false" onclick="cmdAccueil(\'formatBlock\',\'p\')" title="Paragraphe">Texte</button>'
				.'<button type="button" onmousedown="return false" onclick="cmdAccueil(\'insertUnorderedList\')" title="Liste">&bull; Liste</button>'
				.'<button type="button" onmousedown="return false" onclick="lienAccueil()" title="Insérer un lien">Lien</button>'
				.'<button type="button" onmousedown="return false" onclick="imageAccueil()" title="Insérer une image">&#128247;</button>'
				.'<input type="file" id="tb-file-img" accept="image/*" style="display:none" onchange="uploadImageAccueil(this)">'
				.'</div>'
				.'<form method="post" action="" onsubmit="return avantSauvegardeAccueil(\''.$id.'\')">'
				.'<input type="hidden" name="Page" value="accueil" />'
				.'<input type="hidden" name="Action" value="SauvegardeAccueil" />'
				.'<input type="hidden" name="CleContenu" value="'.$id.'" />'
				.'<input type="hidden" name="ContenuAccueil" id="hidden_'.$id.'" />'
				.'<button type="submit" class="Action">Sauvegarder</button> '
				.'<button type="button" class="Bouton Annule" onclick="location.reload()">Annuler</button>'
				.'</form>'
				.'</div>';
		}
		echo '<div class="contenu-accueil" id="contenu_'.$id.'">'.$contenu.'</div>';
		echo '</div>';
	}
}

// Traitement de la sauvegarde (réservé aux admins)
if ($estAdminAccueil && isset($_POST['Action']) && $_POST['Action'] == 'SauvegardeAccueil') {
	$clesValides = array('accueil_visiteur', 'accueil_membre');
	$cleMaj = isset($_POST['CleContenu']) ? $_POST['CleContenu'] : '';
	// Contenu HTML BRUT depuis $_POST (et non la variable extraite, qui est filtrée)
	$contenuMaj = isset($_POST['ContenuAccueil']) ? $_POST['ContenuAccueil'] : '';
	// Sécurité : on retire les balises script et les gestionnaires d'évènements onX=
	$contenuMaj = preg_replace('#<script.*?>.*?</script>#is', '', $contenuMaj);
	$contenuMaj = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\')#i', '', $contenuMaj);
	if (in_array($cleMaj, $clesValides) && trim(strip_tags($contenuMaj)) !== '') {
		$cSql = mysql_real_escape_string($contenuMaj, $sdblink);
		$kSql = mysql_real_escape_string($cleMaj, $sdblink);
		$par  = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);
		mysql_query("INSERT INTO NPVB_Contenu (cle, contenu, updated_at, updated_by) VALUES ('$kSql', '$cSql', NOW(), '$par') ON DUPLICATE KEY UPDATE contenu='$cSql', updated_at=NOW(), updated_by='$par'", $sdblink);
	}
}
?>

<table id="Accueil">
<?
if ($ErreurDonnees["Login"]){print("\t<tr>\n\t\t<td><p class=\"ModifError\">".$ErreurDonnees["Login"]."</p></td>\n\t</tr>\n");}
?>
	<tr>
		<td>

<?php
// ============================================================
// Affichage des messages actifs de la page d'accueil
// UNIQUEMENT pour les membres connectés avec pagination
// ============================================================
if (isset($Joueur) && is_object($Joueur)) {
	// Pagination - 2 messages par page
	$messages_par_page = 2;
	$page_msg = isset($_GET['PageMsg']) ? (int)$_GET['PageMsg'] : 1;
	if ($page_msg < 1) $page_msg = 1;

	$offset = ($page_msg - 1) * $messages_par_page;

	// Compter le nombre total de messages actifs
	$query_count = "SELECT COUNT(*) as total FROM NPVB_Messages WHERE is_active = 1";
	$result_count = mysql_query($query_count, $sdblink);
	$total_messages = 0;
	if ($result_count) {
		$row_count = mysql_fetch_object($result_count);
		$total_messages = (int)$row_count->total;
	}

	$total_pages = ceil($total_messages / $messages_par_page);

	// Récupérer les messages de la page courante
	$query_messages = "SELECT * FROM NPVB_Messages WHERE is_active = 1 ORDER BY created_at DESC LIMIT $offset, $messages_par_page";
	$result_messages = mysql_query($query_messages, $sdblink);

	if ($result_messages && mysql_num_rows($result_messages) > 0) {
?>
	<div style="background: #f5f5f5; border: 2px solid #cccccc; border-radius: 5px; padding: 15px; margin-bottom: 20px;">
		<h3 style="margin-top: 0; color: #333333; border-bottom: 2px solid #999999; padding-bottom: 10px;">
			Actualités
		</h3>
<?php
		while ($message = mysql_fetch_object($result_messages)) {
?>
		<div style="margin-bottom: 15px; padding: 10px; background: white; border-left: 4px solid #999999;">
<?php if ($message->title): ?>
			<h4 style="margin: 0 0 10px 0; color: #003366;">
				<?php echo htmlspecialchars($message->title, ENT_QUOTES, 'ISO-8859-1'); ?>
			</h4>
<?php endif; ?>
			<div style="line-height: 1.6;">
				<?php echo linkify($message->content); ?>
			</div>
			<div style="font-size: 11px; color: #666; margin-top: 5px; font-style: italic;">
				Publié le <?php echo date('d/m/Y', strtotime($message->created_at)); ?>
			</div>
		</div>
<?php
		}

		// Affichage de la pagination si nécessaire
		if ($total_pages > 1) {
?>
		<div style="text-align: center; margin-top: 15px; padding-top: 10px; border-top: 1px solid #999999;">
			<div style="display: inline-block;">
<?php
			// Lien page précédente
			if ($page_msg > 1) {
?>
				<a href="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'], ENT_QUOTES); ?>?Page=accueil&amp;PageMsg=<?php echo ($page_msg - 1); ?>"
				   style="padding: 5px 10px; margin: 0 5px; background: #fff; border: 1px solid #999999; border-radius: 3px; text-decoration: none; color: #666666;">
					&laquo; Précédent
				</a>
<?php
			}
?>
				<span style="padding: 5px 10px; margin: 0 5px; color: #666;">
					Page <?php echo $page_msg; ?> sur <?php echo $total_pages; ?>
				</span>
<?php
			// Lien page suivante
			if ($page_msg < $total_pages) {
?>
				<a href="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'], ENT_QUOTES); ?>?Page=accueil&amp;PageMsg=<?php echo ($page_msg + 1); ?>"
				   style="padding: 5px 10px; margin: 0 5px; background: #fff; border: 1px solid #999999; border-radius: 3px; text-decoration: none; color: #666666;">
					Suivant &raquo;
				</a>
<?php
			}
?>
			</div>
		</div>
<?php
		}
?>
	</div>
<?php
	}
}
?>

<?php
// Capture le texte visiteur par défaut (toujours, pour permettre l'édition
// par un admin depuis sa vue connectée)
ob_start();
?>
		<p><em>Mise à jour : 1er Mai 2026</em></p>
		<p align="center">Bienvenue à tous les sportifs !<br>
		<p align="center"><em>Le NPVB est un club de volley loisirs dont les mots d'ordre principaux sont</em>
<p align="center"><em>« détente, plaisir et progrès collectif ».</em><br><br>

		<h3>Présentation générale</h3>
		<p>Historiquement situé dans l'Est nantais, le club ouvre ses portes à toute personne <strong>majeure maîtrisant les règles et gestes de base</strong> et désirant jouer au volleyball dans un cadre loisir mais sportif.
		</p>
		<p>Nous disposons actuellement de 4 créneaux d'entraînement hebdomadaires :
		<ul>
			<li>Lundi, Mercredi et Jeudi de 20 h à 22 h au <a href="https://www.google.com/maps/d/u/0/viewer?mid=1beBtdHzJw2FiLivhUvttzyMPtulFTew6&ll=47.23101189185592%2C-1.5257359986317987&z=15" target="_blank">gymnase Noé Lambert</a></strong> (boulevard des Poilus)
			</li>
			<li>Mardi de 21 h à 23 h au <a href="https://www.google.com/maps/d/u/0/viewer?mid=1beBtdHzJw2FiLivhUvttzyMPtulFTew6&ll=47.23796131997372%2C-1.509853378467405&z=15" target="_blank">gymnase Bottière-Chénaie</a></strong> (route de Sainte Luce - Tramway L1, arrêt Souillarderie)
			</li>
			
			<br>Ces séances de progrès se décomposent en 3 phases : échauffement, travail de technique individuelle ou collective puis petits matchs.
		</ul>
		</p>

		<p>Pour ceux qui aiment la compétition loisir, 11 équipes sont engagées dans les championnats détente de Loire-Atlantique (plus de 1000 licenciés) :</p>
		
		<ul>
		 <li>2 équipes mixtes participent au <strong>championnat Ufolep</strong> organisé par <a href="https://www.ufolep44.com/activites-sportives/volley-ball" target="_blank">le volley-ball à l'UFOLEP 44</a></li>
		 <li>7 équipes mixtes et 2 équipes féminines participent aux <strong>championnats Competlib</strong> organisés par le <a href="https://www.comite44volleyball.org/" target="_blank">Comité Départemental 44 de Volley-Ball</a></li>
		</ul>

		<p>Les matchs se déroulent en semaine (aucun matchs le week-end), à la fréquence d'une fois par semaine pour les équipes mixtes en Competlib, d'une fois toutes les deux semaines pour les équipes en Ufolep et d'une fois par mois pour les équipes féminines en Competlib.</p>

<!--
		<p>Le NPVB organise également chaque année au printemps son Tournoi Green Volley. Pour plus d'infos, n'hésitez pas à en discuter avec les membres du codir, ou envoyez-nous un mail ^^</p>
-->
<!--
		<br />
		<p align="center"><strong>ATTENTION! </strong>
		<a <blink><strong>LE CLUB EST COMPLET POUR LA SAISON 2025-2026 </strong></blink></a>
		<strong> !ATTENTION</strong></p>
-->
		<br />
		<h3>Note à l'attention des personnes souhaitant nous rejoindre</h3>
		<p>Vous êtes très nombreuses et nombreux à nous solliciter chaque année et nous ne pouvons malheureusement pas accepter tout le monde. <strong>Nous ne faisons pas de recrutement en cours d’année</strong> : si vous souhaitez nous rejoindre pour la saison prochaine, merci de nous envoyer un mail à <a href="mailto:nantespvb@gmail.com">l’adresse de messagerie du club</a> où nous collectons vos demandes pour vous inviter, en fonction des places disponibles, aux séances d’essai qui se déroulent généralement fin Août, début Septembre.</p>

<p>Nous vous rappelons que nous sommes un club loisir et que <strong>nous ne dispensons pas de cours</strong> (nous n’avons pas d’entraîneurs). Il est donc nécessaire d’avoir <strong>déjà pratiqué le volley-ball</strong> et de <strong>maîtriser les gestes de base</strong> (passe, manchette, attaque, bloc et service) pour pouvoir nous rejoindre. <strong>Nous ne prenons pas non plus les mineurs</strong> : vous trouverez l’ensemble des clubs formateurs sur le site du <a href="https://www.ffvbbeach.org/ffvbapp/adressier/rech_aff.php?ws_new_ligue=0&ws_new_comit=044&ws_list_dep=44&id_club=" target="_blank">comité départemental FFVB</a> ou sur le site de <a href="https://www.ufolep44.com/activites-sportives/volley-ball" target="_blank">l'UFOLEP</a>.</p>

		<br />
		<h3>Supporterre</h3>
		<p>Le NPVB est membre de l'association nantaise <a href="https://www.supporterre.fr/" target="_blank">SupporTerre</a>, engagée pour rendre le sport plus responsable, en y favorisant les actions sociales et environnementales.</p>

		<p><a href="Documents/2024_charte_responsable_alimentation_comp.pdf" target="ailleurs">Charte d'achats responsables dans l'alimentation.</a></p>

		<br />		
		<p><u>Pour tous renseignements</u> :</p>
		<ul>
		  <li>Par mail : <a href="mailto:nantespvb@gmail.com">nantespvb@gmail.com</a></li>
		</ul>
		

				
<?php
$defautVisiteur = ob_get_clean();
$contenuVisiteur = getContenuAccueil('accueil_visiteur', $defautVisiteur, $sdblink);

if (!$Joueur){
	//************************************************************//
	// Page d'accueil pour les personnes non identifiées
	//************************************************************//
	rendreZoneAccueil('accueil_visiteur', $contenuVisiteur, false);
}else{
	//******************************************************//
	// Ici la page d'accueil pour les utilisateurs identifiés //
	//******************************************************//

?>
		
<?php ob_start(); ?>
		<br />
		<h3>Inscription aux séances</h3>

		<p>Vous êtes désormais connecté et pouvez renseigner vos présences dans le calendrier. Par défaut, vous êtes absent. Il vous est donc demandé de renseigner vos présences, et cela au moins trois jours avant un événement. Pensez également à vous désinscrire dans l'éventualité où vous ne pourriez pas être présent, le plus tôt possible étant le mieux pour que les autres adhérents puissent disposer d'une place libre.</p>

		<p>Les inscriptions pour les matchs en championnat sont également possibles : seuls les membres des équipes concernées peuvent renseigner leur présence. Contactez nous ou votre capitaine d'équipe si vous ne parvenez pas à vous inscrire.</p>

		<p><a href="Documents/calendrier.pdf" target="ailleurs">Voici un petit guide qui vous explique comment noter votre présence à un événement.</a></p>
	    
		<br />
		<h3>Pendant les séances</h3>

		<p>Merci de participer au montage et démontage des terrains pour que chacun puisse bénéficier d'un plus grand temps de jeu. Nous vous rappelons également que <strong>vous devez apporter votre ballon aux séances</strong> ; si vous n'en avez pas, vous pouvez en acheter un à tarif préférentiel sur <a href="https://www.helloasso.com/associations/npvb/boutiques/boutique-npvb-2025-2026" target="_blank">la boutique du club.</a> </p>

		<p>Pour toute autre question, n'hésitez pas à consulter le <a href="Documents/2025_Livret_accueil.pdf" target="ailleurs">livret d'accueil du NPVB.</a></p>

		<br />
   		<h3>Réinscription 2026-2027</h3>
<iframe id="haWidgetButton" allowtransparency="true" src="https://www.helloasso.com/associations/npvb/adhesions/adhesion-inscription-npvb-2026-2027/widget-bouton" style="width: 100%; height: 70px; border: none;"></iframe>

		<br />
		<h3>Documents</h3>
		<ul>
			<li><a href="Documents/2025_Reglement_interieur_NPVB.pdf" target="_blank">Réglement intérieur du club</a></li>
			<li><a href="Documents/STATUTS_2022.pdf" target="_blank">Statuts du club</a></li>
			<li><a href="Documents/iban_NANTES_PLAISIR_DU_VOLLEY_BALL_00011507001.pdf" target="_blank">IBAN / RIB du compte bancaire du NPVB</a></li>
			<li><a href="Documents/240703_CR_AG_NPVB.pdf" target="_blank"><strong>Compte-rendu de l'AG du 03/07/2024</strong></a></li>
			<li><a href="Documents/250702_CR_AG_NPVB.pdf" target="_blank"><strong>Compte-rendu de l'AG du 02/07/2025</strong></a></li>
		</ul>

		<br />
   		<h3>Applications NPVB</h3>
		
		<p>Inscrivez-vous aux matchs et entraînements directement avec votre téléphone !</p>
		<div class="applications">
			<a href="https://apps.apple.com/us/app/nantes-pvb/id793137223"><img src="./Images/applestore.svg" alt="App Apple Store"/></a>
			<a href="https://play.google.com/store/apps/details?id=npvb.appid"><img src="./Images/googleplay.svg" alt="App Google Play"/></a>
		</div>

		<br />
   		<h3>Contact</h3>

		<p><u>Pour tous renseignements</u> :</p>
		<ul>
		  <li>Messagerie du club : <a href="mailto:nantespvb@gmail.com">nantespvb@gmail.com</a></li>
		  <li>Trésorerie : <a href="mailto:npvbtreso@gmail.com">npvbtreso@gmail.com</a></li>
		  <li>Convivialité : <a href="mailto:npvbconviv@gmail.com">npvbconviv@gmail.com</a></li>
		  <li>Équipe Green : <a href="mailto:greenvolleynpvb@gmail.com">greenvolleynpvb@gmail.com</a>		  </li>
		</ul>
		<br />
		
<?php
	$defautMembre = ob_get_clean();
	if ($estAdminAccueil) {
?>
		<br />
		<hr />
		<h3>Texte de la page d'accueil pour les membres (connectés)</h3>
		<p class="Remarque">Ce texte est visible uniquement par les membres connectés. Cliquez sur le crayon pour le modifier.</p>
<?php
	}
	rendreZoneAccueil('accueil_membre', getContenuAccueil('accueil_membre', $defautMembre, $sdblink), $estAdminAccueil);

	if ($Anniversaires = mySql_query("SELECT * FROM NPVB_Joueurs WHERE (DateNaissance LIKE '%-".date("m-d")."')", $sdblink))
	{
		$ListeAnniversaires="";
		while($Aniv=mySql_fetch_object($Anniversaires)) $ListeAnniversaires .= (($ListeAnniversaires)?", ":"").$Aniv->Prenom." ".$Aniv->Nom;
		if ($ListeAnniversaires)
		{
?>
		<p>Pour la discrétion c'est raté!!!!!
		<br/>Aujourd'hui, c'est l'anniversaire de <?=$ListeAnniversaires?>.</p>
<?
		}
	}
?>

<?php
if($Joueur->DieuToutPuissant=="o"){
	//******************************************************//
	// Édition du texte affiché aux visiteurs non connectés //
	//******************************************************//
?>
		<br />
		<hr />
		<h3>Texte de la page d'accueil pour les visiteurs (non connectés)</h3>
		<p class="Remarque">Ce texte n'est visible que par les personnes non connectées. Cliquez sur le crayon pour le modifier.</p>
<?php
	rendreZoneAccueil('accueil_visiteur', $contenuVisiteur, true);
	}
}
?>

		</td>
  </tr>
</table>

<?php if ($estAdminAccueil) { ?>
<script>
// Éditeur visuel inline de la page d'accueil (admins)
var __zoneAccueil = null;
function editerAccueil(cle){
	__zoneAccueil = cle;
	var c = document.getElementById('contenu_'+cle);
	c.setAttribute('contenteditable','true');
	c.classList.add('en-edition');
	document.getElementById('editeur_'+cle).style.display='block';
	var cr = c.parentNode.querySelector('.crayon-accueil');
	if (cr) cr.style.display='none';
	c.focus();
	window.scrollTo({ top: c.getBoundingClientRect().top + window.pageYOffset - 90, behavior:'smooth' });
}
function cmdAccueil(cmd, val){
	if (__zoneAccueil) document.getElementById('contenu_'+__zoneAccueil).focus();
	document.execCommand(cmd, false, val || null);
}
function lienAccueil(){
	var url = prompt('Adresse du lien (https://...) :', 'https://');
	if (url) document.execCommand('createLink', false, url);
}
function imageAccueil(){
	document.getElementById('tb-file-img').click();
}
function uploadImageAccueil(input){
	var file = input.files[0];
	if (!file) return;
	var fd = new FormData();
	fd.append('image', file);
	fd.append('Page', 'adminaccueilimage');
	fetch('index.php', { method: 'POST', body: fd })
		.then(function(r){ return r.json(); })
		.then(function(data){
			if (data.ok) {
				document.execCommand('insertImage', false, data.url);
			} else {
				alert('Erreur upload : ' + data.err);
			}
		})
		.catch(function(){ alert('Erreur réseau lors de l\'upload.'); });
	input.value = '';
}
function avantSauvegardeAccueil(cle){
	document.getElementById('hidden_'+cle).value = document.getElementById('contenu_'+cle).innerHTML;
	return true;
}
</script>
<?php } ?>


