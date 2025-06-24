<?php
// Include the database connection
include('../../database/db.php');

// Capture the filter values from the dropdowns
$specificfee = isset($_GET['specificfee']) ? $_GET['specificfee'] : '';
$yearlevel = isset($_GET['yearlevel']) ? $_GET['yearlevel'] : '';
$course = isset($_GET['course']) ? $_GET['course'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Capture the search term
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Default values for pagination
$limit = 20; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number from URL
$offset = ($page - 1) * $limit; // Offset for the SQL query

// Fetch the total number of rows from semester_fees and student_payments combined
$totalQuery = "SELECT COUNT(*) as total FROM (
                    SELECT student_number FROM semester_fees WHERE status = 'Paid' OR status = 'Partial Payment'
                    UNION ALL
                    SELECT student_number FROM student_payments WHERE status = 'Paid' OR status = 'Partial Payment'
                ) AS combined";

// Query to fetch the payment fees and student details from semester_fees and student_payments combined
$query = "SELECT 
                student_number, course, year_level, firstname, lastname, fee_for, payment_date, formatted_payment_date, amount, status, reference
            FROM (
                SELECT 
                    sf.student_number,
                    sa.course,
                    sa.year_level,
                    sf.firstname,
                    sf.lastname,
                    sf.fee_for,
                    sf.payment_date,
                    DATE_FORMAT(sf.payment_date, '%b. %d %h:%i') AS formatted_payment_date,
                    sf.amount,
                    sf.status,
                    sf.reference
                FROM semester_fees sf
                JOIN student_accounts sa ON sf.student_number = sa.student_number
                WHERE sf.status = 'Paid' OR sf.status = 'Partial Payment'
                UNION ALL
                SELECT 
                    sp.student_number,
                    sa.course,
                    sa.year_level,
                    sa.firstname,
                    sa.lastname,
                    sp.fee_for,
                    sp.payment_date,
                    DATE_FORMAT(sp.payment_date, '%b. %d %h:%i') AS formatted_payment_date,
                    sp.amount,
                    sp.status,
                    sp.reference
                FROM student_payments sp
                JOIN student_accounts sa ON sp.student_number = sa.student_number
                WHERE sp.status = 'Paid' OR sp.status = 'Partial Payment'
            ) AS combined_data
            WHERE 1=1"; // Add a dummy where clause to easily append conditions

// Add conditions for filters
if ($course != '') {
    $query .= " AND course = :course";
}
if ($status != '') {
    $query .= " AND status = :status";
}
if ($yearlevel != '') {
    $query .= " AND year_level = :year_level";
}
if ($specificfee != '') {
    $query .= " AND fee_for = :fee_for";
}

// Add the search filter condition
if ($search != '') {
    $query .= " AND (student_number LIKE :search 
                         OR lastname LIKE :search 
                         OR firstname LIKE :search 
                         OR reference LIKE :search
                         OR fee_for LIKE :search
                         OR status LIKE :search
                         OR formatted_payment_date LIKE :search)";
}

$query .= " ORDER BY payment_date DESC LIMIT :limit OFFSET :offset";

$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->execute();
$totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $limit); // Calculate total pages

