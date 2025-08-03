<?php
/**
 * User Management Page
 * Manage admin users and permissions
 */
session_start();
define('ADMIN_PAGE', true);

// Include configuration first
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

// Get database connection
$conn = getConnection();

// Ensure admin_users table exists
$tableCheckSql = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    email VARCHAR(100),
    role ENUM('admin', 'editor') DEFAULT 'admin',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$conn->query($tableCheckSql);

// Initialize variables
$success = '';
$error = '';
$users = [];

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
            
            // Validation
            if (empty($username) || empty($email) || empty($password)) {
                $error = 'Username, email, and password are required.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long.';
            } else {
                // Check if username or email already exists
                $checkSql = "SELECT id FROM admin_users WHERE username = ? OR email = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute([$username, $email]);
                $existing = $checkStmt->fetch();
                
                if ($existing) {
                    $error = 'Username or email already exists.';
                } else {
                    // Add new user
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO admin_users (username, email, full_name, password_hash, role) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt->execute([$username, $email, $full_name, $password_hash, $role])) {
                        $success = 'User added successfully.';
                    } else {
                        $errorInfo = $stmt->errorInfo();
                        $error = 'Error adding user: ' . $errorInfo[2];
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
            $new_password = $_POST['new_password'] ?? '';
            
            if (empty($username) || empty($email)) {
                $error = 'Username and email are required.';
            } else {
                // Check if username or email already exists (excluding current user)
                $checkSql = "SELECT id FROM admin_users WHERE (username = ? OR email = ?) AND id != ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute([$username, $email, $user_id]);
                $existing = $checkStmt->fetch();
                
                if ($existing) {
                    $error = 'Username or email already exists.';
                } else {
                    // Update user
                    if (!empty($new_password)) {
                        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $sql = "UPDATE admin_users SET username = ?, email = ?, full_name = ?, role = ?, password_hash = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $executeParams = [$username, $email, $full_name, $role, $password_hash, $user_id];
                    } else {
                        $sql = "UPDATE admin_users SET username = ?, email = ?, full_name = ?, role = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $executeParams = [$username, $email, $full_name, $role, $user_id];
                    }
                    
                    if ($stmt->execute($executeParams)) {
                        $success = 'User updated successfully.';
                    } else {
                        $errorInfo = $stmt->errorInfo();
                        $error = 'Error updating user: ' . $errorInfo[2];
                    }
                }
            }
            break;
            
        case 'delete_user':
            $user_id = (int)($_POST['user_id'] ?? 0);
            
            // Prevent deleting the last admin user
            $adminCountSql = "SELECT COUNT(*) as count FROM admin_users WHERE role = 'admin'";
            $adminCountResult = $conn->query($adminCountSql);
            $adminCount = $adminCountResult->fetch()['count'];
            
            $userRoleSql = "SELECT role FROM admin_users WHERE id = ?";
            $userRoleStmt = $conn->prepare($userRoleSql);
            $userRoleStmt->execute([$user_id]);
            $userRole = $userRoleStmt->fetch()['role'] ?? '';
            
            if ($userRole === 'admin' && $adminCount <= 1) {
                $error = 'Cannot delete the last admin user.';
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
            break;
    }
}

// Get all users
$sql = "SELECT id, username, email, full_name, role, last_login, created_at FROM admin_users ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch()) {
        $users[] = $row;
    }
}

// Page configuration for shared layout
$pageTitle = 'User Management';
$pageSubtitle = 'Manage admin users and permissions';
$currentPage = 'users';
$pageIcon = 'fas fa-users';

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

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users me-2 text-primary"></i>Admin Users
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
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Last Login</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
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
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                                <i class="fas <?php echo $user['role'] === 'admin' ? 'fa-crown' : 'fa-edit'; ?> me-1"></i>
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
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
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                        title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                                        title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
    <div class="modal-dialog">
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
                    <div class="mb-3">
                        <label for="add_username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="add_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="add_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="add_full_name" name="full_name">
                    </div>
                    <div class="mb-3">
                        <label for="add_password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="add_password" name="password" required minlength="6">
                        <small class="form-text text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="mb-3">
                        <label for="add_role" class="form-label">Role</label>
                        <select class="form-select" id="add_role" name="role">
                            <option value="editor">Editor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
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
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name">
                    </div>
                    <div class="mb-3">
                        <label for="edit_new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="edit_new_password" name="new_password" minlength="6">
                        <small class="form-text text-muted">Leave empty to keep current password</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select class="form-select" id="edit_role" name="role">
                            <option value="editor">Editor</option>
                            <option value="admin">Admin</option>
                        </select>
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
                    <div class="alert alert-warning">
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

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_full_name').value = user.full_name || '';
    document.getElementById('edit_role').value = user.role;
    
    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    editModal.show();
}

function deleteUser(userId, username) {
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('delete_username').textContent = username;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    deleteModal.show();
}
</script>

<?php include 'includes/admin_layout_footer.php'; ?>
