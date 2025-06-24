<?php
// Include the database connection
include('../../database/db.php');

// Ensure the student number is available
if (!isset($student_number)) {
    die("Student number is not set. Please log in to view your fees.");
}

// Initialize date filter variables
$dateFrom = isset($_GET['DateFrom']) && !empty($_GET['DateFrom']) ? $_GET['DateFrom'] : null;
$dateTo = isset($_GET['DateTo']) && !empty($_GET['DateTo']) ? $_GET['DateTo'] : null;

// Build the WHERE clause for date filtering
$dateFilter = '';
if ($dateFrom && $dateTo) {
    $dateFilter = " AND updated_at BETWEEN :dateFrom AND :dateTo";
} elseif ($dateFrom) {
    $dateFilter = " AND updated_at >= :dateFrom";
} elseif ($dateTo) {
    $dateFilter = " AND updated_at <= :dateTo";
}

// Fetch the total number of rows with date filter
$totalQuery = "SELECT COUNT(*) as total FROM (
                    SELECT id, due_date FROM semester_fees WHERE student_number = :student_number AND (status = 'Paid' OR status = 'Partial Payment') $dateFilter
                    UNION ALL
                    SELECT id, due_date FROM student_payments WHERE student_number = :student_number AND (status = 'Paid' OR status = 'Partial Payment') $dateFilter
                ) AS combined_results";

$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->bindValue(':student_number', $student_number, PDO::PARAM_STR);
if ($dateFrom) $totalStmt->bindValue(':dateFrom', $dateFrom, PDO::PARAM_STR);
if ($dateTo) $totalStmt->bindValue(':dateTo', $dateTo . ' 23:59:59', PDO::PARAM_STR); // include the whole day.
$totalStmt->execute();
$totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
$totalRecords = $totalResult['total'];

// Pagination
$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$totalPages = ceil($totalRecords / $limit);

// Main query with date filter and due_date
$query = "SELECT id, fee_for, amount, updated_at, DATE_FORMAT(updated_at, '%M %d, %Y') AS formatted_updated_at, status, reference, due_date
            FROM semester_fees
            WHERE student_number = :student_number AND (status = 'Paid' OR status = 'Partial Payment') $dateFilter
            UNION ALL
            SELECT id, fee_for, amount, updated_at, DATE_FORMAT(updated_at, '%M %d, %Y') AS formatted_updated_at, status, reference, due_date
            FROM student_payments
            WHERE student_number = :student_number AND (status = 'Paid' OR status = 'Partial Payment') $dateFilter
            ORDER BY updated_at DESC
            LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':student_number', $student_number, PDO::PARAM_STR);
