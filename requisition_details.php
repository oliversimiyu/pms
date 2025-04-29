<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if requisition ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_requisitions.php");
    exit();
}

$requisition_id = intval($_GET['id']);
$requisition = get_requisition_by_id($requisition_id);

// Get user information for department check
$current_user = get_user_by_id($_SESSION['user_id']);

// If requisition doesn't exist or user doesn't have permission to view it
if (!$requisition || 
    ($_SESSION['user_role'] !== 'admin' && 
     $_SESSION['user_role'] !== 'procurement' && 
     $_SESSION['user_role'] !== 'approver' && 
     // Allow HODs to view requisitions from their department
     !($_SESSION['user_role'] === 'hod' && $current_user && isset($current_user['department']) && 
       $requisition['department'] == $current_user['department']) && 
     $requisition['requester_id'] !== $_SESSION['user_id'])) {
    header("Location: view_requisitions.php");
    exit();
}

// Get additional information
$requester = get_user_by_id($requisition['requester_id']);
$department = get_department_by_id($requisition['department']);
$approvals = get_approvals_for_requisition($requisition_id);

// Calculate total amount
$total_amount = 0;
foreach ($requisition['items'] as $item) {
    $total_amount += $item['total_price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisition Details - Purchase Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-pending { color: #FFC107; }
        .status-approved { color: #28A745; }
        .status-rejected { color: #DC3545; }
        .status-processed { color: #17A2B8; }
        
        .approval-history {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 15px;
        }
        
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -20px;
            top: 0;
            height: 100%;
            width: 2px;
            background-color: #dee2e6;
        }
        
        .timeline-item:after {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            height: 10px;
            width: 10px;
            border-radius: 50%;
            background-color: #007bff;
        }
        
        .timeline-item:last-child:before {
            height: 5px;
        }
    </style>
</head>
<body>
    <?php require 'nav.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Requisition Details</h2>
            <a href="view_requisitions.php" class="btn btn-secondary">Back to Requisitions</a>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Requisition #<?php echo $requisition['requisition_id']; ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Requester:</strong> <?php echo $requester ? $requester['full_name'] : 'Unknown'; ?></p>
                        <p><strong>Department:</strong> <?php echo $department ? $department['department_name'] : 'Unknown'; ?></p>
                        <p><strong>Date Created:</strong> <?php echo date('F d, Y', strtotime($requisition['created_at'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p>
                            <strong>Status:</strong> 
                            <span class="status-<?php echo strtolower($requisition['status']); ?>">
                                <?php echo ucfirst($requisition['status']); ?>
                            </span>
                        </p>
                        <p><strong>Total Amount:</strong> KES <?php echo number_format($total_amount, 2); ?></p>
                    </div>
                </div>
                
                <hr>
                
                <h5>Justification</h5>
                <p><?php echo nl2br(htmlspecialchars($requisition['justification'])); ?></p>
                
                <h5 class="mt-4">Items</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requisition['items'] as $index => $item): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>KES <?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td>KES <?php echo number_format($item['total_price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-active">
                                <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                <td><strong>KES <?php echo number_format($total_amount, 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <?php if (!empty($approvals)): ?>
                    <div class="approval-history mt-4">
                        <h5>Approval History</h5>
                        <div class="timeline">
                            <?php foreach ($approvals as $approval): ?>
                                <?php $approver = get_user_by_id($approval['approver_id']); ?>
                                <div class="timeline-item">
                                    <p>
                                        <strong><?php echo $approver ? $approver['full_name'] : 'Unknown'; ?></strong> 
                                        <span class="status-<?php echo strtolower($approval['status']); ?>">
                                            <?php echo ucfirst($approval['status']); ?>
                                        </span>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y H:i', strtotime($approval['approved_at'])); ?>
                                        </small>
                                    </p>
                                    <?php if (!empty($approval['comments'])): ?>
                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($approval['comments'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (($_SESSION['user_role'] === 'approver' || $_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'hod') && $requisition['status'] === 'pending'): ?>
                    <div class="mt-4">
                        <h5>Approval Actions</h5>
                        <form action="process_approval.php" method="post">
                            <input type="hidden" name="requisition_id" value="<?php echo $requisition_id; ?>">
                            
                            <div class="mb-3">
                                <label for="comments" class="form-label">Comments</label>
                                <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                
                <?php if ($_SESSION['user_role'] === 'procurement' && $requisition['status'] === 'approved'): ?>
                    <div class="mt-4">
                        <h5>Process Requisition</h5>
                        <form action="process_requisition.php" method="post">
                            <input type="hidden" name="requisition_id" value="<?php echo $requisition_id; ?>">
                            
                            <div class="mb-3">
                                <label for="po_number" class="form-label">Purchase Order Number</label>
                                <input type="text" class="form-control" id="po_number" name="po_number" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="supplier" class="form-label">Supplier</label>
                                <input type="text" class="form-control" id="supplier" name="supplier" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Process Order</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
