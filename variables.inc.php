<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

//**********************************VARIABLES GLOBALES A PARAMETRABLES************************************************//
//+++++++++expressions régulières: caractères autorisés (voir doc avant de changer)
// Ancienne regex (ereg déprécié): $EregTexteSeulement="[^a-zA-Z0-9_\ ',é@àèùâêîôûëïüÿçÀÈÙÂÊÎÔÛËÏÜŸÇÉ-]";
// Nouvelle regex UTF-8 compatible avec preg_match: interdit seulement les caractères dangereux
$EregTexteSeulement="/[<>$]/u"; // Interdit < > $ pour éviter les injections
$EregTexteComplet="/[<>$]/u"; // Interdit < > $ pour éviter les injections

//++++++++++++++Durées
$FermetureEvenementAvant=4*24;//nombre d'heures avant l'event pour permettre sa fermeture
$DureeEvenement=1;//nombre d'heures apres le debut event pour la saisie des resultats


//++++++++++++++Membres
$NombreMembresParLigne = 6;//a l'affichage des membres
$MembreModifCoordonnes=true;//Attention, le bouton retour est à modifier si on met la valeur à vrai!!!

$SujetMailCreationCompte = "Votre compte a été créé sur le site de " . $config['club_sigle'];
$CorpsMailCreationCompte = "Bonjour,\n\nVotre compte a été créé pour accéder au calendrier de " . $config['club_nom'] . ".\n\nvotre login: $Membre\nvotre mot de passe: $MotDePasse\n\nIdentifiez vous sur le site pour accéder à la liste des membres et saisir vos présences futures aux séances et aux matches, pour une meilleure gestion du club.\n\nVous pouvez changer vos coordonnées et votre mot de passe en visualisant vos coordonnées dans le menu 'Membres'.\nVous pouvez aussi choisir de ne pas diffuser vos coordonnées aux autres membres en cochant 'accord pour diffusion'='non'\n\nA très bientôt sur " . $config['club_url'] . " .\n\n ATTENTION. NE PAS REPONDRE A CET EMAIL : vous pouvez contacter " . $config['club_email'] . " en cas de besoin";
$SujetMailModifMotDePasse = "Votre mot de passe pour accéder au calendrier de " . $config['club_sigle'] . " a été changé";
$CorpsMailModifMotDePasse = "Bonjour,\n\nVotre nouveau mot de passe pour accéder au calendrier de " . $config['club_nom'] . " est: $MotDePasse\n\nJe vous rappelle que vous pouvez le modifier, ainsi que vos coordonnées, directement sur le site en visualisant votre fiche à partir du menu 'Membres'.\n\nA très bientôt sur " . $config['club_url'] . " .\n\n ATTENTION. NE PAS REPONDRE A CET EMAIL : vous pouvez contacter " . $config['club_email'] . " en cas de besoin";

//++++++++++++++Reinitialisation mot de passe
$DureeValiditeTokenHeures = 24; // Duree de validite du lien (en heures) - 24h pour laisser le temps aux utilisateurs
$LimiteDemandesParHeure = 3;   // Maximum de demandes de reset par heure (anti-spam)

$SujetMailDemandeReset = "Reinitialisation de votre mot de passe " . $config['club_sigle'];
$CorpsMailDemandeReset = "Bonjour,\n\nVous avez demande la reinitialisation de votre mot de passe pour acceder au calendrier de " . $config['club_nom'] . ".\n\nCliquez sur le lien ci-dessous pour definir un nouveau mot de passe :\n\n" . $config['club_url'] . "/index.php?Page=resetmotdepasse&Token=\$Token\n\nCe lien est valable pendant \$DureeValiditeTokenHeures heures.\n\nSi vous n'etes pas a l'origine de cette demande, ignorez cet email. Votre mot de passe actuel reste inchange.\n\nA tres bientot sur " . $config['club_url'] . " .\n\n ATTENTION. NE PAS REPONDRE A CET EMAIL : vous pouvez contacter " . $config['club_email'] . " en cas de besoin";

$SujetMailConfirmationReset = "Votre mot de passe " . $config['club_sigle'] . " a ete modifie";
$CorpsMailConfirmationReset = "Bonjour,\n\nVotre mot de passe pour acceder au calendrier de " . $config['club_nom'] . " vient d'etre modifie avec succes.\n\nSi vous n'etes pas a l'origine de ce changement, contactez immediatement un administrateur a " . $config['club_email'] . ".\n\nVous pouvez desormais vous connecter avec votre nouveau mot de passe sur " . $config['club_url'] . " .\n\nA tres bientot sur " . $config['club_url'] . " .\n\n ATTENTION. NE PAS REPONDRE A CET EMAIL : vous pouvez contacter " . $config['club_email'] . " en cas de besoin";


//++++++++++++++Divers
$EquipeComplete=6; //Nombre d'inscrits mini à un match pour qu'il s'affiche avec équipe complète

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
	if (strpos(getenv("HTTP_USER_AGENT"), $Navigateur) !== false) $SupportePNG = true;
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
