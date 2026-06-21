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
	'adminchat'         => 'gerer_roles',
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

// Vrai si le joueur peut poster (capacité requise par la conversation).
// $posterCapacite = NPVB_Conversations.PosterCapacite (NULL = tous les participants)
function peutPosterConversation($Joueur, $posterCapacite) {
	if (!$posterCapacite) return true;
	return peut($Joueur, $posterCapacite);
}

// Vrai si le joueur participe (a accès) à une conversation. $conv = ligne NPVB_Conversations.
//   generale : tous les connectés
//   equipe   : membre de l'équipe (appartenance) ou responsable/suppléant
//   bureau/prive : membre explicite (NPVB_ConversationMembres)
function peutAccederConversation($Joueur, $conv, $sdblink) {
	if (!isset($Joueur) || !is_object($Joueur) || !$conv) return false;
	$pseudo = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);
	if ($conv->Type == 'generale') return true;
	if ($conv->Type == 'equipe') {
		$eq = mysql_real_escape_string($conv->Equipe, $sdblink);
		$r = mysql_query("SELECT 1 FROM NPVB_Appartenance WHERE Joueur='".$pseudo."' AND Equipe='".$eq."'
		                  UNION SELECT 1 FROM NPVB_Equipes WHERE Nom='".$eq."' AND (Responsable='".$pseudo."' OR Supleant='".$pseudo."') LIMIT 1", $sdblink);
		return ($r && mysql_num_rows($r) > 0);
	}
	$r = mysql_query("SELECT 1 FROM NPVB_ConversationMembres WHERE Conversation=".(int)$conv->Id." AND Joueur='".$pseudo."' LIMIT 1", $sdblink);
	return ($r && mysql_num_rows($r) > 0);
}

// Vrai si le joueur peut poster dans cette conversation (non archivée + accès + capacité).
function peutPosterDansConv($Joueur, $conv, $sdblink) {
	if (!$conv || (isset($conv->Archive) && $conv->Archive == 'o')) return false;
	if (!peutAccederConversation($Joueur, $conv, $sdblink)) return false;
	return peutPosterConversation($Joueur, $conv->PosterCapacite);
}

// Nom affiché d'une conversation pour un joueur donné.
// Pour un privé : le nom de l'AUTRE participant.
function nomConversationPourJoueur($conv, $Joueur, $sdblink) {
	if (!$conv) return '';
	if ($conv->Type != 'prive') return $conv->Nom;
	$me = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);
	$r = mysql_query("SELECT j.Prenom, j.Nom, j.Pseudonyme
	                  FROM NPVB_ConversationMembres cm JOIN NPVB_Joueurs j ON j.Pseudonyme=cm.Joueur
	                  WHERE cm.Conversation=".(int)$conv->Id." AND cm.Joueur<>'".$me."' LIMIT 1", $sdblink);
	if ($r && ($x = mysql_fetch_object($r))) { $n = trim($x->Prenom.' '.$x->Nom); return ($n != '') ? $n : $x->Pseudonyme; }
	return 'Privé';
}

