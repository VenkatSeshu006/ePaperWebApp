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

// Homepage dynamic settings
$homepageWelcomeTitle = $pageSettings['homepage_welcome_title'] ?? 'Welcome to Digital News';
$homepageWelcomeSubtitle = $pageSettings['homepage_welcome_subtitle'] ?? 'Stay informed with the latest news and updates';
$homepageArchiveTitle = $pageSettings['homepage_archive_title'] ?? 'Archive';
$homepageArchiveSubtitle = $pageSettings['homepage_archive_subtitle'] ?? 'Browse all published editions';
$homepageShowArchive = ($pageSettings['homepage_show_archive'] ?? '1') === '1';
$homepageMaxArchiveItems = (int)($pageSettings['homepage_max_archive_items'] ?? 12);
$homepageBackgroundColor = $pageSettings['homepage_background_color'] ?? '#ffffff';
$homepageTextColor = $pageSettings['homepage_text_color'] ?? '#333333';

// Get the latest published edition from database
$sql = "SELECT id, title, date, thumbnail_path FROM editions WHERE status = 'published' ORDER BY date DESC, created_at DESC LIMIT 1";
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
    // Get edition details (only if published)
    $stmt = $conn->prepare("SELECT * FROM editions WHERE id = ? AND status = 'published'");
    $stmt->execute([$editionId]);
    $currentEdition = $stmt->fetch();
    
    if ($currentEdition) {
        // Get pages for this edition - but check if we should prioritize PDF viewing
        $pagesResult = $conn->query("SELECT * FROM edition_pages WHERE edition_id = $editionId ORDER BY page_number");
        if ($pagesResult) {
            while ($pageRow = $pagesResult->fetch()) {
                $editionPages[] = $pageRow;
            }
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
            padding: 20px 0;
            position: relative; /* Changed from sticky to relative */
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .header-bar h1 {
            color: #333;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .header-bar .text-muted {
            font-size: 0.9rem;
            font-style: italic;
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
            width: 200px;
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
            overflow-y: auto;
            padding: 15px;
            max-height: calc(100vh - 120px);
        }
        
        .sidebar::-webkit-scrollbar {
            width: 8px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: #f1f3f4;
            border-radius: 4px;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
        }
        
        .sidebar-header {
            margin-bottom: 15px;
            padding: 12px;
            background: rgba(0,123,255,0.1);
            border-radius: 8px;
            color: #495057;
            border: 1px solid rgba(0,123,255,0.2);
        }
        
        .sidebar-header h6 {
            font-weight: 500;
            margin-bottom: 4px;
            font-size: 0.9rem;
            color: #007bff;
        }
        
        .sidebar-header small {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .page-thumbnail {
            margin-bottom: 10px;
            cursor: pointer;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            overflow: hidden;
            transition: all 0.2s ease;
            background: #fff;
            position: relative;
        }
        
        .page-thumbnail:hover {
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0,123,255,0.15);
        }
        
        .page-thumbnail.active {
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0,123,255,0.2);
        }
        
        .page-thumbnail img {
            width: 100%;
            height: auto;
            display: block;
            max-height: 120px;
            object-fit: cover;
        }
        
        .page-number {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,123,255,0.9);
            color: white;
            text-align: center;
            padding: 4px 6px;
            font-size: 0.75rem;
            font-weight: 500;
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
            cursor: pointer; /* Indicate clickable */
        }
        
        .full-image:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            transform: scale(1.01);
        }
        
        /* Fullscreen Modal */
        .fullscreen-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.95);
            backdrop-filter: blur(5px);
        }
        
        .fullscreen-content {
            position: relative;
            margin: auto;
            padding: 20px;
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 20px;
            overflow: hidden;
        }
        
        .fullscreen-image-container {
            width: 85%;
            max-height: 90vh;
            overflow: auto;
            border-radius: 8px;
            box-shadow: 0 10px 50px rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            scroll-behavior: smooth;
        }
        
        .fullscreen-image {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 6px;
        }
        
        .fullscreen-image-container::-webkit-scrollbar {
            width: 16px;
            height: 16px;
        }
        
        .fullscreen-image-container::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .fullscreen-image-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, rgba(255,255,255,0.4), rgba(255,255,255,0.6));
            border-radius: 8px;
            border: 2px solid rgba(0,0,0,0.1);
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .fullscreen-image-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, rgba(255,255,255,0.6), rgba(255,255,255,0.8));
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.2);
        }
        
        .fullscreen-image-container::-webkit-scrollbar-thumb:active {
            background: linear-gradient(135deg, rgba(255,255,255,0.8), rgba(255,255,255,1));
        }
        
        .fullscreen-image-container::-webkit-scrollbar-corner {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        
        .fullscreen-toolbar {
            display: flex;
            flex-direction: column;
            gap: 15px;
            padding: 20px;
            background: rgba(0,0,0,0.8);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            height: fit-content;
        }
        
        .fullscreen-toolbar .btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            border-radius: 15px;
            padding: 15px 25px;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            width: 180px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .fullscreen-toolbar .btn:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.3);
            transform: translateX(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        .fullscreen-toolbar .btn-primary {
            background: rgba(0,123,255,0.8);
            border-color: rgba(0,123,255,0.8);
        }
        
        .fullscreen-toolbar .btn-primary:hover {
            background: rgba(0,123,255,1);
            border-color: rgba(0,123,255,1);
        }
        
        .fullscreen-toolbar .btn-success {
            background: rgba(40,167,69,0.8);
            border-color: rgba(40,167,69,0.8);
        }
        
        .fullscreen-toolbar .btn-success:hover {
            background: rgba(40,167,69,1);
            border-color: rgba(40,167,69,1);
        }
        
        .fullscreen-toolbar .btn-warning {
            background: rgba(255,193,7,0.8);
            border-color: rgba(255,193,7,0.8);
            color: #212529;
        }
        
        .fullscreen-toolbar .btn-warning:hover {
            background: rgba(255,193,7,1);
            border-color: rgba(255,193,7,1);
            color: #212529;
        }
        
        .fullscreen-close {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(220,53,69,0.9);
            border: none;
            color: white;
            font-size: 1.5rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            z-index: 10001;
            border: 2px solid rgba(255,255,255,0.2);
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .fullscreen-close:hover {
            background: rgba(220,53,69,1);
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(220,53,69,0.4);
        }
        
        /* Responsive fullscreen */
        @media (max-width: 768px) {
            .fullscreen-content {
                flex-direction: column;
                padding: 10px;
                align-items: center;
            }
            
            .fullscreen-image-container {
                width: 95%;
                max-height: 80vh;
                -webkit-overflow-scrolling: touch; /* Enable smooth scrolling on iOS */
            }
            
            .fullscreen-toolbar {
                flex-direction: row;
                gap: 10px;
                padding: 15px;
                width: 95%;
                justify-content: center;
            }
            
            .fullscreen-toolbar .btn {
                width: auto;
                padding: 12px 20px;
                font-size: 0.9rem;
            }
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
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .toolbar > div {
                width: 100%;
                justify-content: center;
            }
            
            .toolbar .ms-auto {
                margin-left: auto !important;
                margin-right: auto !important;
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

        /* Clipping Tool Styles */
        #pageImageContainer {
            position: relative;
            display: inline-block;
            max-width: 100%;
        }

        .clip-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.05);
            z-index: 10; /* Lower z-index to not interfere with cropper */
            border-radius: 8px;
            pointer-events: none; /* Allow click-through to cropper */
        }
        
        /* When clipping is active, ensure cropper elements are accessible */
        .clipping-active .clip-overlay {
            pointer-events: none; /* Keep as none to allow cropper interaction */
        }
        
        /* Ensure cropper elements have higher z-index */
        .clipping-active .cropper-container {
            z-index: 1000;
        }

        /* Sticky clip toolbar - positioned relative to crop box */
        .clip-toolbar {
            position: absolute;
            top: -50px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 8px 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1002;
            transition: all 0.2s ease;
            border: 1px solid rgba(0, 0, 0, 0.1);
            min-width: 180px;
            pointer-events: auto;
        }

        .clip-toolbar-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .clip-toolbar .btn {
            border-radius: 4px;
            font-size: 12px;
            padding: 6px 14px;
            font-weight: 600;
            transition: all 0.2s ease;
            border: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            min-width: 75px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .clip-toolbar .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .clip-toolbar .btn i {
            margin-right: 4px;
        }

        /* Cancel button styling */
        .clip-toolbar .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        .clip-toolbar .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            color: white;
        }

        /* Save Clip button styling */
        .clip-toolbar .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }

        .clip-toolbar .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
            color: white;
        }

        /* Initially hide toolbar */
        .clip-toolbar {
            opacity: 0;
            visibility: hidden;
        }

        /* Show toolbar when clipping is active */
        .clipping-active .clip-toolbar {
            opacity: 1;
            visibility: visible;
        }

        /* Position toolbar as child of crop box */
        .cropper-crop-box {
            position: relative;
        }

        /* Toolbar attached to crop box */
        .cropper-crop-box .clip-toolbar {
            position: absolute !important;
            top: -50px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            z-index: 1002 !important;
        }

        /* Edition info styles for main toolbar */
        .edition-info {
            padding: 8px 16px;
            text-align: center;
            flex: 1;
            max-width: 400px;
        }

        .edition-title {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 2px;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .edition-date {
            font-size: 12px;
            color: #6c757d;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .edition-date i {
            color: #28a745;
            font-size: 11px;
        }

        /* Responsive edition info */
        @media (max-width: 768px) {
            .edition-info {
                max-width: none;
                padding: 4px 8px;
            }
            
            .edition-title {
                font-size: 14px;
            }
            
            .edition-date {
                font-size: 11px;
            }
            
            .edition-date span {
                display: none;
            }
        }

        /* Position toolbar relative to crop box */
        .cropper-container .clip-toolbar {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
        }

        /* Responsive toolbar */
        @media (max-width: 768px) {
            .clip-toolbar {
                padding: 6px 10px !important;
                min-width: 160px !important;
                top: -45px !important;
            }
            
            .clip-toolbar .btn {
                font-size: 11px !important;
                padding: 5px 10px !important;
                min-width: 65px !important;
            }
            
            .clip-toolbar .btn i {
                margin-right: 3px !important;
            }
        }

        /* Hide toolbar text on very small screens */
        @media (max-width: 480px) {
            .clip-toolbar {
                min-width: 120px !important;
                padding: 5px 8px !important;
                top: -40px !important;
            }
            
            .clip-toolbar .btn {
                padding: 4px 8px !important;
                min-width: 45px !important;
                font-size: 10px !important;
            }
            
            .clip-toolbar .btn span {
                display: none !important;
            }
            
            .clip-toolbar-content {
                gap: 6px !important;
            }
        }

        .crop-dimensions div {
            margin-bottom: 5px;
        }

        /* Clip mode active states */
        .clipping-active #currentPageImage {
            cursor: crosshair;
        }

        .clipping-active .toolbar {
            pointer-events: none;
            opacity: 0.7;
        }

        .clipping-active #clipModeBtn {
            pointer-events: all;
            opacity: 1;
            background-color: #dc3545;
            border-color: #dc3545;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Cropper.js overlay styles */
        .cropper-container {
            direction: ltr;
            font-size: 0;
            line-height: 0;
            position: relative;
            touch-action: none;
            user-select: none;
        }

        .cropper-container img {
            display: block;
            height: 100%;
            image-orientation: 0deg;
            max-height: none !important;
            max-width: none !important;
            min-height: 0 !important;
            min-width: 0 !important;
            width: 100%;
        }

        /* Preview Modal Styles */
        .clip-preview-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .clip-preview-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 90vw;
            max-height: 90vh;
            overflow: auto;
            position: relative;
        }

        .preview-canvas {
            max-width: 100%;
            max-height: 60vh;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .preview-actions {
            margin-top: 20px;
            text-align: center;
        }

        .share-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }

        .crop-container {
            position: relative;
            max-height: 500px;
            overflow: hidden;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .crop-image {
            max-width: 100%;
            display: block;
        }

        .crop-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .crop-info ul li {
            padding: 5px 0;
            font-size: 14px;
        }

        .crop-dimensions {
            background: white;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            font-size: 12px;
        }

        .preview-container {
            text-align: center;
        }

        .preview-image-container {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            background: #f8f9fa;
            margin: 15px 0;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-panel {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .action-group {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 15px;
        }

        .action-group:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .success-message {
            padding: 40px 20px;
        }

        .clip-step {
            min-height: 400px;
        }

        /* Enhanced PDF Viewer Styles */
        .edition-info-panel {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #e9ecef;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .sidebar-actions .btn {
            font-size: 0.85rem;
        }

        .pdf-viewer-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .pdf-embed-wrapper {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .pdf-controls {
            margin-top: 15px;
        }

        .pdf-controls .btn-group .btn {
            font-size: 0.85rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .clip-controls-top {
                flex-direction: column;
                gap: 10px;
                top: -80px;
                padding: 15px;
            }
            
            .clip-info-panel {
                position: relative;
                top: auto;
                right: auto;
                margin-top: 15px;
                width: 100%;
            }
            
            .clip-overlay {
                position: relative;
                background: transparent;
            }
            
            .crop-container {
                max-height: 300px;
            }
            
            .modal-xl {
                margin: 10px;
                max-width: calc(100% - 20px);
            }
            
            .crop-info ul li {
                font-size: 12px;
            }
            
            .action-panel {
                margin-top: 20px;
            }

            .share-options {
                flex-direction: column;
            }
        }
        
        /* Date Filter Styles */
        .date-filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .filter-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #495057;
            margin: 0;
            white-space: nowrap;
        }
        
        .filter-input {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .filter-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        
        .filter-btn {
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .filter-btn:hover {
            background: #0056b3;
        }
        
        .filter-btn.clear {
            background: #6c757d;
        }
        
        .filter-btn.clear:hover {
            background: #545b62;
        }
        
        .filter-stats {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .filter-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                justify-content: space-between;
            }
        }
        
        /* Archive Section Styles */
        .archive-section {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .section-header {
            margin-bottom: 30px;
        }
        
        .section-title {
            color: #333;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .section-subtitle {
            font-size: 1.1rem;
        }
        
        .archive-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1400px) {
            .archive-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }
        
        @media (max-width: 1200px) {
            .archive-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        @media (max-width: 992px) {
            .archive-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .archive-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }
        
        @media (max-width: 576px) {
            .archive-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
        
        .archive-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .archive-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            border-color: #007bff;
        }
        
        .archive-thumbnail {
            position: relative;
            height: 200px;
            background: #f8f9fa;
            overflow: hidden;
        }
        
        .archive-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .archive-card:hover .archive-thumbnail img {
            transform: scale(1.05);
        }
        
        .no-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            background: #f8f9fa;
        }
        
        .archive-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .archive-card:hover .archive-overlay {
            opacity: 1;
        }
        
        .archive-info {
            padding: 20px;
        }
        
        .archive-title {
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .archive-date {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .archive-description {
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        @media (max-width: 768px) {
            .archive-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
            
            .archive-section {
                padding: 20px;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-bar">
        <div class="container-fluid">
            <div class="d-flex justify-content-center align-items-center">
                <div class="text-center">
                    <h1 class="h3 mb-1">
                        <i class="fas fa-newspaper me-2"></i>
                        <?php echo htmlspecialchars($headerLogoText); ?>
                    </h1>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($siteTagline); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid px-3">
        <?php if ($currentEdition): ?>
        <div class="viewer-container">
            <?php if (!empty($editionPages)): ?>
            <!-- Sidebar (only show if we have individual pages) -->
            <div class="sidebar">
                <div class="sidebar-header text-center">
                    <h6 class="text-center mb-1">
                        <i class="fas fa-file-image"></i>
                        <?php echo $totalPages; ?> Pages
                    </h6>
                    <small class="text-muted">
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
                    <div class="page-number"><?php echo $index + 1; ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Main Content Area -->
            <div class="main-content">
                <!-- Toolbar -->
                <div class="toolbar">
                    <!-- Navigation on the left -->
                    <div class="d-flex align-items-center">
                        <?php if ($page > 1): ?>
                        <a href="?id=<?php echo $editionId; ?>&page=<?php echo $page - 1; ?>" class="btn btn-outline-primary btn-sm me-2">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        <span class="me-2">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                        <?php if ($page < $totalPages): ?>
                        <a href="?id=<?php echo $editionId; ?>&page=<?php echo $page + 1; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Edition info in the center -->
                    <div class="flex-grow-1 text-center">
                        <div class="edition-info">
                            <h5 class="edition-title mb-0"><?php echo htmlspecialchars($currentEdition['title'] ?? 'E-Paper Edition'); ?></h5>
                            <small class="edition-date text-muted">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?php echo date('F j, Y', strtotime($currentEdition['date'] ?? 'now')); ?>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Action buttons on the right -->
                    <div class="d-flex align-items-center">
                        <button class="btn btn-info me-2" onclick="activateClipMode()" id="clipModeBtn" 
                                title="Click to start clipping. Drag to select, resize corners/edges, double-click to save.">
                            <i class="fas fa-cut"></i> Clip
                        </button>
                        <?php if ($currentEdition && $currentEdition['pdf_path']): ?>
                        <button class="btn btn-primary me-2" onclick="downloadCurrentEdition()">
                            <i class="fas fa-download"></i> Download
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#socialShareModal">
                            <i class="fas fa-share-alt"></i> Share
                        </button>
                    </div>
                </div>

                <!-- Current Page Display -->
                <?php if (isset($editionPages[$page - 1])): ?>
                <div class="text-center" id="pageImageContainer">
                    <img src="<?php echo htmlspecialchars($editionPages[$page - 1]['image_path']); ?>" 
                         alt="Page <?php echo $page; ?>" 
                         class="full-image" 
                         id="currentPageImage"
                         onclick="openFullscreen('<?php echo htmlspecialchars($editionPages[$page - 1]['image_path']); ?>')"
                         title="Click to view in fullscreen">
                    
                    <!-- Clip Mode Overlay (hidden by default) -->
                    <div id="clipOverlay" class="clip-overlay" style="display: none;">
                        <!-- Sticky toolbar for clipping actions -->
                        <div id="clipToolbar" class="clip-toolbar">
                            <div class="clip-toolbar-content">
                                <button class="btn btn-sm btn-danger me-2" onclick="exitClipMode()" title="Cancel clipping (Esc)">
                                    <i class="fas fa-times"></i><span> Cancel</span>
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="confirmCrop()" title="Save the selected clip (S)">
                                    <i class="fas fa-download"></i><span> Save Clip</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
            <!-- PDF-based Edition Display (when no individual pages) -->
            <div class="viewer-container">
                <!-- Sidebar for PDF info and navigation -->
                <div class="sidebar">
                    <div class="sidebar-header text-center">
                        <h6 class="text-center mb-1">
                            <i class="fas fa-file-pdf"></i>
                            PDF Edition
                        </h6>
                        <small class="text-muted">
                            <?php echo htmlspecialchars($currentEdition['title']); ?>
                        </small>
                    </div>
                    
                    <!-- Edition Info -->
                    <div class="edition-info-panel mt-3">
                        <div class="info-item">
                            <i class="fas fa-calendar-alt text-primary"></i>
                            <span><?php echo date('M j, Y', strtotime($currentEdition['date'])); ?></span>
                        </div>
                        <?php if ($currentEdition['total_pages'] > 0): ?>
                        <div class="info-item">
                            <i class="fas fa-file-alt text-info"></i>
                            <span><?php echo $currentEdition['total_pages']; ?> Pages</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($currentEdition['file_size'] > 0): ?>
                        <div class="info-item">
                            <i class="fas fa-hdd text-success"></i>
                            <span><?php echo number_format($currentEdition['file_size'] / 1024 / 1024, 1); ?> MB</span>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <i class="fas fa-eye text-warning"></i>
                            <span><?php echo number_format($currentEdition['views']); ?> Views</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="sidebar-actions mt-4">
                        <?php if ($currentEdition && $currentEdition['pdf_path']): ?>
                        <button class="btn btn-primary btn-sm w-100 mb-2" onclick="downloadCurrentEdition()">
                            <i class="fas fa-download"></i> Download PDF
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-warning btn-sm w-100 mb-2" data-bs-toggle="modal" data-bs-target="#socialShareModal">
                            <i class="fas fa-share-alt"></i> Share Edition
                        </button>
                        <button class="btn btn-info btn-sm w-100" onclick="openFullscreenPDF()">
                            <i class="fas fa-expand"></i> Fullscreen
                        </button>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="main-content">
                    <!-- Enhanced Toolbar -->
                    <div class="toolbar">
                        <!-- Edition info in the center -->
                        <div class="flex-grow-1 text-center">
                            <div class="edition-info">
                                <h5 class="edition-title mb-0"><?php echo htmlspecialchars($currentEdition['title'] ?? 'E-Paper Edition'); ?></h5>
                                <small class="edition-date text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?php echo date('F j, Y', strtotime($currentEdition['date'] ?? 'now')); ?>
                                </small>
                            </div>
                        </div>
                        
                        <!-- Action buttons on the right -->
                        <div class="toolbar-actions d-flex align-items-center">
                            <?php if ($currentEdition && $currentEdition['pdf_path']): ?>
                            <button class="btn btn-primary me-2" onclick="downloadCurrentEdition()">
                                <i class="fas fa-download"></i> Download
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#socialShareModal">
                                <i class="fas fa-share-alt"></i> Share
                            </button>
                        </div>
                    </div>

                    <!-- Enhanced PDF Display -->
                    <?php if ($currentEdition && $currentEdition['pdf_path'] && file_exists($currentEdition['pdf_path'])): ?>
                    <div class="pdf-viewer-container" id="pdfContainer">
                        <div class="pdf-embed-wrapper">
                            <embed src="<?php echo htmlspecialchars($currentEdition['pdf_path']); ?>" 
                                   type="application/pdf" 
                                   width="100%" 
                                   height="800px" 
                                   id="pdfEmbed"
                                   style="border: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                        </div>
                        <div class="pdf-controls text-center mt-3">
                            <p class="text-muted mb-2">
                                <i class="fas fa-info-circle"></i>
                                Interactive PDF viewer - Use browser's PDF controls for navigation
                            </p>
                            <div class="btn-group" role="group">
                                <a href="<?php echo htmlspecialchars($currentEdition['pdf_path']); ?>" 
                                   target="_blank" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-external-link-alt"></i> Open in New Tab
                                </a>
                                <button class="btn btn-outline-info btn-sm" onclick="refreshPDF()">
                                    <i class="fas fa-refresh"></i> Refresh
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="openFullscreenPDF()">
                                    <i class="fas fa-expand"></i> Fullscreen
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-pdf fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Edition Content Not Available</h4>
                        <p class="text-muted">The PDF file for this edition could not be found.</p>
                        <?php if ($currentEdition && $currentEdition['pdf_path']): ?>
                        <p class="small text-muted">Looking for: <?php echo htmlspecialchars($currentEdition['pdf_path']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-newspaper fa-4x text-muted mb-4"></i>
            <h2 class="text-muted">No Edition Available</h2>
            <p class="text-muted">Please check back later or contact the administrator.</p>
            <a href="admin/" class="btn btn-primary">Go to Admin Panel</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Archive Section -->
    <?php if ($homepageShowArchive): ?>
    <div class="container-fluid px-3 mt-4">
        <div class="archive-section">
            <div class="section-header text-center mb-4">
                <h2 class="section-title">
                    <i class="fas fa-archive"></i> 
                    <?php echo htmlspecialchars($homepageArchiveTitle); ?>
                </h2>
                <p class="section-subtitle text-muted"><?php echo htmlspecialchars($homepageArchiveSubtitle); ?></p>
            </div>
            
            <!-- Date Filter Section -->
            <div class="date-filter-section">
                <div class="filter-controls">
                    <div class="filter-group">
                        <label for="dateFrom">From:</label>
                        <input type="date" id="dateFrom" class="filter-input" title="Select start date">
                    </div>
                    <div class="filter-group">
                        <label for="dateTo">To:</label>
                        <input type="date" id="dateTo" class="filter-input" title="Select end date">
                    </div>
                    <div class="filter-group">
                        <button class="filter-btn" onclick="applyDateFilter()" title="Apply date filter">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <button class="filter-btn clear" onclick="clearDateFilter()" title="Clear all filters">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
                <div class="filter-stats" id="filterStats">
                    Showing all editions
                </div>
            </div>
            
            <div class="archive-grid" id="archiveGrid">
                <?php
                // Get database connection for archive
                $archiveConn = getConnection();
                
                // Enhanced query to get all published editions with more details
                $archiveResult = $archiveConn->query("
                    SELECT e.id, e.title, e.date, e.thumbnail_path, e.description, e.created_at,
                           (SELECT COUNT(*) FROM edition_pages WHERE edition_id = e.id) as page_count,
                           (SELECT image_path FROM edition_pages WHERE edition_id = e.id ORDER BY page_number LIMIT 1) as first_page_path
                    FROM editions e 
                    WHERE e.status = 'published' 
                    ORDER BY e.date DESC, e.created_at DESC
                ");
                
                if ($archiveResult && $archiveResult->rowCount() > 0):
                    $editions = $archiveResult->fetchAll();
                    foreach ($editions as $edition):
                ?>
                <div class="archive-card" data-date="<?php echo $edition['date']; ?>" data-title="<?php echo strtolower($edition['title']); ?>">
                    <div class="archive-thumbnail">
                        <?php 
                        // Try to find the best thumbnail
                        $thumbnailPath = null;
                        if ($edition['thumbnail_path'] && file_exists($edition['thumbnail_path'])) {
                            $thumbnailPath = $edition['thumbnail_path'];
                        } elseif ($edition['first_page_path'] && file_exists($edition['first_page_path'])) {
                            $thumbnailPath = $edition['first_page_path'];
                        }
                        ?>
                        
                        <?php if ($thumbnailPath): ?>
                            <img src="<?php echo htmlspecialchars($thumbnailPath); ?>" 
                                 alt="<?php echo htmlspecialchars($edition['title']); ?>"
                                 class="img-fluid"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="no-preview">
                                <i class="fas fa-newspaper fa-2x text-muted"></i>
                                <p class="text-muted mt-2 small">No Preview</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="archive-overlay">
                            <a href="?id=<?php echo $edition['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <?php if ($edition['page_count'] > 0): ?>
                                <small class="d-block mt-1 text-white">
                                    <?php echo $edition['page_count']; ?> pages
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="archive-info">
                        <h6 class="archive-title" title="<?php echo htmlspecialchars($edition['title']); ?>">
                            <?php echo htmlspecialchars(strlen($edition['title']) > 25 ? substr($edition['title'], 0, 25) . '...' : $edition['title']); ?>
                        </h6>
                        <p class="archive-date">
                            <i class="fas fa-calendar-alt"></i>
                            <small><?php echo date('M j, Y', strtotime($edition['date'])); ?></small>
                        </p>
                    </div>
                </div>
                <?php 
                    endforeach;
                else:
                ?>
                <div class="col-12 text-center py-4">
                    <i class="fas fa-archive fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Archived Editions</h4>
                    <p class="text-muted">Check back later for more editions.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Load More Section -->
            <div class="text-center mt-4" id="loadMoreSection" style="display: none;">
                <button class="btn btn-outline-primary" onclick="loadMoreEditions()">
                    <i class="fas fa-plus-circle"></i> Load More Editions
                </button>
            </div>
        </div>
    </div>
    <?php endif; // End of archive section conditional ?>

    <!-- Clip Modal -->
    <div class="modal fade" id="clipModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-cut"></i> <span id="clipModalTitle">Clip & Share</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="cancelClipping()"></button>
                </div>
                <div class="modal-body">
                    <!-- Step 1: Crop Selection -->
                    <div id="cropStep" class="clip-step">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="crop-container">
                                    <img src="" alt="Page for clipping" id="cropImage" class="crop-image">
                                </div>
                                <div class="crop-controls mt-3">
                                    <button class="btn btn-secondary me-2" onclick="resetCrop()">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                    <button class="btn btn-info me-2" onclick="rotateCrop(90)">
                                        <i class="fas fa-redo"></i> Rotate
                                    </button>
                                    <button class="btn btn-success" onclick="confirmCrop()">
                                        <i class="fas fa-check"></i> Confirm Selection
                                    </button>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="crop-info">
                                    <h6><i class="fas fa-info-circle"></i> Instructions</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-mouse-pointer text-primary"></i> Drag to select area</li>
                                        <li><i class="fas fa-expand-arrows-alt text-success"></i> Resize by dragging corners</li>
                                        <li><i class="fas fa-arrows-alt text-info"></i> Move selection by dragging center</li>
                                        <li><i class="fas fa-check-circle text-warning"></i> Click "Confirm" when ready</li>
                                    </ul>
                                    <div class="crop-dimensions mt-3">
                                        <h6>Selection Info:</h6>
                                        <div id="cropDimensions">
                                            <small class="text-muted">Select an area to see dimensions</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Preview & Actions -->
                    <div id="previewStep" class="clip-step" style="display: none;">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="preview-container">
                                    <h6><i class="fas fa-eye"></i> Preview of your clip:</h6>
                                    <div class="preview-image-container">
                                        <canvas id="previewCanvas" class="preview-canvas"></canvas>
                                    </div>
                                    <div class="preview-controls mt-3">
                                        <button class="btn btn-secondary me-2" onclick="backToCropping()">
                                            <i class="fas fa-arrow-left"></i> Back to Edit
                                        </button>
                                        <button class="btn btn-primary me-2" onclick="saveClip()">
                                            <i class="fas fa-save"></i> Save Clip
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="action-panel">
                                    <h6><i class="fas fa-share-alt"></i> Download & Share Options</h6>
                                    
                                    <!-- Download Options -->
                                    <div class="action-group mb-3">
                                        <h6 class="small text-muted mb-2">Download</h6>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-success btn-sm" onclick="downloadClipImage()" id="downloadClipBtn" disabled>
                                                <i class="fas fa-download"></i> Download Image
                                            </button>
                                            <button class="btn btn-secondary btn-sm" onclick="copyClipUrl()" id="copyClipBtn" disabled>
                                                <i class="fas fa-copy"></i> Copy Link
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Social Share Options -->
                                    <div class="action-group">
                                        <h6 class="small text-muted mb-2">Share on Social Media</h6>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <button class="btn btn-primary btn-sm w-100" onclick="shareClipToSocial('facebook')" title="Share on Facebook" disabled>
                                                    <i class="fab fa-facebook-f"></i>
                                                </button>
                                            </div>
                                            <div class="col-6">
                                                <button class="btn btn-info btn-sm w-100" onclick="shareClipToSocial('twitter')" title="Share on Twitter" disabled>
                                                    <i class="fab fa-twitter"></i>
                                                </button>
                                            </div>
                                            <div class="col-6">
                                                <button class="btn btn-success btn-sm w-100" onclick="shareClipToSocial('whatsapp')" title="Share on WhatsApp" disabled>
                                                    <i class="fab fa-whatsapp"></i>
                                                </button>
                                            </div>
                                            <div class="col-6">
                                                <button class="btn btn-primary btn-sm w-100" onclick="shareClipToSocial('linkedin')" title="Share on LinkedIn" disabled>
                                                    <i class="fab fa-linkedin-in"></i>
                                                </button>
                                            </div>
                                            <div class="col-6">
                                                <button class="btn btn-info btn-sm w-100" onclick="shareClipToSocial('telegram')" title="Share on Telegram" disabled>
                                                    <i class="fab fa-telegram-plane"></i>
                                                </button>
                                            </div>
                                            <div class="col-6">
                                                <button class="btn btn-warning btn-sm w-100" onclick="shareClipToSocial('email')" title="Share via Email" disabled>
                                                    <i class="fas fa-envelope"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Success/Confirmation -->
                    <div id="successStep" class="clip-step text-center" style="display: none;">
                        <div class="success-message">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <h4>Clip Saved Successfully!</h4>
                            <p class="text-muted">Your clip has been saved and is ready to share.</p>
                            <div class="success-actions mt-4">
                                <button class="btn btn-primary me-2" onclick="viewSavedClip()">
                                    <i class="fas fa-eye"></i> View Clip
                                </button>
                                <button class="btn btn-success me-2" onclick="downloadSavedClip()">
                                    <i class="fas fa-download"></i> Download
                                </button>
                                <button class="btn btn-secondary" onclick="createNewClip()">
                                    <i class="fas fa-plus"></i> Create Another
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Clip Preview Modal -->
    <div id="clipPreviewModal" class="clip-preview-modal">
        <div class="clip-preview-content">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-eye"></i> Preview Your Clip</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="closePreview()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="text-center mb-3">
                <canvas id="previewCanvas" class="preview-canvas"></canvas>
            </div>
            
            <div class="preview-actions">
                <button class="btn btn-secondary me-2" onclick="backToClipping()">
                    <i class="fas fa-arrow-left"></i> Back to Edit
                </button>
                <button class="btn btn-primary me-2" onclick="saveClip()">
                    <i class="fas fa-save"></i> Save Clip
                </button>
                <button class="btn btn-success me-2" onclick="downloadClipImage()">
                    <i class="fas fa-download"></i> Download
                </button>
            </div>
            
            <div class="share-options">
                <button class="btn btn-sm btn-primary" onclick="shareClipToSocial('facebook')">
                    <i class="fab fa-facebook-f"></i> Facebook
                </button>
                <button class="btn btn-sm btn-info" onclick="shareClipToSocial('twitter')">
                    <i class="fab fa-twitter"></i> Twitter
                </button>
                <button class="btn btn-sm btn-success" onclick="shareClipToSocial('whatsapp')">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </button>
                <button class="btn btn-sm btn-primary" onclick="shareClipToSocial('linkedin')">
                    <i class="fab fa-linkedin-in"></i> LinkedIn
                </button>
                <button class="btn btn-sm btn-info" onclick="shareClipToSocial('telegram')">
                    <i class="fab fa-telegram-plane"></i> Telegram
                </button>
                <button class="btn btn-sm btn-warning" onclick="shareClipToSocial('email')">
                    <i class="fas fa-envelope"></i> Email
                </button>
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

    <!-- Fullscreen Image Modal -->
    <div id="fullscreenModal" class="fullscreen-modal">
        <div class="fullscreen-content">
            <button class="fullscreen-close" onclick="closeFullscreen()">
                <i class="fas fa-times"></i>
            </button>
            <div class="fullscreen-image-container">
                <img id="fullscreenImage" class="fullscreen-image" src="" alt="Fullscreen View">
            </div>
            <div class="fullscreen-toolbar">
                <button class="btn btn-success" onclick="downloadCurrentPage()" title="Download this page">
                    <i class="fas fa-download"></i> Download
                </button>
                <button class="btn btn-primary" onclick="activateClipModeFromFullscreen()" title="Create a clip from this page">
                    <i class="fas fa-cut"></i> Create Clip
                </button>
                <button class="btn btn-warning" onclick="shareFromFullscreen()" title="Share this page">
                    <i class="fas fa-share-alt"></i> Share
                </button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        // Global variables
        window.editionData = {
            editionId: <?php echo $editionId ?: 'null'; ?>,
            editionTitle: '<?php echo addslashes($currentEdition['title'] ?? 'E-Paper'); ?>',
            totalPages: <?php echo $totalPages; ?>,
            currentPage: <?php echo $page; ?>
        };

        // Clipping Tool Global Variables
        let cropper = null;
        let currentClipData = null;
        let savedClipId = null;

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

        // ===========================================
        // FULLSCREEN FUNCTIONALITY
        // ===========================================
        
        function openFullscreen(imageSrc) {
            const modal = document.getElementById('fullscreenModal');
            const fullscreenImage = document.getElementById('fullscreenImage');
            
            fullscreenImage.src = imageSrc;
            modal.style.display = 'block';
            
            // Prevent body scrolling
            document.body.style.overflow = 'hidden';
            
            // Add escape key listener
            document.addEventListener('keydown', handleFullscreenEscape);
            
            // Close modal when clicking outside the image container
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeFullscreen();
                }
            });
        }
        
        function closeFullscreen() {
            const modal = document.getElementById('fullscreenModal');
            modal.style.display = 'none';
            
            // Restore body scrolling
            document.body.style.overflow = 'auto';
            
            // Remove escape key listener
            document.removeEventListener('keydown', handleFullscreenEscape);
        }
        
        function handleFullscreenEscape(event) {
            if (event.key === 'Escape') {
                closeFullscreen();
            }
        }
        
        function downloadCurrentPage() {
            const fullscreenImage = document.getElementById('fullscreenImage');
            const link = document.createElement('a');
            link.href = fullscreenImage.src;
            link.download = `page-<?php echo $page; ?>-<?php echo date('Y-m-d'); ?>.jpg`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        function activateClipModeFromFullscreen() {
            closeFullscreen();
            setTimeout(() => {
                activateClipMode();
            }, 300);
        }
        
        function shareFromFullscreen() {
            closeFullscreen();
            setTimeout(() => {
                const shareModal = new bootstrap.Modal(document.getElementById('socialShareModal'));
                shareModal.show();
            }, 300);
        }

        // ===========================================
        // OVERLAY-BASED CLIPPING TOOL FUNCTIONALITY
        // ===========================================

        // Additional clipping variables
        let isClippingMode = false;

        // Activate clipping mode on the current page image
        function activateClipMode() {
            console.log('activateClipMode called');
            const currentPageImage = document.getElementById('currentPageImage');
            const clipOverlay = document.getElementById('clipOverlay');
            const clipBtn = document.getElementById('clipModeBtn');
            
            console.log('Elements found:', {
                currentPageImage: currentPageImage ? 'found' : 'not found',
                clipOverlay: clipOverlay ? 'found' : 'not found', 
                clipBtn: clipBtn ? 'found' : 'not found'
            });
            
            if (currentPageImage) {
                console.log('Image details:', {
                    src: currentPageImage.src,
                    naturalWidth: currentPageImage.naturalWidth,
                    naturalHeight: currentPageImage.naturalHeight,
                    clientWidth: currentPageImage.clientWidth,
                    clientHeight: currentPageImage.clientHeight,
                    complete: currentPageImage.complete
                });
            }
            
            if (!currentPageImage || !currentPageImage.src) {
                console.log('No image available, src:', currentPageImage ? currentPageImage.src : 'no element');
                showNotification('No image available for clipping', 'error');
                return;
            }

            if (isClippingMode) {
                exitClipMode();
                return;
            }

            // Wait for image to be fully loaded before initializing cropper
            if (!currentPageImage.complete || currentPageImage.naturalWidth === 0) {
                showNotification('Waiting for image to load...', 'info');
                const loadHandler = function() {
                    console.log('Image loaded, initializing cropper...');
                    currentPageImage.removeEventListener('load', loadHandler);
                    setTimeout(() => initializeCropper(currentPageImage, clipOverlay, clipBtn), 200);
                };
                currentPageImage.addEventListener('load', loadHandler);
                
                // Also check if image is already loaded but naturalWidth not set yet
                setTimeout(() => {
                    if (currentPageImage.naturalWidth > 0) {
                        currentPageImage.removeEventListener('load', loadHandler);
                        initializeCropper(currentPageImage, clipOverlay, clipBtn);
                    }
                }, 100);
                return;
            }

            initializeCropper(currentPageImage, clipOverlay, clipBtn);
        }

        function initializeCropper(currentPageImage, clipOverlay, clipBtn) {
            console.log('initializeCropper called');
            
            // Enter clipping mode
            isClippingMode = true;
            document.body.classList.add('clipping-active');
            if (clipOverlay) clipOverlay.style.display = 'block';
            
            // Update button state
            if (clipBtn) {
                clipBtn.innerHTML = '<i class="fas fa-times"></i> Exit Clip';
                clipBtn.classList.remove('btn-info');
                clipBtn.classList.add('btn-danger');
            }
            
            // Initialize cropper on the current image
            if (cropper) {
                console.log('Destroying existing cropper');
                cropper.destroy();
            }
            
            console.log('Creating new cropper...');
            console.log('Cropper class available:', typeof Cropper !== 'undefined');
            
            try {
                cropper = new Cropper(currentPageImage, {
                    aspectRatio: NaN, // Free form cropping
                    viewMode: 1, // Restrict crop box not to exceed canvas
                    dragMode: 'crop', // Create new crop box
                    autoCropArea: 0.5, // Start with 50% area selected
                    restore: false,
                    guides: true, // Show grid lines
                    center: true, // Show center indicator
                    highlight: true, // Show crop area
                    cropBoxMovable: true, // Allow moving crop box
                    cropBoxResizable: true, // Allow resizing crop box
                    toggleDragModeOnDblclick: false,
                    movable: false, // Don't allow moving the image itself
                    rotatable: false, // Disable rotation
                    scalable: false, // Disable scaling
                    zoomable: false, // Disable zooming
                    minCropBoxWidth: 50, // Minimum crop box width
                    minCropBoxHeight: 50, // Minimum crop box height
                    responsive: true, // Auto resize
                    checkCrossOrigin: false,
                    ready: function() {
                        console.log('Cropper ready - should be able to drag/resize now');
                        console.log('Canvas data:', this.cropper.getCanvasData());
                        console.log('Crop box data:', this.cropper.getCropBoxData());
                        showNotification('Selection ready! Use Cancel or Save Clip buttons above the selection area.', 'success');
                        
                        // Attach toolbar to crop box
                        attachToolbarToCropBox();
                        updateToolbarPosition();
                    },
                    crop: function(event) {
                        console.log('Crop event:', event.detail);
                        // No need to update position as toolbar moves with crop box
                    },
                    cropstart: function(event) {
                        console.log('Crop start:', event.detail.action);
                    },
                    cropmove: function(event) {
                        console.log('Crop move:', event.detail.action);
                        // Toolbar automatically moves with crop box
                    },
                    cropend: function(event) {
                        console.log('Crop ended:', event.detail);
                        // Ensure toolbar is still properly positioned
                        attachToolbarToCropBox();
                    }
                });
                
                console.log('Cropper created successfully:', cropper);
                
                // Start watching for crop box creation
                watchForCropBox();
                
            } catch (error) {
                console.error('Error creating cropper:', error);
                showNotification('Error initializing cropper: ' + error.message, 'error');
                return;
            }
            
            // Add double-click to confirm crop (attach to cropper container, not image)
            setTimeout(() => {
                const cropperContainer = document.querySelector('.cropper-container');
                if (cropperContainer) {
                    cropperContainer.addEventListener('dblclick', function(e) {
                        console.log('Double-click detected on cropper');
                        if (isClippingMode && cropper) {
                            e.preventDefault();
                            confirmCrop();
                        }
                    });
                    console.log('Double-click handler attached to cropper container');
                }
            }, 500);
            
            showNotification('Clipping mode activated! Drag to select, use Cancel/Save Clip buttons above selection.', 'success');
        }

        // Update toolbar position to stay above the crop box
        function updateToolbarPosition() {
            if (!cropper || !isClippingMode) return;
            
            const toolbar = document.getElementById('clipToolbar');
            if (!toolbar) return;

            try {
                // Get crop box element
                const cropBox = document.querySelector('.cropper-crop-box');
                if (!cropBox) return;

                // If toolbar is not already a child of crop box, move it there
                if (toolbar.parentElement !== cropBox) {
                    cropBox.appendChild(toolbar);
                }

                // Apply direct positioning to the toolbar
                toolbar.style.position = 'absolute';
                toolbar.style.top = '-50px';
                toolbar.style.left = '50%';
                toolbar.style.transform = 'translateX(-50%)';
                toolbar.style.zIndex = '1002';
                toolbar.style.opacity = '1';
                toolbar.style.visibility = 'visible';

                console.log('Toolbar positioned above crop box');
            } catch (error) {
                console.log('Error updating toolbar position:', error);
                // Fallback positioning
                toolbar.style.position = 'fixed';
                toolbar.style.top = '15px';
                toolbar.style.left = '50%';
                toolbar.style.transform = 'translateX(-50%)';
                toolbar.style.zIndex = '1002';
            }
        }

        // Move toolbar to crop box after cropper is ready
        function attachToolbarToCropBox() {
            setTimeout(() => {
                const toolbar = document.getElementById('clipToolbar');
                const cropBox = document.querySelector('.cropper-crop-box');
                
                if (toolbar && cropBox && toolbar.parentElement !== cropBox) {
                    console.log('Attaching toolbar to crop box');
                    cropBox.appendChild(toolbar);
                    toolbar.style.position = 'absolute';
                    toolbar.style.top = '-50px';
                    toolbar.style.left = '50%';
                    toolbar.style.transform = 'translateX(-50%)';
                    toolbar.style.opacity = '1';
                    toolbar.style.visibility = 'visible';
                }
            }, 100);
        }

        // Watch for crop box creation and attach toolbar
        function watchForCropBox() {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1 && node.classList && node.classList.contains('cropper-crop-box')) {
                            console.log('Crop box detected, attaching toolbar');
                            attachToolbarToCropBox();
                        }
                    });
                });
            });

            const cropperContainer = document.querySelector('.cropper-container');
            if (cropperContainer) {
                observer.observe(cropperContainer, { childList: true, subtree: true });
            }

            // Cleanup observer after 5 seconds
            setTimeout(() => observer.disconnect(), 5000);
        }

        // Exit clipping mode
        function exitClipMode() {
            const clipOverlay = document.getElementById('clipOverlay');
            const clipBtn = document.getElementById('clipModeBtn');
            
            isClippingMode = false;
            document.body.classList.remove('clipping-active');
            clipOverlay.style.display = 'none';
            
            // Reset button state
            clipBtn.innerHTML = '<i class="fas fa-cut"></i> Clip';
            clipBtn.classList.remove('btn-danger');
            clipBtn.classList.add('btn-info');
            
            // Destroy cropper
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            
            // Reset clip data
            currentClipData = null;
            savedClipId = null;
            
            showNotification('Clipping mode deactivated. Click Clip button to start again.', 'info');
        }

        // Crop control functions (simplified for streamlined UI)
        function resetCrop() {
            if (cropper) {
                cropper.reset();
                showNotification('Selection reset', 'info');
            }
        }

        // Remove rotate function - not needed for simple clipping
        // function rotateCrop() - removed for cleaner UX

        function confirmCrop() {
            if (!cropper) {
                showNotification('Please select an area first', 'warning');
                return;
            }

            const cropData = cropper.getData();
            if (cropData.width < 10 || cropData.height < 10) {
                showNotification('Selected area is too small', 'warning');
                return;
            }

            // Generate preview and show modal
            generateClipPreview(cropData);
            showPreviewModal();
        }

        function generateClipPreview(cropData) {
            const canvas = document.getElementById('previewCanvas');
            const ctx = canvas.getContext('2d');
            const currentPageImage = document.getElementById('currentPageImage');

            // Set canvas dimensions to crop size
            canvas.width = cropData.width;
            canvas.height = cropData.height;

            // Create a temporary image to get the original size
            const tempImg = new Image();
            tempImg.onload = function() {
                // Calculate the scale ratio between displayed image and original image
                const displayedWidth = currentPageImage.clientWidth;
                const displayedHeight = currentPageImage.clientHeight;
                const originalWidth = tempImg.width;
                const originalHeight = tempImg.height;
                
                const scaleX = originalWidth / displayedWidth;
                const scaleY = originalHeight / displayedHeight;

                // Adjust crop coordinates for original image scale
                const adjustedX = cropData.x * scaleX;
                const adjustedY = cropData.y * scaleY;
                const adjustedWidth = cropData.width * scaleX;
                const adjustedHeight = cropData.height * scaleY;

                // Draw the cropped area
                ctx.drawImage(
                    tempImg,
                    adjustedX, adjustedY, adjustedWidth, adjustedHeight,
                    0, 0, cropData.width, cropData.height
                );

                // Store current clip data
                currentClipData = {
                    cropData: cropData,
                    editionId: window.editionData.editionId,
                    pageNumber: window.editionData.currentPage,
                    width: Math.round(cropData.width),
                    height: Math.round(cropData.height),
                    x: Math.round(cropData.x),
                    y: Math.round(cropData.y)
                };

                showNotification('Preview generated successfully!', 'success');
            };
            
            tempImg.src = currentPageImage.src;
        }

        function showPreviewModal() {
            const modal = document.getElementById('clipPreviewModal');
            modal.style.display = 'flex';
        }

        function closePreview() {
            const modal = document.getElementById('clipPreviewModal');
            modal.style.display = 'none';
        }

        function backToClipping() {
            closePreview();
            // Keep clipping mode active for further adjustments
        }

        // Save clip to server
        function saveClip() {
            if (!currentClipData) {
                showNotification('No clip data available', 'error');
                return;
            }

            const canvas = document.getElementById('previewCanvas');
            const imageData = canvas.toDataURL('image/jpeg', 0.9);

            // Prepare data for server
            const clipInfo = {
                edition_id: currentClipData.editionId,
                page_number: currentClipData.pageNumber,
                x: currentClipData.x,
                y: currentClipData.y,
                width: currentClipData.width,
                height: currentClipData.height,
                image_data: imageData
            };

            // Show loading notification
            showNotification('Saving clip...', 'info');

            // Send to server
            fetch('api/save-clip.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(clipInfo)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    savedClipId = data.clip_id;
                    showNotification('Clip saved successfully!', 'success');
                    
                    // Exit clipping mode and close preview
                    exitClipMode();
                    closePreview();
                    
                    // Show success options
                    setTimeout(() => {
                        if (confirm('Clip saved! Would you like to view it now?')) {
                            window.open(`view-clip.php?id=${savedClipId}`, '_blank');
                        }
                    }, 1000);
                } else {
                    throw new Error(data.error || 'Failed to save clip');
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                showNotification('Failed to save clip: ' + error.message, 'error');
            });
        }

        // Download functions
        function downloadClipImage() {
            if (!savedClipId && !currentClipData) {
                // Fallback to original functionality
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
                return;
            }

            // Download the clipped image
            const canvas = document.getElementById('previewCanvas');
            if (!canvas) {
                showNotification('No clip available for download', 'error');
                return;
            }

            canvas.toBlob(function(blob) {
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `${window.editionData.editionTitle}_Page_${window.editionData.currentPage}_Clip.jpg`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(link.href);
                showNotification('Clip download started!', 'success');
            }, 'image/jpeg', 0.9);
        }

        function downloadCurrentEdition() {
            <?php if ($currentEdition && $currentEdition['pdf_path']): ?>
            showNotification('Starting download...', 'info');
            
            const downloadUrl = 'api/download-edition.php?id=<?php echo $editionId; ?>';
            
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showNotification('Download started!', 'success');
            <?php else: ?>
            showNotification('PDF file not available', 'warning');
            <?php endif; ?>
        }

        function downloadAsPDF() {
            downloadCurrentEdition();
        }

        // Copy functions
        function copyClipUrl() {
            let urlToCopy;
            
            if (savedClipId) {
                urlToCopy = `${window.location.origin}${window.location.pathname}view-clip.php?id=${savedClipId}`;
            } else {
                urlToCopy = window.location.href;
            }

            if (navigator.clipboard) {
                navigator.clipboard.writeText(urlToCopy).then(() => {
                    showNotification('Clip URL copied to clipboard!', 'success');
                }).catch(() => {
                    fallbackCopy(urlToCopy);
                });
            } else {
                fallbackCopy(urlToCopy);
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

        // Social sharing functions
        function shareClipToSocial(platform) {
            let shareUrl;
            let shareText = `Check out this clip from ${window.editionData.editionTitle}`;
            
            if (savedClipId) {
                shareUrl = `${window.location.origin}${window.location.pathname}view-clip.php?id=${savedClipId}`;
            } else {
                shareUrl = window.location.href;
            }

            const encodedUrl = encodeURIComponent(shareUrl);
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

        // Success step functions
        function viewSavedClip() {
            if (savedClipId) {
                window.open(`view-clip.php?id=${savedClipId}`, '_blank');
            }
        }

        function downloadSavedClip() {
            downloadClipImage();
        }

        function createNewClip() {
            cancelClipping();
            // Reopen the modal
            setTimeout(() => {
                const clipModal = new bootstrap.Modal(document.getElementById('clipModal'));
                clipModal.show();
            }, 100);
        }

        // Cancel clipping
        function cancelClipping() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            currentClipData = null;
            savedClipId = null;
            
            // Reset modal state
            showCropStep();
            updateClipModalTitle('Clip & Share');
            
            // Disable sharing buttons
            const buttons = document.querySelectorAll('#previewStep button');
            buttons.forEach(btn => {
                if (btn.onclick && btn.onclick.toString().includes('share') || btn.onclick.toString().includes('copy') || btn.onclick.toString().includes('download')) {
                    btn.disabled = true;
                }
            });
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
            let qrUrl;
            
            // Use clip URL if available, otherwise current page URL
            if (savedClipId) {
                qrUrl = `${window.location.origin}${window.location.pathname}view-clip.php?id=${savedClipId}`;
            } else {
                qrUrl = window.location.href;
            }
            
            const qrSize = 150;
            const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${qrSize}x${qrSize}&data=${encodeURIComponent(qrUrl)}`;
            
            const qrImg = document.createElement('img');
            qrImg.src = qrApiUrl;
            qrImg.alt = 'QR Code';
            qrImg.className = 'img-fluid border rounded';
            qrImg.style.maxWidth = qrSize + 'px';
            qrDiv.appendChild(qrImg);
            
            showNotification('QR code generated!', 'success');
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

            // Keyboard shortcuts for clipping
            document.addEventListener('keydown', function(e) {
                if (isClippingMode) {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        exitClipMode();
                    } else if (e.key === 'Enter' || e.key === 's' || e.key === 'S') {
                        e.preventDefault();
                        if (cropper) {
                            confirmCrop();
                        }
                    } else if (e.key === 'r' || e.key === 'R') {
                        e.preventDefault();
                        resetCrop();
                    }
                }
            });

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

            console.log('E-Paper CMS v2.0 - Comprehensive Clipping Tool loaded successfully');
        });

        // Enhanced PDF Viewer Functions
        function refreshPDF() {
            const pdfEmbed = document.getElementById('pdfEmbed');
            if (pdfEmbed) {
                const src = pdfEmbed.src;
                pdfEmbed.src = '';
                setTimeout(() => {
                    pdfEmbed.src = src + '?refresh=' + Date.now();
                    showNotification('PDF refreshed', 'success');
                }, 100);
            }
        }

        function openFullscreenPDF() {
            const pdfContainer = document.getElementById('pdfContainer');
            if (pdfContainer) {
                if (pdfContainer.requestFullscreen) {
                    pdfContainer.requestFullscreen();
                } else if (pdfContainer.webkitRequestFullscreen) {
                    pdfContainer.webkitRequestFullscreen();
                } else if (pdfContainer.msRequestFullscreen) {
                    pdfContainer.msRequestFullscreen();
                }
                showNotification('PDF opened in fullscreen', 'success');
            }
        }

        // Handle fullscreen change events
        document.addEventListener('fullscreenchange', function() {
            if (document.fullscreenElement) {
                // Entered fullscreen
                const pdfEmbed = document.querySelector('#pdfContainer embed');
                if (pdfEmbed) {
                    pdfEmbed.style.height = '100vh';
                }
            } else {
                // Exited fullscreen
                const pdfEmbed = document.querySelector('#pdfContainer embed');
                if (pdfEmbed) {
                    pdfEmbed.style.height = '800px';
                }
            }
        });

        // ===========================================
        // ARCHIVE DATE FILTER FUNCTIONALITY
        // ===========================================
        
        let allEditions = [];
        let filteredEditions = [];
        
        // Initialize archive filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Store all editions for filtering
            const archiveCards = document.querySelectorAll('.archive-card');
            allEditions = Array.from(archiveCards).map(card => ({
                element: card,
                date: card.getAttribute('data-date'),
                title: card.getAttribute('data-title')
            }));
            
            filteredEditions = [...allEditions];
            updateFilterStats();
            
            // Set up event listeners for quick filter
            setupQuickFilters();
        });
        
        function applyDateFilter() {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            filteredEditions = allEditions.filter(edition => {
                const editionDate = new Date(edition.date);
                let showEdition = true;
                
                // Date range filter
                if (dateFrom) {
                    const fromDate = new Date(dateFrom);
                    if (editionDate < fromDate) showEdition = false;
                }
                
                if (dateTo) {
                    const toDate = new Date(dateTo);
                    if (editionDate > toDate) showEdition = false;
                }
                
                return showEdition;
            });
            
            // Show/hide editions based on filter
            allEditions.forEach(edition => {
                const shouldShow = filteredEditions.includes(edition);
                edition.element.style.display = shouldShow ? 'block' : 'none';
            });
            
            updateFilterStats();
            
            // Show notification
            showNotification(`Filtered to ${filteredEditions.length} editions`, 'success');
        }
        
        function clearDateFilter() {
            // Clear all filter inputs
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            
            // Show all editions
            allEditions.forEach(edition => {
                edition.element.style.display = 'block';
            });
            
            filteredEditions = [...allEditions];
            updateFilterStats();
            
            showNotification('Filter cleared - showing all editions', 'info');
        }
        
        function updateFilterStats() {
            const statsElement = document.getElementById('filterStats');
            const totalEditions = allEditions.length;
            const visibleEditions = filteredEditions.length;
            
            if (visibleEditions === totalEditions) {
                statsElement.textContent = `Showing all ${totalEditions} editions`;
            } else {
                statsElement.textContent = `Showing ${visibleEditions} of ${totalEditions} editions`;
            }
        }
        
        function setupQuickFilters() {
            // Add quick filter buttons for common date ranges
            const filterSection = document.querySelector('.date-filter-section');
            if (filterSection) {
                const quickFiltersHtml = `
                    <div class="quick-filters mt-3">
                        <small class="text-muted d-block mb-2">Quick Filters:</small>
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <button class="btn btn-sm btn-outline-secondary" onclick="applyQuickFilter('thisMonth')">This Month</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="applyQuickFilter('lastMonth')">Last Month</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="applyQuickFilter('last3Months')">Last 3 Months</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="applyQuickFilter('thisYear')">This Year</button>
                        </div>
                    </div>
                `;
                filterSection.insertAdjacentHTML('beforeend', quickFiltersHtml);
            }
        }
        
        function applyQuickFilter(filterType) {
            const now = new Date();
            const currentYear = now.getFullYear();
            const currentMonth = now.getMonth();
            
            // Clear existing filters
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            
            switch (filterType) {
                case 'thisMonth':
                    const thisMonthStart = new Date(currentYear, currentMonth, 1);
                    const thisMonthEnd = new Date(currentYear, currentMonth + 1, 0);
                    document.getElementById('dateFrom').value = thisMonthStart.toISOString().split('T')[0];
                    document.getElementById('dateTo').value = thisMonthEnd.toISOString().split('T')[0];
                    break;
                    
                case 'lastMonth':
                    const lastMonthStart = new Date(currentYear, currentMonth - 1, 1);
                    const lastMonthEnd = new Date(currentYear, currentMonth, 0);
                    document.getElementById('dateFrom').value = lastMonthStart.toISOString().split('T')[0];
                    document.getElementById('dateTo').value = lastMonthEnd.toISOString().split('T')[0];
                    break;
                    
                case 'last3Months':
                    const threeMonthsAgo = new Date(currentYear, currentMonth - 3, 1);
                    document.getElementById('dateFrom').value = threeMonthsAgo.toISOString().split('T')[0];
                    break;
                    
                case 'thisYear':
                    document.getElementById('dateFrom').value = `${currentYear}-01-01`;
                    document.getElementById('dateTo').value = `${currentYear}-12-31`;
                    break;
            }
            
            // Apply the filter
            applyDateFilter();
        }
        
        function loadMoreEditions() {
            // This would typically load more editions via AJAX
            // For now, just show a message
            showNotification('Load more functionality would be implemented here', 'info');
        }
    </script>
</body>
</html>
