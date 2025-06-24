<?php
// Include the database connection
include('../../database/db.php');

// Capture the search term
$search = isset($_GET['search']) ? $_GET['search'] : '';
$feeForFilter = isset($_GET['fee_for']) ? $_GET['fee_for'] : ''; // Capture fee_for filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'Paid'; // Capture status filter, default to 'Paid'
$dateFromFilter = isset($_GET['date_from']) && $_GET['date_from'] != '' ? $_GET['date_from'] : null;
$dateToFilter = isset($_GET['date_to']) && $_GET['date_to'] != '' ? $_GET['date_to'] : null;

// Capture the limit per page, default to 5 if not set
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number from URL
$offset = ($page - 1) * $limit; // Offset for the SQL query

// Initialize variables for fee_for name and counts
$feeForName = '';
$paidCount = 0;
$partialCount = 0;
$unpaidCount = 0;
$totalPaidAmount = 0;
$totalPartialAmount = 0;
$totalUnpaidAmount = 0;
$combinedTotalPaidAmount = 0;

// Fetch the total number of rows
// $totalQuery = "SELECT COUNT(*) as total FROM (
//     SELECT pf.student_number, pf.firstname, pf.lastname, pf.fee_for, pf.reference, ac.course, pf.payment_method, pf.status FROM semester_fees pf 
//     JOIN student_accounts ac ON pf.student_number = ac.student_number 
//     -- WHERE pf.status IN ('Paid', 'Partial Payment')
//     UNION ALL
//     SELECT sp.student_number, ac.firstname, ac.lastname, sp.fee_for, sp.reference, ac.course, sp.payment_method, sp.status FROM student_payments sp 
//     JOIN student_accounts ac ON sp.student_number = ac.student_number 
//     -- WHERE sp.status IN ('Paid', 'Partial Payment')
// ) AS combined WHERE 1=1";
$totalQuery = "SELECT COUNT(*) as total FROM (
    SELECT pf.student_number, pf.firstname, pf.lastname, pf.fee_for, pf.reference, ac.course, pf.payment_method, pf.status, pf.payment_date FROM semester_fees pf
    JOIN student_accounts ac ON pf.student_number = ac.student_number
    UNION ALL
    SELECT sp.student_number, ac.firstname, ac.lastname, sp.fee_for, sp.reference, ac.course, sp.payment_method, sp.status, sp.payment_date FROM student_payments sp
    JOIN student_accounts ac ON sp.student_number = ac.student_number
) AS combined WHERE 1=1";

if ($search != '') {
    $totalQuery .= " AND (student_number LIKE :search 
                        OR lastname LIKE :search 
                        OR firstname LIKE :search 
                        OR fee_for LIKE :search 
                        OR reference LIKE :search 
                        OR course LIKE :search
                        OR payment_method LIKE :search)";
}

if ($feeForFilter != '') {
    $totalQuery .= " AND fee_for = :fee_for_filter";
}
if ($statusFilter != '') {
    $totalQuery .= " AND status = :status_filter";
}
if ($dateFromFilter !== null) {
    $totalQuery .= " AND payment_date >= :date_from";
}
if ($dateToFilter !== null) {
    $totalQuery .= " AND payment_date <= :date_to";
}

$totalStmt = $pdo->prepare($totalQuery);
// If there is a search term, bind it with wildcards for partial matching
if ($search != '') {
    $totalStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
}
if ($feeForFilter != '') {
    $totalStmt->bindValue(':fee_for_filter', $feeForFilter, PDO::PARAM_STR);
}
if ($statusFilter != '') {
    $totalStmt->bindValue(':status_filter', $statusFilter, PDO::PARAM_STR);
}
if ($dateFromFilter !== null) {
    $totalStmt->bindValue(':date_from', $dateFromFilter . ' 00:00:00', PDO::PARAM_STR);
}
if ($dateToFilter !== null) {
    $totalStmt->bindValue(':date_to', $dateToFilter . ' 23:59:59', PDO::PARAM_STR);
}

