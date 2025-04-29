<?php

// Use a simple file-based storage system instead of a database
$data_dir = __DIR__ . '/../data';
$users_file = $data_dir . '/users.json';
$departments_file = $data_dir . '/departments.json';
$requisitions_file = $data_dir . '/requisitions.json';
$items_file = $data_dir . '/items.json';
$approvals_file = $data_dir . '/approvals.json';
$notifications_file = $data_dir . '/notifications.json';
$inventory_file = $data_dir . '/inventory.json';
$account_requests_file = $data_dir . '/account_requests.json';
$messages_file = $data_dir . '/messages.json';

// Ensure the data directory exists
if (!file_exists($data_dir)) {
    mkdir($data_dir, 0755, true);
}

$db_available = false;
$db_error = "Database connection not initialized";

try {
    // Check if we can access the data directory
    if (is_dir($data_dir) && is_writable($data_dir)) {
        $db_available = true;
        
        // Check if users file exists
        if (!file_exists($users_file)) {
            // Create default admin user
            $admin_user = [
                'user_id' => 1,
                'full_name' => 'Admin User',
                'email' => 'admin@example.com',
                'department' => 'Administration',
                'role' => 'admin',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Create initial users array with admin user
            $users = [$admin_user];
            
            // Save to file
            file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
        }
        
        // Check if departments file exists
        if (!file_exists($departments_file)) {
            // Create initial departments
            $departments = [
                [
                    'department_id' => 1,
                    'department_name' => 'Administration',
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'department_id' => 2,
                    'department_name' => 'Finance',
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'department_id' => 3,
                    'department_name' => 'IT',
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            // Save to file
            file_put_contents($departments_file, json_encode($departments, JSON_PRETTY_PRINT));
        }
        
        // Initialize other files if they don't exist
        if (!file_exists($requisitions_file)) {
            file_put_contents($requisitions_file, json_encode([], JSON_PRETTY_PRINT));
        }
        
        if (!file_exists($items_file)) {
            file_put_contents($items_file, json_encode([], JSON_PRETTY_PRINT));
        }
        
        if (!file_exists($approvals_file)) {
            file_put_contents($approvals_file, json_encode([], JSON_PRETTY_PRINT));
        }
        
        if (!file_exists($notifications_file)) {
            file_put_contents($notifications_file, json_encode([], JSON_PRETTY_PRINT));
        }
        
        if (!file_exists($inventory_file)) {
            // Create initial inventory items
            $inventory = [
                [
                    'item_id' => 1,
                    'item_name' => 'Laptop',
                    'category' => 'Electronics',
                    'unit' => 'piece',
                    'stock_level' => 10,
                    'reorder_level' => 3,
                    'unit_price' => 1200.00,
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'item_id' => 2,
                    'item_name' => 'Printer Paper',
                    'category' => 'Office Supplies',
                    'unit' => 'ream',
                    'stock_level' => 50,
                    'reorder_level' => 10,
                    'unit_price' => 5.99,
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'item_id' => 3,
                    'item_name' => 'Desk Chair',
                    'category' => 'Furniture',
                    'unit' => 'piece',
                    'stock_level' => 15,
                    'reorder_level' => 5,
                    'unit_price' => 199.99,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            file_put_contents($inventory_file, json_encode($inventory, JSON_PRETTY_PRINT));
        }
    } else {
        $db_error = "Cannot access data directory. Please check permissions.";
    }
} catch (Exception $e) {
    $db_error = "File system error: " . $e->getMessage();
}

function is_db_available() {
    global $db_available;
    return $db_available;
}

function get_db_error() {
    global $db_error;
    return isset($db_error) ? $db_error : "Unknown database error";
}

// ===== USER MANAGEMENT FUNCTIONS =====

// Function to get all users
function get_users() {
    global $users_file;
    
    if (!file_exists($users_file)) {
        return [];
    }
    
    $data = file_get_contents($users_file);
    return json_decode($data, true) ?: [];
}

// Function to get user by email
function get_user_by_email($email) {
    $users = get_users();
    
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return $user;
        }
    }
    
    return null;
}

// Function to get user by ID
function get_user_by_id($user_id) {
    $users = get_users();
    
    foreach ($users as $user) {
        if ($user['user_id'] == $user_id) {
            return $user;
        }
    }
    
    return null;
}

// Function to add a new user
function add_user($user_data) {
    global $users_file;
    
    $users = get_users();
    
    // Generate new user ID
    $max_id = 0;
    foreach ($users as $user) {
        if ($user['user_id'] > $max_id) {
            $max_id = $user['user_id'];
        }
    }
    
    $user_data['user_id'] = $max_id + 1;
    $user_data['created_at'] = date('Y-m-d H:i:s');
    
    // Add to users array
    $users[] = $user_data;
    
    // Save to file
    return file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT)) !== false;
}

// Function to update an existing user
function update_user($user_id, $user_data) {
    global $users_file;
    
    $users = get_users();
    $updated = false;
    
    foreach ($users as $key => $user) {
        if ($user['user_id'] == $user_id) {
            // Preserve user_id and created_at
            $user_data['user_id'] = $user['user_id'];
            $user_data['created_at'] = $user['created_at'];
            
            // Update user
            $users[$key] = $user_data;
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        // Save to file
        return file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT)) !== false;
    }
    
    return false;
}

