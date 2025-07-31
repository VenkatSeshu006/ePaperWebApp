<?php
/**
 * Database Setup Script
 * Creates the database and all required tables for E-Paper CMS v2.0
 */

// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'epaper_cms';

try {
    // Connect to MySQL server (without database selection)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🗄️ Creating Database and Tables</h2>";
    
    // Create database if it doesn't exist
    echo "<p>✅ Creating database 'epaper_cms'...</p>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Select the database
    $pdo->exec("USE `$database`");
    
    // Create categories table
    echo "<p>✅ Creating 'categories' table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `categories` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `slug` varchar(255) NOT NULL UNIQUE,
            `description` text,
            `color` varchar(7) DEFAULT '#0d6efd',
            `icon` varchar(50) DEFAULT 'fas fa-folder',
            `sort_order` int(11) DEFAULT 0,
            `status` enum('active','inactive') DEFAULT 'active',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_slug` (`slug`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create editions table
    echo "<p>✅ Creating 'editions' table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `editions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `slug` varchar(255) UNIQUE,
            `description` text,
            `date` date NOT NULL,
            `pdf_path` varchar(500) NOT NULL,
            `thumbnail_path` varchar(500),
            `status` enum('draft','published','archived') DEFAULT 'draft',
            `featured` tinyint(1) DEFAULT 0,
            `views` int(11) DEFAULT 0,
            `downloads` int(11) DEFAULT 0,
            `created_by` int(11),
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_slug` (`slug`),
            KEY `idx_status` (`status`),
            KEY `idx_date` (`date`),
            KEY `idx_featured` (`featured`),
            FULLTEXT KEY `idx_search` (`title`, `description`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create edition_categories junction table
    echo "<p>✅ Creating 'edition_categories' table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `edition_categories` (
            `edition_id` int(11) NOT NULL,
            `category_id` int(11) NOT NULL,
            PRIMARY KEY (`edition_id`, `category_id`),
            KEY `idx_edition` (`edition_id`),
            KEY `idx_category` (`category_id`),
            CONSTRAINT `fk_ec_edition` FOREIGN KEY (`edition_id`) REFERENCES `editions` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_ec_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create edition_pages table
    echo "<p>✅ Creating 'edition_pages' table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `edition_pages` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `edition_id` int(11) NOT NULL,
            `page_number` int(11) NOT NULL,
            `image_path` varchar(500) NOT NULL,
            `width` int(11),
            `height` int(11),
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_edition_page` (`edition_id`, `page_number`),
            KEY `idx_edition_pages` (`edition_id`),
            CONSTRAINT `fk_ep_edition` FOREIGN KEY (`edition_id`) REFERENCES `editions` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create analytics table
    echo "<p>✅ Creating 'analytics' table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `analytics` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `edition_id` int(11),
            `action` enum('view','download','share') NOT NULL,
            `ip_address` varchar(45),
            `user_agent` text,
            `referrer` varchar(500),
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_edition_analytics` (`edition_id`),
            KEY `idx_action` (`action`),
            KEY `idx_created_at` (`created_at`),
            CONSTRAINT `fk_analytics_edition` FOREIGN KEY (`edition_id`) REFERENCES `editions` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create users table for admin authentication
    echo "<p>✅ Creating 'users' table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL UNIQUE,
            `email` varchar(255) NOT NULL UNIQUE,
            `password_hash` varchar(255) NOT NULL,
            `full_name` varchar(255),
            `role` enum('admin','editor','viewer') DEFAULT 'editor',
            `status` enum('active','inactive') DEFAULT 'active',
            `last_login` timestamp NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_username` (`username`),
            UNIQUE KEY `unique_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create settings table
    echo "<p>✅ Creating 'settings' table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(100) NOT NULL UNIQUE,
            `setting_value` text,
            `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
            `description` text,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create clips table for content clipping feature
    echo "<p>✅ Creating 'clips' table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `clips` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `edition_id` int(11) NOT NULL,
            `page_number` int(11) NOT NULL,
            `x` int(11) NOT NULL,
            `y` int(11) NOT NULL,
            `width` int(11) NOT NULL,
            `height` int(11) NOT NULL,
            `image_path` varchar(500),
            `title` varchar(255),
            `description` text,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_edition_clips` (`edition_id`),
            CONSTRAINT `fk_clips_edition` FOREIGN KEY (`edition_id`) REFERENCES `editions` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Insert sample categories
    echo "<p>✅ Inserting sample categories...</p>";
    $sampleCategories = [
        ['News', 'news', 'Latest news and current events', '#dc3545', 'fas fa-newspaper'],
        ['Sports', 'sports', 'Sports news and updates', '#28a745', 'fas fa-football-ball'],
        ['Business', 'business', 'Business and economic news', '#007bff', 'fas fa-chart-line'],
        ['Technology', 'technology', 'Tech news and innovations', '#6f42c1', 'fas fa-microchip'],
        ['Entertainment', 'entertainment', 'Entertainment and celebrity news', '#fd7e14', 'fas fa-film'],
        ['Opinion', 'opinion', 'Editorial and opinion pieces', '#6c757d', 'fas fa-comment-alt']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description, color, icon) VALUES (?, ?, ?, ?, ?)");
    foreach ($sampleCategories as $category) {
        $stmt->execute($category);
    }
    
    // Insert default admin user (username: admin, password: admin123)
    echo "<p>✅ Creating default admin user...</p>";
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT IGNORE INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)")
        ->execute(['admin', 'admin@example.com', $adminPassword, 'System Administrator', 'admin']);
    
    // Insert default settings
    echo "<p>✅ Inserting default settings...</p>";
    $defaultSettings = [
        ['site_name', 'E-Paper CMS', 'string', 'Website name'],
        ['site_description', 'Digital Newspaper Content Management System', 'string', 'Website description'],
        ['items_per_page', '12', 'number', 'Items per page for pagination'],
        ['enable_analytics', 'true', 'boolean', 'Enable analytics tracking'],
        ['maintenance_mode', 'false', 'boolean', 'Maintenance mode status']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
    }
    
    echo "<p>✅ <strong>Database setup completed successfully!</strong></p>";
    echo "<p>🔐 <strong>Default admin credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "</ul>";
    echo "<p>⚠️ <strong>Remember to change the default password after first login!</strong></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ <strong>Database setup failed:</strong> " . $e->getMessage() . "</p>";
    exit;
}
?>
