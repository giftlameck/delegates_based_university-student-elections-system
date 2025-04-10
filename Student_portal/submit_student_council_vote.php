<?php
session_name('student_portal');
session_start();
include 'includes/db_connection.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $voter_id = $_SESSION['student_id'];
    $position = $_POST['position'];
    $candidate_id = isset($_POST['ticket_id']) ? $_POST['ticket_id'] : $_POST['candidate_id'];

    // Verify voter is a delegate
    $sql = "SELECT * FROM delegate_winners WHERE delegate_id = :voter_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['voter_id' => $voter_id]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<script>alert('Only elected delegates can vote!'); window.location.href='elections.php';</script>";
        exit();
    }

    // Check voting schedule
    $sql = "SELECT start_date, end_date FROM election_schedule WHERE event_type = 'student_council_voting' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_date = new DateTime();
    if (!$schedule || $current_date < new DateTime($schedule['start_date']) || $current_date > new DateTime($schedule['end_date'])) {
        echo "<script>alert('Voting is not currently open!'); window.location.href='elections.php';</script>";
        exit();
    }

    // Check if delegate has already voted for this position
    $sql = "SELECT * FROM student_council_votes WHERE voter_id = :voter_id AND position = :position";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['voter_id' => $voter_id, 'position' => $position]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<script>alert('You have already voted for $position!'); window.location.href='elections.php';</script>";
        exit();
    }

    // Verify the candidate/ticket
    if ($position === 'Chairperson') {
        $sql = "SELECT * FROM applications a
                JOIN applications b ON a.vice_chairperson_id = b.student_id
                WHERE a.student_id = :candidate_id AND a.candidate_type = 'Student Council' 
                AND a.position = 'Chairperson' AND a.status = 'Approved' AND b.status = 'Approved'";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['candidate_id' => $candidate_id]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<script>alert('Invalid ticket selection for Chairperson!'); window.location.href='elections.php';</script>";
            exit();
        }
    } else {
        $sql = "SELECT * FROM applications 
                WHERE student_id = :candidate_id AND candidate_type = 'Student Council' 
                AND position = :position AND status = 'Approved'";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['candidate_id' => $candidate_id, 'position' => $position]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<script>alert('Invalid selection for $position!'); window.location.href='elections.php';</script>";
            exit();
        }
    }

    // Record the vote (fresh preparation to avoid parameter mismatch)
    $sql = "INSERT INTO student_council_votes (voter_id, ticket_id, position, voted_at) 
            VALUES (:voter_id, :ticket_id, :position, NOW())";
    $stmt = $conn->prepare($sql); // Re-prepare to ensure correct parameter binding
    $params = [
        'voter_id' => $voter_id,
        'ticket_id' => $candidate_id,
        'position' => $position
    ];
    try {
        $stmt->execute($params);
        echo "<script>alert('Vote for $position submitted successfully!'); window.location.href='elections.php?autoload=student_council_voting';</script>";
    } catch (PDOException $e) {
        // Debug output (remove after testing)
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='elections.php';</script>";
        exit();
    }
} else {
    header('Location: elections.php');
    exit();
}
?>