// Function to delete a user
function delete_user($user_id) {
    global $users_file;
    
    $users = get_users();
    $updated = false;
    
    foreach ($users as $key => $user) {
        if ($user['user_id'] == $user_id) {
            // Remove user
            unset($users[$key]);
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        // Reindex array
        $users = array_values($users);
        
        // Save to file
        return file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT)) !== false;
    }
    
    return false;
}

// ===== DEPARTMENT MANAGEMENT FUNCTIONS =====

// Function to get all departments
function get_departments() {
    global $departments_file;
    
    if (!file_exists($departments_file)) {
        return [];
    }
    
    $data = file_get_contents($departments_file);
    return json_decode($data, true) ?: [];
}

// Function to get department by ID
function get_department_by_id($department_id) {
    $departments = get_departments();
    
    foreach ($departments as $department) {
        if ($department['department_id'] == $department_id) {
            return $department;
        }
    }
    
    return null;
}

// Function to add a new department
function add_department($department_data) {
    global $departments_file;
    
    $departments = get_departments();
    
    // Generate new department ID
    $max_id = 0;
    foreach ($departments as $dept) {
        if ($dept['department_id'] > $max_id) {
            $max_id = $dept['department_id'];
        }
    }
    
    $department_data['department_id'] = $max_id + 1;
    $department_data['created_at'] = date('Y-m-d H:i:s');
    
    // Add to departments array
    $departments[] = $department_data;
    
    // Save to file
    return file_put_contents($departments_file, json_encode($departments, JSON_PRETTY_PRINT)) !== false;
}

// ===== REQUISITION MANAGEMENT FUNCTIONS =====

