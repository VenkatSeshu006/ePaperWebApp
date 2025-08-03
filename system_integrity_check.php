<?php
/**
 * System Integrity Checker
 * Ensures PDF processing system is working correctly for all editions
 */

require_once 'config.php';
require_once 'includes/database.php';

class SystemIntegrityChecker {
    private $db;
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    /**
     * Comprehensive system check
     */
    public function runFullCheck() {
        $results = [
            'ghostscript' => $this->checkGhostscript(),
            'database' => $this->checkDatabaseStructure(),
            'uploads' => $this->checkUploadDirectory(),
            'editions' => $this->checkEditions(),
            'integration' => $this->checkIntegration(),
            'recommendations' => []
        ];
        
        // Generate recommendations
        $results['recommendations'] = $this->generateRecommendations($results);
        
        return $results;
    }
    
    private function checkGhostscript() {
        $gsPath = GHOSTSCRIPT_COMMAND;
        $result = [
            'status' => 'error',
            'path' => $gsPath,
            'version' => null,
            'working' => false
        ];
        
        if (file_exists($gsPath)) {
            $result['status'] = 'success';
            
            // Test version
            $versionCmd = "\"$gsPath\" --version 2>&1";
            $output = [];
            exec($versionCmd, $output);
            if (!empty($output)) {
                $result['version'] = $output[0];
            }
            
            // Test conversion
            $testCmd = "\"$gsPath\" -dNOPAUSE -dBATCH -sDEVICE=png16m -r72 -dFirstPage=1 -dLastPage=1 -sOutputFile=nul: -c \"showpage\" 2>&1";
            $testOutput = [];
            $returnCode = 0;
            exec($testCmd, $testOutput, $returnCode);
            $result['working'] = ($returnCode === 0);
        }
        
        return $result;
    }
    
    private function checkDatabaseStructure() {
        $result = [
            'editions_table' => false,
            'edition_pages_table' => false,
            'required_columns' => []
        ];
        
        try {
            // Check editions table
            $editionsResult = $this->db->query("DESCRIBE editions");
            if ($editionsResult) {
                $result['editions_table'] = true;
                $columns = [];
                while ($row = $editionsResult->fetch()) {
                    $columns[] = $row['Field'];
                }
                $result['required_columns']['editions'] = $columns;
            }
            
            // Check edition_pages table
            $pagesResult = $this->db->query("DESCRIBE edition_pages");
            if ($pagesResult) {
                $result['edition_pages_table'] = true;
                $columns = [];
                while ($row = $pagesResult->fetch()) {
                    $columns[] = $row['Field'];
                }
                $result['required_columns']['edition_pages'] = $columns;
            }
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
        
        return $result;
    }
    
    private function checkUploadDirectory() {
        $result = [
            'exists' => false,
            'writable' => false,
            'recent_directories' => []
        ];
        
        $uploadBase = 'uploads/';
        if (is_dir($uploadBase)) {
            $result['exists'] = true;
            $result['writable'] = is_writable($uploadBase);
            
            // Check recent upload directories
            $dirs = glob($uploadBase . '20*', GLOB_ONLYDIR);
            rsort($dirs);
            $result['recent_directories'] = array_slice($dirs, 0, 5);
        }
        
        return $result;
    }
    
    private function checkEditions() {
        $result = [
            'total_editions' => 0,
            'published_editions' => 0,
            'with_pdfs' => 0,
            'with_pages' => 0,
            'needs_processing' => 0,
            'details' => []
        ];
        
        // Get edition statistics
        $query = "
            SELECT 
                e.id, e.title, e.status, e.pdf_path, e.total_pages,
                COUNT(ep.id) as actual_pages,
                CASE 
                    WHEN e.pdf_path IS NOT NULL AND e.pdf_path != '' THEN 1 
                    ELSE 0 
                END as has_pdf,
                CASE 
                    WHEN COUNT(ep.id) > 0 THEN 1 
                    ELSE 0 
                END as has_pages
            FROM editions e 
            LEFT JOIN edition_pages ep ON e.id = ep.edition_id 
            GROUP BY e.id
            ORDER BY e.created_at DESC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $editions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($editions as $edition) {
            $result['total_editions']++;
            
            if ($edition['status'] === 'published') {
                $result['published_editions']++;
            }
            
            if ($edition['has_pdf']) {
                $result['with_pdfs']++;
            }
            
            if ($edition['has_pages']) {
                $result['with_pages']++;
            }
            
            // Check if needs processing
            $needsProcessing = ($edition['has_pdf'] && !$edition['has_pages']);
            if ($needsProcessing) {
                $result['needs_processing']++;
                $result['details'][] = [
                    'id' => $edition['id'],
                    'title' => $edition['title'],
                    'issue' => 'needs_processing'
                ];
            }
            
            // Check for mismatched page counts
            if ($edition['has_pages'] && $edition['total_pages'] != $edition['actual_pages']) {
                $result['details'][] = [
                    'id' => $edition['id'],
                    'title' => $edition['title'],
                    'issue' => 'page_count_mismatch',
                    'expected' => $edition['total_pages'],
                    'actual' => $edition['actual_pages']
                ];
            }
        }
        
        return $result;
    }
    
    private function checkIntegration() {
        $result = [
            'pdf_processor_exists' => false,
            'upload_integration' => false,
            'auto_processor_exists' => false
        ];
        
        // Check if PDFProcessor class exists
        if (file_exists('pdf_processor.php')) {
            require_once 'pdf_processor.php';
            $result['pdf_processor_exists'] = class_exists('PDFProcessor');
        }
        
        // Check upload integration
        if (file_exists('admin/upload.php')) {
            $uploadContent = file_get_contents('admin/upload.php');
            $result['upload_integration'] = (strpos($uploadContent, 'PDFProcessor') !== false);
        }
        
        // Check auto processor
        $result['auto_processor_exists'] = file_exists('auto_pdf_processor.php');
        
        return $result;
    }
    
    private function generateRecommendations($results) {
        $recommendations = [];
        
        // Ghostscript recommendations
        if ($results['ghostscript']['status'] !== 'success') {
            $recommendations[] = [
                'type' => 'critical',
                'message' => 'Ghostscript is not properly installed or configured',
                'action' => 'Install Ghostscript and update GHOSTSCRIPT_COMMAND in config.php'
            ];
        } elseif (!$results['ghostscript']['working']) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Ghostscript found but conversion test failed',
                'action' => 'Check Ghostscript installation and permissions'
            ];
        }
        
