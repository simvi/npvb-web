<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
if (!$Joueur){ require("accueil.inc.php"); return;}

$peutModerer = peut($Joueur, 'gerer_roles');

// ============================================================
// Actions admin
// ============================================================

// Archivage en masse des conversations d'équipe (réinitialisation saison)
if ($peutModerer && isset($_POST['Action']) && $_POST['Action']=="ChatArchiveEquipes") {
	mySql_query("UPDATE NPVB_Conversations SET Archive='o', ArchiveDate=NOW() WHERE Type='equipe' AND Archive='n'", $sdblink);
}

// Archivage individuel d'une conversation
if ($peutModerer && isset($_POST['Action']) && $_POST['Action']=="ArchiverConversation") {
	$sid = (int)$_POST['conv'];
	if ($sid > 1) { // Id=1 = Annonces du club, non archivable
		mySql_query("UPDATE NPVB_Conversations SET Archive='o', ArchiveDate=NOW() WHERE Id=".$sid." AND Archive='n'", $sdblink);
	}
	header('Location: '.$PHP_SELF.'?Page=chat');
	return;
}

// Renommer une conversation
if ($peutModerer && isset($_POST['Action']) && $_POST['Action']=="RenommerConversation") {
	$sid = (int)$_POST['conv'];
	$nom = isset($_POST['Nom']) ? trim($_POST['Nom']) : '';
	if ($nom !== '' && $sid > 0) {
		$ne = mysql_real_escape_string($nom, $sdblink);
		mySql_query("UPDATE NPVB_Conversations SET Nom='".$ne."' WHERE Id=".$sid." AND Type!='prive'", $sdblink);
	}
	header('Location: '.$PHP_SELF.'?Page=chat&conv='.$sid);
	return;
}

// Ajouter un membre à une conversation
if ($peutModerer && isset($_POST['Action']) && $_POST['Action']=="AjouterMembreConv") {
	$sid = (int)$_POST['conv'];
	$membre = isset($_POST['Membre']) ? mysql_real_escape_string($_POST['Membre'], $sdblink) : '';
	if ($sid > 0 && $membre !== '') {
		mySql_query("INSERT IGNORE INTO NPVB_ConversationMembres (Conversation, Joueur) VALUES (".$sid.", '".$membre."')", $sdblink);
	}
	header('Location: '.$PHP_SELF.'?Page=chat&conv='.$sid.'&edit='.$sid);
	return;
}

// Retirer un membre d'une conversation
if ($peutModerer && isset($_POST['Action']) && $_POST['Action']=="RetirerMembreConv") {
	$sid = (int)$_POST['conv'];
	$membre = isset($_POST['Membre']) ? mysql_real_escape_string($_POST['Membre'], $sdblink) : '';
	if ($sid > 0 && $membre !== '') {
		mySql_query("DELETE FROM NPVB_ConversationMembres WHERE Conversation=".$sid." AND Joueur='".$membre."'", $sdblink);
	}
	header('Location: '.$PHP_SELF.'?Page=chat&conv='.$sid.'&edit='.$sid);
	return;
}

// Créer un groupe bureau
if ($peutModerer && isset($_POST['Action']) && $_POST['Action']=="CreerGroupe") {
	$nom = isset($_POST['NomGroupe']) ? trim($_POST['NomGroupe']) : '';
	if ($nom !== '') {
		$ne = mysql_real_escape_string($nom, $sdblink);
		mySql_query("INSERT INTO NPVB_Conversations (Type, Nom, DateCreation) VALUES ('bureau', '".$ne."', NOW())", $sdblink);
		$newConvId = mysql_insert_id($sdblink);
		if ($newConvId) {
			$createur = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);
			mySql_query("INSERT IGNORE INTO NPVB_ConversationMembres (Conversation, Joueur) VALUES (".$newConvId.", '".$createur."')", $sdblink);
		}
		header('Location: '.$PHP_SELF.'?Page=chat&conv='.$newConvId.'&edit='.$newConvId);
	} else {
		header('Location: '.$PHP_SELF.'?Page=chat');
	}
	return;
}

// Suppression définitive d'une conversation archivée (bureau/prive uniquement, pas les équipes auto-créées)
if ($peutModerer && isset($_POST['Action']) && $_POST['Action']=="SupprimerConversation") {
	$sid = (int)$_POST['conv'];
	$cible = mySql_fetch_object(mySql_query("SELECT Type FROM NPVB_Conversations WHERE Id=".$sid." AND Archive='o' AND Type IN ('bureau','prive')", $sdblink));
	if ($cible) {
		mySql_query("DELETE FROM NPVB_MessagesLus WHERE Conversation=".$sid, $sdblink);
		mySql_query("DELETE FROM NPVB_MessagesChat WHERE Conversation=".$sid, $sdblink);
		mySql_query("DELETE FROM NPVB_ConversationMembres WHERE Conversation=".$sid, $sdblink);
		mySql_query("DELETE FROM NPVB_Conversations WHERE Id=".$sid, $sdblink);
		header('Location: '.$PHP_SELF.'?Page=chat');
		return;
	}
}

// ============================================================
// Ouverture/création d'une conversation privée (?Prive=<pseudo>)
// ============================================================
assurerConversationsEquipes($sdblink);
$convForce = 0;
if (isset($_REQUEST['Prive']) && $_REQUEST['Prive'] != '' && $_REQUEST['Prive'] != $Joueur->Pseudonyme) {
	$cibleEcap = mysql_real_escape_string($_REQUEST['Prive'], $sdblink);
	if (mySql_fetch_object(mySql_query("SELECT 1 FROM NPVB_Joueurs WHERE Pseudonyme='".$cibleEcap."' AND Etat='V'", $sdblink))) {
		$convForce = trouverOuCreerPrive($Joueur->Pseudonyme, $_REQUEST['Prive'], $sdblink);
	}
}

