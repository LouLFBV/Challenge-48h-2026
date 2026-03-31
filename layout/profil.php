<?php
/**
 * Challenge 48h - Ynov Informatique
 * Fichier: profil.php - Genişletilmiş ve Hizalanmış Versiyon
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('../config/database.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT name, email, avatar, total_score FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $dbUser = $stmt->fetch();
} catch (Exception $e) {
    $dbUser = null;
}

// Logları çekme
try {
    $query = "SELECT r.title, us.score, us.completed_at 
              FROM user_scores_per_riddle us 
              JOIN riddles r ON us.riddle_id = r.id 
              WHERE us.user_id = ? 
              ORDER BY us.completed_at DESC";
    $stmtLogs = $pdo->prepare($query);
    $stmtLogs->execute([$user_id]);
    $missions = $stmtLogs->fetchAll();
} catch (Exception $e) {
    $missions = [];
}

$user_full_name = $dbUser['name'] ?? 'Agent Inconnu';
$user_email = $dbUser['email'] ?? 'agent@enyymes.com';
$user_current_score = $dbUser['total_score'] ?? 0;
$user_avatar = $dbUser['avatar'] ?? null;

require_once('../includes/header.php'); 
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@500;700&display=swap');

        body { background-color: #050a0e; font-family: 'Rajdhani', sans-serif; color: #e0e0e0; margin: 0; }
        .bg-grid { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-image: linear-gradient(to right, rgba(22, 27, 34, 0.7) 1px, transparent 1px), linear-gradient(to bottom, rgba(22, 27, 34, 0.7) 1px, transparent 1px); background-size: 40px 40px; z-index: -1; }
        
        .profile-container { max-width: 1200px; margin: 100px auto; padding: 0 20px; }

        /* ANA MAVİ KART - DAHA BÜYÜK */
        .main-profile-card { 
            background: rgba(10, 20, 28, 0.95); 
            border: 2px solid #00ffff; 
            border-radius: 20px; 
            padding: 60px 40px; /* Dikey padding 60px'e çıkarıldı */
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 0 40px rgba(0, 255, 255, 0.25);
            backdrop-filter: blur(15px);
        }

        /* SOL GRUP - DAHA SOLA KAYDIRILDI */
        .left-group {
            display: flex;
            align-items: center;
            gap: 40px; /* Foto ile yazı arası açıldı */
            flex: 1;
            padding-left: 10px; /* Yazıları sola daha çok yaklaştırmak için ayar */
            min-width: 0;
        }

        .avatar-box {
            width: 150px; /* Fotoğraf boyutu büyütüldü */
            height: 150px;
            border-radius: 50%;
            border: 4px solid #00ffff;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 0 25px rgba(0, 255, 255, 0.4);
        }
        .avatar-box img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-box i { font-size: 70px; color: #00ffff; display: flex; height: 100%; align-items: center; justify-content: center; }

        .user-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .user-text h2 { 
            margin: 0; 
            font-family: 'Orbitron', sans-serif; 
            font-size: 42px; /* Yazı boyutu büyütüldü */
            color: #ffffff; 
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: 2px;
        }

        .user-text p { 
            margin: 8px 0 0 0; 
            color: #00ffff; 
            font-size: 20px; 
            opacity: 0.8;
            word-break: break-all;
        }

        /* SAĞ PUAN KUTUSU */
        .score-box { 
            background: linear-gradient(135deg, #00ffff 0%, #7d66ff 100%); 
            padding: 30px 50px; 
            border-radius: 18px; 
            text-align: center; 
            flex-shrink: 0;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
            margin-left: 20px;
        }
        .score-box span { color: #fff; font-size: 13px; font-weight: bold; display: block; margin-bottom: 8px; letter-spacing: 2px; }
        .score-box strong { font-family: 'Orbitron', sans-serif; font-size: 38px; color: #fff; display: block; }

        /* TABLO ALANI */
        .section-title { font-family: 'Orbitron', sans-serif; color: #00ffff; margin: 70px 0 25px; font-size: 24px; display: flex; align-items: center; gap: 15px; }
        .content-card { background: rgba(15, 25, 35, 0.85); border: 1px solid rgba(0, 255, 255, 0.1); border-radius: 15px; padding: 40px; }
        .history-table { width: 100%; border-collapse: collapse; }
        .history-table th { text-align: left; padding: 15px; color: #00ffff; opacity: 0.6; font-size: 14px; text-transform: uppercase; border-bottom: 2px solid rgba(0, 255, 255, 0.1); }
        .history-table td { padding: 20px 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); color: #fff; font-size: 17px; }
        .score-value { color: #00ffff; font-family: 'Orbitron'; font-weight: bold; text-align: right; font-size: 18px; }
    </style>
</head>
<body>

<div class="bg-grid"></div>

<div class="profile-container">
    <div class="main-profile-card">
        <div class="left-group">
            <div class="avatar-box">
                <?php if ($user_avatar): ?>
                    <img src="../public/uploads/avatars/<?= htmlspecialchars($user_avatar) ?>" alt="Avatar">
                <?php else: ?>
                    <i class="fas fa-user-astronaut"></i>
                <?php endif; ?>
            </div>
            <div class="user-text">
                <h2><?= htmlspecialchars($user_full_name) ?></h2>
                <p><?= htmlspecialchars($user_email) ?></p>
            </div>
        </div>

        <div class="score-box">
            <span>TOTAL SCORE</span>
            <strong><?= number_format($user_current_score, 0, ',', ' ') ?> PTS</strong>
        </div>
    </div>

    <div class="section-title">
        <i class="fas fa-terminal"></i> LOGS DES MISSIONS
    </div>

    <div class="content-card">
        <table class="history-table">
            <thead>
                <tr>
                    <th>Mission</th>
                    <th>Date d'achèvement</th>
                    <th style="text-align: right;">Points gagnés</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($missions)): ?>
                    <?php foreach ($missions as $mission): ?>
                        <tr>
                            <td><?= htmlspecialchars($mission['title']) ?></td>
                            <td style="color: #aaa;"><?= date('d/m/Y H:i', strtotime($mission['completed_at'])) ?></td>
                            <td class="score-value">+ <?= number_format($mission['score'], 0, ',', ' ') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: #666; padding: 60px; font-style: italic;">Aucune mission enregistrée pour le moment.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
</body>
</html>