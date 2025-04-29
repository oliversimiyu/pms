<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// All logged-in users can create requisitions

// Get user information
$user = get_user_by_id($_SESSION['user_id']);
$departments = get_departments();

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get form data
    $department_id = intval($_POST['department']);
    $justification = htmlspecialchars($_POST['justification']);
    $items = $_POST['items']; // Array of items
    
    // Create requisition data
    $requisition_data = [
        'requester_id' => $_SESSION['user_id'],
        'department' => $department_id,
        'justification' => $justification,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'items' => []
    ];
    
    // Add items to requisition
    foreach ($items as $item) {
        if (!empty($item['name']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
            $requisition_data['items'][] = [
                'item_name' => htmlspecialchars($item['name']),
                'quantity' => intval($item['quantity']),
                'unit_price' => floatval($item['unit_price']),
                'total_price' => intval($item['quantity']) * floatval($item['unit_price'])
            ];
        }
    }
    
    // Save requisition
    if (add_requisition($requisition_data)) {
        $success_message = "Requisition created successfully!";
    } else {
        $error_message = "Error creating requisition.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Requisition - Purchase Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php require 'nav.php'; ?>
    
    <div class="container mt-4">
        <h2>Create New Requisition</h2>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="mb-3">
                <label for="department" class="form-label">Department</label>
                <select class="form-select" id="department" name="department" required>
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="justification" class="form-label">Justification</label>
                <textarea class="form-control" id="justification" name="justification" rows="3" required></textarea>
            </div>
            
            <h4>Items</h4>
            <div id="items-container">
                <div class="row item-row mb-2">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="items[0][name]" placeholder="Item Name" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" name="items[0][quantity]" placeholder="Quantity" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control" name="items[0][unit_price]" placeholder="Unit Price" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-item">Remove</button>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <button type="button" class="btn btn-secondary" id="add-item">Add Another Item</button>
            </div>
            
            <button type="submit" class="btn btn-primary">Submit Requisition</button>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add item
            document.getElementById('add-item').addEventListener('click', function() {
                const container = document.getElementById('items-container');
                const itemCount = container.querySelectorAll('.item-row').length;
                
                const newRow = document.createElement('div');
                newRow.className = 'row item-row mb-2';
                newRow.innerHTML = `
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="items[${itemCount}][name]" placeholder="Item Name" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" name="items[${itemCount}][quantity]" placeholder="Quantity" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control" name="items[${itemCount}][unit_price]" placeholder="Unit Price" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-item">Remove</button>
                    </div>
                `;
                
                container.appendChild(newRow);
                
                // Add event listener to the new remove button
                newRow.querySelector('.remove-item').addEventListener('click', function() {
                    container.removeChild(newRow);
                });
            });
            
            // Remove item (for initial row)
            document.querySelector('.remove-item').addEventListener('click', function() {
                const container = document.getElementById('items-container');
                if (container.querySelectorAll('.item-row').length > 1) {
                    this.closest('.item-row').remove();
                }
            });
        });
    </script>
</body>
</html>
