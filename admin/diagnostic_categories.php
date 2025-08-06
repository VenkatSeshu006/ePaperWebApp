<?php
/**
 * Categories Admin Diagnostic Tool
 * Test all aspects of the categories admin functionality
 */

session_start();
$_SESSION['admin_logged_in'] = true; // Simulate admin login

// Set up error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';
require_once '../includes/database.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Admin Diagnostic</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .diagnostic-section {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .test-result {
            padding: 0.5rem;
            margin: 0.25rem 0;
            border-radius: 0.25rem;
        }
        .test-success {
            background-color: #d1edff;
            border-left: 4px solid #0d6efd;
        }
        .test-error {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .test-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-3">
        <h1><i class="fas fa-tools"></i> Categories Admin Diagnostic Tool</h1>
        <p class="text-muted">Testing all aspects of the categories admin functionality</p>
        
        <div class="row">
            <!-- Database Tests -->
            <div class="col-lg-6">
                <div class="diagnostic-section">
                    <h3><i class="fas fa-database"></i> Database Tests</h3>
                    <div id="databaseTests">
                        <!-- Database tests will be populated here -->
                    </div>
                </div>
                
                <!-- Frontend Tests -->
                <div class="diagnostic-section">
                    <h3><i class="fas fa-code"></i> Frontend Tests</h3>
                    <div id="frontendTests">
                        <!-- Frontend tests will be populated here -->
                    </div>
                </div>
            </div>
            
            <!-- Live Category Management -->
            <div class="col-lg-6">
                <div class="diagnostic-section">
                    <h3><i class="fas fa-cogs"></i> Live Category Management Test</h3>
                    
                    <!-- Add Category Test -->
                    <div class="mb-3">
                        <h5>Add Category Test</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testCategoryModal">
                            <i class="fas fa-plus"></i> Test Add Category
                        </button>
                        <button type="button" class="btn btn-info" onclick="testModalJS()">
                            <i class="fas fa-code"></i> Test JS Modal
                        </button>
                    </div>
                    
                    <!-- Current Categories -->
                    <div class="mb-3">
                        <h5>Current Categories</h5>
                        <div id="currentCategories">
                            Loading categories...
                        </div>
                    </div>
                </div>
                
                <!-- Test Results -->
                <div class="diagnostic-section">
                    <h3><i class="fas fa-clipboard-check"></i> Test Results</h3>
                    <div id="testResults">
                        <div class="test-result test-warning">
                            <i class="fas fa-clock"></i> Tests not started yet. Click "Run All Tests" below.
                        </div>
                    </div>
                    <button type="button" class="btn btn-success" onclick="runAllTests()">
                        <i class="fas fa-play"></i> Run All Tests
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Action Logs -->
        <div class="mt-4">
            <div class="diagnostic-section">
                <h3><i class="fas fa-terminal"></i> Action Logs</h3>
                <div id="actionLogs" style="max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 1rem; font-family: monospace;">
                    <div>System initialized...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Category Modal -->
    <div class="modal fade" id="testCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Test Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="testCategoryForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="testCategoryName" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="testCategoryName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="testCategoryDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="testCategoryDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="testCategoryColor" class="form-label">Color</label>
                            <input type="color" class="form-control form-control-color" id="testCategoryColor" name="color" value="#007bff">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Test Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let testResults = [];
        
        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const logElement = document.getElementById('actionLogs');
            const entry = document.createElement('div');
            entry.innerHTML = `[${timestamp}] ${message}`;
            logElement.appendChild(entry);
            logElement.scrollTop = logElement.scrollHeight;
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
        
        function addTestResult(test, result, details = '') {
            testResults.push({test, result, details});
            updateTestResults();
        }
        
        function updateTestResults() {
            const container = document.getElementById('testResults');
            container.innerHTML = '';
            
            testResults.forEach(function(test) {
                const div = document.createElement('div');
                div.className = `test-result ${test.result ? 'test-success' : 'test-error'}`;
                div.innerHTML = `
                    <i class="fas fa-${test.result ? 'check' : 'times'}"></i>
                    <strong>${test.test}</strong>: ${test.result ? 'PASSED' : 'FAILED'}
                    ${test.details ? `<br><small>${test.details}</small>` : ''}
                `;
                container.appendChild(div);
            });
        }
        
        // Test 1: Bootstrap and Dependencies
        function testDependencies() {
            log('Testing dependencies...');
            
            // Test Bootstrap
            if (typeof bootstrap !== 'undefined') {
                addTestResult('Bootstrap JS', true, `Version: ${bootstrap.Modal.VERSION || 'Unknown'}`);
                log('✅ Bootstrap JS loaded successfully');
            } else {
                addTestResult('Bootstrap JS', false, 'Bootstrap not found');
                log('❌ Bootstrap JS not loaded');
                return false;
            }
            
            // Test jQuery (if present)
            if (typeof $ !== 'undefined') {
                addTestResult('jQuery', true, `Version: ${$.fn.jquery || 'Unknown'}`);
                log('✅ jQuery loaded (optional)');
            } else {
                addTestResult('jQuery', true, 'Not loaded (not required for Bootstrap 5)');
                log('ℹ️ jQuery not loaded (not required)');
            }
            
            return true;
        }
        
        // Test 2: Modal Functionality
        function testModals() {
            log('Testing modal functionality...');
            
            try {
                const modalElement = document.getElementById('testCategoryModal');
                if (!modalElement) {
                    addTestResult('Modal Element', false, 'Modal element not found');
                    return false;
                }
                
                addTestResult('Modal Element', true, 'Modal element found');
                
                // Test modal creation
                const modal = new bootstrap.Modal(modalElement);
                addTestResult('Modal Creation', true, 'Modal object created successfully');
                
                // Test modal show/hide
                modal.show();
                setTimeout(function() {
                    modal.hide();
                    addTestResult('Modal Show/Hide', true, 'Modal opened and closed successfully');
                    log('✅ Modal show/hide test completed');
                }, 1000);
                
                return true;
            } catch (error) {
                addTestResult('Modal Functionality', false, error.message);
                log('❌ Modal test failed: ' + error.message);
                return false;
            }
        }
        
        // Test 3: Database Operations
        function testDatabase() {
            log('Testing database operations...');
            
            // Test category creation via AJAX
            const testData = {
                action: 'create',
                name: 'Test Category ' + Date.now(),
                description: 'Diagnostic test category',
                color: '#ff0000'
            };
            
            fetch('categories.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(testData)
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes('successfully')) {
                    addTestResult('Database CREATE', true, 'Category created successfully');
                    log('✅ Database CREATE test passed');
                } else {
                    addTestResult('Database CREATE', false, 'Unexpected response');
                    log('❌ Database CREATE test failed');
                }
            })
            .catch(error => {
                addTestResult('Database CREATE', false, error.message);
                log('❌ Database CREATE test error: ' + error.message);
            });
        }
        
        // Test 4: Form Submission
        function testFormSubmission() {
            log('Testing form submission...');
            
            const form = document.getElementById('testCategoryForm');
            if (!form) {
                addTestResult('Form Element', false, 'Form not found');
                return false;
            }
            
            addTestResult('Form Element', true, 'Form found');
            
            // Test form data collection
            const formData = new FormData(form);
            const dataObj = Object.fromEntries(formData);
            
            if (Object.keys(dataObj).length > 0) {
                addTestResult('Form Data', true, `Fields: ${Object.keys(dataObj).join(', ')}`);
                log('✅ Form data collection test passed');
            } else {
                addTestResult('Form Data', false, 'No form data collected');
                log('❌ Form data collection test failed');
            }
        }
        
        // Load current categories
        function loadCurrentCategories() {
            log('Loading current categories...');
            
            fetch('categories.php')
            .then(response => response.text())
            .then(html => {
                // Parse the HTML to extract category information
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const categoryRows = doc.querySelectorAll('tbody tr');
                
                const container = document.getElementById('currentCategories');
                if (categoryRows.length > 0) {
                    let categoriesHTML = '<div class="list-group">';
                    categoryRows.forEach(function(row) {
                        const cells = row.querySelectorAll('td');
                        if (cells.length >= 2) {
                            const name = cells[0].textContent.trim();
                            const slug = cells[1].textContent.trim();
                            categoriesHTML += `<div class="list-group-item">${name} <code>${slug}</code></div>`;
                        }
                    });
                    categoriesHTML += '</div>';
                    container.innerHTML = categoriesHTML;
                } else {
                    container.innerHTML = '<div class="alert alert-info">No categories found</div>';
                }
            })
            .catch(error => {
                document.getElementById('currentCategories').innerHTML = 
                    '<div class="alert alert-danger">Error loading categories: ' + error.message + '</div>';
            });
        }
        
        // Test modal via JavaScript
        function testModalJS() {
            log('Testing modal via JavaScript...');
            
            try {
                const modal = new bootstrap.Modal(document.getElementById('testCategoryModal'));
                modal.show();
                log('✅ JavaScript modal test successful');
            } catch (error) {
                log('❌ JavaScript modal test failed: ' + error.message);
                alert('JavaScript modal test failed: ' + error.message);
            }
        }
        
        // Run all tests
        function runAllTests() {
            log('Starting comprehensive test suite...');
            testResults = [];
            
            setTimeout(() => testDependencies(), 100);
            setTimeout(() => testModals(), 500);
            setTimeout(() => testFormSubmission(), 1000);
            setTimeout(() => testDatabase(), 1500);
            
            setTimeout(() => {
                log('All tests completed!');
                const passed = testResults.filter(t => t.result).length;
                const total = testResults.length;
                log(`Test Summary: ${passed}/${total} tests passed`);
            }, 3000);
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            log('Diagnostic tool initialized');
            loadCurrentCategories();
            
            // Set up form submission test
            document.getElementById('testCategoryForm').addEventListener('submit', function(e) {
                e.preventDefault();
                log('Test form submitted');
                testFormSubmission();
            });
        });
    </script>
</body>
</html>
