<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
if (!peutAccederPage($Joueur, 'adminchat')){ require("accueil.inc.php"); return;}

$Joueurs = ChargeJoueurs("V", "Nom, Prenom");

// --- Créer un groupe bureau ---
if (isset($_POST['Action']) && $_POST['Action']=='CreerGroupe') {
	$nom = isset($_POST['NomGroupe']) ? trim($_POST['NomGroupe']) : '';
	if ($nom !== '') {
		$n = mysql_real_escape_string($nom, $sdblink);
		mySql_query("INSERT INTO NPVB_Conversations (Type, Nom, DateCreation) VALUES ('bureau', '".$n."', NOW())", $sdblink);
	}
}

// --- Supprimer un groupe bureau ---
if (isset($_POST['Action']) && $_POST['Action']=='SupprimerGroupe') {
	$gid = (int)$_POST['Groupe'];
	if (mySql_fetch_object(mySql_query("SELECT 1 FROM NPVB_Conversations WHERE Id=".$gid." AND Type='bureau'", $sdblink))) {
		mySql_query("DELETE FROM NPVB_MessagesChat WHERE Conversation=".$gid, $sdblink);
		mySql_query("DELETE FROM NPVB_ConversationMembres WHERE Conversation=".$gid, $sdblink);
		mySql_query("DELETE FROM NPVB_Conversations WHERE Id=".$gid." AND Type='bureau'", $sdblink);
	}
}

// --- Ajouter un membre ---
if (isset($_POST['Action']) && $_POST['Action']=='AjouterMembre') {
	$gid = (int)$_POST['Groupe'];
	$m = isset($_POST['Membre']) ? mysql_real_escape_string($_POST['Membre'], $sdblink) : '';
	if ($m && mySql_fetch_object(mySql_query("SELECT 1 FROM NPVB_Conversations WHERE Id=".$gid." AND Type='bureau'", $sdblink))) {
		mySql_query("INSERT IGNORE INTO NPVB_ConversationMembres (Conversation, Joueur) VALUES (".$gid.", '".$m."')", $sdblink);
	}
}

// --- Retirer un membre ---
if (isset($_POST['Action']) && $_POST['Action']=='RetirerMembre') {
	$gid = (int)$_POST['Groupe'];
	$m = isset($_POST['Membre']) ? mysql_real_escape_string($_POST['Membre'], $sdblink) : '';
	if ($m) mySql_query("DELETE FROM NPVB_ConversationMembres WHERE Conversation=".$gid." AND Joueur='".$m."'", $sdblink);
}

// Charger les groupes bureau
$groupes = array();
$res = mySql_query("SELECT * FROM NPVB_Conversations WHERE Type='bureau' ORDER BY Nom", $sdblink);
while ($g = mySql_fetch_object($res)) { $groupes[] = $g; }
?>

<div class="Explications">
	<h2>Groupes de discussion (bureau, CODIR, commissions…)</h2>
	<p class="Remarque">Crée des conversations fermées et choisis leurs membres. Tous les membres d'un groupe peuvent y écrire.</p>
</div>

<table id="Accueil">
	<tr><td>
		<fieldset>
			<legend>Nouveau groupe</legend>
			<form method="post" action="<?=$PHP_SELF?>">
				<input type="hidden" name="Page" value="adminchat" />
				<input type="hidden" name="Action" value="CreerGroupe" />
				<input type="text" name="NomGroupe" size="30" maxlength="60" placeholder="Nom du groupe (ex: Bureau 2026)" />
				<input type="submit" value="Créer" class="Action" />
			</form>
		</fieldset>
	</td></tr>
</table>

<?php foreach ($groupes as $g) {
	// membres du groupe
	$membres = array();
	$rm = mySql_query("SELECT Joueur FROM NPVB_ConversationMembres WHERE Conversation=".(int)$g->Id, $sdblink);
	while ($x = mySql_fetch_object($rm)) { $membres[$x->Joueur] = true; }
?>
<table id="Accueil">
	<tr><td>
		<fieldset>
			<legend><?=htmlspecialchars($g->Nom, ENT_QUOTES)?></legend>

			<p><strong>Membres (<?=count($membres)?>) :</strong></p>
			<ul>
<?php foreach ($membres as $pseudo => $v) {
				$nom = isset($Joueurs[$pseudo]) ? trim($Joueurs[$pseudo]->Prenom.' '.$Joueurs[$pseudo]->Nom) : $pseudo;
?>
				<li>
					<?=htmlspecialchars($nom, ENT_QUOTES)?>
					<form method="post" action="<?=$PHP_SELF?>" style="display:inline">
						<input type="hidden" name="Page" value="adminchat" />
						<input type="hidden" name="Action" value="RetirerMembre" />
						<input type="hidden" name="Groupe" value="<?=(int)$g->Id?>" />
						<input type="hidden" name="Membre" value="<?=htmlspecialchars($pseudo, ENT_QUOTES)?>" />
						<button type="submit" class="PetitBouton Annule" title="Retirer">&#10006;</button>
					</form>
				</li>
<?php } if (!count($membres)) { ?><li class="Remarque">Aucun membre</li><?php } ?>
			</ul>

			<form method="post" action="<?=$PHP_SELF?>">
				<input type="hidden" name="Page" value="adminchat" />
				<input type="hidden" name="Action" value="AjouterMembre" />
				<input type="hidden" name="Groupe" value="<?=(int)$g->Id?>" />
				Ajouter : <select name="Membre">
					<option value=""></option>
<?php foreach ($Joueurs as $j) { if (isset($membres[$j->Pseudonyme])) continue; ?>
					<option value="<?=htmlspecialchars($j->Pseudonyme, ENT_QUOTES)?>"><?=htmlspecialchars(trim($j->Prenom.' '.$j->Nom), ENT_QUOTES)?></option>
<?php } ?>
				</select>
				<input type="submit" value="Ajouter" class="PetitBouton Action" />
			</form>

			<form method="post" action="<?=$PHP_SELF?>" onsubmit="return confirm('Supprimer le groupe « <?=htmlspecialchars($g->Nom, ENT_QUOTES)?> » et tous ses messages ?');" style="margin-top:10px">
				<input type="hidden" name="Page" value="adminchat" />
				<input type="hidden" name="Action" value="SupprimerGroupe" />
				<input type="hidden" name="Groupe" value="<?=(int)$g->Id?>" />
				<button type="submit" class="PetitBouton Annule">Supprimer ce groupe</button>
			</form>
		</fieldset>
	</td></tr>
</table>
<?php } ?>

<div class="Explications">
	<a href="<?=$PHP_SELF?>?Page=chat">&larr; Retour aux discussions</a>
</div>
