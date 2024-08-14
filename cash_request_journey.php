<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $cash_request_id = $_GET['id'];

    // Fetch the cash request details
    $stmt = $pdo->prepare("
        SELECT cr.*, u.username AS created_by, b.item AS budget_item, b.cost AS budget_cost, d.name AS department_name 
        FROM cash_requests cr 
        JOIN users u ON cr.user_id = u.id 
        JOIN budgets b ON cr.budget_id = b.id 
        JOIN departments d ON cr.department_id = d.id 
        WHERE cr.id = ?
    ");
    $stmt->execute([$cash_request_id]);
    $cash_request = $stmt->fetch();

    if ($cash_request) {
        // Fetch the approval/rejection history
        $stmt = $pdo->prepare("
            SELECT * FROM cash_request_approvals 
            WHERE request_id = ?
            ORDER BY approved_at ASC, rejected_at ASC
        ");
        $stmt->execute([$cash_request_id]);
        $approvals = $stmt->fetchAll();
    } else {
        die("Invalid cash request.");
    }
} else {
    die("No cash request ID provided.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Request Journey</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Cash Request Journey</h2>
        <p><strong>Reason for Funds:</strong> <?php echo htmlspecialchars($cash_request['budget_item']); ?></p>
        <p><strong>Requested Amount:</strong> UGX <?php echo number_format($cash_request['amount'], 2); ?></p>
        <p><strong>Budgeted Amount:</strong> UGX <?php echo number_format($cash_request['budget_cost'], 2); ?></p>
        <p><strong>Created By:</strong> <?php echo htmlspecialchars($cash_request['created_by']); ?></p>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($cash_request['department_name']); ?></p>
        <p><strong>Date Submitted:</strong> <?php echo htmlspecialchars($cash_request['created_at']); ?></p>

        <h4>Approval Journey</h4>
        <ul class="timeline">
            <li>
                <strong>Created:</strong> <?php echo htmlspecialchars($cash_request['created_at']); ?> by <?php echo htmlspecialchars($cash_request['created_by']); ?>
            </li>
            <?php foreach ($approvals as $approval): ?>
                <?php if ($approval['approved_at']): ?>
                    <li>
                        <strong>Approved:</strong> <?php echo htmlspecialchars($approval['approved_at']); ?> by <?php echo htmlspecialchars($approval['username']); ?> (Head of Department)
                    </li>
                <?php endif; ?>
                <?php if ($approval['rejected_at']): ?>
                    <li>
                        <strong>Rejected:</strong> <?php echo htmlspecialchars($approval['rejected_at']); ?> by <?php echo htmlspecialchars($approval['username']); ?> (Head of Department)
                    </li>
                <?php endif; ?>
                <?php if ($approval['status'] == 'disbursed'): ?>
                    <li>
                        <strong>Disbursed:</strong> <?php echo htmlspecialchars($approval['approved_at']); ?> by <?php echo htmlspecialchars($approval['username']); ?> (Finance)
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
    <!-- Optional JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
