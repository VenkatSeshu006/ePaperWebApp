<?php
/**
 * Enhanced Save Clip API
 * Handles comprehensive clip saving with image processing
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check for required files
$requiredFiles = [
    '../includes/database.php'
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

try {
    // Get database connection
    $conn = getConnection();
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    $required = ['edition_id', 'page_number', 'x', 'y', 'width', 'height', 'image_data'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Validate data types and ranges
    $editionId = (int)$input['edition_id'];
    $pageNumber = (int)$input['page_number'];
    $x = (int)$input['x'];
    $y = (int)$input['y'];
    $width = (int)$input['width'];
    $height = (int)$input['height'];
    $imageData = $input['image_data'];
    
    if ($editionId <= 0 || $pageNumber <= 0 || $width <= 0 || $height <= 0) {
        throw new Exception('Invalid clip dimensions or IDs');
    }
    
    // Validate image data format
    if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,(.+)$/', $imageData, $matches)) {
        throw new Exception('Invalid image data format');
    }
    
    $imageFormat = $matches[1];
    $base64Data = $matches[2];
    
    // Decode base64 image
    $imageContent = base64_decode($base64Data);
    if ($imageContent === false) {
        throw new Exception('Failed to decode image data');
    }
    
    // Create uploads directory structure
    $uploadsDir = '../uploads/clips';
    $yearMonth = date('Y-m');
    $clipDir = "$uploadsDir/$yearMonth";
    
    if (!is_dir($clipDir)) {
        if (!mkdir($clipDir, 0755, true)) {
            throw new Exception('Failed to create clips directory');
        }
    }
    
    // Generate unique filename
    $clipId = uniqid('clip_', true);
    $filename = $clipId . '.jpg';
    $filepath = "$clipDir/$filename";
    $relativePath = "uploads/clips/$yearMonth/$filename";
    
    // Save image file
    if (!file_put_contents($filepath, $imageContent)) {
        throw new Exception('Failed to save image file');
    }
    
    // Get client information
    $clientInfo = [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'referer' => $_SERVER['HTTP_REFERER'] ?? ''
    ];
    
    // Insert clip record into database
    $sql = "INSERT INTO clips (
        edition_id, 
        page_number, 
        x, 
        y, 
        width, 
        height, 
        image_path, 
        client_ip, 
        user_agent, 
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $success = $stmt->execute([
        $editionId,
        $pageNumber,
        $x,
        $y,
        $width,
        $height,
        $relativePath,
        $clientInfo['ip_address'],
        $clientInfo['user_agent']
    ]);
    
    if (!$success) {
        // Clean up file if database insert failed
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        throw new Exception('Failed to save clip to database');
    }
    
    $insertedId = $conn->lastInsertId();
    
    // Generate clip URL
    $clipUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . 
               $_SERVER['HTTP_HOST'] . 
               dirname(dirname($_SERVER['PHP_SELF'])) . 
               '/view-clip.php?id=' . $insertedId;
    
    // Return success response
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
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
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
        'image_path' => 'uploads/clips/' . $filename,
        'x' => (int)$cropData['x'],
        'y' => (int)$cropData['y'],
        'width' => (int)$cropData['width'],
        'height' => (int)$cropData['height']
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
