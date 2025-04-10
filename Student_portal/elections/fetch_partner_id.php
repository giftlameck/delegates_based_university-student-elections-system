<?php
// fetch_partner_id.php
session_name('student_portal');
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}
include '../includes/db_connection.php';

$student_id = $_GET['student_id'];
$position = $_GET['position'];

if ($position === 'Chairperson') {
    // Fetch Vice Chairperson's Student ID for the given Chairperson
    $sql = "SELECT student_id FROM applications WHERE vice_chairperson_id = :student_id AND position = 'Vice Chairperson'";
} elseif ($position === 'Vice Chairperson') {
    // Fetch Chairperson's Student ID for the given Vice Chairperson
    $sql = "SELECT student_id FROM applications WHERE chairperson_id = :student_id AND position = 'Chairperson'";
}

$stmt = $conn->prepare($sql);
$stmt->execute(['student_id' => $student_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo $result['student_id']; // Return the partner's Student ID
} else {
    echo ''; // Return empty if no partner is found
}
?>