<?php
$content = '../components/payment_content.php';
include '../../cashier/layouts/master.php';

include '../includes/force_revert.php';
?>

    <style>
        /* Modal Styles */
        .confirm-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .confirm-modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            padding-top: 8px;
            border-radius: 5px;
            border: 1px solid #888;
            width: 60%;
        }

        .confirm-modal-content .online-header{
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1.5px solid rgb(0, 0, 0, 0.5);
            /* background-color: green; */
            padding: 1px 0;
            margin: 0;
        }
        .confirm-modal-content .otc-header{
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1.5px solid rgb(0, 0, 0, 0.5);
            /* background-color: green; */
            padding: 1px 0;
            margin: 0;
        }
        .confirm-modal-content .online-header h2,
        .confirm-modal-content .otc-header h2{
            /* background-color: yellow; */
            padding: 0;
            margin: 0;
        }

        .confirm-modal-content .online-top-content,
        .confirm-modal-content .otc-top-content{
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            gap: 20px;
            margin-top: 10px;
            margin-bottom: 10px;
            padding: 20px;
            background-color: #fff;
            text-align: center;
            border-radius: 10px;
            border: 1px solid rgb(85, 85, 85, 0.3);
            box-shadow: 2px 10px 15px -3px rgba(0, 0, 0, 0.2), 2px 4px 6px -2px rgba(0, 0, 0, 0.2);
        }
        .confirm-modal-content .online-top-content p,
        .confirm-modal-content .otc-top-content p{
            font-size: 20px;
            font-weight: bold;
            padding: 0;
            margin: 0;
        }
        .confirm-modal-content .online-top-content .online-top-con,
        .confirm-modal-content .otc-top-content .otc-top-con{
            display: flex;
            justify-content: flex-start;
            align-items: center;
            font-size: 20px;
            font-weight: bold;
            padding: 0;
            margin: 0;
            width: 100%;
        }
        .confirm-modal-content .online-top-content .online-top-con .online-p,
        .confirm-modal-content .otc-top-content .otc-top-con .otc-p{
            width: 200px;
            display: flex;
            justify-content: flex-start;
            align-items: center;
        }

        .close-otc, .close-online {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-otc:hover,
        .clos-otc:focus,
        .close-online:hover,
        .clos-online:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .confirm-modal-content form{
            /* background-color: grey; */
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 5px;
        }
        .confirm-modal-content form button{
            padding: 5px 15px;
            font-size: 15px;
            font-weight: bold;
            border-radius: 5px;
            border: 1px solid rgb(85, 85, 85, 0.3);
            background-color: #fff;
            box-shadow: 2px 10px 15px -3px rgba(0, 0, 0, 0.2), 2px 4px 6px -2px rgba(0, 0, 0, 0.2);
        }
        .confirm-modal-content form button:hover{
            font-size: 14px;
        }
        .confirm-modal-content form .modalConfirm{
            background-color: green;
            color: #fff;
        }
        .confirm-modal-content form .modalCancel{
            background-color: red;
            color: #fff;
        }
    </style>

    
    <div id="confirmationModalOnline" class="confirm-modal">
        <div class="confirm-modal-content">
            <div class="online-header">
                <h2>Are you sure you want to confirm the Online payment for:</h2>
                <span class="close-online">&times;</span>
            </div>
            <div class="online-top-content">
                <div class="online-top-con">
                    <div class="online-p">
                        <p>Student Name:</p>
                    </div>
                    <p><span id="modalStudentFullNameOnline"></span></p>
                </div>

                <div class="online-top-con">
                    <div class="online-p">
                        <p>Student Number:</p> 
                    </div>
                    <p><span id="modalStudentNumberOnline"></span></p>
                </div>

                <div class="online-top-con">
                    <div class="online-p">
                        <p>Payment:</p> 
                    </div>
                    <p><span id="modalFeeForOnline"></span></p>
                </div>

                <div class="online-top-con">
                    <div class="online-p">
                        <p>Payment Date:</p>
                    </div>
                    <p><span id="modalPaymentDateOnline"></span></p>
                </div>
                    
                <div class="online-top-con">
                    <div class="online-p">
                        <p>Amount:</p> 
                    </div>
                    <p>₱ <span id="modalAmountOnline"></span></p>
                </div>

                <div class="online-top-con">
                    <div class="online-p">
                        <p>Reference:</p>
                    </div>
                    <p><span id="modalReferenceOnline"></span></p>
                </div>
            </div>
            <form method="post" id="modalConfirmFormOnline">
                <input type="hidden" name="student_number" id="modalHiddenStudentNumberOnline">
                <input type="hidden" name="fee_for" id="modalHiddenFeeForOnline">
                <input type="hidden" name="amount" id="modalHiddenAmountOnline">

                <button type="submit" name="ConfirmOnlinePayment" class="modalConfirm">Confirm</button>
                <button type="button" id="modalCancelOnline" class="modalCancel modalCancelOnline">Cancel</button>
            </form>
        </div>
    </div>

    
    <div id="confirmationModalOTC" class="confirm-modal">
        <div class="confirm-modal-content">
            <div class="otc-header">
                <h2>Are you sure you want to confirm the OTC payment for</h2>
                <span class="close-otc">&times;</span>
            </div>
            <div class="otc-top-content">
                <div class="otc-top-con">
                    <div class="otc-p">
                        <p>Student Name:</p>
                    </div>
                    <p><span id="modalStudentFullNameOTC"></span></p>
                </div>
                <div class="otc-top-con">
                    <div class="otc-p">
                        <p>Student Number:</p> 
                    </div>
                    <p><span id="modalStudentNumberOTC"></span></p>
                </div>
                <div class="otc-top-con">
                    <div class="otc-p">
                        <p>Payment:</p> 
                    </div>
                    <p><span id="modalFeeForOTC"></span></p>
                </div>
                <div class="otc-top-con">
                    <div class="otc-p">
                        <p>Amount:</p> 
                    </div>
                    ₱ <input type="number" id="modalAmountOTC" style="width: 150px;" step="0.01"> 
                </div>
                <div class="otc-top-con">
                    <div class="otc-p">
                        <p>Receipt No:</p>
                    </div>
                    <!-- <input type="text" style="width: 250px;"> -->
                    <input type="text" id="modalReferenceOTC" placeholder="Enter the reciept number" style="width: 250px;">
                </div>
                
            </div>
            <form method="post" id="modalConfirmFormOTC">
                <input type="hidden" name="student_number" id="modalHiddenStudentNumberOTC">
                <input type="hidden" name="fee_for" id="modalHiddenFeeForOTC">
                <input type="hidden" name="amount" id="modalHiddenAmountOTC">
                <input type="hidden" name="reference" id="modalHiddenReferenceOTC">

                <button type="submit" name="ConfirmOTCPayment" class="modalConfirm">Confirm</button>
                <button type="button" id="modalCancelOTC" class="modalCancel modalCancelOTC">Cancel</button>
            </form>
        </div>
    </div>


    <script>
        const modalOnline = document.getElementById("confirmationModalOnline");
        const modalOTC = document.getElementById("confirmationModalOTC");

        // OTC Modal Elements
        const modalStudentNumberOTC = document.getElementById("modalStudentNumberOTC");
        const modalFeeForOTC = document.getElementById("modalFeeForOTC");
        const modalAmountOTC = document.getElementById("modalAmountOTC");
        const modalReferenceOTC = document.getElementById("modalReferenceOTC");
        const modalHiddenStudentNumberOTC = document.getElementById("modalHiddenStudentNumberOTC");
        const modalHiddenFeeForOTC = document.getElementById("modalHiddenFeeForOTC");
        const modalHiddenAmountOTC = document.getElementById("modalHiddenAmountOTC");
        const modalHiddenReferenceOTC = document.getElementById("modalHiddenReferenceOTC");
        const modalCancelOTC = document.querySelector(".modalCancelOTC");
        const closeBtnOTC = document.querySelector(".close-otc");
        const modalConfirmFormOTC = document.getElementById("modalConfirmFormOTC");

        // Online Modal Elements
        const modalStudentNumberOnline = document.getElementById("modalStudentNumberOnline");
        const modalFeeForOnline = document.getElementById("modalFeeForOnline");
        const modalAmountOnline = document.getElementById("modalAmountOnline");

        const modalHiddenStudentNumberOnline = document.getElementById("modalHiddenStudentNumberOnline");
        const modalHiddenFeeForOnline = document.getElementById("modalHiddenFeeForOnline");
        const modalHiddenAmountOnline = document.getElementById("modalHiddenAmountOnline");

        const modalCancelOnline = document.getElementById("modalCancelOnline");
        const modalConfirmFormOnline = document.getElementById("modalConfirmFormOnline");
        const closeBtnOnline = document.querySelector(".close-online");

        const confirmButtons = document.querySelectorAll(".confirm-btn");

        confirmButtons.forEach(button => {
            button.addEventListener("click", function() {
                const studentNumber = this.getAttribute("data-student");
                const feeFor = this.getAttribute("data-fee");
                const amount = this.getAttribute("data-amount");
                const paymentMethod = this.getAttribute("data-method");
                const fullName = this.getAttribute("data-fullname"); 

                if (paymentMethod === "OTC") {
                    modalStudentNumberOTC.textContent = studentNumber;
                    modalFeeForOTC.textContent = feeFor;
                    // modalAmountOTC.textContent = amount;
                    modalAmountOTC.value = amount; // Set the input value
                    modalHiddenStudentNumberOTC.value = studentNumber;
                    modalHiddenFeeForOTC.value = feeFor;
                    modalHiddenAmountOTC.value = amount;
                    modalStudentFullNameOTC.textContent = fullName; // Set the full name
                    modalOTC.style.display = "block";
                } else if (paymentMethod === "Online") {
                    modalStudentNumberOnline.textContent = studentNumber;
                    modalFeeForOnline.textContent = feeFor;
                    modalAmountOnline.textContent = amount;
                    modalHiddenStudentNumberOnline.value = studentNumber;
                    modalHiddenFeeForOnline.value = feeFor;
                    modalHiddenAmountOnline.value = amount;
                    modalPaymentDateOnline.textContent = this.getAttribute("data-date"); // Add this line
                    modalReferenceOnline.textContent = this.getAttribute("data-reference"); // Add this line
                    modalStudentFullNameOnline.textContent = fullName; // Set the full name
                    modalOnline.style.display = "block";
                }
            });
        });


        modalConfirmFormOTC.addEventListener("submit", function(event) {
            if (!modalReferenceOTC.value.trim()) {
                event.preventDefault();
                modalReferenceOTC.setCustomValidity("Please enter the reference number.");
                modalReferenceOTC.reportValidity();
            } else {
                modalReferenceOTC.setCustomValidity(""); // Clear any previous error message
                modalHiddenReferenceOTC.value = modalReferenceOTC.value;
            }

            // Get the amount from the input field
            modalHiddenAmountOTC.value = modalAmountOTC.value;
        });

        modalReferenceOTC.addEventListener('input', function() {
            modalReferenceOTC.setCustomValidity(""); // Clear validation on input change
        });

        // OTC Amount Input Visual Indicator
        // modalAmountOTC.addEventListener('input', function() {
        //     if (parseFloat(modalAmountOTC.value) !== parseFloat(originalAmountOTC.value)) {
        //         modalAmountOTC.style.backgroundColor = 'yellow'; // Visual indicator
        //     } else {
        //         modalAmountOTC.style.backgroundColor = ''; // Reset background
        //     }
        // });

        // OTC Modal Close Logic
        closeBtnOTC.addEventListener("click", function() {
            modalOTC.style.display = "none";
        });

        modalCancelOTC.addEventListener("click", function() {
            modalOTC.style.display = "none";
        });

        // Online Modal Close Logic
        closeBtnOnline.addEventListener("click", function() {
            modalOnline.style.display = "none";
        });

        modalCancelOnline.addEventListener("click", function() {
            modalOnline.style.display = "none";
        });

        window.onclick = function(event) {
            if (event.target == modalOTC) {
                modalOTC.style.display = "none";
            }
            if(event.target == modalOnline){
                modalOnline.style.display = "none";
            }
        };
    </script>