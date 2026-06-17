<?php
header('Content-Type: application/json');

if (!isset($Joueur) || !is_object($Joueur) || $Joueur->DieuToutPuissant != "o") {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'err' => 'Accès refusé'));
    exit;
}

if (empty($_FILES['image'])) {
    echo json_encode(array('ok' => false, 'err' => 'Aucun fichier fourni'));
    exit;
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(array('ok' => false, 'err' => 'Erreur upload : ' . $file['error']));
    exit;
}

$maxSize = 5 * 1024 * 1024; // 5 Mo
if ($file['size'] > $maxSize) {
    echo json_encode(array('ok' => false, 'err' => 'Fichier trop volumineux (max 5 Mo)'));
    exit;
}

$imageInfo = @getimagesize($file['tmp_name']);
if (!$imageInfo) {
    echo json_encode(array('ok' => false, 'err' => 'Fichier non reconnu comme image'));
    exit;
}

$type = $imageInfo[2];
$allowedTypes = array(
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG => 'png',
    IMAGETYPE_GIF => 'gif',
    IMAGETYPE_WEBP => 'webp'
);

if (!isset($allowedTypes[$type])) {
    echo json_encode(array('ok' => false, 'err' => 'Type d\'image non autorisé'));
    exit;
}

$ext = $allowedTypes[$type];
$filename = 'img_' . time() . '_' . rand(100, 999) . '.' . $ext;
$uploadDir = 'Images/contenu/';
$filepath = $uploadDir . $filename;

if (!is_dir($uploadDir)) {
    echo json_encode(array('ok' => false, 'err' => 'Dossier upload introuvable'));
    exit;
}

if (is_uploaded_file($file['tmp_name'])) {
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(array('ok' => false, 'err' => 'Impossible de sauvegarder le fichier'));
        exit;
    }
} else if (is_file($file['tmp_name'])) {
    if (!copy($file['tmp_name'], $filepath)) {
        echo json_encode(array('ok' => false, 'err' => 'Impossible de sauvegarder le fichier'));
        exit;
    }
} else {
    echo json_encode(array('ok' => false, 'err' => 'Fichier temporaire introuvable'));
    exit;
}

echo json_encode(array('ok' => true, 'url' => $filepath));
?>
