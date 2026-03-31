<?php
/**
 * upload_avatar.php — Upload et stockage de photo de profil en base64
 * AJAX POST endpoint pour parametres.php
 */

// Désactiver la sortie d'erreur HTML, utiliser JSON uniquement
ob_clean();
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Header JSON en premier
header('Content-Type: application/json; charset=utf-8');

// Auth requise
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Non connecté']));
}

// Vérifier le fichier
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Aucun fichier ou erreur d\'upload']));
}

$file = $_FILES['avatar'];
$maxSize = 2 * 1024 * 1024; // 2MB

// Validation taille
if ($file['size'] > $maxSize) {
    http_response_code(413);
    die(json_encode(['success' => false, 'message' => 'Fichier trop volumineux (max 2MB)']));
}

// Détecter MIME type
$mimeType = null;
if (function_exists('mime_content_type')) {
    $mimeType = mime_content_type($file['tmp_name']);
} elseif (function_exists('finfo_file')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
}

// Fallback si mime_type pas trouvé
if (!$mimeType) {
    $mimeType = 'image/jpeg'; // Default
}

$allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($mimeType, $allowedMimes)) {
    http_response_code(415);
    die(json_encode(['success' => false, 'message' => 'Type de fichier non autorisé']));
}

try {
    require_once '../config/database.php';
    
    // Lire le fichier
    $imageData = file_get_contents($file['tmp_name']);
    if ($imageData === false) {
        throw new Exception('Impossible de lire le fichier');
    }
    
    // Créer data URL
    $dataUrl = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
    
    // Mettre à jour la BD
    $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
    $success = $stmt->execute([$dataUrl, $_SESSION['user_id']]);
    
    if (!$success) {
        throw new Exception('Erreur SQL lors de la mise à jour');
    }
    
    // Mettre à jour la session
    $_SESSION['profile_image'] = $dataUrl;
    
    echo json_encode([
        'success' => true,
        'message' => 'Photo uploadée avec succès'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
