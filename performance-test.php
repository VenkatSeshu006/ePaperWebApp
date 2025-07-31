<?php
// Performance Test Script - E-Paper CMS v2.0
header('Content-Type: text/html; charset=UTF-8');
include_once 'config/config.php';

// Start timing
$start_time = microtime(true);
$start_memory = memory_get_usage();

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>E-Paper CMS - Performance Test</title>\n";
echo "<style>body{font-family:sans-serif;max-width:800px;margin:40px auto;padding:20px}";
echo ".test{margin:20px 0;padding:15px;border-left:4px solid #2196F3;background:#f5f5f5}";
echo ".pass{border-color:#4CAF50}.fail{border-color:#f44336}</style>\n</head>\n<body>\n";

echo "<h1>E-Paper CMS Performance Test</h1>\n";

// Test 1: Database Connection
$test1_start = microtime(true);
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) throw new Exception($conn->connect_error);
    $test1_time = round((microtime(true) - $test1_start) * 1000, 2);
    echo "<div class='test pass'>✓ Database Connection: {$test1_time}ms</div>\n";
} catch (Exception $e) {
    $test1_time = round((microtime(true) - $test1_start) * 1000, 2);
    echo "<div class='test fail'>✗ Database Connection Failed: {$test1_time}ms - {$e->getMessage()}</div>\n";
}

// Test 2: Latest Edition Query
if (isset($conn)) {
    $test2_start = microtime(true);
    $result = $conn->query("SELECT id, title, date, thumbnail_path FROM editions ORDER BY date DESC LIMIT 1");
    $test2_time = round((microtime(true) - $test2_start) * 1000, 2);
    if ($result && $result->num_rows > 0) {
        echo "<div class='test pass'>✓ Latest Edition Query: {$test2_time}ms</div>\n";
    } else {
        echo "<div class='test fail'>✗ No editions found: {$test2_time}ms</div>\n";
    }
}

// Test 3: Archives Query
if (isset($conn)) {
    $test3_start = microtime(true);
    $result = $conn->query("SELECT id, title, date FROM editions ORDER BY date DESC LIMIT 10");
    $test3_time = round((microtime(true) - $test3_start) * 1000, 2);
    if ($result) {
        $count = $result->num_rows;
        echo "<div class='test pass'>✓ Archives Query ({$count} results): {$test3_time}ms</div>\n";
    } else {
        echo "<div class='test fail'>✗ Archives Query Failed: {$test3_time}ms</div>\n";
    }
}

// Test 4: File System Check
$test4_start = microtime(true);
$upload_check = is_dir('uploads') && is_writable('uploads');
$cache_check = is_dir('cache') && is_writable('cache');
$test4_time = round((microtime(true) - $test4_start) * 1000, 2);
if ($upload_check && $cache_check) {
    echo "<div class='test pass'>✓ File System Check: {$test4_time}ms</div>\n";
} else {
    echo "<div class='test fail'>✗ File System Issues: {$test4_time}ms</div>\n";
}

// Final Results
$total_time = round((microtime(true) - $start_time) * 1000, 2);
$memory_used = round((memory_get_usage() - $start_memory) / 1024, 2);
$peak_memory = round(memory_get_peak_usage() / 1024, 2);

echo "<h2>Performance Summary</h2>\n";
echo "<div class='test'>Total Execution Time: {$total_time}ms</div>\n";
echo "<div class='test'>Memory Used: {$memory_used}KB</div>\n";
echo "<div class='test'>Peak Memory: {$peak_memory}KB</div>\n";

if ($total_time < 100) {
    echo "<div class='test pass'>✓ Excellent Performance!</div>\n";
} else if ($total_time < 500) {
    echo "<div class='test pass'>✓ Good Performance</div>\n";
} else {
    echo "<div class='test fail'>⚠ Performance Could Be Improved</div>\n";
}

echo "</body>\n</html>";

if (isset($conn)) $conn->close();
?>
