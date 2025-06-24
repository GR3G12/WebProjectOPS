<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Canceled</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f4f4f4;
            padding: 50px;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: inline-block;
        }
        .icon {
            font-size: 50px;
            color: #dc3545;
        }
        h2 {
            color: #dc3545;
        }
        p {
            font-size: 18px;
        }
        .btn {
            background: #dc3545;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }
        .btn:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">❌</div>
        <h2>Payment Canceled</h2>
        <p>Your payment was not completed. If this was a mistake, you can try again.</p>
        <a href="../controllers/paynow_controller.php" class="btn">Try Again</a>
    </div>
</body>
</html>
