<?php
// ticket_reports.php
session_name('election_commission');
session_start();
include 'includes/db_connection.php';
include 'includes/new_layout.php';

// Get ticket statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) FROM election_support_tickets")->fetchColumn(),
    'open' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE status = 'open'")->fetchColumn(),
    'in_progress' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE status = 'in_progress'")->fetchColumn(),
    'closed' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE status = 'closed'")->fetchColumn(),
    
    'high' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE priority = 'high'")->fetchColumn(),
    'medium' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE priority = 'medium'")->fetchColumn(),
    'low' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE priority = 'low'")->fetchColumn(),
    
    'technical' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE issue_type = 'technical'")->fetchColumn(),
    'voting' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE issue_type = 'voting'")->fetchColumn(),
    'candidate' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE issue_type = 'candidate'")->fetchColumn(),
    'other' => $conn->query("SELECT COUNT(*) FROM election_support_tickets WHERE issue_type = 'other'")->fetchColumn(),
];

// Get monthly data for charts
$monthly_data = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
    FROM election_support_tickets
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Support Ticket Reports</h1>

    <div class="row">
        <!-- Summary Cards -->
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
                                Technical Issues</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['technical'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-laptop-code fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Ticket Trends</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="ticketsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ticket Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="ticketPieChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-primary"></i> Open
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-warning"></i> In Progress
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-secondary"></i> Closed
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Issue Type Breakdown -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Issue Types</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="issueTypeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Priority Levels</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="priorityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="../assets/js/chart.min.js"></script>

<script>
// Monthly Tickets Chart
var ctx = document.getElementById('ticketsChart').getContext('2d');
var chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?php foreach ($monthly_data as $data) echo "'" . date('M Y', strtotime($data['month'] . '-01')) . "',"; ?>],
        datasets: [
            {
                label: 'Open',
                data: [<?php foreach ($monthly_data as $data) echo $data['open'] . ','; ?>],
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 2
            },
            {
                label: 'In Progress',
                data: [<?php foreach ($monthly_data as $data) echo $data['in_progress'] . ','; ?>],
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                borderColor: 'rgba(255, 193, 7, 1)',
                borderWidth: 2
            },
            {
                label: 'Closed',
                data: [<?php foreach ($monthly_data as $data) echo $data['closed'] . ','; ?>],
                backgroundColor: 'rgba(108, 117, 125, 0.1)',
                borderColor: 'rgba(108, 117, 125, 1)',
                borderWidth: 2
            }
        ]
    },
    options: {
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Ticket Status Pie Chart
var ctx2 = document.getElementById('ticketPieChart').getContext('2d');
var pieChart = new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Open', 'In Progress', 'Closed'],
        datasets: [{
            data: [<?= $stats['open'] ?>, <?= $stats['in_progress'] ?>, <?= $stats['closed'] ?>],
            backgroundColor: ['#28a745', '#ffc107', '#6c757d'],
            hoverBackgroundColor: ['#218838', '#e0a800', '#5a6268'],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
    },
    options: {
        maintainAspectRatio: false,
        cutout: '70%',
    },
});

// Issue Type Chart
var ctx3 = document.getElementById('issueTypeChart').getContext('2d');
var typeChart = new Chart(ctx3, {
    type: 'bar',
    data: {
        labels: ['Technical', 'Voting', 'Candidate', 'Other'],
        datasets: [{
            label: 'Tickets',
            data: [<?= $stats['technical'] ?>, <?= $stats['voting'] ?>, <?= $stats['candidate'] ?>, <?= $stats['other'] ?>],
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
        }]
    },
    options: {
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Priority Chart
var ctx4 = document.getElementById('priorityChart').getContext('2d');
var priorityChart = new Chart(ctx4, {
    type: 'bar',
    data: {
        labels: ['High', 'Medium', 'Low'],
        datasets: [{
            label: 'Tickets',
            data: [<?= $stats['high'] ?>, <?= $stats['medium'] ?>, <?= $stats['low'] ?>],
            backgroundColor: ['#e74a3b', '#f6c23e', '#858796'],
        }]
    },
    options: {
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php include 'includes/new_footer.php'; ?>