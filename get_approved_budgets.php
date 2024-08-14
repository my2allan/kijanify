<?php
require 'config.php';

if (isset($_GET['department_id'])) {
    $department_id = $_GET['department_id'];

    $stmt = $pdo->prepare("SELECT item, cost FROM budgets WHERE department_id = ? AND status = 'approved'");
    $stmt->execute([$department_id]);
    $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($budgets);
}
?>
