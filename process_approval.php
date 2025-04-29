<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in and has approver or admin role
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'approver' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['requisition_id']) || !isset($_POST['action'])) {
    header("Location: view_requisitions.php");
    exit();
}

$requisition_id = intval($_POST['requisition_id']);
$action = $_POST['action'];
$comments = isset($_POST['comments']) ? htmlspecialchars($_POST['comments']) : '';

// Get the requisition
$requisition = get_requisition_by_id($requisition_id);

// Check if requisition exists and is pending
if (!$requisition || $requisition['status'] !== 'pending') {
    $_SESSION['error_message'] = "Invalid requisition or requisition is not pending approval.";
    header("Location: view_requisitions.php");
    exit();
}

// Process approval or rejection
$approval_data = [
    'requisition_id' => $requisition_id,
    'approver_id' => $_SESSION['user_id'],
    'status' => ($action === 'approve') ? 'approved' : 'rejected',
    'comments' => $comments,
    'approved_at' => date('Y-m-d H:i:s')
];

if (add_approval($approval_data)) {
    $_SESSION['success_message'] = "Requisition has been " . (($action === 'approve') ? 'approved' : 'rejected') . " successfully.";
} else {
    $_SESSION['error_message'] = "Error processing approval.";
}

// Redirect back to requisition details
header("Location: requisition_details.php?id=" . $requisition_id);
exit();
?>
