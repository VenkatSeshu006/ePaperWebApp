<?php
/**
 * Admin Authentication Header
 * Include this at the top of admin files
 */

// Suppress warnings for cleaner admin display
error_reporting(E_ERROR | E_PARSE);

session_start();
define('ADMIN_PAGE', true);

// Include configuration
require_once '../config/config.php';
require_once '../includes/database.php';

// Simple authentication check
$isAuthenticated = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: dashboard.php');
    exit;
}

// Redirect to dashboard if not authenticated
if (!$isAuthenticated) {
    header('Location: dashboard.php');
    exit;
}

// Get database connection
try {
    $conn = getConnection();
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    $conn = null;
}
?>
