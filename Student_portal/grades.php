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
                    <h3>Grades</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Computer Science 101</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td>Mathematics 101</td>
                                <td>B+</td>
                            </tr>
                            <tr>
                                <td>Physics 101</td>
                                <td>A-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>