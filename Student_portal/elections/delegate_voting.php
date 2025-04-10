<?php
// elections/delegate_voting.php
include '../includes/db_connection.php';

$student_id = $_SESSION['student_id'];
$programme = $_SESSION['programme'];

// Debug: Check session variables
if (!isset($student_id) || !isset($programme)) {
    die("Session Error: student_id or programme not set.");
}

// Get current date and time (assuming server timezone is set correctly)
$current_date = new DateTime();

// Fetch delegate voting schedule
$sql = "SELECT start_date, end_date 
        FROM election_schedule 
        WHERE event_type = 'delegate_voting' 
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute();
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

// Determine voting status
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

// Check if the student has already voted (only if voting is open)
$has_voted = false;
if ($voting_status === 'open') {
    $sql = "SELECT * FROM delegate_votes WHERE voter_id = :student_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['student_id' => $student_id]);
    $has_voted = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch approved delegate candidates (only if voting is open)
$candidates = [];
if ($voting_status === 'open') {
    $sql = "SELECT student_id, student_name, photo_path 
            FROM applications 
            WHERE candidate_type = 'Delegate' 
            AND status = 'Approved' 
            AND programme = :programme";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['programme' => $programme]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="mt-4">
    <h3 class="text-primary">Delegate Voting - <?php echo htmlspecialchars($programme); ?></h3>
    
    <?php if ($voting_status === 'not_set'): ?>
        <div class="alert alert-info">The delegate voting dates are not yet set.</div>
    <?php elseif ($voting_status === 'not_open'): ?>
        <div class="alert alert-warning">
            Delegate voting is not yet open. It will open on <?php echo $start_date->format('F j, Y, g:i A'); ?> 
            and end on <?php echo $end_date->format('F j, Y, g:i A'); ?>.
        </div>
    <?php elseif ($voting_status === 'closed'): ?>
        <div class="alert alert-danger">
            Delegate voting has ended on <?php echo $end_date->format('F j, Y, g:i A'); ?>.
        </div>
    <?php elseif ($has_voted): ?>
        <div class="alert alert-success">You have already voted for a delegate.</div>
    <?php elseif (empty($candidates)): ?>
        <div class="alert alert-warning">No approved delegate candidates available.</div>
    <?php elseif (count($candidates) <= 3): ?>
        <div class="alert alert-info text-center">
            <p>Sorry, there is no election for your programme! The following delegates win unopposed:</p>
            <div class="row justify-content-center">
                <?php foreach ($candidates as $candidate): ?>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card ballot-card shadow-sm h-100" data-name="<?php echo htmlspecialchars($candidate['student_name']); ?>" data-photo="<?php echo htmlspecialchars($candidate['photo_path']); ?>">
                            <div class="card-body text-center p-2">
                                <img src="<?php echo htmlspecialchars($candidate['photo_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($candidate['student_name']); ?>" 
                                     class="candidate-photo rounded-circle mb-2">
                                <h6 class="card-title"><?php echo htmlspecialchars($candidate['student_name']); ?></h6>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars($candidate['student_id']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Voting Instructions -->
        <div class="card instruction-card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Voting Instructions</h5>
            </div>
            <div class="card-body">
                <p><strong>What:</strong> You are voting to elect delegates for your programme (<?php echo htmlspecialchars($programme); ?>). These delegates will represent your interests and participate in the Student Council election.</p>
                <p><strong>How:</strong> Select one candidate by clicking the "Vote" radio button next to their name, then click "Submit Vote" to cast your vote.</p>
                <p><strong>Limit:</strong> You can vote only once for this election. Once submitted, your vote cannot be changed.</p>
            </div>
        </div>
        <!-- Display ballot for voting -->
        <form method="POST" action="./submit_delegate_vote.php" id="delegate-vote-form" class="delegate-vote-form">
            <div class="row justify-content-center">
                <?php foreach ($candidates as $candidate): ?>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card ballot-card shadow-sm h-100" data-name="<?php echo htmlspecialchars($candidate['student_name']); ?>" data-photo="<?php echo htmlspecialchars($candidate['photo_path']); ?>">
                            <div class="card-body text-center p-2">
                                <img src="<?php echo htmlspecialchars($candidate['photo_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($candidate['student_name']); ?>" 
                                     class="candidate-photo rounded-circle mb-2">
                                <h6 class="card-title"><?php echo htmlspecialchars($candidate['student_name']); ?></h6>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars($candidate['student_id']); ?></p>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" 
                                           name="delegate_id" 
                                           value="<?php echo htmlspecialchars($candidate['student_id']); ?>" 
                                           id="delegate_<?php echo $candidate['student_id']; ?>" required>
                                    <label class="form-check-label vote-label" for="delegate_<?php echo $candidate['student_id']; ?>">Vote</label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary mt-3" id="submit-vote-btn">Submit Vote</button>
            </div>
        </form>
    <?php endif; ?>

    <style>
        .ballot-card {
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border: 2px solid #007bff;
            border-radius: 15px;
            transition: transform 0.3s ease, border-color 0.3s ease;
            overflow: hidden;
            max-width: 200px;
            margin: 0 auto;
        }
        .ballot-card:hover {
            transform: scale(1.05);
            border-color: #28a745;
        }
        .candidate-photo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 2px solid #007bff;
            border-radius: 50%;
            transition: border-color 0.3s ease;
        }
        .ballot-card:hover .candidate-photo {
            border-color: #28a745;
        }
        .card-body {
            padding: 10px !important;
        }
        .card-title {
            font-size: 1rem;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }
        .card-text {
            font-size: 0.8rem;
            margin-bottom: 8px;
        }
        .form-check-input {
            margin-top: 0;
        }
        .vote-label {
            color: #007bff;
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.3s ease;
            cursor: pointer;
        }
        .vote-label:hover {
            color: #28a745;
        }
        .btn-primary {
            border-radius: 20px;
            padding: 8px 20px;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .instruction-card {
            border: 1px solid #007bff;
            border-radius: 10px;
            background: #f8f9fa;
        }
        .instruction-card .card-header {
            padding: 10px;
            border-radius: 10px 10px 0 0;
        }
        .instruction-card .card-body {
            padding: 15px;
        }
        .instruction-card p {
            margin-bottom: 10px;
            font-size: 0.95rem;
            color: #333;
        }
        .instruction-card strong {
            color: #007bff;
        }
    </style>
</div>