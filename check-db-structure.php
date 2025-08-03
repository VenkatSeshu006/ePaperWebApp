<?php
include 'config/config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Checking editions table structure:\n";
    $result = $conn->query('DESCRIBE editions');
    
    if ($result) {
        while ($row = $result->fetch()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
    
    $conn = null;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
