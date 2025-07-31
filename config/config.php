<?php
/**
 * E-Paper CMS Configuration
 * Central configuration file for the entire application
 */

// Environment settings
define('ENVIRONMENT', 'development'); // development, staging, production

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'epaper_cms');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'E-Paper CMS');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'http://localhost/epaper-site');
define('BASE_PATH', __DIR__ . '/../');

// Security settings
define('SECRET_KEY', 'your-secret-key-change-in-production');
define('ENCRYPTION_METHOD', 'AES-256-CBC');
define('SESSION_LIFETIME', 3600); // 1 hour

// File upload settings
define('UPLOAD_PATH', BASE_PATH . 'uploads/');
define('TEMP_PATH', BASE_PATH . 'temp/');

// Pagination settings
if (!defined('ITEMS_PER_PAGE')) {
    define('ITEMS_PER_PAGE', 12);
}
define('CACHE_PATH', BASE_PATH . 'cache/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);

// Image processing settings
define('THUMBNAIL_WIDTH', 200);
define('THUMBNAIL_HEIGHT', 260);
define('MAX_IMAGE_WIDTH', 1920);
define('MAX_IMAGE_HEIGHT', 2560);
define('IMAGE_QUALITY', 85);

// Pagination settings (additional)
define('MAX_PAGINATION_LINKS', 10);

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hour

// Error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG_MODE', true);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    define('DEBUG_MODE', false);
}

// Timezone
date_default_timezone_set('UTC');

// Auto-create required directories
$directories = [UPLOAD_PATH, TEMP_PATH, CACHE_PATH];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Security headers (for production)
if (ENVIRONMENT === 'production') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
?>
