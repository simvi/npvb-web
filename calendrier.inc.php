<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
$Evenements = ChargeEvenements($Annee, $Mois, null);
$Equipes = ChargeEquipes();

if ($Mode<>"Admin") $Mode=($Joueur)?"Saisie":"Visu";

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
	<table id="Calendrier">
		<tr class="TitreMois">
			<td><a href="<?=$PHP_SELF?>?Page=<?=(($Mode=="Admin")?"adminevenements":"calendrier")?>&amp;Annee=<?=$AnneeAvant?>&amp;Mois=<?=$MoisAvant?>">&lt;</a></td> <td colspan="5"><?=$montharray[(int)$Mois]?> <?=$Annee?></td> <td><a href="<?=$PHP_SELF?>?Page=<?=(($Mode=="Admin")?"adminevenements":"calendrier")?>&amp;Annee=<?=$AnneeApres?>&amp;Mois=<?=$MoisApres?>">&gt;</a></td>
		</tr>
		<tr class="TitreJourSemaine">
			<td>Lundi</td> <td>Mardi</td> <td>Mercredi</td> <td>Jeudi</td> <td>Vendredi</td> <td>Samedi</td> <td>Dimanche</td>
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
		print("<em>".$DateDuJour["mday"]."</em>");
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
		print("</td>");
		$Jour++;
		// *********** Passage au jour suivant
	}while($DateDuJour["wday"] > 0);
	print("\n\t\t</tr>\n");
	$DateDuJour = getDate(mkTime(12, 0, 0, $Mois, $Jour, $Annee));
}while ($DateDuJour["mon"] == $Mois);
?>

		<tr class="Legende">
			<td>Légende:<br/>&nbsp;</td>
			<td colspan="2">
			<table>
				<tr>
					<td><div class="RectangleLegende Seance"></div></td><td>Séance de progrès</td>
				</tr>
				<tr>
					<td><div class="RectangleLegende Asso"></div></td><td>Evénement divers</td>
				</tr>
			</table>
			</td>
			<td colspan="2">
			<table>
				<tr>
					<td><div class="RectangleLegende RencontreComplet"></div></td><td>Rencontre, équipe complète</td>
				</tr>
				<tr>
					<td><div class="RectangleLegende RencontreIncomplet"></div></td><td>Rencontre, équipe incomplète</td>
				</tr>
			</table>
			</td>
			<td colspan="2">
			<table>
				<tr>
					<td><div class="RectangleLegende Annule"></div></td><td>Evénement annulé</td>
				</tr>
				<tr>
<?
if($Mode=="Visu"){?>					<td>&nbsp;</td><td>&nbsp;</td>
<?}else if ($Mode=="Saisie"){?>					<td><img src="<?=$RepertoireImages?>presence.gif" alt="" /> <img src="<?=$RepertoireImages?>presence.gif" alt="" /></td><td>Présence</td>
<?}else if ($Mode=="Admin"){?>					<td><div class="RectangleLegende Initialise"></div></td><td>Evenement seulement initialise</td>
<?}?>
				</tr>
			</table>
			</td>
		</tr>
	</table>
    
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