$stmt = $pdo->prepare($query);
if ($course != '') {
    $stmt->bindValue(':course', $course, PDO::PARAM_STR);
}
if ($status != '') {
    $stmt->bindValue(':status', $status, PDO::PARAM_STR);
}
if ($yearlevel != '') {
    $stmt->bindValue(':year_level', $yearlevel, PDO::PARAM_STR);
}
if ($specificfee != '') {
    $stmt->bindValue(':fee_for', $specificfee, PDO::PARAM_STR);
}
// If there is a search term, bind it with wildcards for partial matching
if ($search != '') {
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT); // Bind limit
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT); // Bind offset
$stmt->execute();

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
    <div class="transaction-header">
        <h2>Transaction of Payments</h2>

        <div class="transaction">
            <h3>Transaction for</h3>
        </div>
        <div class="date">
            <form method="GET" action="">
                <div class="transaction-filter">
                    <!-- UPDATED: Added selected attributes to maintain filter state -->
                    <select id="fee-dropdown" name="specificfee" class="specific-fee" onchange="this.form.submit()">
                        <option value="" <?= $specificfee === '' ? 'selected' : '' ?>>All Fee</option>
                        <option value="Prelim" <?= $specificfee === 'Prelim' ? 'selected' : '' ?>>Prelim</option>
                        <option value="Midterm" <?= $specificfee === 'Midterm' ? 'selected' : '' ?>>Midterm</option>
                        <option value="Prefinal" <?= $specificfee === 'Prefinal' ? 'selected' : '' ?>">Prefinal</option>
                        <option value="Final" <?= $specificfee === 'Final' ? 'selected' : '' ?>">Final</option>
                    </select>
                    <!-- Filter for year level -->
                    <select id="year-level-dropdown" name="yearlevel" class="year-level" onchange="this.form.submit()">
                        <option value="" <?= $yearlevel === '' ? 'selected' : '' ?>>All Level</option>
                        <option value="1" <?= $yearlevel === '1' ? 'selected' : '' ?>>First Year</option>
                        <option value="2" <?= $yearlevel === '2' ? 'selected' : '' ?>>Second Year</option>
                        <option value="3" <?= $yearlevel === '3' ? 'selected' : '' ?>>Third Year</option>
                        <option value="4" <?= $yearlevel === '4' ? 'selected' : '' ?>>Fourth Year</option>
                    </select>

                    <!-- Filter for course -->
                    <select id="course-dropdown" name="course" class="course" onchange="this.form.submit()">
                        <option value="" <?= $course === '' ? 'selected' : '' ?>>All Course</option>
                        <option value="BSIT" <?= $course === 'BSIT' ? 'selected' : '' ?>>BSIT</option>
                        <option value="BSCS" <?= $course === 'BSCS' ? 'selected' : '' ?>>BSCS</option>
                        <option value="BSCE" <?= $course === 'BSCE' ? 'selected' : '' ?>>BSCE</option>
                    </select>

                    <!-- Filter for status -->
                    <select id="status-dropdown" name="status" class="section" onchange="this.form.submit()">
                        <option value="" <?= $status === '' ? 'selected' : '' ?>>All Status</option>
                        <option value="Paid" <?= $status === 'Paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="Partial Payment" <?= $status === 'Partial Payment' ? 'selected' : '' ?>>Partial Payment</option>
                    </select>

                    <!-- Search filter -->
                    <input type="search" name="search" class="search" placeholder="Search here" value="<?= htmlspecialchars($search) ?>" />

                    <!-- Reset Button -->
                    <button type="reset" class="reset-btn">   
                        <img src="../../img/cancel.png" alt="Cancel-search" width=25px>
                    </button>
                </div>
            </form>
        </div>
        <div class="transaction-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Stud No</th>
                        <th>Course</th>
                        <!-- <th>Firstname</th> -->
                        <!-- <th>Lastname</th> -->
                        <!-- <th>Date Sent</th> -->
                        <th>Payments</th>
                        <th>Date</th>
                        <th>Reference / Receipt No.</th>
                        <th>Amount</th>
                        <!--<th>Status</th>-->
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stmt->rowCount() > 0): ?>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['student_number']) ?></td>
                                <td><?= htmlspecialchars($row['course']) ?></td>
                                <!-- <td><?= htmlspecialchars($row['firstname']) ?></td> -->
                                <!-- <td><?= htmlspecialchars($row['lastname']) ?></td> -->
                                <!-- <td><?= htmlspecialchars($row['formatted_updated_at']) ?></td> -->
                                <td><?= htmlspecialchars($row['fee_for']) ?></td>
                                <td><?= htmlspecialchars($row['formatted_payment_date']) ?></td>
                                <td><?= htmlspecialchars($row['reference']) ?></td>
                                <td>₱<?= number_format($row['amount'], 2) ?></td>
                                <!--<td><?= htmlspecialchars($row['status']) ?></td>-->
                                <td style="text-align: center;">
                                    <i class="far fa-eye" style="cursor: pointer;"
                                        data-student-number="<?= htmlspecialchars($row['student_number']) ?>"
                                        data-student-course="<?= htmlspecialchars($row['course']) ?>"
                                        data-student-lastname="<?= htmlspecialchars($row['lastname']) ?>"
                                        data-student-firstname="<?= htmlspecialchars($row['firstname']) ?>"
                                        data-fee-for="<?= htmlspecialchars($row['fee_for']) ?>"
                                        data-payment-date="<?= htmlspecialchars($row['formatted_payment_date']) ?>"
                                        data-reference="<?= htmlspecialchars($row['reference']) ?>"
                                        data-student-amount="₱<?= number_format($row['amount'], 2) ?>"
                                        data-student-status="<?= htmlspecialchars($row['status']) ?>">
                                        <!-- data-updated-at="<?= htmlspecialchars($row['formatted_updated_at']) ?>" -->
                                    </i>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10">No records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination Links -->
            <div class="pagination">
                <!-- <div class="total-records">
                    <p>Total Records: <?= $totalRecords ?></p>
                </div> -->
                <!-- Display current page of total pages -->
                <div class="page-info">
                    Page <?= $page ?> of <?= $totalPages ?>
                </div>

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

        <script>
            document.querySelector('.reset-btn').addEventListener('click', function() {
            window.location.href = window.location.pathname; // This reloads the page with no query parameters
            });
        </script>
    </div>
</body>
</html>
