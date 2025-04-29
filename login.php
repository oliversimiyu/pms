<?php
session_start();
require 'includes/config.php'; // Ensure database connection

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];  
    $password = $_POST['password']; 
    
    if (is_db_available()) {
        // Get user by email using our file-based function
        $user = get_user_by_email($email);
        
        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                if ($user['role'] === 'admin') {
                    header("Location: dashboard.php");
                } else {
                    header("Location: user_dashboard.php");
                }
                exit();
            } else {
                $error_message = "Invalid password. Please try again.";
            }
        } else {
            $error_message = "User not found. Please check your email.";
        }
    } else {
        $error_message = "Database connection error: " . get_db_error();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Login - Purchase Management System</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://use.fontawesome.com/releases/v6.1.0/js/all.js" crossorigin="anonymous"></script>
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
                            <div class="col-lg-5">
                                <div class="card shadow-lg border-0 rounded-lg mt-5">
                                    <div class="card-header"><h3 class="text-center font-weight-light my-4">Purchase Management System</h3></div>
                                    <div class="card-body">
                                        <?php if (!is_db_available()): ?>
                                        <div class="alert alert-warning">
                                            <h4>Database Connection Issue</h4>
                                            <p>The system cannot connect to the database. Please contact your system administrator.</p>
                                            <p><strong>Error:</strong> <?php echo get_db_error(); ?></p>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($error_message): ?>
                                        <div class="error-message">
                                            <?php echo $error_message; ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <form action="" method="post">
                                            <div class="form-floating mb-3">
                                                <input class="form-control" name="email" id="email" type="email" placeholder="name@example.com" />
                                                <label for="email">Email address</label>
                                            </div>
                                            <div class="form-floating mb-3">
                                                <input class="form-control" name="password" id="password" type="password" placeholder="Password" />
                                                <label for="password">Password</label>
                                            </div>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" id="inputRememberPassword" type="checkbox" value="" />
                                                <label class="form-check-label" for="inputRememberPassword">Remember Password</label>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                                                <a class="small" href="#">Forgot Password?</a>
                                                <button type="submit" class="btn btn-primary">Login</button>
                                            </div>
                                        </form>
                                        
                                        <?php if (is_db_available()): ?>
                                        <div class="alert alert-info mt-4">
                                            <p><strong>Default Admin Login:</strong></p>
                                            <p>Email: admin@example.com<br>Password: admin123</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer text-center py-3">
                                        <div class="small"><a href="#">Need an account? Contact Administrator</a></div>
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
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    </body>
</html>