$totalStmt->execute();
$totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $limit); // Calculate total pages

// Query to fetch the payment fees and student details with the filters
$query = "  SELECT 
                student_number, course, year_level, firstname, lastname, fee_for, amount, payment_date, 
                DATE_FORMAT(payment_date, '%b. %d, %Y %h:%i %p') AS formatted_payment_date, payment_method, status, reference
            FROM (
                SELECT 
                    pf.student_number, ac.course, ac.year_level, pf.firstname, pf.lastname, pf.fee_for, pf.amount, pf.payment_date, pf.payment_method, pf.status, pf.reference
                FROM semester_fees pf
                JOIN student_accounts ac ON pf.student_number = ac.student_number
                -- WHERE pf.status IN ('Paid', 'Partial Payment')
                UNION ALL
                SELECT 
                    sp.student_number, ac.course, ac.year_level, ac.firstname, ac.lastname, sp.fee_for, sp.amount, sp.payment_date AS payment_date, sp.payment_method, sp.status, sp.reference
                FROM student_payments sp
                JOIN student_accounts ac ON sp.student_number = ac.student_number
                -- WHERE sp.status IN ('Paid', 'Partial Payment')
            ) AS combined_data
            WHERE 1=1";

if ($search != '') {
    $query .= " AND (student_number LIKE :search 
                        OR lastname LIKE :search 
                        OR firstname LIKE :search 
                        OR fee_for LIKE :search 
                        OR reference LIKE :search 
                        OR course LIKE :search
                        OR payment_method LIKE :search)";
}

if ($feeForFilter != '') {
    $query .= " AND fee_for = :fee_for_filter";
}
if ($statusFilter != '') {
    $query .= " AND status = :status_filter";
}
if ($dateFromFilter !== null) {
    $query .= " AND payment_date >= :date_from";
}
if ($dateToFilter !== null) {
    $query .= " AND payment_date <= :date_to";
}

$query .= " ORDER BY payment_date DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
// If there is a search term, bind it with wildcards for partial matching
if ($search != '') {
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
}
if ($feeForFilter != '') {
    $stmt->bindValue(':fee_for_filter', $feeForFilter, PDO::PARAM_STR);
}
if ($statusFilter != '') {
    $stmt->bindValue(':status_filter', $statusFilter, PDO::PARAM_STR);
}
if ($dateFromFilter !== null) {
    $stmt->bindValue(':date_from', $dateFromFilter . ' 00:00:00', PDO::PARAM_STR);
}
if ($dateToFilter !== null) {
    $stmt->bindValue(':date_to', $dateToFilter . ' 23:59:59', PDO::PARAM_STR);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT); // Bind limit
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT); // Bind offset
$stmt->execute();

// Fetch the total accumulated amount for Paid transactions
// $amountQuery = "SELECT SUM(amount) AS total_amount FROM (
//     SELECT amount FROM semester_fees WHERE status IN ('Paid', 'Partial Payment')
//     UNION ALL
//     SELECT amount FROM student_payments WHERE status IN ('Paid', 'Partial Payment')
// ) AS combined_amounts";
// Fetch the total accumulated amount for Paid transactions
$amountQuery = "SELECT SUM(amount) AS total_amount FROM (
    SELECT amount, payment_date, status FROM semester_fees
    UNION ALL
    SELECT amount, payment_date, status FROM student_payments
) AS combined_amounts WHERE status IN ('Paid', 'Partial Payment')";

if ($dateFromFilter !== null) {
    $amountQuery .= " AND payment_date >= :date_from";
}
if ($dateToFilter !== null) {
    $amountQuery .= " AND payment_date <= :date_to";
}

// Prepare and execute the query
$amountStmt = $pdo->prepare($amountQuery);
if ($dateFromFilter !== null) {
    $amountStmt->bindValue(':date_from', $dateFromFilter . ' 00:00:00', PDO::PARAM_STR);
}
if ($dateToFilter !== null) {
    $amountStmt->bindValue(':date_to', $dateToFilter . ' 23:59:59', PDO::PARAM_STR);
}
$amountStmt->execute();
$amountResult = $amountStmt->fetch(PDO::FETCH_ASSOC);
$totalAmount = $amountResult['total_amount'] ?? 0; // Default to 0 if no records

