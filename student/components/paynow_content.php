<!-- paynow_content.php -->
<?php
// Include the database connection
include('../../database/db.php');

// Ensure the student number is available
if (!isset($student_number)) {
    die("Student number is not set. Please log in to view your fees.");
}

// Default values for pagination
$limit = 6; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number from URL
$offset = ($page - 1) * $limit; // Offset for the SQL query

// Fetch the total number of rows for the specific student
// $totalQuery = "SELECT COUNT(*) as total FROM semester_fees WHERE student_number = :student_number AND event_date_start <= CURDATE() AND event_date_end >= CURDATE()";
$totalQuery = "SELECT COUNT(*) as total FROM (
    SELECT id FROM semester_fees WHERE student_number = :student_number AND event_date_start <= CURDATE() AND status != 'Paid' AND status != 'Partial Payment'
    UNION ALL
    SELECT id FROM student_payments WHERE student_number = :student_number AND status != 'Paid' AND status != 'Partial Payment' AND event_date_start <= CURDATE()
) AS combined_results";
$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->bindValue(':student_number', $student_number, PDO::PARAM_STR);
$totalStmt->execute();
$totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $limit); // Calculate total pages

$query = "SELECT p.id, p.fee_for, 
                 CONCAT(DATE_FORMAT(p.event_date_start, '%M %d'), ' - ', DATE_FORMAT(p.event_date_end, '%M %d')) AS event_date, 
                 p.amount, p.due_date, DATE_FORMAT(p.due_date, '%M %d, %Y') AS formatted_due_date, p.status, a.total_balance, a.remaining_balance_to_pay
          FROM semester_fees p
          LEFT JOIN student_accounts a ON p.student_number = a.student_number
          WHERE p.student_number = :student_number 
          AND p.event_date_start <= CURDATE()
          AND p.status != 'Paid' AND p.status != 'Partial Payment'
          
          UNION ALL
          
          SELECT sp.id, sp.fee_for, 
                 CONCAT(DATE_FORMAT(sp.event_date_start, '%M %d'), ' - ', DATE_FORMAT(sp.event_date_end, '%M %d')) AS event_date, 
                 sp.amount, sp.due_date, DATE_FORMAT(sp.due_date, '%M %d, %Y') AS formatted_due_date, sp.status, a.total_balance, a.remaining_balance_to_pay
          FROM student_payments sp
          LEFT JOIN student_accounts a ON sp.student_number = a.student_number
          WHERE sp.student_number = :student_number
          AND sp.status != 'Paid' AND sp.status != 'Partial Payment'
          AND sp.event_date_start <= CURDATE() 

          ORDER BY 
              CASE fee_for -- or sp.fee_for, since they are the same in the UNION
                  WHEN 'Prelim' THEN 1
                  WHEN 'Midterm' THEN 2
                  WHEN 'Prefinal' THEN 3
                  WHEN 'Final' THEN 4
                  ELSE 5 -- For any other fee_for values, order them last
              END,
              due_date ASC -- Then order by due_date within each fee_for group
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':student_number', $student_number, PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();


// Query to fetch data from the student_accounts table
$accountCardQuery = "SELECT total_tuition_fee, tuition_fee_discount, balance_to_be_paid, down_payment, total_balance, remaining_balance_to_pay, firstname, lastname
                FROM student_accounts 
                WHERE student_number = :student_number";

$accountCardStmt = $pdo->prepare($accountCardQuery);
$accountCardStmt->bindValue(':student_number', $student_number, PDO::PARAM_STR);
$accountCardStmt->execute();
$accountCardData = $accountCardStmt->fetch(PDO::FETCH_ASSOC);

if (!$accountCardData) {
    die("No account card data found for this student.");
}

$firstname = $accountCardData['firstname'];
$lastname = $accountCardData['lastname'];

// Fetch Paid Fees (fees that are already marked as 'Paid')
$paidFeesQuery = "SELECT fee_for, amount FROM semester_fees 
                  WHERE student_number = :student_number AND status = 'Paid' AND deducted = 0";

$paidFeesStmt = $pdo->prepare($paidFeesQuery);
$paidFeesStmt->bindValue(':student_number', $student_number, PDO::PARAM_STR);
$paidFeesStmt->execute();
$paidFees = $paidFeesStmt->fetchAll(PDO::FETCH_ASSOC);

