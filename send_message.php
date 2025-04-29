<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (
        isset($_POST['receiver_id']) && !empty($_POST['receiver_id']) &&
        isset($_POST['subject']) && !empty($_POST['subject']) &&
        isset($_POST['content']) && !empty($_POST['content'])
    ) {
        $receiver_id = intval($_POST['receiver_id']);
        $subject = trim($_POST['subject']);
        $content = trim($_POST['content']);
        
        // Verify receiver exists
        $receiver = get_user_by_id($receiver_id);
        if ($receiver) {
            // Send the message
            if (send_message($user_id, $receiver_id, $subject, $content)) {
                // Add notification for the receiver
                $notification_message = "You have received a new message from " . $_SESSION['user_name'] . ": " . $subject;
                add_notification($receiver_id, [
                    'title' => 'New Message',
                    'message' => $notification_message,
                    'type' => 'message',
                    'link' => 'messages.php'
                ]);
                
                // Redirect to messages page with success message
                header("Location: messages.php?sent=1");
                exit();
            } else {
                // Error sending message
                header("Location: messages.php?error=send_failed");
                exit();
            }
        } else {
            // Receiver doesn't exist
            header("Location: messages.php?error=invalid_receiver");
            exit();
        }
    } else {
        // Missing required fields
        header("Location: messages.php?error=missing_fields");
        exit();
    }
} else {
    // Not a POST request
    header("Location: messages.php");
    exit();
}
?>
