<?php
require 'config.php';

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'root', 'headofdepartment'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $email = $_POST['email'];
    $role = $_POST['role'];
    $department_id = isset($_POST['department_id']) ? $_POST['department_id'] : null;

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $error = "Username already exists.";
    } else {
        // Check if the selected department already has a Head of Department
        if ($role == 'headofdepartment') {
            $stmt = $pdo->prepare("SELECT * FROM departments WHERE head_of_department_id IS NOT NULL AND id = ?");
            $stmt->execute([$department_id]);
            if ($stmt->rowCount() > 0) {
                $error = "This department already has a Head of Department.";
            } else {
                // Insert new user
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $password, $email, $role]);
                $user_id = $pdo->lastInsertId();

                // Update the department with the new Head of Department
                $stmt = $pdo->prepare("UPDATE departments SET head_of_department_id = ? WHERE id = ?");
                $stmt->execute([$user_id, $department_id]);

                $success = "Registration successful. You can now <a href='login.php'>login</a>.";
            }
        } else {
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $password, $email, $role]);

            $success = "Registration successful. You can now <a href='login.php'>login</a>.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h1 class="text-center mb-4">Register</h1>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <form method="post" action="register.php">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" class="form-control" name="username" id="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" class="form-control" name="password" id="password" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" class="form-control" name="email" id="email" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select class="form-control" name="role" id="role" required>
                            <option value="user">User</option>
                            <option value="headofdepartment">Head of Department</option>
                            <option value="finance">Finance</option>
                            <option value="admin">Admin</option>
                            <option value="root">Root</option>
                        </select>
                    </div>
                    <div class="form-group" id="department-group" style="display: none;">
                        <label for="department_id">Department:</label>
                        <select class="form-control" name="department_id" id="department_id">
                            <option value="">Select Department</option>
                            <?php
                            $stmt = $pdo->query("SELECT id, name FROM departments");
                            while ($row = $stmt->fetch()) {
                                echo "<option value=\"{$row['id']}\">{$row['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Register</button>
                </form>
                <p class="text-center mt-3">Already have an account? <a href="login.php">Login here</a>.</p>
            </div>
        </div>
    </div>
    <!-- Optional JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#role').change(function() {
                if ($(this).val() === 'headofdepartment') {
                    $('#department-group').show();
                } else {
                    $('#department-group').hide();
                }
            });
        });
    </script>
</body>
</html>
