<?php
/**
 * Track Download API
 * Records download analytics for editions and clips
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
    '../classes/Analytics.php'
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
require_once '../classes/Analytics.php';

try {
    // Initialize analytics
    $analytics = new Analytics();
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    if (!isset($input['type']) || !isset($input['id'])) {
        throw new Exception('Missing required fields: type and id');
    }
    
    $type = filter_var($input['type'], FILTER_SANITIZE_STRING);
    $id = filter_var($input['id'], FILTER_VALIDATE_INT);
    
    if (!$id) {
        throw new Exception('Invalid ID');
    }
    
    if (!in_array($type, ['edition', 'clip', 'page'])) {
        throw new Exception('Invalid download type');
    }
    
    // Get client information
    $clientInfo = [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'referer' => $_SERVER['HTTP_REFERER'] ?? null,
        'user_id' => $_SESSION['user_id'] ?? null
    ];
    
    // Track the download based on type
    switch ($type) {
        case 'edition':
            $result = $analytics->trackDownload('edition', $id, $clientInfo);
            break;
            
        case 'clip':
            $result = $analytics->trackDownload('clip', $id, $clientInfo);
            break;
            
        case 'page':
            $editionId = $input['edition_id'] ?? null;
            $pageNumber = $input['page_number'] ?? null;
            
            if (!$editionId || !$pageNumber) {
                throw new Exception('Missing edition_id or page_number for page download');
            }
            
            $result = $analytics->trackPageDownload($editionId, $pageNumber, $clientInfo);
            break;
            
        default:
            throw new Exception('Unsupported download type');
    }
    
    if (!$result) {
        throw new Exception('Failed to track download');
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Download tracked successfully',
        'type' => $type,
        'id' => $id
    ]);
    
} catch (Exception $e) {
    error_log("Track download error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
