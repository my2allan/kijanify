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
            echo "Your account is disabled. Please contact the administrator.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
        }
    } else {
        echo "Invalid login credentials.";
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
        echo "Username already exists.";
    } else {
        // Check if the selected department already has a Head of Department
        if ($role == 'headofdepartment') {
            $stmt = $pdo->prepare("SELECT * FROM departments WHERE head_of_department_id IS NOT NULL AND id = ?");
            $stmt->execute([$department_id]);
            if ($stmt->rowCount() > 0) {
                echo "This department already has a Head of Department.";
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

        echo "Registration successful. You can now <a href='login.php'>login</a>.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    <form method="post" action="auth.php">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <br>
        <button type="submit" name="login">Login</button>
    </form>
    <p>Don't have an account? <a href="register.php">Register here</a>.</p>
</body>
</html>
