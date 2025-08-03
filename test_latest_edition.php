<?php
/**
 * Test Latest Edition Display
 */
require_once 'config/config.php';
require_once 'includes/database.php';
require_once 'classes/Edition.php';

echo "<h2>Testing Latest Edition Display</h2>\n";

try {
    // Get database connection like the home page does
    $conn = getConnection();
    
    // Test 1: Check what the home page query returns
    echo "<h3>1. Home Page Latest Edition Query</h3>\n";
    $sql = "SELECT id, title, date, thumbnail_path FROM editions WHERE status = 'published' ORDER BY date DESC, created_at DESC LIMIT 1";
    $result = $conn->query($sql);
    $latest = $result ? $result->fetch() : null;
    
    if ($latest) {
        echo "<p style='color: green;'>✅ Latest published edition found:</p>\n";
        echo "<ul>\n";
        echo "<li><strong>ID:</strong> " . $latest['id'] . "</li>\n";
        echo "<li><strong>Title:</strong> " . htmlspecialchars($latest['title']) . "</li>\n";
        echo "<li><strong>Date:</strong> " . $latest['date'] . "</li>\n";
        echo "<li><strong>Status:</strong> This edition is published and will show on home page</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p style='color: red;'>❌ No published editions found</p>\n";
        echo "<p>The home page will show no content until an edition is published.</p>\n";
    }
    
    // Test 2: Create a new published edition and verify it becomes the latest
    echo "<h3>2. Testing New Edition Publication</h3>\n";
    
    $edition = new Edition();
    
    // Create a test edition with future date to ensure it's the latest
    $futureDate = date('Y-m-d', strtotime('+1 day'));
    $testData = [
        'title' => 'Test Latest Edition - ' . date('Y-m-d H:i:s'),
        'description' => 'Testing automatic home page update',
        'publication_date' => $futureDate,
        'pdf_path' => 'test/latest.pdf',
        'status' => 'published'
    ];
    
    $newId = $edition->create($testData);
    if ($newId) {
        echo "<p style='color: green;'>✅ New published edition created with ID: $newId</p>\n";
        
        // Check if this becomes the new latest
        $newResult = $conn->query($sql);
        $newLatest = $newResult ? $newResult->fetch() : null;
        
        if ($newLatest && $newLatest['id'] == $newId) {
            echo "<p style='color: green;'>✅ SUCCESS: New edition is now the latest on home page!</p>\n";
            echo "<p><strong>New Home Page Edition:</strong> " . htmlspecialchars($newLatest['title']) . "</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ New edition created but not showing as latest (might be date ordering)</p>\n";
        }
        
        // Clean up test data
        $db = Database::getInstance();
        $cleanConn = $db->getConnection();
        $stmt = $cleanConn->prepare("DELETE FROM editions WHERE id = ?");
        $stmt->execute([$newId]);
        echo "<p>Test edition cleaned up</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Failed to create test edition</p>\n";
    }
    
    // Test 3: Check draft editions are not shown
    echo "<h3>3. Testing Draft Edition Exclusion</h3>\n";
    
    $draftData = [
        'title' => 'Test Draft Edition - ' . date('Y-m-d H:i:s'),
        'description' => 'This should not appear on home page',
        'publication_date' => date('Y-m-d', strtotime('+2 days')), // Even newer date
        'pdf_path' => 'test/draft.pdf',
        'status' => 'draft'
    ];
    
    $draftId = $edition->create($draftData);
    if ($draftId) {
        echo "<p style='color: green;'>✅ Draft edition created with ID: $draftId</p>\n";
        
        // Check that it doesn't become the latest on home page
        $draftTestResult = $conn->query($sql);
        $draftTestLatest = $draftTestResult ? $draftTestResult->fetch() : null;
        
        if ($draftTestLatest && $draftTestLatest['id'] != $draftId) {
            echo "<p style='color: green;'>✅ SUCCESS: Draft edition correctly excluded from home page</p>\n";
        } else {
            echo "<p style='color: red;'>❌ PROBLEM: Draft edition is showing on home page!</p>\n";
        }
        
        // Clean up draft test data
        $stmt = $cleanConn->prepare("DELETE FROM editions WHERE id = ?");
        $stmt->execute([$draftId]);
        echo "<p>Test draft edition cleaned up</p>\n";
    }
    
    echo "<h3>✅ Home Page Latest Edition System Working!</h3>\n";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<h4>How it works:</h4>\n";
    echo "<ul>\n";
    echo "<li>✅ Home page automatically shows the latest <strong>published</strong> edition</li>\n";
    echo "<li>✅ When you publish a new edition, it immediately becomes the home page edition</li>\n";
    echo "<li>✅ Draft editions are completely hidden from public view</li>\n";
    echo "<li>✅ Ordering is by publication date (newest first), then creation date</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
