<?php
session_name('election_commission');
session_start();
include 'includes/db_connection.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['commission_username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate input
if (!isset($_POST['ticket_id']) || !isset($_POST['message'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$ticket_id = (int)$_POST['ticket_id'];
$message = trim($_POST['message']);
$is_solution = isset($_POST['is_solution']) ? 1 : 0;

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Response message cannot be empty']);
    exit;
}

try {
    // Check if the responses table exists
    $tableExists = false;
    try {
        $conn->query("SELECT 1 FROM election_support_responses LIMIT 1");
        $tableExists = true;
    } catch (PDOException $e) {
        // Table doesn't exist
        $tableExists = false;
    }
    
    // If table doesn't exist, create it
    if (!$tableExists) {
        $sql = "CREATE TABLE IF NOT EXISTS election_support_responses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            responder_id VARCHAR(50) NOT NULL,
            responder_name VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            is_solution TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES election_support_tickets(id) ON DELETE CASCADE
        )";
        $conn->exec($sql);
    }
    
    // Start transaction
    $conn->beginTransaction();

    // Add response
    $sql = "INSERT INTO election_support_responses (ticket_id, responder_id, responder_name, message, is_solution, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $ticket_id,
        $_SESSION['commission_username'],
        $_SESSION['commission_username'], // Use username as name if commission_name is not set
        $message,
        $is_solution
    ]);

    // If marked as solution, update ticket status to closed
    if ($is_solution) {
        $sql = "UPDATE election_support_tickets SET status = 'closed', updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$ticket_id]);
    } else {
        // Update ticket status to in_progress if it's open
        $sql = "UPDATE election_support_tickets SET status = 'in_progress', updated_at = NOW() WHERE id = ? AND status = 'open'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$ticket_id]);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Response added successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 