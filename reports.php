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

// Only admin, approver, and procurement roles can access reports
if ($user_role !== 'admin' && $user_role !== 'approver' && $user_role !== 'procurement') {
    header("Location: index.php");
    exit();
}

// Get report type from query string
$report_type = isset($_GET['type']) ? $_GET['type'] : 'requisition_status';

// Get date range for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get all requisitions
$requisitions = get_all_requisitions();

// Filter requisitions by date range
$filtered_requisitions = [];
foreach ($requisitions as $req) {
    $req_date = date('Y-m-d', strtotime($req['created_at']));
    if ($req_date >= $start_date && $req_date <= $end_date) {
        $filtered_requisitions[] = $req;
    }
}

// Prepare data for reports
$requisition_status_data = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'processed' => 0
];

$department_data = [];
$monthly_data = [];

foreach ($filtered_requisitions as $req) {
    // Requisition status report
    $status = $req['status'];
    $requisition_status_data[$status]++;
    
    // Department-wise report
    $dept_id = $req['department'];
    $department = get_department_by_id($dept_id);
    $dept_name = $department ? $department['department_name'] : 'Unknown';
    
    if (!isset($department_data[$dept_name])) {
        $department_data[$dept_name] = [
            'count' => 0,
            'total_amount' => 0
        ];
    }
    
    $department_data[$dept_name]['count']++;
    
    // Calculate total amount for this requisition
    $total_amount = 0;
    foreach ($req['items'] as $item) {
        $total_amount += $item['total_price'];
    }
    
    $department_data[$dept_name]['total_amount'] += $total_amount;
    
    // Monthly report
    $month = date('Y-m', strtotime($req['created_at']));
    if (!isset($monthly_data[$month])) {
        $monthly_data[$month] = [
            'count' => 0,
            'total_amount' => 0
        ];
    }
    
    $monthly_data[$month]['count']++;
    $monthly_data[$month]['total_amount'] += $total_amount;
}

// Sort monthly data by month
ksort($monthly_data);

// Get inventory data for inventory report
$inventory_items = get_inventory_items();

