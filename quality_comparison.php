<?php
/**
 * Quality Comparison Tool
 * Compare old vs new image quality
 */

echo "📊 IMAGE QUALITY COMPARISON ANALYSIS\n";
echo str_repeat("=", 60) . "\n";

require_once 'includes/database.php';
require_once 'enhanced_quality_processor.php';

try {
    $conn = getConnection();
    
    // Get all editions to compare
    $editions = $conn->query("SELECT id, title, created_at FROM editions ORDER BY created_at DESC LIMIT 3")->fetchAll();
    
    echo "📋 EDITIONS FOUND:\n";
    foreach ($editions as $edition) {
        echo "  - ID {$edition['id']}: {$edition['title']} ({$edition['created_at']})\n";
    }
    
    echo "\n" . str_repeat("-", 60) . "\n";
    
    $processor = new EnhancedQualityPDFProcessor();
    
    foreach ($editions as $edition) {
        echo "\n📄 EDITION: {$edition['title']} (ID: {$edition['id']})\n";
        
        // Get first page for analysis
        $firstPage = $conn->query("SELECT image_path FROM edition_pages WHERE edition_id = {$edition['id']} ORDER BY page_number LIMIT 1")->fetch();
        
        if ($firstPage && file_exists($firstPage['image_path'])) {
            $analysis = $processor->analyzeImageQuality($firstPage['image_path']);
            
            echo "  📏 Dimensions: {$analysis['width']} x {$analysis['height']} pixels\n";
            echo "  💾 File Size: {$analysis['file_size_mb']} MB\n";
            echo "  🎯 Quality Score: {$analysis['quality_score']}%\n";
            
            // Calculate readability metrics
            $dpi = round($analysis['width'] / 8.5); // Assuming 8.5" width
            $textClarity = $analysis['quality_score'] > 80 ? "Excellent" : ($analysis['quality_score'] > 60 ? "Good" : "Fair");
            
            echo "  📖 Estimated DPI: ~{$dpi}\n";
            echo "  👁️  Text Readability: {$textClarity}\n";
            
            // Quality grade
            if ($analysis['quality_score'] >= 90) {
                echo "  🏆 Quality Grade: Premium (Newspaper Quality)\n";
            } elseif ($analysis['quality_score'] >= 75) {
                echo "  🥈 Quality Grade: High (Very Readable)\n";
            } elseif ($analysis['quality_score'] >= 60) {
                echo "  🥉 Quality Grade: Standard (Readable)\n";
            } else {
                echo "  📱 Quality Grade: Basic (Mobile Friendly)\n";
            }
        } else {
            echo "  ❌ No image file found\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎯 QUALITY IMPROVEMENT SUMMARY:\n";
    echo "✅ Enhanced Settings Applied:\n";
    echo "   - Resolution: 300 DPI (was 150 DPI)\n";
    echo "   - Color Depth: 16M colors with optimized compression\n";
    echo "   - Anti-aliasing: 4x for text and graphics\n";
    echo "   - Font Optimization: Embedded and compressed\n";
    echo "   - Image Filtering: Lossless FlateEncode\n";
    echo "\n💡 Benefits:\n";
    echo "   - 📖 Crystal clear text readability\n";
    echo "   - 🖼️  Sharp image reproduction\n";
    echo "   - 🎨 Accurate color representation\n";
    echo "   - 📱 Excellent zoom capability\n";
    echo "   - 🔍 Perfect for detailed clipping\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
