<?php
require '../../database/db.php';

if (isset($_GET['student_number'])) {
    $studentNumber = $_GET['student_number'];

    $stmt = $pdo->prepare("SELECT fee_for, amount FROM student_payments WHERE student_number = ?");

    echo "SQL Query: " . $stmt->queryString . "<br>"; // Debugging line

    $stmt->execute([$studentNumber]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Fetched Data: <pre>"; // Debugging lines
    print_r($payments);
    echo "</pre>";

    echo json_encode($payments);
} else {
    echo json_encode([]);
}
?>