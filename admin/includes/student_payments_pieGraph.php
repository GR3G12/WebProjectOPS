<?php
include '../../database/db.php';

// from Semester_fees table
// Fetch total number of students
$stmt = $pdo->query("SELECT COUNT(*) AS total_students FROM student_accounts");
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'];

// Fetch paid and unpaid student counts
$stmt = $pdo->query("SELECT 
    COUNT(CASE WHEN status = 'Paid' THEN 1 END) AS paid_students, 
    COUNT(CASE WHEN status = 'Unpaid' THEN 1 END) AS unpaid_students,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS pending_students 
    FROM semester_fees");
$payment_data = $stmt->fetch(PDO::FETCH_ASSOC);

$paid_students = $payment_data['paid_students'] ?? 0;
$unpaid_students = $payment_data['unpaid_students'] ?? 0;
$pending_students = $payment_data['pending_students'] ?? 0;
$total_payment_records = $paid_students + $unpaid_students + $pending_students;//

// Ensure the percentages sum to 100%
if ($total_payment_records > 0) {
    $paid_percentage = round(($paid_students / $total_payment_records) * 100, 1);
    $unpaid_percentage = round(($unpaid_students / $total_payment_records) * 100, 1);
    // $pending_percentage = 100 - ($paid_percentage + $unpaid_percentage);    
    $pending_percentage = round(($pending_students / $total_payment_records) * 100, 1);
} else {
    $paid_percentage = 0;
    $unpaid_percentage = 0;
    $pending_percentage = 0;
}

// Calculate total accumulated payments
$stmt = $pdo->query("SELECT SUM(amount) AS total_payments FROM semester_fees WHERE status = 'Paid'");
$total_payments = $stmt->fetch(PDO::FETCH_ASSOC)['total_payments'] ?? 0;


//  From student_payments table
// Fetch total number of students
$stmt = $pdo->query("SELECT COUNT(*) AS total_students FROM student_accounts");
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'];

// Fetch paid and unpaid student counts
$stmt = $pdo->query("SELECT 
    COUNT(CASE WHEN status = 'Paid' THEN 1 END) AS paid_students, 
    COUNT(CASE WHEN status = 'Unpaid' THEN 1 END) AS unpaid_students,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS pending_students 
    FROM student_payments");
$payment_data = $stmt->fetch(PDO::FETCH_ASSOC);

$paid_students = $payment_data['paid_students'] ?? 0;
$unpaid_students = $payment_data['unpaid_students'] ?? 0;
$pending_students = $payment_data['pending_students'] ?? 0;
$total_payment_records = $paid_students + $unpaid_students + $pending_students;//

// Ensure the percentages sum to 100%
if ($total_payment_records > 0) {
    $student_paid_percentage = round(($paid_students / $total_payment_records) * 100, 1);
    $student_unpaid_percentage = round(($unpaid_students / $total_payment_records) * 100, 1);
    $student_pending_percentage = round(($pending_students / $total_payment_records) * 100, 1);
} else {
    $student_paid_percentage = 0;
    $student_unpaid_percentage = 0;
    $student_pending_percentage = 0;
}

// Calculate total accumulated payments
$stmt = $pdo->query("SELECT SUM(amount) AS total_payments FROM student_payments WHERE status = 'Paid'");
$student_total_payments = $stmt->fetch(PDO::FETCH_ASSOC)['total_payments'] ?? 0;

?>