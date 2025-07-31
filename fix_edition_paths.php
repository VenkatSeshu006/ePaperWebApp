<?php
/**
 * Fix Edition Date and File Paths
 * Updates the database to match actual file locations
 */

require_once 'includes/db.php';

$conn = getConnection();

if (!$conn) {
    die("Database connection failed");
}

echo "<h2>Fixing Edition Date and File Paths...</h2>";

// Update the edition date to match the actual files
$currentDate = '2025-07-29'; // Today's date where files actually exist

$stmt = $conn->prepare("UPDATE editions SET date = ? WHERE id = 1");
$stmt->bind_param('s', $currentDate);

if ($stmt->execute()) {
    echo "<p>✅ Updated edition date to $currentDate</p>";
} else {
    echo "<p>❌ Failed to update edition date</p>";
}

// Update page paths to point to correct directory
$stmt = $conn->prepare("UPDATE pages SET image_path = REPLACE(image_path, '2025-07-27', '2025-07-29'), thumbnail_path = REPLACE(thumbnail_path, '2025-07-27', '2025-07-29') WHERE edition_id = 1");

if ($stmt->execute()) {
    echo "<p>✅ Updated page file paths</p>";
} else {
    echo "<p>❌ Failed to update page file paths</p>";
}

// Verify the changes
$result = $conn->query("SELECT id, title, date FROM editions WHERE id = 1");
if ($result && $result->num_rows > 0) {
    $edition = $result->fetch_assoc();
    echo "<p>✅ Edition verification:</p>";
    echo "<ul>";
    echo "<li>ID: {$edition['id']}</li>";
    echo "<li>Title: {$edition['title']}</li>";
    echo "<li>Date: {$edition['date']}</li>";
    echo "</ul>";
}

// Check page paths
$result = $conn->query("SELECT COUNT(*) as count, MIN(image_path) as sample_path FROM pages WHERE edition_id = 1");
if ($result && $result->num_rows > 0) {
    $pages = $result->fetch_assoc();
    echo "<p>✅ Pages verification:</p>";
    echo "<ul>";
    echo "<li>Total pages: {$pages['count']}</li>";
    echo "<li>Sample path: {$pages['sample_path']}</li>";
    echo "</ul>";
}

// Check if files actually exist
$samplePath = "uploads/2025-07-29/pages/page_001.png";
if (file_exists($samplePath)) {
    echo "<p>✅ Sample file exists: $samplePath</p>";
} else {
    echo "<p>❌ Sample file missing: $samplePath</p>";
}

echo "<hr>";
echo "<h3>✅ Database update completed!</h3>";
echo "<p><a href='index.php' class='btn btn-primary'>Test Homepage</a></p>";
?>
