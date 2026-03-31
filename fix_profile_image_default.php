<?php
/**
 * fix_profile_image_default.php
 * Updates the database schema to use DEFAULT NULL instead of DEFAULT 'default.png'
 */

require './config/database.php';

try {
    // 1. Alter the table to change the default value
    $pdo->exec("ALTER TABLE users MODIFY COLUMN profile_image VARCHAR(255) DEFAULT NULL");
    echo "✓ Database schema updated: profile_image DEFAULT changed to NULL\n";
    
    // 2. Update any existing 'default.png' values to NULL
    $stmt = $pdo->prepare("UPDATE users SET profile_image = NULL WHERE profile_image = 'default.png'");
    $stmt->execute();
    $count = $stmt->rowCount();
    
    if ($count > 0) {
        echo "✓ " . $count . " user(s) with 'default.png' updated to NULL\n";
    } else {
        echo "ℹ No users with 'default.png' found\n";
    }
    
    // 3. Verify the changes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN profile_image IS NULL THEN 1 ELSE 0 END) as no_avatar FROM users");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n=== SUMMARY ===\n";
    echo "Total users: " . $result['total'] . "\n";
    echo "Users without avatar: " . $result['no_avatar'] . "\n";
    echo "\n✓ Database schema fix completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
