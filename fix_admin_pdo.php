<?php
/**
 * Comprehensive PDO Syntax Fix Script
 * Fixes all remaining PDO/MySQLi compatibility issues
 */

echo "<h2>Fixing PDO Syntax Issues</h2>\n";

$files_to_fix = [
    'admin/test-database_new.php',
    'admin/setup-database_new.php'
];

$fixes = [
    // PDO attribute fixes
    '$conn->server_info' => '$conn->getAttribute(PDO::ATTR_SERVER_VERSION)',
    '$conn->client_info' => '$conn->getAttribute(PDO::ATTR_CLIENT_VERSION)',
    '$conn->host_info' => '$conn->getAttribute(PDO::ATTR_CONNECTION_STATUS)',
    '$conn->protocol_version' => '$conn->getAttribute(PDO::ATTR_SERVER_VERSION)',
    '$conn->character_set_name()' => '"utf8mb4"',
    
    // Transaction fixes
    '$conn->autocommit(false)' => '$conn->beginTransaction()',
    '$conn->autocommit(true)' => '// Transaction committed or rolled back',
    
    // Error handling fixes
    '$conn->error' => '$conn->errorInfo()[2]',
    '$conn->insert_id' => '$conn->lastInsertId()',
    
    // Result handling fixes
    '$result->num_rows' => '$result->rowCount()',
    '$result->fetch_assoc()' => '$result->fetch(PDO::FETCH_ASSOC)',
    '$result->fetch_row()' => '$result->fetch(PDO::FETCH_NUM)',
    '$result->fetch()' => '$result->fetch(PDO::FETCH_ASSOC)',
    
    // Statement binding fixes
    'bind_param(' => '// Converted to execute() with array - bind_param(',
    '->get_result()' => '// PDO statements return results directly',
];

foreach ($files_to_fix as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "<h3>Fixing: $file</h3>\n";
        
        $content = file_get_contents($fullPath);
        $original = $content;
        
        foreach ($fixes as $old => $new) {
            $count = 0;
            $content = str_replace($old, $new, $content, $count);
            if ($count > 0) {
                echo "- Fixed $count instances of: $old<br>\n";
            }
        }
        
        if ($content !== $original) {
            file_put_contents($fullPath, $content);
            echo "<span style='color: green;'>✓ File updated successfully!</span><br>\n";
        } else {
            echo "<span style='color: blue;'>ℹ No changes needed</span><br>\n";
        }
    } else {
        echo "<span style='color: red;'>✗ File not found: $file</span><br>\n";
    }
}

echo "<h3>Manual fixes still needed:</h3>\n";
echo "<ul>\n";
echo "<li>Replace bind_param() calls with execute([]) arrays</li>\n";
echo "<li>Update fetch() calls to specify PDO::FETCH_ASSOC</li>\n";
echo "<li>Replace get_result() calls with direct statement usage</li>\n";
echo "</ul>\n";

echo "<h3>Testing database connection after fixes...</h3>\n";
try {
    require_once 'includes/database.php';
    $conn = getConnection();
    if ($conn) {
        echo "<p style='color: green;'>✓ Database connection working!</p>\n";
        
        // Test a simple query
        $result = $conn->query("SELECT COUNT(*) as count FROM editions");
        if ($result) {
            $row = $result->fetch(PDO::FETCH_ASSOC);
            echo "<p>Found {$row['count']} editions in database</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>\n";
}

echo "<p><strong>Fix completed! Check admin panel functionality.</strong></p>\n";
?>
