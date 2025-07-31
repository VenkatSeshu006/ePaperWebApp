<?php
/**
 * Save Clip Functionality
 * Handles saving cropped images as clips
 */

session_start();
require_once 'includes/database.php';

// Get database connection
$conn = getConnection();

header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Check if required data is present
    if (!isset($_FILES['image']) || !isset($_POST['edition_id'])) {
        throw new Exception('Missing required data');
    }

    $editionId = (int)$_POST['edition_id'];
    $imageId = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 1;
    $uploadedFile = $_FILES['image'];

    // Validate file upload
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed');
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPEG and PNG are allowed.');
    }

    // Create clips directory if it doesn't exist
    $clipsDir = 'uploads/clips/';
    if (!is_dir($clipsDir)) {
        if (!mkdir($clipsDir, 0755, true)) {
            throw new Exception('Failed to create clips directory');
        }
    }

    // Generate unique filename
    $extension = ($mimeType === 'image/png') ? 'png' : 'jpg';
    $filename = 'clip_' . $editionId . '_' . uniqid() . '.' . $extension;
    $filePath = $clipsDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
        throw new Exception('Failed to save file');
    }

    // Save clip information to database
    $title = "Clip from Edition " . $editionId;
    $description = "Clipped image from digital newspaper";
    $created_at = date('Y-m-d H:i:s');
    $page_number = $imageId; // Use image_id as page number

    // Check if clips table exists, if not create it with correct structure
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS clips (
            id INT AUTO_INCREMENT PRIMARY KEY,
            edition_id INT NOT NULL,
            image_id INT DEFAULT 1,
            page_number INT NOT NULL DEFAULT 1,
            x INT NOT NULL DEFAULT 0,
            y INT NOT NULL DEFAULT 0,
            width INT NOT NULL DEFAULT 100,
            height INT NOT NULL DEFAULT 100,
            image_path VARCHAR(500),
            title VARCHAR(255),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_edition_id (edition_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $conn->query($createTableSql);

    // Insert clip record with correct column names
    $stmt = $conn->prepare("
        INSERT INTO clips (edition_id, image_id, page_number, x, y, width, height, image_path, title, description, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Set default values for crop coordinates (these would normally come from the cropper)
    $x = 0;
    $y = 0;
    $width = 200;
    $height = 200;
    
    $stmt->bind_param("iiiiiiissss", $editionId, $imageId, $page_number, $x, $y, $width, $height, $filePath, $title, $description, $created_at);
    
    if (!$stmt->execute()) {
        // If database insert fails, clean up the file
        unlink($filePath);
        throw new Exception('Failed to save clip information');
    }

    $clipId = $conn->insert_id;

    // Return success response
    echo json_encode([
        'success' => true,
        'clip_id' => $clipId,
        'clip_path' => $filePath,
        'image_path' => $filePath, // For backward compatibility
        'message' => 'Clip saved successfully'
    ]);

} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
