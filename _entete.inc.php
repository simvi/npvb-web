<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

// Credentials DB depuis config.php (chargé par index.php)

// Reimplementation de OLD_PASSWORD() de MySQL (supprime en MySQL 5.7)
function old_password_hash($password) {
	$nr  = 1345345333;
	$add = 7;
	$nr2 = 0x12345671;
	for ($i = 0; $i < strlen($password); $i++) {
		$c = ord($password[$i]);
		if ($c == 32 || $c == 9) continue; // ignore espaces et tabs
		$nr  = $nr ^ (((($nr & 63) + $add) * $c) + ($nr * 256));
		$nr  = (($nr % 4294967296) + 4294967296) % 4294967296;
		$nr2 = $nr2 + (($nr2 * 256) ^ $nr);
		$nr2 = (($nr2 % 4294967296) + 4294967296) % 4294967296;
		$add += $c;
	}
	$result1 = $nr  & 0x7FFFFFFF;
	$result2 = $nr2 & 0x7FFFFFFF;
	return sprintf("%08x%08x", $result1, $result2);
}

// Charge les rôles du joueur depuis NPVB_JoueurRoles et les attache à l'objet
// sous forme de tableau $Joueur->Roles (système de permissions, voir permissions.inc.php)
function chargerRolesJoueur($Joueur, $sdblink) {
	$Joueur->Roles = array();
	$pseudo = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);
	$res = mysql_query("SELECT Role FROM NPVB_JoueurRoles WHERE Pseudonyme='".$pseudo."'", $sdblink);
	if ($res) {
		while ($row = mysql_fetch_object($res)) {
			$Joueur->Roles[] = $row->Role;
		}
	}
	return $Joueur;
}

//Tentative de connexion a la base de donnees
$ConnectDB=true;
$motdepassesqlok = $config['db_pass'];

if (!($sdblink = mySql_connect($config['db_host'], $config['db_user'], $motdepassesqlok))){
	$ConnectDB=false;
	}
if (!(mySql_select_db($config['db_name'], $sdblink))){
	$ConnectDB=false;
	}

if ($ConnectDB){
	// Configure l'encodage de la connexion MySQL
	mysql_query("SET CHARACTER SET utf8mb4", $sdblink);
	mysql_query("SET NAMES utf8mb4", $sdblink);

	$Pseudonyme=$_POST["Pseudonyme"];
	$Password=$_POST["Password"];
	//si la connection a la base de donnee est reussie
	if (($Pseudonyme)&&($Password)){
		//les deux champs sont renseignes, tentative d'identification
		$hash = old_password_hash($Password);
		if ($DBJoueur = mySql_fetch_object(mySql_query("SELECT * FROM NPVB_Joueurs WHERE (Pseudonyme='". $Pseudonyme ."' AND Password='".$hash."')", $sdblink))){
			//Joueur enregistre dans la base avec mot de passe ok
			if ($DBJoueur->Etat=="V"){
				//Demarre la session et y enregistre le pseudonyme
				session_start();
				$_SESSION['Pseudonyme'] = $Pseudonyme;
				//recupere toutes les infos du joueur
				$Joueur = $DBJoueur;
				chargerRolesJoueur($Joueur, $sdblink);
			}else{
				$Joueur=null;
				$ErreurDonnees["Login"] .= "Ce compte n'est plus valide, contactez le responsable pour plus d'informations.<br/>";
			}
		}else{
			//Joueur non identifie
			$Joueur=null;
			$ErreurDonnees["Login"] .= "Le pseudonyme ou le mot de passe est incorrect<br/>";
		}
	}else{//Si pas login demande, on regarde si la personne est deja sous session
		$Pseudonyme=null;
		session_start();
		if ((!$_SESSION['Pseudonyme'])||($Action=="deloguer")) {
			//session terminee ou demande de deconnexion
			$_SESSION['Pseudonyme']="";
			session_destroy();
			$Joueur=null;
		}else{
			//recupere toutes les infos du joueur
			$Pseudonyme = $_SESSION['Pseudonyme'];
			if ($DBJoueur = mySql_fetch_object(mySql_query("SELECT * FROM NPVB_Joueurs WHERE (Pseudonyme='". $Pseudonyme ."' AND Etat='V')", $sdblink))){
				//Recupere les infos du joueur
				$Joueur = $DBJoueur;
				chargerRolesJoueur($Joueur, $sdblink);
			}else{
				//Le joueur n'est plus enregistre, ou compte devenu invalide
				session_destroy();
				$Joueur=null;
				$Page="accueil";
				$ErreurDonnees["Login"] .= "Votre compte a ete desactive, contactez le responsable pour plus d'informations.<br/>";
			}

		}
	}
}
?>
