<?php
// session_start();
$content = '../components/users_content.php';
include '../../admin/layouts/master.php';

require '../../vendor/autoload.php'; // Add this line
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['submit'])) {
    // Collect form data
    $student_number = $_POST['student_number'];
    $password = $_POST['password'];
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Handle Image Upload
    $profile_image = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "../../uploads/"; // Folder to save images
        $image_name = basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . time() . "_" . $image_name; // Unique filename
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowed_types)) {
            die("Error: Only JPG, JPEG, PNG, & GIF files are allowed.");
        }

        // Move file to uploads directory
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image = $target_file; // Save file path in the database
        } else {
            die("Error uploading file.");
        }
    }

    try {
        // Start transaction
        $pdo->beginTransaction();
        // Insert student credentials into acts_ops_login table
        $stmt1 = $pdo->prepare("INSERT INTO acts_ops_login (profile_image, student_number, firstname, middlename, lastname, email, role, password) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt1->execute([$profile_image, $student_number, $firstname, $middlename, $lastname, $email, $role, $hashedPassword]);

        // Commit transaction
        $pdo->commit();

        $mail = new PHPMailer(true); // Enable exceptions

        try {
            //Server settings
            $mail->SMTPDebug = 0; // Disable verbose debug output (set to 2 for debugging)
            $mail->isSMTP(); // Send using SMTP
            $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
            $mail->SMTPAuth = true; // Enable SMTP authentication
            $mail->Username = 'gregoriollagas12@gmail.com'; // Replace with your SMTP username
            $mail->Password = 'bsxonsalysvuivzw'; // Replace with your SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
            $mail->Port = 587; // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

            //Recipients
            $mail->setFrom('gregoriollagas12@gmail.com', 'ACTS-OPS'); // Replace with your sending email and name
            $mail->addAddress($email, $firstname . ' ' . $lastname); // Add recipient

            //Content
            $mail->isHTML(true); // Set email format to HTML
            $mail->Subject = 'Account Created';
            $mail->Body = "
                <p>Dear {$firstname} {$lastname},</p>
                <p>Your account has been created successfully. Here are your login details:</p>
                <p><strong>ID Number:</strong> {$student_number}</p>
                <p><strong>Password:</strong> {$password}</p>
                <p>Please change your password after logging in.</p>
                <p>Here are your account details:</p>
                <p>Firstname: {$firstname}</p>
                <p>Middlename: {$middlename}</p>
                <p>Lastname: {$lastname}</p>
                <p>Email: {$email}</p>
                <p>Role: {$role}</p>
                <p>Thank you!</p>
                
                <p>You can access your account and log in through our ACTS Online Payment System's portal at: <a href=\"http://actsccops.com\">actsccops.com</a></p>
                <p>We recommend bookmarking this link for easy access.</p>
            ";

            $mail->send();
            // Optional: Log success
            error_log("Email sent to: " . $email);

        } catch (PHPMailer\PHPMailer\Exception $e) {
            // Optional: Log the error
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            echo "<script>alert('Account created successfully, but email could not be sent. Please contact admin.');</script>";
        }

        // Set success message
        $_SESSION['success_messagee'] = 'User added successfully!';

        // Redirect or show a success message
        // header('Location: ../controllers/users_controller.php'); // Redirect back to users page
        // exit();
        if (isset($_SESSION['success_messagee'])) {
            $message = $_SESSION['success_messagee']; 
            unset($_SESSION['success_messagee']); // Unset before redirecting
        
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
                successMessage.innerText = '" . addslashes($message) . "';
                document.body.appendChild(successMessage);
        
                setTimeout(function() {
                    window.location.href = '../controllers/users_controller.php'; 
                }, 500);
            </script>";
        }

    } catch (Exception $e) {
        // Rollback transaction if an error occurs
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

?>

<style>
    /* Modal styles */
    .add-student-modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.4);
        overflow: auto;
        z-index: 1000;
    }

    .add-student-modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 20px;
        border-radius: 20px;
        border: 1px solid #888;
        width: 800px;
    }
    
    .add-student-modal-content h2{
        padding: 0;
        margin: 0;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
    
    .row-image{
        display: flex;
        justify-content: space-between;
        align-items: end;
        gap: 10px;
        margin-top: 10px;
        /* background-color: grey; */
    }

    .row1, .row2, .row3, .row4 {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .row1 input[type='number'], .row1 select {
        width:  245px;
        padding: 5px;
        font-size: 16px;
    }
    .row1 input[type='password'], .row1 input[type='email']{
        width: 500px;
        padding: 5px;
        font-size: 16px;
    }

    .row2 input[type='text'] {
        width: 244px;
        padding: 5px;
        font-size: 16px;
    }

    .row3 select{
        width:  245px;
        padding: 5px;
        font-size: 16px;
    } 

    .row4 input[type='number'] {
        width: 245px;
        padding: 5px;
        font-size: 16px;
    }

    .sub-button {
        background-color: rgba(0, 135, 47, 0.9);
        border-radius: 10px;
        padding: 5px 20px;
        color: #fff;
        display: flex;
        justify-content: center; /* Centers the text inside the button */
        align-items: center;
        margin-left: auto; /* Aligns button to the right */
        font-size: 17px;
    }
    .sub-button:hover{
        background-color: rgba(1, 69, 25, 0.9);
        color: #fff;
    }
</style>

        <!-- Modal for Adding Student -->
        <div id="studentModal" class="add-student-modal">
            <div class="add-student-modal-content">
                <span class="close" id="closeModalButton">&times;</span>
                <h2>Add Users</h2>
                <form action="users_controller.php" method="POST" enctype="multipart/form-data"><!--../components/-->
                    <div class="row1">
                        <div class="row-image">
                            <div class="image-con">
                                <img id="imagePreview" src="" alt="Image Preview" style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                            </div>
                            <div class="image-details">
                                <label for="profile_image">Profile Image:</label><br>
                                <input type="file" name="profile_image" accept="image/*" onchange="previewImage(event)"><br>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row1">
                        <div class="row11">
                            <label for="student_number">ID Number</label><br>
                            <input type="number" id="student_number" name="student_number" required><br>
                        </div>
                        <div class="row11-p">
                            <label for="password">Password</label><br>
                            <input type="password" id="password" name="password" required><br>
                        </div>
                    </div>
                    <br>

                    <div class="row2">
                        <div class="row22">
                            <label for="firstname">First Name</label><br>
                            <input type="text" id="firstname" name="firstname" required><br>
                        </div>
                        <div class="row22">
                            <label for="middlename">Middle Name</label><br>
                            <input type="text" id="middlename" name="middlename" required><br>
                        </div>
                        <div class="row22">
                            <label for="lastname">Last Name</label><br>
                            <input type="text" id="lastname" name="lastname" required><br>
                        </div>
                    </div>
                    <br>

                    <div class="row1">
                        <div class="row33">
                            <label for="email">Email</label><br>
                            <input type="email" id="email" name="email" required><br>
                        </div>
                        <div class="row33">
                            <label for="role">Role</label><br>
                            <select name="role" id="role" required>
                                <option value="" disabled selected>Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="cashier">Cashier</option>
                                <!-- <option value="student">Student</option> -->
                            </select><br>
                        </div>
                    </div>
                    <br>

                    <button type="submit" name="submit" class="sub-button">Submit</button>
                </form>
            </div>
        </div>

        <script>
            // Modal toggle
            var modal = document.getElementById("studentModal");
            var openModalButton = document.getElementById("openModalButton");
            var closeModalButton = document.getElementById("closeModalButton");

            openModalButton.onclick = function() {
                modal.style.display = "block";
            }

            closeModalButton.onclick = function() {
                modal.style.display = "none";
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        </script>
        <script>
            function previewImage(event) {
                var input = event.target;
                var reader = new FileReader();
                
                reader.onload = function() {
                    var imagePreview = document.getElementById("imagePreview");
                    imagePreview.src = reader.result;
                    imagePreview.style.display = "block"; // Show the image preview
                };
                
                reader.readAsDataURL(input.files[0]); // Read the selected file
            }
        </script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.href);
                }
            });
        </script>

<div id="deleteModal" class="delete-modal">
    <div class="delete-modal-content">
        <div class="par">
            <p>Are you sure you want to delete this account?</p>
        </div>
        <form id="deleteForm" method="GET" action="">
            <input type="hidden" name="delete_student_number" id="deleteStudentNumberInput">
            <button type="submit" class="yes-button">Yes</button>
            <!-- <button type="button" onclick="closeModal()" class="no-button">No</button> -->
            <button type="button" onclick="closeModal()" class="no-button">No</button>
        </form>
    </div>
</div>
<script>
    function closeModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
</script>