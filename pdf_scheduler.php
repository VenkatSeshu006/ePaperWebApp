<?php
/**
 * PDF Processing Scheduler
 * Can be called via cron job or manually to ensure continuous processing
 */

require_once 'config.php';
require_once 'includes/database.php';

// Prevent direct web access
if (isset($_SERVER['HTTP_HOST']) && !isset($_GET['force'])) {
    die('This script should be run from command line or with ?force parameter');
}

class PDFProcessingScheduler {
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/logs/pdf_processing.log';
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function run() {
        $this->log("=== PDF Processing Scheduler Started ===");
        
        try {
            // 1. Process unprocessed PDFs
            $this->log("Running auto PDF processor...");
            require_once 'auto_pdf_processor.php';
            $processor = new AutoPDFProcessor();
            $results = $processor->processUnprocessedEditions();
            
            if (!empty($results)) {
                foreach ($results as $result) {
                    if ($result['status'] === 'success') {
                        $this->log("✅ Processed edition {$result['id']}: {$result['pages']} pages");
                    } else {
                        $this->log("❌ Failed edition {$result['id']}: {$result['message']}");
                    }
                }
            } else {
                $this->log("ℹ️ No editions needed processing");
            }
            
            // 2. Validate page counts
            $this->log("Validating page counts...");
            $validation = $processor->validateProcessedEditions();
            
            if (!empty($validation)) {
                foreach ($validation as $fix) {
                    $this->log("🔧 Fixed page count for edition {$fix['id']}: {$fix['old_count']} → {$fix['new_count']}");
                }
            }
            
            // 3. Clean up old logs (keep last 30 days)
            $this->cleanupLogs();
            
            $this->log("=== PDF Processing Scheduler Completed Successfully ===");
            return true;
            
        } catch (Exception $e) {
            $this->log("❌ ERROR: " . $e->getMessage());
            $this->log("=== PDF Processing Scheduler Failed ===");
            return false;
        }
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;
        
        // Write to log file
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also output to console if running from CLI
        if (php_sapi_name() === 'cli') {
            echo $logEntry;
        }
    }
    
    private function cleanupLogs() {
        $logDir = dirname($this->logFile);
        $oldLogs = glob($logDir . '/pdf_processing_*.log');
        
        foreach ($oldLogs as $logFile) {
            if (filemtime($logFile) < strtotime('-30 days')) {
                unlink($logFile);
                $this->log("🗑️ Cleaned up old log: " . basename($logFile));
            }
        }
    }
}

// Run the scheduler
$scheduler = new PDFProcessingScheduler();
$success = $scheduler->run();

// If running from web, show results
if (isset($_SERVER['HTTP_HOST'])) {
    echo "<h1>📅 PDF Processing Scheduler</h1>";
    echo "<p>Status: " . ($success ? "✅ Completed Successfully" : "❌ Failed") . "</p>";
    echo "<h2>Recent Log Entries</h2>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;'>";
    
    $logFile = __DIR__ . '/logs/pdf_processing.log';
    if (file_exists($logFile)) {
        $logLines = file($logFile);
        $recentLines = array_slice($logLines, -50); // Show last 50 lines
        echo htmlspecialchars(implode('', $recentLines));
    } else {
        echo "No log file found.";
    }
    echo "</pre>";
    
    echo "<p><small>This scheduler can be run automatically via cron job: <code>*/15 * * * * php " . __FILE__ . "</code></small></p>";
}

exit($success ? 0 : 1);
?>
