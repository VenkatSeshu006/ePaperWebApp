<?php
/**
 * Simple database check for editions
 */
require_once 'config/config.php';
require_once 'includes/database.php';

try {
    $conn = Database::getInstance();
    
    echo "<h2>Database Editions Check</h2>";
    
    // Get all editions directly from database
    $stmt = $conn->query("SELECT id, title, status, date, created_at FROM editions ORDER BY created_at DESC");
    $editions = $stmt->fetchAll();
    
    echo "<p><strong>Total editions in database:</strong> " . count($editions) . "</p>";
    
    if (empty($editions)) {
        echo "<p>No editions found in the database.</p>";
        echo "<p><strong>Possible solutions:</strong></p>";
        echo "<ul>";
        echo "<li>Create some editions through the admin panel</li>";
        echo "<li>Check if the database connection is working</li>";
        echo "<li>Verify the editions table exists and has data</li>";
        echo "</ul>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 8px;'>ID</th>";
        echo "<th style='padding: 8px;'>Title</th>";
        echo "<th style='padding: 8px;'>Status</th>";
        echo "<th style='padding: 8px;'>Date</th>";
        echo "<th style='padding: 8px;'>Created At</th>";
        echo "</tr>";
        
        foreach ($editions as $edition) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($edition['id']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($edition['title']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($edition['status']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($edition['date']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($edition['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count by status
        $publishedCount = 0;
        $draftCount = 0;
        foreach ($editions as $edition) {
            if ($edition['status'] === 'published') {
                $publishedCount++;
            } else {
                $draftCount++;
            }
        }
        
        echo "<h3>Summary by Status:</h3>";
        echo "<p><strong>Published:</strong> $publishedCount</p>";
        echo "<p><strong>Draft/Other:</strong> $draftCount</p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
