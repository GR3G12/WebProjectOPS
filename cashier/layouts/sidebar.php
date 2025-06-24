<?php
    session_start();
    require '../../database/db.php';
    require '../../database/connectParams.php';

    $firstname = $_SESSION['firstname'] ?? '';
    $lastname = $_SESSION['lastname'] ?? '';
    $profile_image = $_SESSION['profile_image'] ?? '';
    $student_number = $_SESSION['student_number'] ?? ''; 

    // Fetch role from the acts_ops_login table using student_number
    $role = '';
    if (!empty($student_number)) {
        $stmt = $pdo->prepare("SELECT role FROM acts_ops_login WHERE student_number = ?");
        $stmt->execute([$student_number]);
        $role_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($role_data && isset($role_data['role'])) {
            $role = ucfirst(htmlspecialchars($role_data['role']));
        }

    }
?>
<aside class="sidebar">
    <div class="top">
        <div class="sidebar-header">
            <div class="profile-image">
                <?php if (!empty($profile_image)): ?>
                    <img src="../../uploads/profile_images/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image">
                <?php else: ?>
                    <img src="../../img/profile-default-W.png" alt="Default Profile Image" ><!-- width="50px" height="50px" -->
                <?php endif; ?>
            </div>
            <h4>
                <?php
                    if (empty($firstname)) {
                        echo "Login again"; // Or any message you prefer
                        // // Redirect to the login page
                        header("Location: ../../login.php"); // Replace 'login.php' with your actual login page URL
                        exit();
                    } else {
                        echo htmlspecialchars($firstname);
                    }
                ?>
            </h4>
            <!-- <h4><?php echo htmlspecialchars($firstname); ?></h4> -->
        </div>

        <div class="sidebar-content">
            
            <ul class="nav">
                <li class="nav-item">
                    <a href="../index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <!-- <i class="fas grid fa-fw"><ion-icon name="grid"> </ion-icon></i> -->
                        <img src="../imgs/dashboard.png" alt="Dashboard" width=28px>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="transaction_controller.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'transaction_controller.php' ? 'active' : ''; ?>">
                        <!-- <i class="fas fa-exchange-alt fa-fw"> </i> -->
                        <img src="../imgs/transfer.png" alt="Transfer" width=28px>
                        <span>Transaction</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="student_controller.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student_controller.php' ? 'active' : ''; ?>">
                        <!-- <i class="fas fa-wallet fa-fw"></i>  -->
                        <img src="../imgs/users.png" alt="Users"" width=29px>
                        <span>Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="payment_controller.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payment_controller.php' ? 'active' : ''; ?>">
                        <!-- <i class="fas fa-wallet fa-fw"></i>  -->
                        <img src="../../img/payments-i.png" alt="Users"" width=28px>
                        <span>Payments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports_controller.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports_controller.php' ? 'active' : ''; ?>">
                        <!-- <i class="fas fa-money-bill-wave fa-fw"></i>  -->
                        <img src="../imgs/reports.png" alt="Reports" width=29px>
                        <span>Reports</span>
                    </a>
                </li>
                
            </ul>
        </div>
    </div>

    <div class="bottom">
        <div class="sidebar-footer-a">
            <form method="post" onsubmit="return confirm('Are you sure you want to cancel ALL pending payments over the counter? This action cannot be undone.');">
                <button type="submit" name="forceRevertAll" class="revert-all-btn">
                    <img src="../../img/cancel-otc.png" alt="OTC" width=28px style="margin-right: 10px;">
                    <span>Cancel OTC</span>
                </button>
            </form> 
        </div>
        
        <div class="sidebar-footer">
            <div class="footer-item">
                <a href="../layouts/logout.php" class="nav-link-logout" onclick="confirmLogout(event)">
                    <!-- <i class="fas fa-sign-out-alt fa-fw"></i>  -->
                    <img src="../imgs/logout.png" alt="Logout" width=28px style="margin-right: 10px;">
                    <span>Logout</span>
                </a>    
            </div>
        </div>
    </div>
</aside>


<div id="profileModal" class="profilee-modal">
    <div class="profilee-modal-content">
        <span class="close-button">&times;</span>
        <img id="modalProfileImage" src="" alt="Full Profile Image" style="width: 100%; object-fit: contain; border-radius: 100%;">
        <div class="profile-box">
            <div class="profileNumber">
                <h4 id="modalStudentNumber" style="margin-top: 5px; text-align: center; color: blue; font-size: 20px;"></h4>
            </div>
            <div class="profileName">
                <h4 id="modalFirstName" style="margin-top: 5px; text-align: center; color: green; font-size: 20px;"></h4>
                <h4 id="modalLastName" style="margin-top: 5px; margin-left: 5px; text-align: center; color: green; font-size: 20px;"></h4>
                <!-- <h4 id="modalStudentNumber" style="margin-top: 5px; margin-left: 5px; text-align: center; color: green; font-size: 20px;"></h4> -->
                <!-- <h4 id="modalCourse" style="margin-top: 5px; text-align: center; color: purple; font-size: 16px;"></h4> -->
            </div>
            <div class="role">
                <h4 id="modalRole" style="margin-top: 5px; text-align: center; color: orange; font-size: 20px;"></h4>
            </div>
        </div>
        
    </div>