if ($dateFrom) $stmt->bindValue(':dateFrom', $dateFrom, PDO::PARAM_STR);
if ($dateTo) $stmt->bindValue(':dateTo', $dateTo . ' 23:59:59', PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();


// Format dates for display
$formattedDateFrom = '';
if ($dateFrom) {
    $formattedDateFrom = date("F j, Y", strtotime($dateFrom));
}

$formattedDateTo = '';
if ($dateTo) {
    $formattedDateTo = date("F j, Y", strtotime($dateTo));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction</title>
    <link rel="stylesheet" href="../css/content/transaction.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="transaction">
        <h2>Transaction of Payments</h2>

        <div class="date">
            <form action="" method="GET">
                <div class="con-date">
                    <div class="Date-From">
                        <label for="DateFrom">Date From: <strong><span id="DateFromDisplay"></span></strong></label><br>
                        <input type="date" name="DateFrom" id="DateFrom" value="<?php echo isset($_GET['DateFrom']) ? $_GET['DateFrom'] : ''; ?>" onchange="updateDateDisplay('DateFrom', 'DateFromDisplay')">
                    </div>
                    <div class="Date-To">
                        <label for="DateTo">Date To: <strong><span id="DateToDisplay"></span></strong></label><br>
                        <input type="date" name="DateTo" id="DateTo" value="<?php echo isset($_GET['DateTo']) ? $_GET['DateTo'] : ''; ?>" onchange="updateDateDisplay('DateTo', 'DateToDisplay')">
                    </div>
                </div>
                <div class="con-button">
                    <div class="sub-button-con">
                        <button class="submit-btn" type="submit">
                            <img src="../../img/filter-W.png" alt="filter" width=20px style="margin-right:5px;">
                            <label for="filter">Filter</label>
                        </button>
                    </div>

                    <div class="can-button-con">
                        <?php if (isset($_GET['DateFrom']) || isset($_GET['DateTo'])): ?>
                            <button class="cancel-submit-btn" type="button" onclick="clearFilters()">
                                <img src="../../img/filter-x-W.png" alt="filter" width=20px style="margin-right:5px;">
                                <label for="cancel-filter">Cancel Filter</label>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="transaction-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Payment</th>
                        <th>Payment Date</th>
                        <th>Reference No.</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stmt->rowCount() > 0): ?>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['fee_for']) ?></td>
                                <td><?= htmlspecialchars($row['formatted_updated_at']) ?>
                                    <?php if (isset($row['due_date']) && strtotime($row['updated_at']) > strtotime($row['due_date'])): ?>
                                        <span class="overdue" style="color: red; font-weight: bold;">(Overdue)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['reference']) ?></td>
                                <td>₱<?= number_format($row['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td>
                                    <i
                                        class="far fa-eye"
                                        style="cursor: pointer;"
                                        data-reference="<?= htmlspecialchars($row['reference']) ?>"
                                        data-fee-for="<?= htmlspecialchars($row['fee_for']) ?>"
                                        data-amount="₱<?= number_format($row['amount'], 2) ?>"
                                        data-status="<?= htmlspecialchars($row['status']) ?>"
                                        data-updated-at="<?= htmlspecialchars($row['formatted_updated_at']) ?>"
                                    ></i>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <?php if ($totalPages > 1): ?>
                <div class="page-info">
                    Page <?= $page ?> of <?= $totalPages ?>
                </div>

                <ul>
                    <?php if ($page > 1): ?>
                        <li><a href="?page=<?= $page - 1 ?><?php if(isset($_GET['DateFrom'])) echo '&DateFrom=' . $_GET['DateFrom']; ?><?php if(isset($_GET['DateTo'])) echo '&DateTo=' . $_GET['DateTo']; ?>">Previous</a></li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li>
                            <a href="?page=<?= $i ?><?php if(isset($_GET['DateFrom'])) echo '&DateFrom=' . $_GET['DateFrom']; ?><?php if(isset($_GET['DateTo'])) echo '&DateTo=' . $_GET['DateTo']; ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li><a href="?page=<?= $page + 1 ?><?php if(isset($_GET['DateFrom'])) echo '&DateFrom=' . $_GET['DateFrom']; ?><?php if(isset($_GET['DateTo'])) echo '&DateTo=' . $_GET['DateTo']; ?>">Next</a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>


    <script>
        function updateDateDisplay(inputId, displayId) {
            let dateInput = document.getElementById(inputId);
            let displayElement = document.getElementById(displayId);

            if (dateInput.value) {
                let date = new Date(dateInput.value);
                let options = { year: 'numeric', month: 'long', day:'numeric' };
                displayElement.textContent = date.toLocaleDateString(undefined, options);
            } else {
                displayElement.textContent = ''; // Clear display if no date is selected
            }
        }

        // Initialize display on page load
        window.onload = function() {
            updateDateDisplay('DateFrom', 'DateFromDisplay');
            updateDateDisplay('DateTo', 'DateToDisplay');
        };

        function clearFilters() {
            let url = new URL(window.location.href);
            url.searchParams.delete('DateFrom');
            url.searchParams.delete('DateTo');
            window.location.href = url.toString();
        }
    </script>
</body>
</html>