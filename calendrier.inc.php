<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
$Evenements = ChargeEvenements($Annee, $Mois, null);
$Equipes = ChargeEquipes();

if ($Mode<>"Admin") $Mode=($Joueur)?"Saisie":"Visu";

// Hauteur uniforme des cellules calée sur le jour le plus chargé du mois affiché
// (transmise au CSS via la variable --cal-events sur le tableau)
$MaxEvenementsJour = 0;
if (is_array($Evenements)) {
	foreach ($Evenements as $__date => $__heures) {
		if ((int)substr($__date, 4, 2) != (int)$Mois) continue; // uniquement le mois affiché
		$__nb = 0;
		foreach ($__heures as $__hkey => $__events) {
			foreach ($__events as $__ev) {
				if (($Mode != "Admin") && ($__ev->Etat == "I")) continue; // events masqués hors admin
				$__nb++;
			}
		}
		if ($__nb > $MaxEvenementsJour) $MaxEvenementsJour = $__nb;
	}
}
if ($MaxEvenementsJour < 1) $MaxEvenementsJour = 1;

//**********************************   PAGE WEB   ************************************************//
?>
<link href="Feuilles de style/style.css" rel="stylesheet" type="text/css" />
<div id="Bulle"> 
	Votre navigateur est trop vieux ou ne supporte pas javascript
	</div>  
<?
if ($Mode<>"Admin"){
?>

		<div class="Explications">
			<?=($Mode=="Saisie")?"Survolez un événement pour l'afficher. Cliquez sur les flèches pour changer de mois. ":"Survolez un événement pour l'afficher. Cliquez sur les flèches pour changer de mois. "?>
			Vous cherchez <strong>un plan, une adresse</strong> ? C'est par ici : <a href="https://www.google.com/maps/d/u/2/edit?mid=1beBtdHzJw2FiLivhUvttzyMPtulFTew6&usp=sharing" target="_blank">Carte des gymnases</a>
		</div>
    
<?
}
?>	
	<table id="Calendrier" style="--cal-events: <?=$MaxEvenementsJour?>;">
		<tr class="TitreMois">
			<td><a href="<?=$PHP_SELF?>?Page=<?=(($Mode=="Admin")?"adminevenements":"calendrier")?>&amp;Annee=<?=$AnneeAvant?>&amp;Mois=<?=$MoisAvant?>">&lt;</a></td> <td colspan="5"><?=$montharray[(int)$Mois]?> <?=$Annee?></td> <td><a href="<?=$PHP_SELF?>?Page=<?=(($Mode=="Admin")?"adminevenements":"calendrier")?>&amp;Annee=<?=$AnneeApres?>&amp;Mois=<?=$MoisApres?>">&gt;</a></td>
		</tr>
		<tr class="TitreJourSemaine">
			<td><span class="jour-long">Lundi</span><span class="jour-court">Lun</span></td> <td><span class="jour-long">Mardi</span><span class="jour-court">Mar</span></td> <td><span class="jour-long">Mercredi</span><span class="jour-court">Mer</span></td> <td><span class="jour-long">Jeudi</span><span class="jour-court">Jeu</span></td> <td><span class="jour-long">Vendredi</span><span class="jour-court">Ven</span></td> <td><span class="jour-long">Samedi</span><span class="jour-court">Sam</span></td> <td><span class="jour-long">Dimanche</span><span class="jour-court">Dim</span></td>
		</tr>
