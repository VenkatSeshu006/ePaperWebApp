<?php
/**
 * View Clip Page
 * Display individual newspaper clips with sharing options
 */

session_start();
require_once 'includes/database.php';

// Get clip ID from URL
$clipId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$clipId) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

try {
    // Get database connection
    $conn = getConnection();
    
    // Get clip details with edition information
    $sql = "SELECT c.*, e.title as edition_title, e.date as edition_date 
            FROM clips c 
            LEFT JOIN editions e ON c.edition_id = e.id 
            WHERE c.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$clipId]);
    $clip = $stmt->fetch();
    
    if (!$clip) {
        header('HTTP/1.0 404 Not Found');
        include '404.php';
        exit;
    }
    
    // Get site settings
    $settingsResult = $conn->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    if ($settingsResult) {
        while ($row = $settingsResult->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    $siteTitle = $settings['site_title'] ?? 'Prayatnam Digital News';
    $siteTagline = $settings['site_tagline'] ?? 'Excellence in Digital Journalism';
    
    // Generate page metadata
    $pageTitle = "Clip from " . ($clip['edition_title'] ?: 'Edition') . " - " . $siteTitle;
    $pageDescription = "View this clip from " . ($clip['edition_title'] ?: 'our newspaper') . 
                      " published on " . date('F j, Y', strtotime($clip['edition_date'] ?: $clip['created_at']));
    
    // Full URL for sharing
    $fullUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . 
               $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    // Image URL for social sharing
    $imageUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . 
                $_SERVER['HTTP_HOST'] . '/' . $clip['image_path'];
    
} catch (Exception $e) {
    error_log("View clip error: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    include '500.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="newspaper clip, digital news, <?php echo htmlspecialchars($clip['edition_title'] ?? ''); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($siteTitle); ?>">
    <meta name="robots" content="index, follow">
    
    <!-- OpenGraph Meta Tags for Social Sharing -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo htmlspecialchars($fullUrl); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($imageUrl); ?>">
    <meta property="og:image:width" content="<?php echo $clip['width']; ?>">
    <meta property="og:image:height" content="<?php echo $clip['height']; ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($siteTitle); ?>">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($imageUrl); ?>">
    
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            position: relative; /* Ensure it's not sticky */
        }
        
        .clip-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 30px;
            margin: 30px 0;
        }
        
        .clip-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            border: 1px solid #dee2e6;
        }
        
        .clip-meta {
            background: var(--gray-100);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .share-buttons {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .social-btn {
            margin: 5px;
            min-width: 120px;
        }
        
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
        
        @media (max-width: 768px) {
            .clip-container {
                margin: 15px;
                padding: 20px;
            }
            
            .social-btn {
                min-width: 100%;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-bar">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h4 mb-0">
                        <a href="index.php" class="text-decoration-none text-dark">
                            <i class="fas fa-newspaper me-2"></i>
                            <?php echo htmlspecialchars($siteTitle); ?>
                        </a>
                    </h1>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="index.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Edition
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="clip-container">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Clip Image -->
                    <div class="text-center mb-4">
                        <img src="<?php echo htmlspecialchars($clip['image_path']); ?>" 
                             alt="Newspaper Clip" 
                             class="clip-image"
                             id="clipImage">
                    </div>
                    
                    <!-- Clip Actions -->
                    <div class="text-center">
                        <button class="btn btn-success me-2" onclick="downloadClip()">
                            <i class="fas fa-download"></i> Download
                        </button>
                        <button class="btn btn-secondary me-2" onclick="copyClipUrl()">
                            <i class="fas fa-copy"></i> Copy Link
                        </button>
                        <button class="btn btn-primary" onclick="printClip()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Clip Metadata -->
                    <div class="clip-meta">
                        <h5><i class="fas fa-info-circle"></i> Clip Details</h5>
                        <div class="mb-2">
                            <strong>Edition:</strong> 
                            <?php echo htmlspecialchars($clip['edition_title'] ?: 'Unknown'); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Page:</strong> <?php echo $clip['page_number']; ?>
                        </div>
                        <div class="mb-2">
                            <strong>Date:</strong> 
                            <?php echo date('F j, Y', strtotime($clip['edition_date'] ?: $clip['created_at'])); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Dimensions:</strong> 
                            <?php echo $clip['width']; ?>×<?php echo $clip['height']; ?> px
                        </div>
                        <div>
                            <strong>Created:</strong> 
                            <?php echo date('M j, Y g:i A', strtotime($clip['created_at'])); ?>
                        </div>
                    </div>
                    
                    <!-- Share Options -->
                    <div class="share-buttons">
                        <h5><i class="fas fa-share-alt"></i> Share This Clip</h5>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary social-btn" onclick="shareToSocial('facebook')">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </button>
                            <button class="btn btn-info social-btn" onclick="shareToSocial('twitter')">
                                <i class="fab fa-twitter"></i> Twitter
                            </button>
                            <button class="btn btn-success social-btn" onclick="shareToSocial('whatsapp')">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </button>
                            <button class="btn btn-primary social-btn" onclick="shareToSocial('linkedin')">
                                <i class="fab fa-linkedin-in"></i> LinkedIn
                            </button>
                            <button class="btn btn-info social-btn" onclick="shareToSocial('telegram')">
                                <i class="fab fa-telegram-plane"></i> Telegram
                            </button>
                            <button class="btn btn-warning social-btn" onclick="shareToSocial('email')">
                                <i class="fas fa-envelope"></i> Email
                            </button>
                        </div>
                        
                        <!-- QR Code Section -->
                        <div class="text-center mt-3">
                            <button class="btn btn-outline-dark btn-sm" onclick="generateQRCode()">
                                <i class="fas fa-qrcode"></i> Show QR Code
                            </button>
                            <div id="qrcode" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteTitle); ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Clip data
        const clipData = {
            id: <?php echo $clipId; ?>,
            title: '<?php echo addslashes($clip['edition_title'] ?? 'Clip'); ?>',
            url: '<?php echo addslashes($fullUrl); ?>',
            imageUrl: '<?php echo addslashes($imageUrl); ?>'
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

        // Download clip with watermark
        function downloadClip() {
            showNotification('Preparing download...', 'info');
            
            // Use the watermarked download API
            const downloadUrl = `api/download-clip.php?id=${clipData.id}&download=1`;
            
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `${clipData.title}_clip_${clipData.id}.jpg`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            showNotification('Download started!', 'success');
        }

        // Copy clip URL
        function copyClipUrl() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(clipData.url).then(() => {
                    showNotification('Link copied to clipboard!', 'success');
                }).catch(() => {
                    fallbackCopy(clipData.url);
                });
            } else {
                fallbackCopy(clipData.url);
            }
        }

        function fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showNotification('Link copied to clipboard!', 'success');
            } catch (err) {
                showNotification('Failed to copy link', 'error');
            }
            document.body.removeChild(textArea);
        }

        // Print clip
        function printClip() {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print Clip - ${clipData.title}</title>
                    <style>
                        body { margin: 20px; text-align: center; }
                        img { max-width: 100%; height: auto; }
                        .header { margin-bottom: 20px; font-family: Arial, sans-serif; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>${clipData.title}</h2>
                        <p>Clip ID: ${clipData.id} | <?php echo htmlspecialchars($siteTitle); ?></p>
                    </div>
                    <img src="${clipData.imageUrl}" alt="Newspaper Clip">
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
            showNotification('Print dialog opened', 'info');
        }

        // Social sharing
        function shareToSocial(platform) {
            const shareText = `Check out this clip from ${clipData.title}`;
            const encodedUrl = encodeURIComponent(clipData.url);
            const encodedText = encodeURIComponent(shareText);

            let targetUrl;
            switch(platform) {
                case 'facebook':
                    targetUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
                    break;
                case 'twitter':
                    targetUrl = `https://twitter.com/intent/tweet?text=${encodedText}&url=${encodedUrl}`;
                    break;
                case 'whatsapp':
                    targetUrl = `https://wa.me/?text=${encodedText}%20${encodedUrl}`;
                    break;
                case 'linkedin':
                    targetUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`;
                    break;
                case 'telegram':
                    targetUrl = `https://t.me/share/url?url=${encodedUrl}&text=${encodedText}`;
                    break;
                case 'email':
                    targetUrl = `mailto:?subject=${encodedText}&body=${encodedUrl}`;
                    break;
                default:
                    showNotification('Platform not supported', 'warning');
                    return;
            }

            window.open(targetUrl, '_blank', 'width=600,height=400');
            showNotification(`Sharing to ${platform}...`, 'info');
        }

        // Generate QR Code
        function generateQRCode() {
            const qrDiv = document.getElementById('qrcode');
            qrDiv.innerHTML = '';
            
            const qrSize = 150;
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${qrSize}x${qrSize}&data=${encodeURIComponent(clipData.url)}`;
            
            const qrImg = document.createElement('img');
            qrImg.src = qrUrl;
            qrImg.alt = 'QR Code';
            qrImg.className = 'img-fluid border rounded';
            qrImg.style.maxWidth = qrSize + 'px';
            qrDiv.appendChild(qrImg);
            
            showNotification('QR code generated!', 'success');
        }
    </script>
</body>
</html>