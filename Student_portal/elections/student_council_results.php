<?php
// elections/student_council_results.php
include '../includes/db_connection.php';

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
            $winners[$position]['vote_count'] = 0; // Explicitly set for consistency
            $winners[$position]['status'] = 'unopposed';
        } elseif (!$tied && $top_votes > 0) {
            // Voted winner: Highest votes, no tie
            $winners[$position] = $position_results[0];
            $winners[$position]['status'] = 'elected';
        }
    }
}
?>

<div class="mt-4">
    <h3 class="text-primary mb-4 text-center" style="font-weight: bold; text-shadow: 1px 1px 2px rgba(0,0,0,0.1);">Student Council Election Results</h3>

    <?php if (!$schedule): ?>
        <div class="alert alert-warning text-center" style="border-radius: 10px; background: linear-gradient(135deg, #fff3cd, #ffeeba);">
            The elections for Student Council are Not yet Held, Hence there are no results at the moment!
        </div>
    <?php elseif (!$voting_closed): ?>
        <div class="alert alert-info text-center" style="border-radius: 10px; background: linear-gradient(135deg, #e6f3fa, #cce5ff);">
            Results will be available after the Student Council voting period ends on <?php echo (new DateTime($schedule['end_date']))->format('F j, Y, g:i A'); ?>.
        </div>
    <?php elseif (empty($results)): ?>
        <div class="alert alert-warning text-center" style="border-radius: 10px; background: linear-gradient(135deg, #fff3cd, #ffeeba);">
            No Student Council candidates or results available.
        </div>
    <?php else: ?>
        <!-- Results Overview -->
        <div class="card shadow-sm mb-4" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); border: 1px solid #007bff;">
            <div class="card-header bg-primary text-white text-center" style="border-radius: 15px 15px 0 0;">
                <h5 class="mb-0">Results Overview</h5>
            </div>
            <div class="card-body">
                <?php foreach ($results as $position => $position_results): ?>
                    <div class="mb-4">
                        <h6 class="text-primary" style="font-weight: bold;">
                            <?php echo $position === 'Chairperson' ? 'Chairperson & Vice Chairperson Ticket' : htmlspecialchars($position); ?> Results
                        </h6>
                        <table class="table table-striped table-bordered" style="border-radius: 10px; overflow: hidden;">
                            <thead style="background: linear-gradient(to right, #007bff, #0056b3); color: #fff;">
                                <tr>
                                    <th>Rank</th>
                                    <th>Candidate/Ticket</th>
                                    <th>Votes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($position_results as $rank => $result): ?>
                                    <tr style="transition: background 0.3s;">
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
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Elected Winners -->
        <div class="card shadow-sm mb-4" style="background: #fff; border: 2px solid #28a745;">
            <div class="card-header bg-success text-white text-center" style="border-radius: 15px 15px 0 0;">
                <h5 class="mb-0">Elected Student Council</h5>
            </div>
            <div class="card-body">
                <div class="row justify-content-center">
                    <?php foreach ($positions as $position): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card winner-card text-center" style="background: linear-gradient(135deg, #d4edda, #c3e6cb);">
                                <div class="card-body">
                                    <h6 class="text-success" style="font-weight: bold;">
                                        <?php echo $position === 'Chairperson' ? 'Chairperson & Vice Chairperson' : htmlspecialchars($position); ?>
                                    </h6>
                                    <?php if (isset($winners[$position])): ?>
                                        <?php if ($position === 'Chairperson'): ?>
                                            <img src="<?php echo htmlspecialchars($winners[$position]['chair_photo'] ?: '../assets/images/default-avatar.png'); ?>" 
                                                 alt="<?php echo htmlspecialchars($winners[$position]['chair_name']); ?>" 
                                                 class="winner-photo rounded-circle mb-2">
                                            <p class="text-muted small"><?php echo htmlspecialchars($winners[$position]['chair_name']); ?></p>
                                            <img src="<?php echo htmlspecialchars($winners[$position]['vice_photo'] ?: '../assets/images/default-avatar.png'); ?>" 
                                                 alt="<?php echo htmlspecialchars($winners[$position]['vice_name']); ?>" 
                                                 class="winner-photo rounded-circle mb-2">
                                            <p class="text-muted small"><?php echo htmlspecialchars($winners[$position]['vice_name']); ?></p>
                                            <span class="badge <?php echo $winners[$position]['status'] === 'unopposed' ? 'badge-warning' : 'badge-success'; ?> py-1 px-2">
                                                <?php echo $winners[$position]['status'] === 'unopposed' ? 'Unopposed' : "Votes: {$winners[$position]['vote_count']}"; ?>
                                            </span>
                                        <?php else: ?>
                                            <img src="<?php echo htmlspecialchars($winners[$position]['photo_path'] ?: '../assets/images/default-avatar.png'); ?>" 
                                                 alt="<?php echo htmlspecialchars($winners[$position]['student_name']); ?>" 
                                                 class="winner-photo rounded-circle mb-2">
                                            <p class="text-muted small"><?php echo htmlspecialchars($winners[$position]['student_name']); ?></p>
                                            <span class="badge <?php echo $winners[$position]['status'] === 'unopposed' ? 'badge-warning' : 'badge-success'; ?> py-1 px-2">
                                                <?php echo $winners[$position]['status'] === 'unopposed' ? 'Unopposed' : "Votes: {$winners[$position]['vote_count']}"; ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="text-warning" style="font-style: italic;">No winner (Tie - Run-off TBD)</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
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
                    <li><strong>Voter Turnout:</strong> <span class="text-info"><?php echo $turnout; ?>%</span> (<?php echo $voters; ?> of <?php echo $eligible_voters; ?> delegates voted)</li>
                    <li><strong>Insights:</strong> 
                        <span class="text-info">
                            <?php
                            if ($turnout > 75) echo "High delegate turnout shows strong engagement.";
                            elseif ($turnout > 50) echo "Moderate turnout with solid participation.";
                            else echo "Low turnout suggests limited delegate interest.";
                            $ties = count($positions) - count($winners);
                            if ($ties > 0) echo " $ties position(s) tied or unopposed, awaiting run-offs if tied.";
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