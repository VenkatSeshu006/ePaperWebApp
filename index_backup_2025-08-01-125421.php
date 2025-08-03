<?php
/**
 * E-Paper CMS v2.0 - Optimized Digital Newspaper Viewer
 * Clean, fast, and fully functional
 */

// Start session and load core files
session_start();
error_reporting(E_ERROR | E_PARSE); // Only show critical errors
require_once 'includes/database.php';

// Get database connection
$conn = getConnection();

// Load dynamic page settings (optimized query)
$pageSettings = [];
$settingsResult = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($settingsResult) {
    while ($row = $settingsResult->fetch()) {
        $pageSettings[$row['setting_key']] = $row['setting_value'];
    }
}

// Set default values
$siteTitle = $pageSettings['site_title'] ?? 'Prayatnam Digital News';
$siteTagline = $pageSettings['site_tagline'] ?? 'Excellence in Digital Journalism';
$headerLogoText = $pageSettings['header_logo_text'] ?? 'Prayatnam Digital News';
$copyrightText = $pageSettings['copyright_text'] ?? 'Prayatnam Digital News. All rights reserved.';
$contactEmail = $pageSettings['contact_email'] ?? 'info@prayatnam.com';
$contactPhone = $pageSettings['contact_phone'] ?? '+1 234 567 8900';
$metaDescription = $pageSettings['meta_description'] ?? 'Stay informed with Prayatnam Digital News - Your trusted source for comprehensive news coverage and digital journalism excellence.';
$metaKeywords = $pageSettings['meta_keywords'] ?? 'epaper, digital newspaper, news, prayatnam, online news';

// Get the latest edition from database
$sql = "SELECT id, title, date, thumbnail_path FROM editions ORDER BY date DESC LIMIT 1";
$result = $conn->query($sql);
$latest = $result ? $result->fetch() : null;

// Get edition ID from URL or use latest
$editionId = isset($_GET['id']) ? (int)$_GET['id'] : ($latest ? $latest['id'] : null);
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Get current edition
$currentEdition = null;
$editionPages = [];
$totalPages = 0;

if ($editionId) {
    // Get edition details
    $stmt = $conn->prepare("SELECT * FROM editions WHERE id = ?");
    $stmt->execute([$editionId]);
    $currentEdition = $stmt->fetch();
    
    if ($currentEdition) {
        // Get pages for this edition
        $pagesResult = $conn->query("SELECT * FROM pages WHERE edition_id = $editionId ORDER BY page_number");
        while ($pageRow = $pagesResult->fetch()) {
            $editionPages[] = $pageRow;
        }
        $totalPages = count($editionPages);
    }
}

// Set page title
$pageTitle = $currentEdition ? $currentEdition['title'] : 'Latest Edition';
$pageTitle .= " - $siteTitle";

