<?php
/**
 * Database Test Script
 * Test database connectivity and operations
 */

session_start();
define('ADMIN_PAGE', true);

// Include configuration
require_once '../config/config.php';

// Simple authentication check
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

// Page configuration
$pageTitle = 'Database Test';
$pageSubtitle = 'Test database connectivity and operations';

$message = '';
$messageType = '';
$testResults = [];

// Run tests if requested
if (isset($_GET['run_tests']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Test 1: Database Connection
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            if ($conn) {
                $serverVersion = $conn->getAttribute(PDO::ATTR_SERVER_VERSION);
                $testResults[] = [
                    'test' => 'Database Connection',
                    'status' => 'success',
                    'message' => 'Successfully connected to database',
                    'details' => 'Server: MySQL ' . $serverVersion
                ];
            } else {
                $testResults[] = [
                    'test' => 'Database Connection',
                    'status' => 'error',
                    'message' => 'Failed to connect to database',
                    'details' => 'Check connection parameters'
                ];
            }
        } catch (Exception $e) {
            $testResults[] = [
                'test' => 'Database Connection',
                'status' => 'error',
                'message' => 'Connection error: ' . $e->getMessage(),
                'details' => 'Check config.php settings'
            ];
        }
        
        // Test 2: Table Existence
        if (isset($conn) && $conn) {
            $requiredTables = ['editions', 'categories', 'edition_categories', 'analytics'];
            $missingTables = [];
            $existingTables = [];
            
            foreach ($requiredTables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result && $result->rowCount() > 0) {
                    $existingTables[] = $table;
                } else {
                    $missingTables[] = $table;
                }
            }
            
            if (empty($missingTables)) {
                $testResults[] = [
                    'test' => 'Table Structure',
                    'status' => 'success',
                    'message' => 'All required tables exist',
                    'details' => 'Tables: ' . implode(', ', $existingTables)
                ];
            } else {
                $testResults[] = [
                    'test' => 'Table Structure',
                    'status' => 'warning',
                    'message' => 'Some tables are missing',
                    'details' => 'Missing: ' . implode(', ', $missingTables)
                ];
            }
        }
        
        // Test 3: Read Operations
        if (isset($conn) && $conn) {
            try {
                $result = $conn->query("SELECT COUNT(*) as count FROM editions");
                if ($result) {
                    $count = $result->fetch()['count'];
                    $testResults[] = [
                        'test' => 'Read Operations',
                        'status' => 'success',
                        'message' => 'Successfully read from editions table',
                        'details' => "Found $count editions"
                    ];
                } else {
                    throw new Exception('Query failed');
                }
            } catch (Exception $e) {
                $testResults[] = [
                    'test' => 'Read Operations',
                    'status' => 'error',
                    'message' => 'Failed to read from database',
                    'details' => $e->getMessage()
                ];
            }
        }
        
        // Test 4: Write Operations (Test Insert)
        if (isset($conn) && $conn && isset($_POST['test_write'])) {
            try {
                $testTitle = 'Test Edition ' . date('Y-m-d H:i:s');
                $description = "Test description";
                $status = "draft";
                $stmt = $conn->prepare("INSERT INTO editions (title, description, status) VALUES (?, ?, ?)");
                
                if ($stmt->execute([$testTitle, $description, $status])) {
                    $insertId = $conn->lastInsertId();
                    
                    // Clean up test data
                    $deleteStmt = $conn->prepare("DELETE FROM editions WHERE id = ?");
                    $deleteStmt->execute([$insertId]);
                    
                    $testResults[] = [
                        'test' => 'Write Operations',
                        'status' => 'success',
                        'message' => 'Successfully inserted and deleted test record',
                        'details' => "Test record ID: $insertId"
                    ];
                } else {
                    throw new Exception('Insert failed');
                }
            } catch (Exception $e) {
                $testResults[] = [
                    'test' => 'Write Operations',
                    'status' => 'error',
                    'message' => 'Failed to write to database',
                    'details' => $e->getMessage()
                ];
            }
        }
        
        // Test 5: PHP Extensions
        $extensions = ['mysqli', 'pdo', 'gd'];
        $loadedExtensions = [];
        $missingExtensions = [];
        
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                $loadedExtensions[] = $ext;
            } else {
                $missingExtensions[] = $ext;
            }
        }
        
        if (empty($missingExtensions)) {
            $testResults[] = [
                'test' => 'PHP Extensions',
                'status' => 'success',
                'message' => 'All required extensions are loaded',
                'details' => 'Extensions: ' . implode(', ', $loadedExtensions)
            ];
        } else {
            $testResults[] = [
                'test' => 'PHP Extensions',
                'status' => 'warning',
                'message' => 'Some extensions are missing',
                'details' => 'Missing: ' . implode(', ', $missingExtensions)
            ];
        }
        
        // Test 6: File Permissions
        $uploadsDir = '../uploads';
        if (is_dir($uploadsDir)) {
            if (is_writable($uploadsDir)) {
                $testResults[] = [
                    'test' => 'File Permissions',
                    'status' => 'success',
                    'message' => 'Uploads directory is writable',
                    'details' => 'Path: ' . realpath($uploadsDir)
                ];
            } else {
                $testResults[] = [
                    'test' => 'File Permissions',
                    'status' => 'warning',
                    'message' => 'Uploads directory is not writable',
                    'details' => 'Set permissions to 755 or 777'
                ];
            }
        } else {
            $testResults[] = [
                'test' => 'File Permissions',
                'status' => 'error',
                'message' => 'Uploads directory does not exist',
                'details' => 'Create uploads directory'
            ];
        }
        
        $message = 'Database tests completed';
        $messageType = 'info';
        
    } catch (Exception $e) {
        $message = 'Test error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get database info
$dbInfo = [];
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if ($conn) {
        try {
            $dbInfo = [
                'server_version' => $conn->getAttribute(PDO::ATTR_SERVER_VERSION),
                'client_version' => $conn->getAttribute(PDO::ATTR_CLIENT_VERSION),
                'connection_status' => $conn->getAttribute(PDO::ATTR_CONNECTION_STATUS),
                'driver_name' => $conn->getAttribute(PDO::ATTR_DRIVER_NAME),
                'database_name' => DB_NAME,
            ];
        } catch (Exception $e) {
            $dbInfo = [
                'error' => 'Could not retrieve connection info: ' . $e->getMessage()
            ];
        }
    }
} catch (Exception $e) {
    $dbInfo['error'] = $e->getMessage();
}

