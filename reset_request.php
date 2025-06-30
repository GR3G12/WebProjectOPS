<?php
session_start();
require 'database/db.php'; // Ensure this path is correct

// Composer autoload
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_number = trim($_POST['student_number']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM acts_ops_login WHERE student_number = :student_number");
        $stmt->bindParam(':student_number', $student_number, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate a unique token
            $token = bin2hex(random_bytes(50));
            $expiry = date('Y-m-d H:i:s', strtotime('+12 hour')); // Token expires in 1 hour

            $updateStmt = $pdo->prepare("UPDATE acts_ops_login SET reset_token = :token, reset_token_expiry = :expiry WHERE student_number = :student_number");
            $updateStmt->bindParam(':token', $token, PDO::PARAM_STR);
            $updateStmt->bindParam(':expiry', $expiry, PDO::PARAM_STR);
            $updateStmt->bindParam(':student_number', $student_number, PDO::PARAM_STR);
            $updateStmt->execute();

            // Send email using PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Change to DEBUG_OFF for production Remember to set this back to SMTP::DEBUG_OFF once you've resolved the issue.
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'gregoriollagas12@gmail.com'; // SMTP username
                $mail->Password   = 'bsxonsalysvuivzw'; // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
                $mail->Port       = 587; // TCP port to connect to or //465

                // Recipients
                $mail->setFrom('gregoriollagas12@gmail.com', 'ACTS-OPS');
                $mail->addAddress($user['email'], $user['firstname']); // Add a recipient

                // Content
                $mail->isHTML(true); // Set email format to HTML
                $mail->Subject = 'Password Reset';
                $resetLink = "http://localhost/NewPHP/Capstone-New/reset_password.php?token=" . $token; // Replace yourdomain.com
                $mail->Body    = 'Click this link to reset your password: <a href="' . $resetLink . '">' . $resetLink . '</a>';
                $mail->AltBody = 'Click this link to reset your password: ' . $resetLink;

                $mail->send();
                $_SESSION['forgot_success_message'] = "A password reset link has been sent to your email. <br><br> Check it now before it expire.";
            } catch (Exception $e) {
                $_SESSION['forgot_error_message'] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $_SESSION['forgot_error_message'] = "Student number not found.";
        }
    } catch (PDOException $e) {
        $_SESSION['forgot_error_message'] = "Database error: " . $e->getMessage();
    }

    echo '<script>window.location.href = "forgot_password.php";</script>';
    exit;
}
?>