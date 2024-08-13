<?php
require 'config.php';

// Check if the user is an admin or root user
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'root') {
    header("Location: dashboard.php");
    exit();
}

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();

// Fetch all departments
$departmentsStmt = $pdo->query("SELECT id, name FROM departments");
$departments = $departmentsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    $new_department_id = $_POST['department_id'];
    $new_password = $_POST['password'];
    $disable_account = isset($_POST['disable']) ? 1 : 0;

    // Update user role
    $stmt = $pdo->prepare("UPDATE users SET role = ?, disabled = ? WHERE id = ?");
    $stmt->execute([$new_role, $disable_account, $user_id]);

    // Update head of department if applicable
    if ($new_role == 'headofdepartment') {
        $stmt = $pdo->prepare("UPDATE departments SET head_of_department_id = ? WHERE id = ?");
        $stmt->execute([$user_id, $new_department_id]);
    }

    // Update password if provided
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);
    }

    header("Location: manage_users.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between mb-4">
            <h1>Manage Users</h1>
            <div>
                <a href="register.php" class="btn btn-primary">Register User</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        <table id="users_table" class="table table-bordered">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td>
                            <?php
                            $stmt = $pdo->prepare("SELECT name FROM departments WHERE head_of_department_id = ?");
                            $stmt->execute([$user['id']]);
                            $department = $stmt->fetchColumn();
                            echo htmlspecialchars($department) ? htmlspecialchars($department) : 'N/A';
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#editUserModal<?php echo $user['id']; ?>">Edit</button>
                            <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editUserModalLabel<?php echo $user['id']; ?>">Edit User</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form method="post" action="manage_users.php">
                                            <div class="modal-body">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <div class="form-group">
                                                    <label for="role">Role:</label>
                                                    <select class="form-control" name="role" required>
                                                        <option value="user" <?php if ($user['role'] == 'user') echo 'selected'; ?>>User</option>
                                                        <option value="headofdepartment" <?php if ($user['role'] == 'headofdepartment') echo 'selected'; ?>>Head of Department</option>
                                                        <option value="finance" <?php if ($user['role'] == 'finance') echo 'selected'; ?>>Finance</option>
                                                        <option value="admin" <?php if ($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                                                        <option value="root" <?php if ($user['role'] == 'root') echo 'selected'; ?>>Root</option>
                                                    </select>
                                                </div>
                                                <div class="form-group" style="display: <?php echo $user['role'] == 'headofdepartment' ? 'block' : 'none'; ?>">
                                                    <label for="department_id">Department:</label>
                                                    <select class="form-control" name="department_id">
                                                        <option value="">Select Department</option>
                                                        <?php foreach ($departments as $department): ?>
                                                            <option value="<?php echo $department['id']; ?>" <?php if (isset($department['head_of_department_id']) && $department['head_of_department_id'] == $user['id']) echo 'selected'; ?>><?php echo htmlspecialchars($department['name']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="password">New Password:</label>
                                                    <input type="password" class="form-control" name="password">
                                                </div>
                                                <div class="form-group">
                                                    <label for="disable">Disable Account:</label>
                                                    <input type="checkbox" name="disable" <?php if ($user['disabled']) echo 'checked'; ?>>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#users_table').DataTable();
        });
    </script>
</body>
</html>
