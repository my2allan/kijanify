<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'finance') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $budget_id = $_GET['id'];

    // Fetch the budget details
    $stmt = $pdo->prepare("SELECT * FROM budgets WHERE id = ?");
    $stmt->execute([$budget_id]);
    $budget = $stmt->fetch();

    if ($budget && $budget['status'] == 'pending') {
        // Update the budget status to approved, and record who approved it and when
        $stmt = $pdo->prepare("
            UPDATE budgets 
            SET status = 'approved', approved_by = ?, approved_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $budget_id]);

        // Insert into the cash_requests table
        $stmt = $pdo->prepare("
            INSERT INTO cash_requests (user_id, department_id, budget_id, amount, status, created_at)
            VALUES (?, ?, ?, ?, 'approved', NOW())
        ");
        $stmt->execute([
            $budget['submitted_by'],  // User ID (Head of Department)
            $budget['department_id'], // Department ID
            $budget_id,                // Budget ID
            $budget['cost'],           // Amount
        ]);

        // Redirect back to the dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        die("Invalid budget request.");
    }
}
?>
