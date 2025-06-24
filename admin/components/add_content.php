<?php
include '../../database/db.php';

$limit = 12; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number from URL
$offset = ($page - 1) * $limit; // Offset for the SQL query

// Fetch the total number of records in payment_fees
$totalQuery = "SELECT COUNT(fee_for) as total 
               FROM created_payments";
$totalStmt = $pdo->query($totalQuery);
$totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $limit); // Calculate total pages

$tuitionquery = "SELECT fee_for, event_date_start, DATE_FORMAT(event_date_start, '%M %d') AS formatted_event_date_start, 
                        event_date_end, DATE_FORMAT(event_date_end, '%M %d, %Y') AS formatted_event_date_end, due_date, DATE_FORMAT(due_date, '%M %d, %Y') AS formatted_due_date
                 FROM semester_fees 
                 WHERE fee_for IN ('Prelim', 'Midterm', 'Prefinal', 'Final')
                 GROUP BY fee_for 
                 ORDER BY FIELD(fee_for, 'Prelim', 'Midterm', 'Prefinal', 'Final') 
                 LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($tuitionquery);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);   // Bind the limit parameter
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT); // Bind the offset parameter
$stmt->execute();
$tuition_fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['tuition_old'])) {
        // Process tuition update (without modifying fee_for)
        $old_tuition = $_POST['tuition_old'];
        $event_date_start = $_POST['event_date_start'];
        $event_date_end = $_POST['event_date_end'];
        $due_date = $_POST['due_date'];

        try {
            $query = "UPDATE semester_fees 
                      SET event_date_start = :event_date_start, 
                          event_date_end = :event_date_end, due_date = :due_date
                      WHERE fee_for = :old_tuition";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':event_date_start' => $event_date_start,
                ':event_date_end' => $event_date_end,
                ':due_date' => $due_date,
                ':old_tuition' => $old_tuition
            ]);

            $_SESSION['add_success_message'] = "Tuition updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating tuition: " . $e->getMessage();
        }
    }
}

