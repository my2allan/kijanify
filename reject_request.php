<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'headofdepartment') {
    header("Location: login.php");
    exit();
}

$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($request_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reason = $_POST['reason'];
    $stmt = $pdo->prepare("UPDATE requests SET status = 'rejected', rejection_reason = ? WHERE id = ?");
    $stmt->execute([$reason, $request_id]);
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch();
if (!$request) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reject Request</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Reject Request</h1>
        <form method="post" action="reject_request.php?id=<?php echo $request_id; ?>">
            <div class="form-group">
                <label for="item">Item:</label>
                <input type="text" class="form-control" id="item" value="<?php echo $request['item']; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="reason">Reason for Rejection:</label>
                <textarea class="form-control" name="reason" id="reason" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-danger">Reject Request</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <!-- Optional JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
