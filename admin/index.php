<?php
/**
 * Admin Panel Index - Redirect to Dashboard
 * Make dashboard the default admin page
 */

session_start();

// Always redirect to dashboard (which handles authentication)
header('Location: dashboard.php');
exit;
?>
