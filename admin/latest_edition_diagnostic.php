<?php
/**
 * Latest Edition Diagnostic
 * Check if the latest edition has been properly processed
 */

require_once '../includes/database.php';

echo "=== LATEST EDITION DIAGNOSTIC ===\n";
echo str_repeat("-", 50) . "\n";

try {
    $conn = getConnection();
    
    // Check latest edition
    echo "1. CHECKING LATEST EDITION:\n";
    $latest = $conn->query("SELECT id, title, date, pdf_path, total_pages, status, created_at FROM editions ORDER BY created_at DESC LIMIT 1")->fetch();
    
    if ($latest) {
        echo "   ✅ ID: " . $latest['id'] . "\n";
        echo "   ✅ Title: " . $latest['title'] . "\n";
        echo "   ✅ Date: " . $latest['date'] . "\n";
        echo "   ✅ Status: " . $latest['status'] . "\n";
        echo "   ✅ PDF Path: " . $latest['pdf_path'] . "\n";
        echo "   ✅ Recorded Total Pages: " . $latest['total_pages'] . "\n";
        echo "   ✅ Created: " . $latest['created_at'] . "\n";
        
        // Check if PDF file exists
        $pdfPath = '../' . ltrim($latest['pdf_path'], '/');
        if (file_exists($pdfPath)) {
            echo "   ✅ PDF File: EXISTS\n";
        } else {
            echo "   ❌ PDF File: NOT FOUND at " . $pdfPath . "\n";
        }
        
        echo "\n2. CHECKING PAGE IMAGES:\n";
        // Check if it has page images
        $pagesResult = $conn->query("SELECT * FROM edition_pages WHERE edition_id = " . $latest['id'] . " ORDER BY page_number");
        $pages = $pagesResult->fetchAll();
        $pageCount = count($pages);
        
        echo "   📊 Page Images Found: " . $pageCount . "\n";
        
        if ($pageCount == 0) {
            echo "   ❌ ISSUE: No page images found for latest edition!\n";
            echo "   🔍 PDF processing may have failed or not started\n";
            
            // Check if PDF processor is available
            if (file_exists('../pdf_processor.php')) {
                echo "   ✅ PDFProcessor file exists\n";
                
                // Try to process now
                echo "\n3. ATTEMPTING MANUAL PROCESSING:\n";
                require_once '../pdf_processor.php';
                
                $processor = new PDFProcessor();
                $result = $processor->processPDF($pdfPath, $latest['id']);
                
                if ($result) {
                    echo "   ✅ SUCCESS: PDF processed successfully!\n";
                    
                    // Recheck pages
                    $newPages = $conn->query("SELECT COUNT(*) as count FROM edition_pages WHERE edition_id = " . $latest['id'])->fetch();
                    echo "   ✅ New Page Count: " . $newPages['count'] . "\n";
                } else {
                    echo "   ❌ FAILED: Could not process PDF\n";
                }
            } else {
                echo "   ❌ PDFProcessor file missing\n";
            }
        } else {
            echo "   ✅ SUCCESS: Page images are available\n";
            echo "\n   📋 PAGE DETAILS:\n";
            foreach ($pages as $page) {
                $imagePath = '../' . ltrim($page['image_path'], '/');
                $exists = file_exists($imagePath) ? "EXISTS" : "MISSING";
                echo "      Page " . $page['page_number'] . ": " . $exists . " (" . $page['image_path'] . ")\n";
            }
        }
        
    } else {
        echo "   ❌ No editions found in database\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 50) . "\n";
echo "Diagnostic complete\n";
?>
