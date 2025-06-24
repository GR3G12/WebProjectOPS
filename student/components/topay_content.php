<?php
// Include the database connection
include('../../database/db.php'); 

// Ensure the student number is available
if (!isset($student_number)) {
    die("Student number is not set. Please log in to view your fees.");
}

// Default values for pagination
$limit = 8; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number from URL
$offset = ($page - 1) * $limit; // Offset for the SQL query

// Fetch the total number of rows for the specific student
$totalQuery = "SELECT COUNT(*) as total 
               FROM student_payments
               WHERE student_number = :student_number  
               AND due_date >= CURDATE() 
            --    AND fee_for NOT IN ('Prelim', 'Midterm', 'Prefinal', 'Final')
               ";
$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->bindValue(':student_number', $student_number, PDO::PARAM_STR);
$totalStmt->execute();
$totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $limit); // Calculate total pages

// Fetch data with pagination for the specific student
$query = "SELECT id, fee_for, CONCAT(DATE_FORMAT(event_date_start, '%M %d'), ' - ', DATE_FORMAT(event_date_end, '%M %d')) AS event_date, 
            amount, due_date, DATE_FORMAT(due_date, '%M %d, %Y') AS formatted_due_date, status 
          FROM student_payments
          WHERE student_number = :student_number
          AND event_date_start >= CURDATE() 
        --   AND due_date >= CURDATE() 
        --   AND fee_for NOT IN ('Prelim', 'Midterm', 'Prefinal', 'Final')
          ORDER BY due_date ASC 
          LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':student_number', $student_number, PDO::PARAM_STR); // Bind student number
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT); // Bind limit
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT); // Bind offset
$stmt->execute();

if (!$stmt) {
    die("Error retrieving data: " . $pdo->errorInfo());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Now</title>
    <link rel="stylesheet" href="../css/content/topay.css">
</head>
<body>
    <div class="paynow">
        <div class="paynow-header">
            <h2>Upcoming Fees to Pay</h2>
            <button class="button">
                <img src="../imgs/phone-ring.png" alt="Phone ring" width="25px">
                Pay Now
            </button>
        </div>

        <div class="paynow-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Payment</th>
                        <th>Event Date</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Pay</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $currentDate = date('Y-m-d');
                    // Loop through the fetched data and display it in table rows
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $isOverdue = (strtotime($row['due_date']) < strtotime($currentDate)) ? true : false;

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['fee_for']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['event_date']) . "</td>";
                        echo "<td>₱" . number_format($row['amount'], 2) . "</td>";

                        // Highlight overdue due dates
                        if ($isOverdue) {
                            echo "<td style='color: red; font-weight: bold;'>" . htmlspecialchars($row['formatted_due_date']) . " (Overdue)</td>";
                        } else {
                            echo "<td>" . htmlspecialchars($row['formatted_due_date']) . "</td>";
                        }

                        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                        // echo "<td><input type='checkbox' name='select_amount' value='" . $row['amount'] . "' data-fee-for='" . htmlspecialchars($row['fee_for']) . "'></td>";
                        // Disable checkbox if status is 'Pending'
                        if ($row['status'] === 'Pending') {
                            echo "<td><input type='checkbox' name='select_amount' value='" . $row['amount'] . "' data-fee-for='" . htmlspecialchars($row['fee_for']) . "' data-fee-id='" . htmlspecialchars($row['id']) . "' disabled></td>";
                        } else {
                            echo "<td><input type='checkbox' name='select_amount' value='" . $row['amount'] . "' data-fee-for='" . htmlspecialchars($row['fee_for']) . "' data-fee-id='" . htmlspecialchars($row['id']) . "'></td>";
                        }

                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        <div class="pagination">
            <?php if ($totalPages > 1): ?>
                <ul>
                    <?php if ($page > 1): ?>
                        <li><a href="?page=<?= $page - 1 ?>">Previous</a></li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li>
                            <a href="?page=<?= $i ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li><a href="?page=<?= $page + 1 ?>">Next</a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
