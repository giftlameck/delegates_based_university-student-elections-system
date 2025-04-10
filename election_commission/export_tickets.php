<?php
// export_tickets.php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

// Get the export format from the URL parameter
$format = $_GET['format'] ?? 'csv';

// Get all tickets data
$sql = "SELECT * FROM election_support_tickets ORDER BY 
        CASE priority 
            WHEN 'high' THEN 1 
            WHEN 'medium' THEN 2 
            WHEN 'low' THEN 3 
        END, created_at DESC";
$tickets = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    // Export as CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="support_tickets.csv"');

    $output = fopen('php://output', 'w');

    // Add headers
    fputcsv($output, ['ID', 'Student ID', 'Student Name', 'Issue Type', 'Priority', 'Status', 'Description', 'Created Date', 'Last Updated']);

    // Add data rows
    foreach ($tickets as $ticket) {
        fputcsv($output, [
            $ticket['id'],
            $ticket['student_id'],
            $ticket['student_name'],
            ucfirst($ticket['issue_type']),
            ucfirst($ticket['priority']),
            ucfirst(str_replace('_', ' ', $ticket['status'])),
            $ticket['description'],
            date('Y-m-d H:i:s', strtotime($ticket['created_at'])),
            date('Y-m-d H:i:s', strtotime($ticket['updated_at']))
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
    $pdf->SetTitle('Support Tickets Report');

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
    $pdf->Cell(0, 10, 'Support Tickets Report', 0, 1, 'C');
    $pdf->Ln(10);

    // Table header
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 9);
    
    // Table header row
    $header = array('ID', 'Student ID', 'Student Name', 'Issue Type', 'Priority', 'Status', 'Description', 'Created Date');
    
    // Adjust column widths to fit the page width
    // Calculate total available width (A4 width minus margins)
    $pageWidth = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];
    
    // Define relative widths (percentages of available space)
    $relativeWidths = array(5, 10, 15, 10, 8, 8, 32, 12);
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
        // Reduce the description column width to fit
        $w[6] -= $diff;
    }
    
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Table data
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(255, 255, 255);
    
    foreach($tickets as $ticket) {
        // Check if we need a new page
        if($pdf->GetY() > 250) {
            $pdf->AddPage();
        }
        
        // Get the maximum height needed for this row
        $maxHeight = 6; // Default height
        
        // Calculate text height for each cell to determine the maximum height needed
        $texts = array(
            $ticket['id'],
            $ticket['student_id'],
            $ticket['student_name'],
            ucfirst($ticket['issue_type']),
            ucfirst($ticket['priority']),
            ucfirst(str_replace('_', ' ', $ticket['status'])),
            $ticket['description'],
            date('Y-m-d', strtotime($ticket['created_at']))
        );
        
        // Calculate the maximum height needed for this row
        for($i = 0; $i < count($texts); $i++) {
            $textHeight = $pdf->getStringHeight($w[$i], $texts[$i], false, true, '', 1);
            $maxHeight = max($maxHeight, $textHeight);
        }
        
        // Add some padding to the height
        $maxHeight += 2;
        
        // ID
        $pdf->MultiCell($w[0], $maxHeight, $ticket['id'], 1, 'C', false, 0);
        // Student ID
        $pdf->MultiCell($w[1], $maxHeight, $ticket['student_id'], 1, 'C', false, 0);
        // Student Name
        $pdf->MultiCell($w[2], $maxHeight, $ticket['student_name'], 1, 'L', false, 0);
        // Issue Type
        $pdf->MultiCell($w[3], $maxHeight, ucfirst($ticket['issue_type']), 1, 'L', false, 0);
        // Priority
        $pdf->MultiCell($w[4], $maxHeight, ucfirst($ticket['priority']), 1, 'C', false, 0);
        // Status
        $pdf->MultiCell($w[5], $maxHeight, ucfirst(str_replace('_', ' ', $ticket['status'])), 1, 'C', false, 0);
        // Description - use the full description with wrapping
        $pdf->MultiCell($w[6], $maxHeight, $ticket['description'], 1, 'L', false, 0);
        // Created Date
        $pdf->MultiCell($w[7], $maxHeight, date('Y-m-d', strtotime($ticket['created_at'])), 1, 'C', false, 1);
    }

    // Output the PDF
    $pdf->Output('support_tickets.pdf', 'D');
    exit();
} else {
    // Invalid format
    header('Location: manage_tickets.php');
    exit();
} 