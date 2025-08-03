<?php
/**
 * Quick test for admin edition functionality
 */
require_once 'config/config.php';
require_once 'includes/database.php';
require_once 'classes/Edition.php';

$edition = new Edition();

// Create a test edition
$testData = [
    'title' => 'Admin Test Edition',
    'description' => 'Testing admin functionality',
    'publication_date' => date('Y-m-d'),
    'pdf_path' => 'test/admin.pdf',
    'status' => 'draft'
];

$id = $edition->create($testData);
echo "Created edition with ID: $id\n";

// Test getByIdAdmin
$retrieved = $edition->getByIdAdmin($id);
if ($retrieved) {
    echo "✅ getByIdAdmin works: " . $retrieved['title'] . " (Status: " . $retrieved['status'] . ")\n";
} else {
    echo "❌ getByIdAdmin failed\n";
}

// Test publish
$updated = $edition->update($id, ['status' => 'published']);
if ($updated) {
    echo "✅ Update works\n";
} else {
    echo "❌ Update failed\n";
}

// Clean up
$db = Database::getInstance();
$conn = $db->getConnection();
$stmt = $conn->prepare("DELETE FROM editions WHERE id = ?");
$stmt->execute([$id]);
echo "Cleaned up test data\n";
echo "✅ All admin functionality working!\n";
?>
