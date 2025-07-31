<?php
/**
 * Homepage Data API
 * Provides data for the main homepage
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Use simple database connection
    require_once '../includes/db.php';
    $conn = getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Get latest editions with their pages
    $sql = "
        SELECT 
            e.*,
            COUNT(p.id) as page_count,
            MIN(p.image_path) as first_page_path,
            p.thumbnail_path as thumbnail_path
        FROM editions e 
        LEFT JOIN pages p ON e.id = p.edition_id 
        WHERE e.status = 'published' 
        GROUP BY e.id 
        ORDER BY e.date DESC, e.id DESC 
        LIMIT 20
    ";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    
    $editions = [];
    while ($row = $result->fetch_assoc()) {
        // Process the edition data
        $edition = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'description' => $row['description'],
            'date' => $row['date'],
            'status' => $row['status'],
            'featured' => (bool)$row['featured'],
            'total_pages' => (int)($row['total_pages'] ?: $row['page_count']),
            'views' => (int)$row['views'],
            'downloads' => (int)$row['downloads'],
            'thumbnail' => $row['thumbnail_path'] ?: $row['first_page_path'],
            'pdf_path' => $row['pdf_path'],
            'file_size' => (int)$row['file_size'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
        
        // Get pages for this edition
        $pages_sql = "
            SELECT page_number, image_path, thumbnail_path, width, height 
            FROM pages 
            WHERE edition_id = ? 
            ORDER BY page_number ASC
        ";
        
        $pages_stmt = $conn->prepare($pages_sql);
        $pages_stmt->bind_param('i', $row['id']);
        $pages_stmt->execute();
        $pages_result = $pages_stmt->get_result();
        
        $pages = [];
        while ($page = $pages_result->fetch_assoc()) {
            $pages[] = [
                'page_number' => (int)$page['page_number'],
                'image_path' => $page['image_path'],
                'thumbnail_path' => $page['thumbnail_path'],
                'width' => (int)$page['width'],
                'height' => (int)$page['height']
            ];
        }
        
        $edition['pages'] = $pages;
        $editions[] = $edition;
    }
    
    // Get statistics
    $stats_sql = "
        SELECT 
            COUNT(*) as total_editions,
            SUM(views) as total_views,
            SUM(downloads) as total_downloads,
            COUNT(CASE WHEN status = 'published' THEN 1 END) as published_editions
        FROM editions
    ";
    
    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();
    
    // Get recent clips
    $clips_sql = "
        SELECT c.*, e.title as edition_title 
        FROM clips c 
        LEFT JOIN editions e ON c.edition_id = e.id 
        ORDER BY c.created_at DESC 
        LIMIT 10
    ";
    
    $clips_result = $conn->query($clips_sql);
    $clips = [];
    
    if ($clips_result) {
        while ($clip = $clips_result->fetch_assoc()) {
            $clips[] = [
                'id' => (int)$clip['id'],
                'edition_id' => (int)$clip['edition_id'],
                'edition_title' => $clip['edition_title'],
                'image_path' => $clip['image_path'],
                'title' => $clip['title'],
                'description' => $clip['description'],
                'created_at' => $clip['created_at']
            ];
        }
    }
    
    // Response data
    $response = [
        'success' => true,
        'data' => [
            'editions' => $editions,
            'stats' => [
                'total_editions' => (int)$stats['total_editions'],
                'total_views' => (int)$stats['total_views'],
                'total_downloads' => (int)$stats['total_downloads'],
                'published_editions' => (int)$stats['published_editions']
            ],
            'recent_clips' => $clips
        ],
        'timestamp' => date('c')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
}
?>
