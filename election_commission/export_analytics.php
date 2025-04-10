<?php
// export_analytics.php (in C:\xampp1\htdocs\My_Election_system\Election_commission\)
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="election_analytics_full.csv"');

$output = fopen('php://output', 'w');

// Overview
fputcsv($output, ['Section', 'Metric', 'Value']);
fputcsv($output, ['Overview', 'Total Students', $conn->query("SELECT COUNT(*) FROM student_details")->fetchColumn()]);
fputcsv($output, ['Overview', 'Total Delegates', $conn->query("SELECT COUNT(*) FROM delegate_winners")->fetchColumn()]);
fputcsv($output, ['Overview', 'Delegate Voters', $conn->query("SELECT COUNT(DISTINCT voter_id) FROM delegate_votes")->fetchColumn()]);
fputcsv($output, ['Overview', 'Council Voters', $conn->query("SELECT COUNT(DISTINCT voter_id) FROM student_council_votes")->fetchColumn()]);

// Programme-wise Turnout
fputcsv($output, ['', '']);
fputcsv($output, ['Programme-wise Turnout', 'Programme', 'Voters', 'Total Students', 'Turnout (%)']);
$programme_turnout = $conn->query("SELECT s.programme, COUNT(DISTINCT dv.voter_id) AS voters, COUNT(DISTINCT s.student_id) AS total
                                   FROM student_details s
                                   LEFT JOIN delegate_votes dv ON s.student_id = dv.voter_id
                                   GROUP BY s.programme")->fetchAll(PDO::FETCH_ASSOC);
foreach ($programme_turnout as $row) {
    $turnout = $row['total'] > 0 ? round(($row['voters'] / $row['total']) * 100, 1) : 0;
    fputcsv($output, ['', $row['programme'], $row['voters'], $row['total'], $turnout]);
}

// Delegate Votes
fputcsv($output, ['', '']);
fputcsv($output, ['Delegate Votes', 'Candidate', 'Programme', 'Votes']);
$delegate_votes = $conn->query("SELECT a.student_name, a.programme, COUNT(dv.delegate_id) AS vote_count
                                FROM applications a
                                LEFT JOIN delegate_votes dv ON a.student_id = dv.delegate_id
                                WHERE a.candidate_type = 'Delegate' AND a.status = 'Approved'
                                GROUP BY a.student_id, a.student_name, a.programme")->fetchAll(PDO::FETCH_ASSOC);
foreach ($delegate_votes as $vote) {
    fputcsv($output, ['', $vote['student_name'], $vote['programme'], $vote['vote_count']]);
}

// Student Council Votes
fputcsv($output, ['', '']);
fputcsv($output, ['Student Council Votes', 'Candidate/Ticket', 'Position', 'Votes']);
$council_votes = $conn->query("SELECT a.student_name, a.position, COUNT(scv.ticket_id) AS vote_count
                               FROM applications a
                               LEFT JOIN student_council_votes scv ON a.student_id = scv.ticket_id
                               WHERE a.candidate_type = 'Student Council' AND a.status = 'Approved'
                               GROUP BY a.student_id, a.student_name, a.position")->fetchAll(PDO::FETCH_ASSOC);
foreach ($council_votes as $vote) {
    fputcsv($output, ['', $vote['student_name'], $vote['position'], $vote['vote_count']]);
}

fclose($output);
exit();