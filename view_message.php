<?php
session_start();
include_once('includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = get_user_by_id($user_id);
$user_role = $_SESSION['user_role'];

// Check if message ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: messages.php");
    exit();
}

$message_id = $_GET['id'];
$messages = get_all_messages();
$message = null;

// Find the message and check if user has access to it
foreach ($messages as $msg) {
    if ($msg['message_id'] === $message_id && 
        ($msg['sender_id'] == $user_id || $msg['receiver_id'] == $user_id)) {
        $message = $msg;
        break;
    }
}

// If message doesn't exist or user doesn't have permission to view it
if (!$message) {
    header("Location: messages.php");
    exit();
}

// Mark message as read if user is the receiver
if ($message['receiver_id'] == $user_id && !$message['is_read']) {
    mark_message_as_read($message_id);
    // Update the message object to reflect the change
    $message['is_read'] = true;
}

// Get sender and receiver information
$sender = get_user_by_id($message['sender_id']);
$receiver = get_user_by_id($message['receiver_id']);

// Determine the other user (for reply functionality)
$other_user_id = ($message['sender_id'] == $user_id) ? $message['receiver_id'] : $message['sender_id'];
$other_user = ($message['sender_id'] == $user_id) ? $receiver : $sender;

// Get conversation history
$conversation = get_conversation($user_id, $other_user_id);
?>

<?php
$page_title = 'View Message';
$page_styles = <<<HTML
<style>
    .message-bubble {
        border-radius: 1rem;
        padding: 1rem;
        margin-bottom: 1rem;
        max-width: 80%;
    }
    .message-sent {
        background-color: #d1e7ff;
        margin-left: auto;
    }
    .message-received {
        background-color: #f0f0f0;
        margin-right: auto;
    }
    .message-time {
        font-size: 0.75rem;
        color: #6c757d;
        text-align: right;
        margin-top: 0.25rem;
    }
    .conversation-container {
        max-height: 400px;
        overflow-y: auto;
    }
</style>
HTML;
include('includes/header.php');
?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <a href="messages.php" class="btn btn-sm btn-outline-secondary me-2">
                                <i class="bi bi-arrow-left"></i> Back to Messages
                            </a>
                            <?php echo htmlspecialchars($message['subject']); ?>
                        </h5>
                        <div>
                            <a href="messages.php?delete=<?php echo $message_id; ?>" class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Are you sure you want to delete this message?');">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="message-header mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>From:</strong> <?php echo htmlspecialchars($sender['full_name']); ?> (<?php echo htmlspecialchars($sender['email']); ?>)</p>
                                    <p><strong>To:</strong> <?php echo htmlspecialchars($receiver['full_name']); ?> (<?php echo htmlspecialchars($receiver['email']); ?>)</p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <p><strong>Date:</strong> <?php echo date('F d, Y h:i A', strtotime($message['created_at'])); ?></p>
                                    <p>
                                        <strong>Status:</strong> 
                                        <?php if ($message['is_read']): ?>
                                            <span class="text-success">Read</span>
                                        <?php else: ?>
                                            <span class="text-warning">Unread</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Conversation with <?php echo htmlspecialchars($other_user['full_name']); ?></h6>
                        
                        <div class="conversation-container mb-4">
                            <?php foreach ($conversation as $msg): ?>
                                <div class="d-flex">
                                    <div class="message-bubble <?php echo ($msg['sender_id'] == $user_id) ? 'message-sent' : 'message-received'; ?>">
                                        <div class="message-content">
                                            <?php if ($msg['message_id'] === $message_id): ?>
                                                <strong><?php echo htmlspecialchars($msg['subject']); ?></strong>
                                                <hr>
                                            <?php endif; ?>
                                            <?php echo nl2br(htmlspecialchars($msg['content'])); ?>
                                        </div>
                                        <div class="message-time">
                                            <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <div class="reply-form mt-4">
                            <h6>Reply to <?php echo htmlspecialchars($other_user['full_name']); ?></h6>
                            <form action="send_message.php" method="post">
                                <input type="hidden" name="receiver_id" value="<?php echo $other_user_id; ?>">
                                <input type="hidden" name="subject" value="Re: <?php echo htmlspecialchars($message['subject']); ?>">
                                
                                <div class="mb-3">
                                    <textarea class="form-control" name="content" rows="4" placeholder="Type your reply here..." required></textarea>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-reply"></i> Send Reply
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
$page_scripts = <<<HTML
<script>
    // Scroll to the bottom of the conversation container
    document.addEventListener('DOMContentLoaded', function() {
        const conversationContainer = document.querySelector('.conversation-container');
        conversationContainer.scrollTop = conversationContainer.scrollHeight;
    });
</script>
HTML;
include('includes/footer.php');
?>
