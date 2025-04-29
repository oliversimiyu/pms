<?php
session_start();
require 'includes/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Validate requisition ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard_employee.php?error=Invalid Request");
    exit();
}

$requisition_id = $_GET['id'];

// Fetch requisition details
$stmt = $conn->prepare("SELECT r.id, u.name AS requester, r.department, r.justification, r.status, r.created_at 
                        FROM requisitions r 
                        JOIN users u ON r.requester_id = u.id 
                        WHERE r.id = ?");
$stmt->bind_param("i", $requisition_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard_employee.php?error=Requisition Not Found");
    exit();
}

$requisition = $result->fetch_assoc();

// Fetch approval history
$approval_stmt = $conn->prepare("SELECT a.status, m.name AS manager, a.approval_date 
                                 FROM approvals a 
                                 JOIN users m ON a.manager_id = m.id 
                                 WHERE a.requisition_id = ?");
$approval_stmt->bind_param("i", $requisition_id);
$approval_stmt->execute();
$approvals = $approval_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Requisition | Purchase Requisition System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
    <h3 class="text-center">Requisition Details</h3>
    <a href="<?= $user_role === 'employee' ? 'dashboard_employee.php' : ($user_role === 'manager' ? 'dashboard_manager.php' : 'dashboard_admin.php') ?>" class="btn btn-secondary mb-3">Back to Dashboard</a>

    <table class="table table-bordered">
        <tr>
            <th>Requisition ID</th>
            <td><?= $requisition['id'] ?></td>
        </tr>
        <tr>
            <th>Requester</th>
            <td><?= htmlspecialchars($requisition['requester']) ?></td>
        </tr>
        <tr>
            <th>Department</th>
            <td><?= htmlspecialchars($requisition['department']) ?></td>
        </tr>
        <tr>
            <th>Justification</th>
            <td><?= htmlspecialchars($requisition['justification']) ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <td><span class="badge bg-<?= $requisition['status'] == 'Approved' ? 'success' : ($requisition['status'] == 'Rejected' ? 'danger' : 'warning') ?>">
                <?= $requisition['status'] ?>
            </span></td>
        </tr>
        <tr>
            <th>Submitted On</th>
            <td><?= $requisition['created_at'] ?></td>
        </tr>
    </table>

    <h5>Approval History</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Status</th>
                <th>Approved/Rejected By</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $approvals->fetch_assoc()) { ?>
                <tr>
                    <td><span class="badge bg-<?= $row['status'] == 'Approved' ? 'success' : 'danger' ?>">
                        <?= $row['status'] ?>
                    </span></td>
                    <td><?= htmlspecialchars($row['manager']) ?></td>
                    <td><?= $row['approval_date'] ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</body>
</html>
