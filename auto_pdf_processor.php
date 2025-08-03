<?php
/**
 * Automatic PDF Processing Hook
 * This file ensures all new PDFs are automatically converted to images
 */

require_once 'config.php';
require_once 'includes/database.php';
require_once 'pdf_processor.php';

class AutoPDFProcessor {
    private $db;
    private $processor;
    
    public function __construct() {
        $this->db = getConnection();
        $this->processor = new PDFProcessor();
    }
    
    /**
     * Check for any editions that need processing and process them
     */
    public function processUnprocessedEditions() {
        $query = "
            SELECT e.id, e.title, e.pdf_path 
            FROM editions e 
            LEFT JOIN edition_pages ep ON e.id = ep.edition_id 
            WHERE e.status IN ('published', 'draft') 
            AND e.pdf_path IS NOT NULL 
            AND e.pdf_path != ''
            AND ep.edition_id IS NULL
            GROUP BY e.id
            ORDER BY e.created_at DESC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $editions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results = [];
        foreach ($editions as $edition) {
            try {
                if (!file_exists($edition['pdf_path'])) {
                    continue;
                }
                
                $pages = $this->processor->processPDF($edition['pdf_path'], $edition['id']);
                $results[] = [
                    'id' => $edition['id'],
                    'title' => $edition['title'],
                    'status' => 'success',
                    'pages' => count($pages)
                ];
                
                // Log success
                error_log("AutoPDFProcessor: Successfully processed edition {$edition['id']} - {$edition['title']} (" . count($pages) . " pages)");
                
            } catch (Exception $e) {
                $results[] = [
                    'id' => $edition['id'],
                    'title' => $edition['title'],
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                
                // Log error
                error_log("AutoPDFProcessor: Failed to process edition {$edition['id']} - {$edition['title']}: " . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    /**
     * Validate all processed editions have correct page counts
     */
    public function validateProcessedEditions() {
        $query = "
            SELECT e.id, e.title, e.total_pages, 
                   COUNT(ep.id) as actual_pages
            FROM editions e 
            LEFT JOIN edition_pages ep ON e.id = ep.edition_id 
            WHERE e.status IN ('published', 'draft') 
            AND e.pdf_path IS NOT NULL 
            GROUP BY e.id
            HAVING e.total_pages != actual_pages OR actual_pages = 0
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $mismatched = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results = [];
        foreach ($mismatched as $edition) {
            // Update the total_pages count to match actual pages
            $updateStmt = $this->db->prepare("UPDATE editions SET total_pages = ? WHERE id = ?");
            $updateStmt->execute([$edition['actual_pages'], $edition['id']]);
            
            $results[] = [
                'id' => $edition['id'],
                'title' => $edition['title'], 
                'old_count' => $edition['total_pages'],
                'new_count' => $edition['actual_pages']
            ];
        }
        
        return $results;
    }
}

// If called directly, run the auto-processor
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $autoProcessor = new AutoPDFProcessor();
    
    echo "<h1>🤖 Automatic PDF Processor</h1>";
    
    echo "<h2>Processing Unprocessed Editions</h2>";
    $results = $autoProcessor->processUnprocessedEditions();
    
    if (empty($results)) {
        echo "<p>✅ All editions are already processed!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Details</th></tr>";
        
        foreach ($results as $result) {
            $statusColor = $result['status'] === 'success' ? 'green' : 'red';
            $statusIcon = $result['status'] === 'success' ? '✅' : '❌';
            $details = $result['status'] === 'success' ? $result['pages'] . ' pages' : $result['message'];
            
            echo "<tr>";
            echo "<td>" . $result['id'] . "</td>";
            echo "<td>" . htmlspecialchars($result['title']) . "</td>";
            echo "<td style='color: $statusColor;'>$statusIcon " . ucfirst($result['status']) . "</td>";
            echo "<td>" . htmlspecialchars($details) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>Validating Page Counts</h2>";
    $validation = $autoProcessor->validateProcessedEditions();
    
    if (empty($validation)) {
        echo "<p>✅ All page counts are correct!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Old Count</th><th>Corrected Count</th></tr>";
        
        foreach ($validation as $fix) {
            echo "<tr>";
            echo "<td>" . $fix['id'] . "</td>";
            echo "<td>" . htmlspecialchars($fix['title']) . "</td>";
            echo "<td>" . $fix['old_count'] . "</td>";
            echo "<td style='color: green;'>" . $fix['new_count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>🔄 Automatic Processing Complete!</strong><br>";
    echo "This script runs automatically to catch any PDFs that weren't processed during upload.";
    echo "</div>";
}
?>
