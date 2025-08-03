<?php
/**
 * Browser Path Test
 * Test paths from the root directory perspective (like index.php)
 */

echo "=== BROWSER PATH TEST ===\n";
echo "Testing from root directory perspective\n";
echo str_repeat("-", 50) . "\n";

require_once 'includes/database.php';
$conn = getConnection();

// Get latest edition pages
$pages = $conn->query("SELECT page_number, image_path FROM edition_pages WHERE edition_id = 21 ORDER BY page_number LIMIT 3")->fetchAll();

echo "Testing image paths from root directory:\n";
foreach ($pages as $page) {
    $imagePath = $page['image_path'];
    $exists = file_exists($imagePath) ? "✅ EXISTS" : "❌ MISSING";
    echo "Page " . $page['page_number'] . ": " . $exists . " (" . $imagePath . ")\n";
    
    if (!file_exists($imagePath)) {
        // Try to find the correct path
        $altPath = str_replace('../', '', $imagePath);
        if (file_exists($altPath)) {
            echo "   🔍 Found at: " . $altPath . "\n";
        }
    }
}

echo "\n" . str_repeat("-", 50) . "\n";
echo "Path test complete\n";
?>
