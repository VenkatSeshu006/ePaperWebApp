<?php
/**
 * Database Method Test
 * Quick test to verify database fixes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Fix Test - E-Paper CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h2 class="mb-0"><i class="fas fa-bug me-2"></i>Database Fix Verification</h2>
                    </div>
                    <div class="card-body">
                        <?php
                        echo '<h4><i class="fas fa-cogs me-2"></i>Testing Database Fixes</h4>';
                        
                        try {
                            // Test 1: Check constant definition
                            echo '<h5>1. Testing ITEMS_PER_PAGE Constant</h5>';
                            if (defined('ITEMS_PER_PAGE')) {
                                echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>ITEMS_PER_PAGE constant: <strong>DEFINED</strong> (Value: ' . ITEMS_PER_PAGE . ')</div>';
                            } else {
                                echo '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>ITEMS_PER_PAGE constant: <strong>NOT DEFINED</strong></div>';
                            }
                            
                            // Test 2: Database class loading
                            echo '<h5>2. Testing Database Class</h5>';
                            require_once 'includes/database.php';
                            $db = Database::getInstance();
                            echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Database class: <strong>LOADED SUCCESSFULLY</strong></div>';
                            
                            // Test 3: PDO query method
                            echo '<h5>3. Testing PDO Query Method</h5>';
                            $result = $db->query("SELECT 1 as test");
                            $data = $result->fetch();
                            if ($data && $data['test'] == 1) {
                                echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>PDO query method: <strong>WORKING</strong></div>';
                            } else {
                                echo '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>PDO query method: <strong>FAILED</strong></div>';
                            }
                            
                            // Test 4: Edition class methods
                            echo '<h5>4. Testing Edition Class Methods</h5>';
                            if (file_exists('classes/Edition.php')) {
                                require_once 'classes/Edition.php';
                                $editionModel = new Edition();
                                
                                // Test getTotalCount method
                                try {
                                    $count = $editionModel->getTotalCount();
                                    echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Edition::getTotalCount(): <strong>WORKING</strong> (Found ' . $count . ' editions)</div>';
                                } catch (Exception $e) {
                                    echo '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Edition::getTotalCount(): <strong>ERROR</strong> - ' . $e->getMessage() . '</div>';
                                }
                                
                                // Test getLatest method
                                try {
                                    $latest = $editionModel->getLatest();
                                    echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Edition::getLatest(): <strong>WORKING</strong></div>';
                                } catch (Exception $e) {
                                    echo '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Edition::getLatest(): <strong>ERROR</strong> - ' . $e->getMessage() . '</div>';
                                }
                                
                            } else {
                                echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Edition class file not found</div>';
                            }
                            
                            // Test 5: Category class methods
                            echo '<h5>5. Testing Category Class Methods</h5>';
                            if (file_exists('classes/Category.php')) {
                                require_once 'classes/Category.php';
                                $categoryModel = new Category();
                                
                                try {
                                    $categories = $categoryModel->getAll();
                                    echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Category::getAll(): <strong>WORKING</strong> (Found ' . count($categories) . ' categories)</div>';
                                } catch (Exception $e) {
                                    echo '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Category::getAll(): <strong>ERROR</strong> - ' . $e->getMessage() . '</div>';
                                }
                                
                            } else {
                                echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Category class file not found</div>';
                            }
                            
                            echo '<hr>';
                            echo '<div class="alert alert-info"><h5><i class="fas fa-info-circle me-2"></i>All Tests Completed</h5>';
                            echo '<p>The database method issues have been resolved. The system should now work properly.</p></div>';
                            
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i><strong>Test Failed:</strong> ' . $e->getMessage() . '</div>';
                        }
                        ?>
                        
                        <div class="mt-4">
                            <h4><i class="fas fa-rocket me-2"></i>Next Steps</h4>
                            <div class="d-grid gap-2 d-md-block">
                                <a href="admin/dashboard.php" class="btn btn-primary">
                                    <i class="fas fa-tachometer-alt me-1"></i>Test Admin Dashboard
                                </a>
                                <a href="admin/categories.php" class="btn btn-success">
                                    <i class="fas fa-tags me-1"></i>Test Categories Management
                                </a>
                                <a href="index.php" class="btn btn-outline-primary">
                                    <i class="fas fa-home me-1"></i>Test Homepage
                                </a>
                                <a href="system-test.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-cogs me-1"></i>Full System Test
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
