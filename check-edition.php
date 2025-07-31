<?php
require_once 'includes/database.php';

$conn = getConnection();
$today = '2025-07-29';

echo "Checking for edition entry for $today...\n";

$result = $conn->query("SELECT * FROM editions WHERE date = '$today'");

if ($result && $result->num_rows > 0) {
    $edition = $result->fetch_assoc();
    echo "✓ Found existing edition: " . $edition['title'] . "\n";
    echo "  ID: " . $edition['id'] . "\n";
    echo "  Status: " . $edition['status'] . "\n";
} else {
    echo "! No edition entry found for $today\n";
    echo "Creating new edition entry...\n";
    
    $title = "Digital E-Paper - " . date('F j, Y', strtotime($today));
    $description = "Daily digital newspaper edition";
    
    $stmt = $conn->prepare("
        INSERT INTO editions (title, date, description, status, created_at) 
        VALUES (?, ?, ?, 'published', NOW())
    ");
    
    $stmt->bind_param("sss", $title, $today, $description);
    
    if ($stmt->execute()) {
        $editionId = $conn->insert_id;
        echo "✓ Created new edition with ID: $editionId\n";
        echo "  Title: $title\n";
        echo "  Date: $today\n";
        echo "  Status: Published\n";
    } else {
        echo "Error creating edition: " . $stmt->error . "\n";
    }
}

echo "\nEdition is ready! You can view it at:\n";
echo "http://localhost/epaper-site/\n";
?>
