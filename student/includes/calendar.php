<?php
// Assuming $student_number is available from your session or authentication
if (!isset($student_number)) {
    // Handle the case where the student number is not set, e.g., redirect to login
    die("Student number is not set. Please log in.");
}

// Query to fetch announcements
try {
    $sql = "SELECT deadline, title FROM tbl_announcements";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // Fetch the results
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle query error
    echo "Error fetching announcements: " . $e->getMessage();
    exit;
}

// Query to fetch semester fee due dates
try {
    $sql = "SELECT due_date, fee_for FROM semester_fees";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching fee deadlines: " . $e->getMessage();
    exit;
}

// Query to fetch student payments due dates
try {
    $sql = "SELECT payment_date, fee_for FROM student_payments WHERE student_number = :student_number";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_number', $student_number, PDO::PARAM_STR);
    $stmt->execute();
    $studentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching student payments: " . $e->getMessage();
    exit;
}

// Create an associative array to pass deadlines and their titles
$deadlines = [];
foreach ($announcements as $announcement) {
    $date = date('Y-m-d', strtotime($announcement['deadline']));
    $deadlines[$date] = $announcement['title']; // Key: date, Value: title
}

// Store semester fee due dates
foreach ($fees as $fee) {
    $date = date('Y-m-d', strtotime($fee['due_date']));
    if (!empty($fee['due_date'])) {
        if (isset($deadlines[$date])) {
            $deadlines[$date] = $fee['fee_for'] . " Due date"; // Append to existing deadlines
        } else {
            $deadlines[$date] = $fee['fee_for'] . " Due";
        }
    }
}

// Store student payments due dates
foreach ($studentPayments as $payment) {
    $date = date('Y-m-d', strtotime($payment['payment_date']));
    if (!empty($payment['payment_date'])) {
        if (isset($deadlines[$date])) {
            $deadlines[$date] .= ", " . $payment['fee_for'] . " Due date"; // Append to existing deadlines
        } else {
            $deadlines[$date] = $payment['fee_for'] . " Due date";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>
    <style>
        /* Styling the calendar */
        .controls {
            text-align: center;
            /* background-color: grey; */
            margin-top: -5px;
            padding: 0;
        }

        .controls button {
            background-color: transparent;
            color: white;
            border: none;
            padding: 5px 8px;
            margin: 0 5px;
            border-radius: 5px;
            font-size: 30px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .controls button:hover {
            color: #003E29;
        }

        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 7px;
            margin: 2px auto;
            max-width: 500px;
            border-radius: 10px;
            padding: 5px;
        }

        .day {
            font-size: 18px;
            border: 1px solid #e0e0e0;
            text-align: center;
            background-color: rgba(0, 62, 41, 0.3); /* Transparent green */
            border-radius: 8px;
            transition: background-color 0.4s, transform 0.4s;
            padding: 5px;
            height: 32px;
            cursor: pointer;
            font-family: 'Roboto', sans-serif;
            /* height: auto; */
            padding: 5px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .announcement-title {
            display: none;
            font-size: 0.8em;
            margin-top: 5px;
            text-align: center;
        }

        .day.deadline {
            background-color: #ff6347; /* Red for deadlines */
            color: white;
            font-weight: bold;
            transition: transform 0.3s ease, background-color 0.3s ease;
            /*position: relative;*/
        }
        .day.deadline:hover {
            background-color:rgb(71, 255, 154); /* Red for deadlines */
        }
        
        /* Show the title when hovering over the day */
        .day.deadline:hover .announcement-title {
            display: block;
            font-weight: bold;
            padding: 0;
            margin:0;
            position: absolute;
            top: 100%; /* Position it below the day number */
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap; /* Prevents title from wrapping */
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 15px;
            z-index: 9999;
        }
        
        .day.today {
            background-color: rgb(19, 206, 63); /* Green for today */
            color: #fff;
            font-weight: bold;
        }

        .day:hover {
            background-color: #003E29;
            color: #fff;
            transform: scale(1.09);
        }

        .header {
            font-weight: bold;
            text-align: center;
            color: white;
        }

        .month-year {
            font-size: 1.8em;
            margin: 10px 0;
            color: #fff;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .month-year {
                font-size: 1.2em;
            }
        }
        @media (max-width: 400px) {
            .month-year {
                font-size: 1.2em;
            }
            .calendar {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 5px;
                padding: 5px;
                font-size: 13px;
            }
            .day {
                font-size: 17px;
                text-align: center;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="controls">
        <button onclick="prevMonth()">&#8249;</button>
        <span class="month-year" id="monthYear"></span>
        <button onclick="nextMonth()">&#8250;</button>
    </div>

    <div class="calendar" id="calendar">
        <div class="header day-name">Sun</div>
        <div class="header day-name">Mon</div>
        <div class="header day-name">Tue</div>
        <div class="header day-name">Wed</div>
        <div class="header day-name">Thu</div>
        <div class="header day-name">Fri</div>
        <div class="header day-name">Sat</div>
    </div>

    <!-- Pass PHP deadlines array to JavaScript -->
    <script>
        const deadlines = <?php echo json_encode($deadlines); ?>;

        const calendarElement = document.getElementById('calendar');
        const monthYearElement = document.getElementById('monthYear');
        let currentDate = new Date();

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const today = new Date();

            // Set the month and year title
            monthYearElement.innerText = currentDate.toLocaleString('default', { month: 'long' }) + " " + year;

            // Clear the previous calendar
            calendarElement.innerHTML = '';

            // Add headers for days of the week
            const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            daysOfWeek.forEach(day => {
                calendarElement.innerHTML += `<div class="header">${day}</div>`;
            });

            // Get the first day of the month and the number of days in the month
            const firstDay = new Date(year, month, 1).getDay();
            const totalDays = new Date(year, month + 1, 0).getDate();

            // Add blank cells for days before the first day of the month
            for (let i = 0; i < firstDay; i++) {
                calendarElement.innerHTML += `<div class="day"></div>`;
            }

            // Add days of the month
            for (let day = 1; day <= totalDays; day++) {
                const isToday = 
                    today.getFullYear() === year &&
                    today.getMonth() === month &&
                    today.getDate() === day;

                const currentDay = `${year}-${month + 1 < 10 ? '0' : ''}${month + 1}-${day < 10 ? '0' + day : day}`;
                const isDeadline = deadlines[currentDay] !== undefined; // Check if there's a deadline

                // Use the title attribute for the tooltip
                calendarElement.innerHTML += `<div class="day ${isToday ? 'today' : ''} ${isDeadline ? 'deadline' : ''}" 
                    ${isDeadline ? deadlines[currentDay] : ''}>
                    ${day}
                    ${isDeadline ? '<div class="announcement-title">' + deadlines[currentDay] + '</div>' : ''}
                </div>`;
            }
        }

        function prevMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        }

        // Render the initial calendar
        renderCalendar();
    </script>
</body>
</html>
