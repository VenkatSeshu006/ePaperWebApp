<?php
/**
 * Fix Clips Table Structure
 */
require_once 'includes/database.php';

try {
    $conn = getConnection();
    echo "Database connection successful\n";
    
    // Check clips table structure
    echo "\n=== CURRENT CLIPS TABLE STRUCTURE ===\n";
    $result = $conn->query("DESCRIBE clips");
    if ($result) {
        while ($row = $result->fetch()) {
            echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . "\n";
        }
    } else {
        echo "❌ Clips table doesn't exist or error: " . $conn->error . "\n";
    }
    
    // Check if image_id column exists
    $result = $conn->query("SHOW COLUMNS FROM clips LIKE 'image_id'");
    if ($result && $result->num_rows > 0) {
        echo "\n✅ image_id column already exists\n";
    } else {
        echo "\n📝 Adding image_id column to clips table...\n";
        $conn->query("ALTER TABLE clips ADD COLUMN image_id INT DEFAULT 1 AFTER edition_id");
        
        if ($conn->error) {
            echo "❌ Error adding column: " . $conn->error . "\n";
        } else {
            echo "✅ image_id column added successfully\n";
        }
    }
    
    // Test the insert query that was failing
    echo "\n=== TESTING CLIP INSERT QUERY ===\n";
    $testSql = "INSERT INTO clips (edition_id, image_id, title, description, file_path, created_at) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($testSql);
    
    if ($stmt) {
        echo "✅ Prepared statement created successfully\n";
        echo "Query: $testSql\n";
        
        // Test with dummy data (don't actually insert)
        $edition_id = 1;
        $image_id = 1;
        $title = "Test Clip";
        $description = "Test Description";
        $file_path = "test/path.jpg";
        $created_at = date('Y-m-d H:i:s');
        
        echo "Parameters: edition_id=$edition_id, image_id=$image_id, title=$title\n";
        echo "✅ Query structure is correct\n";
    } else {
        echo "❌ Error preparing statement: " . $conn->error . "\n";
    }
    
    echo "\n=== FINAL CLIPS TABLE STRUCTURE ===\n";
    $result = $conn->query("DESCRIBE clips");
    if ($result) {
        while ($row = $result->fetch()) {
            echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
