<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f4f4;
        }

        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 350px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background-color: #0056b3;
        }

        .message {
            text-align: center;
            margin-top: 15px;
        }

        .error {
            color: red;
        }

        .success {
            color: green;
        }

        /* Responsive adjustments */
        @media (max-width: 400px) {
            body{
                padding: 10px;
            }
            .container {
                width: 95%; /* Even wider on smaller screens */
                padding: 15px; /* Further reduced padding */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php
        session_start();
        require 'database/db.php';

        if (isset($_GET['token'])) {
            $token = $_GET['token'];

            try {
                $stmt = $pdo->prepare("SELECT * FROM acts_ops_login WHERE reset_token = :token AND reset_token_expiry > NOW()");
                $stmt->bindParam(':token', $token, PDO::PARAM_STR);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    ?>
                    <form action="reset_password_process.php" method="POST">
                        <input type="hidden" name="token" value="<?php echo $token; ?>">
                        <div class="form-group">
                            <label for="new_password">New Password:</label>
                            <input type="password" name="new_password" required>
                        </div>
                        <button type="submit">Reset Password</button>
                    </form>
                    <?php
                } else {
                    echo "<p class='message error'>Invalid or expired reset token.</p>";
                }
            } catch (PDOException $e) {
                echo "<p class='message error'>Database error: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p class='message error'>Invalid request.</p>";
        }
        ?>
    </div>
</body>
</html>