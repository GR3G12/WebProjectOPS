<?php
$content = '../components/student_content.php';
include '../../admin/layouts/master.php';

include '../includes/update_account.php';

require '../../vendor/autoload.php'; // Add this line
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['submit'])) {
    $student_number = $_POST['student_number'];
    $password = $_POST['password'];
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $student_type = $_POST['student_type'];
    $tuition_type = $_POST['tuition_type'];
    $email = $_POST['email'];
    $course = $_POST['course'];
    $year_level = $_POST['year_level'];
    $section = $_POST['section'];
    $semester = $_POST['semester'];
    $total_tuition_fee = $_POST['total_tuition_fee'];
    $tuition_fee_discount = $_POST['tuition_fee_discount'];
    $down_payment = $_POST['down_payment'];

    // Calculate remaining balances
    $balance_to_be_paid = $total_tuition_fee - $tuition_fee_discount;
    $total_balance = $balance_to_be_paid - $down_payment;
    $remaining_balance_to_pay = $balance_to_be_paid - $down_payment;

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $role = 'student';

    // Check if student_number already exists
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM student_accounts WHERE student_number = ?");
    $stmt_check->execute([$student_number]);
    $studentExists = $stmt_check->fetchColumn();

    if ($studentExists) {
        echo "<script>
            alert('Error: Student number already exists.');
            window.history.back();
        </script>";
        exit(); // Stop script execution
    }
    
    // Handle Image Upload and Resize to Square
    $profile_image = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "../../uploads/"; // Folder to save images
        $image_name = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $image_name; // Unique filename
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowed_types)) {
            die("Error: Only JPG, JPEG, PNG, & GIF files are allowed.");
        }

        // Get image dimensions
        list($width, $height) = getimagesize($_FILES["profile_image"]["tmp_name"]);
        $new_size = min($width, $height); // Get the smallest dimension
        $square_size = 200; // Set desired square size

        // Create image resource based on type
        switch ($imageFileType) {
            case 'jpg':
            case 'jpeg':
                $src = imagecreatefromjpeg($_FILES["profile_image"]["tmp_name"]);
                break;
            case 'png':
                $src = imagecreatefrompng($_FILES["profile_image"]["tmp_name"]);
                break;
            case 'gif':
                $src = imagecreatefromgif($_FILES["profile_image"]["tmp_name"]);
                break;
            default:
                die("Error: Unsupported image type.");
        }

        // Create a new true color image
        $dst = imagecreatetruecolor($square_size, $square_size);

        // Crop & resize to square
        imagecopyresampled($dst, $src, 0, 0, ($width - $new_size) / 2, ($height - $new_size) / 2, $square_size, $square_size, $new_size, $new_size);

        // Save the new image
        switch ($imageFileType) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($dst, $target_file);
                break;
            case 'png':
                imagepng($dst, $target_file);
                break;
            case 'gif':
                imagegif($dst, $target_file);
                break;
        }

        // Free memory
        imagedestroy($src);
        imagedestroy($dst);

        $profile_image = $target_file; // Save file path in the database
    }


    try {
        // Start transaction
        $pdo->beginTransaction();

        // Insert data into the student_accounts table
        $stmt1 = $pdo->prepare("INSERT INTO student_accounts (profile_image, student_number, student_type, tuition_type, firstname, middlename, lastname, email, course, year_level, section, semester, total_tuition_fee, tuition_fee_discount, balance_to_be_paid, down_payment, total_balance, remaining_balance_to_pay) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt1->execute([$profile_image, $student_number, $student_type, $tuition_type, $firstname, $middlename, $lastname, $email, $course, $year_level, $section, $semester, $total_tuition_fee, $tuition_fee_discount, $balance_to_be_paid, $down_payment, $total_balance, $remaining_balance_to_pay]);

        // Insert student credentials into acts_ops_login table
        $stmt2 = $pdo->prepare("INSERT INTO acts_ops_login (profile_image, student_number, firstname, middlename, lastname, email, role, password) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt2->execute([$profile_image, $student_number, $firstname, $middlename, $lastname, $email, $role, $hashedPassword]);


        // Fetch fee dates from semester_fees (assuming there's a general record for these)
        $stmt = $pdo->prepare("SELECT `fee_for`, `event_date_start`, `event_date_end`, `due_date` FROM `semester_fees` WHERE `fee_for` IN ('Prelim', 'Midterm', 'Prefinal', 'Final') GROUP BY `fee_for`");
        $stmt->execute();
        $feeDates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Create an associative array for easy lookup of fee dates
        $feeDateLookup =[];
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
            $dividedAmount = $remainingBalance / count($unpaidFees); // Divide by the count of unpaid fees

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
                    ':fee_for' => $feeFor 
                ]);
            }
        }

        // Commit transaction
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
                <p>Your account has been created successfully. Here are your login details:</p>
                <p><strong>Student Number:</strong> {$student_number}</p>
                <p><strong>Password:</strong> {$password}</p>
                <p>Please change your password after logging in.</p>
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
            echo "<script>alert('Account created successfully, but email could not be sent. Please contact admin.');</script>";
        }

        // Redirect or show a success message
        $_SESSION['student_success_message'] = "Student added successfully!";
        
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
                }, 500);
            </script>";
        }
    } catch (Exception $e) {
        // Rollback transaction if an error occurs
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

require __DIR__ . '/../../vendor/autoload.php';  // Adjust the path as needed

// Include the PhpSpreadsheet library
use PhpOffice\PhpSpreadsheet\IOFactory;

// Handle the import logic separately
if (isset($_FILES['import_excel']) && $_FILES['import_excel']['error'] == 0) {
    // Load the uploaded Excel file
    $filePath = $_FILES['import_excel']['tmp_name'];
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    
    $rowIndex = 0; // Row index starts from 0 for the first row
    
    // Loop through the rows and insert student data from the file
    foreach ($sheet->getRowIterator() as $row) {
        $rowIndex++;
        
        // Skip the first row (title row)
        if ($rowIndex <= 4) {
            continue; // Skip the first row
        }

        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false); // Iterate through all cells
        
        $data = [];
        foreach ($cellIterator as $cell) {
            $data[] = $cell->getFormattedValue();
        }

        // Extract data from the Excel row and insert into the database
        $student_number = $data[0];
        $password = $data[1];
        $firstname = $data[2];
        $middlename = $data[3];
        $lastname = $data[4];
        $student_type = $data[5];
        $tuition_type = $data[6];
        $email = $data[7];
        $course = $data[8];
        $year_level = $data[9];
        $section = $data[10];
        $semester = $data[11];
        $total_tuition_fee = (float) $data[12];
        $tuition_fee_discount = (float) $data[13];
        $down_payment = (float) $data[14];
        $profile_image_url = $data[15] ?? null; // Image column

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
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
            // Skip this row if student number already exists
            continue;
        }
        
        try {
            // Start transaction for each import
            $pdo->beginTransaction();

            // Insert into student_accounts table
            $stmt1 = $pdo->prepare("INSERT INTO student_accounts (profile_image, student_number, student_type, tuition_type, firstname, middlename, lastname, course, year_level, section, semester, total_tuition_fee, tuition_fee_discount, balance_to_be_paid, down_payment, total_balance, remaining_balance_to_pay) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $balance_to_be_paid = $total_tuition_fee - $tuition_fee_discount;
            $total_balance = $balance_to_be_paid - $down_payment;
            $remaining_balance_to_pay = $total_balance;

            $stmt1->execute([$profile_image, $student_number, $student_type, $tuition_type, $firstname, $middlename, $lastname, $course, $year_level, $section, $semester, $total_tuition_fee, $tuition_fee_discount, $balance_to_be_paid, $down_payment, $total_balance, $remaining_balance_to_pay]);

            // Insert student credentials into acts_ops_login table
            $stmt2 = $pdo->prepare("INSERT INTO acts_ops_login (profile_image, student_number, firstname, middlename, lastname, email, role, password) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt2->execute([$profile_image, $student_number, $firstname, $middlename, $lastname, $email, $role, $hashedPassword]);

            // Fetch fee dates from semester_fees (assuming there's a general record for these)
            $stmt = $pdo->prepare("SELECT `fee_for`, `event_date_start`, `event_date_end`, `due_date` FROM `semester_fees` WHERE `fee_for` IN ('Prelim', 'Midterm', 'Prefinal', 'Final') GROUP BY `fee_for`");
            $stmt->execute();
            $feeDates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Create an associative array for easy lookup of fee dates
            $feeDateLookup =[];
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
                $dividedAmount = $remainingBalance / count($unpaidFees); // Divide by the count of unpaid fees

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
                        ':fee_for' => $feeFor 
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
                    <p>Your account has been created successfully. Here are your login details:</p>
                    <p><strong>Student Number:</strong> {$student_number}</p>
                    <p><strong>Password:</strong> {$password}</p>
                    <p>Please change your password after logging in.</p>
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
                echo "<script>alert('Account created successfully, but email could not be sent. Please contact admin.');</script>";
            }

            // Redirect or show a success message
            $_SESSION['student_success_message'] = "Student added successfully!";
            
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
                    }, 500);
                </script>";
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
    <div id="studentModal" class="add-student-modal">
        <div class="add-student-modal-content">
            <span class="close" id="closeModalButton">&times;</span>
            <div class="modal-header">
                <h2>Add Student</h2>

                <div class="modal-header-btn">
                    <button id="importFileButton" class="upload-Btn">
                        <div class="sign"><img src="../../img/upload-Icon.png" alt="Add" width="26" height="26" style="margin-right: 5px;"></div>
                        <div class="text">Import File</div>
                    </button>
                    <button id="downloadTemplateButton" class="Btn">
                        <div class="sign"><img src="../../img/download-Icon.png" alt="Add" width="26" height="26" style="margin-right: 5px;"></div>
                        <div class="text">Download Template</div>
                    </button>
                </div>
            </div>

            <!-- Import Form for Excel File -->
            <div id="importForm" class="import-form" style="display: none;">
                <h3>Import Student Data</h3>
                <form action="student_controller.php" method="POST" enctype="multipart/form-data">
                    <label for="import_excel">Upload Excel File:</label>
                    <input type="file" name="import_excel" accept=".xls, .xlsx" required>
                    <button type="submit" name="submit_import" class="import-button">Import</button>
                </form>
            </div>

            <div id="addStudentForm" class="add-student-form">
                <form action="student_controller.php" method="POST" enctype="multipart/form-data"><!--../components/ -->
                    <div class="row1">
                        <div class="row-image">
                            <div class="image-con">
                                <img id="imagePreview" src="" alt="Image Preview" style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                            </div>
                            <div class="image-details">
                                <label for="profile_image">Profile Image:</label><br>
                                <input type="file" name="profile_image" accept="image/*" onchange="previewImage(event)"><br>
                            </div>
                        </div>
                    </div>

                    <div class="row1">
                        <div class="row11">
                            <label for="student_number">Student Number</label><br>
                            <input type="number" id="student_number" name="student_number" required><br>
                        </div>
                        <div class="row11-p">
                            <label for="password">Password</label><br>
                            <input type="password" id="password" name="password" required><br>
                        </div>
                    </div>

                    <div class="row2">
                        <div class="row22">
                            <label for="firstname">First Name</label><br>
                            <input type="text" id="firstname" name="firstname" required><br>
                        </div>
                        <div class="row22">
                            <label for="middlename">Middle Name</label><br>
                            <input type="text" id="middlename" name="middlename" required><br>
                        </div>
                        <div class="row22">
                            <label for="lastname">Last Name</label><br>
                            <input type="text" id="lastname" name="lastname" required><br>
                        </div>
                    </div>

                    <div class="row1">
                        <div class="row33">
                            <label for="student_type">Student Type</label><br>
                            <select name="student_type" id="student_type" required>
                                <option value="" disabled selected>--Select Type--</option>
                                <option value="Regular">Regular</option>
                                <option value="Irregular">Irregular</option>
                            </select><br>
                        </div>
                        <div class="row33">
                            <label for="tuition_type">Tuition Type</label><br>
                            <select name="tuition_type" id="tuition_type" required>
                                <option value="" disabled selected>--Select Tuition--</option>
                                <option value="scholar">ASAP Scholar</option>
                                <option value="gov_scholar">Government Scholar</option>
                                <option value="free_tuition">Free</option>
                            </select><br>
                        </div>
                        <div class="row33">
                            <label for="email">Email *</label><br>
                            <input type="email" id="email" name="email" required><br>
                        </div>
                    </div>

                    <div class="row3">
                        <div class="row33">
                            <label for="course">Course *</label><br>
                            <select name="course" id="course" required>
                                <option value="" disabled selected>--Select Course--</option>
                                <option value="BSIT">BSIT</option>
                                <option value="BSCS">BSCS</option>
                                <option value="BSCE">BSCE</option>
                            </select><br>
                        </div>
                        <div class="row33">
                            <label for="year_level">Year Level</label><br>
                            <select name="year_level" id="year_level" required>
                                <option value="" disabled selected>--Select Year--</option>
                                <option value="1">First Year</option>
                                <option value="2">Second Year</option>
                                <option value="3">Third Year</option>
                                <option value="4">Fourth Year</option>
                            </select><br>
                        </div>
                        <div class="row33">
                            <label for="section">Section</label><br>
                            <select name="section" id="section" required>
                                <option value="" disabled selected>--Select Section--</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                            </select><br>
                        </div>
                        <div class="row33">
                            <label for="semester">Semester</label><br>
                            <select name="semester" id="semester" required>
                                <option value="" disabled selected>--Select Semester--</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                            </select><br>
                        </div>
                    </div>

                    <div class="row4">
                        <div class="row44">
                            <label for="total_tuition_fee">Total Tuition Fee</label><br>
                            <input type="number" id="total_tuition_fee" name="total_tuition_fee" required><br>
                        </div>
                        <div class="row44">
                            <label for="tuition_fee_discount">Tuition Fee Discount</label><br>
                            <input type="number" id="tuition_fee_discount" name="tuition_fee_discount" required><br>
                        </div>
                        <div class="row44">
                            <label for="down_payment">Down Payment</label><br>
                            <input type="number" id="down_payment" name="down_payment" required><br>
                        </div>
                    </div>

                    <button type="submit" name="submit" class="sub-button">Submit</button>
                </form>
            </div>
        </div>
    </div>

        <script>
            // Modal toggle
            var modal = document.getElementById("studentModal");
            var openModalButton = document.getElementById("openModalButton");
            var closeModalButton = document.getElementById("closeModalButton");

            openModalButton.onclick = function() {
                modal.style.display = "block";
            }

            closeModalButton.onclick = function() {
                modal.style.display = "none";
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        </script>

        <script>
            function previewImage(event) {
                var input = event.target;
                var reader = new FileReader();
                
                reader.onload = function() {
                    var imagePreview = document.getElementById("imagePreview");
                    imagePreview.src = reader.result;
                    imagePreview.style.display = "block"; // Show the image preview
                };
                
                reader.readAsDataURL(input.files[0]); // Read the selected file
            }
        </script>

        <!-- import file button toggle -->
        <script>
            document.getElementById("importFileButton").addEventListener("click", function () {
                var importForm = document.getElementById("importForm");
                var addStudentForm = document.getElementById("addStudentForm");

                // Toggle the import form visibility
                if (importForm.style.display === "none") {
                    importForm.style.display = "block"; // Show import form
                    addStudentForm.style.display = "none"; // Hide add student form
                } else {
                    importForm.style.display = "none"; // Hide import form
                    addStudentForm.style.display = "block"; // Show add student form
                }
            });
        </script>

        <script>
            document.getElementById("downloadTemplateButton").addEventListener("click", function() {
                // window.location.href = "../includes/download_template.php"; 
                window.location.href = "../includes/import_template.xlsx";
            });
        </script>


    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="delete-modal">
        <div class="delete-modal-content">
            <div class="par">
                <p>Are you sure you want to delete this account?</p>
            </div>
            <form id="deleteForm" method="GET" action="">
                <input type="hidden" name="delete_student_number" id="deleteStudentNumberInput">
                <button type="submit" class="yes-button">Yes</button>
                <!-- <button type="button" onclick="closeModal()" class="no-button">No</button> -->
                <button type="button" onclick="closeModal()" class="no-button">No</button>
            </form>
        </div>
    </div>
<script>
    function closeModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
</script>