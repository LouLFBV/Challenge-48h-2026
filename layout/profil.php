<?php
/**
 * Challenge 48h - Ynov Informatique [cite: 1, 3]
 * Fichier: profile.php - Focus Performance & Missions
 */

require_once('../config/database.php');
require_once('../includes/header.php'); 

// Données Utilisateur
$user_firstname = "Yarkin";
$user_lastname = "Oner";
$user_email = "yarkin.oner@ynov.com"; 
$user_current_score = 1250;
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

        .profile-container {
            max-width: 900px;
            margin: 60px auto;
            padding: 0 20px;
        }

        /* --- Carte de Profil Principale --- */
        .main-profile-card {
            background: rgba(10, 20, 28, 0.8);
            border: 1px solid rgba(0, 255, 255, 0.2);
            border-radius: 12px;
            padding: 30px; /* Augmenté pour plus d'espace */
            display: flex;
            justify-content: space-between; /* Pousse le score vers la droite */
            align-items: center;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.05);
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 25px; /* Espace entre l'avatar et les textes */
        }

        .avatar-box {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 2px solid #00ffff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.2);
            background: #0a141c;
        }

        .avatar-box i {
            font-size: 35px;
            color: #00ffff;
        }

        .user-info h2 {
            margin: 0;
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            letter-spacing: 1px;
            color: #ffffff;
            text-transform: uppercase;
        }

        .user-info p {
            margin: 8px 0 0 0;
            color: #00ffff;
            font-size: 15px;
            opacity: 0.8;
        }

        /* --- Zone Score (Éloignée via justify-content) --- */
        .score-box-mini {
            background: linear-gradient(135deg, #00ffff 0%, #7d66ff 100%);
            padding: 18px 30px;
            border-radius: 10px;
            text-align: center;
            min-width: 160px;
            box-shadow: 0 4px 15px rgba(0, 255, 255, 0.2);
            margin-left: 40px; /* Sécurité supplémentaire pour l'éloignement */
        }

        .score-box-mini span {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #fff;
            margin-bottom: 4px;
            letter-spacing: 1px;
        }

        .score-box-mini strong {
            font-family: 'Orbitron', sans-serif;
            font-size: 22px;
            color: #fff;
        }

        /* --- Historique des Missions --- */
        .section-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            color: #00ffff;
            margin: 50px 0 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            padding: 25px;
            border-left: 3px solid #7d66ff;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th {
            text-align: left;
            font-size: 12px;
            color: #888;
            padding-bottom: 20px;
            text-transform: uppercase;
        }

        .history-table td {
            padding: 18px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 15px;
        }

        .mission-name { color: #fff; font-weight: 600; }
        .score-value { 
            color: #00ffff; 
            font-weight: bold; 
            text-align: right; 
            font-family: 'Orbitron', sans-serif;
        }
    </style>
</head>
<body>

<div class="profile-container">
    
    <div class="main-profile-card">
        <div class="user-section">
            <div class="avatar-box">
                <i class="fas fa-user-astronaut"></i>
            </div>
            <div class="user-info">
                <h2><?php echo $user_firstname . ' ' . $user_lastname; ?></h2>
                <p><?php echo $user_email; ?></p>
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
                <tr>
                    <td class="mission-name">Initialisation Système</td>
                    <td>30/03/2026</td>
                    <td class="score-value">+ 500</td>
                </tr>
                <tr>
                    <td class="mission-name">Décryptage Réseau Alpha</td>
                    <td>29/03/2026</td>
                    <td class="score-value">+ 750</td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<?php require_once('../includes/footer.php'); ?>
</body>
</html>