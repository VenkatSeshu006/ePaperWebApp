<?php
/**
 * Download Clip API with Watermark Support
 * Provides watermarked clips for download
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../includes/database.php';
require_once '../classes/WatermarkProcessor.php';

try {
    // Get clip ID from request
    $clipId = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $clipId = $_GET['id'] ?? null;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $clipId = $input['clip_id'] ?? null;
    }
    
    if (!$clipId) {
        throw new Exception('Clip ID is required');
    }
    
    $clipId = filter_var($clipId, FILTER_VALIDATE_INT);
    if (!$clipId) {
        throw new Exception('Invalid clip ID');
    }
    
    // Get clip details from database
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM clips WHERE id = ?");
    $stmt->execute([$clipId]);
    $clip = $stmt->fetch();
    
    if (!$clip) {
        throw new Exception('Clip not found');
    }
    
    // Check if original file exists
    $originalPath = '../' . $clip['image_path'];
    if (!file_exists($originalPath)) {
        throw new Exception('Clip file not found');
    }
    
    // Initialize watermark processor
    $watermarkProcessor = new WatermarkProcessor();
    
    // Generate watermarked version for download
    $downloadPath = $watermarkProcessor->generateWatermarkedClip($originalPath);
    
    if (!file_exists($downloadPath)) {
        throw new Exception('Failed to prepare download file');
    }
    
    // Update download count
    $updateStmt = $conn->prepare("UPDATE clips SET download_count = COALESCE(download_count, 0) + 1 WHERE id = ?");
    $updateStmt->execute([$clipId]);
    
    // Get file info
    $fileInfo = pathinfo($downloadPath);
    $mimeType = 'image/jpeg'; // Default
    
    switch (strtolower($fileInfo['extension'])) {
        case 'png':
            $mimeType = 'image/png';
            break;
        case 'gif':
            $mimeType = 'image/gif';
            break;
        case 'jpg':
        case 'jpeg':
        default:
            $mimeType = 'image/jpeg';
            break;
    }
    
    // Check if this is a download request (has download parameter)
    $isDownload = isset($_GET['download']) || (isset($input['download']) && $input['download']);
    
    if ($isDownload) {
        // Force download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="newspaper_clip_' . $clipId . '.' . $fileInfo['extension'] . '"');
        header('Content-Length: ' . filesize($downloadPath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Output file
        readfile($downloadPath);
        
        // Clean up temporary watermarked file if it's different from original
        if ($downloadPath !== $originalPath && strpos($downloadPath, '/temp/watermarked/') !== false) {
            // Don't delete immediately, but mark for cleanup
            // The cleanup will happen via a cron job or periodic cleanup
        }
        
        exit;
    } else {
        // Return JSON response with download URL
        $downloadUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . 
                      $_SERVER['HTTP_HOST'] . 
                      $_SERVER['PHP_SELF'] . 
                      '?id=' . $clipId . '&download=1';
        
        echo json_encode([
            'success' => true,
            'clip_id' => $clipId,
            'download_url' => $downloadUrl,
            'watermarked' => $watermarkProcessor->isWatermarkEnabled(),
            'file_size' => filesize($downloadPath),
            'mime_type' => $mimeType,
            'message' => 'Download ready'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Download clip error: " . $e->getMessage());
    
    if (!headers_sent()) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
