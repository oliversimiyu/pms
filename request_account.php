<?php
session_start();
require 'includes/config.php';

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate inputs
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $role_requested = trim($_POST['role_requested'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    
    // Simple validation
    if (empty($full_name) || empty($email) || empty($department) || empty($role_requested) || empty($reason)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Check if email already exists
        $existing_user = get_user_by_email($email);
        if ($existing_user) {
            $error_message = "This email is already registered in the system.";
        } else {
            // Create account request
            $request_id = uniqid('req_');
            $request_data = [
                'request_id' => $request_id,
                'full_name' => $full_name,
                'email' => $email,
                'department' => $department,
                'role_requested' => $role_requested,
                'reason' => $reason,
                'status' => 'pending',
                'date_requested' => date('Y-m-d H:i:s')
            ];
            
            // Data directory is now handled in the save_account_request function
            
            // Save request
            $saved = save_account_request($request_data);
            
            if ($saved) {
                // Create notification for all admins
                $notification_data = [
                    'notification_id' => uniqid('notif_'),
                    'user_id' => 'admin', // Special identifier for all admins
                    'type' => 'account_request',
                    'message' => "New account request from {$full_name} ({$email}) for {$role_requested} role.",
                    'reference_id' => $request_id,
                    'is_read' => false,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                add_notification($notification_data);
                
                $success_message = "Your account request has been submitted successfully. An administrator will review your request.";
            } else {
                $error_message = "Failed to submit your request. Please try again later.";
            }
        }
    }
}

// Define department list for dropdown since the file might not exist yet
$departments = [
    'Mathematics',
    'Sciences',
    'Languages',
    'Social Studies',
    'Arts',
    'Physical Education',
    'Technology',
    'Administration',
    'Finance',
    'Library',
    'Student Affairs'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Request Account - Bumbe School Resource Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .bg-primary {
            background-color: #4e73df !important;
        }
        #layoutAuthentication {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        #layoutAuthentication_content {
            flex-grow: 1;
        }
        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
        }
        .success-message {
            color: #198754;
            margin-bottom: 15px;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
    </style>
</head>
<body class="bg-primary">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-7">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header">
                                    <h3 class="text-center font-weight-light my-4">Request a Bumbe School Account</h3>
                                </div>
                                <div class="card-body">
                                    <?php if ($success_message): ?>
                                    <div class="alert alert-success">
                                        <?php echo $success_message; ?>
                                        <div class="mt-3">
                                            <a href="login.php" class="btn btn-outline-success">Return to Login</a>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    
                                    <?php if ($error_message): ?>
                                    <div class="alert alert-danger">
                                        <?php echo $error_message; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <form action="" method="post">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" name="full_name" id="full_name" type="text" placeholder="Enter your full name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" />
                                                    <label for="full_name">Full Name</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input class="form-control" name="email" id="email" type="email" placeholder="name@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                                                    <label for="email">Email address</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <select class="form-select" id="department" name="department" required>
                                                        <option value="" selected disabled>Select Department</option>
                                                        <?php foreach ($departments as $dept): ?>
                                                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo (isset($_POST['department']) && $_POST['department'] == $dept) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($dept); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <label for="department">Department</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select class="form-select" id="role_requested" name="role_requested" required>
                                                        <option value="" selected disabled>Select Role</option>
                                                        <option value="admin" <?php echo (isset($_POST['role_requested']) && $_POST['role_requested'] == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                                        <option value="hod" <?php echo (isset($_POST['role_requested']) && $_POST['role_requested'] == 'hod') ? 'selected' : ''; ?>>Head of Department (HOD)</option>
                                                        <option value="staff" <?php echo (isset($_POST['role_requested']) && $_POST['role_requested'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                                                        <option value="student" <?php echo (isset($_POST['role_requested']) && $_POST['role_requested'] == 'student') ? 'selected' : ''; ?>>Student</option>
                                                    </select>
                                                    <label for="role_requested">Role Requested</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <textarea class="form-control" id="reason" name="reason" style="height: 100px;" required><?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?></textarea>
                                            <label for="reason">Reason for Request</label>
                                        </div>
                                        <div class="mt-4 mb-0">
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary btn-block">Submit Request</button>
                                            </div>
                                        </div>
                                    </form>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer text-center py-3">
                                    <div class="small"><a href="login.php">Already have an account? Go to login</a></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <div id="layoutAuthentication_footer">
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Purchase Management System 2025</div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
