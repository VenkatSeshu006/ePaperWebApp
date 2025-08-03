<?php
/**
 * Download Edition API
 * Serves PDF files and tracks downloads
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    // Get edition ID from URL parameter
    $editionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$editionId) {
        throw new Exception('Edition ID is required');
    }
    
    // Get database connection
    $conn = getConnection();
    
    // Get edition details
    $stmt = $conn->prepare("SELECT id, title, date, pdf_path FROM editions WHERE id = ? AND status = 'published'");
    $stmt->execute([$editionId]);
    $edition = $stmt->fetch();
    
    if (!$edition) {
        throw new Exception('Edition not found or not published');
    }
    
    // Check if PDF file exists
    $pdfPath = '../' . $edition['pdf_path'];
    if (!file_exists($pdfPath) || !is_readable($pdfPath)) {
        throw new Exception('PDF file not found or not accessible');
    }
    
    // Track download
    try {
        $analytics = new Analytics();
        $clientInfo = [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? ''
        ];
        $analytics->trackDownload('edition', $editionId, $clientInfo);
    } catch (Exception $e) {
        // Log analytics error but continue with download
        error_log("Download tracking failed: " . $e->getMessage());
    }
    
    // Prepare file for download
    $filename = $edition['title'] . '_' . date('Y-m-d', strtotime($edition['date'])) . '.pdf';
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename); // Sanitize filename
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($pdfPath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Output the file
    readfile($pdfPath);
    exit;
    
} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
