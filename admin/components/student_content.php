<?php
// Connect to the database
require '../../database/db.php'; // Ensure your database connection is correct

$message = null;

// Capture the search term
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination Variables
$limit = 12; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page, default is 1
$offset = ($page - 1) * $limit; // Offset for SQL query


// Delete Account Handling
if (isset($_GET['delete_student_number'])) {
    $deleteStudentNumber = $_GET['delete_student_number'];

    try {
        // Start a transaction to ensure atomicity
        $pdo->beginTransaction();

        // Delete from student_accounts
        $deleteStudentQuery = "DELETE FROM student_accounts WHERE student_number = :student_number";
        $deleteStudentStmt = $pdo->prepare($deleteStudentQuery);
        $deleteStudentStmt->bindParam(':student_number', $deleteStudentNumber, PDO::PARAM_STR);
        $deleteStudentStmt->execute();

        // Delete from acts_ops_login
        $deleteLoginQuery = "DELETE FROM acts_ops_login WHERE student_number = :student_number";
        $deleteLoginStmt = $pdo->prepare($deleteLoginQuery);
        $deleteLoginStmt->bindParam(':student_number', $deleteStudentNumber, PDO::PARAM_STR);
        $deleteLoginStmt->execute();

        // Delete from semester_fees
        $deleteLoginQuery = "DELETE FROM semester_fees WHERE student_number = :student_number";
        $deleteLoginStmt = $pdo->prepare($deleteLoginQuery);
        $deleteLoginStmt->bindParam(':student_number', $deleteStudentNumber, PDO::PARAM_STR);
        $deleteLoginStmt->execute();

        // Delete from student_payments
        $deleteLoginQuery = "DELETE FROM student_payments WHERE student_number = :student_number";
        $deleteLoginStmt = $pdo->prepare($deleteLoginQuery);
        $deleteLoginStmt->bindParam(':student_number', $deleteStudentNumber, PDO::PARAM_STR);
        $deleteLoginStmt->execute();

        // Commit the transaction
        $pdo->commit();

        $_SESSION['delete_success_message'] = "Fee successfully deleted!";
        echo "<script>
            var successMessage = document.createElement('div');
            successMessage.style.position = 'fixed';
            successMessage.style.top = '20px';
            successMessage.style.left = '50%';
            successMessage.style.transform = 'translateX(-50%)';
            successMessage.style.padding = '15px';
            successMessage.style.backgroundColor = '#4CAF50';
            successMessage.style.color = '#fff';
            successMessage.style.fontSize = '16px';
            successMessage.style.borderRadius = '5px';
            successMessage.style.zIndex = '9999';
            successMessage.innerText = '" . addslashes($_SESSION['delete_success_message']) . "';
            document.body.appendChild(successMessage);

            setTimeout(function() {
                window.location.href = 'student_controller.php';
            }, 500);
        </script>";

        unset($_SESSION['delete_success_message']);
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        $message = "Error deleting account: " . $e->getMessage();
    }
}

// Query to get the total number of students
$totalQuery = "SELECT COUNT(*) FROM student_accounts WHERE 1";
if ($search != '') {
    $totalQuery .= " AND (student_number LIKE :search 
                        OR student_type LIKE :search 
                        OR tuition_type LIKE :search 
                        OR firstname LIKE :search 
                        OR middlename LIKE :search 
                        OR lastname LIKE :search 
                        OR course LIKE :search 
                        OR year_level LIKE :search 
                        OR section LIKE :search 
                        OR semester LIKE :search)";
}
$totalStmt = $pdo->prepare($totalQuery);
if ($search != '') {
    $searchParam = "%" . $search . "%"; // For LIKE query matching
    $totalStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
}
$totalStmt->execute();
$totalStudents = $totalStmt->fetchColumn();

// Calculate total pages
$totalPages = ceil($totalStudents / $limit);

