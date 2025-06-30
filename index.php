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
                $_SESSION['lastname'] = $user['lastname'];

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
    <title>ACTS OPS</title>
    <link rel="icon" type="image/png" href="img/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Piazzolla:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Loading indicator container */
        .loading-indicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.9); /* Semi-transparent white background */
            z-index: 1000; /* Ensure it appears above other elements */
            justify-content: center;
            align-items: center;
            display: flex;
        }

        /* Spinner container (this will scale up and down) */
        .loading-indicator .spinner-con {
            display: flex;
            justify-content: center;
            align-items: center;
            animation: scalle 2s ease-in-out infinite; /* Apply scaling animation to the container */
        }

        /* Spinner styling (fixed size) */
        .spinner {
            width: 150px;
            height: 150px;
            border: 8px solid #ccc; /* Light gray border */
            border-top: 8px solid #4caf50; /* Green color for the rotating part */
            border-radius: 50%;
            animation: spin 1s linear infinite; /* Apply spinning animation to the spinner */
            position: relative;
        }

        /* Keyframe for the spinner rotation */
        @keyframes spin {
            0% {
                transform: rotate(0deg); /* Start at 0 degrees */
            }
            100% {
                transform: rotate(360deg); /* Complete one full rotation */
            }
        }

        /* Keyframe for scaling (big to small and back) */
        @keyframes scalle {
            0% {
                transform: scale(1); /* Start at original size */
            }
            50% {
                transform: scale(1.5); /* Grow the container to 1.5 times its size */
            }
            100% {
                transform: scale(1); /* Shrink back to original size */
            }
        }

        /* Set the image as the background inside the container */
        .spinner-con {
            background-image: url('img/ACTS_LOGO.png'); /* Path to your image */
            background-size: 162px; /* Adjust size of the background image */
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
</head>
<body>
    <div id="loading-indicator" class="loading-indicator">
        <div class="spinner-con">
            <div class="spinner"></div>
        </div>
    </div>
    <script>
            // Simulate a delay (5 seconds) before redirecting
            setTimeout(function() {
                window.location.href = 'login.php';
            }, 2000); // 5000ms = 5 seconds
    </script>
</body>
</html>
