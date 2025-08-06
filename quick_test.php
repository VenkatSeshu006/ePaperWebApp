<?php
// Quick website test
try {
    require_once 'includes/database.php';
    $conn = getConnection();
    
    if ($conn) {
        echo "✅ Database connection working\n";
        
        // Test homepage data
        $result = $conn->query("SELECT id, title, date FROM editions ORDER BY date DESC LIMIT 1");
        if ($result && $row = $result->fetch()) {
            echo "✅ Latest edition: {$row['title']} ({$row['date']})\n";
        }
        
        // Test pages
        $result = $conn->query("SELECT COUNT(*) as count FROM edition_pages");
        if ($result) {
            $pageCount = $result->fetch()['count'];
            echo "✅ Pages available: $pageCount\n";
        }
        
        echo "\n🎉 Website should work at:\n";
        echo "📱 http://localhost/Projects/ePaperApplication/\n";
        echo "👨‍💼 http://localhost/Projects/ePaperApplication/admin/\n";
        
    } else {
        echo "❌ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
