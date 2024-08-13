<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $department_name = $_POST['department_name'];
    $head_of_department_id = $_POST['head_of_department_id'];

    // Check if the head of department is already assigned to another department
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE head_of_department_id = ?");
    $stmt->execute([$head_of_department_id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $error = "This user is already assigned as the head of another department.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO departments (name, head_of_department_id) VALUES (?, ?)");
        $stmt->execute([$department_name, $head_of_department_id]);
        header("Location: dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Department</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Register Department</h1>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <form method="post" class="needs-validation" novalidate>
            <div class="form-group">
                <label for="department_name">Department Name:</label>
                <input type="text" class="form-control" name="department_name" id="department_name" required>
                <div class="invalid-feedback">
                    Please provide a department name.
                </div>
            </div>
            <div class="form-group">
                <label for="head_of_department_id">Head of Department:</label>
                <select class="form-control" name="head_of_department_id" id="head_of_department_id" required>
                    <option value="">Select Head of Department</option>
                    <?php
                    $stmt = $pdo->query("SELECT id, username FROM users WHERE role = 'headofdepartment'");
                    while ($row = $stmt->fetch()) {
                        echo "<option value=\"{$row['id']}\">{$row['username']}</option>";
                    }
                    ?>
                </select>
                <div class="invalid-feedback">
                    Please select a head of department.
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
    </div>

    <!-- Optional JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Example starter JavaScript for disabling form submissions if there are invalid fields
        (function () {
            'use strict';
            window.addEventListener('load', function () {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function (form) {
                    form.addEventListener('submit', function (event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>
