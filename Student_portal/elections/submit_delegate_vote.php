<?php
// submit_delegate_vote.php
session_start();
include 'includes/db_connection.php'; // Corrected path

// Check if the student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_SESSION['student_id'];
    $delegate_id = $_POST['delegate_id'];
    $programme = $_SESSION['programme'];

    // Check if the student has already voted
    $sql = "SELECT * FROM delegate_votes WHERE voter_id = :student_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['student_id' => $student_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<script>alert('You have already voted for a delegate!'); window.location.href='elections.php';</script>";
        exit();
    }

    // Verify the delegate is approved and in the same programme
    $sql = "SELECT * FROM applications 
            WHERE student_id = :delegate_id 
            AND candidate_type = 'Delegate' 
            AND status = 'Approved' 
            AND programme = :programme";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'delegate_id' => $delegate_id,
        'programme' => $programme
    ]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<script>alert('Invalid delegate selection! The selected candidate is not approved or not in your programme.'); window.location.href='elections.php';</script>";
        exit();
    }

    // Record the vote
    $sql = "INSERT INTO delegate_votes (voter_id, delegate_id, programme, voted_at) 
            VALUES (:student_id, :delegate_id, :programme, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'student_id' => $student_id,
        'delegate_id' => $delegate_id,
        'programme' => $programme
    ]);

    echo "<script>alert('Vote submitted successfully! Thank you for voting.'); window.location.href='elections.php';</script>";
} else {
    // Redirect if accessed directly without POST
    header('Location: elections.php');
    exit();
}
?>