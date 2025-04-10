<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/db_connection.php';

try {
    // Create election_phases table if it doesn't exist
    $conn->exec("CREATE TABLE IF NOT EXISTS election_phases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phase_name VARCHAR(100) NOT NULL,
        description TEXT,
        start_date DATETIME NOT NULL,
        end_date DATETIME NOT NULL,
        status ENUM('Upcoming', 'Active', 'Completed') DEFAULT 'Upcoming',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create election_faqs table if it doesn't exist
    $conn->exec("CREATE TABLE IF NOT EXISTS election_faqs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question TEXT NOT NULL,
        answer TEXT NOT NULL,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create support_tickets table if it doesn't exist
    $conn->exec("CREATE TABLE IF NOT EXISTS support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('Open', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES student_details(student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Insert sample election phase if none exist
    $phase_count = $conn->query("SELECT COUNT(*) FROM election_phases")->fetchColumn();
    if ($phase_count == 0) {
        $conn->exec("INSERT INTO election_phases (phase_name, description, start_date, end_date, status) 
                    VALUES ('Nomination Period', 'Period for submitting nominations', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'Active')");
    }

    // Insert sample FAQs if none exist
    $faq_count = $conn->query("SELECT COUNT(*) FROM election_faqs")->fetchColumn();
    if ($faq_count == 0) {
        $conn->exec("INSERT INTO election_faqs (question, answer, display_order) VALUES 
                    ('Who can participate in the elections?', 'All registered students are eligible to participate in the elections.', 1),
                    ('How do I submit my nomination?', 'You can submit your nomination through the Apply Now section in the elections module.', 2),
                    ('When will the results be announced?', 'Results will be announced after the voting period ends and all votes are counted.', 3)");
    }

    echo "All tables have been set up successfully!";
    echo "<br><a href='elections.php'>Return to Elections Page</a>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    echo "<br>SQL State: " . $e->getCode();
}
?> 