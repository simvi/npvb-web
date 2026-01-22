<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
?>

<table id="Accueil">
<?
if ($ErreurDonnees["Login"]){print("\t<tr>\n\t\t<td><p class=\"ModifError\">".$ErreurDonnees["Login"]."</p></td>\n\t</tr>\n");}
?>
	<tr>
		<td>
				
<?
if (!$Joueur){
	//********************************************************************************************************************************************//
	//											Ici la page d'accueil pour les personnes non identifiées 										  //
	//********************************************************************************************************************************************//
	
?>
		
		<p><em>Mise à jour : 1 Novembre 2025</em></p>
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

		<br />
		<p align="center"><strong>ATTENTION! </strong>
		<a <blink><strong>LE CLUB EST COMPLET POUR LA SAISON 2025-2026 </strong></blink></a>
		<strong> !ATTENTION</strong></p>

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
		

				
<?
}else{
	//********************************************************************************************************************************************//
	//											Ici la page d'accueil pour les utilisateurs identifiés	 										  //
	//********************************************************************************************************************************************//

?>
		
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
   		<h3>Réinscription 2025-2026</h3>
<iframe id="haWidget" allowtransparency="true" src="https://www.helloasso.com/associations/npvb/adhesions/adhesion-inscription-npvb-2025-2026/widget-bouton" style="width: 100%; height: 70px; border: none;" onload="window.addEventListener( 'message', e => { const dataHeight = e.data.height; const haWidgetElement = document.getElementById('haWidget'); haWidgetElement.height = dataHeight + 'px'; } )" ></iframe>

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
		
<?
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
	//********************************************************************************************************************************************//
	//											Complément éventuel pour les super-utilisateurs			 										  //
	//********************************************************************************************************************************************//
?>
		
<?php
	}
}
?>

		</td>
  </tr>
</table>