// Trouve la conversation privée entre 2 membres, ou la crée. Retourne son Id (0 si invalide).
function trouverOuCreerPrive($a, $b, $sdblink) {
	if (!$a || !$b || $a == $b) return 0;
	$ae = mysql_real_escape_string($a, $sdblink);
	$be = mysql_real_escape_string($b, $sdblink);
	$r = mysql_query("SELECT Conversation FROM NPVB_ConversationMembres
	                  WHERE Conversation IN (SELECT Id FROM NPVB_Conversations WHERE Type='prive')
	                    AND Joueur IN ('".$ae."','".$be."')
	                  GROUP BY Conversation HAVING COUNT(*)=2 LIMIT 1", $sdblink);
	if ($r && ($x = mysql_fetch_object($r))) return (int)$x->Conversation;
	mysql_query("INSERT INTO NPVB_Conversations (Type, Nom, DateCreation) VALUES ('prive', 'Privé', NOW())", $sdblink);
	$cid = mysql_insert_id($sdblink);
	mysql_query("INSERT IGNORE INTO NPVB_ConversationMembres (Conversation, Joueur) VALUES (".$cid.", '".$ae."'), (".$cid.", '".$be."')", $sdblink);
	return $cid;
}

// Crée une conversation d'équipe active pour chaque équipe qui n'en a pas
// (idempotent ; appelé à l'ouverture du chat). Inclut ASSO/SEANCE/CODIR.
function assurerConversationsEquipes($sdblink) {
	mysql_query("INSERT INTO NPVB_Conversations (Type, Nom, Equipe, DateCreation)
	             SELECT 'equipe', e.Nom, e.Nom, NOW() FROM NPVB_Equipes e
	             WHERE NOT EXISTS (SELECT 1 FROM NPVB_Conversations c
	                               WHERE c.Type='equipe' AND c.Equipe=e.Nom AND c.Archive='n')", $sdblink);
}

// Liste des pseudonymes participant à une conversation (pour le push notamment).
function participantsConversation($conv, $sdblink) {
	$liste = array();
	if (!$conv) return $liste;
	if ($conv->Type == 'generale') {
		$r = mysql_query("SELECT Pseudonyme FROM NPVB_Joueurs WHERE Etat='V'", $sdblink);
	} else if ($conv->Type == 'equipe') {
		$eq = mysql_real_escape_string($conv->Equipe, $sdblink);
		$r = mysql_query("SELECT Joueur AS Pseudonyme FROM NPVB_Appartenance WHERE Equipe='".$eq."'
		                  UNION SELECT Responsable FROM NPVB_Equipes WHERE Nom='".$eq."' AND Responsable IS NOT NULL AND Responsable<>''
		                  UNION SELECT Supleant FROM NPVB_Equipes WHERE Nom='".$eq."' AND Supleant IS NOT NULL AND Supleant<>''", $sdblink);
	} else {
		$r = mysql_query("SELECT Joueur AS Pseudonyme FROM NPVB_ConversationMembres WHERE Conversation=".(int)$conv->Id, $sdblink);
	}
	if ($r) { while ($x = mysql_fetch_object($r)) { if ($x->Pseudonyme) $liste[] = $x->Pseudonyme; } }
	return $liste;
}

// Non-lus d'UNE conversation pour le joueur (exclut ses propres messages).
function nonLusConversation($Joueur, $convId, $sdblink) {
	$pseudo = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);
	$convId = (int)$convId;
	$sql = "SELECT COUNT(*) AS n FROM NPVB_MessagesChat m
	        LEFT JOIN NPVB_MessagesLus l ON l.Conversation=m.Conversation AND l.Joueur='".$pseudo."'
	        WHERE m.Conversation=".$convId." AND m.Supprime='n' AND m.Auteur<>'".$pseudo."'
	          AND m.Id > COALESCE(l.DernierLuId,0)";
	$r = mysql_query($sql, $sdblink);
	if ($r && ($row = mysql_fetch_object($r))) return (int)$row->n;
	return 0;
}

// Conversations accessibles au joueur (objets NPVB_Conversations + ->nonlus).
function conversationsAccessibles($Joueur, $sdblink) {
	if (!isset($Joueur) || !is_object($Joueur)) return array();
	$pseudo = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);
	$ids = array();
	$r = mysql_query("SELECT Id FROM NPVB_Conversations WHERE Type='generale'", $sdblink);
	if ($r) while ($x = mysql_fetch_object($r)) $ids[(int)$x->Id] = true;
	$r = mysql_query("SELECT c.Id FROM NPVB_Conversations c WHERE c.Type='equipe' AND (
	                    c.Equipe IN (SELECT Equipe FROM NPVB_Appartenance WHERE Joueur='".$pseudo."')
	                    OR c.Equipe IN (SELECT Nom FROM NPVB_Equipes WHERE Responsable='".$pseudo."' OR Supleant='".$pseudo."'))", $sdblink);
	if ($r) while ($x = mysql_fetch_object($r)) $ids[(int)$x->Id] = true;
	$r = mysql_query("SELECT Conversation AS Id FROM NPVB_ConversationMembres WHERE Joueur='".$pseudo."'", $sdblink);
	if ($r) while ($x = mysql_fetch_object($r)) $ids[(int)$x->Id] = true;
	if (empty($ids)) return array();
	$in = implode(',', array_keys($ids));
	$res = mysql_query("SELECT * FROM NPVB_Conversations WHERE Id IN (".$in.") ORDER BY Archive, FIELD(Type,'generale','equipe','bureau','prive'), Nom", $sdblink);
	$convs = array();
	if ($res) { while ($c = mysql_fetch_object($res)) {
		$c->nonlus = ($c->Archive == 'o') ? 0 : nonLusConversation($Joueur, $c->Id, $sdblink);
		$convs[] = $c;
	} }
	return $convs;
}

// Total des non-lus sur toutes les conversations accessibles (badge du menu).
function compterNonLus($Joueur, $sdblink) {
	if (!isset($Joueur) || !is_object($Joueur)) return 0;
	$pseudo = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);
	$sql = "SELECT COUNT(*) AS n
	        FROM NPVB_MessagesChat m
	        JOIN NPVB_Conversations c ON c.Id = m.Conversation
	        LEFT JOIN NPVB_MessagesLus l ON l.Conversation = m.Conversation AND l.Joueur='".$pseudo."'
	        WHERE m.Supprime='n' AND m.Auteur <> '".$pseudo."' AND m.Id > COALESCE(l.DernierLuId, 0)
	          AND c.Archive='n'
	          AND (
	            c.Type='generale'
	            OR (c.Type='equipe' AND (c.Equipe IN (SELECT Equipe FROM NPVB_Appartenance WHERE Joueur='".$pseudo."')
	                                     OR c.Equipe IN (SELECT Nom FROM NPVB_Equipes WHERE Responsable='".$pseudo."' OR Supleant='".$pseudo."')))
	            OR (c.Type IN ('bureau','prive') AND c.Id IN (SELECT Conversation FROM NPVB_ConversationMembres WHERE Joueur='".$pseudo."'))
	          )";
	$res = mysql_query($sql, $sdblink);
	if ($res && ($row = mysql_fetch_object($res))) return (int)$row->n;
	return 0;
}
?>