$query = "SELECT sa.id, sa.student_number, sa.student_type, sa.tuition_type, sa.firstname, sa.middlename, sa.lastname, sa.course, sa.year_level, sa.section, sa.semester,
                 sa.total_tuition_fee, sa.tuition_fee_discount, sa.balance_to_be_paid, sa.down_payment, sa.total_balance, sa.remaining_balance_to_pay, sa.profile_image,
                 
                 MAX(CASE WHEN sf.fee_for = 'Prelim' THEN sf.amount ELSE 0 END) AS prelim_fee,
                 MAX(CASE WHEN sf.fee_for = 'Midterm' THEN sf.amount ELSE 0 END) AS midterm_fee,
                 MAX(CASE WHEN sf.fee_for = 'Prefinal' THEN sf.amount ELSE 0 END) AS prefinal_fee,
                 MAX(CASE WHEN sf.fee_for = 'Final' THEN sf.amount ELSE 0 END) AS final_fee,
                 
                 MAX(CASE WHEN sf.fee_for = 'Prelim' THEN sf.status ELSE 0 END) AS prelim_status,
                 MAX(CASE WHEN sf.fee_for = 'Midterm' THEN sf.status ELSE 0 END) AS midterm_status,
                 MAX(CASE WHEN sf.fee_for = 'Prefinal' THEN sf.status ELSE 0 END) AS prefinal_status,
                 MAX(CASE WHEN sf.fee_for = 'Final' THEN sf.status ELSE 0 END) AS final_status,
                 sa.email

          FROM student_accounts sa
          LEFT JOIN semester_fees sf ON sa.student_number = sf.student_number
          WHERE 1";
if ($search != '') {
    $query .= " AND (sa.student_number LIKE :search 
                        OR sa.student_type LIKE :search 
                        OR sa.tuition_type LIKE :search 
                        OR sa.lastname LIKE :search 
                        OR sa.middlename LIKE :search 
                        OR sa.firstname LIKE :search 
                        OR sa.course LIKE :search 
                        OR sa.year_level LIKE :search 
                        OR sa.section LIKE :search 
                        OR sa.semester LIKE :search)";
}
$query .= " GROUP BY sa.student_number ORDER BY sa.id DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

if ($search != '') {
    $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
}

$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Account</title>
    <link rel="stylesheet" href="../css/content/student.css">
