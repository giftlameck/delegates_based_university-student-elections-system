<?php
session_name('student_portal');
session_start();
include 'includes/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}

// Get election schedule to check if results should be displayed
$sql = "SELECT start_date, end_date, results_visible FROM election_schedule WHERE event_type = 'student_council_voting' LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute();
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

// Only show results if they are marked as visible
if (!$schedule || !$schedule['results_visible']) {
    echo "<script>alert('Results are not yet available!'); window.location.href='elections.php';</script>";
    exit();
}

// Get results for each position
$positions = ['Chairperson', 'Secretary', 'Treasurer', 'Public Relations Officer'];
$results = [];

foreach ($positions as $position) {
    if ($position === 'Chairperson') {
        // For Chairperson, we need to get the ticket results
        $sql = "SELECT 
                    a.student_id as chairperson_id,
                    a.first_name as chairperson_first_name,
                    a.last_name as chairperson_last_name,
                    b.student_id as vice_chairperson_id,
                    b.first_name as vice_chairperson_first_name,
                    b.last_name as vice_chairperson_last_name,
                    COUNT(scv.ticket_id) as vote_count
                FROM applications a
                JOIN applications b ON a.vice_chairperson_id = b.student_id
                LEFT JOIN student_council_votes scv ON a.student_id = scv.ticket_id
                WHERE a.candidate_type = 'Student Council' 
                AND a.position = 'Chairperson' 
                AND a.status = 'Approved' 
                AND b.status = 'Approved'
                GROUP BY a.student_id, b.student_id
                ORDER BY vote_count DESC";
    } else {
        // For other positions
        $sql = "SELECT 
                    a.student_id,
                    a.first_name,
                    a.last_name,
                    COUNT(scv.ticket_id) as vote_count
                FROM applications a
                LEFT JOIN student_council_votes scv ON a.student_id = scv.ticket_id
                WHERE a.candidate_type = 'Student Council' 
                AND a.position = :position 
                AND a.status = 'Approved'
                GROUP BY a.student_id
                ORDER BY vote_count DESC";
    }
    
    $stmt = $conn->prepare($sql);
    if ($position !== 'Chairperson') {
        $stmt->execute(['position' => $position]);
    } else {
        $stmt->execute();
    }
    $results[$position] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Council Election Results</title>
    <link rel="stylesheet" href="styles/style.css">
    <style>
        .results-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 20px;
        }
        .position-section {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .position-title {
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        .candidate-list {
            list-style: none;
            padding: 0;
        }
        .candidate-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .winner {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .vote-count {
            font-weight: bold;
            color: #2c3e50;
        }
        .ticket-info {
            display: flex;
            flex-direction: column;
        }
        .ticket-member {
            margin: 2px 0;
        }
    </style>
</head>
<body>
    <div class="results-container">
        <h1>Student Council Election Results</h1>
        
        <?php foreach ($positions as $position): ?>
            <div class="position-section">
                <h2 class="position-title"><?php echo htmlspecialchars($position); ?></h2>
                <ul class="candidate-list">
                    <?php 
                    $max_votes = 0;
                    foreach ($results[$position] as $candidate) {
                        if ($candidate['vote_count'] > $max_votes) {
                            $max_votes = $candidate['vote_count'];
                        }
                    }
                    
                    foreach ($results[$position] as $candidate): 
                        $is_winner = $candidate['vote_count'] == $max_votes && $max_votes > 0;
                    ?>
                        <li class="candidate-item <?php echo $is_winner ? 'winner' : ''; ?>">
                            <?php if ($position === 'Chairperson'): ?>
                                <div class="ticket-info">
                                    <div class="ticket-member">
                                        Chairperson: <?php echo htmlspecialchars($candidate['chairperson_first_name'] . ' ' . $candidate['chairperson_last_name']); ?>
                                    </div>
                                    <div class="ticket-member">
                                        Vice Chairperson: <?php echo htmlspecialchars($candidate['vice_chairperson_first_name'] . ' ' . $candidate['vice_chairperson_last_name']); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="candidate-name">
                                    <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="vote-count">
                                <?php echo $candidate['vote_count']; ?> votes
                                <?php if ($is_winner): ?>
                                    <span style="color: #28a745;">(Winner)</span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="elections.php" class="btn btn-primary">Back to Elections</a>
        </div>
    </div>
</body>
</html> 