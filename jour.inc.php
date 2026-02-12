<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
if (!$Joueur){ require("accueil.inc.php"); return;}


//******************************************************************
//************ Effectue les modifications demandées
//******************************************************************
$Joueurs = ChargeJoueurs("", "Nom, Prenom");

if($Action=="Presence"){
    
    $Modification=true;
    $Evenements = ChargeEvenements(null, null, $Jour);
    foreach ($Evenements[$Jour] as $HeureKey=>$HeureEvent){
        foreach ($Evenements[$Jour][$HeureKey] as $Key=>$Event){
            
            eval("$"."Presence = (substr($"."seraPresent".$Event->Libelle.$Event->DateHeure.", 0, 1) == \"o\")?\"o\":\"n\";");
            if (($Presence)&&($Event->seraPresent($Joueur->Pseudonyme)<>($Presence=="o"))) {
                if ($DBPresence = mySql_fetch_object(mySql_query("SELECT * FROM NPVB_Presence WHERE (Joueur='".$Joueur->Pseudonyme."' AND DateHeure='".$Event->DateHeure."' AND Libelle='".$Event->Libelle."')", $sdblink))){
                    
                    //Il y a deja l'info, faire un UPDATE
                    if (!mySql_query("UPDATE NPVB_Presence SET DateHeure=DateHeure, Prevue='".$Presence."' WHERE (Joueur='".$Joueur->Pseudonyme."' AND DateHeure='".$Event->DateHeure."' AND Libelle='".$Event->Libelle."')", $sdblink)) $ErreurDonnees["Enregistrement"] .= "L'enregistrement de ".(($PresenceAutreJoueur=="o")?"la présence":"l'absence")." de ".$Joueur->Pseudonyme."<br/>à ".$Event->Libelle." le ".$Event->DateHeure."a échoué<br/>";
                    
                }else{
                    
                    //Il n'y a pas l'info, faire un INSERT
                    if (!mySql_query("INSERT INTO NPVB_Presence (Joueur, DateHeure, Libelle, Prevue) VALUES ('".$Joueur->Pseudonyme."', '".$Event->DateHeure."', '".$Event->Libelle."', '".$Presence."')", $sdblink)) $ErreurDonnees["Enregistrement"] .= "L'enregistrement de ".(($PresenceAutreJoueur=="o")?"la présence":"l'absence")." de ".$Joueur->Pseudonyme."<br/>à ".$Event->Libelle." le ".$Event->DateHeure."a échoué<br/>";
                    
                }
            }
            
            //+++++++++++++++++
            //++ Si la présence a été saisie pour les autres joueurs, mettre à jour les valeurs
            //+++++++++++++++++
            eval ("$"."estDerouleseraPresent = $"."estDerouleseraPresent".$Event->Libelle.$Event->DateHeure.";");
            if ($estDerouleseraPresent=="o"){
                $Depanne=null;
                eval ("$"."Depanne = $"."Depanne".$Event->Libelle.$Event->DateHeure.";");
                foreach($Joueurs as $AutreJoueur){
                    
                    // Si le compte est inactif  et qu'il a un truc saisi, le virer et n'afficher que les valides
                    
                    $seraPresentAutreJoueur=null;
                    eval("$"."seraPresentAutreJoueur = $"."seraPresent".$AutreJoueur->Pseudonyme.$Event->Libelle.$Event->DateHeure.";");
                    if (($Depanne==$AutreJoueur->Pseudonyme)&&(!$seraPresentAutreJoueur=="o")) {
                        $seraPresentAutreJoueur="o";//si un joueur dépanne
                    }
                    if (($seraPresentAutreJoueur)&&($Event->seraPresent($AutreJoueur->Pseudonyme)<>($seraPresentAutreJoueur=="o"))){
                        if ($DBPresence = mySql_fetch_object(mySql_query("SELECT * FROM NPVB_Presence WHERE (Joueur='".$AutreJoueur->Pseudonyme."' AND DateHeure='".$Event->DateHeure."' AND Libelle='".$Event->Libelle."')", $sdblink))){
                            //Il y a deja l'info, faire un UPDATE
                            if (!mySql_query("UPDATE NPVB_Presence SET DateHeure=DateHeure, Prevue='".$seraPresentAutreJoueur."' WHERE (Joueur='".$AutreJoueur->Pseudonyme."' AND DateHeure='".$Event->DateHeure."' AND Libelle='".$Event->Libelle."')", $sdblink)) $ErreurDonnees["Enregistrement"] .= "L'enregistrement de ".(($PresenceAutreJoueur=="o")?"la présence":"l'absence")." de ".$AutreJoueur->Pseudonyme."<br/>à ".$Event->Libelle." le ".$Event->DateHeure."a échoué<br/>";
                            
                            
                            
                        }else{
                            if ($seraPresentAutreJoueur=="") $seraPresentAutreJoueur=$Event->seraPresent($AutreJoueur->Pseudonyme);
                            //Il n'y a pas l'info, faire un INSERT
                            if (!mySql_query("INSERT INTO NPVB_Presence (Joueur, DateHeure, Libelle, Prevue) VALUES ('".$AutreJoueur->Pseudonyme."', '".$Event->DateHeure."', '".$Event->Libelle."', '".$seraPresentAutreJoueur."')", $sdblink)) $ErreurDonnees["Enregistrement"] .= "L'enregistrement de ".(($PresenceAutreJoueur=="o")?"la présence":"l'absence")." de ".$AutreJoueur->Pseudonyme."<br/>à ".$Event->Libelle." le ".$Event->DateHeure."a échoué<br/>";
                        }
                    }
                    
                }
            }
            
            //+++++++++++++++++
            //++ Si la présence réelle a été saisie pour les autres joueurs, mettre à jour les valeurs
            //+++++++++++++++++
            
            eval ("$"."estDerouleetaitPresent = $"."estDerouleetaitPresent".$Event->Libelle.$Event->DateHeure.";");
            eval("$"."TermineEvent = (substr($"."TermineEvent".$Event->Libelle.$Event->DateHeure.", 0, 1) == \"o\");");
            if (($estDerouleetaitPresent=="o")&&(!$ErreurDonnees)){
                //Récupère le résultat
                $Resultat="";
                if (($Event->Libelle<>"ASSO")&&($Event->Libelle<>"SEANCE")){
                    for ($Set=0; $Set<=5; $Set++){
                        eval("if (preg_match(\"/^[0-9]{1".(($Set==0)?"":",2")."}$/\", $"."Set".(($Set==0)?"s":$Set)."Equipe".$Event->Libelle.$Event->DateHeure.")) { $"."Resultat".$Event->Libelle.$Event->DateHeure." .= ".(($Set==0)?"$"."Set".(($Set==0)?"s":$Set)."Equipe".$Event->Libelle.$Event->DateHeure:"($"."Set".(($Set==0)?"s":$Set)."Equipe".$Event->Libelle.$Event->DateHeure." >= 10)?$"."Set".(($Set==0)?"s":$Set)."Equipe".$Event->Libelle.$Event->DateHeure.":\"0\".(int)$"."Set".(($Set==0)?"s":$Set)."Equipe".$Event->Libelle.$Event->DateHeure)."; }else{ $"."ErreurDonnees[\"Enregistrement\"] .= \"Veillez vérifier le résultat<br/>\"; }");
                        eval("if (preg_match(\"/^[0-9]{1".(($Set==0)?"":",2")."}$/\", $"."Set".(($Set==0)?"s":$Set)."Adversaire".$Event->Libelle.$Event->DateHeure.")) { $"."Resultat".$Event->Libelle.$Event->DateHeure." .= ".(($Set==0)?"$"."Set".(($Set==0)?"s":$Set)."Adversaire".$Event->Libelle.$Event->DateHeure:"($"."Set".(($Set==0)?"s":$Set)."Adversaire".$Event->Libelle.$Event->DateHeure." >= 10)?$"."Set".(($Set==0)?"s":$Set)."Adversaire".$Event->Libelle.$Event->DateHeure.":\"0\".(int)$"."Set".(($Set==0)?"s":$Set)."Adversaire".$Event->Libelle.$Event->DateHeure)."; }else{ $"."ErreurDonnees[\"Enregistrement\"] .= \"Veillez vérifier le résultat<br/>\"; }");
                    }
                }
                if (($TermineEvent)&&(!$ErreurDonnees)){
                    
                    eval("$"."Resultat = $"."Resultat".$Event->Libelle.$Event->DateHeure.";");
                    
                    if (!mySql_query("UPDATE NPVB_Evenements SET DateHeure=DateHeure, Resultat='".$Resultat."' WHERE (DateHeure='".$Event->DateHeure."' AND Libelle='".$Event->Libelle."')", $sdblink)) $ErreurDonnees["Enregistrement"] .= "L'enregistrement de ".(($PresenceAutreJoueur=="o")?"la présence":"l'absence")." de ".$AutreJoueur->Pseudonyme."<br/>à ".$Event->Libelle." le ".$Event->DateHeure."a échoué<br/>".mySql_errno($sdblink).", ".mySql_error($sdblink);;
                    
                    foreach($Joueurs as $AutreJoueur){
                        $etaitPresentAutreJoueur=null;
                        eval("$"."etaitPresentAutreJoueur = $"."etaitPresent".$AutreJoueur->Pseudonyme.$Event->Libelle.$Event->DateHeure.";");
                        if ($etaitPresentAutreJoueur){
                            $seraPresentAutreJoueur=null;
                            if ($estDerouleseraPresent=="o") {eval("$"."seraPresentAutreJoueur = $"."seraPresent".$AutreJoueur->Pseudonyme.$Event->Libelle.$Event->DateHeure.";");
                            }else{$seraPresentAutreJoueur= ($Event->seraPresent($AutreJoueur->Pseudonyme)?"o":"n");
                            }
                            if ($DBPresence = mySql_fetch_object(mySql_query("SELECT * FROM NPVB_Presence WHERE (Joueur='".$AutreJoueur->Pseudonyme."' AND DateHeure='".$Event->DateHeure."' AND Libelle='".$Event->Libelle."')", $sdblink))){
                                //Il y a deja l'info, faire un UPDATE
                                if (!mySql_query("UPDATE NPVB_Presence SET DateHeure=DateHeure, Effective='".$etaitPresentAutreJoueur."' WHERE (Joueur='".$AutreJoueur->Pseudonyme."' AND DateHeure='".$Event->DateHeure."' AND Libelle='".$Event->Libelle."')", $sdblink)) $ErreurDonnees["Enregistrement"] .= "L'enregistrement de ".(($PresenceAutreJoueur=="o")?"la présence":"l'absence")." de ".$AutreJoueur->Pseudonyme."<br/>à ".$Event->Libelle." le ".$Event->DateHeure."a échoué<br/>";
                            }else{
                                //Il n'y a pas l'info, faire un INSERT
                                if (!mySql_query("INSERT INTO NPVB_Presence (Joueur, DateHeure, Libelle, Prevue, Effective) VALUES ('".$AutreJoueur->Pseudonyme."', '".$Event->DateHeure."', '".$Event->Libelle."', '".$seraPresentAutreJoueur."', '".$etaitPresentAutreJoueur."')", $sdblink)) $ErreurDonnees["Enregistrement"] .= "L'enregistrement de ".(($PresenceAutreJoueur=="o")?"la présence":"l'absence")." de ".$AutreJoueur->Pseudonyme."<br/>à ".$Event->Libelle." le ".$Event->DateHeure."a échoué<br/>";
                            }
                        }
                        
                    }
                }else {$ErreurDonnees["Enregistrement"] .= "Vous devez confirmer pour enregistrer les présences<br/>";}
                
                if ((!$ErreurDonnees)&&($Event->Etat=="F")) {
                    
                    if (!mySql_query("UPDATE NPVB_Evenements SET DateHeure=DateHeure, Etat='T' WHERE (DateHeure='".$Event->DateHeure."' AND Libelle='".$Event->Libelle."')", $sdblink)) $ErreurDonnees["Enregistrement"] .= "La cloture de ".$Event->Libelle." le ".$Event->DateHeure."a échoué<br/>";
                    
                }
                
            }
            //+++++++++++++++++
            //++ finSi
            //+++++++++++++++++
        }
    }
    if (!$ErreurDonnees){
        foreach ($Evenements[$Jour] as $HeureKey=>$HeureEvent){
            foreach ($Evenements[$Jour][$HeureKey] as $Key=>$Event){
                eval("$"."FermeEvenement = (substr($"."FermeEvenement".$Event->Libelle.$Event->DateHeure.", 0, 1) == \"o\");");
                if (($FermeEvenement)&&($Event->Etat=="O")) {
                    if (!mySql_query("UPDATE NPVB_Evenements SET DateHeure=DateHeure, Etat='F' WHERE (DateHeure='".$Event->DateHeure."' AND Libelle='".$Event->Libelle."')", $sdblink)) $ErreurDonnees["Enregistrement"] .= "La fermeture de ".$Event->Libelle." le ".$Event->DateHeure."a échoué<br/>";
                }
            }
        }
    }
}


