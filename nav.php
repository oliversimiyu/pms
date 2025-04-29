<?php
// Get user role from session
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';

// Get unread notifications count
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $notifications = get_user_notifications($_SESSION['user_id']);
    foreach ($notifications as $notification) {
        if (isset($notification['is_read']) && $notification['is_read'] === false) {
            $unread_count++;
        }
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Bumbe School Resource Management System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($user_role === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">User Management</a></li>
                    <li class="nav-item"><a class="nav-link" href="account_requests.php">Account Requests</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="resourcesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Resources</a>
                        <ul class="dropdown-menu" aria-labelledby="resourcesDropdown">
                            <li><a class="dropdown-item" href="create_requisition.php">New Request</a></li>
                            <li><a class="dropdown-item" href="view_requisitions.php">All Requests</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="inventory.php">Inventory</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <?php elseif ($user_role === 'hod'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="resourcesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Department Resources</a>
                        <ul class="dropdown-menu" aria-labelledby="resourcesDropdown">
                            <li><a class="dropdown-item" href="create_requisition.php">New Request</a></li>
                            <li><a class="dropdown-item" href="view_requisitions.php">Department Requests</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="inventory.php">Department Inventory</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Department Reports</a></li>
                <?php elseif ($user_role === 'staff'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="resourcesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Resources</a>
                        <ul class="dropdown-menu" aria-labelledby="resourcesDropdown">
                            <li><a class="dropdown-item" href="create_requisition.php">New Request</a></li>
                            <li><a class="dropdown-item" href="view_requisitions.php">My Requests</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="inventory.php">View Inventory</a></li>
                <?php elseif ($user_role === 'student'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="resourcesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">My Resources</a>
                        <ul class="dropdown-menu" aria-labelledby="resourcesDropdown">
                            <li><a class="dropdown-item" href="create_requisition.php">New Request</a></li>
                            <li><a class="dropdown-item" href="view_requisitions.php">My Requests</a></li>
                        </ul>
                    </li>
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