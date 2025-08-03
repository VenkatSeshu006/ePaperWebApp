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
    $page_number = $imageId; // Use image_id as page number

    // Insert clip record with correct column names
    $stmt = $conn->prepare("
        INSERT INTO clips (edition_id, image_id, page_number, x, y, width, height, image_path, title, description) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Set default values for crop coordinates (these would normally come from the cropper)
    $x = 0;
    $y = 0;
    $width = 200;
    $height = 200;
    
    $success = $stmt->execute([$editionId, $imageId, $page_number, $x, $y, $width, $height, $filePath, $title, $description]);
    
    if (!$success) {
        // If database insert fails, clean up the file
        unlink($filePath);
        throw new Exception('Failed to save clip information');
    }

    $clipId = $conn->lastInsertId();

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
