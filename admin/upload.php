<?php
/**
 * Admin Upload Interface
 * Upload new editions to the E-Paper CMS
 */

session_start();
define('ADMIN_PAGE', true);

// Include configuration first
require_once '../config.php';

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

// Check if classes exist
if (file_exists('../classes/Edition.php')) {
    require_once '../classes/Edition.php';
} else {
    die('Required class files not found. Please check your installation.');
}

// Page configuration
$pageTitle = 'Upload Edition';
$pageSubtitle = 'Upload new newspaper editions';

$message = '';
$messageType = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['pdf_file'])) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        if (!$conn) {
            throw new Exception('Database connection failed');
        }
        
        $edition = new Edition();
        
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $category = $_POST['category'] ?? 'general';
        $publication_date = $_POST['publication_date'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'published'; // Default to published
        
        if (empty($title)) {
            throw new Exception('Title is required');
        }
        
        $file = $_FILES['pdf_file'] ?? '';
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }
        
        if ($file['type'] !== 'application/pdf') {
            throw new Exception('Only PDF files are allowed');
        }
        
        // Create upload directory
        $uploadDir = '../uploads/' . date('Y-m-d') . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = 'edition.pdf';
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file(($file['tmp_name'] ?? ''), $filepath)) {
            // Get file size
            $fileSize = filesize($filepath);
            
            // Generate thumbnail using PDF first page (simplified approach)
            $thumbnailPath = $uploadDir . 'thumbnail.png';
            
            // Try to generate thumbnail using Ghostscript if available
            $gsCommand = defined('GHOSTSCRIPT_COMMAND') ? GHOSTSCRIPT_COMMAND : 'gswin64c.exe';
            $thumbnailCmd = "\"$gsCommand\" -dNOPAUSE -dBATCH -sDEVICE=png16m -dFirstPage=1 -dLastPage=1 -r150 -dGraphicsAlphaBits=4 -dTextAlphaBits=4 -sOutputFile=\"$thumbnailPath\" \"$filepath\" 2>&1";
            
            $thumbnailOutput = [];
            $thumbnailReturn = 0;
            exec($thumbnailCmd, $thumbnailOutput, $thumbnailReturn);
            
            // Try to get total pages count
            $totalPages = 0;
            if ($thumbnailReturn === 0) {
                // If thumbnail generation succeeded, try to get page count
                $pageCountCmd = "$gsCommand -q -dNODISPLAY -c \"($filepath) (r) file runpdfbegin pdfpagecount = quit\" 2>&1";
                $pageOutput = [];
                $pageReturn = 0;
                exec($pageCountCmd, $pageOutput, $pageReturn);
                if ($pageReturn === 0 && !empty($pageOutput)) {
                    $totalPages = (int)trim($pageOutput[0]);
                }
            }
            
            // Insert into database
            $result = $edition->create([
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'publication_date' => $publication_date,
                'pdf_path' => str_replace('../', '', $filepath), // Use pdf_path to match database
                'thumbnail_path' => file_exists($thumbnailPath) ? str_replace('../', '', $thumbnailPath) : '',
                'status' => $status,
                'file_size' => $fileSize,
                'total_pages' => $totalPages
            ]);
            
            if ($result) {
                // Automatically process PDF to images with enhanced quality
                try {
                    require_once '../enhanced_quality_processor.php';
                    $processor = new EnhancedQualityPDFProcessor();
                    $pages = $processor->processWithBestQuality($filepath, $result);
                    
                    // Run comprehensive post-processing to ensure homepage compatibility
                    require_once '../edition_post_processor.php';
                    $postProcessor = new EditionPostProcessor();
                    $postResults = $postProcessor->processNewEdition($result);
                    
                    $statusText = $status === 'draft' ? 'uploaded as draft' : 'uploaded and published';
                    $message = "Edition $statusText successfully!";
                    
                    if (!empty($pages)) {
                        $message .= " (" . count($pages) . " high-quality pages converted)";
                    } elseif ($totalPages > 0) {
                        $message .= " ($totalPages pages detected)";
                    }
                    
                    // Add post-processing results
                    if ($postResults['homepage_ready']) {
                        $message .= " [✅ Homepage ready]";
                    }
                    if (!empty($postResults['message'])) {
                        $message .= " " . $postResults['message'];
                    }
                    
                    $messageType = 'success';
                } catch (Exception $e) {
                    $statusText = $status === 'draft' ? 'uploaded as draft' : 'uploaded and published';
                    $message = "Edition $statusText successfully, but PDF conversion failed: " . $e->getMessage();
                    $messageType = 'warning';
                }
            } else {
                throw new Exception('Failed to save edition to database');
            }
        } else {
            throw new Exception('Failed to move uploaded file');
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Set alert message for layout
if ($message) {
    $alertMessage = $message;
    $alertType = $messageType;
}

// Include the admin layout
require_once 'includes/admin_layout.php';
?>

<!-- Upload Form -->
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-upload"></i>
                    Upload New Edition
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label">
                                    <i class="fas fa-heading"></i>
                                    Edition Title *
                                </label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       placeholder="Enter edition title" required>
                                <div class="form-text">
                                    This will be displayed as the main title for this edition
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="publication_date" class="form-label">
                                    <i class="fas fa-calendar"></i>
                                    Publication Date
                                </label>
                                <input type="date" class="form-control" id="publication_date" 
                                       name="publication_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">
                            <i class="fas fa-align-left"></i>
                            Description
                        </label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Brief description of this edition (optional)"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category" class="form-label">
                                    <i class="fas fa-tags"></i>
                                    Category
                                </label>
                                <select class="form-select" id="category" name="category">
                                    <option value="general">General</option>
                                    <option value="news">News</option>
                                    <option value="sports">Sports</option>
                                    <option value="business">Business</option>
                                    <option value="lifestyle">Lifestyle</option>
                                    <option value="technology">Technology</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">
                                    <i class="fas fa-flag"></i>
                                    Status
                                </label>
                                <select class="form-select" id="status" name="status">
                                    <option value="published">Publish Immediately</option>
                                    <option value="draft">Save as Draft</option>
                                </select>
                                <div class="form-text">
                                    Published editions are immediately visible to users
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pdf_file" class="form-label">
                            <i class="fas fa-file-pdf"></i>
                            PDF File *
                        </label>
                        <input type="file" class="form-control" id="pdf_file" name="pdf_file" 
                               accept=".pdf" required>
                        <div class="form-text">
                            Select a PDF file to upload (max 50MB)
                        </div>
                    </div>
                    
                    <!-- Upload Progress -->
                    <div id="uploadProgress" class="mb-3" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">Uploading...</span>
                            <span id="progressPercent" class="small">0%</span>
                        </div>
                        <div class="progress">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                 style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <i class="fas fa-info-circle"></i>
                            Supported format: PDF only
                        </div>
                        <div>
                            <a href="manage_editions.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-list"></i>
                                View Editions
                            </a>
                            <button type="submit" class="btn btn-admin-primary" id="submitBtn">
                                <i class="fas fa-upload"></i>
                                Upload Edition
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Upload Guidelines -->
<div class="row mt-4">
    <div class="col-lg-8 mx-auto">
        <div class="admin-card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-lightbulb"></i>
                    Upload Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-file-pdf fa-2x text-danger mb-2"></i>
                            <h6>PDF Format</h6>
                            <p class="small text-muted">
                                Only PDF files are supported. Ensure your file is properly formatted.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-weight-hanging fa-2x text-warning mb-2"></i>
                            <h6>File Size</h6>
                            <p class="small text-muted">
                                Maximum file size is 50MB. Compress large files if needed.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-image fa-2x text-info mb-2"></i>
                            <h6>Quality</h6>
                            <p class="small text-muted">
                                Use high-resolution PDFs for better reading experience.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom JavaScript -->
<?php 
$additionalJS = "
<script>
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const progressDiv = document.getElementById('uploadProgress');
    const fileInput = document.getElementById('pdf_file');
    
    if (fileInput.files.length === 0) {
        e.preventDefault();
        showToast('Please select a PDF file to upload', 'warning');
        return;
    }
    
    const file = fileInput.files[0];
    if (file.size > 50 * 1024 * 1024) { // 50MB
        e.preventDefault();
        showToast('File size must be less than 50MB', 'danger');
        return;
    }
    
    // Show upload progress
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class=\"spinner\"></span> Uploading...';
    progressDiv.style.display = 'block';
    
    // Simulate progress (in real implementation, use XMLHttpRequest for real progress)
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 20;
        if (progress > 90) progress = 90;
        
        document.getElementById('progressBar').style.width = progress + '%';
        document.getElementById('progressPercent').textContent = Math.round(progress) + '%';
        
        if (progress >= 90) {
            clearInterval(progressInterval);
        }
    }, 200);
});

// File input validation
document.getElementById('pdf_file').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        if (file.type !== 'application/pdf') {
            showToast('Only PDF files are allowed', 'danger');
            this.value = '';
            return;
        }
        
        if (file.size > 50 * 1024 * 1024) {
            showToast('File size must be less than 50MB', 'danger');
            this.value = '';
            return;
        }
        
        showToast('File selected: ' + file.name, 'success');
    }
});
</script>
";

// Include the admin layout footer
require_once 'includes/admin_layout_footer.php';
?>
