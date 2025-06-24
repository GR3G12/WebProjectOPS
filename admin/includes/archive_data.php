<?php
// session_start();
include '../../database/db.php'; // Adjust the path to your db.php file

// Function to move data from one table to another
function archiveTable($pdo, $sourceTable, $archiveTable, $selectQuery) {
    try {
        // Disable autocommit for transactional safety
        $pdo->beginTransaction();

        // Copy data from source to archive table
        $insertQuery = "INSERT INTO `$archiveTable` SELECT * FROM ($selectQuery) AS tmp";
        $pdo->exec($insertQuery);

        // Delete data from the source table (optional, but typical for archiving)
        $deleteQuery = "DELETE FROM `$sourceTable`";
        $pdo->exec($deleteQuery);

        // Commit the transaction
        $pdo->commit();
        return true; // Return true on success

    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        return "Error archiving `$sourceTable`: " . $e->getMessage(); // Return error message on failure
    }
}

// Table information
$tablesToArchive = [
    'created_payments' => [
        'archive_table' => 'created_payments_archive',
        'select_query' => "SELECT `payment_id`, `course`, `year_level`, `student_type`, `tuition_type`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted` FROM `created_payments`"
    ],
    'semester_fees' => [
        'archive_table' => 'semester_fees_archive',
        'select_query' => "SELECT `id`, `student_number`, `firstname`, `lastname`, `fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `deducted`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp` FROM `semester_fees`"
    ],
    'student_accounts' => [
        'archive_table' => 'student_accounts_archive',
        'select_query' => "SELECT `id`, `student_number`, `student_type`, `tuition_type`, `firstname`, `middlename`, `lastname`, `email`, `course`, `year_level`, `section`, `semester`, `total_tuition_fee`, `tuition_fee_discount`, `balance_to_be_paid`, `down_payment`, `total_balance`, `remaining_balance_to_pay`, `profile_image` FROM `student_accounts`"
    ],
    'student_payments' => [
        'archive_table' => 'student_payments_archive',
        'select_query' => "SELECT `id`, `student_number`, `fee_for`, `amount`, `event_date_start`, `event_date_end`, `due_date`, `status`, `reference`, `created_at`, `updated_at`, `payment_date`, `payment_method`, `verified_date`, `pending_timestamp` FROM `student_payments`"
    ],
];

// Check if the request is an AJAX request to trigger archiving
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $archiveSuccess = true;
    $archiveErrors = [];

    foreach ($tablesToArchive as $source => $details) {
        $result = archiveTable($pdo, $source, $details['archive_table'], $details['select_query']);
        if ($result !== true) { // Check if the result is not true (meaning an error occurred)
            $archiveSuccess = false;
            $archiveErrors[] = $result;
        }
    }

    if ($archiveSuccess) {
        echo "Successfully archived data.";

        // Insert dummy data into semester_fees after successful archiving
        $dummyData = [
            ['fee_for' => 'Prelim', 'event_date_start' => date('Y-m-d', strtotime('+1 week')), 'event_date_end' => date('Y-m-d', strtotime('+3 weeks')), 'amount' => 1500.00, 'due_date' => date('Y-m-d H:i:s')],
            ['fee_for' => 'Midterm', 'event_date_start' => date('Y-m-d', strtotime('+5 weeks')), 'event_date_end' => date('Y-m-d', strtotime('+7 weeks')), 'amount' => 1500.00, 'due_date' => date('Y-m-d H:i:s')],
            ['fee_for' => 'Prefinal', 'event_date_start' => date('Y-m-d', strtotime('+9 weeks')), 'event_date_end' => date('Y-m-d', strtotime('+11 weeks')), 'amount' => 1500.00, 'due_date' => date('Y-m-d H:i:s')],
            ['fee_for' => 'Final', 'event_date_start' => date('Y-m-d', strtotime('+13 weeks')), 'event_date_end' => date('Y-m-d', strtotime('+15 weeks')), 'amount' => 1500.00, 'due_date' => date('Y-m-d H:i:s')],
        ];

        try {
            $stmt = $pdo->prepare("INSERT INTO `semester_fees` (`fee_for`, `event_date_start`, `event_date_end`, `amount`, `due_date`) VALUES (:fee_for, :event_date_start, :event_date_end, :amount, :due_date)");
            foreach ($dummyData as $data) {
                $stmt->bindParam(':fee_for', $data['fee_for']);
                $stmt->bindParam(':event_date_start', $data['event_date_start']);
                $stmt->bindParam(':event_date_end', $data['event_date_end']);
                $stmt->bindParam(':amount', $data['amount']);
                $stmt->bindParam(':due_date', $data['due_date']);
                $stmt->execute();
            }
            // echo "<br>Successfully added dummy data to semester_fees.";
        } catch (PDOException $e) {
            echo "<br>Error adding dummy data to semester_fees: " . $e->getMessage();
        }

    } else {
        echo "Error during archiving: " . implode("<br>", $archiveErrors);
    }
    exit(); // Important: Stop further execution after handling the AJAX request
}
?>

