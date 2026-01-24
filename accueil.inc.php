<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
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
// ============================================================
$query_messages = "SELECT * FROM NPVB_Messages WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5";
$result_messages = mysql_query($query_messages, $sdblink);
$has_messages = false;

if ($result_messages && mysql_num_rows($result_messages) > 0) {
	$has_messages = true;
?>
	<div style="background: #fffacd; border: 2px solid #ffa500; border-radius: 5px; padding: 15px; margin-bottom: 20px;">
		<h3 style="margin-top: 0; color: #ff6600; border-bottom: 2px solid #ffa500; padding-bottom: 10px;">
			📢 Messages importants
		</h3>
<?php
	while ($message = mysql_fetch_object($result_messages)) {
?>
		<div style="margin-bottom: 15px; padding: 10px; background: white; border-left: 4px solid #ffa500;">
<?php if ($message->title): ?>
			<h4 style="margin: 0 0 10px 0; color: #003366;">
				<?php echo htmlspecialchars($message->title, ENT_QUOTES, 'ISO-8859-1'); ?>
			</h4>
<?php endif; ?>
			<div style="line-height: 1.6;">
				<?php echo $message->content; ?>
			</div>
			<div style="font-size: 11px; color: #666; margin-top: 5px; font-style: italic;">
				Publié le <?php echo date('d/m/Y', strtotime($message->created_at)); ?>
			</div>
		</div>
<?php
	}
?>
	</div>
<?php
}
?>

