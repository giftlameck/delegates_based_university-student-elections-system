<?php
// election_commission/export_delegate_winners.php
session_name('election_commission');
session_start();
require_once 'includes/db_connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}

// Get the format from URL parameters
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';

// Fetch all delegate winners
$sql = "SELECT 
            dw.delegate_id,
            dw.student_name,
            dw.programme,
            dw.school,
            dw.gender,
            dw.vote_count,
            dw.status
        FROM delegate_winners dw
        ORDER BY dw.programme, dw.school, dw.student_name";

$winners = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Get election statistics
$total_winners = count($winners);
$total_programmes = $conn->query("SELECT COUNT(DISTINCT programme) FROM delegate_winners")->fetchColumn();
$total_schools = $conn->query("SELECT COUNT(DISTINCT school) FROM delegate_winners")->fetchColumn();
$unopposed_count = $conn->query("SELECT COUNT(*) FROM delegate_winners WHERE status = 'unopposed'")->fetchColumn();
$elected_count = $total_winners - $unopposed_count;

if ($format === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="delegate_winners.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel display
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers for results
    fputcsv($output, ['DELEGATE ELECTION WINNERS']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, ['']);
    
    // Add election statistics
    fputcsv($output, ['ELECTION STATISTICS']);
    fputcsv($output, ['Total Winners:', $total_winners]);
    fputcsv($output, ['Total Programmes:', $total_programmes]);
    fputcsv($output, ['Total Schools:', $total_schools]);
    fputcsv($output, ['Elected:', $elected_count]);
    fputcsv($output, ['Unopposed:', $unopposed_count]);
    fputcsv($output, ['']);
    
    // Add winners section
    fputcsv($output, ['DELEGATE WINNERS']);
    fputcsv($output, ['Programme', 'School', 'Student Name', 'Gender', 'Votes', 'Status']);
    
    foreach ($winners as $winner) {
        fputcsv($output, [
            $winner['programme'],
            $winner['school'],
            $winner['student_name'],
            $winner['gender'] === 'M' ? 'Male' : 'Female',
            $winner['vote_count'],
            $winner['status'] === 'unopposed' ? 'Unopposed' : 'Elected'
        ]);
    }
    
    fclose($output);
    exit();
} else if ($format === 'pdf') {
    // Check if TCPDF is available
    if (!file_exists('assets/tcpdf/tcpdf.php')) {
        $_SESSION['error'] = "PDF export requires TCPDF library. Please contact the administrator.";
        header('Location: delegate_results.php');
        exit();
    }
    
    require_once('assets/tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Election Commission');
    $pdf->SetTitle('Delegate Election Winners');
    
    // Set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Delegate Election Winners', 'Generated on ' . date('Y-m-d H:i:s'));
    
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
    $html = '<h1 style="text-align: center;">Delegate Election Winners</h1>';
    $html .= '<p style="text-align: center;">Generated on ' . date('Y-m-d H:i:s') . '</p>';
    
    // Election Statistics
    $html .= '<h2>Election Statistics</h2>';
    $html .= '<table border="1" cellpadding="4">
        <tr>
            <th><b>Total Winners</b></th>
            <td>' . $total_winners . '</td>
        </tr>
        <tr>
            <th><b>Total Programmes</b></th>
            <td>' . $total_programmes . '</td>
        </tr>
        <tr>
            <th><b>Total Schools</b></th>
            <td>' . $total_schools . '</td>
        </tr>
        <tr>
            <th><b>Elected</b></th>
            <td>' . $elected_count . '</td>
        </tr>
        <tr>
            <th><b>Unopposed</b></th>
            <td>' . $unopposed_count . '</td>
        </tr>
    </table><br>';
    
    // Winners by Programme
    $html .= '<h2>Delegate Winners</h2>';
    $html .= '<table border="1" cellpadding="4">
        <tr style="background-color: #f8f9fa;">
            <th><b>Programme</b></th>
            <th><b>School</b></th>
            <th><b>Student Name</b></th>
            <th><b>Gender</b></th>
            <th><b>Votes</b></th>
            <th><b>Status</b></th>
        </tr>';
    
    foreach ($winners as $winner) {
        $html .= '<tr>
            <td>' . htmlspecialchars($winner['programme']) . '</td>
            <td>' . htmlspecialchars($winner['school']) . '</td>
            <td>' . htmlspecialchars($winner['student_name']) . '</td>
            <td>' . ($winner['gender'] === 'M' ? 'Male' : 'Female') . '</td>
            <td>' . $winner['vote_count'] . '</td>
            <td>' . ($winner['status'] === 'unopposed' ? 'Unopposed' : 'Elected') . '</td>
        </tr>';
    }
    
    $html .= '</table>';
    
    // Output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output('delegate_winners.pdf', 'D');
    exit();
} 