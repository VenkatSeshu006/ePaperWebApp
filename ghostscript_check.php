<?php
/**
 * Ghostscript Configuration Check
 * Since we use Ghostscript (NOT ImageMagick) for PDF to image conversion
 */

echo "<h2>Ghostscript Configuration Check</h2>\n";

// Check if Ghostscript is available
$ghostscript_paths = [
    'C:\Program Files\gs\gs10.05.1\bin\gswin64c.exe',
    'C:\Program Files\gs\gs10.02.1\bin\gswin64c.exe',
    'C:\Program Files (x86)\gs\gs10.05.1\bin\gswin32c.exe',
    'C:\Program Files (x86)\gs\gs10.02.1\bin\gswin32c.exe',
    'gswin64c.exe', // If in PATH
    'gswin32c.exe', // If in PATH
    'gs' // Linux/Mac style
];

$gs_found = false;
$gs_path = '';

foreach ($ghostscript_paths as $path) {
    if (file_exists($path)) {
        $gs_found = true;
        $gs_path = $path;
        break;
    } else {
        // Try to execute to see if it's in PATH
        $output = shell_exec($path . ' --version 2>&1');
        if ($output && strpos($output, 'GPL Ghostscript') !== false) {
            $gs_found = true;
            $gs_path = $path;
            break;
        }
    }
}

if ($gs_found) {
    echo "<p style='color: green;'>✓ Ghostscript found: $gs_path</p>\n";
    
    // Get version info
    $version_output = shell_exec($gs_path . ' --version 2>&1');
    if ($version_output) {
        echo "<p>Version: " . trim($version_output) . "</p>\n";
    }
    
    // Test PDF to image conversion capability
    echo "<h3>Testing PDF to Image Conversion</h3>\n";
    
    $test_command = $gs_path . ' -dNOPAUSE -dBATCH -sDEVICE=jpeg -r150 -sOutputFile=test_output_%d.jpg -dFirstPage=1 -dLastPage=1 test.pdf 2>&1';
    echo "<p>Command syntax: <code>$test_command</code></p>\n";
    
} else {
    echo "<p style='color: red;'>❌ Ghostscript not found!</p>\n";
    echo "<p>Please install Ghostscript from: <a href='https://www.ghostscript.com/download/gsdnld.html' target='_blank'>https://www.ghostscript.com/download/gsdnld.html</a></p>\n";
}

// Check our conversion function
echo "<h3>ePaper CMS Conversion Setup</h3>\n";

$uploads_dir = __DIR__ . '/uploads';
if (is_dir($uploads_dir)) {
    echo "<p>✓ Uploads directory exists: $uploads_dir</p>\n";
    
    // Check for PDF files
    $pdf_files = glob($uploads_dir . '/*/*.pdf');
    if (!empty($pdf_files)) {
        echo "<p>✓ Found " . count($pdf_files) . " PDF files for processing</p>\n";
        
        // Check converted images
        $image_dirs = glob($uploads_dir . '/*/images');
        echo "<p>✓ Found " . count($image_dirs) . " image directories</p>\n";
    } else {
        echo "<p>ℹ No PDF files found in uploads directory</p>\n";
    }
} else {
    echo "<p style='color: orange;'>⚠ Uploads directory not found</p>\n";
}

// Configuration notes
echo "<h3>Configuration Notes</h3>\n";
echo "<ul>\n";
echo "<li><strong>ImageMagick:</strong> Disabled (commented out in php.ini)</li>\n";
echo "<li><strong>Ghostscript:</strong> Used for PDF to image conversion</li>\n";
echo "<li><strong>Command:</strong> External system calls via shell_exec()</li>\n";
echo "<li><strong>Format:</strong> Converting PDF pages to JPEG images</li>\n";
echo "<li><strong>Resolution:</strong> 150 DPI for web display</li>\n";
echo "</ul>\n";

echo "<p><strong>Summary:</strong> ePaper CMS uses Ghostscript for reliable PDF to image conversion without requiring PHP extensions.</p>\n";
?>
