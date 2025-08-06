<?php
/**
 * Database Connection Compatibility Layer
 * Redirects to main database.php for consistency
 */

require_once __DIR__ . "/database.php";

// Provide MySQLi compatibility if needed
if (!isset($conn) && function_exists("getConnection")) {
    try {
        $pdoConn = getConnection();
        // Note: This provides PDO connection, not MySQLi
        // Update your code to use PDO instead of MySQLi
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
    }
}
?>