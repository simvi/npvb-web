<?php


if ( !function_exists('json_decode') ){
function json_encode($data) {
        switch ($type = gettype($data)) {
            case 'NULL':
                return 'null';
            case 'boolean':
                return ($data ? 'true' : 'false');
            case 'integer':
            case 'double':
            case 'float':
                return $data;
            case 'string':
                return '"' . addslashes($data) . '"';
            case 'object':
                $data = get_object_vars($data);
            case 'array':
                $output_index_count = 0;
                $output_indexed = array();
                $output_associative = array();
                foreach ($data as $key => $value) {
                    $output_indexed[] = json_encode($value);
                    $output_associative[] = json_encode($key) . ':' . json_encode($value);
                    if ($output_index_count !== NULL && $output_index_count++ !== $key) {
                        $output_index_count = NULL;
                    }
                }
                if ($output_index_count !== NULL) {
                    return '[' . implode(',', $output_indexed) . ']';
                } else {
                    return '{' . implode(',', $output_associative) . '}';
                }
            default:
                return ''; // Not supported
        }
    }
}
		
/*
// on se connecte à notre base  pour recuperer les data
$base = mysql_connect ("sql.free.fr", "nantespvb", $databasepassword);  
mysql_select_db ("nantespvb", $base) ;  
$query=$_POST['query'];

$query = str_replace("#", "'", $query);

$req = mysql_query($query);

$rows = array();
while($r = mysql_fetch_assoc($req)) {
	$rows[] = $r;
			}
print json_encode($rows);

mysql_close();

*/

	// datas connexion bdd
	$server = "ftpperso.free.fr";
	$database = "nantespvb";
	$username = "nantespvb";
	$pwd = "wozd7pdo";

		$mySql = mysql_connect($server, $username, $pwd);
		
		 if(!$mySql) {
       		 die('Unable to establish connection to MYSQL server on ' . $host . ' for user ' . $dbuser);
   		 }
    	else
    	{ 
      		  //mysql_set_charset('utf8',$connection); // Only available for (PHP 5 >= 5.2.3)
      		  mysql_query("SET CHARACTER SET utf8",$mySql); // Ensures correct encoding
      		  mysql_query("SET NAMES utf8",$mySql); // Ensures correct encoding
   		 }
    
    
		
		mysql_select_db($database, $mySql);
				
	if ($_GET['type'] == "get_members") {

		$request = "SELECT Pseudonyme, DieuToutPuissant, Nom, Prenom, Sexe, DateNaissance, Profession, Adresse, CPVille, Telephones, Email, Accord, NumeroLicence FROM NPVB_Joueurs WHERE etat = 'V'";
		
		//echo ($request);

		$result = mysql_query($request) or die('Errant query:  '.$query);
	
	 	/* create one master array of the records */
	    $posts = array();
	    if(mysql_num_rows($result)) {
	       while($post = mysql_fetch_assoc($result)) {
	          $posts[] = array($post);
	       }
	    }
	
	   /* output in necessary format */
	    header('Content-type: application/json');
	    echo json_encode($posts);
	}
		else if ($_GET['type'] == "get_appartenances") {

		$request = "SELECT * FROM NPVB_Appartenance";	
		
		$result = mysql_query($request) or die('Errant query:  '.$query);
	
	 	/* create one master array of the records */
	    $posts = array();
	    if(mysql_num_rows($result)) {
	       while($post = mysql_fetch_assoc($result)) {
	          $posts[] = $post;
	       }
	    }
	
	   /* output in necessary format */
	    header('Content-type: application/json');
	    echo json_encode($posts);
	    
	//
		}
		else if ($_GET['type'] == "get_events") {

		$request = "SELECT DateHeure, Libelle, Etat, Titre, Intitule, Lieu, Adresse, Adversaire, Analyse, InscritsMax FROM NPVB_Evenements WHERE (DateHeure > 20190000000000 AND etat != 'I')";	
		$result = mysql_query($request) or die('Errant query:  '.$query);
	
	 	/* create one master array of the records */
	    $posts = array();
	    if(mysql_num_rows($result)) {
	       while($post = mysql_fetch_assoc($result)) {
	          $posts[] = $post;
	       }
	    }
	
	   /* output in necessary format */
	    header('Content-type: application/json');
	    echo json_encode($posts);
	    
	//
		}	else if ($_GET['type'] == "connection") {
			
		$identifiant = $_GET['id'];
		$pwd = $_GET['pwd'];
		
		$request = "SELECT Pseudonyme FROM NPVB_Joueurs WHERE etat = 'V' AND Pseudonyme = '".$identifiant."' AND Password = OLD_PASSWORD('".$pwd."')";
		
		//echo ($request);
		//echo "\n";
		//echo ($_GET['pwd']);
		
		$result = mysql_query($request) or die('Errant query:  '.$query);

	 	/* create one master array of the records */
	    $posts = array();
	    if(mysql_num_rows($result)) {
	       while($post = mysql_fetch_assoc($result)) {
	          $posts[] = $post;
	       }
	    }
	
	   /* output in necessary format */
	    header('Content-type: application/json');
	    echo json_encode($posts);
	    
	//
	}
	else if ($_GET['type'] == "get_presence") {
				
		$request = "SELECT Joueur, Libelle, Prevue FROM NPVB_Presence WHERE (DateHeure = ".$_GET['date'].")";
		
		//echo($request);
		
		$result = mysql_query($request) or die('Errant query:  '.$query);
	
	 	/* create one master array of the records */
	    $posts = array();
	    if(mysql_num_rows($result)) {
	       while($post = mysql_fetch_assoc($result)) {
	          $posts[] = $post;
	       }
	    }
	
	   /* output in necessary format */
	    header('Content-type: application/json');
	    echo json_encode($posts);
	    
	//
		}
	else if ($_GET['type'] == "get_presences") {

		$pseudo = $_GET['pseudo'];
		$presence = $_GET['presence'];
			
		$request = "SELECT Joueur, Libelle, DateHeure, Prevue FROM NPVB_Presence WHERE (Joueur = '".$pseudo."' AND Prevue='".$presence."')";
		
		//echo($request);
		
		$result = mysql_query($request) or die('Errant query:  '.$query);
	
		/* create one master array of the records */
		$posts = array();
		if(mysql_num_rows($result)) {
			while($post = mysql_fetch_assoc($result)) {
				$posts[] = $post;
			}
		}
	
		/* output in necessary format */
		header('Content-type: application/json');
		echo json_encode($posts);
		
	//
		}
		else if ($_GET['type'] == "inscription") {

			$date = $_GET['date'];
			$pseudo = $_GET['pseudo'];
			$libelle = $_GET['libelle'];
			$presence = $_GET['presence'];

			$testPresenceRequest = "SELECT * FROM NPVB_Presence WHERE (Joueur='".$pseudo."' AND DateHeure='".$date."' AND Libelle='".$libelle."')";

			if ($presence == 'n') { // DESINSCRIPTION
				if (mySql_fetch_object(mySql_query($testPresenceRequest))) {

					//La personne était inscrite, supprimer la ligne
					$request = "DELETE FROM NPVB_Presence WHERE (Joueur='".$pseudo."' AND DateHeure='".$date."' AND Libelle='".$libelle."')";
					$result = mysql_query($request) or die('Errant query:  '.$query);

					header('Content-type: application/json');
					$error = array('status' => $result);
					echo json_encode($error);

				}
				else {

					header('Content-type: application/json');
					$error = array('status' => false);
					$error['message'] = "Votre présence n'était pas enregistrée.";
					echo json_encode($error);

				}
			} else if ($presence == '!') { // ABSENT
				if (mySql_fetch_object(mySql_query($testPresenceRequest))) {

					//La personne est déjà inscrite, faire un UPDATE avec Prevue='n'
					$request = "UPDATE NPVB_Presence SET DateHeure=DateHeure, Prevue='n' WHERE (Joueur='".$pseudo."' AND DateHeure='".$date."' AND Libelle='".$libelle."')";
					$result = mysql_query($request) or die('Errant query:  '.$query);

					header('Content-type: application/json');
					$error = array('status' => $result);
					echo json_encode($error);

				}
				else {

					//La personne n'est pas inscrite, faire un INSERT avec Prevue='n'
					$request = "INSERT INTO NPVB_Presence (Joueur, DateHeure, Libelle, Prevue) VALUES ('".$pseudo."', '".$date."', '".$libelle."', 'n')";
					$result = mysql_query($request) or die('Errant query:  '.$query);

					header('Content-type: application/json');
					$error = array('status' => $result);
					echo json_encode($error);

				}
			} else if ($presence == 'o') { // PRESENT

				$nbSubscribeRequest = "SELECT count(*) FROM NPVB_Presence WHERE (DateHeure='".$date."' AND Libelle='".$libelle."' AND Prevue='o')";
				$nbSubscribersQuery = mysql_query($nbSubscribeRequest) or die('Errant query:  '.$query);
				$nbSubscribers = mysql_fetch_row($nbSubscribersQuery);
	
				$maxSubscribersRequest = "SELECT InscritsMax FROM NPVB_Evenements WHERE (DateHeure='".$date."' AND Libelle='".$libelle."')";
				$maxSubscribersQuery = mysql_query($maxSubscribersRequest) or die('Errant query:  '.$query);
				$maxSubscribers = mysql_fetch_row($maxSubscribersQuery);
	
				if ($nbSubscribers[0] < $maxSubscribers[0] || $libelle != 'SEANCE') {

					if (mySql_fetch_object(mySql_query($testPresenceRequest))) {

						//La personne est déjà inscrite, faire un UPDATE avec Prevue='o'
						$request = "UPDATE NPVB_Presence SET DateHeure=DateHeure, Prevue='o' WHERE (Joueur='".$pseudo."' AND DateHeure='".$date."' AND Libelle='".$libelle."')";
						$result = mysql_query($request) or die('Errant query:  '.$query);

						header('Content-type: application/json');
						$error = array('status' => $result);
						echo json_encode($error);

					}
					else {

						//La personne n'est pas inscrite, faire un INSERT avec Prevue='o'
						$request = "INSERT INTO NPVB_Presence (Joueur, DateHeure, Libelle, Prevue) VALUES ('".$pseudo."', '".$date."', '".$libelle."', 'o')";
						$result = mysql_query($request) or die('Errant query:  '.$query);

						header('Content-type: application/json');
						$error = array('status' => $result);
						echo json_encode($error);

					}

				}
				else {

					header('Content-type: application/json');
					$error = array('status' => false);
					$error['message'] = "Nombre d'inscrits maximum déjà atteint";
					echo json_encode($error);

				}

			} else { // Invalide valeur pour presence

				header('Content-type: application/json');
				$error = array('status' => false);
				$error['message'] = "Requête invalide";
				echo json_encode($error);

			}

		}
		else if ($_GET['type'] == "rules") {
			/* output in necessary format */
			header('Content-type: application/json');
			echo json_encode('https://www.fivb.com/wp-content/uploads/2025/06/FIVB-Volleyball_Rules2025_2028-FR-v04.pdf');
		}
		else if ($_GET['type'] == "competlib") {
			/* output in necessary format */
			header('Content-type: application/json');
			echo json_encode('https://www.ffvbbeach.org/ffvbapp/resu/vbspo_calendrier_export.php');
		}
		else if ($_GET['type'] == "ufolep") {
			/* output in necessary format */
			header('Content-type: application/json');
			echo json_encode('https://www.ufolep44.com/resultats/resultats-volley-ball');
			// echo json_encode('https://docs.google.com/spreadsheets/d/1itZexbnL8Q_6wDHTqknU3rN8161qUqvzsK5LMpxsc8g/pubhtml?widget=true&amp;headers=false');

			// https://docs.google.com/spreadsheets/d/1itZexbnL8Q_6wDHTqknU3rN8161qUqvzsK5LMpxsc8g/pubhtml?widget=true&amp;headers=false
			// https://docs.google.com/spreadsheets/d/1itZexbnL8Q_6wDHTqknU3rN8161qUqvzsK5LMpxsc8g/export?format=pdf 
		}

?>
