<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

// Page admin pour gerer les demandes de reset manuellement
// Necessite d'etre admin

// Verifier que l'utilisateur est admin
if (!isset($Joueur) || !is_object($Joueur) || !$Joueur->EstAdmin()) {
    header('Location: index.php?Page=Erreur404');
    return;
}

$MessageConfirmation = "";
$MessageErreur = "";

// Marquer un token comme envoye manuellement
if (isset($_POST['MarquerEnvoye']) && $_POST['MarquerEnvoye'] == 'o') {
    $TokenId = isset($_POST['TokenId']) ? intval($_POST['TokenId']) : 0;

    if ($TokenId > 0) {
        $query = "UPDATE NPVB_PasswordReset
                  SET EmailEnvoye='o'
                  WHERE Id=$TokenId";

        if (mysql_query($query, $sdblink)) {
            $MessageConfirmation = "Email marque comme envoye.";
        } else {
            $MessageErreur = "Erreur lors de la mise a jour.";
        }
    }
}

// Supprimer une demande
if (isset($_POST['SupprimerDemande']) && $_POST['SupprimerDemande'] == 'o') {
    $TokenId = isset($_POST['TokenId']) ? intval($_POST['TokenId']) : 0;

    if ($TokenId > 0) {
        $query = "DELETE FROM NPVB_PasswordReset WHERE Id=$TokenId";

        if (mysql_query($query, $sdblink)) {
            $MessageConfirmation = "Demande supprimee.";
        } else {
            $MessageErreur = "Erreur lors de la suppression.";
        }
    }
}

// Recuperer les demandes en attente (moins de 24h)
$query = "SELECT pr.*, j.Email
          FROM NPVB_PasswordReset pr
          LEFT JOIN NPVB_Joueurs j ON pr.Pseudonyme = j.Pseudonyme
          WHERE pr.Utilise='n'
          AND pr.DateExpiration > NOW()
          AND (pr.EmailEnvoye IS NULL OR pr.EmailEnvoye='n')
          ORDER BY pr.DateCreation DESC";

$result = mysql_query($query, $sdblink);

$demandes = array();
if ($result) {
    while ($row = mysql_fetch_assoc($result)) {
        $demandes[] = $row;
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <title>NPVB - Gestion demandes reset mot de passe</title>
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
        }
        h1 {
            color: #003366;
            border-bottom: 2px solid #003366;
            padding-bottom: 10px;
        }
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #003366;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .lien-reset {
            background: #e7f3ff;
            padding: 10px;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
            margin: 5px 0;
        }
        .btn {
            padding: 8px 15px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .empty {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Gestion des demandes de r&eacute;initialisation</h1>

    <div class="info">
        <h3>Mode manuel activ&eacute;</h3>
        <p>
            <strong>Free.fr bloque toutes les connexions sortantes.</strong>
            Les emails ne peuvent pas &ecirc;tre envoy&eacute;s automatiquement.
        </p>
        <p><strong>Proc&eacute;dure :</strong></p>
        <ol>
            <li>Copier le lien de r&eacute;initialisation ci-dessous</li>
            <li>Envoyer manuellement l'email depuis votre client mail (Gmail, etc.)</li>
            <li>Cliquer "Marquer comme envoy&eacute;" pour retirer de la liste</li>
        </ol>
    </div>

    <? if ($MessageConfirmation) { ?>
    <div class="alert success">
        <?= $MessageConfirmation ?>
    </div>
    <? } ?>

    <? if ($MessageErreur) { ?>
    <div class="alert error">
        <?= $MessageErreur ?>
    </div>
    <? } ?>

    <h2>Demandes en attente (<?= count($demandes) ?>)</h2>

    <? if (count($demandes) == 0) { ?>
    <div class="empty">
        <p>Aucune demande en attente</p>
    </div>
    <? } else { ?>

    <table>
        <tr>
            <th>Date</th>
            <th>Pseudonyme</th>
            <th>Email</th>
            <th>Lien de r&eacute;initialisation</th>
            <th>Actions</th>
        </tr>

        <? foreach ($demandes as $demande) { ?>
        <tr>
            <td><?= date('d/m/Y H:i', strtotime($demande['DateCreation'])) ?></td>
            <td><strong><?= htmlspecialchars($demande['Pseudonyme']) ?></strong></td>
            <td><?= htmlspecialchars($demande['Email']) ?></td>
            <td>
                <div class="lien-reset">
                    http://nantespvb.free.fr/index.php?Page=resetmotdepasse&Token=<?= $demande['Token'] ?>
                </div>
                <small>Expire le : <?= date('d/m/Y H:i', strtotime($demande['DateExpiration'])) ?></small>
            </td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="TokenId" value="<?= $demande['Id'] ?>" />
                    <input type="hidden" name="MarquerEnvoye" value="o" />
                    <input type="submit" value="Marquer envoy&eacute;" class="btn btn-success"
                           onclick="return confirm('Email envoy&eacute; manuellement ?');" />
                </form>

                <form method="post" style="display:inline;">
                    <input type="hidden" name="TokenId" value="<?= $demande['Id'] ?>" />
                    <input type="hidden" name="SupprimerDemande" value="o" />
                    <input type="submit" value="Supprimer" class="btn btn-danger"
                           onclick="return confirm('Supprimer cette demande ?');" />
                </form>
            </td>
        </tr>
        <? } ?>
    </table>

    <? } ?>

    <hr style="margin: 30px 0;" />

    <h3>Template d'email</h3>
    <div style="background:#f9f9f9;padding:15px;border:1px solid #ddd;border-radius:4px;">
        <p><strong>Sujet :</strong> R&eacute;initialisation de votre mot de passe NPVB</p>
        <p><strong>Corps :</strong></p>
        <pre style="white-space:pre-wrap;font-family:Arial;">Bonjour,

Vous avez demand&eacute; la r&eacute;initialisation de votre mot de passe sur le site NPVB.

Pour d&eacute;finir un nouveau mot de passe, cliquez sur le lien ci-dessous :

[COPIER LE LIEN ICI]

Ce lien est valable pendant 2 heures.

Si vous n'&ecirc;tes pas &agrave; l'origine de cette demande, ignorez simplement cet email.

Cordialement,
L'&eacute;quipe NPVB</pre>
    </div>

    <p style="margin-top:20px;">
        <a href="index.php?Page=accueil">Retour &agrave; l'accueil</a>
    </p>
</div>

</body>
</html>
