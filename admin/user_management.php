<?php
/**
 * User Management System
 * Advanced admin user management with roles and permissions
 */
session_start();
define('ADMIN_PAGE', true);

// Include configuration
require_once '../config/config.php';

// Authentication check
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

// Get database connection - ensure we get PDO
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Ensure admin_users table exists with proper structure
$tableCheckSql = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    role ENUM('super_admin', 'admin', 'editor', 'viewer') DEFAULT 'editor',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$conn->query($tableCheckSql);

// Add missing columns if they don't exist
$columnsToAdd = [
    'permissions' => 'ALTER TABLE admin_users ADD COLUMN permissions JSON',
    'avatar' => 'ALTER TABLE admin_users ADD COLUMN avatar VARCHAR(255)',
    'phone' => 'ALTER TABLE admin_users ADD COLUMN phone VARCHAR(20)',
    'department' => 'ALTER TABLE admin_users ADD COLUMN department VARCHAR(50)',
    'login_attempts' => 'ALTER TABLE admin_users ADD COLUMN login_attempts INT DEFAULT 0',
    'locked_until' => 'ALTER TABLE admin_users ADD COLUMN locked_until TIMESTAMP NULL',
    'created_by' => 'ALTER TABLE admin_users ADD COLUMN created_by INT'
];

// Check which columns exist - use PDO methods
$existingColumns = [];
try {
    $result = $conn->query("DESCRIBE admin_users");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }
} catch (Exception $e) {
    // Fallback if DESCRIBE doesn't work
    $existingColumns = ['id', 'username', 'password_hash', 'full_name', 'email', 'role', 'is_active', 'last_login', 'created_at', 'updated_at'];
}

// Add missing columns
foreach ($columnsToAdd as $column => $sql) {
    if (!in_array($column, $existingColumns)) {
        try {
            $conn->query($sql);
            $existingColumns[] = $column; // Add to list after successful creation
        } catch (Exception $e) {
            // Column might already exist or there's another issue, continue
        }
    }
}