// Axe 2b : Paramètre de navigation (depuis quel message afficher)
$depuisMsgId = isset($_REQUEST['depuis']) ? (int)$_REQUEST['depuis'] : 0;

// ============================================================
// Conversations
// ============================================================
$conversations = conversationsAccessibles($Joueur, $sdblink);
$conversationsActives  = array();
$conversationsArchives = array();
foreach ($conversations as $c) {
	if ($c->Archive == 'o') { $conversationsArchives[] = $c; }
	else                    { $conversationsActives[]  = $c; }
}

$convSel = $convForce ? $convForce : (isset($_REQUEST['conv']) ? (int)$_REQUEST['conv'] : 0);
$conv = null;
foreach ($conversations as $c) { if ($c->Id == $convSel) { $conv = $c; break; } }
if (!$conv && count($conversations)) $conv = $conversations[0];
$convId = $conv ? (int)$conv->Id : 0;

$peutPoster  = $conv ? peutPosterDansConv($Joueur, $conv, $sdblink) : false;
$pseudoEcap  = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);

// ============================================================
// Pré-chargement des données pour les panneaux d'édition (admin)
// ============================================================
$Joueurs = array();
$convEditData = array();
$editOuvert = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($peutModerer) {
	$Joueurs = ChargeJoueurs("V", "Nom, Prenom");
	foreach ($conversationsActives as $c) {
		$cid = (int)$c->Id;
		$auto   = array();
		$manuel = array();
		if ($c->Type == 'equipe') {
			$eq = mysql_real_escape_string($c->Equipe, $sdblink);
			$r = mySql_query(
				"SELECT DISTINCT j.Pseudonyme, j.Prenom, j.Nom
				 FROM (SELECT Joueur AS p FROM NPVB_Appartenance WHERE Equipe='".$eq."'
				       UNION SELECT Responsable FROM NPVB_Equipes WHERE Nom='".$eq."' AND Responsable IS NOT NULL AND Responsable<>''
				       UNION SELECT Supleant FROM NPVB_Equipes WHERE Nom='".$eq."' AND Supleant IS NOT NULL AND Supleant<>'') t
				 JOIN NPVB_Joueurs j ON j.Pseudonyme=t.p
				 ORDER BY j.Nom, j.Prenom", $sdblink);
			while ($x = mySql_fetch_object($r)) { $auto[$x->Pseudonyme] = $x; }
			$r2 = mySql_query(
				"SELECT j.Pseudonyme, j.Prenom, j.Nom
				 FROM NPVB_ConversationMembres cm JOIN NPVB_Joueurs j ON j.Pseudonyme=cm.Joueur
				 WHERE cm.Conversation=".$cid." ORDER BY j.Nom, j.Prenom", $sdblink);
			while ($x = mySql_fetch_object($r2)) {
				if (!isset($auto[$x->Pseudonyme])) $manuel[$x->Pseudonyme] = $x;
			}
		} else if ($c->Type != 'generale') {
			$r = mySql_query(
				"SELECT j.Pseudonyme, j.Prenom, j.Nom
				 FROM NPVB_ConversationMembres cm JOIN NPVB_Joueurs j ON j.Pseudonyme=cm.Joueur
				 WHERE cm.Conversation=".$cid." ORDER BY j.Nom, j.Prenom", $sdblink);
			while ($x = mySql_fetch_object($r)) { $manuel[$x->Pseudonyme] = $x; }
		}
		$convEditData[$cid] = array('auto' => $auto, 'manuel' => $manuel);
	}
}

// ============================================================
// Envoi d'un message
// ============================================================
if ($conv && isset($_POST['Action']) && $_POST['Action']=="ChatEnvoi" && $peutPoster) {
	$contenu = isset($_POST['Contenu']) ? trim($_POST['Contenu']) : '';
	if ($contenu !== '') {
		$cc = mysql_real_escape_string($contenu, $sdblink);
		mySql_query("INSERT INTO NPVB_MessagesChat (Conversation, Auteur, Contenu, DateEnvoi) VALUES (".$convId.", '".$pseudoEcap."', '".$cc."', NOW())", $sdblink);
		include_once('push.inc.php');
		$apercu = (strlen($contenu) > 80) ? substr($contenu, 0, 77).'...' : $contenu;
		envoyerPush(destinatairesChat($convId, $Joueur->Pseudonyme, $sdblink), $conv->Nom, $apercu, $sdblink, array('conv' => $convId, 'type' => 'chat'));
	}
}

// Suppression d'un message (admin ou auteur)
if ($conv && isset($_POST['Action']) && $_POST['Action']=="ChatSupprime" && isset($_POST['MsgId'])) {
	$mid = (int)$_POST['MsgId'];
	$msg = mySql_fetch_object(mySql_query("SELECT Auteur FROM NPVB_MessagesChat WHERE Id=".$mid." AND Conversation=".$convId, $sdblink));
	if ($msg && peutSupprimerMessage($Joueur, $msg, $sdblink)) {
		mySql_query("UPDATE NPVB_MessagesChat SET Supprime='o' WHERE Id=".$mid." AND Conversation=".$convId, $sdblink);
	}
}

// Épingler un message (Axe 1a)
if ($peutModerer && isset($_POST['Action']) && $_POST['Action']=="EpinglerMessage" && isset($_POST['MsgId'])) {
	$mid = (int)$_POST['MsgId'];
	mySql_query("UPDATE NPVB_MessagesChat SET Epingle='n' WHERE Conversation=".$convId." AND Epingle='o'", $sdblink);
	mySql_query("UPDATE NPVB_MessagesChat SET Epingle='o' WHERE Id=".$mid." AND Conversation=".$convId, $sdblink);
	header('Location: '.$PHP_SELF.'?Page=chat&conv='.$convId);
	return;
}

// Désépingler un message (Axe 1a)
if ($peutModerer && isset($_POST['Action']) && $_POST['Action']=="DesepinglerMessage" && isset($_POST['MsgId'])) {
	$mid = (int)$_POST['MsgId'];
	mySql_query("UPDATE NPVB_MessagesChat SET Epingle='n' WHERE Id=".$mid." AND Conversation=".$convId, $sdblink);
	header('Location: '.$PHP_SELF.'?Page=chat&conv='.$convId);
	return;
}

// Éditer un message (Axe 3b)
if ($conv && isset($_POST['Action']) && $_POST['Action']=="ChatEditer" && isset($_POST['MsgId']) && isset($_POST['Contenu'])) {
	$mid = (int)$_POST['MsgId'];
	$contenu = isset($_POST['Contenu']) ? trim($_POST['Contenu']) : '';
	if ($contenu !== '') {
		$msg = mySql_fetch_object(mySql_query("SELECT Auteur, DateEnvoi, Supprime FROM NPVB_MessagesChat WHERE Id=".$mid." AND Conversation=".$convId, $sdblink));
		if ($msg && peutEditerMessage($Joueur, $msg, $sdblink)) {
			$cc = mysql_real_escape_string($contenu, $sdblink);
			mySql_query("UPDATE NPVB_MessagesChat SET Contenu='".$cc."', DateModif=NOW() WHERE Id=".$mid." AND Conversation=".$convId, $sdblink);
		}
	}
	header('Location: '.$PHP_SELF.'?Page=chat&conv='.$convId);
	return;
}

// ============================================================
// Chargement des messages
// ============================================================
$messages = array();
$messageEpingle = null;
$plusAncienId = 0;
$plusieursPlusAnciens = false;
if ($conv) {
	if ($depuisMsgId) {
		// Axe 2a : Charger une fenêtre centrée autour de depuisMsgId (25 avant/après)
		$res = mySql_query("SELECT * FROM (
			SELECT m.Id, m.Auteur, m.Contenu, m.DateEnvoi, m.DateModif, m.Epingle, j.Prenom, j.Nom
			FROM NPVB_MessagesChat m LEFT JOIN NPVB_Joueurs j ON j.Pseudonyme = m.Auteur
			WHERE m.Conversation=".$convId." AND m.Supprime='n' AND m.Id <= ".$depuisMsgId."
			ORDER BY m.Id DESC LIMIT 26
		) t ORDER BY t.Id ASC", $sdblink);
		while ($row = mySql_fetch_object($res)) { $messages[] = $row; }
		// Charger aussi 25 après
		$res2 = mySql_query("SELECT m.Id, m.Auteur, m.Contenu, m.DateEnvoi, m.DateModif, m.Epingle, j.Prenom, j.Nom
		                     FROM NPVB_MessagesChat m LEFT JOIN NPVB_Joueurs j ON j.Pseudonyme = m.Auteur
		                     WHERE m.Conversation=".$convId." AND m.Supprime='n' AND m.Id > ".$depuisMsgId."
		                     ORDER BY m.Id ASC LIMIT 25", $sdblink);
		while ($row = mySql_fetch_object($res2)) { $messages[] = $row; }
	} else {
		// Chargement normal : 50 derniers messages
		$res = mySql_query("SELECT * FROM (
			SELECT m.Id, m.Auteur, m.Contenu, m.DateEnvoi, m.DateModif, m.Epingle, j.Prenom, j.Nom
			FROM NPVB_MessagesChat m LEFT JOIN NPVB_Joueurs j ON j.Pseudonyme = m.Auteur
			WHERE m.Conversation=".$convId." AND m.Supprime='n'
			ORDER BY m.Id DESC LIMIT 50
		) t ORDER BY t.Id ASC", $sdblink);
		while ($row = mySql_fetch_object($res)) { $messages[] = $row; }

		// Vérifier s'il y a plus de messages
		$res_count = mySql_query("SELECT COUNT(*) AS n FROM NPVB_MessagesChat WHERE Conversation=".$convId." AND Supprime='n' AND Id < ".(count($messages) ? $messages[0]->Id : 999999), $sdblink);
		$row_count = mySql_fetch_object($res_count);
		$plusieursPlusAnciens = $row_count && $row_count->n > 0;
	}

	if (count($messages)) $plusAncienId = $messages[0]->Id;

	// Charger le message épinglé pour l'affichage
	$res2 = mySql_query("SELECT m.Id, m.Auteur, m.Contenu, m.DateEnvoi, j.Prenom, j.Nom
	                     FROM NPVB_MessagesChat m LEFT JOIN NPVB_Joueurs j ON j.Pseudonyme = m.Auteur
	                     WHERE m.Conversation=".$convId." AND m.Epingle='o' AND m.Supprime='n' LIMIT 1", $sdblink);
	if ($res2 && ($row2 = mySql_fetch_object($res2))) {
		$messageEpingle = $row2;
	}
}

// Marquer comme lu
$dernierId = count($messages) ? $messages[count($messages)-1]->Id : 0;
if ($conv) {
	mySql_query("INSERT INTO NPVB_MessagesLus (Joueur, Conversation, DernierLuId)
	             VALUES ('".$pseudoEcap."', ".$convId.", ".(int)$dernierId.")
	             ON DUPLICATE KEY UPDATE DernierLuId=GREATEST(DernierLuId, ".(int)$dernierId.")", $sdblink);
	$conv->nonlus = 0;
}

function chatTypeLabel($t) {
	switch ($t) {
		case 'generale': return 'Général';
		case 'equipe':   return 'Équipe';
	}
	return '';
}

// Axe 3c : Highlight des mentions @pseudo (regex simple, pas de validation)
function highlightMentions($texte) {
	return preg_replace('/\@(\w+)/', '<strong style="color:#0066cc">@$1</strong>', htmlspecialchars($texte, ENT_QUOTES));
}
?>

<div id="ChatLayout">

	<div id="ChatListe">
		<h3>Conversations</h3>

		<div style="margin-bottom:8px">
			<input type="text" id="ChatRecherche" placeholder="Rechercher..." style="width:100%;box-sizing:border-box;padding:6px 8px;font-size:12px;border:1px solid #d0d8ec;border-radius:4px" />
			<div id="ChatResultatsRecherche" style="display:none;position:absolute;background:#fff;border:1px solid #d0d8ec;border-radius:4px;max-height:200px;overflow-y:auto;width:200px;z-index:100;font-size:12px;box-shadow:0 2px 4px rgba(0,0,0,0.1)"></div>
		</div>

<?php if ($peutModerer) { ?>
		<form id="ChatMbrForm" method="post" action="<?=$PHP_SELF?>" style="display:none">
			<input type="hidden" name="Page" value="chat" />
			<input type="hidden" id="ChatMbrConv" name="conv" value="" />
			<input type="hidden" id="ChatMbrAction" name="Action" value="" />
			<input type="hidden" id="ChatMbrMembre" name="Membre" value="" />
		</form>
<?php }

if (!count($conversationsActives)) { ?>
		<p class="Remarque">Aucune conversation.</p>
<?php }

foreach ($conversationsActives as $c) {
	$cid    = (int)$c->Id;
	$actif  = ($c->Id == $convId);
	$nomAff = htmlspecialchars(nomConversationPourJoueur($c, $Joueur, $sdblink), ENT_QUOTES);
	$nomJS  = addslashes(htmlspecialchars($c->Nom, ENT_QUOTES));
	$data   = ($peutModerer && isset($convEditData[$cid])) ? $convEditData[$cid] : null;
?>
		<div class="ChatConvWrap">
			<a class="ChatConv<?=($actif?' ChatConvActif':'')?>" href="<?=$PHP_SELF?>?Page=chat&amp;conv=<?=$cid?>">
<?php $typeLabel = chatTypeLabel($c->Type); if ($typeLabel !== '') { ?><span class="ChatConvType"><?=$typeLabel?></span><?php } ?>
				<span class="ChatConvNom"><?=$nomAff?></span>
<?php if ($c->nonlus > 0) { ?><span class="ChatBadge"><?=(int)$c->nonlus?></span><?php } ?>
			</a>
<?php if ($peutModerer) { ?>
			<div class="ChatConvActions">
				<button class="ChatBtnEdit" onclick="chatEdit(<?=$cid?>)" title="Modifier">✎</button>
<?php if ($cid !== 1) { ?>
				<form id="archForm-<?=$cid?>" method="post" action="<?=$PHP_SELF?>" style="display:none">
					<input type="hidden" name="Page" value="chat" />
					<input type="hidden" name="conv" value="<?=$cid?>" />
					<input type="hidden" name="Action" value="ArchiverConversation" />
				</form>
				<button class="ChatBtnArch" onclick="chatArchiver(<?=$cid?>, '<?=$nomJS?>')" title="Archiver">
				<svg viewBox="0 0 16 16" width="13" height="13" fill="currentColor" aria-hidden="true">
					<path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
					<path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
				</svg>
			</button>
<?php } ?>
			</div>

			<div class="ChatEditPanel" id="editPanel-<?=$cid?>" style="display:none">
<?php if ($c->Type != 'prive') { ?>
				<form class="ChatEditRow" method="post" action="<?=$PHP_SELF?>">
					<input type="hidden" name="Page" value="chat" />
					<input type="hidden" name="conv" value="<?=$cid?>" />
					<input type="hidden" name="Action" value="RenommerConversation" />
					<input type="text" name="Nom" value="<?=$nomAff?>" maxlength="60" class="ChatEditNom" />
					<button type="submit" class="PetitBouton Action">Renommer</button>
				</form>
<?php }

if ($c->Type != 'generale') {
	$memAuto   = $data['auto'];
	$memManuel = $data['manuel'];
	$dejaDans  = array_merge(array_keys($memAuto), array_keys($memManuel));
?>
				<div class="ChatEditMembres">
					<span class="ChatEditMembresLabel">Membres :</span>
<?php foreach ($memAuto as $p => $j) {
	$n = trim($j->Prenom.' '.$j->Nom) ?: $p;
?>					<span class="ChatMembreAuto"><?=htmlspecialchars($n, ENT_QUOTES)?> <em>(auto)</em></span>
<?php }
foreach ($memManuel as $p => $j) {
	$n = trim($j->Prenom.' '.$j->Nom) ?: $p;
?>					<span class="ChatMembreManuel"><?=htmlspecialchars($n, ENT_QUOTES)?>
						<button type="button" class="ChatBtnRetirer"
							onclick="chatRetirerMembre(<?=$cid?>, '<?=addslashes(htmlspecialchars($p, ENT_QUOTES))?>')"
							title="Retirer">×</button>
					</span>
<?php }
if (!count($memAuto) && !count($memManuel)) { ?>
					<span class="Remarque">Aucun membre</span>
<?php } ?>
				</div>
				<div class="ChatEditAjouter">
					<select id="ajouterSel-<?=$cid?>" class="ChatEditSelMembre">
						<option value="">— Ajouter —</option>
<?php foreach ($Joueurs as $j) {
	if (in_array($j->Pseudonyme, $dejaDans)) continue;
	$jn = htmlspecialchars(trim($j->Prenom.' '.$j->Nom), ENT_QUOTES);
	$jp = htmlspecialchars($j->Pseudonyme, ENT_QUOTES);
?>						<option value="<?=$jp?>"><?=$jn?></option>
<?php } ?>
					</select>
					<button type="button" class="PetitBouton Action" onclick="chatAjouterMembre(<?=$cid?>)">Ajouter</button>
				</div>
<?php } else { ?>
				<p class="Remarque ChatEditGeneraleNote">Accessible à tous les membres connectés.</p>
<?php } ?>
				<div class="ChatEditFermer">
					<button type="button" class="PetitBouton" onclick="chatEdit(<?=$cid?>)">Fermer</button>
				</div>
			</div>
<?php } ?>
		</div>
<?php } ?>

		<div id="ChatNouveauMessage" style="display:none">
			<select id="ChatMessageSelect" class="ChatEditSelMembre">
				<option value="">— Sélectionner un membre —</option>
			</select>
			<div style="display:flex;gap:6px;margin-top:6px">
				<button type="button" class="PetitBouton Action" onclick="chatChargerMessageSelect()">Démarrer</button>
				<button type="button" class="PetitBouton" onclick="document.getElementById('ChatNouveauMessage').style.display='none'">Annuler</button>
			</div>
		</div>
		<button class="ChatGererGroupes" style="background:none;border:none;width:100%;cursor:pointer;text-align:center;margin-bottom:8px;"
			onclick="chatOuvrirNouveauMessage()">＋ Nouveau message</button>

<?php if ($peutModerer) { ?>
		<div class="ChatAdminZone">
			<div id="ChatNouveauGroupe" style="display:none">
				<form method="post" action="<?=$PHP_SELF?>">
					<input type="hidden" name="Page" value="chat" />
					<input type="hidden" name="Action" value="CreerGroupe" />
					<input type="text" name="NomGroupe" placeholder="Nom de la conversation..." maxlength="60" />
					<div class="btnRow">
						<button type="submit" class="PetitBouton Action">Créer</button>
						<button type="button" class="PetitBouton" onclick="document.getElementById('ChatNouveauGroupe').style.display='none'">Annuler</button>
					</div>
				</form>
			</div>
			<button class="ChatGererGroupes" style="background:none;border:none;width:100%;cursor:pointer;text-align:center"
				onclick="var z=document.getElementById('ChatNouveauGroupe');z.style.display=z.style.display==='none'?'block':'none'">＋ Nouvelle conversation</button>
		</div>
<?php } ?>


<?php if (count($conversationsArchives)) { ?>
		<a class="ChatGererGroupes ChatArchivesToggle" href="#"
			onclick="var p=document.getElementById('ChatArchivesListe');var o=p.style.display!=='none';p.style.display=o?'none':'block';this.textContent=o?'Afficher les archives':'Masquer les archives';return false;"
			>Afficher les archives</a>
		<div id="ChatArchivesListe" style="display:none;">
<?php foreach ($conversationsArchives as $c) {
	$actif = ($c->Id == $convId);
?>
			<div style="position:relative">
				<a class="ChatConv ChatConvArchive<?=($actif?' ChatConvActif':'')?>" href="<?=$PHP_SELF?>?Page=chat&amp;conv=<?=(int)$c->Id?>">
<?php $typeLabel = chatTypeLabel($c->Type); if ($typeLabel !== '') { ?><span class="ChatConvType"><?=$typeLabel?></span><?php } ?>
					<span class="ChatConvNom"><?=htmlspecialchars(nomConversationPourJoueur($c, $Joueur, $sdblink), ENT_QUOTES)?></span>
				</a>
<?php if ($peutModerer && $c->Type != 'equipe') { ?>
				<form method="post" action="<?=$PHP_SELF?>" style="position:absolute;right:4px;top:50%;transform:translateY(-50%)"
					onsubmit="return confirm('Supprimer définitivement « <?=htmlspecialchars($c->Nom, ENT_QUOTES)?> » et tous ses messages ?');">
					<input type="hidden" name="Page" value="chat" />
					<input type="hidden" name="Action" value="SupprimerConversation" />
					<input type="hidden" name="conv" value="<?=(int)$c->Id?>" />
					<button type="submit" title="Supprimer définitivement"
						style="background:none;border:none;color:#dc3545;cursor:pointer;font-size:14px;padding:0 4px;line-height:1">&#10006;</button>
				</form>
<?php } ?>
			</div>
<?php } ?>
		</div>
<?php } ?>
	</div>

	<div id="ChatPanneau">
<?php if (!$conv) { ?>
		<div class="Explications"><p>Aucune conversation à afficher.</p></div>
<?php } else { ?>
		<h2 id="ChatTitre"><?=htmlspecialchars(nomConversationPourJoueur($conv, $Joueur, $sdblink), ENT_QUOTES)?></h2>

<?php if ($messageEpingle) { ?>
		<div id="ChatEpingle" style="background:#fffacd;border:1px solid #f0e68c;border-radius:6px;padding:10px;margin-bottom:10px;position:sticky;top:0;z-index:10">
			<div style="font-size:12px;color:#999;margin-bottom:4px">📌 Message épinglé</div>
			<div style="font-weight:bold;font-size:13px;margin-bottom:4px">
<?php $nomPin = trim($messageEpingle->Prenom." ".$messageEpingle->Nom); if ($nomPin=="") $nomPin = $messageEpingle->Auteur; echo htmlspecialchars($nomPin, ENT_QUOTES); ?>
				<span style="color:#999;font-weight:normal;margin-left:8px"><?=substr($messageEpingle->DateEnvoi, 8, 2)."/".substr($messageEpingle->DateEnvoi, 5, 2)?></span>
			</div>
			<div style="margin-bottom:8px"><?=nl2br(htmlspecialchars($messageEpingle->Contenu, ENT_QUOTES))?></div>
			<div style="font-size:11px">
				<span id="ChatEpingLecteurs" style="cursor:pointer;color:#0066cc;text-decoration:underline">Voir lecteurs</span>
<?php if ($peutModerer) { ?>
				<form method="post" action="<?=$PHP_SELF?>" style="display:inline;margin-left:8px">
					<input type="hidden" name="Page" value="chat" />
					<input type="hidden" name="conv" value="<?=(int)$convId?>" />
					<input type="hidden" name="Action" value="DesepinglerMessage" />
					<input type="hidden" name="MsgId" value="<?=(int)$messageEpingle->Id?>" />
					<button type="submit" style="background:none;border:none;color:#0066cc;cursor:pointer;text-decoration:underline">Désépingler</button>
				</form>
<?php } ?>
			</div>
			<div id="ChatEpingLecteursPopup" style="display:none;background:#f5f5f5;border-radius:4px;padding:8px;margin-top:8px;font-size:12px"></div>
		</div>
<?php } ?>

		<div id="ChatFil" data-conv="<?=(int)$convId?>" data-dernier="<?=(int)$dernierId?>" data-plus-ancien="<?=(int)$plusAncienId?>">
<?php if ($plusieursPlusAnciens) { ?>
			<div style="text-align:center;margin:10px 0">
				<button id="ChatChargerPlus" style="padding:6px 12px;background:#f0f4ff;border:1px solid #d0d8ec;border-radius:4px;cursor:pointer;font-size:13px">↑ Charger les messages précédents</button>
			</div>
<?php } ?>
<?php
		if (!count($messages)) {
			echo '<p class="ChatVide">Aucun message pour le moment.</p>';
		}
		foreach ($messages as $m) {
			$estMoi = ($m->Auteur == $Joueur->Pseudonyme);
			$nom = trim($m->Prenom." ".$m->Nom);
			if ($nom=="") $nom = $m->Auteur;
			$heure = substr($m->DateEnvoi, 8, 2)."/".substr($m->DateEnvoi, 5, 2)." ".substr($m->DateEnvoi, 11, 5);
			$modifie = isset($m->DateModif) && $m->DateModif ? " <em style='font-size:11px;color:#999'>(modifié)</em>" : "";
?>
			<div class="ChatMsg<?=($estMoi?" ChatMsgMoi":"")?>" data-id="<?=(int)$m->Id?>" style="position:relative">
				<div class="ChatMsgEntete"><span class="ChatAuteur"><?=htmlspecialchars($nom, ENT_QUOTES)?></span> <span class="ChatDate"><?=$heure?><?=$modifie?></span></div>
				<div class="ChatMsgCorps"><?=nl2br(highlightMentions($m->Contenu))?></div>
<?php if ($peutModerer) { ?>
				<form method="post" action="<?=$PHP_SELF?>" style="position:absolute;top:6px;right:26px">
					<input type="hidden" name="Page" value="chat" />
					<input type="hidden" name="conv" value="<?=(int)$convId?>" />
					<input type="hidden" name="Action" value="<?=($m->Epingle=='o'?'DesepinglerMessage':'EpinglerMessage')?>" />
					<input type="hidden" name="MsgId" value="<?=(int)$m->Id?>" />
					<button type="submit" class="ChatSuppr" title="<?=($m->Epingle=='o'?'Désépingler':'Épingler')?>">📌</button>
				</form>
<?php } ?>
<?php if ($peutModerer || $estMoi) { ?>
				<form method="post" action="<?=$PHP_SELF?>" class="ChatSupprForm" onsubmit="return confirm('Supprimer ce message ?');">
					<input type="hidden" name="Page" value="chat" />
					<input type="hidden" name="conv" value="<?=(int)$convId?>" />
					<input type="hidden" name="Action" value="ChatSupprime" />
					<input type="hidden" name="MsgId" value="<?=(int)$m->Id?>" />
					<button type="submit" class="ChatSuppr" title="Supprimer">&#10006;</button>
				</form>
<?php } ?>
			</div>
<?php } ?>
		</div>

<?php if ($peutPoster) { ?>
		<form id="ChatForm" method="post" action="<?=$PHP_SELF?>">
			<input type="hidden" name="Page" value="chat" />
			<input type="hidden" name="conv" value="<?=(int)$convId?>" />
			<input type="hidden" name="Action" value="ChatEnvoi" />
			<textarea name="Contenu" id="ChatContenu" rows="3" placeholder="Votre message..."></textarea>
			<input type="submit" value="Envoyer" class="Action" />
		</form>
<?php } else if ($conv->Archive == 'o') { ?>
		<p class="Remarque">Conversation archivée — lecture seule.</p>
<?php } else { ?>
		<p class="Remarque">Vous ne pouvez pas publier dans cette conversation.</p>
<?php } ?>

<script type="text/javascript">
(function(){
	// ---- Panneau d'édition ----
	function chatEdit(convId) {
		var panel = document.getElementById('editPanel-' + convId);
		if (!panel) return;
		var isOpen = panel.style.display !== 'none';
		document.querySelectorAll('.ChatEditPanel').forEach(function(p) { p.style.display = 'none'; });
		if (!isOpen) panel.style.display = 'block';
	}

	function chatArchiver(convId, nom) {
		if (!confirm('Archiver « ' + nom + ' » ?\n\nL\'historique sera conservé en lecture seule.')) return;
		var f = document.getElementById('archForm-' + convId);
		if (f) f.submit();
	}

	function chatRetirerMembre(convId, pseudo) {
		if (!confirm('Retirer ce membre de la conversation ?')) return;
		document.getElementById('ChatMbrConv').value = convId;
		document.getElementById('ChatMbrAction').value = 'RetirerMembreConv';
		document.getElementById('ChatMbrMembre').value = pseudo;
		document.getElementById('ChatMbrForm').submit();
	}

	function chatAjouterMembre(convId) {
		var sel = document.getElementById('ajouterSel-' + convId);
		if (!sel || !sel.value) return;
		document.getElementById('ChatMbrConv').value = convId;
		document.getElementById('ChatMbrAction').value = 'AjouterMembreConv';
		document.getElementById('ChatMbrMembre').value = sel.value;
		document.getElementById('ChatMbrForm').submit();
	}

	// Axe 4 - Nouveau message privé
	function chatOuvrirNouveauMessage() {
		var panel = document.getElementById('ChatNouveauMessage');
		var isOpen = panel.style.display !== 'none';
		if (!isOpen) {
			chatChargerMembres();
		}
		panel.style.display = isOpen ? 'none' : 'block';
	}

	function chatChargerMembres() {
		var sel = document.getElementById('ChatMessageSelect');
		if (!sel || sel.children.length > 1) return; // Déjà chargé
		fetch('index.php?Page=chatapi&action=membres', {credentials:'same-origin'})
			.then(function(r){ return r.json(); })
			.then(function(data){
				if (!data || !data.ok || !data.membres) return;
				data.membres.forEach(function(m){
					var opt = document.createElement('option');
					opt.value = m.pseudo;
					opt.textContent = m.nom;
					sel.appendChild(opt);
				});
			})
			.catch(function(){});
	}

	function chatChargerMessageSelect() {
		var sel = document.getElementById('ChatMessageSelect');
		if (!sel || !sel.value) return;
		var pseudo = sel.value;
		window.location.href = window.location.pathname + '?Page=chat&Prive=' + encodeURIComponent(pseudo);
	}

	// Axe 1b - Afficher les lecteurs du message épinglé
	var epingleMsgId = null;
	var lecteursBtnElem = document.getElementById('ChatEpingLecteurs');
	if (lecteursBtnElem) {
		var fil = document.getElementById('ChatFil');
		if (fil) {
			epingleMsgId = parseInt(fil.getAttribute('data-conv'), 10);
			// Chercher l'id du message épinglé depuis les messages
			var msgs = fil.querySelectorAll('[data-id]');
			for (var i = 0; i < msgs.length; i++) {
				var m = msgs[i];
				var btn = m.querySelector('button[title*="épingler"]') || m.querySelector('button[title*="Épingler"]') || m.querySelector('button[title*="Désépingler"]');
				if (btn) {
					epingleMsgId = parseInt(m.getAttribute('data-id'), 10);
					break;
				}
			}
		}
		lecteursBtnElem.addEventListener('click', function(){
			var popup = document.getElementById('ChatEpingLecteursPopup');
			if (popup.style.display !== 'none') {
				popup.style.display = 'none';
				return;
			}
			if (!epingleMsgId || !fil) return;
			fetch('index.php?Page=chatapi&action=lecteurs&id=' + epingleMsgId + '&conv=' + parseInt(fil.getAttribute('data-conv'), 10), {credentials:'same-origin'})
				.then(function(r){ return r.json(); })
				.then(function(data){
					if (!data || !data.ok) return;
					var html = '<strong>Lu par (' + data.total_lu + '/' + data.total + ') :</strong><br>';
					if (data.lu.length) html += '✓ ' + data.lu.join(', ') + '<br>';
					if (data.nonlu.length) html += '<strong style="color:#dc3545">✗ Pas lu par (' + data.nonlu.length + ') :</strong><br>✗ ' + data.nonlu.join(', ');
					popup.innerHTML = html;
					popup.style.display = 'block';
				})
				.catch(function(){});
		});
	}

	// Axe 2b - Recherche
	var inputRecherche = document.getElementById('ChatRecherche');
	var divsResultats = document.getElementById('ChatResultatsRecherche');
	if (inputRecherche) {
		inputRecherche.addEventListener('keyup', function(e){
			var q = this.value.trim();
			if (q.length < 2) {
				divsResultats.style.display = 'none';
				return;
			}
			fetch('index.php?Page=chatapi&action=search&q=' + encodeURIComponent(q), {credentials:'same-origin'})
				.then(function(r){ return r.json(); })
				.then(function(data){
					if (!data || !data.ok || !data.resultats) {
						divsResultats.style.display = 'none';
						return;
					}
					var html = '';
					for (var i = 0; i < data.resultats.length; i++) {
						var r = data.resultats[i];
						html += '<div style="padding:6px 8px;border-bottom:1px solid #eee;cursor:pointer" onclick="window.location.href=\'?Page=chat&conv='+r.conv+'&depuis='+r.id+'\'"><strong>'+r.nomConv+'</strong><br><em style="color:#999">'+r.apercu+'</em><br><span style="font-size:11px;color:#999">'+r.date+'</span></div>';
					}
					divsResultats.innerHTML = html;
					divsResultats.style.display = 'block';
				})
				.catch(function(){});
		});
		document.addEventListener('click', function(e){
			if (e.target !== inputRecherche) divsResultats.style.display = 'none';
		});
	}

	// Exposition globale
	window.chatEdit = chatEdit;
	window.chatArchiver = chatArchiver;
	window.chatRetirerMembre = chatRetirerMembre;
	window.chatAjouterMembre = chatAjouterMembre;
	window.chatOuvrirNouveauMessage = chatOuvrirNouveauMessage;
	window.chatChargerMessageSelect = chatChargerMessageSelect;

	// Auto-ouvrir le panneau d'édition après un ajout/retrait de membre
	var autoEdit = <?=($editOuvert?$editOuvert:'0')?>;
	if (autoEdit) {
		var p = document.getElementById('editPanel-' + autoEdit);
		if (p) p.style.display = 'block';
	}

	// ---- Messagerie en temps réel ----
	var fil = document.getElementById('ChatFil');
	if (!fil) return;
	var conv = parseInt(fil.getAttribute('data-conv'), 10);
	var dernier = parseInt(fil.getAttribute('data-dernier'), 10) || 0;
	var peutModerer = <?=($peutModerer?'true':'false')?>;
	var enCours = false;

	function api(params){ return fetch('index.php?Page=chatapi&' + params, {credentials:'same-origin'}).then(function(r){return r.json();}); }
	function apiPost(params, body){
		return fetch('index.php?Page=chatapi&' + params, {method:'POST', credentials:'same-origin',
			headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:body}).then(function(r){return r.json();});
	}
	function majBadge(n){
		var b = document.getElementById('ChatBadge');
		if (!b) return;
		if (n > 0){ b.textContent = n; b.style.display = ''; } else { b.style.display = 'none'; }
	}
	function corps(parent, texte){
		var lignes = texte.split('\n');
		for (var i=0;i<lignes.length;i++){ if (i>0) parent.appendChild(document.createElement('br')); parent.appendChild(document.createTextNode(lignes[i])); }
	}
	function highlightMentionsJS(texte){
		return texte.replace(/\@(\w+)/g, '<strong style="color:#0066cc">@$1</strong>');
	}
	function corpsAvecMentions(parent, texte){
		var lignes = texte.split('\n');
		for (var i=0;i<lignes.length;i++){
			if (i>0) parent.appendChild(document.createElement('br'));
			var span = document.createElement('span');
			span.innerHTML = highlightMentionsJS(texte.split('\n')[i].replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'));
			parent.appendChild(span);
		}
	}
	function ajoute(m){
		var vide = fil.querySelector('.ChatVide'); if (vide) vide.parentNode.removeChild(vide);
		var div = document.createElement('div');
		div.className = 'ChatMsg' + (m.moi ? ' ChatMsgMoi' : '');
		div.setAttribute('data-id', m.id);
		div.style.position = 'relative';
		var ent = document.createElement('div'); ent.className = 'ChatMsgEntete';
		var a = document.createElement('span'); a.className = 'ChatAuteur'; a.textContent = m.nom;
		var d = document.createElement('span'); d.className = 'ChatDate'; d.textContent = m.date + (m.modifie ? ' <em style="font-size:11px;color:#999">(modifié)</em>' : '');
		d.innerHTML = m.date + (m.modifie ? ' <em style="font-size:11px;color:#999">(modifié)</em>' : '');
		ent.appendChild(a); ent.appendChild(document.createTextNode(' ')); ent.appendChild(d);
		var cps = document.createElement('div'); cps.className = 'ChatMsgCorps'; corpsAvecMentions(cps, m.contenu);
		div.appendChild(ent); div.appendChild(cps);
		if (peutModerer || m.moi){
			var btn = document.createElement('button');
			btn.className = 'ChatSuppr'; btn.innerHTML = '&#10006;'; btn.title = 'Supprimer';
			btn.onclick = function(){ if (!confirm('Supprimer ce message ?')) return;
				apiPost('conv='+conv, 'action=delete&id='+m.id).then(function(){ div.parentNode.removeChild(div); }); };
			var wrap = document.createElement('div'); wrap.className = 'ChatSupprForm'; wrap.appendChild(btn);
			div.appendChild(wrap);
		}
		fil.appendChild(div);
	}
	function poll(){
		if (enCours) return; enCours = true;
		api('action=poll&conv='+conv+'&since='+dernier).then(function(data){
			enCours = false;
			if (!data || !data.ok) return;
			var auBas = (fil.scrollTop + fil.clientHeight >= fil.scrollHeight - 30);
			if (data.messages && data.messages.length){
				data.messages.forEach(function(m){ ajoute(m); if (m.id > dernier) dernier = m.id; });
				if (auBas) fil.scrollTop = fil.scrollHeight;
				apiPost('conv='+conv, 'action=markread&lastid='+dernier).then(function(r){ if (r && r.ok) majBadge(r.nonlus); });
			}
		}).catch(function(){ enCours = false; });
	}

	// Axe 2a : Charger historique
	var btnChargerPlus = document.getElementById('ChatChargerPlus');
	if (btnChargerPlus) {
		btnChargerPlus.addEventListener('click', function(){
			var plusAncien = parseInt(fil.getAttribute('data-plus-ancien'), 10);
			if (!plusAncien) return;
			var oldScrollHeight = fil.scrollHeight;
			api('action=historique&conv='+conv+'&avant='+plusAncien).then(function(data){
				if (!data || !data.ok || !data.messages || !data.messages.length) return;
				// Insérer les messages en haut (dans l'ordre inverse pour insertBefore)
				var container = fil;
				var firstMsg = container.querySelector('[data-id]');
				for (var i = data.messages.length - 1; i >= 0; i--) {
					var m = data.messages[i];
					ajoute(m);
				}
				// Reordonner : les messages doivent être en ordre croissant d'ID
				// Pour simplifier, refaire le tri via une réinsertion
				var allMsgs = Array.from(container.querySelectorAll('[data-id]'));
				allMsgs.sort(function(a, b){ return parseInt(a.getAttribute('data-id'), 10) - parseInt(b.getAttribute('data-id'), 10); });
				for (var j = 0; j < allMsgs.length; j++) {
					container.appendChild(allMsgs[j]);
				}
				// Préserver la position de scroll
				var newScrollHeight = fil.scrollHeight;
				fil.scrollTop += (newScrollHeight - oldScrollHeight);
				// Mettre à jour le plus ancien
				if (data.messages.length > 0) fil.setAttribute('data-plus-ancien', data.messages[0].id);
				// Masquer le bouton si moins de 50 messages
				if (data.messages.length < 50) btnChargerPlus.style.display = 'none';
			}).catch(function(){});
		});
	}

	var form = document.getElementById('ChatForm');
	if (form){
		form.addEventListener('submit', function(e){
			e.preventDefault();
			var ta = document.getElementById('ChatContenu'); var txt = ta.value.trim();
			if (txt === '') return;
			apiPost('conv='+conv, 'action=send&contenu='+encodeURIComponent(txt)).then(function(r){
				if (r && r.ok){ ta.value = ''; poll(); } else { alert((r && r.err) ? r.err : 'Erreur'); }
			});
		});
	}

	fil.scrollTop = fil.scrollHeight;
	majBadge(0);
	setInterval(poll, 4000);
})();
</script>

<?php } ?>
	</div>
</div>
