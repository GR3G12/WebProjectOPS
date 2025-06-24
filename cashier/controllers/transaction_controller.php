<?php
include '../../includes/view-details.php';
$content = '../components/transaction_content.php';
include '../../cashier/layouts/master.php';
include '../includes/force_revert.php';
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

        .modal-content .modal-header{
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1.5px solid rgba(0, 0, 0, 0.59);
            margin-top: -10px;
        }
        
        .modal-content .modal-header h2{
            margin: 0;
            padding: 0;
        }
        .modal-content .details-con{
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap:10px;
        }
        .modal-content .details-con .title{
            width: 200px;
        }
        .modal-content .details-con span{
            width: 100%;
        }

        .close-btn {
            /* position: absolute; */
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
            <div class="modal-header">
                <h2>Transaction Details</h2>
                <span class="close-btn">&times;</span>
            </div>
            <div class="modal-details">
                <div class="details-con">
                    <div class="title">
                        <p><strong>Student Number:</strong></p> 
                    </div>
                    <span id="modal-student-number"></span>
                </div>
                <div class="details-con">
                    <div class="title">
                        <p><strong>Course:</strong></p> 
                    </div>
                    <span id="modal-student-course"></span>
                </div>
                <div class="details-con">
                    <div class="title">
                        <p><strong>Lastname:</strong></p> 
                    </div>
                    <span id="modal-student-lastname"></span>
                </div>
                <div class="details-con">
                    <div class="title">
                        <p><strong>Firstname:</strong></p> 
                    </div>
                    <span id="modal-student-firstname"></span> <!-- Corrected here -->
                </div>
                <div class="details-con">
                    <div class="title">
                        <p><strong>Fee For:</strong></p> 
                    </div>
                    <span id="modal-fee-for"></span>
                </div>
                <div class="details-con">
                    <div class="title">
                        <p><strong>Payment Date:</strong></p> 
                    </div>
                    <span id="modal-payment-date"></span>
                </div>
                <div class="details-con">
                    <div class="title">
                        <p><strong>Reference No:</strong></p> 
                    </div>
                    <span id="modal-reference"></span>
                </div>
                <div class="details-con">
                    <div class="title">
                        <p><strong>Amount:</strong></p> 
                    </div>
                    <span id="modal-student-amount"></span>
                </div>
                <div class="details-con">
                    <div class="title">
                        <p><strong>Status:</strong></p>
                    </div> 
                    <span id="modal-student-status"></span>
                </div>
                    <!-- <p><strong>Date Sent:</strong> <span id="modal-updated-at"></span></p> -->
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
                const feeFor = this.getAttribute('data-fee-for');
                const paymentDate = this.getAttribute('data-payment-date');
                const reference = this.getAttribute('data-reference');
                const studentAmount = this.getAttribute('data-student-amount');
                const studentStatus = this.getAttribute('data-student-status');
                // const updatedAt = this.getAttribute('data-updated-at');

                // Populate modal with data
                document.getElementById('modal-student-number').innerText = studentNumber;
                document.getElementById('modal-student-course').innerText = studentCourse;
                document.getElementById('modal-student-lastname').innerText = studentLastname;
                document.getElementById('modal-student-firstname').innerText = studentFirstname;
                document.getElementById('modal-fee-for').innerText = feeFor;
                document.getElementById('modal-payment-date').innerText = paymentDate;
                document.getElementById('modal-reference').innerText = reference;
                document.getElementById('modal-student-amount').innerText = studentAmount;
                document.getElementById('modal-student-status').innerText = studentStatus;
                // document.getElementById('modal-updated-at').innerText = updatedAt;

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