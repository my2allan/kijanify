<?php
require 'config.php';

if (isset($_GET['department_id'])) {
    $department_id = $_GET['department_id'];

    $stmt = $pdo->prepare("SELECT item_name, quantity FROM inventory WHERE department_id = ?");
    $stmt->execute([$department_id]);
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($inventory);
}
?>
