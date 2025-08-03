<?php
/**
 * Test Edition Creation Fix
 */

require_once 'config/config.php';
require_once 'includes/database.php';
require_once 'classes/Edition.php';

try {
    $edition = new Edition();
    
    // Test data with publication_date (like from form)
    $testData = [
        'title' => 'Test Edition - ' . date('Y-m-d H:i:s'),
        'description' => 'Test description',
        'publication_date' => date('Y-m-d'), // This should work now
        'pdf_path' => 'test/path.pdf',
        'thumbnail_path' => 'test/thumb.png',
        'status' => 'draft'
    ];
    
    echo "<h2>Testing Edition Creation Fix</h2>\n";
    echo "<p>Test data:</p>\n";
    echo "<pre>" . print_r($testData, true) . "</pre>\n";
    
    $result = $edition->create($testData);
    
    if ($result) {
        echo "<p style='color: green;'>✅ SUCCESS: Edition created with ID: $result</p>\n";
        
        // Clean up test data
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("DELETE FROM editions WHERE id = ?");
        $stmt->execute([$result]);
        echo "<p>Test data cleaned up.</p>\n";
    } else {
        echo "<p style='color: red;'>❌ FAILED: Could not create edition</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test with missing date (should use current date)
try {
    echo "<h3>Testing with missing date field</h3>\n";
    
    $testDataNoDate = [
        'title' => 'Test Edition No Date - ' . date('Y-m-d H:i:s'),
        'description' => 'Test without date field',
        'pdf_path' => 'test/path2.pdf',
        'thumbnail_path' => 'test/thumb2.png',
        'status' => 'draft'
    ];
    
    echo "<p>Test data (no date):</p>\n";
    echo "<pre>" . print_r($testDataNoDate, true) . "</pre>\n";
    
    $result2 = $edition->create($testDataNoDate);
    
    if ($result2) {
        echo "<p style='color: green;'>✅ SUCCESS: Edition created with ID: $result2 (auto date)</p>\n";
        
        // Clean up test data
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("DELETE FROM editions WHERE id = ?");
        $stmt->execute([$result2]);
        echo "<p>Test data cleaned up.</p>\n";
    } else {
        echo "<p style='color: red;'>❌ FAILED: Could not create edition without date</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