        // Database recommendations
        if (!$results['database']['editions_table'] || !$results['database']['edition_pages_table']) {
            $recommendations[] = [
                'type' => 'critical',
                'message' => 'Required database tables are missing',
                'action' => 'Run database setup script'
            ];
        }
        
        // Upload directory recommendations
        if (!$results['uploads']['exists']) {
            $recommendations[] = [
                'type' => 'critical',
                'message' => 'Upload directory does not exist',
                'action' => 'Create uploads/ directory with proper permissions'
            ];
        } elseif (!$results['uploads']['writable']) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Upload directory is not writable',
                'action' => 'Set proper write permissions on uploads/ directory'
            ];
        }
        
        // Edition processing recommendations
        if ($results['editions']['needs_processing'] > 0) {
            $recommendations[] = [
                'type' => 'info',
                'message' => $results['editions']['needs_processing'] . ' editions need PDF processing',
                'action' => 'Run auto_pdf_processor.php or convert_pdfs.php'
            ];
        }
        
        // Integration recommendations
        if (!$results['integration']['pdf_processor_exists']) {
            $recommendations[] = [
                'type' => 'critical',
                'message' => 'PDFProcessor class not found',
                'action' => 'Ensure pdf_processor.php exists and is properly configured'
            ];
        }
        
        if (!$results['integration']['upload_integration']) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Upload integration is not active',
                'action' => 'Update admin/upload.php to include PDF processing'
            ];
        }
        
        return $recommendations;
    }
}

// If called directly, run the integrity check
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $checker = new SystemIntegrityChecker();
    $results = $checker->runFullCheck();
    
    echo "<h1>🔍 System Integrity Check</h1>";
    
    // Ghostscript Status
    echo "<h2>Ghostscript Status</h2>";
    if ($results['ghostscript']['status'] === 'success') {
        echo "✅ Ghostscript working properly<br>";
        echo "📍 Path: " . htmlspecialchars($results['ghostscript']['path']) . "<br>";
        if ($results['ghostscript']['version']) {
            echo "🔢 Version: " . htmlspecialchars($results['ghostscript']['version']) . "<br>";
        }
        echo "🧪 Conversion test: " . ($results['ghostscript']['working'] ? "✅ Passed" : "❌ Failed") . "<br>";
    } else {
        echo "❌ Ghostscript not properly configured<br>";
    }
    
    // Database Status
    echo "<h2>Database Status</h2>";
    echo "Editions table: " . ($results['database']['editions_table'] ? "✅ Exists" : "❌ Missing") . "<br>";
    echo "Edition pages table: " . ($results['database']['edition_pages_table'] ? "✅ Exists" : "❌ Missing") . "<br>";
    
    // Editions Summary
    echo "<h2>Editions Summary</h2>";
    echo "Total editions: " . $results['editions']['total_editions'] . "<br>";
    echo "Published editions: " . $results['editions']['published_editions'] . "<br>";
    echo "With PDF files: " . $results['editions']['with_pdfs'] . "<br>";
    echo "With page images: " . $results['editions']['with_pages'] . "<br>";
    echo "Needs processing: " . ($results['editions']['needs_processing'] > 0 ? 
        "🔄 " . $results['editions']['needs_processing'] : "✅ 0") . "<br>";
    
    // Integration Status
    echo "<h2>Integration Status</h2>";
    echo "PDFProcessor class: " . ($results['integration']['pdf_processor_exists'] ? "✅ Available" : "❌ Missing") . "<br>";
    echo "Upload integration: " . ($results['integration']['upload_integration'] ? "✅ Active" : "❌ Inactive") . "<br>";
    echo "Auto processor: " . ($results['integration']['auto_processor_exists'] ? "✅ Available" : "❌ Missing") . "<br>";
    
    // Recommendations
    if (!empty($results['recommendations'])) {
        echo "<h2>Recommendations</h2>";
        foreach ($results['recommendations'] as $rec) {
            $icon = $rec['type'] === 'critical' ? '🚨' : ($rec['type'] === 'warning' ? '⚠️' : 'ℹ️');
            $color = $rec['type'] === 'critical' ? 'red' : ($rec['type'] === 'warning' ? 'orange' : 'blue');
            
            echo "<div style='border-left: 4px solid $color; padding: 10px; margin: 10px 0; background: #f9f9f9;'>";
            echo "<strong>$icon " . ucfirst($rec['type']) . ":</strong> " . htmlspecialchars($rec['message']) . "<br>";
            echo "<em>Action:</em> " . htmlspecialchars($rec['action']);
            echo "</div>";
        }
    } else {
        echo "<h2>✅ All Systems Operational</h2>";
        echo "<p>No issues detected. PDF processing system is fully functional!</p>";
    }
}
?>
