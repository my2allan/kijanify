<?php
require 'config.php';

if (isset($_GET['department_id'])) {
    $department_id = $_GET['department_id'];

    // Fetch available funds from the budgets table
    $stmt = $pdo->prepare("SELECT SUM(amount) AS available_funds FROM budgets WHERE department_id = ? AND status = 'approved'");
    $stmt->execute([$department_id]);
    $available_funds = $stmt->fetchColumn();

    echo json_encode($available_funds);
}
?>
