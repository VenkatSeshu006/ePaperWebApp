<?php
/**
 * Quick Settings Update Demo
 * Run this to see dynamic changes in action
 */

require_once 'includes/database.php';
$conn = getConnection();

// Sample dynamic content updates
$updates = [
    'site_title' => 'Prayatnam Digital News',
    'header_logo_text' => 'Prayatnam E-Paper',
    'site_tagline' => 'Excellence in Digital Journalism',
    'copyright_text' => 'Prayatnam Digital News. All rights reserved.',
    'meta_description' => 'Stay informed with Prayatnam Digital News - Your trusted source for comprehensive news coverage and digital journalism excellence.',
    'contact_email' => 'news@prayatnam.com',
    'footer_links' => json_encode([
        ['text' => 'Home', 'url' => '/'],
        ['text' => 'Latest News', 'url' => '#latest'],
        ['text' => 'Sports', 'url' => '#sports'],
        ['text' => 'Business', 'url' => '#business'],
        ['text' => 'Contact', 'url' => '#contact']
    ]),
    'social_facebook' => 'https://facebook.com/prayatnam',
    'social_twitter' => 'https://twitter.com/prayatnam',
    'social_instagram' => 'https://instagram.com/prayatnam'
];

$success = 0;
$total = count($updates);

foreach ($updates as $key => $value) {
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    if ($stmt->execute([$key, $value])) {
        $success++;
    }
}

echo "Dynamic Settings Update Complete!\n";
echo "Updated: $success / $total settings\n\n";
echo "✅ Changes made:\n";
echo "• Site Title: Prayatnam Digital News\n";
echo "• Header Logo: Prayatnam E-Paper\n";
echo "• Tagline: Excellence in Digital Journalism\n";
echo "• Footer Links: 5 navigation items\n";
echo "• Social Media: Facebook, Twitter, Instagram\n";
echo "• Contact Email: news@prayatnam.com\n\n";
echo "🌟 Visit your homepage to see the changes!\n";
echo "🔧 Use Admin > Page Settings to customize further.\n";
?>