<?
if (!$Joueur){
	//********************************************************************************************************************************************//
	//											Ici la page d'accueil pour les personnes non identifi�es 										  //
	//********************************************************************************************************************************************//
	
?>
		
		<p><em>Mise � jour : 1 Novembre 2025</em></p>
		<p align="center">Bienvenue � tous les sportifs !<br>
		<p align="center"><em>Le NPVB est un club de volley loisirs dont les mots d'ordre principaux sont</em>
<p align="center"><em>� d�tente, plaisir et progr�s collectif �.</em><br><br>

		<h3>Pr�sentation g�n�rale</h3>
		<p>Historiquement situ� dans l'Est nantais, le club ouvre ses portes � toute personne <strong>majeure ma�trisant les r�gles et gestes de base</strong> et d�sirant jouer au volleyball dans un cadre loisir mais sportif.
		</p>
		<p>Nous disposons actuellement de 4 cr�neaux d'entra�nement hebdomadaires :
		<ul>
			<li>Lundi, Mercredi et Jeudi de 20 h � 22 h au <a href="https://www.google.com/maps/d/u/0/viewer?mid=1beBtdHzJw2FiLivhUvttzyMPtulFTew6&ll=47.23101189185592%2C-1.5257359986317987&z=15" target="_blank">gymnase No� Lambert</a></strong> (boulevard des Poilus)
			</li>
			<li>Mardi de 21 h � 23 h au <a href="https://www.google.com/maps/d/u/0/viewer?mid=1beBtdHzJw2FiLivhUvttzyMPtulFTew6&ll=47.23796131997372%2C-1.509853378467405&z=15" target="_blank">gymnase Botti�re-Ch�naie</a></strong> (route de Sainte Luce - Tramway L1, arr�t Souillarderie)
			</li>
			
			<br>Ces s�ances de progr�s se d�composent en 3 phases : �chauffement, travail de technique individuelle ou collective puis petits matchs.
		</ul>
		</p>

		<p>Pour ceux qui aiment la comp�tition loisir, 11 �quipes sont engag�es dans les championnats d�tente de Loire-Atlantique (plus de 1000 licenci�s) :</p>
		
		<ul>
		 <li>2 �quipes mixtes participent au <strong>championnat Ufolep</strong> organis� par <a href="https://www.ufolep44.com/activites-sportives/volley-ball" target="_blank">le volley-ball � l'UFOLEP 44</a></li>
		 <li>7 �quipes mixtes et 2 �quipes f�minines participent aux <strong>championnats Competlib</strong> organis�s par le <a href="https://www.comite44volleyball.org/" target="_blank">Comit� D�partemental 44 de Volley-Ball</a></li>
		</ul>

		<p>Les matchs se d�roulent en semaine (aucun matchs le week-end), � la fr�quence d'une fois par semaine pour les �quipes mixtes en Competlib, d'une fois toutes les deux semaines pour les �quipes en Ufolep et d'une fois par mois pour les �quipes f�minines en Competlib.</p>

<!--
		<p>Le NPVB organise �galement chaque ann�e au printemps son Tournoi Green Volley. Pour plus d'infos, n'h�sitez pas � en discuter avec les membres du codir, ou envoyez-nous un mail ^^</p>
-->

		<br />
		<p align="center"><strong>ATTENTION! </strong>
		<a <blink><strong>LE CLUB EST COMPLET POUR LA SAISON 2025-2026 </strong></blink></a>
		<strong> !ATTENTION</strong></p>

		<br />
		<h3>Note � l'attention des personnes souhaitant nous rejoindre</h3>
		<p>Vous �tes tr�s nombreuses et nombreux � nous solliciter chaque ann�e et nous ne pouvons malheureusement pas accepter tout le monde. <strong>Nous ne faisons pas de recrutement en cours d�ann�e</strong> : si vous souhaitez nous rejoindre pour la saison prochaine, merci de nous envoyer un mail � <a href="mailto:nantespvb@gmail.com">l�adresse de messagerie du club</a> o� nous collectons vos demandes pour vous inviter, en fonction des places disponibles, aux s�ances d�essai qui se d�roulent g�n�ralement fin Ao�t, d�but Septembre.</p>

<p>Nous vous rappelons que nous sommes un club loisir et que <strong>nous ne dispensons pas de cours</strong> (nous n�avons pas d�entra�neurs). Il est donc n�cessaire d�avoir <strong>d�j� pratiqu� le volley-ball</strong> et de <strong>ma�triser les gestes de base</strong> (passe, manchette, attaque, bloc et service) pour pouvoir nous rejoindre. <strong>Nous ne prenons pas non plus les mineurs</strong> : vous trouverez l�ensemble des clubs formateurs sur le site du <a href="https://www.ffvbbeach.org/ffvbapp/adressier/rech_aff.php?ws_new_ligue=0&ws_new_comit=044&ws_list_dep=44&id_club=" target="_blank">comit� d�partemental FFVB</a> ou sur le site de <a href="https://www.ufolep44.com/activites-sportives/volley-ball" target="_blank">l'UFOLEP</a>.</p>

		<br />
		<h3>Supporterre</h3>
		<p>Le NPVB est membre de l'association nantaise <a href="https://www.supporterre.fr/" target="_blank">SupporTerre</a>, engag�e pour rendre le sport plus responsable, en y favorisant les actions sociales et environnementales.</p>

		<p><a href="Documents/2024_charte_responsable_alimentation_comp.pdf" target="ailleurs">Charte d'achats responsables dans l'alimentation.</a></p>

		<br />		
		<p><u>Pour tous renseignements</u> :</p>
		<ul>
		  <li>Par mail : <a href="mailto:nantespvb@gmail.com">nantespvb@gmail.com</a></li>
		</ul>
		

				
<?
}else{
	//********************************************************************************************************************************************//
	//											Ici la page d'accueil pour les utilisateurs identifi�s	 										  //
	//********************************************************************************************************************************************//

?>
		
		<br />
		<h3>Inscription aux s�ances</h3>

		<p>Vous �tes d�sormais connect� et pouvez renseigner vos pr�sences dans le calendrier. Par d�faut, vous �tes absent. Il vous est donc demand� de renseigner vos pr�sences, et cela au moins trois jours avant un �v�nement. Pensez �galement � vous d�sinscrire dans l'�ventualit� o� vous ne pourriez pas �tre pr�sent, le plus t�t possible �tant le mieux pour que les autres adh�rents puissent disposer d'une place libre.</p>

		<p>Les inscriptions pour les matchs en championnat sont �galement possibles : seuls les membres des �quipes concern�es peuvent renseigner leur pr�sence. Contactez nous ou votre capitaine d'�quipe si vous ne parvenez pas � vous inscrire.</p>

		<p><a href="Documents/calendrier.pdf" target="ailleurs">Voici un petit guide qui vous explique comment noter votre pr�sence � un �v�nement.</a></p>
	    
		<br />
		<h3>Pendant les s�ances</h3>

		<p>Merci de participer au montage et d�montage des terrains pour que chacun puisse b�n�ficier d'un plus grand temps de jeu. Nous vous rappelons �galement que <strong>vous devez apporter votre ballon aux s�ances</strong> ; si vous n'en avez pas, vous pouvez en acheter un � tarif pr�f�rentiel sur <a href="https://www.helloasso.com/associations/npvb/boutiques/boutique-npvb-2025-2026" target="_blank">la boutique du club.</a> </p>

		<p>Pour toute autre question, n'h�sitez pas � consulter le <a href="Documents/2025_Livret_accueil.pdf" target="ailleurs">livret d'accueil du NPVB.</a></p>

		<br />
   		<h3>R�inscription 2025-2026</h3>
<iframe id="haWidget" allowtransparency="true" src="https://www.helloasso.com/associations/npvb/adhesions/adhesion-inscription-npvb-2025-2026/widget-bouton" style="width: 100%; height: 70px; border: none;" onload="window.addEventListener( 'message', e => { const dataHeight = e.data.height; const haWidgetElement = document.getElementById('haWidget'); haWidgetElement.height = dataHeight + 'px'; } )" ></iframe>

		<br />
		<h3>Documents</h3>
		<ul>
			<li><a href="Documents/2025_Reglement_interieur_NPVB.pdf" target="_blank">R�glement int�rieur du club</a></li>
			<li><a href="Documents/STATUTS_2022.pdf" target="_blank">Statuts du club</a></li>
			<li><a href="Documents/iban_NANTES_PLAISIR_DU_VOLLEY_BALL_00011507001.pdf" target="_blank">IBAN / RIB du compte bancaire du NPVB</a></li>
			<li><a href="Documents/240703_CR_AG_NPVB.pdf" target="_blank"><strong>Compte-rendu de l'AG du 03/07/2024</strong></a></li>
			<li><a href="Documents/250702_CR_AG_NPVB.pdf" target="_blank"><strong>Compte-rendu de l'AG du 02/07/2025</strong></a></li>
		</ul>

		<br />
   		<h3>Applications NPVB</h3>
		
		<p>Inscrivez-vous aux matchs et entra�nements directement avec votre t�l�phone !</p>
		<div class="applications">
			<a href="https://apps.apple.com/us/app/nantes-pvb/id793137223"><img src="./Images/applestore.svg" alt="App Apple Store"/></a>
			<a href="https://play.google.com/store/apps/details?id=npvb.appid"><img src="./Images/googleplay.svg" alt="App Google Play"/></a>
		</div>

		<br />
   		<h3>Contact</h3>

		<p><u>Pour tous renseignements</u> :</p>
		<ul>
		  <li>Messagerie du club : <a href="mailto:nantespvb@gmail.com">nantespvb@gmail.com</a></li>
		  <li>Tr�sorerie : <a href="mailto:npvbtreso@gmail.com">npvbtreso@gmail.com</a></li>
		  <li>Convivialit� : <a href="mailto:npvbconviv@gmail.com">npvbconviv@gmail.com</a></li>
		  <li>�quipe Green : <a href="mailto:greenvolleynpvb@gmail.com">greenvolleynpvb@gmail.com</a>		  </li>
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
		<p>Pour la discr�tion c'est rat�!!!!!
		<br/>Aujourd'hui, c'est l'anniversaire de <?=$ListeAnniversaires?>.</p>
<?
		}
	}
?>

<?php
if($Joueur->DieuToutPuissant=="o"){
	//********************************************************************************************************************************************//
	//											Compl�ment �ventuel pour les super-utilisateurs			 										  //
	//********************************************************************************************************************************************//
?>
		
<?php
	}
}
?>

		</td>
  </tr>
</table>


