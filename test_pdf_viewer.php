<?php
/**
 * Test PDF Viewer Implementation
 * Quick test to see if our changes work correctly
 */

require_once 'includes/database.php';

echo "<h1>Testing PDF Viewer Implementation</h1>";

// Get database connection
try {
    $conn = getConnection();
    
    echo "<h2>Database Connection</h2>";
    echo "✅ Database connected successfully<br>";
    
    // Check editions table structure
    echo "<h2>Editions Table Structure</h2>";
    $result = $conn->query("DESCRIBE editions");
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check existing editions
    echo "<h2>Existing Editions</h2>";
    $result = $conn->query("SELECT id, title, date, pdf_path, status FROM editions ORDER BY date DESC LIMIT 5");
    if ($result && $result->rowCount() > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Date</th><th>PDF Path</th><th>Status</th></tr>";
        while ($row = $result->fetch()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['date']) . "</td>";
            echo "<td>" . htmlspecialchars($row['pdf_path'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No editions found in database.<br>";
    }
    
    // Check edition_pages table
    echo "<h2>Edition Pages Table</h2>";
    $result = $conn->query("SELECT COUNT(*) as count FROM edition_pages");
    if ($result) {
        $row = $result->fetch();
        echo "Total individual pages in database: " . $row['count'] . "<br>";
    }
    
    // Test Ghostscript
    echo "<h2>Ghostscript Test</h2>";
    $gsCommand = 'C:\Program Files\gs\gs10.05.1\bin\gswin64c.exe';
    if (file_exists($gsCommand)) {
        echo "✅ Ghostscript found at: " . $gsCommand . "<br>";
        
        // Test version
        $versionCmd = "\"$gsCommand\" --version 2>&1";
        $output = [];
        $return = 0;
        exec($versionCmd, $output, $return);
        if ($return === 0 && !empty($output)) {
            echo "Ghostscript version: " . trim($output[0]) . "<br>";
        }
    } else {
        echo "❌ Ghostscript not found at expected location<br>";
    }
    
    echo "<h2>Configuration Check</h2>";
    echo "Upload directory: " . (is_dir('./uploads/') ? "✅ Exists" : "❌ Missing") . "<br>";
    echo "PHP file uploads: " . (ini_get('file_uploads') ? "✅ Enabled" : "❌ Disabled") . "<br>";
    echo "Max file size: " . ini_get('upload_max_filesize') . "<br>";
    echo "Max post size: " . ini_get('post_max_size') . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
