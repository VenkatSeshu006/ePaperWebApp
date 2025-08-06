<?php
/**
 * Get Clip Data API
 * Returns clip information for editing
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once 'includes/database.php';
    $conn = getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    $clipId = (int)($_GET['id'] ?? 0);
    
    if (!$clipId) {
        throw new Exception('Invalid clip ID');
    }
    
    $stmt = $conn->prepare("
        SELECT c.*, e.title as edition_title 
        FROM clips c 
        LEFT JOIN editions e ON c.edition_id = e.id 
        WHERE c.id = ?
    ");
    
    $stmt->execute([$clipId]);
    $result = $stmt->get_result();
    $clip = $result->fetch();
    
    if (!$clip) {
        throw new Exception('Clip not found');
    }
    
    echo json_encode([
        'success' => true,
        'clip' => $clip
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
