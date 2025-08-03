<?php
/**
 * Database Migration Script
 * Migrate pdf_path to pdf_file and ensure proper PDF viewer display
 */

require_once 'includes/database.php';

echo "<h1>Database Migration for PDF Viewer</h1>";

try {
    $conn = getConnection();
    
    // Check if pdf_file column exists
    $result = $conn->query("SHOW COLUMNS FROM editions LIKE 'pdf_file'");
    $pdfFileExists = $result && $result->rowCount() > 0;
    
    // Check if pdf_path column exists
    $result = $conn->query("SHOW COLUMNS FROM editions LIKE 'pdf_path'");
    $pdfPathExists = $result && $result->rowCount() > 0;
    
    echo "<h2>Column Status</h2>";
    echo "pdf_file column: " . ($pdfFileExists ? "✅ Exists" : "❌ Missing") . "<br>";
    echo "pdf_path column: " . ($pdfPathExists ? "✅ Exists" : "❌ Missing") . "<br>";
    
    if (!$pdfFileExists && $pdfPathExists) {
        echo "<h2>Migration Needed</h2>";
        echo "Renaming pdf_path column to pdf_file...<br>";
        
        $alterSql = "ALTER TABLE editions CHANGE COLUMN pdf_path pdf_file VARCHAR(255)";
        $result = $conn->query($alterSql);
        
        if ($result) {
            echo "✅ Successfully renamed pdf_path to pdf_file<br>";
        } else {
            echo "❌ Failed to rename column<br>";
        }
    } else if ($pdfFileExists && $pdfPathExists) {
        echo "<h2>Both Columns Exist</h2>";
        echo "Migrating data from pdf_path to pdf_file...<br>";
        
        // Copy data from pdf_path to pdf_file where pdf_file is empty
        $migrateSql = "UPDATE editions SET pdf_file = pdf_path WHERE (pdf_file IS NULL OR pdf_file = '') AND pdf_path IS NOT NULL AND pdf_path != ''";
        $result = $conn->query($migrateSql);
        
        if ($result) {
            $affected = $result->rowCount();
            echo "✅ Migrated $affected records from pdf_path to pdf_file<br>";
            
            // Now drop the old pdf_path column
            echo "Dropping old pdf_path column...<br>";
            $dropSql = "ALTER TABLE editions DROP COLUMN pdf_path";
            $dropResult = $conn->query($dropSql);
            
            if ($dropResult) {
                echo "✅ Successfully dropped pdf_path column<br>";
            } else {
                echo "❌ Failed to drop pdf_path column<br>";
            }
        } else {
            echo "❌ Failed to migrate data<br>";
        }
    } else if ($pdfFileExists && !$pdfPathExists) {
        echo "<h2>Migration Complete</h2>";
        echo "✅ Database already using pdf_file column correctly<br>";
    } else {
        echo "<h2>Database Issues</h2>";
        echo "❌ Neither pdf_file nor pdf_path columns found. This suggests database structure issues.<br>";
    }
    
    // Check for missing columns and add them if needed
    echo "<h2>Checking Additional Columns</h2>";
    
    $columnsToCheck = [
        'slug' => "VARCHAR(200) UNIQUE",
        'cover_image' => "VARCHAR(255)",
        'total_pages' => "INT DEFAULT 0",
        'file_size' => "BIGINT DEFAULT 0",
        'featured' => "BOOLEAN DEFAULT FALSE",
        'views' => "INT DEFAULT 0",
        'downloads' => "INT DEFAULT 0"
    ];
    
    foreach ($columnsToCheck as $column => $definition) {
        $result = $conn->query("SHOW COLUMNS FROM editions LIKE '$column'");
        $exists = $result && $result->rowCount() > 0;
        
        if (!$exists) {
            echo "Adding missing column: $column...<br>";
            $addSql = "ALTER TABLE editions ADD COLUMN $column $definition";
            $addResult = $conn->query($addSql);
            
            if ($addResult) {
                echo "✅ Added $column column<br>";
            } else {
                echo "❌ Failed to add $column column<br>";
            }
        } else {
            echo "✅ Column $column already exists<br>";
        }
    }
    
    // Generate slugs for existing records without slugs
    echo "<h2>Generating Missing Slugs</h2>";
    $result = $conn->query("SELECT id, title FROM editions WHERE slug IS NULL OR slug = ''");
    if ($result) {
        $updated = 0;
        while ($row = $result->fetch()) {
            $title = $row['title'];
            $id = $row['id'];
            
            // Generate slug
            $slug = strtolower(trim($title));
            $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
            
            // Ensure uniqueness
            $originalSlug = $slug;
            $counter = 1;
            while (true) {
                $checkSql = "SELECT COUNT(*) as count FROM editions WHERE slug = ? AND id != ?";
                $checkResult = $conn->prepare($checkSql);
                $checkResult->execute([$slug, $id]);
                $checkData = $checkResult->fetch();
                
                if ($checkData['count'] == 0) {
                    break;
                }
                
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            // Update the record
            $updateSql = "UPDATE editions SET slug = ? WHERE id = ?";
            $updateResult = $conn->prepare($updateSql);
            if ($updateResult->execute([$slug, $id])) {
                $updated++;
                echo "Generated slug '$slug' for edition: $title<br>";
            }
        }
        echo "✅ Generated $updated slugs<br>";
    }
    
    echo "<h2>Migration Complete!</h2>";
    echo "You can now test your PDF viewer at: <a href='index.php'>index.php</a><br>";
    echo "Upload new editions at: <a href='admin/upload.php'>admin/upload.php</a><br>";
    
} catch (Exception $e) {
    echo "❌ Migration Error: " . $e->getMessage();
}
?>
