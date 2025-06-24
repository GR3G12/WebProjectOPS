<?php
session_start(); // Start the session

$secretKey = "put your own secretkey"; 


// Get the JSON input sent from the JavaScript fetch request
$inputData = json_decode(file_get_contents('php://input'), true);
$items = $inputData['items']; // Get the array of selected fees

// Calculate the total amount before fees
$totalAmount = 0;
foreach ($items as $item) {
    $totalAmount += $item['amount']; // Sum all selected fees
}

// Calculate the gross amount (reverse calculation)
$multiplier = 1 - 0.025; // 1 - 2.5% = 0.975
$grossAmount = $totalAmount / $multiplier;

$processingFee = ceil($grossAmount - $totalAmount);

// Add the processing fee as a separate item
$items[] = [
    "name" => "Processing Fee",
    "amount" => $processingFee, // Amount in centavos
    "quantity" => 1,
    "currency" => "PHP"
];

// Store items in session to retrieve later
$_SESSION['paid_items'] = $items;

//Get student number from session.
$student_number = isset($_SESSION['student_number']) ? $_SESSION['student_number'] : null;

// Prepare line_items for PayMongo
$line_items = [];
foreach ($items as $item) {
    $line_items[] = [
        "currency" => "PHP",
        "amount" => $item['amount'], // Amount in centavos
        "name" => $item['name'], // Fee name
        "quantity" => 1
    ];
}

// Define data payload for Checkout Session
$data = [
    "data" => [
        "attributes" => [
            "line_items" => $line_items,
            "payment_method_types" => ["gcash"],
            "success_url" => "http://localhost/NewPHP/Capstone-New/student/paymongo/payment-success.php",
            "cancel_url" => "http://localhost/NewPHP/Capstone-New/student/paymongo/payment-cancel.php"
        ]
    ]
];

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.paymongo.com/v1/checkout_sessions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Basic " . base64_encode($secretKey . ":")
]);

// Execute the cURL request
$result = curl_exec($ch);
curl_close($ch);

// Decode the response
$response = json_decode($result, true);

// Check if the Checkout Session was created successfully
if (isset($response['data']['attributes']['checkout_url'])) {

    $_SESSION['checkout_session_id'] = $response['data']['id'];

    error_log("Session paid_items: " . print_r($_SESSION['paid_items'], true));
    error_log("Session student_number: " . print_r($_SESSION['student_number'], true));
    error_log("Session checkout_session_id: " . print_r($_SESSION['checkout_session_id'], true));

    // Return the checkout URL as a JSON response
    echo json_encode([
        'success' => true,
        'checkout_url' => $response['data']['attributes']['checkout_url']
    ]);
} else {
    // Output the error if there was an issue creating the Checkout Session
    echo json_encode([
        'success' => false,
        'error' => 'Error creating checkout session: ' . print_r($response, true)
    ]);
}
?>
