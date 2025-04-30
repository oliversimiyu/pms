<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in and is a storekeeper or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'storekeeper' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user = get_user_by_id($user_id);

// Get item ID from query string if provided
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : null;
$item = null;

// If item_id is provided, get the specific item
if ($item_id) {
    $item = get_inventory_item_by_id($item_id);
    if (!$item) {
        $error_message = "Item not found.";
        $item_id = null;
    }
}

// Get all stock movements, filtered by item_id if provided
$stock_movements = get_stock_movements($item_id, 0); // 0 means no limit

// Get users for displaying names
$users = get_users();
$users_by_id = [];
foreach ($users as $u) {
    $users_by_id[$u['user_id']] = $u;
}

// Get all inventory items for filtering
$inventory_items = get_inventory_items();

// Page title and styles
$page_title = $item ? 'Stock History: ' . htmlspecialchars($item['name']) : 'Stock Movement History';
$page_styles = <<<HTML
<style>
    .movement-add {
        color: #198754;
    }
    .movement-remove {
        color: #dc3545;
    }
    .filter-form {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
</style>
HTML;

include('includes/header.php');
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php echo $page_title; ?></h5>
            <div>
                <?php if ($user_role === 'storekeeper'): ?>
                    <a href="storekeeper_dashboard.php" class="btn btn-sm btn-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                <?php else: ?>
                    <a href="inventory.php" class="btn btn-sm btn-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to Inventory
                    </a>
                <?php endif; ?>
                <a href="update_stock.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle"></i> Update Stock
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (!$item): ?>
                <!-- Filter Form -->
                <div class="filter-form">
                    <form method="get" action="stock_history.php" class="row g-3">
                        <div class="col-md-6">
                            <label for="item_id" class="form-label">Filter by Item</label>
                            <select class="form-select" id="item_id" name="item_id">
                                <option value="">All Items</option>
                                <?php foreach ($inventory_items as $inv_item): ?>
                                    <option value="<?php echo $inv_item['item_id']; ?>">
                                        <?php echo htmlspecialchars($inv_item['name']); ?> 
                                        (<?php echo htmlspecialchars($inv_item['category']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Apply Filter</button>
                            <a href="stock_history.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Item Details -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Item Details</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($item['name']); ?></p>
                                <p><strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?></p>
                                <p><strong>Current Stock:</strong> <?php echo $item['quantity']; ?></p>
                                <p><strong>Reorder Level:</strong> <?php echo $item['reorder_level']; ?></p>
                                <p><strong>Unit Price:</strong> KES <?php echo number_format($item['unit_price'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Stock Summary</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $total_added = 0;
                                $total_removed = 0;
                                
                                foreach ($stock_movements as $movement) {
                                    if ($movement['action_type'] === 'add') {
                                        $total_added += $movement['quantity'];
                                    } else {
                                        $total_removed += $movement['quantity'];
                                    }
                                }
                                ?>
                                <p><strong>Total Added:</strong> <?php echo $total_added; ?></p>
                                <p><strong>Total Removed:</strong> <?php echo $total_removed; ?></p>
                                <p><strong>Net Change:</strong> <?php echo $total_added - $total_removed; ?></p>
                                <p><strong>Total Movements:</strong> <?php echo count($stock_movements); ?></p>
                                <p><strong>Last Updated:</strong> <?php echo $item['updated_at'] ?? 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Stock Movements Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <?php if (!$item): ?>
                                <th>Item</th>
                                <th>Category</th>
                            <?php endif; ?>
                            <th>Date & Time</th>
                            <th>Action</th>
                            <th>Quantity</th>
                            <th>Performed By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stock_movements)): ?>
                            <tr>
                                <td colspan="<?php echo $item ? 5 : 7; ?>" class="text-center">No stock movements found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stock_movements as $movement): ?>
                                <?php 
                                    $action_class = $movement['action_type'] === 'add' ? 'movement-add' : 'movement-remove';
                                    $action_text = $movement['action_type'] === 'add' ? 'Added' : 'Removed';
                                    $action_icon = $movement['action_type'] === 'add' ? 'plus-circle' : 'dash-circle';
                                    
                                    // Get item details if not filtered by item
                                    $movement_item = $item;
                                    if (!$item) {
                                        foreach ($inventory_items as $inv_item) {
                                            if ($inv_item['item_id'] == $movement['item_id']) {
                                                $movement_item = $inv_item;
                                                break;
                                            }
                                        }
                                    }
                                    
                                    // Get user who performed the action
                                    $performed_by = 'Unknown';
                                    if (isset($movement['performed_by']) && isset($users_by_id[$movement['performed_by']])) {
                                        $performed_by = $users_by_id[$movement['performed_by']]['full_name'];
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $movement['movement_id']; ?></td>
                                    <?php if (!$item): ?>
                                        <td>
                                            <a href="stock_history.php?item_id=<?php echo $movement['item_id']; ?>">
                                                <?php echo htmlspecialchars($movement_item['name'] ?? 'Unknown Item'); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($movement_item['category'] ?? 'N/A'); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo $movement['created_at']; ?></td>
                                    <td class="<?php echo $action_class; ?>">
                                        <i class="bi bi-<?php echo $action_icon; ?>"></i> <?php echo $action_text; ?>
                                    </td>
                                    <td><?php echo $movement['quantity']; ?></td>
                                    <td><?php echo htmlspecialchars($performed_by); ?></td>
                                    <td><?php echo htmlspecialchars($movement['notes'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Export Button -->
            <?php if (!empty($stock_movements)): ?>
                <div class="text-end mt-3">
                    <button type="button" class="btn btn-success" onclick="exportToExcel()">
                        <i class="bi bi-file-earmark-excel"></i> Export to Excel
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$page_scripts = <<<HTML
<script>
    // Function to export table data to Excel
    function exportToExcel() {
        let table = document.querySelector('table');
        let html = table.outerHTML;
        
        // Format for Excel
        let uri = 'data:application/vnd.ms-excel;base64,';
        let template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Stock Movement History</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--><meta http-equiv="content-type" content="text/plain; charset=UTF-8"/></head><body>' + html + '</body></html>';
        
        // Create download link
        let link = document.createElement('a');
        link.href = uri + window.btoa(unescape(encodeURIComponent(template)));
        link.download = 'Stock_Movement_History_' + new Date().toISOString().slice(0, 10) + '.xls';
        link.click();
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Any additional JavaScript functionality
    });
</script>
HTML;
include('includes/footer.php');
?>
