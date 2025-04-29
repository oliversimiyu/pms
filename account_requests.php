<?php
session_start();
require 'includes/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? '';
    $action = $_POST['action'] ?? '';
    $admin_comment = $_POST['admin_comment'] ?? '';
    
    if (!empty($request_id) && !empty($action)) {
        if ($action === 'approve') {
            $result = approve_account_request($request_id, $admin_comment);
            if ($result && isset($result['success']) && $result['success']) {
                $success_message = "Account request approved successfully. Temporary password: " . $result['password'];
            } else {
                $error_message = "Failed to approve account request.";
            }
        } elseif ($action === 'reject') {
            $result = reject_account_request($request_id, $admin_comment);
            if ($result) {
                $success_message = "Account request rejected successfully.";
            } else {
                $error_message = "Failed to reject account request.";
            }
        }
    } else {
        $error_message = "Invalid request.";
    }
}

// Get all account requests
$account_requests = get_account_requests();

// Sort by date (newest first)
usort($account_requests, function($a, $b) {
    return strtotime($b['date_requested']) - strtotime($a['date_requested']);
});

// Group by status
$pending_requests = [];
$processed_requests = [];

foreach ($account_requests as $request) {
    if ($request['status'] === 'pending') {
        $pending_requests[] = $request;
    } else {
        $processed_requests[] = $request;
    }
}

// Pagination for pending requests
$items_per_page = 10;
$current_page_pending = isset($_GET['pending_page']) ? (int)$_GET['pending_page'] : 1;
$total_pages_pending = ceil(count($pending_requests) / $items_per_page);
$current_page_pending = max(1, min($current_page_pending, $total_pages_pending));
$offset_pending = ($current_page_pending - 1) * $items_per_page;
$current_pending_requests = array_slice($pending_requests, $offset_pending, $items_per_page);

// Pagination for processed requests
$current_page_processed = isset($_GET['processed_page']) ? (int)$_GET['processed_page'] : 1;
$total_pages_processed = ceil(count($processed_requests) / $items_per_page);
$current_page_processed = max(1, min($current_page_processed, $total_pages_processed));
$offset_processed = ($current_page_processed - 1) * $items_per_page;
$current_processed_requests = array_slice($processed_requests, $offset_processed, $items_per_page);
}

$page_title = "Account Requests";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Bumbe Technical Training Institute (BTTI) Resource Management System</title>
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
        
        .btn-group-xs > .btn, .btn-xs {
            padding: .25rem .4rem;
            font-size: .875rem;
            line-height: .5;
            border-radius: .2rem;
        }
        
        /* Using Bootstrap 5 badge classes instead of custom ones */
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'nav.php'; ?>
    
    <div class="container mt-4">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">BTTI <?php echo $page_title; ?></h1>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $page_title; ?></li>
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
                <h6 class="m-0 font-weight-bold"><i class="bi bi-person-plus me-1"></i> Pending School Account Requests</h6>
            </div>
            <div class="card-body">
                <?php if (empty($pending_requests)): ?>
                <div class="alert alert-info">
                    No pending account requests.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Role Requested</th>
                                <th>Reason</th>
                                <th>Date Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($current_pending_requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['email']); ?></td>
                                <td><?php echo htmlspecialchars($request['department']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($request['role_requested'])); ?></td>
                                <td><?php echo htmlspecialchars($request['reason']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($request['date_requested'])); ?></td>
                                <td>
                                     <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $request['request_id']; ?>">
                                         <i class="bi bi-check-circle"></i> Approve
                                     </button>
                                     <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $request['request_id']; ?>">
                                         <i class="bi bi-x-circle"></i> Reject
                                     </button>
                                     
                                     <!-- Approve Modal -->
                                     <div class="modal fade" id="approveModal<?php echo $request['request_id']; ?>" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
                                         <div class="modal-dialog">
                                             <div class="modal-content">
                                                 <form action="" method="post">
                                                     <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                     <input type="hidden" name="action" value="approve">
                                                     <div class="modal-header">
                                                         <h5 class="modal-title" id="approveModalLabel">Approve Account Request</h5>
                                                         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                     </div>
                                                     <div class="modal-body">
                                                         <p>Are you sure you want to approve the account request for <strong><?php echo htmlspecialchars($request['full_name']); ?></strong>?</p>
                                                         <p>A user account will be created with the requested role and department. A temporary password will be generated.</p>
                                                         <div class="mb-3">
                                                             <label for="admin_comment" class="form-label">Admin Comment (Optional)</label>
                                                             <textarea class="form-control" id="admin_comment" name="admin_comment" rows="3"></textarea>
                                                         </div>
                                                     </div>
                                                     <div class="modal-footer">
                                                         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                         <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Approve</button>
                                                     </div>
                                                 </form>
                                             </div>
                                         </div>
                                     </div>
                                     
                                     <!-- Reject Modal -->
                                     <div class="modal fade" id="rejectModal<?php echo $request['request_id']; ?>" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
                                         <div class="modal-dialog">
                                             <div class="modal-content">
                                                 <form action="" method="post">
                                                     <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                     <input type="hidden" name="action" value="reject">
                                                     <div class="modal-header">
                                                         <h5 class="modal-title" id="rejectModalLabel">Reject Account Request</h5>
                                                         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                     </div>
                                                     <div class="modal-body">
                                                         <p>Are you sure you want to reject the account request for <strong><?php echo htmlspecialchars($request['full_name']); ?></strong>?</p>
                                                         <div class="mb-3">
                                                             <label for="admin_comment" class="form-label">Reason for Rejection</label>
                                                             <textarea class="form-control" id="admin_comment" name="admin_comment" rows="3" placeholder="Please provide a reason for rejection"></textarea>
                                                         </div>
                                                     </div>
                                                     <div class="modal-footer">
                                                         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                         <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> Reject</button>
                                                     </div>
                                                 </form>
                                             </div>
                                         </div>
                                     </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold"><i class="bi bi-clock-history me-1"></i> Processed School Account Requests</h6>
            </div>
            <div class="card-body">
                <?php if (empty($processed_requests)): ?>
                <div class="alert alert-info">
                    No processed account requests.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Role Requested</th>
                                <th>Status</th>
                                <th>Date Requested</th>
                                <th>Admin Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($current_processed_requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['email']); ?></td>
                                <td><?php echo htmlspecialchars($request['department']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($request['role_requested'])); ?></td>
                                <td>
                                    <?php if ($request['status'] === 'approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                    <?php elseif ($request['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger">Rejected</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo ucfirst($request['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($request['date_requested'])); ?></td>
                                <td><?php echo !empty($request['admin_comment']) ? htmlspecialchars($request['admin_comment']) : '<em>No comment</em>'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