<?//Recuperation des données du mois
$PremierJourDuMois = getDate(mkTime(12, 0, 0, $Mois, 1, $Annee));
$Jour = 2-$PremierJourDuMois["wday"];
$Jour = ($Jour==2)?-5:$Jour;
if ((int)$Mois<10) $Mois="0".(int)$Mois;
do{
    //une nouvelle semaine
	$DateDuJour;
	print("\t\t<tr class=\"JourMois\">\n");
	do{
		//********************************
		// *********** Affichage d'un jour
		//********************************
		$DateDuJour = getDate(mkTime(12, 0, 0, $Mois, $Jour, $Annee));
		$DateDuJourMySQL =  substr(ConvertisDate(mkTime(12, 0, 0, $Mois, $Jour, $Annee), "MySQL"), 0, 8);
		$StyleJour = ($DateDuJour["mon"] == $Mois)?"BonMois":"AutreMois";
		if (($DateDuJour["yday"] == $Maintenant["yday"])&&($DateDuJour["year"] == $Maintenant["year"])) $StyleJour="Aujourdhui";
		if ((($Evenements[$DateDuJourMySQL])&&($Mode=="Saisie"))||($Mode=="Admin")) $StyleJour .= " JourAvecEvent";
		print("\t\t\t<td class=\"".$StyleJour."\"".(((($Evenements[$DateDuJourMySQL])&&($Mode=="Saisie"))||($Mode=="Admin"))?" onclick=\"window.location.href='".$PHP_SELF."?Page=".(($Mode=="Admin")?"adminfichejour":"jour")."&amp;Jour=".$DateDuJourMySQL."&amp;Mois=".$Mois."&amp;Annee=".$Annee."'\"":"").">");
		//if (($Evenements[$DateDuJourMySQL])&&(($Mode=="Saisie")||($Mode=="Admin"))) print("<a href=\"".$PHP_SELF."?Page=jour&Jour=".$DateDuJourMySQL."&Mois=".$Mois."&Annee=".$Annee."\">");
		print("<div class=\"JourContenu\"><em>".$DateDuJour["mday"]."</em>");
		//if (($Evenements[$DateDuJourMySQL])&&(($Mode=="Saisie")||($Mode=="Admin"))) print(" (Détails) </a>");
		
		//*************************************
		// *********** Affichage des événements pour ce jour
		//*************************************
		
		if ($Evenements[$DateDuJourMySQL]){
			foreach ($Evenements[$DateDuJourMySQL] as $HeureKey=>$HeureEvent){
				foreach ($Evenements[$DateDuJourMySQL][$HeureKey] as $Key=>$Event){
					if (($Mode<>"Admin")&($Event->Etat=="I")) continue;
					$Presence="";
					$BullePresence="";
					if (substr($Event->Etat, 0 ,1) == "A"){
						$Style="Annule";
						$MessageBulle = "&lt;p&gt;".$Event->Intitule."&lt;/p&gt;&lt;ul&gt;";  
						$MessageBulle .= "&lt;li&gt;Annulé !&lt;/li&gt;&lt;/ul&gt;"; 
					}else{
						if (($Mode=="Saisie")&&((((($Event->Etat=="O")||($Event->Etat=="F"))&&($Event->seraPresent($Joueur->Pseudonyme))))||((($Event->Etat=="T")&&($Event->etaitPresent($Joueur->Pseudonyme)))))) {
							$Presence = " <img src=\"".$RepertoireImages."presence.gif\" alt=\"\" /> ";
							$BullePresence = " &lt;img src='".$RepertoireImages."presence.gif' alt='' /&gt; ";
						}
						$MessageBulle = "&lt;p&gt;".$BullePresence.$Event->Intitule.$BullePresence."&lt;/p&gt;&lt;ul&gt;";  
						$MessageBulle .= "&lt;li&gt;Heure: ".substr($Event->DateHeure, 8, 2).":".substr($Event->DateHeure, 10, 2)."&lt;/li&gt;";
						if ($Event->Lieu) $MessageBulle .= "&lt;li&gt;".$Event->Lieu."&lt;/li&gt;";

						if (($Mode=="Admin")&($Event->Etat=="I")){

							$Style="Initialise";

						}else if(($Key=="SEANCE")||($Key=="ASSO")){
							
							//C'est une séance de progrès ou un evènement de l'asso
							$Style=ucFirst(strToLower($Key));
							
						}else if ($Equipes[$Key]){
							
							//C'est une rencontre d'une équipe
							$nombreJoueursPresents = (($Event->Etat=="O")||($Event->Etat=="F"))?$Event->nombreJoueursPresents():$Event->NombreJoueursEtaientPresents;
							$Style = ($nombreJoueursPresents >= $EquipeComplete)?"RencontreComplet":"RencontreIncomplet";
							$MessageBulle .= "&lt;li&gt;Rencontre à ".(($Event->Domicile == "o")?"domicile":"l'extérieur")."&lt;/li&gt;";
							if ($Event->Resultat) $MessageBulle .= "&lt;li&gt;Résultat: ".substr($Event->Resultat, 0, 1)." / ".substr($Event->Resultat, 1, 1)."&lt;/li&gt;";
							
						} else continue;//Ce n'est pas une evènement reconnu
						
						if (($Joueur) && (($Event->Etat=="O")||($Event->Etat=="F")) ) $MessageBulle .= "&lt;li&gt;".$Event->nombreJoueursPresents()." inscrit".(($Event->nombreJoueursPresents() > 1)?"s":"")."&lt;/li&gt;";
						if (($Joueur) && ($Event->Etat=="T") ) $MessageBulle .= "&lt;li&gt;".$Event->NombreJoueursEtaientPresents." ".(($Event->NombreJoueursEtaientPresents > 1)?"membres étaient présents":"membre était présent")."&lt;/li&gt;";
						$MessageBulle .= "&lt;/ul&gt;";
						if ($Joueur) $MessageBulle .= "&lt;em&gt;(Cliquez pour voir plus de détails)&lt;/em&gt;";
					}
					$MessageBulle=str_replace("'", "\'", $MessageBulle);
					print("<p class=\"".$Style."\" onmouseover=\"bulle('".$MessageBulle."', event, 3)\" onmouseout=\"couic()\"> ".$Presence.$Event->Titre.$Presence." </p>");
				}
			}
		}
		// *********** Fin affichage des des événements pour une journée
		print("</div></td>");
		$Jour++;
		// *********** Passage au jour suivant
	}while($DateDuJour["wday"] > 0);
	print("\n\t\t</tr>\n");
	$DateDuJour = getDate(mkTime(12, 0, 0, $Mois, $Jour, $Annee));
}while ($DateDuJour["mon"] == $Mois);
?>

	</table>

	<div class="LegendeCalendrier">
		<span class="LegendeTitre">Légende :</span>
		<span class="LegendeItem"><span class="RectangleLegende Seance"></span>Séance de progrès</span>
		<span class="LegendeItem"><span class="RectangleLegende Asso"></span>Événement divers</span>
		<span class="LegendeItem"><span class="RectangleLegende RencontreComplet"></span>Rencontre, équipe complète</span>
		<span class="LegendeItem"><span class="RectangleLegende RencontreIncomplet"></span>Rencontre, équipe incomplète</span>
		<span class="LegendeItem"><span class="RectangleLegende Annule"></span>Événement annulé</span>
