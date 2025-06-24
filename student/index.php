<?php
session_start(); // Start the session to access session variables
include('../database/db.php'); // Include the database connection file

$content = 'components/dashboard_content.php';
include '../student/layouts/dashboard.php'; // master


// // Check if the user is logged in and has the role 'student'
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
//     header("Location: ../login.php");
//     exit;
// }

// // Retrieve user information from session
// $user_id = $_SESSION['user_id'];
// // $firstname = $_SESSION['firstname'];
// // $lastname = $_SESSION['lastname'];
// $profile_image = $_SESSION['profile_image'];

// // Query the database to get the student's profile image (if needed)
// try {
//     $stmt = $pdo->prepare("SELECT * FROM acts_ops_login WHERE id = :user_id");
//     $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
//     $stmt->execute();

//     if ($stmt->rowCount() > 0) {
//         $user = $stmt->fetch(PDO::FETCH_ASSOC);
//         $profile_image = $user['profile_image']; // Use profile image from the database if needed
//     } else {
//         // Handle case where user is not found in the database
//         $_SESSION['error_message'] = "User not found.";
//         header("Location: ../login.php");
//         exit;
//     }
// } catch (PDOException $e) {
//     $_SESSION['error_message'] = "Database error: " . $e->getMessage();
//     header("Location: ../login.php");
//     exit;
// }
?>
