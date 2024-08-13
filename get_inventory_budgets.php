<?php
require 'config.php';

if (isset($_GET['department_id'])) {
    $department_id = $_GET['department_id'];

    // Fetch inventory items for the selected department
    $stmt = $pdo->prepare("SELECT item_name AS name, quantity FROM inventory WHERE department_id = ?");
    $stmt->execute([$department_id]);
    $inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch approved and pending budget items for the selected department
    $stmt = $pdo->prepare("SELECT item AS name, amount, status FROM budgets WHERE department_id = ? AND (status = 'approved' OR status = 'pending')");
    $stmt->execute([$department_id]);
    $budget_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['inventory' => $inventory_items, 'budgets' => $budget_items]);
}
?>
