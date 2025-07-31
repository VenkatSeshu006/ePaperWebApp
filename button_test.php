<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Button Test - E-Paper CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-btn {
            margin: 10px;
            padding: 10px 20px;
        }
        .console-output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>🔧 Button Test Page</h1>
        <p class="lead">Testing if buttons are clickable and functions are working</p>
        
        <div class="row">
            <div class="col-md-6">
                <h3>Test Buttons</h3>
                <button class="btn btn-primary test-btn clip-button" onclick="testClipFunction()">
                    <i class="fas fa-cut"></i> Test Clip Tool
                </button>
                <button class="btn btn-success test-btn share-button" onclick="testShareFunction()">
                    <i class="fas fa-share"></i> Test Share Tool
                </button>
                <button class="btn btn-info test-btn" onclick="testNotification()">
                    <i class="fas fa-bell"></i> Test Notification
                </button>
                <button class="btn btn-warning test-btn" onclick="checkEditionData()">
                    <i class="fas fa-database"></i> Check Edition Data
                </button>
            </div>
            <div class="col-md-6">
                <h3>Console Output</h3>
                <div id="console" class="console-output">
                    Console output will appear here...
                </div>
                <button class="btn btn-secondary btn-sm" onclick="clearConsole()">Clear Console</button>
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-12">
                <h3>Navigation Links</h3>
                <a href="index.php" class="btn btn-primary me-2">
                    <i class="fas fa-newspaper"></i> Homepage
                </a>
                <a href="clips.php" class="btn btn-success me-2">
                    <i class="fas fa-cut"></i> Clips Management
                </a>
                <a href="final_test.php" class="btn btn-info">
                    <i class="fas fa-clipboard-check"></i> System Test
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Mock edition data for testing
        window.editionData = {
            imagePaths: [
                'uploads/2025-07-29/pages/page_001.png',
                'uploads/2025-07-29/pages/page_002.png',
                'uploads/2025-07-29/pages/page_003.png'
            ],
            editionId: 1,
            imageIds: [1, 2, 3],
            editionTitle: 'Test Edition',
            baseUrl: 'http://localhost/epaper-site',
            totalPages: 3,
            currentUrl: 'http://localhost/epaper-site/index.php?id=1&page=1'
        };

        function log(message) {
            const console = document.getElementById('console');
            const timestamp = new Date().toLocaleTimeString();
            console.textContent += `[${timestamp}] ${message}\n`;
            console.scrollTop = console.scrollHeight;
        }

        function clearConsole() {
            document.getElementById('console').textContent = '';
        }

        function testClipFunction() {
            log('🎯 Testing Clip Function...');
            try {
                log('✅ Clip button clicked successfully');
                log('Edition data available: ' + (!!window.editionData));
                log('Total pages: ' + (window.editionData ? window.editionData.totalPages : 'N/A'));
                
                // Simulate clip tool activation
                alert('✅ Clip tool would activate here!\n\nIn the main homepage:\n1. Click on a newspaper page\n2. Then click the clip button\n3. Draw a selection area');
                log('✅ Clip tool test completed');
            } catch (error) {
                log('❌ Error in clip function: ' + error.message);
            }
        }

        function testShareFunction() {
            log('📢 Testing Share Function...');
            try {
                log('✅ Share button clicked successfully');
                
                // Simulate share modal
                alert('✅ Share modal would open here!\n\nFeatures:\n- 9 social media platforms\n- QR code generation\n- Direct link copying\n- Email sharing');
                log('✅ Share function test completed');
            } catch (error) {
                log('❌ Error in share function: ' + error.message);
            }
        }

        function testNotification() {
            log('🔔 Testing Notification System...');
            try {
                showNotification('✅ Test notification working!', 'success');
                log('✅ Notification test completed');
            } catch (error) {
                log('❌ Notification system not available: ' + error.message);
                alert('ℹ️ Notification system test - This would show a toast notification in the main app');
            }
        }

        function checkEditionData() {
            log('📊 Checking Edition Data...');
            log('Edition Data Object: ' + JSON.stringify(window.editionData, null, 2));
            log('Data check completed');
        }

        // Simple notification fallback
        function showNotification(message, type = 'info') {
            const alertClass = type === 'success' ? 'alert-success' : 
                              type === 'error' ? 'alert-danger' : 'alert-info';
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 3000);
        }

        // Initial log
        log('🚀 Button test page loaded');
        log('Edition data initialized: ' + (!!window.editionData));
    </script>
</body>
</html>
