<?php
// Direct database connection
$host = 'localhost';
$database = 'epaper_cms';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Simple Test</h1>";
    
    // Test basic query
    $query = "SELECT id, title, slug, pdf_path FROM editions WHERE status = 'published' LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $edition = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($edition) {
        echo "<h2>Found Edition:</h2>";
        echo "<p>ID: " . $edition['id'] . "</p>";
        echo "<p>Title: " . $edition['title'] . "</p>";
        echo "<p>Slug: " . $edition['slug'] . "</p>";
        echo "<p>PDF Path: " . $edition['pdf_path'] . "</p>";
        
        if ($edition['pdf_path'] && file_exists($edition['pdf_path'])) {
            echo "<p>✅ PDF file exists</p>";
        } else {
            echo "<p>❌ PDF file not found: " . $edition['pdf_path'] . "</p>";
        }
    } else {
        echo "<p>No published editions found</p>";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
