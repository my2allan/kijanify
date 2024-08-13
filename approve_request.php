<?php
require 'config.php';
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $pdo->prepare("UPDATE requests SET status = 'approved' WHERE id = ?");
    $stmt->execute([$id]);

    // Get user email for notification
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = (SELECT user_id FROM requests WHERE id = ?)");
    $stmt->execute([$id]);
    $email = $stmt->fetchColumn();

    mail($email, "Request Approved", "Your request has been approved.");

    header("Location: dashboard.php");
}
?>
