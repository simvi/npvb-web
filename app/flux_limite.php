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

		$request = "SELECT * FROM NPVB_Joueurs WHERE etat = 'V'";
		
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

		$request = "SELECT DateHeure, DateHeureOLD,	Libelle, Etat, Titre, Intitule, Lieu, Adresse, Adversaire FROM NPVB_Evenements WHERE (DateHeure > 20130000000000)";	
		//, Analyse
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
				
		$request = "SELECT Joueur, DateHeure, DateHeureOLD, Libelle, Prevue, Effective FROM NPVB_Presence WHERE (DateHeure = ".$_GET['date'].")";
		
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
            
            $getInscritsMaxRequest = "SELECT InscritsMax FROM NPVB_Evenements WHERE (DateHeure='".$date."' AND Libelle='".$libelle."')";
            $inscritsMaxResult = mysql_query($getInscritsMaxRequest) or die('Errant query 1:  '.$query);
            while($result=mysql_fetch_array($inscritsMaxResult)){
                $inscritsMax = $result['InscritsMax'];
            }
            
            $getInscritsRequest = "SELECT COUNT(*) as Inscrits FROM NPVB_Presence WHERE (DateHeure='".$date."' AND Libelle='".$libelle."' AND Prevue='o')";
            $inscritsResult = mysql_query($getInscritsRequest) or die('Errant query 2:  '.$query);
            while($result=mysql_fetch_array($inscritsResult)){
                $inscrits = $result['Inscrits'];
            }
            
			$date = $_GET['date'];
			$pseudo = $_GET['pseudo'];
			$libelle = $_GET['libelle'];
			$presence = $_GET['presence'];
			
			$testPresenceRequest = "SELECT * FROM NPVB_Presence WHERE (Joueur='".$pseudo."' AND DateHeure='".$date."' AND Libelle='".$libelle."')";
			
			if (mySql_fetch_object(mySql_query($testPresenceRequest))) {

                 if (($inscritsMax > 0 && $inscrits <= $inscritsMax) || $presence != "o") {
                     //La personne est déjà inscrite, faire un UPDATE
                     $request = "UPDATE NPVB_Presence SET DateHeure=DateHeure, Prevue='".$presence."' WHERE (Joueur='".$pseudo."' AND DateHeure='".$date."' AND Libelle='".$libelle."')";
                     $result = mysql_query($request) or die('Errant query:  '.$query);
                 }
			}
			else if ($libelle != "SEANCE" || ($inscritsMax > 0 && $inscrits <= $inscritsMax)) {
                 
				//La personne n'est pas inscrite, faire un INSERT
				$request = "INSERT INTO NPVB_Presence (Joueur, DateHeure, Libelle, Prevue) VALUES ('".$pseudo."', '".$date."', '".$libelle."', '".$presence."')";
				$result = mysql_query($request) or die('Errant query:  '.$query);

			}

		}
	
	
	

	

?>
