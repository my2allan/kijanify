<?php
require 'config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['department_id'])) {
    if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'headofdepartment') {
        $stmt = $pdo->prepare("SELECT id FROM departments WHERE head_of_department_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $department = $stmt->fetch();
        if ($department) {
            $_SESSION['department_id'] = $department['id'];
        } else {
            die("Department ID not found for the head of department.");
        }
    } else {
        die("Department ID not set in the session.");
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $department_id = $_SESSION['department_id'];
    $submitted_by = $_SESSION['user_id'];

    // Handle CSV upload
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        while (($line = fgetcsv($file)) !== FALSE) {
            $item = $line[0];
            $cost = $line[1];
            $start_month = $line[2];
            $end_month = $line[3];
            $year = $line[4];
            $description = $line[5];

            // Insert budget into the budgets table
            $stmt = $pdo->prepare("INSERT INTO budgets (department_id, item, cost, start_month, end_month, year, submitted_by, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$department_id, $item, $cost, $start_month, $end_month, $year, $submitted_by, $description]);
        }
        fclose($file);
        header("Location: dashboard.php");
        exit();
    }

    // Handle multiple item submission manually
    if (!empty($_POST['item']) && !empty($_POST['cost'])) {
        $items = $_POST['item'];
        $costs = $_POST['cost'];
        $start_month = $_POST['start_month'];
        $end_month = $_POST['end_month'];
        $year = $_POST['year'];
        $descriptions = $_POST['description'];

        for ($i = 0; $i < count($items); $i++) {
            $stmt = $pdo->prepare("INSERT INTO budgets (department_id, item, cost, start_month, end_month, year, submitted_by, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$department_id, $items[$i], $costs[$i], $start_month[$i], $end_month[$i], $year[$i], $submitted_by, $descriptions[$i]]);
        }

        header("Location: dashboard.php");
        exit();
    }
}

// Define months and get the current month
$months = [
    "January", "February", "March", "April", "May", "June", 
    "July", "August", "September", "October", "November", "December"
];
$currentMonth = date('n') - 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Budget (Uganda Shillings)</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .disabled-button {
            cursor: not-allowed;
            opacity: 0.65;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Submit Budget (UGX)</h1>
        <form method="post" enctype="multipart/form-data" id="budgetForm">
            <div id="budgetItems">
                <div class="budget-item mb-4">
                    <h4>Item 1</h4>
                    <div class="form-group">
                        <label for="item[]">Item (Description of Purchase):</label>
                        <input type="text" class="form-control" name="item[]" placeholder="e.g., Office Supplies" required>
                    </div>
                    <div class="form-group">
                        <label for="cost[]">Cost (UGX):</label>
                        <input type="number" class="form-control" name="cost[]" placeholder="e.g., 1000000" required>
                    </div>
                    <div class="form-group">
                        <label for="start_month[]">Start Month:</label>
                        <select class="form-control start-month" name="start_month[]" required>
                            <option value="">Select Start Month</option>
                            <?php
                            for ($i = $currentMonth; $i < 12; $i++) {
                                echo '<option value="' . $months[$i] . '">' . $months[$i] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="end_month[]">End Month:</label>
                        <select class="form-control end-month" name="end_month[]" required>
                            <option value="">Select End Month</option>
                            <?php
                            foreach ($months as $month) {
                                echo '<option value="' . $month . '">' . $month . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="year[]">Year:</label>
                        <input type="number" class="form-control" name="year[]" value="<?php echo date('Y'); ?>" placeholder="<?php echo date('Y'); ?>" min="2000" required>
                    </div>
                    <div class="form-group">
                        <label for="description[]">Description/Comments:</label>
                        <textarea class="form-control" name="description[]" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-secondary" id="addItem">Add Another Item</button>
            <hr>
            <h4>Or Upload Budget via CSV</h4>
            <div class="form-group">
                <label for="csv_file">CSV File:</label>
                <input type="file" class="form-control" name="csv_file" accept=".csv" id="csv_file">
            </div>
            <p><a href="help.php">Need help with the CSV format?</a></p>
            <button type="submit" class="btn btn-primary disabled-button" id="submitBudget" disabled>Submit Budget</button>
        </form>
    </div>
    <!-- Optional JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function () {
            const months = [
                "January", "February", "March", "April", "May", "June", 
                "July", "August", "September", "October", "November", "December"
            ];

            $('#addItem').click(function () {
                var itemIndex = $('.budget-item').length + 1;
                $('#budgetItems').append(`
                    <div class="budget-item mb-4">
                        <h4>Item ` + itemIndex + `</h4>
                        <div class="form-group">
                            <label for="item[]">Item (Description of Purchase):</label>
                            <input type="text" class="form-control" name="item[]" placeholder="e.g., Office Supplies" required>
                        </div>
                        <div class="form-group">
                            <label for="cost[]">Cost (UGX):</label>
                            <input type="number" class="form-control" name="cost[]" placeholder="e.g., 1000000" required>
                        </div>
                        <div class="form-group">
                            <label for="start_month[]">Start Month:</label>
                            <select class="form-control start-month" name="start_month[]" required>
                                <option value="">Select Start Month</option>
                                <?php
                                for ($i = $currentMonth; $i < 12; $i++) {
                                    echo '<option value="' . $months[$i] . '">' . $months[$i] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="end_month[]">End Month:</label>
                            <select class="form-control end-month" name="end_month[]" required>
                                <option value="">Select End Month</option>
                                <?php
                                foreach ($months as $month) {
                                    echo '<option value="' . $month . '">' . $month . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="year[]">Year:</label>
                            <input type="number" class="form-control" name="year[]" value="<?php echo date('Y'); ?>" placeholder="<?php echo date('Y'); ?>" min="2000" required>
                        </div>
                        <div class="form-group">
                            <label for="description[]">Description/Comments:</label>
                            <textarea class="form-control" name="description[]" rows="3"></textarea>
                        </div>
                    </div>
                `);
            });

            // Enable submit button if there is at least one filled item or a CSV file
            function toggleSubmitButton() {
                const hasItemFilled = $('.budget-item').find('input').filter(function () {
                    return this.value.trim() !== "";
                }).length > 0;

                const hasCsvFile = $('#csv_file').val().trim() !== "";

                if (hasItemFilled || hasCsvFile) {
                    $('#submitBudget').removeClass('disabled-button').prop('disabled', false);
                } else {
                    $('#submitBudget').addClass('disabled-button').prop('disabled', true);
                }
            }

            // Check if the form can be submitted
            $('#budgetForm').on('input change', toggleSubmitButton);

            // Ensure End Month is not before Start Month unless it's December
            $(document).on('change', '.start-month, .end-month', function () {
                const startMonth = $(this).closest('.budget-item').find('.start-month').val();
                const endMonth = $(this).closest('.budget-item').find('.end-month').val();

                if (startMonth && endMonth) {
                    const startMonthIndex = months.indexOf(startMonth);
                    const endMonthIndex = months.indexOf(endMonth);

                    // Validate month selection
                    if (startMonthIndex > endMonthIndex && startMonth !== 'December') {
                        alert("End Month cannot be before Start Month unless the Start Month is December.");
                        $(this).closest('.budget-item').find('.end-month').val('');
                    }
                }
            });
        });
    </script>
</body>
</html>
