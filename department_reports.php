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

// Redirect non-HOD users
if ($user_role !== 'hod') {
    header("Location: index.php");
    exit();
}

// Get department information
$department_id = $user['department'];
$department = get_department_by_id($department_id);

// Get report type from query string
$report_type = isset($_GET['type']) ? $_GET['type'] : 'requisition_status';

// Get date range for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get all requisitions
$all_requisitions = get_all_requisitions();
$department_requisitions = [];

// Filter requisitions by department
foreach ($all_requisitions as $req) {
    $req_user = get_user_by_id($req['user_id']);
    if ($req_user && $req_user['department'] == $department_id) {
        $department_requisitions[] = $req;
    }
}

// Filter requisitions by date range
$filtered_requisitions = [];
foreach ($department_requisitions as $req) {
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

$requester_data = [];
$monthly_data = [];
$category_data = [];
$total_amount = 0;

foreach ($filtered_requisitions as $req) {
    // Requisition status report
    $status = $req['status'];
    $requisition_status_data[$status]++;
    
    // Requester report
    $requester = get_user_by_id($req['user_id']);
    $requester_name = $requester ? $requester['full_name'] : 'Unknown';
    
    if (!isset($requester_data[$requester_name])) {
        $requester_data[$requester_name] = [
            'count' => 0,
            'amount' => 0
        ];
    }
    
    $requester_data[$requester_name]['count']++;
    
    // Calculate total amount for this requisition
    $req_total = 0;
    foreach ($req['items'] as $item) {
        $req_total += $item['total_price'];
        
        // Category data
        $category = $item['category'] ?? 'Uncategorized';
        if (!isset($category_data[$category])) {
            $category_data[$category] = 0;
        }
        $category_data[$category] += $item['total_price'];
    }
    
    $requester_data[$requester_name]['amount'] += $req_total;
    $total_amount += $req_total;
    
    // Monthly trend report
    $month = date('M Y', strtotime($req['created_at']));
    if (!isset($monthly_data[$month])) {
        $monthly_data[$month] = [
            'count' => 0,
            'amount' => 0
        ];
    }
    
    $monthly_data[$month]['count']++;
    $monthly_data[$month]['amount'] += $req_total;
}

// Sort monthly data by date
uksort($monthly_data, function($a, $b) {
    return strtotime($a) - strtotime($b);
});

// Get inventory items for department
$inventory_items = get_inventory_items();
$department_inventory = [];
$inventory_value = 0;
$low_stock_count = 0;

foreach ($inventory_items as $item) {
    if ($item['department'] == $department_id) {
        $department_inventory[] = $item;
        $inventory_value += $item['stock_level'] * $item['unit_price'];
        
        if ($item['stock_level'] <= $item['reorder_level']) {
            $low_stock_count++;
        }
    }
}

// Prepare inventory category data
$inventory_category_data = [];
foreach ($department_inventory as $item) {
    $category = $item['category'] ?? 'Uncategorized';
    if (!isset($inventory_category_data[$category])) {
        $inventory_category_data[$category] = 0;
    }
    $inventory_category_data[$category] += $item['stock_level'] * $item['unit_price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Reports - Bumbe Technical Training Institute (BTTI) Resource Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        .form-select:focus, .form-control:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
    </style>
</head>
<body>
    <?php require 'nav.php'; ?>
    
    <div class="container mt-4">
        <h1 class="h3 mb-2 text-gray-800">BTTI Department Reports: <?php echo htmlspecialchars($department['name']); ?></h1>
        <p class="mb-4">View and analyze detailed resource usage and inventory data for your department.</p>
        
        <!-- Report Type Selection -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="" method="get" class="row g-3">
                    <div class="col-md-4">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="type" onchange="this.form.submit()">
                            <option value="requisition_status" <?php echo ($report_type === 'requisition_status') ? 'selected' : ''; ?>>Request Status</option>
                            <option value="requester_wise" <?php echo ($report_type === 'requester_wise') ? 'selected' : ''; ?>>Staff Analysis</option>
                            <option value="monthly_trend" <?php echo ($report_type === 'monthly_trend') ? 'selected' : ''; ?>>Monthly Trends</option>
                            <option value="inventory" <?php echo ($report_type === 'inventory') ? 'selected' : ''; ?>>Inventory Status</option>
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
        
        <!-- Department Overview Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Requests</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($filtered_requisitions); ?></div>
                                <div class="small text-muted mt-2">During selected period</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-file-earmark-text fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Budget Used</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">KES <?php echo number_format($total_amount, 2); ?></div>
                                <div class="small text-muted mt-2">Department resources</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Current Inventory Value</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">KES <?php echo number_format($inventory_value, 2); ?></div>
                                <div class="small text-muted mt-2">Department assets</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-box-seam fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Low Stock Items</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $low_stock_count; ?></div>
                                <div class="small text-muted mt-2">Need reordering</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-exclamation-triangle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($report_type === 'requisition_status'): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4 shadow">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold">Department Request Status</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="requisitionStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4 shadow">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold">Request Status Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Status</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                            <th>Visual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_count = array_sum($requisition_status_data);
                                        foreach ($requisition_status_data as $status => $count): 
                                            $percentage = $total_count > 0 ? round(($count / $total_count) * 100, 2) : 0;
                                            $badge_class = '';
                                            switch($status) {
                                                case 'pending': $badge_class = 'bg-warning'; break;
                                                case 'approved': $badge_class = 'bg-success'; break;
                                                case 'rejected': $badge_class = 'bg-danger'; break;
                                                case 'processed': $badge_class = 'bg-info'; break;
                                                default: $badge_class = 'bg-secondary';
                                            }
                                        ?>
                                        <tr>
                                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span></td>
                                            <td><?php echo $count; ?></td>
                                            <td><?php echo $percentage; ?>%</td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar <?php echo $badge_class; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                                        aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $percentage; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
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
                        type: 'doughnut',
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
                                    '#17A2B8'  // Processed - Blue
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '60%',
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            const value = context.raw;
                                            const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            label += value + ' (' + percentage + '%)';
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
            </script>
            
        <?php elseif ($report_type === 'requester_wise'): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4 shadow">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold">Staff Resource Usage Analysis</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="requesterChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4 shadow">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold">Top Resource Users</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Staff Member</th>
                                            <th>Requests</th>
                                            <th>Amount (KES)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Sort requesters by count
                                        uasort($requester_data, function($a, $b) {
                                            return $b['count'] - $a['count'];
                                        });
                                        
                                        $count = 0;
                                        foreach ($requester_data as $requester => $data): 
                                            $count++;
                                            if ($count > 5) break; // Show only top 5
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm mr-2 bg-<?php echo ['primary', 'success', 'info', 'warning', 'danger'][$count % 5]; ?> text-white rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 30px; height: 30px; margin-right: 10px;">
                                                        <?php echo strtoupper(substr($requester, 0, 1)); ?>
                                                    </div>
                                                    <?php echo htmlspecialchars($requester); ?>
                                                </div>
                                            </td>
                                            <td class="text-center"><?php echo $data['count']; ?></td>
                                            <td class="text-right"><?php echo number_format($data['amount'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="card mb-4 shadow">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold">Department Resource Distribution</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Staff Member</th>
                                            <th>Requests</th>
                                            <th>Amount (KES)</th>
                                            <th>% of Department Budget</th>
                                            <th>Distribution</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        foreach ($requester_data as $requester => $data): 
                                            $percentage = $total_amount > 0 ? round(($data['amount'] / $total_amount) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($requester); ?></td>
                                            <td><?php echo $data['count']; ?></td>
                                            <td><?php echo number_format($data['amount'], 2); ?></td>
                                            <td><?php echo $percentage; ?>%</td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                                        aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $percentage; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('requesterChart').getContext('2d');
                    
                    // Prepare data for the chart
                    const requesters = [];
                    const requestCounts = [];
                    const requestAmounts = [];
                    
                    <?php 
                    // Sort requesters by count
                    uasort($requester_data, function($a, $b) {
                        return $b['count'] - $a['count'];
                    });
                    
                    $count = 0;
                    foreach ($requester_data as $requester => $data): 
                        $count++;
                        if ($count > 7) break; // Show only top 7
                    ?>
                        requesters.push('<?php echo addslashes($requester); ?>');
                        requestCounts.push(<?php echo $data['count']; ?>);
                        requestAmounts.push(<?php echo $data['amount']; ?>);
                    <?php endforeach; ?>
                    
                    const requesterChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: requesters,
                            datasets: [{
                                label: 'Number of Requests',
                                data: requestCounts,
                                backgroundColor: 'rgba(78, 115, 223, 0.8)',
                                borderColor: 'rgba(78, 115, 223, 1)',
                                borderWidth: 1
                            }, {
                                label: 'Total Amount (KES)',
                                data: requestAmounts,
                                backgroundColor: 'rgba(28, 200, 138, 0.8)',
                                borderColor: 'rgba(28, 200, 138, 1)',
                                borderWidth: 1,
                                yAxisID: 'y1'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Requests'
                                    },
                                    ticks: {
                                        precision: 0
                                    }
                                },
                                y1: {
                                    beginAtZero: true,
                                    position: 'right',
                                    grid: {
                                        drawOnChartArea: false
                                    },
                                    title: {
                                        display: true,
                                        text: 'Amount (KES)'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.datasetIndex === 0) {
                                                label += context.raw + ' requests';
                                            } else {
                                                label += 'KES ' + new Intl.NumberFormat().format(context.raw);
                                            }
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
            </script>
            
        <?php elseif ($report_type === 'monthly_trend'): ?>
            <div class="card mb-4 report-card">
                <div class="card-header">
                    <h5 class="card-title">Monthly Department Resource Trends</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
            
            <div class="card mb-4 report-card">
                <div class="card-header">
                    <h5 class="card-title">Monthly Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Number of Requests</th>
                                    <th>Total Amount (KES)</th>
                                    <th>Average per Request</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_data as $month => $data): ?>
                                <tr>
                                    <td><?php echo $month; ?></td>
                                    <td><?php echo $data['count']; ?></td>
                                    <td><?php echo number_format($data['amount'], 2); ?></td>
                                    <td><?php echo $data['count'] > 0 ? number_format($data['amount'] / $data['count'], 2) : 0; ?></td>
                                </tr>
                                <?php endforeach; ?>
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
                            labels: [<?php foreach ($monthly_data as $month => $data) echo "'" . $month . "', "; ?>],
                            datasets: [{
                                label: 'Number of Requests',
                                data: [<?php foreach ($monthly_data as $data) echo $data['count'] . ", "; ?>],
                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 2,
                                tension: 0.1
                            }, {
                                label: 'Total Amount (KES)',
                                data: [<?php foreach ($monthly_data as $data) echo $data['amount'] . ", "; ?>],
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
                                        text: 'Number of Requests'
                                    }
                                },
                                y1: {
                                    beginAtZero: true,
                                    position: 'right',
                                    grid: {
                                        drawOnChartArea: false
                                    },
                                    title: {
                                        display: true,
                                        text: 'Amount (KES)'
                                    }
                                }
                            },
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Monthly Resource Request Trends'
                                }
                            }
                        }
                    });
                });
            </script>
            
        <?php elseif ($report_type === 'inventory'): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4 report-card">
                        <div class="card-header">
                            <h5 class="card-title">Inventory by Category</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="inventoryCategoryChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4 report-card">
                        <div class="card-header">
                            <h5 class="card-title">Low Stock Items</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($low_stock_count === 0): ?>
                                <div class="alert alert-success">No low stock items found.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Category</th>
                                                <th>Current Stock</th>
                                                <th>Reorder Level</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            foreach ($department_inventory as $item): 
                                                if ($item['stock_level'] <= $item['reorder_level']):
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['category'] ?? 'Uncategorized'); ?></td>
                                                <td class="<?php echo $item['stock_level'] === 0 ? 'text-danger' : 'text-warning'; ?>">
                                                    <?php echo $item['stock_level']; ?>
                                                </td>
                                                <td><?php echo $item['reorder_level']; ?></td>
                                            </tr>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4 report-card">
                <div class="card-header">
                    <h5 class="card-title">Department Inventory Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Number of Items</th>
                                    <th>Total Value (KES)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $category_count = [];
                                foreach ($department_inventory as $item) {
                                    $category = $item['category'] ?? 'Uncategorized';
                                    if (!isset($category_count[$category])) {
                                        $category_count[$category] = 0;
                                    }
                                    $category_count[$category]++;
                                }
                                
                                foreach ($inventory_category_data as $category => $value): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td><?php echo $category_count[$category]; ?></td>
                                    <td><?php echo number_format($value, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
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
                            labels: [<?php foreach ($inventory_category_data as $category => $value) echo "'" . addslashes($category) . "', "; ?>],
                            datasets: [{
                                data: [<?php foreach ($inventory_category_data as $value) echo $value . ", "; ?>],
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.7)',
                                    'rgba(54, 162, 235, 0.7)',
                                    'rgba(255, 206, 86, 0.7)',
                                    'rgba(75, 192, 192, 0.7)',
                                    'rgba(153, 102, 255, 0.7)',
                                    'rgba(255, 159, 64, 0.7)',
                                    'rgba(199, 199, 199, 0.7)',
                                    'rgba(83, 102, 255, 0.7)',
                                    'rgba(40, 159, 64, 0.7)',
                                    'rgba(210, 199, 199, 0.7)'
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
                                    text: 'Inventory Value by Category'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            label += 'KES ' + new Intl.NumberFormat().format(context.raw);
                                            return label;
                                        }
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
