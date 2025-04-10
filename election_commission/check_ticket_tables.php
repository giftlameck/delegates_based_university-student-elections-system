<?php
session_name('election_commission');
session_start();
include 'includes/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['commission_username'])) {
    die('Unauthorized access');
}

echo "<h2>Checking Support Ticket Tables</h2>";

// Check election_support_tickets table
try {
    $sql = "DESCRIBE election_support_tickets";
    $result = $conn->query($sql);
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>election_support_tickets Table Structure:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>$column</li>";
    }
    echo "</ul>";
    
    // Check if status column is an enum
    $sql = "SHOW COLUMNS FROM election_support_tickets WHERE Field = 'status'";
    $result = $conn->query($sql);
    $statusColumn = $result->fetch(PDO::FETCH_ASSOC);
    
    if ($statusColumn['Type'] !== "enum('open','in_progress','closed')") {
        echo "<p>Updating status column to enum...</p>";
        $sql = "ALTER TABLE election_support_tickets MODIFY COLUMN status ENUM('open', 'in_progress', 'closed') NOT NULL DEFAULT 'open'";
        $conn->exec($sql);
        echo "<p>Status column updated successfully!</p>";
    } else {
        echo "<p>Status column is already an enum.</p>";
    }
} catch (PDOException $e) {
    echo "<p>Error checking election_support_tickets table: " . $e->getMessage() . "</p>";
}

// Check election_support_responses table
try {
    $tableExists = false;
    try {
        $conn->query("SELECT 1 FROM election_support_responses LIMIT 1");
        $tableExists = true;
    } catch (PDOException $e) {
        // Table doesn't exist
        $tableExists = false;
    }
    
    if (!$tableExists) {
        echo "<p>Creating election_support_responses table...</p>";
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
        echo "<p>election_support_responses table created successfully!</p>";
    } else {
        echo "<p>election_support_responses table already exists.</p>";
        
        // Check table structure
        $sql = "DESCRIBE election_support_responses";
        $result = $conn->query($sql);
        $columns = $result->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>election_support_responses Table Structure:</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>$column</li>";
        }
        echo "</ul>";
        
        // Check if responder_id is VARCHAR
        $sql = "SHOW COLUMNS FROM election_support_responses WHERE Field = 'responder_id'";
        $result = $conn->query($sql);
        $responderIdColumn = $result->fetch(PDO::FETCH_ASSOC);
        
        if (strpos($responderIdColumn['Type'], 'varchar') === false) {
            echo "<p>Updating responder_id column to VARCHAR...</p>";
            $sql = "ALTER TABLE election_support_responses MODIFY COLUMN responder_id VARCHAR(50) NOT NULL";
            $conn->exec($sql);
            echo "<p>responder_id column updated successfully!</p>";
        } else {
            echo "<p>responder_id column is already VARCHAR.</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p>Error checking election_support_responses table: " . $e->getMessage() . "</p>";
}

echo "<p><a href='manage_tickets.php'>Return to Manage Tickets</a></p>"; 