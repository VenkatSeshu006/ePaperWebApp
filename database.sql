-- E-Paper CMS Database Structure
-- Complete rebuild with optimized tables and relationships

DROP DATABASE IF EXISTS epaper_cms;
CREATE DATABASE epaper_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE epaper_cms;

-- Admin users table
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'editor', 'viewer') DEFAULT 'editor',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- Categories table
CREATE TABLE categories (
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
);

-- Editions table (main newspaper editions)
CREATE TABLE editions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    cover_image VARCHAR(255),
    pdf_file VARCHAR(255),
    total_pages INT DEFAULT 0,
    file_size BIGINT DEFAULT 0,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    featured BOOLEAN DEFAULT FALSE,
    views INT DEFAULT 0,
    downloads INT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_date (date),
    INDEX idx_status (status),
    INDEX idx_featured (featured),
    INDEX idx_views (views),
    FULLTEXT idx_search (title, description)
);

-- Edition pages table (individual page images)
CREATE TABLE edition_pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    edition_id INT NOT NULL,
    page_number INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255),
    width INT,
    height INT,
    file_size BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (edition_id) REFERENCES editions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_edition_page (edition_id, page_number),
    INDEX idx_edition_page (edition_id, page_number)
);

-- Edition categories relationship
CREATE TABLE edition_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    edition_id INT NOT NULL,
    category_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (edition_id) REFERENCES editions(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_edition_category (edition_id, category_id)
);

-- Clips table (user-generated clippings)
CREATE TABLE clips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    edition_id INT NOT NULL,
    page_number INT NOT NULL,
    title VARCHAR(200),
    description TEXT,
    image_path VARCHAR(255) NOT NULL,
    original_x INT NOT NULL,
    original_y INT NOT NULL,
    width INT NOT NULL,
    height INT NOT NULL,
    file_size BIGINT DEFAULT 0,
    ip_address VARCHAR(45),
    user_agent TEXT,
    views INT DEFAULT 0,
    shares INT DEFAULT 0,
    status ENUM('active', 'moderated', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (edition_id) REFERENCES editions(id) ON DELETE CASCADE,
    INDEX idx_edition_page (edition_id, page_number),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Analytics table
CREATE TABLE page_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    edition_id INT,
    page_number INT,
    event_type ENUM('view', 'download', 'share', 'clip') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(500),
    country VARCHAR(2),
    city VARCHAR(100),
    device_type ENUM('desktop', 'tablet', 'mobile') DEFAULT 'desktop',
    browser VARCHAR(50),
    os VARCHAR(50),
    session_id VARCHAR(100),
    duration INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (edition_id) REFERENCES editions(id) ON DELETE SET NULL,
    INDEX idx_edition (edition_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at),
    INDEX idx_session (session_id)
);

-- Settings table
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (setting_key),
    INDEX idx_public (is_public)
);

-- Activity logs table
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
);

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, email, password_hash, full_name, role) VALUES 
('admin', 'admin@epaper.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Insert default categories
INSERT INTO categories (name, slug, description, icon, color, sort_order) VALUES 
('General', 'general', 'General news and articles', 'fas fa-newspaper', '#2196F3', 1),
('Politics', 'politics', 'Political news and analysis', 'fas fa-landmark', '#FF5722', 2),
('Business', 'business', 'Business and financial news', 'fas fa-chart-line', '#4CAF50', 3),
('Sports', 'sports', 'Sports news and updates', 'fas fa-futbol', '#FF9800', 4),
('Technology', 'technology', 'Technology and innovation', 'fas fa-microchip', '#9C27B0', 5),
('Entertainment', 'entertainment', 'Entertainment and lifestyle', 'fas fa-film', '#E91E63', 6);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_type, description, is_public) VALUES 
('site_name', 'Digital E-Paper', 'string', 'Website name', TRUE),
('site_description', 'Your trusted source for digital news', 'string', 'Website description', TRUE),
('items_per_page', '12', 'number', 'Items per page in listings', FALSE),
('max_file_size', '50', 'number', 'Maximum file size in MB', FALSE),
('allowed_extensions', '["pdf", "jpg", "jpeg", "png"]', 'json', 'Allowed file extensions', FALSE),
('enable_analytics', 'true', 'boolean', 'Enable analytics tracking', FALSE),
('enable_clips', 'true', 'boolean', 'Enable clipping feature', TRUE),
('clip_moderation', 'false', 'boolean', 'Require clip moderation', FALSE),
('timezone', 'UTC', 'string', 'Server timezone', FALSE),
('date_format', 'Y-m-d', 'string', 'Date display format', TRUE),
('time_format', 'H:i:s', 'string', 'Time display format', TRUE);
