<?php
// Include the database connection
include('../../database/db.php');

// --- Pagination for Transaction History ---
$transaction_limit = 12;
$transaction_page = isset($_GET['transaction_page']) ? (int)$_GET['transaction_page'] : 1;
$transaction_offset = ($transaction_page - 1) * $transaction_limit;

// Capture filter and search parameters for transactions
$specificfee = isset($_GET['specificfee']) ? $_GET['specificfee'] : '';
$yearlevel = isset($_GET['yearlevel']) ? $_GET['yearlevel'] : '';
$course = isset($_GET['course']) ? $_GET['course'] : '';
$section = isset($_GET['section']) ? $_GET['section'] : '';
$transaction_search = isset($_GET['search']) ? $_GET['search'] : '';

// Base query for counting total filtered transactions
$baseTotalTransactionQuery = "SELECT COUNT(*) as total FROM (
    SELECT
        sf.student_number,
        sa.course,
        sa.section,
        sa.year_level,
        sf.fee_for,
        sf.firstname,
        sf.lastname,
        sf.reference,
        sf.status,
        DATE_FORMAT(sf.payment_date, '%M %d') AS formatted_payment_date
    FROM semester_fees_archive sf
    JOIN student_accounts_archive sa ON sf.student_number = sa.student_number
    WHERE sf.status = 'Paid' OR sf.status = 'Partial Payment'
    UNION ALL
    SELECT
        sp.student_number,
        sa.course,
        sa.section,
        sa.year_level,
        sp.fee_for,
        sa.firstname,
        sa.lastname,
        sp.reference,
        sp.status,
        DATE_FORMAT(sp.payment_date, '%M %d') AS formatted_payment_date
    FROM student_payments_archive sp
    JOIN student_accounts_archive sa ON sp.student_number = sa.student_number
    WHERE sp.status = 'Paid' OR sp.status = 'Partial Payment'
) AS combined_data WHERE 1=1";

// Add filter conditions to the count query
$totalTransactionQuery = $baseTotalTransactionQuery;
if ($course != '') {
    $totalTransactionQuery .= " AND course = :course";
}
if ($section != '') {
    $totalTransactionQuery .= " AND section = :section";
}
if ($yearlevel != '') {
    $totalTransactionQuery .= " AND year_level = :year_level";
}
if ($specificfee != '') {
    $totalTransactionQuery .= " AND fee_for = :fee_for";
}
if ($transaction_search != '') {
    $totalTransactionQuery .= " AND (combined_data.student_number LIKE :search
                                   OR lastname LIKE :search
                                   OR firstname LIKE :search
                                   OR reference LIKE :search
                                   OR fee_for LIKE :search
                                   OR status LIKE :search
                                   OR formatted_payment_date LIKE :search)";
}

// Prepare and execute the count query
$totalTransactionStmt = $pdo->prepare($totalTransactionQuery);
if ($course != '') {
    $totalTransactionStmt->bindValue(':course', $course, PDO::PARAM_STR);
}
if ($section != '') {
    $totalTransactionStmt->bindValue(':section', $section, PDO::PARAM_STR);
}
if ($yearlevel != '') {
    $totalTransactionStmt->bindValue(':year_level', $yearlevel, PDO::PARAM_STR);
}
if ($specificfee != '') {
    $totalTransactionStmt->bindValue(':fee_for', $specificfee, PDO::PARAM_STR);
}
if ($transaction_search != '') {
    $totalTransactionStmt->bindValue(':search', '%' . $transaction_search . '%', PDO::PARAM_STR);
}
$totalTransactionStmt->execute();
$totalTransactionResult = $totalTransactionStmt->fetch(PDO::FETCH_ASSOC);
$totalTransactionRecords = $totalTransactionResult['total'];
$totalTransactionPages = ceil($totalTransactionRecords / $transaction_limit);

// Query to fetch transaction history with filters and pagination
$transactionQuery = "SELECT
    student_number, course, section, year_level, firstname, lastname, fee_for, payment_date, formatted_payment_date, amount, status, reference
