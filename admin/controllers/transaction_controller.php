<?php
include '../../includes/view-details.php';
$content = '../components/transaction_content.php';
include '../../admin/layouts/master.php';
?>
<style>
    /* Modal styling */
    .modal {
        display: none; /* Hidden by default */
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5); /* Black with transparency */
        z-index: 1000;
    }

    .modal-content {
        background: #fff;
        margin: 10% auto;
        padding: 20px;
        border-radius: 10px;
        width: 50%;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        position: relative;
    }

    .close-btn {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 30px;
        cursor: pointer;
        color: #555;
    }

    .close-btn:hover {
        color: #f00;
    }

    .modal-details {
        margin-top: 20px;
    }

    .modal-details p {
        margin: 5px 0;
    }
</style>

<!-- Modal -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2>Transaction Details</h2>
        <div class="modal-details">
            <p><strong>Student Number:</strong> <span id="modal-student-number"></span></p>
            <p><strong>Course:</strong> <span id="modal-student-course"></span></p>
            <p><strong>Lastname:</strong> <span id="modal-student-lastname"></span></p>
            <p><strong>Firstname:</strong> <span id="modal-student-firstname"></span></p> <!-- Corrected here -->
            <p><strong>Reference No:</strong> <span id="modal-reference"></span></p>
            <p><strong>Fee For:</strong> <span id="modal-fee-for"></span></p>
            <p><strong>Amount:</strong> <span id="modal-student-amount"></span></p>
            <p><strong>Status:</strong> <span id="modal-student-status"></span></p>
            <p><strong>Date Sent:</strong> <span id="modal-updated-at"></span></p>
        </div>
    </div>
</div>


<script>
    // Get modal elements
    const modal = document.getElementById('detailsModal');
    const closeModalBtn = document.querySelector('.close-btn');
    const eyeIcons = document.querySelectorAll('.fa-eye');

    // Add click event to eye icons
    eyeIcons.forEach(icon => {
        icon.addEventListener('click', function () {
            // Get data from clicked icon
            const studentNumber = this.getAttribute('data-student-number');
            const studentCourse = this.getAttribute('data-student-course');
            // const course = this.getAttribute('data-course');
            const studentLastname = this.getAttribute('data-student-lastname');
            const studentFirstname = this.getAttribute('data-student-firstname');
            const reference = this.getAttribute('data-reference');
            const feeFor = this.getAttribute('data-fee-for');
            const studentAmount = this.getAttribute('data-student-amount');
            const studentStatus = this.getAttribute('data-student-status');
            const updatedAt = this.getAttribute('data-updated-at');

            // Populate modal with data
            document.getElementById('modal-student-number').innerText = studentNumber;
            document.getElementById('modal-student-course').innerText = studentCourse;
            document.getElementById('modal-student-lastname').innerText = studentLastname;
            document.getElementById('modal-student-firstname').innerText = studentFirstname;
            document.getElementById('modal-reference').innerText = reference;
            document.getElementById('modal-fee-for').innerText = feeFor;
            document.getElementById('modal-student-amount').innerText = studentAmount;
            document.getElementById('modal-student-status').innerText = studentStatus;
            document.getElementById('modal-updated-at').innerText = updatedAt;

            // Show the modal
            modal.style.display = 'block';
        });
    });

    // Close modal when clicking on the close button
    closeModalBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // Close modal when clicking outside of the modal
    window.addEventListener('click', event => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
</script>