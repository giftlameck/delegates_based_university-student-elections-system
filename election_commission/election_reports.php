<?php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

// Fetch election statistics
$stats = array();

// Total registered voters
$stmt = $conn->query("SELECT COUNT(*) as total FROM student_details");
$stats['total_voters'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total votes cast
$stmt = $conn->query("SELECT COUNT(DISTINCT voter_id) as total FROM delegate_votes");
$stats['delegate_votes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(DISTINCT voter_id) as total FROM student_council_votes");
$stats['council_votes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get total delegate winners (maximum 3 per programme)
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

// Check if both voting periods have ended
$delegate_schedule = $conn->query("SELECT end_date FROM election_schedule WHERE event_type = 'delegate_voting' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$council_schedule = $conn->query("SELECT end_date FROM election_schedule WHERE event_type = 'student_council_voting' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Check if schedules exist before checking end dates
$delegate_schedule_exists = !empty($delegate_schedule);
$council_schedule_exists = !empty($council_schedule);

// Only check end dates if schedules exist
$delegate_voting_ended = $delegate_schedule_exists && (new DateTime() > new DateTime($delegate_schedule['end_date']));
$council_voting_ended = $council_schedule_exists && (new DateTime() > new DateTime($council_schedule['end_date']));

// Show appropriate message if schedules don't exist or voting is still active
if (!$delegate_schedule_exists || !$council_schedule_exists || !$delegate_voting_ended || !$council_voting_ended) {
    include 'includes/new_layout.php';
    ?>
    <div class="container-fluid">
        <div class="alert alert-warning">
            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Election Reports Not Available</h4>
            <?php if (!$delegate_schedule_exists || !$council_schedule_exists): ?>
                <p>There are no reports currently as the voting process has not commenced or announced.</p>
            <?php else: ?>
                <?php if (!$delegate_voting_ended && $delegate_schedule_exists): ?>
                    <p>The delegate election voting period is still active and will end on <?php echo date('F j, Y', strtotime($delegate_schedule['end_date'])); ?>.</p>
                <?php endif; ?>
                <?php if (!$council_voting_ended && $council_schedule_exists): ?>
                    <p>The student council election voting period is still active and will end on <?php echo date('F j, Y', strtotime($council_schedule['end_date'])); ?>.</p>
                <?php endif; ?>
                <p>Election reports will be available after both voting periods have ended.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    include 'includes/new_footer.php';
    exit();
}

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

include 'includes/new_layout.php';
?>

<!-- Page Content -->
<div class="container-fluid">
    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Registered Voters</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['total_voters']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Delegate Election Turnout</h5>
                    <h2 class="mb-0"><?php echo $stats['delegate_turnout']; ?>%</h2>
                    <small class="text-muted"><?php echo number_format($stats['delegate_votes']); ?> votes cast</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Student Council Turnout</h5>
                    <h2 class="mb-0"><?php echo $stats['council_turnout']; ?>%</h2>
                    <small class="text-muted">Based on <?php echo $stats['total_delegate_winners']; ?> delegate winners</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Overall Turnout</h5>
                    <h2 class="mb-0"><?php echo round(($stats['delegate_turnout'] + $stats['council_turnout']) / 2, 1); ?>%</h2>
                    <small class="text-muted">Average of both elections</small>
                </div>
            </div>
        </div>
    </div>

    <!-- School-wise Statistics -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Voting Patterns by School</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>School</th>
                                    <th>Total Students</th>
                                    <th>Delegate Voters</th>
                                    <th>Delegate Turnout</th>
                                    <th>Council Voters</th>
                                    <th>Council Turnout</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($school_stats as $stat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stat['school']); ?></td>
                                        <td><?php echo number_format($stat['total_students']); ?></td>
                                        <td><?php echo number_format($stat['delegate_voters']); ?></td>
                                        <td>
                                            <?php 
                                            $turnout = $stat['total_students'] > 0 ? 
                                                round(($stat['delegate_voters'] / $stat['total_students']) * 100, 1) : 0;
                                            echo $turnout . '%';
                                            ?>
                                        </td>
                                        <td><?php echo number_format($stat['council_voters']); ?></td>
                                        <td>
                                            <?php 
                                            $turnout = $stat['delegate_winners'] > 0 ? 
                                                round(($stat['council_voters'] / $stat['delegate_winners']) * 100, 1) : 0;
                                            echo $turnout . '%';
                                            ?>
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

    <!-- Programme-wise Statistics -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Voting Patterns by Programme</h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportReports('csv')">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="exportReports('pdf')">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Programme</th>
                                    <th>Total Students</th>
                                    <th>Delegate Voters</th>
                                    <th>Female Voters</th>
                                    <th>Male Voters</th>
                                    <th>Delegate Turnout</th>
                                    <th>Female Turnout</th>
                                    <th>Male Turnout</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($programme_stats as $stat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stat['programme']); ?></td>
                                        <td><?php echo $stat['total_students']; ?></td>
                                        <td><?php echo $stat['delegate_voters']; ?></td>
                                        <td><?php echo $stat['female_voters']; ?></td>
                                        <td><?php echo $stat['male_voters']; ?></td>
                                        <td>
                                            <?php 
                                            $delegate_turnout = $stat['total_students'] > 0 ? 
                                                round(($stat['delegate_voters'] / $stat['total_students']) * 100, 1) : 0;
                                            echo $delegate_turnout . '%';
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $female_turnout = $stat['total_female'] > 0 ? 
                                                round(($stat['female_voters'] / $stat['total_female']) * 100, 1) : 0;
                                            echo $female_turnout . '%';
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $male_turnout = $stat['total_male'] > 0 ? 
                                                round(($stat['male_voters'] / $stat['total_male']) * 100, 1) : 0;
                                            echo $male_turnout . '%';
                                            ?>
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
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.card-header {
    background-color: #fff;
    border-bottom: 1px solid #eee;
    padding: 15px 20px;
}

.card-title {
    color: #333;
    margin: 0;
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
    padding: 0.5em 0.75em;
    font-weight: 500;
}
</style>

<script>
function exportReports(format) {
    window.location.href = 'export_election_reports.php?format=' + format;
}
</script>

<?php include 'includes/new_footer.php'; ?> 