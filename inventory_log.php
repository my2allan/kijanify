<?php
require 'config.php';

// Check if the user is an admin or root user
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'root') {
    header("Location: dashboard.php");
    exit();
}

// Fetch inventory changes log
$stmt = $pdo->query("
    SELECT ic.*, i.item_name, d.name AS department_name, u.username
    FROM inventory_changes ic
    JOIN inventory i ON ic.inventory_id = i.id
    JOIN departments d ON i.department_id = d.id
    JOIN users u ON ic.user_id = u.id
");
$inventory_changes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Log</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between mb-4">
            <h1>Inventory Log</h1>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
        <table id="inventory_log_table" class="table table-bordered">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Change Type</th>
                    <th>Quantity Change</th>
                    <th>Change Reason</th>
                    <th>Department</th>
                    <th>Updated By</th>
                    <th>Change Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inventory_changes as $change): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($change['item_name']); ?></td>
                        <td><?php echo htmlspecialchars($change['change_type']); ?></td>
                        <td><?php echo htmlspecialchars($change['quantity_change']); ?></td>
                        <td><?php echo htmlspecialchars($change['change_reason']); ?></td>
                        <td><?php echo htmlspecialchars($change['department_name']); ?></td>
                        <td><?php echo htmlspecialchars($change['username']); ?></td>
                        <td><?php echo htmlspecialchars($change['change_date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#inventory_log_table').DataTable();
        });
    </script>
</body>
</html>
