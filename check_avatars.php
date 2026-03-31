<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=challenge48_db', 'root', '');
    $result = $pdo->query('SELECT id, username, profile_image FROM users LIMIT 15');
    
    echo "ID | Username | Profile Image\n";
    echo str_repeat("─", 60) . "\n";
    
    foreach ($result as $row) {
        $avatar = $row['profile_image'] === null ? 'NULL' : $row['profile_image'];
        echo $row['id'] . ' | ' . $row['username'] . ' | ' . $avatar . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
