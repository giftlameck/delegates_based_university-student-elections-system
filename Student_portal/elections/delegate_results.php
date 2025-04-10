<?php
// elections/delegate_results_student.php
session_start();
include '../includes/db_connection.php';

$student_id = $_SESSION['student_id'];
$programme = $_SESSION['programme'];

// Debug: Check session variables
if (!isset($student_id) || !isset($programme)) {
    die("Session Error: student_id or programme not set.");
}

// Fetch delegate voting schedule
$schedule = $conn->query("SELECT start_date, end_date FROM election_schedule WHERE event_type = 'delegate_voting' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$voting_closed = $schedule && (new DateTime() > new DateTime($schedule['end_date']));

// Function to update delegate winners (borrowed from delegate_results.php)
function updateDelegateWinners($conn, $programme) {
    $conn->exec("DELETE FROM delegate_winners WHERE programme = '$programme'"); // Clear old winners

    $approved_count = $conn->query("SELECT COUNT(*) FROM applications WHERE candidate_type = 'Delegate' AND status = 'Approved' AND programme = '$programme'")->fetchColumn();

    $votes_sql = "SELECT a.student_id, a.student_name, sd.gender, COUNT(dv.delegate_id) as vote_count
                  FROM applications a
                  LEFT JOIN delegate_votes dv ON a.student_id = dv.delegate_id
                  LEFT JOIN student_details sd ON a.student_id = sd.Student_id
                  WHERE a.candidate_type = 'Delegate' AND a.status = 'Approved' AND a.programme = '$programme'
                  GROUP BY a.student_id, a.student_name, sd.gender
                  ORDER BY vote_count DESC, a.student_id ASC";
    $delegates = $conn->query($votes_sql)->fetchAll(PDO::FETCH_ASSOC);

    if ($approved_count <= 3 && $approved_count > 0) {
        foreach ($delegates as $delegate) {
            $conn->exec("INSERT INTO delegate_winners (delegate_id, student_name, programme, school, gender, vote_count, status)
                         VALUES ('{$delegate['student_id']}', '{$delegate['student_name']}', '$programme', 
                                 (SELECT school FROM student_details WHERE Student_id = '{$delegate['student_id']}'), 
                                 '{$delegate['gender']}', 0, 'unopposed')");
        }
    } elseif ($approved_count > 3 && !empty($delegates[0]['vote_count'])) {
        $winners = array_slice($delegates, 0, 3);
        $genders = array_column($winners, 'gender');
        if (count(array_unique($genders)) === 1) {
            $opposite_gender = $genders[0] === 'M' ? 'F' : 'M';
            foreach (array_slice($delegates, 3) as $delegate) {
                if ($delegate['gender'] === $opposite_gender) {
                    $winners[2] = $delegate;
                    break;
                }
            }
        }
        foreach ($winners as $winner) {
            $conn->exec("INSERT INTO delegate_winners (delegate_id, student_name, programme, school, gender, vote_count, status)
                         VALUES ('{$winner['student_id']}', '{$winner['student_name']}', '$programme', 
                                 (SELECT school FROM student_details WHERE Student_id = '{$winner['student_id']}'), 
                                 '{$winner['gender']}', {$winner['vote_count']}, 'elected')");
        }
    }
}

// Update winners if voting has closed
if ($voting_closed) {
    updateDelegateWinners($conn, $programme);
}

// Fetch results for the student's programme
$results_sql = "SELECT a.student_id, a.student_name, sd.gender, sd.school, COUNT(dv.delegate_id) as vote_count
                FROM applications a
                LEFT JOIN delegate_votes dv ON a.student_id = dv.delegate_id
                LEFT JOIN student_details sd ON a.student_id = sd.Student_id
                WHERE a.candidate_type = 'Delegate' AND a.status = 'Approved' AND a.programme = :programme
                GROUP BY a.student_id, a.student_name, sd.gender, sd.school
                ORDER BY vote_count DESC, a.student_id ASC";
$stmt = $conn->prepare($results_sql);
$stmt->execute(['programme' => $programme]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch winners
$winners_sql = "SELECT * FROM delegate_winners WHERE programme = :programme ORDER BY vote_count DESC, delegate_id ASC";
$stmt = $conn->prepare($winners_sql);
$stmt->execute(['programme' => $programme]);
$winners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate analysis data
$total_votes = array_sum(array_column($results, 'vote_count'));
$voters = $conn->query("SELECT COUNT(DISTINCT voter_id) FROM delegate_votes WHERE programme = '$programme'")->fetchColumn();
$eligible_voters = $conn->query("SELECT COUNT(*) FROM student_details WHERE programme = '$programme'")->fetchColumn();
$turnout = $eligible_voters > 0 ? round(($voters / $eligible_voters) * 100, 1) : 0;
$top_margin = count($results) > 1 ? $results[0]['vote_count'] - $results[1]['vote_count'] : 0;
?>

<div class="mt-4">
    <h3 class="text-primary mb-4">Delegate Election Results - <?php echo htmlspecialchars($programme); ?></h3>

    <?php if (!$voting_closed): ?>
        <div class="alert alert-info">Results will be available after the delegate voting period ends on <?php echo (new DateTime($schedule['end_date']))->format('F j, Y, g:i A'); ?>.</div>
    <?php elseif (empty($results)): ?>
        <div class="alert alert-warning">No delegate candidates or results available for <?php echo htmlspecialchars($programme); ?>.</div>
    <?php else: ?>
        <!-- General Ranking -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">General Ranking</h5>
            </div>
            <div class="card-body">
                <?php
                $approved_count = $conn->query("SELECT COUNT(*) FROM applications WHERE candidate_type = 'Delegate' AND status = 'Approved' AND programme = '$programme'")->fetchColumn();
                $has_votes = !empty($results[0]['vote_count']);
                ?>
                <?php if ($approved_count <= 3 && $approved_count > 0): ?>
                    <div class="alert alert-success">
                        No voting took place as the delegate(s) below won unopposed:
                        <ul>
                            <?php foreach ($results as $delegate): ?>
                                <li><?php echo htmlspecialchars($delegate['student_name']) . " (" . $delegate['student_id'] . ")"; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php elseif ($approved_count > 3 && !$has_votes): ?>
                    <div class="alert alert-warning">No votes were cast for this programme.</div>
                <?php else: ?>
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Rank</th>
                                <th>Name</th>
                                <th>Student ID</th>
                                <th>School</th>
                                <th>Gender</th>
                                <th>Votes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $rank => $delegate): ?>
                                <tr>
                                    <td><?php echo $rank + 1; ?></td>
                                    <td><?php echo htmlspecialchars($delegate['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($delegate['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($delegate['school']); ?></td>
                                    <td><?php echo $delegate['gender'] === 'M' ? 'Male' : 'Female'; ?></td>
                                    <td><?php echo $delegate['vote_count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Winners -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Elected Delegates</h5>
            </div>
            <div class="card-body">
                <?php if (empty($winners)): ?>
                    <div class="alert alert-warning">No winners determined due to no votes cast.</div>
                <?php else: ?>
                    <div class="row justify-content-center">
                        <?php foreach ($winners as $winner): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card winner-card text-center">
                                    <div class="card-body">
                                        <h6 class="text-success"><?php echo htmlspecialchars($winner['student_name']); ?></h6>
                                        <p class="text-muted small"><?php echo htmlspecialchars($winner['delegate_id']); ?></p>
                                        <span class="badge <?php echo $winner['status'] === 'unopposed' ? 'badge-warning' : 'badge-success'; ?>">
                                            <?php echo $winner['status'] === 'unopposed' ? 'Unopposed' : "Votes: {$winner['vote_count']}"; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pie Chart and Analysis -->
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Vote Distribution & Analysis</h5>
            </div>
            <div class="card-body">
                <?php if ($approved_count > 3 && $has_votes): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="votePieChart" height="200"></canvas>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-info">Election Insights</h6>
                            <ul class="list-unstyled">
                                <li><strong>Total Votes Cast:</strong> <?php echo $total_votes; ?></li>
                                <li><strong>Voter Turnout:</strong> <?php echo $turnout; ?>% (<?php echo $voters; ?> of <?php echo $eligible_voters; ?> eligible voters)</li>
                                <li><strong>Top Candidate Margin:</strong> <?php echo $top_margin; ?> votes</li>
                                <li><strong>Analysis:</strong> 
                                    <?php
                                    if ($turnout > 75) echo "High turnout indicates strong student engagement.";
                                    elseif ($turnout > 50) echo "Moderate turnout with decent participation.";
                                    else echo "Low turnout suggests limited student interest.";
                                    if ($top_margin > 10) echo " The top winner had a significant lead.";
                                    elseif ($top_margin > 0) echo " The race was close among top candidates.";
                                    ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <script src="../assets/js/chart.min.js"></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var ctx = document.getElementById('votePieChart').getContext('2d');
                            var chart = new Chart(ctx, {
                                type: 'pie',
                                data: {
                                    labels: [<?php echo "'" . implode("','", array_column($results, 'student_name')) . "'"; ?>],
                                    datasets: [{
                                        data: [<?php echo implode(',', array_column($results, 'vote_count')); ?>],
                                        backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d'],
                                        borderColor: '#fff',
                                        borderWidth: 2
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: { position: 'top' },
                                        tooltip: {
                                            callbacks: {
                                                label: function(tooltipItem) {
                                                    return tooltipItem.label + ': ' + tooltipItem.raw + ' votes';
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        });
                    </script>
                <?php else: ?>
                    <p class="text-muted">No vote distribution available due to unopposed winners or no votes cast.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    .card-header {
        border-radius: 15px 15px 0 0;
        padding: 10px 15px;
    }
    .table thead.thead-dark th {
        background: #343a40;
        color: #fff;
        border: none;
    }
    .table {
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
    }
    .winner-card {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        border: 2px solid #28a745;
        border-radius: 10px;
        transition: transform 0.3s ease;
    }
    .winner-card:hover {
        transform: scale(1.05);
    }
    .badge-success {
        background: #28a745;
    }
    .badge-warning {
        background: #ffc107;
        color: #333;
    }
    .text-info {
        color: #17a2b8 !important;
    }
</style>