<?php
session_name('student_portal');
session_start();
include 'includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $password = $_POST['password'];

    // Fetch student details from the database
    $sql = "SELECT * FROM student_details WHERE Student_id = :student_id AND Password = :password";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['student_id' => $student_id, 'password' => $password]);

    // Check if a row was returned
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['student_id'] = $row['Student_id'];
        $_SESSION['student_name'] = $row['Student_Name'];
        $_SESSION['school'] = $row['School'];
        $_SESSION['programme'] = $row['Programme'];
        $_SESSION['gender'] = $row['Gender'];
        $_SESSION['year'] = $row['Year'];
        header('Location: dashboard.php');
    } else {
        echo "<script>alert('Invalid credentials!'); window.location.href='index.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Login</title>
    <!-- Local Bootstrap CSS -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            background: linear-gradient(135deg, #007bff, #0056b3);
            font-family: 'Arial', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        .card-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-bottom: none;
            padding: 20px;
        }
        .card-header h3 {
            margin: 0;
            font-weight: 600;
            color: #fff;
        }
        .card-body {
            background-color: #fff;
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 500;
            color: #333;
        }
        .form-control {
            border-radius: 8px;
            padding: 10px;
            border: 1px solid #ddd;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.3);
        }
        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-weight: 600;
            transition: background 0.3s ease, transform 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #007bff);
            transform: translateY(-2px);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card login-card">
                    <div class="card-header text-center">
                        <h3>Student Portal Login</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="student_id">Student ID</label>
                                <input type="text" class="form-control" id="student_id" name="student_id" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Local Bootstrap JS and dependencies -->
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>