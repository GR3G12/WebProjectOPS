<?php
session_start();

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include the database connection file
    require 'database/db.php'; // Ensure this path is correct

    // Get the input values from the login form
    $student_number = trim($_POST['student_number']);
    $password = trim($_POST['password']);

    // Validate input fields
    if (empty($student_number) || empty($password)) {
        $_SESSION['error_message'] = "Please fill in all fields.";
        header("Location: login.php");
        exit;
    }

    try {
        // Query the database for the given student number
        $stmt = $pdo->prepare("SELECT * FROM acts_ops_login WHERE student_number = :student_number");
        $stmt->bindParam(':student_number', $student_number, PDO::PARAM_STR);
        $stmt->execute();

        // Check if the user exists
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Store user data in session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['student_number'] = $user['student_number'];
                $_SESSION['firstname'] = $user['firstname'];
                $_SESSION['middlename'] = $user['middlename'];  
                $_SESSION['lastname'] = $user['lastname'];
                $_SESSION['profile_image'] = $user['profile_image']; // Store profile image

                // Redirect user based on their role
                switch ($user['role']) {
                    case 'student':
                        header("Location: student/index.php");
                        break;
                    case 'cashier':
                        header("Location: cashier/index.php");
                        break;
                    case 'admin':
                        header("Location: admin/index.php");
                        break;
                    default:
                        $_SESSION['error_message'] = "Invalid user role.";
                        header("Location: login.php");
                        break;
                }
                exit;
            } else {
                // Password is incorrect
                $_SESSION['error_message'] = "Invalid password. Please try again.";
            }
        } else {
            // No user found with the provided student number
            $_SESSION['error_message'] = "No user found for that student number.";
        }
    } catch (PDOException $e) {
        // Handle any database errors
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    }

    // Redirect back to login page with error message
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACTS Online Payment System</title>
    <link rel="icon" type="image/png" href="img/logo.png">
    <link rel="stylesheet" href="login.css">
    <link href="https://fonts.googleapis.com/css2?family=Piazzolla:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div id="loading-indicator" class="loading-indicator">
        <div class="spinner-con">
            <div class="spinner"></div>
        </div>
    </div>
    <div class="container">
        <div class="green">
            <form action="login.php" method="POST" id="login-form">
                <div class="title-logo">
                    <img src="img/ACTS_LOGO.png" alt="LOGO" width=190px>
                    <h1>ACTS - OPS</h1>
                </div>

                <div class="title-title">
                    <h1>GOOD DAY, ACTSTISTAS</h1>
                </div>

                <!-- Error Message Display -->
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="error-message">
                        <?php 
                        echo htmlspecialchars($_SESSION['error_message']); 
                        unset($_SESSION['error_message']); // Clear the message
                        ?>
                    </div>
                <?php endif; ?>

                <input type="text" name="student_number" placeholder="Student Number / ID Number" required>
                <input type="password" name="password" placeholder="Password" required>
                <!-- <a href="#" class="forgot-password"><span>Forgot Password?</span></a> -->
                <a href="forgot_password.php" class="forgot-password"><span>Forgot Password?</span></a>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>
        <div class="yellow">
            <!-- <div class="header-right">
                <img src="img/ACTS_LOGO.png" alt="logo" width="120px" height="120px">
                <h1>ACTS OPS</h1>
                <h3>Online Payment System</h3>
                <span>"YOUR Success is OUR Goal"</span>
            </div> -->
        </div>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', function(e) {
            // Show the loading indicator
            document.getElementById('loading-indicator').style.display = 'flex';

            // Delay form submission for at least 2 seconds
            e.preventDefault(); // Prevent immediate submission

            setTimeout(() => {
                this.submit(); // Submit the form after 2 seconds
            }, 3000);
        });
    </script>
</body>
</html>
