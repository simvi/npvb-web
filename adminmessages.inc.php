<?php
// ============================================================
// NPVB - Admin Messages d'Accueil
// Compatible PHP 4 - Pages Perso Free
// Date: 2026-01-24
// ============================================================

if (!$PasseParIndex) { header('Location: index2.php?Page=Erreur404'); return; }
if ($Joueur->DieuToutPuissant != "o") { header('Location: index2.php?Page=accueil'); return; }

// ============================================================
// TRAITEMENT DES ACTIONS (POST)
// ============================================================

$message_success = "";
$message_error = "";

// Action: Créer un nouveau message
if (isset($_POST['action']) && $_POST['action'] == 'create') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (empty($content)) {
        $message_error = "Le contenu du message est obligatoire.";
    } else {
        // Sanitization compatible PHP 4
        $title = mysql_real_escape_string(stripslashes($title), $sdblink);
        $content = mysql_real_escape_string(stripslashes($content), $sdblink);
        $created_by = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);

        $query = "INSERT INTO NPVB_Messages (title, content, is_active, created_at, created_by)
                  VALUES ('$title', '$content', $is_active, NOW(), '$created_by')";

        if (mysql_query($query, $sdblink)) {
            $message_success = "Message créé avec succès.";
        } else {
            $message_error = "Erreur lors de la création du message: " . mysql_error($sdblink);
        }
    }
}

// Action: Mettre à jour un message existant
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = (int)$_POST['id'];
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (empty($content)) {
        $message_error = "Le contenu du message est obligatoire.";
    } else {
        // Sanitization
        $title = mysql_real_escape_string(stripslashes($title), $sdblink);
        $content = mysql_real_escape_string(stripslashes($content), $sdblink);

        $query = "UPDATE NPVB_Messages
                  SET title = '$title',
                      content = '$content',
                      is_active = $is_active,
                      updated_at = NOW()
                  WHERE id = $id";

        if (mysql_query($query, $sdblink)) {
            $message_success = "Message modifié avec succès.";
        } else {
            $message_error = "Erreur lors de la modification du message: " . mysql_error($sdblink);
        }
    }
}

// Action: Supprimer un message
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = (int)$_POST['id'];

    $query = "DELETE FROM NPVB_Messages WHERE id = $id";

    if (mysql_query($query, $sdblink)) {
        $message_success = "Message supprimé avec succès.";
    } else {
        $message_error = "Erreur lors de la suppression du message: " . mysql_error($sdblink);
    }
}

// Action: Basculer le statut actif/inactif
if (isset($_POST['action']) && $_POST['action'] == 'toggle') {
    $id = (int)$_POST['id'];
    $is_active = (int)$_POST['is_active'];
    $new_status = ($is_active == 1) ? 0 : 1;

    $query = "UPDATE NPVB_Messages SET is_active = $new_status WHERE id = $id";

    if (mysql_query($query, $sdblink)) {
        $message_success = "Statut du message modifié.";
    } else {
        $message_error = "Erreur lors de la modification du statut: " . mysql_error($sdblink);
    }
}

// ============================================================
// RÉCUPÉRATION DES DONNÉES
// ============================================================

// Récupérer le message en cours d'édition
$edit_message = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $query = "SELECT * FROM NPVB_Messages WHERE id = $edit_id";
    $result = mysql_query($query, $sdblink);
    if ($result && mysql_num_rows($result) > 0) {
        $edit_message = mysql_fetch_object($result);
    }
}

// Récupérer tous les messages (triés par date décroissante)
$messages = array();
$query = "SELECT * FROM NPVB_Messages ORDER BY created_at DESC";
$result = mysql_query($query, $sdblink);
if ($result) {
    while ($row = mysql_fetch_object($result)) {
        $messages[] = $row;
    }
}

?>

<style>
/* Styles spécifiques pour la gestion des messages */
.admin-messages {
    padding: 20px;
}

.admin-messages h2 {
    color: #003366;
    border-bottom: 2px solid #003366;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.admin-messages .message-form {
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    margin-bottom: 30px;
}

.admin-messages .form-group {
    margin-bottom: 15px;
}

