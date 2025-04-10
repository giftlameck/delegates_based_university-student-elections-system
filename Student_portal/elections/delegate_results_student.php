<?php
// elections/delegate_results_student.php
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

// Fetch winners with photo paths
$winners_sql = "SELECT dw.*, a.photo_path 
                FROM delegate_winners dw
                LEFT JOIN applications a ON dw.delegate_id = a.student_id
                WHERE dw.programme = :programme 
                ORDER BY dw.vote_count DESC, dw.delegate_id ASC";
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
    <h3 class="text-primary mb-4 text-center" style="font-weight: bold; text-shadow: 1px 1px 2px rgba(0,0,0,0.1);">Delegate Election Results - <?php echo htmlspecialchars($programme); ?></h3>

    <?php if (!$schedule): ?>
        <div class="alert alert-warning text-center" style="border-radius: 10px; background: linear-gradient(135deg, #fff3cd, #ffeeba);">
            The elections for Delegates are Not yet Held, Hence there are no results at the moment!
        </div>
    <?php elseif (!$voting_closed): ?>
        <div class="alert alert-info text-center" style="border-radius: 10px; background: linear-gradient(135deg, #e6f3fa, #cce5ff);">
            Results will be available after the delegate voting period ends on <?php echo (new DateTime($schedule['end_date']))->format('F j, Y, g:i A'); ?>.
        </div>
    <?php elseif (empty($results)): ?>
        <div class="alert alert-warning text-center" style="border-radius: 10px; background: linear-gradient(135deg, #fff3cd, #ffeeba);">
            No delegate candidates or results available for <?php echo htmlspecialchars($programme); ?>.
        </div>
    <?php else: ?>
        <!-- How Results Are Determined -->
        <div class="card shadow-sm mb-4" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); border: 1px solid #007bff;">
            <div class="card-header bg-primary text-white text-center" style="border-radius: 15px 15px 0 0;">
                <h5 class="mb-0">How Results Are Determined</h5>
            </div>
            <div class="card-body">
                <p class="text-muted" style="font-size: 0.95rem;">
                    <strong>General Ranking:</strong> Candidates are ranked by the total votes they received, from highest to lowest. Ties are broken by student ID order.<br>
                    <strong>Elected Delegates:</strong> If ≤3 candidates applied, they win unopposed. If >3, the top 3 vote-getters are elected, with at least one from each gender (e.g., if all top 3 are male, the 3rd is replaced by the highest-voted female, if available).
                </p>
            </div>
        </div>

        <!-- General Ranking -->
        <div class="card shadow-sm mb-4" style="background: #fff; border: 2px solid #007bff;">
            <div class="card-header bg-primary text-white text-center" style="border-radius: 15px 15px 0 0;">
                <h5 class="mb-0">General Ranking</h5>
            </div>
            <div class="card-body">
                <?php
                $approved_count = $conn->query("SELECT COUNT(*) FROM applications WHERE candidate_type = 'Delegate' AND status = 'Approved' AND programme = '$programme'")->fetchColumn();
                $has_votes = !empty($results[0]['vote_count']);
                ?>
                <?php if ($approved_count <= 3 && $approved_count > 0): ?>
                    <div class="alert alert-success text-center" style="border-radius: 10px; background: linear-gradient(135deg, #d4edda, #c3e6cb);">
                        No voting took place as the delegate(s) below won unopposed:
                        <ul class="list-unstyled mt-2">
                            <?php foreach ($results as $delegate): ?>
                                <li><?php echo htmlspecialchars($delegate['student_name']) . " (" . $delegate['student_id'] . ")"; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php elseif ($approved_count > 3 && !$has_votes): ?>
                    <div class="alert alert-warning text-center" style="border-radius: 10px; background: linear-gradient(135deg, #fff3cd, #ffeeba);">
                        No votes were cast for this programme.
                    </div>
                <?php else: ?>
                    <table class="table table-striped table-bordered" style="border-radius: 10px; overflow: hidden;">
                        <thead style="background: linear-gradient(to right, #007bff, #0056b3); color: #fff;">
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
                                <tr style="transition: background 0.3s;">
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

        <!-- Elected Delegates -->
        <div class="card shadow-sm mb-4" style="background: #fff; border: 2px solid #28a745;">
            <div class="card-header bg-success text-white text-center" style="border-radius: 15px 15px 0 0;">
                <h5 class="mb-0">Elected Delegates</h5>
            </div>
            <div class="card-body">
                <?php if (empty($winners)): ?>
                    <div class="alert alert-warning text-center" style="border-radius: 10px; background: linear-gradient(135deg, #fff3cd, #ffeeba);">
                        No winners determined due to no votes cast.
                    </div>
                <?php else: ?>
                    <div class="row justify-content-center">
                        <?php foreach ($winners as $winner): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card winner-card text-center" style="background: linear-gradient(135deg, #d4edda, #c3e6cb);">
                                    <div class="card-body">
                                        <img src="<?php echo htmlspecialchars($winner['photo_path'] ?: '../assets/images/default-avatar.png'); ?>" 
                                             alt="<?php echo htmlspecialchars($winner['student_name']); ?>" 
                                             class="winner-photo rounded-circle mb-2">
                                        <h6 class="text-success" style="font-weight: bold;"><?php echo htmlspecialchars($winner['student_name']); ?></h6>
                                        <p class="text-muted small"><?php echo htmlspecialchars($winner['delegate_id']); ?></p>
                                        <span class="badge <?php echo $winner['status'] === 'unopposed' ? 'badge-warning' : 'badge-success'; ?> py-1 px-2">
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

        <!-- Quick Analysis -->
        <div class="card shadow-sm" style="background: linear-gradient(135deg, #e6f7fa, #ccefff); border: 2px solid #17a2b8;">
            <div class="card-header bg-info text-white text-center" style="border-radius: 15px 15px 0 0;">
                <h5 class="mb-0">Quick Analysis</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled text-muted" style="font-size: 0.95rem;">
                    <li><strong>Total Votes Cast:</strong> <span class="text-info"><?php echo $total_votes; ?></span></li>
                    <li><strong>Voter Turnout:</strong> <span class="text-info"><?php echo $turnout; ?>%</span> (<?php echo $voters; ?> of <?php echo $eligible_voters; ?> eligible voters)</li>
                    <li><strong>Top Candidate Margin:</strong> <span class="text-info"><?php echo $top_margin; ?> votes</span></li>
                    <li><strong>Insights:</strong> 
                        <span class="text-info">
                            <?php
                            if ($turnout > 75) echo "High turnout reflects strong student engagement.";
                            elseif ($turnout > 50) echo "Moderate turnout shows decent participation.";
                            else echo "Low turnout suggests limited interest.";
                            if ($top_margin > 10) echo " The top winner dominated the race.";
                            elseif ($top_margin > 0) echo " It was a tight contest at the top.";
                            ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
    }
    .card-header {
        border-radius: 15px 15px 0 0;
        padding: 12px 20px;
        font-weight: bold;
    }
    .table {
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 0;
    }
    .table thead th {
        background: linear-gradient(to right, #007bff, #0056b3);
        color: #fff;
        border: none;
        padding: 10px;
    }
    .table tbody tr:hover {
        background: #f1f3f5;
    }
    .winner-card {
        border: 2px solid #28a745;
        border-radius: 12px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .winner-card:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 20px rgba(40, 167, 69, 0.2);
    }
    .winner-photo {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border: 3px solid #28a745;
        border-radius: 50%;
        transition: border-color 0.3s ease;
    }
    .winner-card:hover .winner-photo {
        border-color: #007bff;
    }
    .badge-success {
        background: #28a745;
        font-size: 0.9rem;
    }
    .badge-warning {
        background: #ffc107;
        color: #333;
        font-size: 0.9rem;
    }
    .text-info {
        color: #17a2b8 !important;
        font-weight: 600;
    }
    .text-muted {
        color: #6c757d !important;
    }
    .alert {
        border: none;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }
</style>