// Function to get all requisitions
function get_all_requisitions() {
    global $requisitions_file;
    
    if (!file_exists($requisitions_file)) {
        return [];
    }
    
    $data = file_get_contents($requisitions_file);
    $requisitions = json_decode($data, true) ?: [];
    
    // Sort by created_at date (newest first)
    usort($requisitions, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return $requisitions;
}

// Function to get requisitions for a specific user
function get_user_requisitions($user_id) {
    $requisitions = get_all_requisitions();
    $user_requisitions = [];
    
    foreach ($requisitions as $req) {
        if ($req['requester_id'] == $user_id) {
            $user_requisitions[] = $req;
        }
    }
    
    return $user_requisitions;
}

// Function to get requisitions pending approval
function get_requisitions_for_approval($approver_id) {
    $requisitions = get_all_requisitions();
    $pending_requisitions = [];
    
    // Get user role to determine which requisitions they can approve
    $approver = get_user_by_id($approver_id);
    if (!$approver) {
        return [];
    }
    
    foreach ($requisitions as $req) {
        if ($req['status'] === 'pending') {
            // Check if this approver has already approved this requisition
            $already_approved = false;
            $approvals = get_approvals_for_requisition($req['requisition_id']);
            
            foreach ($approvals as $approval) {
                if ($approval['approver_id'] == $approver_id) {
                    $already_approved = true;
                    break;
                }
            }
            
            if (!$already_approved) {
                $pending_requisitions[] = $req;
            }
        }
    }
    
    return $pending_requisitions;
}

// Function to get a specific requisition by ID
function get_requisition_by_id($requisition_id) {
    $requisitions = get_all_requisitions();
    
    foreach ($requisitions as $req) {
        if ($req['requisition_id'] == $requisition_id) {
            return $req;
        }
    }
    
    return null;
}

// Function to add a new requisition
function add_requisition($requisition_data) {
    global $requisitions_file;
    
    $requisitions = get_all_requisitions();
    
    // Generate new requisition ID
    $max_id = 0;
    foreach ($requisitions as $req) {
        if ($req['requisition_id'] > $max_id) {
            $max_id = $req['requisition_id'];
        }
    }
    
    $requisition_data['requisition_id'] = $max_id + 1;
    if (!isset($requisition_data['created_at'])) {
        $requisition_data['created_at'] = date('Y-m-d H:i:s');
    }
    
    // Add to requisitions array
    $requisitions[] = $requisition_data;
    
    // Save to file
    $result = file_put_contents($requisitions_file, json_encode($requisitions, JSON_PRETTY_PRINT)) !== false;
    
    if ($result) {
        // Create notification for department approvers
        $requester = get_user_by_id($requisition_data['requester_id']);
        $department = get_department_by_id($requisition_data['department']);
        
        $notification_message = "New requisition #" . $requisition_data['requisition_id'] . " submitted by " . 
                               ($requester ? $requester['full_name'] : 'Unknown') . " for " . 
                               ($department ? $department['department_name'] : 'Unknown') . " department.";
        
        // Get all approvers
        $users = get_users();
        foreach ($users as $user) {
            if ($user['role'] === 'approver') {
                add_notification($user['user_id'], $notification_message);
            }
        }
    }
    
    return $result;
}

// Function to update a requisition
function update_requisition($requisition_id, $requisition_data) {
    global $requisitions_file;
    
    $requisitions = get_all_requisitions();
    $updated = false;
    
    foreach ($requisitions as $key => $req) {
        if ($req['requisition_id'] == $requisition_id) {
            // Preserve requisition_id and created_at
            $requisition_data['requisition_id'] = $req['requisition_id'];
            $requisition_data['created_at'] = $req['created_at'];
            
            // Update requisition
            $requisitions[$key] = $requisition_data;
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        // Save to file
        return file_put_contents($requisitions_file, json_encode($requisitions, JSON_PRETTY_PRINT)) !== false;
    }
    
    return false;
}

// ===== APPROVAL MANAGEMENT FUNCTIONS =====

// Function to get all approvals
function get_all_approvals() {
    global $approvals_file;
    
    if (!file_exists($approvals_file)) {
        return [];
    }
    
    $data = file_get_contents($approvals_file);
    return json_decode($data, true) ?: [];
}

// Function to get approvals for a specific requisition
function get_approvals_for_requisition($requisition_id) {
    $approvals = get_all_approvals();
    $req_approvals = [];
    
    foreach ($approvals as $approval) {
        if ($approval['requisition_id'] == $requisition_id) {
            $req_approvals[] = $approval;
        }
    }
    
    // Sort by approved_at date
    usort($req_approvals, function($a, $b) {
        return strtotime($a['approved_at']) - strtotime($b['approved_at']);
    });
    
    return $req_approvals;
}

// Function to add a new approval
function add_approval($approval_data) {
    global $approvals_file;
    
    $approvals = get_all_approvals();
    
    // Generate new approval ID
    $max_id = 0;
    foreach ($approvals as $approval) {
        if ($approval['approval_id'] > $max_id) {
            $max_id = $approval['approval_id'];
        }
    }
    
    $approval_data['approval_id'] = $max_id + 1;
    if (!isset($approval_data['approved_at'])) {
        $approval_data['approved_at'] = date('Y-m-d H:i:s');
    }
    
    // Add to approvals array
    $approvals[] = $approval_data;
    
    // Save to file
    $result = file_put_contents($approvals_file, json_encode($approvals, JSON_PRETTY_PRINT)) !== false;
    
    if ($result) {
        // Update requisition status if necessary
        $requisition = get_requisition_by_id($approval_data['requisition_id']);
        if ($requisition) {
            if ($approval_data['status'] === 'rejected') {
                // If rejected, update requisition status to rejected
                $requisition['status'] = 'rejected';
                update_requisition($requisition['requisition_id'], $requisition);
                
                // Create notification for requester
                $approver = get_user_by_id($approval_data['approver_id']);
                $notification_message = "Your requisition #" . $requisition['requisition_id'] . " has been rejected by " . 
                                       ($approver ? $approver['full_name'] : 'Unknown') . ".";
                add_notification($requisition['requester_id'], $notification_message);
            } else if ($approval_data['status'] === 'approved') {
                // If approved, check if all required approvals are done
                // For simplicity, we'll assume one approval is enough to change status to approved
                $requisition['status'] = 'approved';
                update_requisition($requisition['requisition_id'], $requisition);
                
                // Create notification for requester
                $approver = get_user_by_id($approval_data['approver_id']);
                $notification_message = "Your requisition #" . $requisition['requisition_id'] . " has been approved by " . 
                                       ($approver ? $approver['full_name'] : 'Unknown') . ".";
                add_notification($requisition['requester_id'], $notification_message);
                
                // Create notification for procurement team
                $users = get_users();
                foreach ($users as $user) {
                    if ($user['role'] === 'procurement') {
                        $proc_message = "Requisition #" . $requisition['requisition_id'] . " has been approved and is ready for processing.";
                        add_notification($user['user_id'], $proc_message);
                    }
                }
            }
        }
    }
    
    return $result;
}

// ===== NOTIFICATION MANAGEMENT FUNCTIONS =====

// Function to get all notifications
function get_all_notifications() {
    global $notifications_file;
    
    if (!file_exists($notifications_file)) {
        return [];
    }
    
    $data = file_get_contents($notifications_file);
    return json_decode($data, true) ?: [];
}

// Function to get notifications for a specific user
function get_user_notifications($user_id) {
    $notifications = get_all_notifications();
    $user_notifications = [];
    
    foreach ($notifications as $notification) {
        // Include notifications specifically for this user
        // Also include admin notifications for all admins
        if ($notification['user_id'] == $user_id || 
            ($notification['user_id'] == 'admin' && $user_id == 'admin')) {
            $user_notifications[] = $notification;
        }
    }
    
    // Sort by created_at date (newest first)
    usort($user_notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return $user_notifications;
}

// Function to add a new notification
function add_notification($user_id_or_data, $message = null) {
    global $notifications_file, $data_dir;
    
    // Ensure data directory exists
    if (!file_exists($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    
    $notifications = get_all_notifications();
    
    // Handle different parameter formats
    $notification_data = [];
    
    if (is_array($user_id_or_data)) {
        // First parameter is already a notification data array
        $notification_data = $user_id_or_data;
    } else {
        // First parameter is user_id, second is message
        $notification_data = [
            'user_id' => $user_id_or_data,
            'message' => $message,
            'title' => 'System Notification'
        ];
    }
    
    // Ensure required fields
    if (!isset($notification_data['notification_id'])) {
        $notification_data['notification_id'] = uniqid('notif_');
    }
    if (!isset($notification_data['created_at'])) {
        $notification_data['created_at'] = date('Y-m-d H:i:s');
    }
    if (!isset($notification_data['is_read'])) {
        $notification_data['is_read'] = false;
    }
    
    $notifications[] = $notification_data;
    
    $json = json_encode($notifications, JSON_PRETTY_PRINT);
    return file_put_contents($notifications_file, $json) !== false;
}

// Function to mark notification as read
function mark_notification_as_read($notification_id) {
    global $notifications_file;
    
    $notifications = get_all_notifications();
    $updated = false;
    
    foreach ($notifications as $key => $notification) {
        if ($notification['notification_id'] === $notification_id) {
            $notifications[$key]['is_read'] = true;
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        $json = json_encode($notifications, JSON_PRETTY_PRINT);
        return file_put_contents($notifications_file, $json) !== false;
    }
    
    return false;
}

// Messaging System Functions

// Function to get all messages
function get_all_messages() {
    global $messages_file;
    if (!file_exists($messages_file)) {
        return [];
    }
    $data = file_get_contents($messages_file);
    return json_decode($data, true) ?: [];
}

// Function to get messages for a specific user
function get_user_messages($user_id) {
    $messages = get_all_messages();
    $user_messages = [];
    
    foreach ($messages as $message) {
        if ($message['receiver_id'] == $user_id || $message['sender_id'] == $user_id) {
            $user_messages[] = $message;
        }
    }
    
    // Sort messages by date (newest first)
    usort($user_messages, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return $user_messages;
}

// Function to get conversation between two users
function get_conversation($user1_id, $user2_id) {
    $messages = get_all_messages();
    $conversation = [];
    
    foreach ($messages as $message) {
        if (($message['sender_id'] == $user1_id && $message['receiver_id'] == $user2_id) ||
            ($message['sender_id'] == $user2_id && $message['receiver_id'] == $user1_id)) {
            $conversation[] = $message;
        }
    }
    
    // Sort messages by date (oldest first for conversations)
    usort($conversation, function($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
    
    return $conversation;
}

// Function to send a message
function send_message($sender_id, $receiver_id, $subject, $content) {
    global $messages_file, $data_dir;
    
    // Ensure data directory exists
    if (!file_exists($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    
    $messages = get_all_messages();
    
    $new_message = [
        'message_id' => uniqid('msg_'),
        'sender_id' => $sender_id,
        'receiver_id' => $receiver_id,
        'subject' => $subject,
        'content' => $content,
        'is_read' => false,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $messages[] = $new_message;
    
    $json = json_encode($messages, JSON_PRETTY_PRINT);
    return file_put_contents($messages_file, $json) !== false;
}

// Function to mark message as read
function mark_message_as_read($message_id) {
    global $messages_file;
    
    $messages = get_all_messages();
    $updated = false;
    
    foreach ($messages as $key => $message) {
        if ($message['message_id'] === $message_id) {
            $messages[$key]['is_read'] = true;
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        $json = json_encode($messages, JSON_PRETTY_PRINT);
        return file_put_contents($messages_file, $json) !== false;
    }
    
    return false;
}

// Function to get unread message count for a user
function get_unread_message_count($user_id) {
    $messages = get_all_messages();
    $count = 0;
    
    foreach ($messages as $message) {
        if ($message['receiver_id'] == $user_id && $message['is_read'] === false) {
            $count++;
        }
    }
    
    return $count;
}

// Function to delete a message
function delete_message($message_id, $user_id) {
    global $messages_file;
    
    $messages = get_all_messages();
    $updated = false;
    
    foreach ($messages as $key => $message) {
        if ($message['message_id'] === $message_id && 
            ($message['sender_id'] == $user_id || $message['receiver_id'] == $user_id)) {
            // Remove the message
            unset($messages[$key]);
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        // Reindex array
        $messages = array_values($messages);
        $json = json_encode($messages, JSON_PRETTY_PRINT);
        return file_put_contents($messages_file, $json) !== false;
    }
    
    return false;
}

// ===== ACCOUNT REQUEST FUNCTIONS =====

// Function to get all account requests
function get_account_requests() {
    global $account_requests_file;
    
    if (file_exists($account_requests_file)) {
        $json = file_get_contents($account_requests_file);
        return json_decode($json, true) ?: [];
    }
    
    return [];
}

// Function to get account request by ID
function get_account_request_by_id($request_id) {
    $requests = get_account_requests();
    
    foreach ($requests as $request) {
        if ($request['request_id'] === $request_id) {
            return $request;
        }
    }
    
    return null;
}

// Function to save a new account request
function save_account_request($request_data) {
    global $account_requests_file, $data_dir;
    
    // Ensure data directory exists
    if (!file_exists($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    
    // Get existing requests or initialize empty array
    $requests = get_account_requests();
    $requests[] = $request_data;
    
    // Encode and save
    $json = json_encode($requests, JSON_PRETTY_PRINT);
    return file_put_contents($account_requests_file, $json) !== false;
}

// Function to update account request status
function update_account_request_status($request_id, $status, $admin_comment = '') {
    global $account_requests_file;
    
    $requests = get_account_requests();
    $updated = false;
    
    foreach ($requests as &$request) {
        if ($request['request_id'] === $request_id) {
            $request['status'] = $status;
            $request['admin_comment'] = $admin_comment;
            $request['updated_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        $json = json_encode($requests, JSON_PRETTY_PRINT);
        return file_put_contents($account_requests_file, $json) !== false;
    }
    
    return false;
}

// Function to approve account request and create user
function approve_account_request($request_id, $admin_comment = '') {
    $request = get_account_request_by_id($request_id);
    
    if (!$request || $request['status'] !== 'pending') {
        return false;
    }
    
    // Generate a random password
    $password = bin2hex(random_bytes(4)); // 8 characters
    
    // Create user from request
    $user_data = [
        'user_id' => uniqid('user_'),
        'full_name' => $request['full_name'],
        'email' => $request['email'],
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $request['role_requested'],
        'department' => $request['department'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $user_added = add_user($user_data);
    
    if ($user_added) {
        // Update request status
        $updated = update_account_request_status($request_id, 'approved', $admin_comment);
        
        if ($updated) {
            // Create notification for the user (will be visible after they log in)
            $notification_data = [
                'notification_id' => uniqid('notif_'),
                'user_id' => $user_data['user_id'],
                'type' => 'account_created',
                'message' => "Your account request has been approved. Your temporary password is: {$password}",
                'reference_id' => $request_id,
                'is_read' => false,
                'created_at' => date('Y-m-d H:i:s')
            ];
            add_notification($notification_data);
            
            return ['success' => true, 'password' => $password];
        }
    }
    
    return false;
}

// Function to reject account request
function reject_account_request($request_id, $admin_comment = '') {
    return update_account_request_status($request_id, 'rejected', $admin_comment);
}

// ===== INVENTORY MANAGEMENT FUNCTIONS =====

// Function to get all inventory items
function get_inventory_items() {
    global $inventory_file;
    
    if (!file_exists($inventory_file)) {
        return [];
    }
    
    $data = file_get_contents($inventory_file);
    return json_decode($data, true) ?: [];
}

// Function to get inventory item by ID
function get_inventory_item_by_id($item_id) {
    $inventory = get_inventory_items();
    
    foreach ($inventory as $item) {
        if ($item['item_id'] == $item_id) {
            return $item;
        }
    }
    
    return null;
}

// Function to add a new inventory item
function add_inventory_item($item_data) {
    global $inventory_file;
    
    $inventory = get_inventory_items();
    
    // Generate new item ID
    $max_id = 0;
    foreach ($inventory as $item) {
        if ($item['item_id'] > $max_id) {
            $max_id = $item['item_id'];
        }
    }
    
    $item_data['item_id'] = $max_id + 1;
    $item_data['created_at'] = date('Y-m-d H:i:s');
    
    // Add to inventory array
    $inventory[] = $item_data;
    
    // Save to file
    return file_put_contents($inventory_file, json_encode($inventory, JSON_PRETTY_PRINT)) !== false;
}

// Function to update inventory item
function update_inventory_item($item_id, $item_data) {
    global $inventory_file;
    
    $inventory = get_inventory_items();
    $updated = false;
    
    foreach ($inventory as $key => $item) {
        if ($item['item_id'] == $item_id) {
            // Preserve item_id and created_at
            $item_data['item_id'] = $item['item_id'];
            $item_data['created_at'] = $item['created_at'];
            
            // Update item
            $inventory[$key] = $item_data;
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        // Save to file
        $result = file_put_contents($inventory_file, json_encode($inventory, JSON_PRETTY_PRINT)) !== false;
        
        // Check if stock level is below reorder level
        if ($item_data['stock_level'] <= $item_data['reorder_level']) {
            // Create notification for procurement team
            $users = get_users();
            foreach ($users as $user) {
                if ($user['role'] === 'procurement' || $user['role'] === 'admin') {
                    $message = "Low stock alert: " . $item_data['item_name'] . " is below reorder level. Current stock: " . 
                              $item_data['stock_level'] . " " . $item_data['unit'] . "s.";
                    add_notification($user['user_id'], $message);
                }
            }
        }
        
        return $result;
    }
    
    return false;
}

// Function to update stock level
function update_stock_level($item_id, $quantity_change) {
    $item = get_inventory_item_by_id($item_id);
    
    if ($item) {
        $item['stock_level'] += $quantity_change;
        if ($item['stock_level'] < 0) {
            $item['stock_level'] = 0;
        }
        
        return update_inventory_item($item_id, $item);
    }
    
    return false;
}

?>
