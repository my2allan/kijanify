<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'finance') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $budget_id = $_GET['id'];

    // Fetch the budget item details
    $stmt = $pdo->prepare("SELECT * FROM budgets WHERE id = ?");
    $stmt->execute([$budget_id]);
    $budget = $stmt->fetch();

    if ($budget) {
        // Reject the budget
        $stmt = $pdo->prepare("UPDATE budgets SET status = 'rejected', rejected_by = ?, rejected_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $budget_id]);

        header("Location: dashboard.php");
        exit();
    } else {
        echo "Budget item not found.";
    }
} else {
    echo "Invalid request.";
}
?>
