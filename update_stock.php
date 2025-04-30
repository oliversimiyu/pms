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

// Initialize variables
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$item = null;
$success_message = '';
$error_message = '';

// Get all inventory items for selection if no specific item is provided
$inventory_items = get_inventory_items();

// If item_id is provided, get the specific item
if ($item_id > 0) {
    $item = get_inventory_item_by_id($item_id);
    if (!$item) {
        $error_message = "Item not found.";
        $item_id = 0;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update_item_id = intval($_POST['item_id'] ?? 0);
    $quantity_change = intval($_POST['quantity_change'] ?? 0);
    $action_type = $_POST['action_type'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate input
    if ($update_item_id <= 0) {
        $error_message = "Please select a valid inventory item.";
    } elseif ($action_type !== 'add' && $action_type !== 'remove') {
        $error_message = "Invalid action type.";
    } elseif ($quantity_change <= 0) {
        $error_message = "Quantity must be greater than zero.";
    } else {
        // Get the item to update
        $update_item = get_inventory_item_by_id($update_item_id);
        
        if (!$update_item) {
            $error_message = "Item not found.";
        } else {
            // Calculate new quantity
            $new_quantity = $update_item['quantity'];
            
            if ($action_type === 'add') {
                $new_quantity += $quantity_change;
            } else { // remove
                if ($quantity_change > $update_item['quantity']) {
                    $error_message = "Cannot remove more than the current stock level.";
                } else {
                    $new_quantity -= $quantity_change;
                }
            }
            
            // Update stock if no errors
            if (empty($error_message)) {
                // Update the item data
                $update_item['quantity'] = $new_quantity;
                $update_item['updated_at'] = date('Y-m-d H:i:s');
                
                if (update_inventory_item($update_item_id, $update_item)) {
                    // Create stock movement record
                    $movement_data = [
                        'item_id' => $update_item_id,
                        'action_type' => $action_type,
                        'quantity' => $quantity_change,
                        'notes' => $notes,
                        'performed_by' => $user_id,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // You could implement a function to record stock movements here
                    // record_stock_movement($movement_data);
                    
                    // Add notification for admins if stock is low
                    if ($new_quantity <= $update_item['reorder_level']) {
                        $admin_users = array_filter(get_users(), function($u) {
                            return $u['role'] === 'admin';
                        });
                        
                        foreach ($admin_users as $admin) {
                            add_notification($admin['user_id'], [
                                'title' => 'Low Stock Alert',
                                'message' => "Item '{$update_item['name']}' is low on stock ({$new_quantity} remaining).",
                                'type' => 'inventory',
                                'is_read' => false
                            ]);
                        }
                    }
                    
                    $action_text = $action_type === 'add' ? 'added to' : 'removed from';
                    $success_message = "Successfully {$action_text} inventory. New stock level: {$new_quantity}.";
                    
                    // Refresh the item data
                    $item = get_inventory_item_by_id($update_item_id);
                } else {
                    $error_message = "Failed to update inventory. Please try again.";
                }
            }
        }
    }
}

// Page title and styles
$page_title = 'Update Inventory Stock';
$page_styles = <<<HTML
<style>
    .stock-action-btn {
        width: 100%;
        margin-bottom: 10px;
    }
    .current-stock {
        font-size: 2rem;
        font-weight: bold;
        text-align: center;
        margin: 20px 0;
    }
    .low-stock {
        color: #dc3545;
    }
</style>
HTML;

include('includes/header.php');
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Update Inventory Stock</h5>
                    <?php if ($user_role === 'storekeeper'): ?>
                        <a href="storekeeper_dashboard.php" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="inventory.php" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Inventory
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$item): ?>
                        <!-- Item Selection Form -->
                        <form method="get" action="update_stock.php" class="mb-4">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="id" class="form-label">Select Item to Update</label>
                                        <select class="form-select" id="id" name="id" required>
                                            <option value="">Select an inventory item...</option>
                                            <?php foreach ($inventory_items as $inv_item): ?>
                                                <option value="<?php echo $inv_item['item_id']; ?>">
                                                    <?php echo htmlspecialchars($inv_item['name']); ?> - 
                                                    Current Stock: <?php echo $inv_item['quantity']; ?> 
                                                    (<?php echo htmlspecialchars($inv_item['category']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">Select Item</button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Item List Table -->
                        <div class="table-responsive mt-4">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Current Stock</th>
                                        <th>Reorder Level</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventory_items as $inv_item): ?>
                                        <tr class="<?php echo $inv_item['quantity'] <= $inv_item['reorder_level'] ? 'table-warning' : ''; ?>">
                                            <td><?php echo $inv_item['item_id']; ?></td>
                                            <td><?php echo htmlspecialchars($inv_item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($inv_item['category']); ?></td>
                                            <td>
                                                <?php if ($inv_item['quantity'] <= $inv_item['reorder_level']): ?>
                                                    <span class="text-danger fw-bold"><?php echo $inv_item['quantity']; ?></span>
                                                <?php else: ?>
                                                    <?php echo $inv_item['quantity']; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $inv_item['reorder_level']; ?></td>
                                            <td>
                                                <a href="update_stock.php?id=<?php echo $inv_item['item_id']; ?>" class="btn btn-sm btn-primary">
                                                    Update Stock
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <!-- Stock Update Form for Selected Item -->
                        <div class="row">
                            <div class="col-md-6 mx-auto">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?></p>
                                        <p><strong>Description:</strong> <?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></p>
                                        <p><strong>Unit Price:</strong> KES <?php echo number_format($item['unit_price'], 2); ?></p>
                                        <p><strong>Reorder Level:</strong> <?php echo $item['reorder_level']; ?></p>
                                        
                                        <div class="current-stock <?php echo $item['quantity'] <= $item['reorder_level'] ? 'low-stock' : ''; ?>">
                                            Current Stock: <?php echo $item['quantity']; ?>
                                        </div>
                                        
                                        <form method="post" action="update_stock.php">
                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label for="quantity_change" class="form-label">Quantity</label>
                                                <input type="number" class="form-control" id="quantity_change" name="quantity_change" min="1" value="1" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Action</label>
                                                <div class="d-flex gap-2">
                                                    <div class="form-check flex-grow-1">
                                                        <input class="form-check-input" type="radio" name="action_type" id="action_add" value="add" checked>
                                                        <label class="form-check-label" for="action_add">
                                                            Add to Stock
                                                        </label>
                                                    </div>
                                                    <div class="form-check flex-grow-1">
                                                        <input class="form-check-input" type="radio" name="action_type" id="action_remove" value="remove">
                                                        <label class="form-check-label" for="action_remove">
                                                            Remove from Stock
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="notes" class="form-label">Notes</label>
                                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Enter reason for stock change or additional notes"></textarea>
                                            </div>
                                            
                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-primary">Update Stock</button>
                                                <a href="update_stock.php" class="btn btn-secondary">Select Different Item</a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$page_scripts = <<<HTML
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add any JavaScript functionality here
    });
</script>
HTML;
include('includes/footer.php');
?>
