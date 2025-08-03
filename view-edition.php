<?php
/**
 * View Edition
 * Redirects to main viewer with edition ID
 */

// Get edition ID from URL
$editionId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($editionId) {
    // Redirect to main index with edition ID
    header("Location: index.php?id=$editionId");
    exit;
} else {
    // No ID provided, redirect to home page (latest edition)
    header("Location: index.php");
    exit;
}
?>
