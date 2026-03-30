<?php
/**
 * Challenge 48h - Ynov Informatique
 * Fichier: profil.php - Vue complète du profil utilisateur avec correction du nom
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

$user_id = $_SESSION['user_id'];

// 2. Récupération des données utilisateur depuis la base de données
try {
    // Veritabanından ismi ve diğer bilgileri çekiyoruz
    $stmt = $pdo->prepare("SELECT name, email, avatar, total_score FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $dbUser = $stmt->fetch();

    // KRİTİK DÜZELTME: Eğer veritabanında isim varsa, session'ı hemen güncelliyoruz 
    // Böylece sağ üstteki header alanında da isim anında görünür hale gelir.
    if ($dbUser && !empty($dbUser['name'])) {
        $_SESSION['name'] = $dbUser['name'];
    }
} catch (Exception $e) {
    $dbUser = null;
}

// 3. RÉCUPÉRATION DES LOGS RÉELS
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

// Değişken atamaları - Fallback (yedek) mekanizması ile
$user_full_name = (!empty($dbUser['name'])) ? $dbUser['name'] : (!empty($_SESSION['name']) ? $_SESSION['name'] : 'Agent Inconnu');
$user_email = $dbUser['email'] ?? $_SESSION['email'] ?? 'test@gmail.com';
$user_current_score = $dbUser['total_score'] ?? 0;
$user_avatar = $dbUser['avatar'] ?? null;

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

        body {
            background-color: #050a0e; 
            font-family: 'Rajdhani', sans-serif;
            color: #e0e0e0;
            margin: 0;
        }

        .bg-grid {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: 
                linear-gradient(to right, rgba(22, 27, 34, 0.7) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(22, 27, 34, 0.7) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: -1;
        }

        .profile-container {
            max-width: 1100px;
            margin: 80px auto;
            padding: 0 25px;
            position: relative;
        }

        .main-profile-card {
            background: rgba(10, 20, 28, 0.9);
            border: 1px solid rgba(0, 255, 255, 0.2);
            border-radius: 15px;
            padding: 45px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 0 35px rgba(0, 255, 255, 0.15);
            backdrop-filter: blur(12px);
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 35px;
            margin-right: 100px; 
        }

        .avatar-box {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid #00ffff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 25px rgba(0, 255, 255, 0.4);
            background: #0a141c;
            overflow: hidden;
        }

        .avatar-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-box i {
            font-size: 55px;
            color: #00ffff;
        }

        .user-info h2 {
            margin: 0;
            font-family: 'Orbitron', sans-serif;
            font-size: 36px;
            letter-spacing: 2px;
            color: #ffffff;
            text-transform: uppercase;
            text-shadow: 0 0 15px rgba(0, 255, 255, 0.3);
        }

        .user-info p {
            margin: 12px 0 0 0;
            color: #00ffff;
            font-size: 18px;
            opacity: 0.8;
            font-weight: 500;
        }

        .score-box-mini {
            background: linear-gradient(135deg, #00ffff 0%, #7d66ff 100%);
            padding: 30px 50px;
            border-radius: 18px;
            text-align: center;
            min-width: 220px;
            box-shadow: 0 12px 30px rgba(0, 255, 255, 0.25);
            position: relative;
        }

        .score-box-mini span {
            display: block;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            color: #fff;
            margin-bottom: 10px;
            letter-spacing: 2.5px;
        }

        .score-box-mini strong {
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            color: #fff;
            display: block;
        }

        .section-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 20px;
            color: #00ffff;
            margin: 70px 0 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .content-card {
            background: rgba(15, 25, 35, 0.7);
            border-radius: 12px;
            padding: 35px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 15px 40px rgba(0,0,0,0.6);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th {
            text-align: left;
            font-size: 14px;
            color: #777;
            padding-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            border-bottom: 2px solid rgba(0, 255, 255, 0.1);
        }

        .history-table td {
            padding: 25px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 17px;
        }

        .mission-name { color: #ffffff; font-weight: 600; }
        .score-value { 
            color: #00ffff; 
            font-weight: bold; 
            text-align: right; 
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
        }
        .date-col { color: #999; font-style: italic; }
        .no-data { text-align: center; color: #555; padding: 30px; font-style: italic; }
    </style>
</head>
<body>

<div class="bg-grid"></div>

<div class="profile-container">
    
    <div class="main-profile-card">
        <div class="user-section">
            <div class="avatar-box">
                <?php if ($user_avatar): ?>
                    <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Avatar Utilisateur">
                <?php else: ?>
                    <i class="fas fa-user-astronaut"></i>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <h2><?php echo htmlspecialchars($user_full_name); ?></h2>
                <p><?php echo htmlspecialchars($user_email); ?></p>
            </div>
        </div>

        <div class="score-box-mini">
            <span>Score Total</span>
            <strong><?php echo number_format($user_current_score, 0, ',', ' '); ?> PTS</strong>
        </div>
    </div>

    <div class="section-title">
        <i class="fas fa-trophy"></i> LOGS DES MISSIONS
    </div>

    <div class="content-card">
        <table class="history-table">
            <thead>
                <tr>
                    <th>Mission / Défi</th>
                    <th>Date de Complétion</th>
                    <th style="text-align: right;">Points</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($missions) > 0): ?>
                    <?php foreach ($missions as $mission): ?>
                        <tr>
                            <td class="mission-name"><?php echo htmlspecialchars($mission['title']); ?></td>
                            <td class="date-col">
                                <?php echo date('d/m/Y H:i', strtotime($mission['completed_at'])); ?>
                            </td>
                            <td class="score-value">+ <?php echo number_format($mission['score'], 0, ',', ' '); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="no-data">Aucune mission complétée pour le moment.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
require_once('../includes/footer.php'); 
?>
</body>
</html>