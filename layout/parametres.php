<?php
/**
 * Challenge 48h - Ynov Informatique
 * Fichier: parametres.php - Paramètres du compte et Upload d'Avatar
 * INTÈGRE: traitement_parametres.php + update_process.php
 */

session_start();

// ─── Redirection si non connecté ───
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// ─── Inclure le header (qui charge $user, $pdo, etc) ───
$page = 'parametres';
require_once('../includes/header.php');

// ─── Vérifier que l'user est bien chargé ───
if (!isset($user) || empty($user)) {
    die("Erreur: Utilisateur non trouvé.");
}

// ─── Gestion POST: Mise à jour profil ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $newUsername = trim($_POST['username'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newPassword = $_POST['password'] ?? '';

    // Validation
    $errors = [];
    if (empty($newUsername)) $errors[] = "Le nom d'utilisateur ne peut pas être vide.";
    if (empty($newEmail)) $errors[] = "L'email ne peut pas être vide.";
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
    if (!empty($newPassword) && strlen($newPassword) < 8) $errors[] = "Le mot de passe doit faire au moins 8 caractères.";

    if (empty($errors)) {
        try {
            $updateData = ['username' => $newUsername, 'email' => $newEmail];
            $sql = "UPDATE users SET username = :username, email = :email";
            
            if (!empty($newPassword)) {
                $sql .= ", password = :password";
                $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = :id";
            $updateData['id'] = $_SESSION['user_id'];

            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateData);

            // Update session
            $_SESSION['name'] = $newUsername;
            $_SESSION['username'] = $newUsername;
            $_SESSION['email'] = $newEmail;

            // Update $user array for display
            $user['name'] = $newUsername;
            $user['username'] = $newUsername;
            $user['email'] = $newEmail;

            $_SESSION['success_msg'] = "✅ Modifications enregistrées avec succès!";

        } catch (Exception $e) {
            $_SESSION['error_msg'] = "❌ Erreur lors de la mise à jour: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_msg'] = "❌ Erreurs: " . implode(", ", $errors);
    }
}

// ─── Préparer les données d'affichage ───
$userDisplayName = $user['username'] ?? $user['name'] ?? 'Utilisateur';
$userEmail = $user['email'] ?? '';
$userAvatar = $user['avatar'] ?? null;

// Messages de session
$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramètres | EnYgmes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: #050a0e;
            font-family: 'Rajdhani', sans-serif;
            color: #e0e0e0;
            line-height: 1.6;
        }

        .settings-container {
            max-width: 900px;
            margin: 100px auto;
            padding: 20px;
        }

        h1 {
            font-family: 'Orbitron', sans-serif;
            color: #00ffff;
            text-transform: uppercase;
            margin-bottom: 40px;
            letter-spacing: 2px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert.success {
            background: rgba(57, 255, 20, 0.1);
            border: 1px solid #39ff14;
            color: #39ff14;
        }

        .alert.error {
            background: rgba(255, 77, 77, 0.1);
            border: 1px solid #ff4d4d;
            color: #ff4d4d;
        }

        .settings-card {
            background: rgba(10, 20, 28, 0.95);
            border: 1px solid rgba(0, 255, 255, 0.2);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 0 40px rgba(0, 255, 255, 0.1), inset 0 0 40px rgba(0, 255, 255, 0.02);
        }

        .form-group {
            margin-bottom: 30px;
        }

        label {
            display: block;
            color: #a0a0a0;
            text-transform: uppercase;
            font-size: 12px;
            margin-bottom: 12px;
            letter-spacing: 1px;
            font-weight: 700;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 255, 255, 0.25);
            color: #fff;
            padding: 14px 16px;
            border-radius: 6px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            background: rgba(0, 0, 0, 0.7);
            border-color: #00ffff;
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.3);
        }

        .avatar-edit-box {
            display: flex;
            align-items: flex-start;
            gap: 30px;
            background: rgba(255, 255, 255, 0.02);
            padding: 25px;
            border-radius: 10px;
            border: 1px dashed rgba(0, 255, 255, 0.2);
        }

        .preview-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid #00ffff;
            overflow: hidden;
            background: linear-gradient(135deg, #050a0e, #0a1418);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .preview-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-circle i {
            font-size: 40px;
            color: #00ffff;
        }

        .upload-controls {
            flex: 1;
        }

        .upload-btn-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0, 255, 255, 0.15);
            color: #00ffff;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            border: 1px solid #00ffff;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .upload-btn-label:hover {
            background: rgba(0, 255, 255, 0.25);
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.3);
        }

        #uploadStatus {
            font-size: 12px;
            color: #777;
            margin-top: 12px;
            display: block;
        }

        .btn-save {
            width: 100%;
            background: linear-gradient(135deg, #00ffff, #00cccc);
            color: #000;
            border: none;
            padding: 16px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 6px;
            margin-top: 30px;
            transition: all 0.3s ease;
            letter-spacing: 1px;
            font-size: 14px;
        }

        .btn-save:hover {
            background: linear-gradient(135deg, #00cccc, #00aaaa);
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.4);
            transform: translateY(-2px);
        }

        .btn-save:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>

<div class="settings-container">
    <h1>⚙️ Paramètres du Compte</h1>

    <?php if ($successMsg): ?>
        <div class="alert success">
            <i class="fas fa-check-circle"></i>
            <span><?= htmlspecialchars($successMsg) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="alert error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <div class="settings-card">
        <form action="parametres.php" method="POST">
            <div class="form-group">
                <label for="username">Nom d'Utilisateur</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($userDisplayName) ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Adresse Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($userEmail) ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Nouveau Mot de Passe (optionnel)</label>
                <input type="password" id="password" name="password" placeholder="Laisser vide pour ne pas changer" minlength="8">
            </div>

            <div class="form-group">
                <label>Photo de Profil</label>
                <div class="avatar-edit-box">
                    <div class="preview-circle">
                        <?php if (!empty($userAvatar) && strpos($userAvatar, 'data:') === 0): ?>
                            <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="upload-controls">
                        <label for="avatarInput" class="upload-btn-label">
                            <i class="fas fa-camera"></i> Télécharger une Image
                        </label>
                        <input type="file" id="avatarInput" style="display:none;" accept="image/*" name="avatar">
                        <span id="uploadStatus">JPG, PNG ou GIF. Max 2MB.</span>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-save">💾 Enregistrer les Modifications</button>
        </form>
    </div>
</div>

<script>
document.getElementById('avatarInput')?.addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const status = document.getElementById('uploadStatus');
        const file = this.files[0];

        // Validation taille
        if (file.size > 2 * 1024 * 1024) {
            status.textContent = "❌ Fichier trop volumineux (max 2MB)";
            status.style.color = "#ff4d4d";
            return;
        }

        status.textContent = "⏳ Téléchargement en cours...";
        status.style.color = "#00ffff";

        const formData = new FormData();
        formData.append('avatar', file);

        fetch('upload_avatar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                status.textContent = "✅ Upload réussi, rechargement...";
                status.style.color = "#39ff14";
                setTimeout(() => location.reload(), 1000);
            } else {
                status.textContent = "❌ Erreur: " + data.message;
                status.style.color = "#ff4d4d";
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            status.textContent = "❌ Erreur de connexion";
            status.style.color = "#ff4d4d";
        });
    }
});
</script>

<?php require_once('../includes/footer.php'); ?>
</body>
</html>