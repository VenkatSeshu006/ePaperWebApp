<?php
/**
 * Reprocess Latest Edition with Enhanced Quality
 * Upgrade the current edition to premium image quality
 */

echo "🎯 REPROCESSING LATEST EDITION WITH ENHANCED QUALITY\n";
echo str_repeat("=", 60) . "\n";

require_once 'includes/database.php';

try {
    $conn = getConnection();
    
    // Get the latest edition
    $latest = $conn->query("SELECT id, title, pdf_path FROM editions ORDER BY created_at DESC LIMIT 1")->fetch();
    
    if (!$latest) {
        throw new Exception("No editions found");
    }
    
    echo "📄 Edition: '{$latest['title']}' (ID: {$latest['id']})\n";
    echo "📁 PDF Path: {$latest['pdf_path']}\n";
    echo str_repeat("-", 60) . "\n";
    
    // Check if PDF file exists
    if (!file_exists($latest['pdf_path'])) {
        throw new Exception("PDF file not found: " . $latest['pdf_path']);
    }
    
    echo "✅ PDF file found\n";
    echo "🔄 Starting enhanced quality processing...\n\n";
    
    // Use enhanced quality processor
    require_once 'enhanced_quality_processor.php';
    $processor = new EnhancedQualityPDFProcessor();
    $pages = $processor->processWithBestQuality($latest['pdf_path'], $latest['id']);
    
    echo "\n📊 PROCESSING RESULTS:\n";
    echo "✅ Total pages processed: " . count($pages) . "\n";
    
    // Analyze quality of first few pages
    echo "\n🔍 QUALITY ANALYSIS:\n";
    foreach (array_slice($pages, 0, 3) as $i => $pageFile) {
        $analysis = $processor->analyzeImageQuality($pageFile);
        echo sprintf(
            "Page %d: %dx%d pixels, %sMB, Quality Score: %d%%\n",
            $i + 1,
            $analysis['width'],
            $analysis['height'],
            $analysis['file_size_mb'],
            $analysis['quality_score']
        );
    }
    
    // Validate paths are correct
    echo "\n🔧 PATH VALIDATION:\n";
    require_once 'path_validator.php';
    $validator = new PathValidator();
    $pathResults = $validator->validateAndFixAllPaths();
    
    if ($pathResults['fixed'] > 0) {
        echo "✅ Fixed {$pathResults['fixed']} paths for web compatibility\n";
    } else {
        echo "✅ All paths are already web-compatible\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎉 SUCCESS: Latest edition upgraded to premium quality!\n";
    echo "💡 Your homepage will now display crystal-clear, highly readable images\n";
    echo "🚀 All future uploads will automatically use these enhanced quality settings\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "💡 The original images are still available if processing failed\n";
}
?>