FROM (
    SELECT
        sf.student_number,
        sa.course,
        sa.section,
        sa.year_level,
        sf.firstname,
        sf.lastname,
        sf.fee_for,
        sf.payment_date,
        DATE_FORMAT(sf.payment_date, '%M %d') AS formatted_payment_date,
        sf.amount,
        sf.status,
        sf.reference
    FROM semester_fees_archive sf
    JOIN student_accounts_archive sa ON sf.student_number = sa.student_number
    WHERE sf.status = 'Paid' OR sf.status = 'Partial Payment'
    UNION ALL
    SELECT
        sp.student_number,
        sa.course,
        sa.section,
        sa.year_level,
        sa.firstname,
        sa.lastname,
        sp.fee_for,
        sp.payment_date,
        DATE_FORMAT(sp.payment_date, '%M %d') AS formatted_payment_date,
        sp.amount,
        sp.status,
        sp.reference
    FROM student_payments_archive sp
    JOIN student_accounts_archive sa ON sp.student_number = sa.student_number
    WHERE sp.status = 'Paid' OR sp.status = 'Partial Payment'
) AS combined_data
WHERE 1=1";

if ($course != '') {
    $transactionQuery .= " AND course = :course";
}
if ($section != '') {
    $transactionQuery .= " AND section = :section";
}
if ($yearlevel != '') {
    $transactionQuery .= " AND year_level = :year_level";
}
if ($specificfee != '') {
    $transactionQuery .= " AND fee_for = :fee_for";
}
if ($transaction_search != '') {
    $transactionQuery .= " AND (student_number LIKE :search
                                   OR lastname LIKE :search
                                   OR firstname LIKE :search
                                   OR reference LIKE :search
                                   OR fee_for LIKE :search
                                   OR status LIKE :search
                                   OR formatted_payment_date LIKE :search)";
}

$transactionQuery .= " ORDER BY payment_date DESC LIMIT :limit OFFSET :offset";

$transactionStmt = $pdo->prepare($transactionQuery);
if ($course != '') {
    $transactionStmt->bindValue(':course', $course, PDO::PARAM_STR);
}
if ($section != '') {
    $transactionStmt->bindValue(':section', $section, PDO::PARAM_STR);
}
if ($yearlevel != '') {
    $transactionStmt->bindValue(':year_level', $yearlevel, PDO::PARAM_STR);
}
if ($specificfee != '') {
    $transactionStmt->bindValue(':fee_for', $specificfee, PDO::PARAM_STR);
}
if ($transaction_search != '') {
    $transactionStmt->bindValue(':search', '%' . $transaction_search . '%', PDO::PARAM_STR);
}
$transactionStmt->bindValue(':limit', $transaction_limit, PDO::PARAM_INT);
$transactionStmt->bindValue(':offset', $transaction_offset, PDO::PARAM_INT);
$transactionStmt->execute();
$transactions = $transactionStmt->fetchAll(PDO::FETCH_ASSOC);

// --- Pagination for Student List ---
$student_limit = 10; // You can have a different limit if you want
$student_page = isset($_GET['student_page']) ? (int)$_GET['student_page'] : 1;
$student_offset = ($student_page - 1) * $student_limit;
$student_search = isset($_GET['student_search']) ? $_GET['student_search'] : ''; // Separate search for students

// Fetch the total number of student records, considering the search term
$totalStudentQuery = "SELECT COUNT(*) FROM student_accounts_archive WHERE 1";
if ($student_search != '') {
    $totalStudentQuery .= " AND (student_number LIKE :student_search
                           OR firstname LIKE :student_search
                           OR middlename LIKE :student_search
                           OR lastname LIKE :student_search
                           OR course LIKE :student_search)";
}
$totalStudentStmt = $pdo->prepare($totalStudentQuery);
if ($student_search != '') {
    $totalStudentStmt->bindValue(':student_search', '%' . $student_search . '%', PDO::PARAM_STR);
}
$totalStudentStmt->execute();
$totalStudentResult = $totalStudentStmt->fetchColumn();
$totalStudentRecords = $totalStudentResult;
$totalStudentPages = ceil($totalStudentRecords / $student_limit);

