<?php
    ob_start();
    require '../database/db.php';

    try {
        $sql = "SELECT id, title, deadline, content, date, created_by FROM tbl_announcements ORDER BY id DESC";
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
    <div class="dashboard-content">
        <div class="top-box">
            <div class="announcement-box">
                <div class="announcement-header">
                    <h3>ANNOUNCEMENT</h3>
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
                                    <!-- <p>Deadline: <?php echo htmlspecialchars(date('F d, Y', strtotime($announcement['deadline']))); ?></p> -->
                                     
                                    <!-- <p>Date: 
                                        <?php
                                        if ($announcement['deadline'] === null) {
                                            echo "No Deadline";
                                        } else {
                                            echo htmlspecialchars(date('F d, Y', strtotime($announcement['deadline'])));
                                        }
                                        ?>
                                    </p> -->
                                    <div class="bottom">
                                        <small>
                                            <div class="datecreated"><?php echo date("F j, Y", strtotime($announcement['date'])); ?>| </div>
                                            <div class="createdby"> By: <?php echo htmlspecialchars($announcement['created_by']); ?></div>
                                        </small>
                                        <button class="view-announcement-button"
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

                    <div class="progress-bar-con">
                        <?php foreach ($announcements as $index => $announcement) : ?>
                            <div class="progress-bar <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="event-box">
                <?php
                include 'includes/calendar.php';
                ?> 
            </div>
        </div>

        <?php include '../includes/announcement-details.php'; ?>

        <div class="dashboard-box">
                <div class="card-row">
                    <a href="controllers/transaction_controller.php" class="nav-link">
                        <div class="card">
                            <div class="card-item">
                                <h3>Payment History</h3>
                                <p class="p-sub">View and manage all payment history</p>
                            </div>
                            <div class="card-icon">
                                <!-- <i class="fas fa-credit-card fa-5x"></i> -->
                                <img src="imgs/history_Icon.png" alt="Transfer" width=88px>
                            </div>
                        </div>
                    </a>

                    <a href="controllers/paynow_controller.php" class="nav-link">
                        <div class="card">
                            <div class="card-item">
                                <h3>Pay Now</h3>
                                <p class="p-sub">Make payment using verified Gcash account</p>
                            </div>
                            <div class="card-icon">
                                <!-- <i class="fas fa-money-bill-wave fa-5x"></i> -->
                                <img src="imgs/phone-ring3.png" alt="Phone ring" width=88px>
                            </div>
                        </div>
                    </a>
                    
                    <a href="controllers/topay_controller.php" class="nav-link">
                        <div class="card">
                            <div class="card-item">
                                <h3>Payable</h3>
                                <p class="p-sub">View upcoming deadline/s of new school fee for respective event/s</p>
                            </div>
                            <div class="card-icon">
                                <!-- <i class="fas fa-calendar-alt fa-5x"></i> -->
                                <img src="imgs/calendar.png" alt="Calendar" width=99px>
                            </div>
                        </div>
                    </a>
                </div>
        </div>
    </div>
    <script>
        // Get all announcement items and buttons
        const announcements = document.querySelectorAll('.announcement-item');
        const progressDots = document.querySelectorAll('.progress-bar');
        const leftArrow = document.querySelector('.arrow-left');
        const rightArrow = document.querySelector('.arrow-right');
        let currentIndex = 0;

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

        // Handle left arrow click (show previous announcement)
        leftArrow.addEventListener('click', function() {
            currentIndex = (currentIndex === 0) ? announcements.length - 1 : currentIndex - 1;
            showAnnouncement(currentIndex);
        });

        // Handle right arrow click (show next announcement)
        rightArrow.addEventListener('click', function() {
            currentIndex = (currentIndex === announcements.length - 1) ? 0 : currentIndex + 1;
            showAnnouncement(currentIndex);
        });

        // Function to automatically switch to the next announcement every 5 seconds
        setInterval(function() {
            currentIndex = (currentIndex === announcements.length - 1) ? 0 : currentIndex + 1;
            showAnnouncement(currentIndex);
        }, 10000);  // 5000 milliseconds = 5 seconds

        // Initialize the first announcement
        showAnnouncement(currentIndex);
    </script>
    
<?php
ob_end_flush(); // Send buffered output
?>