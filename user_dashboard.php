<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user = get_user_by_id($user_id);

// Get user's requisitions
$user_requisitions = get_user_requisitions($user_id);

// Count requisitions by status
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;
$processed_count = 0;
$total_amount = 0;

foreach ($user_requisitions as $req) {
    if ($req['status'] === 'pending') {
        $pending_count++;
    } elseif ($req['status'] === 'approved') {
        $approved_count++;
    } elseif ($req['status'] === 'rejected') {
        $rejected_count++;
    } elseif ($req['status'] === 'processed') {
        $processed_count++;
    }
    
    // Calculate total amount for this requisition
    $req_total = 0;
    foreach ($req['items'] as $item) {
        $req_total += $item['total_price'];
    }
    $total_amount += $req_total;
}

// Get recent requisitions (last 5)
$recent_requisitions = array_slice($user_requisitions, 0, 5);

// Get user's notifications
$notifications = get_user_notifications($user_id);
$unread_count = 0;
foreach ($notifications as $notification) {
    if ($notification['status'] === 'unread') {
        $unread_count++;
    }
}
$recent_notifications = array_slice($notifications, 0, 5);

// Get department information
$department = get_department_by_id($user['department']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Bumbe Technical Training Institute (BTTI) Resource Management System</title>
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
        
        .bg-gradient-primary {
            background: linear-gradient(45deg, #4e73df, #224abe);
        }
        
        .bg-gradient-success {
            background: linear-gradient(45deg, #1cc88a, #13855c);
        }
        
        .bg-gradient-info {
            background: linear-gradient(45deg, #36b9cc, #258391);
        }
        
        .bg-gradient-warning {
            background: linear-gradient(45deg, #f6c23e, #dda20a);
        }
        
        .bg-gradient-danger {
            background: linear-gradient(45deg, #e74a3b, #be2617);
        }
        
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <?php require 'nav.php'; ?>
    
    <div class="container mt-4">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">BTTI Resource Dashboard</h1>
            <a href="create_requisition.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="bi bi-plus-circle"></i> New Requisition
            </a>
        </div>
        
        <!-- Welcome Card -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <h5 class="card-title">Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h5>
                <p class="card-text">
                    Department: <?php echo $department ? htmlspecialchars($department['department_name']) : 'Not assigned'; ?> | 
                    Role: <?php echo ucfirst(htmlspecialchars($user_role)); ?> | 
                    <?php if ($unread_count > 0): ?>
                        <span class="text-danger">You have <?php echo $unread_count; ?> unread notification<?php echo $unread_count > 1 ? 's' : ''; ?></span>
                    <?php else: ?>
                        <span class="text-success">You're all caught up!</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row">
            <!-- Pending Requisitions Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card h-100 py-2 bg-gradient-primary text-white">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Pending Resource Requests</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $pending_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-hourglass-split stat-icon"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="view_requisitions.php" class="text-white small stretched-link">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Approved Requisitions Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card h-100 py-2 bg-gradient-success text-white">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Approved Resource Requests</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $approved_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-check-circle stat-icon"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="view_requisitions.php" class="text-white small stretched-link">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Rejected Requisitions Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card h-100 py-2 bg-gradient-danger text-white">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Rejected Resource Requests</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $rejected_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-x-circle stat-icon"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="view_requisitions.php" class="text-white small stretched-link">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Total Amount Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card h-100 py-2 bg-gradient-info text-white">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Total Resource Budget Used</div>
                                <div class="h5 mb-0 font-weight-bold">KES <?php echo number_format($total_amount, 2); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-currency-dollar stat-icon"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="view_requisitions.php" class="text-white small stretched-link">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Content Row -->
        <div class="row">
            <!-- Recent Requisitions -->
            <div class="col-lg-7 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h2 class="mt-4">Recent Resource Requests</h2>
                        <a href="view_requisitions.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_requisitions)): ?>
                            <p class="text-center">You haven't created any requisitions yet.</p>
                            <div class="text-center mt-3">
                                <a href="create_requisition.php" class="btn btn-primary">Create Your First Requisition</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Total Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_requisitions as $req): ?>
                                            <?php 
                                                // Calculate total amount for this requisition
                                                $req_total = 0;
                                                foreach ($req['items'] as $item) {
                                                    $req_total += $item['total_price'];
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo $req['requisition_id']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($req['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                    <?php elseif ($req['status'] === 'approved'): ?>
                                                        <span class="badge bg-success">Approved</span>
                                                    <?php elseif ($req['status'] === 'rejected'): ?>
                                                        <span class="badge bg-danger">Rejected</span>
                                                    <?php elseif ($req['status'] === 'processed'): ?>
                                                        <span class="badge bg-info">Processed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>KES <?php echo number_format($req_total, 2); ?></td>
                                                <td>
                                                    <a href="requisition_details.php?id=<?php echo $req['requisition_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="col-lg-5 mb-4">
                <!-- Notifications Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold">Recent Notifications</h6>
                        <a href="notifications.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_notifications)): ?>
                            <p class="text-center">No notifications found.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($recent_notifications as $notification): ?>
                                    <a href="notifications.php" class="list-group-item list-group-item-action <?php echo ($notification['status'] === 'unread') ? 'list-group-item-light' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1 text-truncate-2"><?php echo htmlspecialchars($notification['message']); ?></h6>
                                            <small><?php echo date('M d', strtotime($notification['created_at'])); ?></small>
                                        </div>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($notification['created_at'])); ?></small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h2 class="mt-4">School Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="create_requisition.php" class="btn btn-primary btn-lg btn-block mb-3">
                            <i class="bi bi-plus-circle"></i> Request School Resources
                        </a>    </a>
                            <a href="view_requisitions.php" class="btn btn-info btn-lg btn-block mb-3">
                            <i class="bi bi-list-check"></i> View My Resource Requests
                        </a>    </a>
                            <a href="notifications.php" class="btn btn-outline-primary">
                                <i class="bi bi-bell"></i> Check Notifications
                                <?php if ($unread_count > 0): ?>
                                    <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="profile.php" class="btn btn-outline-primary">
                                <i class="bi bi-person"></i> Update My Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
