<?php
/**
 * Debug script to check editions in database
 */

require_once 'config/config.php';
require_once 'includes/database.php';

echo "<h2>Debug: Editions in Database</h2>";

try {
    // Check database connection
    $conn = Database::getInstance();
    echo "<p><strong>✅ Database connection successful</strong></p>";
    
    // Check if editions table exists
    $result = $conn->query("SHOW TABLES LIKE 'editions'");
    if ($result->rowCount() > 0) {
        echo "<p><strong>✅ Editions table exists</strong></p>";
    } else {
        echo "<p><strong>❌ Editions table does not exist</strong></p>";
        exit;
    }
    
    // Count total editions (like dashboard does)
    $result = $conn->query("SELECT COUNT(*) as count FROM editions");
    $totalCount = $result->fetch()['count'] ?? 0;
    echo "<p><strong>Total editions in database:</strong> $totalCount</p>";
    
    // Get all editions with basic info
    $result = $conn->query("SELECT id, title, status, date, created_at FROM editions ORDER BY created_at DESC");
    $editions = $result->fetchAll();
    
    echo "<h3>All Editions in Database:</h3>";
    if (empty($editions)) {
        echo "<p>No editions found in database</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Date</th><th>Created At</th></tr>";
        foreach ($editions as $edition) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($edition['id']) . "</td>";
            echo "<td>" . htmlspecialchars($edition['title']) . "</td>";
            echo "<td>" . htmlspecialchars($edition['status']) . "</td>";
            echo "<td>" . htmlspecialchars($edition['date']) . "</td>";
            echo "<td>" . htmlspecialchars($edition['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test the Edition class getAll method
    echo "<h3>Testing Edition Class getAll() method:</h3>";
    if (file_exists('classes/Edition.php')) {
        require_once 'classes/Edition.php';
        $editionClass = new Edition();
        $classEditions = $editionClass->getAll();
        
        echo "<p><strong>Editions returned by Edition::getAll():</strong> " . count($classEditions) . "</p>";
        
        if (!empty($classEditions)) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Categories</th></tr>";
            foreach ($classEditions as $edition) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($edition['id']) . "</td>";
                echo "<td>" . htmlspecialchars($edition['title']) . "</td>";
                echo "<td>" . htmlspecialchars($edition['status'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($edition['categories'] ?? 'None') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p>❌ Edition.php class file not found</p>";
    }
    
    // Check categories table
    echo "<h3>Categories Table Check:</h3>";
    $result = $conn->query("SHOW TABLES LIKE 'categories'");
    if ($result->rowCount() > 0) {
        echo "<p><strong>✅ Categories table exists</strong></p>";
        $result = $conn->query("SELECT COUNT(*) as count FROM categories");
        $catCount = $result->fetch()['count'] ?? 0;
        echo "<p><strong>Total categories:</strong> $catCount</p>";
    } else {
        echo "<p><strong>⚠️ Categories table does not exist</strong></p>";
    }
    
    // Check edition_categories table
    echo "<h3>Edition Categories Junction Table Check:</h3>";
    $result = $conn->query("SHOW TABLES LIKE 'edition_categories'");
    if ($result->rowCount() > 0) {
        echo "<p><strong>✅ Edition_categories table exists</strong></p>";
        $result = $conn->query("SELECT COUNT(*) as count FROM edition_categories");
        $junctionCount = $result->fetch()['count'] ?? 0;
        echo "<p><strong>Total edition-category relationships:</strong> $junctionCount</p>";
    } else {
        echo "<p><strong>⚠️ Edition_categories table does not exist</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}
?>
