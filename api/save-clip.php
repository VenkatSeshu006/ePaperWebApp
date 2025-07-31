<?php
/**
 * Save Clip API
 * Handles saving newspaper clips with metadata
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check for required files
$requiredFiles = [
    '../includes/database.php',
    '../classes/Clip.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        error_log("Required file missing: $file");
        http_response_code(500);
        echo json_encode(['error' => 'Server configuration error']);
        exit;
    }
}

require_once '../includes/database.php';
require_once '../classes/Clip.php';

try {
    // Initialize models
    $clipModel = new Clip();
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    $required = ['edition_id', 'page_number', 'image_data', 'crop_data'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $editionId = filter_var($input['edition_id'], FILTER_VALIDATE_INT);
    $pageNumber = filter_var($input['page_number'], FILTER_VALIDATE_INT);
    $imageData = $input['image_data'];
    $cropData = $input['crop_data'];
    
    if (!$editionId || !$pageNumber) {
        throw new Exception('Invalid edition ID or page number');
    }
    
    // Validate crop data
    $requiredCropFields = ['x', 'y', 'width', 'height'];
    foreach ($requiredCropFields as $field) {
        if (!isset($cropData[$field]) || !is_numeric($cropData[$field])) {
            throw new Exception("Invalid crop data: $field");
        }
    }
    
    // Process base64 image data
    if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,(.+)$/', $imageData, $matches)) {
        throw new Exception('Invalid image data format');
    }
    
    $imageType = $matches[1];
    $imageContent = base64_decode($matches[2]);
    
    if (!$imageContent) {
        throw new Exception('Failed to decode image data');
    }
    
    // Generate unique filename
    $filename = 'clip_' . $editionId . '_' . uniqid() . '.' . ($imageType === 'jpeg' ? 'jpg' : $imageType);
    $clipPath = '../uploads/clips/' . $filename;
    
    // Ensure clips directory exists
    $clipsDir = dirname($clipPath);
    if (!is_dir($clipsDir)) {
        if (!mkdir($clipsDir, 0755, true)) {
            throw new Exception('Failed to create clips directory');
        }
    }
    
    // Save image file
    if (!file_put_contents($clipPath, $imageContent)) {
        throw new Exception('Failed to save image file');
    }
    
    // Note: Image optimization can be added later if needed
    // For now, we save the clip as-is
    
    // Save clip metadata to database
    $clipData = [
        'edition_id' => $editionId,
        'page_number' => $pageNumber,
        'title' => $input['title'] ?? 'Untitled Clip',
        'description' => $input['description'] ?? '',
        'category' => $input['category'] ?? 'general',
        'image_path' => 'uploads/clips/' . $filename,
        'crop_x' => (int)$cropData['x'],
        'crop_y' => (int)$cropData['y'],
        'crop_width' => (int)$cropData['width'],
        'crop_height' => (int)$cropData['height'],
        'user_id' => $_SESSION['user_id'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $clipId = $clipModel->create($clipData);
    
    if (!$clipId) {
        // Clean up file if database save failed
        if (file_exists($clipPath)) {
            unlink($clipPath);
        }
        throw new Exception('Failed to save clip to database');
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'clip_id' => $clipId,
        'message' => 'Clip saved successfully',
        'clip_url' => 'uploads/clips/' . $filename
    ]);
    
} catch (Exception $e) {
    error_log("Save clip error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
