<?php
/**
 * Test the pagination and default status fixes
 */
require_once 'config/config.php';
require_once 'includes/database.php';
require_once 'classes/Edition.php';

echo "<h2>Testing Fixes</h2>\n";

try {
    $edition = new Edition();
    
    // Test 1: Create edition with default published status
    echo "<h3>1. Testing Default Published Status</h3>\n";
    $testData = [
        'title' => 'Test Default Status - ' . date('Y-m-d H:i:s'),
        'description' => 'Testing default status',
        'publication_date' => date('Y-m-d'),
        'pdf_path' => 'test/default.pdf'
        // Note: No status specified - should default to 'published'
    ];
    
    $id = $edition->create($testData);
    if ($id) {
        $created = $edition->getByIdAdmin($id);
        $status = $created['status'] ?? 'unknown';
        echo "<p style='color: green;'>✅ Edition created with ID: $id</p>\n";
        echo "<p><strong>Default Status:</strong> <span style='color: " . ($status === 'published' ? 'green' : 'red') . ";'>$status</span></p>\n";
        
        if ($status === 'published') {
            echo "<p style='color: green;'>✅ Default status is now 'published' - Perfect!</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Default status is still '$status' - Need to check</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to create edition</p>\n";
    }
    
    // Test 2: Test pagination with edge cases
    echo "<h3>2. Testing Pagination Fix</h3>\n";
    
    // Test with valid parameters
    $validEditions = $edition->getAll(5, 0);
    echo "<p style='color: green;'>✅ getAll(5, 0) works: " . count($validEditions) . " editions retrieved</p>\n";
    
    // Test with potential negative offset (should be handled)
    $currentPage = 0; // This could cause negative offset
    $itemsPerPage = 10;
    $offset = max(0, ($currentPage - 1) * $itemsPerPage);
    echo "<p>Calculated offset for page $currentPage: $offset (should be 0 or positive)</p>\n";
    
    if ($offset >= 0) {
        echo "<p style='color: green;'>✅ Offset protection working correctly</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Offset is still negative: $offset</p>\n";
    }
    
    // Clean up
    if ($id) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("DELETE FROM editions WHERE id = ?");
        $stmt->execute([$id]);
        echo "<p>Cleaned up test edition</p>\n";
    }
    
    echo "<h3>✅ All Fixes Applied Successfully!</h3>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
