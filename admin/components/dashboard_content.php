<?php

// Check if the user is logged in and is an admin
if (!isset($_SESSION['student_number']) || $_SESSION['role'] !== 'admin') {
    // Redirect to the login page if not logged in or not an admin
    header("Location: ../login.php"); // Assuming login.php is one level up
    exit();
}

ob_start(); // Start output buffering
    try {
        $sql = "SELECT id, title, content, deadline, date, created_by FROM tbl_announcements ORDER BY id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        // Fetch the results
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle query error
        echo "Error fetching announcements: " . $e->getMessage();
        exit;
    }
?>

<?php include '../includes/overview-content.php';?>
<?php include '../includes/other-overview-content.php';?>

<div class="dashboard-content">
        <div class="top-box">
            <div class="announcement-box">
                <div class="announcement-header">
                    <h3>ANNOUNCEMENT</h3>
                    <div class="announcement-button">
                        <button class="add-button">Add</button>
                        <button class="view-button">View</button>
                    </div>
                </div>
                <div class="announcement-content">
                    <button class="arrow-button arrow-left">&#8249;</button>
                    <?php if (!empty($announcements)) : ?>
                        <ul class="announcement-list">
                            <?php foreach ($announcements as $index => $announcement) : ?>
                                <li class="announcement-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                    <div class="content-box">
                                        <p class="content-p"><?php echo htmlspecialchars($announcement['content']); ?></p>
                                    </div>
                                    <!-- Deadline<p>Date: <?php echo htmlspecialchars(date('F d, Y', strtotime($announcement['deadline']))); ?></p> -->

                                    <p>Date: 
                                        <?php
                                        if ($announcement['deadline'] === null) {
                                            echo "No Deadline";
                                        } else {
                                            echo htmlspecialchars(date('F d, Y', strtotime($announcement['deadline'])));
                                        }
                                        ?>
                                    </p>
                                    <div class="bottom">
                                        <small>
                                            <?php echo date("F j, Y", strtotime($announcement['date'])); ?>
                                            | By: <?php echo htmlspecialchars($announcement['created_by']); ?>
                                        </small>
                                        <button title="View" class="view-announcement-button"
                                            data-title="<?php echo htmlspecialchars($announcement['title']); ?>"
                                            data-deadline="<?php echo ($announcement['deadline'] === null) ? '' : htmlspecialchars(date('F d, Y', strtotime($announcement['deadline']))); ?>"
                                            data-content="<?php echo htmlspecialchars($announcement['content']); ?>"
                                            data-created-by="<?php echo htmlspecialchars($announcement['created_by']); ?>"
                                            data-date="<?php echo htmlspecialchars(date('F d, Y', strtotime($announcement['date']))); ?>">
                                            <i class="fas fa-eye" style="font-size: 17px;"></i>
                                        </button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p>No announcements available. Stay tuned!</p>
                    <?php endif; ?>
                    <button class="arrow-button arrow-right">&#8250;</button>

                    <!-- Progress Bar -->
                    <div class="progress-bar-con">
                        <?php foreach ($announcements as $index => $announcement) : ?>
                            <div class="progress-bar <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="event-box">
                <div class="overview-header">
                    <h3>OVERVIEW</h3>
                    <button class="filter-button" id="paymentFilterButton">Semester</button>
                </div>
                <div class="overview-content" id="overviewContent">       
                    <div class="overview-con">
                        <div class="overview-left">
                            <!-- <div class="content">
                                <h2><?= number_format($total_students) ?></h2>
                                <label>Total Students</label>
                            </div> -->
                            <div class="content-con1">
                                <div class="content-g1">
                                    <div class="content">
                                        <h2><?= $paid_percentage ?>%</h2>
                                        <label>Paid Payments</label>
                                    </div>
                                    <div class="content">
                                        <h2><?= $unpaid_percentage ?>%</h2>
                                        <label>Unpaid Payments</label>
                                    </div>
                                </div>
                            </div>
                            <div class="content-con2">
                                <div class="content-percentage">
                                    <h2><?= $pending_percentage ?>%</h2>
                                    <label>Pending Payments</label>
                                </div>
                            </div>
                            
                            <div class="content-con2">
                                <div class="content">
                                    <h2>₱ <?= number_format($total_payments, 2) ?></h2>
                                    <label>Accumulated Payments</label>
                                </div>
                            </div>
                        </div>
                        <div class="overview-right">
                            <div class="pie-graph-con">
                                <canvas id="paymentStatusPieChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="other-overview-content"  id="otherPaymentsContent" style="display: none;">       
                    <div class="overview-con">
                        <div class="overview-left">
                            <!-- <div class="content">
                                <h2><?= number_format($total_students) ?></h2>
                                <label>Total Students</label>
                            </div> -->
                            <div class="content-con1">
                                <div class="content-g1">
                                    <div class="content">
                                        <h2><?= $other_paid_percentage ?>%</h2>
                                        <label>Paid Payments</label>
                                    </div>
                                    <div class="content">
                                        <h2><?= $other_unpaid_percentage ?>%</h2>
                                        <label>Unpaid Payments</label>
                                    </div>
                                </div>
                            </div>
                            <div class="content-con2">
                                <div class="content-percentage">
                                    <h2><?= $other_pending_percentage ?>%</h2>
                                    <label>Pending Payments</label>
                                </div>
                            </div>
                            <div class="content-con2">
                                <div class="content">
                                    <h2>₱ <?= number_format($other_total_payments, 2) ?></h2>
                                    <label>Accumulated Payments</label>
                                </div>
                            </div>
                        </div>
                        <div class="overview-right">
                            <div class="pie-graph-con">
                                <canvas id="otherPaymentStatusPieChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        

        <?php include '../includes/announcement.php';?>
        <?php include '../includes/announcement-details.php'; ?>

        <div class="dashboard-box">
            <!-- <div class="card-container"> -->
                <div class="card-row">
                    <a href="controllers/transaction_controller.php" class="nav-link">
                        <div class="card">
                            <div class="card-item">
                                <h3>Transaction</h3>
                                <p class="p-sub">View and manage all payment made by the users</p>
                            </div>
                            <div class="card-icon">
                                <img src="imgs/transfer.png" alt="Transfer" width=88px>
                            </div>
                        </div>
                    </a>

                    <a href="controllers/reports_controller.php" class="nav-link">
                        <div class="card">
                            <div class="card-item">
                                <h3>Reports</h3>
                                <p class="p-sub">Manage data for accumulated fee/s</p>
                            </div>
                            <div class="card-icon">
                                <img src="imgs/reports.png" alt="Reports" width=88px>
                            </div>
                        </div>
                    </a>
                    
                    <a href="controllers/add_controller.php" class="nav-link">
                        <div class="card">
                            <div class="card-item">
                                <h3>Payments</h3>
                                <p class="p-sub">Add new fee/s</p>
                            </div>
                            <div class="card-icon">
                                <img src="../img/add-payment-Icon.png" alt="Plus" width=99px>
                            </div>
                        </div>
                    </a>
                </div>
                
                <div class="card-row-2">
                    <a href="controllers/student_controller.php" class="nav-link">
                        <div class="card">
                            <div class="card-item">
                                <h3>Students</h3>
                                <p class="p-sub">View and manage students status</p>
                            </div>
                            <div class="card-icon">
                                <img src="imgs/users.png" alt="Users" width=99px>
                            </div>
                        </div>
                    </a>
                    
                    <a href="controllers/users_controller.php" class="nav-link">
                        <div class="card">
                            <div class="card-item">
                                <h3>Users</h3>
                                <p class="p-sub">Manage users account</p>
                            </div>
                            <div class="card-icon">
                                <img src="imgs/user-setting.png" alt="Gateway" width=88px>
                            </div>
                        </div>
                    </a>
                </div>
            <!-- </div> -->
        </div>
    </div>

    <script>
        // JavaScript for Announcement Navigation and Progress Dots
        const announcements = document.querySelectorAll('.announcement-item');
        const progressDots = document.querySelectorAll('.progress-bar');
        const leftArrow = document.querySelector('.arrow-left');
        const rightArrow = document.querySelector('.arrow-right');
        let currentIndex = 0;

        // for changing the overview-content
        const paymentFilterButton = document.getElementById('paymentFilterButton');
        const overviewContent = document.getElementById('overviewContent');
        const otherPaymentsContent = document.getElementById('otherPaymentsContent');

        paymentFilterButton.addEventListener('click', () => {
            if (paymentFilterButton.textContent === 'Semester') {
                paymentFilterButton.textContent = 'Other Payments';
                overviewContent.style.display = 'none';
                otherPaymentsContent.style.display = 'block';   
            } else {
                paymentFilterButton.textContent = 'Semester';
                overviewContent.style.display = 'block';
                otherPaymentsContent.style.display = 'none';
            }
        });


        // Function to show the current announcement
        function showAnnouncement(index) {
            announcements.forEach((announcement, i) => {
                announcement.classList.toggle('active', i === index);
            });
            updateProgressDots(index);
        }

        // Function to update progress dots
        function updateProgressDots(index) {
            progressDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
        }

        // Left arrow click
        leftArrow.addEventListener('click', () => {
            currentIndex = (currentIndex === 0) ? announcements.length - 1 : currentIndex - 1;
            showAnnouncement(currentIndex);
        });

        // Right arrow click
        rightArrow.addEventListener('click', () => {
            currentIndex = (currentIndex === announcements.length - 1) ? 0 : currentIndex + 1;
            showAnnouncement(currentIndex);
        });

        // Auto-switch announcements every 10 seconds
        setInterval(() => {
            currentIndex = (currentIndex === announcements.length - 1) ? 0 : currentIndex + 1;
            showAnnouncement(currentIndex);
        }, 10000);

        // Initialize the first announcement
        showAnnouncement(currentIndex);
    </script>
<?php
ob_end_flush(); // Send buffered output
?>