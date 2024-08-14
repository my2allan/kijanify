<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Initialize variables
$requests = [];
$budgets = [];
$cash_requests = [];
$totalPagesRequests = 1;
$totalPagesBudgets = 1;
$totalPagesCashRequests = 1;

if ($role == 'user') {
    // Fetch user inventory requests
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, ic.quantity_change 
        FROM requests r 
        JOIN users u ON r.user_id = u.id 
        LEFT JOIN inventory_changes ic ON r.id = ic.request_id 
        WHERE r.user_id = ? AND r.status != 'cash'
        ORDER BY r.id DESC
    ");
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll();

    // Fetch user cash requests
    $stmt = $pdo->prepare("
        SELECT cr.*, b.item AS budget_item, b.cost AS budget_cost 
        FROM cash_requests cr 
        JOIN budgets b ON cr.budget_id = b.id 
        WHERE cr.user_id = ?
        ORDER BY cr.id DESC
    ");
    $stmt->execute([$user_id]);
    $cash_requests = $stmt->fetchAll();
} elseif ($role == 'headofdepartment') {
    // Fetch department inventory requests
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, ic.quantity_change 
        FROM requests r 
        JOIN users u ON r.user_id = u.id 
        LEFT JOIN inventory_changes ic ON r.id = ic.request_id 
        WHERE r.department_id = (SELECT id FROM departments WHERE head_of_department_id = ?)
        ORDER BY r.id DESC
    ");
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll();

    // Fetch department budgets
    $stmt = $pdo->prepare("
        SELECT * FROM budgets 
        WHERE department_id = (SELECT id FROM departments WHERE head_of_department_id = ?)
        ORDER BY id DESC
    ");
    $stmt->execute([$user_id]);
    $budgets = $stmt->fetchAll();

    // Fetch department cash requests
    $stmt = $pdo->prepare("
        SELECT cr.*, u.username, b.item AS budget_item, b.cost AS budget_cost 
        FROM cash_requests cr 
        JOIN users u ON cr.user_id = u.id 
        JOIN budgets b ON cr.budget_id = b.id 
        WHERE cr.department_id = (SELECT id FROM departments WHERE head_of_department_id = ?)
        ORDER BY cr.id DESC
    ");
    $stmt->execute([$user_id]);
    $cash_requests = $stmt->fetchAll();
} elseif ($role == 'finance') {
    // Fetch all budgets for finance role, showing all statuses
    $stmt = $pdo->query("
        SELECT b.*, u.username AS submitted_by_name 
        FROM budgets b 
        LEFT JOIN users u ON b.submitted_by = u.id 
        ORDER BY b.id DESC
    ");
    $budgets = $stmt->fetchAll();

    // Fetch all cash requests for finance role, showing all statuses
    $stmt = $pdo->query("
        SELECT cr.*, b.item AS budget_item, b.cost AS budget_cost, u.username AS submitted_by_name, d.name AS department_name 
        FROM cash_requests cr 
        JOIN budgets b ON cr.budget_id = b.id 
        JOIN users u ON cr.user_id = u.id 
        JOIN departments d ON cr.department_id = d.id 
        ORDER BY cr.id DESC
    ");
    $cash_requests = $stmt->fetchAll();
} elseif ($role == 'admin' || $role == 'root') {
    // Fetch all inventory requests
    $stmt = $pdo->query("
        SELECT r.*, u.username, ic.quantity_change 
        FROM requests r 
        JOIN users u ON r.user_id = u.id 
        LEFT JOIN inventory_changes ic ON r.id = ic.request_id
        ORDER BY r.id DESC
    ");
    $requests = $stmt->fetchAll();

    // Fetch all budgets
    $stmt = $pdo->query("SELECT * FROM budgets ORDER BY id DESC");
    $budgets = $stmt->fetchAll();

    // Fetch all cash requests
    $stmt = $pdo->query("
        SELECT cr.*, u.username, b.item AS budget_item, b.cost AS budget_cost 
        FROM cash_requests cr 
        JOIN users u ON cr.user_id = u.id 
        JOIN budgets b ON cr.budget_id = b.id 
        ORDER BY cr.id DESC
    ");
    $cash_requests = $stmt->fetchAll();
}

// Function to handle pagination
function paginate($items, $page = 1, $perPage = 10) {
    $totalItems = count($items);
    $totalPages = ceil($totalItems / $perPage);
    $offset = ($page - 1) * $perPage;
    $pagedItems = array_slice($items, $offset, $perPage);
    return ['items' => $pagedItems, 'totalPages' => $totalPages];
}

// Handle pagination for requests
$pageRequests = isset($_GET['pageRequests']) ? (int)$_GET['pageRequests'] : 1;
$perPageRequests = 10; // Number of requests per page

if (in_array($role, ['user', 'headofdepartment', 'admin', 'root'])) {
    $paginatedRequests = paginate($requests, $pageRequests, $perPageRequests);
    $requests = $paginatedRequests['items'];
    $totalPagesRequests = $paginatedRequests['totalPages'];
}

// Handle pagination for budgets
$pageBudgets = isset($_GET['pageBudgets']) ? (int)$_GET['pageBudgets'] : 1;
$perPageBudgets = 10; // Number of budgets per page

$paginatedBudgets = paginate($budgets, $pageBudgets, $perPageBudgets);
$budgets = $paginatedBudgets['items'];
$totalPagesBudgets = $paginatedBudgets['totalPages'];

// Handle pagination for cash requests
$pageCashRequests = isset($_GET['pageCashRequests']) ? (int)$_GET['pageCashRequests'] : 1;
$perPageCashRequests = 10; // Number of cash requests per page

$paginatedCashRequests = paginate($cash_requests, $pageCashRequests, $perPageCashRequests);
$cash_requests = $paginatedCashRequests['items'];
$totalPagesCashRequests = $paginatedCashRequests['totalPages'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .table th, .table td {
            vertical-align: middle;
        }
        .badge-success {
            background-color: #28a745;
        }
        .badge-danger {
            background-color: #dc3545;
        }
        /* Ensure the row is clickable but doesn't prevent button actions */
        .clickable-row {
            cursor: pointer;
        }
        .clickable-row td:not(.no-click) {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h4 class="mb-4">Welcome, <?php echo ucfirst($_SESSION['username']); ?></h4>
        <a href="logout.php" class="btn btn-danger mb-3">Logout</a>

        <?php if ($role == 'admin' || $role == 'root'): ?>
            <h2>Admin Panel</h2>
            <ul class="list-group mb-4">
                <li class="list-group-item"><a href="register_department.php">Register Department</a></li>
                <li class="list-group-item"><a href="manage_inventory.php">Manage Inventory</a></li>
                <li class="list-group-item"><a href="inventory_log.php">Inventory Change Log</a></li>
                <li class="list-group-item"><a href="register.php">Register User</a></li>
                <li class="list-group-item"><a href="manage_users.php">Manage Users</a></li>
            </ul>
        <?php endif; ?>

        <?php if ($role == 'user'): ?>
            <h2>Your Inventory Requests</h2>
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Quantity Change</th>
                        <th>Attachment</th>
                        <th>Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr class="clickable-row" data-href="request_details.php?id=<?php echo $request['id']; ?>">
                            <td><?php echo htmlspecialchars($request['item']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $request['status'] == 'approved' ? 'success' : ($request['status'] == 'rejected' ? 'danger' : 'secondary'); ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($request['quantity_change']); ?></td>
                            <td>
                                <?php if ($request['attachment']): ?>
                                    <a href="<?php echo htmlspecialchars($request['attachment']); ?>" target="_blank" class="no-click">
                                        <i class="fas fa-paperclip"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($request['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="request.php" class="btn btn-primary">Submit a new request</a>
            <!-- Pagination -->
            <?php if ($totalPagesRequests > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPagesRequests; $i++): ?>
                            <li class="page-item <?php if ($i == $pageRequests) echo 'active'; ?>"><a class="page-link" href="?pageRequests=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

            <h2>Your Cash Requests</h2>
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Reason for Funds</th>
                        <th>Requested Amount</th>
                        <th>Budgeted Amount</th>
                        <th>Status</th>
                        <th>Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cash_requests as $cash_request): ?>
                        <tr class="clickable-row" data-href="cash_request_journey.php?id=<?php echo $cash_request['id']; ?>">
                            <td><?php echo htmlspecialchars($cash_request['budget_item']); ?></td>
                            <td>UGX <?php echo number_format($cash_request['amount'], 2); ?></td>
                            <td>UGX <?php echo number_format($cash_request['budget_cost'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $cash_request['status'] == 'approved' ? 'success' : ($cash_request['status'] == 'rejected' ? 'danger' : 'secondary'); ?>">
                                    <?php echo ucfirst($cash_request['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($cash_request['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Pagination for Cash Requests -->
            <?php if ($totalPagesCashRequests > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPagesCashRequests; $i++): ?>
                            <li class="page-item <?php if ($i == $pageCashRequests) echo 'active'; ?>"><a class="page-link" href="?pageCashRequests=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php elseif ($role == 'headofdepartment'): ?>
            <h2>Department Inventory Requests</h2>
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Quantity Change</th>
                        <th>Attachment</th>
                        <th>Requested By</th>
                        <th>Date Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr class="clickable-row" data-href="request_details.php?id=<?php echo $request['id']; ?>">
                            <td><?php echo htmlspecialchars($request['item']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $request['status'] == 'approved' ? 'success' : ($request['status'] == 'rejected' ? 'danger' : 'secondary'); ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($request['quantity_change']); ?></td>
                            <td>
                                <?php if ($request['attachment']): ?>
                                    <a href="<?php echo htmlspecialchars($request['attachment']); ?>" target="_blank" class="no-click">
                                        <i class="fas fa-paperclip"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($request['username']); ?></td>
                            <td><?php echo htmlspecialchars($request['created_at']); ?></td>
                            <td class="no-click">
                                <a href="approve_cash_request.php?id=<?php echo $request['id']; ?>" class="btn btn-success <?php echo ($request['status'] != 'pending') ? 'disabled' : ''; ?>">Approve</a>
                                <a href="reject_cash_request.php?id=<?php echo $request['id']; ?>" class="btn btn-danger <?php echo ($request['status'] != 'pending') ? 'disabled' : ''; ?>">Reject</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Pagination for Requests -->
            <?php if ($totalPagesRequests > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPagesRequests; $i++): ?>
                            <li class="page-item <?php if ($i == $pageRequests) echo 'active'; ?>"><a class="page-link" href="?pageRequests=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

            <h2>Department Budgets</h2>
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Item</th>
                        <th>Cost</th>
                        <th>Start Month</th>
                        <th>End Month</th>
                        <th>Year</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($budgets as $budget): ?>
                        <tr class="clickable-row" data-href="budget_details.php?id=<?php echo $budget['id']; ?>">
                            <td><?php echo htmlspecialchars($budget['item']); ?></td>
                            <td><?php echo htmlspecialchars($budget['cost']); ?></td>
                            <td><?php echo htmlspecialchars($budget['start_month']); ?></td>
                            <td><?php echo htmlspecialchars($budget['end_month']); ?></td>
                            <td><?php echo htmlspecialchars($budget['year']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $budget['status'] == 'approved' ? 'success' : ($budget['status'] == 'rejected' ? 'danger' : 'secondary'); ?>">
                                    <?php echo ucfirst($budget['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Pagination for Budgets -->
            <?php if ($totalPagesBudgets > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPagesBudgets; $i++): ?>
                            <li class="page-item <?php if ($i == $pageBudgets) echo 'active'; ?>"><a class="page-link" href="?pageBudgets=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

            <h2>Department Cash Requests</h2>
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Reason for Funds</th>
                        <th>Requested Amount</th>
                        <th>Budgeted Amount</th>
                        <th>Submitted By</th>
                        <th>Date Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cash_requests as $cash_request): ?>
                        <tr class="clickable-row" data-href="cash_request_journey.php?id=<?php echo $cash_request['id']; ?>">
                            <td><?php echo htmlspecialchars($cash_request['budget_item']); ?></td>
                            <td>UGX <?php echo number_format($cash_request['amount'], 2); ?></td>
                            <td>UGX <?php echo number_format($cash_request['budget_cost'], 2); ?></td>
                            <td><?php echo htmlspecialchars($cash_request['username']); ?></td>
                            <td><?php echo htmlspecialchars($cash_request['created_at']); ?></td>
                            <td class="no-click">
                                <?php if ($cash_request['status'] == 'pending'): ?>
                                    <a href="approve_cash_request.php?id=<?php echo $cash_request['id']; ?>" class="btn btn-success">Approve</a>
                                    <a href="reject_cash_request.php?id=<?php echo $cash_request['id']; ?>" class="btn btn-danger">Reject</a>
                                <?php else: ?>
                                    <span class="badge badge-<?php echo $cash_request['status'] == 'approved_by_hod' ? 'success' :'danger'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $cash_request['status'])); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Pagination for Cash Requests -->
            <?php if ($totalPagesCashRequests > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPagesCashRequests; $i++): ?>
                            <li class="page-item <?php if ($i == $pageCashRequests) echo 'active'; ?>"><a class="page-link" href="?pageCashRequests=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
            <a href="submit_budget.php" class="btn btn-primary">Submit a new budget</a>
        <?php elseif ($role == 'finance'): ?>
            <h2>Budgets</h2>
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Item</th>
                        <th>Cost</th>
                        <th>Start Month</th>
                        <th>End Month</th>
                        <th>Year</th>
                        <th>Submitted By</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($budgets as $budget): ?>
                        <tr class="clickable-row" data-href="budget_details.php?id=<?php echo $budget['id']; ?>">
                            <td><?php echo htmlspecialchars($budget['item']); ?></td>
                            <td><?php echo htmlspecialchars($budget['cost']); ?></td>
                            <td><?php echo htmlspecialchars($budget['start_month']); ?></td>
                            <td><?php echo htmlspecialchars($budget['end_month']); ?></td>
                            <td><?php echo htmlspecialchars($budget['year']); ?></td>
                            <td><?php echo htmlspecialchars($budget['submitted_by_name']); ?></td>
                            <td><?php echo htmlspecialchars($budget['description']); ?></td>
                            <td>
                                <?php if ($budget['status'] == 'pending'): ?>
                                    <a href="approve_budget.php?id=<?php echo $budget['id']; ?>" class="btn btn-success">Approve</a>
                                    <a href="reject_budget.php?id=<?php echo $budget['id']; ?>" class="btn btn-danger">Reject</a>
                                <?php else: ?>
                                    <span class="badge badge-<?php echo $budget['status'] == 'approved' ? 'success' :'danger'; ?>">
                                        <?php echo ucfirst($budget['status']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Pagination for Budgets -->
            <?php if ($totalPagesBudgets > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPagesBudgets; $i++): ?>
                            <li class="page-item <?php if ($i == $pageBudgets) echo 'active'; ?>"><a class="page-link" href="?pageBudgets=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

            <h2>Cash Requests</h2>
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Reason for Funds</th>
                        <th>Requested Amount</th>
                        <th>Budgeted Amount</th>
                        <th>Department</th>
                        <th>Submitted By</th>
                        <th>Date Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cash_requests as $cash_request): ?>
                        <tr class="clickable-row" data-href="cash_request_journey.php?id=<?php echo $cash_request['id']; ?>">
                            <td><?php echo htmlspecialchars($cash_request['budget_item']); ?></td>
                            <td>UGX <?php echo number_format($cash_request['amount'], 2); ?></td>
                            <td>UGX <?php echo number_format($cash_request['budget_cost'], 2); ?></td>
                            <td><?php echo htmlspecialchars($cash_request['department_name']); ?></td>
                            <td><?php echo htmlspecialchars($cash_request['submitted_by_name']); ?></td>
                            <td><?php echo htmlspecialchars($cash_request['created_at']); ?></td>
                            <td class="no-click">
                                <?php if ($cash_request['status'] == 'approved_by_hod'): ?>
                                    <a href="approve_cash_request_finance.php?id=<?php echo $cash_request['id']; ?>" class="btn btn-success">Disburse</a>
                                    <a href="reject_cash_request_finance.php?id=<?php echo $cash_request['id']; ?>" class="btn btn-danger">Reject</a>
                                <?php else: ?>
                                    <span class="badge badge-<?php echo $cash_request['status'] == 'disbursed' ? 'success' :'danger'; ?>">
                                        <?php echo ucfirst($cash_request['status']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Pagination for Cash Requests -->
            <?php if ($totalPagesCashRequests > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPagesCashRequests; $i++): ?>
                            <li class="page-item <?php if ($i == $pageCashRequests) echo 'active'; ?>"><a class="page-link" href="?pageCashRequests=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php endif; ?>
    </div>
    <!-- Optional JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Make rows clickable
        document.querySelectorAll('.clickable-row').forEach(function(row) {
            row.addEventListener('click', function() {
                window.location = row.getAttribute('data-href');
            });
        });
    </script>
</body>
</html>
