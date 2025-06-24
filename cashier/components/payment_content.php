<?php
include('../../database/db.php'); // Include your database connection

// Function to get student full name (lastname and firstname)
function getStudentFullName($pdo, $student_number) {
    try {
        $stmt = $pdo->prepare("SELECT lastname, firstname FROM student_accounts WHERE student_number = :student_number");
        $stmt->execute(['student_number' => $student_number]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return $result['firstname'] . ' ' . $result['lastname']; // Format: Lastname, Firstname
        } else {
            return 'Unknown';
        }
    } catch (PDOException $e) {
        return 'Error: ' . $e->getMessage();
    }
}

// Fetch pending payments from semester_fees and student_payments table
try {
    $stmt = $pdo->prepare("
        SELECT id, student_number, fee_for, payment_date, reference, amount, payment_method, event_date_start, event_date_end, due_date
        FROM semester_fees 
        WHERE status = 'Pending'
        UNION ALL
        SELECT id, student_number, fee_for, payment_date, reference, amount, payment_method, event_date_start, event_date_end, due_date 
        FROM student_payments 
        WHERE status = 'Pending'
        ORDER BY payment_date DESC
    ");
    $stmt->execute();
    $pendingPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $pendingPayments = []; // Set to empty array to avoid errors
}

// Handle Online payment confirmation
if (isset($_POST['ConfirmOnlinePayment'])) {
    $student_number = $_POST['student_number'];
    $fee_for = $_POST['fee_for'];

    try {
        // Update status and verified_date in semester_fees table
        $stmt = $pdo->prepare("UPDATE semester_fees SET status = 'Paid', verified_date = CURDATE(), pending_timestamp = NULL WHERE student_number = :student_number AND fee_for = :fee_for");
        $stmt->execute(['student_number' => $student_number, 'fee_for' => $fee_for]);

        // Update status and verified_date in student_payments table (if needed)
        $stmt = $pdo->prepare("UPDATE student_payments SET status = 'Paid', verified_date = CURDATE(), pending_timestamp = NULL WHERE student_number = :student_number AND fee_for = :fee_for");
        $stmt->execute(['student_number' => $student_number, 'fee_for' => $fee_for]);

        // Set success message in session and redirect
        $_SESSION['confirm_success_message'] = "Payment confirmed successfully!";

        echo "<script>
            var successMessage = document.createElement('div');
            successMessage.style.position = 'fixed';
            successMessage.style.top = '20px';
            successMessage.style.left = '50%';
            successMessage.style.transform = 'translateX(-50%)';
            successMessage.style.padding = '15px';
            successMessage.style.backgroundColor = '#4CAF50';
            successMessage.style.color = '#fff';
            successMessage.style.fontSize = '16px';
            successMessage.style.borderRadius = '5px';
            successMessage.style.zIndex = '9999';
            successMessage.innerText = '" . addslashes($_SESSION['confirm_success_message']) . "';
            document.body.appendChild(successMessage);

            setTimeout(function() {
                window.location.href = 'payment_controller.php';
            }, 1000);
        </script>";

        unset($_SESSION['confirm_success_message']); 

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Handle OTC payment confirmation
if (isset($_POST['ConfirmOTCPayment'])) {
    $student_number = $_POST['student_number'];
    $fee_for = $_POST['fee_for'];
    $reference = $_POST['reference'];   
    $amount = $_POST['amount']; 

    try {
        // Fetch the original amount from student_payments
        $stmt_original = $pdo->prepare("
            SELECT amount, event_date_start, event_date_end, due_date 
            FROM student_payments 
            WHERE student_number = :student_number AND fee_for = :fee_for AND status = 'Pending'
            ");
        $stmt_original->execute(['student_number' => $student_number, 'fee_for' => $fee_for]);
        $original_data = $stmt_original->fetch(PDO::FETCH_ASSOC);

        if (!$original_data) {
            // Check if the record is in semester_fees instead
            $stmt_semester = $pdo->prepare("SELECT amount, event_date_start, event_date_end, due_date FROM semester_fees WHERE student_number = :student_number AND fee_for = :fee_for AND status = 'Pending'");
            $stmt_semester->execute(['student_number' => $student_number, 'fee_for' => $fee_for]);
            $semester_data = $stmt_semester->fetch(PDO::FETCH_ASSOC);

            if($semester_data){
                //Full payment update for semester_fees
                $stmt_full_update = $pdo->prepare("UPDATE semester_fees SET status = 'Paid', payment_date = CURDATE(), verified_date = CURDATE(), pending_timestamp = NULL, reference = :reference, amount = :amount WHERE student_number = :student_number AND fee_for = :fee_for AND status = 'Pending'");
                $stmt_full_update->execute(['student_number' => $student_number, 'fee_for' => $fee_for, 'reference' => $reference, 'amount' => $amount]);

                $_SESSION['confirm_success_message'] = "Payment confirmed successfully!";
                echo "<script>
                    var successMessage = document.createElement('div');
                    successMessage.style.position = 'fixed';
                    successMessage.style.top = '20px';
                    successMessage.style.left = '50%';
                    successMessage.style.transform = 'translateX(-50%)';
                    successMessage.style.padding = '15px';
                    successMessage.style.backgroundColor = '#4CAF50';
                    successMessage.style.color = '#fff';
                    successMessage.style.fontSize = '16px';
                    successMessage.style.borderRadius = '5px';
                    successMessage.style.zIndex = '9999';
                    successMessage.innerText = '" . addslashes($_SESSION['confirm_success_message']) . "';
                    document.body.appendChild(successMessage);

                    setTimeout(function() {
                        window.location.href = 'payment_controller.php';
                    }, 1000);
                </script>";
                unset($_SESSION['confirm_success_message']);
                return;
            }else{
                echo "Error: Original payment data not found.";
                return;
            }
        }

        $original_amount = $original_data['amount'];
        $event_date_start = $original_data['event_date_start'];
        $event_date_end = $original_data['event_date_end'];
        $due_date = $original_data['due_date'];

        if ($amount < $original_amount) {
            // Partial Payment
            $remaining_balance = $original_amount - $amount;

            // Update the original payment record
            $stmt_update = $pdo->prepare("UPDATE student_payments SET status = 'Partial Payment', payment_date = CURDATE(), verified_date = CURDATE(), pending_timestamp = NULL, reference = :reference, amount = :amount WHERE student_number = :student_number AND fee_for = :fee_for AND status = 'Pending'");
            $stmt_update->execute(['student_number' => $student_number, 'fee_for' => $fee_for, 'reference' => $reference, 'amount' => $amount]);

            // Create a new payment record for the remaining balance
            $new_fee_for = $fee_for . " balance";

            $stmt_insert = $pdo->prepare("INSERT INTO student_payments (student_number, fee_for, amount, payment_date, status, event_date_start, event_date_end, due_date) VALUES (:student_number, :fee_for, :remaining_balance, CURDATE(), 'Unpaid', :event_date_start, :event_date_end, :due_date)");
            $stmt_insert->execute([
                'student_number' => $student_number,
                'fee_for' => $new_fee_for,
                'remaining_balance' => $remaining_balance,
                'event_date_start' => $event_date_start,
                'event_date_end' => $event_date_end,
                'due_date' => $due_date
            ]);

            $_SESSION['confirm_success_message'] = "Partial payment recorded. Remaining balance added for payment.";
        } else {
            // Full Payment
            $stmt_full_update = $pdo->prepare("UPDATE student_payments SET status = 'Paid', payment_date = CURDATE(), verified_date = CURDATE(), pending_timestamp = NULL, reference = :reference, amount = :amount WHERE student_number = :student_number AND fee_for = :fee_for AND status = 'Pending'");
            $stmt_full_update->execute(['student_number' => $student_number, 'fee_for' => $fee_for, 'reference' => $reference, 'amount' => $amount]);
            // Set success message in session and redirect
            $_SESSION['confirm_success_message'] = "Payment confirmed successfully!";
        }
        
        echo "<script>
            var successMessage = document.createElement('div');
            successMessage.style.position = 'fixed';
            successMessage.style.top = '20px';
            successMessage.style.left = '50%';
            successMessage.style.transform = 'translateX(-50%)';
            successMessage.style.padding = '15px';
            successMessage.style.backgroundColor = '#4CAF50';
            successMessage.style.color = '#fff';
            successMessage.style.fontSize = '16px';
            successMessage.style.borderRadius = '5px';
            successMessage.style.zIndex = '9999';
            successMessage.innerText = '" . addslashes($_SESSION['confirm_success_message']) . "';
            document.body.appendChild(successMessage);

            setTimeout(function() {
                window.location.href = 'payment_controller.php';
            }, 1000);
        </script>";

        unset($_SESSION['confirm_success_message']); 

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Function to format date to words
function formatDateToWords($dateString) {
    $date = new DateTime($dateString);
    return $date->format('F j, Y'); // Example: "January 1, 2024"
}

// Function to revert pending status after 12 hours
function revertPendingStatus($pdo) {

    $query_semester_fees = "UPDATE semester_fees SET status = 'Unpaid', payment_method = NULL, pending_timestamp = NULL WHERE status = 'Pending' AND pending_timestamp < ?";
    $query_student_payments = "UPDATE student_payments SET status = 'Unpaid', payment_method = NULL, pending_timestamp = NULL WHERE status = 'Pending' AND pending_timestamp < ?";

    try {
        $stmt_semester_fees = $pdo->prepare($query_semester_fees);
        $stmt_semester_fees->execute(); // UNCOMMENT THIS
        $stmt_student_payments = $pdo->prepare($query_student_payments);
        $stmt_student_payments->execute(); // UNCOMMENT THIS

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
}
// Run the revert pending status function
revertPendingStatus($pdo);


// Initialize variables
$pendingPayments = [];
$searchQuery = '';

// Handle search query
if (isset($_GET['search-payments'])) {
    $searchQuery = $_GET['search-payments'];
    try {
        $stmt = $pdo->prepare("
            SELECT sf.id, sf.student_number, sf.fee_for, sf.payment_date, sf.reference, sf.amount, sf.payment_method, sf.event_date_start, sf.event_date_end, sf.due_date, sa.firstname, sa.lastname
            FROM semester_fees sf
            JOIN student_accounts sa ON sf.student_number = sa.student_number
            WHERE sf.status = 'Pending' AND (CONCAT(sa.firstname, ' ', sa.lastname) 
                LIKE :searchQuery OR sf.student_number 
                LIKE :searchQuery OR sf.fee_for 
                LIKE :searchQuery OR sf.payment_method 
                LIKE :searchQuery OR sf.amount
                LIKE :searchQuery)
            UNION ALL
            SELECT sp.id, sp.student_number, sp.fee_for, sp.payment_date, sp.reference, sp.amount, sp.payment_method, sp.event_date_start, sp.event_date_end, sp.due_date, sa.firstname, sa.lastname
            FROM student_payments sp
            JOIN student_accounts sa ON sp.student_number = sa.student_number
            WHERE sp.status = 'Pending' AND (CONCAT(sa.firstname, ' ', sa.lastname) 
                LIKE :searchQuery OR sp.student_number 
                LIKE :searchQuery OR sp.fee_for 
                LIKE :searchQuery OR sp.payment_method 
                LIKE :searchQuery OR sp.amount
                LIKE :searchQuery)
            ORDER BY payment_date DESC
        ");
        $stmt->execute(['searchQuery' => '%' . $searchQuery . '%']);
        $pendingPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        $pendingPayments = [];
    }
} else {
    // Fetch all pending payments if no search query
    try {
        $stmt = $pdo->prepare("
            SELECT id, student_number, fee_for, payment_date, reference, amount, payment_method 
            FROM semester_fees 
            WHERE status = 'Pending'
            UNION ALL
            SELECT id, student_number, fee_for, payment_date, reference, amount, payment_method 
            FROM student_payments 
            WHERE status = 'Pending'
            ORDER BY payment_date DESC
        ");
        $stmt->execute();
        $pendingPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        $pendingPayments = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="stylesheet" href="../css/content/payment.css">

</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h2>Payments</h2>
            <!-- <input type="search" name="search-payments" placeholder="Search here..."> -->

            <form method="get">
                <input type="search" name="search-payments" placeholder="Search here..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">Search</button>
            </form>

            
            <!-- <div class="sidebar-footer-a"> -->
                <form method="post" onsubmit="return confirm('Are you sure you want to cancel ALL pending payments over the counter? This action cannot be undone.');">
                    <button type="submit" name="forceRevertAll" class="revert-all-btn" style="width: 100px; Background-color: green;">
                        <!-- <img src="../../img/cancel-otc.png" alt="OTC" width=28px style="margin-right: 10px;"> -->
                        <span>Cancel OTC</span>
                    </button>
                </form> 
            <!-- </div> -->
            
        </div>
        <div class="payment-details">
            <table class="payment-table">
                <thead">
                    <tr>
                        <th>Stud #</th>
                        <th>Name</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Reference / Receipt No.</th>
                        <th>Amount</th>
                        <th title="Mode of Payment">MOP</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingPayments as $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['student_number']); ?></td>
                            <td><?php echo htmlspecialchars(getStudentFullName($pdo, $payment['student_number'])); ?></td>
                            <td><?php echo htmlspecialchars($payment['fee_for']); ?></td>
                            <td><?php echo formatDateToWords($payment['payment_date']); ?></td>
                            <td><?php echo htmlspecialchars($payment['reference']); ?></td>
                            <td><?php echo '₱' . number_format($payment['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                            <td>
                                <form method="post" class="confirm-form">
                                    <input type="hidden" name="student_number" value="<?php echo htmlspecialchars($payment['student_number']); ?>">
                                    <input type="hidden" name="fee_for" value="<?php echo htmlspecialchars($payment['fee_for']); ?>">
                                    <input type="hidden" name="amount" value="<?php echo htmlspecialchars($payment['amount']); ?>">

                                    <button type="button" class="action-button confirm-btn" 
                                        data-student="<?php echo htmlspecialchars($payment['student_number']); ?>" 
                                        data-fee="<?php echo htmlspecialchars($payment['fee_for']); ?>"
                                        data-amount="<?php echo htmlspecialchars($payment['amount']); ?>"
                                        data-method="<?php echo htmlspecialchars($payment['payment_method']); ?>"
                                        data-date="<?php echo htmlspecialchars(formatDateToWords($payment['payment_date'])); ?>"
                                        data-reference="<?php echo htmlspecialchars($payment['reference']); ?>"
                                        data-fullname="<?php echo htmlspecialchars(getStudentFullName($pdo, $payment['student_number'])); ?>"
                                    
                                        >Confirm</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
