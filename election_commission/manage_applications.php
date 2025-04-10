<?php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

// Fetch all applications
$sql = "SELECT * FROM applications ORDER BY created_at DESC";
$stmt = $conn->query($sql);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/new_layout.php';
?>

<!-- Page Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Manage Applications</h5>
                    <div class="header-actions">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search applications...">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <span class="icon icon-file"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="applicationsTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Student Name</th>
                                    <th>Candidate Type</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                    <th>Date Applied</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $application): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($application['student_name']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $application['candidate_type'] === 'Student Council' ? 'bg-primary' : 'bg-success'; ?>">
                                                <?php echo htmlspecialchars($application['candidate_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($application['position'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                switch($application['status']) {
                                                    case 'Pending':
                                                        echo 'bg-warning';
                                                        break;
                                                    case 'Approved':
                                                        echo 'bg-success';
                                                        break;
                                                    case 'Rejected':
                                                        echo 'bg-danger';
                                                        break;
                                                    default:
                                                        echo 'bg-secondary';
                                                }
                                            ?>">
                                                <?php echo htmlspecialchars($application['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($application['created_at'])); ?></td>
                                        <td>
                                            <a href="review_application.php?id=<?php echo $application['application_id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <span class="icon icon-file"></span> Review
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .badge {
        padding: 6px 12px;
        font-weight: 500;
        font-size: 12px;
    }
    
    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .btn-sm {
        padding: 0.25rem 0.75rem;
    }
    
    .input-group {
        width: 300px;
    }
    
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .card-header {
        border-bottom: 1px solid #eee;
        padding: 1rem;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('applicationsTable');
    const rows = table.getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function(e) {
        const searchText = e.target.value.toLowerCase();

        Array.from(rows).forEach(function(row) {
            if(row.getElementsByTagName('td').length > 0) {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            }
        });
    });
});
</script>

<?php include 'includes/new_footer.php'; ?>