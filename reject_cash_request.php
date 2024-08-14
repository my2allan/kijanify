<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'headofdepartment') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $cash_request_id = $_GET['id'];

    // Fetch the cash request details
    $stmt = $pdo->prepare("SELECT * FROM cash_requests WHERE id = ?");
    $stmt->execute([$cash_request_id]);
    $cash_request = $stmt->fetch();

    if ($cash_request && $cash_request['status'] == 'pending') {
        // Update the cash request status to rejected
        $stmt = $pdo->prepare("UPDATE cash_requests SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$cash_request_id]);

        // Log the rejection action in cash_request_approvals table
        $stmt = $pdo->prepare("
            INSERT INTO cash_request_approvals (request_id, user_id, department_id, status, rejected_at)
            VALUES (?, ?, ?, 'rejected', NOW())
        ");
        $stmt->execute([
            $cash_request_id,             // Cash Request ID
            $_SESSION['user_id'],         // User ID of the rejector (Head of Department)
            $cash_request['department_id'] // Department ID
        ]);

        // Redirect back to the dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        die("Invalid cash request.");
    }
}
?>
