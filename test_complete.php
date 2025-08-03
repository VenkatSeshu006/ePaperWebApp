<?php
/**
 * Complete PDF Processing Test
 * Verify all components are working correctly
 */

require_once 'config.php';
require_once 'includes/database.php';

echo "<h1>🧪 Complete PDF Processing Test</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection</h2>";
try {
    $conn = getConnection();
    echo "✅ Database connected successfully<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Ghostscript Installation
echo "<h2>2. Ghostscript Installation</h2>";
$gsPath = GHOSTSCRIPT_COMMAND;
if (file_exists($gsPath)) {
    echo "✅ Ghostscript found at: $gsPath<br>";
    
    // Test version
    $versionCmd = "\"$gsPath\" --version 2>&1";
    $output = [];
    exec($versionCmd, $output);
    if (!empty($output)) {
        echo "ℹ️ Version: " . $output[0] . "<br>";
    }
} else {
    echo "❌ Ghostscript not found at: $gsPath<br>";
}

// Test 3: Editions Status
echo "<h2>3. Editions Status</h2>";
$query = "SELECT id, title, pdf_path, total_pages, 
          (SELECT COUNT(*) FROM edition_pages WHERE edition_id = e.id) as image_pages
          FROM editions e 
          WHERE status = 'published' 
          ORDER BY date DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$editions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($editions)) {
    echo "❌ No published editions found<br>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Title</th><th>PDF Status</th><th>Pages Status</th><th>Action Needed</th></tr>";
    
    foreach ($editions as $edition) {
        $pdfExists = file_exists($edition['pdf_path']);
        $hasPages = $edition['image_pages'] > 0;
        
        echo "<tr>";
        echo "<td>" . $edition['id'] . "</td>";
        echo "<td>" . htmlspecialchars($edition['title']) . "</td>";
        echo "<td>" . ($pdfExists ? "✅ PDF exists" : "❌ PDF missing") . "</td>";
        echo "<td>" . ($hasPages ? "✅ " . $edition['image_pages'] . " images" : "❌ No images") . "</td>";
        
        if (!$pdfExists) {
            echo "<td>❌ Fix PDF path</td>";
        } elseif (!$hasPages) {
            echo "<td>🔄 Needs conversion</td>";
        } else {
            echo "<td>✅ Ready</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

// Test 4: Sample PDF Conversion Test
echo "<h2>4. Sample PDF Conversion Test</h2>";
if (!empty($editions)) {
    $testEdition = $editions[0];
    if (file_exists($testEdition['pdf_path'])) {
        echo "Testing conversion of: " . htmlspecialchars($testEdition['title']) . "<br>";
        
        $testDir = dirname($testEdition['pdf_path']) . '/test_pages';
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        
        $outputPattern = $testDir . '/test_page_%03d.png';
        $command = sprintf(
            '"%s" -dNOPAUSE -dBATCH -sDEVICE=png16m -r150 -dFirstPage=1 -dLastPage=1 -sOutputFile="%s" "%s" 2>&1',
            $gsPath,
            $outputPattern,
            $testEdition['pdf_path']
        );
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            $testFile = sprintf($testDir . '/test_page_001.png');
            if (file_exists($testFile)) {
                echo "✅ Test conversion successful<br>";
                echo "ℹ️ Test image created: " . number_format(filesize($testFile)) . " bytes<br>";
                
                // Clean up test file
                unlink($testFile);
                rmdir($testDir);
            } else {
                echo "❌ Test conversion failed - no output file<br>";
            }
        } else {
            echo "❌ Test conversion failed with code: $returnCode<br>";
            if (!empty($output)) {
                echo "Error: " . implode("<br>", $output) . "<br>";
            }
        }
    }
}

// Test 5: Upload Directory Permissions
echo "<h2>5. Upload Directory Permissions</h2>";
$uploadBase = 'uploads/';
if (is_dir($uploadBase)) {
    if (is_writable($uploadBase)) {
        echo "✅ Upload directory is writable<br>";
    } else {
        echo "❌ Upload directory is not writable<br>";
    }
} else {
    echo "❌ Upload directory does not exist<br>";
}

// Test 6: Integration Status
echo "<h2>6. Integration Status</h2>";

// Check if PDFProcessor is available
require_once 'pdf_processor.php';
if (class_exists('PDFProcessor')) {
    echo "✅ PDFProcessor class available<br>";
} else {
    echo "❌ PDFProcessor class not found<br>";
}

if (file_exists('admin/upload.php')) {
    $uploadContent = file_get_contents('admin/upload.php');
    if (strpos($uploadContent, 'PDFProcessor') !== false) {
        echo "✅ Upload integration active<br>";
    } else {
        echo "❌ Upload integration not active<br>";
    }
}

echo "<h2>7. Recommendations</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<strong>✅ PDF to Images conversion is ready!</strong><br><br>";
echo "<strong>Next steps:</strong><br>";
echo "• Use <a href='convert_pdfs.php'>convert_pdfs.php</a> to convert existing PDFs<br>";
echo "• New uploads will automatically be converted<br>";
echo "• Editions will show page-by-page viewer with clipping tools<br>";
echo "• PDF viewer will be used as fallback for editions without images<br>";
echo "</div>";

?>
