<?php
/**
 * Fix Image Paths in Database
 * Remove the '../' prefix from image paths to make them work from root directory
 */

require_once 'includes/database.php';

echo "=== FIXING IMAGE PATHS ===\n";
echo str_repeat("-", 50) . "\n";

try {
    $conn = getConnection();
    
    // Get all pages with ../uploads paths
    $pages = $conn->query("SELECT id, edition_id, page_number, image_path FROM edition_pages WHERE image_path LIKE '../uploads/%'")->fetchAll();
    
    echo "Found " . count($pages) . " pages with incorrect paths\n";
    echo "Fixing paths...\n\n";
    
    $fixed = 0;
    foreach ($pages as $page) {
        $oldPath = $page['image_path'];
        $newPath = str_replace('../uploads/', 'uploads/', $oldPath);
        
        // Update the path
        $stmt = $conn->prepare("UPDATE edition_pages SET image_path = ? WHERE id = ?");
        $result = $stmt->execute([$newPath, $page['id']]);
        
        if ($result) {
            echo "✅ Page " . $page['page_number'] . " (Edition " . $page['edition_id'] . "): " . $oldPath . " → " . $newPath . "\n";
            $fixed++;
        } else {
            echo "❌ Failed to update page " . $page['page_number'] . "\n";
        }
    }
    
    echo "\n" . str_repeat("-", 50) . "\n";
    echo "✅ Fixed " . $fixed . " image paths\n";
    echo "🚀 Your homepage should now display properly!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
