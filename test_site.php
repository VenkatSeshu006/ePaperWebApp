<?php
// Test basic PHP functionality
echo "✅ PHP is working\n";

// Test database connection
try {
    require_once 'includes/database.php';
    $conn = getConnection();
    
    if ($conn) {
        echo "✅ Database connection successful\n";
        
        // Test a simple query
        $result = $conn->query("SELECT COUNT(*) as count FROM editions");
        if ($result) {
            $count = $result->fetch()['count'];
            echo "✅ Database query successful - $count editions found\n";
        }
    } else {
        echo "❌ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n🌐 Try accessing:\n";
echo "📱 http://localhost/Projects/ePaperApplication/\n";
echo "👨‍💼 http://localhost/Projects/ePaperApplication/admin/\n";
?>
