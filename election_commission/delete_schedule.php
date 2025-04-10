<?php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

$schedule_id = $_GET['id'] ?? null;

if ($schedule_id) {
    $sql = "DELETE FROM election_schedule WHERE schedule_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$schedule_id]);
    echo "<script>alert('Schedule deleted successfully'); window.location.href='schedule_elections.php';</script>";
}
?>
