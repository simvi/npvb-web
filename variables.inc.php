<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

//**********************************VARIABLES GLOBALES A PARAMETRABLES************************************************//
//+++++++++expressions régulières: caractères autorisés (voir doc avant de changer)
$EregTexteSeulement="[^a-zA-Z0-9_\ ',ç€@éêèùàâûîôäëïöüÂÄÎÏÔÖÛÜÊË-]";
$EregTexteComplet="[$<>]";

//++++++++++++++Durées
$FermetureEvenementAvant=4*24;//nombre d'heures avant l'event pour permettre sa fermeture
$DureeEvenement=1;//nombre d'heures apres le debut event pour la saisie des resultats


//++++++++++++++Membres
$NombreMembresParLigne = 6;//a l'affichage des membres
$MembreModifCoordonnes=true;//Attention, le bouton retour est à modifier si on met la valeur à vrai!!!

$SujetMailCreationCompte = "Votre compte a été créé sur le site de NPVB";
$CorpsMailCreationCompte = "Bonjour,\n\nVotre compte a été créé pour accéder au calendrier de Nantes Plaisir du Volley Ball.\n\nvotre login: $Membre\nvotre mot de passe: $MotDePasse\n\nIdentifiez vous sur le site pour accéder à la liste des membres et saisir vos présences futures aux séances et aux matches, pour une meilleure gestion du club.\n\nVous pouvez changer vos coordonnées et votre mot de passe en visualisant vos coordonnées dans le menu 'Membres'.\nVous pouvez aussi choisir de ne pas diffuser vos coordonnées aux autres membres en cochant 'accord pour diffusion'='non'\n\nA très bientôt sur http://nantespvb.free.fr .\n\n ATTENTION. NE PAS REPONDRE A CET EMAIL : vous pouvez contacter nantespvb@gmail.com en cas de besoin";
$SujetMailModifMotDePasse = "Votre mot de passe pour accéder au calendrier de NPVB a été changé";
$CorpsMailModifMotDePasse = "Bonjour,\n\nVotre nouveau mot de passe pour accéder au calendrier de Nantes Plaisir du Volley Ball est: $MotDePasse\n\nJe vous rappelle que vous pouvez le modifier, ainsi que vos coordonnées, directement sur le site en visualisant votre fiche à partir du menu 'Membres'.\n\nA très bientôt sur http://nantespvb.free.fr .\n\n ATTENTION. NE PAS REPONDRE A CET EMAIL : vous pouvez contacter nantespvb@gmail.com en cas de besoin";


//++++++++++++++Divers
$EquipeComplete=6; //Nombre d'inscits mni à un match pour qu'il s'affiche avec équipe complète

$NavigateursSupportentPNG = array("Firefox/1.0", "Safari/85.8.1");


//**********************************VARIABLES GLOBALES A EVITER DE TOUCHER************************************************//
session_save_path("./sessions");
$dayarray = Array("Dimanche","Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi");
$montharray = Array(1 => "janvier","f&eacute;vrier","mars","avril","mai","juin","juillet","ao&ucirc;t","septembre","octobre","novembre","d&eacute;cembre");
$RepertoirePhotos = "Photos/";
$RepertoireImages = "Images/";
$RepertoireRelevesFNP = "RelevesFNP/";

$SupportePNG = false;
foreach ($NavigateursSupportentPNG as $Navigateur){ 
	if (ereg($Navigateur, getenv("HTTP_USER_AGENT"))) $SupportePNG = true;
}


$Maintenant = getDate();
if ((!$Mois)||(!$Annee)){
	$Mois = $Maintenant["mon"];
	$Annee = $Maintenant["year"];
	}	
$MoisAvant=($Mois==1)?12:$Mois-1;
$AnneeAvant=($Mois==1)?$Annee-1:$Annee;
$MoisApres=($Mois==12)?1:$Mois+1;
$AnneeApres=($Mois==12)?$Annee+1:$Annee;

?>