// Close database connection (PDO closes automatically, but we can set to null if needed)
$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($metaKeywords); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($siteTitle); ?>">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#007bff">
    
    <!-- OpenGraph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($siteTagline); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E📰%3C/text%3E%3C/svg%3E">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    
    <style>
        :root {
            --primary-red: #f53003;
            --dark-red: #d32d0a;
            --light-red: #ff5722;
            --pure-white: #ffffff;
            --pure-black: #000000;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-700: #495057;
            --gray-800: #343a40;
            --shadow-light: rgba(245, 48, 3, 0.1);
            --shadow-medium: rgba(245, 48, 3, 0.2);
        }
        
        * { box-sizing: border-box; }
        
        html, body {
            height: 100%;
            scroll-behavior: smooth;
            margin: 0;
            padding: 0;
        }
        
        body {
            background: linear-gradient(135deg, var(--pure-white) 0%, var(--gray-100) 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--pure-black);
        }
        
        .header-bar {
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .logo-section {
            border-right: 1px solid #ddd;
            padding-right: 20px;
            margin-right: 15px;
        }
        
        .logo-section h1 {
            color: #333;
            font-weight: 700;
            margin: 0;
        }
        
        .edition-info {
            padding-left: 1.5rem;
            position: relative;
        }
        
        .edition-info::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: linear-gradient(180deg, var(--primary-red), var(--dark-red));
            border-radius: 2px;
        }
        
        .viewer-container {
            display: flex;
            min-height: calc(100vh - 150px);
            margin-bottom: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-right: 1px solid #dee2e6;
            overflow-y: auto;
            padding: 20px 15px;
            max-height: calc(100vh - 120px);
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-radius: 3px;
        }
        
        .page-thumbnail {
            margin-bottom: 12px;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .page-thumbnail:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,123,255,0.15);
        }
        
        .page-thumbnail.active {
            border-color: #007bff;
            box-shadow: 0 4px 16px rgba(0,123,255,0.25);
        }
        
        .page-thumbnail img {
            width: 100%;
            height: auto;
            display: block;
            max-height: 160px;
            object-fit: cover;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            position: relative;
        }
        
        .toolbar {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .toolbar .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.3s ease;
        }
        
        .full-image {
            width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .full-image:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .simple-footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        /* Modal Styles */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .viewer-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                max-height: 200px;
                order: 2;
            }
            
            .main-content {
                order: 1;
            }
            
            .toolbar {
                justify-content: center;
            }
        }
        
        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification.success { background: #28a745; }
        .notification.error { background: #dc3545; }
        .notification.info { background: #17a2b8; }
        .notification.warning { background: #ffc107; color: #212529; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-bar">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center header-left">
                    <div class="logo-section">
                        <h1 class="h4 mb-0">
                            <i class="fas fa-newspaper me-2"></i>
                            <?php echo htmlspecialchars($headerLogoText); ?>
                        </h1>
                        <small class="text-muted"><?php echo htmlspecialchars($siteTagline); ?></small>
                    </div>
                    
                    <?php if ($currentEdition): ?>
                    <div class="edition-info">
                        <div class="edition-title">
                            <i class="fas fa-calendar-alt"></i>
                            <?php echo htmlspecialchars($currentEdition['title']); ?>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-clock"></i>
                            <?php echo date('F j, Y', strtotime($currentEdition['date'])); ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="header-right">
                    <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#socialShareModal">
                        <i class="fas fa-share-alt"></i> Share
                    </button>
                    <a href="admin/" class="btn btn-outline-secondary">
                        <i class="fas fa-cog"></i> Admin
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid px-3">
        <?php if ($currentEdition && !empty($editionPages)): ?>
        <div class="viewer-container">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-header text-center">
                    <h6 class="text-center mb-2">
                        <i class="fas fa-images"></i>
                        Pages (<?php echo $totalPages; ?>)
                    </h6>
                    <small class="text-muted">
                        <i class="fas fa-mouse-pointer"></i>
                        Click to navigate
                    </small>
                </div>
                
                <?php foreach ($editionPages as $index => $pageData): ?>
                <div class="page-thumbnail <?php echo ($index + 1 == $page) ? 'active' : ''; ?>" 
                     onclick="navigateToPage(<?php echo $index + 1; ?>)" 
                     data-page="<?php echo $index + 1; ?>">
                    <img src="<?php echo htmlspecialchars($pageData['image_path']); ?>" 
                         alt="Page <?php echo $index + 1; ?>" 
                         class="lazy-load"
                         loading="lazy">
                    <div class="page-number">Page <?php echo $index + 1; ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Main Content Area -->
            <div class="main-content">
                <!-- Toolbar -->
                <div class="toolbar">
                    <button class="btn btn-primary" onclick="downloadAsPDF()">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <button class="btn btn-success" onclick="openPrintDialog()">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#clipModal">
                        <i class="fas fa-cut"></i> Clip
                    </button>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#socialShareModal">
                        <i class="fas fa-share-alt"></i> Share
                    </button>
                    <button class="btn btn-secondary" onclick="generateQRCode()">
                        <i class="fas fa-qrcode"></i> QR Code
                    </button>
                    
                    <div class="ms-auto d-flex align-items-center">
                        <span class="me-3">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                        <?php if ($page > 1): ?>
                        <a href="?id=<?php echo $editionId; ?>&page=<?php echo $page - 1; ?>" class="btn btn-outline-primary btn-sm me-1">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                        <a href="?id=<?php echo $editionId; ?>&page=<?php echo $page + 1; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Current Page Display -->
                <?php if (isset($editionPages[$page - 1])): ?>
                <div class="text-center">
                    <img src="<?php echo htmlspecialchars($editionPages[$page - 1]['image_path']); ?>" 
                         alt="Page <?php echo $page; ?>" 
                         class="full-image" 
                         id="currentPageImage">
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-newspaper fa-4x text-muted mb-4"></i>
            <h2 class="text-muted">No Edition Available</h2>
            <p class="text-muted">Please check back later or contact the administrator.</p>
            <a href="admin/" class="btn btn-primary">Go to Admin Panel</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Clip Modal -->
    <div class="modal fade" id="clipModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-cut"></i> Clip & Share
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Download Options</h6>
                            <div class="d-grid gap-2 mb-3">
                                <button class="btn btn-success w-100" onclick="downloadClipImage()">
                                    <i class="fas fa-download"></i> Download Image
                                </button>
                                <button class="btn btn-secondary w-100" onclick="copyClipUrl()">
                                    <i class="fas fa-copy"></i> Copy Link
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Share Options</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <button class="btn btn-primary social-share-btn w-100" onclick="shareClipToSocial('facebook')" title="Share on Facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-info social-share-btn w-100" onclick="shareClipToSocial('twitter')" title="Share on Twitter">
                                        <i class="fab fa-twitter"></i>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-success social-share-btn w-100" onclick="shareClipToSocial('whatsapp')" title="Share on WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-primary social-share-btn w-100" onclick="shareClipToSocial('linkedin')" title="Share on LinkedIn">
                                        <i class="fab fa-linkedin-in"></i>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-info social-share-btn w-100" onclick="shareClipToSocial('telegram')" title="Share on Telegram">
                                        <i class="fab fa-telegram-plane"></i>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-warning social-share-btn w-100" onclick="shareClipToSocial('email')" title="Share via Email">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-danger social-share-btn w-100" onclick="shareClipToSocial('reddit')" title="Share on Reddit">
                                        <i class="fab fa-reddit-alien"></i>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-dark social-share-btn w-100" onclick="showClipQR()" title="Show QR Code">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="socialShareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-share-alt"></i> Share This Edition
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Share URL:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="shareUrl" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyShareUrl()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-4">
                            <button class="btn btn-primary w-100" onclick="shareToSocial('facebook')">
                                <i class="fab fa-facebook-f"></i><br>Facebook
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-info w-100" onclick="shareToSocial('twitter')">
                                <i class="fab fa-twitter"></i><br>Twitter
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-success w-100" onclick="shareToSocial('whatsapp')">
                                <i class="fab fa-whatsapp"></i><br>WhatsApp
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-primary w-100" onclick="shareToSocial('linkedin')">
                                <i class="fab fa-linkedin-in"></i><br>LinkedIn
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-info w-100" onclick="shareToSocial('telegram')">
                                <i class="fab fa-telegram-plane"></i><br>Telegram
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-warning w-100" onclick="shareToSocial('email')">
                                <i class="fas fa-envelope"></i><br>Email
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <div id="qrcode"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="simple-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="footer-copyright mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($copyrightText); ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>" class="me-3">
                            <i class="fas fa-envelope"></i>
                        </a>
                        <a href="tel:<?php echo htmlspecialchars($contactPhone); ?>">
                            <i class="fas fa-phone"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        // Global variables
        window.editionData = {
            editionId: <?php echo $editionId ?: 'null'; ?>,
            editionTitle: '<?php echo addslashes($currentEdition['title'] ?? 'E-Paper'); ?>',
            totalPages: <?php echo $totalPages; ?>,
            currentPage: <?php echo $page; ?>
        };

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.classList.add('show'), 100);
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => document.body.removeChild(notification), 300);
            }, 3000);
        }

        // Navigation
        function navigateToPage(pageNum) {
            const url = new URL(window.location);
            url.searchParams.set('page', pageNum);
            window.location.href = url.toString();
        }

        // Download functions
        function downloadClipImage() {
            const activeImage = document.getElementById('currentPageImage');
            if (!activeImage) {
                showNotification('No image available for download', 'error');
                return;
            }
            
            const link = document.createElement('a');
            link.href = activeImage.src;
            link.download = `${window.editionData.editionTitle}_Page_${window.editionData.currentPage}.jpg`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            showNotification('Image download started!', 'success');
        }

        function downloadAsPDF() {
            showNotification('PDF generation in progress...', 'info');
            // Add PDF generation logic here
            setTimeout(() => showNotification('PDF feature coming soon!', 'warning'), 1000);
        }

        // Copy functions
        function copyClipUrl() {
            const currentUrl = window.location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(currentUrl).then(() => {
                    showNotification('URL copied to clipboard!', 'success');
                }).catch(() => {
                    fallbackCopy(currentUrl);
                });
            } else {
                fallbackCopy(currentUrl);
            }
        }

        function copyShareUrl() {
            const shareUrlInput = document.getElementById('shareUrl');
            shareUrlInput.select();
            document.execCommand('copy');
            showNotification('URL copied!', 'success');
        }

        function fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showNotification('URL copied to clipboard!', 'success');
            } catch (err) {
                showNotification('Failed to copy URL', 'error');
            }
            document.body.removeChild(textArea);
        }

        // Share functions
        function shareClipToSocial(platform) {
            shareToSocial(platform);
        }

        function shareToSocial(platform) {
            const currentUrl = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            const text = encodeURIComponent(`Check out this digital newspaper: ${document.title}`);
            
            const shareUrls = {
                facebook: `https://www.facebook.com/sharer/sharer.php?u=${currentUrl}`,
                twitter: `https://twitter.com/intent/tweet?url=${currentUrl}&text=${text}`,
                linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${currentUrl}`,
                whatsapp: `https://wa.me/?text=${text}%20${currentUrl}`,
                telegram: `https://t.me/share/url?url=${currentUrl}&text=${text}`,
                email: `mailto:?subject=${title}&body=${text}%0A%0A${currentUrl}`,
                reddit: `https://reddit.com/submit?url=${currentUrl}&title=${title}`,
                pinterest: `https://pinterest.com/pin/create/button/?url=${currentUrl}&description=${text}`
            };

            if (shareUrls[platform]) {
                window.open(shareUrls[platform], '_blank', 'width=600,height=400,scrollbars=yes,resizable=yes');
                showNotification(`Opening ${platform.charAt(0).toUpperCase() + platform.slice(1)}...`, 'success');
            } else {
                showNotification('Platform not supported', 'error');
            }
        }

        // QR Code functions
        function showClipQR() {
            generateQRCode();
        }

        function generateQRCode() {
            const qrDiv = document.getElementById('qrcode');
            if (!qrDiv) return;
            
            qrDiv.innerHTML = '';
            const currentUrl = window.location.href;
            const qrSize = 150;
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${qrSize}x${qrSize}&data=${encodeURIComponent(currentUrl)}`;
            
            const qrImg = document.createElement('img');
            qrImg.src = qrUrl;
            qrImg.alt = 'QR Code';
            qrImg.className = 'img-fluid border rounded';
            qrImg.style.maxWidth = qrSize + 'px';
            qrDiv.appendChild(qrImg);
            
            showNotification('QR code generated!', 'success');
        }

        // Print function
        function openPrintDialog() {
            window.print();
            showNotification('Print dialog opened', 'info');
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set share URL
            const shareUrlInput = document.getElementById('shareUrl');
            if (shareUrlInput) {
                shareUrlInput.value = window.location.href;
            }

            // Generate QR code when share modal opens
            const shareModal = document.getElementById('socialShareModal');
            if (shareModal) {
                shareModal.addEventListener('show.bs.modal', generateQRCode);
            }

            // Lazy loading for images
            const lazyImages = document.querySelectorAll('img[loading="lazy"]');
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        imageObserver.unobserve(entry.target);
                    }
                });
            });
            lazyImages.forEach(img => imageObserver.observe(img));

            console.log('E-Paper CMS v2.0 - Optimized version loaded successfully');
        });
    </script>
</body>
</html>
