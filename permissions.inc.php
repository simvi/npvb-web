<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

// ============================================================================
// Système de permissions basé sur les rôles
//
// Le code teste des CAPACITÉS, jamais des noms de rôles :
//   peut($Joueur, 'editer_accueil')                       → capacité globale
//   peutPourEquipe($Joueur, 'gerer_evenements', $equipe)  → capacité par équipe
//
// Un membre peut cumuler plusieurs rôles ($Joueur->Roles). peut() fait l'union
// des capacités de tous ses rôles.
// ============================================================================

// Capacités GLOBALES (toutes équipes confondues)
$CAPACITES_GLOBALES = array(
	'admin'        => array('*'),  // tout
	'organisateur' => array('gerer_evenements', 'saisir_presences', 'cloturer_evenements', 'voir_stats'),
	'redacteur'    => array('editer_accueil', 'gerer_messages'),
	'capitaine'    => array(),     // rien en global — uniquement par équipe
	'membre'       => array(),
);

// Capacités PAR ÉQUIPE (le capitaine sur ses propres équipes)
$CAPACITES_EQUIPE = array(
	'capitaine' => array('gerer_evenements', 'saisir_presences', 'cloturer_evenements', 'gerer_membres'),
);

// Renvoie le tableau des rôles du joueur (au moins 'membre')
function rolesJoueur($Joueur) {
	if (!isset($Joueur) || !is_object($Joueur)) return array();
	$roles = (isset($Joueur->Roles) && is_array($Joueur->Roles)) ? $Joueur->Roles : array();
	if (empty($roles)) $roles = array('membre');
	return $roles;
}

// Vrai si le joueur possède une capacité GLOBALE
function peut($Joueur, $capacite) {
	if (!isset($Joueur) || !is_object($Joueur)) return false;
	// Compatibilité ascendante : l'ancien admin tout-puissant a tout (supprimé en phase 7)
	if (isset($Joueur->DieuToutPuissant) && $Joueur->DieuToutPuissant == "o") return true;
	global $CAPACITES_GLOBALES;
	foreach (rolesJoueur($Joueur) as $role) {
		$caps = isset($CAPACITES_GLOBALES[$role]) ? $CAPACITES_GLOBALES[$role] : array();
		if (in_array('*', $caps) || in_array($capacite, $caps)) return true;
	}
	return false;
}

// Renvoie les noms d'équipes dont le joueur est Responsable ou Suppléant
function equipesDuJoueur($Joueur) {
	if (!isset($Joueur) || !is_object($Joueur)) return array();
	global $sdblink;
	$equipes = array();
	$pseudo = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);
	$res = mysql_query("SELECT Nom FROM NPVB_Equipes WHERE Responsable='".$pseudo."' OR Supleant='".$pseudo."'", $sdblink);
	if ($res) {
		while ($row = mysql_fetch_object($res)) {
			$equipes[] = $row->Nom;
		}
	}
	return $equipes;
}

// Vrai si le joueur possède une capacité POUR une équipe donnée
function peutPourEquipe($Joueur, $capacite, $equipe) {
	if (!isset($Joueur) || !is_object($Joueur)) return false;
	// Une capacité globale couvre toutes les équipes (admin, organisateur...)
	if (peut($Joueur, $capacite)) return true;
	// Sinon : rôle capitaine + capacité dans la matrice équipe + responsable de CETTE équipe
	global $CAPACITES_EQUIPE;
	foreach (rolesJoueur($Joueur) as $role) {
		$caps = isset($CAPACITES_EQUIPE[$role]) ? $CAPACITES_EQUIPE[$role] : array();
		if (in_array($capacite, $caps) && in_array($equipe, equipesDuJoueur($Joueur))) {
			return true;
		}
	}
	return false;
}

// Vrai si le joueur a un quelconque pouvoir d'administration (affichage menu, etc.)
function estAdminQuelconque($Joueur) {
	return peut($Joueur, 'gerer_evenements')
		|| peut($Joueur, 'gerer_membres')
		|| peut($Joueur, 'editer_accueil')
		|| peut($Joueur, 'gerer_messages')
		|| peut($Joueur, 'gerer_roles')
		|| (isset($Joueur->Roles) && in_array('capitaine', $Joueur->Roles));
}
?>
