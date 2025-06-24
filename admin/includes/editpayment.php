
<div id="editModal" class="EditModal">
    <div class="Edit-Modal-content">
        <div class="edit-modal-content-header">
            <h2>Edit Payment</h2>
            <span class="close-modal" id="closeModal">&times;</span>
        </div>
        <form id="editForm" action="" method="POST"><!-- ../components/add_content.php -->
            <input type="hidden" name="original_fee_for" id="original_fee_for">

            <div class="group1">
                <div class="g-box">
                    <label>Student Type:</label>
                    <!-- <input type="text" name="student_type" id="edit_student_type" required> -->
                    <select name="student_type" id="edit_student_type" required>
                        <option value="All">All</option>
                        <option value="regular">Regular</option>
                        <option value="irregular">Irregular</option>
                    </select>
                </div>
                    
                <div class="g-box">
                    <label>Tuition Type:</label>
                    <!-- <input type="text" name="tuition_type" id="edit_tuition_type" required> -->
                    <select name="tuition_type" id="edit_tuition_type" required>
                        <option value="All">All</option>
                        <option value="scholar">ASAP Scholar</option>
                        <option value="free">Free Tuition</option>
                    </select>
                </div>
            </div>
            <div class="group1" style="margin-bottom: 10px;">
                <div class="g-box">
                    <label>Course:</label>
                    <div class="custom-dropdown" id="edit_course_dropdown">
                        <div class="selected-options" id="edit_course">--Select Course--</div>
                        <div class="dropdown-options">
                            <div class="option">
                                <input type="checkbox" id="edit_course_all" value="All">
                                <label for="edit_course_all">All Courses</label>
                            </div>
                            <div class="option">
                                <input type="checkbox" id="edit_course_bsit" value="BSIT">
                                <label for="edit_course_bsit">BSIT</label>
                            </div>
                            <div class="option">
                                <input type="checkbox" id="edit_course_bscs" value="BSCS">
                                <label for="edit_course_bscs">BSCS</label>
                            </div>
                            <div class="option">
                                <input type="checkbox" id="edit_course_bsce" value="BSCE">
                                <label for="edit_course_bsce">BSCE</label>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="course" id="edit_course_hidden">
                </div>
                    
                <div class="g-box">
                    <label>Year Level:</label>
                    <div class="custom-dropdown" id="edit_year_level_dropdown">
                        <div class="selected-options" id="edit_year_level">--Select Year Level--</div>
                        <div class="dropdown-options">
                            <div class="option">
                                <input type="checkbox" id="edit_yearlevel_all" value="All">
                                <label for="edit_yearlevel_all">All Year Levels</label>
                            </div>
                            <div class="option">
                                <input type="checkbox" id="edit_yearlevel_1" value="1">
                                <label for="edit_yearlevel_1">First Year</label>
                            </div>
                            <div class="option">
                                <input type="checkbox" id="edit_yearlevel_2" value="2">
                                <label for="edit_yearlevel_2">Second Year</label>
                            </div>
                            <div class="option">
                                <input type="checkbox" id="edit_yearlevel_3" value="3">
                                <label for="edit_yearlevel_3">Third Year</label>
                            </div>
                            <div class="option">
                                <input type="checkbox" id="edit_yearlevel_4" value="4">
                                <label for="edit_yearlevel_4">Fourth Year</label>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="year_level" id="edit_yearlevel_hidden">
                </div>
            </div>

            <label>Fee For:</label>
            <input type="text" name="fee_for" id="edit_fee_for" required>

            <label>Amount:</label>
            <input type="number" name="amount" id="edit_amount" required>

            <div class="group1">
                <div class="g-box">
                    <label>Event Start:</label>
                    <input type="date" name="event_start" id="edit_event_start">
                </div>
                <!-- <div class="g-box">
                    <label>Event End:</label>
                    <input type="date" name="event_end" id="edit_event_end">
                </div> -->
                <div class="g-box">
                    <label>Due Date:</label>
                    <input type="date" name="due_date" id="edit_due_date">
                </div>
            </div>
            
            <div class="edit-button-con">
                <button type="submit" class="save-button">
                    <p>Save</p>
                    <img src="../../img/save-icon.png" alt="save" width=25px>
                </button>
                <button type="button" id="closeModal" class="cancel-button">
                    <p>Cancel</p>
                    <img src="../../img/cancel-icon.png" alt="save" width=25px>
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('editModal');
        const closeModal = document.getElementById('closeModal');
        const editButtons = document.querySelectorAll('.edit-btn');

        editButtons.forEach(button => {
            button.addEventListener('click', () => {
                const studentType = button.getAttribute('data-student-type');
                const tuitionType = button.getAttribute('data-tuition-type');
                const course = button.getAttribute('data-course');
                const yearLevel = button.getAttribute('data-year-level');
                const feeFor = button.getAttribute('data-fee-for');
                const amount = button.getAttribute('data-amount');
                const eventStart = button.getAttribute('data-event-start');
                const dueDate = button.getAttribute('data-due-date');

                document.getElementById('original_fee_for').value = feeFor;
                document.getElementById('edit_student_type').value = studentType;
                document.getElementById('edit_tuition_type').value = tuitionType;

                document.getElementById('edit_fee_for').value = feeFor;
                document.getElementById('edit_amount').value = amount;
                document.getElementById('edit_event_start').value = eventStart;
                document.getElementById('edit_due_date').value = dueDate;

                //Populate Custom Dropdown Checkboxes
                if (course) {
                    const courses = course.split(',');
                    const courseCheckboxes = document.querySelectorAll('#edit_course_dropdown input[type="checkbox"]');
                    courseCheckboxes.forEach(checkbox => {
                        checkbox.checked = courses.includes(checkbox.value);
                    });
                    updateCourseSelection(); // Update the hidden input and selected text
                }
                if (yearLevel) {
                    const yearLevels = yearLevel.split(',');
                    const yearLevelCheckboxes = document.querySelectorAll('#edit_year_level_dropdown input[type="checkbox"]');
                    yearLevelCheckboxes.forEach(checkbox => {
                        checkbox.checked = yearLevels.includes(checkbox.value);
                    });
                    updateYearLevelSelection(); // Update the hidden input and selected text
                }

                modal.style.display = 'block';
            });
        });

        closeModal.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }

            // Hide dropdowns if clicked outside
            if (!e.target.closest('#edit_course_dropdown')) {
                courseDropdown.querySelector('.dropdown-options').classList.remove('open');
            }
            if (!e.target.closest('#edit_year_level_dropdown')) {
                yearLevelDropdown.querySelector('.dropdown-options').classList.remove('open');
            }
        });

        const courseDropdown = document.getElementById("edit_course_dropdown");
        const selectedCourses = document.getElementById("edit_course");
        const courseCheckboxes = courseDropdown.querySelectorAll("input[type='checkbox']");
        const courseHiddenInput = document.getElementById("edit_course_hidden");

        const yearLevelDropdown = document.getElementById("edit_year_level_dropdown");
        const selectedYearLevels = document.getElementById("edit_year_level");
        const yearLevelCheckboxes = yearLevelDropdown.querySelectorAll("input[type='checkbox']");
        const yearLevelHiddenInput = document.getElementById("edit_yearlevel_hidden");

        function toggleCheckbox(event) {
            const checkbox = event.currentTarget.querySelector("input[type='checkbox']");
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event("change"));
            }
        }

        function updateCourseSelection() {
            const selectedCoursesValues = Array.from(courseCheckboxes)
                .filter(checkbox => checkbox.checked)
                .map(checkbox => checkbox.value);

            selectedCourses.textContent = selectedCoursesValues.length > 0 ? selectedCoursesValues.join(", ") : "--Select Course--";
            courseHiddenInput.value = selectedCoursesValues.join(",");
        }

        function updateYearLevelSelection() {
            const selectedYearLevelsValues = Array.from(yearLevelCheckboxes)
                .filter(checkbox => checkbox.checked)
                .map(checkbox => checkbox.value);

            selectedYearLevels.textContent = selectedYearLevelsValues.length > 0 ? selectedYearLevelsValues.join(", ") : "--Select Year Level--";
            yearLevelHiddenInput.value = selectedYearLevelsValues.join(",");
        }

        courseCheckboxes.forEach(checkbox => {
            checkbox.addEventListener("change", function () {
                if (this.id === "edit_course_all" && this.checked) {
                    courseCheckboxes.forEach(cb => {
                        if (cb !== this) cb.checked = false;
                    });
                } else if (this.checked) {
                    document.getElementById("edit_course_all").checked = false;
                }
                updateCourseSelection();
            });
            checkbox.closest("label, div").addEventListener("click", toggleCheckbox);
        });

        yearLevelCheckboxes.forEach(checkbox => {
            checkbox.addEventListener("change", function () {
                if (this.id === "edit_yearlevel_all" && this.checked) {
                    yearLevelCheckboxes.forEach(cb => {
                        if (cb !== this) cb.checked = false;
                    });
                } else if (this.checked) {
                    document.getElementById("edit_yearlevel_all").checked = false;
                }
                updateYearLevelSelection();
            });
            checkbox.closest("label, div").addEventListener("click", toggleCheckbox);
        });

        selectedCourses.addEventListener("click", function () {
            courseDropdown.querySelector(".dropdown-options").classList.toggle("open");
        });

        selectedYearLevels.addEventListener("click", function () {
            yearLevelDropdown.querySelector(".dropdown-options").classList.toggle("open");
        });

    });
</script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const eventStart = document.getElementById("edit_event_start");
            const duedate = document.getElementById("edit_due_date");

            duedate.addEventListener("change", function () {
                if (eventStart.value) {
                    const startDate = new Date(eventStart.value);
                    const deadlineDate = new Date(duedate.value);

                    if (deadlineDate <= startDate) {
                        alert("Deadline must be a date after the Event Start Date.");
                        duedate.value = ""; // Clear the invalid date
                    }
                } else {
                    alert("Please select the Event Start Date first.");
                    duedate.value = ""; // Clear the duedate if no start date
                }
            });

            eventStart.addEventListener("change", function() {
                // When the event start date changes, ensure the duedate's min attribute is updated
                duedate.min = eventStart.value;
                // Also clear the duedate if it's now before the new start date
                if (duedate.value && new Date(duedate.value) <= new Date(eventStart.value)) {
                    duedate.value = "";
                }
            });
        });
    </script>