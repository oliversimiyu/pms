<?php
// session_start();
require 'includes/config.php'; // Database connection file

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['inputEmail'];
    $password = $_POST['inputPassword'];

    // Fetch user from database
    $stmt = $con->prepare("SELECT `user_id`, `full_name`,`role`, `password` FROM users WHERE `email` = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $role, $hashed_password);
        echo "$role";
        echo "$name";
        echo "$id";
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashed_password)) {
            // $_SESSION['user_id'] = $id;
            // $_SESSION['user_name'] = $name;
            // $_SESSION['user_role'] = $role;

            // Redirect based on role
            if ($role === 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($role === 'manager') {
                header("Location: dashboard_manager.php");
            } else {
                header("Location: dashboard_admin.php");
            }
            exit();
        } else {
            $error = "Invalid credentials!";
        }
    } else {
        $error = "User not found!";
    }
}
?>