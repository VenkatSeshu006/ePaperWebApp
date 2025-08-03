<?php
require_once '../includes/database.php';
$conn = getConnection();

echo "Database Image Paths:\n";
echo str_repeat("-", 30) . "\n";

$pages = $conn->query("SELECT page_number, image_path FROM edition_pages WHERE edition_id = 21 ORDER BY page_number LIMIT 3")->fetchAll();
foreach ($pages as $page) {
    echo "Page " . $page['page_number'] . ": " . $page['image_path'] . "\n";
}

echo "\nFile System Check:\n";
echo str_repeat("-", 30) . "\n";

foreach ($pages as $page) {
    $dbPath = $page['image_path'];
    $actualPath = '../' . ltrim($dbPath, '/');
    $exists = file_exists($actualPath) ? "EXISTS" : "MISSING";
    echo "DB Path: " . $dbPath . "\n";
    echo "Checking: " . $actualPath . " - " . $exists . "\n";
    
    // Try alternative paths
    $altPath1 = $dbPath; // Direct path
    $altPath2 = './' . ltrim($dbPath, '/'); // From current directory
    
    if (file_exists($altPath1)) {
        echo "Alternative 1 works: " . $altPath1 . "\n";
    }
    if (file_exists($altPath2)) {
        echo "Alternative 2 works: " . $altPath2 . "\n";
    }
    
    echo "\n";
}
?>
