<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal</title>
    <!-- Local CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        @font-face {
            font-family: "Font Awesome 5 Free";
            font-style: normal;
            font-weight: 900;
            src: url("../assets/webfonts/fa-solid-900.eot");
            src: url("../assets/webfonts/fa-solid-900.eot?#iefix") format("embedded-opentype"),
                 url("../assets/webfonts/fa-solid-900.woff2") format("woff2"),
                 url("../assets/webfonts/fa-solid-900.woff") format("woff"),
                 url("../assets/webfonts/fa-solid-900.ttf") format("truetype");
        }

        @font-face {
            font-family: "Font Awesome 5 Free";
            font-style: normal;
            font-weight: 400;
            src: url("../assets/webfonts/fa-regular-400.eot");
            src: url("../assets/webfonts/fa-regular-400.eot?#iefix") format("embedded-opentype"),
                 url("../assets/webfonts/fa-regular-400.woff2") format("woff2"),
                 url("../assets/webfonts/fa-regular-400.woff") format("woff"),
                 url("../assets/webfonts/fa-regular-400.ttf") format("truetype");
        }
        
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: #1a237e;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: #0d47a1;
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .menu-item {
            padding: 15px 20px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            font-weight: 500;
        }
        
        .menu-item a {
            color: inherit;
            text-decoration: none;
            width: 100%;
            display: flex;
            align-items: center;
        }
        
        .menu-item:hover, .menu-item.active {
            background: #283593;
            color: #ffffff;
            border-left: 4px solid #64b5f6;
        }
        
        .menu-item i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .menu-item:hover i, .menu-item.active i {
            color: #64b5f6;
        }
        
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }
        
        .top-navbar {
            background: white;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .student-info {
            display: flex;
            align-items: center;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
            border: 2px solid #1a237e;
        }
        
        .student-details h5 {
            margin: 0;
            font-size: 1rem;
            color: #1a237e;
            font-weight: 600;
        }
        
        .student-details small {
            color: #6c757d;
        }
        
        .logout-btn {
            padding: 8px 15px;
            border-radius: 5px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .logout-btn i {
            margin-right: 5px;
        }
        
        .logout-btn:hover {
            background: #c82333;
            color: white;
            text-decoration: none;
        }
        
        .student-avatar-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 15px;
            background-color: #e3f2fd;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #1a237e;
        }
        
        .student-avatar-icon i {
            font-size: 24px;
            color: #1a237e;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -var(--sidebar-width);
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3>Student Portal</h3>
            </div>
            <ul class="list-unstyled">
                <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'grades.php' ? 'active' : ''; ?>">
                    <a href="grades.php"><i class="fas fa-graduation-cap"></i> Grades</a>
                </li>
                <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'finance.php' ? 'active' : ''; ?>">
                    <a href="finance.php"><i class="fas fa-money-bill-wave"></i> Finance</a>
                </li>
                <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'elections.php' ? 'active' : ''; ?>">
                    <a href="elections.php"><i class="fas fa-vote-yea"></i> Elections</a>
                </li>
                <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                </li>
                <li class="menu-item">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </nav>

        <div class="main-content">
            <div class="top-navbar">
                <div class="student-info">
                    <?php
                    // Check if student has a photo
                    if (isset($_SESSION['student_photo']) && file_exists($_SESSION['student_photo'])) {
                        echo '<img src="' . $_SESSION['student_photo'] . '" alt="Student Avatar" class="student-avatar">';
                    } else {
                        echo '<div class="student-avatar-icon"><i class="fas fa-user-circle"></i></div>';
                    }
                    ?>
                    <div class="student-details">
                        <h5><?php echo $_SESSION['student_name']; ?></h5>
                        <small>Student ID: <?php echo $_SESSION['student_id']; ?></small>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
        </div>

            <div class="content-wrapper">
                <!-- Local JavaScript -->
                <script src="assets/js/jquery.min.js"></script>
                <script src="assets/js/popper.min.js"></script>
                <script src="assets/js/bootstrap.min.js"></script>
                <script>
                    $(document).ready(function() {
                        $('#menu-toggle').click(function() {
                            $('.sidebar').toggleClass('active');
                        });
                    });
                </script>