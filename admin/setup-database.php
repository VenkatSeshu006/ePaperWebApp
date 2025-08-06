<?php
/**
 * Database Setup Script
 * Initialize the database with required tables
 */

session_start();
define('ADMIN_PAGE', true);

// Include configuration
require_once '../config/config.php';

// Simple authentication check (optional for setup, but recommended)
$requireAuth = true;
if ($requireAuth && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    // Allow setup if no admin users exist yet, otherwise require auth
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
$pageTitle = 'Database Setup';
$pageSubtitle = 'Initialize database tables and structure';

$message = '';
$messageType = '';
$setupSteps = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_database'])) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        if (!$conn) {
            throw new Exception('Database connection failed');
        }
        
        // SQL for creating tables
        $sqlCommands = [
            // Categories table
            "CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT,
                color VARCHAR(7) DEFAULT '#007bff',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            
            // Editions table
            "CREATE TABLE IF NOT EXISTS editions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT,
                publication_date DATE,
                pdf_path VARCHAR(500),
                cover_image VARCHAR(500),
                views INT DEFAULT 0,
                status ENUM('draft', 'published', 'archived') DEFAULT 'published',
                category VARCHAR(100) DEFAULT 'general',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            
            // Edition categories junction table
            "CREATE TABLE IF NOT EXISTS edition_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                edition_id INT NOT NULL,
                category_id INT NOT NULL,
                FOREIGN KEY (edition_id) REFERENCES editions(id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                UNIQUE KEY unique_edition_category (edition_id, category_id)
            )",
            
            // Edition pages table
            "CREATE TABLE IF NOT EXISTS edition_pages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                edition_id INT NOT NULL,
                page_number INT NOT NULL,
                image_path VARCHAR(500) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (edition_id) REFERENCES editions(id) ON DELETE CASCADE,
                UNIQUE KEY unique_edition_page (edition_id, page_number)
            )",
            
            // Analytics table
            "CREATE TABLE IF NOT EXISTS analytics (
                id INT AUTO_INCREMENT PRIMARY KEY,
                edition_id INT,
                event_type ENUM('view', 'download', 'share') NOT NULL,
                user_ip VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (edition_id) REFERENCES editions(id) ON DELETE SET NULL
            )",
            
            // Admin users table
            "CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(100),
                email VARCHAR(100),
                role ENUM('admin', 'editor') DEFAULT 'admin',
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        ];
        
        $conn->beginTransaction();
        
        foreach ($sqlCommands as $i => $sql) {
            $stepName = [
                'Categories table',
                'Editions table', 
                'Edition-Categories junction table',
                'Edition pages table',
                'Analytics table',
                'Admin users table'
            ][$i];
            
            try {
                $result = $conn->query($sql);
                if ($result) {
                    $setupSteps[] = [
                        'step' => $stepName,
                        'status' => 'success',
                        'message' => 'Created successfully'
                    ];
                } else {
                    $errorInfo = $conn->errorInfo();
                    throw new Exception($errorInfo[2] ?? 'Unknown database error');
                }
            } catch (Exception $e) {
                $setupSteps[] = [
                    'step' => $stepName,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        
        // Insert default categories
        $defaultCategories = [
            ['general', 'General', 'General news and content', '#007bff'],
            ['news', 'News', 'Breaking news and updates', '#dc3545'],
            ['sports', 'Sports', 'Sports news and coverage', '#28a745'],
            ['business', 'Business', 'Business and economic news', '#ffc107'],
            ['lifestyle', 'Lifestyle', 'Lifestyle and entertainment', '#e83e8c'],
            ['technology', 'Technology', 'Technology and innovation', '#6f42c1']
        ];
        
        $stmt = $conn->prepare("INSERT IGNORE INTO categories (slug, name, description, color) VALUES (?, ?, ?, ?)");
        foreach ($defaultCategories as $cat) {
            $stmt->execute([$cat[0], $cat[1], $cat[2], $cat[3]]);
        }
        
        $setupSteps[] = [
            'step' => 'Default categories',
            'status' => 'success',
            'message' => 'Default categories inserted'
        ];
        
        $conn->commit();
        
        $message = 'Database setup completed successfully!';
        $messageType = 'success';
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        $message = 'Database setup failed: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Check current database status
$dbStatus = [];
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if ($conn) {
        $tables = ['categories', 'editions', 'edition_categories', 'edition_pages', 'analytics', 'admin_users'];
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            $exists = $result && $result->rowCount() > 0;
            
            $count = 0;
            if ($exists) {
                $countResult = $conn->query("SELECT COUNT(*) as count FROM $table");
                if ($countResult) {
                    $count = $countResult->fetch()['count'];
                }
            }
            
            $dbStatus[$table] = [
                'exists' => $exists,
                'count' => $count
            ];
        }
    }
} catch (Exception $e) {
    $message = 'Error checking database status: ' . $e->getMessage();
    $messageType = 'warning';
}

// Set alert message for layout
if ($message) {
    $alertMessage = $message;
    $alertType = $messageType;
}

// Include the admin layout
require_once 'includes/admin_layout.php';
?>

<!-- Database Setup Content -->
<div class="row">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-database"></i>
                    Database Setup Wizard
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle"></i>
                        Database Setup Information
                    </h6>
                    <p>This wizard will create all necessary database tables for your E-Paper CMS installation.</p>
                    <ul class="mb-0">
                        <li>Creates tables for editions, categories, analytics, and users</li>
                        <li>Sets up proper relationships and constraints</li>
                        <li>Inserts default categories to get you started</li>
                        <li>Safe to run multiple times (won't overwrite existing data)</li>
                    </ul>
                </div>
                
                <?php if (!empty($setupSteps)): ?>
                <div class="mb-4">
                    <h6>Setup Results:</h6>
                    <div class="list-group">
                        <?php foreach ($setupSteps as $step): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-<?php echo $step['status'] === 'success' ? 'check-circle text-success' : 'times-circle text-danger'; ?> me-2"></i>
                                <strong><?php echo htmlspecialchars($step['step']); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($step['message']); ?></small>
                            </div>
                            <span class="badge bg-<?php echo $step['status'] === 'success' ? 'success' : 'danger'; ?>">
                                <?php echo $step['status']; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0">
                                Click the button below to initialize your database with all required tables and default data.
                            </p>
                        </div>
                        <div>
                            <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            <button type="submit" name="setup_database" class="btn btn-admin-primary">
                                <i class="fas fa-play"></i> Initialize Database
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Database Status -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-table"></i>
                    Current Database Status
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($dbStatus)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Table</th>
                                    <th>Status</th>
                                    <th>Records</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dbStatus as $table => $status): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($table); ?></code></td>
                                    <td>
                                        <?php if ($status['exists']): ?>
                                            <span class="badge bg-success">Exists</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Missing</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $status['exists'] ? number_format($status['count']) : '-'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Unable to check database status</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Connection Info -->
        <div class="admin-card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-plug"></i>
                    Connection Information
                </h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Host:</strong></td>
                        <td><?php echo defined('DB_HOST') ? DB_HOST : 'Not configured'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Database:</strong></td>
                        <td><?php echo defined('DB_NAME') ? DB_NAME : 'Not configured'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>User:</strong></td>
                        <td><?php echo defined('DB_USER') ? DB_USER : 'Not configured'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <?php
                            try {
                                $db = Database::getInstance();
                                $conn = $db->getConnection();
                                if ($conn) {
                                    echo '<span class="badge bg-success">Connected</span>';
                                } else {
                                    echo '<span class="badge bg-danger">Failed</span>';
                                }
                            } catch (Exception $e) {
                                echo '<span class="badge bg-danger">Error</span>';
                            }
                            ?>
                        </td>
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
                    <a href="test-database.php" class="btn btn-outline-info">
                        <i class="fas fa-vial"></i> Test Database
                    </a>
                    <a href="diagnostics.php" class="btn btn-outline-warning">
                        <i class="fas fa-stethoscope"></i> Run Diagnostics
                    </a>
                    <button type="button" class="btn btn-outline-secondary" onclick="exportSchema()">
                        <i class="fas fa-download"></i> Export Schema
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom JavaScript -->
<?php 
$additionalJS = "
<script>
function exportSchema() {
    const schema = " . json_encode($dbStatus) . ";
    const dataStr = JSON.stringify(schema, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'database-schema-' + new Date().toISOString().split('T')[0] + '.json';
    link.click();
    
    URL.revokeObjectURL(url);
    showToast('Database schema exported successfully', 'success');
}

// Confirm before running setup
document.querySelector('button[name=\"setup_database\"]').addEventListener('click', function(e) {
    if (!confirm('Are you sure you want to initialize the database?\\n\\nThis will create all necessary tables.')) {
        e.preventDefault();
    }
});
</script>
";

// Include the admin layout footer
require_once 'includes/admin_layout_footer.php';
?>
