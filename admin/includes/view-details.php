<!-- Modal for Viewing Details -->
<div id="viewModal" class="transaction-modal">
    <div class="transaction-modal-content">
        <span class="close">&times;</span>
        <h2>Transaction Details</h2>
        <div class="details">
            <p><strong>Student No:</strong> <span id="modal-student-no"></span></p>
            <p><strong>Last Name:</strong> <span id="modal-lastname"></span></p>
            <p><strong>First Name:</strong> <span id="modal-firstname"></span></p>
            <p><strong>Middle Initial:</strong> <span id="modal-middle-initial"></span></p>
            <p><strong>Reference No:</strong> <span id="modal-reference-no"></span></p>
            <p><strong>Amount:</strong> ₱<span id="modal-amount"></span></p>
            <p><strong>Status:</strong> <span id="modal-status"></span></p>
        </div>
    </div>
</div>

<script>
    // Function to show modal with transaction details
    function viewTransaction(transaction) {
        console.log(transaction); // Check the transaction object

        // Ensure amount is a number
        let amount = parseFloat(transaction.amount);
        if (isNaN(amount)) {
            amount = 0; // Fallback in case parsing fails
        }

        document.getElementById('modal-student-no').textContent = transaction.student_no;
        document.getElementById('modal-lastname').textContent = transaction.lastname;
        document.getElementById('modal-firstname').textContent = transaction.firstname;
        document.getElementById('modal-middle-initial').textContent = transaction.middle_initial;
        document.getElementById('modal-reference-no').textContent = transaction.reference_no;
        document.getElementById('modal-amount').textContent = amount.toFixed(2); 
        document.getElementById('modal-status').textContent = transaction.status;

        document.getElementById('viewModal').style.display = "flex"; // Changed to flex
    }

    // Close the modal
    document.querySelector('.transaction-modal .close').onclick = function() {
        document.getElementById('viewModal').style.display = "none";
    }

    // Close modal when clicked outside
    window.onclick = function(event) {
        if (event.target === document.getElementById('viewModal')) {
            document.getElementById('viewModal').style.display = "none";
        }
    }
</script>