<?php
// Include the database connection
include('../../database/db.php');

$backupListHTML = ''; // Initialize an empty string for the list

if (isset($_POST['backup_tables'])) {
    // Tables to backup
    $tables = ['created_payments', 'semester_fees', 'student_accounts', 'student_payments'];

    // Backup directory
    $backupDir = '../backup/';

    // Ensure the backup directory exists
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    // Create a timestamp for the backup filename
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . 'database_backup_' . $timestamp . '.sql';

    try {
        // Open the backup file for writing
        $handle = fopen($backupFile, 'w+');

        if ($handle) {
            // Write database and timestamp information to the backup file
            fwrite($handle, "-- Database Backup\n");
            fwrite($handle, "-- Database: " . $dbname . "\n");
            fwrite($handle, "-- Timestamp: " . $timestamp . "\n\n");

            // Loop through each table
            foreach ($tables as $table) {
                // Fetch table structure
                $stmt = $pdo->prepare("SHOW CREATE TABLE `$table`");
                $stmt->execute();
                $row2 = $stmt->fetch(PDO::FETCH_ASSOC);

                fwrite($handle, "\n-- Table structure for table `$table`\n");
                fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
                fwrite($handle, "" . $row2['Create Table'] . ";\n\n");

                // Fetch table data
                $stmt = $pdo->prepare("SELECT * FROM `$table`");
                $stmt->execute();
                $numRows = $stmt->rowCount();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Write table data
                if ($numRows > 0) {
                    fwrite($handle, "-- Dumping data for table `$table`\n");
                    foreach ($results as $row) {
                        $columns = array_keys($row);
                        $values = array_map(function ($value) {
                            return "'" . addslashes(str_replace("\n", "\\n", $value)) . "'";
                        }, array_values($row));

                        $insertQuery = "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $values) . ");\n";
                        fwrite($handle, $insertQuery);
                    }
                    fwrite($handle, "\n");
                }
            }
            fclose($handle);
            // $backupStatus = '<p style="color: green;">Database backup successfully saved to <a href="' . $backupFile . '" target="_blank">' . basename($backupFile) . '</a></p>';
            // echo '<script>document.getElementById("backupStatus").innerHTML = "' . $backupStatus . '";</script>';
            $successMessage = "Database backup successfully saved to " . basename($backupFile) . "!";
            echo "<script>
                var successMessageDiv = document.createElement('div');
                successMessageDiv.style.position = 'fixed';
                successMessageDiv.style.top = '20px'; // Adjust as needed to be within the modal view
                successMessageDiv.style.left = '50%';
                successMessageDiv.style.transform = 'translateX(-50%)';
                successMessageDiv.style.padding = '15px';
                successMessageDiv.style.backgroundColor = '#4CAF50';
                successMessageDiv.style.color = '#fff';
                successMessageDiv.style.fontSize = '16px';
                successMessageDiv.style.borderRadius = '5px';
                successMessageDiv.style.zIndex = '10000'; // Higher than modal's z-index
                successMessageDiv.innerText = '" . addslashes($successMessage) . "';
                document.body.appendChild(successMessageDiv);

                setTimeout(function() {
                    window.location.href = 'setting_controller.php';
                }, 1000);
            </script>";
        } else {
            $backupStatus = '<p style="color: red;">Error: Could not open backup file for writing!</p>';
            echo '<script>document.getElementById("backupStatus").innerHTML = "' . $backupStatus . '";</script>';
        }

        // After attempting the backup (success or failure), read the backup files
        $backupDir = '../backup/';
        if (is_dir($backupDir)) {
            $backupFiles = scandir($backupDir);
            $backupListHTML .= '<h3>Previous Backups:</h3><ul>';
            foreach ($backupFiles as $file) {
                if ($file !== '.' && $file !== '..') {
                    $backupListHTML .= '<li><a href="' . $backupDir . $file . '" target="_blank">' . $file . '</a></li>';
                }
            }
            $backupListHTML .= '</ul>';
        } else {
            $backupListHTML .= '<p>Backup directory not found.</p>';
        }

    } catch (PDOException $e) {
        $backupStatus = '<p style="color: red;">Error during backup: ' . $e->getMessage() . '</p>';
        echo '<script>document.getElementById("backupStatus").innerHTML = "' . $backupStatus . '";</script>';
    }
} else {
    // If the form is not submitted on page load, still try to display existing backups
    $backupDir = '../backup/';
    if (is_dir($backupDir)) {
        $backupFiles = scandir($backupDir);
        $backupListHTML .= '<h3>Previous Backups:</h3><ul>';
        foreach ($backupFiles as $file) {
            if ($file !== '.' && $file !== '..') {
                $backupListHTML .= '<li><a href="' . $backupDir . $file . '" target="_blank">' . $file . '</a></li>';
            }
        }
        $backupListHTML .= '</ul>';
    } else {
        $backupListHTML .= '<p>Backup directory not found.</p>';
    }
}
?>

<div id="backupModal" class="backup-modal">
    <div class="backup-modal-content">
        <span class="close-button" onclick="document.getElementById('backupModal').style.display='none'">&times;</span>
        <p>Click the button below to save the database tables to the "backup" folder.</p>
        <form method="post" action="">
            <button type="submit" class="backup-modal-backup-button" name="backup_tables">Save Database Backup</button>
        </form>
        <div id="backupStatus"></div>
        <div id="backupList"><?php echo $backupListHTML; ?></div>
    </div>
</div>

<style>
/* Modal Styles */
.backup-modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 111111111111; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}

.backup-modal-content {
    background-color: #fefefe;
    margin: 15% auto; /* 15% from the top and centered */
    padding: 20px;
    border: 1px solid #888;
    width: 60%; /* Could be more or less, depending on screen size */
    border-radius: 5px;
    position: relative;
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

.backup-modal-backup-button {
    background-color: #4CAF50; /* Green */
    color: white;
    padding: 10px 20px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    font-size: 16px;
}

.backup-modal-backup-button:hover {
    opacity: 0.8;
}

#backupList {
    margin-top: 20px;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    background-color: #f9f9f9;
}

#backupList h3 {
    margin-top: 0;
    font-size: 1.2em;
}

#backupList ul {
    list-style-type: none;
    padding-left: 0;
}

#backupList ul li {
    margin-bottom: 5px;
}

#backupList ul li a {
    text-decoration: none;
    color: #007bff;
}

#backupList ul li a:hover {
    text-decoration: underline;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const backupButton = document.querySelector('.backup-b');
    const backupModal = document.getElementById('backupModal');
    const closeButton = document.querySelector('.close-button');
    const modalBackupButton = document.querySelector('.backup-modal-backup-button');

    if (backupButton) {
        backupButton.addEventListener('click', function() {
            backupModal.style.display = "block";
        });
    }

    closeButton.addEventListener('click', function() {
        backupModal.style.display = "none";
    });

    window.addEventListener('click', function(event) {
        if (event.target == backupModal) {
            backupModal.style.display = "none";
        }
    });

    modalBackupButton.addEventListener('click', function() {
        // Submit the form to trigger the PHP backup and reload the page (to see updated list)
        const form = modalBackupButton.closest('form');
        if (form) {
            form.submit();
        }
    });
});
</script>