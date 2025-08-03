<?php
/**
 * Test Edition Creation Fix
 * Verify that cover_image column error is resolved
 */

require_once '../includes/database.php';
require_once '../classes/Edition.php';

try {
    echo "Testing Edition creation fix...\n";
    echo str_repeat("-", 50) . "\n";
    
    $edition = new Edition();
    
    // Test data
    $testData = [
        'title' => 'Test Edition - Column Fix',
        'description' => 'Testing that cover_image column error is resolved',
        'date' => date('Y-m-d'),
        'thumbnail_path' => '',
        'pdf_path' => '/test/path.pdf',
        'total_pages' => 1,
        'file_size' => 1024,
        'status' => 'draft'
    ];
    
    echo "✅ Attempting to create test edition...\n";
    $result = $edition->create($testData);
    
    if ($result) {
        echo "✅ SUCCESS: Edition created without cover_image error!\n";
        echo "✅ Edition ID: " . $result . "\n";
        
        // Clean up test data
        $db = Database::getInstance();
        $cleanupSql = "DELETE FROM editions WHERE id = ?";
        $db->query($cleanupSql, [$result]);
        echo "✅ Test data cleaned up\n";
    } else {
        echo "❌ FAILED: Edition creation failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), 'cover_image') !== false) {
        echo "\n🔍 DIAGNOSIS: cover_image column error still exists\n";
        echo "This means there may be other references to fix\n";
    }
}

echo "\n" . str_repeat("-", 50) . "\n";
echo "Column fix test complete\n";
?>
