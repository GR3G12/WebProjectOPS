<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body {
            font-family: 'Roboto',sans-serif;
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
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

        input[type="text"] {
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
            font-size: 18px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            margin-bottom: 10px;
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
        <h2>Forgot Password</h2>
        <?php
            session_start();
            
            if (isset($_SESSION['forgot_error_message'])) {
                echo '<p style="color: red;">' . $_SESSION['forgot_error_message'] . '</p>';
                unset($_SESSION['forgot_error_message']);
            }
            if (isset($_SESSION['forgot_success_message'])) {
                echo '<p style="color: green;">' . $_SESSION['forgot_success_message'] . '</p>';
                unset($_SESSION['forgot_success_message']);
            }
        ?>
        <form action="reset_request.php" method="POST">
            <div class="form-group">
                <label for="student_number">Student Number / ID Number:</label>
                <input type="text" name="student_number" required>
            </div>
            <button type="submit">Reset Password</button>
        </form>
        <p class="message"><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>