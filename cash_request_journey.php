<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$cash_request_id = $_GET['id'] ?? null;

if (!$cash_request_id) {
    die("Invalid request ID.");
}

// Fetch the cash request details
$stmt = $pdo->prepare("
    SELECT cr.*, b.item AS budget_item, b.cost AS budget_cost, 
           u.username AS submitted_by_name, d.name AS department_name 
    FROM cash_requests cr 
    JOIN budgets b ON cr.budget_id = b.id 
    JOIN users u ON cr.user_id = u.id 
    JOIN departments d ON cr.department_id = d.id 
    WHERE cr.id = ?
");
$stmt->execute([$cash_request_id]);
$cash_request = $stmt->fetch();

if (!$cash_request) {
    die("Cash request not found.");
}

// Fetch approval details (Head of Department and Finance)
$stmt = $pdo->prepare("
    SELECT cra.*, u.username AS approver_name 
    FROM cash_request_approvals cra 
    JOIN users u ON cra.user_id = u.id 
    WHERE cra.request_id = ?
    ORDER BY cra.approved_at DESC
");
$stmt->execute([$cash_request_id]);
$approvals = $stmt->fetchAll();

// Extract the most recent approval or rejection action
$last_action = $approvals[0] ?? null;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Request Journey</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .timeline {
            position: relative;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px 0;
        }
        .timeline::after {
            content: '';
            position: absolute;
            width: 6px;
            background-color: #D3D3D3;
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -3px;
        }
        .container-timeline {
            padding: 10px 40px;
            position: relative;
            background-color: inherit;
            width: 50%;
        }
        .container-timeline.left {
            left: 0;
        }
        .container-timeline.right {
            left: 50%;
        }
        .container-timeline::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            right: -17px;
            background-color: white;
            border: 4px solid #D3D3D3;
            top: 15px;
            border-radius: 50%;
            z-index: 1;
        }
        .right::after {
            left: -16px;
        }
        .content {
            padding: 20px;
            background-color: white;
            position: relative;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .status-approved {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .status-rejected {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .status-pending {
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Cash Request Journey</h2>
        <div class="timeline">
            <div class="container-timeline left">
                <div class="content">
                    <h5>Request Submitted</h5>
                    <p><strong>Reason for Funds:</strong> <?php echo htmlspecialchars($cash_request['budget_item']); ?></p>
                    <p><strong>Requested Amount:</strong> UGX <?php echo number_format($cash_request['amount'], 2); ?></p>
                    <p><strong>Budgeted Amount:</strong> UGX <?php echo number_format($cash_request['budget_cost'], 2); ?></p>
                    <p><strong>Submitted By:</strong> <?php echo htmlspecialchars($cash_request['submitted_by_name']); ?> (<?php echo htmlspecialchars($cash_request['department_name']); ?>)</p>
                    <p><strong>Date Submitted:</strong> <?php echo htmlspecialchars($cash_request['created_at']); ?></p>
                </div>
            </div>

            <?php foreach ($approvals as $approval): ?>
                <div class="container-timeline right">
                    <div class="content <?php echo 'status-' . strtolower($approval['status']); ?>">
                        <h5><?php echo ucfirst($approval['status']); ?> by <?php echo htmlspecialchars($approval['approver_name']); ?></h5>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($approval['approved_at'] ?? $approval['rejected_at']); ?></p>
                        <?php if (!empty($approval['approval_reason'])): ?>
                            <p><strong>Reason:</strong> <?php echo htmlspecialchars($approval['approval_reason']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($approvals)): ?>
                <div class="container-timeline right">
                    <div class="content status-pending">
                        <h5>Pending Approval</h5>
                        <p>This request is still awaiting action.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Display the disbursement status -->
            <?php if ($cash_request['status'] == 'disbursed'): ?>
                <div class="container-timeline left">
                    <div class="content status-approved">
                        <h5>Finance Disbursement</h5>
                        <p><strong>Status:</strong> Disbursed</p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($last_action['approved_at']); ?></p>
                    </div>
                </div>
            <?php elseif ($cash_request['status'] == 'rejected'): ?>
                <div class="container-timeline left">
                    <div class="content status-rejected">
                        <h5>Finance Rejection</h5>
                        <p><strong>Status:</strong> Rejected</p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($last_action['rejected_at']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <a href="dashboard.php" class="btn btn-primary mt-4">Back to Dashboard</a>
    </div>
    <!-- Optional JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
