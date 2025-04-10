<?php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}

include 'includes/db_connection.php';

// Check if both voting periods have ended
$delegate_schedule = $conn->query("SELECT end_date FROM election_schedule WHERE event_type = 'delegate_voting' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$council_schedule = $conn->query("SELECT end_date FROM election_schedule WHERE event_type = 'student_council_voting' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Check if schedules exist before checking end dates
$delegate_schedule_exists = !empty($delegate_schedule);
$council_schedule_exists = !empty($council_schedule);

$delegate_voting_ended = $delegate_schedule_exists && (new DateTime() > new DateTime($delegate_schedule['end_date']));
$council_voting_ended = $council_schedule_exists && (new DateTime() > new DateTime($council_schedule['end_date']));

if (!$delegate_schedule_exists || !$council_schedule_exists || !$delegate_voting_ended || !$council_voting_ended) {
    header('Location: election_reports.php');
    exit();
}

// Fetch statistics
$stats = array();

// Total registered voters
$stmt = $conn->query("SELECT COUNT(*) as total FROM student_details");
$stats['total_voters'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total votes cast
$stmt = $conn->query("SELECT COUNT(DISTINCT voter_id) as total FROM delegate_votes");
$stats['delegate_votes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(DISTINCT voter_id) as total FROM student_council_votes");
$stats['council_votes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get total delegate winners
$stmt = $conn->query("SELECT COUNT(*) as total FROM delegate_winners");
$stats['total_delegate_winners'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate turnout percentages
$stats['delegate_turnout'] = $stats['total_voters'] > 0 ? round(($stats['delegate_votes'] / $stats['total_voters']) * 100, 1) : 0;
$stats['council_turnout'] = $stats['total_delegate_winners'] > 0 ? round(($stats['council_votes'] / $stats['total_delegate_winners']) * 100, 1) : 0;

// Fetch voting patterns by school
$school_stats = $conn->query("
    SELECT 
        sd.school,
        COUNT(DISTINCT sd.Student_id) as total_students,
        COUNT(DISTINCT dv.voter_id) as delegate_voters,
        COUNT(DISTINCT scv.voter_id) as council_voters,
        (SELECT COUNT(*) FROM delegate_winners dw 
         WHERE dw.school = sd.school) as delegate_winners
    FROM student_details sd
    LEFT JOIN delegate_votes dv ON sd.Student_id = dv.voter_id
    LEFT JOIN student_council_votes scv ON sd.Student_id = scv.voter_id
    GROUP BY sd.school
    ORDER BY sd.school
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch voting patterns by programme
$programme_stats = $conn->query("
    SELECT 
        a.programme,
        (SELECT COUNT(DISTINCT sd.Student_id) 
         FROM student_details sd 
         WHERE sd.programme = a.programme) as total_students,
        (SELECT COUNT(*) FROM delegate_votes dv 
         JOIN applications ap ON dv.delegate_id = ap.student_id 
         WHERE ap.programme = a.programme) as delegate_voters,
        (SELECT COUNT(*) FROM delegate_votes dv 
         JOIN applications ap ON dv.delegate_id = ap.student_id 
         JOIN student_details sd ON ap.student_id = sd.Student_id
         WHERE ap.programme = a.programme AND sd.gender = 'F') as female_voters,
        (SELECT COUNT(*) FROM delegate_votes dv 
         JOIN applications ap ON dv.delegate_id = ap.student_id 
         JOIN student_details sd ON ap.student_id = sd.Student_id
         WHERE ap.programme = a.programme AND sd.gender = 'M') as male_voters,
        (SELECT COUNT(*) FROM student_details sd 
         WHERE sd.programme = a.programme AND sd.gender = 'F') as total_female,
        (SELECT COUNT(*) FROM student_details sd 
         WHERE sd.programme = a.programme AND sd.gender = 'M') as total_male
    FROM applications a
    GROUP BY a.programme
    ORDER BY a.programme
")->fetchAll(PDO::FETCH_ASSOC);

$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

if ($format === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="election_reports.csv"');
    
    // Create CSV output
    $output = fopen('php://output', 'w');
    
    // Write Summary Statistics
    fputcsv($output, ['Election Reports Summary']);
    fputcsv($output, ['']);
    fputcsv($output, ['Total Registered Voters', $stats['total_voters']]);
    fputcsv($output, ['Delegate Election Turnout', $stats['delegate_turnout'] . '%']);
    fputcsv($output, ['Student Council Turnout', $stats['council_turnout'] . '%']);
    fputcsv($output, ['']);
    
    // Write School Statistics
    fputcsv($output, ['Voting Patterns by School']);
    fputcsv($output, ['School', 'Total Students', 'Delegate Voters', 'Delegate Turnout']);
    foreach ($school_stats as $stat) {
        $delegate_turnout = $stat['total_students'] > 0 ? 
            round(($stat['delegate_voters'] / $stat['total_students']) * 100, 1) : 0;
        
        fputcsv($output, [
            $stat['school'],
            $stat['total_students'],
            $stat['delegate_voters'],
            $delegate_turnout . '%'
        ]);
    }
    fputcsv($output, ['']);
    
    // Write Programme Statistics
    fputcsv($output, ['Voting Patterns by Programme']);
    fputcsv($output, ['Programme', 'Total Students', 'Delegate Voters', 'Female Voters', 'Male Voters', 'Delegate Turnout', 'Female Turnout', 'Male Turnout']);
    foreach ($programme_stats as $stat) {
        $delegate_turnout = $stat['total_students'] > 0 ? 
            round(($stat['delegate_voters'] / $stat['total_students']) * 100, 1) : 0;
        $female_turnout = $stat['total_female'] > 0 ? 
            round(($stat['female_voters'] / $stat['total_female']) * 100, 1) : 0;
        $male_turnout = $stat['total_male'] > 0 ? 
            round(($stat['male_voters'] / $stat['total_male']) * 100, 1) : 0;
        
        fputcsv($output, [
            $stat['programme'],
            $stat['total_students'],
            $stat['delegate_voters'],
            $stat['female_voters'],
            $stat['male_voters'],
            $delegate_turnout . '%',
            $female_turnout . '%',
            $male_turnout . '%'
        ]);
    }
    
    fclose($output);
} else {
    // PDF Export
    require_once('assets/tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set document information
    $pdf->SetCreator('Election Commission');
    $pdf->SetAuthor('Election Commission');
    $pdf->SetTitle('Election Reports');
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Set image scale factor
    $pdf->setImageScale(1.25);
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Add a page
    $pdf->AddPage();
    
    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Election Reports', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Summary Statistics', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Summary table
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(120, 8, 'Total Registered Voters:', 0, 0);
    $pdf->Cell(60, 8, $stats['total_voters'], 0, 1);
    $pdf->Cell(120, 8, 'Delegate Election Turnout:', 0, 0);
    $pdf->Cell(60, 8, $stats['delegate_turnout'] . '%', 0, 1);
    $pdf->Ln(10);
    
    // School Statistics
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Voting Patterns by School', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Table header for schools
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(80, 7, 'School', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Total Students', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Delegate Voters', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Delegate Turnout', 1, 1, 'C', true);
    
    // Table data for schools
    $pdf->SetFont('helvetica', '', 9);
    $fill = false;
    foreach ($school_stats as $stat) {
        $delegate_turnout = $stat['total_students'] > 0 ? 
            round(($stat['delegate_voters'] / $stat['total_students']) * 100, 1) : 0;
        
        $pdf->Cell(80, 6, $stat['school'], 1, 0, 'C', $fill);
        $pdf->Cell(40, 6, $stat['total_students'], 1, 0, 'C', $fill);
        $pdf->Cell(40, 6, $stat['delegate_voters'], 1, 0, 'C', $fill);
        $pdf->Cell(40, 6, $delegate_turnout . '%', 1, 1, 'C', $fill);
        $fill = !$fill; // Alternate row colors
    }
    
    // Programme Statistics - New page
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Voting Patterns by Programme', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Table header for programmes
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $header_height = 7;
    
    // Create a fixed width table
    $col_widths = array(60, 25, 25, 25, 25, 33, 33, 33);
    
    // Draw the header row
    $pdf->Cell($col_widths[0], $header_height, 'Programme', 1, 0, 'C', true);
    $pdf->Cell($col_widths[1], $header_height, 'Total Students', 1, 0, 'C', true);
    $pdf->Cell($col_widths[2], $header_height, 'Delegate Voters', 1, 0, 'C', true);
    $pdf->Cell($col_widths[3], $header_height, 'Female Voters', 1, 0, 'C', true);
    $pdf->Cell($col_widths[4], $header_height, 'Male Voters', 1, 0, 'C', true);
    $pdf->Cell($col_widths[5], $header_height, 'Delegate Turnout', 1, 0, 'C', true);
    $pdf->Cell($col_widths[6], $header_height, 'Female Turnout', 1, 0, 'C', true);
    $pdf->Cell($col_widths[7], $header_height, 'Male Turnout', 1, 1, 'C', true);
    
    // Table data
    $pdf->SetFont('helvetica', '', 8);
    $fill = false;
    
    // Check if we have data
    if (empty($programme_stats)) {
        $pdf->Cell(array_sum($col_widths), 8, 'No programme data available', 1, 1, 'C');
    } else {
        // Process each programme
        foreach ($programme_stats as $stat) {
            // Calculate turnout percentages
            $delegate_turnout = $stat['total_students'] > 0 ? 
                round(($stat['delegate_voters'] / $stat['total_students']) * 100, 1) : 0;
            $female_turnout = $stat['total_female'] > 0 ? 
                round(($stat['female_voters'] / $stat['total_female']) * 100, 1) : 0;
            $male_turnout = $stat['total_male'] > 0 ? 
                round(($stat['male_voters'] / $stat['total_male']) * 100, 1) : 0;
            
            // If programme name is long, split it to get estimated height
            $programme_text = $stat['programme'];
            
            // Check for page break before starting a new row
            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
                // Redraw header
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Cell($col_widths[0], $header_height, 'Programme', 1, 0, 'C', true);
                $pdf->Cell($col_widths[1], $header_height, 'Total Students', 1, 0, 'C', true);
                $pdf->Cell($col_widths[2], $header_height, 'Delegate Voters', 1, 0, 'C', true);
                $pdf->Cell($col_widths[3], $header_height, 'Female Voters', 1, 0, 'C', true);
                $pdf->Cell($col_widths[4], $header_height, 'Male Voters', 1, 0, 'C', true);
                $pdf->Cell($col_widths[5], $header_height, 'Delegate Turnout', 1, 0, 'C', true);
                $pdf->Cell($col_widths[6], $header_height, 'Female Turnout', 1, 0, 'C', true);
                $pdf->Cell($col_widths[7], $header_height, 'Male Turnout', 1, 1, 'C', true);
                $pdf->SetFont('helvetica', '', 8);
                $fill = false;
            }
            
            // Split the programme name only for internal calculation
            $lines = $pdf->getNumLines($programme_text, $col_widths[0]);
            $row_height = max(6, $lines * 4); // Minimum 6mm height, more if needed
            
            // Save positions to align cells properly
            $startY = $pdf->GetY();
            $startX = $pdf->GetX();
            
            // Use simple table with aligned cells
            $pdf->Cell($col_widths[0], $row_height, $programme_text, 1, 0, 'C', $fill);
            $pdf->Cell($col_widths[1], $row_height, $stat['total_students'], 1, 0, 'C', $fill);
            $pdf->Cell($col_widths[2], $row_height, $stat['delegate_voters'], 1, 0, 'C', $fill);
            $pdf->Cell($col_widths[3], $row_height, $stat['female_voters'], 1, 0, 'C', $fill);
            $pdf->Cell($col_widths[4], $row_height, $stat['male_voters'], 1, 0, 'C', $fill);
            $pdf->Cell($col_widths[5], $row_height, $delegate_turnout . '%', 1, 0, 'C', $fill);
            $pdf->Cell($col_widths[6], $row_height, $female_turnout . '%', 1, 0, 'C', $fill);
            $pdf->Cell($col_widths[7], $row_height, $male_turnout . '%', 1, 1, 'C', $fill);
            
            $fill = !$fill; // Alternate row colors
        }
    }
    
    // Signature line
    $pdf->Ln(15);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'Report generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');
    
    // Output PDF
    $pdf->Output('election_reports.pdf', 'D');
}
?> 