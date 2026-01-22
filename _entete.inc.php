<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

include("PASSWD/_passwrds.inc.php");
//Tentative de connexion à la base de données
$ConnectDB=true;
$motdepassesqlok=$motdepassesql{4}.$motdepassesql{1}.$motdepassesql{7}.$motdepassesql{0}.$motdepassesql{9}.$motdepassesql{12}.$motdepassesql{14}.$motdepassesql{6};
if (!($sdblink = mySql_connect($basesql, $utilisateursql, $motdepassesqlok))){
 	$ConnectDB=false;
	}
if (!(mySql_select_db($labasededonnees, $sdblink))){
	$ConnectDB=false;
	}

if ($ConnectDB){
	$Pseudonyme=$_POST["Pseudonyme"];
	$Password=$_POST["Password"];
	//si la connection à la base de donnée est réussite
	if (($Pseudonyme)&&($Password)){
		//les deux champs sont renseigné, tentative d'identification
		if ($DBJoueur = mySql_fetch_object(mySql_query("SELECT * FROM NPVB_Joueurs WHERE (Pseudonyme='". $Pseudonyme ."' AND Password=OLD_PASSWORD('".$Password."'))", $sdblink))){
			//Joueur enregistré dans la base avec mot de passe ok
			if ($DBJoueur->Etat=="V"){
				//Démarre la session et y enregistre le pseudonyme
				session_start();
				session_register("Pseudonyme");
				//recupère toutes les infos du joueur
				$Joueur = $DBJoueur; 
			}else{
				$Joueur=null;
				$ErreurDonnees["Login"] .= "Ce compte n'est plus valide, contactez le responsable pour plus d'informations.<br/>";
			}
		}else{
			//Joueur non identifie
			$Joueur=null;
			$ErreurDonnees["Login"] .= "Le pseudonyme ou le mot de passe est incorrect<br/>";
		}
	}else{//Si pas login demandé, on regarde si la personne est deja sous session
		$Pseudonyme=null;
		session_start();
		if ((!$_SESSION['Pseudonyme'])||($Action=="deloguer")) {
			//session terminee ou demande de deconnexion
			$_SESSION['Pseudonyme']="";
			session_destroy();
			$Joueur=null;
		}else{
			//recupère toutes les infos du joueur
			$Pseudonyme = $_SESSION['Pseudonyme'];
			if ($DBJoueur = mySql_fetch_object(mySql_query("SELECT * FROM NPVB_Joueurs WHERE (Pseudonyme='". $Pseudonyme ."' AND Etat='V')", $sdblink))){
				//Récupère les infos du joueur
				$Joueur = $DBJoueur; 
			}else{
				//Le joueur n'est plus enregistré, ou compte devenu invalide
				session_destroy();
				$Joueur=null;
				$Page="accueil";
				$ErreurDonnees["Login"] .= "Votre compte a été désactivé, contactez le responsable pour plus d'informations.<br/>";
			}
			
		}
	}
}
?>
