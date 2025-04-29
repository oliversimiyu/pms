<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user role
$user_role = $_SESSION['user_role'];

// Redirect non-admin users to appropriate pages
if ($user_role !== 'admin' && $user_role !== 'approver' && $user_role !== 'procurement' && $user_role !== 'requester') {
    header("Location: login.php");
    exit();
}

// Get system statistics
$users = get_users();
$user_count = count($users);

$requisitions = get_all_requisitions();
$requisition_count = count($requisitions);

$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;
$processed_count = 0;

foreach ($requisitions as $req) {
    if ($req['status'] === 'pending') {
        $pending_count++;
    } elseif ($req['status'] === 'approved') {
        $approved_count++;
    } elseif ($req['status'] === 'rejected') {
        $rejected_count++;
    } elseif ($req['status'] === 'processed') {
        $processed_count++;
    }
}

// Get inventory statistics
$inventory_items = get_inventory_items();
$inventory_count = count($inventory_items);

$low_stock_count = 0;
$inventory_value = 0;

foreach ($inventory_items as $item) {
    if ($item['stock_level'] <= $item['reorder_level']) {
        $low_stock_count++;
    }
    $inventory_value += $item['stock_level'] * $item['unit_price'];
}

// Get recent requisitions
$recent_requisitions = array_slice($requisitions, 0, 5);

// Get recent notifications
$notifications = get_user_notifications($_SESSION['user_id']);
$recent_notifications = array_slice($notifications, 0, 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bumbe School Resource Management System</title>
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
            <h1 class="h3 mb-0 text-gray-800">Bumbe School Admin Dashboard</h1>
            <a href="reports.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="bi bi-file-earmark-text"></i> Generate Report
            </a>
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
            
            <!-- Total Users Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card h-100 py-2 bg-gradient-info text-white">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Total School Users</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $user_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people stat-icon"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="admin_dashboard.php" class="text-white small stretched-link">Manage Users</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Low Stock Items Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card h-100 py-2 <?php echo ($low_stock_count > 0) ? 'bg-gradient-danger' : 'bg-gradient-warning'; ?> text-white">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Low Stock School Supplies</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $low_stock_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-exclamation-triangle stat-icon"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="inventory.php" class="text-white small stretched-link">View Inventory</a>
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
                        <h6 class="m-0 font-weight-bold">Recent Requisitions</h6>
                        <a href="view_requisitions.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_requisitions)): ?>
                            <p class="text-center">No requisitions found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Requester</th>
                                            <th>Department</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_requisitions as $req): ?>
                                            <?php 
                                                $requester = get_user_by_id($req['requester_id']);
                                                $department = get_department_by_id($req['department']);
                                            ?>
                                            <tr>
                                                <td><?php echo $req['requisition_id']; ?></td>
                                                <td><?php echo $requester ? $requester['full_name'] : 'Unknown'; ?></td>
                                                <td><?php echo $department ? $department['department_name'] : 'Unknown'; ?></td>
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
                
                <!-- System Overview Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold">System Overview</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>Total Requisitions</span>
                                <span class="font-weight-bold"><?php echo $requisition_count; ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>Inventory Items</span>
                                <span class="font-weight-bold"><?php echo $inventory_count; ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>Inventory Value</span>
                                <span class="font-weight-bold">KES <?php echo number_format($inventory_value, 2); ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="reports.php" class="btn btn-sm btn-outline-primary">View Detailed Reports</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
