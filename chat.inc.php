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

<?php } ?>
