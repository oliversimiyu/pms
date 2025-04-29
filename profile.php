<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user = get_user_by_id($user_id);

// Handle form submission for profile update
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = htmlspecialchars($_POST['full_name']);
    $email = htmlspecialchars($_POST['email']);
    $department = intval($_POST['department']);
    
    // Check if email is already taken by another user
    $existing_user = get_user_by_email($email);
    if ($existing_user && $existing_user['user_id'] != $user_id) {
        $error_message = "Email address is already in use by another user.";
    } else {
        // Prepare user data for update
        $user_data = [
            'full_name' => $full_name,
            'email' => $email,
            'department' => $department,
            'role' => $user['role'] // Keep the same role
        ];
        
        // Handle password change if provided
        if (!empty($_POST['new_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                $error_message = "Current password is incorrect.";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "New password and confirmation do not match.";
            } else {
                // Hash the new password
                $user_data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }
        } else {
            // Keep the existing password
            $user_data['password'] = $user['password'];
        }
        
        // Update user if no errors
        if (empty($error_message)) {
            if (update_user($user_id, $user_data)) {
                $success_message = "Profile updated successfully!";
                // Update session data
                $_SESSION['user_name'] = $full_name;
                // Refresh user data
                $user = get_user_by_id($user_id);
            } else {
                $error_message = "Error updating profile.";
            }
        }
    }
}

// Get departments for dropdown
$departments = get_departments();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Purchase Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php require 'nav.php'; ?>
    
    <div class="container mt-4">
        <h2>My Profile</h2>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" name="department" required>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['department_id']; ?>" <?php echo ($user['department'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <input type="text" class="form-control" id="role" value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" readonly>
                                <div class="form-text">Role cannot be changed. Contact an administrator if you need a role change.</div>
                            </div>
                            
                            <hr>
                            
                            <h5>Change Password</h5>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                <div class="form-text">Leave password fields empty if you don't want to change your password.</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Account Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>User ID:</strong> <?php echo $user['user_id']; ?></p>
                        <p><strong>Account Created:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                        <p><strong>Last Login:</strong> <?php echo isset($user['last_login']) ? date('F d, Y H:i', strtotime($user['last_login'])) : 'Not available'; ?></p>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Quick Links</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><a href="view_requisitions.php" class="text-decoration-none">My Requisitions</a></li>
                            <li class="list-group-item"><a href="notifications.php" class="text-decoration-none">Notifications</a></li>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <li class="list-group-item"><a href="admin_dashboard.php" class="text-decoration-none">User Management</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
