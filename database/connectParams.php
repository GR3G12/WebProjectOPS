<?php
// Check if the student number is set in the session, meaning the user is logged in
if (isset($_SESSION['student_number'])) {
    $student_number = $_SESSION['student_number']; // Get the student number
    $firstname = isset($_SESSION['firstname']) ? $_SESSION['firstname'] : "Unknown";
    $middlename = isset($_SESSION['middlename']) ? $_SESSION['middlename'] : "Unknown";
    $lastname = isset($_SESSION['lastname']) ? $_SESSION['lastname'] : "Unknown";
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : "Undefined";
    
    $profile_image = $_SESSION['profile_image'];
}
?>