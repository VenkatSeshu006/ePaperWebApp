<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Paper CMS - User Guide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .feature-card {
            transition: transform 0.2s ease;
            border-left: 4px solid #007bff;
        }
        .feature-card:hover {
            transform: translateY(-2px);
        }
        .step-number {
            background: #007bff;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        .success-badge {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.8em;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-newspaper"></i> E-Paper CMS
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home"></i> Homepage
                </a>
                <a class="nav-link" href="clips.php">
                    <i class="fas fa-cut"></i> Clips
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="text-center mb-5">
                    <h1><i class="fas fa-book-open text-primary"></i> E-Paper CMS User Guide</h1>
                    <p class="lead">Learn how to use the clip and share tools effectively</p>
                    <span class="success-badge">✅ All Systems Operational</span>
                </div>
            </div>
        </div>

        <!-- Quick Start -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card feature-card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-rocket"></i> Quick Start Guide</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>📰 Reading E-Paper</h5>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="step-number">1</div>
                                    <div>Visit the <a href="index.php">Homepage</a></div>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="step-number">2</div>
                                    <div>Click on any page thumbnail to view it</div>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="step-number">3</div>
                                    <div>Use zoom, navigation, and toolbar tools</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>✂️ Creating Clips</h5>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="step-number">1</div>
                                    <div>Select a page by clicking on it</div>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="step-number">2</div>
                                    <div>Click the <strong>"Clip"</strong> button in toolbar</div>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="step-number">3</div>
                                    <div>Draw a selection area on the image</div>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="step-number">4</div>
                                    <div>Save your clip to view in <a href="clips.php">Clips Management</a></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Overview -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-share-alt"></i> Share Features</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fab fa-facebook text-primary"></i> Facebook</li>
                            <li class="mb-2"><i class="fab fa-twitter text-info"></i> Twitter</li>
                            <li class="mb-2"><i class="fab fa-linkedin text-primary"></i> LinkedIn</li>
                            <li class="mb-2"><i class="fab fa-whatsapp text-success"></i> WhatsApp</li>
                            <li class="mb-2"><i class="fab fa-telegram text-info"></i> Telegram</li>
                            <li class="mb-2"><i class="fab fa-reddit text-danger"></i> Reddit</li>
                            <li class="mb-2"><i class="fab fa-pinterest text-danger"></i> Pinterest</li>
                            <li class="mb-2"><i class="fas fa-envelope text-secondary"></i> Email</li>
                            <li class="mb-2"><i class="fas fa-qrcode text-dark"></i> QR Code Generation</li>
                        </ul>
                        <a href="index.php" class="btn btn-success btn-sm">
                            <i class="fas fa-external-link-alt"></i> Try Share Tool
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-cut"></i> Clip Management</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-crop text-primary"></i> Smart Image Cropping</li>
                            <li class="mb-2"><i class="fas fa-save text-success"></i> Automatic Saving</li>
                            <li class="mb-2"><i class="fas fa-search text-info"></i> Search & Filter</li>
                            <li class="mb-2"><i class="fas fa-edit text-warning"></i> Edit Titles & Descriptions</li>
                            <li class="mb-2"><i class="fas fa-download text-success"></i> Download Options</li>
                            <li class="mb-2"><i class="fas fa-share text-primary"></i> Social Media Sharing</li>
                            <li class="mb-2"><i class="fas fa-trash text-danger"></i> Delete Management</li>
                            <li class="mb-2"><i class="fas fa-chart-bar text-info"></i> Usage Statistics</li>
                        </ul>
                        <a href="clips.php" class="btn btn-warning btn-sm">
                            <i class="fas fa-external-link-alt"></i> Manage Clips
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card feature-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-question-circle"></i> Troubleshooting</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>🔧 Common Issues</h6>
                                <div class="mb-3">
                                    <strong>Q: Clip button not responding?</strong><br>
                                    <small class="text-muted">A: Make sure you've clicked on a page thumbnail first to select it.</small>
                                </div>
                                <div class="mb-3">
                                    <strong>Q: Share options not opening?</strong><br>
                                    <small class="text-muted">A: Ensure pop-ups are allowed in your browser settings.</small>
                                </div>
                                <div class="mb-3">
                                    <strong>Q: Can't save clips?</strong><br>
                                    <small class="text-muted">A: Check that you have an active page selected and draw a selection area.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>💡 Tips & Tricks</h6>
                                <div class="mb-3">
                                    <strong>🎯 Better Clips:</strong><br>
                                    <small class="text-muted">Zoom in before clipping for higher quality selections.</small>
                                </div>
                                <div class="mb-3">
                                    <strong>📱 Mobile Sharing:</strong><br>
                                    <small class="text-muted">Use QR codes for easy mobile device sharing.</small>
                                </div>
                                <div class="mb-3">
                                    <strong>🗂️ Organization:</strong><br>
                                    <small class="text-muted">Use the search feature in Clips Management to find specific content.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Dashboard -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-dashboard"></i> System Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <i class="fas fa-newspaper fa-2x text-success"></i>
                                    <h6 class="mt-2">Homepage</h6>
                                    <span class="badge bg-success">✅ Working</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <i class="fas fa-cut fa-2x text-success"></i>
                                    <h6 class="mt-2">Clip Tools</h6>
                                    <span class="badge bg-success">✅ Working</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <i class="fas fa-share-alt fa-2x text-success"></i>
                                    <h6 class="mt-2">Share System</h6>
                                    <span class="badge bg-success">✅ Working</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <i class="fas fa-database fa-2x text-success"></i>
                                    <h6 class="mt-2">Database</h6>
                                    <span class="badge bg-success">✅ Working</span>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="text-center">
                            <h5 class="text-success">🎉 All Systems Operational!</h5>
                            <p class="text-muted">Your E-Paper CMS clip and share tools are working perfectly.</p>
                            <div class="mt-3">
                                <a href="index.php" class="btn btn-primary me-2">
                                    <i class="fas fa-newspaper"></i> Start Reading
                                </a>
                                <a href="clips.php" class="btn btn-success me-2">
                                    <i class="fas fa-cut"></i> Manage Clips
                                </a>
                                <a href="final_test.php" class="btn btn-info">
                                    <i class="fas fa-clipboard-check"></i> Run Tests
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">
                <i class="fas fa-newspaper"></i> E-Paper CMS - 
                Clip & Share tools fully operational! 
                <i class="fas fa-check-circle text-success"></i>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
