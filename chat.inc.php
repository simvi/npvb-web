<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
if (!$Joueur){ require("accueil.inc.php"); return;}

// Conversation générale (v1)
$convId = 1;
$conv = mySql_fetch_object(mySql_query("SELECT * FROM NPVB_Conversations WHERE Id=".(int)$convId, $sdblink));
if (!$conv) {
	$conv = mySql_fetch_object(mySql_query("SELECT * FROM NPVB_Conversations WHERE Type='generale' ORDER BY Id LIMIT 1", $sdblink));
}
if ($conv) $convId = $conv->Id;

$peutPoster   = ($conv) ? peutPosterConversation($Joueur, $conv->PosterCapacite) : false;
$peutModerer  = peut($Joueur, 'gerer_roles'); // admin : peut supprimer un message
$pseudoEcap   = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);

// --- Envoi d'un message (POST classique, sans JS) ---
if ($conv && isset($_POST['Action']) && $_POST['Action']=="ChatEnvoi" && $peutPoster) {
	$contenu = isset($_POST['Contenu']) ? trim($_POST['Contenu']) : '';
	if ($contenu !== '') {
		$c = mysql_real_escape_string($contenu, $sdblink);
		mySql_query("INSERT INTO NPVB_MessagesChat (Conversation, Auteur, Contenu, DateEnvoi) VALUES (".(int)$convId.", '".$pseudoEcap."', '".$c."', NOW())", $sdblink);
	}
}

// --- Suppression d'un message (admin) ---
if ($conv && isset($_POST['Action']) && $_POST['Action']=="ChatSupprime" && $peutModerer && isset($_POST['MsgId'])) {
	$mid = (int)$_POST['MsgId'];
	mySql_query("UPDATE NPVB_MessagesChat SET Supprime='o' WHERE Id=".$mid." AND Conversation=".(int)$convId, $sdblink);
}

// --- Chargement des messages ---
$messages = array();
if ($conv) {
	$res = mySql_query("SELECT m.Id, m.Auteur, m.Contenu, m.DateEnvoi, j.Prenom, j.Nom
	                    FROM NPVB_MessagesChat m
	                    LEFT JOIN NPVB_Joueurs j ON j.Pseudonyme = m.Auteur
	                    WHERE m.Conversation=".(int)$convId." AND m.Supprime='n'
	                    ORDER BY m.Id ASC", $sdblink);
	while ($row = mySql_fetch_object($res)) { $messages[] = $row; }
}

// --- Marquer comme lu (dernier message vu) ---
$dernierId = count($messages) ? $messages[count($messages)-1]->Id : 0;
if ($conv) {
	mySql_query("INSERT INTO NPVB_MessagesLus (Joueur, Conversation, DernierLuId)
	             VALUES ('".$pseudoEcap."', ".(int)$convId.", ".(int)$dernierId.")
	             ON DUPLICATE KEY UPDATE DernierLuId=GREATEST(DernierLuId, ".(int)$dernierId.")", $sdblink);
}
?>

<h2 id="ChatTitre"><?=($conv)?htmlspecialchars($conv->Nom, ENT_QUOTES):"Discussion"?></h2>

<?php if (!$conv) { ?>
	<div class="Explications"><p class="ModifError">Aucune conversation disponible.</p></div>
<?php } else { ?>

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
		<input type="hidden" name="Action" value="ChatEnvoi" />
		<textarea name="Contenu" id="ChatContenu" rows="3" placeholder="Votre annonce..."></textarea>
		<input type="submit" value="Envoyer" class="Action" />
	</form>
<?php } else { ?>
	<p class="Remarque">Seuls les administrateurs et rédacteurs peuvent publier ici.</p>
<?php } ?>

<script type="text/javascript">
(function(){
	var fil = document.getElementById('ChatFil');
	if (!fil) return;
	var conv = parseInt(fil.getAttribute('data-conv'), 10);
	var dernier = parseInt(fil.getAttribute('data-dernier'), 10) || 0;
	var peutModerer = <?=($peutModerer?'true':'false')?>;
	var enCours = false;

	function api(params){
		return fetch('index.php?Page=chatapi&' + params, {credentials:'same-origin'}).then(function(r){return r.json();});
	}
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
		for (var i=0;i<lignes.length;i++){
			if (i>0) parent.appendChild(document.createElement('br'));
			parent.appendChild(document.createTextNode(lignes[i]));
		}
	}
	function ajoute(m){
		var vide = fil.querySelector('.ChatVide');
		if (vide) vide.parentNode.removeChild(vide);
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
			btn.onclick = function(){
				if (!confirm('Supprimer ce message ?')) return;
				apiPost('conv='+conv, 'action=delete&id='+m.id).then(function(){ div.parentNode.removeChild(div); });
			};
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
				// marque comme lu (on est sur la page)
				apiPost('conv='+conv, 'action=markread&lastid='+dernier).then(function(r){ if (r && r.ok) majBadge(r.nonlus); });
			}
		}).catch(function(){ enCours = false; });
	}

	// Envoi sans rechargement
	var form = document.getElementById('ChatForm');
	if (form){
		form.addEventListener('submit', function(e){
			e.preventDefault();
			var ta = document.getElementById('ChatContenu');
			var txt = ta.value.trim();
			if (txt === '') return;
			apiPost('conv='+conv, 'action=send&contenu='+encodeURIComponent(txt)).then(function(r){
				if (r && r.ok){ ta.value = ''; poll(); } else { alert((r && r.err) ? r.err : 'Erreur'); }
			});
		});
	}

	fil.scrollTop = fil.scrollHeight;
	majBadge(0); // on vient de lire à l'ouverture
	setInterval(poll, 4000);
})();
</script>

<?php } ?>
