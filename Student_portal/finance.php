<?php
session_name('student_portal');
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}
include 'includes/header.php';
?>
<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h3>Finance</h3>
                </div>
                <div class="card-body">
                    <p><strong>Total Fees:</strong> KES 50,000</p>
                    <p><strong>Amount Paid:</strong> KES 30,000</p>
                    <p><strong>Balance:</strong> KES 20,000</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>