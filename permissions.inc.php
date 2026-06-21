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

// Rôles attribuables via l'interface admin (le rôle 'membre' est implicite : aucune ligne)
$ROLES_ASSIGNABLES = array(
	'admin'        => 'Administrateur (tous les droits)',
	'organisateur' => 'Organisateur (événements, toutes équipes)',
	'capitaine'    => 'Capitaine (événements + membres de son équipe)',
	'redacteur'    => 'Rédacteur (accueil, messages, actualités)',
);

// Capacités GLOBALES (toutes équipes confondues)
$CAPACITES_GLOBALES = array(
	'admin'        => array('*'),  // tout
	'organisateur' => array('gerer_evenements', 'saisir_presences', 'cloturer_evenements', 'voir_stats'),
	'redacteur'    => array('editer_accueil', 'gerer_messages', 'poster_annonce'),
	'capitaine'    => array(),     // rien en global — uniquement par équipe
	'membre'       => array(),
);

// Capacités PAR ÉQUIPE (le capitaine sur ses propres équipes)
$CAPACITES_EQUIPE = array(
	'capitaine' => array('gerer_evenements', 'saisir_presences', 'cloturer_evenements', 'gerer_membres'),
);

// Capacité globale requise pour accéder à chaque page admin
$CAPACITES_PAGES = array(
	'adminequipes'      => 'gerer_equipes',
	'adminevenements'   => 'gerer_evenements',
	'adminfichejour'    => 'gerer_evenements',
	'adminmembres'      => 'gerer_membres',
	'adminfichemembre'  => 'gerer_membres',
	'adminmessages'     => 'gerer_messages',
	'adminnewmessage'   => 'gerer_messages',
	'adminaccueil'      => 'editer_accueil',
	'adminaccueilimage' => 'editer_accueil',
	'adminstats'        => 'voir_stats',
);

// Pages où le capitaine entre (contenu filtré par équipe à l'intérieur de la page).
// Valeur = capacité par équipe correspondante.
$PAGES_CAPITAINE = array(
	'adminevenements'  => 'gerer_evenements',
	'adminfichejour'   => 'gerer_evenements',
	'adminmembres'     => 'gerer_membres',
	'adminfichemembre' => 'gerer_membres',
);

// Vrai si le joueur peut accéder à une page admin (capacité globale OU capitaine
// gérant au moins une équipe pour les pages filtrées par équipe).
function peutAccederPage($Joueur, $Page) {
	global $CAPACITES_PAGES, $PAGES_CAPITAINE;
	if (!isset($CAPACITES_PAGES[$Page])) return true; // page non protégée
	if (peut($Joueur, $CAPACITES_PAGES[$Page])) return true;
	if (isset($PAGES_CAPITAINE[$Page])) {
		$equipes = equipesGerables($Joueur, $PAGES_CAPITAINE[$Page]);
		if (is_array($equipes) && count($equipes) > 0) return true;
	}
	return false;
}

// Liste des équipes gérables par le joueur pour une capacité :
//   null  = toutes (droit global : admin, organisateur)
//   array = liste restreinte (capitaine sur ses équipes)
//   array() vide = aucune
function equipesGerables($Joueur, $capacite) {
	if (peut($Joueur, $capacite)) return null; // droit global
	global $CAPACITES_EQUIPE;
	foreach (rolesJoueur($Joueur) as $role) {
		$caps = isset($CAPACITES_EQUIPE[$role]) ? $CAPACITES_EQUIPE[$role] : array();
		if (in_array($capacite, $caps)) return equipesDuJoueur($Joueur);
	}
	return array();
}

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

// Pseudonymes des membres gérables par le joueur pour 'gerer_membres' :
//   null  = tous (droit global : admin)
//   array = membres des équipes du capitaine (via NPVB_Appartenance)
//   array() vide = aucun
function membresGerables($Joueur) {
	if (peut($Joueur, 'gerer_membres')) return null; // droit global
	global $CAPACITES_EQUIPE, $sdblink;
	$estCapitaine = false;
	foreach (rolesJoueur($Joueur) as $role) {
		$caps = isset($CAPACITES_EQUIPE[$role]) ? $CAPACITES_EQUIPE[$role] : array();
		if (in_array('gerer_membres', $caps)) { $estCapitaine = true; break; }
	}
	if (!$estCapitaine) return array();
	$equipes = equipesDuJoueur($Joueur);
	if (empty($equipes)) return array();
	$in = array();
	foreach ($equipes as $eq) { $in[] = "'".mysql_real_escape_string($eq, $sdblink)."'"; }
	$liste = array();
	$res = mysql_query("SELECT DISTINCT Joueur FROM NPVB_Appartenance WHERE Equipe IN (".implode(",", $in).")", $sdblink);
	if ($res) { while ($row = mysql_fetch_object($res)) { $liste[] = $row->Joueur; } }
	return $liste;
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

// ============================================================================
// Chat / messagerie
// ============================================================================

// Vrai si le joueur peut poster dans une conversation.
// $posterCapacite = valeur NPVB_Conversations.PosterCapacite (NULL = tous les participants)
function peutPosterConversation($Joueur, $posterCapacite) {
	if (!$posterCapacite) return true;
	return peut($Joueur, $posterCapacite);
}

// Nombre total de messages non lus pour le joueur (v1 : conversations 'generale',
// accessibles à tous les connectés). Exclut ses propres messages.
function compterNonLus($Joueur, $sdblink) {
	if (!isset($Joueur) || !is_object($Joueur)) return 0;
	$pseudo = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);
	$sql = "SELECT COUNT(*) AS n
	        FROM NPVB_MessagesChat m
	        JOIN NPVB_Conversations c ON c.Id = m.Conversation AND c.Type='generale'
	        LEFT JOIN NPVB_MessagesLus l ON l.Conversation = m.Conversation AND l.Joueur='".$pseudo."'
	        WHERE m.Supprime='n' AND m.Auteur <> '".$pseudo."'
	          AND m.Id > COALESCE(l.DernierLuId, 0)";
	$res = mysql_query($sql, $sdblink);
	if ($res && ($row = mysql_fetch_object($res))) return (int)$row->n;
	return 0;
}
?>
