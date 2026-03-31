<?php
/**
 * Challenge 48h - Ynov Informatique
 * Fichier: parametres.php - Paramètres du compte et Upload d'Avatar
 * INTÈGRE: traitement_parametres.php + update_process.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('../config/database.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// ═══════════════════════════════════════════════════════
// TRAITEMENT POST : Mise à jour profil + password
// ═══════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $userId = (int) $_SESSION['user_id'];
    $newUsername = $_POST['username'] ?? '';
    $newEmail = $_POST['email'] ?? '';
    $newPassword = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Déterminer si on utilise username ou name
        $hasUsernameColumn = isset($userData['username']);
        $usernameColumn = $hasUsernameColumn ? 'username' : 'name';

        $passwordUpdateSql = "";
        $params = [$newUsername, $newEmail];

        if (!empty($newPassword)) {
            // Valider le nouveau password (min 8 caractères)
            if (strlen($newPassword) < 8) {
                $_SESSION['error_msg'] = "Le mot de passe doit faire au moins 8 caractères.";
                header('Location: parametres.php');
                exit();
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $passwordUpdateSql = ", password = ?";
            $params[] = $hashedPassword;
        }

        $params[] = $userId;

        $updateStmt = $pdo->prepare("UPDATE users SET $usernameColumn = ?, email = ? $passwordUpdateSql WHERE id = ?");
        $updateStmt->execute($params);

        $_SESSION['name'] = $newUsername;
        $_SESSION['email'] = $newEmail;

        $_SESSION['success_msg'] = "Vos modifications ont été enregistrées avec succès !";
        header('Location: parametres.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Une erreur est survenue lors de la mise à jour.";
        header('Location: parametres.php');
        exit();
    }
}

// ═══════════════════════════════════════════════════════
// FIN TRAITEMENT
// ═══════════════════════════════════════════════════════

// Charger les données utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    die("Utilisateur non trouvé.");
}

// Récupérer les données de l'utilisateur
$userDisplayName = $user['username'] ?? 'Utilisateur';
$userAvatar = $user['profile_image'] ?? null;
$userEmail = $user['email'] ?? '';

require_once('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramètres | EnYgmes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@500;700&display=swap');
        body { background-color: #050a0e; font-family: 'Rajdhani', sans-serif; color: #e0e0e0; }
        .settings-container { max-width: 800px; margin: 80px auto; padding: 0 20px; }
        .settings-card { background: rgba(10, 20, 28, 0.9); border: 1px solid rgba(0, 255, 255, 0.2); border-radius: 15px; padding: 40px; box-shadow: 0 0 35px rgba(0, 255, 255, 0.1); }
        h1 { font-family: 'Orbitron'; color: #00ffff; text-transform: uppercase; margin-bottom: 40px; }
        .form-group { margin-bottom: 25px; }
        label { display: block; color: #777; text-transform: uppercase; font-size: 12px; margin-bottom: 10px; letter-spacing: 1px; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(0, 255, 255, 0.2);
            padding: 15px; color: #fff; border-radius: 5px; font-family: 'Rajdhani'; font-size: 16px; box-sizing: border-box;
        }
        .btn-save {
            width: 100%; background: #00ffff; color: #000; border: none; padding: 18px;
            font-family: 'Orbitron'; font-weight: bold; cursor: pointer; border-radius: 5px; margin-top: 20px; transition: 0.3s;
        }
        .btn-save:hover { background: #00cccc; box-shadow: 0 0 20px rgba(0, 255, 255, 0.4); }
        
        .avatar-edit-box {
            display: flex; align-items: center; gap: 20px; background: rgba(255,255,255,0.03);
            padding: 20px; border-radius: 8px; border: 1px dashed rgba(0, 255, 255, 0.2);
        }
        .preview-circle {
            width: 80px; height: 80px; border-radius: 50%; border: 2px solid #00ffff;
            overflow: hidden; background: #050a0e; display: flex; align-items: center; justify-content: center;
        }
        .preview-circle img { width: 100%; height: 100%; object-fit: cover; }
        .upload-btn-label {
            background: rgba(0, 255, 255, 0.1); color: #00ffff; padding: 8px 15px;
            border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: bold; border: 1px solid #00ffff;
        }
    </style>
</head>
<body>

<div class="settings-container">
    <h1>Données Utilisateur</h1>
    
    <div class="settings-card">
        <form action="parametres.php" method="POST">
            <div class="form-group">
                <label>Nom d'Utilisateur</label>
                <input type="text" name="username" value="<?= htmlspecialchars($userDisplayName) ?>" required>
            </div>

            <div class="form-group">
                <label>Adresse Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($userEmail) ?>" required>
            </div>

            <div class="form-group">
                <label>Nouveau Mot de Passe</label>
                <input type="password" name="password" placeholder="Laisser vide pour ne pas changer">
            </div>

            <div class="form-group">
                <label>Photo de Profil</label>
                <div class="avatar-edit-box">
                    <div class="preview-circle">
                        <?php if (!empty($userAvatar) && strpos($userAvatar, 'data:') === 0): ?>
                            <img src="<?= $userAvatar ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-astronaut" style="font-size: 30px; color: #00ffff;"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="avatarInput" class="upload-btn-label">
                            <i class="fas fa-camera"></i> MODIFIER L'IMAGE
                        </label>
                        <input type="file" id="avatarInput" style="display:none;" accept="image/*" name="avatar">
                        <p id="uploadStatus" style="font-size: 11px; color: #555; margin-top: 5px;">JPG, PNG ou GIF. Max 2MB.</p>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-save">ENREGISTRER LES MODIFICATIONS</button>
        </form>
    </div>
</div>

<script>
document.getElementById('avatarInput').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const status = document.getElementById('uploadStatus');
        status.textContent = "Téléchargement...";
        status.style.color = "#00ffff";

        const formData = new FormData();
        formData.append('avatar', this.files[0]);

        fetch('upload_avatar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                status.textContent = "Erreur: " + data.message;
                status.style.color = "#ff4d4d";
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            status.textContent = "Erreur de connexion.";
        });
    }
});
</script>

<?php require_once('../includes/footer.php'); ?>
</body>
</html>