<?php
/**
 * Test Home Page Latest Edition Display
 */
require_once 'config/config.php';
require_once 'includes/database.php';

echo "<h2>Testing Home Page Fix</h2>\n";

try {
    // Simulate what index.php does
    $conn = getConnection();
    
    // Get the latest published edition
    $sql = "SELECT id, title, date, thumbnail_path FROM editions WHERE status = 'published' ORDER BY date DESC, created_at DESC LIMIT 1";
    $result = $conn->query($sql);
    $latest = $result ? $result->fetch() : null;
    
    // Get edition ID (like index.php does)
    $editionId = $latest ? $latest['id'] : null;
    
    echo "<h3>Step 1: Latest Edition Detection</h3>\n";
    if ($latest) {
        echo "<p style='color: green;'>✅ Latest edition found: <strong>" . htmlspecialchars($latest['title']) . "</strong> (ID: {$latest['id']})</p>\n";
    } else {
        echo "<p style='color: red;'>❌ No published editions found</p>\n";
        exit;
    }
    
    // Get current edition (like index.php does)
    if ($editionId) {
        $stmt = $conn->prepare("SELECT * FROM editions WHERE id = ? AND status = 'published'");
        $stmt->execute([$editionId]);
        $currentEdition = $stmt->fetch();
        
        echo "<h3>Step 2: Current Edition Retrieval</h3>\n";
        if ($currentEdition) {
            echo "<p style='color: green;'>✅ Current edition retrieved successfully</p>\n";
            echo "<ul>\n";
            echo "<li><strong>ID:</strong> " . $currentEdition['id'] . "</li>\n";
            echo "<li><strong>Title:</strong> " . htmlspecialchars($currentEdition['title']) . "</li>\n";
            echo "<li><strong>PDF Path:</strong> " . ($currentEdition['pdf_path'] ?: 'None') . "</li>\n";
            echo "<li><strong>Status:</strong> " . $currentEdition['status'] . "</li>\n";
            echo "</ul>\n";
        } else {
            echo "<p style='color: red;'>❌ Failed to retrieve current edition</p>\n";
            exit;
        }
        
        // Check for pages (old system)
        $pagesResult = $conn->query("SELECT * FROM pages WHERE edition_id = $editionId ORDER BY page_number");
        $editionPages = [];
        if ($pagesResult) {
            while ($pageRow = $pagesResult->fetch()) {
                $editionPages[] = $pageRow;
            }
        }
        
        echo "<h3>Step 3: Pages Check</h3>\n";
        if (!empty($editionPages)) {
            echo "<p style='color: green;'>✅ Edition has " . count($editionPages) . " individual pages (image-based system)</p>\n";
            echo "<p>→ Will use image-based viewer with sidebar</p>\n";
        } else {
            echo "<p style='color: orange;'>📄 No individual pages found (PDF-based system)</p>\n";
            echo "<p>→ Will use PDF viewer display</p>\n";
        }
        
        // Check PDF file
        echo "<h3>Step 4: PDF File Check</h3>\n";
        if ($currentEdition['pdf_path']) {
            $pdfPath = $currentEdition['pdf_path'];
            echo "<p><strong>PDF Path:</strong> $pdfPath</p>\n";
            
            if (file_exists($pdfPath)) {
                echo "<p style='color: green;'>✅ PDF file exists and can be displayed</p>\n";
            } else {
                echo "<p style='color: red;'>❌ PDF file not found at specified path</p>\n";
                echo "<p><strong>Looking for:</strong> " . realpath('.') . "/$pdfPath</p>\n";
            }
        } else {
            echo "<p style='color: red;'>❌ No PDF path specified for this edition</p>\n";
        }
        
        echo "<h3>✅ Expected Result</h3>\n";
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>\n";
        if (!empty($editionPages)) {
            echo "<p><strong>Image-based Display:</strong> Edition will show with sidebar navigation and individual page images</p>\n";
        } else if ($currentEdition['pdf_path'] && file_exists($currentEdition['pdf_path'])) {
            echo "<p><strong>PDF Display:</strong> Edition will show with embedded PDF viewer</p>\n";
        } else {
            echo "<p><strong>Fallback Display:</strong> Edition title and info will show, but PDF content may not display properly</p>\n";
        }
        echo "<p>Instead of 'No Edition Available', users will see the edition content!</p>\n";
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