// Fetch created fees from the payment_fees table
$query = "SELECT payment_id, student_type, tuition_type, course, year_level, fee_for, amount, event_date_start, DATE_FORMAT(event_date_start, '%M %d') AS formatted_event_date_start, 
          event_date_end, DATE_FORMAT(event_date_end, '%M %d, %Y') AS formatted_event_date_end, due_date, DATE_FORMAT(due_date, '%M %d, %Y') AS formatted_due_date
          FROM created_payments 
          ORDER BY payment_id DESC
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['form_type'])) {
        $formType = $_POST['form_type']; 
        $studentType = $_POST['student-type'] ?? '';
        $tuitionType = $_POST['tuition-type'] ?? '';
        $course = $_POST['course'] ?? '';
        $yearLevel = $_POST['yearlevel'] ?? '';
        $feeFor = $_POST['fee-for'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $eventStart = $_POST['event-start'] ?? '';
        $eventEnd = $_POST['deadline'] ?? '';//event-end
        $deadline = $_POST['deadline'] ?? '';

        // Convert course string to an array
        $courseArray = explode(",", $course);
        $yearLevelArray = explode(",", $yearLevel);

        try {
            $pdo->beginTransaction(); // Start a transaction for consistency

            // 1. Insert the fee into the created_payments table
            $stmt = $pdo->prepare("INSERT INTO created_payments
                (course, year_level, student_type, tuition_type, fee_for, event_date_start, event_date_end, amount, due_date, status, reference, created_at, updated_at, deducted) 
                VALUES 
                (:course, :year_level, :student_type, :tuition_type, :fee_for, :event_start, :event_end, :amount, :due_date, 'unpaid', '', NOW(), NOW(), 0)");

            // Handle "All" case - store as "All" in the created_payments table
            // $stmt->bindValue(':course', $course === 'All' ? 'All' : $course);
            $stmt->bindValue(':course', in_array("All", $courseArray) ? 'All' : $course);
            $stmt->bindValue(':year_level', in_array("All", $yearLevelArray) ? 'All' : $yearLevel);
            // $stmt->bindValue(':year_level', $yearLevel === 'All' ? 'All' : $yearLevel);
            $stmt->bindValue(':student_type', $studentType === 'All' ? 'All' : $studentType);
            $stmt->bindValue(':tuition_type', $tuitionType === 'All' ? 'All' : $tuitionType);
            $stmt->bindParam(':fee_for', $feeFor);
            $stmt->bindParam(':event_start', $eventStart);
            $stmt->bindParam(':event_end', $eventEnd);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':due_date', $deadline);
            $stmt->execute();

            // 2. Retrieve matching student_numbers from student_accounts
            $studentQuery = "SELECT student_number FROM student_accounts WHERE 1=1";
            $params = [];

            if ($studentType !== 'All') {
                $studentQuery .= " AND student_type = :student_type";
                $params[':student_type'] = $studentType;
            }
            if ($tuitionType !== 'All') {
                $studentQuery .= " AND tuition_type = :tuition_type";
                $params[':tuition_type'] = $tuitionType;
            }
            if (!in_array("All", $courseArray)) {
                $studentQuery .= " AND FIND_IN_SET(course, :course)";
                $params[':course'] = implode(",", $courseArray);
            }
            // if ($yearLevel !== 'All') {
            //     $studentQuery .= " AND year_level = :year_level";
            //     $params[':year_level'] = $yearLevel;
            // }
            if (!in_array("All", $yearLevelArray)) {
                $studentQuery .= " AND FIND_IN_SET(year_level, :year_level)";
                $params[':year_level'] = implode(",", $yearLevelArray);
            }

            $studentStmt = $pdo->prepare($studentQuery);
            $bindIndex = 1;

            foreach ($params as $key => $value) {
                $studentStmt->bindValue($key, $value);
            }

            $studentStmt->execute();
            $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($students) === 0) {
                throw new PDOException("No students match the given criteria.");
            }

            // 3. Insert records into student_payments for each matching student
            $paymentStmt = $pdo->prepare("INSERT INTO student_payments 
                (student_number, fee_for, amount, event_date_start, event_date_end, due_date, status, created_at) 
                VALUES 
                (:student_number, :fee_for, :amount, :event_date_start, :event_date_end, :due_date, 'unpaid', NOW())");

            foreach ($students as $student) {
                $paymentStmt->bindParam(':student_number', $student['student_number']);
                $paymentStmt->bindParam(':fee_for', $feeFor);
                $paymentStmt->bindParam(':amount', $amount);
                $paymentStmt->bindParam(':event_date_start', $eventStart);
                $paymentStmt->bindParam(':event_date_end', $eventEnd);
                $paymentStmt->bindParam(':due_date', $deadline);
                $paymentStmt->execute();
            }

            $pdo->commit(); // Commit transaction if everything is successful

            // Display success message and redirect
            // $_SESSION['add_success_message'] = "Fee successfully added to matching students!";
            $_SESSION['add_success_message'] = "New fee created successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack(); // Rollback transaction on error
            echo "<script>
                alert('Error: " . $e->getMessage() . "');
                window.history.back();
            </script>";
        }
    } elseif (isset($_POST['original_fee_for'])) {
        $originalFeeFor = $_POST['original_fee_for'];
        $feeFor = $_POST['fee_for'];
        $amount = $_POST['amount'];
        $eventStart = $_POST['event_start'];
        $eventEnd = $_POST['due_date'];//event_end
        $dueDate = $_POST['due_date'];
    
        // Include new fields
        $studentType = $_POST['student_type'] ?? '';
        $tuitionType = $_POST['tuition_type'] ?? '';
        $course = $_POST['course'] ?? '';
        $yearLevel = $_POST['year_level'] ?? '';
    
        try {
            $pdo->beginTransaction();
    
            // Update created_payments
            $stmt = $pdo->prepare("UPDATE created_payments 
                SET fee_for = :fee_for, amount = :amount, event_date_start = :event_start, 
                    event_date_end = :event_end, due_date = :due_date, 
                    student_type = :student_type, tuition_type = :tuition_type, 
                    course = :course, year_level = :year_level, updated_at = NOW() 
                WHERE fee_for = :original_fee_for");
    
            $stmt->bindParam(':fee_for', $feeFor);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':event_start', $eventStart);
            $stmt->bindParam(':event_end', $eventEnd);
            $stmt->bindParam(':due_date', $dueDate);
            $stmt->bindParam(':student_type', $studentType);
            $stmt->bindParam(':tuition_type', $tuitionType);
            $stmt->bindParam(':course', $course);
            $stmt->bindParam(':year_level', $yearLevel);
            $stmt->bindParam(':original_fee_for', $originalFeeFor);
    
            $stmt->execute();
    
            // Delete matching records in student_payments
            $deleteStudentPayments = $pdo->prepare("DELETE FROM student_payments WHERE fee_for = :fee_for");
            $deleteStudentPayments->bindParam(':fee_for', $originalFeeFor);
            $deleteStudentPayments->execute();
    
            // Retrieve matching student numbers from student_accounts
            $studentQuery = "SELECT student_number FROM student_accounts WHERE 1=1";
    
            if ($studentType !== 'All') {
                $studentQuery .= " AND student_type = :student_type";
            }
            if ($tuitionType !== 'All') {
                $studentQuery .= " AND tuition_type = :tuition_type";
            }
            if ($course !== 'All') {
                $studentQuery .= " AND course = :course";
            }
            if ($yearLevel !== 'All') {
                $studentQuery .= " AND year_level = :year_level";
            }
    
            $studentStmt = $pdo->prepare($studentQuery);
    
            if ($studentType !== 'All') {
                $studentStmt->bindParam(':student_type', $studentType);
            }
            if ($tuitionType !== 'All') {
                $studentStmt->bindParam(':tuition_type', $tuitionType);
            }
            if ($course !== 'All') {
                $studentStmt->bindParam(':course', $course);
            }
            if ($yearLevel !== 'All') {
                $studentStmt->bindParam(':year_level', $yearLevel);
            }
    
            $studentStmt->execute();
            $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);
    
            $paymentStmt = $pdo->prepare("INSERT INTO student_payments 
                (student_number, fee_for, amount, event_date_start, event_date_end, due_date, status, created_at) 
                VALUES 
                (:student_number, :fee_for, :amount, :event_date_start, :event_date_end, :due_date, 'unpaid', NOW())");
    
            foreach ($students as $student) {
                $paymentStmt->bindValue(':student_number', $student['student_number']);
                $paymentStmt->bindValue(':fee_for', $feeFor);
                $paymentStmt->bindValue(':amount', $amount);
                $paymentStmt->bindParam(':event_date_start', $eventStart);
                $paymentStmt->bindParam(':event_date_end', $eventEnd);
                $paymentStmt->bindValue(':due_date', $dueDate);
                $paymentStmt->execute();
            }
    
            $pdo->commit();
    
            $_SESSION['update_success_message'] = "Fee successfully updated!";
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
                successMessage.innerText = '" . addslashes($_SESSION['update_success_message']) . "';
                document.body.appendChild(successMessage);
    
                setTimeout(function() {
                    window.location.href = 'add_controller.php';
                }, 1000);
            </script>";
    
            unset($_SESSION['update_success_message']);
    
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error during update: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }
    }    
}

