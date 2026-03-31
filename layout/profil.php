<?php
/**
 * Challenge 48h - Ynov Informatique
 * Fichier: profil.php - Visualiser son profil ou celui d'un autre utilisateur
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// ─── Inclure le header qui charge $pdo et $user ───
$page = 'profil';
require_once('../includes/header.php');

// Déterminer le profil à afficher
$user_id = $_SESSION['user_id'];
$view_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $user_id;
$is_own_profile = ($view_user_id === $user_id);

// Charger les données du profil à afficher
$displayName = null;
$avatarFile = null;
$totalScore = 0;
$user_rank = null;

try {
    $stmt = $pdo->prepare("SELECT id, username, email, profile_image, total_score FROM users WHERE id = ?");
    $stmt->execute([$view_user_id]);
    $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dbUser) {
        $displayName = $dbUser['username'];
        $avatarFile = $dbUser['profile_image'] ?? null;
        $totalScore = (int)($dbUser['total_score'] ?? 0);
    }
} catch (Exception $e) {
    die("Erreur: " . $e->getMessage());
}

if (!$displayName) {
    die("Utilisateur non trouvé.");
}

// Récupération du rang de l'utilisateur
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
    $rankStmt->execute(['uid' => $view_user_id]);
    $rankRow = $rankStmt->fetch(PDO::FETCH_ASSOC);
    if ($rankRow) {
        $user_rank = (int) $rankRow['user_rank'];
    }
} catch (Exception $e) {
    $user_rank = null;
}

try {
    $stmtTotal = $pdo->prepare("SELECT SUM(obtained_score) as total FROM user_scores_per_riddle WHERE user_id = ?");
    $stmtTotal->execute([$view_user_id]);
    $scoreRow = $stmtTotal->fetch();
    $user_current_score = (int)($scoreRow['total'] ?? 0);
} catch (Exception $e) {
    $user_current_score = 0;
}

try {
    $query = "SELECT r.id as riddle_id, r.title, uspr.obtained_score, uspr.solved_at 
              FROM user_scores_per_riddle uspr 
              JOIN riddles r ON uspr.riddle_id = r.id 
              WHERE uspr.user_id = ? 
              ORDER BY uspr.solved_at DESC";
    $stmtLogs = $pdo->prepare($query);
    $stmtLogs->execute([$view_user_id]);
    $missions = $stmtLogs->fetchAll();
} catch (Exception $e) {
    $missions = [];
}

// Variables finales pour l'affichage
$user_full_name = $displayName;
$user_email = $dbUser['email'] ?? $_SESSION['email'] ?? 'test@gmail.com';
// Le score total vient de la somme des points des niveaux (pas de total_score de la table users)
$user_avatar = $avatarFile;

$page = 'profil';
require_once('../includes/header.php'); 
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil Agent | EnYgmes</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@500;700&display=swap');

        body { background-color: #050a0e; font-family: 'Rajdhani', sans-serif; color: #e0e0e0; margin: 0; }
        .bg-grid { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-image: linear-gradient(to right, rgba(22, 27, 34, 0.7) 1px, transparent 1px), linear-gradient(to bottom, rgba(22, 27, 34, 0.7) 1px, transparent 1px); background-size: 40px 40px; z-index: -1; }
        
        .profile-container { max-width: 1300px; margin: 80px auto; padding: 0 30px; }

        .main-profile-card { 
            background: rgba(10, 20, 28, 0.95); 
            border: 2px solid #00ffff; 
            border-radius: 25px; 
            padding: 80px 60px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 0 50px rgba(0, 255, 255, 0.3);
            backdrop-filter: blur(20px);
            min-height: 200px;
        }

        .left-group {
            display: flex;
            align-items: center;
            gap: 50px;
            flex: 1;
            min-width: 0;
            margin-right: 40px;
        }

        .avatar-box {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 5px solid #00ffff;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
            background: #0a141c;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .avatar-box img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-box i { font-size: 85px; color: #00ffff; }

        .user-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .user-text h2 { 
            margin: 0; 
            font-family: 'Orbitron', sans-serif; 
            font-size: 50px; 
            color: #ffffff; 
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-shadow: 0 0 20px rgba(0, 255, 255, 0.4);
        }

        .user-text p { 
            margin: 10px 0 0 0; 
            color: #00ffff; 
            font-size: 24px; 
            opacity: 0.9;
        }

        .score-box { 
            background: linear-gradient(135deg, #00ffff 0%, #7d66ff 100%); 
            padding: 40px 65px; 
            border-radius: 20px; 
            text-align: center; 
            flex-shrink: 0;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }
        .score-box span { color: #fff; font-size: 15px; font-weight: bold; display: block; margin-bottom: 10px; letter-spacing: 3px; }
        .score-box strong { font-family: 'Orbitron', sans-serif; font-size: 45px; color: #fff; display: block; }

        .section-title { font-family: 'Orbitron', sans-serif; color: #00ffff; margin: 80px 0 30px; font-size: 28px; display: flex; align-items: center; gap: 20px; }
        .content-card { background: rgba(15, 25, 35, 0.9); border: 1px solid rgba(0, 255, 255, 0.15); border-radius: 20px; padding: 50px; margin-bottom: 50px; }
        
        .history-table { width: 100%; border-collapse: collapse; }
        .history-table th { text-align: left; color: #00ffff; padding-bottom: 20px; text-transform: uppercase; font-size: 14px; letter-spacing: 1px; border-bottom: 2px solid rgba(0, 255, 255, 0.1); }
        .history-table td { padding: 25px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.05); color: #fff; font-size: 18px; }
        .score-val { text-align: right; color: #00ffff; font-family: 'Orbitron'; font-weight: bold; }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .profile-container { margin: 60px auto; padding: 0 20px; }
            .main-profile-card { padding: 50px 40px; gap: 20px; flex-wrap: wrap; }
            .left-group { gap: 30px; margin-right: 0; }
            .avatar-box { width: 140px; height: 140px; }
            .avatar-box i { font-size: 70px; }
            .user-text h2 { font-size: 36px; }
            .user-text p { font-size: 18px; }
            .score-box { padding: 30px 45px; }
            .score-box span { font-size: 12px; }
            .score-box strong { font-size: 32px; }
            .section-title { margin: 60px 0 20px; font-size: 22px; }
            .content-card { padding: 30px; }
            .history-table td { padding: 20px 0; font-size: 16px; }
        }

        @media (max-width: 768px) {
            .profile-container { margin: 40px auto; padding: 0 15px; }
            .main-profile-card { 
                flex-direction: column;
                padding: 30px 20px; 
                gap: 20px;
                min-height: auto;
                text-align: center;
            }
            .left-group { 
                flex-direction: column;
                gap: 20px;
                width: 100%;
            }
            .avatar-box { width: 120px; height: 120px; margin: 0 auto; }
            .avatar-box i { font-size: 60px; }
            .user-text { align-items: center; }
            .user-text h2 { font-size: 28px; white-space: normal; }
            .user-text p { font-size: 14px; }
            .score-box, [style*="background: linear-gradient"] { 
                padding: 25px 40px;
                width: 100%;
                box-sizing: border-box;
            }
            .score-box span, [style*="font-size: 15px"] { font-size: 11px; }
            .score-box strong, [style*="font-size: 45px"] { font-size: 36px; }
            .section-title { margin: 40px 0 20px; font-size: 18px; gap: 10px; }
            .content-card { padding: 20px; margin-bottom: 30px; }
            .history-table th { font-size: 12px; padding-bottom: 15px; }
            .history-table td { padding: 15px 0; font-size: 14px; }
        }

        @media (max-width: 480px) {
            .profile-container { margin: 30px auto; padding: 0 10px; }
            .main-profile-card { padding: 20px 15px; gap: 15px; }
            .avatar-box { width: 100px; height: 100px; }
            .avatar-box i { font-size: 50px; }
            .user-text h2 { font-size: 22px; }
            .user-text p { font-size: 12px; }
            .score-box, [style*="background: linear-gradient"] { 
                padding: 20px 25px;
                font-size: 14px;
            }
            .score-box strong, [style*="font-size: 45px"] { font-size: 28px; }
            .section-title { margin: 30px 0 15px; font-size: 16px; }
            .content-card { padding: 15px; }
            .history-table th { font-size: 11px; }
            .history-table td { padding: 12px 0; font-size: 12px; }
        }
    </style>
</head>
<body>

<div class="bg-grid"></div>

<div class="profile-container">
    <div class="main-profile-card">
        <div class="left-group">
            <div class="avatar-box" id="avatarContainer">
                <?php 
                if ($user_avatar && $user_avatar !== 'default.png' && strpos($user_avatar, 'data:') === 0): 
                    // C'est un data URL (image en base64 depuis la BD)
                ?>
                    <img src="<?= $user_avatar ?>" alt="Avatar">
                <?php elseif ($user_avatar && $user_avatar !== 'default.png'): 
                    // Ancien format (fichier) - afficher une icône noire
                ?>
                    <i class="fas fa-user-astronaut"></i>
                <?php else: ?>
                    <i class="fas fa-user-astronaut"></i>
                <?php endif; ?>
            </div>

            <div class="user-text">
                <h2><?= htmlspecialchars($user_full_name) ?></h2>
            </div>
        </div>

        <div class="score-box">
            <span>SCORE TOTAL</span>
            <strong><?= number_format($user_current_score, 0, ',', ' ') ?> PTS</strong>
        </div>
        
        <?php if ($user_rank !== null): ?>
        <div style="background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%); padding: 40px 65px; border-radius: 20px; text-align: center; flex-shrink: 0; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);">
            <span style="color:#fff; font-size: 15px; font-weight: bold; display: block; margin-bottom: 10px; letter-spacing: 3px;">CLASSEMENT</span>
            <strong style="font-family: 'Orbitron', sans-serif; font-size: 45px; color: #fff; display: block;"><?= $user_rank <= 3 ? ['🥇', '🥈', '🥉'][$user_rank - 1] : '#' . $user_rank ?></strong>
        </div>
        <?php endif; ?>
    </div>

    <div class="section-title"><i class="fas fa-trophy"></i> LOGS DES MISSIONS</div>
    <div class="content-card">
        <table class="history-table">
            <thead>
                <tr style="border-bottom: 2px solid rgba(0, 255, 255, 0.1);">
                    <th style="text-align:left; padding-bottom:20px; color:#777;">Mission</th>
                    <th style="text-align:left; padding-bottom:20px; color:#777;">Date</th>
                    <th style="text-align:right; padding-bottom:20px; color:#777;">Points</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($missions)): ?>
                    <?php foreach ($missions as $mission): ?>
                        <tr>
                            <td style="color:#fff;"><?= htmlspecialchars($mission['title']) ?></td>
                            <td style="color:#999;"><?= date('d/m/Y H:i', strtotime($mission['solved_at'])) ?></td>
                            <td class="score-value">+ <?= number_format($mission['obtained_score'], 0, ',', ' ') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align:center; padding:30px; color:#555;">Aucune mission.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Gestion du fallback d'avatar sans boucle infinie
document.querySelectorAll('.user-avatar').forEach(img => {
    img.onerror = function() {
        const fallback = this.getAttribute('data-fallback');
        if (fallback && this.src !== fallback) {
            this.src = fallback;
            this.removeAttribute('data-fallback'); // Évite la boucle
        } else {
            // Si aucune image ne fonctionne, affiche l'icône
            this.style.display = 'none';
            const icon = document.createElement('i');
            icon.className = 'fas fa-user-astronaut';
            this.parentElement.innerHTML = '';
            this.parentElement.appendChild(icon);
        }
    };
});
</script>

<?php require_once('../includes/footer.php'); ?>
</body>
</html>