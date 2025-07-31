<?php
/**
 * Simple Database Connection
 * Fallback connection for compatibility
 */

// Simple database connection variables
$host = "localhost";
$user = "root";
$pass = "";
$db = "epaper_cms";

// Initialize connection variables
$conn = null;
$db_error = false;
$db_error_message = "";

// Try to establish MySQLi connection with error handling
try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    $db_error = true;
    $db_error_message = $e->getMessage();
    
    // Log error if possible
    error_log("Database connection error: " . $e->getMessage());
}

// Function to get connection (for compatibility)
function getConnection() {
    global $conn;
    return $conn;
}

// Function to check if database is connected
function isDatabaseConnected() {
    global $conn, $db_error;
    return !$db_error && $conn && !$conn->connect_error;
}

// Function to get database error
function getDatabaseError() {
    global $db_error_message;
    return $db_error_message;
}
?>
