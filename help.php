<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CSV Upload Help</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">CSV Upload Instructions</h1>
        <p>To upload your budget via a CSV file, please follow these instructions:</p>
        <ul>
            <li>The CSV file must have the following columns in this exact order:</li>
            <ul>
                <li><strong>Item</strong>: The name of the budget item.</li>
                <li><strong>Cost</strong>: The cost associated with the item.</li>
                <li><strong>Start Month</strong>: The month when the budget should start (e.g., January, February).</li>
                <li><strong>End Month</strong>: The month when the budget should end (e.g., January, February).</li>
                <li><strong>Year</strong>: The year the budget is for.</li>
                <li><strong>Description</strong>: Any additional comments or descriptions related to the budget item.</li>
            </ul>
            <li>Ensure that the file is saved with a .csv extension.</li>
            <li>Each row in the CSV file corresponds to a different budget item.</li>
            <li>An example of a correctly formatted CSV file:</li>
        </ul>
        <pre>
Item,Cost,Start Month,End Month,Year,Description
Office Supplies,200,January,March,2024,Pens and paper
Software Licenses,500,April,June,2024,Renewal of software licenses
Employee Training,1500,July,December,2024,Training for new hires
        </pre>
        <a href="submit_budget.php" class="btn btn-primary mt-3">Back to Submit Budget</a>
    </div>
</body>
</html>
