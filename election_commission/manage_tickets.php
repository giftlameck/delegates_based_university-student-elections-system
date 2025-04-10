<?php
// manage_tickets.php
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
$filter_status = $_GET['status'] ?? '';
$where = '';
if ($filter_status) {
    $where = " WHERE status = " . $conn->quote($filter_status);
}

// Pagination
$per_page = 10;
$page = max(1, $_GET['page'] ?? 1);
$offset = ($page - 1) * $per_page;

// Get tickets
$sql = "SELECT * FROM election_support_tickets $where ORDER BY 
        CASE priority 
            WHEN 'high' THEN 1 
            WHEN 'medium' THEN 2 
            WHEN 'low' THEN 3 
        END, created_at DESC 
        LIMIT $offset, $per_page";
$tickets = $conn->query($sql)->fetchAll();

// Count total for pagination
$total = $conn->query("SELECT COUNT(*) FROM election_support_tickets $where")->fetchColumn();
$total_pages = ceil($total / $per_page);

// Get ticket statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) FROM election_support_tickets")->fetchColumn(),
    'open' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE status = 'open'")->fetchColumn(),
    'in_progress' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE status = 'in_progress'")->fetchColumn(),
    'closed' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE status = 'closed'")->fetchColumn(),
    'high' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE priority = 'high'")->fetchColumn(),
    'medium' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE priority = 'medium'")->fetchColumn(),
    'low' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE priority = 'low'")->fetchColumn(),
];
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Support Tickets</h1>
        <div>
            <div class="dropdown d-inline-block mr-2">
                <button class="btn btn-light dropdown-toggle" data-toggle="dropdown">
                    Filter by Status
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="?">All Tickets</a>
                    <a class="dropdown-item" href="?status=open">Open</a>
                    <a class="dropdown-item" href="?status=in_progress">In Progress</a>
                    <a class="dropdown-item" href="?status=closed">Closed</a>
                </div>
            </div>
            <a href="check_ticket_tables.php" class="btn btn-info mr-2">
                <i class="fas fa-database"></i> Check Tables
            </a>
            <a href="ticket_reports.php" class="btn btn-success mr-2">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="setup_support_system.php" class="btn btn-primary">
                <i class="fas fa-cog"></i> Setup
            </a>
        </div>
    </div>

    <!-- Ticket Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Tickets</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
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
                                Open Tickets</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['open'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-folder-open fa-2x text-gray-300"></i>
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
                                High Priority</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['high'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                                In Progress</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['in_progress'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Support Tickets</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Export Options:</div>
                    <a class="dropdown-item" href="export_tickets.php?format=csv">Export as CSV</a>
                    <a class="dropdown-item" href="export_tickets.php?format=pdf">Export as PDF</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($tickets)): ?>
                <div class="alert alert-info">No tickets found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Student</th>
                                <th>Issue Type</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td>#<?= $ticket['id'] ?></td>
                                <td>
                                    <?= htmlspecialchars($ticket['student_name']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($ticket['student_id']) ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?= 
                                        $ticket['issue_type'] === 'technical' ? 'info' : 
                                        ($ticket['issue_type'] === 'voting' ? 'primary' : 
                                        ($ticket['issue_type'] === 'candidate' ? 'success' : 'secondary'))
                                    ?>">
                                        <?= ucfirst($ticket['issue_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= 
                                        $ticket['priority'] === 'high' ? 'danger' : 
                                        ($ticket['priority'] === 'medium' ? 'warning' : 'secondary')
                                    ?>">
                                        <?= ucfirst($ticket['priority']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= 
                                        $ticket['status'] === 'open' ? 'success' : 
                                        ($ticket['status'] === 'in_progress' ? 'warning' : 'secondary')
                                    ?>">
                                        <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($ticket['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewTicket(<?= $ticket['id'] ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page-1 ?><?= $filter_status ? '&status='.$filter_status : '' ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $filter_status ? '&status='.$filter_status : '' ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page+1 ?><?= $filter_status ? '&status='.$filter_status : '' ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Ticket Modal (loaded via AJAX) -->
<div class="modal fade" id="ticketModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Ticket Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="ticketDetails">
                <!-- Content loaded via AJAX -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading ticket details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 4px solid #4e73df !important;
}
.border-left-success {
    border-left: 4px solid #1cc88a !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}
.border-left-info {
    border-left: 4px solid #36b9cc !important;
}
.badge {
    padding: 0.5em 0.75em;
    font-weight: 500;
}
</style>

<script>
function viewTicket(ticketId) {
    $('#ticketModal').modal('show');
    
    $.ajax({
        url: 'get_ticket_details.php',
        type: 'GET',
        data: { id: ticketId },
        success: function(response) {
            $('#ticketDetails').html(response);
        },
        error: function() {
            $('#ticketDetails').html('<div class="alert alert-danger">Error loading ticket details</div>');
        }
    });
}
</script>

<?php include 'includes/new_footer.php'; ?>