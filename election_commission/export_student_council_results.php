<?php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}

include 'includes/db_connection.php';

// Get format parameter
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Check voting schedule
$schedule = $conn->query("SELECT end_date FROM election_schedule WHERE event_type = 'student_council_voting' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$voting_closed = $schedule && (new DateTime() > new DateTime($schedule['end_date']));

// Fetch all positions, excluding Vice Chairperson explicitly
$positions_sql = "SELECT DISTINCT position FROM applications WHERE candidate_type = 'Student Council' AND status = 'Approved' AND position != 'Vice Chairperson'";
$positions = $conn->query($positions_sql)->fetchAll(PDO::FETCH_COLUMN);

// Fetch results and winners per position
$results = [];
$winners = [];
$total_votes = 0;
$voters = $conn->query("SELECT COUNT(DISTINCT voter_id) FROM student_council_votes")->fetchColumn();
$eligible_voters = $conn->query("SELECT COUNT(*) FROM delegate_winners")->fetchColumn();
$turnout = $eligible_voters > 0 ? round(($voters / $eligible_voters) * 100, 1) : 0;

foreach ($positions as $position) {
    if ($position === 'Chairperson') {
        $sql = "SELECT a.student_id AS chair_id, a.student_name AS chair_name, 
                       b.student_id AS vice_id, b.student_name AS vice_name, 
                       COUNT(scv.ticket_id) AS vote_count
                FROM applications a
                JOIN applications b ON a.vice_chairperson_id = b.student_id
                LEFT JOIN student_council_votes scv ON a.student_id = scv.ticket_id
                WHERE a.candidate_type = 'Student Council' AND a.position = 'Chairperson' 
                AND a.status = 'Approved' AND b.status = 'Approved'
                GROUP BY a.student_id, a.student_name, b.student_id, b.student_name
                ORDER BY vote_count DESC";
    } else {
        $sql = "SELECT a.student_id, a.student_name, COUNT(scv.ticket_id) AS vote_count
                FROM applications a
                LEFT JOIN student_council_votes scv ON a.student_id = scv.ticket_id
                WHERE a.candidate_type = 'Student Council' AND a.position = :position AND a.status = 'Approved'
                GROUP BY a.student_id, a.student_name
                ORDER BY vote_count DESC";
    }
    $stmt = $conn->prepare($sql);
    if ($position !== 'Chairperson') $stmt->execute(['position' => $position]);
    else $stmt->execute();
    $position_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results[$position] = $position_results;
    $total_votes += array_sum(array_column($position_results, 'vote_count'));

    // Determine winner (highest votes, no tie, or unopposed)
    if (!empty($position_results) && $voting_closed) {
        $approved_count = count($position_results);
        $top_votes = $position_results[0]['vote_count'];
        $tied = count(array_filter($position_results, fn($r) => $r['vote_count'] === $top_votes)) > 1;

        if ($approved_count === 1 && $top_votes == 0) {
            // Unopposed: 1 candidate/ticket, no votes
            $winners[$position] = $position_results[0];
            $winners[$position]['vote_count'] = 0;
            $winners[$position]['status'] = 'unopposed';
        } elseif (!$tied && $top_votes > 0) {
            // Voted winner: Highest votes, no tie
            $winners[$position] = $position_results[0];
            $winners[$position]['status'] = 'elected';
        }
    }
}

if ($format === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="student_council_results.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel display
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers for results
    fputcsv($output, ['STUDENT COUNCIL ELECTION RESULTS']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, ['']);
    
    // Add election statistics
    fputcsv($output, ['ELECTION STATISTICS']);
    fputcsv($output, ['Total Votes Cast:', $total_votes]);
    fputcsv($output, ['Voter Turnout:', $turnout . '% (' . $voters . ' of ' . $eligible_voters . ' delegates voted)']);
    fputcsv($output, ['']);
    
    // Add results by position
    fputcsv($output, ['RESULTS BY POSITION']);
    fputcsv($output, ['Position', 'Candidate/Ticket', 'Votes', 'Rank']);
    
    foreach ($results as $position => $position_results) {
        foreach ($position_results as $rank => $result) {
            if ($position === 'Chairperson') {
                fputcsv($output, [
                    'Chairperson & Vice Chairperson',
                    $result['chair_name'] . " & " . $result['vice_name'],
                    $result['vote_count'],
                    $rank + 1
                ]);
            } else {
                fputcsv($output, [
                    $position,
                    $result['student_name'],
                    $result['vote_count'],
                    $rank + 1
                ]);
            }
        }
        fputcsv($output, ['']); // Empty row between positions
    }
    
    // Add winners section
    fputcsv($output, ['ELECTED STUDENT COUNCIL']);
    fputcsv($output, ['Position', 'Candidate/Ticket', 'Votes', 'Status']);
    
    foreach ($positions as $position) {
        if (isset($winners[$position])) {
            if ($position === 'Chairperson') {
                fputcsv($output, [
                    'Chairperson & Vice Chairperson',
                    $winners[$position]['chair_name'] . " & " . $winners[$position]['vice_name'],
                    $winners[$position]['vote_count'],
                    $winners[$position]['status'] === 'unopposed' ? 'Unopposed' : 'Elected'
                ]);
            } else {
                fputcsv($output, [
                    $position,
                    $winners[$position]['student_name'],
                    $winners[$position]['vote_count'],
                    $winners[$position]['status'] === 'unopposed' ? 'Unopposed' : 'Elected'
                ]);
            }
        } else {
            fputcsv($output, [
                $position,
                'No winner',
                'N/A',
                'Tie - Run-off TBD'
            ]);
        }
    }
    
    fclose($output);
    exit();
} else if ($format === 'pdf') {
    // Check if TCPDF is available
    if (!file_exists('assets/tcpdf/tcpdf.php')) {
        $_SESSION['error'] = "PDF export requires TCPDF library. Please contact the administrator.";
        header('Location: student_council_results.php');
        exit();
    }
    
    require_once('assets/tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Election Commission');
    $pdf->SetTitle('Student Council Election Results');
    
    // Set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Student Council Election Results', 'Generated on ' . date('Y-m-d H:i:s'));
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Create the content
    $html = '<h1 style="text-align: center;">Student Council Election Results</h1>';
    $html .= '<p style="text-align: center;">Generated on ' . date('Y-m-d H:i:s') . '</p>';
    
    // Election Statistics
    $html .= '<h2>Election Statistics</h2>';
    $html .= '<table border="1" cellpadding="4">
        <tr>
            <th><b>Total Votes Cast</b></th>
            <td>' . $total_votes . '</td>
        </tr>
        <tr>
            <th><b>Voter Turnout</b></th>
            <td>' . $turnout . '% (' . $voters . ' of ' . $eligible_voters . ' delegates voted)</td>
        </tr>
    </table><br>';
    
    // Results by Position
    $html .= '<h2>Results by Position</h2>';
    foreach ($results as $position => $position_results) {
        $html .= '<h3>' . ($position === 'Chairperson' ? 'Chairperson & Vice Chairperson' : htmlspecialchars($position)) . '</h3>';
        $html .= '<table border="1" cellpadding="4">
            <tr style="background-color: #f8f9fa;">
                <th><b>Rank</b></th>
                <th><b>Candidate/Ticket</b></th>
                <th><b>Votes</b></th>
            </tr>';
        
        foreach ($position_results as $rank => $result) {
            $html .= '<tr>
                <td>' . ($rank + 1) . '</td>
                <td>';
            if ($position === 'Chairperson') {
                $html .= htmlspecialchars($result['chair_name']) . " & " . htmlspecialchars($result['vice_name']);
            } else {
                $html .= htmlspecialchars($result['student_name']);
            }
            $html .= '</td>
                <td>' . $result['vote_count'] . '</td>
            </tr>';
        }
        
        $html .= '</table><br>';
    }
    
    // Elected Winners
    $html .= '<h2>Elected Student Council</h2>';
    $html .= '<table border="1" cellpadding="4">
        <tr style="background-color: #f8f9fa;">
            <th><b>Position</b></th>
            <th><b>Candidate/Ticket</b></th>
            <th><b>Votes</b></th>
            <th><b>Status</b></th>
        </tr>';
    
    foreach ($positions as $position) {
        $html .= '<tr>
            <td>' . ($position === 'Chairperson' ? 'Chairperson & Vice Chairperson' : htmlspecialchars($position)) . '</td>
            <td>';
        
        if (isset($winners[$position])) {
            if ($position === 'Chairperson') {
                $html .= htmlspecialchars($winners[$position]['chair_name']) . " & " . htmlspecialchars($winners[$position]['vice_name']);
            } else {
                $html .= htmlspecialchars($winners[$position]['student_name']);
            }
            $html .= '</td>
                <td>' . $winners[$position]['vote_count'] . '</td>
                <td>' . ($winners[$position]['status'] === 'unopposed' ? 'Unopposed' : 'Elected') . '</td>';
        } else {
            $html .= 'No winner</td>
                <td>N/A</td>
                <td>Tie - Run-off TBD</td>';
        }
        
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    // Output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output('student_council_results.pdf', 'D');
    exit();
} 