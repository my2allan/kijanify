<?php
require 'config.php';

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$request_id = $_GET['id'];

$stmt = $pdo->prepare("UPDATE cash_requests SET status = 'rejected', rejected_by = ?, rejected_at = NOW() WHERE id = ?");
$stmt->execute([$_SESSION['user_id'], $request_id]);

header("Location: dashboard.php");
