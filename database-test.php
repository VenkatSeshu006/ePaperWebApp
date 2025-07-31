<?php
/**
 * Database Connection Test
 * Quick test to verify database connectivity
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Test - E-Paper CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0"><i class="fas fa-database me-2"></i>Database Connection Test</h2>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            // Test basic MySQL connection
                            echo '<h4><i class="fas fa-server me-2"></i>MySQL Server Connection</h4>';
                            $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', '');
                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>MySQL server connection: <strong>SUCCESS</strong></div>';
                            
                            // Test database existence
                            echo '<h4><i class="fas fa-database me-2"></i>Database Test</h4>';
                            $result = $pdo->query("SHOW DATABASES LIKE 'epaper_cms'")->fetch();
                            if ($result) {
                                echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Database "epaper_cms": <strong>EXISTS</strong></div>';
                                
                                // Select database and test tables
                                $pdo->exec("USE epaper_cms");
                                echo '<h4><i class="fas fa-table me-2"></i>Tables Test</h4>';
                                
                                $tables = ['categories', 'editions', 'edition_categories', 'users', 'analytics', 'settings'];
                                foreach ($tables as $table) {
                                    try {
                                        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
                                        if ($result) {
                                            $count = $pdo->query("SELECT COUNT(*) as count FROM $table")->fetch();
                                            echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Table "' . $table . '": <strong>EXISTS</strong> (' . $count['count'] . ' records)</div>';
                                        } else {
                                            echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Table "' . $table . '": <strong>MISSING</strong></div>';
                                        }
                                    } catch (Exception $e) {
                                        echo '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Table "' . $table . '": <strong>ERROR</strong> - ' . $e->getMessage() . '</div>';
                                    }
                                }
                                
                                // Test E-Paper CMS classes
                                echo '<h4><i class="fas fa-code me-2"></i>Application Classes Test</h4>';
                                try {
                                    require_once 'includes/database.php';
                                    $db = Database::getInstance();
                                    echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Database class: <strong>SUCCESS</strong></div>';
                                    
                                    if (file_exists('classes/Category.php')) {
                                        require_once 'classes/Category.php';
                                        $categoryModel = new Category();
                                        $categories = $categoryModel->getAll();
                                        echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Category class: <strong>SUCCESS</strong> (' . count($categories) . ' categories)</div>';
                                    }
                                    
                                    if (file_exists('classes/Edition.php')) {
                                        require_once 'classes/Edition.php';
                                        $editionModel = new Edition();
                                        echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Edition class: <strong>SUCCESS</strong></div>';
                                    }
                                    
                                } catch (Exception $e) {
                                    echo '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Application test: <strong>ERROR</strong> - ' . $e->getMessage() . '</div>';
                                }
                                
                            } else {
                                echo '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Database "epaper_cms": <strong>NOT FOUND</strong></div>';
                                echo '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Please run the database setup first.</div>';
                            }
                            
                        } catch (PDOException $e) {
                            echo '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Database connection failed: <strong>' . $e->getMessage() . '</strong></div>';
                            echo '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Make sure XAMPP MySQL service is running.</div>';
                        }
                        ?>
                        
                        <div class="mt-4">
                            <h4><i class="fas fa-tools me-2"></i>Quick Actions</h4>
                            <div class="d-grid gap-2 d-md-block">
                                <a href="database-setup.html" class="btn btn-primary">
                                    <i class="fas fa-database me-1"></i>Setup Database
                                </a>
                                <a href="system-test.php" class="btn btn-outline-primary">
                                    <i class="fas fa-cogs me-1"></i>System Test
                                </a>
                                <a href="admin/dashboard.php" class="btn btn-success">
                                    <i class="fas fa-tachometer-alt me-1"></i>Admin Dashboard
                                </a>
                                <a href="index.php" class="btn btn-outline-success">
                                    <i class="fas fa-home me-1"></i>Homepage
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
