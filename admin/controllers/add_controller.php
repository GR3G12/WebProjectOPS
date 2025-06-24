<?php
include '../../database/db.php';

$content = '../components/add_content.php';
include '../../admin/layouts/master.php';

include '../includes/addpayment.php';

include '../includes/editpayment.php';
?>

<div class="view-modal" id="view-modal" style="display: none;">
    <div class="view-modal-container">
        <div class="modal-header">
            <label for="view">View Details</label>
            <span class="close-view-modal" id="close-view-modal">Close</span><!-- &times;-->
        </div>
        <div class="view-modal-content">
            <div class="view-row">
                <label for="student-type">Student Type:</label>  
                <input type="text" id="student_type" name="student_type" readonly>
            </div>
            <div class="view-row">
                <label for="tuition-type">Tuition Type:</label>  
                <input type="text" id="tuition_type" name="tuition_type" readonly>
            </div>
            <div class="view-row">
                <label for="course">Course:</label>  
                <input type="text" id="course" name="course" readonly>
            </div>
            <div class="view-row">
                <label for="year_level">Year Level:</label>  
                <input type="text" id="year_level" name="year_level" readonly>
            </div>
            <div class="view-row">
                <label for="title">Title:</label>  
                <input type="text" id="fee_for" name="fee_for" readonly>
            </div>
            <div class="view-row">
                <label for="amount">Amount:</label>  
                <input type="text" id="amountt" name="amount" readonly>
            </div>
            <div class="view-row">
                <label for="event_date">Event Date:</label>  
                <input type="text" id="event_date" name="event_date" readonly>
            </div>
            <div class="view-row">
                <label for="due_date">Due Date:</label>  
                <input type="text" id="due_date" name="due_date" readonly>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const viewModal = document.getElementById("view-modal");
        const closeViewModalButton = document.getElementById("close-view-modal");

        // Get all view buttons
        const viewModalButtons = document.querySelectorAll(".view-btn");

        // Helper function to format date to "Month Day, Year"
        function formatDate(dateString) {
            if (!dateString) return ""; // Return empty if no date is provided
            const date = new Date(dateString);
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return date.toLocaleDateString('en-US', options);
        }

        // Loop through each button and add event listener
        viewModalButtons.forEach(button => {
            button.addEventListener("click", function () {
                // Populate modal fields with button attributes
                document.getElementById("student_type").value = button.getAttribute("fetch-student-type");
                document.getElementById("tuition_type").value = button.getAttribute("fetch-tuition-type");
                document.getElementById("course").value = button.getAttribute("fetch-course");
                document.getElementById("year_level").value = button.getAttribute("fetch-year-level");
                document.getElementById("fee_for").value = button.getAttribute("fetch-fee-for");
                document.getElementById("amountt").value = button.getAttribute("fetch-amountt");
                document.getElementById("event_date").value = `${button.getAttribute("fetch-event-start")} - ${button.getAttribute("fetch-event-end")}`;
                document.getElementById("due_date").value = button.getAttribute("fetch-due-date");

                // Format event date and due date
                const eventStart = formatDate(button.getAttribute("fetch-event-start"));
                const eventEnd = formatDate(button.getAttribute("fetch-event-end"));
                document.getElementById("event_date").value = `${eventStart} - ${eventEnd}`;

                document.getElementById("due_date").value = formatDate(button.getAttribute("fetch-due-date"));

                // Open modal
                viewModal.style.display = "block";
            });
        });

        // Close the modal
        if (closeViewModalButton) {
            closeViewModalButton.addEventListener("click", function () {
                viewModal.style.display = "none";
            });
        }

        // Close modal when clicking outside
        window.addEventListener("click", function (event) {
            if (event.target === viewModal) {
                viewModal.style.display = "none";
            }
        });
    });
</script>


<div id="editTuitionModal" class="edit-tuition-modal" style="display:none;">
    <div class="edit-tuition-modal-content">
        <div class="edit-tuition-modal-header">
            <span class="close">&times;</span>
            <h2>Edit Tuition Fee</h2>
        </div>
        <form id="edittuitionForm" method="POST" action="add_controller.php">
            <input type="hidden" id="edit-tuition-hidden" name="tuition_old">

            <div class="view-title">
                <label for="edit-tuition">Fee For:</label>
                <input type="text" id="edit-tuition" name="fee_for" readonly>
            </div>

            <div class="event-date-con">
                <div class="event-date">
                    <label for="edit-event-start-tuition">Event Start Date:</label>
                    <input type="date" id="edit-event-start-tuition" name="event_date_start" required>
                </div>

                <div class="event-date">
                    <label for="edit-event-end-tuition">Event End Date:</label>
                    <input type="date" id="edit-event-end-tuition" name="event_date_end" required>
                </div>
            </div>
            
            <div class="deadline">
                <label for="edit-deadline-tuition">Deadline:</label>
                <input type="date" id="edit-deadline-tuition" name="due_date" required>
            </div>

            <div class="button-con">
                <!-- <button type="submit">Save</button>Changes -->
                
                <button type="submit" class="save-button">
                    <p>Save</p>
                    <img src="../../img/save-icon.png" alt="save" width=25px>
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const editButtons = document.querySelectorAll(".edit-btn");
        const modal = document.getElementById("editModal");
        const closeModal = document.querySelector(".close");

        const editTuitionButtons = document.querySelectorAll(".edit-tuition-btn");
        const tuitionModal = document.getElementById("editTuitionModal");

        // Edit Tuition
        editTuitionButtons.forEach(button => {
            button.addEventListener("click", function () {
                document.getElementById("edit-tuition").value = this.dataset.feeFor;
                document.getElementById("edit-tuition-hidden").value = this.dataset.feeFor;
                document.getElementById("edit-event-start-tuition").value = this.dataset.eventStart;
                document.getElementById("edit-event-end-tuition").value = this.dataset.eventEnd;
                document.getElementById("edit-deadline-tuition").value = this.dataset.dueDate;

                tuitionModal.style.display = "block";
            });
        });

        closeModal.addEventListener("click", function () {
            modal.style.display = "none";
        });

        tuitionModal.querySelector(".close").addEventListener("click", function () {
            tuitionModal.style.display = "none";
        });

        window.addEventListener("click", function (e) {
            if (e.target === modal) modal.style.display = "none";
            if (e.target === tuitionModal) tuitionModal.style.display = "none";
        });
    });
</script>


    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="delete-modal">
        <div class="delete-modal-content">
            <!-- <h2>Confirm Deletion</h2> -->
            <div class="par">
                <p>Are you sure you want to delete this fee?</p>
            </div>
            <form id="deleteForm" method="GET" action="">
                <input type="hidden" name="delete_fee_for" id="deleteFeeFor">
                <button type="submit" class="yes-button">Yes</button><!-- Delete -->
                <button type="button" onclick="closeModal()" class="no-button">No</button><!-- Cancel -->
            </form>
        </div>
    </div>

    <script>
        function confirmDelete(feeFor) {
            document.getElementById('deleteFeeFor').value = feeFor;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal if clicked outside the content
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>