<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== MINIMAL INDEX TEST ===\n";

try {
    echo "1. Starting session...\n";
    session_start();
    
    echo "2. Loading database...\n";
    require_once 'includes/database.php';
    
    echo "3. Getting connection...\n";
    $conn = getConnection();
    
    echo "4. Testing settings query...\n";
    $settingsResult = $conn->query("SELECT setting_key, setting_value FROM settings LIMIT 1");
    
    if ($settingsResult) {
        echo "✅ Settings query successful\n";
    } else {
        echo "❌ Settings query failed: " . $conn->error . "\n";
    }
    
    echo "5. Testing editions query...\n";
    $sql = "SELECT id, title, date, thumbnail_path FROM editions ORDER BY date DESC LIMIT 1";
    echo "Query: $sql\n";
    $result = $conn->query($sql);
    
    if ($result) {
        echo "✅ Editions query successful\n";
        $edition = $result->fetch();
        if ($edition) {
            echo "Found edition: " . $edition['title'] . "\n";
        }
    } else {
        echo "❌ Editions query failed: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
?>
