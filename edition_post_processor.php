<?php
/**
 * Automatic Edition Post-Processing System
 * Ensures every new edition is immediately ready for homepage display
 */

class EditionPostProcessor {
    private $db;
    
    public function __construct() {
        require_once 'includes/database.php';
        $this->db = getConnection();
    }
    
    /**
     * Process a newly uploaded edition to ensure it's fully ready
     */
    public function processNewEdition($editionId) {
        $results = [
            'edition_id' => $editionId,
            'pdf_processed' => false,
            'paths_validated' => false,
            'homepage_ready' => false,
            'message' => '',
            'errors' => []
        ];
        
        try {
            // 1. Get edition details
            $edition = $this->getEdition($editionId);
            if (!$edition) {
                throw new Exception("Edition not found");
            }
            
            // 2. Ensure PDF is processed to page images
            $pdfResult = $this->ensurePDFProcessed($edition);
            $results['pdf_processed'] = $pdfResult['success'];
            if (!$pdfResult['success']) {
                $results['errors'][] = $pdfResult['error'];
            }
            
            // 3. Validate and fix image paths
            $pathResult = $this->validateImagePaths($editionId);
            $results['paths_validated'] = $pathResult['success'];
            if ($pathResult['fixed'] > 0) {
                $results['message'] .= "Fixed {$pathResult['fixed']} image paths. ";
            }
            
            // 4. Final homepage readiness check
            $readinessResult = $this->checkHomepageReadiness($editionId);
            $results['homepage_ready'] = $readinessResult['ready'];
            if (!$readinessResult['ready']) {
                $results['errors'][] = $readinessResult['issue'];
            }
            
            // 5. Update edition status if everything is ready
            if ($results['homepage_ready']) {
                $this->markEditionReady($editionId);
                $results['message'] .= "Edition is fully ready for homepage display.";
            }
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Get edition details
     */
    private function getEdition($editionId) {
        $stmt = $this->db->prepare("SELECT * FROM editions WHERE id = ?");
        $stmt->execute([$editionId]);
        return $stmt->fetch();
    }
    
    /**
     * Ensure PDF is processed to page images
     */
    private function ensurePDFProcessed($edition) {
        // Check if pages already exist
        $pageCount = $this->db->query("SELECT COUNT(*) as count FROM edition_pages WHERE edition_id = " . $edition['id'])->fetch();
        
        if ($pageCount['count'] > 0) {
            return ['success' => true, 'message' => 'Pages already exist'];
        }
        
        // Process PDF
        try {
            require_once 'pdf_processor.php';
            $processor = new PDFProcessor();
            $pages = $processor->processPDF($edition['pdf_path'], $edition['id']);
            
            if (count($pages) > 0) {
                return ['success' => true, 'message' => 'PDF processed successfully'];
            } else {
                return ['success' => false, 'error' => 'PDF processing produced no pages'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'PDF processing failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validate and fix image paths for homepage compatibility
     */
    private function validateImagePaths($editionId) {
        $fixed = 0;
        
        // Get pages for this edition
        $pages = $this->db->query("SELECT id, image_path FROM edition_pages WHERE edition_id = $editionId")->fetchAll();
        
        foreach ($pages as $page) {
            $currentPath = $page['image_path'];
            $correctPath = $this->getWebAccessiblePath($currentPath);
            
            if ($currentPath !== $correctPath) {
                $stmt = $this->db->prepare("UPDATE edition_pages SET image_path = ? WHERE id = ?");
                if ($stmt->execute([$correctPath, $page['id']])) {
                    $fixed++;
                }
            }
        }
        
        return ['success' => true, 'fixed' => $fixed];
    }
    
    /**
     * Convert any path to web-accessible format for index.php
     */
    private function getWebAccessiblePath($path) {
        // Remove leading '../' or './'
        $webPath = ltrim($path, './');
        while (strpos($webPath, '../') === 0) {
            $webPath = substr($webPath, 3);
        }
        
        // Ensure it starts with 'uploads/' for root-level access
        if (!str_starts_with($webPath, 'uploads/')) {
            if (str_contains($webPath, 'uploads/')) {
                $webPath = 'uploads/' . substr($webPath, strpos($webPath, 'uploads/') + 8);
            }
        }
        
        return $webPath;
    }
    
    /**
     * Check if edition is ready for homepage display
     */
    private function checkHomepageReadiness($editionId) {
        // Check if pages exist
        $pages = $this->db->query("SELECT image_path FROM edition_pages WHERE edition_id = $editionId")->fetchAll();
        
        if (count($pages) === 0) {
            return ['ready' => false, 'issue' => 'No page images found'];
        }
        
        // Check if all image files exist
        $missingFiles = 0;
        foreach ($pages as $page) {
            if (!file_exists($page['image_path'])) {
                $missingFiles++;
            }
        }
        
        if ($missingFiles > 0) {
            return ['ready' => false, 'issue' => "$missingFiles image files are missing"];
        }
        
        return ['ready' => true, 'message' => 'All checks passed'];
    }
    
    /**
     * Mark edition as fully ready
     */
    private function markEditionReady($editionId) {
        // Update any metadata if needed
        $stmt = $this->db->prepare("UPDATE editions SET updated_at = NOW() WHERE id = ?");
        $stmt->execute([$editionId]);
    }
}

// Test function for manual use
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    if (isset($argv[1])) {
        $editionId = (int)$argv[1];
        echo "🔄 Processing Edition ID: $editionId\n";
        echo str_repeat("-", 40) . "\n";
        
        $processor = new EditionPostProcessor();
        $results = $processor->processNewEdition($editionId);
        
        echo "📊 RESULTS:\n";
        echo "PDF Processed: " . ($results['pdf_processed'] ? '✅ Yes' : '❌ No') . "\n";
        echo "Paths Validated: " . ($results['paths_validated'] ? '✅ Yes' : '❌ No') . "\n";
        echo "Homepage Ready: " . ($results['homepage_ready'] ? '✅ Yes' : '❌ No') . "\n";
        
        if (!empty($results['message'])) {
            echo "Message: " . $results['message'] . "\n";
        }
        
        if (!empty($results['errors'])) {
            echo "Errors: " . implode(', ', $results['errors']) . "\n";
        }
        
    } else {
        echo "Usage: php edition_post_processor.php <edition_id>\n";
    }
}
?>
