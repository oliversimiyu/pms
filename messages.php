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

// Get all messages for the current user
$messages = get_user_messages($user_id);

// Calculate unread message count
$unread_count = get_unread_message_count($user_id);

// Handle message deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $message_id = $_GET['delete'];
    if (delete_message($message_id, $user_id)) {
        // Redirect to avoid resubmission
        header("Location: messages.php?deleted=1");
        exit();
    }
}

// Pagination
$items_per_page = 10;
$total_items = count($messages);
$total_pages = ceil($total_items / $items_per_page);

// Get current page
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get messages for current page
$paged_messages = array_slice($messages, $offset, $items_per_page);

// Get all users for the compose message dropdown
$all_users = get_users();
?>

<?php
$page_title = 'Messages';
$page_styles = <<<HTML
<style>
    .message-item {
        transition: background-color 0.2s;
    }
    .message-item:hover {
        background-color: rgba(0,0,0,0.05);
    }
    .unread {
        font-weight: bold;
        background-color: rgba(13, 110, 253, 0.05);
    }
    .message-sender {
        width: 180px;
    }
    .message-subject {
        font-weight: 500;
    }
    .message-date {
        width: 120px;
        text-align: right;
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
                        <h5 class="mb-0">Messages <?php if ($unread_count > 0): ?><span class="badge bg-primary"><?php echo $unread_count; ?> unread</span><?php endif; ?></h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeModal">
                            <i class="bi bi-pencil-square"></i> Compose New Message
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['sent']) && $_GET['sent'] == '1'): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                Message sent successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                Message deleted successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($paged_messages)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-envelope text-muted" style="font-size: 3rem;"></i>
                                <p class="mt-3 text-muted">No messages found</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($paged_messages as $message): ?>
                                    <?php 
                                    // Determine if user is sender or receiver
                                    $is_sender = ($message['sender_id'] == $user_id);
                                    
                                    // Get the other user's information
                                    $other_user_id = $is_sender ? $message['receiver_id'] : $message['sender_id'];
                                    $other_user = get_user_by_id($other_user_id);
                                    $other_user_name = $other_user ? $other_user['full_name'] : 'Unknown User';
                                    
                                    // Determine if message is unread (only for received messages)
                                    $is_unread = !$is_sender && !$message['is_read'];
                                    ?>
                                    
                                    <a href="view_message.php?id=<?php echo $message['message_id']; ?>" class="list-group-item list-group-item-action message-item <?php echo $is_unread ? 'unread' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <div class="message-sender">
                                                <?php if ($is_sender): ?>
                                                    <i class="bi bi-arrow-right-circle text-primary"></i> To: <?php echo htmlspecialchars($other_user_name); ?>
                                                <?php else: ?>
                                                    <i class="bi bi-arrow-left-circle text-success"></i> From: <?php echo htmlspecialchars($other_user_name); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="message-subject flex-grow-1">
                                                <?php echo htmlspecialchars($message['subject']); ?>
                                            </div>
                                            <div class="message-date">
                                                <small class="text-muted"><?php echo date('M d, Y', strtotime($message['created_at'])); ?></small>
                                            </div>
                                        </div>
                                        <div class="mt-1">
                                            <small class="text-muted"><?php echo substr(htmlspecialchars($message['content']), 0, 100) . (strlen($message['content']) > 100 ? '...' : ''); ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Message pagination" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Compose Message Modal -->
    <div class="modal fade" id="composeModal" tabindex="-1" aria-labelledby="composeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="composeModalLabel">Compose New Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="send_message.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="receiver_id" class="form-label">To:</label>
                            <select class="form-select" id="receiver_id" name="receiver_id" required>
                                <option value="">Select recipient...</option>
                                <?php foreach ($all_users as $recipient): ?>
                                    <?php if ($recipient['user_id'] != $user_id): ?>
                                        <option value="<?php echo $recipient['user_id']; ?>">
                                            <?php echo htmlspecialchars($recipient['full_name']); ?> 
                                            (<?php echo htmlspecialchars($recipient['email']); ?>) - 
                                            <?php echo ucfirst($recipient['role']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject:</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Message:</label>
                            <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php
$page_scripts = <<<HTML
<script>
    // Initialize message-specific functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure dropdown in compose modal works properly
        var receiverSelect = document.getElementById('receiver_id');
        if (receiverSelect) {
            receiverSelect.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
</script>
HTML;
include('includes/footer.php');
?>
