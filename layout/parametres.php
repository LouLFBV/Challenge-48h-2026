<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Contrôle de sécurité
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// 2. Récupération des données à jour depuis la DB
require_once '../config/database.php';

// Correction : On demande 'username' au lieu de 'name' dans la requête SQL
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$dbUser = $stmt->fetch();

// Correction des clés : On remplace 'name' par 'username' partout
$userName  = !empty($dbUser['username']) ? $dbUser['username'] : (!empty($_SESSION['username']) ? $_SESSION['username'] : '');
$userEmail = !empty($dbUser['email']) ? $dbUser['email'] : (!empty($_SESSION['email']) ? $_SESSION['email'] : '');

// Mise à jour de la session si nécessaire pour éviter les futurs Warnings
if (!empty($dbUser['username'])) {
    $_SESSION['username'] = $dbUser['username'];
    // Optionnel : on garde 'name' en session aussi pour la compatibilité avec d'autres fichiers
    $_SESSION['name'] = $dbUser['username']; 
}

$page = 'parametres';
include '../includes/header.php'; 
?>

<style>
    /* Arka Plan ve Genel Stil */
    body {
        background-color: #0a0c10 !important;
        background-image: 
            linear-gradient(to right, rgba(22, 27, 34, 0.7) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(22, 27, 34, 0.7) 1px, transparent 1px) !important;
        background-size: 40px 40px !important;
        background-attachment: fixed !important;
        color: #fff;
    }

    /* Pop-up Stil Mesajları */
    .alert-msg {
        max-width: 600px;
        margin: 20px auto;
        padding: 15px;
        border-radius: 4px;
        font-family: 'Orbitron', sans-serif;
        text-align: center;
        font-size: 0.9rem;
        animation: slideDown 0.4s ease;
    }
    .alert-success { background: rgba(0, 240, 255, 0.1); border: 1px solid #00f0ff; color: #00f0ff; }
    .alert-error { background: rgba(255, 49, 49, 0.1); border: 1px solid #ff3131; color: #ff3131; }

    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

    /* Form Tasarımı */
    .settings-container { max-width: 600px; margin: 60px auto; padding: 20px; text-align: center; }
    .page-title { color: #00f0ff; font-family: 'Orbitron', sans-serif; letter-spacing: 2px; margin-bottom: 30px; text-transform: uppercase; }
    .settings-card { background: rgba(13, 17, 23, 0.8); border-left: 3px solid #a855f7; padding: 40px; border-radius: 4px; text-align: left; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); }
    .form-group { margin-bottom: 25px; }
    .form-group label { display: block; color: #8b949e; font-size: 0.8rem; margin-bottom: 8px; font-family: 'Orbitron', sans-serif; }
    .form-group input { width: 100%; background: rgba(0, 0, 0, 0.3); border: 1px solid #00f0ff; padding: 12px 15px; color: #fff; border-radius: 4px; outline: none; transition: 0.3s; }
    .form-group input:focus { box-shadow: 0 0 10px rgba(0, 240, 255, 0.3); }

    .submit-btn { width: 100%; background: #00f0ff; color: #000; border: none; padding: 15px; font-weight: 800; font-family: 'Orbitron', sans-serif; cursor: pointer; text-transform: uppercase; transition: 0.3s; margin-top: 10px; }
    .submit-btn:hover { background: #fff; box-shadow: 0 0 20px rgba(0, 240, 255, 0.6); }
</style>

<main class="settings-container">
    <h1 class="page-title">DONNÉES UTILISATEUR</h1>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert-msg alert-success">
            <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert-msg alert-error">
            <?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
        </div>
    <?php endif; ?>

    <div class="settings-card">
        <form action="update_process.php" method="POST">
            
            <div class="form-group">
                <label>NOM COMPLET</label>
                <input type="text" name="name" value="<?= htmlspecialchars($userName); ?>" placeholder="Yarkin Oner" required>
            </div>

            <div class="form-group">
                <label>ADRESSE EMAIL</label>
                <input type="email" name="email" value="<?= htmlspecialchars($userEmail); ?>" required>
            </div>

            <div class="form-group">
                <label>NOUVEAU MOT DE PASSE</label>
                <input type="password" name="password" placeholder="Laisser vide pour ne pas changer">
            </div>

            <button type="submit" class="submit-btn">ENREGISTRER LES MODIFICATIONS</button>
            
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>