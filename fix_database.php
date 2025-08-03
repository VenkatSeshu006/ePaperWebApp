<?php
/**
 * Fix Database Columns Script
 */
require_once 'includes/database.php';

try {
    $conn = getConnection();
    echo "Database connection successful\n";
    
    // Check if thumbnail column exists (old name)
    $result = $conn->query("SHOW COLUMNS FROM editions LIKE 'thumbnail'");
    if ($result && $result->num_rows > 0) {
        echo "Found old 'thumbnail' column, renaming to 'thumbnail_path'\n";
        $conn->query("ALTER TABLE editions CHANGE COLUMN thumbnail thumbnail_path VARCHAR(500)");
        echo "Column renamed successfully\n";
    } else {
        echo "No old 'thumbnail' column found\n";
    }
    
    // Check if thumbnail_path column exists
    $result = $conn->query("SHOW COLUMNS FROM editions LIKE 'thumbnail_path'");
    if ($result && $result->num_rows > 0) {
        echo "✅ thumbnail_path column exists\n";
    } else {
        echo "Adding thumbnail_path column\n";
        $conn->query("ALTER TABLE editions ADD COLUMN thumbnail_path VARCHAR(500) AFTER date");
        echo "✅ thumbnail_path column added\n";
    }
    
    // Test the query that was failing
    echo "\nTesting editions query...\n";
    $result = $conn->query("SELECT id, title, date, thumbnail_path FROM editions ORDER BY date DESC LIMIT 1");
    if ($result) {
        echo "✅ Query successful!\n";
        $edition = $result->fetch();
        if ($edition) {
            echo "Found edition: " . $edition['title'] . "\n";
        }
    } else {
        echo "❌ Query failed: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
