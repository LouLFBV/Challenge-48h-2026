<?php
/**
 * fix_db.php — Nettoie les default.png en NULL dans la BD
 * À exécuter une seule fois : http://localhost/Challenge-48h-2026/fix_db.php
 */

require './config/database.php';

try {
    // 1. Mettre à jour les default.png en NULL
    $stmt = $pdo->prepare("UPDATE users SET profile_image = NULL WHERE profile_image = 'default.png'");
    $stmt->execute();
    $count1 = $stmt->rowCount();
    
    echo "✓ " . $count1 . " ligne(s) mises à jour (default.png → NULL)\n";
    
    // 2. Vérifier qu'il n'y a plus de default.png
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE profile_image = 'default.png'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_NUM);
    
    if ($result[0] == 0) {
        echo "✓ Nettoyage terminé avec succès !\n";
    } else {
        echo "✗ " . $result[0] . " default.png restants\n";
    }
    
    // 3. Afficher le résumé
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN profile_image IS NULL THEN 1 ELSE 0 END) as no_avatar FROM users");
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n=== RÉSUMÉ ===\n";
    echo "Total utilisateurs : " . $summary['total'] . "\n";
    echo "Sans avatar : " . $summary['no_avatar'] . "\n";
    
} catch (Exception $e) {
    echo "✗ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
?>
