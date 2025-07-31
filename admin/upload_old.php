<?php
/**
 * Admin Upload Interface
 * Upload new editions to the E-Paper CMS
 */

session_start();

// Include configuration first
require_once '../config/config.php';

// Simple authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: dashboard.php');
    exit;
}

require_once '../includes/database.php';

// Check if classes exist
if (file_exists('../classes/Edition.php')) {
    require_once '../classes/Edition.php';
} else {
    die('Required class files not found. Please check your installation.');
}

$message = '';
$messageType = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
    try {
        $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
        $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
        $date = filter_var($_POST['date'], FILTER_SANITIZE_STRING);
        
        if (!$title || !$date) {
            throw new Exception('Title and date are required');
        }
        
        $uploadFile = $_FILES['pdf_file'];
        
        if ($uploadFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed');
        }
        
        if ($uploadFile['type'] !== 'application/pdf') {
            throw new Exception('Only PDF files are allowed');
        }
        
        // Create upload directory
        $uploadDir = '../uploads/' . date('Y-m-d', strtotime($date)) . '/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }
        
        // Move uploaded file
        $pdfPath = $uploadDir . 'edition.pdf';
        if (!move_uploaded_file($uploadFile['tmp_name'], $pdfPath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        // Create basic thumbnail (placeholder for now)
        $thumbnailPath = $uploadDir . 'thumbnail.png';
        $placeholderContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==');
        file_put_contents($thumbnailPath, $placeholderContent);
        
        // Save to database
        if (class_exists('Edition')) {
            $editionModel = new Edition();
            $editionData = [
                'title' => $title,
                'description' => $description,
                'date' => $date,
                'pdf_path' => str_replace('../', '', $pdfPath),
                'thumbnail_path' => str_replace('../', '', $thumbnailPath),
                'status' => 'published',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $editionId = $editionModel->create($editionData);
            
            if ($editionId) {
                $message = 'Edition uploaded successfully!';
                $messageType = 'success';
            } else {
                throw new Exception('Failed to save edition to database');
            }
        } else {
            $message = 'Edition file uploaded successfully (database save skipped - class not found)';
            $messageType = 'warning';
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Edition - E-Paper CMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .upload-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .file-upload input[type=file] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-upload-label {
            display: block;
            padding: 2rem;
            border: 2px dashed #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .file-upload-label:hover {
            border-color: var(--primary-color);
            background: #f8f9fa;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .file-info {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="upload-container">
            <div class="header-nav">
                <h1><i class="fas fa-upload"></i> Upload New Edition</h1>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Edition Title</label>
                    <input type="text" id="title" name="title" class="form-control" 
                           placeholder="e.g., Daily News - July 29, 2025" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <textarea id="description" name="description" class="form-control" rows="3"
                              placeholder="Brief description of this edition..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="date">Publication Date</label>
                    <input type="date" id="date" name="date" class="form-control" 
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="pdf_file">PDF File</label>
                    <div class="file-upload">
                        <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" required>
                        <label for="pdf_file" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; margin-bottom: 1rem; color: #6c757d;"></i>
                            <div style="font-size: 1.1rem; margin-bottom: 0.5rem;">
                                Click to select or drag & drop your PDF file
                            </div>
                            <div class="file-info">
                                Maximum file size: 50MB • Only PDF files are supported
                            </div>
                        </label>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Edition
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // File upload enhancements
        document.getElementById('pdf_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const label = document.querySelector('.file-upload-label');
            
            if (file) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                label.innerHTML = `
                    <i class="fas fa-file-pdf" style="font-size: 2rem; margin-bottom: 1rem; color: #dc3545;"></i>
                    <div style="font-size: 1.1rem; margin-bottom: 0.5rem;">
                        ${file.name}
                    </div>
                    <div class="file-info">
                        Size: ${fileSize} MB • Click to change file
                    </div>
                `;
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('pdf_file');
            const titleInput = document.getElementById('title');
            const dateInput = document.getElementById('date');
            
            if (!fileInput.files[0]) {
                alert('Please select a PDF file to upload.');
                e.preventDefault();
                return;
            }
            
            if (!titleInput.value.trim()) {
                alert('Please enter a title for the edition.');
                titleInput.focus();
                e.preventDefault();
                return;
            }
            
            if (!dateInput.value) {
                alert('Please select a publication date.');
                dateInput.focus();
                e.preventDefault();
                return;
            }
            
            // Check file size (50MB limit)
            if (fileInput.files[0].size > 50 * 1024 * 1024) {
                alert('File size must be less than 50MB.');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
