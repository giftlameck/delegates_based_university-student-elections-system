<?php
include '../includes/db_connection.php';

// Check if student has already submitted feedback
$stmt = $conn->prepare("SELECT * FROM election_feedback WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]);
$existing_feedback = $stmt->fetch();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Election System Feedback</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                            <?php 
                            echo $_SESSION['message'];
                            unset($_SESSION['message']);
                            unset($_SESSION['message_type']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($existing_feedback): ?>
                        <div class="alert alert-info">
                            You have already submitted feedback for this election. Thank you for your participation!
                        </div>
                    <?php else: ?>
                        <form method="POST" action="submit_feedback.php">
                            <input type="hidden" name="section" value="feedback">
                            <div class="form-group">
                                <label>Overall Rating</label>
                                <div class="rating">
                                    <?php for($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                        <label for="star<?php echo $i; ?>">☆</label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Feedback Type</label>
                                <select class="form-control" name="feedback_type" required>
                                    <option value="">Select Type</option>
                                    <option value="voting_experience">Voting Experience</option>
                                    <option value="system_usability">System Usability</option>
                                    <option value="candidate_info">Candidate Information</option>
                                    <option value="election_process">Overall Election Process</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Comments</label>
                                <textarea class="form-control" name="comments" rows="4" required></textarea>
                            </div>

                            <div class="form-group">
                                <label>Suggestions for Improvement</label>
                                <textarea class="form-control" name="suggestions" rows="4"></textarea>
                            </div>

                            <button type="submit" name="feedback_submit" class="btn btn-primary">Submit Feedback</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.rating input {
    display: none;
}

.rating label {
    font-size: 30px;
    color: #ddd;
    padding: 5px;
    cursor: pointer;
    transition: color 0.3s;
}

.rating input:checked ~ label,
.rating label:hover,
.rating label:hover ~ label {
    color: #ffd700;
}
</style> 