// Group inventory by category
$inventory_by_category = [];
foreach ($inventory_items as $item) {
    $category = $item['category'];
    if (!isset($inventory_by_category[$category])) {
        $inventory_by_category[$category] = [
            'count' => 0,
            'total_value' => 0,
            'low_stock' => 0
        ];
    }
    
    $inventory_by_category[$category]['count']++;
    $inventory_by_category[$category]['total_value'] += $item['stock_level'] * $item['unit_price'];
    
    if ($item['stock_level'] <= $item['reorder_level']) {
        $inventory_by_category[$category]['low_stock']++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Reports - School Resource Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php require 'nav.php'; ?>
    
    <div class="container mt-4">
        <h1 class="mt-4">School Reports & Analytics</h1>
        
        <!-- Report Type Selection -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="" method="get" class="row g-3">
                    <div class="col-md-4">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="type" onchange="this.form.submit()">
                            <option value="requisition_status" <?php echo ($report_type === 'requisition_status') ? 'selected' : ''; ?>>Requisition Status</option>
                            <option value="department_wise" <?php echo ($report_type === 'department_wise') ? 'selected' : ''; ?>>Department-wise Resource Requests</option>
                            <option value="monthly_trend" <?php echo ($report_type === 'monthly_trend') ? 'selected' : ''; ?>>Monthly School Resource Trends</option>
                            <option value="inventory" <?php echo ($report_type === 'inventory') ? 'selected' : ''; ?>>School Supplies Status</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Report Content -->
        <?php if ($report_type === 'requisition_status'): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Requisition Status Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="requisitionStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Requisition Status Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_requisitions = array_sum($requisition_status_data);
                                        foreach ($requisition_status_data as $status => $count): 
                                            $percentage = ($total_requisitions > 0) ? round(($count / $total_requisitions) * 100, 2) : 0;
                                        ?>
                                            <tr>
                                                <td><?php echo ucfirst($status); ?></td>
                                                <td><?php echo $count; ?></td>
                                                <td><?php echo $percentage; ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-active">
                                            <td><strong>Total</strong></td>
                                            <td><strong><?php echo $total_requisitions; ?></strong></td>
                                            <td><strong>100%</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('requisitionStatusChart').getContext('2d');
                    const statusChart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: ['Pending', 'Approved', 'Rejected', 'Processed'],
                            datasets: [{
                                data: [
                                    <?php echo $requisition_status_data['pending']; ?>,
                                    <?php echo $requisition_status_data['approved']; ?>,
                                    <?php echo $requisition_status_data['rejected']; ?>,
                                    <?php echo $requisition_status_data['processed']; ?>
                                ],
                                backgroundColor: [
                                    '#FFC107', // Pending - Yellow
                                    '#28A745', // Approved - Green
                                    '#DC3545', // Rejected - Red
                                    '#17A2B8'  // Processed - Cyan
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                });
            </script>
            
        <?php elseif ($report_type === 'department_wise'): ?>
            <div class="row">
                <div class="col-md-7">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Department-wise Resource Requests</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="departmentChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Department-wise Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Department</th>
                                            <th>Count</th>
                                            <th>Total Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_count = 0;
                                        $total_amount = 0;
                                        foreach ($department_data as $dept => $data): 
                                            $total_count += $data['count'];
                                            $total_amount += $data['total_amount'];
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($dept); ?></td>
                                                <td><?php echo $data['count']; ?></td>
                                                <td>KES <?php echo number_format($data['total_amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-active">
                                            <td><strong>Total</strong></td>
                                            <td><strong><?php echo $total_count; ?></strong></td>
                                            <td><strong>KES <?php echo number_format($total_amount, 2); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('departmentChart').getContext('2d');
                    const deptChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: [<?php echo "'" . implode("', '", array_keys($department_data)) . "'"; ?>],
                            datasets: [{
                                label: 'Requisition Count',
                                data: [<?php echo implode(', ', array_column($department_data, 'count')); ?>],
                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }, {
                                label: 'Total Amount ($)',
                                data: [<?php echo implode(', ', array_column($department_data, 'total_amount')); ?>],
                                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 1,
                                type: 'line',
                                yAxisID: 'y1'
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Requisition Count'
                                    }
                                },
                                y1: {
                                    beginAtZero: true,
                                    position: 'right',
                                    title: {
                                        display: true,
                                        text: 'Total Amount ($)'
                                    },
                                    grid: {
                                        drawOnChartArea: false
                                    }
                                }
                            }
                        }
                    });
                });
            </script>
            
        <?php elseif ($report_type === 'monthly_trend'): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Monthly School Resource Trends</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Monthly Requisition Data</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Requisition Count</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_count = 0;
                                $total_amount = 0;
                                foreach ($monthly_data as $month => $data): 
                                    $total_count += $data['count'];
                                    $total_amount += $data['total_amount'];
                                ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($month . '-01')); ?></td>
                                        <td><?php echo $data['count']; ?></td>
                                        <td>KES <?php echo number_format($data['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-active">
                                    <td><strong>Total</strong></td>
                                    <td><strong><?php echo $total_count; ?></strong></td>
                                    <td><strong>KES <?php echo number_format($total_amount, 2); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
                    const monthlyChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: [
                                <?php 
                                $formatted_months = [];
                                foreach (array_keys($monthly_data) as $month) {
                                    $formatted_months[] = "'" . date('M Y', strtotime($month . '-01')) . "'";
                                }
                                echo implode(', ', $formatted_months);
                                ?>
                            ],
                            datasets: [{
                                label: 'Requisition Count',
                                data: [<?php echo implode(', ', array_column($monthly_data, 'count')); ?>],
                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 2,
                                tension: 0.1
                            }, {
                                label: 'Total Amount ($)',
                                data: [<?php echo implode(', ', array_column($monthly_data, 'total_amount')); ?>],
                                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 2,
                                tension: 0.1,
                                yAxisID: 'y1'
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Requisition Count'
                                    }
                                },
                                y1: {
                                    beginAtZero: true,
                                    position: 'right',
                                    title: {
                                        display: true,
                                        text: 'Total Amount ($)'
                                    },
                                    grid: {
                                        drawOnChartArea: false
                                    }
                                }
                            }
                        }
                    });
                });
            </script>
            
        <?php elseif ($report_type === 'inventory'): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">School Supplies Status</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="inventoryCategoryChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Low Stock Items by Category</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="lowStockChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Inventory Summary by Category</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Item Count</th>
                                    <th>Low Stock Items</th>
                                    <th>Total Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_count = 0;
                                $total_low_stock = 0;
                                $total_value = 0;
                                foreach ($inventory_by_category as $category => $data): 
                                    $total_count += $data['count'];
                                    $total_low_stock += $data['low_stock'];
                                    $total_value += $data['total_value'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category); ?></td>
                                        <td><?php echo $data['count']; ?></td>
                                        <td>
                                            <?php echo $data['low_stock']; ?>
                                            <?php if ($data['low_stock'] > 0): ?>
                                                <span class="badge bg-danger">Low</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>KES <?php echo number_format($data['total_value'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-active">
                                    <td><strong>Total</strong></td>
                                    <td><strong><?php echo $total_count; ?></strong></td>
                                    <td><strong><?php echo $total_low_stock; ?></strong></td>
                                    <td><strong>KES <?php echo number_format($total_value, 2); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Inventory by Category Chart
                    const ctxCategory = document.getElementById('inventoryCategoryChart').getContext('2d');
                    const categoryChart = new Chart(ctxCategory, {
                        type: 'pie',
                        data: {
                            labels: [<?php echo "'" . implode("', '", array_keys($inventory_by_category)) . "'"; ?>],
                            datasets: [{
                                data: [<?php echo implode(', ', array_column($inventory_by_category, 'count')); ?>],
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.7)',
                                    'rgba(54, 162, 235, 0.7)',
                                    'rgba(255, 206, 86, 0.7)',
                                    'rgba(75, 192, 192, 0.7)',
                                    'rgba(153, 102, 255, 0.7)',
                                    'rgba(255, 159, 64, 0.7)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                },
                                title: {
                                    display: true,
                                    text: 'Item Count by Category'
                                }
                            }
                        }
                    });
                    
                    // Low Stock Chart
                    const ctxLowStock = document.getElementById('lowStockChart').getContext('2d');
                    const lowStockChart = new Chart(ctxLowStock, {
                        type: 'bar',
                        data: {
                            labels: [<?php echo "'" . implode("', '", array_keys($inventory_by_category)) . "'"; ?>],
                            datasets: [{
                                label: 'Total Items',
                                data: [<?php echo implode(', ', array_column($inventory_by_category, 'count')); ?>],
                                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }, {
                                label: 'Low Stock Items',
                                data: [<?php echo implode(', ', array_column($inventory_by_category, 'low_stock')); ?>],
                                backgroundColor: 'rgba(255, 99, 132, 0.7)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Item Count'
                                    }
                                }
                            }
                        }
                    });
                });
            </script>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