<style>
    /* Modal Styles (as is) */
    .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1111111111; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    }
    .modal-content {
        background-color: #fefefe;
        margin: 15% auto; /* 15% from the top and centered */
        padding: 20px;
        border: 1px solid #888;
        width: 60%; /* Could be more or less, depending on screen size */
    }
    .close-button {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }
    .close-button:hover,
    .close-button:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
    .modal-buttons {
        display: flex;
        justify-content: flex-end;
        margin-top: 20px;
    }
    .modal-buttons button {
        padding: 10px 20px;
        margin-left: 10px;
        cursor: pointer;
    }
    .archive-data-button {
        background-color: #008CBA;
        color: white;
        border: none;
        border-radius: 5px;
    }
</style>

    <div id="endSemModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="document.getElementById('endSemModal').style.display='none'">&times;</span>
            <p>The semester has ended.</p>
            <div class="modal-buttons">
                <button class="archive-data-button" id="archiveDataBtn">Save Data to Archive</button>
                <button class="cancel-button" onclick="document.getElementById('endSemModal').style.display='none'">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Get the End of Sem modal
        var endSemModal = document.getElementById("endSemModal");

        // Get the "End of Sem" button (assuming you have one with this class)
        var endSemBtn = document.querySelector(".end-sem-b");

        // Get the "Save Data to Archive" button
        var archiveDataBtn = document.getElementById("archiveDataBtn");

        // Open the End of Sem modal when the button is clicked
        endSemBtn.addEventListener('click', function() {
            endSemModal.style.display = "block";
        });

        // Close the modal when the close button is clicked (already handled in HTML)

        // Close the modal if the user clicks outside of it
        window.addEventListener('click', function(event) {
            if (event.target == endSemModal) {
                endSemModal.style.display = "none";
            }
        });

        // Handle the "Save Data to Archive" button click
        archiveDataBtn.addEventListener('click', function() {
            // Perform the archive data action here using an AJAX request to the same PHP file
            fetch('', { // An empty string refers to the current page
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // Indicate it's an AJAX request
                }
            })
            .then(response => response.text())
            .then(data => {
                // Check if the response indicates success or failure
                if (data.includes("Successfully archived")) {
                    var successMessage = document.createElement('div');
                    successMessage.style.position = 'fixed';
                    successMessage.style.top = '20px';
                    successMessage.style.left = '50%';
                    successMessage.style.transform = 'translateX(-50%)';
                    successMessage.style.padding = '15px';
                    successMessage.style.backgroundColor = '#4CAF50';
                    successMessage.style.color = '#fff';
                    successMessage.style.fontSize = '16px';
                    successMessage.style.borderRadius = '5px';
                    successMessage.style.zIndex = '9999';
                    successMessage.innerText = 'Data successfully archived!'; // Use a static message here
                    document.body.appendChild(successMessage);
                    setTimeout(function() {
                        // document.body.removeChild(successMessage);
                        // endSemModal.style.display = "none";
                        window.location.href = 'setting_controller.php';
                    }, 2000);
                } else if (data.includes("Error during archiving")) {
                    var errorMessage = document.createElement('div');
                    errorMessage.style.position = 'fixed';
                    errorMessage.style.top = '20px';
                    errorMessage.style.left = '50%';
                    errorMessage.style.transform = 'translateX(-50%)';
                    errorMessage.style.padding = '15px';
                    errorMessage.style.backgroundColor = '#f44336';
                    errorMessage.style.color = '#fff';
                    errorMessage.style.fontSize = '16px';
                    errorMessage.style.borderRadius = '5px';
                    errorMessage.style.zIndex = '9999';
                    errorMessage.innerText = data; // Display the error message from PHP
                    document.body.appendChild(errorMessage);
                    setTimeout(function() {
                        // document.body.removeChild(errorMessage);
                        window.location.href = 'setting_controller.php';
                    }, 3000);
                } else {
                    alert(data); // Fallback to a simple alert for unexpected responses
                }
                endSemModal.style.display = "none";
            })
            .catch(error => {
                console.error('Error during archiving:', error);
                alert('An error occurred during the archiving.');
                endSemModal.style.display = "none";
            });
        });
    </script>