// Fetch distinct fee_for values for the dropdown
$feeForDropdownQuery = "SELECT DISTINCT fee_for FROM (
                            SELECT fee_for FROM semester_fees
                            UNION ALL
                            SELECT fee_for FROM student_payments
                        ) AS all_fees 
                        ORDER BY 
                            CASE fee_for
                                WHEN 'Prelim' THEN 1
                                WHEN 'Midterm' THEN 2
                                WHEN 'Prefinal' THEN 3
                                WHEN 'Final' THEN 4
                                ELSE 5 -- All others
                            END, fee_for ASC"; // Ascending order for the rest

$feeForDropdownStmt = $pdo->prepare($feeForDropdownQuery);
$feeForDropdownStmt->execute();
$feeForOptions = $feeForDropdownStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch fee_for details, only if there is a search or a fee_for filter.
if ($search != '' || $feeForFilter != '') {
    $feeForQuery = "SELECT fee_for, 
                        SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as paid,
                        SUM(CASE WHEN status = 'Unpaid' THEN 1 ELSE 0 END) as unpaid,
                        SUM(CASE WHEN status = 'Partial Payment' THEN 1 ELSE 0 END) as partial,
                        SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as total_paid_amount,
                        SUM(CASE WHEN status = 'Unpaid' THEN amount ELSE 0 END) as total_unpaid_amount,
                        SUM(CASE WHEN status = 'Partial Payment' THEN amount ELSE 0 END) as total_partial_amount
                    FROM (
                        SELECT fee_for, status, amount, payment_date FROM semester_fees 
                        UNION ALL
                        SELECT fee_for, status, amount, payment_date FROM student_payments
                    ) AS combined_fees
                    WHERE 1=1";
    if ($search != ''){
        $feeForQuery .= " AND fee_for LIKE :search";
    }
    if ($feeForFilter != ''){
        $feeForQuery .= " AND fee_for = :fee_for_filter";
    }
    // Add status filter to feeForQuery
    if ($statusFilter != ''){
        $feeForQuery .= " AND status = :status_filter";
    }
    if ($dateFromFilter !== null) {
        $feeForQuery .= " AND payment_date >= :date_from";
    }
    if ($dateToFilter !== null) {
        $feeForQuery .= " AND payment_date <= :date_to";
    }

    $feeForStmt = $pdo->prepare($feeForQuery);
    if ($search != ''){
        $feeForStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    }
    if ($feeForFilter != ''){
        $feeForStmt->bindValue(':fee_for_filter', $feeForFilter, PDO::PARAM_STR);
    }
    // Bind the status filter
    if ($statusFilter != ''){
        $feeForStmt->bindValue(':status_filter', $statusFilter, PDO::PARAM_STR);
    }
    if ($dateFromFilter !== null) {
        $feeForStmt->bindValue(':date_from', $dateFromFilter . ' 00:00:00', PDO::PARAM_STR);
    }
    if ($dateToFilter !== null) {
        $feeForStmt->bindValue(':date_to', $dateToFilter . ' 23:59:59', PDO::PARAM_STR);
    }

    $feeForStmt->execute();
    $feeForResult = $feeForStmt->fetch(PDO::FETCH_ASSOC);

    if ($feeForResult) {
        $feeForName = $feeForResult['fee_for'];
        $paidCount = $feeForResult['paid'];
        $unpaidCount = $feeForResult['unpaid'];
        $partialCount = $feeForResult['partial'];
        $totalPaidAmount = $feeForResult['total_paid_amount'];
        $totalUnpaidAmount = $feeForResult['total_unpaid_amount'];
        $totalPartialAmount = $feeForResult['total_partial_amount'];
        $combinedTotalPaidAmount = $totalPaidAmount + $totalPartialAmount; // Calculate combined amount
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction</title>
    <link rel="stylesheet" href="../css/content/report.css">
    
</head>
<body>
    <div class="print-header" style="display: none;">
        ACTS Online Payment System
    </div>

    <div class="reports-container">
        <div class="reports-header">
            <h2>Reports</h2>
            <div class="button-header">
                <button class="print-Btn" onclick="printReport()">
                    <div class="sign"><img src="../imgs/print2.png" alt="Add" width="26" height="26" style="margin-right: 5px;"></div>
                    <div class="text">Print</div>
                </button>
            </div>
        </div>

        <div class="reports-top">
            <div class="fee_for-filter">
                <h4>Payment</h4>
                <div class="filter">
                    <form method="GET" action="">
                        <select name="fee_for" id="fee_for" onchange="this.form.submit()">
                            <option value="">All</option>
                            <?php foreach ($feeForOptions as $option): ?>
                                <option value="<?= htmlspecialchars($option) ?>" <?= ($feeForFilter == $option) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="limit" value="<?= htmlspecialchars($limit) ?>">
                        <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
                        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                        <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFromFilter) ?>">
                        <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateToFilter) ?>">
                    </form>
                </div>
            </div>

            <div class="status-filter"> 
                <h4>Status</h4>
                <div class="filter">
                    <form method="GET" action="">
                        <select name="status" id="status" onchange="this.form.submit()">
                            <!-- <option value="">All</option>
                            <option value="Paid" <?= ($statusFilter == 'Paid') ? 'selected' : '' ?>>Paid</option> -->
                            <option value="Paid" <?= ($statusFilter == 'Paid' || empty($statusFilter)) ? 'selected' : '' ?>>Paid</option>
                            <option value="Unpaid" <?= ($statusFilter == 'Unpaid') ? 'selected' : '' ?>>Unpaid</option>
                            <option value="Partial Payment" <?= ($statusFilter == 'Partial Payment') ? 'selected' : '' ?>>Partial Payment</option>
                        </select>
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="limit" value="<?= htmlspecialchars($limit) ?>">
                        <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
                        <input type="hidden" name="fee_for" value="<?= htmlspecialchars($feeForFilter) ?>"> 
                        <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFromFilter) ?>">
                        <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateToFilter) ?>">
                    </form>
                </div>
            </div>

            <div class="report-search">
                <h4>Search</h4>
                <div class="filter">
                    <form method="GET" action="">
                        <div class="search-container">
                            <input type="text" name="search" class="search" placeholder="Search here" value="<?= htmlspecialchars($search) ?>" />
                            <button type="reset" class="reset-btn" onclick="clearSearch()">×</button>
                        </div>
                        <input type="hidden" name="fee_for" value="<?= htmlspecialchars($feeForFilter) ?>">
                        <input type="hidden" name="limit" value="<?= htmlspecialchars($limit) ?>">
                        <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
                    </form>
                </div>
            </div>

            <div class="report-accumm">
                <h3>Accumulated Amount</h3>
                <h2>₱ <?= number_format($totalAmount, 2) ?></h2>
            </div> 
        </div>
        <div class="reports-bot">
            <div class="date-filter">
                <h4>Date from</h4>
                <div class="filter">
                    <form method="GET" action="">
                        <!-- <label for="date_from">From:</label> -->
                        <input type="date" name="date_from" id="date_from" value="<?= htmlspecialchars($dateFromFilter) ?>" onchange="this.form.submit()">
                        <label for="date_to"> - </label>
                        <input type="date" name="date_to" id="date_to" value="<?= htmlspecialchars($dateToFilter) ?>" onchange="this.form.submit()">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="limit" value="<?= htmlspecialchars($limit) ?>">
                        <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
                        <input type="hidden" name="fee_for" value="<?= htmlspecialchars($feeForFilter) ?>">
                        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                    </form>
                </div>
            </div>
            <div class="filteralization">
                <div class="limit-con">
                    <h4>Limit</h4>
                    <div class="pagination-controls">
                        <form method="GET" action="">
                            <!-- <label for="records-per-page">Limit:</label> -->
                            <input type="number" name="limit" id="records-per-page" value="<?= $limit ?>" min="1" max="100" step="1">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                            <input type="hidden" name="fee_for" value="<?= htmlspecialchars($feeForFilter) ?>">
                            <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
                            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                            <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFromFilter) ?>">
                            <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateToFilter) ?>">
                        </form>
                    </div>
                </div>
                <div class="fee_for-details">
                    <?php if ($feeForName): ?>
                        <p><?= htmlspecialchars($feeForName) ?></p>
                    <?php endif; ?>

                    <?php if ($statusFilter == 'Paid' && ($paidCount > 0 || $partialCount > 0)): ?>
                        <p>Paid= <?= $paidCount + $partialCount ?>     (Total: ₱<?= number_format($combinedTotalPaidAmount, 2) ?>)</p>
                    <?php elseif ($statusFilter == 'Unpaid' && $unpaidCount > 0): ?>
                        <p>Unpaid= <?= $unpaidCount ?>  (Total: ₱<?= number_format($totalUnpaidAmount, 2) ?>)</p>
                    <?php elseif ($statusFilter == 'Partial Payment' && $partialCount > 0): ?>
                        <p>Partial Payment= <?= $partialCount ?>  (Total: ₱<?= number_format($totalPartialAmount, 2) ?>)</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        

        <div class="reports-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Stud. No</th>
                        <th>Course</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Reference No.</th>
                        <th>Amount</th>
                        <th title="Mode of Payment">MOP</th>
                        <!-- <th>Status</th> -->
                        </tr>
                </thead>
                <tbody>
                    <?php if ($stmt->rowCount() > 0): ?>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['student_number']) ?></td>
                                <td><?= htmlspecialchars($row['course']) ?></td>
                                <td><?= htmlspecialchars($row['fee_for']) ?></td>
                                <td><?= htmlspecialchars($row['formatted_payment_date']) ?></td>
                                
                                <td><?= htmlspecialchars($row['reference']) ?></td>
                                <td>₱ <?= number_format($row['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($row['payment_method']) ?></td>
                                <!-- <td><?= htmlspecialchars($row['status']) ?></td> -->
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10">No records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            
            <div class="pagination">
                <div class="page-info">
                    Page <?= $page ?> of <?= $totalPages ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <ul>
                        <?php if ($page > 1): ?>
                            <li>
                                <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&fee_for=<?= urlencode($feeForFilter) ?>&status=<?= urlencode($statusFilter) ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li>
                                <a href="?page=<?= $i ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&fee_for=<?= urlencode($feeForFilter) ?>&status=<?= urlencode($statusFilter) ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li>
                                <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&fee_for=<?= urlencode($feeForFilter) ?>&status=<?= urlencode($statusFilter) ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>

        </div>

        <script>
            document.querySelector('.reset-btn').addEventListener('click', function() {
                window.location.href = window.location.pathname; // This reloads the page with no query parameters
            });

            document.getElementById('records-per-page').addEventListener('input', function() {
                const newLimit = this.value; // Get the new limit value from the input field
                const urlParams = new URLSearchParams(window.location.search);

                // Update the 'limit' query parameter with the new value
                urlParams.set('limit', newLimit);
                urlParams.set('page', 1); // Reset to page 1 when limit changes

                // Update the URL and reload the page with the new 'limit' value
                window.location.search = urlParams.toString();
            });

        function printReport() {
            window.print();
        }
        </script>
    </div>
    <div class="print-footer" style="display: none;">
        Generated by <?php echo htmlspecialchars($firstname); ?>
        <?php echo htmlspecialchars($lastname); ?> 
        | Date: <span id="print-date"></span>
    </div>
    <script>
        document.getElementById("print-date").innerText = new Date().toLocaleDateString();
    </script>

</body>
</html> 