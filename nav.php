<?php
// Get user role from session
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';

// Get unread notifications count
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $notifications = get_user_notifications($_SESSION['user_id']);
    foreach ($notifications as $notification) {
        if ($notification['status'] === 'unread') {
            $unread_count++;
        }
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Purchase Management System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($user_role === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">User Management</a></li>
                    <li class="nav-item"><a class="nav-link" href="create_requisition.php">New Requisition</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_requisitions.php">Requisitions</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventory.php">Inventory</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <?php elseif ($user_role === 'approver'): ?>
                    <li class="nav-item"><a class="nav-link" href="create_requisition.php">New Requisition</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_requisitions.php">Pending Approvals</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <?php elseif ($user_role === 'procurement'): ?>
                    <li class="nav-item"><a class="nav-link" href="create_requisition.php">New Requisition</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_requisitions.php">Requisitions</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventory.php">Inventory</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <?php elseif ($user_role === 'requester'): ?>
                    <li class="nav-item"><a class="nav-link" href="create_requisition.php">New Requisition</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_requisitions.php">My Requisitions</a></li>
                <?php elseif ($user_role === 'employee'): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard_employee.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="requisition.php">New Requisition</a></li>
                <?php elseif ($user_role === 'manager'): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard_manager.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="pending_requisitions.php">Pending Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="approve_requisition.html">Approvals</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_requisition.html">Requisition History</a></li>
                <?php endif; ?>
            </ul>
            
            <?php if ($user_role): ?>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="notifications.php">
                            Notifications
                            <?php if ($unread_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $unread_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>