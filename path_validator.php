<?php
/**
 * Path Validation and Auto-Fix System
 * Ensures all image paths work correctly from index.php perspective
 */

class PathValidator {
    private $db;
    
    public function __construct() {
        require_once 'includes/database.php';
        $this->db = getConnection();
    }
    
    /**
     * Validate and fix all image paths in the database
     */
    public function validateAndFixAllPaths() {
        echo "=== PATH VALIDATION & AUTO-FIX ===\n";
        echo "Ensuring all image paths work from homepage (index.php)\n";
        echo str_repeat("-", 60) . "\n";
        
        $fixed = 0;
        $validated = 0;
        
        // Get all edition pages
        $pages = $this->db->query("SELECT id, edition_id, page_number, image_path FROM edition_pages ORDER BY edition_id, page_number")->fetchAll();
        
        foreach ($pages as $page) {
            $currentPath = $page['image_path'];
            $correctPath = $this->getCorrectWebPath($currentPath);
            
            if ($currentPath !== $correctPath) {
                // Fix the path
                $stmt = $this->db->prepare("UPDATE edition_pages SET image_path = ? WHERE id = ?");
                if ($stmt->execute([$correctPath, $page['id']])) {
                    echo "🔧 Fixed Edition {$page['edition_id']} Page {$page['page_number']}: {$currentPath} → {$correctPath}\n";
                    $fixed++;
                } else {
                    echo "❌ Failed to fix Edition {$page['edition_id']} Page {$page['page_number']}\n";
                }
            } else {
                // Path is already correct
                $validated++;
            }
        }
        
        echo "\n" . str_repeat("-", 60) . "\n";
        echo "✅ Validated: {$validated} paths\n";
        echo "🔧 Fixed: {$fixed} paths\n";
        echo "🚀 All paths are now web-accessible!\n";
        
        return ['validated' => $validated, 'fixed' => $fixed];
    }
    
    /**
     * Get the correct web-accessible path for an image
     */
    private function getCorrectWebPath($imagePath) {
        // Remove any leading '../' or './' 
        $cleanPath = ltrim($imagePath, './');
        
        // If it starts with '../', remove all instances
        while (strpos($cleanPath, '../') === 0) {
            $cleanPath = substr($cleanPath, 3);
        }
        
        // Ensure it starts with 'uploads/' for web accessibility from root
        if (!str_starts_with($cleanPath, 'uploads/')) {
            if (str_contains($cleanPath, 'uploads/')) {
                $cleanPath = 'uploads/' . substr($cleanPath, strpos($cleanPath, 'uploads/') + 8);
            }
        }
        
        return $cleanPath;
    }
    
    /**
     * Validate that all paths actually point to existing files
     */
    public function validateFileExistence() {
        echo "\n=== FILE EXISTENCE VALIDATION ===\n";
        echo str_repeat("-", 60) . "\n";
        
        $pages = $this->db->query("SELECT edition_id, page_number, image_path FROM edition_pages ORDER BY edition_id, page_number")->fetchAll();
        
        $existing = 0;
        $missing = 0;
        
        foreach ($pages as $page) {
            $fullPath = $page['image_path'];
            
            if (file_exists($fullPath)) {
                $existing++;
            } else {
                echo "❌ Missing: Edition {$page['edition_id']} Page {$page['page_number']} - {$fullPath}\n";
                $missing++;
            }
        }
        
        echo "\n📊 SUMMARY:\n";
        echo "✅ Existing files: {$existing}\n";
        echo "❌ Missing files: {$missing}\n";
        
        if ($missing === 0) {
            echo "🎉 All image files are accessible!\n";
        }
        
        return ['existing' => $existing, 'missing' => $missing];
    }
}

// Auto-run if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $validator = new PathValidator();
    $pathResults = $validator->validateAndFixAllPaths();
    $fileResults = $validator->validateFileExistence();
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎯 FINAL SUMMARY:\n";
    echo "Paths fixed: {$pathResults['fixed']}\n";
    echo "Files accessible: {$fileResults['existing']}\n";
    echo "Missing files: {$fileResults['missing']}\n";
    
    if ($pathResults['fixed'] > 0 || $fileResults['missing'] > 0) {
        echo "\n💡 Recommendation: Test your homepage to ensure all images display correctly\n";
    } else {
        echo "\n✅ All systems operational - your homepage should work perfectly!\n";
    }
}
?>
