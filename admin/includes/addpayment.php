<div class="modal-overlay" id="modal-overlay">
    <div class="modal-container">
        <form method="POST" action="">
            <input type="hidden" name="form_type" value="specific_form">
            <div class="add-fees-container">
                <div class="modal-header">
                    <label for="fees-d">Add New Fees</label>
                    <span class="close-modal" id="close-modal">&times;</span>
                </div>
                <div class="fee-details">
                    <div class="fee-details2">
                        <div class="level">
                            <label for="student-type">Student Type:</label>  
                            <select id="student-type" name="student-type" required>
                                <option value="" disabled selected>--Select Student Type--</option>
                                <option value="All">All Student Type</option>
                                <option value="regular">Regular</option>
                                <option value="irregular">Irregular</option>
                            </select>
                        </div>
                        <div class="level">
                            <label for="tuition-type">Tuition Type:</label> 
                            <select id="tuition-type" name="tuition-type" required>
                                <option value="" disabled selected>--Select Tuition Type--</option>
                                <option value="All">All Tuition Type</option>
                                <option value="scholar">ASAP Scholar</option>
                                <option value="free">Free Tuition</option>
                            </select>
                        </div>
                    </div>
                    <div class="fee-details2">
                        <div class="level">
                            <label for="course">Course:</label>  
                            <div class="custom-dropdown" id="course-dropdown">
                                <div class="selected-options" id="selected-courses">--Select Course--</div>
                                <div class="dropdown-options">
                                    <div class="option">
                                        <input type="checkbox" id="course-all" value="All">
                                        <label for="course-all">All Courses</label>
                                    </div>
                                    <div class="option">
                                        <input type="checkbox" id="course-bsit" value="BSIT">
                                        <label for="course-bsit">BSIT</label>
                                    </div>
                                    <div class="option">
                                        <input type="checkbox" id="course-bscs" value="BSCS">
                                        <label for="course-bscs">BSCS</label>
                                    </div>
                                    <div class="option">
                                        <input type="checkbox" id="course-bsce" value="BSCE">
                                        <label for="course-bsce">BSCE</label>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="course" id="course-hidden">
                        </div>

                        <div class="level">
                            <label for="year-level">Year Level:</label>  
                            <div class="custom-dropdown" id="year-level-dropdown">
                                <div class="selected-options" id="selected-yearlevels">--Select Year Level--</div>
                                <div class="dropdown-options">
                                    <div class="option">
                                        <input type="checkbox" id="yearlevel-all" value="All">
                                        <label for="yearlevel-all">All Year Levels</label>
                                    </div>
                                    <div class="option">
                                        <input type="checkbox" id="yearlevel-1" value="1">
                                        <label for="yearlevel-1">First Year</label>
                                    </div>
                                    <div class="option">
                                        <input type="checkbox" id="yearlevel-2" value="2">
                                        <label for="yearlevel-2">Second Year</label>
                                    </div>
                                    <div class="option">
                                        <input type="checkbox" id="yearlevel-3" value="3">
                                        <label for="yearlevel-3">Third Year</label>
                                    </div>
                                    <div class="option">
                                        <input type="checkbox" id="yearlevel-4" value="4">
                                        <label for="yearlevel-4">Fourth Year</label>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="yearlevel" id="yearlevel-hidden">
                        </div>
                    </div>
                    <div class="fee-details2">
                        <div class="level">
                            <label for="fee-for">Payment Title:</label>  
                            <input type="text" id="fee-for" name="fee-for" required>
                        </div>
                        <div class="level">
                            <label for="amount">Amount:</label> 
                            <input type="number" id="amount" name="amount" step="0.01" required>
                        </div>
                    </div>
                    <div class="fee-details2">
                        <div class="level">
                            <label for="event-start">Event Start Date:</label>
                            <input type="date" id="event-start" name="event-start" required>
                        </div>
                        <div class="level">
                            <label for="deadline">Deadline:</label> 
                            <input type="date" id="deadline" name="deadline" required>
                        </div>
                    </div>
                    
                    <div class="add-button-con">
                        <button type="submit" class="list-btn">
                            <p>Save</p>
                            <img src="../../img/save-icon.png" alt="save" width=25px>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>


    <script>
        const modalOverlay = document.getElementById("modal-overlay");
        const addFeesButton = document.getElementById("add-fees-button");
        const closeModalButton = document.getElementById("close-modal");

        addFeesButton.addEventListener("click", function () {
            modalOverlay.style.display = "block";
        });

        closeModalButton.addEventListener("click", function () {
            modalOverlay.style.display = "none";
        });

        
        // Close modal when clicking the close button (×)
        document.getElementById("close-modal").addEventListener("click", function () {
            modalOverlay.style.display = "none";
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const eventStart = document.getElementById("event-start");
            const deadline = document.getElementById("deadline");

            deadline.addEventListener("change", function () {
                if (eventStart.value) {
                    const startDate = new Date(eventStart.value);
                    const deadlineDate = new Date(deadline.value);

                    if (deadlineDate <= startDate) {
                        alert("Deadline must be a date after the Event Start Date.");
                        deadline.value = ""; // Clear the invalid date
                    }
                } else {
                    alert("Please select the Event Start Date first.");
                    deadline.value = ""; // Clear the deadline if no start date
                }
            });

            eventStart.addEventListener("change", function() {
                // When the event start date changes, ensure the deadline's min attribute is updated
                deadline.min = eventStart.value;
                // Also clear the deadline if it's now before the new start date
                if (deadline.value && new Date(deadline.value) <= new Date(eventStart.value)) {
                    deadline.value = "";
                }
            });
        });
    </script>
    
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const courseDropdown = document.getElementById("course-dropdown");
            const selectedCourses = document.getElementById("selected-courses");
            const courseCheckboxes = courseDropdown.querySelectorAll("input[type='checkbox']");
            const courseHiddenInput = document.getElementById("course-hidden");

            const yearLevelDropdown = document.getElementById("year-level-dropdown");
            const selectedYearLevels = document.getElementById("selected-yearlevels");
            const yearLevelCheckboxes = yearLevelDropdown.querySelectorAll("input[type='checkbox']");
            const yearLevelHiddenInput = document.getElementById("yearlevel-hidden");

            // Function to toggle checkbox when clicking on row
            function toggleCheckbox(event) {
                const checkbox = event.currentTarget.querySelector("input[type='checkbox']");
                if (checkbox) {
                    checkbox.checked = !checkbox.checked; // Toggle checkbox state
                    checkbox.dispatchEvent(new Event("change")); // Trigger change event manually
                }
            }

            // Update course selection
            function updateCourseSelection() {
                const selectedCoursesValues = Array.from(courseCheckboxes)
                    .filter(checkbox => checkbox.checked)
                    .map(checkbox => checkbox.value);

                selectedCourses.textContent = selectedCoursesValues.length > 0 ? selectedCoursesValues.join(", ") : "--Select Course--";
                courseHiddenInput.value = selectedCoursesValues.join(",");
            }

            // Update year level selection
            function updateYearLevelSelection() {
                const selectedYearLevelsValues = Array.from(yearLevelCheckboxes)
                    .filter(checkbox => checkbox.checked)
                    .map(checkbox => checkbox.value);

                selectedYearLevels.textContent = selectedYearLevelsValues.length > 0 ? selectedYearLevelsValues.join(", ") : "--Select Year Level--";
                yearLevelHiddenInput.value = selectedYearLevelsValues.join(",");
            }

            // Handle checkbox selection for course
            courseCheckboxes.forEach(checkbox => {
                checkbox.addEventListener("change", function () {
                    if (this.id === "course-all" && this.checked) {
                        courseCheckboxes.forEach(cb => {
                            if (cb !== this) cb.checked = false;
                        });
                    } else if (this.checked) {
                        document.getElementById("course-all").checked = false;
                    }
                    updateCourseSelection();
                });

                // Add row click event listener
                checkbox.closest("label, div").addEventListener("click", toggleCheckbox);
            });

            // Handle checkbox selection for year level
            yearLevelCheckboxes.forEach(checkbox => {
                checkbox.addEventListener("change", function () {
                    if (this.id === "yearlevel-all" && this.checked) {
                        yearLevelCheckboxes.forEach(cb => {
                            if (cb !== this) cb.checked = false;
                        });
                    } else if (this.checked) {
                        document.getElementById("yearlevel-all").checked = false;
                    }
                    updateYearLevelSelection();
                });

                // Add row click event listener
                checkbox.closest("label, div").addEventListener("click", toggleCheckbox);
            });

            // Toggle course dropdown visibility
            selectedCourses.addEventListener("click", function () {
                courseDropdown.querySelector(".dropdown-options").classList.toggle("open");
            });

            // Toggle year level dropdown visibility
            selectedYearLevels.addEventListener("click", function () {
                yearLevelDropdown.querySelector(".dropdown-options").classList.toggle("open");
            });

            // Close dropdown when clicking outside
            document.addEventListener("click", function (e) {
                if (!courseDropdown.contains(e.target)) {
                    courseDropdown.querySelector(".dropdown-options").classList.remove("open");
                }
                if (!yearLevelDropdown.contains(e.target)) {
                    yearLevelDropdown.querySelector(".dropdown-options").classList.remove("open");
                }
            });
        });
    </script>

<style>
    /* Dropdown styles */
    .custom-dropdown {
        position: relative;
        display: inline-block;
        width: 100%;
        border: 1px solid #000;
    }

    .selected-options {
        padding: 10px;
        background-color:rgb(255, 255, 255);
        cursor: pointer;
        /* border: 1px solid #ccc; */
        font-size: 14px;
    }

    .dropdown-options {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background-color: #000;
        border: 1px solid #ccc;
        display: none;
        z-index: 1;
        max-height: 200px;
        overflow-y: auto;
    }

    .dropdown-options.open {
        display: block;
    }

    .option {
        padding: 8px;
        cursor: pointer;
    }

    .option input {
        margin-right: 8px;
    }
    </style>