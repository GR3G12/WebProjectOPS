<?php
    session_start();
    require 'database/db.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $token = $_POST['token'];
        $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

        try {
            $updateStmt = $pdo->prepare("UPDATE acts_ops_login SET password = :password, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = :token");
            $updateStmt->bindParam(':password', $newPassword, PDO::PARAM_STR);
            $updateStmt->bindParam(':token', $token, PDO::PARAM_STR);
            $updateStmt->execute();

            echo "<p>Password reset successfully. You can now login with your new password.</p>";
            echo "<a href='login.php'>Login</a>";
            
        } catch (PDOException $e) {
            echo "<p>Database error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>Invalid request.</p>";
    }
?>