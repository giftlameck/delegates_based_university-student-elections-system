<?php
// elections_commission/delegate_results.php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php'; // Updated path: includes/ is inside elections_commission/

// Fetch unique schools and programmes for filtering
$schools = $conn->query("SELECT DISTINCT school FROM student_details ORDER BY school")->fetchAll(PDO::FETCH_COLUMN);
$programmes = $conn->query("SELECT DISTINCT programme FROM applications WHERE candidate_type = 'Delegate' AND status = 'Approved' ORDER BY programme")->fetchAll(PDO::FETCH_COLUMN);

// Filter parameters
$filter_school = isset($_GET['school']) ? $_GET['school'] : '';
$filter_programme = isset($_GET['programme']) ? $_GET['programme'] : '';

// Fetch delegate voting schedule
$schedule = $conn->query("SELECT start_date, end_date FROM election_schedule WHERE event_type = 'delegate_voting' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$voting_closed = $schedule && (new DateTime() > new DateTime($schedule['end_date']));

// Function to update delegate winners
function updateDelegateWinners($conn, $programme) {
    $conn->exec("DELETE FROM delegate_winners WHERE programme = '$programme'"); // Clear old winners

    // Count approved delegates
    $approved_count = $conn->query("SELECT COUNT(*) FROM applications WHERE candidate_type = 'Delegate' AND status = 'Approved' AND programme = '$programme'")->fetchColumn();

    // Count votes per delegate
    $votes_sql = "SELECT a.student_id, a.student_name, a.photo_path, sd.gender, COUNT(dv.delegate_id) as vote_count
                  FROM applications a
                  LEFT JOIN delegate_votes dv ON a.student_id = dv.delegate_id
                  LEFT JOIN student_details sd ON a.student_id = sd.Student_id
                  WHERE a.candidate_type = 'Delegate' AND a.status = 'Approved' AND a.programme = '$programme'
                  GROUP BY a.student_id, a.student_name, a.photo_path, sd.gender
                  ORDER BY vote_count DESC, a.student_id ASC";
    $delegates = $conn->query($votes_sql)->fetchAll(PDO::FETCH_ASSOC);

    if ($approved_count <= 3 && $approved_count > 0) {
        // Unopposed case
        foreach ($delegates as $delegate) {
            $conn->exec("INSERT INTO delegate_winners (delegate_id, student_name, photo_path, programme, school, gender, vote_count, status)
                         VALUES ('{$delegate['student_id']}', '{$delegate['student_name']}', '{$delegate['photo_path']}', '$programme', 
                                 (SELECT school FROM student_details WHERE Student_id = '{$delegate['student_id']}'), 
                                 '{$delegate['gender']}', 0, 'unopposed')");
        }
    } elseif ($approved_count > 3 && !empty($delegates[0]['vote_count'])) {
        // More than 3 with votes: Top 3 with gender adjustment
        $winners = array_slice($delegates, 0, 3); // Initial top 3
        $genders = array_column($winners, 'gender');
        
        // Check if all three are the same gender
        if (count(array_unique($genders)) === 1) {
            $opposite_gender = $genders[0] === 'M' ? 'F' : 'M';
            // Find next delegate of opposite gender
            foreach (array_slice($delegates, 3) as $delegate) {
                if ($delegate['gender'] === $opposite_gender) {
                    $winners[2] = $delegate; // Replace 3rd with opposite gender
                    break;
                }
            }
        }

        foreach ($winners as $winner) {
            $conn->exec("INSERT INTO delegate_winners (delegate_id, student_name, photo_path, programme, school, gender, vote_count, status)
                         VALUES ('{$winner['student_id']}', '{$winner['student_name']}', '{$winner['photo_path']}', '$programme', 
                                 (SELECT school FROM student_details WHERE Student_id = '{$winner['student_id']}'), 
                                 '{$winner['gender']}', {$winner['vote_count']}, 'elected')");
        }
    }
}

// Update winners for all programmes if voting has closed
if ($voting_closed) {
    foreach ($programmes as $prog) {
        updateDelegateWinners($conn, $prog);
    }
}

// Fetch results based on filters
$where = [];
if ($filter_school) $where[] = "sd.school = '$filter_school'";
if ($filter_programme) $where[] = "a.programme = '$filter_programme'";
$where_clause = $where ? 'AND ' . implode(' AND ', $where) : '';

$results_sql = "SELECT a.programme, a.student_id, a.student_name, sd.gender, sd.school, COUNT(dv.delegate_id) as vote_count
                FROM applications a
                LEFT JOIN delegate_votes dv ON a.student_id = dv.delegate_id
                LEFT JOIN student_details sd ON a.student_id = sd.Student_id
                WHERE a.candidate_type = 'Delegate' AND a.status = 'Approved' $where_clause
                GROUP BY a.programme, a.student_id, a.student_name, sd.gender, sd.school
                ORDER BY a.programme, vote_count DESC, a.student_id ASC";
$results = $conn->query($results_sql)->fetchAll(PDO::FETCH_ASSOC);

// Group results by programme
$programme_results = [];
foreach ($results as $row) {
    $programme_results[$row['programme']][] = $row;
}

// Fetch winners
$winners_sql = "SELECT * FROM delegate_winners" . ($filter_school || $filter_programme ? " WHERE " . ($filter_school ? "school = '$filter_school'" : '') . ($filter_programme ? ($filter_school ? " AND " : "") . "programme = '$filter_programme'" : "") : "") . " ORDER BY programme, vote_count DESC, delegate_id ASC";
$winners = $conn->query($winners_sql)->fetchAll(PDO::FETCH_ASSOC);
$programme_winners = [];
foreach ($winners as $winner) {
    $programme_winners[$winner['programme']][] = $winner;
}

include 'includes/new_layout.php';
?>

<!-- Page Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Delegate Election Results</h3>
                        <?php if ($voting_closed): ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-light btn-sm" onclick="exportResults('csv')">
                                <i class="fas fa-file-csv"></i> Export Results CSV
                            </button>
                            <button type="button" class="btn btn-light btn-sm" onclick="exportResults('pdf')">
                                <i class="fas fa-file-pdf"></i> Export Results PDF
                            </button>
                            <button type="button" class="btn btn-light btn-sm" onclick="exportWinners('csv')">
                                <i class="fas fa-file-csv"></i> Export Winners CSV
                            </button>
                            <button type="button" class="btn btn-light btn-sm" onclick="exportWinners('pdf')">
                                <i class="fas fa-file-pdf"></i> Export Winners PDF
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!$voting_closed): ?>
                        <div class="alert alert-info">
                            <h4 class="alert-heading"><i class="fas fa-info-circle"></i> Voting Period Status</h4>
                            <?php if ($schedule): ?>
                                <p>The delegate election voting period is currently active and will end on <?php echo date('F j, Y', strtotime($schedule['end_date'])); ?>.</p>
                                <p>Results will be available after the voting period ends.</p>
                            <?php else: ?>
                                <p>No voting schedule has been set for the delegate elections yet.</p>
                                <p>Please set up the voting schedule in the Election Schedule section.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Filter Form -->
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="school">School</label>
                                        <select class="form-control" id="school" name="school">
                                            <option value="">All Schools</option>
                                            <?php foreach ($schools as $school): ?>
                                                <option value="<?php echo htmlspecialchars($school); ?>" 
                                                        <?php echo isset($_GET['school']) && $_GET['school'] === $school ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($school); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="programme">Programme</label>
                                        <select class="form-control" id="programme" name="programme">
                                            <option value="">All Programmes</option>
                                            <?php foreach ($programmes as $programme): ?>
                                                <option value="<?php echo htmlspecialchars($programme); ?>"
                                                        <?php echo isset($_GET['programme']) && $_GET['programme'] === $programme ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($programme); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary d-block">Apply Filters</button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Results Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Programme</th>
                                        <th>School</th>
                                        <th>Candidate Name</th>
                                        <th>Votes</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($results)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No results found for the selected filters.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($results as $result): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($result['programme']); ?></td>
                                                <td><?php echo htmlspecialchars($result['school']); ?></td>
                                                <td><?php echo htmlspecialchars($result['student_name']); ?></td>
                                                <td><?php echo $result['vote_count']; ?></td>
                                                <td>
                                                    <?php if (isset($programme_winners[$result['programme']]) && in_array($result['student_id'], array_column($programme_winners[$result['programme']], 'delegate_id'))): ?>
                                                        <span class="badge bg-success">Winner</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Runner-up</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-select {
        border-radius: 10px;
    }
    
    .winner-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .winner-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .winner-avatar {
        position: relative;
        display: inline-block;
    }
    
    .winner-avatar img {
        border: 3px solid #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .icon-crown {
        font-size: 1.5rem;
        filter: drop-shadow(0 2px 3px rgba(0,0,0,0.2));
    }
    
    .badge {
        padding: 0.5rem 0.75rem;
        font-weight: 500;
    }
    
    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .alert {
        border-radius: 10px;
    }
    
    .programme-section .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .filter-section {
        background: linear-gradient(to right, #f8f9fa, #e9ecef);
        border: 1px solid #dee2e6;
    }
</style>

<script>
function exportResults(format) {
    const programme = document.getElementById('programme').value;
    if (!programme) {
        alert('Please select a programme first');
        return;
    }
    window.location.href = `export_delegate_results.php?programme=${encodeURIComponent(programme)}&format=${format}`;
}

function exportWinners(format) {
    window.location.href = 'export_delegate_winners.php?format=' + format;
}
</script>

<?php include 'includes/new_footer.php'; ?>