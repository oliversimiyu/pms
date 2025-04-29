<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submission for updating user rights
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $new_role = $_POST['new_role'] ?? '';
    
    if (!empty($user_id) && !empty($new_role)) {
        // Get existing user data
        $user = get_user_by_id($user_id);
        
        if ($user) {
            // Update user role
            $user_data = [
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'department' => $user['department'],
                'role' => $new_role,
                'password' => $user['password']
            ];
            
            if (update_user($user_id, $user_data)) {
                // Add notification for the user
                $notification_data = [
                    'user_id' => $user_id,
                    'message' => "Your account role has been updated to " . ucfirst($new_role) . " by the administrator.",
                    'is_read' => false,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                add_notification($notification_data);
                
                $success_message = "User rights updated successfully. User " . $user['full_name'] . " is now a " . ucfirst($new_role) . ".";
            } else {
                $error_message = "Failed to update user rights.";
            }
        } else {
            $error_message = "User not found.";
        }
    } else {
        $error_message = "Invalid request. User ID and new role are required.";
    }
}

// Get all users
$users = get_users();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Rights - School Resource Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container-fluid px-4 mt-4">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Manage User Rights</h1>
            <a href="admin_dashboard.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="bi bi-arrow-left"></i> Back to Admin Dashboard
            </a>
        </div>
        
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="admin_dashboard.php">User Management</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manage User Rights</li>
            </ol>
        </nav>
        
        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold"><i class="bi bi-shield-lock"></i> User Rights Management</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Current Role</th>
                                <th>Change Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['department']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['role'] === 'admin' ? 'danger' : 
                                            ($user['role'] === 'hod' ? 'warning' : 
                                                ($user['role'] === 'staff' ? 'info' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <form action="" method="post" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <select class="form-select form-select-sm" name="new_role" required>
                                            <option value="" selected disabled>Select new role</option>
                                            <option value="admin">Administrator</option>
                                            <option value="hod">Head of Department (HOD)</option>
                                            <option value="staff">Staff</option>
                                            <option value="student">Student</option>
                                        </select>
                                </td>
                                <td>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-shield-check"></i> Update Rights
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold"><i class="bi bi-info-circle"></i> Role Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-danger text-white">
                                Administrator
                            </div>
                            <div class="card-body">
                                <p>Full access to all system features including:</p>
                                <ul>
                                    <li>User management</li>
                                    <li>Account request approval</li>
                                    <li>All resource requests</li>
                                    <li>System-wide inventory</li>
                                    <li>All reports and analytics</li>
                                    <li>User rights management</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-warning text-dark">
                                Head of Department (HOD)
                            </div>
                            <div class="card-body">
                                <p>Department-level management including:</p>
                                <ul>
                                    <li>Department resource requests</li>
                                    <li>Department inventory</li>
                                    <li>Department reports</li>
                                    <li>Approval of staff requests</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                Staff
                            </div>
                            <div class="card-body">
                                <p>School staff access including:</p>
                                <ul>
                                    <li>Create resource requests</li>
                                    <li>View personal requests</li>
                                    <li>View inventory</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-secondary text-white">
                                Student
                            </div>
                            <div class="card-body">
                                <p>Limited access including:</p>
                                <ul>
                                    <li>Create resource requests</li>
                                    <li>View personal requests</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
