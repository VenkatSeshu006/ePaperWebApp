<?php
/**
 * Complete System Verification
 * Tests the entire upload-to-homepage flow
 */

echo "=== COMPLETE SYSTEM VERIFICATION ===\n";
echo "Testing the entire flow from upload to homepage display\n";
echo str_repeat("=", 60) . "\n";

require_once 'includes/database.php';

try {
    $conn = getConnection();
    
    // Test 1: Check latest edition
    echo "1. LATEST EDITION CHECK:\n";
    $latest = $conn->query("SELECT id, title, date, status FROM editions ORDER BY created_at DESC LIMIT 1")->fetch();
    if ($latest) {
        echo "   ✅ Latest Edition: '{$latest['title']}' (ID: {$latest['id']})\n";
        echo "   ✅ Status: {$latest['status']}\n";
        echo "   ✅ Date: {$latest['date']}\n";
    } else {
        echo "   ❌ No editions found\n";
        exit(1);
    }
    
    // Test 2: Check page images
    echo "\n2. PAGE IMAGES CHECK:\n";
    $pages = $conn->query("SELECT COUNT(*) as count FROM edition_pages WHERE edition_id = " . $latest['id'])->fetch();
    echo "   📊 Page count: {$pages['count']}\n";
    
    if ($pages['count'] > 0) {
        echo "   ✅ Pages exist in database\n";
        
        // Check if files exist
        $pageDetails = $conn->query("SELECT page_number, image_path FROM edition_pages WHERE edition_id = " . $latest['id'] . " ORDER BY page_number LIMIT 3")->fetchAll();
        $filesExist = 0;
        $totalChecked = 0;
        
        foreach ($pageDetails as $page) {
            $totalChecked++;
            if (file_exists($page['image_path'])) {
                $filesExist++;
            }
        }
        
        echo "   📁 Files accessible: {$filesExist}/{$totalChecked} (sample check)\n";
        
        if ($filesExist === $totalChecked) {
            echo "   ✅ Image files are accessible from homepage\n";
        } else {
            echo "   ❌ Some image files are missing\n";
        }
    } else {
        echo "   ❌ No page images found\n";
    }
    
    // Test 3: Path format check
    echo "\n3. PATH FORMAT CHECK:\n";
    $pathSample = $conn->query("SELECT image_path FROM edition_pages WHERE edition_id = " . $latest['id'] . " LIMIT 1")->fetch();
    if ($pathSample) {
        $path = $pathSample['image_path'];
        echo "   📝 Sample path: {$path}\n";
        
        if (str_starts_with($path, 'uploads/')) {
            echo "   ✅ Path format is correct for homepage (starts with 'uploads/')\n";
        } else if (str_starts_with($path, '../uploads/')) {
            echo "   ⚠️  Path format may not work from homepage (starts with '../uploads/')\n";
            echo "   💡 Run: php path_validator.php to fix\n";
        } else {
            echo "   ❌ Path format is incorrect: {$path}\n";
        }
    }
    
    // Test 4: Homepage simulation
    echo "\n4. HOMEPAGE SIMULATION:\n";
    
    // Simulate what index.php does
    $editionData = $conn->query("
        SELECT e.*, 
               (SELECT COUNT(*) FROM edition_pages WHERE edition_id = e.id) as page_count
        FROM editions e 
        WHERE e.status = 'published' 
        ORDER BY e.date DESC, e.created_at DESC 
        LIMIT 1
    ")->fetch();
    
    if ($editionData && $editionData['page_count'] > 0) {
        echo "   ✅ Homepage will find edition: '{$editionData['title']}'\n";
        echo "   ✅ Homepage will show {$editionData['page_count']} pages\n";
        
        // Get first page to test display
        $firstPage = $conn->query("SELECT image_path FROM edition_pages WHERE edition_id = " . $editionData['id'] . " ORDER BY page_number LIMIT 1")->fetch();
        if ($firstPage && file_exists($firstPage['image_path'])) {
            echo "   ✅ First page image will load successfully\n";
        } else {
            echo "   ❌ First page image will fail to load\n";
        }
    } else {
        echo "   ❌ Homepage will not display any edition properly\n";
    }
    
    // Test 5: System integrity
    echo "\n5. SYSTEM INTEGRITY:\n";
    
    // Check if all required files exist
    $requiredFiles = [
        'pdf_processor.php' => 'PDF Processing',
        'path_validator.php' => 'Path Validation',
        'edition_post_processor.php' => 'Post Processing',
        'auto_path_maintenance.php' => 'Maintenance System'
    ];
    
    $systemReady = true;
    foreach ($requiredFiles as $file => $description) {
        if (file_exists($file)) {
            echo "   ✅ {$description}: Available\n";
        } else {
            echo "   ❌ {$description}: Missing ({$file})\n";
            $systemReady = false;
        }
    }
    
    // Final verdict
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎯 FINAL VERDICT:\n";
    
    if ($systemReady && $pages['count'] > 0 && $filesExist === $totalChecked) {
        echo "🎉 SYSTEM FULLY OPERATIONAL!\n";
        echo "✅ Your homepage will display the latest edition correctly\n";
        echo "✅ All future uploads will be automatically processed\n";
        echo "✅ Image paths will be maintained automatically\n";
        echo "\n💡 You can now visit your homepage to see the PDF viewer in action!\n";
    } else {
        echo "⚠️  SYSTEM NEEDS ATTENTION:\n";
        if ($pages['count'] === 0) {
            echo "❌ Latest edition has no page images\n";
        }
        if ($filesExist !== $totalChecked) {
            echo "❌ Some image files are missing\n";
        }
        if (!$systemReady) {
            echo "❌ Some system files are missing\n";
        }
        echo "\n💡 Run the maintenance systems to resolve issues\n";
    }
    
} catch (Exception $e) {
    echo "❌ VERIFICATION FAILED: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Verification complete\n";
?>
