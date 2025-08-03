<?php
require_once '../includes/database.php';

try {
    $conn = getConnection();
    $result = $conn->query('DESCRIBE editions');
    
    echo "Current editions table structure:\n";
    echo str_repeat("-", 50) . "\n";
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-20s %s\n", $row['Field'], $row['Type']);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
