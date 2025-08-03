<?php
/**
 * Enhanced Quality PDF Processor
 * Specialized for maximum readability of newspaper content
 */

class EnhancedQualityPDFProcessor {
    private $gsPath;
    private $db;
    
    public function __construct() {
        if (!defined('GHOSTSCRIPT_COMMAND')) {
            require_once 'config.php';
        }
        
        $this->gsPath = GHOSTSCRIPT_COMMAND;
        require_once 'includes/database.php';
        $this->db = getConnection();
    }
    
    /**
     * Process PDF with multiple quality options
     */
    public function processWithBestQuality($pdfPath, $editionId) {
        $qualityProfiles = [
            'premium' => $this->getPremiumQualitySettings(),
            'high' => $this->getHighQualitySettings(),
            'standard' => $this->getStandardQualitySettings()
        ];
        
        $lastError = null;
        
        foreach ($qualityProfiles as $profileName => $settings) {
            try {
                echo "Attempting {$profileName} quality conversion...\n";
                $pages = $this->convertWithSettings($pdfPath, $editionId, $settings);
                
                if (!empty($pages)) {
                    echo "✅ Successfully converted with {$profileName} quality (" . count($pages) . " pages)\n";
                    return $pages;
                }
                
            } catch (Exception $e) {
                $lastError = $e;
                echo "⚠️  {$profileName} quality failed: " . $e->getMessage() . "\n";
                continue;
            }
        }
        
        throw new Exception("All quality profiles failed. Last error: " . ($lastError ? $lastError->getMessage() : "Unknown error"));
    }
    
    /**
     * Premium quality settings for best text readability
     */
    private function getPremiumQualitySettings() {
        return [
            'resolution' => 300,
            'device' => 'png16m',
            'extra_params' => [
                '-dTextAlphaBits=4',
                '-dGraphicsAlphaBits=4',
                '-dDownScaleFactor=1',
                '-dColorImageResolution=300',
                '-dGrayImageResolution=300', 
                '-dMonoImageResolution=600',
                '-dUseCropBox',
                '-dPDFFitPage',
                '-dColorConversionStrategy=/LeaveColorUnchanged',
                '-dAutoFilterColorImages=false',
                '-dAutoFilterGrayImages=false',
                '-dColorImageFilter=/FlateEncode',
                '-dGrayImageFilter=/FlateEncode',
                '-dOptimize=true',
                '-dEmbedAllFonts=true',
                '-dSubsetFonts=true',
                '-dCompressFonts=true'
            ]
        ];
    }
    
    /**
     * High quality settings (fallback)
     */
    private function getHighQualitySettings() {
        return [
            'resolution' => 250,
            'device' => 'png16m',
            'extra_params' => [
                '-dTextAlphaBits=4',
                '-dGraphicsAlphaBits=4',
                '-dUseCropBox',
                '-dPDFFitPage',
                '-dOptimize=true'
            ]
        ];
    }
    
    /**
     * Standard quality settings (final fallback)
     */
    private function getStandardQualitySettings() {
        return [
            'resolution' => 200,
            'device' => 'png16m',
            'extra_params' => [
                '-dTextAlphaBits=4',
                '-dGraphicsAlphaBits=4'
            ]
        ];
    }
    
    /**
     * Convert PDF with specific quality settings
     */
    private function convertWithSettings($pdfPath, $editionId, $settings) {
        // Create pages directory
        $pagesDir = dirname($pdfPath) . '/pages';
        if (!is_dir($pagesDir)) {
            mkdir($pagesDir, 0755, true);
        }
        
        $outputPattern = $pagesDir . '/page_%03d.png';
        
        // Build command with quality settings
        $extraParams = implode(' ', $settings['extra_params']);
        $command = sprintf(
            '"%s" -dNOPAUSE -dBATCH -sDEVICE=%s -r%d %s -sOutputFile="%s" "%s"',
            $this->gsPath,
            $settings['device'],
            $settings['resolution'],
            $extraParams,
            $outputPattern,
            $pdfPath
        );
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Ghostscript conversion failed: " . implode("\n", $output));
        }
        