<?if ($Mode=="Saisie"){?>		<span class="LegendeItem"><img src="<?=$RepertoireImages?>presence.gif" alt="" />Présence</span>
<?}else if ($Mode=="Admin"){?>		<span class="LegendeItem"><span class="RectangleLegende Initialise"></span>Événement seulement initialisé</span>
<?}?>
	</div>
    
      <?
if ($Mode<>"Admin"){
?>
    </p>
    <p>&nbsp; </p>
<div class="Explications">
			<a href="#HautDePage">Haut de page</a><br/>
		</div>
<?
}
?>
<script>
(function(){
	var KEY = 'npvbCalScroll';
	// Restaure la position de défilement après un changement de mois
	var saved = sessionStorage.getItem(KEY);
	if (saved !== null) {
		sessionStorage.removeItem(KEY);
		var y = parseInt(saved, 10) || 0;
		window.scrollTo(0, y);
		window.addEventListener('load', function(){ window.scrollTo(0, y); });
	}
	// Mémorise la position avant de changer de mois (flèches < et >)
	var liens = document.querySelectorAll('.TitreMois a');
	for (var i = 0; i < liens.length; i++) {
		liens[i].addEventListener('click', function(){
			sessionStorage.setItem(KEY, window.scrollY || window.pageYOffset || 0);
		});
	}
})();
</script>





