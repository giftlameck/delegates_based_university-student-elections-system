<?php
// create_support_responses_table.php
session_name('election_commission');
session_start();
include 'includes/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['commission_id'])) {
    die('Unauthorized access');
}

try {
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
    
    echo "Table 'election_support_responses' created successfully!";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
} 