<?php
/**
 * Database Debug Script
 */
require_once 'includes/database.php';

try {
    $conn = getConnection();
    echo "✅ Database connection successful\n\n";
    
    // Check settings table structure
    echo "=== SETTINGS TABLE STRUCTURE ===\n";
    $result = $conn->query("DESCRIBE settings");
    if ($result) {
        while ($row = $result->fetch()) {
            echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . "\n";
        }
    } else {
        echo "❌ Error describing settings table: " . $conn->error . "\n";
    }
    
    echo "\n=== EDITIONS TABLE STRUCTURE ===\n";
    $result = $conn->query("DESCRIBE editions");
    if ($result) {
        while ($row = $result->fetch()) {
            echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . "\n";
        }
    } else {
        echo "❌ Error describing editions table: " . $conn->error . "\n";
    }
    
    echo "\n=== TEST SETTINGS QUERY ===\n";
    $result = $conn->query("SELECT setting_key, setting_value, setting_type FROM settings LIMIT 5");
    if ($result) {
        echo "✅ Settings query successful\n";
        while ($row = $result->fetch()) {
            echo $row['setting_key'] . " => " . $row['setting_value'] . "\n";
        }
    } else {
        echo "❌ Error in settings query: " . $conn->error . "\n";
    }
    
    echo "\n=== TEST EDITIONS QUERY ===\n";
    $result = $conn->query("SELECT id, title, date, thumbnail_path FROM editions ORDER BY date DESC LIMIT 1");
    if ($result) {
        echo "✅ Editions query successful\n";
        $edition = $result->fetch();
        if ($edition) {
            print_r($edition);
        } else {
            echo "No editions found\n";
        }
    } else {
        echo "❌ Error in editions query: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
?>
