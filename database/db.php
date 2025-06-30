<?php
// Database configuration
$host = 'localhost';
$dbname = 'capstone-new';  // Replace with your actual database name
$username = 'root';    // Replace with your database username
$password = '';        // Replace with your database password

// Create a connection to the database using PDO
try {
    // Set the DSN (Data Source Name) for PDO
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8"; 

    // Create PDO instance
    $pdo = new PDO($dsn, $username, $password);
    
    // Set the PDO error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optionally, echo a success message (not recommended for production environments)
    // echo "Connected successfully to the database.";

} catch (PDOException $e) {
    // Handle connection failure (in production, do not expose this directly)
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>
