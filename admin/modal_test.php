<?php
/**
 * Simple test page for modal functionality
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Category Modal Test</h2>
        
        <!-- Test Bootstrap Modal Functionality -->
        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#testModal">
            Bootstrap Data Attributes Test
        </button>
        
        <button type="button" class="btn btn-success me-2" onclick="showModalJS()">
            JavaScript Test
        </button>
        
        <button type="button" class="btn btn-info" onclick="checkBootstrap()">
            Check Bootstrap
        </button>
        
        <!-- Test Category Form -->
        <div class="mt-4">
            <h4>Test Category Creation</h4>
            <form method="POST" action="categories.php" class="border p-3">
                <input type="hidden" name="action" value="create">
                <div class="mb-3">
                    <label>Name:</label>
                    <input type="text" name="name" class="form-control" value="Test Category" required>
                </div>
                <div class="mb-3">
                    <label>Description:</label>
                    <textarea name="description" class="form-control">Test description</textarea>
                </div>
                <div class="mb-3">
                    <label>Color:</label>
                    <input type="color" name="color" class="form-control" value="#ff5722">
                </div>
                <button type="submit" class="btn btn-primary">Submit Direct Form</button>
            </form>
        </div>
        
        <!-- Modal -->
        <div class="modal fade" id="testModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Test Modal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>If you can see this, the modal is working!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showModalJS() {
            const modal = new bootstrap.Modal(document.getElementById('testModal'));
            modal.show();
        }
        
        function checkBootstrap() {
            alert('Bootstrap loaded: ' + (typeof bootstrap !== 'undefined') + 
                  '\nModal available: ' + (typeof bootstrap.Modal !== 'undefined'));
        }
    </script>
</body>
</html>
