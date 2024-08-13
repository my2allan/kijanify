<?php
require 'config.php';

$department_id = $_GET['department_id'];

// Fetch inventory items
$stmt = $pdo->prepare("SELECT item_name, quantity FROM inventory WHERE department_id = ?");
$stmt->execute([$department_id]);
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch cash requests
$stmt = $pdo->prepare("SELECT item, cost, status FROM cash_requests WHERE department_id = ?");
$stmt->execute([$department_id]);
$cash_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return combined data
echo json_encode(['inventory' => $inventory, 'cash_requests' => $cash_requests]);