// Handle the deletion of a fee record
if (isset($_GET['delete_fee_for'])) {
    $feeFor = $_GET['delete_fee_for'];

    try {
        $pdo->beginTransaction(); // Start a transaction for consistency

        // 1. Delete records from the student_payments table associated with the fee
        $deleteStudentPayments = $pdo->prepare("DELETE FROM student_payments WHERE fee_for = :fee_for");
        $deleteStudentPayments->bindParam(':fee_for', $feeFor);
        $deleteStudentPayments->execute();

        // 2. Delete the fee record from the created_payments table
        $deleteFee = $pdo->prepare("DELETE FROM created_payments WHERE fee_for = :fee_for");
        $deleteFee->bindParam(':fee_for', $feeFor);
        $deleteFee->execute();

        $pdo->commit(); // Commit the transaction

        $_SESSION['delete_success_message'] = "Fee successfully deleted!";
        // Redirect after deleting the fee
        if (isset($_SESSION['delete_success_message'])) {
            echo "<script>
                // Create a div to show the success message
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
                successMessage.innerText = '" . addslashes($_SESSION['delete_success_message']) . "';
                document.body.appendChild(successMessage);
    
                // Redirect after 2 seconds
                setTimeout(function() {
                    window.location.href = 'add_controller.php'; 
                }, 500);  // 2000 milliseconds = 2 seconds
            </script>";
    
            unset($_SESSION['add_success_message']); // Clear the message after displaying it
        }
    } catch (PDOException $e) {
        $pdo->rollBack(); // Rollback the transaction on error
        echo "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Fees</title>
    <link rel="stylesheet" href="../css/content/addfees.css">
</head>
<body>
    <div class="add-fees-con">
        <div class="add-fees-header">
            <h2>Student Payments</h2><!-- New Fee/s -->
            <div id="add-fees-button" class="button-header">
                <button id="openModalButton" style="margin-right: 10px;" class="add-Btn">
                    <div class="sign"><img src="../imgs/plus-g.png" alt="Add" width="26" height="26" style="margin-right: 5px;"></div>
                    <div class="text">Add Fees</div>
                </button>
            </div>
        </div>

        <!-- Display the List of Prelim, Midterm, Prefinal and Final Fees -->
        <label for="fees-d">Tuition Fee</label>
        <div class="view-tuition-table">
            <table class="table" id="tuition-table">
                <thead>
                    <tr>
                        <th>Fee For</th>
                        <!-- <th>Amount</th> -->
                        <th>Event Dates</th>
                        <th>Due Date</th><!--Deadline-->
                        <th style="width: 130px;">Action</th>
                    </tr>
                </thead>
                <tbody id="tuition-table-body">
                    <?php if (!empty($tuition_fees)): ?>
                        <?php foreach ($tuition_fees as $tuition_fee): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tuition_fee['fee_for']); ?></td>
                                <!-- <td>₱<?php echo number_format($tuition_fee['amount'], 2); ?></td> -->
                                <td><?php echo htmlspecialchars($tuition_fee['formatted_event_date_start']); ?> - <?php echo htmlspecialchars($tuition_fee['formatted_event_date_end']); ?></td>
                                <td><?php echo htmlspecialchars($tuition_fee['formatted_due_date']); ?></td>
                                <td>
                                    <button class="edit-tuition-btn" 
                                        data-fee-for="<?php echo htmlspecialchars($tuition_fee['fee_for']); ?>"
                                        data-event-start="<?php echo $tuition_fee['event_date_start']; ?>"
                                        data-event-end="<?php echo $tuition_fee['event_date_end']; ?>"
                                        data-due-date="<?php echo $tuition_fee['due_date']; ?>">
                                        <!-- <img src="../../img/edit-fee.png" alt="Edit" width=20px> -->
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No tuition fees available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <br>

        <!-- Display the List of Created Fees -->
        <label for="fees-d">List of Created Fees</label>
        <div class="view-fees-table">
            <table class="table" id="fees-table">
                <thead>
                    <tr>
                        <!-- <th>Student Type</th> -->
                        <!-- <th>Tuition Type</th> -->
                        <!-- <th>Course</th> -->
                        <!-- <th>Year Level</th> -->
                        <th>Fee For</th>
                        <th style="width: 120px;">Amount</th>
                        <th style="width: 230px;">Event Dates</th>
                        <th style="width: 150px;">Due Date</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="fees-table-body">
                    <?php if (!empty($fees)): ?>
                        <?php foreach ($fees as $fee): ?>
                            <tr>
                                <!-- <td style="text-transform: capitalize;"><?php echo htmlspecialchars($fee['student_type']); ?></td> -->
                                <!-- <td style="text-transform: capitalize;"><?php echo htmlspecialchars($fee['tuition_type']); ?></td> -->
                                <!-- <td><?php echo htmlspecialchars($fee['course']); ?></td> -->
                                <!-- <td><?php echo htmlspecialchars($fee['year_level']); ?></td> -->
                                <td style="text-transform: capitalize;"><?php echo htmlspecialchars($fee['fee_for']); ?></td>
                                <td>₱<?php echo number_format($fee['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($fee['formatted_event_date_start']); ?> - <?php echo htmlspecialchars($fee['formatted_event_date_end']); ?></td>
                                <td><?php echo htmlspecialchars($fee['formatted_due_date']); ?></td>
                                <td>
                                    <button class="view-btn"
                                        fetch-student-type="<?php echo htmlspecialchars($fee['student_type']); ?>"
                                        fetch-tuition-type="<?php echo htmlspecialchars($fee['tuition_type']); ?>"
                                        fetch-course="<?php echo htmlspecialchars($fee['course']); ?>"
                                        fetch-year-level="<?php echo htmlspecialchars($fee['year_level']); ?>"
                                        fetch-fee-for="<?php echo htmlspecialchars($fee['fee_for']); ?>"
                                        fetch-amountt="<?php echo $fee['amount']; ?>"
                                        fetch-event-start="<?php echo $fee['event_date_start']; ?>"
                                        fetch-event-end="<?php echo $fee['event_date_end']; ?>"
                                        fetch-due-date="<?php echo $fee['due_date']; ?>">
                                        View
                                    </button>
                                    <button class="edit-btn" 
                                        data-student-type="<?php echo htmlspecialchars($fee['student_type']); ?>"
                                        data-tuition-type="<?php echo htmlspecialchars($fee['tuition_type']); ?>"
                                        data-course="<?php echo htmlspecialchars($fee['course']); ?>"
                                        data-year-level="<?php echo htmlspecialchars($fee['year_level']); ?>"
                                        data-fee-for="<?php echo htmlspecialchars($fee['fee_for']); ?>"
                                        data-amount="<?php echo $fee['amount']; ?>"
                                        data-event-start="<?php echo $fee['event_date_start']; ?>"
                                        data-event-end="<?php echo $fee['event_date_end']; ?>"
                                        data-due-date="<?php echo $fee['due_date']; ?>">
                                        <!-- <img src="../../img/edit-fee.png" alt="Edit" width=20px> -->
                                        Edit
                                    </button>
                                    <!-- <a href="?delete_fee_for=<?php echo htmlspecialchars($fee['fee_for']); ?>" class="delete-btn">
                                        Delete
                                    </a> -->
                                    <button onclick="confirmDelete('<?= $fee['fee_for'] ?>')" class="delete-btn">Delete</button>
                                    
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No fees added yet</td>
                        </tr>
                    <?php endif; ?>
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
                        <li><a href="?page=<?= $page - 1 ?>" class="pagination-prev">Previous</a></li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li>
                            <a href="?page=<?= $i ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li><a href="?page=<?= $page + 1 ?>" class="pagination-next">Next</a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <?php
    if (isset($_SESSION['add_success_message'])) {
        echo "<script>
            // Create a div to show the success message
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
            successMessage.innerText = '" . addslashes($_SESSION['add_success_message']) . "';
            document.body.appendChild(successMessage);

            // Redirect after 2 seconds
            setTimeout(function() {
                window.location.href = 'add_controller.php'; 
            }, 1000);  // 2000 milliseconds = 2 seconds
        </script>";

        unset($_SESSION['add_success_message']); // Clear the message after displaying it
    }
    ?>
</body>
</html>
