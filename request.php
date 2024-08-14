<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $department_id = $_POST['department_id'];
    $item = $_POST['item'];
    $request_type = $_POST['request_type'];
    $quantity = $_POST['quantity']; // This will store the amount for cash requests
    $attachment = null;

    // Ensure uploads directory exists and is writable
    $uploads_dir = 'uploads';
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0777, true);
    }

    if (!empty($_FILES['attachment']['name'])) {
        $file_info = pathinfo($_FILES['attachment']['name']);
        $ext = $file_info['extension'];
        $new_filename = uniqid() . '.' . $ext;
        $date = date('Y/m/d');
        $file_path = "$uploads_dir/$date";
        if (!is_dir($file_path)) {
            mkdir($file_path, 0777, true);
        }
        $attachment = "$file_path/$new_filename";
        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $attachment)) {
            die("Failed to upload file.");
        }
    }

    if ($request_type == 'cash') {
        // Fetch available funds from the budgets table
        $stmt = $pdo->prepare("SELECT SUM(cost) AS available_funds FROM budgets WHERE department_id = ? AND status = 'approved'");
        $stmt->execute([$department_id]);
        $available_funds = $stmt->fetchColumn();

        if ($available_funds < $quantity) {
            die("Requested amount exceeds available funds.");
        }

        // Insert cash request into requests table
        $stmt = $pdo->prepare("INSERT INTO requests (user_id, department_id, item, quantity, attachment) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $department_id, $item, $quantity, $attachment]);
    } else {
        // Handle inventory or new purchase requests
        $stmt = $pdo->prepare("INSERT INTO requests (user_id, department_id, item, attachment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $department_id, $item, $attachment]);

        $request_id = $pdo->lastInsertId();

        if ($request_type == 'inventory') {
            $stmt = $pdo->prepare("SELECT * FROM inventory WHERE item_name = ? AND department_id = ?");
            $stmt->execute([$item, $department_id]);
            $inventory_item = $stmt->fetch();

            if ($inventory_item && $inventory_item['quantity'] >= $quantity) {
                $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - ?, updated_by = ? WHERE id = ?");
                $stmt->execute([$quantity, $user_id, $inventory_item['id']]);

                $stmt = $pdo->prepare("INSERT INTO inventory_changes (inventory_id, change_type, quantity_change, change_reason, user_id, request_id) VALUES (?, 'deduction', ?, 'Request by user', ?, ?)");
                $stmt->execute([$inventory_item['id'], $quantity, $user_id, $request_id]);
            } else {
                die("Insufficient inventory.");
            }
        }
    }

    // Send email notification
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = (SELECT head_of_department_id FROM departments WHERE id = ?)");
    $stmt->execute([$department_id]);
    $email = $stmt->fetchColumn();

    if ($email) {
        mail($email, "New Request", "A new request has been submitted.");
    }

    header("Location: dashboard.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Request</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Optional JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function fetchInventory(departmentId) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_inventory.php?department_id=' + departmentId, true);
            xhr.onload = function() {
                if (this.status == 200) {
                    const inventory = JSON.parse(this.responseText);
                    let output = '<h4>Inventory Items</h4><ul class="list-group">';
                    inventory.forEach(function(item) {
                        output += `<li class="list-group-item"><span class="badge badge-primary">Inventory</span> ${item.item_name}: ${item.quantity}</li>`;
                    });
                    output += '</ul>';
                    document.getElementById('inventory').innerHTML = output;

                    // Populate item dropdown
                    let itemDropdown = document.getElementById('item');
                    itemDropdown.innerHTML = '<option value="">Select Item</option>';
                    inventory.forEach(function(item) {
                        itemDropdown.innerHTML += `<option value="${item.item_name}">${item.item_name}</option>`;
                    });
                }
            }
            xhr.send();
        }

        function fetchApprovedBudgets(departmentId) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_approved_budgets.php?department_id=' + departmentId, true);
            xhr.onload = function() {
                if (this.status == 200) {
                    const budgets = JSON.parse(this.responseText);
                    let output = '<h4>Approved Budgets</h4><ul class="list-group">';
                    budgets.forEach(function(budget) {
                        output += `<li class="list-group-item"><span class="badge badge-success">Budget</span> ${budget.item}: UGX ${budget.cost}</li>`;
                    });
                    output += '</ul>';
                    document.getElementById('availableFunds').innerHTML = output;

                    // Populate item dropdown
                    let itemDropdown = document.getElementById('item');
                    itemDropdown.innerHTML = '<option value="">Select Item</option>';
                    budgets.forEach(function(budget) {
                        itemDropdown.innerHTML += `<option value="${budget.item}">${budget.item}</option>`;
                    });
                }
            }
            xhr.send();
        }

        function toggleLabels() {
            const requestType = document.getElementById('request_type').value;
            const itemLabel = document.getElementById('item_label');
            const quantityLabel = document.getElementById('quantity_label');
            const itemField = document.getElementById('item');
            if (requestType === 'cash') {
                itemLabel.textContent = 'Reason for Funds:';
                quantityLabel.textContent = 'Amount:';
                itemField.outerHTML = '<input type="text" class="form-control" name="item" id="item" required>';
                const departmentId = document.getElementById('department_id').value;
                if (departmentId) {
                    fetchApprovedBudgets(departmentId);
                }
            } else if (requestType === 'inventory') {
                itemLabel.textContent = 'Item:';
                quantityLabel.textContent = 'Quantity/Amount:';
                itemField.outerHTML = '<select class="form-control" name="item" id="item" required><option value="">Select Item</option></select>';
                const departmentId = document.getElementById('department_id').value;
                if (departmentId) {
                    fetchInventory(departmentId);
                }
            } else if (requestType === 'purchase') {
                itemLabel.textContent = 'New Purchase Details:';
                quantityLabel.textContent = 'Quantity/Amount:';
                itemField.outerHTML = '<input type="text" class="form-control" name="item" id="item" required>';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('department_id').addEventListener('change', function() {
                const departmentId = this.value;
                fetchInventory(departmentId);
                fetchApprovedBudgets(departmentId); // Fetch approved budgets when department changes
            });
            document.getElementById('request_type').addEventListener('change', toggleLabels);
            toggleLabels(); // Initialize labels on page load
        });
    </script>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between">
            <h1>Submit Request</h1>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
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
            <div class="form-group">
                <label for="request_type">Request Type:</label>
                <select class="form-control" name="request_type" id="request_type" required>
                    <option value="inventory">From Inventory</option>
                    <option value="purchase">New Purchase</option>
                    <option value="cash">Cash Request</option>
                </select>
            </div>
            <div id="inventory" class="mb-3"></div>
            <div id="availableFunds" class="mb-3"></div>
            <div class="form-group">
                <label for="item" id="item_label">Item:</label>
                <select class="form-control" name="item" id="item" required>
                    <option value="">Select Item</option>
                </select>
            </div>
            <div class="form-group">
                <label for="quantity" id="quantity_label">Quantity/Amount:</label>
                <input type="number" class="form-control" name="quantity" id="quantity" required>
            </div>
            <div class="form-group">
                <label for="attachment">Attachment:</label>
                <input type="file" class="form-control-file" name="attachment" id="attachment">
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
</body>
</html>
