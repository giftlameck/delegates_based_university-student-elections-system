<?php
session_name('student_portal');
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Home</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <h2>Welcome, <?php echo $_SESSION['student_name']; ?>!</h2>
                <p>Student ID: <?php echo $_SESSION['student_id']; ?></p>
                <p>School: <?php echo $_SESSION['school']; ?></p>
                <p>Programme: <?php echo $_SESSION['programme']; ?></p>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>