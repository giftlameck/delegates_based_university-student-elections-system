<?php
session_name('election_commission');
session_start();
include 'includes/db_connection.php';

if (!isset($_GET['id'])) {
    die('Ticket ID not provided');
}

$ticket_id = (int)$_GET['id'];
$sql = "SELECT * FROM election_support_tickets WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die('Ticket not found');
}

// Get ticket responses - with error handling for missing table
$responses = [];
try {
    $sql = "SELECT * FROM election_support_responses WHERE ticket_id = ? ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ticket_id]);
    $responses = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table doesn't exist yet, just continue with empty responses
    $responses = [];
}
?>

<div class="ticket-details">
    <!-- Ticket Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Ticket #<?= $ticket['id'] ?></h4>
            <p class="text-muted mb-0">
                Created on <?= date('F j, Y, g:i a', strtotime($ticket['created_at'])) ?>
            </p>
        </div>
        <div class="text-right">
            <span class="badge badge-<?= 
                $ticket['status'] === 'open' ? 'success' : 
                ($ticket['status'] === 'in_progress' ? 'warning' : 'secondary')
            ?> mb-2">
                <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
            </span>
            <br>
            <span class="badge badge-<?= 
                $ticket['priority'] === 'high' ? 'danger' : 
                ($ticket['priority'] === 'medium' ? 'warning' : 'secondary')
            ?>">
                <?= ucfirst($ticket['priority']) ?> Priority
            </span>
        </div>
    </div>

    <!-- Student Information -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Student Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($ticket['student_name']) ?></p>
                    <p class="mb-1"><strong>ID:</strong> <?= htmlspecialchars($ticket['student_id']) ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($ticket['contact_email']) ?></p>
                    <p class="mb-1"><strong>Issue Type:</strong> 
                        <span class="badge badge-<?= 
                            $ticket['issue_type'] === 'technical' ? 'info' : 
                            ($ticket['issue_type'] === 'voting' ? 'primary' : 
                            ($ticket['issue_type'] === 'candidate' ? 'success' : 'secondary'))
                        ?>">
                            <?= ucfirst($ticket['issue_type']) ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Issue Description -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Issue Description</h5>
        </div>
        <div class="card-body">
            <p class="mb-0"><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
        </div>
    </div>

    <!-- Responses -->
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Responses</h5>
            <?php if ($ticket['status'] !== 'closed'): ?>
            <button type="button" class="btn btn-primary btn-sm" onclick="showResponseForm()">
                <i class="fas fa-reply"></i> Add Response
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($responses)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">No responses yet</p>
                </div>
            <?php else: ?>
                <div class="responses">
                    <?php foreach ($responses as $response): ?>
                        <div class="response mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong><?= $response['responder_name'] ?></strong>
                                    <small class="text-muted ml-2">
                                        <?= date('M j, Y, g:i a', strtotime($response['created_at'])) ?>
                                    </small>
                                </div>
                                <?php if ($response['is_solution']): ?>
                                    <span class="badge badge-success">Solution</span>
                                <?php endif; ?>
                            </div>
                            <div class="response-content">
                                <?= nl2br(htmlspecialchars($response['message'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Response Form (Hidden by default) -->
    <div id="responseForm" class="card mt-4" style="display: none;">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Add Response</h5>
        </div>
        <div class="card-body">
            <form id="addResponseForm" onsubmit="submitResponse(event)">
                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                <div class="form-group">
                    <label for="message">Your Response</label>
                    <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="is_solution" name="is_solution">
                        <label class="custom-control-label" for="is_solution">Mark as solution</label>
                    </div>
                </div>
                <div class="text-right">
                    <button type="button" class="btn btn-secondary" onclick="hideResponseForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitResponseBtn">
                        <span id="submitBtnText">Submit Response</span>
                        <span id="submitBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.response {
    border-left: 4px solid #e3e6f0;
    padding-left: 1rem;
}
.response-content {
    background-color: #f8f9fc;
    padding: 1rem;
    border-radius: 0.35rem;
}
</style>

<script>
function showResponseForm() {
    $('#responseForm').slideDown();
}

function hideResponseForm() {
    $('#responseForm').slideUp();
}

function submitResponse(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    // Disable submit button and show spinner
    const submitBtn = document.getElementById('submitResponseBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnSpinner = document.getElementById('submitBtnSpinner');
    
    submitBtn.disabled = true;
    submitBtnText.textContent = 'Submitting...';
    submitBtnSpinner.classList.remove('d-none');

    $.ajax({
        url: 'add_ticket_response.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            // Parse the response if it's a string
            if (typeof response === 'string') {
                try {
                    response = JSON.parse(response);
                } catch (e) {
                    console.error('Error parsing response:', e);
                    alert('Error adding response: Invalid server response');
                    resetSubmitButton();
                    return;
                }
            }
            
            if (response.success) {
                // Show success message
                alert('You have successfully added a response');
                // Then reload the page
                location.reload();
            } else {
                alert('Error adding response: ' + (response.message || 'Unknown error'));
                resetSubmitButton();
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            alert('Error adding response. Please try again.');
            resetSubmitButton();
        }
    });
    
    function resetSubmitButton() {
        submitBtn.disabled = false;
        submitBtnText.textContent = 'Submit Response';
        submitBtnSpinner.classList.add('d-none');
    }
}
</script>