// Deduct the total amount of paid fees
$totalDeduction = 0;
$paidFeeCategories = [];
// Deduct the total amount of paid fees
foreach ($paidFees as $paidFee) {
    $totalDeduction += $paidFee['amount'];  // Add the paid fee amount to the total deduction
    $paidFeeCategories[] = $paidFee['fee_for']; // Store the paid fee category (Prelim, Midterm, etc.)

    // Mark the fee as deducted
    $updateDeductionQuery = "UPDATE semester_fees SET deducted = 1 
                             WHERE student_number = :student_number AND fee_for = :fee_for";
    $updateDeductionStmt = $pdo->prepare($updateDeductionQuery);
    $updateDeductionStmt->execute([
        ':student_number' => $student_number,
        ':fee_for' => $paidFee['fee_for']
    ]);
}


// Update remaining_balance_to_pay after deductions
if ($totalDeduction > 0) {
    // Only update remaining balance if it hasn't been already deducted
    $newRemainingBalanceToPay = $accountCardData['remaining_balance_to_pay'] - $totalDeduction;

    // Prevent updating if the deduction is already reflected
    if ($newRemainingBalanceToPay != $accountCardData['remaining_balance_to_pay']) {
        $updateBalanceQuery = "UPDATE student_accounts 
                               SET remaining_balance_to_pay = :new_remaining_balance_to_pay
                               WHERE student_number = :student_number";
        $updateBalanceStmt = $pdo->prepare($updateBalanceQuery);
        $updateBalanceStmt->execute([ 
            ':new_remaining_balance_to_pay' => $newRemainingBalanceToPay, 
            ':student_number' => $student_number 
        ]);
    }
}

// After fetching the remaining balance to pay from student_accounts table

// Insert "Prelim", "Midterm", "Prefinal", "Final" if not already in semester_fees for this student
$fees = ['Prelim', 'Midterm', 'Prefinal', 'Final'];

// Check if the fees already exist in semester_fees for this student
foreach ($fees as $fee) {
    $checkQuery = "SELECT COUNT(*) as fee_count FROM semester_fees WHERE student_number = :student_number AND fee_for = :fee_for";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->bindValue(':student_number', $student_number, PDO::PARAM_STR);
    $checkStmt->bindValue(':fee_for', $fee, PDO::PARAM_STR);
    $checkStmt->execute();
    $feeCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['fee_count'];

    // If the fee doesn't exist, insert it
    if ($feeCount == 0) {
        $insertQuery = "INSERT INTO semester_fees (student_number, fee_for, amount, status) 
                        VALUES (:student_number, :fee_for, 0, 'Unpaid')";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([
            ':student_number' => $student_number,
            ':fee_for' => $fee
        ]);
    }
}

// Now, let's divide the remaining_balance_to_pay among the unpaid fees
$unpaidFees = [];

// Fetch the unpaid fees for the student
foreach ($fees as $fee) {
    // Check if the fee is unpaid
    $feeStatusQuery = "SELECT status FROM semester_fees WHERE student_number = :student_number AND fee_for = :fee_for";
    $feeStatusStmt = $pdo->prepare($feeStatusQuery);
    $feeStatusStmt->bindValue(':student_number', $student_number, PDO::PARAM_STR);
    $feeStatusStmt->bindValue(':fee_for', $fee, PDO::PARAM_STR);
    $feeStatusStmt->execute();
    $feeStatus = $feeStatusStmt->fetch(PDO::FETCH_ASSOC);

    if ($feeStatus['status'] != 'Paid') {
        // If the fee is unpaid, add it to the unpaidFees array
        $unpaidFees[] = $fee;
    }
}

// If there are unpaid fees, divide the remaining balance between them
if ($accountCardData['remaining_balance_to_pay'] > 0 && count($unpaidFees) > 0) {
    $remainingBalance = $accountCardData['remaining_balance_to_pay'];

    // Calculate the divided amount for the remaining unpaid fees
    $dividedAmount = $remainingBalance / count($unpaidFees); // Divide by the count of unpaid fees

    // Update each unpaid fee with the divided amount
    foreach ($unpaidFees as $feeFor) {
        // Only update the amount of unpaid fees
        $updateQuery = "UPDATE semester_fees 
                        SET amount = :amount, firstname = :firstname, lastname = :lastname
                        WHERE student_number = :student_number AND fee_for = :fee_for AND status != 'Paid'";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute([ 
            ':amount' => $dividedAmount, 
            ':student_number' => $student_number, 
            ':firstname' => $firstname, 
            ':lastname' => $lastname, 
            ':fee_for' => $feeFor 
        ]);
    }
}



