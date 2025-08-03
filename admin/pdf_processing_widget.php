<?php
/**
 * PDF Processing Dashboard Widget
 * Shows PDF processing status in admin dashboard
 */

require_once '../config.php';
require_once '../includes/database.php';

class PDFProcessingWidget {
    private $db;
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    public function getStatus() {
        $query = "
            SELECT 
                COUNT(*) as total_editions,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_editions,
                SUM(CASE WHEN pdf_path IS NOT NULL AND pdf_path != '' THEN 1 ELSE 0 END) as with_pdfs,
                SUM(CASE WHEN (SELECT COUNT(*) FROM edition_pages ep WHERE ep.edition_id = e.id) > 0 THEN 1 ELSE 0 END) as with_pages
            FROM editions e
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get unprocessed editions
        $unprocessedQuery = "
            SELECT e.id, e.title, e.created_at
            FROM editions e 
            LEFT JOIN edition_pages ep ON e.id = ep.edition_id 
            WHERE e.pdf_path IS NOT NULL 
            AND e.pdf_path != ''
            AND ep.edition_id IS NULL
            ORDER BY e.created_at DESC
            LIMIT 5
        ";
        
        $unprocessedStmt = $this->db->prepare($unprocessedQuery);
        $unprocessedStmt->execute();
        $unprocessed = $unprocessedStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'stats' => $stats,
            'unprocessed' => $unprocessed,
            'processing_rate' => $this->calculateProcessingRate($stats)
        ];
    }
    
    private function calculateProcessingRate($stats) {
        if ($stats['with_pdfs'] == 0) return 100;
        return round(($stats['with_pages'] / $stats['with_pdfs']) * 100, 1);
    }
    
    public function renderWidget() {
        $status = $this->getStatus();
        $stats = $status['stats'];
        $unprocessed = $status['unprocessed'];
        $rate = $status['processing_rate'];
        
        ?>
        <div class="pdf-processing-widget" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">
            <h3 style="margin: 0 0 15px 0; color: #333; display: flex; align-items: center;">
                <i class="fas fa-file-pdf" style="margin-right: 10px; color: #dc3545;"></i>
                PDF Processing Status
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <div style="font-size: 24px; font-weight: bold; color: #007bff;"><?php echo $stats['total_editions']; ?></div>
                    <div style="font-size: 12px; color: #666;">Total Editions</div>
                </div>
                
                <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <div style="font-size: 24px; font-weight: bold; color: #28a745;"><?php echo $stats['with_pdfs']; ?></div>
                    <div style="font-size: 12px; color: #666;">With PDFs</div>
                </div>
                
                <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <div style="font-size: 24px; font-weight: bold; color: #17a2b8;"><?php echo $stats['with_pages']; ?></div>
                    <div style="font-size: 12px; color: #666;">Processed</div>
                </div>
                
                <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <div style="font-size: 24px; font-weight: bold; color: <?php echo $rate == 100 ? '#28a745' : ($rate > 80 ? '#ffc107' : '#dc3545'); ?>;">
                        <?php echo $rate; ?>%
                    </div>
                    <div style="font-size: 12px; color: #666;">Processing Rate</div>
                </div>
            </div>
            
            <?php if (!empty($unprocessed)): ?>
            <div style="border-top: 1px solid #eee; padding-top: 15px;">
                <h4 style="margin: 0 0 10px 0; color: #666; font-size: 14px;">
                    <i class="fas fa-clock" style="margin-right: 5px;"></i>
                    Pending Processing (<?php echo count($unprocessed); ?>)
                </h4>
                
                <div style="max-height: 150px; overflow-y: auto;">
                    <?php foreach ($unprocessed as $edition): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                        <div>
                            <strong style="color: #333;"><?php echo htmlspecialchars($edition['title']); ?></strong>
                            <small style="color: #666; display: block;">ID: <?php echo $edition['id']; ?></small>
                        </div>
                        <small style="color: #999;">
                            <?php echo date('M j, Y', strtotime($edition['created_at'])); ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 15px; text-align: center;">
                    <a href="../auto_pdf_processor.php" class="btn" style="background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 12px;">
                        <i class="fas fa-play"></i> Process Now
                    </a>
                    <a href="../system_integrity_check.php" class="btn" style="background: #6c757d; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 12px; margin-left: 5px;">
                        <i class="fas fa-check-circle"></i> System Check
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div style="border-top: 1px solid #eee; padding-top: 15px; text-align: center; color: #28a745;">
                <i class="fas fa-check-circle" style="font-size: 24px; margin-bottom: 10px;"></i>
                <div><strong>All PDFs Processed!</strong></div>
                <small>No editions are waiting for processing.</small>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

// If included in admin dashboard
if (isset($includeWidget) && $includeWidget) {
    $widget = new PDFProcessingWidget();
    $widget->renderWidget();
}

// If called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>PDF Processing Dashboard</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>📊 PDF Processing Dashboard</h1>
            <?php
            $widget = new PDFProcessingWidget();
            $widget->renderWidget();
            ?>
        </div>
    </body>
    </html>
    <?php
}
?>
