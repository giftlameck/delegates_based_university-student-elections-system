<?php
include '../includes/db_connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('student_portal');
    session_start();
}

if (!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}

// Get ticket ID from URL
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch ticket details
$stmt = $conn->prepare("
    SELECT t.*, s.full_name as student_name 
    FROM election_support_tickets t
    JOIN students s ON t.student_id = s.id
    WHERE t.id = ? AND t.student_id = ?
");
$stmt->execute([$ticket_id, $_SESSION['student_id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    echo '<div class="alert alert-danger">Ticket not found or access denied.</div>';
    exit;
}

// Fetch ticket responses
$stmt = $conn->prepare("
    SELECT r.*, a.full_name as admin_name
    FROM election_support_responses r
    LEFT JOIN admins a ON r.admin_id = a.id
    WHERE r.ticket_id = ?
    ORDER BY r.created_at ASC
");
$stmt->execute([$ticket_id]);
$responses = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Support Ticket #<?php echo $ticket['id']; ?></h5>
                    <a href="javascript:void(0)" onclick="loadSection('support')" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Support
                    </a>
                </div>
                <div class="card-body">
                    <!-- Ticket Details -->
                    <div class="ticket-details mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Issue Type:</strong> <?php echo ucfirst($ticket['issue_type']); ?></p>
                                <p><strong>Priority:</strong> 
                                    <span class="badge badge-<?php 
                                        echo $ticket['priority'] === 'high' ? 'danger' : 
                                            ($ticket['priority'] === 'medium' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst($ticket['priority']); ?>
                                    </span>
                                </p>
                                <p><strong>Status:</strong> 
                                    <span class="badge badge-<?php 
                                        echo $ticket['status'] === 'open' ? 'success' : 
                                            ($ticket['status'] === 'in_progress' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst($ticket['status']); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Submitted:</strong> <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></p>
                                <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($ticket['updated_at'])); ?></p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Description:</h6>
                                <div class="ticket-description p-3 bg-light rounded">
                                    <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Responses -->
                    <div class="responses-section">
                        <h5 class="mb-4">Responses</h5>
                        <?php if (empty($responses)): ?>
                            <div class="alert alert-info">No responses yet. Our support team will get back to you soon.</div>
                        <?php else: ?>
                            <div class="responses-timeline">
                                <?php foreach($responses as $response): ?>
                                    <div class="response-item mb-4">
                                        <div class="response-header d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong><?php echo htmlspecialchars($response['admin_name'] ?? 'Support Team'); ?></strong>
                                                <span class="text-muted ml-2">
                                                    <?php echo date('M d, Y H:i', strtotime($response['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="response-content p-3 bg-light rounded">
                                            <?php echo nl2br(htmlspecialchars($response['message'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ticket-description {
    white-space: pre-wrap;
    word-wrap: break-word;
}

.response-content {
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

.badge {
    padding: 5px 10px;
}
</style>