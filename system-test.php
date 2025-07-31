<?php
/**
 * E-Paper CMS System Test
 * Comprehensive functionality verification
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Test - E-Paper CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-result {
            transition: all 0.3s ease;
        }
        .test-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .test-error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .test-warning {
            background: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 1rem;
            font-family: monospace;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h1 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>E-Paper CMS System Test
                        </h1>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            This test verifies all core functionalities of the E-Paper CMS v2.0
                        </div>

                        <?php
                        $tests = [];
                        $overallStatus = true;

                        // Test 1: Database Connection
                        echo '<h3><i class="fas fa-database me-2"></i>Database Tests</h3>';
                        try {
                            require_once 'includes/database.php';
                            $db = Database::getInstance();
                            $tests['database'] = ['status' => 'success', 'message' => 'Database connection successful'];
                            echo '<div class="alert test-success"><i class="fas fa-check-circle me-2"></i>Database connection: <strong>SUCCESS</strong></div>';
                        } catch (Exception $e) {
                            $tests['database'] = ['status' => 'error', 'message' => $e->getMessage()];
                            echo '<div class="alert test-error"><i class="fas fa-times-circle me-2"></i>Database connection: <strong>FAILED</strong> - ' . $e->getMessage() . '</div>';
                            $overallStatus = false;
                        }

                        // Test 2: Classes Loading
                        echo '<h3 class="mt-4"><i class="fas fa-cube me-2"></i>Class Loading Tests</h3>';
                        $classes = ['Category', 'Edition', 'Analytics', 'User'];
                        foreach ($classes as $className) {
                            try {
                                $classFile = "classes/{$className}.php";
                                if (file_exists($classFile)) {
                                    require_once $classFile;
                                    if (class_exists($className)) {
                                        $obj = new $className();
                                        echo '<div class="alert test-success"><i class="fas fa-check-circle me-2"></i>' . $className . ' class: <strong>SUCCESS</strong></div>';
                                        $tests["class_{$className}"] = ['status' => 'success', 'message' => 'Class loaded successfully'];
                                    } else {
                                        throw new Exception("Class {$className} not found after include");
                                    }
                                } else {
                                    throw new Exception("File {$classFile} not found");
                                }
                            } catch (Exception $e) {
                                echo '<div class="alert test-error"><i class="fas fa-times-circle me-2"></i>' . $className . ' class: <strong>FAILED</strong> - ' . $e->getMessage() . '</div>';
                                $tests["class_{$className}"] = ['status' => 'error', 'message' => $e->getMessage()];
                                $overallStatus = false;
                            }
                        }

                        // Test 3: Category Model Functions
                        if (class_exists('Category')) {
                            echo '<h3 class="mt-4"><i class="fas fa-tags me-2"></i>Category Model Tests</h3>';
                            try {
                                $categoryModel = new Category();
                                $methods = ['getAll', 'getActive', 'getWithCounts', 'getById', 'getBySlug'];
                                foreach ($methods as $method) {
                                    if (method_exists($categoryModel, $method)) {
                                        echo '<div class="alert test-success"><i class="fas fa-check-circle me-2"></i>Category::' . $method . '(): <strong>EXISTS</strong></div>';
                                    } else {
                                        echo '<div class="alert test-error"><i class="fas fa-times-circle me-2"></i>Category::' . $method . '(): <strong>MISSING</strong></div>';
                                        $overallStatus = false;
                                    }
                                }
                            } catch (Exception $e) {
                                echo '<div class="alert test-error"><i class="fas fa-times-circle me-2"></i>Category model test: <strong>FAILED</strong> - ' . $e->getMessage() . '</div>';
                                $overallStatus = false;
                            }
                        }

                        // Test 4: File Structure
                        echo '<h3 class="mt-4"><i class="fas fa-folder me-2"></i>File Structure Tests</h3>';
                        $requiredFiles = [
                            'index.php' => 'Homepage',
                            'archive.php' => 'Archive page',
                            'categories.php' => 'Categories page',
                            'view.php' => 'Edition viewer',
                            'admin/dashboard.php' => 'Admin dashboard',
                            'admin/categories.php' => 'Category management',
                            'admin/upload.php' => 'Upload system',
                            'includes/navigation.php' => 'Main navigation',
                            'admin/includes/admin_nav.php' => 'Admin navigation',
                            'manifest.json' => 'PWA manifest',
                            'sw.js' => 'Service worker'
                        ];

                        foreach ($requiredFiles as $file => $description) {
                            if (file_exists($file)) {
                                echo '<div class="alert test-success"><i class="fas fa-check-circle me-2"></i>' . $description . ': <strong>EXISTS</strong></div>';
                            } else {
                                echo '<div class="alert test-warning"><i class="fas fa-exclamation-triangle me-2"></i>' . $description . ': <strong>MISSING</strong> (' . $file . ')</div>';
                            }
                        }

                        // Test 5: Directory Structure
                        echo '<h3 class="mt-4"><i class="fas fa-folder-tree me-2"></i>Directory Structure Tests</h3>';
                        $requiredDirs = [
                            'admin' => 'Admin interface',
                            'api' => 'API endpoints',
                            'assets' => 'Static assets',
                            'classes' => 'PHP classes',
                            'includes' => 'Shared includes',
                            'uploads' => 'Upload directory'
                        ];

                        foreach ($requiredDirs as $dir => $description) {
                            if (is_dir($dir)) {
                                $writable = is_writable($dir);
                                $status = $writable ? 'SUCCESS' : 'READ-ONLY';
                                $alertClass = $writable ? 'test-success' : 'test-warning';
                                $icon = $writable ? 'check-circle' : 'exclamation-triangle';
                                echo '<div class="alert ' . $alertClass . '"><i class="fas fa-' . $icon . ' me-2"></i>' . $description . ': <strong>' . $status . '</strong></div>';
                            } else {
                                echo '<div class="alert test-error"><i class="fas fa-times-circle me-2"></i>' . $description . ': <strong>MISSING</strong> (' . $dir . ')</div>';
                            }
                        }

                        // Test Summary
                        echo '<h3 class="mt-4"><i class="fas fa-clipboard-check me-2"></i>Test Summary</h3>';
                        if ($overallStatus) {
                            echo '<div class="alert test-success">';
                            echo '<h4><i class="fas fa-check-circle me-2"></i>All Core Tests Passed!</h4>';
                            echo '<p class="mb-0">Your E-Paper CMS v2.0 system is ready for use.</p>';
                            echo '</div>';
                        } else {
                            echo '<div class="alert test-error">';
                            echo '<h4><i class="fas fa-exclamation-triangle me-2"></i>Some Tests Failed</h4>';
                            echo '<p class="mb-0">Please review the failed tests above and resolve any issues.</p>';
                            echo '</div>';
                        }
                        ?>

                        <div class="mt-4">
                            <h4><i class="fas fa-rocket me-2"></i>Quick Access Links</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0"><i class="fas fa-user-shield me-2"></i>Admin Access</h6>
                                        </div>
                                        <div class="card-body">
                                            <a href="admin/dashboard.php" class="btn btn-primary me-2">
                                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                            </a>
                                            <a href="admin/categories.php" class="btn btn-outline-primary">
                                                <i class="fas fa-tags me-1"></i>Categories
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0"><i class="fas fa-globe me-2"></i>Public Access</h6>
                                        </div>
                                        <div class="card-body">
                                            <a href="index.php" class="btn btn-success me-2">
                                                <i class="fas fa-home me-1"></i>Homepage
                                            </a>
                                            <a href="archive.php" class="btn btn-outline-success">
                                                <i class="fas fa-archive me-1"></i>Archive
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h4><i class="fas fa-info-circle me-2"></i>System Information</h4>
                            <div class="code-block">
                                <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
                                <strong>System:</strong> <?php echo php_uname(); ?><br>
                                <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?><br>
                                <strong>Current Path:</strong> <?php echo __DIR__; ?><br>
                                <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
