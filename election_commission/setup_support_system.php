<?php
// setup_support_system.php
session_name('election_commission');
session_start();
include 'includes/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['commission_username'])) {
    die('Unauthorized access');
}

try {
    // Create the election_support_tickets table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS election_support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        student_name VARCHAR(100) NOT NULL,
        student_email VARCHAR(100) NOT NULL,
        issue_type ENUM('technical', 'voting', 'candidate', 'other') NOT NULL,
        priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
        status ENUM('open', 'in_progress', 'closed') NOT NULL DEFAULT 'open',
        description TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sql);
    echo "Table 'election_support_tickets' created or already exists.<br>";
    
    // Create the election_support_responses table
    $sql = "CREATE TABLE IF NOT EXISTS election_support_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        responder_id INT NOT NULL,
        responder_name VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        is_solution TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES election_support_tickets(id) ON DELETE CASCADE
    )";
    
    $conn->exec($sql);
    echo "Table 'election_support_responses' created or already exists.<br>";
    
    echo "<br>Support system setup completed successfully!<br>";
    echo "<a href='manage_tickets.php'>Go to Support Tickets</a>";
} catch (PDOException $e) {
    echo "Error setting up support system: " . $e->getMessage();
} 