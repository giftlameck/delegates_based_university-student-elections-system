<?php
session_name('student_portal');
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}
include 'includes/header.php';
include 'includes/db_connection.php';

// Fetch student details
$student_id = $_SESSION['student_id'];
$sql = "SELECT * FROM student_details WHERE Student_id = :student_id";
$stmt = $conn->prepare($sql);
$stmt->execute(['student_id' => $student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!-- Welcome Section -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">Welcome back, <?php echo $_SESSION['student_name']; ?>!</h2>
                            <p class="mb-0">Student ID: <?php echo $_SESSION['student_id']; ?></p>
                        </div>
                        <div class="text-right">
                            <h4 class="mb-1"><?php echo date('l, F j, Y'); ?></h4>
                            <p class="mb-0"><?php echo date('g:i A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">School</h5>
                    <h2 class="mb-0"><?php echo $_SESSION['school']; ?></h2>
                    <small>Programme: <?php echo $_SESSION['programme']; ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Academic Year</h5>
                    <h2 class="mb-0"><?php echo $student['Year']; ?></h2>
                    <small>Current Year</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Last Login</h5>
                    <h2 class="mb-0"><?php echo date('M d, Y'); ?></h2>
                    <small>Today at <?php echo date('g:i A'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Quick Access</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="grades.php" class="quick-access-item">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-graduation-cap fa-2x mb-3 text-primary"></i>
                                        <h6 class="card-title">Grades</h6>
                                        <p class="card-text small text-muted">View your academic performance</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="finance.php" class="quick-access-item">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-money-bill-wave fa-2x mb-3 text-success"></i>
                                        <h6 class="card-title">Finance</h6>
                                        <p class="card-text small text-muted">Check your financial status</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="elections.php" class="quick-access-item">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-vote-yea fa-2x mb-3 text-info"></i>
                                        <h6 class="card-title">Elections</h6>
                                        <p class="card-text small text-muted">Participate in student elections</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="profile.php" class="quick-access-item">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-circle fa-2x mb-3 text-warning"></i>
                                        <h6 class="card-title">Profile</h6>
                                        <p class="card-text small text-muted">Update your information</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding: 1rem;
}

.card-title {
    color: #2c3e50;
    font-weight: 600;
}

.quick-access-item {
    text-decoration: none;
    color: inherit;
    display: block;
}

.quick-access-item .card {
    transition: all 0.3s ease;
}

.quick-access-item:hover .card {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.quick-access-item .card-body {
    padding: 1.5rem;
}

.quick-access-item i {
    transition: transform 0.3s ease;
}

.quick-access-item:hover i {
    transform: scale(1.1);
}

@media (max-width: 768px) {
    .card {
        margin-bottom: 1rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>