// Query to fetch student list with pagination and search (remains the same)
$studentQuery = "SELECT sa.id, sa.student_number, sa.student_type, sa.tuition_type, sa.firstname, sa.middlename, sa.lastname, sa.course, sa.year_level, sa.section, sa.semester,
    sa.total_tuition_fee, sa.tuition_fee_discount, sa.balance_to_be_paid, sa.down_payment, sa.total_balance, sa.remaining_balance_to_pay, sa.profile_image,
    sa.email
FROM student_accounts_archive sa
WHERE 1";
if ($student_search != '') {
    $studentQuery .= " AND (sa.student_number LIKE :student_search
                           OR sa.firstname LIKE :student_search
                           OR sa.middlename LIKE :student_search
                           OR sa.lastname LIKE :student_search
                           OR sa.course LIKE :student_search)";
}
$studentQuery .= " GROUP BY sa.student_number ORDER BY sa.id DESC LIMIT :limit OFFSET :offset";

$studentStmt = $pdo->prepare($studentQuery);
$studentStmt->bindParam(':limit', $student_limit, PDO::PARAM_INT);
$studentStmt->bindParam(':offset', $student_offset, PDO::PARAM_INT);
if ($student_search != '') {
    $studentStmt->bindValue(':student_search', '%' . $student_search . '%', PDO::PARAM_STR);
}
$studentStmt->execute();
$students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setting</title>
    <link rel="stylesheet" href="../css/content/setting.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="set-container">
        <div class="set-header">
            <img src="../../img/settingIcon.png" alt="Backup" width=30px>
            <h2>Setting</h2>
        </div>

        <div class="set-button">
            <button class="backup-b">
                <img src="../../img/backup_Icon.png" alt="Backup" width=40px>
                Backup
            </button>

            <button class="end-sem-b">
                <img src="../../img/archive_Icon.png" alt="Backup" width=40px>
                Semester End
            </button>
        </div>
        <br><hr>

        <h2>Archive</h2>
        <div class="archive-con">
            <button class="transaction-b">
                Transaction
            </button>

            <button class="student-list-b">
                Student List
            </button>
        </div>

        <div class="transaction-history" style="display: none;">
            <div class="transaction">
                <h3>Transaction Archive</h3>
            </div>
            <div class="date">
                <form method="GET" action="">
                    <div class="transaction-filter">
                        <select id="fee-dropdown" name="specificfee" class="specific-fee" onchange="this.form.submit()">
                            <option value="" <?= $specificfee === '' ? 'selected' : '' ?>>All Fee</option>
                            <option value="Prelim" <?= $specificfee === 'Prelim' ? 'selected' : '' ?>>Prelim</option>
                            <option value="Midterm" <?= $specificfee === 'Midterm' ? 'selected' : '' ?>>Midterm</option>
                            <option value="Prefinal" <?= $specificfee === 'Prefinal' ? 'selected' : '' ?>">Prefinal</option>
                            <option value="Final" <?= $specificfee === 'Final' ? 'selected' : '' ?>">Final</option>
                        </select>
                        <select id="year-level-dropdown" name="yearlevel" class="year-level" onchange="this.form.submit()">
                            <option value="" <?= $yearlevel === '' ? 'selected' : '' ?>>All Level</option>
                            <option value="1" <?= $yearlevel === '1' ? 'selected' : '' ?>>First Year</option>
                            <option value="2" <?= $yearlevel === '2' ? 'selected' : '' ?>>Second Year</option>
                            <option value="3" <?= $yearlevel === '3' ? 'selected' : '' ?>>Third Year</option>
                            <option value="4" <?= $yearlevel === '4' ? 'selected' : '' ?>>Fourth Year</option>
                        </select>
                        <select id="course-dropdown" name="course" class="course" onchange="this.form.submit()">
                            <option value="" <?= $course === '' ? 'selected' : '' ?>>All Course</option>
                            <option value="BSIT" <?= $course === 'BSIT' ? 'selected' : '' ?>>BSIT</option>
                            <option value="BSCS" <?= $course === 'BSCS' ? 'selected' : '' ?>>BSCS</option>
                            <option value="BSCE" <?= $course === 'BSCE' ? 'selected' : '' ?>>BSCE</option>
                        </select>
                        <select id="section-dropdown" name="section" class="section" onchange="this.form.submit()">
                            <option value="" <?= $section === '' ? 'selected' : '' ?>>All Section</option>
                            <option value="A" <?= $section === 'A' ? 'selected' : '' ?>>A</option>
                            <option value="B" <?= $section === 'B' ? 'selected' : '' ?>>B</option>
                            <option value="C" <?= $section === 'C' ? 'selected' : '' ?>>C</option>
                        </select>
                        <input type="search" name="search" class="search" placeholder="Search here" value="<?= htmlspecialchars($transaction_search) ?>" />
                        <!-- <button type="reset" class="reset-btn">
                            <img src="../../img/cancel.png" alt="Cancel-search" width=25px>
                        </button> -->
                        <button type="button" class="reset-btn" onclick="window.location.href = window.location.pathname + '?show_transactions=true';">
                            <img src="../../img/cancel.png" alt="Cancel-search" width=25px>
                        </button>
                        <input type="hidden" name="show_transactions" value="true"> </div>
                </form>
            </div>
            <div class="transaction-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Stud. No</th>
                            <th>Course</th>
                            <th>Fee For</th>
                            <th>Payment Date</th>
                            <th>Reference No.</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <!-- <th>View</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['student_number']) ?></td>
                                    <td><?= htmlspecialchars($row['course']) ?></td>
                                    <td><?= htmlspecialchars($row['fee_for']) ?></td>
                                    <td><?= htmlspecialchars($row['formatted_payment_date']) ?></td>
                                    <td><?= htmlspecialchars($row['reference']) ?></td>
                                    <td>₱<?= number_format($row['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['status']) ?></td>
                                    <!-- <td>
                                        <i class="far fa-eye" style="cursor: pointer;"
                                            data-student-number="<?= htmlspecialchars($row['student_number']) ?>"
                                            data-student-course="<?= htmlspecialchars($row['course']) ?>"
                                            data-student-lastname="<?= htmlspecialchars($row['lastname']) ?>"
                                            data-student-firstname="<?= htmlspecialchars($row['firstname']) ?>"
                                            data-reference="<?= htmlspecialchars($row['reference']) ?>"
                                            data-fee-for="<?= htmlspecialchars($row['fee_for']) ?>"
                                            data-payment-date="<?= htmlspecialchars($row['formatted_payment_date']) ?>"
                                            data-student-amount="₱<?= number_format($row['amount'], 2) ?>"
                                            data-student-status="<?= htmlspecialchars($row['status']) ?>">
                                        </i>
                                    </td> -->
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8">No transaction records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                <?php if ($totalTransactionPages > 1): ?>
                    <div class="page-info">
                        Page <?= $transaction_page ?> of <?= $totalTransactionPages ?>
                    </div>
                    <ul>
                        <?php if ($transaction_page > 1): ?>
                            <li><a href="?transaction_page=<?= $transaction_page - 1 ?><?= $specificfee ? '&specificfee=' . urlencode($specificfee) : '' ?><?= $yearlevel ? '&yearlevel=' . urlencode($yearlevel) : '' ?><?= $course ? '&course=' . urlencode($course) : '' ?><?= $section ? '&section=' . urlencode($section) : '' ?><?= $transaction_search ? '&search=' . urlencode($transaction_search) : '' ?>&show_transactions=true">Previous</a></li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalTransactionPages; $i++): ?>
                            <li>
                                <a href="?transaction_page=<?= $i ?><?= $specificfee ? '&specificfee=' . urlencode($specificfee) : '' ?><?= $yearlevel ? '&yearlevel=' . urlencode($yearlevel) : '' ?><?= $course ? '&course=' . urlencode($course) : '' ?><?= $section ? '&section=' . urlencode($section) : '' ?><?= $transaction_search ? '&search=' . urlencode($transaction_search) : '' ?>&show_transactions=true" <?= $i === $transaction_page ? 'class="active"' : '' ?>><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($transaction_page < $totalTransactionPages): ?>
                            <li><a href="?transaction_page=<?= $transaction_page + 1 ?><?= $specificfee ? '&specificfee=' . urlencode($specificfee) : '' ?><?= $yearlevel ? '&yearlevel=' . urlencode($yearlevel) : '' ?><?= $course ? '&course=' . urlencode($course) : '' ?><?= $section ? '&section=' . urlencode($section) : '' ?><?= $transaction_search ? '&search=' . urlencode($transaction_search) : '' ?>&show_transactions=true">Next</a></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="student-list" style="display: none;">
            <div class="transaction">
                <h3>Student List Archive</h3>
            </div>
            <div class="student-list-filter" style="margin-bottom: 10px;">
                <form method="GET" action="" style="margin-bottom: 10px; display: flex; justify-content: flex-start; align-items: center;">
                    <input type="search" name="student_search" class="search" placeholder="Search students..." value="<?= htmlspecialchars($student_search) ?>" />
                    <button type="submit" style="padding: 4px 10px; margin-left: 5px;">
                        <!-- <img src="../../img/search.png" alt="Search" width="20px"> -->
                        Search
                    </button>
                    <button type="reset" class="reset-btn" onclick="window.location.href = window.location.pathname + '?show_student_list=true';">
                        <img src="../../img/cancel.png" alt="Cancel-search" width="25px" style="text-aling: center;">
                    </button>
                    <input type="hidden" name="show_student_list" value="true">
                </form>
            </div>
            <div class="student-list-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student No.</th>
                            <th>Student Type</th>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Year Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['student_number']) ?></td>
                                    <td style="text-transform: capitalize;"><?= htmlspecialchars($student['student_type']) ?></td>
                                    <td><?= htmlspecialchars($student['firstname']) ?> <?= htmlspecialchars($student['middlename']) ?> <?= htmlspecialchars($student['lastname']) ?></td>
                                    <td><?= htmlspecialchars($student['course']) ?></td>
                                    <td><?= htmlspecialchars($student['year_level']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5">No student records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                <?php if ($totalStudentPages > 1): ?>
                    <div class="page-info">
                        Page <?= $student_page ?> of <?= $totalStudentPages ?>
                    </div>
                    <ul>
                        <?php if ($student_page > 1): ?>
                            <li><a href="?student_page=<?= $student_page - 1 ?><?= $student_search ? '&student_search=' . urlencode($student_search) : '' ?>&show_student_list=true">Previous</a></li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalStudentPages; $i++): ?>
                            <li>
                                <a href="?student_page=<?= $i ?><?= $student_search ? '&student_search=' . urlencode($student_search) : '' ?>&show_student_list=true" <?= $i === $student_page ? 'class="active"' : '' ?>><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($student_page < $totalStudentPages): ?>
                            <li><a href="?student_page=<?= $student_page + 1 ?><?= $student_search ? '&student_search=' . urlencode($student_search) : '' ?>&show_student_list=true">Next</a></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const transactionButton = document.querySelector('.transaction-b');
            const transactionHistoryDiv = document.querySelector('.transaction-history');
            const studentListButton = document.querySelector('.student-list-b');
            const studentListTableDiv = document.querySelector('.student-list');

            function showTransactionHistory() {
                transactionHistoryDiv.style.display = 'block';
                studentListTableDiv.style.display = 'none';
                transactionButton.classList.add('active-hover');
                studentListButton.classList.remove('active-hover');
            }

            function showStudentList() {
                studentListTableDiv.style.display = 'block';
                transactionHistoryDiv.style.display = 'none';
                studentListButton.classList.add('active-hover');
                transactionButton.classList.remove('active-hover');
            }

            transactionButton.addEventListener('click', showTransactionHistory);
            studentListButton.addEventListener('click', showStudentList);

            // Check on page load which section to show
            const urlParams = new URLSearchParams(window.location.search);
            const showTransactionsParam = urlParams.get('show_transactions');
            const showStudentListParam = urlParams.get('show_student_list');

            if (showTransactionsParam === 'true') {
                showTransactionHistory();
            } else if (showStudentListParam === 'true') {
                showStudentList();
            }
            // If neither is set, you can decide which to show by default (e.g., transaction history)
            else if (transactionHistoryDiv.style.display === '') {
                showTransactionHistory();
            }
        });
    </script>

</body>
</html>