// Fetch the remaining fees (Prelim, Midterm, Prefinal, Final) for the student
$remainingFeesQuery = "SELECT fee_for, amount, status 
                       FROM semester_fees 
                       WHERE student_number = :student_number 
                       AND fee_for IN ('Prelim', 'Midterm', 'Prefinal', 'Final', 'Remaining Balance')";
$remainingFeesStmt = $pdo->prepare($remainingFeesQuery);
$remainingFeesStmt->bindValue(':student_number', $student_number, PDO::PARAM_STR);
$remainingFeesStmt->execute();
$remainingFees = $remainingFeesStmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize fee data array for easier access
$feesData = [
    'Prelim' => ['amount' => 0, 'status' => 'Unpaid'],
    'Midterm' => ['amount' => 0, 'status' => 'Unpaid'],
    'Prefinal' => ['amount' => 0, 'status' => 'Unpaid'],
    'Final' => ['amount' => 0, 'status' => 'Unpaid'],
    'Remaining Balance' => ['amount' => 0, 'status' => 'Unpaid'],
];

// Check if a "Remaining Balance" fee entry exists
$remainingBalanceExists = false;
foreach ($remainingFees as $fee) {
    if ($fee['fee_for'] === 'Remaining Balance') {
        $remainingBalanceExists = true;
        break;
    }
}

// Fetch event dates and due date from the "Final" fee
$finalFeeQuery = "SELECT `event_date_start`, `event_date_end`, `due_date` FROM `semester_fees` WHERE `fee_for` = 'Final' AND `student_number` = :student_number";
$finalFeeStmt = $pdo->prepare($finalFeeQuery);
$finalFeeStmt->execute([':student_number' => $student_number]);
$finalFeeData = $finalFeeStmt->fetch(PDO::FETCH_ASSOC);

if ($finalFeeData) {
    $eventStartDate = $finalFeeData['event_date_start'];
    $eventEndDate = $finalFeeData['event_date_end'];
    $dueDate = $finalFeeData['due_date'];
} else {
    $eventStartDate = date('Y-m-d');
    $eventEndDate = date('Y-m-d');
    $dueDate = date('Y-m-d');
}
// new inserted code for creating a payment if there is still a remaining balance even though all payment is paid
// Check if there's a remaining balance and all Prelim, Midterm, Prefinal, Final are paid
if ($accountCardData['remaining_balance_to_pay'] > 0) {
    $allFeesPaid = true;
    $feesToCheck = ['Prelim', 'Midterm', 'Prefinal', 'Final'];

    foreach ($feesToCheck as $fee) {
        $checkPaidQuery = "SELECT status FROM semester_fees WHERE student_number = :student_number AND fee_for = :fee_for";
        $checkPaidStmt = $pdo->prepare($checkPaidQuery);
        $checkPaidStmt->execute([':student_number' => $student_number, ':fee_for' => $fee]);
        $feeStatus = $checkPaidStmt->fetch(PDO::FETCH_ASSOC);

        if ($feeStatus && $feeStatus['status'] != 'Paid') {
            $allFeesPaid = false;
            break; // No need to check further if any fee is unpaid
        }
    }

    // If all four fees are paid, create a new entry for the remaining balance
    if ($allFeesPaid) {
        // Check if the "Remaining Balance" fee already exists
        $checkRemainingQuery = "SELECT COUNT(*) FROM semester_fees WHERE student_number = :student_number AND fee_for = 'Remaining Balance'";
        $checkRemainingStmt = $pdo->prepare($checkRemainingQuery);
        $checkRemainingStmt->execute([':student_number' => $student_number]);
        $remainingCount = $checkRemainingStmt->fetchColumn();

        if ($remainingCount == 0) { // If it doesn't exist, insert it
            $insertRemainingQuery = "INSERT INTO semester_fees (student_number, fee_for, amount, status, due_date, event_date_start, event_date_end, firstname, lastname) VALUES (:student_number, 'Remaining Balance', :remaining_balance, 'Unpaid', :due_date, :event_date_start, :event_date_end, :firstname, :lastname)"; //you can change the due date and event dates to your preference.
            $insertRemainingStmt = $pdo->prepare($insertRemainingQuery);
            $insertRemainingStmt->execute([
                ':student_number' => $student_number,
                ':remaining_balance' => $accountCardData['remaining_balance_to_pay'],
                ':due_date' => $dueDate,
                ':event_date_start' => $eventStartDate,
                ':event_date_end' => $eventEndDate,
                ':firstname' => $firstname,
                ':lastname' => $lastname,
            ]);
            echo "<script>console.log('Remaining balance fee created.');</script>";
        }
    }
}

