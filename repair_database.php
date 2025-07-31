<?php
/**
 * Database Repair Script
 * Fixes missing columns and data issues
 */

// Database configuration
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "epaper_cms";

try {
    $conn = new mysqli($host, $user, $pass, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>Database Repair Script</h2>\n";
    
    // Check and fix editions table
    echo "<h3>Checking editions table...</h3>\n";
    
    // Check if total_pages column exists
    $result = $conn->query("SHOW COLUMNS FROM editions LIKE 'total_pages'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE editions ADD COLUMN total_pages INT DEFAULT 0 AFTER pdf_path");
        echo "<p>✓ Added missing 'total_pages' column to editions table</p>\n";
    } else {
        echo "<p>✓ 'total_pages' column exists</p>\n";
    }
    
    // Check if other important columns exist
    $important_columns = [
        'views' => 'INT DEFAULT 0',
        'downloads' => 'INT DEFAULT 0',
        'thumbnail_path' => 'VARCHAR(500)',
        'pdf_path' => 'VARCHAR(500)',
        'file_size' => 'BIGINT DEFAULT 0'
    ];
    
    foreach ($important_columns as $column => $definition) {
        $result = $conn->query("SHOW COLUMNS FROM editions LIKE '$column'");
        if ($result->num_rows == 0) {
            $conn->query("ALTER TABLE editions ADD COLUMN $column $definition");
            echo "<p>✓ Added missing '$column' column to editions table</p>\n";
        }
    }
    
    // Check and fix clips table
    echo "<h3>Checking clips table...</h3>\n";
    
    // Check if page_id column exists (might be missing)
    $result = $conn->query("SHOW COLUMNS FROM clips LIKE 'page_id'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE clips ADD COLUMN page_id INT AFTER edition_id");
        echo "<p>✓ Added missing 'page_id' column to clips table</p>\n";
    }
    
    // Make sure image_path column exists (not file_path)
    $result = $conn->query("SHOW COLUMNS FROM clips LIKE 'image_path'");
    if ($result->num_rows == 0) {
        // Check if file_path exists and rename it
        $result2 = $conn->query("SHOW COLUMNS FROM clips LIKE 'file_path'");
        if ($result2->num_rows > 0) {
            $conn->query("ALTER TABLE clips CHANGE file_path image_path VARCHAR(500) NOT NULL");
            echo "<p>✓ Renamed 'file_path' to 'image_path' in clips table</p>\n";
        } else {
            $conn->query("ALTER TABLE clips ADD COLUMN image_path VARCHAR(500) NOT NULL");
            echo "<p>✓ Added missing 'image_path' column to clips table</p>\n";
        }
    }
    
    // Update total_pages for existing editions based on actual page count
    echo "<h3>Updating edition statistics...</h3>\n";
    
    $result = $conn->query("
        UPDATE editions e 
        SET total_pages = (
            SELECT COUNT(*) 
            FROM pages p 
            WHERE p.edition_id = e.id
        )
        WHERE EXISTS (SELECT 1 FROM pages p WHERE p.edition_id = e.id)
    ");
    
    if ($result) {
        echo "<p>✓ Updated total_pages for all editions</p>\n";
    }
    
    // Check if sample data exists, if not create it
    echo "<h3>Checking sample data...</h3>\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM editions WHERE id = 1");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Insert sample edition
        $conn->query("
            INSERT INTO editions (id, title, slug, description, date, status, featured, total_pages) 
            VALUES (1, 'Sample E-Paper Edition', 'sample-edition-2025-07-27', 'This is a sample digital newspaper edition for testing purposes.', '2025-07-27', 'published', 1, 12)
        ");
        echo "<p>✓ Sample edition created</p>\n";
        
        // Insert sample pages
        for ($i = 1; $i <= 12; $i++) {
            $page_num = str_pad($i, 3, '0', STR_PAD_LEFT);
            $conn->query("
                INSERT IGNORE INTO pages (edition_id, page_number, image_path, thumbnail_path, width, height, file_size) 
                VALUES (1, $i, 'uploads/2025-07-27/pages/page_$page_num.png', 'uploads/2025-07-27/pages/thumb_$page_num.png', 800, 1200, 150000)
            ");
        }
        echo "<p>✓ Sample pages created</p>\n";
    } else {
        echo "<p>✓ Sample data already exists</p>\n";
    }
    
    // Verify all tables and their structure
    echo "<h3>Database Structure Verification:</h3>\n";
    
    $tables = ['editions', 'pages', 'clips', 'settings', 'categories'];
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p>✓ Table '$table': {$row['count']} records</p>\n";
        }
    }
    
    echo "<hr>\n";
    echo "<h3 style='color: green;'>✅ Database repair completed successfully!</h3>\n";
    echo "<p><a href='index.php'>Go to Homepage</a> | <a href='test_db_connection.php'>Test Connection</a></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
