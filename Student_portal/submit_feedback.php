<?php
session_name('student_portal');
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}

include 'includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['feedback_submit'])) {
    $student_id = $_SESSION['student_id'];
    $rating = $_POST['rating'];
    $feedback_type = $_POST['feedback_type'];
    $comments = $_POST['comments'];
    $suggestions = $_POST['suggestions'] ?? null;
    $created_at = date('Y-m-d H:i:s');

    // Check if student has already submitted feedback
    $stmt = $conn->prepare("SELECT * FROM election_feedback WHERE student_id = ?");
    $stmt->execute([$student_id]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['message'] = "You have already submitted feedback for this election.";
        $_SESSION['message_type'] = 'warning';
        header('Location: elections.php');
        exit();
    }

    try {
        // Insert feedback into database
        $sql = "INSERT INTO election_feedback (student_id, rating, feedback_type, comments, suggestions, created_at)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$student_id, $rating, $feedback_type, $comments, $suggestions, $created_at]);

        $_SESSION['message'] = "Thank you! Your feedback has been submitted successfully.";
        $_SESSION['message_type'] = 'success';
        
        echo "<script>
                alert('Feedback submitted successfully!');
                window.location.href = 'elections.php';
              </script>";
        exit();

    } catch (PDOException $e) {
        $_SESSION['message'] = "Error submitting feedback: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        echo "<script>
                alert('Error submitting feedback. Please try again.');
                window.location.href = 'elections.php';
              </script>";
        exit();
    }
} else {
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = 'danger';
    header('Location: elections.php');
    exit();
}
?>