// Initialize variables
$success = '';
$error = '';
$users = [];
$currentUser = $_SESSION['admin_user_id'] ?? 1;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_user':
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'editor';
            $department = trim($_POST['department'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            
            // Validation
            if (empty($username) || empty($email) || empty($password)) {
                $error = 'Username, email, and password are required.';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters long.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                // Check if username or email already exists
                $checkSql = "SELECT id FROM admin_users WHERE username = ? OR email = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute([$username, $email]);
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    $error = 'Username or email already exists.';
                } else {
                    // Build INSERT query dynamically based on existing columns
                    $insertColumns = ['username', 'email', 'password_hash', 'role'];
                    $insertValues = ['?', '?', '?', '?'];
                    $bindTypes = 'ssss';
                    $bindValues = [$username, $email, password_hash($password, PASSWORD_DEFAULT), $role];
                    
                    // Add optional columns if they exist
                    if (!empty($full_name)) {
                        $insertColumns[] = 'full_name';
                        $insertValues[] = '?';
                        $bindTypes .= 's';
                        $bindValues[] = $full_name;
                    }
                    
                    if (in_array('department', $existingColumns) && !empty($department)) {
                        $insertColumns[] = 'department';
                        $insertValues[] = '?';
                        $bindTypes .= 's';
                        $bindValues[] = $department;
                    }
                    
                    if (in_array('phone', $existingColumns) && !empty($phone)) {
                        $insertColumns[] = 'phone';
                        $insertValues[] = '?';
                        $bindTypes .= 's';
                        $bindValues[] = $phone;
                    }
                    
                    if (in_array('permissions', $existingColumns)) {
                        $permissions = [
                            'super_admin' => ['all'],
                            'admin' => ['manage_users', 'manage_content', 'view_analytics', 'system_settings'],
                            'editor' => ['manage_content', 'upload_editions'],
                            'viewer' => ['view_content']
                        ];
                        $userPermissions = json_encode($permissions[$role] ?? $permissions['viewer']);
                        $insertColumns[] = 'permissions';
                        $insertValues[] = '?';
                        $bindTypes .= 's';
                        $bindValues[] = $userPermissions;
                    }
                    
                    if (in_array('created_by', $existingColumns)) {
                        $insertColumns[] = 'created_by';
                        $insertValues[] = '?';
                        $bindTypes .= 'i';
                        $bindValues[] = $currentUser;
                    }
                    
                    $sql = "INSERT INTO admin_users (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt->execute($bindValues)) {
                        $success = 'User added successfully.';
                    } else {
                        $error = 'Error adding user: ' . implode(', ', $stmt->errorInfo());
                    }
                }
            }
            break;
            
        case 'edit_user':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $role = $_POST['role'] ?? 'editor';
            $department = trim($_POST['department'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $new_password = $_POST['new_password'] ?? '';
            
            if (empty($username) || empty($email)) {
                $error = 'Username and email are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                // Check if username or email already exists (excluding current user)
                $checkSql = "SELECT id FROM admin_users WHERE (username = ? OR email = ?) AND id != ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute([$username, $email, $user_id]);
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    $error = 'Username or email already exists.';
                } else {
                    // Build UPDATE query dynamically based on existing columns
                    $updateFields = ['username = ?', 'email = ?', 'role = ?'];
                    $bindTypes = 'sss';
                    $bindValues = [$username, $email, $role];
                    
                    // Add optional fields if they exist in the table
                    if (!empty($full_name)) {
                        $updateFields[] = 'full_name = ?';
                        $bindTypes .= 's';
                        $bindValues[] = $full_name;
                    }
                    
                    if (in_array('department', $existingColumns)) {
                        $updateFields[] = 'department = ?';
                        $bindTypes .= 's';
                        $bindValues[] = $department;
                    }
                    
                    if (in_array('phone', $existingColumns)) {
                        $updateFields[] = 'phone = ?';
                        $bindTypes .= 's';
                        $bindValues[] = $phone;
                    }
                    
                    if (in_array('is_active', $existingColumns)) {
                        $updateFields[] = 'is_active = ?';
                        $bindTypes .= 'i';
                        $bindValues[] = $is_active;
                    }
                    
                    if (in_array('permissions', $existingColumns)) {
                        $permissions = [
                            'super_admin' => ['all'],
                            'admin' => ['manage_users', 'manage_content', 'view_analytics', 'system_settings'],
                            'editor' => ['manage_content', 'upload_editions'],
                            'viewer' => ['view_content']
                        ];
                        $userPermissions = json_encode($permissions[$role] ?? $permissions['viewer']);
                        $updateFields[] = 'permissions = ?';
                        $bindTypes .= 's';
                        $bindValues[] = $userPermissions;
                    }
                    
                    // Handle password update
                    if (!empty($new_password)) {
                        if (strlen($new_password) < 8) {
                            $error = 'Password must be at least 8 characters long.';
                            break;
                        }
                        $updateFields[] = 'password_hash = ?';
                        $bindTypes .= 's';
                        $bindValues[] = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        // Reset login attempts if columns exist
                        if (in_array('login_attempts', $existingColumns)) {
                            $updateFields[] = 'login_attempts = 0';
                        }
                        if (in_array('locked_until', $existingColumns)) {
                            $updateFields[] = 'locked_until = NULL';
                        }
                    }
                    
                    // Add user ID for WHERE clause
                    $bindTypes .= 'i';
                    $bindValues[] = $user_id;
                    
                    $sql = "UPDATE admin_users SET " . implode(', ', $updateFields) . " WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt->execute($bindValues)) {
                        $success = 'User updated successfully.';
                    } else {
                        $error = 'Error updating user: ' . implode(', ', $stmt->errorInfo());
                    }
                }
            }
            break;
            
        case 'delete_user':
            $user_id = (int)($_POST['user_id'] ?? 0);
            
            if ($user_id == $currentUser) {
                $error = 'You cannot delete your own account.';
            } else {
                // Prevent deleting the last super admin
                $adminCountSql = "SELECT COUNT(*) as count FROM admin_users WHERE role = 'super_admin'";
                if (in_array('is_active', $existingColumns)) {
                    $adminCountSql .= " AND is_active = 1";
                }
                $adminCountResult = $conn->query($adminCountSql);
                $adminCount = $adminCountResult->fetch(PDO::FETCH_ASSOC)['count'];
                
                $userRoleSql = "SELECT role FROM admin_users WHERE id = ?";
                $userRoleStmt = $conn->prepare($userRoleSql);
                $userRoleStmt->execute([$user_id]);
                $userRole = $userRoleStmt->fetch(PDO::FETCH_ASSOC)['role'] ?? '';
                
                if ($userRole === 'super_admin' && $adminCount <= 1) {
                    $error = 'Cannot delete the last super admin user.';
                } else {
                    $sql = "DELETE FROM admin_users WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt->execute([$user_id])) {
                        $success = 'User deleted successfully.';
                    } else {
                        $errorInfo = $stmt->errorInfo();
                        $error = 'Error deleting user: ' . $errorInfo[2];
                    }
                }
            }
            break;
            
        case 'unlock_user':
            $user_id = (int)($_POST['user_id'] ?? 0);
            // Only update columns that exist
            $updateFields = [];
            if (in_array('login_attempts', $existingColumns)) {
                $updateFields[] = 'login_attempts = 0';
            }
            if (in_array('locked_until', $existingColumns)) {
                $updateFields[] = 'locked_until = NULL';
            }
            
            if (!empty($updateFields)) {
                $sql = "UPDATE admin_users SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $stmt = $conn->prepare($sql);
                
                if ($stmt->execute([$user_id])) {
                    $success = 'User account unlocked successfully.';
                } else {
                    $error = 'Error unlocking user account.';
                }
            } else {
                $success = 'User account status updated.';
            }
            break;
    }
}

