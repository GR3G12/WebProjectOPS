<?php
    session_start();
    require '../../database/db.php';
    require '../../database/connectParams.php';

    $firstname = $_SESSION['firstname'] ?? '';
    $lastname = $_SESSION['lastname'] ?? '';
    $profile_image = $_SESSION['profile_image'] ?? '';
    $student_number = $_SESSION['student_number'] ?? ''; 

    // Fetch course from student_accounts
    $course = '';
    if (!empty($student_number)) {
        $stmtCourse = $pdo->prepare("SELECT course FROM student_accounts WHERE student_number = ?");
        $stmtCourse->execute([$student_number]);
        $course_data = $stmtCourse->fetch(PDO::FETCH_ASSOC);
        if ($course_data) {
            $course = htmlspecialchars($course_data['course']);
        }
    }
?>
<aside class="sidebar">
    <div class="top">
        <div class="sidebar-header">
            <!-- <img src="../imgs/personW.png" alt="Person"> -->
            <!-- <img src="<?php echo isset($_SESSION['profile_image']) ? '../../uploads/' . $_SESSION['profile_image'] : '../imgs/personW.png'; ?>" alt="Person" width="50px"> -->
            
            <div class="profile-image">
                <?php if (!empty($profile_image)): ?>
                    <img src="../../uploads/profile_images/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image">
                <?php else: ?>
                    <img src="../../img/profile-default-W.png" alt="Default Profile Image" ><!-- width="50px" height="50px" -->
                <?php endif; ?>
            </div>
            
            <h4>
                <?php
                    if (empty($student_number)) {
                        echo "Login again"; // Or any message you prefer
                        // // Redirect to the login page
                        header("Location: ../../login.php"); // Replace 'login.php' with your actual login page URL
                        exit();
                    } else {
                        echo htmlspecialchars($student_number);
                    }
                ?>
            </h4>
            <!-- <h4><?php echo htmlspecialchars($student_number); ?></h4> -->
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
                        <img src="../imgs/history_Icon.png" alt="Transfer" width=28px>
                        <span>Payment History</span><!-- <span>Transaction</span> -->
                    </a>
                </li>
                <li class="nav-item">
                    <a href="paynow_controller.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'paynow_controller.php' ? 'active' : ''; ?>">
                        <!-- <i class="fas fa-money-bill-wave fa-fw"></i>  -->
                        <img src="../imgs/phone-ring3.png" alt="Phone Ring" width=25px style="margin-left: 5px;">
                        <span>Pay Now</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="topay_controller.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'topay_controller.php' ? 'active' : ''; ?>">
                        <!-- <i class="fas fa-calendar-alt fa-fw"></i>  -->
                        <img src="../imgs/calendar.png" alt="Calendar" width=28px>
                        <span>Payable</span><!-- <span>To Pay</span> -->
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="bottom">
        <div class="sidebar-footer">
            <div class="footer-item">
                <!-- <a href="../layouts/logout.php" class="nav-link-logout" onclick="return confirmLogout(event)"> -->
                <a href="../layouts/logout.php" class="nav-link-logout" onclick="confirmLogout(event)">
                    <img src="../imgs/logout.png" alt="Logout" width="28px" style="margin-right: 10px;">
                    <span>Logout</span>
                </a>  
            </div>
        </div>
    </div>
</aside>


<div id="profileModal" class="profilee-modal">
    <div class="profilee-modal-content">
        <div class="profileHeader" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #000; margin-bottom: 10px;">
            <h3 style="color:black; padding:0; margin:0;">Profile</h3>
            <span class="close-button">&times;</span>
        </div>
        <img id="modalProfileImage" src="" alt="Full Profile Image" style="width: 100%; object-fit: contain; border-radius: 100%;">
        <div class="profile-box">
            <div class="profileNumber">
                <h4 id="modalStudentNumber" style="margin-top: 5px; text-align: center; color: blue; font-size: 20px;"></h4>
            </div>
            <div class="profileName">
                <h4 id="modalFirstName" style="margin-top: 5px; text-align: center; color: green; font-size: 20px;"></h4>
                <h4 id="modalLastName" style="margin-top: 5px; margin-left: 5px; text-align: center; color: green; font-size: 20px;"></h4>
            </div>
            <div class="info" style="display:flex; flex-direction: column; justify-content: center; align-items: center;">
                <h4 id="modalCourse" style="margin-top: 5px; text-align: center; color: purple; font-size: 20px;"></h4>
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
    .profilee-modal-content .profile-box .profileNumber,
    .profilee-modal-content .profile-box .profileName,
    .profilee-modal-content .profile-box .info {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
    }
    .profilee-modal-content .profile-box h4{
        padding:0;
        margin:0;
        text-align: center;
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
    var userStudentNumber = "<?php echo htmlspecialchars($student_number); ?>";
    var userCourse = "<?php echo $course; ?>";

    // Get the modal elements
    var modal = document.getElementById("profileModal");
    var profileImage = document.querySelector(".profile-image img");
    var closeButton = document.querySelector(".close-button");
    var modalImg = document.getElementById("modalProfileImage");
    var modalFirstName = document.getElementById("modalFirstName");
    var modalLastName = document.getElementById("modalLastName");
    var modalStudentNumber = document.getElementById("modalStudentNumber");
    var modalCourse = document.getElementById("modalCourse");

    profileImage.onclick = function(){
        modal.style.display = "block";
        modalImg.src = this.src;
        modalFirstName.innerText = userFirstName;
        modalLastName.innerText = userLastName;
        modalStudentNumber.innerText = userStudentNumber;
        modalCourse.innerText = userCourse;
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
<script>
    function adjustModalWidth() {
        if (window.innerWidth <= 480) {
            document.getElementById('logoutModal').style.width = window.innerWidth + 'px';
            document.getElementById('logoutModal').style.height = window.innerHeight + 'px';
        } else {
            document.getElementById('logoutModal').style.width = ''; // Reset width for larger screens
            document.getElementById('logoutModal').style.height = ''; // Reset height for larger screens
        }
    }

    // Call on page load and resize
    adjustModalWidth();
    window.addEventListener('resize', adjustModalWidth);
</script>