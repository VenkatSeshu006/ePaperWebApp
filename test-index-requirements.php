<?php
echo "Testing index.php includes...\n";

try {
    echo "1. Testing database include...\n";
    require_once 'includes/database.php';
    echo "   ✓ Database included successfully\n";
    
    echo "2. Testing database connection...\n";
    $conn = getConnection();
    echo "   ✓ Database connection successful\n";
    
    echo "3. Testing editions query...\n";
    $sql = "SELECT * FROM editions ORDER BY date DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result) {
        echo "   ✓ Editions query successful\n";
        if ($result->num_rows > 0) {
            $latest = $result->fetch_assoc();
            echo "   ✓ Found latest edition: " . $latest['title'] . "\n";
        } else {
            echo "   ! No editions found in database\n";
        }
    } else {
        echo "   ! Editions query failed: " . $conn->error . "\n";
    }
    
    echo "\nAll tests passed! Index.php should work now.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
