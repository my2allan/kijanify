<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'finance') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $cash_request_id = $_GET['id'];

    // Fetch the cash request details
    $stmt = $pdo->prepare("SELECT * FROM cash_requests WHERE id = ?");
    $stmt->execute([$cash_request_id]);
    $cash_request = $stmt->fetch();

    if ($cash_request && $cash_request['status'] == 'approved_by_hod') {
        // Update the cash request status to disbursed
        $stmt = $pdo->prepare("UPDATE cash_requests SET status = 'disbursed' WHERE id = ?");
        $stmt->execute([$cash_request_id]);

        // Log the disbursement action
        $stmt = $pdo->prepare("
            INSERT INTO cash_request_approvals (request_id, department_id, user_id, status, approved_at)
            VALUES (?, ?, ?, 'disbursed', NOW())
        ");
        $stmt->execute([
            $cash_request_id,          // Request ID
            $cash_request['department_id'], // Department ID
            $_SESSION['user_id']        // User ID of the finance approver
        ]);

        // Redirect back to the dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        die("This request must be approved by the Head of Department first or is already disbursed.");
    }
}
?>
