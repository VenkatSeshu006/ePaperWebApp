<?php
/**
 * Debug categories functionality
 */
require_once '../config/config.php';
require_once '../includes/database.php';

echo "<h2>Categories Debug</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if (!$conn) {
        echo "<p>❌ Database connection failed</p>";
        exit;
    }
    
    echo "<p>✅ Database connection successful</p>";
    
    // Check if categories table exists
    $result = $conn->query("SHOW TABLES LIKE 'categories'");
    if ($result && $result->rowCount() > 0) {
        echo "<p>✅ Categories table exists</p>";
        
        // Show table structure
        $result = $conn->query("DESCRIBE categories");
        echo "<h3>Categories Table Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show current categories
        $result = $conn->query("SELECT * FROM categories ORDER BY name");
        $categories = $result->fetchAll();
        
        echo "<h3>Current Categories:</h3>";
        if (empty($categories)) {
            echo "<p>No categories found</p>";
        } else {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Slug</th><th>Description</th><th>Color</th></tr>";
            foreach ($categories as $cat) {
                echo "<tr>";
                echo "<td>" . $cat['id'] . "</td>";
                echo "<td>" . htmlspecialchars($cat['name']) . "</td>";
                echo "<td>" . htmlspecialchars($cat['slug']) . "</td>";
                echo "<td>" . htmlspecialchars($cat['description']) . "</td>";
                echo "<td style='background-color: " . htmlspecialchars($cat['color']) . ";'>" . htmlspecialchars($cat['color']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p>❌ Categories table does not exist</p>";
        echo "<p>Creating categories table...</p>";
        
        $createTable = "
        CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            color VARCHAR(7) DEFAULT '#007bff',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->exec($createTable)) {
            echo "<p>✅ Categories table created successfully</p>";
        } else {
            echo "<p>❌ Failed to create categories table</p>";
        }
    }
    
    // Test form submission simulation
    echo "<h3>Test Category Creation:</h3>";
    echo "<form method='POST' action='categories.php' style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<input type='hidden' name='action' value='create'>";
    echo "<label>Name: <input type='text' name='name' value='Test Category' required></label><br><br>";
    echo "<label>Description: <textarea name='description'>Test category description</textarea></label><br><br>";
    echo "<label>Color: <input type='color' name='color' value='#ff5722'></label><br><br>";
    echo "<button type='submit'>Create Test Category</button>";
    echo "</form>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
