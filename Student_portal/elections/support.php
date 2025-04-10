<?php
include '../includes/db_connection.php';

// Fetch student's tickets with responses
$stmt = $conn->prepare("
    SELECT t.*, 
           (SELECT COUNT(*) FROM election_support_responses WHERE ticket_id = t.id) as response_count,
           (SELECT GROUP_CONCAT(
               CONCAT(r.message, '|', r.created_at, '|', 'Support Team')
               SEPARATOR ','
           )
           FROM election_support_responses r
           WHERE r.ticket_id = t.id
           ORDER BY r.created_at ASC) as responses
    FROM election_support_tickets t 
    WHERE t.student_id = ? 
    ORDER BY t.created_at DESC
");
$stmt->execute([$_SESSION['student_id']]);
$tickets = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <!-- Quick Support Section -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Quick Support</h5>
                </div>
                <div class="card-body">
                    <div class="support-item">
                        <div class="support-icon">
                            <i class="fas fa-phone-alt text-primary"></i>
                        </div>
                        <div class="support-content">
                            <strong>Emergency Support:</strong>
                            <p class="mb-0">+1 234 567 8900</p>
                        </div>
                    </div>
                    <div class="support-item">
                        <div class="support-icon">
                            <i class="fas fa-envelope text-primary"></i>
                        </div>
                        <div class="support-content">
                            <strong>Email Support:</strong>
                            <p class="mb-0">election.support@university.edu</p>
                        </div>
                    </div>
                    <div class="support-item">
                        <div class="support-icon">
                            <i class="fas fa-clock text-primary"></i>
                        </div>
                        <div class="support-content">
                            <strong>Support Hours:</strong>
                            <p class="mb-0">Monday - Friday: 9:00 AM - 5:00 PM</p>
                        </div>
                    </div>
                    <div class="support-item">
                        <div class="support-icon">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                        </div>
                        <div class="support-content">
                            <strong>Office Location:</strong>
                            <p class="mb-0">Election Commission Office, Room 101</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Common Issues -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Common Issues</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-action" data-toggle="modal" data-target="#loginIssueModal">
                            <i class="fas fa-sign-in-alt text-info"></i> Login Issues
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" data-toggle="modal" data-target="#votingIssueModal">
                            <i class="fas fa-vote-yea text-info"></i> Voting Problems
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" data-toggle="modal" data-target="#registrationIssueModal">
                            <i class="fas fa-user-plus text-info"></i> Registration Issues
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Support Ticket Form and History -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Election Support</h3>
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

                    <form method="POST" action="submit_support.php" id="supportForm" onsubmit="submitSupport(event)">
                        <div class="form-group">
                            <label>Issue Type</label>
                            <select class="form-control" name="issue_type" required>
                                <option value="">Select Issue Type</option>
                                <option value="technical">Technical Issue</option>
                                <option value="voting">Voting Problem</option>
                                <option value="candidate">Candidate Information</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Priority Level</label>
                            <select class="form-control" name="priority" required>
                                <option value="">Select Priority</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" rows="4" required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" class="form-control" name="contact_email" required>
                        </div>

                        <button type="submit" name="support_submit" class="btn btn-primary">Submit Support Request</button>
                    </form>
                </div>
            </div>

            <!-- Ticket History -->
            <?php if (!empty($tickets)): ?>
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">Your Support Tickets</h5>
                </div>
                <div class="card-body">
                    <?php foreach($tickets as $ticket): ?>
                    <div class="ticket-card mb-4">
                        <div class="ticket-header d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-0">Ticket #<?php echo $ticket['id']; ?> - <?php echo ucfirst($ticket['issue_type']); ?></h6>
                                <small class="text-muted">Submitted on <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></small>
                            </div>
                            <div>
                                <span class="badge badge-<?php 
                                    echo $ticket['priority'] === 'high' ? 'danger' : 
                                        ($ticket['priority'] === 'medium' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst($ticket['priority']); ?>
                                </span>
                                <span class="badge badge-<?php 
                                    echo $ticket['status'] === 'open' ? 'success' : 
                                        ($ticket['status'] === 'in_progress' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst($ticket['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="ticket-description p-3 bg-light rounded mb-3">
                            <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
                        </div>

                        <?php if ($ticket['responses']): ?>
                        <div class="responses-section">
                            <h6 class="mb-3">Responses (<?php echo $ticket['response_count']; ?>)</h6>
                            <div class="responses-timeline">
                                <?php 
                                $responses = explode(',', $ticket['responses']);
                                foreach($responses as $response):
                                    list($message, $created_at, $admin_name) = explode('|', $response);
                                ?>
                                <div class="response-item mb-3">
                                    <div class="response-header d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($admin_name); ?></strong>
                                            <span class="text-muted ml-2">
                                                <?php echo date('M d, Y H:i', strtotime($created_at)); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="response-content p-3 bg-light rounded">
                                        <?php echo nl2br(htmlspecialchars($message)); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            No responses yet. Our support team will get back to you soon.
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Issue Modals -->
<div class="modal fade" id="loginIssueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Login Issues</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6>Common Solutions:</h6>
                <ul>
                    <li>Clear your browser cache and cookies</li>
                    <li>Ensure you're using the correct student ID</li>
                    <li>Try resetting your password</li>
                    <li>Check your internet connection</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="votingIssueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Voting Problems</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6>Common Solutions:</h6>
                <ul>
                    <li>Ensure you're eligible to vote</li>
                    <li>Check if voting period is active</li>
                    <li>Try using a different browser</li>
                    <li>Contact support if issues persist</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="registrationIssueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registration Issues</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6>Common Solutions:</h6>
                <ul>
                    <li>Verify your student status</li>
                    <li>Check registration deadlines</li>
                    <li>Ensure all required documents are submitted</li>
                    <li>Contact the registration office</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.support-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.support-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.support-icon {
    flex: 0 0 40px;
    text-align: center;
}

.support-icon i {
    font-size: 24px;
}

.support-content {
    flex: 1;
    padding-left: 15px;
}

.support-content strong {
    display: block;
    margin-bottom: 5px;
    color: #333;
}

.support-content p {
    color: #666;
    line-height: 1.4;
}

.badge {
    padding: 5px 10px;
}

.ticket-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.ticket-description {
    white-space: pre-wrap;
    word-wrap: break-word;
}

.responses-timeline {
    position: relative;
    padding-left: 20px;
}

.responses-timeline::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.response-item {
    position: relative;
}

.response-item::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #007bff;
    border: 2px solid #fff;
}

.response-content {
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>

<script>
function submitSupport(event) {
    event.preventDefault();
    const form = document.getElementById('supportForm');
    const formData = new FormData(form);

    fetch('load_section.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            form.reset();
            // Refresh the page to show the new ticket in the history
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
    });
}
</script> 