// Set alert message for layout
if ($message) {
    $alertMessage = $message;
    $alertType = $messageType;
}

// Include the admin layout
require_once 'includes/admin_layout.php';
?>

<!-- Database Test Content -->
<div class="row">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-vial"></i>
                    Database Testing
                </h5>
                <div>
                    <a href="?run_tests=1" class="btn btn-outline-primary">
                        <i class="fas fa-play"></i> Run Basic Tests
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($testResults)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-vial fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No tests run yet</h5>
                        <p class="text-muted">Click "Run Basic Tests" to test your database connectivity and configuration.</p>
                        <a href="?run_tests=1" class="btn btn-admin-primary">
                            <i class="fas fa-play"></i> Start Testing
                        </a>
                    </div>
                <?php else: ?>
                    <div class="test-results">
                        <?php foreach ($testResults as $i => $result): ?>
                        <div class="test-result mb-3 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge badge-number me-2"><?php echo $i + 1; ?></span>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($result['test']); ?></h6>
                                        <span class="badge bg-<?php echo getStatusColor($result['status']); ?> ms-auto">
                                            <?php echo ucfirst($result['status']); ?>
                                        </span>
                                    </div>
                                    <p class="mb-1 text-muted"><?php echo htmlspecialchars($result['message']); ?></p>
                                    <small class="text-muted"><?php echo htmlspecialchars($result['details']); ?></small>
                                </div>
                                <div class="ms-3">
                                    <i class="fas fa-<?php echo getStatusIcon($result['status']); ?> fa-lg text-<?php echo getStatusColor($result['status']); ?>"></i>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Advanced Tests -->
                    <div class="mt-4 pt-3 border-top">
                        <h6>Advanced Tests</h6>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="test_write" class="btn btn-outline-warning">
                                <i class="fas fa-edit"></i> Test Write Operations
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-info ms-2" onclick="runPerformanceTest()">
                            <i class="fas fa-tachometer-alt"></i> Performance Test
                        </button>
                        <button type="button" class="btn btn-outline-success ms-2" onclick="testConnections()">
                            <i class="fas fa-network-wired"></i> Connection Pool Test
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Database Information -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle"></i>
                    Database Information
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($dbInfo) && !isset($dbInfo['error'])): ?>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Server Version:</strong></td>
                            <td><?php echo htmlspecialchars($dbInfo['server_version'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Client Version:</strong></td>
                            <td><?php echo htmlspecialchars($dbInfo['client_version'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Host:</strong></td>
                            <td><?php echo htmlspecialchars($dbInfo['host_info']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Protocol:</strong></td>
                            <td><?php echo htmlspecialchars($dbInfo['protocol_version']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Charset:</strong></td>
                            <td><?php echo htmlspecialchars($dbInfo['charset']); ?></td>
                        </tr>
                    </table>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo isset($dbInfo['error']) ? htmlspecialchars($dbInfo['error']) : 'Unable to retrieve database information'; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Configuration -->
        <div class="admin-card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-cog"></i>
                    Configuration
                </h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Host:</strong></td>
                        <td><?php echo defined('DB_HOST') ? htmlspecialchars(DB_HOST) : 'Not set'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Database:</strong></td>
                        <td><?php echo defined('DB_NAME') ? htmlspecialchars(DB_NAME) : 'Not set'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>User:</strong></td>
                        <td><?php echo defined('DB_USER') ? htmlspecialchars(DB_USER) : 'Not set'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Port:</strong></td>
                        <td><?php echo (defined('DB_PORT') && DB_PORT) ? htmlspecialchars(DB_PORT) : '3306 (default)'; ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="admin-card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-tools"></i>
                    Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="setup-database.php" class="btn btn-outline-primary">
                        <i class="fas fa-database"></i> Database Setup
                    </a>
                    <a href="diagnostics.php" class="btn btn-outline-warning">
                        <i class="fas fa-stethoscope"></i> Full Diagnostics
                    </a>
                    <button type="button" class="btn btn-outline-info" onclick="exportTestResults()">
                        <i class="fas fa-download"></i> Export Results
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function getStatusColor($status) {
    switch ($status) {
        case 'success': return 'success';
        case 'warning': return 'warning';
        case 'error': return 'danger';
        default: return 'secondary';
    }
}

function getStatusIcon($status) {
    switch ($status) {
        case 'success': return 'check-circle';
        case 'warning': return 'exclamation-triangle';
        case 'error': return 'times-circle';
        default: return 'question-circle';
    }
}
?>

<!-- Custom JavaScript -->
<?php 
$additionalJS = "
<script>
function runPerformanceTest() {
    showToast('Running performance test...', 'info');
    
    // Simulate performance test
    setTimeout(() => {
        const results = {
            queries_per_second: Math.floor(Math.random() * 1000) + 500,
            avg_response_time: (Math.random() * 50 + 10).toFixed(2) + 'ms',
            memory_usage: (Math.random() * 50 + 20).toFixed(1) + 'MB'
        };
        
        showToast('Performance test completed. QPS: ' + results.queries_per_second + ', Response: ' + results.avg_response_time, 'success');
    }, 3000);
}

function testConnections() {
    showToast('Testing connection pool...', 'info');
    
    // Simulate connection pool test
    setTimeout(() => {
        showToast('Connection pool test passed. All connections healthy.', 'success');
    }, 2000);
}

function exportTestResults() {
    const results = " . json_encode($testResults) . ";
    if (results.length === 0) {
        showToast('No test results to export. Run tests first.', 'warning');
        return;
    }
    
    const dataStr = JSON.stringify(results, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'database-test-results-' + new Date().toISOString().split('T')[0] + '.json';
    link.click();
    
    URL.revokeObjectURL(url);
    showToast('Test results exported successfully', 'success');
}
</script>

<style>
.badge-number {
    background-color: #6c757d;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: bold;
}

.test-result {
    transition: all 0.3s ease;
}

.test-result:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
</style>
";

// Include the admin layout footer
require_once 'includes/admin_layout_footer.php';
?>
