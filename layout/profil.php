<?php
/**
 * Challenge 48h - Ynov Informatique
 * Fichier: profil.php - İsim Düzeltilmiş ve Dev Versiyon
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

// Veritabanından verileri çekme
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

// İSİM MANTIĞI: Eğer isim boşsa mailin başını al
$user_email = $dbUser['email'] ?? 'agent@enyymes.com';
if (!empty($dbUser['name'])) {
    $user_full_name = $dbUser['name'];
} else {
    // Mailin @ öncesini alıp isme çevirir
    $user_full_name = explode('@', $user_email)[0];
}

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
        
        .profile-container { max-width: 1300px; margin: 80px auto; padding: 0 30px; }

        /* ANA MAVİ KART - MAKSİMUM BOYUT */
        .main-profile-card { 
            background: rgba(10, 20, 28, 0.95); 
            border: 2px solid #00ffff; 
            border-radius: 25px; 
            padding: 80px 60px; /* İç boşluklar devleştirildi */
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 0 50px rgba(0, 255, 255, 0.3);
            backdrop-filter: blur(20px);
        }

        /* SOL TARAF - SOLA DAYALI */
        .left-group {
            display: flex;
            align-items: center;
            gap: 50px;
            flex: 1;
            min-width: 0;
        }

        .avatar-box {
            width: 180px; /* Fotoğraf daha da büyüdü */
            height: 180px;
            border-radius: 50%;
            border: 5px solid #00ffff;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
        }
        .avatar-box img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-box i { font-size: 85px; color: #00ffff; display: flex; height: 100%; align-items: center; justify-content: center; }

        .user-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .user-text h2 { 
            margin: 0; 
            font-family: 'Orbitron', sans-serif; 
            font-size: 50px; /* Devasa isim fontu */
            color: #ffffff; 
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
        }

        .user-text p { 
            margin: 10px 0 0 0; 
            color: #00ffff; 
            font-size: 24px; /* Mail fontu büyütüldü */
            opacity: 0.9;
        }

        /* SAĞ TARAF - PUAN */
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

        /* LOG TABLOSU */
        .section-title { font-family: 'Orbitron', sans-serif; color: #00ffff; margin: 80px 0 30px; font-size: 28px; display: flex; align-items: center; gap: 20px; }
        .content-card { background: rgba(15, 25, 35, 0.9); border: 1px solid rgba(0, 255, 255, 0.15); border-radius: 20px; padding: 50px; }
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
            <span>SCORE TOTAL</span>
            <strong><?= number_format($user_current_score, 0, ',', ' ') ?> PTS</strong>
        </div>
    </div>

    <div class="section-title">
        <i class="fas fa-history"></i> LOGS DES MISSIONS
    </div>

    <div class="content-card">
        <table class="history-table" style="width:100%; border-collapse:collapse;">
             <thead>
                <tr>
                    <th style="text-align:left; color:#00ffff; padding-bottom:20px;">Mission</th>
                    <th style="text-align:left; color:#00ffff; padding-bottom:20px;">Date</th>
                    <th style="text-align:right; color:#00ffff; padding-bottom:20px;">Points</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($missions)): ?>
                    <?php foreach ($missions as $mission): ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                            <td style="padding:25px 0;"><?= htmlspecialchars($mission['title']) ?></td>
                            <td style="color:#888;"><?= date('d/m/Y H:i', strtotime($mission['completed_at'])) ?></td>
                            <td style="text-align:right; color:#00ffff; font-family:'Orbitron';">+ <?= number_format($mission['score'], 0, ',', ' ') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align:center; padding:100px; color:#444;">Henüz bir görevi tamamlamadın, Ajan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
</body>
</html>