<?php
// reporting_analytics.php (in C:\xampp1\htdocs\My_Election_system\Election_commission\)
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';
include 'includes/new_layout.php';

// Election Overview
$total_students = $conn->query("SELECT COUNT(*) FROM student_details")->fetchColumn();
$total_delegates = $conn->query("SELECT COUNT(*) FROM delegate_winners")->fetchColumn();
$delegate_voters = $conn->query("SELECT COUNT(DISTINCT voter_id) FROM delegate_votes")->fetchColumn();
$council_voters = $conn->query("SELECT COUNT(DISTINCT voter_id) FROM student_council_votes")->fetchColumn();
$delegate_turnout = $total_students > 0 ? round(($delegate_voters / $total_students) * 100, 1) : 0;
$council_turnout = $total_delegates > 0 ? round(($council_voters / $total_delegates) * 100, 1) : 0;

$schedule_delegate = $conn->query("SELECT start_date, end_date FROM election_schedule WHERE event_type = 'delegate_voting' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$schedule_council = $conn->query("SELECT start_date, end_date FROM election_schedule WHERE event_type = 'student_council_voting' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$current_date = new DateTime();
$delegate_status = $schedule_delegate ? ($current_date < new DateTime($schedule_delegate['start_date']) ? 'Pending' : ($current_date > new DateTime($schedule_delegate['end_date']) ? 'Closed' : 'Ongoing')) : 'Not Scheduled';
$council_status = $schedule_council ? ($current_date < new DateTime($schedule_council['start_date']) ? 'Pending' : ($current_date > new DateTime($schedule_council['end_date']) ? 'Closed' : 'Ongoing')) : 'Not Scheduled';

// Programme-wise Turnout (Delegate Voting)
$programme_turnout = $conn->query("SELECT sd.programme, COUNT(DISTINCT dv.voter_id) AS voters, COUNT(DISTINCT sd.student_id) AS total_students
                                   FROM student_details sd
                                   LEFT JOIN delegate_votes dv ON sd.student_id = dv.voter_id
                                   GROUP BY sd.programme")->fetchAll(PDO::FETCH_ASSOC);
foreach ($programme_turnout as &$pt) {
    $pt['turnout'] = $pt['total_students'] > 0 ? round(($pt['voters'] / $pt['total_students']) * 100, 1) : 0;
}

// Candidate Statistics
$applications_total = $conn->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$applications_approved = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'Approved'")->fetchColumn();
$applications_rejected = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'Rejected'")->fetchColumn();
$delegate_apps = $conn->query("SELECT COUNT(*) FROM applications WHERE candidate_type = 'Delegate' AND status = 'Approved'")->fetchColumn();
$council_apps = $conn->query("SELECT COUNT(*) FROM applications WHERE candidate_type = 'Student Council' AND status = 'Approved' AND position != 'Vice Chairperson'")->fetchColumn();

// Gender Analysis (Fixed delegate_winners join)
$gender_candidates = $conn->query("SELECT sd.gender, COUNT(*) AS count
                                   FROM applications a
                                   JOIN student_details sd ON a.student_id = sd.student_id
                                   WHERE a.status = 'Approved'
                                   GROUP BY sd.gender")->fetchAll(PDO::FETCH_ASSOC);
$gender_delegates = $conn->query("SELECT sd.gender, COUNT(*) AS count
                                  FROM delegate_winners dw
                                  JOIN student_details sd ON dw.delegate_id = sd.student_id
                                  GROUP BY sd.gender")->fetchAll(PDO::FETCH_ASSOC);

// Voting Analytics
$delegate_votes = $conn->query("SELECT COUNT(*) FROM delegate_votes")->fetchColumn();
$council_votes = $conn->query("SELECT COUNT(*) FROM student_council_votes")->fetchColumn();

// Results Summary (Delegate Winners)
$delegate_winners = $conn->query("SELECT programme, student_name, vote_count, status FROM delegate_winners ORDER BY programme")->fetchAll(PDO::FETCH_ASSOC);

// Results Summary (Council Winners)
$council_positions = $conn->query("SELECT DISTINCT position FROM applications WHERE candidate_type = 'Student Council' AND status = 'Approved' AND position != 'Vice Chairperson'")->fetchAll(PDO::FETCH_COLUMN);
$council_winners = [];
foreach ($council_positions as $position) {
    if ($position === 'Chairperson') {
        $sql = "SELECT a.student_name AS chair_name, b.student_name AS vice_name, COUNT(scv.ticket_id) AS vote_count,
                       (SELECT COUNT(*) FROM applications WHERE position = 'Chairperson' AND status = 'Approved') AS candidate_count
                FROM applications a
                JOIN applications b ON a.vice_chairperson_id = b.student_id
                LEFT JOIN student_council_votes scv ON a.student_id = scv.ticket_id
                WHERE a.position = 'Chairperson' AND a.status = 'Approved' AND b.status = 'Approved'
                GROUP BY a.student_id, a.student_name, b.student_name
                ORDER BY vote_count DESC LIMIT 1";
    } else {
        $sql = "SELECT a.student_name, COUNT(scv.ticket_id) AS vote_count,
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
        if ($winner['candidate_count'] == 1 && $winner['vote_count'] == 0) {
            $winner['status'] = 'unopposed';
        } elseif ($winner['vote_count'] > 0) {
            $winner['status'] = 'elected';
        }
        $council_winners[$position] = $winner;
    }
}
?>

<!-- Page Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
    <!-- Election Overview -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Election Overview</h5>
                    <div class="header-actions">
                        <span class="icon icon-chart"></span>
                    </div>
        </div>
        <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card p-3 bg-light rounded">
                                <h6 class="stat-title">Total Students</h6>
                                <div class="stat-value"><?php echo $total_students; ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card p-3 bg-light rounded">
                                <h6 class="stat-title">Total Delegates</h6>
                                <div class="stat-value"><?php echo $total_delegates; ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card p-3 bg-light rounded">
                                <h6 class="stat-title">Delegate Election Turnout</h6>
                                <div class="stat-value <?php echo $delegate_turnout > 75 ? 'text-success' : 'text-warning'; ?>">
                                    <?php echo $delegate_turnout; ?>%
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card p-3 bg-light rounded">
                                <h6 class="stat-title">Council Election Turnout</h6>
                                <div class="stat-value <?php echo $council_turnout > 75 ? 'text-success' : 'text-warning'; ?>">
                                    <?php echo $council_turnout; ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="status-card p-3 bg-light rounded">
                                <h6>Delegate Voting Status</h6>
                                <span class="badge <?php 
                                    echo $delegate_status === 'Closed' ? 'bg-secondary' : 
                                        ($delegate_status === 'Ongoing' ? 'bg-success' : 'bg-warning'); 
                                ?>">
                                    <?php echo $delegate_status; ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="status-card p-3 bg-light rounded">
                                <h6>Student Council Voting Status</h6>
                                <span class="badge <?php 
                                    echo $council_status === 'Closed' ? 'bg-secondary' : 
                                        ($council_status === 'Ongoing' ? 'bg-success' : 'bg-warning'); 
                                ?>">
                                    <?php echo $council_status; ?>
                                </span>
                            </div>
                        </div>
                    </div>
        </div>
    </div>

    <!-- Programme-wise Turnout -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Programme-wise Turnout</h5>
                    <div class="header-actions">
                        <span class="icon icon-bar-chart"></span>
                    </div>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($programme_turnout as $pt): ?>
                    <div class="col-md-4 mb-3">
                                <div class="programme-card p-3 bg-light rounded">
                                    <h6 class="text-center mb-2"><?php echo htmlspecialchars($pt['programme']); ?></h6>
                                    <div class="progress mb-2" style="height: 20px;">
                                        <div class="progress-bar <?php echo $pt['turnout'] > 75 ? 'bg-success' : 'bg-warning'; ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $pt['turnout']; ?>%"
                                             aria-valuenow="<?php echo $pt['turnout']; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?php echo $pt['turnout']; ?>%
                                </div>
                            </div>
                                    <div class="text-center text-muted">
                                        <small><?php echo $pt['voters']; ?> of <?php echo $pt['total_students']; ?> voted</small>
                                    </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Candidate Statistics -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Candidate Statistics</h5>
                    <div class="header-actions">
                        <span class="icon icon-users"></span>
                    </div>
        </div>
        <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="stat-card p-3 bg-light rounded text-center">
                                <h6>Total Applications</h6>
                                <div class="stat-value"><?php echo $applications_total; ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-card p-3 bg-light rounded text-center">
                                <h6>Approved Applications</h6>
                                <div class="stat-value text-success"><?php echo $applications_approved; ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-card p-3 bg-light rounded text-center">
                                <h6>Rejected Applications</h6>
                                <div class="stat-value text-danger"><?php echo $applications_rejected; ?></div>
        </div>
    </div>
        </div>
        </div>
    </div>

            <!-- Results Tables -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#delegateResults">
                                <span class="icon icon-users"></span> Delegate Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#councilResults">
                                <span class="icon icon-star"></span> Council Results
                            </a>
                        </li>
                    </ul>
        </div>
        <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="delegateResults">
            <div class="mb-3">
                                <div class="input-group">
                                    <input type="text" id="delegateSearch" class="form-control" placeholder="Search delegates...">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <span class="icon icon-search"></span>
                                        </span>
                                    </div>
                                </div>
            </div>
                            <div class="table-responsive">
                                <table id="delegateTable" class="table table-hover">
                                    <thead class="bg-light">
                    <tr>
                        <th>Programme</th>
                        <th>Name</th>
                                            <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($delegate_winners as $winner): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($winner['programme']); ?></td>
                            <td><?php echo htmlspecialchars($winner['student_name']); ?></td>
                            <td>
                                                    <span class="badge <?php echo $winner['status'] === 'unopposed' ? 'bg-warning' : 'bg-success'; ?>">
                                                        <?php echo $winner['status'] === 'unopposed' ? 'Unopposed' : $winner['vote_count'] . ' votes'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="councilResults">
            <div class="mb-3">
                                <div class="input-group">
                                    <input type="text" id="councilSearch" class="form-control" placeholder="Search council members...">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <span class="icon icon-search"></span>
                                        </span>
                                    </div>
                                </div>
            </div>
                            <div class="table-responsive">
                                <table id="councilTable" class="table table-hover">
                                    <thead class="bg-light">
                    <tr>
                        <th>Position</th>
                        <th>Name(s)</th>
                                            <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($council_positions as $position): ?>
                        <tr>
                            <td><?php echo $position === 'Chairperson' ? 'Chairperson & Vice Chairperson' : htmlspecialchars($position); ?></td>
                            <td>
                                <?php if (isset($council_winners[$position])): ?>
                                                        <?php echo $position === 'Chairperson' ? 
                                                            htmlspecialchars($council_winners[$position]['chair_name'] . ' & ' . $council_winners[$position]['vice_name']) : 
                                                            htmlspecialchars($council_winners[$position]['student_name']); ?>
                                <?php else: ?>
                                    <span class="text-warning">Pending/Tied</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($council_winners[$position])): ?>
                                                        <span class="badge <?php echo $council_winners[$position]['status'] === 'unopposed' ? 'bg-warning' : 'bg-success'; ?>">
                                                            <?php echo $council_winners[$position]['status'] === 'unopposed' ? 'Unopposed' : $council_winners[$position]['vote_count'] . ' votes'; ?>
                                    </span>
                                <?php else: ?>
                                                        <span class="badge bg-warning">N/A</span>
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
    </div>
</div>

<style>
    .stat-card {
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .stat-title {
        color: #6c757d;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 600;
    }
    
    .progress {
        border-radius: 10px;
        background-color: #e9ecef;
    }
    
    .progress-bar {
        border-radius: 10px;
        transition: width 0.6s ease;
    }
    
    .programme-card {
        transition: all 0.3s ease;
    }
    
    .programme-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .nav-tabs .nav-link {
        color: #495057;
        border: none;
        padding: 1rem 1.5rem;
        transition: all 0.2s ease;
    }
    
    .nav-tabs .nav-link:hover {
        color: #007bff;
        background-color: #f8f9fa;
    }
    
    .nav-tabs .nav-link.active {
        color: #007bff;
        background-color: transparent;
        border-bottom: 2px solid #007bff;
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
        padding: 0.5rem 0.75rem;
        font-weight: 500;
    }
    
    .input-group {
        max-width: 300px;
        margin-bottom: 1rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality for delegate table
    const delegateSearch = document.getElementById('delegateSearch');
    const delegateTable = document.getElementById('delegateTable');
    
    delegateSearch.addEventListener('keyup', function() {
        searchTable(delegateTable, this.value);
    });
    
    // Search functionality for council table
    const councilSearch = document.getElementById('councilSearch');
    const councilTable = document.getElementById('councilTable');
    
    councilSearch.addEventListener('keyup', function() {
        searchTable(councilTable, this.value);
    });
    
    function searchTable(table, query) {
        const rows = table.getElementsByTagName('tr');
        const searchText = query.toLowerCase();
        
        Array.from(rows).forEach(function(row) {
            if(row.getElementsByTagName('td').length > 0) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            }
        });
    }
});
</script>

<?php include 'includes/new_footer.php'; ?>