<?php
session_start();
include_once ("includes/config.php");

if (!isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit();
}

// Get stats using our file-based functions
$users = get_users();
$total_users = count($users);

// For now, we'll set these to 0 since we haven't implemented requisitions yet
$total_requisitions = 0;
$pending_requisitions = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php
    // include_once ("na")
    require 'nav.php';
    ?>

<div class="container mt-4">
    <h3>Admin Dashboard</h3>
    <div class="row">
        <div class="col-md-4"><div class="card bg-primary text-white"><div class="card-body">Total Users: <?= $total_users ?></div></div></div>
        <div class="col-md-4"><div class="card bg-success text-white"><div class="card-body">Total Requisitions: <?= $total_requisitions ?></div></div></div>
        <div class="col-md-4"><div class="card bg-warning text-dark"><div class="card-body">Pending Approvals: <?= $pending_requisitions ?></div></div></div>
    </div>

    <h5 class="mt-4">Manage Users</h5>
    <div class="mb-3">
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-plus"></i> Add New User</button>
        <a href="manage_user_rights.php" class="btn btn-sm btn-success"><i class="bi bi-shield-lock"></i> Manage User Rights</a>
    </div>
    <table class="table table-bordered">
        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Department</th><th>Role</th><th>Action</th></tr></thead>
        <tbody>
            <?php
            // Get all users from our file-based storage
            $users = get_users();
            
            foreach ($users as $user) {
                echo "
                <tr>
                    <td>{$user['user_id']}</td>
                    <td>{$user['full_name']}</td>
                    <td>{$user['email']}</td>
                    <td>{$user['department']}</td>
                    <td>{$user['role']}</td>
                    <td>
                    <button class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#editUserModal' 
                        data-id='{$user['user_id']}'
                        data-name='{$user['full_name']}' 
                        data-email='{$user['email']}' 
                        data-department='{$user['department']}'  
                        data-role='{$user['role']}'>Edit</button>
                    <button class='btn btn-sm btn-danger' onclick='deleteUser({$user['user_id']})'>Delete</button>       
                </td>
                </tr>
                ";
            } 
            ?>
        </tbody>
    </table>
    <script>
function deleteUser(userId) {
    if (confirm("Are you sure you want to delete this user?")) {
        // You can make an AJAX call to delete the user from the database
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "delete_user.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        alert(response.message);
                        if (response.success) {
                            location.reload();
                        }
                    }
        };
        xhr.send(`action=delete&id=${userId}`);
    }
}

    </script>

    <!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="add_users.php" method="post">
                    <!-- <input type="hidden" name="action" value="add"> Add this line -->
                    <div class="mb-3">
                        <label for="username" class="form-label">Name</label>
                        <input type="text" name="username" class="form-control" id="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="useremail" class="form-label">Email</label>
                        <input type="email" name="useremail" class="form-control" id="useremail" required>
                    </div>
                    <div class="mb-3">
                        <label for="userRole" class="form-label">Role</label>
                        <select class="form-select" name="userRole" id="userRole">
                            <option value="admin">Administrator</option>
                            <option value="hod">Head of Department (HOD)</option>
                            <option value="staff">Staff</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" name="department" class="form-control" id="department" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" id="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="manage-users.php" method="post">
                    <input type="hidden" name="action" value="update"> <!-- Add this line -->
                    <input type="hidden" name="id" id="editId">
                    <div class="mb-3">
                        <label for="editName" class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" id="editName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" id="editEmail" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDepartment" class="form-label">Department</label>
                        <input type="text" name="department" class="form-control" id="editDepartment" required>
                    </div>
                    <div class="mb-3">
                        <label for="editRole" class="form-label">Role</label>
                        <select class="form-select" name="role" id="editRole">
                            <option value="admin">Administrator</option>
                            <option value="hod">Head of Department (HOD)</option>
                            <option value="staff">Staff</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
        // Populate the edit modal with the user data
        var editUserModal = document.getElementById('editUserModal');
        editUserModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
           
            var userId = button.getAttribute('data-id');
            var fullName = button.getAttribute('data-name');
            var email = button.getAttribute('data-email');
            var department = button.getAttribute('data-department');
            var role = button.getAttribute('data-role');
           
            document.getElementById('editId').value = userId;
            document.getElementById('editName').value = fullName;
            document.getElementById('editEmail').value = email;
            document.getElementById('editDepartment').value = department;
            document.getElementById('editRole').value = role;
        });
    </script>

</body>
</html>