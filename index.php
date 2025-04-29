<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on user role
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: user_dashboard.php");
    }
} else {
    // Not logged in, redirect to login page
    header("Location: login.php");
}
exit;
?>
