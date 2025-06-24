<?php
session_start();
$content = '../components/transaction_content.php';
include '../../student/layouts/master.php';
?>
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            width: 450px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 20px;
        }

        .modal.active {
            display: block;
        }

        .modal-header {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            border-bottom: 1.5px solid rgba(0, 0, 0, 0.69); 
            padding: 5px 0;
        }

        .modal-content .con-row{
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 10px;
        }
        .modal-content .con-row span{
            text-align: left;
        }

        .modal-content .con-row p {
            margin: 10px 0;
            width: 120px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            text-align: right;
            height: 35px;
        }

        .modal-footer button {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 7px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .modal-footer button:hover {
            background-color: #0056b3;
            padding: 5px 12px;
        }
    </style>

        <!-- Modal -->
        <div id="detailsModal" class="modal">
            <div class="modal-header">Payment Details</div>
            <div class="modal-content">
                <div class="con-row">
                    <p><strong>Reference No:</strong></p> 
                    <span id="modal-reference"></span>
                </div>
                <div class="con-row">
                    <p><strong>Fee For:</strong></p> 
                    <span id="modal-fee-for"></span>
                </div>
                <div class="con-row">
                    <p><strong>Amount:</strong></p> 
                    <span id="modal-amount"></span>
                </div>
                <div class="con-row">
                    <p><strong>Status:</strong></p> 
                    <span id="modal-status"></span>
                </div>
                <div class="con-row">
                    <p><strong>Date Updated:</strong></p> 
                    <span id="modal-updated-at"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button id="closeModal">Close</button>
            </div>
        </div>

        <script>
            // Get modal and close button
            const modal = document.getElementById('detailsModal');
            const closeModalButton = document.getElementById('closeModal');

            // Add event listeners to all eye icons
            document.querySelectorAll('.fa-eye').forEach(icon => {
                icon.addEventListener('click', function () {
                    // Get data attributes from the clicked icon
                    const reference = this.getAttribute('data-reference');
                    const feeFor = this.getAttribute('data-fee-for');
                    const amount = this.getAttribute('data-amount');
                    const status = this.getAttribute('data-status');
                    const updatedAt = this.getAttribute('data-updated-at');

                    // Populate modal with data
                    document.getElementById('modal-reference').textContent = reference;
                    document.getElementById('modal-fee-for').textContent = feeFor;
                    document.getElementById('modal-amount').textContent = amount;
                    document.getElementById('modal-status').textContent = status;
                    document.getElementById('modal-updated-at').textContent = updatedAt;

                    // Show the modal
                    modal.classList.add('active');
                });
            });

            // Close modal when the close button is clicked
            closeModalButton.addEventListener('click', function () {
                modal.classList.remove('active');
            });

            // Optional: Close the modal when clicking outside of it
            window.addEventListener('click', function (event) {
                if (event.target === modal) {
                    modal.classList.remove('active');
                }
            });
        </script>