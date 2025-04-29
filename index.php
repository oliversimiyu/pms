<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on user role
    $user_role = $_SESSION['user_role'];
    
    if ($user_role === 'admin') {
        header("Location: dashboard.php");
    } elseif ($user_role === 'hod') {
        header("Location: hod_dashboard.php");
    } elseif ($user_role === 'staff') {
        header("Location: staff_dashboard.php");
    } elseif ($user_role === 'student') {
        header("Location: student_dashboard.php");
    } else {
        header("Location: user_dashboard.php");
    }
} else {
    // Not logged in, redirect to login page
    header("Location: login.php");
}
exit;
?>
