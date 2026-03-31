<?php
/**
 * Challenge 48h - Ynov Informatique
 * Fichier: profil.php - Vue complète du profil utilisateur avec Upload d'Avatar
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données
require_once('../config/database.php');

// 1. Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Vérifier si on affiche le profil d'un autre utilisateur
$view_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user_id'];
$is_own_profile = ($view_user_id === $_SESSION['user_id']);
$user_id = $view_user_id;

// 2. Récupération des données utilisateur
try {
    $stmt = $pdo->prepare("SELECT username, email, profile_image, total_score FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $dbUser = $stmt->fetch();

    // Ne pas modifier la session pour un autre utilisateur
    if ($is_own_profile && $dbUser && !empty($dbUser['username'])) {
        $_SESSION['name'] = $dbUser['username'];
    }
} catch (Exception $e) {
    $dbUser = null;
}

// 2b. Récupération du rang de l'utilisateur
$user_rank = null;
try {
    $rankStmt = $pdo->prepare("
        SELECT user_rank
        FROM (
            SELECT id,
                   ROW_NUMBER() OVER (ORDER BY total_score DESC, username ASC) AS user_rank
            FROM users
        ) ranked
        WHERE id = :uid
    ");
    $rankStmt->execute(['uid' => $user_id]);
    $rankRow = $rankStmt->fetch(PDO::FETCH_ASSOC);
    if ($rankRow) {
        $user_rank = (int) $rankRow['user_rank'];
    }
} catch (Exception $e) {
    $user_rank = null;
}

// 3. RÉCUPÉRATION DES LOGS RÉELS
try {
    $query = "SELECT r.id as riddle_id, r.title, uspr.obtained_score, uspr.solved_at 
              FROM user_scores_per_riddle uspr 
              JOIN riddles r ON uspr.riddle_id = r.id 
              WHERE uspr.user_id = ? 
              ORDER BY uspr.solved_at DESC";
    $stmtLogs = $pdo->prepare($query);
    $stmtLogs->execute([$user_id]);
    $missions = $stmtLogs->fetchAll();
} catch (Exception $e) {
    $missions = [];
}

// Değişken atamaları
$user_full_name = $dbUser['username'] ?? 'Agent Inconnu';
$user_email = $dbUser['email'] ?? 'Non renseigné';
$user_current_score = $dbUser['total_score'] ?? 0;
$user_avatar = $dbUser['profile_image'] ?? null;

// Inclusion du header
require_once('../includes/header.php'); 
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Joueur | EnYgmes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@500;700&display=swap');

        body { background-color: #050a0e; font-family: 'Rajdhani', sans-serif; color: #e0e0e0; margin: 0; }
        .bg-grid { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-image: linear-gradient(to right, rgba(22, 27, 34, 0.7) 1px, transparent 1px), linear-gradient(to bottom, rgba(22, 27, 34, 0.7) 1px, transparent 1px); background-size: 40px 40px; z-index: -1; }
        .profile-container { max-width: 1100px; margin: 80px auto; padding: 0 25px; position: relative; }
        .main-profile-card { background: rgba(10, 20, 28, 0.9); border: 1px solid rgba(0, 255, 255, 0.2); border-radius: 15px; padding: 45px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 0 35px rgba(0, 255, 255, 0.15); backdrop-filter: blur(12px); }
        .user-section { display: flex; align-items: center; gap: 35px; }

        /* AVATAR STYLES */
        .avatar-box {
            width: 120px; height: 120px; border-radius: 50%; border: 3px solid #00ffff;
            position: relative; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 25px rgba(0, 255, 255, 0.4); background: #0a141c;
            overflow: hidden; cursor: pointer;
        }
        .avatar-box img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-box i { font-size: 55px; color: #00ffff; }
        .avatar-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 255, 255, 0.2); display: flex; align-items: center;
            justify-content: center; opacity: 0; transition: 0.3s ease; backdrop-filter: blur(2px);
        }
        .avatar-box:hover .avatar-overlay { opacity: 1; }
        .avatar-overlay span { color: #fff; font-family: 'Orbitron', sans-serif; font-size: 10px; font-weight: bold; text-shadow: 0 0 10px #000; }

        .user-info h2 { margin: 0; font-family: 'Orbitron', sans-serif; font-size: 36px; color: #ffffff; text-transform: uppercase; text-shadow: 0 0 15px rgba(0, 255, 255, 0.3); }
        .user-info p { margin: 12px 0 0 0; color: #00ffff; font-size: 18px; opacity: 0.8; }
        .score-box-mini { background: linear-gradient(135deg, #00ffff 0%, #7d66ff 100%); padding: 30px 50px; border-radius: 18px; text-align: center; box-shadow: 0 12px 30px rgba(0, 255, 255, 0.25); }
        .score-box-mini strong { font-family: 'Orbitron', sans-serif; font-size: 32px; color: #fff; display: block; }
        .section-title { font-family: 'Orbitron', sans-serif; font-size: 20px; color: #00ffff; margin: 70px 0 30px; display: flex; align-items: center; gap: 15px; }
        .content-card { background: rgba(15, 25, 35, 0.7); border-radius: 12px; padding: 35px; border: 1px solid rgba(255, 255, 255, 0.05); }
        .history-table { width: 100%; border-collapse: collapse; }
        .history-table td { padding: 25px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .score-value { color: #00ffff; font-weight: bold; text-align: right; font-family: 'Orbitron', sans-serif; }
    </style>
</head>
<body>

<div class="bg-grid"></div>

<div class="profile-container">
    <div class="main-profile-card">
        <div class="user-section">
            <div class="avatar-box"<?php if ($is_own_profile): ?> onclick="document.getElementById('avatarInput').click();"<?php endif; ?>>
                <?php if ($user_avatar): ?>
                    <img src="../public/uploads/avatars/<?= htmlspecialchars($user_avatar) ?>" alt="Avatar">
                <?php else: ?>
                    <i class="fas fa-user-astronaut"></i>
                <?php endif; ?>
                
                <?php if ($is_own_profile): ?>
                <div class="avatar-overlay">
                    <span>MODIFIER</span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($is_own_profile): ?>
            <input type="file" id="avatarInput" style="display:none;" accept="image/*">
            <?php endif; ?>

            <div class="user-info">
                <h2><?= htmlspecialchars($user_full_name) ?></h2>
                <p><?= htmlspecialchars($user_email) ?></p>
            </div>
        </div>

        <div class="score-box-mini">
            <span style="color:#fff; font-size:12px; font-weight:800; display:block; margin-bottom:10px;">SCORE TOTAL</span>
            <strong><?= number_format($user_current_score, 0, ',', ' ') ?> PTS</strong>
        </div>
        
        <?php if ($user_rank !== null): ?>
        <div class="score-box-mini" style="background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);">
            <span style="color:#fff; font-size:12px; font-weight:800; display:block; margin-bottom:10px;">CLASSEMENT</span>
            <strong><?= $user_rank <= 3 ? ['🥇', '🥈', '🥉'][$user_rank - 1] : '#' . $user_rank ?></strong>
        </div>
        <?php endif; ?>
    </div>

    <div class="section-title"><i class="fas fa-trophy"></i> HISTORIQUE DES MISSIONS</div>
    <div class="content-card">
        <table class="history-table">
            <thead>
                <tr style="border-bottom: 2px solid rgba(0, 255, 255, 0.1);">
                    <th style="text-align:left; padding-bottom:20px; color:#777;">Niveau</th>
                    <th style="text-align:left; padding-bottom:20px; color:#777;">Mission</th>
                    <th style="text-align:left; padding-bottom:20px; color:#777;">Date</th>
                    <th style="text-align:right; padding-bottom:20px; color:#777;">Points</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($missions) > 0): ?>
                    <?php foreach ($missions as $mission): ?>
                        <tr>
                            <td style="color:#00ffff; font-weight:bold;">Niveau <?= htmlspecialchars($mission['riddle_id']) ?></td>
                            <td style="color:#fff;"><?= htmlspecialchars($mission['title']) ?></td>
                            <td style="color:#999;"><?= date('d/m/Y H:i', strtotime($mission['solved_at'])) ?></td>
                            <td class="score-value">+ <?= number_format($mission['obtained_score'], 0, ',', ' ') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:30px; color:#555;">Aucune mission.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
<?php if ($is_own_profile): ?>
document.getElementById('avatarInput').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const formData = new FormData();
        formData.append('avatar', this.files[0]);

        // Yükleme başladığını belirtmek için imleci değiştir
        document.body.style.cursor = 'wait';

        fetch('upload_avatar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Başarılıysa sayfayı yenile ki yeni resim görünsün
                location.reload();
            } else {
                alert("Hata: " + data.message);
                document.body.style.cursor = 'default';
            }
        })
        .catch(error => {
            console.error('Hata:', error);
            alert("Bir bağlantı hatası oluştu.");
            document.body.style.cursor = 'default';
        });
    }
});
<?php endif; ?>
</script>

<?php require_once('../includes/footer.php'); ?>
</body>
</html>