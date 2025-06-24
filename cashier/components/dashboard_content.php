<?php
ob_start();
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

//     // Handle form submission for adding new announcement
//     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//         // Get form input values
//         $title = $_POST['title'];
//         $deadline = $_POST['date']; 
//         $content = $_POST['content'];
//         // $date = $_POST['date'];

//         try {
//             // Insert announcement into database
//             $created_by = $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
//             $sql = "INSERT INTO tbl_announcements (title, deadline, content, date, created_by) VALUES (?, ?, ?, NOW(), ?)";
//             $stmt = $pdo->prepare($sql);
//             $stmt->execute([$title, $deadline, $content, $created_by]);

//             // Redirect back to the page after adding// Set a success message in the session
//             $_SESSION['success_message'] = "Announcement successfully created!";
//             header("Location: index.php");  // Adjust the location as needed
//             exit();
//         } catch (PDOException $e) {
//             // Handle errors
//             echo "Error adding announcement: " . $e->getMessage();
//             exit;
//         }
//     }

// // Check if a success message exists and show the success message with auto-redirect
// if (isset($_SESSION['success_message'])) {
//     echo "<script>
//         // Create a div to show the success message
//         var successMessage = document.createElement('div');
//         successMessage.style.position = 'fixed';
//         successMessage.style.top = '20px';
//         successMessage.style.left = '50%';
//         successMessage.style.transform = 'translateX(-50%)';
//         successMessage.style.padding = '15px';
//         successMessage.style.backgroundColor = '#4CAF50';
//         successMessage.style.color = '#fff';
//         successMessage.style.fontSize = '16px';
//         successMessage.style.borderRadius = '5px';
//         successMessage.style.zIndex = '9999';
//         successMessage.innerText = '" . htmlspecialchars($_SESSION['success_message']) . "';
//         document.body.appendChild(successMessage);

//         // Redirect after 3 seconds
//         setTimeout(function() {
//             window.location.href = 'index.php';  // Adjust the location as needed
//         }, 2000);  // 2000 milliseconds = 2 seconds
//     </script>";

//     unset($_SESSION['success_message']); // Clear the message after displaying it
// }
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
                                    <p>Deadline: <?php echo htmlspecialchars(date('F d, Y', strtotime($announcement['deadline']))); ?></p>
                                    <div class="bottom">
                                        <small>
                                            <?php echo date("F j, Y", strtotime($announcement['date'])); ?>
                                            | By: <?php echo htmlspecialchars($announcement['created_by']); ?>
                                        </small>
                                        <button class="view-announcement-button"
                                            data-title="<?php echo htmlspecialchars($announcement['title']); ?>"
                                            data-deadline="<?php echo htmlspecialchars(date('F d, Y', strtotime($announcement['deadline']))); ?>"
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
                        <div class="progress-bar">
                            <?php for ($i = 0; $i < count($announcements); $i++) : ?>
                                <div></div>
                            <?php endfor; ?>
                        </div>
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
                <div class="card-row">
                    <a href="controllers/transaction_controller.php" class="nav-link">
                        <div class="card">
                            <div class="card-item">
                                <h3>Transaction</h3>
                                <p class="p-sub">View and manage all payment history</p>
                            </div>
                            <div class="card-icon">
                                <img src="imgs/transfer.png" alt="Transfer" width=88px>
                            </div>
                        </div>
                    </a>
  
                    <a href="controllers/payment_controller.php" class="nav-link">
                        <div class="card">
                            <div class="card-item">
                                <h3>Payments</h3>
                                <p class="p-sub">View and manage all payment approvals</p>
                            </div>
                            <div class="card-icon">
                                <img src="../img/payments-i.png" alt="Transfer" width=88px>
                            </div>
                        </div>
                    </a>
                    
                    <a href="controllers/student_controller.php" class="nav-link">
                        <div class="card">
                            <div class="card-item">
                                <h3>Students</h3>
                                <p class="p-sub">View and manage students status in regards with different fee/s</p>
                            </div>
                            <div class="card-icon">
                                <img src="imgs/users.png" alt="Users" width=99px>
                            </div>
                        </div>
                    </a>
                </div>
        </div>
        <div class="dashboard-box-2">
            <div class="card-row-2">
                <a href="#" class="nav-link">
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
                
                <a href="#" class="nav-link">
                </a>
            </div>
        </div>
    </div>
    <script>
        const announcements = document.querySelectorAll('.announcement-item');
        const progressBarSegments = document.querySelectorAll('.progress-bar div');
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

        
        function showAnnouncement(index) {
            announcements.forEach((announcement, i) => {
                announcement.classList.remove('active');
                if (i === index) {
                    announcement.classList.add('active');
                }
            });

            // Update progress bar
            progressBarSegments.forEach((segment, i) => {
                segment.classList.remove('active');
                if (i === index) {
                    segment.classList.add('active');
                }
            });
        }

        // Left arrow click
        leftArrow.addEventListener('click', function () {
            currentIndex = (currentIndex === 0) ? announcements.length - 1 : currentIndex - 1;
            showAnnouncement(currentIndex);
        });

        // Right arrow click
        rightArrow.addEventListener('click', function () {
            currentIndex = (currentIndex === announcements.length - 1) ? 0 : currentIndex + 1;
            showAnnouncement(currentIndex);
        });

        // Auto-switch announcements
        setInterval(function () {
            currentIndex = (currentIndex === announcements.length - 1) ? 0 : currentIndex + 1;
            showAnnouncement(currentIndex);
        }, 10000);

        // Initialize the first announcement and progress bar
        showAnnouncement(currentIndex);
    </script>
    
<?php
ob_end_flush(); // Send buffered output
?>