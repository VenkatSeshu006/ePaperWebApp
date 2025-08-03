<?php
/**
 * Automated Path Maintenance System
 * Runs checks and fixes to ensure the image display system always works
 */

class AutoPathMaintenance {
    private $db;
    private $logFile;
    
    public function __construct() {
        require_once 'includes/database.php';
        $this->db = getConnection();
        $this->logFile = 'logs/path_maintenance.log';
        
        // Ensure log directory exists
        if (!is_dir('logs')) {
            mkdir('logs', 0755, true);
        }
    }
    
    /**
     * Run all maintenance checks and fixes
     */
    public function runMaintenance() {
        $this->log("=== AUTOMATED PATH MAINTENANCE STARTED ===");
        
        $results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'paths_fixed' => 0,
            'missing_files' => 0,
            'total_pages' => 0,
            'editions_processed' => []
        ];
        
        try {
            // 1. Fix any incorrect paths
            $pathResults = $this->fixIncorrectPaths();
            $results['paths_fixed'] = $pathResults['fixed'];
            
            // 2. Check for missing files
            $fileResults = $this->checkMissingFiles();
            $results['missing_files'] = $fileResults['missing'];
            $results['total_pages'] = $fileResults['total'];
            
            // 3. Process any unprocessed PDFs
            $processingResults = $this->processUnprocessedPDFs();
            $results['editions_processed'] = $processingResults;
            
            // 4. Validate all editions have proper homepage display capability
            $this->validateHomepageCompatibility();
            
            $this->log("Maintenance completed successfully");
            $this->log("Paths fixed: " . $results['paths_fixed']);
            $this->log("Missing files: " . $results['missing_files']);
            $this->log("Editions processed: " . count($results['editions_processed']));
            
        } catch (Exception $e) {
            $this->log("ERROR: " . $e->getMessage());
            throw $e;
        }
        
        $this->log("=== AUTOMATED PATH MAINTENANCE COMPLETED ===\n");
        return $results;
    }
    
    /**
     * Fix paths that don't work from homepage perspective
     */
    private function fixIncorrectPaths() {
        $fixed = 0;
        
        // Find paths that start with '../' which won't work from index.php
        $pages = $this->db->query("SELECT id, edition_id, page_number, image_path FROM edition_pages WHERE image_path LIKE '../%'")->fetchAll();
        
        foreach ($pages as $page) {
            $oldPath = $page['image_path'];
            $newPath = $this->convertToWebPath($oldPath);
            
            $stmt = $this->db->prepare("UPDATE edition_pages SET image_path = ? WHERE id = ?");
            if ($stmt->execute([$newPath, $page['id']])) {
                $this->log("Fixed path for Edition {$page['edition_id']} Page {$page['page_number']}: {$oldPath} → {$newPath}");
                $fixed++;
            }
        }
        
        return ['fixed' => $fixed];
    }
    
    /**
     * Check for missing image files
     */
    private function checkMissingFiles() {
        $pages = $this->db->query("SELECT edition_id, page_number, image_path FROM edition_pages")->fetchAll();
        $missing = 0;
        $total = count($pages);
        
        foreach ($pages as $page) {
            if (!file_exists($page['image_path'])) {
                $this->log("Missing file: Edition {$page['edition_id']} Page {$page['page_number']} - {$page['image_path']}");
                $missing++;
            }
        }
        
        return ['missing' => $missing, 'total' => $total];
    }
    
    /**
     * Process any PDFs that don't have page images
     */
    private function processUnprocessedPDFs() {
        $unprocessed = $this->db->query("
            SELECT e.id, e.title, e.pdf_path 
            FROM editions e 
            LEFT JOIN edition_pages ep ON e.id = ep.edition_id 
            WHERE e.status = 'published' AND ep.id IS NULL
        ")->fetchAll();
        
        $processed = [];
        
        foreach ($unprocessed as $edition) {
            try {
                require_once 'pdf_processor.php';
                $processor = new PDFProcessor();
                $pages = $processor->processPDF($edition['pdf_path'], $edition['id']);
                
                if ($pages) {
                    $this->log("Auto-processed unprocessed edition: {$edition['title']} (" . count($pages) . " pages)");
                    $processed[] = [
                        'id' => $edition['id'],
                        'title' => $edition['title'],
                        'pages' => count($pages)
                    ];
                }
            } catch (Exception $e) {
                $this->log("Failed to process edition {$edition['title']}: " . $e->getMessage());
            }
        }
        
        return $processed;
    }
    
    /**
     * Validate all editions can be displayed on homepage
     */
    private function validateHomepageCompatibility() {
        $editions = $this->db->query("SELECT id, title FROM editions WHERE status = 'published'")->fetchAll();
        
        foreach ($editions as $edition) {
            $pageCount = $this->db->query("SELECT COUNT(*) as count FROM edition_pages WHERE edition_id = " . $edition['id'])->fetch();
            
            if ($pageCount['count'] == 0) {
                $this->log("WARNING: Edition '{$edition['title']}' has no page images - will not display properly on homepage");
            }
        }
    }
    
    /**
     * Convert database path to web-accessible path
     */
    private function convertToWebPath($path) {
        // Remove any leading '../'
        $webPath = ltrim($path, './');
        while (strpos($webPath, '../') === 0) {
            $webPath = substr($webPath, 3);
        }
        
        // Ensure it starts with 'uploads/' for web access from root
        if (!str_starts_with($webPath, 'uploads/')) {
            if (str_contains($webPath, 'uploads/')) {
                $webPath = 'uploads/' . substr($webPath, strpos($webPath, 'uploads/') + 8);
            }
        }
        
        return $webPath;
    }
    
    /**
     * Log maintenance activities
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Run maintenance if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "🔧 Running Automated Path Maintenance...\n";
    echo str_repeat("-", 50) . "\n";
    
    try {
        $maintenance = new AutoPathMaintenance();
        $results = $maintenance->runMaintenance();
        
        echo "✅ Maintenance completed successfully!\n";
        echo "📊 Results:\n";
        echo "   - Paths fixed: " . $results['paths_fixed'] . "\n";
        echo "   - Missing files: " . $results['missing_files'] . "\n";
        echo "   - Total pages: " . $results['total_pages'] . "\n";
        echo "   - Editions processed: " . count($results['editions_processed']) . "\n";
        
        if ($results['paths_fixed'] > 0 || count($results['editions_processed']) > 0) {
            echo "🎉 System improvements made - your homepage should work even better now!\n";
        } else {
            echo "✅ All systems are already optimal!\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Maintenance failed: " . $e->getMessage() . "\n";
    }
}
?>
