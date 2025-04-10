<?php
// Election_commission/dashboard.php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

// Fetch quick statistics
$stats = array();

// Applications
$stmt = $conn->query("SELECT COUNT(*) as total FROM applications");
$stats['applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as pending FROM applications WHERE status = 'Pending'");
$stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];

// Delegates
$stmt = $conn->query("SELECT COUNT(*) as delegates FROM delegate_winners");
$stats['delegates'] = $stmt->fetch(PDO::FETCH_ASSOC)['delegates'];

// Elections
$stmt = $conn->query("SELECT COUNT(*) as scheduled FROM election_schedule WHERE end_date >= CURRENT_DATE");
$stats['scheduled'] = $stmt->fetch(PDO::FETCH_ASSOC)['scheduled'];

// Feedback
$stmt = $conn->query("SELECT COUNT(*) as feedback FROM election_feedback");
$stats['feedback'] = $stmt->fetch(PDO::FETCH_ASSOC)['feedback'];

$stmt = $conn->query("SELECT AVG(rating) as avg_rating FROM election_feedback");
$stats['avg_rating'] = number_format($stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'], 1);

// Support Tickets
$stmt = $conn->query("SELECT COUNT(*) as open_tickets FROM election_support_tickets WHERE status = 'open'");
$stats['open_tickets'] = $stmt->fetch(PDO::FETCH_ASSOC)['open_tickets'];

include 'includes/new_layout.php';
?>

<!-- Dashboard Content -->
<div class="container-fluid">
    <!-- Quick Stats -->
    <div class="row mb-4">
        <!-- Applications Card -->
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="stats-card">
                <div class="stats-icon" style="background: rgba(0, 123, 255, 0.1); color: #007bff;">
                    <span class="icon icon-file"></span>
                </div>
                <h5>Applications</h5>
                <h3 class="mb-0"><?php echo $stats['applications']; ?></h3>
                <small class="text-muted"><?php echo $stats['pending']; ?> pending</small>
            </div>
        </div>
        
        <!-- Delegates Card -->
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="stats-card">
                <div class="stats-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                    <span class="icon icon-users"></span>
                </div>
                <h5>Delegates</h5>
                <h3 class="mb-0"><?php echo $stats['delegates']; ?></h3>
                <small class="text-muted">Elected</small>
            </div>
        </div>
        
        <!-- Elections Card -->
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="stats-card">
                <div class="stats-icon" style="background: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                    <span class="icon icon-calendar"></span>
                </div>
                <h5>Elections</h5>
                <h3 class="mb-0"><?php echo $stats['scheduled']; ?></h3>
                <small class="text-muted">Upcoming</small>
            </div>
        </div>
        
        <!-- Feedback Card -->
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="stats-card">
                <div class="stats-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                    <span class="icon icon-message-square"></span>
                </div>
                <h5>Feedback</h5>
                <h3 class="mb-0"><?php echo $stats['feedback']; ?></h3>
                <small class="text-muted">Avg: <?php echo $stats['avg_rating']; ?>/5</small>
            </div>
        </div>
        
        <!-- Tickets Card -->
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="stats-card">
                <div class="stats-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                    <span class="icon icon-headphones"></span>
                </div>
                <h5>Tickets</h5>
                <h3 class="mb-0"><?php echo $stats['open_tickets']; ?></h3>
                <small class="text-muted">Open</small>
            </div>
        </div>
        
        <!-- Quick Report Card -->
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="stats-card clickable" onclick="window.location.href='ticket_reports.php'">
                <div class="stats-icon" style="background: rgba(23, 162, 184, 0.1); color: #17a2b8;">
                    <span class="icon icon-bar-chart"></span>
                </div>
                <h5>Reports</h5>
                <h3 class="mb-0"><i class="fas fa-arrow-right"></i></h3>
                <small class="text-muted">View analytics</small>
            </div>
        </div>
    </div>

    <!-- Quick Actions and Recent Activities -->
    <div class="row">
        <!-- Quick Actions -->
        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="manage_applications.php" class="quick-action-btn">
                                <span class="icon icon-file"></span>
                                Review Applications
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="schedule_elections.php" class="quick-action-btn">
                                <span class="icon icon-calendar"></span>
                                Schedule Election
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="manage_guidelines.php" class="quick-action-btn">
                                <span class="icon icon-book"></span>
                                Update Guidelines
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="manage_feedback.php" class="quick-action-btn">
                                <span class="icon icon-message-square"></span>
                                View Feedback
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="manage_tickets.php" class="quick-action-btn">
                                <span class="icon icon-headphones"></span>
                                Support Tickets
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="certificates.php" class="quick-action-btn">
                                <span class="icon icon-certificate"></span>
                                Generate Certificates
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Recent Activities</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php
                        // Fetch recent activities from multiple sources
                        $activities = [];
                        
                        // Recent Applications
                        $stmt = $conn->query("
                            SELECT 
                                'application' as type,
                                student_name as title,
                                CONCAT('Application ', status) as description,
                                created_at as activity_date,
                                'icon-file' as icon,
                                CASE 
                                    WHEN status = 'Pending' THEN 'warning'
                                    WHEN status = 'Approved' THEN 'success'
                                    WHEN status = 'Rejected' THEN 'danger'
                                END as status_color
                            FROM applications
                            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        ");
                        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $activities = array_merge($activities, $applications);

                        // Recent Election Schedules
                        $stmt = $conn->query("
                            SELECT 
                                'schedule' as type,
                                event_type as title,
                                CONCAT('Election scheduled for ', DATE_FORMAT(start_date, '%M %d, %Y')) as description,
                                created_at as activity_date,
                                'icon-calendar' as icon,
                                'primary' as status_color
                            FROM election_schedule
                            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        ");
                        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $activities = array_merge($activities, $schedules);

                        // Recent Guidelines
                        $stmt = $conn->query("
                            SELECT 
                                'guideline' as type,
                                title,
                                CONCAT('New ', type, ' guideline added') as description,
                                created_at as activity_date,
                                'icon-book' as icon,
                                'info' as status_color
                            FROM election_guidelines
                            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        ");
                        $guidelines = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $activities = array_merge($activities, $guidelines);

                        // Recent Form Uploads
                        $stmt = $conn->query("
                            SELECT 
                                'form' as type,
                                form_name as title,
                                'New form uploaded' as description,
                                created_at as activity_date,
                                'icon-upload' as icon,
                                'success' as status_color
                            FROM application_forms
                            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        ");
                        $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $activities = array_merge($activities, $forms);

                        // Recent Feedback
                        $stmt = $conn->query("
                            SELECT 
                                'feedback' as type,
                                CONCAT('Rating: ', rating) as title,
                                CONCAT('Feedback on ', feedback_type) as description,
                                created_at as activity_date,
                                'icon-message-square' as icon,
                                CASE 
                                    WHEN rating >= 4 THEN 'success'
                                    WHEN rating >= 2 THEN 'warning'
                                    ELSE 'danger'
                                END as status_color
                            FROM election_feedback
                            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        ");
                        $feedback_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $activities = array_merge($activities, $feedback_activities);

                        // Recent Support Tickets
                        $stmt = $conn->query("
                            SELECT 
                                'ticket' as type,
                                CONCAT('Ticket #', id) as title,
                                CONCAT(issue_type, ' ticket') as description,
                                created_at as activity_date,
                                'icon-headphones' as icon,
                                CASE 
                                    WHEN status = 'open' THEN 'danger'
                                    WHEN status = 'in_progress' THEN 'warning'
                                    ELSE 'secondary'
                                END as status_color
                            FROM election_support_tickets
                            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        ");
                        $ticket_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $activities = array_merge($activities, $ticket_activities);

                        // Sort all activities by date
                        usort($activities, function($a, $b) {
                            return strtotime($b['activity_date']) - strtotime($a['activity_date']);
                        });

                        // Display only the 10 most recent activities
                        $activities = array_slice($activities, 0, 10);
                        
                        if (empty($activities)): ?>
                            <div class="text-center text-muted py-3">
                                <span class="icon icon-info"></span>
                                No recent activities found
                            </div>
                        <?php else:
                            foreach ($activities as $activity): ?>
                                <div class="timeline-item">
                                    <div class="timeline-icon bg-<?php echo $activity['status_color']; ?>-subtle">
                                        <span class="icon <?php echo $activity['icon']; ?> text-<?php echo $activity['status_color']; ?>"></span>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                        <p class="mb-0">
                                            <?php echo htmlspecialchars($activity['description']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y g:i A', strtotime($activity['activity_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach;
                        endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Summary -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Recent Feedback</h5>
                </div>
                <div class="card-body">
                    <?php
                    $recent_feedback = $conn->query("
                        SELECT f.*, s.student_name 
                        FROM election_feedback f
                        LEFT JOIN student_details s ON f.student_id = s.Student_id
                        ORDER BY created_at DESC LIMIT 5
                    ")->fetchAll();
                    
                    if (empty($recent_feedback)): ?>
                        <div class="alert alert-info">No recent feedback found.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recent_feedback as $feedback): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($feedback['student_name'] ?? $feedback['student_id']) ?></h6>
                                        <small class="text-muted">
                                            <?= ucfirst(str_replace('_', ' ', $feedback['feedback_type'])) ?>
                                        </small>
                                    </div>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?= $i > $feedback['rating'] ? '-o' : '' ?> text-warning"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="mb-1 mt-2">
                                    <?= substr(htmlspecialchars($feedback['comments']), 0, 100) ?>
                                    <?= strlen($feedback['comments']) > 100 ? '...' : '' ?>
                                </p>
                                <small class="text-muted">
                                    <?= date('M j, Y', strtotime($feedback['created_at'])) ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-right mt-3">
                            <a href="manage_feedback.php" class="btn btn-sm btn-primary">View All Feedback</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Tickets -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Recent Support Tickets</h5>
                </div>
                <div class="card-body">
                    <?php
                    $recent_tickets = $conn->query("
                        SELECT * FROM election_support_tickets 
                        ORDER BY 
                            CASE priority 
                                WHEN 'high' THEN 1 
                                WHEN 'medium' THEN 2 
                                WHEN 'low' THEN 3 
                            END, 
                            created_at DESC 
                        LIMIT 5
                    ")->fetchAll();
                    
                    if (empty($recent_tickets)): ?>
                        <div class="alert alert-info">No recent tickets found.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recent_tickets as $ticket): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">#<?= $ticket['id'] ?> - <?= ucfirst($ticket['issue_type']) ?></h6>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($ticket['student_name'] ?? $ticket['student_id']) ?>
                                        </small>
                                    </div>
                                    <span class="badge badge-<?= 
                                        $ticket['priority'] === 'high' ? 'danger' : 
                                        ($ticket['priority'] === 'medium' ? 'warning' : 'secondary')
                                    ?>">
                                        <?= ucfirst($ticket['priority']) ?>
                                    </span>
                                </div>
                                <p class="mb-1 mt-2">
                                    <?= substr(htmlspecialchars($ticket['description']), 0, 100) ?>
                                    <?= strlen($ticket['description']) > 100 ? '...' : '' ?>
                                </p>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="text-muted">
                                        <?= date('M j, Y', strtotime($ticket['created_at'])) ?>
                                    </small>
                                    <span class="badge badge-<?= 
                                        $ticket['status'] === 'open' ? 'success' : 
                                        ($ticket['status'] === 'in_progress' ? 'warning' : 'secondary')
                                    ?>">
                                        <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-right mt-3">
                            <a href="manage_tickets.php" class="btn btn-sm btn-primary">View All Tickets</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-card {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    height: 100%;
}

.stats-card.clickable {
    cursor: pointer;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.stats-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
}

.quick-action-btn {
    display: block;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
    color: #333;
    transition: all 0.3s ease;
    height: 100%;
}

.quick-action-btn:hover {
    background: #e9ecef;
    text-decoration: none;
    transform: translateY(-3px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.quick-action-btn .icon {
    display: block;
    font-size: 24px;
    margin-bottom: 8px;
    color: #007bff;
}

.timeline {
    position: relative;
    padding: 20px 0;
    max-height: 600px;
    overflow-y: auto;
}

.timeline-item {
    position: relative;
    padding-left: 45px;
    margin-bottom: 20px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-icon {
    position: absolute;
    left: 0;
    top: 0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: transform 0.2s ease;
}

.timeline-content:hover {
    transform: translateX(5px);
}

.rating-stars {
    color: #ffc107;
    font-size: 16px;
}

.list-group-item {
    transition: all 0.2s ease;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}
</style>

<?php include 'includes/new_footer.php'; ?>