// Get all users with detailed information - handle missing columns gracefully
$baseColumns = "id, username, email, full_name, role, is_active, last_login, created_at";
$optionalColumns = [];

// Check for optional columns that might not exist
$optionalColumnChecks = ['department', 'phone', 'login_attempts', 'locked_until', 'created_by'];

foreach ($optionalColumnChecks as $column) {
    if (in_array($column, $existingColumns)) {
        $optionalColumns[] = $column;
    }
}

$allColumns = $baseColumns;
if (!empty($optionalColumns)) {
    $allColumns .= ', ' . implode(', ', $optionalColumns);
}

// Add created_by_name subquery only if created_by column exists
$createdBySubquery = '';
if (in_array('created_by', $existingColumns)) {
    $createdBySubquery = ', (SELECT username FROM admin_users au WHERE au.id = admin_users.created_by) as created_by_name';
}

$sql = "SELECT {$allColumns}{$createdBySubquery} FROM admin_users ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        // Set default values for missing columns
        $row['department'] = $row['department'] ?? '';
        $row['phone'] = $row['phone'] ?? '';
        $row['login_attempts'] = $row['login_attempts'] ?? 0;
        $row['locked_until'] = $row['locked_until'] ?? null;
        $row['created_by_name'] = $row['created_by_name'] ?? '';
        $users[] = $row;
    }
}

// Page configuration
$pageTitle = 'User Management System';
$pageSubtitle = 'Advanced admin user management with roles and permissions';
$currentPage = 'user_management';
$pageIcon = 'fas fa-users-cog';

// Include shared layout header
include 'includes/admin_layout.php';
?>

<div class="row">
    <div class="col-12">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- User Statistics Cards -->
