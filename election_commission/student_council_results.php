<?php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

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
        $sql = "SELECT a.student_id AS chair_id, a.student_name AS chair_name, a.photo_path AS chair_photo, 
                       b.student_id AS vice_id, b.student_name AS vice_name, b.photo_path AS vice_photo, 
                       COUNT(scv.ticket_id) AS vote_count
                FROM applications a
                JOIN applications b ON a.vice_chairperson_id = b.student_id
                LEFT JOIN student_council_votes scv ON a.student_id = scv.ticket_id
                WHERE a.candidate_type = 'Student Council' AND a.position = 'Chairperson' 
                AND a.status = 'Approved' AND b.status = 'Approved'
                GROUP BY a.student_id, a.student_name, b.student_id, b.student_name, a.photo_path, b.photo_path
                ORDER BY vote_count DESC";
    } else {
        $sql = "SELECT a.student_id, a.student_name, a.photo_path, COUNT(scv.ticket_id) AS vote_count
                FROM applications a
                LEFT JOIN student_council_votes scv ON a.student_id = scv.ticket_id
                WHERE a.candidate_type = 'Student Council' AND a.position = :position AND a.status = 'Approved'
                GROUP BY a.student_id, a.student_name, a.photo_path
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

include 'includes/new_layout.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Student Council Election Results</h3>
                        <?php if ($voting_closed && !empty($results)): ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-light btn-sm" onclick="exportResults('csv')">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </button>
                            <button type="button" class="btn btn-light btn-sm" onclick="exportResults('pdf')">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!$schedule): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> The elections for Student Council have not been held yet.
                        </div>
                    <?php elseif (!$voting_closed): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Results will be available after the voting period ends on 
                            <?php echo (new DateTime($schedule['end_date']))->format('F j, Y, g:i A'); ?>
                        </div>
                    <?php elseif (empty($results)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No candidates or results available.
                        </div>
                    <?php else: ?>
                        <!-- Results Overview -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Results Overview</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($results as $position => $position_results): ?>
                                    <div class="mb-4">
                                        <h6 class="text-primary">
                                            <?php echo $position === 'Chairperson' ? 'Chairperson & Vice Chairperson Ticket' : htmlspecialchars($position); ?> Results
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>Rank</th>
                                                        <th>Candidate/Ticket</th>
                                                        <th>Votes</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($position_results as $rank => $result): ?>
                                                        <tr>
                                                            <td><?php echo $rank + 1; ?></td>
                                                            <td>
                                                                <?php if ($position === 'Chairperson'): ?>
                                                                    <?php echo htmlspecialchars($result['chair_name']) . " & " . htmlspecialchars($result['vice_name']); ?>
                                                                <?php else: ?>
                                                                    <?php echo htmlspecialchars($result['student_name']); ?>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php 
                                                                if (count($position_results) === 1 && $result['vote_count'] == 0) {
                                                                    echo "No voting took place (Unopposed)";
                                                                } else {
                                                                    echo $result['vote_count'];
                                                                }
                                                                ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Elected Winners -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Elected Student Council</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($positions as $position): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body text-center">
                                                    <h6 class="text-success">
                                                        <?php echo $position === 'Chairperson' ? 'Chairperson & Vice Chairperson' : htmlspecialchars($position); ?>
                                                    </h6>
                                                    <?php if (isset($winners[$position])): ?>
                                                        <?php if ($position === 'Chairperson'): ?>
                                                            <div class="mb-3">
                                                                <img src="<?php echo htmlspecialchars($winners[$position]['chair_photo'] ?: 'assets/images/default-avatar.png'); ?>" 
                                                                     alt="<?php echo htmlspecialchars($winners[$position]['chair_name']); ?>" 
                                                                     class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                                                                <p class="mb-1"><?php echo htmlspecialchars($winners[$position]['chair_name']); ?></p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <img src="<?php echo htmlspecialchars($winners[$position]['vice_photo'] ?: 'assets/images/default-avatar.png'); ?>" 
                                                                     alt="<?php echo htmlspecialchars($winners[$position]['vice_name']); ?>" 
                                                                     class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                                                                <p class="mb-1"><?php echo htmlspecialchars($winners[$position]['vice_name']); ?></p>
                                                            </div>
                                                        <?php else: ?>
                                                            <img src="<?php echo htmlspecialchars($winners[$position]['photo_path'] ?: 'assets/images/default-avatar.png'); ?>" 
                                                                 alt="<?php echo htmlspecialchars($winners[$position]['student_name']); ?>" 
                                                                 class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                                                            <p class="mb-1"><?php echo htmlspecialchars($winners[$position]['student_name']); ?></p>
                                                        <?php endif; ?>
                                                        <span class="badge <?php echo $winners[$position]['status'] === 'unopposed' ? 'bg-warning' : 'bg-success'; ?>">
                                                            <?php echo $winners[$position]['status'] === 'unopposed' ? 'Unopposed' : "Votes: {$winners[$position]['vote_count']}"; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <p class="text-warning">No winner (Tie - Run-off TBD)</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Analysis -->
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Quick Analysis</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li><strong>Total Votes Cast:</strong> <?php echo $total_votes; ?></li>
                                    <li><strong>Voter Turnout:</strong> <?php echo $turnout; ?>% (<?php echo $voters; ?> of <?php echo $eligible_voters; ?> delegates voted)</li>
                                    <li><strong>Insights:</strong> 
                                        <?php
                                        if ($turnout > 75) echo "High delegate turnout shows strong engagement.";
                                        elseif ($turnout > 50) echo "Moderate turnout with solid participation.";
                                        else echo "Low turnout suggests limited delegate interest.";
                                        $ties = count($positions) - count($winners);
                                        if ($ties > 0) echo " $ties position(s) tied or unopposed, awaiting run-offs if tied.";
                                        ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/new_footer.php'; ?>

<script>
function exportResults(format) {
    window.location.href = 'export_student_council_results.php?format=' + format;
}
</script> 