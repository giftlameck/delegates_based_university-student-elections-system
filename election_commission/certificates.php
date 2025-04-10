<?php
// Election_commission/certificates.php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';
require_once '../includes/fpdf/fpdf.php'; // Ensure FPDF is in includes/fpdf/

// Generate Certificate on Request (Moved to Top)
if (isset($_GET['generate']) && isset($_GET['type']) && isset($_GET['id'])) {
    $pdf = new FPDF('L', 'mm', 'A4'); // Landscape orientation for better layout
    $pdf->AddPage();
    
    // Add decorative border
    $pdf->SetLineWidth(1.5);
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->Rect(10, 10, 277, 190); // Outer border
    
    // Inner decorative border
    $pdf->SetLineWidth(0.5);
    $pdf->Rect(15, 15, 267, 180);
    
    // Add decorative corners
    $pdf->SetLineWidth(1);
    $pdf->Line(10, 10, 30, 10);
    $pdf->Line(10, 10, 10, 30);
    $pdf->Line(287, 10, 267, 10);
    $pdf->Line(287, 10, 287, 30);
    $pdf->Line(10, 200, 30, 200);
    $pdf->Line(10, 200, 10, 180);
    $pdf->Line(287, 200, 267, 200);
    $pdf->Line(287, 200, 287, 180);
    
    // Add decorative header
    $pdf->SetFont('Arial', 'B', 28);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 30, 'Certificate of Election', 0, 1, 'C');
    
    // Add decorative line
    $pdf->SetLineWidth(0.5);
    $pdf->Line(50, $pdf->GetY(), 237, $pdf->GetY());
    $pdf->Ln(20);
    
    // Main content
    $pdf->SetFont('Arial', '', 16);
    $pdf->Cell(0, 10, "This is to certify that", 0, 1, 'C');
    
    if ($_GET['type'] === 'delegate') {
        $stmt = $conn->prepare("SELECT student_name, programme FROM delegate_winners WHERE delegate_id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $delegate = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($delegate) {
            $pdf->SetFont('Arial', 'B', 20);
            $pdf->Cell(0, 15, $delegate['student_name'], 0, 1, 'C');
            $pdf->SetFont('Arial', '', 16);
            $pdf->Cell(0, 10, "has been duly elected as the Delegate for", 0, 1, 'C');
            $pdf->SetFont('Arial', 'B', 18);
            $pdf->Cell(0, 10, $delegate['programme'], 0, 1, 'C');
        }
    } elseif ($_GET['type'] === 'council') {
        $position = $_GET['position'];
        if ($position === 'Chairperson') {
            $stmt = $conn->prepare("SELECT a.student_name AS chair_name, b.student_name AS vice_name
                                    FROM applications a
                                    JOIN applications b ON a.vice_chairperson_id = b.student_id
                                    WHERE a.student_id = :id AND a.position = 'Chairperson' AND a.status = 'Approved'");
            $stmt->execute(['id' => $_GET['id']]);
            $winner = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($winner) {
                $pdf->SetFont('Arial', 'B', 20);
                $pdf->Cell(0, 15, $winner['chair_name'], 0, 1, 'C');
                $pdf->Cell(0, 15, "and", 0, 1, 'C');
                $pdf->Cell(0, 15, $winner['vice_name'], 0, 1, 'C');
                $pdf->SetFont('Arial', '', 16);
                $pdf->Cell(0, 10, "have been duly elected as", 0, 1, 'C');
                $pdf->SetFont('Arial', 'B', 18);
                $pdf->Cell(0, 10, "Chairperson and Vice Chairperson", 0, 1, 'C');
            }
        } else {
            $stmt = $conn->prepare("SELECT student_name FROM applications WHERE student_id = :id AND position = :position AND status = 'Approved'");
            $stmt->execute(['id' => $_GET['id'], 'position' => $position]);
            $winner = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($winner) {
                $pdf->SetFont('Arial', 'B', 20);
                $pdf->Cell(0, 15, $winner['student_name'], 0, 1, 'C');
                $pdf->SetFont('Arial', '', 16);
                $pdf->Cell(0, 10, "has been duly elected as", 0, 1, 'C');
                $pdf->SetFont('Arial', 'B', 18);
                $pdf->Cell(0, 10, $position, 0, 1, 'C');
            }
        }
    }
    
    // Add decorative line
    $pdf->Ln(10);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(50, $pdf->GetY(), 237, $pdf->GetY());
    
    // Footer
    $pdf->Ln(15);
    $pdf->SetFont('Arial', '', 14);
    $pdf->Cell(0, 10, "Given this " . date('j') . " day of " . date('F Y'), 0, 1, 'C');
    
    // Add signature line
    $pdf->Ln(10);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(100, $pdf->GetY(), 187, $pdf->GetY());
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, "Election Commission", 0, 1, 'C');
    
    $pdf->Output('D', 'certificate.pdf');
    exit();
}

