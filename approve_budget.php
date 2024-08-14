<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'finance') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $budget_id = $_GET['id'];
    $user_id = $_SESSION['user_id']; // The finance user's ID

    // Fetch the budget details
    $stmt = $pdo->prepare("SELECT * FROM budgets WHERE id = ?");
    $stmt->execute([$budget_id]);
    $budget = $stmt->fetch();

    if ($budget && $budget['status'] == 'pending') {
        // Update the budget status to approved and set the approved_by and approved_at fields
        $stmt = $pdo->prepare("UPDATE budgets SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$user_id, $budget_id]);

        // Redirect back to the dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        die("Invalid budget request.");
    }
}
?>
