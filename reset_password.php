<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Get all users for the admin to manage
$all_users = get_users();

// Handle password reset form submission
$reset_success = false;
$reset_error = '';
$reset_user_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $reset_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    
    // Validate inputs
    if (empty($new_password)) {
        $reset_error = "New password is required";
    } elseif (strlen($new_password) < 6) {
        $reset_error = "Password must be at least 6 characters long";
    } elseif ($new_password !== $confirm_password) {
        $reset_error = "Passwords do not match";
    } else {
        // Get user to reset
        $reset_user = get_user_by_id($reset_user_id);
        
        if (!$reset_user) {
            $reset_error = "User not found";
        } else {
            // Update user password
            $reset_user['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            
            if (update_user($reset_user_id, $reset_user)) {
                $reset_success = true;
                $reset_user_name = $reset_user['full_name'];
                
                // Add notification for the user
                add_notification($reset_user_id, [
                    'title' => 'Password Reset',
                    'message' => 'Your password has been reset by an administrator. Please use your new password to log in.',
                    'type' => 'password',
                    'is_read' => false
                ]);
                
                // Log the action
                $admin_user = get_user_by_id($user_id);
                $admin_name = $admin_user ? $admin_user['full_name'] : 'Unknown Admin';
                $log_message = "Password reset for user {$reset_user['full_name']} (ID: {$reset_user_id}) by {$admin_name} (ID: {$user_id})";
                // You could add a logging function here if needed
            } else {
                $reset_error = "Failed to reset password. Please try again.";
            }
        }
    }
}

// Pagination
$items_per_page = 10;
$total_items = count($all_users);
$total_pages = ceil($total_items / $items_per_page);

// Get current page
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get users for current page
$paged_users = array_slice($all_users, $offset, $items_per_page);

// Page title
$page_title = 'Reset User Passwords';
$page_styles = <<<HTML
<style>
    .user-table th, .user-table td {
        vertical-align: middle;
    }
    .role-badge {
        font-size: 0.8rem;
    }
    .role-admin { background-color: #dc3545; }
    .role-hod { background-color: #fd7e14; }
    .role-staff { background-color: #20c997; }
    .role-student { background-color: #0dcaf0; }
</style>
HTML;

include('includes/header.php');
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Reset User Passwords</h5>
                    <a href="manage_user_rights.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to User Management
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($reset_success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Password for <strong><?php echo htmlspecialchars($reset_user_name); ?></strong> has been reset successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($reset_error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($reset_error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover user-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paged_users as $user): ?>
                                    <?php 
                                    $department = get_department_by_id($user['department']);
                                    $department_name = $department ? $department['department_name'] : 'Unknown';
                                    $role_class = 'bg-secondary';
                                    
                                    switch ($user['role']) {
                                        case 'admin':
                                            $role_class = 'role-admin';
                                            break;
                                        case 'hod':
                                            $role_class = 'role-hod';
                                            break;
                                        case 'staff':
                                            $role_class = 'role-staff';
                                            break;
                                        case 'student':
                                            $role_class = 'role-student';
                                            break;
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($department_name); ?></td>
                                        <td>
                                            <span class="badge <?php echo $role_class; ?> role-badge">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#resetPasswordModal" 
                                                    data-user-id="<?php echo $user['user_id']; ?>"
                                                    data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>">
                                                <i class="bi bi-key"></i> Reset Password
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="User pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="reset_password.php" method="post">
                <div class="modal-body">
                    <p>You are about to reset the password for <strong id="resetUserName"></strong>.</p>
                    <input type="hidden" name="user_id" id="resetUserId" value="">
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="reset_password" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$page_scripts = <<<HTML
<script>
    // Handle reset password modal
    document.addEventListener('DOMContentLoaded', function() {
        var resetPasswordModal = document.getElementById('resetPasswordModal');
        if (resetPasswordModal) {
            resetPasswordModal.addEventListener('show.bs.modal', function(event) {
                // Button that triggered the modal
                var button = event.relatedTarget;
                
                // Extract info from data attributes
                var userId = button.getAttribute('data-user-id');
                var userName = button.getAttribute('data-user-name');
                
                // Update the modal's content
                var userIdInput = document.getElementById('resetUserId');
                var userNameElement = document.getElementById('resetUserName');
                
                if (userIdInput) userIdInput.value = userId;
                if (userNameElement) userNameElement.textContent = userName;
            });
        }
        
        // Password confirmation validation
        var newPasswordInput = document.getElementById('new_password');
        var confirmPasswordInput = document.getElementById('confirm_password');
        
        function validatePasswordMatch() {
            if (newPasswordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.setCustomValidity("Passwords do not match");
            } else {
                confirmPasswordInput.setCustomValidity("");
            }
        }
        
        if (newPasswordInput && confirmPasswordInput) {
            newPasswordInput.addEventListener('change', validatePasswordMatch);
            confirmPasswordInput.addEventListener('keyup', validatePasswordMatch);
        }
    });
</script>
HTML;
include('includes/footer.php');
?>
