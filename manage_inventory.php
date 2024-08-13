<?php
require 'config.php';

// Check if the user is an admin or root user
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'root') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = $_POST['item_name'];
    $quantity = $_POST['quantity'];
    $change_type = $_POST['change_type'];
    $department_id = $_POST['department_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE item_name = ? AND department_id = ?");
    $stmt->execute([$item_name, $department_id]);
    $inventory_item = $stmt->fetch();

    if ($inventory_item) {
        if ($change_type == 'addition') {
            $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity + ?, updated_by = ? WHERE id = ?");
            $stmt->execute([$quantity, $user_id, $inventory_item['id']]);

            $stmt = $pdo->prepare("INSERT INTO inventory_changes (inventory_id, change_type, quantity_change, change_reason, user_id) VALUES (?, 'addition', ?, 'Manual update', ?)");
            $stmt->execute([$inventory_item['id'], $quantity, $user_id]);
        } elseif ($change_type == 'deduction') {
            if ($inventory_item['quantity'] >= $quantity) {
                $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - ?, updated_by = ? WHERE id = ?");
                $stmt->execute([$quantity, $user_id, $inventory_item['id']]);

                $stmt = $pdo->prepare("INSERT INTO inventory_changes (inventory_id, change_type, quantity_change, change_reason, user_id) VALUES (?, 'deduction', ?, 'Manual update', ?)");
                $stmt->execute([$inventory_item['id'], $quantity, $user_id]);
            } else {
                echo "Insufficient inventory.";
                exit();
            }
        }
    } else {
        if ($change_type == 'addition') {
            $stmt = $pdo->prepare("INSERT INTO inventory (item_name, quantity, department_id, updated_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$item_name, $quantity, $department_id, $user_id]);

            $inventory_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO inventory_changes (inventory_id, change_type, quantity_change, change_reason, user_id) VALUES (?, 'addition', ?, 'Manual update', ?)");
            $stmt->execute([$inventory_id, $quantity, $user_id]);
        } else {
            echo "Item does not exist in inventory.";
            exit();
        }
    }

    header("Location: manage_inventory.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between mb-4">
            <h1>Manage Inventory</h1>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
        <form method="post" class="mb-4">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="department_id">Department:</label>
                    <select class="form-control" name="department_id" id="department_id" required>
                        <option value="">Select Department</option>
                        <?php
                        $stmt = $pdo->query("SELECT id, name FROM departments");
                        while ($row = $stmt->fetch()) {
                            echo "<option value=\"{$row['id']}\">{$row['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="item_name">Item Name:</label>
                    <input type="text" class="form-control" name="item_name" id="item_name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="quantity">Quantity:</label>
                    <input type="number" class="form-control" name="quantity" id="quantity" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="change_type">Change Type:</label>
                    <select class="form-control" name="change_type" id="change_type" required>
                        <option value="addition">Addition</option>
                        <option value="deduction">Deduction</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Inventory</button>
        </form>
        <h2>Current Inventory</h2>
        <table id="inventory_table" class="table table-bordered">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Department</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT i.item_name, i.quantity, d.name AS department_name FROM inventory i JOIN departments d ON i.department_id = d.id");
                while ($row = $stmt->fetch()) {
                    echo "<tr>
                            <td>{$row['item_name']}</td>
                            <td>{$row['quantity']}</td>
                            <td>{$row['department_name']}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#inventory_table').DataTable();
        });
    </script>
</body>
</html>
