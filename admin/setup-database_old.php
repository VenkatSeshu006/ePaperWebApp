<?php
/**
 * Database Setup Script
 * Initialize the database with required tables
 */

session_start();

// Include configuration
require_once '../config/config.php';

// Only allow in development mode
if (ENVIRONMENT !== 'development') {
    die('Database setup only allowed in development mode');
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_database'])) {
    try {
        // Read the SQL file
        $sqlFile = '../database.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception('Database SQL file not found');
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Connect to MySQL without specifying database
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $executed = 0;
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $pdo->exec($statement);
                    $executed++;
                } catch (PDOException $e) {
                    // Log error but continue
                    error_log("SQL Error: " . $e->getMessage() . " Statement: " . $statement);
                }
            }
        }
        
        $message = "Database setup completed! Executed {$executed} SQL statements.";
        $messageType = 'success';
        
        // Add default admin user
        try {
            $pdo->exec("USE " . DB_NAME);
            $defaultAdminSql = "INSERT IGNORE INTO admin_users (username, email, password_hash, full_name, role) 
                               VALUES ('admin', 'admin@epaper.local', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'Administrator', 'admin')";
            $pdo->exec($defaultAdminSql);
            $message .= " Default admin user created (admin/admin123).";
        } catch (Exception $e) {
            $message .= " Warning: Could not create default admin user.";
        }
        
    } catch (Exception $e) {
        $message = "Database setup failed: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Check current database status
$databaseExists = false;
$tablesExist = [];

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    $databaseExists = $stmt->rowCount() > 0;
    
    if ($databaseExists) {
        $pdo->exec("USE " . DB_NAME);
        $stmt = $pdo->query("SHOW TABLES");
        $tablesExist = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
} catch (Exception $e) {
    $message = "Cannot connect to database: " . $e->getMessage();
    $messageType = 'error';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - E-Paper CMS</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .message { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .status-box { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .exists { color: green; font-weight: bold; }
        .missing { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>E-Paper CMS Database Setup</h1>
    
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <div class="status-box">
        <h2>Current Database Status</h2>
        <p><strong>Database Host:</strong> <?php echo DB_HOST; ?></p>
        <p><strong>Database Name:</strong> <?php echo DB_NAME; ?></p>
        <p><strong>Database User:</strong> <?php echo DB_USER; ?></p>
        <p><strong>Database Exists:</strong> <?php echo $databaseExists ? '<span class="exists">Yes</span>' : '<span class="missing">No</span>'; ?></p>
        
        <?php if ($databaseExists): ?>
            <h3>Existing Tables</h3>
            <?php if (empty($tablesExist)): ?>
                <p class="missing">No tables found</p>
            <?php else: ?>
                <table>
                    <tr><th>Table Name</th><th>Status</th></tr>
                    <?php
                    $requiredTables = ['admin_users', 'categories', 'editions', 'page_analytics', 'clips'];
                    foreach ($requiredTables as $table) {
                        $exists = in_array($table, $tablesExist);
                        echo "<tr><td>{$table}</td><td>" . ($exists ? '<span class="exists">Exists</span>' : '<span class="missing">Missing</span>') . "</td></tr>";
                    }
                    ?>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="status-box">
        <h2>Database Setup</h2>
        <p>This will:</p>
        <ul>
            <li>Create the database if it doesn't exist</li>
            <li>Create all required tables</li>
            <li>Set up the proper structure for E-Paper CMS</li>
            <li>Create a default admin user (admin/admin123)</li>
        </ul>
        
        <form method="POST">
            <button type="submit" name="setup_database" class="btn" onclick="return confirm('This will reset the database. Are you sure?')">
                Setup Database
            </button>
        </form>
    </div>
    
    <div class="status-box">
        <h2>Quick Actions</h2>
        <p>
            <a href="diagnostics.php" class="btn">Run Diagnostics</a>
            <a href="dashboard.php" class="btn">Go to Dashboard</a>
        </p>
    </div>
    
    <div class="status-box">
        <h2>Manual Setup</h2>
        <p>If automatic setup fails, you can manually run the SQL file:</p>
        <ol>
            <li>Access your MySQL server</li>
            <li>Run the contents of <code>database.sql</code></li>
            <li>Create an admin user in the <code>admin_users</code> table</li>
        </ol>
    </div>
    
</body>
</html>
