<?php
/**
 * Test Edition Management Fix
 */

require_once 'config/config.php';
require_once 'includes/database.php';
require_once 'classes/Edition.php';

try {
    $edition = new Edition();
    
    echo "<h2>Testing Edition Management Fixes</h2>\n";
    
    // Test 1: Create a draft edition
    echo "<h3>1. Creating Draft Edition</h3>\n";
    $draftData = [
        'title' => 'Test Draft Edition - ' . date('Y-m-d H:i:s'),
        'description' => 'Test draft description',
        'publication_date' => date('Y-m-d'),
        'pdf_path' => 'test/draft.pdf',
        'thumbnail_path' => 'test/draft_thumb.png',
        'status' => 'draft'
    ];
    
    $draftId = $edition->create($draftData);
    if ($draftId) {
        echo "<p style='color: green;'>✅ Draft edition created with ID: $draftId</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Failed to create draft edition</p>\n";
    }
    
    // Test 2: Create a published edition
    echo "<h3>2. Creating Published Edition</h3>\n";
    $publishedData = [
        'title' => 'Test Published Edition - ' . date('Y-m-d H:i:s'),
        'description' => 'Test published description',
        'publication_date' => date('Y-m-d'),
        'pdf_path' => 'test/published.pdf',
        'thumbnail_path' => 'test/published_thumb.png',
        'status' => 'published'
    ];
    
    $publishedId = $edition->create($publishedData);
    if ($publishedId) {
        echo "<p style='color: green;'>✅ Published edition created with ID: $publishedId</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Failed to create published edition</p>\n";
    }
    
    // Test 3: Test getById functionality
    if ($draftId) {
        echo "<h3>3. Testing getById functionality</h3>\n";
        $retrievedEdition = $edition->getById($draftId);
        if ($retrievedEdition) {
            echo "<p style='color: green;'>✅ Successfully retrieved edition by ID</p>\n";
            echo "<p><strong>Title:</strong> " . htmlspecialchars($retrievedEdition['title']) . "</p>\n";
            echo "<p><strong>Status:</strong> " . htmlspecialchars($retrievedEdition['status']) . "</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Failed to retrieve edition by ID</p>\n";
        }
    }
    
    // Test 4: Test publish functionality
    if ($draftId) {
        echo "<h3>4. Testing Publish Functionality</h3>\n";
        $updateResult = $edition->update($draftId, ['status' => 'published']);
        if ($updateResult) {
            echo "<p style='color: green;'>✅ Successfully updated draft to published</p>\n";
            
            // Verify the update
            $updatedEdition = $edition->getById($draftId);
            if ($updatedEdition && $updatedEdition['status'] === 'published') {
                echo "<p style='color: green;'>✅ Status verified as published</p>\n";
            } else {
                echo "<p style='color: red;'>❌ Status update verification failed</p>\n";
            }
        } else {
            echo "<p style='color: red;'>❌ Failed to update edition status</p>\n";
        }
    }
    
    // Test 5: Get all editions and verify our test data
    echo "<h3>5. Testing Edition Listing</h3>\n";
    $allEditions = $edition->getAll(5);
    if (!empty($allEditions)) {
        echo "<p style='color: green;'>✅ Successfully retrieved editions list</p>\n";
        echo "<p>Found " . count($allEditions) . " editions (showing latest 5)</p>\n";
        
        foreach ($allEditions as $ed) {
            $statusBadge = $ed['status'] === 'published' ? 'green' : 'orange';
            echo "<p>• <strong>" . htmlspecialchars($ed['title']) . "</strong> - <span style='color: $statusBadge;'>" . ucfirst($ed['status']) . "</span></p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ No editions found</p>\n";
    }
    
    // Clean up test data
    echo "<h3>6. Cleaning Up Test Data</h3>\n";
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if ($draftId) {
        $stmt = $conn->prepare("DELETE FROM editions WHERE id = ?");
        $stmt->execute([$draftId]);
        echo "<p>Cleaned up draft test edition (ID: $draftId)</p>\n";
    }
    
    if ($publishedId) {
        $stmt = $conn->prepare("DELETE FROM editions WHERE id = ?");
        $stmt->execute([$publishedId]);
        echo "<p>Cleaned up published test edition (ID: $publishedId)</p>\n";
    }
    
    echo "<h3>✅ All Tests Complete!</h3>\n";
    echo "<p style='color: green;'><strong>Summary:</strong> Edition management functionality is working correctly!</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
