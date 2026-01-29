<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

//**********************************VARIABLES GLOBALES A PARAMETRABLES************************************************//
//+++++++++expressions r�guli�res: caract�res autoris�s (voir doc avant de changer)
$EregTexteSeulement="[^a-zA-Z0-9_\ ',�@������������������������-]";
$EregTexteComplet="[$<>]";

//++++++++++++++Dur�es
$FermetureEvenementAvant=4*24;//nombre d'heures avant l'event pour permettre sa fermeture
$DureeEvenement=1;//nombre d'heures apres le debut event pour la saisie des resultats


//++++++++++++++Membres
$NombreMembresParLigne = 6;//a l'affichage des membres
$MembreModifCoordonnes=true;//Attention, le bouton retour est � modifier si on met la valeur � vrai!!!

$SujetMailCreationCompte = "Votre compte a �t� cr�� sur le site de NPVB";
$CorpsMailCreationCompte = "Bonjour,\n\nVotre compte a �t� cr�� pour acc�der au calendrier de Nantes Plaisir du Volley Ball.\n\nvotre login: $Membre\nvotre mot de passe: $MotDePasse\n\nIdentifiez vous sur le site pour acc�der � la liste des membres et saisir vos pr�sences futures aux s�ances et aux matches, pour une meilleure gestion du club.\n\nVous pouvez changer vos coordonn�es et votre mot de passe en visualisant vos coordonn�es dans le menu 'Membres'.\nVous pouvez aussi choisir de ne pas diffuser vos coordonn�es aux autres membres en cochant 'accord pour diffusion'='non'\n\nA tr�s bient�t sur http://nantespvb.free.fr .\n\n ATTENTION. NE PAS REPONDRE A CET EMAIL : vous pouvez contacter nantespvb@gmail.com en cas de besoin";
$SujetMailModifMotDePasse = "Votre mot de passe pour acc�der au calendrier de NPVB a �t� chang�";
$CorpsMailModifMotDePasse = "Bonjour,\n\nVotre nouveau mot de passe pour acc�der au calendrier de Nantes Plaisir du Volley Ball est: $MotDePasse\n\nJe vous rappelle que vous pouvez le modifier, ainsi que vos coordonn�es, directement sur le site en visualisant votre fiche � partir du menu 'Membres'.\n\nA tr�s bient�t sur http://nantespvb.free.fr .\n\n ATTENTION. NE PAS REPONDRE A CET EMAIL : vous pouvez contacter nantespvb@gmail.com en cas de besoin";

//++++++++++++++Reinitialisation mot de passe
$DureeValiditeTokenHeures = 24; // Duree de validite du lien (en heures) - 24h pour laisser le temps aux utilisateurs
$LimiteDemandesParHeure = 3;   // Maximum de demandes de reset par heure (anti-spam)

$SujetMailDemandeReset = "Reinitialisation de votre mot de passe NPVB";
$CorpsMailDemandeReset = "Bonjour,\n\nVous avez demande la reinitialisation de votre mot de passe pour acceder au calendrier de Nantes Plaisir du Volley Ball.\n\nCliquez sur le lien ci-dessous pour definir un nouveau mot de passe :\n\nhttp://nantespvb.free.fr/index.php?Page=resetmotdepasse&Token=\$Token\n\nCe lien est valable pendant \$DureeValiditeTokenHeures heures.\n\nSi vous n'etes pas a l'origine de cette demande, ignorez cet email. Votre mot de passe actuel reste inchange.\n\nA tres bientot sur http://nantespvb.free.fr .\n\n ATTENTION. NE PAS REPONDRE A CET EMAIL : vous pouvez contacter nantespvb@gmail.com en cas de besoin";

$SujetMailConfirmationReset = "Votre mot de passe NPVB a ete modifie";
$CorpsMailConfirmationReset = "Bonjour,\n\nVotre mot de passe pour acceder au calendrier de Nantes Plaisir du Volley Ball vient d'etre modifie avec succes.\n\nSi vous n'etes pas a l'origine de ce changement, contactez immediatement un administrateur a nantespvb@gmail.com.\n\nVous pouvez desormais vous connecter avec votre nouveau mot de passe sur http://nantespvb.free.fr .\n\nA tres bientot sur http://nantespvb.free.fr .\n\n ATTENTION. NE PAS REPONDRE A CET EMAIL : vous pouvez contacter nantespvb@gmail.com en cas de besoin";


//++++++++++++++Divers
$EquipeComplete=6; //Nombre d'inscits mni � un match pour qu'il s'affiche avec �quipe compl�te

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