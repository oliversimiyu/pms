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

// Redirect non-staff users
if ($user_role !== 'staff') {
    header("Location: index.php");
    exit();
}

// Get department information
$department_id = $user['department'];
$department = get_department_by_id($department_id);

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
usort($user_requisitions, function($a, $b) {
    return strtotime($b['date_created']) - strtotime($a['date_created']);
});
$recent_requisitions = array_slice($user_requisitions, 0, 5);

// Get user's notifications
$notifications = get_user_notifications($user_id);
$unread_count = 0;
foreach ($notifications as $notification) {
    if (!$notification['is_read']) {
        $unread_count++;
    }
}
$recent_notifications = array_slice($notifications, 0, 5);

// Get department inventory items
$inventory_items = get_inventory_items();
$department_inventory = [];

foreach ($inventory_items as $item) {
    if ($item['department'] == $department_id) {
        $department_inventory[] = $item;
    }
}
$recent_inventory = array_slice($department_inventory, 0, 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Bumbe Technical Training Institute (BTTI) REQUISITION MANAGEMENT SYSTEM</title>
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
    </style>
</head>
<body>
    <?php require 'nav.php'; ?>
    
    <div class="container mt-4">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">BTTI Staff Dashboard</h1>
            <a href="create_requisition.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="bi bi-plus-circle"></i> New Resource Request
            </a>
        </div>
        
        <!-- Staff Info Card -->
        <div class="card mb-4 shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">Staff Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($department['name']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Total Requests:</strong> <?php echo count($user_requisitions); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Staff Statistics -->
        <div class="row">
            <!-- Pending Requisitions Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card h-100 py-2 bg-gradient-primary text-white">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Pending Requests</div>
                                <div class="h5 mb-0 font-weight-bold text-white"><?php echo $pending_count; ?></div>
                                <div class="mt-2 mb-0 text-white">
                                    <a href="view_requisitions.php?status=pending&user=<?php echo $user_id; ?>" class="text-white">View all</a>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-hourglass-split stat-icon"></i>
                            </div>
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
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Approved Requests</div>
                                <div class="h5 mb-0 font-weight-bold text-white"><?php echo $approved_count; ?></div>
                                <div class="mt-2 mb-0 text-white">
                                    <a href="view_requisitions.php?status=approved&user=<?php echo $user_id; ?>" class="text-white">View all</a>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-check-circle stat-icon"></i>
                            </div>
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
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Total Requested</div>
                                <div class="h5 mb-0 font-weight-bold text-white">KES <?php echo number_format($total_amount, 2); ?></div>
                                <div class="mt-2 mb-0 text-white">
                                    <span>Year to date</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-currency-dollar stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card h-100 py-2 bg-gradient-warning text-white">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Unread Notifications</div>
                                <div class="h5 mb-0 font-weight-bold text-white"><?php echo $unread_count; ?></div>
                                <div class="mt-2 mb-0 text-white">
                                    <a href="notifications.php" class="text-white">View all</a>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-bell stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Requisitions -->
            <div class="col-lg-7 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold">My Recent Requests</h6>
                        <a href="view_requisitions.php?user=<?php echo $user_id; ?>" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_requisitions)): ?>
                            <p class="text-center">No recent resource requests found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Total</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_requisitions as $req): ?>
                                            <?php 
                                            $req_total = 0;
                                            foreach ($req['items'] as $item) {
                                                $req_total += $item['total_price'];
                                            }
                                            ?>
                                            <tr>
                                                <td><?php echo $req['requisition_id']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($req['date_created'])); ?></td>
                                                <td>
                                                    <?php if ($req['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning">Pending</span>
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
                                                    <a href="requisition_details.php?id=<?php echo $req['requisition_id']; ?>" class="btn btn-sm btn-primary">View</a>
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
                                    <a href="notifications.php?id=<?php echo $notification['notification_id']; ?>" class="list-group-item list-group-item-action <?php echo !$notification['is_read'] ? 'bg-light' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <small><?php echo date('M d, Y', strtotime($notification['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <a href="create_requisition.php" class="btn btn-success btn-block w-100">
                                    <i class="bi bi-plus-circle"></i> New Request
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="view_requisitions.php?user=<?php echo $user_id; ?>" class="btn btn-primary btn-block w-100">
                                    <i class="bi bi-list-check"></i> My Requests
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="inventory.php?department=<?php echo $department_id; ?>" class="btn btn-info btn-block w-100">
                                    <i class="bi bi-box-seam"></i> View Inventory
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="notifications.php" class="btn btn-warning btn-block w-100">
                                    <i class="bi bi-bell"></i> Notifications
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Department Inventory Preview -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold">Department Inventory</h6>
                        <a href="inventory.php?department=<?php echo $department_id; ?>" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_inventory)): ?>
                            <p class="text-center">No inventory items found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_inventory as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo $item['stock_level']; ?></td>
                                                <td>
                                                    <?php if ($item['stock_level'] <= $item['reorder_level']): ?>
                                                        <span class="badge bg-danger">Low Stock</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">In Stock</span>
                                                    <?php endif; ?>
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
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
