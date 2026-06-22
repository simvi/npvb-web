<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
if (!$Joueur){ require("accueil.inc.php"); return;}

// Saison sportive d'une date AAAAMMJJ... : sept→août (ex "2025-2026")
function saisonDe($dh) {
	$an = (int)substr($dh, 0, 4);
	$mois = (int)substr($dh, 4, 2);
	return ($mois >= 9) ? ($an."-".($an+1)) : (($an-1)."-".$an);
}

// Charge tous les matchs (hors ASSO/SEANCE) ayant un résultat
$matchs = array();
$res = mySql_query("SELECT DateHeure, Libelle, Intitule, Adversaire, Domicile, Resultat, Analyse
                    FROM NPVB_Evenements
                    WHERE Resultat<>'' AND Libelle NOT IN ('ASSO','SEANCE')
                    ORDER BY DateHeure DESC", $sdblink);
while ($m = mySql_fetch_object($res)) { $matchs[] = $m; }

// Listes pour les filtres
$equipesDispo = array(); $saisonsDispo = array();
foreach ($matchs as $m) {
	$equipesDispo[$m->Libelle] = true;
	$saisonsDispo[saisonDe($m->DateHeure)] = true;
}
ksort($equipesDispo);
krsort($saisonsDispo);

$FiltreEquipe = isset($_GET['Equipe']) ? $_GET['Equipe'] : '';
$FiltreSaison = isset($_GET['Saison']) ? $_GET['Saison'] : '';
?>

<h2>Résultats des rencontres</h2>

<form method="get" action="<?=$PHP_SELF?>" class="Explications">
	<input type="hidden" name="Page" value="resultats" />
	Équipe
	<select name="Equipe">
		<option value="">Toutes</option>
<?php foreach ($equipesDispo as $eq => $v) { ?>
		<option value="<?=htmlspecialchars($eq, ENT_QUOTES)?>"<?=($FiltreEquipe==$eq?" selected=\"selected\"":"")?>><?=htmlspecialchars($eq, ENT_QUOTES)?></option>
<?php } ?>
	</select>
	Saison
	<select name="Saison">
		<option value="">Toutes</option>
<?php foreach ($saisonsDispo as $sa => $v) { ?>
		<option value="<?=htmlspecialchars($sa, ENT_QUOTES)?>"<?=($FiltreSaison==$sa?" selected=\"selected\"":"")?>><?=htmlspecialchars($sa, ENT_QUOTES)?></option>
<?php } ?>
	</select>
	<input type="submit" value="Filtrer" class="PetitBouton Action" />
</form>

<table id="Accueil" class="TableResultats">
	<tr>
		<th>Date</th><th>Équipe</th><th>Rencontre</th><th>Adversaire</th><th>Score</th><th>Détail des sets</th>
	</tr>
<?php
$nb = 0;
foreach ($matchs as $m) {
	if ($FiltreEquipe !== '' && $m->Libelle != $FiltreEquipe) continue;
	if ($FiltreSaison !== '' && saisonDe($m->DateHeure) != $FiltreSaison) continue;
	$nb++;
	$date = substr($m->DateHeure,6,2)."/".substr($m->DateHeure,4,2)."/".substr($m->DateHeure,0,4);
	$lieu = ($m->Domicile=="o") ? "domicile" : (($m->Domicile=="n") ? "extérieur" : "");
	$d = decoderResultat($m->Resultat);
	$score = resultatSetsGagnes($m->Resultat);
	$gagne = ($d['setsL'] > $d['setsV']);
	$detail = "";
	foreach ($d['sets'] as $s) { $detail .= ($detail?" · ":"").$s['L']."-".$s['V']; }
?>
	<tr class="<?=($gagne?"MatchGagne":"MatchPerdu")?>">
		<td><?=$date?></td>
		<td><?=htmlspecialchars($m->Libelle, ENT_QUOTES)?></td>
		<td><?=htmlspecialchars($m->Intitule, ENT_QUOTES)?><?=($lieu?" <em>(".$lieu.")</em>":"")?></td>
		<td><?=htmlspecialchars($m->Adversaire, ENT_QUOTES)?></td>
		<td class="ScoreResultat"><?=$score?></td>
		<td><?=$detail?></td>
	</tr>
<?php } ?>
<?php if ($nb == 0) { ?>
	<tr><td colspan="6" class="AucunEvenement">Aucun résultat pour ce filtre.</td></tr>
<?php } ?>
</table>

<div class="Explications">
	<a href="#HautDePage">Haut de page</a>
</div>
