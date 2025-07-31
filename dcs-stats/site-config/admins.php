<?php
/**
 * Admin Management Interface
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';

// Require admin login and permission
requireAdmin();
requirePermission('manage_admins');

// Get current admin
$currentAdmin = getCurrentAdmin();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = ERROR_MESSAGES['csrf_invalid'];
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add_admin':
                // Validate inputs
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = intval($_POST['role'] ?? ROLE_LSO);
                
                if (empty($username) || empty($email) || empty($password)) {
                    $message = 'All fields are required';
                    $messageType = 'error';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Invalid email address';
                    $messageType = 'error';
                } elseif (strlen($password) < 8) {
                    $message = 'Password must be at least 8 characters';
                    $messageType = 'error';
                } else {
                    // Check if username or email already exists
                    $users = getAdminUsers();
                    $exists = false;
                    
                    foreach ($users as $user) {
                        if ($user['username'] === $username || $user['email'] === $email) {
                            $exists = true;
                            break;
                        }
                    }
                    
                    if ($exists) {
                        $message = 'Username or email already exists';
                        $messageType = 'error';
                    } else {
                        // Create new admin
                        $newAdmin = [
                            'id' => count($users) + 1,
                            'username' => $username,
                            'email' => $email,
                            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                            'role' => $role,
                            'created_at' => date(DATE_FORMAT),
                            'last_login' => null,
                            'is_active' => true,
                            'failed_attempts' => 0,
                            'locked_until' => null
                        ];
                        
                        $users[] = $newAdmin;
                        saveAdminUsers($users);
                        
                        logAdminActivity('ADMIN_CREATE', $_SESSION['admin_id'], 'admin', $username);
                        
                        $message = SUCCESS_MESSAGES['admin_created'];
                        $messageType = 'success';
                    }
                }
                break;
                
            case 'remove_admin':
                $adminId = intval($_POST['admin_id'] ?? 0);
                
                // Can't remove yourself
                if ($adminId == $_SESSION['admin_id']) {
                    $message = 'You cannot remove your own account';
                    $messageType = 'error';
                } else {
                    $users = getAdminUsers();
                    $newUsers = [];
                    $removed = false;
                    
                    foreach ($users as $user) {
                        if ($user['id'] == $adminId) {
                            $removed = true;
                            logAdminActivity('ADMIN_DELETE', $_SESSION['admin_id'], 'admin', $user['username']);
                        } else {
                            $newUsers[] = $user;
                        }
                    }
                    
                    if ($removed) {
                        saveAdminUsers($newUsers);
                        $message = 'Admin removed successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Admin not found';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'toggle_active':
                $adminId = intval($_POST['admin_id'] ?? 0);
                
                // Can't deactivate yourself
                if ($adminId == $_SESSION['admin_id']) {
                    $message = 'You cannot deactivate your own account';
                    $messageType = 'error';
                } else {
                    $users = getAdminUsers();
                    
                    foreach ($users as &$user) {
                        if ($user['id'] == $adminId) {
                            $user['is_active'] = !$user['is_active'];
                            saveAdminUsers($users);
                            
                            $action = $user['is_active'] ? 'activated' : 'deactivated';
                            logAdminActivity('ADMIN_EDIT', $_SESSION['admin_id'], 'admin', $user['username'], ['action' => $action]);
                            
                            $message = "Admin {$action} successfully";
                            $messageType = 'success';
                            break;
                        }
                    }
                }
                break;
                
            case 'reset_password':
                $adminId = intval($_POST['admin_id'] ?? 0);
                $newPassword = $_POST['new_password'] ?? '';
                
                if (strlen($newPassword) < 8) {
                    $message = 'Password must be at least 8 characters';
                    $messageType = 'error';
                } else {
                    $users = getAdminUsers();
                    
                    foreach ($users as &$user) {
                        if ($user['id'] == $adminId) {
                            $user['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
                            saveAdminUsers($users);
                            
                            logAdminActivity('ADMIN_EDIT', $_SESSION['admin_id'], 'admin', $user['username'], ['action' => 'password_reset']);
                            
                            $message = 'Password reset successfully';
                            $messageType = 'success';
                            break;
                        }
                    }
                }
                break;
        }
    }
}

// Get all admin users
$admins = getAdminUsers();

// Page title
$pageTitle = 'Admin Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Carrier Air Wing Command</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .admin-card {
            background-color: var(--bg-tertiary);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-info h3 {
            margin-bottom: 5px;
        }
        
        .admin-meta {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .admin-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .status-active {
            background-color: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }
        
        .status-inactive {
            background-color: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'nav.php'; ?>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <h1><?= $pageTitle ?></h1>
                <div class="admin-user-menu">
                    <div class="admin-user-info">
                        <div class="admin-username"><?= e($currentAdmin['username']) ?></div>
                        <div class="admin-role"><?= getRoleBadge($currentAdmin['role']) ?></div>
                    </div>
                    <a href="logout.php" class="btn btn-secondary btn-small">Logout</a>
                </div>
            </header>
            
            <!-- Content -->
            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Add New Admin -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Add New Admin</h2>
                    </div>
                    
                    <form method="POST" action="">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="add_admin">
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-control" 
                                   required 
                                   pattern="[a-zA-Z0-9_]{3,50}"
                                   title="3-50 characters, letters, numbers and underscore only">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   required 
                                   minlength="8">
                            <small class="text-muted">Minimum 8 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" class="form-control">
                                <option value="<?= ROLE_LSO ?>">LSO (Landing Signal Officer)</option>
                                <?php if ($currentAdmin['role'] == ROLE_AIR_BOSS): ?>
                                    <option value="<?= ROLE_AIR_BOSS ?>">Air Boss (Air Officer)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Add Admin</button>
                    </form>
                </div>
                
                <!-- Admin List -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Admin Users (<?= count($admins) ?>)</h2>
                    </div>
                    
                    <?php foreach ($admins as $admin): ?>
                        <div class="admin-card">
                            <div class="admin-info">
                                <h3>
                                    <?= e($admin['username']) ?>
                                    <?= getRoleBadge($admin['role']) ?>
                                    <span class="admin-status <?= $admin['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $admin['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </h3>
                                <div class="admin-meta">
                                    Email: <?= e($admin['email']) ?><br>
                                    Created: <?= formatDate($admin['created_at']) ?><br>
                                    Last Login: <?= formatDate($admin['last_login']) ?>
                                    <?php if ($admin['failed_attempts'] > 0): ?>
                                        <br><span class="text-warning">Failed attempts: <?= $admin['failed_attempts'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="btn-group">
                                <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                    <!-- Toggle Active Status -->
                                    <form method="POST" action="" style="display: inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-small">
                                            <?= $admin['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                    
                                    <!-- Reset Password -->
                                    <button type="button" 
                                            class="btn btn-secondary btn-small"
                                            onclick="showResetPasswordModal(<?= $admin['id'] ?>, '<?= e($admin['username']) ?>')">
                                        Reset Password
                                    </button>
                                    
                                    <!-- Remove Admin -->
                                    <form method="POST" action="" style="display: inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="remove_admin">
                                        <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                        <button type="submit" 
                                                class="btn btn-danger btn-small"
                                                onclick="return confirm('Remove admin <?= e($admin['username']) ?>? This cannot be undone.')">
                                            Remove
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Current User</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Reset Password</h2>
                <button type="button" class="modal-close" onclick="closeResetPasswordModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="admin_id" id="resetAdminId">
                
                <div class="form-group">
                    <label>Admin</label>
                    <p id="resetAdminName"></p>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" 
                           name="new_password" 
                           id="new_password" 
                           class="form-control" 
                           required 
                           minlength="8">
                    <small class="text-muted">Minimum 8 characters</small>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                    <button type="button" class="btn btn-secondary" onclick="closeResetPasswordModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showResetPasswordModal(adminId, adminName) {
            document.getElementById('resetAdminId').value = adminId;
            document.getElementById('resetAdminName').textContent = adminName;
            document.getElementById('resetPasswordModal').classList.add('active');
        }
        
        function closeResetPasswordModal() {
            document.getElementById('resetPasswordModal').classList.remove('active');
            document.getElementById('new_password').value = '';
        }
        
        // Close modal on outside click
        document.getElementById('resetPasswordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeResetPasswordModal();
            }
        });
    </script>
</body>
</html>