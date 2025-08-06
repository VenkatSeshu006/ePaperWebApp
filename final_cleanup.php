<?php
/**
 * Final Cleanup - Remove Test and Development Files
 * Removes unnecessary test, cleanup, and development files
 */

echo "🧹 Final Project Cleanup - Removing Test & Development Files\n";
echo "============================================================\n\n";

// Files to remove (test, cleanup, development files)
$filesToRemove = [
    // Test files
    'test_gd.php',
    'test_watermark.php',
    'test_edition_creation.php',
    'project_status_check.php',
    'check_gd_status.php',
    'watermark_status.php',
    'verify_homepage.php',
    'check_admin_users.php',
    'check_db.php',
    'check_edition_pages.php',
    'check_tables.php',
    'add_download_count.php',
    'setup_watermark_db.php',
    'upload_validation.php',
    
    // Cleanup files
    'cleanup_cli.php',
    'cleanup_project.php',
    'run_cleanup.bat',
    
    // Development utilities
    'convert_latest_edition.php',
    'create_sample_pages.php',
    'pdf_processor.php',
    'process_editions.php',
    'reset_admin_password.php',
    'restart_apache.bat',
    
    // Environment/config test files
    'environment.php',
    'install.php'
];

$removedCount = 0;
$errors = [];

foreach ($filesToRemove as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "✅ Removed: $file\n";
            $removedCount++;
        } else {
            echo "❌ Failed to remove: $file\n";
            $errors[] = $file;
        }
    } else {
        echo "⚠️  Not found: $file\n";
    }
}

echo "\n📊 Cleanup Summary:\n";
echo "   ✅ Files removed: $removedCount\n";
echo "   ❌ Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\n❌ Failed to remove:\n";
    foreach ($errors as $error) {
        echo "   • $error\n";
    }
}

echo "\n🎯 Project is now clean and production-ready!\n";
echo "📁 Remaining files are only production-essential components.\n";
?>
