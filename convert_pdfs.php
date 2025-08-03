<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF to Images Converter</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .process-button {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            margin: 20px auto;
        }
        .process-button:hover {
            background: #0056b3;
        }
        .status {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f1aeb5;
        }
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #b8daff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .success-text { color: #28a745; }
        .error-text { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 PDF to Images Converter</h1>
        
        <div class="status info">
            <strong>ℹ️ About this tool:</strong><br>
            This tool converts existing PDF editions into individual page images using Ghostscript.
            Each PDF page will be converted to a high-quality PNG image that can be viewed page-by-page 
            with the clipping tool and other interactive features.
        </div>

        <?php if (isset($_POST['process'])): ?>
            <div class="status info">
                <strong>🔄 Processing PDFs...</strong>
            </div>
            
            <?php
            require_once 'pdf_processor.php';
            
            try {
                $processor = new PDFProcessor();
                $results = $processor->processAllExistingPDFs();
                
                if (empty($results)) {
                    echo '<div class="status success">✅ <strong>All PDFs are already processed!</strong><br>No PDFs found that need conversion to images.</div>';
                } else {
                    echo '<div class="status success">✅ <strong>Processing completed!</strong></div>';
                    
                    echo '<h2>📊 Processing Results</h2>';
                    echo '<table>';
                    echo '<tr><th>Edition ID</th><th>Title</th><th>Status</th><th>Details</th></tr>';
                    
                    foreach ($results as $result) {
                        $statusClass = $result['status'] === 'success' ? 'success-text' : 'error-text';
                        $statusIcon = $result['status'] === 'success' ? '✅' : '❌';
                        
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($result['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($result['title']) . '</td>';
                        echo '<td class="' . $statusClass . '">' . $statusIcon . ' ' . ucfirst($result['status']) . '</td>';
                        echo '<td>' . htmlspecialchars($result['message']) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                    
                    // Summary
                    $successCount = count(array_filter($results, function($r) { return $r['status'] === 'success'; }));
                    $errorCount = count($results) - $successCount;
                    
                    echo '<div class="status ' . ($errorCount > 0 ? 'error' : 'success') . '">';
                    echo '<strong>📈 Summary:</strong><br>';
                    echo "✅ Successfully processed: $successCount editions<br>";
                    if ($errorCount > 0) {
                        echo "❌ Failed to process: $errorCount editions<br>";
                    }
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="status error">❌ <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
            
        <?php else: ?>
            
            <p>Click the button below to scan for PDF editions that haven't been converted to individual page images yet, and convert them automatically.</p>
            
            <form method="post" action="">
                <button type="submit" name="process" class="process-button">
                    🚀 Start PDF to Images Conversion
                </button>
            </form>
            
            <div class="status info">
                <strong>🔧 What this does:</strong><br>
                • Scans all published editions with PDF files<br>
                • Identifies PDFs that don't have individual page images<br>
                • Converts each PDF page to a high-quality PNG image (150 DPI)<br>
                • Saves page images to the database for page-by-page viewing<br>
                • Updates the edition with the correct page count<br>
                • Enables clipping, navigation, and other interactive features
            </div>
            
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <div style="text-align: center; color: #666;">
            <small>
                <strong>Requirements:</strong> Ghostscript must be installed and configured.<br>
                <strong>Location:</strong> <?php echo htmlspecialchars(GHOSTSCRIPT_COMMAND ?? 'Not configured'); ?>
            </small>
        </div>
    </div>
</body>
</html>
