<?php
// election_commission/export_delegate_results.php
session_name('election_commission');
session_start();
require_once 'includes/db_connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}

// Get the programme and format from URL parameters
$programme = isset($_GET['programme']) ? $_GET['programme'] : '';
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';

if (empty($programme)) {
    die('Programme not specified');
}

// Fetch delegate results for the selected programme
$sql = "SELECT 
            a.student_id,
            a.student_name,
            a.programme,
            sd.gender,
            sd.school,
            COUNT(dv.delegate_id) as vote_count
        FROM applications a
        LEFT JOIN delegate_votes dv ON a.student_id = dv.delegate_id
        LEFT JOIN student_details sd ON a.student_id = sd.Student_id
        WHERE a.candidate_type = 'Delegate' AND a.status = 'Approved' AND a.programme = :programme
        GROUP BY a.student_id, a.student_name, a.programme, sd.gender, sd.school
        ORDER BY vote_count DESC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':programme', $programme, PDO::PARAM_STR);
$stmt->execute();
$delegates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="delegate_results.csv"');
    
    // Create CSV file
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, array('Student ID', 'Name', 'Programme', 'School', 'Gender', 'Vote Count'));
    
    // Add data
    foreach ($delegates as $delegate) {
        fputcsv($output, array(
            $delegate['student_id'],
            $delegate['student_name'],
            $delegate['programme'],
            $delegate['school'],
            $delegate['gender'] === 'M' ? 'Male' : 'Female',
            $delegate['vote_count']
        ));
    }
    
    fclose($output);
} else if ($format === 'pdf') {
    // Check if TCPDF is available
    if (!file_exists('assets/tcpdf/tcpdf.php')) {
        die('TCPDF library not found. Please install it to export PDF files.');
    }
    
    require_once('assets/tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Election Commission');
    $pdf->SetTitle('Delegate Election Results');
    
    // Set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Delegate Election Results', 'Generated on ' . date('Y-m-d H:i:s'));
    
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
    
    // Create the content
    $html = '<h1>Delegate Election Results</h1>';
    $html .= '<table border="1" cellpadding="4">
                <tr>
                    <th><b>Student ID</b></th>
                    <th><b>Name</b></th>
                    <th><b>Programme</b></th>
                    <th><b>School</b></th>
                    <th><b>Gender</b></th>
                    <th><b>Vote Count</b></th>
                </tr>';
    
    foreach ($delegates as $delegate) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($delegate['student_id']) . '</td>
                    <td>' . htmlspecialchars($delegate['student_name']) . '</td>
                    <td>' . htmlspecialchars($delegate['programme']) . '</td>
                    <td>' . htmlspecialchars($delegate['school']) . '</td>
                    <td>' . ($delegate['gender'] === 'M' ? 'Male' : 'Female') . '</td>
                    <td>' . $delegate['vote_count'] . '</td>
                </tr>';
    }
    
    $html .= '</table>';
    
    // Print the content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output('delegate_results.pdf', 'D');
} else {
    die('Invalid export format');
}
?> 