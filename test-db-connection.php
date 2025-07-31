<?php
require_once 'includes/database.php';

echo "Database connection test:\n";
try {
    $conn = getConnection();
    echo "SUCCESS - Connected to database\n";
    echo "Connection type: " . get_class($conn) . "\n";
} catch (Exception $e) {
    echo "FAILED - " . $e->getMessage() . "\n";
}
?>
