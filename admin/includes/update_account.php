<?php

require '../../vendor/autoload.php'; // Add this line
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';  // Adjust the path as needed

// Include the PhpSpreadsheet library
use PhpOffice\PhpSpreadsheet\IOFactory;

// Handle the import logic separately
if (isset($_FILES['import_update_excel']) && $_FILES['import_update_excel']['error'] == 0) {
    // Load the uploaded Excel file
    $filePath = $_FILES['import_update_excel']['tmp_name'];
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    $rowIndex = 0; // Row index starts from 0 for the first row

    // Loop through the rows and insert student data from the file
    foreach ($sheet->getRowIterator() as $row) {
        $rowIndex++;

        // Skip the first few rows (title rows)
        if ($rowIndex <= 4) {
            continue; // Skip the first few rows
        }

        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false); // Iterate through all cells

        $data = [];
        foreach ($cellIterator as $cell) {
            $data[] = $cell->getFormattedValue();
        }

        // Extract data from the Excel row
        $student_number = $data[0];
        $firstname = $data[1];
        $middlename = $data[2];
        $lastname = $data[3];
        $student_type = $data[4];
        $tuition_type = $data[5];
        $email = $data[6];
        $course = $data[7];
        $year_level = $data[8];
        $section = $data[9];
        $semester = $data[10];
        $total_tuition_fee = (float) $data[11];
        $tuition_fee_discount = (float) $data[12];
        $down_payment = (float) $data[13];
        $profile_image_url = $data[14] ?? null; // Image column

        $role = 'student';

        // Process image if provided
        $profile_image = null;
        if (!empty($profile_image_url)) {
            $target_dir = "../../uploads/";
            $image_name = time() . "_" . basename($profile_image_url);
            $target_file = $target_dir . $image_name;

            // If it's a URL, download the image
            if (filter_var($profile_image_url, FILTER_VALIDATE_URL)) {
                file_put_contents($target_file, file_get_contents($profile_image_url));
                $profile_image = $target_file;
            } else {
                // If it's just a filename, assume it's in the uploads folder
                $local_path = $target_dir . $profile_image_url;
                if (file_exists($local_path)) {
                    $profile_image = $local_path;
                }
            }
        }

        // Check if student number already exists
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM student_accounts WHERE student_number = ?");
        $stmtCheck->execute([$student_number]);
        $exists = $stmtCheck->fetchColumn();

        if ($exists > 0) {
            // Student number already exists, show an alert and skip this row
            echo "<script>alert('Student number {$student_number} already exists in the database.');</script>";
            continue;
        }

        try {
            // Start transaction for each import
            $pdo->beginTransaction();

            // Insert into student_accounts table
            $stmt1 = $pdo->prepare("INSERT INTO student_accounts (profile_image, student_number, student_type, tuition_type, firstname, middlename, lastname, email, course, year_level, section, semester, total_tuition_fee, tuition_fee_discount, balance_to_be_paid, down_payment, total_balance, remaining_balance_to_pay)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $balance_to_be_paid = $total_tuition_fee - $tuition_fee_discount;
            $total_balance = $balance_to_be_paid - $down_payment;
            $remaining_balance_to_pay = $total_balance;

            $stmt1->execute([$profile_image, $student_number, $student_type, $tuition_type, $firstname, $middlename, $lastname, $email, $course, $year_level, $section, $semester, $total_tuition_fee, $tuition_fee_discount, $balance_to_be_paid, $down_payment, $total_balance, $remaining_balance_to_pay]);

            // Fetch fee dates from semester_fees (assuming there's a general record for these)
            $stmt = $pdo->prepare("SELECT `fee_for`, `event_date_start`, `event_date_end`, `due_date` FROM `semester_fees` WHERE `fee_for` IN ('Prelim', 'Midterm', 'Prefinal', 'Final') GROUP BY `fee_for`");
            $stmt->execute();
            $feeDates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Create an associative array for easy lookup of fee dates
            $feeDateLookup = [];
            foreach ($feeDates as $feeDate) {
                $feeDateLookup[$feeDate['fee_for']] = $feeDate;
            }

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
                    $insertQuery = "INSERT INTO semester_fees (student_number, fee_for, amount, status, event_date_start, event_date_end, due_date)
                                        VALUES (:student_number, :fee_for, 0, 'Unpaid', :event_date_start, :event_date_end, :due_date)";
                    $insertStmt = $pdo->prepare($insertQuery);

                    // Get the fee's event dates if available
                    $eventStartDate = isset($feeDateLookup[$fee]['event_date_start']) ? $feeDateLookup[$fee]['event_date_start'] : null;
                    $eventEndDate = isset($feeDateLookup[$fee]['event_date_end']) ? $feeDateLookup[$fee]['event_date_end'] : null;
                    $dueDate = isset($feeDateLookup[$fee]['due_date']) ? $feeDateLookup[$fee]['due_date'] : null;

                    $insertStmt->execute([
                        ':student_number' => $student_number,
                        ':fee_for' => $fee,
                        ':event_date_start' => $eventStartDate,
                        ':event_date_end' => $eventEndDate,
                        ':due_date' => $dueDate,
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
            if ($remaining_balance_to_pay > 0 && count($unpaidFees) > 0) {
                $remainingBalance = $remaining_balance_to_pay;

                // Calculate the divided amount for the remaining unpaid fees
                $dividedAmount = round($remainingBalance / count($unpaidFees), 2); // Divide by the count of unpaid fees and round to 2 decimal places

                // Update each unpaid fee with the divided amount
                foreach ($unpaidFees as $feeFor) {
                    // Only update the amount of unpaid fees
                    $updateQuery = "UPDATE semester_fees
                                        SET amount = :amount, firstname = :firstname, lastname = :lastname
                                        WHERE student_number = :student_number
                                        AND fee_for = :fee_for
                                        AND status != 'Paid'";
                    $updateStmt = $pdo->prepare($updateQuery);
                    $updateStmt->execute([
                        ':amount' => $dividedAmount,
                        ':student_number' => $student_number,
                        ':firstname' => $firstname,
                        ':lastname' => $lastname,
                        ':fee_for' => $feeFor,
                    ]);
                }
            }

            // Commit the transaction
            $pdo->commit();

            $mail = new PHPMailer(true); // Enable exceptions

            try {
                //Server settings
                $mail->SMTPDebug = 0; // Disable verbose debug output (set to 2 for debugging)
                $mail->isSMTP(); // Send using SMTP
                $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
                $mail->SMTPAuth = true; // Enable SMTP authentication
                $mail->Username = 'gregoriollagas12@gmail.com'; // Replace with your SMTP username
                $mail->Password = 'bsxonsalysvuivzw'; // Replace with your SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
                $mail->Port = 587; // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

                //Recipients
                $mail->setFrom('gregoriollagas12@gmail.com', 'ACTS-OPS'); // Replace with your sending email and name
                $mail->addAddress($email, $firstname . ' ' . $lastname); // Add recipient

                //Content
                $mail->isHTML(true); // Set email format to HTML
                $mail->Subject = 'Account Created';
                $mail->Body = "
                    <p>Dear {$firstname} {$lastname},</p>
                    <p>Your account has been updated successfully. Please login now to see updates:</p>
                    <p><strong>Student Number:</strong> {$student_number}</p>
                    <p>Here are your account details:</p>
                    <p>Firstname: {$firstname}</p>
                    <p>Middlename: {$middlename}</p>
                    <p>Lastname: {$lastname}</p>
                    <p>Email: {$email}</p>
                    <p>Course: {$course}</p>
                    <p>Year Level: {$year_level}</p>
                    <p>Section: {$section}</p>
                    <p>Semester: {$semester}</p>
                    <p>Total Tuition Fee: {$total_tuition_fee}</p>
                    <p>Tuition Fee Discount: {$tuition_fee_discount}</p>
                    <p>Down Payment: {$down_payment}</p>
                    <p>Balance to be Paid: {$balance_to_be_paid}</p>
                    <p>Total Balance: {$total_balance}</p>
                    <p>Remaining Balance: {$remaining_balance_to_pay}</p>
                    <p>Thank you!</p>

                    <p>You can access your account and log in through our student portal at: <a href=\"http://actsccops.com\">actsccops.com</a></p>
                    <p>We recommend bookmarking this link for easy access.</p>
                ";

                $mail->send();
                // Optional: Log success
                error_log("Email sent to: " . $email);

            } catch (PHPMailer\PHPMailer\Exception $e) {
                // Optional: Log the error
                error_log("PHPMailer Error: " . $mail->ErrorInfo);
                echo "<script>alert('Account updated successfully, but email could not be sent. Please contact admin.');</script>";
            }

            // Redirect or show a success message
            $_SESSION['student_success_message'] = "Student updated successfully!";

            if (isset($_SESSION['student_success_message'])) {
                $message = $_SESSION['student_success_message'];
                unset($_SESSION['student_success_message']); // Unset before redirecting

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
                    successMessage.innerText = '" . addslashes($message) . "';
                    document.body.appendChild(successMessage);

                    setTimeout(function() {
                        window.location.href = '../controllers/student_controller.php';
                    }, 1500); // Adjust time as needed for the message to be visible
                </script>";
                // exit();
            }
        } catch (Exception $e) {
            // Rollback if any error occurs
            $pdo->rollBack();
            die("Error: " . $e->getMessage());
        }
    }
}
?>
<!-- Modal for Adding Student -->
    <div id="studentUpdateModal" class="update-student-modal">
        <div class="update-student-modal-content">
            <span class="close" id="closeUpdateModalButton">&times;</span>
            <div class="modal-header">
                <h2>Update Student</h2>

                <div class="modal-header-btn">
                    <button id="downloadUpdateTemplate" class="Btn">
                        <div class="sign"><img src="../../img/download-Icon.png" alt="Add" width="26" height="26" style="margin-right: 5px;"></div>
                        <div class="text">Download Template</div>
                    </button>
                </div>
            </div>

            <!-- Import Form for Excel File -->
            <div id="importUpdateForm" class="import-form"">
                <h3>Import Student Data</h3>
                <form action="student_controller.php" method="POST" enctype="multipart/form-data">
                    <label for="import_update_excel">Upload Excel File:</label>
                    <input type="file" name="import_update_excel" accept=".xls, .xlsx" required>
                    <button name="submit_update_import" class="import-button">Import</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- import file button toggle -->
    <script>
        document.getElementById("importFileButton").addEventListener("click", function () {
            var importUpdateForm = document.getElementById("importUpdateForm");
            var addStudentForm = document.getElementById("addStudentForm");

            // Toggle the import form visibility
            if (importUpdateForm.style.display === "none") {
                importUpdateForm.style.display = "block"; // Show import form
                addStudentForm.style.display = "none"; // Hide add student form
            } else {
                importUpdateForm.style.display = "none"; // Hide import form
                addStudentForm.style.display = "block"; // Show add student form
            }
        });
    </script>
    
    <script>
        document.getElementById("downloadUpdateTemplate").addEventListener("click", function() {
            // window.location.href = "../includes/download_template.php"; 
            window.location.href = "../includes/import_update_template.xlsx";
        });
    </script>
    
    <script>
        // Modal toggle
        var updateModal = document.getElementById("studentUpdateModal");
        var openUpdateModalButton = document.getElementById("openUpdateModalButton");
        var closeUpdateModalButton = document.getElementById("closeUpdateModalButton");

        openUpdateModalButton.onclick = function() {
            updateModal.style.display = "block";
        }

        closeUpdateModalButton.onclick = function() {
            updateModal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == updateModal) {
                updateModal.style.display = "none";
            }
        }
    </script>