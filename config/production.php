<?php
/**
 * Production Environment Configuration
 * Use this configuration for live hosting platforms
 */

// Prevent direct access
if (!defined('EPAPER_CMS')) {
    die('Direct access not permitted');
}

// Production Database Configuration
// UPDATE THESE VALUES FOR YOUR HOSTING PLATFORM
define('DB_HOST', 'localhost'); // Change to your database host
define('DB_NAME', 'your_database_name'); // Change to your database name
define('DB_USER', 'your_db_username'); // Change to your database username
define('DB_PASS', 'your_strong_password'); // Change to your database password
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', 3306);

// Application Configuration
define('APP_NAME', 'E-Paper CMS');
define('APP_VERSION', '2.0.0');
define('APP_ENV', 'production');
define('DEBUG_MODE', false); // ALWAYS false in production

// Security Configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOCKOUT_DURATION', 1800); // 30 minutes
define('SECURE_COOKIES', true); // Enable secure cookies over HTTPS
define('CSRF_PROTECTION', true);

// File Upload Configuration
define('MAX_FILE_SIZE', 25 * 1024 * 1024); // 25MB (adjust based on hosting limits)
define('ALLOWED_PDF_TYPES', ['application/pdf']);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Image Processing Configuration
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 400);
define('PAGE_DPI', 150);
define('IMAGE_QUALITY', 85);

// PDF Processing Configuration (Linux/Unix paths)
define('GHOSTSCRIPT_COMMAND', '/usr/bin/gs'); // Standard Linux path
define('PDF_TIMEOUT', 180); // 3 minutes (hosting-friendly)

// Path Configuration (Cross-platform)
define('UPLOAD_DIR', __DIR__ . '/.uploads/');
define('CACHE_DIR', __DIR__ . '/../cache/');
define('LOG_DIR', __DIR__ . '/../logs/');

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 7200); // 2 hours

// URL Configuration
// IMPORTANT: Update these for your domain
define('SITE_URL', 'https://yourdomain.com'); // Your website URL
define('ADMIN_URL', 'https://yourdomain.com/admin'); // Admin panel URL

// Email Configuration (if needed)
define('SMTP_HOST', 'smtp.yourdomain.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@yourdomain.com');
define('SMTP_PASS', 'your_email_password');
define('SMTP_FROM', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', 'E-Paper CMS');

// Performance Configuration
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');
ini_set('post_max_size', '30M');
ini_set('upload_max_filesize', '25M');

// Error Reporting (Off in production)
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_DIR . 'php_errors.log');

// Session Configuration
ini_set('session.cookie_secure', '1'); // HTTPS only
ini_set('session.cookie_httponly', '1'); // No JavaScript access
ini_set('session.use_strict_mode', '1'); // Strict session management
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection

// Timezone
date_default_timezone_set('UTC'); // Adjust to your timezone

/**
 * HOSTING PLATFORM SPECIFIC NOTES:
 * 
 * HOSTINGER:
 * - Usually uses '/home/username/public_html/' as web root
 * - Database host might be 'localhost' or specific IP
 * - Check cPanel for exact database details
 * 
 * AWS EC2:
 * - Use RDS endpoint for database host
 * - Configure security groups for MySQL (port 3306)
 * - Consider using environment variables
 * 
 * AZURE:
 * - Use Azure Database for MySQL connection string
 * - Configure App Settings for sensitive data
 * - Enable Application Insights for logging
 * 
 * GENERIC SHARED HOSTING:
 * - Check hosting provider documentation
 * - Database host often 'localhost'
 * - May have file permission restrictions
 */
?>
