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

// Only admin and procurement roles can manage inventory
$can_manage = ($user_role === 'admin' || $user_role === 'procurement');

// Handle form submission for adding/updating inventory item
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && $can_manage) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Add new inventory item
            $item_data = [
                'item_name' => htmlspecialchars($_POST['item_name']),
                'category' => htmlspecialchars($_POST['category']),
                'unit' => htmlspecialchars($_POST['unit']),
                'stock_level' => intval($_POST['stock_level']),
                'reorder_level' => intval($_POST['reorder_level']),
                'unit_price' => floatval($_POST['unit_price'])
            ];
            
            if (add_inventory_item($item_data)) {
                $success_message = "Inventory item added successfully!";
            } else {
                $error_message = "Error adding inventory item.";
            }
        } elseif ($_POST['action'] === 'update' && isset($_POST['item_id'])) {
            // Update existing inventory item
            $item_id = intval($_POST['item_id']);
            $item = get_inventory_item_by_id($item_id);
            
            if ($item) {
                $item_data = [
                    'item_name' => htmlspecialchars($_POST['item_name']),
                    'category' => htmlspecialchars($_POST['category']),
                    'unit' => htmlspecialchars($_POST['unit']),
                    'stock_level' => intval($_POST['stock_level']),
                    'reorder_level' => intval($_POST['reorder_level']),
                    'unit_price' => floatval($_POST['unit_price'])
                ];
                
                if (update_inventory_item($item_id, $item_data)) {
                    $success_message = "Inventory item updated successfully!";
                } else {
                    $error_message = "Error updating inventory item.";
                }
            } else {
                $error_message = "Invalid inventory item.";
            }
        } elseif ($_POST['action'] === 'adjust' && isset($_POST['item_id'])) {
            // Adjust stock level
            $item_id = intval($_POST['item_id']);
            $quantity_change = intval($_POST['quantity_change']);
            
            if (update_stock_level($item_id, $quantity_change)) {
                $success_message = "Stock level adjusted successfully!";
            } else {
                $error_message = "Error adjusting stock level.";
            }
        }
    }
}

// Get all inventory items
$inventory = get_inventory_items();

// Filter by category if specified
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';
if (!empty($selected_category)) {
    $filtered_inventory = [];
    foreach ($inventory as $item) {
        if ($item['category'] === $selected_category) {
            $filtered_inventory[] = $item;
        }
    }
    $inventory = $filtered_inventory;
}

// Group items by category for easier display
$categories = [];
foreach ($inventory as $item) {
    if (!isset($categories[$item['category']])) {
        $categories[$item['category']] = [];
    }
    $categories[$item['category']][] = $item;
}

// Get all unique categories for the filter dropdown
$all_categories = [];
foreach ($inventory as $item) {
    if (!in_array($item['category'], $all_categories)) {
        $all_categories[] = $item['category'];
    }
}
sort($all_categories);

