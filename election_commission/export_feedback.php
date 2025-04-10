<?php
// export_feedback.php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

// Get the export format from the URL parameter
$format = $_GET['format'] ?? 'csv';

// Get all feedback data
$sql = "SELECT f.*, s.student_name 
        FROM election_feedback f
        LEFT JOIN student_details s ON f.student_id = s.Student_id
        ORDER BY f.created_at DESC";
$feedback = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    // Export as CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="student_feedback.csv"');

    $output = fopen('php://output', 'w');

    // Add headers
    fputcsv($output, ['ID', 'Student ID', 'Student Name', 'Rating', 'Feedback Type', 'Comments', 'Suggestions', 'Date']);

    // Add data rows
    foreach ($feedback as $item) {
        $feedback_type = ucfirst(str_replace('_', ' ', $item['feedback_type']));
        fputcsv($output, [
            $item['id'],
            $item['student_id'],
            $item['student_name'] ?? 'Unknown',
            $item['rating'] . '/5',
            $feedback_type,
            $item['comments'],
            $item['suggestions'] ?? '',
            date('Y-m-d H:i:s', strtotime($item['created_at']))
        ]);
    }

    fclose($output);
    exit();
} else if ($format === 'pdf') {
    // Include TCPDF library
    require_once('assets/tcpdf/tcpdf.php');

    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Election Commission');
    $pdf->SetTitle('Student Feedback Report');

    // Set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

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

    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 10);

    // Add title
    $pdf->Cell(0, 10, 'Student Feedback Report', 0, 1, 'C');
    $pdf->Ln(10);

    // Table header
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 9);
    
    // Table header row
    $header = array('ID', 'Student ID', 'Student Name', 'Rating', 'Feedback Type', 'Comments', 'Date');
    
    // Adjust column widths to fit the page width
    // Calculate total available width (A4 width minus margins)
    $pageWidth = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];
    
    // Define relative widths (percentages of available space)
    $relativeWidths = array(5, 10, 15, 8, 12, 40, 10);
    $totalPercent = array_sum($relativeWidths);
    
    // Calculate actual widths based on available space
    $w = array();
    foreach ($relativeWidths as $percent) {
        $w[] = round(($percent / $totalPercent) * $pageWidth);
    }
    
    // Ensure the sum of widths doesn't exceed page width
    $totalWidth = array_sum($w);
    if ($totalWidth > $pageWidth) {
        $diff = $totalWidth - $pageWidth;
        // Reduce the comments column width to fit
        $w[5] -= $diff;
    }
    
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Table data
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(255, 255, 255);
    
    foreach($feedback as $item) {
        // Check if we need a new page
        if($pdf->GetY() > 250) {
            $pdf->AddPage();
        }
        
        // Get the maximum height needed for this row
        $maxHeight = 6; // Default height
        
        $feedback_type = ucfirst(str_replace('_', ' ', $item['feedback_type']));
        
        // Calculate text height for each cell to determine the maximum height needed
        $texts = array(
            $item['id'],
            $item['student_id'],
            $item['student_name'] ?? 'Unknown',
            $item['rating'] . '/5',
            $feedback_type,
            $item['comments'],
            date('Y-m-d', strtotime($item['created_at']))
        );
        
        // Calculate the maximum height needed for this row
        for($i = 0; $i < count($texts); $i++) {
            $textHeight = $pdf->getStringHeight($w[$i], $texts[$i], false, true, '', 1);
            $maxHeight = max($maxHeight, $textHeight);
        }
        
        // Add some padding to the height
        $maxHeight += 2;
        
        // ID
        $pdf->MultiCell($w[0], $maxHeight, $item['id'], 1, 'C', false, 0);
        // Student ID
        $pdf->MultiCell($w[1], $maxHeight, $item['student_id'], 1, 'C', false, 0);
        // Student Name
        $pdf->MultiCell($w[2], $maxHeight, $item['student_name'] ?? 'Unknown', 1, 'L', false, 0);
        // Rating
        $pdf->MultiCell($w[3], $maxHeight, $item['rating'] . '/5', 1, 'C', false, 0);
        // Feedback Type
        $pdf->MultiCell($w[4], $maxHeight, $feedback_type, 1, 'L', false, 0);
        // Comments - use the full comments with wrapping
        $pdf->MultiCell($w[5], $maxHeight, $item['comments'], 1, 'L', false, 0);
        // Date
        $pdf->MultiCell($w[6], $maxHeight, date('Y-m-d', strtotime($item['created_at'])), 1, 'C', false, 1);
    }

    // Output the PDF
    $pdf->Output('student_feedback.pdf', 'D');
    exit();
} else {
    // Invalid format
    header('Location: manage_feedback.php');
    exit();
} 