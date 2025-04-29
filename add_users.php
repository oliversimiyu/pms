<?php
// session_start();
include_once ('includes/config.php');

// if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
//     header("Location: login.php");
//     exit();
// }

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Add user logic
    $name = htmlspecialchars($_POST["username"]);
    $role = htmlspecialchars($_POST["userRole"]);
    $department = htmlspecialchars($_POST["department"]);
    $email = htmlspecialchars($_POST["useremail"]);
    $password = htmlspecialchars($_POST["password"]);
    $hashedpwd = password_hash($password, PASSWORD_DEFAULT);

    // Create user data array
    $user_data = [
        'full_name' => $name,
        'email' => $email,
        'department' => $department,
        'role' => $role,
        'password' => $hashedpwd
    ];

    // Use our file-based add_user function
    if (add_user($user_data)) {
        header("Location: admin_dashboard.php?success=User added");
    } else {
        echo "Error adding user.";
    }
}
?>