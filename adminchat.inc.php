<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
header('Location: '.$PHP_SELF.'?Page=chat');