        // Get created files
        $pageFiles = glob($pagesDir . '/page_*.png');
        sort($pageFiles, SORT_NATURAL);
        
        if (empty($pageFiles)) {
            throw new Exception("No page files were created");
        }
        
        // Save to database with correct web paths
        $this->savePagesToDB($editionId, $pageFiles);
        
        // Update edition page count
        $this->updateEditionPageCount($editionId, count($pageFiles));
        
        return $pageFiles;
    }
    
    /**
     * Save pages to database with web-accessible paths
     */
    private function savePagesToDB($editionId, $pageFiles) {
        // Remove existing pages
        $stmt = $this->db->prepare("DELETE FROM edition_pages WHERE edition_id = ?");
        $stmt->execute([$editionId]);
        
        // Insert new pages
        $insertStmt = $this->db->prepare("
            INSERT INTO edition_pages (edition_id, page_number, image_path, width, height) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($pageFiles as $index => $pageFile) {
            $pageNumber = $index + 1;
            
            // Create web-accessible path from project root
            $projectRoot = __DIR__;
            $relativePath = str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $pageFile);
            $relativePath = str_replace('\\', '/', $relativePath); // Normalize for web
            
            // Get image dimensions
            $imageInfo = getimagesize($pageFile);
            $width = $imageInfo ? $imageInfo[0] : 0;
            $height = $imageInfo ? $imageInfo[1] : 0;
            
            $insertStmt->execute([$editionId, $pageNumber, $relativePath, $width, $height]);
        }
    }
    
    /**
     * Update edition page count
     */
    private function updateEditionPageCount($editionId, $pageCount) {
        $stmt = $this->db->prepare("UPDATE editions SET total_pages = ? WHERE id = ?");
        $stmt->execute([$pageCount, $editionId]);
    }
    
    /**
     * Get image quality analysis
     */
    public function analyzeImageQuality($imagePath) {
        if (!file_exists($imagePath)) {
            return ['error' => 'Image file not found'];
        }
        
        $imageInfo = getimagesize($imagePath);
        $fileSize = filesize($imagePath);
        
        return [
            'width' => $imageInfo[0] ?? 0,
            'height' => $imageInfo[1] ?? 0,
            'file_size' => $fileSize,
            'file_size_mb' => round($fileSize / 1024 / 1024, 2),
            'mime_type' => $imageInfo['mime'] ?? 'unknown',
            'quality_score' => $this->calculateQualityScore($imageInfo[0] ?? 0, $fileSize)
        ];
    }
    
    /**
     * Calculate quality score based on dimensions and file size
     */
    private function calculateQualityScore($width, $fileSize) {
        if ($width === 0) return 0;
        
        // Quality factors
        $resolutionScore = min(100, ($width / 2000) * 100); // Based on width
        $fileSizeScore = min(100, ($fileSize / (1024 * 1024)) * 20); // Based on file size
        
        return round(($resolutionScore + $fileSizeScore) / 2);
    }
}

// Test function if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    if (isset($argv[1]) && isset($argv[2])) {
        $pdfPath = $argv[1];
        $editionId = (int)$argv[2];
        
        echo "🎯 Enhanced Quality PDF Processing\n";
        echo "PDF: {$pdfPath}\n";
        echo "Edition ID: {$editionId}\n";
        echo str_repeat("-", 50) . "\n";
        
        try {
            $processor = new EnhancedQualityPDFProcessor();
            $pages = $processor->processWithBestQuality($pdfPath, $editionId);
            
            echo "\n📊 QUALITY ANALYSIS:\n";
            foreach (array_slice($pages, 0, 3) as $i => $page) {
                $analysis = $processor->analyzeImageQuality($page);
                echo "Page " . ($i + 1) . ": {$analysis['width']}x{$analysis['height']} ({$analysis['file_size_mb']}MB) - Quality Score: {$analysis['quality_score']}%\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Processing failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Usage: php enhanced_quality_processor.php <pdf_path> <edition_id>\n";
    }
}
?>
