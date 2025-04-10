<?php
// elections/student_council_voting.php
include '../includes/db_connection.php';

$student_id = $_SESSION['student_id'];
$current_date = new DateTime();

// Check if user is an elected delegate
$is_delegate = $conn->query("SELECT * FROM delegate_winners WHERE delegate_id = '$student_id'")->fetch(PDO::FETCH_ASSOC);
if (!$is_delegate) {
    die("<div class='alert alert-danger'>Only elected delegates can vote for Student Council positions.</div>");
}

// Fetch voting schedule
$schedule = $conn->query("SELECT start_date, end_date FROM election_schedule WHERE event_type = 'student_council_voting' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$voting_status = '';
if (!$schedule) {
    $voting_status = 'not_set';
} else {
    $start_date = new DateTime($schedule['start_date']);
    $end_date = new DateTime($schedule['end_date']);
    if ($current_date < $start_date) {
        $voting_status = 'not_open';
    } elseif ($current_date > $end_date) {
        $voting_status = 'closed';
    } else {
        $voting_status = 'open';
    }
}

// Positions array
$positions = [
    'ticket' => 'Chairperson & Vice Chairperson',
    'Secretary General' => 'Secretary General',
    'Treasurer' => 'Treasurer',
    'Campus Representative' => 'Campus Representative',
    'PWD Representative' => 'PWD Representative',
    'games-entertainment' => 'Games & Entertainment'
];

// Check voting status and fetch candidates
$has_voted = [];
$candidates = [];
if ($voting_status === 'open') {
    foreach ($positions as $key => $label) {
        $sql = "SELECT * FROM student_council_votes WHERE voter_id = :student_id AND position = :position";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['student_id' => $student_id, 'position' => $key === 'ticket' ? 'Chairperson' : ($key === 'games-entertainment' ? 'Games & Entertainment' : $key)]);
        $has_voted[$key] = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($key === 'ticket') {
            $sql = "SELECT a.student_id AS chair_id, a.student_name AS chair_name, a.photo_path AS chair_photo, 
                           b.student_id AS vice_id, b.student_name AS vice_name, b.photo_path AS vice_photo
                    FROM applications a
                    JOIN applications b ON a.vice_chairperson_id = b.student_id
                    WHERE a.candidate_type = 'Student Council' AND a.position = 'Chairperson' 
                    AND a.status = 'Approved' AND b.status = 'Approved'";
            $candidates[$key] = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $sql = "SELECT student_id, student_name, photo_path 
                    FROM applications 
                    WHERE candidate_type = 'Student Council' AND position = :position AND status = 'Approved'";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['position' => $key === 'games-entertainment' ? 'Games & Entertainment' : $key]);
            $candidates[$key] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}
?>

<div class="mt-4">
    <h3 class="text-primary mb-4 text-center" style="font-weight: bold; text-shadow: 1px 1px 2px rgba(0,0,0,0.1);">Student Council Voting</h3>

    <?php if ($voting_status === 'not_set'): ?>
        <div class="alert alert-info text-center" style="border-radius: 10px; background: linear-gradient(135deg, #e6f3fa, #cce5ff);">
            <i class="fas fa-info-circle mr-2"></i>The Student Council voting dates are not yet set.
        </div>
    <?php elseif ($voting_status === 'not_open'): ?>
        <div class="alert alert-warning text-center" style="border-radius: 10px; background: linear-gradient(135deg, #fff3cd, #ffeeba);">
            <i class="fas fa-clock mr-2"></i>Student Council voting is not yet open. It will open on <?php echo $start_date->format('F j, Y, g:i A'); ?> 
            and end on <?php echo $end_date->format('F j, Y, g:i A'); ?>.
        </div>
    <?php elseif ($voting_status === 'closed'): ?>
        <div class="alert alert-danger text-center" style="border-radius: 10px; background: linear-gradient(135deg, #f8d7da, #f5c6cb);">
            <i class="fas fa-times-circle mr-2"></i>Student Council voting has ended on <?php echo $end_date->format('F j, Y, g:i A'); ?>.
        </div>
    <?php else: ?>
        <div class="progress mb-4" style="height: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo (count(array_filter($has_voted)) / count($positions)) * 100; ?>%;" 
                 aria-valuenow="<?php echo count(array_filter($has_voted)); ?>" aria-valuemin="0" aria-valuemax="<?php echo count($positions); ?>">
                <span style="font-weight: bold;"><?php echo count(array_filter($has_voted)); ?>/<?php echo count($positions); ?> Positions Voted</span>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4 justify-content-center" style="border-bottom: 2px solid #dee2e6;">
            <?php foreach ($positions as $key => $label): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $key === 'ticket' ? 'active' : ''; ?>" data-toggle="tab" href="#<?php echo str_replace(' ', '-', $key); ?>-voting" 
                       style="border-radius: 20px 20px 0 0; margin: 0 5px; padding: 10px 20px; transition: all 0.3s;">
                        <?php echo $label; ?> <?php echo $has_voted[$key] ? '<span class="badge badge-success ml-1">Voted</span>' : ''; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content">
            <?php foreach ($positions as $key => $label): ?>
                <div id="<?php echo str_replace(' ', '-', $key); ?>-voting" class="tab-pane fade <?php echo $key === 'ticket' ? 'show active' : ''; ?>">
                    <?php if ($has_voted[$key]): ?>
                        <div class="alert alert-success">You have already voted for <?php echo $label; ?>.</div>
                    <?php elseif (empty($candidates[$key])): ?>
                        <div class="alert alert-warning">No approved candidates available for <?php echo $label; ?>.</div>
                    <?php elseif (count($candidates[$key]) === 1): ?>
                        <?php $unopposed = $candidates[$key][0]; ?>
                        <div class="alert alert-success text-center">
                            <?php if ($key === 'ticket'): ?>
                                <div style="display: flex; justify-content: center; gap: 20px; align-items: center; margin-bottom: 10px;">
                                    <img src="<?php echo htmlspecialchars($unopposed['chair_photo']); ?>" alt="Chair" style="width: 60px; height: 60px; border-radius: 50%; border: 2px solid #28a745;">
                                    <img src="<?php echo htmlspecialchars($unopposed['vice_photo']); ?>" alt="Vice" style="width: 60px; height: 60px; border-radius: 50%; border: 2px solid #28a745;">
                                </div>
                                There is no voting here, Since candidate <?php echo htmlspecialchars($unopposed['chair_name'] . ' & ' . $unopposed['vice_name']); ?> vied unopposed.
                            <?php else: ?>
                                <div style="margin-bottom: 10px;">
                                    <img src="<?php echo htmlspecialchars($unopposed['photo_path']); ?>" alt="<?php echo htmlspecialchars($unopposed['student_name']); ?>" style="width: 60px; height: 60px; border-radius: 50%; border: 2px solid #28a745;">
                                </div>
                                There is no voting here, Since candidate <?php echo htmlspecialchars($unopposed['student_name']); ?> vied unopposed.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Voting Instructions -->
                        <div class="card instruction-card mb-4 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Voting Instructions for <?php echo $label; ?></h5>
                            </div>
                            <div class="card-body">
                                <p><strong>What:</strong> You are voting for the <?php echo $label; ?> of the Student Council. <?php echo $key === 'ticket' ? 'This ticket includes both Chairperson and Vice Chairperson.' : 'This position represents a key role in student leadership.'; ?></p>
                                <p><strong>How:</strong> Select one <?php echo $key === 'ticket' ? 'ticket' : 'candidate'; ?> by clicking the "Vote" button next to their name(s), then click "Submit Vote" to cast your vote.</p>
                                <p><strong>Limit:</strong> You can vote only once for this position. Once submitted, your vote cannot be changed.</p>
                            </div>
                        </div>
                        <form method="POST" action="./submit_student_council_vote.php" id="vote-form-<?php echo $key; ?>" class="position-vote-form" data-position="<?php echo $key === 'ticket' ? 'Chairperson' : ($key === 'games-entertainment' ? 'Games & Entertainment' : $key); ?>">
                            <input type="hidden" name="position" value="<?php echo $key === 'ticket' ? 'Chairperson' : ($key === 'games-entertainment' ? 'Games & Entertainment' : $key); ?>">
                            <div class="row">
                                <?php if ($key === 'ticket'): ?>
                                    <?php foreach ($candidates[$key] as $ticket): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card ticket-card shadow-sm" 
                                                 data-chair-name="<?php echo htmlspecialchars($ticket['chair_name']); ?>" 
                                                 data-vice-name="<?php echo htmlspecialchars($ticket['vice_name']); ?>" 
                                                 data-chair-photo="<?php echo htmlspecialchars($ticket['chair_photo']); ?>" 
                                                 data-vice-photo="<?php echo htmlspecialchars($ticket['vice_photo']); ?>">
                                                <div class="card-body p-2 text-center">
                                                    <div class="row no-gutters">
                                                        <div class="col-6">
                                                            <img src="<?php echo htmlspecialchars($ticket['chair_photo']); ?>" alt="Chair" class="candidate-photo mb-1">
                                                            <small><?php echo htmlspecialchars($ticket['chair_name']); ?></small>
                                                        </div>
                                                        <div class="col-6">
                                                            <img src="<?php echo htmlspecialchars($ticket['vice_photo']); ?>" alt="Vice" class="candidate-photo mb-1">
                                                            <small><?php echo htmlspecialchars($ticket['vice_name']); ?></small>
                                                        </div>
                                                    </div>
                                                    <input type="radio" name="ticket_id" value="<?php echo htmlspecialchars($ticket['chair_id']); ?>" id="ticket_<?php echo $ticket['chair_id']; ?>" class="form-check-input mt-2" required>
                                                    <label for="ticket_<?php echo $ticket['chair_id']; ?>" class="vote-btn btn btn-outline-primary btn-sm mt-1">Vote</label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($candidates[$key] as $candidate): ?>
                                        <div class="col-md-3 mb-3">
                                            <div class="card candidate-card shadow-sm" 
                                                 data-name="<?php echo htmlspecialchars($candidate['student_name']); ?>" 
                                                 data-photo="<?php echo htmlspecialchars($candidate['photo_path']); ?>">
                                                <div class="card-body p-2 text-center">
                                                    <img src="<?php echo htmlspecialchars($candidate['photo_path']); ?>" alt="<?php echo htmlspecialchars($candidate['student_name']); ?>" class="candidate-photo mb-1">
                                                    <small><?php echo htmlspecialchars($candidate['student_name']); ?></small>
                                                    <input type="radio" name="candidate_id" value="<?php echo htmlspecialchars($candidate['student_id']); ?>" id="candidate_<?php echo $candidate['student_id']; ?>" class="form-check-input mt-2" required>
                                                    <label for="candidate_<?php echo $candidate['student_id']; ?>" class="vote-btn btn btn-outline-primary btn-sm mt-1">Vote</label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm mt-2" style="width: 100%; border-radius: 20px;">Submit Vote for <?php echo $label; ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <style>
        .nav-tabs .nav-link {
            border: none;
            background: #f8f9fa;
            color: #495057;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-tabs .nav-link:before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: #007bff;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .nav-tabs .nav-link:hover:before,
        .nav-tabs .nav-link.active:before {
            transform: scaleX(1);
        }

        .nav-tabs .nav-link.active {
            background: #fff;
            color: #007bff;
            border: none;
            box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
        }

        .ticket-card, .candidate-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            transition: all 0.3s ease;
            height: 200px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .ticket-card:hover, .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
            border-color: #007bff;
        }

        .candidate-photo {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border: 3px solid #007bff;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .ticket-card:hover .candidate-photo, .candidate-card:hover .candidate-photo {
            border-color: #28a745;
            transform: scale(1.05);
        }

        .vote-btn {
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            background: #fff;
            border: 2px solid #007bff;
            color: #007bff;
        }

        .vote-btn:hover {
            background: #007bff;
            color: #fff;
            transform: translateY(-2px);
        }

        .instruction-card {
            border: none;
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .instruction-card .card-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: #fff;
            border-radius: 15px 15px 0 0;
            padding: 15px;
        }

        .instruction-card .card-body {
            padding: 20px;
        }

        .instruction-card p {
            margin-bottom: 15px;
            font-size: 0.95rem;
            color: #495057;
            line-height: 1.6;
        }

        .instruction-card strong {
            color: #007bff;
            font-weight: 600;
        }

        .alert {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 15px 20px;
        }

        .progress {
            height: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .progress-bar {
            font-size: 0.9rem;
            font-weight: 500;
            transition: width 0.5s ease-in-out;
        }

        small {
            font-size: 0.8rem;
            font-weight: 500;
            color: #495057;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 5px;
        }

        .badge {
            padding: 5px 10px;
            font-weight: 500;
            border-radius: 15px;
        }

        .badge-success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
    </style>

    <!-- Add Bootstrap JS dependencies -->
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
    <script src="../assets/js/popper.min.js"></script>
    <script>
        // Initialize Bootstrap tabs
        $(document).ready(function() {
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                // Ensure the tab content is properly displayed
                $(e.target).tab('show');
            });
        });
    </script>
</div>