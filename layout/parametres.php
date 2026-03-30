<?php 
require_once '../includes/header.php'; 
if (!$user) { header('Location: ../auth/login.php'); exit; }

$msg = $_GET['status'] ?? null;
?>

<div class="settings-page" style="background: #050505; color: white; min-height: 100vh; padding: 50px 20px; font-family: 'Orbitron', sans-serif;">
    <h1 style="text-align: center; color: #00ffff; margin-bottom: 40px; font-size: 24px;">DONNÉES UTILISATEUR</h1>

    <div style="max-width: 500px; margin: 0 auto; background: #0a0c10; border: 1px solid #1a1d23; border-left: 4px solid #7d66ff; padding: 30px; border-radius: 4px;">
        
        <?php if ($msg === 'no_change'): ?>
            <div style="color: #ff4444; background: rgba(255,68,68,0.1); padding: 10px; margin-bottom: 20px; border: 1px solid #ff4444; font-size: 13px;">ERREUR : AUCUNE MODIFICATION DÉTECTÉE.</div>
        <?php elseif ($msg === 'success'): ?>
            <div style="color: #00ff00; background: rgba(0,255,0,0.1); padding: 10px; margin-bottom: 20px; border: 1px solid #00ff00; font-size: 13px;">SUCCÈS : VOTRE PROFIL A ÉTÉ MIS À JOUR.</div>
        <?php endif; ?>

        <form action="traitement_parametres.php" method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #888; font-size: 12px; margin-bottom: 8px;">NOM COMPLET</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" style="width: 100%; background: #050505; border: 1px solid #00ffff; color: white; padding: 12px; border-radius: 4px;" required>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #888; font-size: 12px; margin-bottom: 8px;">ADRESSE EMAIL</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" style="width: 100%; background: #050505; border: 1px solid #00ffff; color: white; padding: 12px; border-radius: 4px;" required>
            </div>

            <div style="margin-bottom: 30px;">
                <label style="display: block; color: #888; font-size: 12px; margin-bottom: 8px;">NOUVEAU MOT DE PASSE</label>
                <input type="password" name="new_password" placeholder="Laisser vide pour ne pas changer" style="width: 100%; background: #050505; border: 1px solid #00ffff; color: white; padding: 12px; border-radius: 4px;">
            </div>

            <button type="submit" style="width: 100%; background: #00ffff; color: black; border: none; padding: 15px; font-weight: bold; cursor: pointer; text-transform: uppercase; border-radius: 2px;">
                Enregistrer les modifications
            </button>
        </form>
    </div>
</div>