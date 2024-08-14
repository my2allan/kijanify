<?php

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['disabled']) {
            $error = "Your account is disabled. Please contact the administrator.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username']; // Add this line to store the username in the session
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
            exit();
        }
    } else {
        $error = "Invalid login credentials.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['login'])) {
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
                exit();
            }
        }

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password, $email, $role]);
        $user_id = $pdo->lastInsertId();

        // Update the department with the new Head of Department
        if ($role == 'headofdepartment') {
            $stmt = $pdo->prepare("UPDATE departments SET head_of_department_id = ? WHERE id = ?");
            $stmt->execute([$user_id, $department_id]);
        }

        $success = "Registration successful. You can now <a href='login.php'>login</a>.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h1 class="text-center mb-4">Login</h1>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <form method="post" action="login.php">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" class="form-control" name="username" id="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" class="form-control" name="password" id="password" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary btn-block">Login</button>
                </form>
                <p class="text-center mt-3">Don't have an account? <a href="#">Contact Administrator</a>.</p>
            </div>
        </div>
    </div>
</body>
</html>
