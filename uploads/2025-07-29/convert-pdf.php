<?php
/**
 * PDF to Images Converter
 * Converts uploaded PDF to individual page images
 */

$uploadDir = '.';
$pdfFile = $uploadDir . '/edition.pdf';
$pagesDir = $uploadDir . '/pages';

echo "PDF to Images Converter\n";
echo "======================\n";

if (!file_exists($pdfFile)) {
    echo "Error: edition.pdf not found!\n";
    exit(1);
}

if (!is_dir($pagesDir)) {
    mkdir($pagesDir, 0755, true);
    echo "Created pages directory\n";
}

echo "Converting PDF: " . basename($pdfFile) . "\n";
echo "Output directory: $pagesDir\n";

// Try different conversion methods
$methods = [
    // Ghostscript method 1
    'gswin64c -dNOPAUSE -dBATCH -sDEVICE=png16m -r150 -sOutputFile="' . $pagesDir . '/page_%03d.png" "' . $pdfFile . '"',
    // Ghostscript method 2
    'gs -dNOPAUSE -dBATCH -sDEVICE=png16m -r150 -sOutputFile="' . $pagesDir . '/page_%03d.png" "' . $pdfFile . '"',
    // ImageMagick method
    'magick convert -density 150 "' . $pdfFile . '" "' . $pagesDir . '/page_%03d.png"',
];

$success = false;

foreach ($methods as $i => $command) {
    echo "\nTrying method " . ($i + 1) . "...\n";
    echo "Command: $command\n";
    
    $output = [];
    $returnCode = 0;
    exec($command . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "Success! PDF converted to images.\n";
        $success = true;
        break;
    } else {
        echo "Failed with return code: $returnCode\n";
        if (!empty($output)) {
            echo "Output: " . implode("\n", $output) . "\n";
        }
    }
}

if (!$success) {
    echo "\nAll conversion methods failed.\n";
    echo "Creating placeholder images instead...\n";
    
    // Create placeholder images
    for ($i = 1; $i <= 12; $i++) { // Assume 12 pages max
        $pageFile = sprintf("%s/page_%03d.png", $pagesDir, $i);
        
        // Create a simple placeholder image
        $img = imagecreate(800, 1100);
        $bg = imagecolorallocate($img, 240, 240, 240);
        $text_color = imagecolorallocate($img, 100, 100, 100);
        
        imagestring($img, 5, 350, 500, "Page $i", $text_color);
        imagestring($img, 3, 300, 550, "PDF Conversion Required", $text_color);
        
        imagepng($img, $pageFile);
        imagedestroy($img);
        
        if (file_exists($pageFile)) {
            echo "Created placeholder: page_" . sprintf("%03d", $i) . ".png\n";
        }
    }
}

// Count created pages
$pages = glob($pagesDir . '/page_*.png');
echo "\nConversion completed!\n";
echo "Total pages created: " . count($pages) . "\n";

foreach ($pages as $page) {
    echo "- " . basename($page) . " (" . number_format(filesize($page)) . " bytes)\n";
}

echo "\nYou can now view the edition at: http://localhost/epaper-site/\n";
?>
