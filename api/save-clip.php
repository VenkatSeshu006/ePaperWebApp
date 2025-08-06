<?php
/**
 * Enhanced Save Clip API
 * Handles comprehensive clip saving with image processing
 */

// Start output buffering to prevent any unwanted output
ob_start();

// Disable error display to prevent HTML errors from corrupting JSON
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_end_clean();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    ob_end_flush();
    exit;
}

// Check for required files
$requiredFiles = [
    '../includes/database.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        error_log("Required file missing: $file");
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server configuration error']);
        ob_end_flush();
        exit;
    }
}

require_once '../includes/database.php';
require_once '../classes/WatermarkProcessor.php';

try {
    // Get database connection
    $conn = getConnection();
    
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $requiredFields = ['edition_id', 'page_number', 'image_data', 'crop_data'];
    foreach ($requiredFields as $field) {
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
    
    $x = (int)$cropData['x'];
    $y = (int)$cropData['y'];
    $width = (int)$cropData['width'];
    $height = (int)$cropData['height'];
    
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
    $filename = 'clip_' . $editionId . '_' . $pageNumber . '_' . uniqid() . '.' . ($imageType === 'jpeg' ? 'jpg' : $imageType);
    $relativePath = 'uploads/clips/' . $filename;
    $filepath = '../' . $relativePath;
    
    // Ensure clips directory exists
    $clipsDir = dirname($filepath);
    if (!is_dir($clipsDir)) {
        if (!mkdir($clipsDir, 0755, true)) {
            throw new Exception('Failed to create clips directory');
        }
    }
    
    // Save image file
    if (!file_put_contents($filepath, $imageContent)) {
        throw new Exception('Failed to save image file');
    }
    
    // Apply watermark if enabled
    $watermarkProcessor = new WatermarkProcessor();
    if ($watermarkProcessor->isWatermarkEnabled()) {
        try {
            // Apply watermark to the saved clip
            $watermarkProcessor->applyWatermark($filepath, $filepath);
        } catch (Exception $e) {
            error_log("Watermark application failed: " . $e->getMessage());
            // Continue without watermark rather than failing the entire operation
        }
    }
    
    // Get client information
    $clientInfo = [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // Save to database using correct column names
    $sql = "INSERT INTO clips (edition_id, page_number, x, y, width, height, image_path, client_ip, user_agent, title, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . implode(' ', $conn->errorInfo()));
    }
    
    $title = $input['title'] ?? 'Newspaper Clip';
    $description = $input['description'] ?? "Clip from page $pageNumber";
    
    $success = $stmt->execute([
        $editionId,
        $pageNumber,
        $x,
        $y,
        $width,
        $height,
        $relativePath,
        $clientInfo['ip_address'],
        $clientInfo['user_agent'],
        $title,
        $description
    ]);
    
    if (!$success) {
        // Clean up file if database insert failed
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        throw new Exception('Failed to save clip to database: ' . implode(' ', $stmt->errorInfo()));
    }
    
    $insertedId = $conn->lastInsertId();
    
    // Generate clip URL
    $clipUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . 
               $_SERVER['HTTP_HOST'] . 
               dirname(dirname($_SERVER['PHP_SELF'])) . 
               '/view-clip.php?id=' . $insertedId;
    
    // Clear output buffer and return success response
    ob_clean();
    echo json_encode([
        'success' => true,
        'clip_id' => $insertedId,
        'clip_url' => $clipUrl,
        'image_path' => $relativePath,
        'message' => 'Clip saved successfully',
        'dimensions' => [
            'width' => $width,
            'height' => $height,
            'x' => $x,
            'y' => $y
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Clip save error: " . $e->getMessage());
    ob_clean(); // Clear any output buffer
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Final cleanup
ob_end_flush();
?>