.admin-messages .form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}

.admin-messages .form-group input[type="text"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 3px;
    box-sizing: border-box;
}

.admin-messages .form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 3px;
    min-height: 150px;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

.admin-messages .form-group .checkbox-wrapper {
    display: flex;
    align-items: center;
}

.admin-messages .form-group input[type="checkbox"] {
    margin-right: 8px;
}

.admin-messages .form-actions {
    margin-top: 20px;
}

.admin-messages .btn {
    padding: 10px 20px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-weight: bold;
    margin-right: 10px;
}

.admin-messages .btn-primary {
    background: #0066cc;
    color: white;
}

.admin-messages .btn-primary:hover {
    background: #0052a3;
}

.admin-messages .btn-secondary {
    background: #6c757d;
    color: white;
}

.admin-messages .btn-secondary:hover {
    background: #5a6268;
}

.admin-messages .alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 3px;
}

.admin-messages .alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.admin-messages .alert-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.admin-messages .messages-list {
    margin-top: 30px;
}

.admin-messages .message-item {
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
}

.admin-messages .message-item.inactive {
    background: #f9f9f9;
    opacity: 0.7;
}

.admin-messages .message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.admin-messages .message-title {
    font-size: 18px;
    font-weight: bold;
    color: #003366;
}

.admin-messages .message-status {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.admin-messages .message-status.active {
    background: #d4edda;
    color: #155724;
}

.admin-messages .message-status.inactive {
    background: #f8d7da;
    color: #721c24;
}

.admin-messages .message-content {
    margin: 15px 0;
    line-height: 1.6;
}

.admin-messages .message-meta {
    font-size: 12px;
    color: #666;
    margin-bottom: 10px;
}

.admin-messages .message-actions {
    display: flex;
    gap: 10px;
}

.admin-messages .btn-sm {
    padding: 5px 10px;
    font-size: 13px;
}

.admin-messages .btn-danger {
    background: #dc3545;
    color: white;
}

.admin-messages .btn-danger:hover {
    background: #c82333;
}

.admin-messages .btn-warning {
    background: #ffc107;
    color: #212529;
}

.admin-messages .btn-warning:hover {
    background: #e0a800;
}

.admin-messages .btn-info {
    background: #17a2b8;
    color: white;
}

.admin-messages .btn-info:hover {
    background: #138496;
}

.admin-messages .no-messages {
    text-align: center;
    padding: 40px;
    color: #666;
    font-style: italic;
}
</style>

<div class="admin-messages">
    <h2><?php echo $edit_message ? 'Modifier un message' : 'Gestion des Messages d\'Accueil'; ?></h2>

    <?php if ($message_success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($message_success, ENT_QUOTES, 'ISO-8859-1'); ?>
        </div>
    <?php endif; ?>

    <?php if ($message_error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($message_error, ENT_QUOTES, 'ISO-8859-1'); ?>
        </div>
    <?php endif; ?>

    <!-- Formulaire de création/édition -->
    <div class="message-form">
        <h3><?php echo $edit_message ? 'Modifier le message' : 'Créer un nouveau message'; ?></h3>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'], ENT_QUOTES); ?>">
            <input type="hidden" name="Page" value="adminmessages" />
            <input type="hidden" name="action" value="<?php echo $edit_message ? 'update' : 'create'; ?>" />
            <?php if ($edit_message): ?>
                <input type="hidden" name="id" value="<?php echo $edit_message->id; ?>" />
            <?php endif; ?>

            <div class="form-group">
                <label for="title">Titre (optionnel)</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="<?php echo $edit_message ? htmlspecialchars($edit_message->title, ENT_QUOTES, 'ISO-8859-1') : ''; ?>"
                    placeholder="Ex: Information importante, Nouveauté, etc."
                    maxlength="255"
                />
            </div>

            <div class="form-group">
                <label for="content">Contenu du message *</label>
                <textarea
                    id="content"
                    name="content"
                    required
                    placeholder="Saisissez le contenu de votre message ici..."
                ><?php echo $edit_message ? htmlspecialchars($edit_message->content, ENT_QUOTES, 'ISO-8859-1') : ''; ?></textarea>
                <small>Vous pouvez utiliser du HTML basique (&lt;b&gt;, &lt;i&gt;, &lt;br/&gt;, &lt;a&gt;, etc.)</small>
            </div>

            <div class="form-group">
                <div class="checkbox-wrapper">
                    <input
                        type="checkbox"
                        id="is_active"
                        name="is_active"
                        <?php echo ($edit_message && $edit_message->is_active) || !$edit_message ? 'checked' : ''; ?>
                    />
                    <label for="is_active">Message actif (visible sur la page d'accueil)</label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_message ? 'Mettre à jour' : 'Créer le message'; ?>
                </button>

                <?php if ($edit_message): ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'], ENT_QUOTES); ?>?Page=adminmessages" class="btn btn-secondary">
                        Annuler
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Liste des messages existants -->
    <div class="messages-list">
        <h3>Messages existants (<?php echo count($messages); ?>)</h3>

        <?php if (empty($messages)): ?>
            <div class="no-messages">
                Aucun message n'a encore été créé. Utilisez le formulaire ci-dessus pour créer votre premier message.
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <div class="message-item <?php echo $msg->is_active ? '' : 'inactive'; ?>">
                    <div class="message-header">
                        <div>
                            <?php if ($msg->title): ?>
                                <div class="message-title">
                                    <?php echo htmlspecialchars($msg->title, ENT_QUOTES, 'ISO-8859-1'); ?>
                                </div>
                            <?php else: ?>
                                <div class="message-title" style="color: #999;">
                                    (Sans titre)
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="message-status <?php echo $msg->is_active ? 'active' : 'inactive'; ?>">
                                <?php echo $msg->is_active ? 'ACTIF' : 'INACTIF'; ?>
                            </span>
                        </div>
                    </div>

                    <div class="message-content">
                        <?php
                        // Afficher les 200 premiers caractères
                        $preview = strip_tags($msg->content);
                        if (strlen($preview) > 200) {
                            echo htmlspecialchars(substr($preview, 0, 200), ENT_QUOTES, 'ISO-8859-1') . '...';
                        } else {
                            echo htmlspecialchars($preview, ENT_QUOTES, 'ISO-8859-1');
                        }
                        ?>
                    </div>

                    <div class="message-meta">
                        Créé le <?php echo date('d/m/Y à H:i', strtotime($msg->created_at)); ?>
                        <?php if ($msg->created_by): ?>
                            par <?php echo htmlspecialchars($msg->created_by, ENT_QUOTES, 'ISO-8859-1'); ?>
                        <?php endif; ?>
                        <?php if ($msg->updated_at): ?>
                            <br/>Modifié le <?php echo date('d/m/Y à H:i', strtotime($msg->updated_at)); ?>
                        <?php endif; ?>
                    </div>

                    <div class="message-actions">
                        <!-- Bouton Éditer -->
                        <a href="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'], ENT_QUOTES); ?>?Page=adminmessages&amp;edit=<?php echo $msg->id; ?>" class="btn btn-sm btn-info">
                            Éditer
                        </a>

                        <!-- Bouton Activer/Désactiver -->
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'], ENT_QUOTES); ?>" style="display: inline;">
                            <input type="hidden" name="Page" value="adminmessages" />
                            <input type="hidden" name="action" value="toggle" />
                            <input type="hidden" name="id" value="<?php echo $msg->id; ?>" />
                            <input type="hidden" name="is_active" value="<?php echo $msg->is_active; ?>" />
                            <button type="submit" class="btn btn-sm btn-warning">
                                <?php echo $msg->is_active ? 'Désactiver' : 'Activer'; ?>
                            </button>
                        </form>

                        <!-- Bouton Supprimer -->
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'], ENT_QUOTES); ?>" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible.');">
                            <input type="hidden" name="Page" value="adminmessages" />
                            <input type="hidden" name="action" value="delete" />
                            <input type="hidden" name="id" value="<?php echo $msg->id; ?>" />
                            <button type="submit" class="btn btn-sm btn-danger">
                                Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
