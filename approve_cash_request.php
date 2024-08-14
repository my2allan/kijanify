<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the role of the current user
$user_role = $_SESSION['role'];

if (isset($_GET['id'])) {
    $cash_request_id = $_GET['id'];

    // Fetch the cash request details
    $stmt = $pdo->prepare("SELECT * FROM cash_requests WHERE id = ?");
    $stmt->execute([$cash_request_id]);
    $cash_request = $stmt->fetch();

    if ($cash_request && $cash_request['status'] == 'pending') {
        if ($user_role == 'headofdepartment') {
            // Update the cash request status to approved_by_hod
            $stmt = $pdo->prepare("UPDATE cash_requests SET status = 'approved_by_hod' WHERE id = ?");
            $stmt->execute([$cash_request_id]);

            // Log the approval action
            $stmt = $pdo->prepare("
                INSERT INTO cash_request_approvals (request_id, department_id, user_id, status, approved_at)
                VALUES (?, ?, ?, 'approved_by_hod', NOW())
            ");
            $stmt->execute([
                $cash_request_id,          // Request ID
                $cash_request['department_id'], // Department ID
                $_SESSION['user_id']        // User ID of the approver (Head of Department)
            ]);
        } elseif ($user_role == 'finance') {
            // Check if the request has been approved by the Head of Department
            if ($cash_request['status'] == 'approved_by_hod') {
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
                    $_SESSION['user_id']        // User ID of the approver (Finance)
                ]);
            } else {
                die("This request must be approved by the Head of Department first.");
            }
        }

        // Redirect back to the dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        die("Invalid cash request.");
    }
}
?>
