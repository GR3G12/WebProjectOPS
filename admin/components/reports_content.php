<?php
include '../../database/db.php'; // Include database connection

// Fetch fee_for data for the table
$stmt = $pdo->query("SELECT fee_for, 
    COUNT(CASE WHEN status = 'Paid' THEN 1 END) AS paid,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS pending,
    COUNT(CASE WHEN status = 'Unpaid' THEN 1 END) AS unpaid,
    COUNT(*) AS total_fees
    FROM semester_fees
    GROUP BY fee_for
    ORDER BY 
        CASE fee_for
            WHEN 'Prelim' THEN 1
            WHEN 'Midterm' THEN 2
            WHEN 'Prefinal' THEN 3
            WHEN 'Final' THEN 4
            ELSE 5
        END;
    ");
$fees_status = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Fetch student_payments data with fee_for
$stmt = $pdo->query("SELECT fee_for, 
    COUNT(CASE WHEN status = 'Paid' THEN 1 END) AS paid,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS pending,
    COUNT(CASE WHEN status = 'Unpaid' THEN 1 END) AS unpaid,
    COUNT(*) AS total_fees
    FROM student_payments
    GROUP BY fee_for"); // Group by fee_for
$misc_payments_status = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all rows

?>
<?php
include '../includes/student_payments_pieGraph.php'
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Now</title>
    <link rel="stylesheet" href="../css/content/reports.css">
    <link rel="stylesheet" href="../css/content/reports.css" media="all">
    <link rel="stylesheet" href="../css/content/reports.css" media="print">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="paynow">
        <div class="paynow-header">
            <h2>Reports</h2>
            <div class="button-header">
                <button class="print-Btn" onclick="printPage()">
                    <div class="sign"><img src="../imgs/print2.png" alt="Add" width="26" height="26" style="margin-right: 5px;"></div>
                    <div class="text">Print</div>
                </button>
            </div>
            <!-- <img src="../imgs/print2.png" alt="Print Icon" width="35" onclick="printPage()" class="no-print"> -->
        </div>

        <h3>Semester Fees</h3>
        <div class="top">
            <div class="content">
                <h2><?= number_format($total_students) ?></h2>
                <label>Total Students</label>
            </div>
            <div class="content">
                <h2><?= $paid_percentage ?>%</h2>
                <label>Paid Students</label>
            </div>
            <div class="content">
                <h2><?= $pending_percentage ?>%</h2>
                <label>Pending Payments</label>
            </div>
            <div class="content">
                <h2><?= $unpaid_percentage ?>%</h2>
                <label>Unpaid Students</label>
            </div>
            <div class="content">
                <h2><?= number_format($total_payments, 2) ?></h2>
                <label>Accumulated Payments</label>
            </div>
        </div>

        <div class="middle">
            <div class="middle-con">
                <div class="pie-graph-con">
                    <canvas id="paymentStatusPieChart"></canvas>
                </div>
                <div class="table-con">
                    <div class="table-wrapper">
                        <h3>Semester Fees Payment Status</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Fee for</th>
                                    <th>UNPAID</th>
                                    <th>PENDING</th>
                                    <th>PAID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fees_status as $fee): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($fee['fee_for']) ?></td>
                                        <td><?= $fee['unpaid'] ?></td>
                                        <td><?= $fee['pending'] ?></td>
                                        <td><?= $fee['paid'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                </div>
            </div>

            <div class="bottom-con">
                <div class="bottom">
                    <div class="student-pay">
                        <h3>Miscellaneous Fees</h3>
                        <div class="content-con">
                            <div class="content">
                                <h2><?= $student_paid_percentage ?>%</h2>
                                <label>Paid Students</label>
                            </div>
                            <div class="content">
                                <h2><?= $student_pending_percentage ?>%</h2>
                                <label>Pending Payments</label>
                            </div>
                            <div class="content">
                                <h2><?= $student_unpaid_percentage ?>%</h2>
                                <label>Unpaid Students</label>
                            </div>
                        </div>
                        
                        <div class="content-con">
                            <div class="content">
                                <h2>₱ <?= number_format($student_total_payments, 2) ?></h2>
                                <label>Accumulated Payments</label>
                            </div>
                        </div>
                    </div>
                    <div class="pie-graph-con">
                        <canvas id="studentPaymentStatusPieChart"></canvas>
                    </div>
                </div>
                <div class="table-con">
                    <div class="table-wrapper">
                        <h3>Miscellaneous Payment Status</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Fee for</th>
                                    <th>UNPAID</th>
                                    <th>PENDING</th>
                                    <th>PAID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($misc_payments_status as $fee): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($fee['fee_for']) ?></td>
                                        <td><?= $fee['unpaid'] ?></td>
                                        <td><?= $fee['pending'] ?></td>
                                        <td><?= $fee['paid'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
            
    </div>

    

    <script>
        // JavaScript to render the pie chart
        document.addEventListener("DOMContentLoaded", function () {
            var ctx = document.getElementById('paymentStatusPieChart').getContext('2d');

            var paymentStatusPieChart = new Chart(ctx, {
                type: 'pie',  // Pie chart
                data: {
                    labels: ['Paid', 'Pending', 'Unpaid'],  // Labels for each status
                    datasets: [{
                        label: 'Payment Status',
                        data: [
                            <?= $paid_percentage ?>,  // Paid percentage
                            <?= $pending_percentage ?>,  // Pending percentage
                            <?= $unpaid_percentage ?>   // Unpaid percentage
                        ],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.5)',  // Color for Paid
                            'rgba(255, 159, 64, 0.5)',  // Color for Pending
                            'rgba(255, 99, 132, 0.5)'   // Color for Unpaid
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)', 
                            'rgba(255, 159, 64, 1)', 
                            'rgba(255, 99, 132, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    aspectRatio: 1,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Semester Fees Status', // Add chart title
                            color: 'white',
                            font: {
                                size: 16
                            }
                        },
                        legend: {
                            position: 'top',
                            labels: {
                                color: 'white'  // Change legend labels color to white
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return tooltipItem.raw + ' Payments';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
    <script>
        var ctx = document.getElementById('studentPaymentStatusPieChart').getContext('2d');

        var studentPaymentStatusPieChart = new Chart(ctx, {
            type: 'pie',  // Pie chart
            data: {
                labels: ['Paid', 'Pending', 'Unpaid'],  // Labels for each status
                datasets: [{
                    label: 'Payment Status',
                    data: [
                        <?= $student_paid_percentage ?>,  // Paid percentage
                        <?= $student_pending_percentage ?>,  // Pending percentage
                        <?= $student_unpaid_percentage ?>   // Unpaid percentage
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.5)',  // Color for Paid
                        'rgba(255, 159, 64, 0.5)',  // Color for Pending
                        'rgba(255, 99, 132, 0.5)'   // Color for Unpaid
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)', 
                        'rgba(255, 159, 64, 1)', 
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                aspectRatio: 1,
                plugins: {
                    title: {
                        display: true,
                        text: 'Miscellaneous Fees Status', // Add chart title
                        color: 'white',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            color: 'white'  // Change legend labels color to white
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.raw + ' Payments';
                            }
                        }
                    }
                }
            }
        });
    </script>
    </div>
</body>
</html>
