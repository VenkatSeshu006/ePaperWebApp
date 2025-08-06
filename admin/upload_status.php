<?php
/**
 * Upload System Status
 * Quick verification that upload and PDF processing is working
 */

session_start();
define('ADMIN_PAGE', true);

// Include configuration
require_once '../config.php';

// Simple check - no authentication required for status
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload System Status</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status-item { display: flex; align-items: center; margin: 10px 0; padding: 10px; border-radius: 5px; }
        .status-ok { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-warning { background: #fff3cd; color: #856404; }
        .icon { margin-right: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📤 Upload System Status</h1>
        <p>This page shows whether the PDF upload and processing system is ready for new editions.</p>
        
        <?php
        $allGood = true;
        
        // Check GHOSTSCRIPT_COMMAND
        if (defined('GHOSTSCRIPT_COMMAND')) {
            if (file_exists(GHOSTSCRIPT_COMMAND)) {
                echo '<div class="status-item status-ok"><span class="icon">✅</span>Ghostscript is properly configured and available</div>';
            } else {
                echo '<div class="status-item status-error"><span class="icon">❌</span>Ghostscript configured but executable not found</div>';
                $allGood = false;
            }
        } else {
            echo '<div class="status-item status-error"><span class="icon">❌</span>GHOSTSCRIPT_COMMAND not defined in config</div>';
            $allGood = false;
        }
        
        // Check PDFProcessor
        try {
            require_once '../pdf_processor.php';
            $processor = new PDFProcessor();
            echo '<div class="status-item status-ok"><span class="icon">✅</span>PDFProcessor class is available and ready</div>';
        } catch (Exception $e) {
            echo '<div class="status-item status-error"><span class="icon">❌</span>PDFProcessor error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $allGood = false;
        }
        
        // Check database
        try {
            require_once '../includes/database.php';
            $conn = getConnection();
            echo '<div class="status-item status-ok"><span class="icon">✅</span>Database connection is working</div>';
            
            // Check required tables
            $editions = $conn->query("DESCRIBE editions");
            $pages = $conn->query("DESCRIBE edition_pages");
            if ($editions && $pages) {
                echo '<div class="status-item status-ok"><span class="icon">✅</span>Database tables are ready</div>';
            } else {
                echo '<div class="status-item status-error"><span class="icon">❌</span>Required database tables missing</div>';
                $allGood = false;
            }
        } catch (Exception $e) {
            echo '<div class="status-item status-error"><span class="icon">❌</span>Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $allGood = false;
        }
        
        // Check upload directory
        $uploadDir = '.uploads/';
        if (is_dir($uploadDir) && is_writable($uploadDir)) {
            echo '<div class="status-item status-ok"><span class="icon">✅</span>Upload directory is writable</div>';
        } else {
            echo '<div class="status-item status-error"><span class="icon">❌</span>Upload directory is not writable</div>';
            $allGood = false;
        }
        
        // Check upload integration
        if (file_exists('upload.php')) {
            $uploadContent = file_get_contents('upload.php');
            if (strpos($uploadContent, 'PDFProcessor') !== false) {
                echo '<div class="status-item status-ok"><span class="icon">✅</span>Upload integration is active</div>';
            } else {
                echo '<div class="status-item status-warning"><span class="icon">⚠️</span>Upload integration may not be active</div>';
            }
        } else {
            echo '<div class="status-item status-error"><span class="icon">❌</span>Upload.php file not found</div>';
            $allGood = false;
        }
        ?>
        
        <hr style="margin: 30px 0;">
        
        <?php if ($allGood): ?>
        <div class="status-item status-ok">
            <span class="icon">🎉</span>
            <div>
                <strong>All Systems Ready!</strong><br>
                You can now upload new PDF editions and they will be automatically processed into individual page images.
            </div>
        </div>
        <?php else: ?>
        <div class="status-item status-error">
            <span class="icon">🚨</span>
            <div>
                <strong>System Issues Detected</strong><br>
                Please resolve the issues above before uploading new editions.
            </div>
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="upload.php" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 5px;">
                📤 Go to Upload Page
            </a>
            <a href="../system_integrity_check.php" style="background: #6c757d; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 5px;">
                🔍 Detailed System Check
            </a>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: #e8f4f8; border-radius: 5px; font-size: 14px;">
            <strong>💡 What happens when you upload a PDF:</strong><br>
            1. PDF is saved to uploads directory<br>
            2. Edition record is created in database<br>
            3. <strong>Automatic:</strong> PDF is converted to individual page images<br>
            4. Page records are saved to database<br>
            5. Edition displays with page-by-page viewer and clipping tools
        </div>
    </div>
</body>
</html>
