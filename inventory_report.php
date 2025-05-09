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

// Get all inventory items
$inventory_items = get_inventory_items();

// Filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get unique categories for filter dropdown
$categories = [];
foreach ($inventory_items as $item) {
    if (!empty($item['category']) && !in_array($item['category'], $categories)) {
        $categories[] = $item['category'];
    }
}
sort($categories);

// Apply filters
$filtered_items = $inventory_items;

if (!empty($category_filter)) {
    $filtered_items = array_filter($filtered_items, function($item) use ($category_filter) {
        return $item['category'] === $category_filter;
    });
}

if (!empty($status_filter)) {
    $filtered_items = array_filter($filtered_items, function($item) use ($status_filter) {
        if ($status_filter === 'low') {
            return $item['quantity'] <= $item['reorder_level'];
        } elseif ($status_filter === 'out') {
            return $item['quantity'] === 0;
        } elseif ($status_filter === 'in') {
            return $item['quantity'] > 0;
        }
        return true;
    });
}

// Calculate report statistics
$total_items = count($filtered_items);
$total_value = 0;
$low_stock_count = 0;
$out_of_stock_count = 0;

foreach ($filtered_items as $item) {
    $total_value += $item['quantity'] * $item['unit_price'];
    
    if ($item['quantity'] === 0) {
        $out_of_stock_count++;
    } elseif ($item['quantity'] <= $item['reorder_level']) {
        $low_stock_count++;
    }
}

// Page title and styles
$page_title = 'Inventory Report';
$page_styles = <<<HTML
<style>
    .report-header {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .report-stat {
        text-align: center;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 10px;
    }
    .report-stat h3 {
        margin: 0;
        font-size: 1.5rem;
    }
    .report-stat p {
        margin: 0;
        font-size: 0.9rem;
        color: #6c757d;
    }
    .report-actions {
        margin-top: 20px;
    }
    .print-button {
        margin-right: 10px;
    }
    @media print {
        .no-print {
            display: none !important;
        }
        .container {
            width: 100%;
            max-width: 100%;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .table {
            width: 100% !important;
        }
    }
</style>
HTML;

include('includes/header.php');
?>

<div class="container mt-4" id="report-container">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Inventory Report</h5>
            <div class="no-print">
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
        </div>
        <div class="card-body">
            <!-- Report Header -->
            <div class="report-header">
                <div class="row">
                    <div class="col-md-6">
                        <h4>BTTI Inventory Report</h4>
                        <p>Generated on: <?php echo date('F d, Y h:i A'); ?></p>
                        <p>Generated by: <?php echo htmlspecialchars($user['full_name']); ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <?php if (!empty($category_filter)): ?>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($category_filter); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($status_filter)): ?>
                            <p><strong>Status:</strong> 
                                <?php 
                                    if ($status_filter === 'low') echo 'Low Stock';
                                    elseif ($status_filter === 'out') echo 'Out of Stock';
                                    elseif ($status_filter === 'in') echo 'In Stock';
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Report Filters -->
            <div class="row mb-4 no-print">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="get" action="inventory_report.php" class="row g-3">
                                <div class="col-md-4">
                                    <label for="category" class="form-label">Filter by Category</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter === $category ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Filter by Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">All Items</option>
                                        <option value="in" <?php echo $status_filter === 'in' ? 'selected' : ''; ?>>In Stock</option>
                                        <option value="low" <?php echo $status_filter === 'low' ? 'selected' : ''; ?>>Low Stock</option>
                                        <option value="out" <?php echo $status_filter === 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                                    <a href="inventory_report.php" class="btn btn-secondary">Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Report Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="report-stat bg-primary text-white">
                        <h3><?php echo $total_items; ?></h3>
                        <p>Total Items</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="report-stat bg-success text-white">
                        <h3>KES <?php echo number_format($total_value, 2); ?></h3>
                        <p>Total Value</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="report-stat bg-warning text-dark">
                        <h3><?php echo $low_stock_count; ?></h3>
                        <p>Low Stock Items</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="report-stat bg-danger text-white">
                        <h3><?php echo $out_of_stock_count; ?></h3>
                        <p>Out of Stock Items</p>
                    </div>
                </div>
            </div>
            
            <!-- Report Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Unit Price (KES)</th>
                            <th>Total Value (KES)</th>
                            <th>Reorder Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filtered_items)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No items found matching the selected filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filtered_items as $item): ?>
                                <?php 
                                    $item_value = $item['quantity'] * $item['unit_price'];
                                    $status_class = '';
                                    $status_text = 'In Stock';
                                    
                                    if ($item['quantity'] === 0) {
                                        $status_class = 'table-danger';
                                        $status_text = 'Out of Stock';
                                    } elseif ($item['quantity'] <= $item['reorder_level']) {
                                        $status_class = 'table-warning';
                                        $status_text = 'Low Stock';
                                    }
                                ?>
                                <tr class="<?php echo $status_class; ?>">
                                    <td><?php echo $item['item_id']; ?></td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td><?php echo number_format($item_value, 2); ?></td>
                                    <td><?php echo $item['reorder_level']; ?></td>
                                    <td><?php echo $status_text; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Report Actions -->
            <div class="report-actions text-end no-print">
                <button type="button" class="btn btn-primary print-button" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print Report
                </button>
                <button type="button" class="btn btn-success" onclick="exportToExcel()">
                    <i class="bi bi-file-earmark-excel"></i> Export to Excel
                </button>
            </div>
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
        
        // Add report header
        let reportHeader = document.querySelector('.report-header').innerText;
        
        // Format for Excel
        let uri = 'data:application/vnd.ms-excel;base64,';
        let template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>BTTI Inventory Report</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--><meta http-equiv="content-type" content="text/plain; charset=UTF-8"/></head><body><h3>BTTI Inventory Report</h3><p>' + reportHeader + '</p>' + html + '</body></html>';
        
        // Create download link
        let link = document.createElement('a');
        link.href = uri + window.btoa(unescape(encodeURIComponent(template)));
        link.download = 'BTTI_Inventory_Report_' + new Date().toISOString().slice(0, 10) + '.xls';
        link.click();
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Any additional JavaScript functionality
    });
</script>
HTML;
include('includes/footer.php');
?>
