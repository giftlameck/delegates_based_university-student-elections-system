<?php
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Commission</title>
    <!-- Local Bootstrap CSS -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Arial', sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #007bff, #0056b3);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            padding: 10px 20px;
        }
        .navbar-brand {
            font-size: 24px;
            font-weight: 600;
            color: #fff !important;
            transition: color 0.3s ease;
        }
        .navbar-brand:hover {
            color: #ffdd57 !important;
        }
        .navbar-toggler {
            border: none;
            outline: none;
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba(255, 255, 255, 0.8)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
        }
        .navbar-nav .nav-item {
            margin: 0 10px;
        }
        .navbar-nav .nav-link {
            color: #fff !important;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 8px;
            transition: background 0.3s ease, color 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffdd57 !important;
        }
        .navbar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: #ffdd57 !important;
        }
        .navbar-nav .nav-link.logout {
            background: #dc3545;
            color: #fff !important;
        }
        .navbar-nav .nav-link.logout:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <a class="navbar-brand" href="dashboard.php">Election Commission</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="manage_applications.php">Manage Applications</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_guidelines.php">Manage Guidelines</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="upload_forms.php">Upload Forms</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="schedule_elections.php">Schedule Elections</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="delegate_results.php">Delegate Results</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link logout" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Local Bootstrap JS and dependencies -->
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>