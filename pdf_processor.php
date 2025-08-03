<?php
/**
 * PDF to Images Processor
 * Converts PDFs to individual page images using Ghostscript
 */

require_once 'config.php';
require_once 'includes/database.php';

class PDFProcessor {
    private $gsPath;
    private $db;
    
    public function __construct() {
        $this->gsPath = GHOSTSCRIPT_COMMAND;
        $this->db = getConnection();
    }
    
    /**
     * Process a PDF file and convert to images
     */
    public function processPDF($pdfPath, $editionId) {
        if (!file_exists($pdfPath)) {
            throw new Exception("PDF file not found: $pdfPath");
        }
        
        // Create pages directory for this edition
        $pagesDir = dirname($pdfPath) . '/pages';
        if (!is_dir($pagesDir)) {
            mkdir($pagesDir, 0755, true);
        }
        
        // Convert PDF to images
        $pages = $this->convertPDFToImages($pdfPath, $pagesDir);
        
        if (!empty($pages)) {
            // Save page data to database
            $this->savePagesToDB($editionId, $pages);
            
            // Update edition with page count
            $this->updateEditionPageCount($editionId, count($pages));
        }
        
        return $pages;
    }
    
    /**
     * Convert PDF to individual page images
     */
    private function convertPDFToImages($pdfPath, $pagesDir) {
        $outputPattern = $pagesDir . '/page_%03d.png';
        
        // Build Ghostscript command with premium quality settings for newspaper readability
        $command = sprintf(
            '"%s" -dNOPAUSE -dBATCH -sDEVICE=png16m -r300 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dDownScaleFactor=1 -dColorImageResolution=300 -dGrayImageResolution=300 -dMonoImageResolution=600 -dUseCropBox -dPDFFitPage -dColorConversionStrategy=/LeaveColorUnchanged -dAutoFilterColorImages=false -dAutoFilterGrayImages=false -dColorImageFilter=/FlateEncode -dGrayImageFilter=/FlateEncode -sOutputFile="%s" "%s"',
            $this->gsPath,
            $outputPattern,
            $pdfPath
        );
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Ghostscript conversion failed: " . implode("\n", $output));
        }
        
        // Get created page files
        $pageFiles = glob($pagesDir . '/page_*.png');
        sort($pageFiles, SORT_NATURAL);
        
        return $pageFiles;
    }
    
    /**
     * Save page data to database
     */
    private function savePagesToDB($editionId, $pageFiles) {
        // First, remove existing pages for this edition
        $stmt = $this->db->prepare("DELETE FROM edition_pages WHERE edition_id = ?");
        $stmt->execute([$editionId]);
        
        // Insert new pages
        $insertStmt = $this->db->prepare("
            INSERT INTO edition_pages (edition_id, page_number, image_path, width, height) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($pageFiles as $index => $pageFile) {
            $pageNumber = $index + 1;
            
            // Create web-accessible path from project root (for index.php)
            $projectRoot = __DIR__; // This is the project root directory
            $relativePath = str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $pageFile);
            $relativePath = str_replace('\\', '/', $relativePath); // Normalize path separators for web
            
            // Get image dimensions
            $imageInfo = getimagesize($pageFile);
            $width = $imageInfo ? $imageInfo[0] : 0;
            $height = $imageInfo ? $imageInfo[1] : 0;
            
            $insertStmt->execute([$editionId, $pageNumber, $relativePath, $width, $height]);
        }
    }
    
    /**
     * Update edition with total page count
     */
    private function updateEditionPageCount($editionId, $pageCount) {
        $stmt = $this->db->prepare("UPDATE editions SET total_pages = ? WHERE id = ?");
        $stmt->execute([$pageCount, $editionId]);
    }
    
    /**
     * Process all existing PDFs that don't have pages
     */
    public function processAllExistingPDFs() {
        $query = "
            SELECT e.id, e.title, e.pdf_path 
            FROM editions e 
            LEFT JOIN edition_pages ep ON e.id = ep.edition_id 
            WHERE e.status = 'published' 
            AND e.pdf_path IS NOT NULL 
            AND ep.edition_id IS NULL
            GROUP BY e.id
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $editions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results = [];
        
        foreach ($editions as $edition) {
            try {
                $pdfPath = $edition['pdf_path'];
                if (!file_exists($pdfPath)) {
                    $results[] = [
                        'id' => $edition['id'],
                        'title' => $edition['title'],
                        'status' => 'error',
                        'message' => "PDF file not found: $pdfPath"
                    ];
                    continue;
                }
                
                $pages = $this->processPDF($pdfPath, $edition['id']);
                
                $results[] = [
                    'id' => $edition['id'],
                    'title' => $edition['title'],
                    'status' => 'success',
                    'pages' => count($pages),
                    'message' => "Converted to " . count($pages) . " pages"
                ];
                
            } catch (Exception $e) {
                $results[] = [
                    'id' => $edition['id'],
                    'title' => $edition['title'],
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}

// If called directly, process all existing PDFs
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    echo "<h1>PDF to Images Processor</h1>";
    
    try {
        $processor = new PDFProcessor();
        
        echo "<h2>Processing existing PDFs...</h2>";
        $results = $processor->processAllExistingPDFs();
        
        if (empty($results)) {
            echo "<p>No PDFs found that need processing.</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Message</th></tr>";
            
            foreach ($results as $result) {
                $statusColor = $result['status'] === 'success' ? 'green' : 'red';
                echo "<tr>";
                echo "<td>" . htmlspecialchars($result['id']) . "</td>";
                echo "<td>" . htmlspecialchars($result['title']) . "</td>";
                echo "<td style='color: $statusColor;'>" . htmlspecialchars($result['status']) . "</td>";
                echo "<td>" . htmlspecialchars($result['message']) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>