// Populate the fee data array with actual data from the database
foreach ($remainingFees as $fee) {
    $feesData[$fee['fee_for']] = [
        'amount' => $fee['amount'],
        'status' => $fee['status'],
    ];
}

//this is for fetching the remaining balance and display inside the REMAINING FEES box
// Fetch Remaining Balance fee data
$remainingBalanceFeeQuery = "SELECT amount, status FROM semester_fees WHERE student_number = :student_number AND fee_for = 'Remaining Balance'";
$remainingBalanceFeeStmt = $pdo->prepare($remainingBalanceFeeQuery);
$remainingBalanceFeeStmt->execute([':student_number' => $student_number]);
$remainingBalanceFeeData = $remainingBalanceFeeStmt->fetch(PDO::FETCH_ASSOC);

// Initialize remaining balance data if not found
if (!$remainingBalanceFeeData) {    
    $remainingBalanceFeeData = ['amount' => 0, 'status' => 'Unpaid'];
}

// Populate the fee data array with actual data from the database
foreach ($remainingFees as $fee) {
    $feesData[$fee['fee_for']] = [
        'amount' => $fee['amount'],
        'status' => $fee['status'],
    ];
}

// Add the remaining balance data to the feesData array
$feesData['Remaining Balance'] = [
    'amount' => $remainingBalanceFeeData['amount'],
    'status' => $remainingBalanceFeeData['status'],
];

// Check if all Prelim, Midterm, Prefinal, Final fees are paid
$allFeesPaid = true;
$feesToCheck = ['Prelim', 'Midterm', 'Prefinal', 'Final'];

foreach ($feesToCheck as $fee) {
    $checkPaidQuery = "SELECT status FROM semester_fees WHERE student_number = :student_number AND fee_for = :fee_for";
    $checkPaidStmt = $pdo->prepare($checkPaidQuery);
    $checkPaidStmt->execute([':student_number' => $student_number, ':fee_for' => $fee]);
    $feeStatus = $checkPaidStmt->fetch(PDO::FETCH_ASSOC);

    if ($feeStatus && $feeStatus['status'] != 'Paid') {
        $allFeesPaid = false;
        break; // No need to check further if any fee is unpaid
    }
}

// Check if there is a remaining balance to pay
$hasRemainingBalance = ($accountCardData['remaining_balance_to_pay'] > 0);

// Set a flag to control the display of the remaining balance fee label
$showRemainingBalanceFee = $allFeesPaid && $hasRemainingBalance;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Now</title>
    <link rel="stylesheet" href="../css/content/paynow.css">
