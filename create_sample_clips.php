<?php
/**
 * Create Sample Clips
 * Adds sample clips for testing
 */

require_once 'includes/db.php';

$conn = getConnection();

if (!$conn) {
    die("Database connection failed");
}

echo "<h2>Creating Sample Clips...</h2>";

// Ensure clips directory exists
$clipsDir = 'uploads/clips/';
if (!is_dir($clipsDir)) {
    mkdir($clipsDir, 0755, true);
    echo "<p>✓ Created clips directory</p>";
}

// Create sample clip files (simple colored rectangles)
$clipData = [
    [
        'title' => 'Breaking News Headline',
        'description' => 'Important news clip about current events',
        'edition_id' => 1,
        'color' => '#FF6B6B'
    ],
    [
        'title' => 'Sports Section Highlight',
        'description' => 'Key sports news and scores',
        'edition_id' => 1,
        'color' => '#4ECDC4'
    ],
    [
        'title' => 'Weather Forecast',
        'description' => 'Weekly weather outlook',
        'edition_id' => 1,
        'color' => '#45B7D1'
    ],
    [
        'title' => 'Business Report',
        'description' => 'Market analysis and financial news',
        'edition_id' => 1,
        'color' => '#96CEB4'
    ],
    [
        'title' => 'Entertainment News',
        'description' => 'Celebrity news and movie reviews',
        'edition_id' => 1,
        'color' => '#FFEAA7'
    ]
];

foreach ($clipData as $index => $clip) {
    // Create a simple colored image
    $width = 300;
    $height = 200;
    $image = imagecreate($width, $height);
    
    // Convert hex color to RGB
    $hex = ltrim($clip['color'], '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $backgroundColor = imagecolorallocate($image, $r, $g, $b);
    $textColor = imagecolorallocate($image, 255, 255, 255);
    
    // Fill background
    imagefill($image, 0, 0, $backgroundColor);
    
    // Add text
    $text = "Sample Clip " . ($index + 1);
    imagestring($image, 5, 50, 90, $text, $textColor);
    
    // Save image
    $filename = 'sample_clip_' . ($index + 1) . '_' . uniqid() . '.png';
    $filepath = $clipsDir . $filename;
    
    if (imagepng($image, $filepath)) {
        echo "<p>✓ Created image: $filename</p>";
        
        // Insert into database
        $stmt = $conn->prepare("
            INSERT INTO clips (edition_id, image_id, page_number, x, y, width, height, image_path, title, description) 
            VALUES (?, 1, 1, 0, 0, 300, 200, ?, ?, ?)
        ");
        
        $stmt->bind_param('isss', $clip['edition_id'], $filepath, $clip['title'], $clip['description']);
        
        if ($stmt->execute()) {
            echo "<p>✓ Added clip to database: {$clip['title']}</p>";
        } else {
            echo "<p>❌ Failed to add clip to database: {$clip['title']}</p>";
        }
    } else {
        echo "<p>❌ Failed to create image: $filename</p>";
    }
    
    imagedestroy($image);
}

echo "<hr>";
echo "<h3>✅ Sample clips created successfully!</h3>";
echo "<p><a href='clips.php' class='btn btn-primary'>View Clips</a></p>";
?>
