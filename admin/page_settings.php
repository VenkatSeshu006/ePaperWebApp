<?php
/**
 * Page Settings Management
 * Manage website header, footer, and other page content
 */
session_start();
define('ADMIN_PAGE', true);

// Include configuration first
require_once '../config/config.php';

// Authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: dashboard.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: dashboard.php');
    exit;
}

require_once '../includes/database.php';

// Get database connection
$conn = getConnection();

// Ensure settings table exists
$tableCheckSql = "CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string','number','boolean','json','html') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$conn->query($tableCheckSql);

// Initialize variables
$success = '';
$error = '';

// Default page settings
$defaultSettings = [
    'site_title' => [
        'value' => 'Prayatnam Epaper',
        'type' => 'string',
        'description' => 'Main website title displayed in header and browser tab'
    ],
    'site_tagline' => [
        'value' => 'Your trusted source for digital news',
        'type' => 'string',
        'description' => 'Website tagline or subtitle'
    ],
    'header_logo_text' => [
        'value' => 'Digital E-Paper',
        'type' => 'string',
        'description' => 'Logo text displayed in the header'
    ],
    'footer_links' => [
        'value' => json_encode([
            ['text' => 'Jay', 'url' => '?'],
            ['text' => 'Andhra', 'url' => '?'],
            ['text' => 'Tamil Nadu', 'url' => '?'],
            ['text' => 'Game', 'url' => '?']
        ]),
        'type' => 'json',
        'description' => 'Navigation links displayed in the footer'
    ],
    'copyright_text' => [
        'value' => 'Prayatnam Epaper. All rights reserved.',
        'type' => 'string',
        'description' => 'Copyright text displayed in footer'
    ],
    'contact_email' => [
        'value' => 'info@prayatnamepaper.com',
        'type' => 'string',
        'description' => 'Contact email address'
    ],
    'contact_phone' => [
        'value' => '+91 12345 67890',
        'type' => 'string',
        'description' => 'Contact phone number'
    ],
    'social_facebook' => [
        'value' => 'https://facebook.com/prayatnamepaper',
        'type' => 'string',
        'description' => 'Facebook page URL'
    ],
    'social_twitter' => [
        'value' => 'https://twitter.com/prayatnamepaper',
        'type' => 'string',
        'description' => 'Twitter profile URL'
    ],
    'social_instagram' => [
        'value' => 'https://instagram.com/prayatnamepaper',
        'type' => 'string',
        'description' => 'Instagram profile URL'
    ],
    'about_text' => [
        'value' => 'Prayatnam Epaper is your trusted source for digital news and information. We deliver quality journalism in the digital age with cutting-edge technology and user-friendly interface.',
        'type' => 'html',
        'description' => 'About us text for footer or about page'
    ],
    'meta_description' => [
        'value' => 'Read the latest news and updates from Prayatnam Epaper - your digital newspaper with comprehensive coverage.',
        'type' => 'string',
        'description' => 'Website meta description for SEO'
    ],
    'meta_keywords' => [
        'value' => 'epaper, digital newspaper, news, prayatnam, online news',
        'type' => 'string',
        'description' => 'Website meta keywords for SEO'
    ],
    'enable_newsletter' => [
        'value' => '1',
        'type' => 'boolean',
        'description' => 'Enable newsletter signup in footer'
    ],
    'enable_social_sharing' => [
        'value' => '1',
        'type' => 'boolean',
        'description' => 'Enable social media sharing buttons'
    ],
    'custom_css' => [
        'value' => '',
        'type' => 'html',
        'description' => 'Custom CSS styles to be added to all pages'
    ],
    'custom_js' => [
        'value' => '',
        'type' => 'html',
        'description' => 'Custom JavaScript code to be added to all pages'
    ],
    'maintenance_mode' => [
        'value' => '0',
        'type' => 'boolean',
        'description' => 'Enable maintenance mode (site will show maintenance message)'
    ],
    'maintenance_message' => [
        'value' => 'We are currently performing scheduled maintenance. Please check back soon.',
        'type' => 'html',
        'description' => 'Message displayed when maintenance mode is enabled'
    ],
    // Homepage Content Settings
    'homepage_welcome_title' => [
        'value' => 'Welcome to Digital News',
        'type' => 'string',
        'description' => 'Main welcome title on homepage'
    ],
    'homepage_welcome_subtitle' => [
        'value' => 'Stay informed with the latest news and updates',
        'type' => 'string',
        'description' => 'Welcome subtitle on homepage'
    ],
    'homepage_archive_title' => [
        'value' => 'Archive',
        'type' => 'string',
        'description' => 'Title for archive section on homepage'
    ],
    'homepage_archive_subtitle' => [
        'value' => 'Browse all published editions',
        'type' => 'string',
        'description' => 'Subtitle for archive section on homepage'
    ],
    'homepage_show_archive' => [
        'value' => '1',
        'type' => 'boolean',
        'description' => 'Show archive section on homepage'
    ],
    'homepage_max_archive_items' => [
        'value' => '12',
        'type' => 'number',
        'description' => 'Maximum number of archive items to show on homepage'
    ],
    'homepage_background_color' => [
        'value' => '#ffffff',
        'type' => 'string',
        'description' => 'Homepage background color'
    ],
    'homepage_text_color' => [
        'value' => '#333333',
        'type' => 'string',
        'description' => 'Homepage text color'
    ]
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_settings':
            $conn->beginTransaction();
            try {
                foreach ($_POST as $key => $value) {
                    if ($key === 'action') continue;
                    
                    // Handle footer_links specially as JSON
                    if ($key === 'footer_links') {
                        $links = [];
                        if (isset($_POST['footer_link_text']) && isset($_POST['footer_link_url'])) {
                            for ($i = 0; $i < count($_POST['footer_link_text']); $i++) {
                                if (!empty($_POST['footer_link_text'][$i])) {
                                    $links[] = [
                                        'text' => $_POST['footer_link_text'][$i],
                                        'url' => $_POST['footer_link_url'][$i] ?? '#'
                                    ];
                                }
                            }
                        }
                        $value = json_encode($links);
                    }
                    
                    // Get setting type
                    $settingType = $defaultSettings[$key]['type'] ?? 'string';
                    
                    // Insert or update setting
                    $sql = "INSERT INTO settings (setting_key, setting_value, setting_type, description) 
                            VALUES (?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE 
                            setting_value = VALUES(setting_value), 
                            setting_type = VALUES(setting_type),
                            description = VALUES(description)";
                    
                    $stmt = $conn->prepare($sql);
                    $description = $defaultSettings[$key]['description'] ?? '';
                    $stmt->execute([$key, $value, $settingType, $description]);
                }
                
                $conn->commit();
                $success = 'Settings saved successfully!';
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Error saving settings: ' . $e->getMessage();
            }
            break;
            
        case 'reset_settings':
            try {
                // Delete all current settings
                $conn->query("DELETE FROM settings WHERE setting_key IN ('" . implode("','", array_keys($defaultSettings)) . "')");
                
                // Insert default settings
                foreach ($defaultSettings as $key => $setting) {
                    $sql = "INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$key, $setting['value'], $setting['type'], $setting['description']]);
                }
                
                $success = 'Settings reset to defaults successfully!';
            } catch (Exception $e) {
                $error = 'Error resetting settings: ' . $e->getMessage();
            }
            break;
    }
}