</div>
<style>
    /* Basic Modal Styling */
    .profilee-modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1111111; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    }

    .profilee-modal-content {
        background-color: #fefefe;
        margin: 15% auto; /* 15% from the top and centered */
        padding: 10px;
        padding-left: 50px;
        padding-right: 50px;
        border: 1px solid #888;
        width: 400px; /* Could be more or less, depending on screen size */
        border-radius: 5px;
        position: relative;
    }

    .profilee-modal-content .profile-box{
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width: 100%;
        padding-left: 20px;
        padding-right: 20px;
    }
    .profilee-modal-content .profile-box .profileName, .profilee-modal-content .profile-box .role{
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
    }
    .profilee-modal-content .profile-box h4{
        padding:0;
        margin:0;
    }

    .close-button {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close-button:hover,
    .close-button:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
    
</style>
<script>
    var userFirstName = "<?php echo htmlspecialchars($firstname); ?>";
    var userLastName = "<?php echo htmlspecialchars($lastname); ?>";
    // var userStudentNumber = "<?php echo htmlspecialchars($student_number); ?>";
    var userStudentNumber = "<?php echo htmlspecialchars($student_number); ?>";
    var userRole = "<?php echo $role; ?>";

    // Get the modal elements
    var modal = document.getElementById("profileModal");
    var profileImage = document.querySelector(".profile-image img");
    var closeButton = document.querySelector(".close-button");
    var modalImg = document.getElementById("modalProfileImage");
    var modalFirstName = document.getElementById("modalFirstName");
    var modalLastName = document.getElementById("modalLastName");
    var modalStudentNumber = document.getElementById("modalStudentNumber");
    var modalCourse = document.getElementById("modalCourse");
    var modalRole = document.getElementById("modalRole");

    profileImage.onclick = function(){
        modal.style.display = "block";
        modalImg.src = this.src;
        modalFirstName.innerText = userFirstName; // Display the firstname
        modalLastName.innerText = userLastName;   // Display the lastname
        modalStudentNumber.innerText = userStudentNumber;
        modalRole.innerText = userRole;
    }

    closeButton.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

 <!-- Logout Confirmation Modal -->
 <div id="logoutModal" class="confirmation-logout-modal">
    <div class="confirmation-logout-modal-content">
        <div class="par">
            <p>Are you sure you want to log out?</p>
        </div>
        <div class="modal-buttons">
            <button onclick="confirmLogoutAction()" class="btn-confirm">Yes</button>
            <button onclick="closeLogoutModal()" class="btn-cancel">No</button>
        </div>
    </div>
</div>

<script>
    let logoutLink = ""; // Store logout URL

    function confirmLogout(event) {
        event.preventDefault(); // Prevent the link from executing immediately
        logoutLink = event.currentTarget.href; // Store the logout link
        document.getElementById("logoutModal").style.display = "flex"; // Show modal
    }

    function confirmLogoutAction() {
        window.location.href = logoutLink; // Redirect to logout if confirmed
    }

    function closeLogoutModal() {
        document.getElementById("logoutModal").style.display = "none"; // Hide modal
    }
</script>
<style>
    /* Modal Background */
    .confirmation-logout-modal {
        display: none; 
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
    }

    /* Modal Content */
    .confirmation-logout-modal-content {
        background-color:rgba(255, 255, 255, 0.91);
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        width: 420px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }
    .confirmation-logout-modal-content .par{
        background-color: #fff;
        padding: 20px 2px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 19px;
        font-weight: bold;
        /* border: 1px solid #000; */
        border-radius: 10px;
        box-shadow: 2px 10px 15px -3px rgba(0, 0, 0, 0.1), 2px 4px 6px -2px rgba(0, 0, 0, 0.1);
        color: #000;
    }
    /* Buttons */
    .modal-buttons {
        margin-top: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
    }

    .btn-confirm {
        font-size: 18px;
        height: 35px;
        width: 120px;
        background-color: #ba1300;
        border: none;
        color: #fff;
        border-radius: 5px;
    }

    .btn-cancel {
        font-size: 18px;
        height: 35px;
        width: 120px;
        background-color: #0165bc;
        border: none;
        color: #fff;
        border-radius: 5px;
    }

    .btn-confirm:hover {
        height: 32px;
        width: 100px;
        font-size: 15px;
        background-color: #ba1300;
    }

    .btn-cancel:hover {
        height: 32px;
        width: 100px;
        font-size: 15px;
        background-color: #0165bc;
    }
</style>

<!-- Include the external JavaScript file -->
<!-- <script src="../../js/logout.js"></script> -->