<div class="row mb-4">
    <?php
    $totalUsers = count($users);
    $activeUsers = array_filter($users, fn($u) => $u['is_active']);
    $lockedUsers = array_filter($users, fn($u) => $u['locked_until'] && strtotime($u['locked_until']) > time());
    $adminUsers = array_filter($users, fn($u) => in_array($u['role'], ['super_admin', 'admin']));
    ?>
    
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Users</h5>
                        <h2><?php echo $totalUsers; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Active Users</h5>
                        <h2><?php echo count($activeUsers); ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Locked Users</h5>
                        <h2><?php echo count($lockedUsers); ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-lock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Administrators</h5>
                        <h2><?php echo count($adminUsers); ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-shield fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users-cog me-2 text-primary"></i>Admin User Management
                </h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus me-2"></i>Add New User
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No users found. Add your first admin user to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Contact</th>
                                    <th>Role & Status</th>
                                    <th>Department</th>
                                    <th>Last Login</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr class="<?php echo !$user['is_active'] ? 'table-secondary' : ''; ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3">
                                                    <i class="fas fa-user-circle fa-2x text-secondary"></i>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    <?php if ($user['full_name']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($user['full_name']); ?></small>
                                                    <?php endif; ?>
                                                    <?php if ($user['id'] == $currentUser): ?>
                                                        <span class="badge bg-primary ms-2">You</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($user['email']); ?></div>
                                            <?php if ($user['phone']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($user['phone']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="badge <?php 
                                                    echo match($user['role']) {
                                                        'super_admin' => 'bg-danger',
                                                        'admin' => 'bg-warning',
                                                        'editor' => 'bg-primary',
                                                        'viewer' => 'bg-secondary',
                                                        default => 'bg-secondary'
                                                    };
                                                ?>">
                                                    <i class="fas <?php 
                                                        echo match($user['role']) {
                                                            'super_admin' => 'fa-crown',
                                                            'admin' => 'fa-user-shield',
                                                            'editor' => 'fa-edit',
                                                            'viewer' => 'fa-eye',
                                                            default => 'fa-user'
                                                        };
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                                </span>
                                            </div>
                                            <div class="mt-1">
                                                <?php if (!$user['is_active']): ?>
                                                    <span class="badge bg-secondary"><i class="fas fa-ban me-1"></i>Inactive</span>
                                                <?php elseif ($user['locked_until'] && strtotime($user['locked_until']) > time()): ?>
                                                    <span class="badge bg-danger"><i class="fas fa-lock me-1"></i>Locked</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Active</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo $user['department'] ? htmlspecialchars($user['department']) : '<span class="text-muted">-</span>'; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['last_login']): ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">Never</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                <?php if ($user['created_by_name']): ?>
                                                    <br>by <?php echo htmlspecialchars($user['created_by_name']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                        title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($user['locked_until'] && strtotime($user['locked_until']) > time()): ?>
                                                    <button type="button" class="btn btn-outline-warning" 
                                                            onclick="unlockUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                                            title="Unlock User">
                                                        <i class="fas fa-unlock"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($user['id'] != $currentUser): ?>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                                            title="Delete User">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Add New User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="add_username" name="username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="add_email" name="email" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="add_full_name" name="full_name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="add_phone" name="phone">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="add_department" name="department" placeholder="e.g. Editorial, IT, Management">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_role" class="form-label">Role</label>
                                <select class="form-select" id="add_role" name="role">
                                    <option value="viewer">Viewer - View content only</option>
                                    <option value="editor" selected>Editor - Manage content</option>
                                    <option value="admin">Admin - Full access except user management</option>
                                    <option value="super_admin">Super Admin - Full system access</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="add_password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="add_password" name="password" required minlength="8">
                        <small class="form-text text-muted">Minimum 8 characters</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>Edit User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="edit_username" name="username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="edit_full_name" name="full_name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="edit_phone" name="phone">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="edit_department" name="department">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_role" class="form-label">Role</label>
                                <select class="form-select" id="edit_role" name="role">
                                    <option value="viewer">Viewer - View content only</option>
                                    <option value="editor">Editor - Manage content</option>
                                    <option value="admin">Admin - Full access except user management</option>
                                    <option value="super_admin">Super Admin - Full system access</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="edit_new_password" name="new_password" minlength="8">
                                <small class="form-text text-muted">Leave empty to keep current password</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" checked>
                                    <label class="form-check-label" for="edit_is_active">
                                        Active User
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="delete_user_id">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the user <strong id="delete_username"></strong>?</p>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. The user will no longer be able to access the admin panel.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Unlock User Modal -->
<div class="modal fade" id="unlockUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="unlock_user">
                <input type="hidden" name="user_id" id="unlock_user_id">
                <div class="modal-header">
                    <h5 class="modal-title text-warning">
                        <i class="fas fa-unlock me-2"></i>Unlock User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Unlock the user account for <strong id="unlock_username"></strong>?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This will reset the login attempts and remove the account lock.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-unlock me-2"></i>Unlock User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_full_name').value = user.full_name || '';
    document.getElementById('edit_phone').value = user.phone || '';
    document.getElementById('edit_department').value = user.department || '';
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_is_active').checked = user.is_active == 1;
    
    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    editModal.show();
}

function deleteUser(userId, username) {
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('delete_username').textContent = username;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    deleteModal.show();
}

function unlockUser(userId, username) {
    document.getElementById('unlock_user_id').value = userId;
    document.getElementById('unlock_username').textContent = username;
    
    const unlockModal = new bootstrap.Modal(document.getElementById('unlockUserModal'));
    unlockModal.show();
}
</script>

<?php include 'includes/admin_layout_footer.php'; ?>
