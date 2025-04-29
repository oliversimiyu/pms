<?php
require_once("includes/config.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? null;

    if ($action === "delete") {
        // Handle delete user
        $userId = intval($_POST['id'] ?? 0);

        if ($userId > 0) {
            // Use our file-based delete_user function
            if (delete_user($userId)) {
                echo json_encode([
                    "success" => true,
                    "message" => "User deleted successfully!"
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Error deleting user."
                ]);
            }
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Invalid user ID."
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid action."
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
}
?>