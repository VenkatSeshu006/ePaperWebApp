<?php
require_once 'includes/database.php';

try {
    $conn = getConnection();
    
    echo "Edition Pages Table Structure:\n";
    echo "================================\n";
    
    $result = $conn->query('DESCRIBE edition_pages');
    if ($result) {
        while ($row = $result->fetch()) {
            echo $row['Field'] . ' - ' . $row['Type'] . "\n";
        }
    } else {
        echo "Table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
