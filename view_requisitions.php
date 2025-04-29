<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user = get_user_by_id($_SESSION['user_id']);
$user_role = $_SESSION['user_role'];

// Get requisitions based on user role
$requisitions = [];
if ($user_role === 'admin' || $user_role === 'procurement') {
    // Admin and procurement can see all requisitions
    $requisitions = get_all_requisitions();
} elseif ($user_role === 'approver') {
    // Approvers see requisitions pending their approval
    $requisitions = get_requisitions_for_approval($_SESSION['user_id']);
} elseif ($user_role === 'hod') {
    // HODs see requisitions from their department
    $all_requisitions = get_all_requisitions();
    $department_id = $user['department'];
    
    foreach ($all_requisitions as $req) {
        $req_user = get_user_by_id($req['requester_id']);
        if ($req_user && $req_user['department'] == $department_id) {
            $requisitions[] = $req;
        }
    }
} else {
    // Regular users see only their own requisitions
    $requisitions = get_user_requisitions($_SESSION['user_id']);
}

// Filter by status if specified
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status_filter = $_GET['status'];
    $filtered_requisitions = [];
    foreach ($requisitions as $req) {
        if ($req['status'] === $status_filter) {
            $filtered_requisitions[] = $req;
        }
    }
    $requisitions = $filtered_requisitions;
}

// Sort requisitions by date (newest first)
usort($requisitions, function($a, $b) {
    return strtotime($b['date_created']) - strtotime($a['date_created']);
});

// Pagination
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$total_pages = ceil(count($requisitions) / $items_per_page);
$current_page = max(1, min($current_page, $total_pages));
$offset = ($current_page - 1) * $items_per_page;
$current_requisitions = array_slice($requisitions, $offset, $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Resource Requests - Bumbe Technical Training Institute (BTTI) Resource Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-pending { color: #FFC107; }
        .status-approved { color: #28A745; }
        .status-rejected { color: #DC3545; }
        .status-processed { color: #17A2B8; }
    </style>
</head>
<body>
    <?php require 'nav.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mt-4">BTTI Resource Requests</h1>
            <?php if ($user_role === 'requester' || $user_role === 'admin'): ?>
                <a href="create_requisition.php" class="btn btn-primary">Create New Requisition</a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($requisitions)): ?>
            <div class="alert alert-info">No requisitions found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Requester</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($current_requisitions as $req): ?>
                            <?php 
                                $requester = get_user_by_id($req['requester_id']);
                                $department = get_department_by_id($req['department']);
                                $total_amount = 0;
                                foreach ($req['items'] as $item) {
                                    $total_amount += $item['total_price'];
                                }
                            ?>
                            <tr>
                                <td><?php echo $req['requisition_id']; ?></td>
                                <td><?php echo $requester ? $requester['full_name'] : 'Unknown'; ?></td>
                                <td><?php echo $department ? $department['department_name'] : 'Unknown'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                                <td>
                                    <span class="status-<?php echo strtolower($req['status']); ?>">
                                        <?php echo ucfirst($req['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($total_amount, 2); ?></td>
                                <td>
                                    <a href="requisition_details.php?id=<?php echo $req['requisition_id']; ?>" class="btn btn-sm btn-info">View</a>
                                    
                                    <?php if (($user_role === 'approver' || $user_role === 'admin' || $user_role === 'hod') && $req['status'] === 'pending'): ?>
                                        <a href="approve_requisition.php?id=<?php echo $req['requisition_id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                        <a href="reject_requisition.php?id=<?php echo $req['requisition_id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                    <?php endif; ?>
                                    
                                    <?php if ($user_role === 'procurement' && $req['status'] === 'approved'): ?>
                                        <a href="process_requisition.php?id=<?php echo $req['requisition_id']; ?>" class="btn btn-sm btn-primary">Process</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Resource requests pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
