<?php
/**
 * Update Clip API
 * Updates clip title and description
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once '../includes/db.php';
    $conn = getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id']) || !isset($input['title'])) {
        throw new Exception('Missing required fields');
    }
    
    $clipId = (int)$input['id'];
    $title = trim($input['title']);
    $description = trim($input['description'] ?? '');
    
    if (empty($title)) {
        throw new Exception('Title cannot be empty');
    }
    
    $stmt = $conn->prepare("
        UPDATE clips 
        SET title = ?, description = ? 
        WHERE id = ?
    ");
    
    $stmt->bind_param('ssi', $title, $description, $clipId);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update clip');
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Clip not found or no changes made');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Clip updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
