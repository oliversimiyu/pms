<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in and is a storekeeper
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'storekeeper') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = get_user_by_id($user_id);

// Get inventory statistics
$inventory_items = get_inventory_items();
$total_items = count($inventory_items);

// Count items by category
$items_by_category = [];
$low_stock_items = [];
$total_value = 0;

foreach ($inventory_items as $item) {
    // Count by category
    if (!isset($items_by_category[$item['category']])) {
        $items_by_category[$item['category']] = 0;
    }
    $items_by_category[$item['category']]++;
    
    // Calculate total value
    $item_value = $item['quantity'] * $item['unit_price'];
    $total_value += $item_value;
    
    // Check for low stock
    if ($item['quantity'] <= $item['reorder_level']) {
        $low_stock_items[] = $item;
    }
}

// Get recent inventory activities (last 5)
$recent_activities = array_slice($inventory_items, 0, 5);

// Get unread notifications
$notifications = get_user_notifications($user_id);
$unread_notifications = array_filter($notifications, function($notification) {
    return !$notification['is_read'];
});
$recent_notifications = array_slice($notifications, 0, 5);

// Get unread messages count
$unread_messages_count = get_unread_message_count($user_id);

// Page title and styles
$page_title = 'Storekeeper Dashboard';
$page_styles = <<<HTML
<style>
    .stat-card {
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .low-stock {
        color: #dc3545;
        font-weight: bold;
    }
    .activity-item {
        border-left: 3px solid #0d6efd;
        padding-left: 1rem;
        margin-bottom: 1rem;
    }
    .activity-time {
        font-size: 0.8rem;
        color: #6c757d;
    }
</style>
HTML;

include('includes/header.php');
?>

<div class="container mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">BTTI Storekeeper Dashboard</h1>
        <div>
            <a href="add_inventory.php" class="btn btn-sm btn-success shadow-sm">
                <i class="bi bi-plus-circle"></i> Add New Item
            </a>
            <a href="inventory.php" class="btn btn-sm btn-primary shadow-sm">
                <i class="bi bi-box-seam"></i> View Full Inventory
            </a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary stat-card h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Inventory Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_items; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-box-seam fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success stat-card h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Inventory Value</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">KES <?php echo number_format($total_value, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning stat-card h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Low Stock Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($low_stock_items); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info stat-card h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Categories</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($items_by_category); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-tags fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Low Stock Items -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Low Stock Items</h6>
                    <a href="inventory.php?filter=low_stock" class="btn btn-sm btn-warning">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($low_stock_items)): ?>
                        <p class="text-center">No low stock items found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Current Stock</th>
                                        <th>Reorder Level</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                                            <td class="low-stock"><?php echo $item['quantity']; ?></td>
                                            <td><?php echo $item['reorder_level']; ?></td>
                                            <td>
                                                <a href="update_stock.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-plus-circle"></i> Update Stock
                                                </a>
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
        
        <!-- Recent Activities -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Recent Inventory Activities</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_activities)): ?>
                        <p class="text-center">No recent activities found.</p>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <p class="mb-1">
                                    <strong><?php echo htmlspecialchars($activity['name']); ?></strong> - 
                                    <?php echo htmlspecialchars($activity['category']); ?>
                                </p>
                                <p class="mb-0">
                                    Current Stock: <?php echo $activity['quantity']; ?> units
                                    (KES <?php echo number_format($activity['unit_price'], 2); ?> each)
                                </p>
                                <p class="activity-time">
                                    Last updated: <?php echo date('M d, Y', strtotime($activity['updated_at'] ?? $activity['created_at'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
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
                            <a href="add_inventory.php" class="btn btn-success btn-block w-100">
                                <i class="bi bi-plus-circle"></i> Add New Item
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="update_stock.php" class="btn btn-primary btn-block w-100">
                                <i class="bi bi-arrow-repeat"></i> Update Stock Levels
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="inventory_report.php" class="btn btn-info btn-block w-100">
                                <i class="bi bi-file-earmark-text"></i> Generate Report
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="messages.php" class="btn btn-secondary btn-block w-100">
                                <i class="bi bi-chat-dots"></i> Messages
                                <?php if ($unread_messages_count > 0): ?>
                                    <span class="badge bg-danger ms-1"><?php echo $unread_messages_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$page_scripts = <<<HTML
<script>
    // Any dashboard-specific JavaScript can go here
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize any dashboard components
    });
</script>
HTML;
include('includes/footer.php');
?>