// Pagination for each category
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// If we're showing all categories, we'll paginate the categories themselves
// If we're filtering by a specific category, we'll paginate the items in that category
if (empty($selected_category)) {
    $total_categories = count($categories);
    $total_pages = ceil($total_categories / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $items_per_page;
    
    // Get only the categories for the current page
    $paginated_categories = array_slice($categories, $offset, $items_per_page, true);
} else {
    // We're only showing one category, so we paginate its items
    if (isset($categories[$selected_category])) {
        $total_items = count($categories[$selected_category]);
        $total_pages = ceil($total_items / $items_per_page);
        $current_page = max(1, min($current_page, $total_pages));
        $offset = ($current_page - 1) * $items_per_page;
        
        // Paginate the items in this category
        $categories[$selected_category] = array_slice($categories[$selected_category], $offset, $items_per_page);
        $paginated_categories = [$selected_category => $categories[$selected_category]];
    } else {
        $paginated_categories = [];
        $total_pages = 1;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Supplies Inventory - Bumbe Technical Training Institute (BTTI) Resource Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .low-stock {
            color: #DC3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php require 'nav.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mt-4">BTTI Supplies Inventory</h1>
            <?php if ($can_manage): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                    Add New Item
                </button>
            <?php endif; ?>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Category Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label for="category" class="form-label">Filter by Category</label>
                        <select name="category" id="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($all_categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selected_category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                    <?php if (!empty($selected_category)): ?>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="inventory.php" class="btn btn-secondary">Clear Filter</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <?php if (empty($paginated_categories)): ?>
            <div class="alert alert-info">No inventory items found.</div>
        <?php else: ?>
            <?php foreach ($paginated_categories as $category => $items): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><?php echo htmlspecialchars($category); ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Unit</th>
                                        <th>Stock Level</th>
                                        <th>Reorder Level</th>
                                        <th>Unit Price</th>
                                        <?php if ($can_manage): ?>
                                            <th>Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                            <td class="<?php echo ($item['stock_level'] <= $item['reorder_level']) ? 'low-stock' : ''; ?>">
                                                <?php echo $item['stock_level']; ?>
                                                <?php if ($item['stock_level'] <= $item['reorder_level']): ?>
                                                    <span class="badge bg-danger">Low</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $item['reorder_level']; ?></td>
                                            <td>KES <?php echo number_format($item['unit_price'], 2); ?></td>
                                            <?php if ($can_manage): ?>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-item" 
                                                            data-item-id="<?php echo $item['item_id']; ?>"
                                                            data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                                            data-category="<?php echo htmlspecialchars($item['category']); ?>"
                                                            data-unit="<?php echo htmlspecialchars($item['unit']); ?>"
                                                            data-stock-level="<?php echo $item['stock_level']; ?>"
                                                            data-reorder-level="<?php echo $item['reorder_level']; ?>"
                                                            data-unit-price="<?php echo $item['unit_price']; ?>">
                                                        Edit
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success adjust-stock" 
                                                            data-item-id="<?php echo $item['item_id']; ?>"
                                                            data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                                            data-stock-level="<?php echo $item['stock_level']; ?>">
                                                        Adjust Stock
                                                    </button>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Inventory pagination" class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($selected_category) ? '&category=' . urlencode($selected_category) : ''; ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($selected_category) ? '&category=' . urlencode($selected_category) : ''; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            
            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($selected_category) ? '&category=' . urlencode($selected_category) : ''; ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
    
    <?php if ($can_manage): ?>
        <!-- Add Item Modal -->
        <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="modal-header">
                            <h5 class="modal-title" id="addItemModalLabel">Add New Inventory Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="item_name" class="form-label">Item Name</label>
                                <input type="text" class="form-control" id="item_name" name="item_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <input type="text" class="form-control" id="category" name="category" required>
                            </div>
                            <div class="mb-3">
                                <label for="unit" class="form-label">Unit (e.g., piece, box, ream)</label>
                                <input type="text" class="form-control" id="unit" name="unit" required>
                            </div>
                            <div class="mb-3">
                                <label for="stock_level" class="form-label">Initial Stock Level</label>
                                <input type="number" class="form-control" id="stock_level" name="stock_level" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label for="reorder_level" class="form-label">Reorder Level</label>
                                <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label for="unit_price" class="form-label">Unit Price ($)</label>
                                <input type="number" class="form-control" id="unit_price" name="unit_price" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Edit Item Modal -->
        <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="item_id" id="edit_item_id">
                        
                        <div class="modal-header">
                            <h5 class="modal-title" id="editItemModalLabel">Edit Inventory Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit_item_name" class="form-label">Item Name</label>
                                <input type="text" class="form-control" id="edit_item_name" name="item_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_category" class="form-label">Category</label>
                                <input type="text" class="form-control" id="edit_category" name="category" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_unit" class="form-label">Unit (e.g., piece, box, ream)</label>
                                <input type="text" class="form-control" id="edit_unit" name="unit" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_stock_level" class="form-label">Stock Level</label>
                                <input type="number" class="form-control" id="edit_stock_level" name="stock_level" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_reorder_level" class="form-label">Reorder Level</label>
                                <input type="number" class="form-control" id="edit_reorder_level" name="reorder_level" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_unit_price" class="form-label">Unit Price ($)</label>
                                <input type="number" class="form-control" id="edit_unit_price" name="unit_price" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Adjust Stock Modal -->
        <div class="modal fade" id="adjustStockModal" tabindex="-1" aria-labelledby="adjustStockModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="adjust">
                        <input type="hidden" name="item_id" id="adjust_item_id">
                        
                        <div class="modal-header">
                            <h5 class="modal-title" id="adjustStockModalLabel">Adjust Stock Level</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Current stock level: <span id="current_stock_level"></span></p>
                            
                            <div class="mb-3">
                                <label for="quantity_change" class="form-label">Quantity Change</label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary" id="decrease_btn">-</button>
                                    <input type="number" class="form-control" id="quantity_change" name="quantity_change" value="0" required>
                                    <button type="button" class="btn btn-outline-secondary" id="increase_btn">+</button>
                                </div>
                                <div class="form-text">Use positive values to add stock, negative values to remove stock.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="adjustment_reason" class="form-label">Reason for Adjustment</label>
                                <textarea class="form-control" id="adjustment_reason" name="adjustment_reason" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit item
            const editButtons = document.querySelectorAll('.edit-item');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-item-id');
                    const itemName = this.getAttribute('data-item-name');
                    const category = this.getAttribute('data-category');
                    const unit = this.getAttribute('data-unit');
                    const stockLevel = this.getAttribute('data-stock-level');
                    const reorderLevel = this.getAttribute('data-reorder-level');
                    const unitPrice = this.getAttribute('data-unit-price');
                    
                    document.getElementById('edit_item_id').value = itemId;
                    document.getElementById('edit_item_name').value = itemName;
                    document.getElementById('edit_category').value = category;
                    document.getElementById('edit_unit').value = unit;
                    document.getElementById('edit_stock_level').value = stockLevel;
                    document.getElementById('edit_reorder_level').value = reorderLevel;
                    document.getElementById('edit_unit_price').value = unitPrice;
                    
                    const editModal = new bootstrap.Modal(document.getElementById('editItemModal'));
                    editModal.show();
                });
            });
            
            // Adjust stock
            const adjustButtons = document.querySelectorAll('.adjust-stock');
            adjustButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-item-id');
                    const itemName = this.getAttribute('data-item-name');
                    const stockLevel = this.getAttribute('data-stock-level');
                    
                    document.getElementById('adjust_item_id').value = itemId;
                    document.getElementById('adjustStockModalLabel').textContent = 'Adjust Stock: ' + itemName;
                    document.getElementById('current_stock_level').textContent = stockLevel;
                    document.getElementById('quantity_change').value = 0;
                    
                    const adjustModal = new bootstrap.Modal(document.getElementById('adjustStockModal'));
                    adjustModal.show();
                });
            });
            
            // Increase/decrease buttons for stock adjustment
            document.getElementById('increase_btn').addEventListener('click', function() {
                const input = document.getElementById('quantity_change');
                input.value = parseInt(input.value) + 1;
            });
            
            document.getElementById('decrease_btn').addEventListener('click', function() {
                const input = document.getElementById('quantity_change');
                input.value = parseInt(input.value) - 1;
            });
        });
    </script>
</body>
</html>
