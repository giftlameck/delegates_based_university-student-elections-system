<?php
// manage_feedback.php
session_name('election_commission');
session_start();
include 'includes/db_connection.php';
include 'includes/new_layout.php';

// Check for export message
if (isset($_SESSION['export_message'])) {
    echo '<div class="alert alert-info alert-dismissible fade show" role="alert">
            ' . $_SESSION['export_message'] . '
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
          </div>';
    unset($_SESSION['export_message']);
}

// Filtering logic
$filter_type = $_GET['type'] ?? '';
$where = '';
if ($filter_type) {
    $where = " WHERE feedback_type = " . $conn->quote($filter_type);
}

// Pagination
$per_page = 10;
$page = max(1, $_GET['page'] ?? 1);
$offset = ($page - 1) * $per_page;

// Get feedback
$sql = "SELECT * FROM election_feedback $where ORDER BY created_at DESC LIMIT $offset, $per_page";
$feedback = $conn->query($sql)->fetchAll();

// Count total for pagination
$total = $conn->query("SELECT COUNT(*) FROM election_feedback $where")->fetchColumn();
$total_pages = ceil($total / $per_page);

// Get feedback statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) FROM election_feedback")->fetchColumn(),
    'avg_rating' => number_format($conn->query("SELECT AVG(rating) FROM election_feedback")->fetchColumn(), 1),
    'voting_experience' => $conn->query("SELECT COUNT(*) FROM election_feedback WHERE feedback_type = 'voting_experience'")->fetchColumn(),
    'system_usability' => $conn->query("SELECT COUNT(*) FROM election_feedback WHERE feedback_type = 'system_usability'")->fetchColumn(),
    'candidate_info' => $conn->query("SELECT COUNT(*) FROM election_feedback WHERE feedback_type = 'candidate_info'")->fetchColumn(),
    'election_process' => $conn->query("SELECT COUNT(*) FROM election_feedback WHERE feedback_type = 'election_process'")->fetchColumn(),
];
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Student Feedback</h1>
        <div>
            <div class="dropdown d-inline-block">
                <button class="btn btn-light dropdown-toggle" data-toggle="dropdown">
                    Filter by Type
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="?">All Types</a>
                    <a class="dropdown-item" href="?type=voting_experience">Voting Experience</a>
                    <a class="dropdown-item" href="?type=system_usability">System Usability</a>
                    <a class="dropdown-item" href="?type=candidate_info">Candidate Information</a>
                    <a class="dropdown-item" href="?type=election_process">Election Process</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Feedback</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Average Rating</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['avg_rating'] ?>/5</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Voting Experience</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['voting_experience'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-vote-yea fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                System Usability</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['system_usability'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-laptop fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Feedback List</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Export Options:</div>
                    <a class="dropdown-item" href="export_feedback.php?format=csv">Export as CSV</a>
                    <a class="dropdown-item" href="export_feedback.php?format=pdf">Export as PDF</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($feedback)): ?>
                <div class="alert alert-info">No feedback found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student ID</th>
                                <th>Rating</th>
                                <th>Type</th>
                                <th>Comments</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedback as $item): ?>
                            <tr>
                                <td>#<?= $item['id'] ?></td>
                                <td><?= htmlspecialchars($item['student_id']) ?></td>
                                <td>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?= $i > $item['rating'] ? '-o' : '' ?> text-warning"></i>
                                        <?php endfor; ?>
                                        <span class="ml-2 font-weight-bold"><?= $item['rating'] ?>/5</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?= 
                                        $item['feedback_type'] === 'voting_experience' ? 'primary' : 
                                        ($item['feedback_type'] === 'system_usability' ? 'info' : 
                                        ($item['feedback_type'] === 'candidate_info' ? 'success' : 'warning'))
                                    ?>">
                                        <?= ucfirst(str_replace('_', ' ', $item['feedback_type'])) ?>
                                    </span>
                                </td>
                                <td><?= substr(htmlspecialchars($item['comments']), 0, 50) . '...' ?></td>
                                <td><?= date('M j, Y', strtotime($item['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#feedbackModal<?= $item['id'] ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>

                            <!-- Feedback Modal -->
                            <div class="modal fade" id="feedbackModal<?= $item['id'] ?>">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">Feedback Details #<?= $item['id'] ?></h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <h6 class="font-weight-bold">Student ID:</h6>
                                                    <p><?= htmlspecialchars($item['student_id']) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="font-weight-bold">Rating:</h6>
                                                    <div class="rating-stars">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star<?= $i > $item['rating'] ? '-o' : '' ?> text-warning"></i>
                                                        <?php endfor; ?>
                                                        <span class="ml-2 font-weight-bold"><?= $item['rating'] ?>/5</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-4">
                                                <h6 class="font-weight-bold">Feedback Type:</h6>
                                                <p>
                                                    <span class="badge badge-<?= 
                                                        $item['feedback_type'] === 'voting_experience' ? 'primary' : 
                                                        ($item['feedback_type'] === 'system_usability' ? 'info' : 
                                                        ($item['feedback_type'] === 'candidate_info' ? 'success' : 'warning'))
                                                    ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $item['feedback_type'])) ?>
                                                    </span>
                                                </p>
                                            </div>
                                            
                                            <div class="mb-4">
                                                <h6 class="font-weight-bold">Comments:</h6>
                                                <div class="p-3 bg-light rounded">
                                                    <?= nl2br(htmlspecialchars($item['comments'])) ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($item['suggestions'])): ?>
                                            <div class="mb-4">
                                                <h6 class="font-weight-bold">Suggestions:</h6>
                                                <div class="p-3 bg-light rounded">
                                                    <?= nl2br(htmlspecialchars($item['suggestions'])) ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="text-muted">
                                                <small>Submitted on <?= date('F j, Y, g:i a', strtotime($item['created_at'])) ?></small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page-1 ?><?= $filter_type ? '&type='.$filter_type : '' ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $filter_type ? '&type='.$filter_type : '' ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page+1 ?><?= $filter_type ? '&type='.$filter_type : '' ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.rating-stars {
    color: #ffc107;
    font-size: 16px;
}
.table tr {
    cursor: pointer;
}
.border-left-primary {
    border-left: 4px solid #4e73df !important;
}
.border-left-success {
    border-left: 4px solid #1cc88a !important;
}
.border-left-info {
    border-left: 4px solid #36b9cc !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}
</style>

<?php include 'includes/new_footer.php'; ?> 