<?php
/**
 * Database Setup Script
 * Creates the complete database structure for E-Paper CMS
 */

// Database configuration
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "epaper_cms";

try {
    // Connect to MySQL (without specifying database first)
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>E-Paper CMS Database Setup</h2>\n";
    
    // Create database if it doesn't exist
    $conn->query("CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✓ Database '$db_name' created/verified</p>\n";
    
    // Select the database
    $conn->select_db($db_name);
    
    // Create editions table
    $editions_sql = "
    CREATE TABLE IF NOT EXISTS editions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(200) UNIQUE NOT NULL,
        description TEXT,
        date DATE NOT NULL,
        status ENUM('draft', 'published', 'archived') DEFAULT 'published',
        featured BOOLEAN DEFAULT FALSE,
        views INT DEFAULT 0,
        downloads INT DEFAULT 0,
        thumbnail_path VARCHAR(500),
        pdf_path VARCHAR(500),
        total_pages INT DEFAULT 0,
        file_size BIGINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_date (date),
        INDEX idx_status (status),
        INDEX idx_slug (slug),
        INDEX idx_featured (featured)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($editions_sql);
    echo "<p>✓ Table 'editions' created</p>\n";
    
    // Create pages table
    $pages_sql = "
    CREATE TABLE IF NOT EXISTS pages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        edition_id INT NOT NULL,
        page_number INT NOT NULL,
        image_path VARCHAR(500) NOT NULL,
        thumbnail_path VARCHAR(500),
        width INT DEFAULT 0,
        height INT DEFAULT 0,
        file_size INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (edition_id) REFERENCES editions(id) ON DELETE CASCADE,
        INDEX idx_edition_page (edition_id, page_number),
        UNIQUE KEY unique_edition_page (edition_id, page_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($pages_sql);
    echo "<p>✓ Table 'pages' created</p>\n";
    
    // Create clips table
    $clips_sql = "
    CREATE TABLE IF NOT EXISTS clips (
        id INT PRIMARY KEY AUTO_INCREMENT,
        edition_id INT NOT NULL,
        page_id INT,
        page_number INT DEFAULT 1,
        x INT NOT NULL DEFAULT 0,
        y INT NOT NULL DEFAULT 0,
        width INT NOT NULL DEFAULT 100,
        height INT NOT NULL DEFAULT 100,
        image_path VARCHAR(500) NOT NULL,
        title VARCHAR(255),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (edition_id) REFERENCES editions(id) ON DELETE CASCADE,
        INDEX idx_edition_id (edition_id),
        INDEX idx_page_number (page_number),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($clips_sql);
    echo "<p>✓ Table 'clips' created</p>\n";
    
    // Create settings table
    $settings_sql = "
    CREATE TABLE IF NOT EXISTS settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($settings_sql);
    echo "<p>✓ Table 'settings' created</p>\n";
    
    // Create categories table
    $categories_sql = "
    CREATE TABLE IF NOT EXISTS categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        description TEXT,
        icon VARCHAR(50) DEFAULT 'fas fa-newspaper',
        color VARCHAR(7) DEFAULT '#2196F3',
        sort_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_slug (slug),
        INDEX idx_status (status),
        INDEX idx_sort_order (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($categories_sql);
    echo "<p>✓ Table 'categories' created</p>\n";
    
    // Insert default settings
    $default_settings = [
        ['site_title', 'E-Paper CMS', 'string', 'Website title'],
        ['site_description', 'Digital Newspaper Platform', 'string', 'Website description'],
        ['items_per_page', '10', 'number', 'Items per page in listings'],
        ['enable_downloads', '1', 'boolean', 'Allow PDF downloads'],
        ['enable_sharing', '1', 'boolean', 'Enable social sharing'],
        ['enable_clips', '1', 'boolean', 'Enable clip functionality']
    ];
    
    foreach ($default_settings as $setting) {
        $stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$setting[0], $setting[1], $setting[2], $setting[3]]);
    }
    echo "<p>✓ Default settings inserted</p>\n";
    
    // Insert sample edition
    $sample_edition_sql = "
    INSERT IGNORE INTO editions (id, title, slug, description, date, status, featured) 
    VALUES (1, 'Sample E-Paper Edition', 'sample-edition-2025-07-27', 'This is a sample digital newspaper edition for testing purposes.', '2025-07-27', 'published', 1)";
    
    $conn->query($sample_edition_sql);
    echo "<p>✓ Sample edition inserted</p>\n";
    
    // Update the total_pages after inserting pages
    $update_total_pages_sql = "UPDATE editions SET total_pages = 12 WHERE id = 1";
    $conn->query($update_total_pages_sql);
    echo "<p>✓ Total pages updated</p>\n";
    
    // Insert sample pages for the edition
    $sample_pages = [];
    for ($i = 1; $i <= 12; $i++) {
        $page_num = str_pad($i, 3, '0', STR_PAD_LEFT);
        $sample_pages[] = "(1, $i, 'uploads/2025-07-27/pages/page_$page_num.png', 'uploads/2025-07-27/pages/thumb_$page_num.png', 800, 1200, 150000)";
    }
    
    $sample_pages_sql = "
    INSERT IGNORE INTO pages (edition_id, page_number, image_path, thumbnail_path, width, height, file_size) 
    VALUES " . implode(', ', $sample_pages);
    
    $conn->query($sample_pages_sql);
    echo "<p>✓ Sample pages inserted</p>\n";
    
    echo "<hr>\n";
    echo "<h3 style='color: green;'>✅ Database setup completed successfully!</h3>\n";
    echo "<p><a href='index.php'>Go to Homepage</a> | <a href='test_db_connection.php'>Test Connection</a></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
