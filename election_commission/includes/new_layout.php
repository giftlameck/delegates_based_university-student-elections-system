<?php
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF'], '.php');
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
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --primary-color: #1a237e;
            --secondary-color: #283593;
            --hover-color: #3949ab;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f6f9;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--primary-color);
            color: white;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto; /* Make sidebar scrollable */
        }

        .sidebar-header {
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 20px;
            background: var(--secondary-color);
            font-size: 1.2rem;
            font-weight: bold;
            position: sticky; /* Keep header visible when scrolling */
            top: 0;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 12px 20px;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .menu-item:hover {
            background: var(--hover-color);
            color: #fff;
            text-decoration: none;
        }

        .menu-item.active {
            background: var(--hover-color);
            border-left: 4px solid #fff;
        }

        .menu-item .icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Area */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
        }

        /* Header */
        .main-header {
            height: var(--header-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .user-profile {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .logout-btn:hover {
            background: #c82333;
            color: white;
            text-decoration: none;
        }

        /* Dashboard Cards */
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1001;
                background: var(--primary-color);
                color: white;
                border: none;
                padding: 8px;
                border-radius: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-toggle d-md-none">
        <span class="icon">☰</span>
    </button>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            Election Commission
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="icon">📊</span>
                Dashboard
            </a>
            <a href="manage_applications.php" class="menu-item <?php echo $current_page === 'manage_applications' ? 'active' : ''; ?>">
                <span class="icon">📝</span>
                Applications
            </a>
            <a href="schedule_elections.php" class="menu-item <?php echo $current_page === 'schedule_elections' ? 'active' : ''; ?>">
                <span class="icon">📅</span>
                Schedule Elections
            </a>
            <a href="delegate_results.php" class="menu-item <?php echo $current_page === 'delegate_results' ? 'active' : ''; ?>">
                <span class="icon">👥</span>
                Delegate Results
            </a>
            <a href="student_council_results.php" class="menu-item <?php echo $current_page === 'student_council_results' ? 'active' : ''; ?>">
                <span class="icon">👑</span>
                Student Council Results
            </a>
            <a href="election_reports.php" class="menu-item <?php echo $current_page === 'election_reports' ? 'active' : ''; ?>">
                <span class="icon">📈</span>
                Election Reports
            </a>
            <a href="manage_guidelines.php" class="menu-item <?php echo $current_page === 'manage_guidelines' ? 'active' : ''; ?>">
                <span class="icon">📚</span>
                Guidelines
            </a>
            <a href="certificates.php" class="menu-item <?php echo $current_page === 'certificates' ? 'active' : ''; ?>">
                <span class="icon">📜</span>
                Certificates
            </a>
            <a href="manage_feedback.php" class="menu-item <?php echo $current_page === 'manage_feedback' ? 'active' : ''; ?>">
                <span class="icon">💬</span>
                Feedback
            </a>
            <a href="manage_tickets.php" class="menu-item <?php echo $current_page === 'manage_tickets' ? 'active' : ''; ?>">
                <span class="icon">🎫</span>
                Support Tickets
            </a>
            <a href="ticket_reports.php" class="menu-item <?php echo $current_page === 'ticket_reports' ? 'active' : ''; ?>">
                <span class="icon">📈</span>
                Ticket Reports
            </a>
            <a href="logout.php" class="menu-item">
                <span class="icon">🚪</span>
                Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="main-header">
            <div class="user-profile">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['commission_username']); ?></span>
                <a href="logout.php" class="logout-btn">
                    <span class="icon icon-logout"></span> Logout
                </a>
            </div>
        </div>

        <!-- Content will be injected here -->
        <div class="content-wrapper"> 