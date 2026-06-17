<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
//**********************************DEFINITION DES CLASSES************************************************//

class Equipe{
	var $Nom;
	var $Responsable;
	var $Supleant;
	var $TousJoueurs;
	var $PresenceDefaut;
	var $Joueurs;//Tableau
	
	function Equipe($Nom, $Responsable, $Supleant, $TousJoueurs, $PresenceDefaut, $Joueurs){
		$this->Nom = $Nom;
		$this->Responsable = $Responsable;
		$this->Supleant = $Supleant;
		$this->TousJoueurs = $TousJoueurs;
		$this->PresenceDefaut = $PresenceDefaut;
		$this->Joueurs = $Joueurs;
	}
	
	function faisPartie($Joueur){
		return ($this->Joueurs[$Joueur]);
	}
	
	function faisPartieAu($Joueur, $DateTest){
		return ($this->Joueurs[$Joueur] > $DateTest);
	}
	
	function nombreJoueurs(){
		count($this->Joueurs);
	}
}


class Evenement{
	var $DateHeure;
	var $Libelle;
	var $Etat;
	var $Titre;
	var $Intitule;
	var $Lieu;
	var $Adresse;
	var $Adversaire;
	var $Domicile;
	var $Resultat;
	var $Analyse;
	var $InscritsMax;
	var $serontPresents;//tableau
	var $serontIndisponibles;//tableau
	var $etaientPresents;//tableau
	var $NombreJoueursEtaientPresents;

	function Evenement($DateHeure, $Libelle, $Etat, $Titre, $Intitule, $Lieu, $Adresse, $Adversaire, $Domicile, $Resultat, $Analyse, $InscritsMax, $serontPresents, $serontIndisponibles, $etaientPresents){
		
		$this->DateHeure = $DateHeure;
		$this->Libelle = $Libelle;
		$this->Etat = $Etat;
		$this->Titre = $Titre;
		$this->Intitule = $Intitule;
		$this->Lieu = $Lieu;
		$this->Adresse = $Adresse;
		$this->Adversaire = $Adversaire;
		$this->Domicile = $Domicile;
		$this->Resultat = $Resultat;
		$this->Analyse = $Analyse;
		$this->InscritsMax = $InscritsMax;
		$this->serontPresents = $serontPresents;
		$this->serontIndisponibles = $serontIndisponibles;
		$this->etaientPresents = $etaientPresents;
		$NombreJoueursEtaientPresents = 0;
		
		Foreach ($etaientPresents as $JoueurEtaitPresent){
			if ($JoueurEtaitPresent=="o") $NombreJoueursEtaientPresents++;
		}
		
		$this->NombreJoueursEtaientPresents = $NombreJoueursEtaientPresents;
		
	}
		
	function seraPresent($Joueur){
		return ($this->serontPresents[$Joueur]);
	}
		
	function etaitPresent($Joueur){
		return ($this->etaientPresents[$Joueur]=="o");
	}
		
	function etaitAbsent($Joueur){
		return ($this->etaientPresents[$Joueur]=="n");
	}
		
	function nombreJoueursPresents(){
		return count($this->serontPresents);
	}

}
/*
class Rencontre{
	var $Etat;
	var $Intitule;
	var $Adversaire;
	var $Lieu;
	var $Joueurs;
	var $Scores;
	var $Analyse;
		
	function Rencontre($_Etat, $_Intitule, $_Adversaire, $_Lieu, $_Joueurs, $_Scores, $_Analyse){
		$this->Etat = $_Etat;
		$this->Intitule = $_Intitule;
		$this->Adversaire = $_Adversaire;
		$this->Lieu = $_Lieu;
		$this->Joueurs = $_Joueurs;
		$this->Scores = $_Scores;
		$this->Analyse = $_Analyse;
		}
	function seraPresent($Joueur){
		foreach ($this->Joueurs as $JoueurPresent){
			if ($Joueur==$JoueurPresent) return true;
			}
		return false;
		}
	}

class Seance{
	var $Etat;
	var $Intitule;
	var $Joueurs;
	
	function Seance($_Etat, $_Intitule, $_Joueurs){
		$this->Etat = $_Etat;
		$this->Intitule = $_Intitule;
		$this->Joueurs = $_Joueurs;
		}
	function seraPresent($Joueur){
		foreach ($this->Joueurs as $JoueurPresent){
			if ($Joueur==$JoueurPresent) return true;
			}
		return false;
		}
	}
*/

?>