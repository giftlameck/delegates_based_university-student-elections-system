<?php
session_name('student_portal');
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}

include 'includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['support_submit'])) {
    $student_id = $_SESSION['student_id'];
    $student_name = $_SESSION['student_name'] ?? '';
    $issue_type = $_POST['issue_type'];
    $priority = $_POST['priority'];
    $description = $_POST['description'];
    $contact_email = $_POST['contact_email'];
    $status = 'open';
    $created_at = date('Y-m-d H:i:s');

    try {
        // Insert support ticket into database
        $sql = "INSERT INTO election_support_tickets 
                (student_id, student_name, issue_type, priority, description, contact_email, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $student_id,
            $student_name,
            $issue_type,
            $priority,
            $description,
            $contact_email,
            $status,
            $created_at
        ]);

        // Get the inserted ticket ID
        $ticket_id = $conn->lastInsertId();

        // Set success message
        $_SESSION['message'] = "Your support ticket #$ticket_id has been submitted successfully!";
        $_SESSION['message_type'] = 'success';

        // JavaScript alert and redirect
        echo "<script>
                alert('Support ticket submitted successfully!\\nTicket ID: #$ticket_id');
                window.location.href = 'elections.php?section=support';
              </script>";
        exit();

    } catch (PDOException $e) {
        $_SESSION['message'] = "Error submitting support ticket: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        
        echo "<script>
                alert('Error submitting support ticket. Please try again.');
                window.location.href = 'elections.php?section=support';
              </script>";
        exit();
    }
} else {
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = 'danger';
    header('Location: elections.php?section=support');
    exit();
}
?>