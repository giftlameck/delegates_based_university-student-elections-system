<?php
session_name('student_portal');
session_start();
// Unset only the Student Portal session variables
unset($_SESSION['student_id']);
unset($_SESSION['student_name']);
unset($_SESSION['school']);
unset($_SESSION['programme']);
session_destroy(); // Destroy the Student Portal session
header('Location: index.php');
exit();
?>