</head>
<body>
    <div class="reports-container">
        <div class="reports-header">
            <h2>Student</h2>
            <div class="button-header">
                <button id="openModalButton" style="margin-right: 10px;" class="add-Btn">
                    <div class="sign"><img src="../../img/add-s.png" alt="Add" width="26" height="26" style="margin-right: 5px;"></div>
                    <div class="text">Add Student</div>
                </button>
                <button id="openUpdateModalButton" style="margin-right: 10px;" class="update-Btn" title="For New Semester">
                    <div class="sign"><img src="../../img/accountUp.png" alt="Add" width="32" height="32" style="margin-right: 5px;"></div>
                    <div class="text">Update Student</div>
                </button>
                <a href="setting_controller.php">
                    <button style="margin-right: 10px;" class="setting-Btn">
                        <div class="sign"><img src="../../img/settingIcon.png" alt="Add" width="26" height="26" style="margin-right: 5px;"></div>
                        <div class="text">Setting</div>
                    </button>
                </a>
            </div>
        </div>

        <div class="search-field">        
            <!-- Search filter -->
            <form method="GET" action="">
                <div class="search-container">
                    <input type="text" name="search" class="search" placeholder="Search here" value="<?= htmlspecialchars($search) ?>" />
                    <button type="button" class="clear-btn" onclick="clearSearch()">×</button>
                </div>
                <button type="submit" class="search-button">
                    <label for="">Search</label>
                </button>
            </form>
        </div>
        <br>

        <!-- <?php if ($message): ?>
            <div style="color: <?= strpos($message, 'Error') === 0 ? 'red' : 'green' ?>;"><?= $message ?></div>
        <?php endif; ?> -->

        <div class="student-list-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student No.</th>
                        <th>Student Type</th>
                        <!-- <th>Tuition Status</th> -->
                        <!-- <th>Firstname</th>
                        <th>Middlename</th>
                        <th>Lastname</th> -->
                        <th>Name</th>
                        <th>Course</th>
                        <th>Year Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr 
                            onclick="showStudentDetails(this)"
                            data-student-type="<?= htmlspecialchars($student['student_type']) ?>"
                            data-tuition-status="<?= htmlspecialchars($student['tuition_type']) ?>"
                            data-student-number="<?= htmlspecialchars($student['student_number']) ?>"
                            data-firstname="<?= htmlspecialchars($student['firstname']) ?>"
                            data-middlename="<?= htmlspecialchars($student['middlename']) ?>"
                            data-lastname="<?= htmlspecialchars($student['lastname']) ?>"
                            data-email="<?= htmlspecialchars($student['email']) ?>"
                            data-course="<?= htmlspecialchars($student['course']) ?>"
                            data-year-level="<?= htmlspecialchars($student['year_level']) ?>"
                            data-section="<?= htmlspecialchars($student['section']) ?>"
                            data-semester="<?= htmlspecialchars($student['semester']) ?>"
                            data-total-tuition-fee="<?= htmlspecialchars($student['total_tuition_fee']) ?>"
                            data-tuition-fee-discount="<?= htmlspecialchars($student['tuition_fee_discount']) ?>"
                            data-balance-to-be-paid="<?= htmlspecialchars($student['balance_to_be_paid']) ?>"
                            data-down-payment="<?= htmlspecialchars($student['down_payment']) ?>"
                            data-profile-image="<?= htmlspecialchars($student['profile_image']) ?>"
                            data-remaining-balance="<?= htmlspecialchars($student['remaining_balance_to_pay']) ?>"
                            data-total-balance="<?= htmlspecialchars($student['total_balance']) ?>"
                            data-prelim-fee="<?= htmlspecialchars($student['prelim_fee']) ?>"
                            data-midterm-fee="<?= htmlspecialchars($student['midterm_fee']) ?>"
                            data-prefinal-fee="<?= htmlspecialchars($student['prefinal_fee']) ?>"
                            data-final-fee="<?= htmlspecialchars($student['final_fee']) ?>"
                            data-prelim-status="<?= htmlspecialchars($student['prelim_status']) ?>"
                            data-midterm-status="<?= htmlspecialchars($student['midterm_status']) ?>"
                            data-prefinal-status="<?= htmlspecialchars($student['prefinal_status']) ?>"
                            data-final-status="<?= htmlspecialchars($student['final_status']) ?>">

                            <td><?= htmlspecialchars($student['student_number']) ?></td>
                            <td style="text-transform: capitalize;"><?= htmlspecialchars($student['student_type']) ?></td>
                            <!-- <td><?= htmlspecialchars($student['tuition_type']) ?></td> -->
                            <td><?= htmlspecialchars($student['firstname']) ?>  <?= htmlspecialchars($student['middlename']) ?>  <?= htmlspecialchars($student['lastname']) ?></td>
                            <!-- <td><?= htmlspecialchars($student['middlename']) ?></td>
                            <td><?= htmlspecialchars($student['lastname']) ?></td> -->
                            <td><?= htmlspecialchars($student['course']) ?></td>
                            <td><?= htmlspecialchars($student['year_level']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination Links -->
            <div class="pagination">
                <?php if ($totalPages > 1): ?>
                    <!-- Display current page of total pages -->
                    <div class="page-info">
                        Page <?= $page ?> of <?= $totalPages ?>
                    </div>
                    <ul>
                        <?php if ($page > 1): ?>
                            <li><a href="?page=<?= $page - 1 ?>">Previous</a></li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li>
                                <a href="?page=<?= $i ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li><a href="?page=<?= $page + 1 ?>">Next</a></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="student-details" id="studentDetailsContainer" style="display: none;">
            <div class="close-button">
                <label for="account">Student Details</label>
                <button id="closeDetailsButton">Close</button>
            </div>
            <div class="filter-container">
                <div class="filtercon">
                    <div class="image-label">
                        <img id="studentProfileImage" src="" alt="Profile Image" style="width: 150px; height: 150px; border-radius: 50%; margin-top: 10px;">
                    </div>
                    <div class="rightside">
                        <div class="filter1">
                            <div class="name-label">
                                <label for="lastname">Student Number</label><br>
                                <input type="number" name="student_number" readonly>
                            </div>
                            <div class="name-label">
                                <label for="student_type">Student Type</label><br>
                                <input type="text" name="student_type" readonly style="text-transform: capitalize;">
                            </div>
                            <div class="name-label">
                                <label for="tuition_type">Tuition Type</label><br>
                                <input type="text" name="tuition_type" readonly>
                            </div>
                        </div>
                        <!-- <div class="filter1">
                            <div class="name-label">
                                <label for="firstname">First Name</label><br>
                                <input type="text" name="firstname" readonly>
                            </div>
                            <div class="name-label">
                                <label for="middlename">Middle Name</label><br>
                                <input type="text" name="middlename" readonly>
                            </div>
                            <div class="name-label">
                                <label for="lastname">Last Name</label><br>
                                <input type="text" name="lastname" readonly>
                            </div>
                        </div> -->
                        <div class="filter1">
                            <div class="name-label" style="display:none;">
                                <label for="firstname">First Name</label><br>
                                <input type="text" name="firstname" readonly>
                            </div>
                            <div class="name-label" style="display:none;">
                                <label for="middlename">Middle Name</label><br>
                                <input type="text" name="middlename" readonly>
                            </div>
                            <div class="name-label" style="display:none;">
                                <label for="lastname">Last Name</label><br>
                                <input type="text" name="lastname" readonly>
                            </div>

                            <div class="name-label">
                                <label for="fullname">Full Name</label><br>
                                <input type="text" name="fullname" readonly>
                            </div>
                            <div class="name-label">
                                <label for="email">Email</label><br>
                                <input type="text" name="email" readonly>
                            </div>
                        </div>
                        <div class="filter2">
                            <div class="course-label">
                                <label for="course">Course</label><br>
                                <input type="text" name="course" readonly>
                            </div>
                            <div class="year-label">
                                <label for="yearlevel">Year Level</label><br>
                                <input type="text" name="year_level" readonly>
                            </div>
                            <div class="section-label">
                                <label for="section">Section</label><br>
                                <input type="text" name="section" readonly>
                            </div>
                            <div class="semester-label">
                                <label for="semester">Semester</label><br>
                                <input type="text" name="semester" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="action-button">
                <button id="deleteAccountButton">Delete</button>
            </div>

            <div class="balance-details">
                <label for="balance-details">BALANCE DETAILS</label>
                <table class="table-balance">
                    <thead">
                        <tr>
                            <th>Semester Fees</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Tuition Fee</td>
                            <td class="tuition_fee"></td>
                        </tr>
                        <tr>
                            <td>Tuition Fee Discount</td>
                            <td class="discount"></td>
                        </tr>
                        <tr>
                            <td>Balance to be Paid</td>
                            <td class="balance_due"></td>
                        </tr>
                        <tr>
                            <td>Down Payment</td>
                            <td class="down_payment"></td>
                        </tr>
                        <tr>
                            <td>Total Balance</td>
                            <td class="total_balance"></td>
                        </tr>
                    </tbody>
                </table>
                <br>
                <table class="table-remaining">
                    <thead">
                        <tr>
                            <th>Reamining Fees</th>
                            <th>Amount</th>
                            <th> </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Remaining Balance</td>
                            <td class="remaining_balance"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Prelim</td>
                            <td class="prelim"></td>
                            <td class="prelim_status"></td>
                        </tr>
                        <tr>
                            <td>Midterm</td>
                            <td class="midterm"></td>
                            <td class="midterm_status"></td>
                        </tr>
                        <tr>
                            <td>Prefinal</td>
                            <td class="prefinal"></td>
                            <td class="prefinal_status"></td>
                        </tr>
                        <tr>
                            <td>Final</td>
                            <td class="final"></td>
                            <td class="final_status"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            function showStudentDetails(row) {
                // Get the student data from the clicked row
                const studentType = row.getAttribute('data-student-type');
                const tuitionStatus = row.getAttribute('data-tuition-status');
                const studentNumber = row.getAttribute('data-student-number');
                const firstname = row.getAttribute('data-firstname');
                const middlename = row.getAttribute('data-middlename');
                const lastname = row.getAttribute('data-lastname');
                const email = row.getAttribute('data-email');
                const course = row.getAttribute('data-course');
                const yearLevel = row.getAttribute('data-year-level');
                const section = row.getAttribute('data-section');
                const semester = row.getAttribute('data-semester');
                const totalTuitionFee = row.getAttribute('data-total-tuition-fee');
                const tuitionFeeDiscount = row.getAttribute('data-tuition-fee-discount');
                const balanceToBePaid = row.getAttribute('data-balance-to-be-paid');
                const downPayment = row.getAttribute('data-down-payment');
                const totalBalance = row.getAttribute('data-total-balance');
                const remainingBalance = row.getAttribute('data-remaining-balance');
                var profileImage = row.getAttribute("data-profile-image"); 
                
                const tuitionStatusRaw = row.getAttribute('data-tuition-status');
                const tuitionStatusFormatted = tuitionStatusRaw.replace(/_/g, ' '); 
                
                const prelim = row.getAttribute('data-prelim-fee');
                const midterm = row.getAttribute('data-midterm-fee');
                const prefinal = row.getAttribute('data-prefinal-fee');
                const final = row.getAttribute('data-final-fee');
                
                const prelim_status = row.getAttribute('data-prelim-status');
                const midterm_status = row.getAttribute('data-midterm-status');
                const prefinal_status = row.getAttribute('data-prefinal-status');
                const final_status = row.getAttribute('data-final-status');
                
                // Combine the names and set the value of the fullname input
                const fullName = `${firstname} ${middlename} ${lastname}`;
                document.querySelector('#studentDetailsContainer input[name="fullname"]').value = fullName;

                // Hide the student list table
                document.querySelector('.student-list-table').style.display = 'none';

                // Display the student details
                document.getElementById('studentDetailsContainer').style.display = 'block';

                // Set the values in the form fields inside the student details section
                document.querySelector('#studentDetailsContainer input[name="student_type"]').value = studentType;
                // document.querySelector('#studentDetailsContainer input[name="tuition_type"]').value = tuitionStatus;
                document.querySelector('#studentDetailsContainer input[name="tuition_type"]').value = tuitionStatusFormatted;
                document.querySelector('#studentDetailsContainer input[name="student_number"]').value = studentNumber;
                document.querySelector('#studentDetailsContainer input[name="firstname"]').value = firstname;
                document.querySelector('#studentDetailsContainer input[name="middlename"]').value = middlename;
                document.querySelector('#studentDetailsContainer input[name="lastname"]').value = lastname;
                document.querySelector('#studentDetailsContainer input[name="email"]').value = email;
                document.querySelector('#studentDetailsContainer input[name="course"]').value = course;
                document.querySelector('#studentDetailsContainer input[name="year_level"]').value = yearLevel;
                document.querySelector('#studentDetailsContainer input[name="section"]').value = section;
                document.querySelector('#studentDetailsContainer input[name="semester"]').value = semester;

                // Check if profile image exists, if not set to default
                var imageSrc = profileImage ? profileImage : '../../img/profile-default-G.png';  // Replace with your default image path
                // Set the profile image URL
                document.getElementById("studentProfileImage").src = imageSrc;

                // Function to format numbers as Philippine Peso
                function formatCurrency(amount) {
                    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
                }

                // Show the balance details dynamically if needed
                document.querySelector('#studentDetailsContainer .balance-details .tuition_fee').innerText = totalTuitionFee;
                document.querySelector('#studentDetailsContainer .balance-details .discount').innerText = tuitionFeeDiscount;
                document.querySelector('#studentDetailsContainer .balance-details .balance_due').innerText = balanceToBePaid;
                document.querySelector('#studentDetailsContainer .balance-details .down_payment').innerText = downPayment;
                document.querySelector('#studentDetailsContainer .balance-details .total_balance').innerText = totalBalance;
                document.querySelector('#studentDetailsContainer .balance-details .remaining_balance').innerText = remainingBalance;

                document.querySelector('#studentDetailsContainer .balance-details .prelim').innerText = prelim;
                document.querySelector('#studentDetailsContainer .balance-details .midterm').innerText = midterm;
                document.querySelector('#studentDetailsContainer .balance-details .prefinal').innerText = prefinal;
                document.querySelector('#studentDetailsContainer .balance-details .final').innerText = final;
                
                document.querySelector('#studentDetailsContainer .balance-details .prelim_status').innerText = prelim_status;
                document.querySelector('#studentDetailsContainer .balance-details .midterm_status').innerText = midterm_status;
                document.querySelector('#studentDetailsContainer .balance-details .prefinal_status').innerText = prefinal_status;
                document.querySelector('#studentDetailsContainer .balance-details .final_status').innerText = final_status;
            
                // Delete Account Button Event Listener
                document.getElementById('deleteAccountButton').addEventListener('click', function() {
                    const studentNumber = row.getAttribute('data-student-number');
                    confirmDelete(studentNumber);
                });
                
                function confirmDelete(studentNumber) {
                document.getElementById('deleteStudentNumberInput').value = studentNumber;
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
            }

            // Close the student details and show the student list table
            document.getElementById('closeDetailsButton').addEventListener('click', function() {
                document.getElementById('studentDetailsContainer').style.display = 'none';
                document.querySelector('.student-list-table').style.display = 'block';
            });

            function clearSearch() {
                document.querySelector('.search').value = ''; // Clear the input field
                document.querySelector('form').submit(); // Trigger form submit to refresh the page without the search term
            }
        </script>

    </div>
</body>
</html>