//******************************************************************
//************ Charge les données et affiche la page
//******************************************************************
$Evenements = ChargeEvenements(null, null, $Jour);
$Equipes = ChargeEquipes();

?>

<h2>Les événements de la journée du <?=substr($Jour, 6, 2)?> <?=$montharray[(int)substr($Jour, 4, 2)]?> <?=substr($Jour, 0, 4)?></p></h2>

<?
if (($Evenements[$Jour])&&($Joueur)){
    /********************** Il y a bien au moins un evènement ce jour **********************/
    ?>
    
    <form id="formulaire" action="<?=$PHP_SELF?>" method="post">
    <div>
    <input type="hidden" name="Page" value="<?=$Page?>" />
    <input type="hidden" name="Jour" value="<?=$Jour?>" />
    <input type="hidden" name="Mois" value="<?=$Mois?>" />
    <input type="hidden" name="Annee" value="<?=$Annee?>" />
    <input type="hidden" name="Action" value="Presence" />
    
    
    <table id="Jour">
    <?
    if ($Modification){
        if ($ErreurDonnees["Enregistrement"]){
            print("\t<tr>\n\t\t<td><p class=\"ModifError\">".$ErreurDonnees["Enregistrement"]."</p></td>\n\t</tr>\n");
        }else{
            print("\t<tr>\n\t\t<td><p class=\"ModifOk\">Modifications effectuées avec succès</p></td>\n\t</tr>\n");
        }
    }
    $Termine=true;
    
    foreach ($Evenements[$Jour] as $HeureKey=>$HeureEvent){
        
        foreach ($Evenements[$Jour][$HeureKey] as $Key=>$Event){
            
            if (substr($Event->Etat, 0, 1) == "I") continue;
            
            // Enregistre les mails des inscrits
            $Emails="";
            
            //On met tous les événements
            $Resultat="";
            $Responsable = (($Joueur->Pseudonyme==$Equipes[$Event->Libelle]->Responsable)||($Joueur->Pseudonyme==$Equipes[$Event->Libelle]->Supleant));
            $EvenementDémarré = (ConvertisDate($Event->DateHeure, "PHP") < mkTime());
            $EvenementTerminé = (ConvertisDate($Event->DateHeure, "PHP") < mkTime()-($DureeEvenement*3600));
            $RaisonBloque="";
            
            if (substr($Event->Etat, 0, 1) == "A"){
                
                $TexteCellule = "<p>".$Event->Intitule."</p><ul><li>Annulé : ".substr($Event->Etat, 1)."</li></ul>";
                $Style="Annule";
                
            }else{
                
                //----Teste si le joueur peut saisir ses résultats----//
                
                $nombreJoueursPresents = (($Event->Etat=="O")||($Event->Etat=="F"))?$Event->nombreJoueursPresents():$Event->NombreJoueursEtaientPresents;
                
                
                if ((int)($Event->InscritsMax) > 0 &&  $nombreJoueursPresents > (int)($Event->InscritsMax))
                    $RaisonBloque = "Il n'y a plus de place disponible (". $nombreJoueursPresents ."/".($Event->InscritsMax).")<br/>";
                
                if ($EvenementDémarré) $RaisonBloque = "L'événement est commencé";
                if ($EvenementTerminé) $RaisonBloque = "L'événement est terminé";
                if ($Event->Etat=="F") $RaisonBloque = "L'événement est fermé à la saisie des présences<br/>";
                if ($Event->Etat=="T") $RaisonBloque = "L'événement est terminé<br/>";
                
                if ((!$Equipes[$Event->Libelle]->faisPartie($Joueur->Pseudonyme))&&(!$Event->seraPresent($Joueur->Pseudonyme)))
                    $RaisonBloque = "Vous ne faites pas partie de ".$Equipes[$Event->Libelle]->Nom;
                
                //A virer le $termine
                if (($Joueur->DieuToutPuissant=="o")||((!$EvenementDémarré)&&($Event->Etat=="O") && (($Equipes[$Event->Libelle]->faisPartie($Joueur->Pseudonyme))||($Event->seraPresent($Joueur->Pseudonyme))))||(($Responsable)&&($EvenementTerminé)&&(($Event->Etat=="F")||($Event->Etat=="O"))))
                    $Termine=false;
                
                //----Prépare le contenu de la cellule----//
                
                $ADomicile = (($Event->Libelle<>"ASSO")&&($Event->Libelle<>"SEANCE"))?(($Event->Domicile == "o")?" <em>(à domicile)</em>":" <em>(à l'extérieur)</em>"):"";
                $TexteCellule = "<p class=\"TitreJour\"><input type=\"checkbox\" name=\"seraPresent".$Event->Libelle.$Event->DateHeure."\" value=\"oui\"".(($Event->seraPresent($Joueur->Pseudonyme))?" checked=\"checked\"":"").(($RaisonBloque && !($Event->seraPresent($Joueur->Pseudonyme)))?" disabled=\"disabled\"":"")." /> ".$Event->Intitule.(($RaisonBloque && !($Event->seraPresent($Joueur->Pseudonyme)))?"<input type=\"hidden\" name=\"seraPresent".$Event->Libelle.$Event->DateHeure."\" value=\"".(($Event->seraPresent($Joueur->Pseudonyme))?"oui":"non")."\" />":"").$ADomicile."</p><table class=\"InfosEvent\">";
                
                $TexteCellule .= "<tr><td class=\"InfosEvent1\">Heure:</td><td class=\"InfosEvent2\">".substr($Event->DateHeure, 8, 2)."H".substr($Event->DateHeure, 10, 2)."</td></tr>";
                if ($Event->Adversaire) $TexteCellule .= "<tr><td>Adversaire:</td><td>".$Event->Adversaire."</td></tr>";
                if ($Event->Lieu) $TexteCellule .= "<tr><td>Lieu:</td><td>".$Event->Lieu."</td></tr>";
                if ($Event->Adresse) $TexteCellule .= "<tr><td>Adresse:</td><td>".$Event->Adresse."</td></tr>";
                if (($Event->serontPresents)&&(($Event->Etat=="O")||($Event->Etat=="F"))) {
                    
                    $TexteCellule .= "<tr><td>Seront présents: (".$nombreJoueursPresents.")</td><td><ul class=\"ListePresents\">";
                    foreach($Event->serontPresents as $JoueurPresent=>$JoueurSeraPresent){
                        
                        if ($Joueurs[$JoueurPresent]->Email) {
                            // Ajout le mail de l'inscrit
                            $Emails .= (($Emails)?";":"").$Joueurs[$JoueurPresent]->Email;
                        }
                        
                        if (($Joueurs[$JoueurPresent]->Etat=="V")&&(($Joueurs[$JoueurPresent]->Accord=="o")||($Joueurs[$JoueurPresent]->Pseudonyme==$Joueur->Pseudonyme))){
                            $TexteCellule .= "<li><a href=\"".$PHP_SELF."?Page=membres&amp;Membre=".$Joueurs[$JoueurPresent]->Pseudonyme."\">".$Joueurs[$JoueurPresent]->Prenom." ".$Joueurs[$JoueurPresent]->Nom."</a></li>";
                        }else{
                            $TexteCellule .= "<li>".$Joueurs[$JoueurPresent]->Prenom." ".$Joueurs[$JoueurPresent]->Nom."</li>";
                        }
                    }
                    
                    $TexteCellule .= "</ul></td></tr>";

                }
                if (($Event->serontIndisponibles)&&(($Event->Etat=="O")||($Event->Etat=="F"))) {
                    $nombreJoueursIndisponibles = count($Event->serontIndisponibles);
                    $TexteCellule .= "<tr><td>Seront indisponibles: (".$nombreJoueursIndisponibles.")</td><td><ul class=\"ListePresents\">";
                    foreach($Event->serontIndisponibles as $JoueurIndisponible=>$JoueurSeraIndisponible){
                        if (($Joueurs[$JoueurIndisponible]->Etat=="V")&&(($Joueurs[$JoueurIndisponible]->Accord=="o")||($Joueurs[$JoueurIndisponible]->Pseudonyme==$Joueur->Pseudonyme))){
                            $TexteCellule .= "<li><a href=\"".$PHP_SELF."?Page=membres&amp;Membre=".$Joueurs[$JoueurIndisponible]->Pseudonyme."\">".$Joueurs[$JoueurIndisponible]->Prenom." ".$Joueurs[$JoueurIndisponible]->Nom."</a></li>";
                        }else{
                            $TexteCellule .= "<li>".$Joueurs[$JoueurIndisponible]->Prenom." ".$Joueurs[$JoueurIndisponible]->Nom."</li>";
                        }
                    }
                    $TexteCellule .= "</ul></td></tr>";
                }
                if (($Event->etaientPresents)&&($Event->Etat=="T")) {
                    $TexteCellule .= "<tr><td>Etaient présents:</td><td><ul class=\"ListePresents\">";
                    $Virgule="";
                    $CompteurPresents=0;
                    //Liste des joueurs présents
                    foreach($Event->etaientPresents as $JoueurPresent=>$JoueurEtaitPresent){
                        if ($JoueurEtaitPresent=="o"){
                            if (($Joueurs[$JoueurPresent]->Etat=="V")&&(($Joueurs[$JoueurPresent]->Accord=="o")||($Joueurs[$JoueurPresent]->Pseudonyme==$Joueur->Pseudonyme))){
                                $TexteCellule .= "<li><a href=\"".$PHP_SELF."?Page=membres&amp;Membre=".$Joueurs[$JoueurPresent]->Pseudonyme."\">".$Joueurs[$JoueurPresent]->Prenom." ".$Joueurs[$JoueurPresent]->Nom."</a></li>";
                            }else{
                                $TexteCellule .= "<li>".$Joueurs[$JoueurPresent]->Prenom." ".$Joueurs[$JoueurPresent]->Nom."</li>";
                            }
                        }
                    }
                    $TexteCellule .= "</ul></td></tr>";
                }
                if(($Key=="SEANCE")||($Key=="ASSO")){
                    //C'est une séance de progrès ou un evènement de l'asso
                    $Style=ucFirst(strToLower($Key));
                }else if ($Equipes[$Key]){
                    //C'est une rencontre d'une équipe
                    if ($ErreurDonnees) {eval("$"."Resultat = $"."Resultat".$Event->Libelle.$Event->DateHeure.";");}else{$Resultat = $Event->Resultat;}
                    $Style = ($nombreJoueursPresents >= $EquipeComplete)?"RencontreComplet":"RencontreIncomplet";
                    if (trim($Event->Resultat)) {
                        $TexteCellule .= "<tr><td>Résultat:</td><td><table class=\"scoretableau\"><tr class=\"ScoreTitre\"><td colspan=\"6\"> Locaux <div class=\"Score\">".substr($Event->Resultat, 0, 1)."</div> / <div class=\"Score\">".substr($Event->Resultat, 1, 1)."</div> Visiteurs </td></tr><tr class=\"ScoreSet\"><td>Set</td><td>1</td><td>2</td><td>3</td><td>4</td><td>5</td></tr><tr class=\"ScoreSet\"><td>L</td><td><div class=\"Score\">".substr($Event->Resultat, 2, 2)."</div></td><td><div class=\"Score\">".substr($Event->Resultat, 6, 2)."</div></td><td><div class=\"Score\">".substr($Event->Resultat, 10, 2)."</div></td><td><div class=\"Score\">".substr($Event->Resultat, 14, 2)."</div></td><td><div class=\"Score\">".substr($Event->Resultat, 18, 2)."</div></td></tr><tr class=\"ScoreSet\"><td>V</td><td><div class=\"Score\">".substr($Event->Resultat, 4, 2)."</div></td><td><div class=\"Score\">".substr($Event->Resultat, 8, 2)."</div></td><td><div class=\"Score\">".substr($Event->Resultat, 12, 2)."</div></td><td><div class=\"Score\">".substr($Event->Resultat, 16, 2)."</div></td><td><div class=\"Score\">".substr($Event->Resultat, 20, 2)."</div></td></tr></table></td></tr>";
                    }
                }else continue;//Ce n'est pas une evènement reconnu
                if ($Event->Analyse) $TexteCellule .= "<tr><td>Commentaire:</td><td><div class=\"Commentaire\">".str_replace("\n", "<br/>", $Event->Analyse)."</div></td></tr>";
                if (is_file($RepertoireRelevesFNP."FNP_".$Event->DateHeure."_".$Event->Libelle.".xls")) $TexteCellule .= "<tr><td>Relevé FNP:</td><td><a href=\"".$RepertoireRelevesFNP."FNP_".$Event->DateHeure."_".$Event->Libelle.".xls\">Disponible ici</a></td></tr>";
                if ((($Joueur->DieuToutPuissant=="o")||($Joueur->Pseudonyme==$Equipes[$Event->Libelle]->Responsable)||($Joueur->Pseudonyme==$Equipes[$Event->Libelle]->Supleant))&&($Event->Etat=="F")){
                    $TexteCellule .= "<tr><td>Disponible:</td><td><a href=\"FicheDePresence.php?Evenement=".$Event->Libelle."&amp;Jour=".$Event->DateHeure."\">Télécharger le tableau des présence</a></td></tr>";
                }
                $TexteCellule .= "</table>";
            }
            print("\t<tr>\n\t\t<td class=\"".$Style." FicheEvent\">".$TexteCellule);
            if (($Joueur->DieuToutPuissant=="o")||((($Joueur->Pseudonyme==$Equipes[$Event->Libelle]->Responsable)||($Joueur->Pseudonyme==$Equipes[$Event->Libelle]->Supleant))&&(($Event->Etat=="O")||($Event->Etat=="F")))) {
                
                //------------------------
                //---Menu deroulant saisie de seraPresent
                //------------------------
                
                $TxtTypeResponsable = ($Joueur->Pseudonyme==$Equipes[$Event->Libelle]->Responsable)?"que responsable":"que suppléant";
                $TxtTypeEquipe = " de l'équipe";
                if ($Event->Libelle=="SEANCE") $TxtTypeResponsable .= " des absences";
                if ($Event->Libelle=="ASSO") $TxtTypeResponsable .= " des événements";
                if ($Joueur->DieuToutPuissant=="o") $TxtTypeResponsable = "qu'administrateur";
                if (($Event->Etat=="O")||((($Event->Etat=="T")||(($Event->Etat=="F")))&&($Joueur->DieuToutPuissant=="o"))){
                    
                    ?>
                    
                 
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    <input type="hidden" id="estDerouleseraPresent<?=$Event->Libelle.$Event->DateHeure?>" name="estDerouleseraPresent<?=$Event->Libelle.$Event->DateHeure?>" value="n"/>
                    <div id="DerouleseraPresent<?=$Event->Libelle.$Event->DateHeure?>" class="DerouleRouleau">
                    
                    <p>
                    <a href="mailto:<?=$Emails?>">Envoyer un mail</a>  |
                    <a href="javascript:Deroule('seraPresent<?=$Event->Libelle.$Event->DateHeure?>');">Seront présents</a>
                    </p>
                    
                    </div>
                    
                    
                    
                    <div id="EnrouleseraPresent<?=$Event->Libelle.$Event->DateHeure?>" class="Rouleau"><p><a href="javascript:Enroule('seraPresent<?=$Event->Libelle.$Event->DateHeure?>');">Cacher les options</a></p></div>
                    <div class="Rouleau" id="RouleauseraPresent<?=$Event->Libelle.$Event->DateHeure?>">
                    En tant <?=$TxtTypeResponsable?>
                    <br/>vous pouvez saisir la présence pour les autres membres
                    <table>
                    <tr>
                    <td class="Colone1 TitreListeJoueurs">Joueur</td>
                    <td class="Colone2 TitreListeJoueurs">Absent</td>
                    <td class="Colone2 TitreListeJoueurs">Présent</td>
                    </tr>
                    </table>
                    <div class="ListeJoueurs">
                    <table>
                    <?
                    $Compteur=1;
                    foreach ($Joueurs as $UnJoueur){
                        if (((($Event->etaitPresent($UnJoueur->Pseudonyme))||($Event->etaitAbsent($UnJoueur->Pseudonyme)||($Event->seraPresent($UnJoueur->Pseudonyme))||($Equipes[$Event->Libelle]->faisPartie($UnJoueur->Pseudonyme)))&&($Event->Etat=="T"))||((($Event->Etat=="O")||($Event->Etat=="F"))&&(($Event->seraPresent($UnJoueur->Pseudonyme))||($Equipes[$Event->Libelle]->faisPartie($UnJoueur->Pseudonyme)))&&(($UnJoueur->Pseudonyme<>$Joueur->Pseudonyme)||(($Joueur->DieuToutPuissant=="o")&&($RaisonBloque)))))&&($UnJoueur->Etat<>"E")){
                            print("\n\t\t\t\t\t\t<tr class=\"".(($Compteur==-1)?"":"Grise")."\"><td class=\"Colone1\">".$UnJoueur->Prenom." ".$UnJoueur->Nom."</td><td class=\"Colone2\"><input type=\"radio\" name=\"seraPresent".$UnJoueur->Pseudonyme.$Event->Libelle.$Event->DateHeure."\" value=\"n\"".(($Event->seraPresent($UnJoueur->Pseudonyme))?"":" checked=\"checked\"")." /></td><td class=\"Colone2\"><input type=\"radio\" name=\"seraPresent".$UnJoueur->Pseudonyme.$Event->Libelle.$Event->DateHeure."\" value=\"o\"".(($Event->seraPresent($UnJoueur->Pseudonyme))?" checked=\"checked\"":"")." /></td></tr>");
                            $Compteur = -$Compteur;
                        }
                    }
                    ?>			</table>
                    <?
                    if (($Equipes[$Event->Libelle]->TousJoueurs=="n")&&($Joueur->DieuToutPuissant=="o")){
                        print("\n\t\t\tDépanner l'équipe avec <select name=\"Depanne".$Event->Libelle.$Event->DateHeure."\">");
                        print("\n\t\t\t\t<option value=\"\" selected=\"selected\"></option>");
                        foreach ($Joueurs as $UnJoueur){
                            if ((!$Event->seraPresent($UnJoueur->Pseudonyme))&&(!$Equipes[$Event->Libelle]->faisPartie($UnJoueur->Pseudonyme))&&($UnJoueur->License<>"0000-00-00")){
                                print("\n\t\t\t\t<option value=\"".$UnJoueur->Pseudonyme."\">".$UnJoueur->Prenom." ".$UnJoueur->Nom."</option>");
                            }
                        }
                        print("\n\t\t\t</select>");
                    }
                    ?>
                    
                    </div>
                    <?if (($Event->Etat=="O")&&(ConvertisDate($Event->DateHeure, "PHP") < mkTime()+($FermetureEvenementAvant*3600))){?>			<br/><br/><input type="checkbox" name="FermeEvenement<?=$Event->Libelle.$Event->DateHeure?>" />Fermer l'événement<a href="javascript:alert('Cette action empèchera toute saisie des absences prévues\npar quelqu\'un d\'autre que l\'administrateur pour cet événement!!!')">-&gt; a savoir! &lt;-</a><?}?>
                        <br/><br/>Remarque: Les changements ne seront pris en compte que si ce menu est déroulé!
                        <p>Saisissez la présence de chaque membre et validez (en bas)</p>
                        </div>
                        <?
                    }
                    //------------------------
                    //---Fin Menu deroulant saisie de seraPresent
                    //------------------------
                    
                    //------------------------
                    //---Menu deroulant saisie de etaitPresent
                    //------------------------
                    
                    if ((($Event->Etat=="F")||(($Event->Etat=="T")&&($Joueur->DieuToutPuissant=="o")))&&($EvenementTerminé)){
                        ?>
                        
                        <input type="hidden" id="estDerouleetaitPresent<?=$Event->Libelle.$Event->DateHeure?>" name="estDerouleetaitPresent<?=$Event->Libelle.$Event->DateHeure?>" value="n"/>
                        <div id="DerouleetaitPresent<?=$Event->Libelle.$Event->DateHeure?>" class="DerouleRouleau"><p><a href="javascript:Deroule('etaitPresent<?=$Event->Libelle.$Event->DateHeure?>');">Etaient présents<?=(($Event->Libelle<>"SEANCE")&&($Event->Libelle<>"ASSO"))?" / Résultats":""?></a></p></div>
                        <div id="EnrouleetaitPresent<?=$Event->Libelle.$Event->DateHeure?>" class="Rouleau"><p><a href="javascript:Enroule('etaitPresent<?=$Event->Libelle.$Event->DateHeure?>');">Cacher les options</a></p></div>
                        <div class="Rouleau" id="RouleauetaitPresent<?=$Event->Libelle.$Event->DateHeure?>">
                        En tant <?=$TxtTypeResponsable?>
                        <br/>vous pouvez saisir la présence pour les autres membres
                        <br/>
                        <?
                        if (($Event->Libelle<>"ASSO")&&($Event->Libelle<>"SEANCE")){
                            ?>
                            <br/>
                            <table class="SaisieResultat"><!--tableau des résultats-->
                            <tr><td colspan="7" class="TitreListeJoueurs">Résultats de la rencontre:</td></tr>
                            <tr><td></td><td class="TitreListeJoueurs">Sets</td><td class="TitreListeJoueurs">1</td><td class="TitreListeJoueurs">2</td><td class="TitreListeJoueurs">3</td><td class="TitreListeJoueurs">4</td><td class="TitreListeJoueurs">5</td></tr>
                            <tr><td class="TitreListeJoueurs">Locaux</td><td><input type="text" name="SetsEquipe<?=$Event->Libelle.$Event->DateHeure?>" value="<?=(int)substr($Resultat, 0, 1)?>" size="2" maxlength="1" /></td><td><input type="text" name="Set1Equipe<?=$Event->Libelle.$Event->DateHeure?>" value="<?=(int)substr($Resultat, 2, 2)?>" size="2" maxlength="2" /></td><td><input type="text" name="Set2Equipe<?=$Event->Libelle.$Event->DateHeure?>" value="<?=(int)substr($Resultat, 6, 2)?>" size="2" maxlength="2" /></td><td><input type="text" name="Set3Equipe<?=$Event->Libelle.$Event->DateHeure?>" value="<?=(int)substr($Resultat, 10, 2)?>" size="2" maxlength="2" /></td><td><input type="text" name="Set4Equipe<?=$Event->Libelle.$Event->DateHeure?>" value="<?=(int)substr($Resultat, 14, 2)?>" size="2" maxlength="2" /></td><td><input type="text" name="Set5Equipe<?=$Event->Libelle.$Event->DateHeure?>" value="<?=(int)substr($Resultat, 18, 2)?>" size="2" maxlength="2" /></td></tr>
                            <tr><td class="TitreListeJoueurs">Visiteurs</td><td><input type="text" name="SetsAdversaire<?=$Event->Libelle.$Event->DateHeure?>" value="<?=(int)substr($Resultat, 1, 1)?>" size="2" maxlength="1" /></td><td><input type="text" name="Set1Adversaire<?=$Event->Libelle.$Event->DateHeure?>" value="<?=(int)substr($Resultat, 4, 2)?>" size="2" maxlength="2" /></td><td><input type="text" name="Set2Adversaire<?=$Event->Libelle.$Event->DateHeure?>" value="<?=(int)substr($Resultat, 8, 2)?>" size="2" maxlength="2" /></td><td><input type="text" name="Set3Adversaire<?=$Event->Libelle.$Event->DateHeure?>" value="<?=(int)substr($Resultat, 12, 2)?>" size="2" maxlength="2" /></td><td><input type="text" name="Set4Adversaire<?=$Event->Libelle.$Event->DateHeure?>" value="<?=(int)substr($Resultat, 16, 2)?>" size="2" maxlength="2" /></td><td><input type="text" name="Set5Adversaire<?=$Event->Libelle.$Event->DateHeure?>" value="<?=(int)substr($Resultat, 20, 2)?>" size="2" maxlength="2" /></td></tr>
                            <tr><td></td><td colspan="6" class="Remarque">(Mettre 0 si set non joué)</td></tr>
                            </table>
                            <br/>
                            <?
                        }
                        ?>
                        <table><!--tableau des titres présence-->
                        <tr>
                        <td class="Colone1 TitreListeJoueurs">Joueur</td>
                        <td class="Colone2 TitreListeJoueurs">Absent</td>
                        <td class="Colone2 TitreListeJoueurs">Présent</td>
                        </tr>
                        </table>
                        <div class="ListeJoueurs">
                        <table class="SaisiePresence">
                        <?
                        $Compteur=1;
                        foreach ($Joueurs as $UnJoueur){
                            if (((($Event->etaitPresent($UnJoueur->Pseudonyme))||($Event->etaitAbsent($UnJoueur->Pseudonyme))||($Event->seraPresent($UnJoueur->Pseudonyme))||($Equipes[$Event->Libelle]->faisPartie($UnJoueur->Pseudonyme)))&&($Event->Etat=="T"))||(($Event->Etat=="F")&&(($Equipes[$Event->Libelle]->faisPartie($UnJoueur->Pseudonyme))||($Event->seraPresent($UnJoueur->Pseudonyme))))||(($UnJoueur->Etat=="E")&&($Event->Libelle=="SEANCE"))){
                                print("\n\t\t\t\t\t\t<tr class=\"".(($Compteur==-1)?"":"Grise")."\"><td class=\"Colone1\">".$UnJoueur->Prenom." ".$UnJoueur->Nom."</td><td class=\"Colone2\"><input type=\"radio\" name=\"etaitPresent".$UnJoueur->Pseudonyme.$Event->Libelle.$Event->DateHeure."\" value=\"n\"".(((($Event->seraPresent($UnJoueur->Pseudonyme)&&($Event->Etat=="F"))||(($Event->etaitPresent($UnJoueur->Pseudonyme)&&($Event->Etat=="T")))))?"":" checked=\"checked\"")." /></td><td class=\"Colone2\"><input type=\"radio\" name=\"etaitPresent".$UnJoueur->Pseudonyme.$Event->Libelle.$Event->DateHeure."\" value=\"o\"".(((($Event->seraPresent($UnJoueur->Pseudonyme)&&($Event->Etat=="F"))||(($Event->etaitPresent($UnJoueur->Pseudonyme)&&($Event->Etat=="T")))))?" checked=\"checked\"":"")." /></td></tr>");
                                $Compteur = -$Compteur;
                            }
                        }
                        ?>			</table>
                        </div>
                        <?
                        if ($Event->Etat=="F"){
                            ?>
                            
                            <br/><br/><input type="checkbox" name="TermineEvent<?=$Event->Libelle.$Event->DateHeure?>" />Confirme les données<a href="javascript:alert('Cette action empèchera toute saisie des absences effectives\npar quelqu\'un d\'autre que l\'administrateur pour cet événement\n et le passera à l\'état terminé!!!')">-&gt; a savoir! &lt;-</a>
                            <?
                        }else{
                            ?>
                            
                            <input type="hidden" name="TermineEvent<?=$Event->Libelle.$Event->DateHeure?>" value="oui" />
                            <?
                        }
                        ?>
                        
                        <br/><br/>Remarque: Les changements ne seront pris en compte que si ce menu est déroulé!
                        <p>Saisissez la présence de chaque membre et validez (en bas)</p>
                        </div>
                        
                        <?
                    }
                    //------------------------
                    //---Fin Menu deroulant saisie de etaitPresent
                    //------------------------
                }
                if (($RaisonBloque)&&(substr($Event->Etat, 0, 1)<>"A")) print("\t\t<p class=\"Bloque\">".$RaisonBloque."</p>");
                print("</td>\n\t</tr>\n");
            }
        }
        ?>
        
        </table>
        <?
        if(!$Termine){
            ?>
            <input type="submit" value="VALIDER" class="Bouton Action"/>
            <?
        }
        ?>
        </div>
        </form>
        
        <?
    }else{
        /********************** Aucun événement ce jour **********************/
        ?>
        Inutile de forcer l'URL!!!!
        
        <?
    }
    ?>
    <div class="Explications">
    <a href="#HautDePage">Haut de page</a><br/>
    </div>
