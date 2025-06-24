<?php
    // Connect to the database
    require '../../database/db.php'; // Ensure your database connection is correct

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

        // Delete from acts_ops_login
        $deleteLoginQuery = "DELETE FROM acts_ops_login WHERE student_number = :student_number";
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
                window.location.href = 'users_controller.php';
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
$totalQuery = "SELECT COUNT(*) FROM acts_ops_login WHERE role IN ('cashier', 'admin')";
if ($search != '') {
    $totalQuery .= " AND (student_number LIKE :search 
                        OR firstname LIKE :search 
                        OR middlename LIKE :search 
                        OR lastname LIKE :search 
                        OR email LIKE :search 
                        OR role LIKE :search)";
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

$query = "SELECT id, profile_image, student_number, firstname, middlename, lastname, email, role
          FROM acts_ops_login 
          WHERE role IN ('cashier', 'admin')";

    if ($search != '') {
        $query .= " AND (student_number LIKE :search 
                        OR lastname LIKE :search 
                        OR middlename LIKE :search 
                        OR firstname LIKE :search 
                        OR email LIKE :search 
                        OR role LIKE :search)";
    }

$query .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";
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
    <title>Users</title>
    <link rel="stylesheet" href="../css/content/users.css">
</head>
<body>
    <div class="reports-container">
        <div class="reports-header">
            <h2>Manage User Accounts</h2>
            <div class="button-header">
                <!-- <button class="button" id="openModalButton">
                    <img src="../imgs/plus-g.png" alt="Plus" width="20" height="20">
                    <h3>Add Users</h3>
                </button> -->
                <button id="openModalButton" style="margin-right: 10px;" class="add-Btn">
                    <div class="sign"><img src="../imgs/plus-g.png" alt="Add" width="26" height="26" style="margin-right: 5px;"></div>
                    <div class="text">Add Users</div>
                </button>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <script>
                alert('<?= $_SESSION['success_message'] ?>');
            </script>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <div class="search-field">        
            <!-- Search filter -->
            <form method="GET" action="">
                <div class="search-container">
                    <input type="text" name="search" class="search" placeholder="Search here" value="<?= htmlspecialchars($search) ?>" />
                    <button type="button" class="clear-btn" onclick="clearSearch()">×</button>
                </div>
                <div>
                    <button type="submit" class="search-button">
                        <label for="">Search</label>
                    </button>
                </div>
            </form>
        </div>
        <br>

        <div class="student-list-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID No.</th>
                        <th>Name</th>
                        <!-- <th>Firstname</th>
                        <th>Middlename</th>
                        <th>Lastname</th> -->
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr 
                            onclick="showStudentDetails(this)"
                            data-profile-image="<?= htmlspecialchars($student['profile_image']) ?>"
                            data-student-number="<?= htmlspecialchars($student['student_number']) ?>"
                            data-firstname="<?= htmlspecialchars($student['firstname']) ?>"
                            data-middlename="<?= htmlspecialchars($student['middlename']) ?>"
                            data-lastname="<?= htmlspecialchars($student['lastname']) ?>"
                            data-email="<?= htmlspecialchars($student['email']) ?>"
                            data-role="<?= htmlspecialchars($student['role']) ?>">
                            <td><?= htmlspecialchars($student['student_number']) ?></td>
                            <td><?= htmlspecialchars($student['firstname']) ?> <?= htmlspecialchars($student['middlename']) ?> <?= htmlspecialchars($student['lastname']) ?></td>
                            <!-- <td><?= htmlspecialchars($student['middlename']) ?></td>
                            <td><?= htmlspecialchars($student['lastname']) ?></td> -->
                            <td><?= htmlspecialchars($student['email']) ?></td>
                            <td style="text-transform: capitalize;"><?= htmlspecialchars($student['role']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

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


        <div class="student-details" id="studentDetailsContainer">
            <div class="close-button">
                <label for="account">Account Details</label>
                <button id="closeDetailsButton">Close</button>
            </div>
            <div class="filter-container">
                <div class="image-label">
                    <img id="studentProfileImage" src="" alt="Profile Image" style="width: 150px; height: 150px; border-radius: 50%; margin-top: 10px;">
                </div>
                <div class="rightside">
                    <div class="filter1">
                        <div class="student-label">
                            <label for="lastname">ID Number</label><br>
                            <input type="number" name="student_number" readonly>
                        </div>
                    </div>
                    <div class="filter1">
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
                    </div>
                    <div class="filter2">
                        <div class="email-label">
                            <label for="email">Email</label><br>
                            <input type="email" name="email" readonly>
                        </div>
                        <div class="role-label">
                            <label for="role">Role</label><br>
                            <input type="text" name="role" readonly>
                        </div>
                    </div>
                </div>
            </div>
            <div class="action-button">
                <button id="deleteAccountButton">Delete</button>
            </div>
        </div>


        <script>
            function showStudentDetails(row) {
                // Get student data from the clicked row
                const studentNumber = row.getAttribute("data-student-number");
                const firstname = row.getAttribute("data-firstname");
                const middlename = row.getAttribute("data-middlename");
                const lastname = row.getAttribute("data-lastname");
                const email= row.getAttribute("data-email");
                const role = row.getAttribute("data-role");
                var profileImage = row.getAttribute("data-profile-image");  
                
                // Hide the student list table
                document.querySelector('.student-list-table').style.display = 'none';

                // Display the student details
                document.getElementById('studentDetailsContainer').style.display = 'block';

                // Set the values in the form fields inside the student details section
                document.querySelector('#studentDetailsContainer input[name="student_number"]').value = studentNumber;
                document.querySelector('#studentDetailsContainer input[name="firstname"]').value = firstname;
                document.querySelector('#studentDetailsContainer input[name="middlename"]').value = middlename;
                document.querySelector('#studentDetailsContainer input[name="lastname"]').value = lastname;
                document.querySelector('#studentDetailsContainer input[name="email"]').value = email;
                document.querySelector('#studentDetailsContainer input[name="role"]').value = role;
                
                // Check if profile image exists, if not set to default
                var imageSrc = profileImage ? profileImage : '../imgs/default-image.jpg';  // Replace with your default image path
                // Set the profile image URL
                document.getElementById("studentProfileImage").src = imageSrc;

                
                // Delete Account Button Event Listener
                document.getElementById('deleteAccountButton').addEventListener('click', function() {
                    const studentNumber = row.getAttribute('data-student-number');
                    confirmDelete(studentNumber);
                });
                
                function confirmDelete(studentNumber) {
                document.getElementById('deleteStudentNumberInput').value = studentNumber;
                document.getElementById('deleteModal').style.display = 'block';
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