</head>
<body>
    <div class="paynow">
        <div class="balance-details">
            <h2>Balance</h2>
            <div class="balance-con">
                <div class="sem-fee">    
                    <label class="fees">SEMESTER FEES</label>
                    <div class="balance-con-box">
                        <label>
                            <span>Total Tuition Fee:</span> 
                            <span>₱<?= number_format($accountCardData['total_tuition_fee'], 2) ?></span>
                        </label>
                        <label>
                            <span>Tuition Fee Discount:</span> 
                            <span>₱<?= number_format($accountCardData['tuition_fee_discount'], 2) ?></span>
                        </label>
                        <label>
                            <span>Balance to be Paid:</span> 
                            <span>₱<?= number_format($accountCardData['balance_to_be_paid'], 2) ?></span>
                        </label>
                        <label>
                            <span>Down Payment:</span> 
                            <span>₱<?= number_format($accountCardData['down_payment'], 2) ?></span>
                        </label>
                        <label>
                            <span>Total Balance:</span> 
                            <span>₱<?= number_format($accountCardData['total_balance'], 2) ?></span>
                        </label>
                    </div>
                </div>
                <div class="remaining-fee"> 
                    <label class="fees">REMAINING FEES</label>
                    <div class="balance-con-box">
                        <label>
                            <span>Remaining Balance to pay: </span>  
                            <span>₱<?= number_format($accountCardData['remaining_balance_to_pay'], 2) ?></span> 
                        </label>
                        <label>
                            <span>Prelim Fee:</span> 
                            <span id="prelim-amount">₱<?= number_format($feesData['Prelim']['amount'], 2) ?></span>
                            <span id="prelim-status">(<?= htmlspecialchars($feesData['Prelim']['status']) ?>)</span>
                        </label>
                        <label>
                            <span>Midterm Fee:</span> 
                            <span id="midterm-amount">₱<?= number_format($feesData['Midterm']['amount'], 2) ?></span>
                            <span id="prelim-status">(<?= htmlspecialchars($feesData['Midterm']['status']) ?>)</span>
                            <input type="hidden" id="midterm-status" value="<?= htmlspecialchars($feesData['Midterm']['status']) ?>">
                        </label>
                        <label>
                            <span>Prefinal Fee:</span> 
                            <span id="prefinal-amount">₱<?= number_format($feesData['Prefinal']['amount'], 2) ?></span>
                            <span id="prelim-status">(<?= htmlspecialchars($feesData['Prefinal']['status']) ?>)</span>
                            <input type="hidden" id="prefinal-status" value="<?= htmlspecialchars($feesData['Prefinal']['status']) ?>">
                        </label>
                        <label>
                            <span>Final Fee:</span> 
                            <span id="final-amount">₱<?= number_format($feesData['Final']['amount'], 2) ?></span>
                            <span id="prelim-status">(<?= htmlspecialchars($feesData['Final']['status']) ?>)</span>
                            <input type="hidden" id="final-status" value="<?= htmlspecialchars($feesData['Final']['status']) ?>">
                        </label>
                        <!-- <?php if ($showRemainingBalanceFee): ?>
                            <label>
                                <span>Remaining Balance Fee:</span>
                                <span id="remaining-balance-amount">₱<?= number_format($feesData['Remaining Balance']['amount'], 2) ?></span>
                                <span id="remaining-balance-status">(<?= htmlspecialchars($feesData['Remaining Balance']['status']) ?>)</span>
                                <input type="hidden" id="remaining-balance-status" value="<?= htmlspecialchars($feesData['Remaining Balance']['status']) ?>">
                            </label>
                        <?php endif; ?> -->

                        <?php if ($remainingBalanceExists): ?>
                            <label>
                                <span>Remaining Balance Fee:</span>
                                <span id="remaining-balance-amount">₱<?= number_format($feesData['Remaining Balance']['amount'], 2) ?></span>
                                <span id="remaining-balance-status">(<?= htmlspecialchars($feesData['Remaining Balance']['status']) ?>)</span>
                                <input type="hidden" id="remaining-balance-status" value="<?= htmlspecialchars($feesData['Remaining Balance']['status']) ?>">
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <br>

        <div class="paynow-header">
            <h2>Available Fees to Pay</h2>
            <button class="button" id="payNowButton">
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
                <!-- Display current page of total pages -->
                <div class="page-info">
                    Page <?= $page ?> of <?= $totalPages ?>
                </div>

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
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[name="select_amount"]');
            const payNowButton = document.getElementById('payNowButton');

            function updatePayNowButton() {
                let checked = false;
                checkboxes.forEach(function(checkbox) {
                    if (checkbox.checked) {
                        checked = true;
                    }
                });
                payNowButton.disabled = !checked;
            }

            // Initialize the button state on page load
            updatePayNowButton();

            // Add event listeners to checkboxes
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', updatePayNowButton);
            });
        });
    </script>
</body>
</html>

<?php

// Check if the 'Remaining Balance' fee is paid
$remainingBalancePaidQuery = "SELECT status FROM semester_fees WHERE student_number = :student_number AND fee_for = 'Remaining Balance'";
$remainingBalancePaidStmt = $pdo->prepare($remainingBalancePaidQuery);
$remainingBalancePaidStmt->execute([':student_number' => $student_number]);
$remainingBalancePaidStatus = $remainingBalancePaidStmt->fetch(PDO::FETCH_ASSOC);

if ($remainingBalancePaidStatus && $remainingBalancePaidStatus['status'] == 'Paid') {
    // Update the remaining_balance_to_pay in student_accounts to 0
    $updateRemainingBalance = "UPDATE student_accounts SET remaining_balance_to_pay = 0 WHERE student_number = :student_number";
    $updateStmt = $pdo->prepare($updateRemainingBalance);
    $updateStmt->execute([':student_number' => $student_number]);

    // Refresh the accountCardData after the update
    $accountCardStmt->execute();
    $accountCardData = $accountCardStmt->fetch(PDO::FETCH_ASSOC);

    // Optional: Provide feedback to the user (e.g., a message)
    echo "<script>console.log('Remaining balance paid, student_accounts updated.');</script>";
}
?>