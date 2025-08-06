<?php
/**
 * E-Paper CMS v2.0 - Global Configuration
 * Centralized configuration for the entire system
 */

// Prevent direct access
if (!defined('EPAPER_CMS')) {
    define('EPAPER_CMS', true);
}

// Application Configuration
define('APP_NAME', 'E-Paper CMS');
define('APP_VERSION', '2.0.0');
define('APP_AUTHOR', 'E-Paper CMS Team');
define('APP_RELEASE_DATE', '2025-07-29');

// Database Configuration (Override in includes/database.php if needed)
if (!defined('DB_HOST')) {
    // Use environment-based configuration
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'epaper_cms');
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
    define('DB_CHARSET', 'utf8mb4');
}

// File Upload Configuration
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_PDF_TYPES', ['application/pdf']);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Image Processing Configuration
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 400);
define('PAGE_DPI', 150);
define('IMAGE_QUALITY', 85);

// PDF Processing Configuration
// Cross-platform Ghostscript detection
$ghostscriptPaths = [
    '/usr/bin/gs',              // Linux/Unix standard
    '/usr/local/bin/gs',        // Linux/Unix alternative
    'C:\Program Files\gs\gs10.05.1\bin\gswin64c.exe', // Windows XAMPP
    'gs'                        // System PATH
];

$gsCommand = 'gs'; // Default fallback
foreach ($ghostscriptPaths as $path) {
    if (is_executable($path) || (PHP_OS_FAMILY === 'Windows' && file_exists($path))) {
        $gsCommand = $path;
        break;
    }
}

define('GHOSTSCRIPT_COMMAND', $gsCommand);
define('PDF_TIMEOUT', 300); // 5 minutes

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hour
define('CACHE_DIR', __DIR__ . '/cache/');

// Directory Structure
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('CLIPS_DIR', UPLOAD_DIR . 'clips/');
define('TEMP_DIR', UPLOAD_DIR . 'temp/');
define('ADMIN_DIR', __DIR__ . '/admin/');
define('API_DIR', __DIR__ . '/api/');
define('ASSETS_DIR', __DIR__ . '/assets/');

// URL Configuration
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));
define('ADMIN_URL', BASE_URL . '/admin/');
define('API_URL', BASE_URL . '/api/');
define('ASSETS_URL', BASE_URL . '/assets/');

// External CDN URLs
define('BOOTSTRAP_CSS_URL', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
define('BOOTSTRAP_JS_URL', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js');
define('FONTAWESOME_URL', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
define('JQUERY_URL', 'https://code.jquery.com/jquery-3.6.0.min.js');
define('JQUERY_UI_CSS_URL', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
define('JQUERY_UI_JS_URL', 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js');
define('CROPPER_CSS_URL', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css');
define('CROPPER_JS_URL', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js');
define('JSPDF_URL', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');

// Social Media Configuration
define('SOCIAL_PLATFORMS', [
    'facebook' => [
        'name' => 'Facebook',
        'icon' => 'fab fa-facebook',
        'color' => '#1877f2'
    ],
    'twitter' => [
        'name' => 'Twitter/X',
        'icon' => 'fab fa-twitter',
        'color' => '#1da1f2'
    ],
    'whatsapp' => [
        'name' => 'WhatsApp',
        'icon' => 'fab fa-whatsapp',
        'color' => '#25d366'
    ],
    'linkedin' => [
        'name' => 'LinkedIn',
        'icon' => 'fab fa-linkedin',
        'color' => '#0077b5'
    ],
    'telegram' => [
        'name' => 'Telegram',
        'icon' => 'fab fa-telegram',
        'color' => '#0088cc'
    ],
    'email' => [
        'name' => 'Email',
        'icon' => 'fas fa-envelope',
        'color' => '#ea4335'
    ]
]);

// Feature Flags
define('FEATURE_ANALYTICS', true);
define('FEATURE_PWA', true);
define('FEATURE_SEARCH', true);
define('FEATURE_SOCIAL_SHARING', true);
define('FEATURE_CLIPPING', true);
define('FEATURE_PDF_DOWNLOAD', true);

// Error Handling
define('DEBUG_MODE', false);
define('LOG_ERRORS', true);
define('LOG_FILE', __DIR__ . '/logs/error.log');

// Performance Settings
define('ENABLE_GZIP', true);
define('ENABLE_BROWSER_CACHE', true);
define('CACHE_EXPIRE_TIME', 86400); // 24 hours

// Pagination Settings
define('PAGES_PER_GROUP', 5);
define('EDITIONS_PER_PAGE', 12);
define('CLIPS_PER_PAGE', 20);

// Date and Time Configuration
define('DEFAULT_TIMEZONE', 'America/New_York');
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'F j, Y');

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Helper Functions
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

function isFeatureEnabled($feature) {
    $constantName = 'FEATURE_' . strtoupper($feature);
    return defined($constantName) && constant($constantName) === true;
}

function getBaseUrl() {
    return BASE_URL;
}

function getAssetUrl($asset) {
    return ASSETS_URL . '/' . ltrim($asset, '/');
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function sanitizeFilename($filename) {
    return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
}

function generateUniqueId() {
    return uniqid() . '_' . mt_rand(1000, 9999);
}

// Auto-create required directories
$requiredDirs = [
    UPLOAD_DIR,
    CLIPS_DIR, 
    TEMP_DIR,
    CACHE_DIR,
    dirname(LOG_FILE)
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Set error handling
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

if (LOG_ERRORS) {
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_FILE);
}

// Output compression
if (ENABLE_GZIP && extension_loaded('zlib') && !headers_sent()) {
    ini_set('zlib.output_compression', 1);
}

// Browser caching headers
if (ENABLE_BROWSER_CACHE && !headers_sent()) {
    $expires = gmdate('D, d M Y H:i:s', time() + CACHE_EXPIRE_TIME) . ' GMT';
    header("Expires: $expires");
    header("Cache-Control: public, max-age=" . CACHE_EXPIRE_TIME);
}

?>
