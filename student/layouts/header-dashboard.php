<header class="custom-header">
    <a class="logo" href="index.php"> 
        <img src="imgs/actscc-logo.png" alt="School Logo" style="height: 35px; margin-right: 10px; margin-left:20px; transition: transform 0.5s ease;"
        onmouseover="this.style.transform='scale(1.2)'" 
        onmouseout="this.style.transform='scale(1)'">
        <span class="acts-ops">ACTS OPS </span>
        <span class="online-payment"> - ONLINE PAYMENT SYSTEM</span>
    </a>
        
    <div class="header-profile">
        <div class="profile-image">
            <?php if (!empty($profile_image)): ?>
                <img src="../uploads/profile_images/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" width="40px" class="profile-img">
            <?php else: ?>
                <img src="../img/profile-default-G.png" alt="Default Profile Image" class="profile-img"><!-- width="50px" height="50px" -->
            <?php endif; ?>
        </div>
        <!-- <h4><?php echo htmlspecialchars($firstname); ?> <?php echo htmlspecialchars($lastname); ?></h4> -->
        <h4>
            <?php
                if (empty($firstname) || empty($lastname)) {
                    echo "No user, Login again"; // Or any message you prefer
                    // Redirect to the login page
                    header("Location: ../login.php"); // Replace 'login.php' with your actual login page URL
                    exit();
                } else {
                    echo htmlspecialchars($firstname) . " " . htmlspecialchars($lastname);
                }
            ?>
        </h4>

        <div class="dropdown-container">
            <!-- <button class="dropdown-button">&#9662;</button> -->
            <button class="dropdown-button">
                <img src="../img/dropdown-b.png" alt="Dropdown">
            </button>
            <div class="dropdown-content">
                <a href="layouts/logout.php" class="logout-button" onclick="confirmLogout(event)">
                    <img src="../img/logout-b.png" alt="logout logo">
                    Logout
                </a>
            </div>
        </div>
    </div>
</header>

<?php
include '../includes/dashboard_confirm_logout.php';
?>