// Get current settings
$currentSettings = [];
$sql = "SELECT setting_key, setting_value, setting_type FROM settings";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch()) {
        $currentSettings[$row['setting_key']] = $row['setting_value'];
    }
}

// Merge with defaults for any missing settings
foreach ($defaultSettings as $key => $setting) {
    if (!isset($currentSettings[$key])) {
        $currentSettings[$key] = $setting['value'];
    }
}

// Parse footer links
$footerLinks = [];
if (isset($currentSettings['footer_links'])) {
    $footerLinks = json_decode($currentSettings['footer_links'], true) ?: [];
}

// Page configuration for shared layout
$pageTitle = 'Page Settings';
$pageSubtitle = 'Manage website content and appearance';
$currentPage = 'page_settings';
$pageIcon = 'fas fa-cogs';

// Include shared layout header
include 'includes/admin_layout.php';
?>

<div class="row">
    <div class="col-12">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
</div>

<form method="POST" id="settingsForm">
    <input type="hidden" name="action" value="save_settings">
    
    <div class="row">
        <!-- General Settings -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-globe me-2 text-primary"></i>General Settings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="site_title" class="form-label">Site Title</label>
                            <input type="text" class="form-control" id="site_title" name="site_title" 
                                   value="<?php echo htmlspecialchars($currentSettings['site_title'] ?? ''); ?>">
                            <small class="form-text text-muted"><?php echo $defaultSettings['site_title']['description']; ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="site_tagline" class="form-label">Site Tagline</label>
                            <input type="text" class="form-control" id="site_tagline" name="site_tagline" 
                                   value="<?php echo htmlspecialchars($currentSettings['site_tagline'] ?? ''); ?>">
                            <small class="form-text text-muted"><?php echo $defaultSettings['site_tagline']['description']; ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="header_logo_text" class="form-label">Header Logo Text</label>
                            <input type="text" class="form-control" id="header_logo_text" name="header_logo_text" 
                                   value="<?php echo htmlspecialchars($currentSettings['header_logo_text'] ?? ''); ?>">
                            <small class="form-text text-muted"><?php echo $defaultSettings['header_logo_text']['description']; ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="copyright_text" class="form-label">Copyright Text</label>
                            <input type="text" class="form-control" id="copyright_text" name="copyright_text" 
                                   value="<?php echo htmlspecialchars($currentSettings['copyright_text'] ?? ''); ?>">
                            <small class="form-text text-muted"><?php echo $defaultSettings['copyright_text']['description']; ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Homepage Settings -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-home me-2 text-success"></i>Homepage Settings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="homepage_welcome_title" class="form-label">Welcome Title</label>
                            <input type="text" class="form-control" id="homepage_welcome_title" name="homepage_welcome_title" 
                                   value="<?php echo htmlspecialchars($currentSettings['homepage_welcome_title'] ?? ''); ?>">
                            <small class="form-text text-muted"><?php echo $defaultSettings['homepage_welcome_title']['description']; ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="homepage_welcome_subtitle" class="form-label">Welcome Subtitle</label>
                            <input type="text" class="form-control" id="homepage_welcome_subtitle" name="homepage_welcome_subtitle" 
                                   value="<?php echo htmlspecialchars($currentSettings['homepage_welcome_subtitle'] ?? ''); ?>">
                            <small class="form-text text-muted"><?php echo $defaultSettings['homepage_welcome_subtitle']['description']; ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="homepage_archive_title" class="form-label">Archive Section Title</label>
                            <input type="text" class="form-control" id="homepage_archive_title" name="homepage_archive_title" 
                                   value="<?php echo htmlspecialchars($currentSettings['homepage_archive_title'] ?? ''); ?>">
                            <small class="form-text text-muted"><?php echo $defaultSettings['homepage_archive_title']['description']; ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="homepage_archive_subtitle" class="form-label">Archive Section Subtitle</label>
                            <input type="text" class="form-control" id="homepage_archive_subtitle" name="homepage_archive_subtitle" 
                                   value="<?php echo htmlspecialchars($currentSettings['homepage_archive_subtitle'] ?? ''); ?>">
                            <small class="form-text text-muted"><?php echo $defaultSettings['homepage_archive_subtitle']['description']; ?></small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="homepage_show_archive" class="form-label">Show Archive Section</label>
                            <select class="form-control" id="homepage_show_archive" name="homepage_show_archive">
                                <option value="1" <?php echo ($currentSettings['homepage_show_archive'] ?? '1') == '1' ? 'selected' : ''; ?>>Yes</option>
                                <option value="0" <?php echo ($currentSettings['homepage_show_archive'] ?? '1') == '0' ? 'selected' : ''; ?>>No</option>
                            </select>
                            <small class="form-text text-muted"><?php echo $defaultSettings['homepage_show_archive']['description']; ?></small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="homepage_max_archive_items" class="form-label">Max Archive Items</label>
                            <input type="number" class="form-control" id="homepage_max_archive_items" name="homepage_max_archive_items" 
                                   value="<?php echo htmlspecialchars($currentSettings['homepage_max_archive_items'] ?? '12'); ?>" min="1" max="50">
                            <small class="form-text text-muted"><?php echo $defaultSettings['homepage_max_archive_items']['description']; ?></small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="homepage_background_color" class="form-label">Background Color</label>
                            <input type="color" class="form-control form-control-color" id="homepage_background_color" name="homepage_background_color" 
                                   value="<?php echo htmlspecialchars($currentSettings['homepage_background_color'] ?? '#ffffff'); ?>">
                            <small class="form-text text-muted"><?php echo $defaultSettings['homepage_background_color']['description']; ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Navigation -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-link me-2 text-info"></i>Footer Navigation
                    </h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addFooterLink()">
                        <i class="fas fa-plus me-1"></i>Add Link
                    </button>
                </div>
                <div class="card-body">
                    <div id="footerLinksContainer">
                        <?php foreach ($footerLinks as $index => $link): ?>
                            <div class="row align-items-center mb-2 footer-link-row">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="footer_link_text[]" 
                                           placeholder="Link Text" value="<?php echo htmlspecialchars($link['text']); ?>">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="footer_link_url[]" 
                                           placeholder="URL" value="<?php echo htmlspecialchars($link['url']); ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeFooterLink(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($footerLinks)): ?>
                            <div class="row align-items-center mb-2 footer-link-row">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="footer_link_text[]" placeholder="Link Text">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="footer_link_url[]" placeholder="URL">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeFooterLink(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-phone me-2 text-success"></i>Contact Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="contact_email" class="form-label">Contact Email</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" 
                               value="<?php echo htmlspecialchars($currentSettings['contact_email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="contact_phone" class="form-label">Contact Phone</label>
                        <input type="text" class="form-control" id="contact_phone" name="contact_phone" 
                               value="<?php echo htmlspecialchars($currentSettings['contact_phone'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-share-alt me-2 text-primary"></i>Social Media
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="social_facebook" class="form-label">Facebook URL</label>
                        <input type="url" class="form-control" id="social_facebook" name="social_facebook" 
                               value="<?php echo htmlspecialchars($currentSettings['social_facebook'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="social_twitter" class="form-label">Twitter URL</label>
                        <input type="url" class="form-control" id="social_twitter" name="social_twitter" 
                               value="<?php echo htmlspecialchars($currentSettings['social_twitter'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="social_instagram" class="form-label">Instagram URL</label>
                        <input type="url" class="form-control" id="social_instagram" name="social_instagram" 
                               value="<?php echo htmlspecialchars($currentSettings['social_instagram'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SEO Settings -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-search me-2 text-warning"></i>SEO Settings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($currentSettings['meta_description'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Recommended: 150-160 characters</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="meta_keywords" class="form-label">Meta Keywords</label>
                            <textarea class="form-control" id="meta_keywords" name="meta_keywords" rows="3"><?php echo htmlspecialchars($currentSettings['meta_keywords'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Separate keywords with commas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- About Section -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2 text-info"></i>About Section
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="about_text" class="form-label">About Text</label>
                        <textarea class="form-control" id="about_text" name="about_text" rows="4"><?php echo htmlspecialchars($currentSettings['about_text'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">HTML tags are allowed</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature Settings -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-toggle-on me-2 text-success"></i>Feature Settings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="enable_newsletter" name="enable_newsletter" value="1"
                               <?php echo ($currentSettings['enable_newsletter'] ?? '0') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="enable_newsletter">
                            Enable Newsletter Signup
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="enable_social_sharing" name="enable_social_sharing" value="1"
                               <?php echo ($currentSettings['enable_social_sharing'] ?? '0') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="enable_social_sharing">
                            Enable Social Media Sharing
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Mode -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tools me-2 text-warning"></i>Maintenance Mode
                    </h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1"
                               <?php echo ($currentSettings['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="maintenance_mode">
                            Enable Maintenance Mode
                        </label>
                    </div>
                    <div class="mb-3">
                        <label for="maintenance_message" class="form-label">Maintenance Message</label>
                        <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3"><?php echo htmlspecialchars($currentSettings['maintenance_message'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Code -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-code me-2 text-danger"></i>Custom Code
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="custom_css" class="form-label">Custom CSS</label>
                            <textarea class="form-control font-monospace" id="custom_css" name="custom_css" rows="8" 
                                      placeholder="/* Custom CSS styles */"><?php echo htmlspecialchars($currentSettings['custom_css'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">CSS code will be added to all pages</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="custom_js" class="form-label">Custom JavaScript</label>
                            <textarea class="form-control font-monospace" id="custom_js" name="custom_js" rows="8" 
                                      placeholder="// Custom JavaScript code"><?php echo htmlspecialchars($currentSettings['custom_js'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">JavaScript code will be added to all pages</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-warning" onclick="resetSettings()">
                            <i class="fas fa-undo me-2"></i>Reset to Defaults
                        </button>
                        <div>
                            <button type="button" class="btn btn-secondary me-2" onclick="window.location.reload()">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Reset Confirmation Modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="reset_settings">
                <div class="modal-header">
                    <h5 class="modal-title text-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>Reset Settings
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reset all settings to their default values?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. All your customizations will be lost.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo me-2"></i>Reset Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addFooterLink() {
    const container = document.getElementById('footerLinksContainer');
    const newRow = document.createElement('div');
    newRow.className = 'row align-items-center mb-2 footer-link-row';
    newRow.innerHTML = `
        <div class="col-md-5">
            <input type="text" class="form-control" name="footer_link_text[]" placeholder="Link Text">
        </div>
        <div class="col-md-5">
            <input type="text" class="form-control" name="footer_link_url[]" placeholder="URL">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeFooterLink(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(newRow);
}

function removeFooterLink(button) {
    const row = button.closest('.footer-link-row');
    row.remove();
}

function resetSettings() {
    const resetModal = new bootstrap.Modal(document.getElementById('resetModal'));
    resetModal.show();
}

// Form validation
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    // You can add custom validation here if needed
    const requiredFields = ['site_title', 'copyright_text'];
    let isValid = true;
    
    requiredFields.forEach(field => {
        const input = document.getElementById(field);
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields.');
    }
});
</script>

<?php include 'includes/admin_layout_footer.php'; ?>
