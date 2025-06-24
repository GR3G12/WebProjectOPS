<?php
    require '../../database/connectParams.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACTS OPS</title>
    <link rel="icon" type="image/png" href="../../img/logo.png">

    <!-- FontAwesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Piazzolla:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS for additional styles -->
    <link rel="stylesheet" href="../css/styles.css"> <!-- Adjust path -->
</head>
<body>
    <!-- Include Header -->
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="sidebar">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <main class="content">
            <?php
            if (isset($content)) {
                include $content;
            } else {
                echo "<p>No content found</p>";
            }
            ?>
        </main>
    </div>

    <!-- Include Footer -->
    <?php include 'footer.php'; ?>
    <!-- jQuery and Bootstrap JS -->
    <!-- <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>  -->

    <!-- Custom JS -->
    <!-- <script src="js/scripts.js"></script> -->
    
</body>
</html>
