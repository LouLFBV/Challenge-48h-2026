<?php
// Create a minimal transparent PNG as default avatar
$pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
$filepath = __DIR__ . '/public/uploads/avatars/default.png';

// Ensure directory exists
if (!is_dir(dirname($filepath))) {
    mkdir(dirname($filepath), 0777, true);
}

// Write the file
if (file_put_contents($filepath, $pngData)) {
    echo "✓ default.png created successfully at: " . $filepath . "\n";
    echo "✓ File size: " . filesize($filepath) . " bytes\n";
} else {
    echo "✗ Failed to create default.png\n";
}
?>
