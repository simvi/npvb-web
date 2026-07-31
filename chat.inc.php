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
	}
	header('Location: '.$PHP_SELF.'?Page=chat');
	return;
}

// Suppression définitive d'une conversation archivée
if ($peutModerer && isset($_POST['Action']) && $_POST['Action']=="SupprimerConversation") {
	$sid = (int)$_POST['conv'];
	$cible = mySql_fetch_object(mySql_query("SELECT Id FROM NPVB_Conversations WHERE Id=".$sid." AND Archive='o'", $sdblink));
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

// Suppression d'un message (admin)
if ($conv && isset($_POST['Action']) && $_POST['Action']=="ChatSupprime" && $peutModerer && isset($_POST['MsgId'])) {
	$mid = (int)$_POST['MsgId'];
	mySql_query("UPDATE NPVB_MessagesChat SET Supprime='o' WHERE Id=".$mid." AND Conversation=".$convId, $sdblink);
}

// ============================================================
// Chargement des messages
// ============================================================
$messages = array();
if ($conv) {
	$res = mySql_query("SELECT m.Id, m.Auteur, m.Contenu, m.DateEnvoi, j.Prenom, j.Nom
	                    FROM NPVB_MessagesChat m LEFT JOIN NPVB_Joueurs j ON j.Pseudonyme = m.Auteur
	                    WHERE m.Conversation=".$convId." AND m.Supprime='n'
	                    ORDER BY m.Id ASC", $sdblink);
	while ($row = mySql_fetch_object($res)) { $messages[] = $row; }
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
		case 'bureau':   return 'Bureau';
		case 'prive':    return 'Privé';
	}
	return '';
}
?>

<div id="ChatLayout">

	<div id="ChatListe">
		<h3>Conversations</h3>

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
				<span class="ChatConvType"><?=chatTypeLabel($c->Type)?></span>
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
				<button class="ChatBtnArch" onclick="chatArchiver(<?=$cid?>, '<?=$nomJS?>')" title="Archiver">↓</button>
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

<?php if ($peutModerer) { ?>
		<div class="ChatAdminZone">
			<div id="ChatNouveauGroupe" style="display:none">
				<form method="post" action="<?=$PHP_SELF?>">
					<input type="hidden" name="Page" value="chat" />
					<input type="hidden" name="Action" value="CreerGroupe" />
					<input type="text" name="NomGroupe" placeholder="Nom du groupe..." maxlength="60" />
					<div class="btnRow">
						<button type="submit" class="PetitBouton Action">Créer</button>
						<button type="button" class="PetitBouton" onclick="document.getElementById('ChatNouveauGroupe').style.display='none'">Annuler</button>
					</div>
				</form>
			</div>
			<button class="ChatGererGroupes" style="background:none;border:none;width:100%;cursor:pointer;text-align:center"
				onclick="var z=document.getElementById('ChatNouveauGroupe');z.style.display=z.style.display==='none'?'block':'none'">＋ Nouveau groupe</button>
			<form method="post" action="<?=$PHP_SELF?>" class="ChatArchForm"
				onsubmit="return confirm('Archiver toutes les conversations d\'équipe ?\n\nL\'historique est conservé en lecture seule et de nouvelles conversations vierges sont recréées.');">
				<input type="hidden" name="Page" value="chat" />
				<input type="hidden" name="Action" value="ChatArchiveEquipes" />
				<button type="submit" class="PetitBouton Annule">Archiver les équipes</button>
			</form>
		</div>
<?php } ?>

<?php $archivesOuvertes = ($conv && $conv->Archive == 'o'); ?>
<?php if (count($conversationsArchives)) { ?>
		<a class="ChatGererGroupes ChatArchivesToggle" href="#"
			onclick="var p=document.getElementById('ChatArchivesListe');var o=p.style.display!=='none';p.style.display=o?'none':'block';this.textContent=o?'Afficher les archives':'Masquer les archives';return false;"
			><?=($archivesOuvertes?'Masquer les archives':'Afficher les archives')?></a>
		<div id="ChatArchivesListe" style="display:<?=($archivesOuvertes?'block':'none')?>;">
<?php foreach ($conversationsArchives as $c) {
	$actif = ($c->Id == $convId);
?>
			<div style="position:relative">
				<a class="ChatConv ChatConvArchive<?=($actif?' ChatConvActif':'')?>" href="<?=$PHP_SELF?>?Page=chat&amp;conv=<?=(int)$c->Id?>">
					<span class="ChatConvType"><?=chatTypeLabel($c->Type)?></span>
					<span class="ChatConvNom"><?=htmlspecialchars(nomConversationPourJoueur($c, $Joueur, $sdblink), ENT_QUOTES)?></span>
				</a>
<?php if ($peutModerer) { ?>
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

		<div id="ChatFil" data-conv="<?=(int)$convId?>" data-dernier="<?=(int)$dernierId?>">
<?php
		if (!count($messages)) {
			echo '<p class="ChatVide">Aucun message pour le moment.</p>';
		}
		foreach ($messages as $m) {
			$estMoi = ($m->Auteur == $Joueur->Pseudonyme);
			$nom = trim($m->Prenom." ".$m->Nom);
			if ($nom=="") $nom = $m->Auteur;
			$heure = substr($m->DateEnvoi, 8, 2)."/".substr($m->DateEnvoi, 5, 2)." ".substr($m->DateEnvoi, 11, 5);
?>
			<div class="ChatMsg<?=($estMoi?" ChatMsgMoi":"")?>" data-id="<?=(int)$m->Id?>">
				<div class="ChatMsgEntete"><span class="ChatAuteur"><?=htmlspecialchars($nom, ENT_QUOTES)?></span> <span class="ChatDate"><?=$heure?></span></div>
				<div class="ChatMsgCorps"><?=nl2br(htmlspecialchars($m->Contenu, ENT_QUOTES))?></div>
<?php if ($peutModerer) { ?>
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

	// Exposition globale
	window.chatEdit = chatEdit;
	window.chatArchiver = chatArchiver;
	window.chatRetirerMembre = chatRetirerMembre;
	window.chatAjouterMembre = chatAjouterMembre;

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
	function ajoute(m){
		var vide = fil.querySelector('.ChatVide'); if (vide) vide.parentNode.removeChild(vide);
		var div = document.createElement('div');
		div.className = 'ChatMsg' + (m.moi ? ' ChatMsgMoi' : '');
		div.setAttribute('data-id', m.id);
		var ent = document.createElement('div'); ent.className = 'ChatMsgEntete';
		var a = document.createElement('span'); a.className = 'ChatAuteur'; a.textContent = m.nom;
		var d = document.createElement('span'); d.className = 'ChatDate'; d.textContent = m.date;
		ent.appendChild(a); ent.appendChild(document.createTextNode(' ')); ent.appendChild(d);
		var cps = document.createElement('div'); cps.className = 'ChatMsgCorps'; corps(cps, m.contenu);
		div.appendChild(ent); div.appendChild(cps);
		if (peutModerer){
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
