<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
if (!$Joueur){ require("accueil.inc.php"); return;}

$peutModerer = peut($Joueur, 'gerer_roles'); // admin

// Archivage en masse des conversations d'équipe (admin) — réinitialisation de saison
if ($peutModerer && isset($_POST['Action']) && $_POST['Action']=="ChatArchiveEquipes") {
	mySql_query("UPDATE NPVB_Conversations SET Archive='o', ArchiveDate=NOW() WHERE Type='equipe' AND Archive='n'", $sdblink);
}

// Crée (si besoin) une conversation active par équipe — inclut la recréation
// après un archivage de saison
assurerConversationsEquipes($sdblink);

// Ouverture/création d'une conversation privée (?Prive=<pseudo>)
$convForce = 0;
if (isset($_REQUEST['Prive']) && $_REQUEST['Prive'] != '' && $_REQUEST['Prive'] != $Joueur->Pseudonyme) {
	$cibleEcap = mysql_real_escape_string($_REQUEST['Prive'], $sdblink);
	if (mySql_fetch_object(mySql_query("SELECT 1 FROM NPVB_Joueurs WHERE Pseudonyme='".$cibleEcap."' AND Etat='V'", $sdblink))) {
		$convForce = trouverOuCreerPrive($Joueur->Pseudonyme, $_REQUEST['Prive'], $sdblink);
	}
}

// Conversations accessibles au membre (avec non-lus)
$conversations = conversationsAccessibles($Joueur, $sdblink);

// Conversation sélectionnée (Prive prioritaire, sinon ?conv, sinon 1ère accessible)
$convSel = $convForce ? $convForce : (isset($_REQUEST['conv']) ? (int)$_REQUEST['conv'] : 0);
$conv = null;
foreach ($conversations as $c) { if ($c->Id == $convSel) { $conv = $c; break; } }
if (!$conv && count($conversations)) $conv = $conversations[0];
$convId = $conv ? (int)$conv->Id : 0;

$peutPoster  = $conv ? peutPosterDansConv($Joueur, $conv, $sdblink) : false;
$pseudoEcap  = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);

// --- Envoi d'un message (POST classique, sans JS) ---
if ($conv && isset($_POST['Action']) && $_POST['Action']=="ChatEnvoi" && $peutPoster) {
	$contenu = isset($_POST['Contenu']) ? trim($_POST['Contenu']) : '';
	if ($contenu !== '') {
		$cc = mysql_real_escape_string($contenu, $sdblink);
		mySql_query("INSERT INTO NPVB_MessagesChat (Conversation, Auteur, Contenu, DateEnvoi) VALUES (".$convId.", '".$pseudoEcap."', '".$cc."', NOW())", $sdblink);
		$newId = mysql_insert_id($sdblink);
		include_once('push.inc.php');
		$apercu = (strlen($contenu) > 80) ? substr($contenu, 0, 77).'...' : $contenu;
		envoyerPush(destinatairesChat($convId, $Joueur->Pseudonyme, $sdblink), $conv->Nom, $apercu, $sdblink, array('conv' => $convId, 'type' => 'chat'));
	}
}

// --- Suppression d'un message (admin) ---
if ($conv && isset($_POST['Action']) && $_POST['Action']=="ChatSupprime" && $peutModerer && isset($_POST['MsgId'])) {
	$mid = (int)$_POST['MsgId'];
	mySql_query("UPDATE NPVB_MessagesChat SET Supprime='o' WHERE Id=".$mid." AND Conversation=".$convId, $sdblink);
}

// --- Chargement des messages de la conversation active ---
$messages = array();
if ($conv) {
	$res = mySql_query("SELECT m.Id, m.Auteur, m.Contenu, m.DateEnvoi, j.Prenom, j.Nom
	                    FROM NPVB_MessagesChat m LEFT JOIN NPVB_Joueurs j ON j.Pseudonyme = m.Auteur
	                    WHERE m.Conversation=".$convId." AND m.Supprime='n'
	                    ORDER BY m.Id ASC", $sdblink);
	while ($row = mySql_fetch_object($res)) { $messages[] = $row; }
}

// --- Marquer comme lu (dernier message vu) ---
$dernierId = count($messages) ? $messages[count($messages)-1]->Id : 0;
if ($conv) {
	mySql_query("INSERT INTO NPVB_MessagesLus (Joueur, Conversation, DernierLuId)
	             VALUES ('".$pseudoEcap."', ".$convId.", ".(int)$dernierId.")
	             ON DUPLICATE KEY UPDATE DernierLuId=GREATEST(DernierLuId, ".(int)$dernierId.")", $sdblink);
	$conv->nonlus = 0; // on vient de lire
}

// Libellé court du type de conversation
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
<?php if (!count($conversations)) { ?>
		<p class="Remarque">Aucune conversation.</p>
<?php } $sectionArchive = false; foreach ($conversations as $c) {
		if ($c->Archive == 'o' && !$sectionArchive) { $sectionArchive = true; ?>
		<h3 class="ChatArchTitre">Archives</h3>
<?php }
		$actif = ($c->Id == $convId);
?>
		<a class="ChatConv<?=($actif?' ChatConvActif':'')?><?=($c->Archive=='o'?' ChatConvArchive':'')?>" href="<?=$PHP_SELF?>?Page=chat&amp;conv=<?=(int)$c->Id?>">
			<span class="ChatConvType"><?=chatTypeLabel($c->Type)?></span>
			<span class="ChatConvNom"><?=htmlspecialchars(nomConversationPourJoueur($c, $Joueur, $sdblink), ENT_QUOTES)?></span>
<?php if ($c->nonlus > 0) { ?><span class="ChatBadge"><?=(int)$c->nonlus?></span><?php } ?>
		</a>
<?php } ?>
<?php if ($peutModerer) { ?>
		<form method="post" action="<?=$PHP_SELF?>" class="ChatArchForm" onsubmit="return confirm('Archiver toutes les conversations d\'équipe ?\n\nL\'historique est conservé en lecture seule et de nouvelles conversations vierges sont recréées.');">
			<input type="hidden" name="Page" value="chat" />
			<input type="hidden" name="Action" value="ChatArchiveEquipes" />
			<button type="submit" class="PetitBouton Annule">Archiver les conversations d'équipe</button>
		</form>
		<a class="ChatGererGroupes" href="<?=$PHP_SELF?>?Page=adminchat">Gérer les groupes bureau</a>
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
