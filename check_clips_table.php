<?php
// Check clips table structure
try {
    require_once 'includes/database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Checking clips table structure...\n";
    $result = $conn->query('DESCRIBE clips');
    if ($result) {
        echo "Clips table columns:\n";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']} ({$row['Type']})\n";
        }
    } else {
        echo "Clips table not found or error occurred\n";
    }
    
    echo "\nChecking if clips table exists...\n";
    $tables = $conn->query("SHOW TABLES LIKE 'clips'")->fetchAll();
    if (empty($tables)) {
        echo "❌ Clips table does not exist!\n";
        
        echo "\nAvailable tables:\n";
        $allTables = $conn->query("SHOW TABLES")->fetchAll();
        foreach ($allTables as $table) {
            echo "- " . array_values($table)[0] . "\n";
        }
    } else {
        echo "✅ Clips table exists\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
