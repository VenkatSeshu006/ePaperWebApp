<?php
/**
 * E-Paper CMS v2.0 - Project Cleanup Script
 * Removes temporary files, optimizes database, and prepares for deployment
 */

echo "🧹 E-Paper CMS v2.0 - Project Cleanup Script\n";
echo "============================================\n\n";

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'includes/database.php';

$conn = getConnection();
if (!$conn) {
    echo "❌ Database connection failed!\n";
    exit(1);
}

echo "✅ Database connected successfully\n";

// 1. Clean up temporary files
echo "\n📁 Cleaning up temporary files...\n";
$tempDirs = ['uploads/temp/', 'cache/'];
$cleanedFiles = 0;

foreach ($tempDirs as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $cleanedFiles++;
            }
        }
        echo "   Cleaned $dir\n";
    }
}
echo "   Removed $cleanedFiles temporary files\n";

// 2. Optimize database tables
echo "\n🗄️ Optimizing database tables...\n";
$tables = ['editions', 'pages', 'clips', 'settings', 'users'];
foreach ($tables as $table) {
    $result = $conn->query("OPTIMIZE TABLE $table");
    if ($result) {
        echo "   Optimized table: $table\n";
    }
}

// 3. Clean up old logs
echo "\n📜 Cleaning up log files...\n";
$logFiles = glob('*.log');
foreach ($logFiles as $logFile) {
    if (file_exists($logFile)) {
        unlink($logFile);
        echo "   Removed: $logFile\n";
    }
}

// 4. Remove backup files
echo "\n🗂️ Removing backup files...\n";
$backupFiles = glob('*_backup.php');
$backupFiles = array_merge($backupFiles, glob('*_old.php'));
$backupFiles = array_merge($backupFiles, glob('index_*.php'));

foreach ($backupFiles as $backupFile) {
    if (file_exists($backupFile) && $backupFile !== 'index.php') {
        unlink($backupFile);
        echo "   Removed: $backupFile\n";
    }
}

// 5. Check file permissions
echo "\n🔐 Checking file permissions...\n";
$importantDirs = ['uploads/', 'cache/', 'includes/'];
foreach ($importantDirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "   $dir: $perms\n";
        if (!is_writable($dir)) {
            echo "   ⚠️ Warning: $dir is not writable\n";
        }
    }
}

// 6. Generate project info
echo "\n📊 Project Statistics:\n";
$totalFiles = count(glob('{*.php,admin/*.php,includes/*.php,api/*.php}', GLOB_BRACE));
$totalSize = 0;

function getDirSize($directory) {
    $size = 0;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file){
        $size += $file->getSize();
    }
    return $size;
}

$totalSize = getDirSize('./');
$totalSizeMB = round($totalSize / 1024 / 1024, 2);

echo "   Total PHP files: $totalFiles\n";
echo "   Total project size: {$totalSizeMB} MB\n";

// 7. Database statistics
$editionsCount = $conn->query("SELECT COUNT(*) as count FROM editions")->fetch_assoc()['count'] ?? 0;
$pagesCount = $conn->query("SELECT COUNT(*) as count FROM pages")->fetch_assoc()['count'] ?? 0;
$clipsCount = $conn->query("SELECT COUNT(*) as count FROM clips")->fetch_assoc()['count'] ?? 0;

echo "   Editions in database: $editionsCount\n";
echo "   Pages in database: $pagesCount\n";
echo "   Clips in database: $clipsCount\n";

// 8. Security check
echo "\n🔒 Security Checks:\n";
$securityIssues = 0;

// Check for exposed sensitive files
$sensitiveFiles = ['.env', 'config.php', 'database.php'];
foreach ($sensitiveFiles as $file) {
    if (file_exists($file)) {
        echo "   ⚠️ Warning: $file should not be web-accessible\n";
        $securityIssues++;
    }
}

// Check directory permissions
if (is_writable('./')) {
    echo "   ⚠️ Warning: Root directory is writable\n";
    $securityIssues++;
}

if ($securityIssues === 0) {
    echo "   ✅ No security issues found\n";
}

// 9. Create deployment checklist
echo "\n📋 Creating deployment checklist...\n";
$checklist = "# E-Paper CMS v2.0 - Deployment Checklist

## Pre-Deployment
- [ ] Database backup created
- [ ] All files uploaded to server
- [ ] File permissions set correctly
- [ ] Database credentials updated

## Post-Deployment
- [ ] Test homepage loading
- [ ] Test admin panel access
- [ ] Test file upload functionality
- [ ] Test sharing features
- [ ] Check error logs

## Performance Optimization
- [ ] Enable gzip compression
- [ ] Set proper cache headers
- [ ] Optimize images
- [ ] Monitor database performance

## Security
- [ ] Change default admin password
- [ ] Restrict file permissions
- [ ] Enable HTTPS
- [ ] Regular backups scheduled

Generated on: " . date('Y-m-d H:i:s') . "
";

file_put_contents('DEPLOYMENT_CHECKLIST.md', $checklist);
echo "   Created DEPLOYMENT_CHECKLIST.md\n";

// Close database connection
$conn->close();

echo "\n✅ Cleanup completed successfully!\n";
echo "📦 Project is ready for deployment\n";
echo "\nNext steps:\n";
echo "1. Review DEPLOYMENT_CHECKLIST.md\n";
echo "2. Test all functionality\n"; 
echo "3. Deploy to production server\n";
echo "4. Update database credentials\n";
echo "5. Set proper file permissions\n\n";

echo "🎉 E-Paper CMS v2.0 is ready to share!\n";
?>
