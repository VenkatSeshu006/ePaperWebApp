<?php
/**
 * Check current editions in database
 */
require_once 'config/config.php';
require_once 'includes/database.php';

echo "<h2>Database Edition Check</h2>\n";

try {
    $conn = getConnection();
    
    // Check all editions
    echo "<h3>All Editions in Database:</h3>\n";
    $allResult = $conn->query("SELECT id, title, status, date, created_at FROM editions ORDER BY created_at DESC");
    
    if ($allResult) {
        $allEditions = $allResult->fetchAll();
        if (empty($allEditions)) {
            echo "<p style='color: red;'>❌ No editions found in database at all!</p>\n";
        } else {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Date</th><th>Created</th></tr>\n";
            foreach ($allEditions as $ed) {
                $statusColor = $ed['status'] === 'published' ? 'green' : 'orange';
                echo "<tr>";
                echo "<td>" . $ed['id'] . "</td>";
                echo "<td>" . htmlspecialchars($ed['title']) . "</td>";
                echo "<td style='color: $statusColor;'>" . $ed['status'] . "</td>";
                echo "<td>" . $ed['date'] . "</td>";
                echo "<td>" . $ed['created_at'] . "</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
    }
    
    // Check published editions specifically
    echo "<h3>Published Editions Query (what home page should see):</h3>\n";
    $sql = "SELECT id, title, date, thumbnail_path FROM editions WHERE status = 'published' ORDER BY date DESC, created_at DESC LIMIT 1";
    echo "<p><strong>Query:</strong> <code>$sql</code></p>\n";
    
    $result = $conn->query($sql);
    $latest = $result ? $result->fetch() : null;
    
    if ($latest) {
        echo "<p style='color: green;'>✅ Latest published edition found:</p>\n";
        echo "<ul>\n";
        echo "<li><strong>ID:</strong> " . $latest['id'] . "</li>\n";
        echo "<li><strong>Title:</strong> " . htmlspecialchars($latest['title']) . "</li>\n";
        echo "<li><strong>Date:</strong> " . $latest['date'] . "</li>\n";
        echo "<li><strong>Thumbnail:</strong> " . ($latest['thumbnail_path'] ?: 'None') . "</li>\n";
        echo "</ul>\n";
        
        echo "<p style='background: #e8f5e8; padding: 10px;'>This edition should appear on the home page!</p>\n";
    } else {
        echo "<p style='color: red;'>❌ No published editions found!</p>\n";
        echo "<p>This is why the home page shows 'No Edition Available'</p>\n";
        
        // Check if there are any drafts that could be published
        $draftResult = $conn->query("SELECT id, title FROM editions WHERE status = 'draft'");
        if ($draftResult) {
            $drafts = $draftResult->fetchAll();
            if (!empty($drafts)) {
                echo "<p style='color: orange;'>📝 Found " . count($drafts) . " draft edition(s) that could be published:</p>\n";
                foreach ($drafts as $draft) {
                    echo "<p>• " . htmlspecialchars($draft['title']) . " (ID: " . $draft['id'] . ")</p>\n";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
