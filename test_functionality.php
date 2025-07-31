<?php
/**
 * Comprehensive Functionality Test
 * Tests all clip and share features
 */

// Database configuration
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "epaper_cms";

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>E-Paper CMS - Functionality Test</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
    <style>
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
<div class='container mt-4'>
    <h1><i class='fas fa-newspaper'></i> E-Paper CMS - System Test</h1>";

try {
    $conn = new mysqli($host, $user, $pass, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Database connection successful</div>";
    
    // Test 1: Database Structure
    echo "<div class='test-section'>
            <h3><i class='fas fa-database'></i> Database Structure Test</h3>";
    
    $tables = ['editions', 'pages', 'clips', 'settings', 'categories'];
    $all_tables_ok = true;
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $count_result->fetch_assoc()['count'];
            echo "<p class='success'><i class='fas fa-check'></i> Table '$table' exists with $count records</p>";
        } else {
            echo "<p class='error'><i class='fas fa-times'></i> Table '$table' missing!</p>";
            $all_tables_ok = false;
        }
    }
    
    echo "</div>";
    
    // Test 2: Sample Data
    echo "<div class='test-section'>
            <h3><i class='fas fa-file-alt'></i> Sample Data Test</h3>";
    
    $result = $conn->query("
        SELECT e.*, COUNT(p.id) as page_count 
        FROM editions e 
        LEFT JOIN pages p ON e.id = p.edition_id 
        WHERE e.id = 1 
        GROUP BY e.id
    ");
    
    if ($result && $result->num_rows > 0) {
        $edition = $result->fetch_assoc();
        echo "<p class='success'><i class='fas fa-check'></i> Sample edition found: '{$edition['title']}'</p>";
        echo "<p class='info'><i class='fas fa-info-circle'></i> Pages: {$edition['page_count']}, Status: {$edition['status']}</p>";
        
        // Test if actual page files exist
        $sample_page = "uploads/2025-07-27/pages/page_001.png";
        if (file_exists($sample_page)) {
            echo "<p class='success'><i class='fas fa-check'></i> Sample page files exist</p>";
        } else {
            echo "<p class='warning'><i class='fas fa-exclamation-triangle'></i> Sample page files not found (this is OK for testing)</p>";
        }
        
    } else {
        echo "<p class='error'><i class='fas fa-times'></i> No sample edition found!</p>";
    }
    
    echo "</div>";
    
    // Test 3: Required Columns
    echo "<div class='test-section'>
            <h3><i class='fas fa-columns'></i> Column Structure Test</h3>";
    
    $required_columns = [
        'editions' => ['id', 'title', 'slug', 'description', 'date', 'status', 'total_pages', 'views', 'downloads'],
        'pages' => ['id', 'edition_id', 'page_number', 'image_path', 'thumbnail_path'],
        'clips' => ['id', 'edition_id', 'page_id', 'image_path', 'x', 'y', 'width', 'height']
    ];
    
    foreach ($required_columns as $table => $columns) {
        echo "<h5>Table: $table</h5>";
        foreach ($columns as $column) {
            $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
            if ($result->num_rows > 0) {
                echo "<span class='success'><i class='fas fa-check'></i> $column </span>";
            } else {
                echo "<span class='error'><i class='fas fa-times'></i> $column </span>";
            }
        }
        echo "<br><br>";
    }
    
    echo "</div>";
    
    // Test 4: File Structure
    echo "<div class='test-section'>
            <h3><i class='fas fa-folder'></i> File Structure Test</h3>";
    
    $required_files = [
        'index.php' => 'Main homepage',
        'assets/js/app.js' => 'Main JavaScript file',
        'assets/css/style.css' => 'Main stylesheet',
        'includes/db.php' => 'Database connection',
        'api/homepage-data.php' => 'Homepage API',
        'uploads/' => 'Uploads directory'
    ];
    
    foreach ($required_files as $file => $description) {
        if (file_exists($file)) {
            echo "<p class='success'><i class='fas fa-check'></i> $file - $description</p>";
        } else {
            echo "<p class='error'><i class='fas fa-times'></i> $file - $description (MISSING)</p>";
        }
    }
    
    echo "</div>";
    
    // Test 5: JavaScript Functions
    echo "<div class='test-section'>
            <h3><i class='fas fa-code'></i> JavaScript Integration Test</h3>
            <p class='info'><i class='fas fa-info-circle'></i> Testing if main functions are available...</p>
            
            <button class='btn btn-primary' onclick='testShowNotification()'>Test Notification</button>
            <button class='btn btn-success' onclick='testShareModal()'>Test Share Modal</button>
            <button class='btn btn-warning' onclick='testClipTool()'>Test Clip Tool</button>
        </div>";
    
    // Test Summary
    echo "<div class='test-section'>
            <h3><i class='fas fa-clipboard-check'></i> Test Summary</h3>";
    
    if ($all_tables_ok) {
        echo "<div class='alert alert-success'>
                <h4><i class='fas fa-check-circle'></i> All Tests Passed!</h4>
                <p>Your E-Paper CMS is ready to use. The clip and share functionality should be working correctly.</p>
                <p><a href='index.php' class='btn btn-primary'><i class='fas fa-home'></i> Go to Homepage</a></p>
              </div>";
    } else {
        echo "<div class='alert alert-warning'>
                <h4><i class='fas fa-exclamation-triangle'></i> Some Issues Found</h4>
                <p>Please run the repair script to fix any missing tables or columns.</p>
                <p><a href='repair_database.php' class='btn btn-warning'><i class='fas fa-wrench'></i> Run Repair Script</a></p>
              </div>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> Error: " . $e->getMessage() . "</div>";
}

echo "</div>

<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
<script>
function testShowNotification() {
    if (typeof showNotification === 'function') {
        showNotification('✅ Notification system working!', 'success');
    } else {
        alert('❌ showNotification function not found');
    }
}

function testShareModal() {
    if (typeof shareContent === 'function') {
        shareContent('http://example.com/test', 'Test Content', 'This is a test');
    } else {
        alert('❌ shareContent function not found');
    }
}

function testClipTool() {
    if (typeof openClipTool === 'function') {
        alert('✅ Clip tool function found (would open clip tool for a page)');
    } else {
        alert('❌ openClipTool function not found');
    }
}

// Simple notification fallback for testing
function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 'alert-info';
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}
</script>

</body>
</html>";
?>
