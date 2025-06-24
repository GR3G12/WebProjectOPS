<?php

// Include database connection
include('../../database/db.php');

// Function to update fee status to 'Pending' with timestamp
function updateFeeStatusToPending($pdo, $student_number, $fee_ids) {
    if (empty($fee_ids) || !is_array($fee_ids)) {
        return false; // No fees to update or invalid input
    }

    $placeholders = implode(',', array_fill(0, count($fee_ids), '?'));
    $query_semester_fees = "UPDATE semester_fees SET status = 'Pending', payment_date = NOW(), payment_method = 'OTC', pending_timestamp = NOW() WHERE student_number = ? AND id IN ($placeholders)";
    $query_student_payments = "UPDATE student_payments SET status = 'Pending', payment_date = NOW(), payment_method = 'OTC', pending_timestamp = NOW() WHERE student_number = ? AND id IN ($placeholders)";

    try {
        // Update semester_fees
        $stmt_semester_fees = $pdo->prepare($query_semester_fees);
        $params_semester_fees = [$student_number];
        $params_semester_fees = array_merge($params_semester_fees, $fee_ids);
        $stmt_semester_fees->execute($params_semester_fees);

        // Update student_payments
        $stmt_student_payments = $pdo->prepare($query_student_payments);
        $params_student_payments = [$student_number];
        $params_student_payments = array_merge($params_student_payments, $fee_ids);
        $stmt_student_payments->execute($params_student_payments);

        return true;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to revert pending status after 12 hours
function revertPendingStatus($pdo) {
    $twelveHoursAgo = date('Y-m-d H:i:s', strtotime('-9 hours'));

    $query_semester_fees = "UPDATE semester_fees SET status = 'Unpaid', payment_method = NULL, pending_timestamp = NULL WHERE status = 'Pending' AND pending_timestamp < ?";
    $query_student_payments = "UPDATE student_payments SET status = 'Unpaid', payment_method = NULL, pending_timestamp = NULL WHERE status = 'Pending' AND pending_timestamp < ?";

    try {
        $stmt_semester_fees = $pdo->prepare($query_semester_fees);
        $stmt_semester_fees->execute([$twelveHoursAgo]);

        $stmt_student_payments = $pdo->prepare($query_student_payments);
        $stmt_student_payments->execute([$twelveHoursAgo]);

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
}

// Handle OTC Proceed button click
if (isset($_POST['otc_proceed'])) {
    $student_number = $_SESSION['student_number'];
    $fee_ids_string = isset($_POST['fee_ids']) ? $_POST['fee_ids'] : '';
    $fee_ids = explode(',', $fee_ids_string);

    if (updateFeeStatusToPending($pdo, $student_number, $fee_ids)) {
        // echo "<script>alert('Proceed to Cashier now!.');</script>";
        // echo "<script>window.location.href = window.location.href;</script>";
        
        $_SESSION['proceed_success_message'] = "Proceed to Cashier now!";
        echo "<script>
            var successMessage = document.createElement('div');
            successMessage.style.position = 'fixed';
            successMessage.style.top = '50%';
            successMessage.style.left = '50%';
            successMessage.style.transform = 'translateX(-50%)';
            successMessage.style.padding = '30px 70px';
            successMessage.style.backgroundColor = '#5CB338';
            successMessage.style.color = '#fff';
            successMessage.style.fontSize = '18px';
            successMessage.style.borderRadius = '8px';
            successMessage.style.zIndex = '9999';
            successMessage.innerText = '" . addslashes($_SESSION['proceed_success_message']) . "';
            document.body.appendChild(successMessage);

            setTimeout(function() {
                window.location.href = 'topay_controller.php';
            }, 1500);
        </script>";
    } else {
        echo "<script>alert('Failed to update fee status.');</script>";
        echo "<script>window.location.href = window.location.href;</script>";
    }
}

// Run the revert pending status function
// revertPendingStatus($pdo);
?>


<!-- Modal -->
<div id="payModal" class="paynow-modal" style="display: none;">
    <div class="paynow-modal-content">
        <span class="close" style="cursor: pointer;">&times;</span>
        <img src="../imgs/Gcash.png" alt="GCash Logo" width="300">
        <p>You are about to pay. Please select fees and proceed.</p>
        <div class="modal-buttons" style="margin-top: 20px;">
            <button id="overTheCounterBtn">Over The Counter</button><!--I don't have-->
            <button id="payNowBtn">GCash</button><!-- Pay Now -->
        </div>
    </div>
</div>


<div id="overTheCounterModal" class="paynow-modal" style="display: none;">
    <div class="otc-modal-content">
        <div class="otc-header">
            <h3>Over the Counter Method</h3>
            <span class="close-otc" style="cursor: pointer;">&times;</span>
        </div>
        <div class="otc-content">
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <div class="otc-content-gl">
                <h3>General Guidelines:</h3>
                <ul>
                    <li>All fees must be settled within the day.</li>
                    <li>Pay exact amount in CASH at Cashier's window, main building.</li>
                    <li>Confirmation of Payments are processed once paid.</li>
                    <li>Check payment history once your payment is recieved by the cashier.</li>
                </ul>
            </div>
            <br>
            <!-- <strong>Important Notes:</strong>
            <ul>
                <li>Ensure you have the exact amount to avoid delays.</li>
                <li>Keep your official receipt for your records.</li>
                <li>For inquiries, contact the Cashier Office directly.</li>
            </ul> -->
            <div id="otcSelectedFees">
            </div>
            <br>

                <div class="otc-footer">
                    <div class="otc-footer-start">
                        <input type="checkbox" id="otcAgreeCheckbox" required>
                        <label for="otcAgreeCheckbox">I Agree</label>
                    </div>
                    <div class="otc-footer-end">
                        <input type="hidden" id="fee_ids_input" name="fee_ids" value="">
                        <button type="submit" id="otcProceedBtn" name="otc_proceed">Proceed</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Add a loading indicator (Centered) -->
<div id="loadingIndicator" style="
    display: none; 
    position: fixed; 
    top: 0; 
    left: 0; 
    width: 100%; 
    height: 100%; 
    background: rgba(0, 0, 0, 0.5); 
    /* display: flex;  */
    align-items: center; 
    justify-content: center; 
    flex-direction: column;
    color: white;
    font-size: 18px;
    z-index: 99999999;">
    
    <img src="../imgs/loading.gif" alt="Loading..." width="60">
    <p style="margin-top: 10px;">Processing payment...</p>
</div>

<script>
    // Get modal elements
    const modal = document.getElementById('payModal');
    const closeModal = document.querySelector('.close');
    const topPayNowButton = document.querySelector('.paynow-header .button'); // Top "Pay Now" button
    const modalParagraph = modal.querySelector('p'); // Paragraph inside modal
    const payNowBtn = document.getElementById('payNowBtn'); // Pay Now button inside modal
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name="select_amount"]'); // Checkboxes
    const loadingIndicator = document.getElementById('loadingIndicator');

    const overTheCounterModal = document.getElementById('overTheCounterModal');
    const closeModalOTC = document.querySelector('.close-otc');
    const otcSelectedFeesDiv = document.getElementById('otcSelectedFees');

    // Event listener for the top "Pay Now" button
    topPayNowButton.addEventListener('click', () => {
        let totalAmount = 0;
        let selectedFees = [];

        // Calculate total amount and list selected fees
        checkboxes.forEach((checkbox) => {
            if (checkbox.checked) {
                const feeTitle = checkbox.dataset.feeFor;
                const amount = parseFloat(checkbox.value);
                totalAmount += amount;
                selectedFees.push({
                    name: feeTitle,
                    amount: amount * 100, // Convert to centavos
                    quantity: 1,
                    currency: "PHP"
                });
            }
        });

        if (selectedFees.length > 0) {
            // Calculate the gross amount (reverse calculation)
            let multiplier = 1 - 0.025; // 1 - 2.5% = 0.975
            let grossAmount = totalAmount / multiplier;

            // Calculate the processing fee (for display only)
            let payMongoFee = grossAmount - totalAmount;

            // Update modal content
            modalParagraph.innerHTML = `
                You have selected the following fees:<br>
                ${selectedFees.map(fee => `${fee.name}: ₱${(fee.amount / 100).toFixed(2)}`).join('<br>')}<br><br>
                <strong>Subtotal:</strong> ₱ ${totalAmount.toFixed(2)}<br>
                <strong>Processing Fee:</strong> ₱ ${payMongoFee.toFixed(2)}<br>
                <strong>Total Amount:</strong> <span style="font-weight: bold;">₱ ${grossAmount.toFixed(2)}</span><br><br>
                <i>(Click Over-the-Counter if you choose to pay at Cashier's office window.)</i><br>
                <br>
                IMPORTANT!<br>
                <i>Promissory Request and other concerns</i><br>
                <i>regarding payments or fees will be addressed</i><br>
                <i>in Cashier Office. Thank you & God Bless!</i>
            `;

            // Show the modal
            modal.style.display = 'block';
        } else {
            alert('No fees selected. Please select at least one fee to proceed.');
        }
    });

    // Close modal when the "X" button is clicked
    closeModal.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // Close modal when clicking outside of it
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Get references to the checkbox and the Proceed button
    const otcAgreeCheckbox = document.getElementById('otcAgreeCheckbox');
    const otcProceedBtn = document.getElementById('otcProceedBtn');

    // Disable the Proceed button initially
    otcProceedBtn.disabled = true;

    // Add an event listener to the checkbox
    otcAgreeCheckbox.addEventListener('change', () => {
        otcProceedBtn.disabled = !otcAgreeCheckbox.checked;
    });

    // Event listener for "Over The Counter" button
    document.getElementById('overTheCounterBtn').addEventListener('click', () => {
        let selectedFeesDetails = [];
    let totalAmount = 0; // Initialize total amount

        checkboxes.forEach((checkbox) => {
            if (checkbox.checked) {
                const feeTitle = checkbox.dataset.feeFor;
                const amount = parseFloat(checkbox.value);
                totalAmount += amount; // Add amount to the total
                selectedFeesDetails.push(`
                <div class="otc-btn-con">
                    <div class="otc-con">
                        <div class="otc-label-con"><strong>${feeTitle}:</strong></div>
                        <div class="otc-label-con">₱${amount.toFixed(2)}</div>
                    </div>
                </div>
                `);
            }
        });
        if (selectedFeesDetails.length > 0) {
            // otcSelectedFeesDiv.innerHTML = '<h3>This Mode of payment is requesting for:</h3>' + selectedFeesDetails.join('');
            otcSelectedFeesDiv.innerHTML = `
                <h3>This Mode of payment is requesting for:</h3>
                <div class="otc-con">        
                    <div class="otc-label-con"><strong>Status</strong></div>
                    <div class="otc-label-con">Pending</div>
                </div>
                ${selectedFeesDetails.join('')}
                <div class="otc-total-con">
                    <strong>Total Amount:</strong> ₱${totalAmount.toFixed(2)}
                </div>
            `;
        } else {
            otcSelectedFeesDiv.innerHTML = '<p>No fees selected.</p>';
        }
        overTheCounterModal.style.display = 'block';
        modal.style.display = 'none';
    });

    // Event listener for "Proceed" button in OTC modal
    document.getElementById('otcProceedBtn').addEventListener('click', () => {
        let feeIds = [];
        checkboxes.forEach((checkbox) => {
            if (checkbox.checked) {
                feeIds.push(checkbox.dataset.feeId);
            }
        });
        document.getElementById('fee_ids_input').value = feeIds.join(',');
        loadingIndicator.style.display = 'flex';
    });

    // Close Over The Counter modal
    closeModalOTC.addEventListener('click', () => {
        overTheCounterModal.style.display = 'none';
        loadingIndicator.style.display = 'none';
    });

    // Close modal when clicking outside of it
    window.addEventListener('click', (event) => {
        if (event.target === overTheCounterModal) {
            overTheCounterModal.style.display = 'none';
            loadingIndicator.style.display = 'none';
        }
    });

    // Event listener for "Pay Now" button inside modal (Corrected)
    payNowBtn.addEventListener('click', () => {
        let selectedFees = [];
        let totalAmount = 0;

        checkboxes.forEach((checkbox) => {
            if (checkbox.checked) {
                let amount = parseFloat(checkbox.value);
                totalAmount += amount;

                selectedFees.push({
                    name: checkbox.dataset.feeFor,
                    amount: amount * 100, // Convert to centavos
                    quantity: 1,
                    currency: "PHP"
                });
            }
        });

        if (selectedFees.length > 0) {
            // Calculate the gross amount (reverse calculation)
            let multiplier = 1 - 0.025; // 1 - 2.5% = 0.975
            let grossAmount = totalAmount / multiplier;

            // Calculate the processing fee (for display only)
            let payMongoFee = grossAmount - totalAmount;

            // Update modal content to show the gross amount and fee
            modalParagraph.innerHTML = `
                You have selected the following fees:<br>
                ${selectedFees.map(fee => `${fee.name}: ₱${(fee.amount / 100).toFixed(2)}`).join('<br>')}<br><br>
                <strong>Subtotal:</strong> ₱ ${totalAmount.toFixed(2)}<br>
                <strong>Processing Fee:</strong> ₱ ${payMongoFee.toFixed(2)}<br>
                <strong>Total Amount:</strong> <span style="font-weight: bold;">₱ ${grossAmount.toFixed(2)}</span><br><br>
                <i>(Disregard this if you choose Over-the-Counter MOP)</i><br>
                <br>
                IMPORTANT!<br>
                <i>Promissory Request and other concerns</i><br>
                <i>regarding payments or fees will be addressed</i><br>
                <i>in Cashier Office. Thank you & God Bless!</i>
            `;

            loadingIndicator.style.display = 'flex';
            payNowBtn.disabled = true;

            fetch('../paymongo/create_checkout_session.php', {
                method: 'POST',
                body: JSON.stringify({
                    items: selectedFees
                }),
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                loadingIndicator.style.display = 'none';
                if (data.success && data.checkout_url) {
                    window.location.href = data.checkout_url;
                } else {
                    alert('An error occurred while processing the payment.');
                }
            })
            .catch(error => {
                loadingIndicator.style.display = 'none';
                console.error('Error:', error);
                alert('Something went wrong.');
            });

        } else {
            alert('No fees selected. Please select at least one fee to proceed.');
        }

        modal.style.display = 'none';
    });
</script>
