<?php
session_start();
include('../../database/db.php'); // Include your database connection

// Send email using PHPMailer
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Retrieve paid items from session
$paidItems = isset($_SESSION['paid_items']) ? $_SESSION['paid_items'] : [];
$student_number = isset($_SESSION['student_number']) ? $_SESSION['student_number'] : null; // Get student number
$checkoutSessionId = isset($_SESSION['checkout_session_id']) ? $_SESSION['checkout_session_id'] : null;

$secretKey = "put your own secretkey"; 

// Initialize customer details
$customerName = '';
$customerEmail = '';
$totalAmount = 0; // Initialize total amount

// Debugging: Inspect session data (optional)
// echo "<pre>Session Data: ";
// print_r($_SESSION);
// echo "</pre>";

if (!empty($paidItems) && $student_number !== null && $checkoutSessionId !== null) {
    try {
        // Retrieve payment details from PayMongo API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.paymongo.com/v1/checkout_sessions/" . $checkoutSessionId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Basic " . base64_encode($secretKey . ":")
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);

        // Debug: Print the raw response to see its structure
        // echo "<pre>";
        // print_r($response);
        // echo "</pre>";

        if (isset($response['data']['attributes']['payments'][0]['id'])) {
            $paymentReference = $response['data']['attributes']['payments'][0]['id'];
        } else {
            throw new Exception("Payment ID not found in PayMongo response.");
        }

        // Retrieve customer details from PayMongo response (corrected location)
        if (isset($response['data']['attributes']['payments'][0]['attributes']['billing']['name'])) {
            $customerName = $response['data']['attributes']['payments'][0]['attributes']['billing']['name'];
        }
        if (isset($response['data']['attributes']['payments'][0]['attributes']['billing']['email'])) {
            $customerEmail = $response['data']['attributes']['payments'][0]['attributes']['billing']['email'];
        }

        // Debug: Check the values of $customerName and $customerEmail
        // echo "Customer Name: " . $customerName . "<br>";
        // echo "Customer Email: " . $customerEmail . "<br>";

        // Start a transaction for atomicity
        $pdo->beginTransaction();

        $paymentDate = date('Y-m-d H:i:s'); // Get current date and time
        $paymentMethod = 'Online'; // Set payment method to Online

        foreach ($paidItems as $item) {
            $feeName = $item['name'];
            $totalAmount += $item['amount']; // Accumulate total amount

            // Debugging: Inspect feeName (optional)
            // echo "Fee Name: " . $feeName . "<br>";

            // Update the status in the semester_fees table
            $updateSemesterFeesQuery = "UPDATE semester_fees SET status = 'Pending', payment_date = :payment_date, payment_method = :payment_method, reference = :reference WHERE student_number = :student_number AND fee_for = :fee_for";
            $updateSemesterFeesStmt = $pdo->prepare($updateSemesterFeesQuery);
            $updateSemesterFeesStmt->execute([
                ':student_number' => $student_number,
                ':fee_for' => $feeName,
                ':payment_date' => $paymentDate,
                ':payment_method' => $paymentMethod,
                ':reference' => $paymentReference,
            ]);
            // $updateSemesterFeesStmt->debugDumpParams(); // Debugging

            // Update the status in the student_payments table
            $updateStudentPaymentsQuery = "UPDATE student_payments SET status = 'Pending', payment_date = :payment_date, payment_method = :payment_method, reference = :reference WHERE student_number = :student_number AND fee_for = :fee_for";
            $updateStudentPaymentsStmt = $pdo->prepare($updateStudentPaymentsQuery);
            $updateStudentPaymentsStmt->execute([
                ':student_number' => $student_number,
                ':fee_for' => $feeName,
                ':payment_date' => $paymentDate,
                ':payment_method' => $paymentMethod,
                ':reference' => $paymentReference,
            ]);
            // $updateStudentPaymentsStmt->debugDumpParams(); // Debugging
        }

        // Commit the transaction
        $pdo->commit();

        // Clear the session data after successful processing
        unset($_SESSION['paid_items']);
        unset($_SESSION['checkout_session_id']);

        // Success message or redirect
        $successMessage = "Payment successfully processed. Kindly proceed to the cashier to get your receipt.";

        // --- PHPMailer Code ---
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'gregoriollagas12@gmail.com'; // Replace with your SMTP username  //actsccops1987@gmail.com
            $mail->Password = 'bsxonsalysvuivzw'; // Replace with your SMTP password  //yoccmfeycfxfcwxc
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('gregoriollagas12@gmail.com', 'ACTS-OPS');
            $mail->addAddress($customerEmail, $customerName); // Add payer's email

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Payment Confirmation - ACTS Computer College';
            $mail->Body = "
                <p>Dear {$customerName},</p>
                <p>We have received your payment of ₱ " . number_format($totalAmount / 100, 2) . ".</p>
                <p>Your transaction has been successfully processed.</p>

                <p><strong>Payment Details:</strong></p>
                <ul>
            ";
            foreach ($paidItems as $item) {
                $mail->Body .= "<li>" . htmlspecialchars($item['name']) . " - ₱ " . number_format($item['amount'] / 100, 2) . "</li>";
            }

            $mail->Body .= "
                </ul>

                <p><strong>Customer Details:</strong></p>
                <ul>
                    <li><strong>Customer Name:</strong> {$customerName}</li>
                    <li><strong>Reference:</strong> {$paymentReference}</li>
                    <li><strong>Payment Method:</strong> {$paymentMethod}</li>
                    <li><strong>Amount Paid:</strong> ₱ " . number_format($totalAmount / 100, 2) . "</li>
                    <li><strong>Payment Date:</strong> {$paymentDate}</li>
                </ul>

                <p>If you have any questions or need further assistance, please don't hesitate to contact the Cashier Office.</p>
                <p>Keep this email for your records. Thank you and God bless</p>
            ";

            $mail->send();
            error_log("Payment confirmation email sent to: " . $customerEmail);

        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            echo "<script>alert('Payment successful, but email could not be sent. Please contact admin.');</script>";
        }
        // --- End PHPMailer Code ---

    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();

        // Error handling
        $errorMessage = "An error occurred: " . $e->getMessage();
        // Log the error for debugging
        error_log("Payment Processing Error: " . $e->getMessage());
        $errorMessage = $errorMessage . " SQL Error detail: " . $e->errorInfo[2]; //Add SQL Error detail to message
    }
} else {
    $errorMessage = "Invalid payment data or student number.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f4f4f4;
            padding: 20px; /* Reduced padding for mobile */
        }
        .container {
            background: white;
            padding: 20px; /* Reduced padding for mobile */
            border-radius: 8px; /* Slightly smaller radius for mobile */
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
            display: inline-block;
            width: 90%; /* Adjust width for mobile */
            max-width: 500px; /* Ensure it doesn't get too wide on larger phones */
        }
        .icon {
            font-size: 40px; /* Reduced icon size for mobile */
            color: <?php echo isset($successMessage) ? '#28a745' : 'red'; ?>;
        }
        h2 {
            color: <?php echo isset($successMessage) ? '#28a745' : 'red'; ?>;
            font-size: 24px; /* Reduced heading size for mobile */
        }
        p {
            font-size: 16px; /* Reduced paragraph size for mobile */
            line-height: 1.4; /* Improved line spacing for readability */
        }
        .btn {
            background: #28a745;
            color: white;
            text-decoration: none;
            padding: 10px 15px; /* Slightly smaller button padding for mobile */
            border-radius: 5px;
            display: inline-block;
            margin-top: 15px; /* Reduced margin for mobile */
        }
        .btn:hover {
            background: #218838;
        }
        .item-list {
            text-align: left;
            margin-top: 15px; /* Reduced margin for mobile */
        }
        .item-list ul {
            list-style: none;
            padding: 0;
        }
        .item-list li {
            font-size: 14px; /* Reduced list item size for mobile */
            background: #f8f9fa;
            margin: 5px 0;
            padding: 8px; /* Reduced padding for mobile */
            border-radius: 5px;
        }

        /* Media query for mobile devices */
        @media (max-width: 400px), (max-width: 480px), (max-width: 768px) {
            body {
                padding: 10px; /* Further reduce padding on very small screens */
            }
            .container {
                width: 95%; /* Make container wider on smaller screens */
                padding: 15px; /* Further reduce padding on very small screens */
            }
            .icon {
                font-size: 30px; /* Even smaller icon on very small screens */
            }
            h2 {
                font-size: 20px; /* Even smaller heading on very small screens */
            }
            p {
                font-size: 15px; /* Even smaller paragraph on very small screens */
            }

            .itel-list h3{
                font-size: 15px; 
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon"><?php echo isset($successMessage) ? '✅' : '❌'; ?></div>
        <h2><?php echo isset($successMessage) ? 'Payment Successful' : 'Payment Failed'; ?></h2>
        <p>
            <?php if (isset($successMessage)): ?>
                <?php echo $successMessage; ?>
            <?php elseif (isset($errorMessage)): ?>
                <?php echo $errorMessage; ?>
            <?php else: ?>
                Thank you for your payment. Your transaction was successful.
            <?php endif; ?>
        </p>

        <?php if (!empty($customerName) && !empty($customerEmail)) : ?>
            <div class="item-list">
                <h3>Customer Details:</h3>
                <ul>
                    <li>Name: <?php echo htmlspecialchars($customerName); ?></li>
                    <li>Email: <?php echo htmlspecialchars($customerEmail); ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($paidItems) && isset($successMessage)) : ?>
            <div class="item-list">
                <h3>Paid Items:</h3>
                <ul>
                    <?php foreach ($paidItems as $item) : ?>
                        <li>
                            <?php echo htmlspecialchars($item['name']) . " - ₱" . number_format($item['amount'] / 100, 2); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <a href="../controllers/paynow_controller.php" class="btn">Go back to Dashboard</a>
    </div>
</body>
</html>