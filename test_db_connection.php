<?php
// Test database connection and tables
require_once 'includes/database.php';

echo "<h2>Database Connection Test</h2>\n";

try {
    $conn = getConnection();
    if ($conn) {
        echo "<p style='color: green;'>✓ Database connection successful!</p>\n";
        
        // Get server version using PDO
        $serverVersion = $conn->getAttribute(PDO::ATTR_SERVER_VERSION);
        echo "<p>Database: MySQL " . $serverVersion . "</p>\n";
        
        // Check if database exists
        $result = $conn->query("SELECT DATABASE() as current_db");
        if ($result) {
            $row = $result->fetch(PDO::FETCH_ASSOC);
            echo "<p>Current database: " . $row['current_db'] . "</p>\n";
        }
        
        // Check tables
        echo "<h3>Available Tables:</h3>\n";
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            $tables = [];
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            if (empty($tables)) {
                echo "<p style='color: red;'>❌ No tables found in database!</p>\n";
            } else {
                echo "<ul>\n";
                foreach ($tables as $table) {
                    echo "<li>$table</li>\n";
                }
                echo "</ul>\n";
            }
        }
        
        // Check specific tables needed for the app
        $required_tables = ['editions', 'clips', 'settings'];
        echo "<h3>Required Tables Check:</h3>\n";
        foreach ($required_tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->rowCount() > 0) {
                echo "<p style='color: green;'>✓ Table '$table' exists</p>\n";
                
                // Show table structure
                $desc_result = $conn->query("DESCRIBE $table");
                if ($desc_result) {
                    echo "<details><summary>Show structure</summary><pre>\n";
                    while ($col = $desc_result->fetch(PDO::FETCH_ASSOC)) {
                        echo $col['Field'] . " | " . $col['Type'] . " | " . $col['Null'] . " | " . $col['Key'] . "\n";
                    }
                    echo "</pre></details>\n";
                }
            } else {
                echo "<p style='color: red;'>❌ Table '$table' missing!</p>\n";
            }
        }
        
    } else {
        echo "<p style='color: red;'>❌ Database connection failed!</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
