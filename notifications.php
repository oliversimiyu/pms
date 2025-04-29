<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle marking notifications as read
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['mark_read'])) {
    $notification_id = $_POST['notification_id'];
    mark_notification_read($notification_id);
    // Redirect to avoid form resubmission
    header("Location: notifications.php");
    exit();
}

// Get user notifications
$notifications = get_user_notifications($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Purchase Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .notification {
            padding: 15px;
            border-left: 4px solid #ccc;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .notification.unread {
            border-left-color: #007bff;
            background-color: #e9f5ff;
        }
        
        .notification-time {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .notification-actions {
            display: flex;
            justify-content: flex-end;
        }
    </style>
</head>
<body>
    <?php require 'nav.php'; ?>
    
    <div class="container mt-4">
        <h2>Notifications</h2>
        
        <?php if (empty($notifications)): ?>
            <div class="alert alert-info">You have no notifications.</div>
        <?php else: ?>
            <div class="mb-3">
                <form action="" method="post">
                    <input type="hidden" name="mark_all_read" value="1">
                    <button type="submit" class="btn btn-outline-primary btn-sm">Mark All as Read</button>
                </form>
            </div>
            
            <?php foreach ($notifications as $notification): ?>
                <div class="notification <?php echo (isset($notification['is_read']) && $notification['is_read'] === false) ? 'unread' : ''; ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <p class="notification-time mb-0">
                                <?php echo date('F d, Y h:i A', strtotime($notification['created_at'])); ?>
                            </p>
                        </div>
                        
                        <?php if (isset($notification['is_read']) && $notification['is_read'] === false): ?>
                            <div class="notification-actions">
                                <form action="" method="post">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                    <button type="submit" name="mark_read" class="btn btn-sm btn-outline-secondary">Mark as Read</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