// Fetch Delegate Winners
$delegate_winners = $conn->query("SELECT delegate_id, student_name, programme, status FROM delegate_winners ORDER BY programme")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Student Council Winners
$council_positions = $conn->query("SELECT DISTINCT position FROM applications WHERE candidate_type = 'Student Council' AND status = 'Approved' AND position != 'Vice Chairperson'")->fetchAll(PDO::FETCH_COLUMN);
$council_winners = [];
foreach ($council_positions as $position) {
    if ($position === 'Chairperson') {
        $sql = "SELECT a.student_id AS chair_id, a.student_name AS chair_name, b.student_name AS vice_name, COUNT(scv.ticket_id) AS vote_count,
                       (SELECT COUNT(*) FROM applications WHERE position = 'Chairperson' AND status = 'Approved') AS candidate_count
                FROM applications a
                JOIN applications b ON a.vice_chairperson_id = b.student_id
                LEFT JOIN student_council_votes scv ON a.student_id = scv.ticket_id
                WHERE a.position = 'Chairperson' AND a.status = 'Approved' AND b.status = 'Approved'
                GROUP BY a.student_id, a.student_name, b.student_name
                ORDER BY vote_count DESC LIMIT 1";
    } else {
        $sql = "SELECT a.student_id, a.student_name, COUNT(scv.ticket_id) AS vote_count,
                       (SELECT COUNT(*) FROM applications WHERE position = :position AND status = 'Approved') AS candidate_count
                FROM applications a
                LEFT JOIN student_council_votes scv ON a.student_id = scv.ticket_id
                WHERE a.position = :position AND a.status = 'Approved'
                GROUP BY a.student_id, a.student_name
                ORDER BY vote_count DESC LIMIT 1";
    }
    $stmt = $conn->prepare($sql);
    if ($position !== 'Chairperson') $stmt->execute(['position' => $position]);
    else $stmt->execute();
    $winner = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($winner) {
        $winner['status'] = ($winner['candidate_count'] == 1 && $winner['vote_count'] == 0) ? 'unopposed' : 'elected';
        $council_winners[$position] = $winner;
    }
}

include 'includes/new_layout.php';
?>

<!-- Page Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Delegate Certificates -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <span class="icon icon-award"></span> Delegate Certificates
                    </h5>
                    <div class="header-actions">
                        <input type="text" 
                               class="form-control form-control-sm" 
                               id="delegateSearch" 
                               placeholder="Search delegates..."
                               style="width: 200px;">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="delegateTable">
                            <thead>
                                <tr>
                                    <th>Programme</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($delegate_winners as $winner): ?>
                                    <tr>
                                        <td>
                                            <span class="icon icon-graduation-cap text-primary"></span>
                                            <?php echo htmlspecialchars($winner['programme']); ?>
                                        </td>
                                        <td>
                                            <span class="icon icon-user text-secondary"></span>
                                            <?php echo htmlspecialchars($winner['student_name']); ?>
                                        </td>
                                        <td>
                                            <?php if ($winner['status'] === 'unopposed'): ?>
                                                <span class="badge bg-warning">Unopposed</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Elected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?generate=1&type=delegate&id=<?php echo $winner['delegate_id']; ?>" 
                                               class="btn btn-primary btn-sm">
                                                <span class="icon icon-download"></span> Generate Certificate
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Student Council Certificates -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <span class="icon icon-users"></span> Student Council Certificates
                    </h5>
                    <div class="header-actions">
                        <input type="text" 
                               class="form-control form-control-sm" 
                               id="councilSearch" 
                               placeholder="Search council..."
                               style="width: 200px;">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="councilTable">
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Name(s)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($council_positions as $position): ?>
                                    <tr>
                                        <td>
                                            <span class="icon icon-briefcase text-primary"></span>
                                            <?php echo $position === 'Chairperson' ? 'Chairperson & Vice Chairperson' : htmlspecialchars($position); ?>
                                        </td>
                                        <td>
                                            <?php if (isset($council_winners[$position])): ?>
                                                <span class="icon icon-users text-secondary"></span>
                                                <?php echo $position === 'Chairperson' ? 
                                                    htmlspecialchars($council_winners[$position]['chair_name'] . ' & ' . $council_winners[$position]['vice_name']) : 
                                                    htmlspecialchars($council_winners[$position]['student_name']); ?>
                                            <?php else: ?>
                                                <span class="text-warning">
                                                    <span class="icon icon-alert-triangle"></span> Pending/Tied
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($council_winners[$position])): ?>
                                                <?php if ($council_winners[$position]['status'] === 'unopposed'): ?>
                                                    <span class="badge bg-warning">Unopposed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Elected</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($council_winners[$position])): ?>
                                                <a href="?generate=1&type=council&id=<?php echo $council_winners[$position]['chair_id'] ?? $council_winners[$position]['student_id']; ?>&position=<?php echo $position; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <span class="icon icon-download"></span> Generate Certificate
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    <span class="icon icon-slash"></span> Not Available
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control {
        border-radius: 10px;
    }
    
    .btn {
        border-radius: 10px;
        padding: 0.5rem 1rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
    }
    
    .card {
        border: none;
        border-radius: 15px;
        margin-bottom: 2rem;
    }
    
    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .badge {
        font-weight: 500;
        padding: 0.5em 0.8em;
        border-radius: 6px;
    }
    
    .icon {
        margin-right: 0.5rem;
    }
</style>

<script>
// Search functionality for delegate table
document.getElementById('delegateSearch').addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const table = document.getElementById('delegateTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const programme = rows[i].getElementsByTagName('td')[0].textContent.toLowerCase();
        const name = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
        rows[i].style.display = 
            programme.includes(searchText) || name.includes(searchText) ? '' : 'none';
    }
});

// Search functionality for council table
document.getElementById('councilSearch').addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const table = document.getElementById('councilTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const position = rows[i].getElementsByTagName('td')[0].textContent.toLowerCase();
        const names = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
        rows[i].style.display = 
            position.includes(searchText) || names.includes(searchText) ? '' : 'none';
    }
});
</script>

<?php include 'includes/new_footer.php'; ?>