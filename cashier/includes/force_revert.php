<?php

// Function to forcefully revert all pending payments with a timestamp
function forceRevertAllPendingWithTimestamp($pdo) {
    try {
        // Update status in semester_fees table
        $stmt_sf = $pdo->prepare("UPDATE semester_fees SET status = 'Unpaid', payment_method = NULL, pending_timestamp = NULL WHERE status = 'Pending' AND pending_timestamp IS NOT NULL");
        $stmt_sf->execute();

        // Update status in student_payments table
        $stmt_sp = $pdo->prepare("UPDATE student_payments SET status = 'Unpaid', payment_method = NULL, pending_timestamp = NULL WHERE status = 'Pending' AND pending_timestamp IS NOT NULL");
        $stmt_sp->execute();

        $rowCountSF = $stmt_sf->rowCount();
        $rowCountSP = $stmt_sp->rowCount();
        $totalReverted = $rowCountSF + $rowCountSP;

        return $totalReverted; // Return the number of reverted records
    } catch (PDOException $e) {
        error_log("Database error during forced revert all: " . $e->getMessage());
        return -1; // Indicate an error
    }
}

// Handle forced revert all action
if (isset($_POST['forceRevertAll'])) {
    $revertedCount = forceRevertAllPendingWithTimestamp($pdo);

    if ($revertedCount >= 0) {
        $_SESSION['revert_all_success_message'] = "Successfully cancelled {$revertedCount} pending payments over the counter.";
        echo "<script>
            var successMessage = document.createElement('div');
            successMessage.style.position = 'fixed';
            successMessage.style.top = '20px';
            successMessage.style.left = '50%';
            successMessage.style.transform = 'translateX(-50%)';
            successMessage.style.padding = '15px';
            successMessage.style.backgroundColor = '#f44336'; /* Red color for revert */
            successMessage.style.color = '#fff';
            successMessage.style.fontSize = '16px';
            successMessage.style.borderRadius = '5px';
            successMessage.style.zIndex = '9999';
            successMessage.innerText = '" . addslashes($_SESSION['revert_all_success_message']) . "';
            document.body.appendChild(successMessage);

            setTimeout(function() {
                window.location.href = 'payment_controller.php';
            }, 1500);
        </script>";
        unset($_SESSION['revert_all_success_message']);
    } else {
        // $_SESSION['revert_all_error_message'] = "Error reverting pending payments. Please try again.";
        $_SESSION['revert_all_error_message'] = "Error canceling pending payments. Please try again.";
        echo "<script>
            var errorMessage = document.createElement('div');
            errorMessage.style.position = 'fixed';
            errorMessage.style.top = '20px';
            errorMessage.style.left = '50%';
            errorMessage.style.transform = 'translateX(-50%)';
            errorMessage.style.padding = '15px';
            errorMessage.style.backgroundColor = '#f44336'; /* Red color for error */
            errorMessage.style.color = '#fff';
            errorMessage.style.fontSize = '16px';
            errorMessage.style.borderRadius = '5px';
            errorMessage.style.zIndex = '9999';
            errorMessage.innerText = '" . addslashes($_SESSION['revert_all_error_message']) . "';
            document.body.appendChild(errorMessage);

            setTimeout(function() {
                var errorElement = document.querySelector('div[style*=\"background-color: rgb(244, 67, 54)\"]');
                if (errorElement) {
                    errorElement.remove();
                }
            }, 2500);
        </script>";
        unset($_SESSION['revert_all_error_message']);
    }
}
?>