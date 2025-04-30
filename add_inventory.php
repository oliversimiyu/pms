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

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $reorder_level = intval($_POST['reorder_level'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $supplier = trim($_POST['supplier'] ?? '');
    
    // Validate required fields
    if (empty($name) || empty($category) || $quantity < 0 || $unit_price <= 0) {
        $error_message = "Please fill in all required fields with valid values.";
    } else {
        // Create item data
        $item_data = [
            'name' => $name,
            'category' => $category,
            'description' => $description,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'reorder_level' => $reorder_level,
            'location' => $location,
            'supplier' => $supplier,
            'added_by' => $user_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Add item to inventory
        if (add_inventory_item($item_data)) {
            // Add activity log
            $log_message = "New item '{$name}' added to inventory by " . $user['full_name'];
            // You could implement an activity logging function here
            
            // Add notification for admins
            $admin_users = array_filter(get_users(), function($u) {
                return $u['role'] === 'admin';
            });
            
            foreach ($admin_users as $admin) {
                add_notification($admin['user_id'], [
                    'title' => 'New Inventory Item',
                    'message' => "New item '{$name}' has been added to inventory by " . $user['full_name'],
                    'type' => 'inventory',
                    'is_read' => false
                ]);
            }
            
            $success_message = "Item added successfully to inventory.";
            
            // Clear form data on success
            $name = $category = $description = '';
            $quantity = $unit_price = $reorder_level = 0;
            $location = $supplier = '';
        } else {
            $error_message = "Failed to add item to inventory. Please try again.";
        }
    }
}

// Get existing categories for dropdown
$inventory_items = get_inventory_items();
$categories = [];
foreach ($inventory_items as $item) {
    if (!empty($item['category']) && !in_array($item['category'], $categories)) {
        $categories[] = $item['category'];
    }
}
sort($categories);

// Page title and styles
$page_title = 'Add Inventory Item';
$page_styles = <<<HTML
<style>
    .required-field::after {
        content: "*";
        color: red;
        margin-left: 4px;
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
                    <h5 class="mb-0">Add New Inventory Item</h5>
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
                    
                    <form method="post" action="add_inventory.php">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label required-field">Item Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label required-field">Category</label>
                                <div class="input-group">
                                    <select class="form-select" id="category" name="category">
                                        <option value="">Select or type a category...</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category ?? '') === $cat ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" class="form-control d-none" id="new_category" placeholder="Enter new category">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleCategoryInput">New</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="quantity" class="form-label required-field">Initial Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo $quantity ?? 0; ?>" min="0" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="unit_price" class="form-label required-field">Unit Price (KES)</label>
                                <input type="number" class="form-control" id="unit_price" name="unit_price" value="<?php echo $unit_price ?? 0; ?>" min="0" step="0.01" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="reorder_level" class="form-label">Reorder Level</label>
                                <input type="number" class="form-control" id="reorder_level" name="reorder_level" value="<?php echo $reorder_level ?? 5; ?>" min="0">
                                <div class="form-text">Minimum stock level before reordering</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Storage Location</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($location ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="supplier" class="form-label">Supplier</label>
                                <input type="text" class="form-control" id="supplier" name="supplier" value="<?php echo htmlspecialchars($supplier ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex justify-content-between">
                            <button type="reset" class="btn btn-secondary">Reset Form</button>
                            <button type="submit" class="btn btn-primary">Add Item to Inventory</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$page_scripts = <<<HTML
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle custom category input toggle
        const categorySelect = document.getElementById('category');
        const newCategoryInput = document.getElementById('new_category');
        const toggleButton = document.getElementById('toggleCategoryInput');
        
        toggleButton.addEventListener('click', function() {
            if (categorySelect.classList.contains('d-none')) {
                // Switch back to select
                categorySelect.classList.remove('d-none');
                newCategoryInput.classList.add('d-none');
                toggleButton.textContent = 'New';
                
                // If user entered a new category, add it to select and select it
                if (newCategoryInput.value.trim() !== '') {
                    const newOption = document.createElement('option');
                    newOption.value = newCategoryInput.value.trim();
                    newOption.textContent = newCategoryInput.value.trim();
                    newOption.selected = true;
                    categorySelect.appendChild(newOption);
                }
            } else {
                // Switch to text input
                categorySelect.classList.add('d-none');
                newCategoryInput.classList.remove('d-none');
                newCategoryInput.focus();
                toggleButton.textContent = 'Select';
            }
        });
        
        // Handle form submission
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            // If new category input is visible, use its value
            if (!newCategoryInput.classList.contains('d-none') && newCategoryInput.value.trim() !== '') {
                categorySelect.value = newCategoryInput.value.trim();
            }
            
            // Validate required fields
            if (!categorySelect.value) {
                event.preventDefault();
                alert('Please select or enter a category');
            }
        });
    });
</script>
HTML;
include('includes/footer.php');
?>
