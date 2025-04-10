<?php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = $_POST['event_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $event_type = $_POST['event_type'];

    // Validate end date is not earlier than start date
    if (strtotime($end_date) < strtotime($start_date)) {
        echo "<script>
            alert('End date cannot be earlier than the start date.');
            window.location.href = 'schedule_elections.php';
        </script>";
        exit();
    }

    // Validate event type uniqueness
    $sql = "SELECT * FROM election_schedule WHERE event_type = :event_type";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['event_type' => $event_type]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<script>
            alert('Event type already exists. Please edit the existing schedule.');
            window.location.href = 'schedule_elections.php';
        </script>";
        exit();
    }

    // Fetch existing schedules for validation
    $sql = "SELECT * FROM election_schedule";
    $stmt = $conn->query($sql);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $isValid = true;
    $errorMessage = '';

    // Validate Delegate Voting
    if ($event_type == 'delegate_voting') {
        // Check if Candidate Applications schedule exists
        $candidate_applications = array_filter($schedules, function($schedule) {
            return $schedule['event_type'] == 'candidate_application';
        });
        if (empty($candidate_applications)) {
            $isValid = false;
            $errorMessage = 'Delegate Voting cannot be scheduled before Candidate Applications are scheduled.';
        } else {
            $candidate_end_date = strtotime($candidate_applications[0]['end_date']);
            if (strtotime($start_date) < $candidate_end_date) {
                $isValid = false;
                $errorMessage = 'Delegate Voting cannot start before Candidate Applications end.';
            }
        }

        // Validate duration (not more than one day)
        $duration = strtotime($end_date) - strtotime($start_date);
        if ($duration > 86400) { // 86400 seconds = 1 day
            $isValid = false;
            $errorMessage = 'Delegate Voting duration cannot be more than one day.';
        }
    }

    // Validate Student Council Voting
    if ($event_type == 'student_council_voting') {
        // Check if Delegate Voting schedule exists
        $delegate_voting = array_filter($schedules, function($schedule) {
            return $schedule['event_type'] == 'delegate_voting';
        });
        if (empty($delegate_voting)) {
            $isValid = false;
            $errorMessage = 'Student Council Voting cannot be scheduled before Delegate Voting is scheduled.';
        } else {
            // Reset array keys after filtering and get the first element safely
            $delegate_voting = array_values($delegate_voting);
            // Check if delegate_voting array has elements before accessing index 0
            if (isset($delegate_voting[0]) && isset($delegate_voting[0]['end_date'])) {
                $delegate_end_date = strtotime($delegate_voting[0]['end_date']);
                if (strtotime($start_date) < $delegate_end_date) {
                    $isValid = false;
                    $errorMessage = 'Student Council Voting cannot start before Delegate Voting ends.';
                }
            } else {
                $isValid = false;
                $errorMessage = 'Error accessing delegate voting schedule data. Please try again.';
            }
        }

        // Validate duration (not more than one day)
        $duration = strtotime($end_date) - strtotime($start_date);
        if ($duration > 86400) { // 86400 seconds = 1 day
            $isValid = false;
            $errorMessage = 'Student Council Voting duration cannot be more than one day.';
        }
    }

    // Insert schedule if valid
    if ($isValid) {
        $sql = "INSERT INTO election_schedule (event_name, start_date, end_date, event_type) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$event_name, $start_date, $end_date, $event_type]);
        echo "<script>alert('Schedule added successfully');</script>";
    } else {
        echo "<script>
            alert('$errorMessage');
            window.location.href = 'schedule_elections.php';
        </script>";
    }
}

include 'includes/new_layout.php';
?>

<!-- Page Content -->
<div class="container-fluid">
        <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Schedule New Event</h5>
                    <div class="header-actions">
                        <span class="icon icon-calendar"></span>
                    </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="schedule_elections.php">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="event_name" class="form-label">Event Name</label>
                                <input type="text" class="form-control" id="event_name" name="event_name" required>
                            </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="event_type" class="form-label">Event Type</label>
                                <select class="form-control" id="event_type" name="event_type" required>
                                    <option value="candidate_application">Candidate Applications</option>
                                    <option value="delegate_voting">Delegate Voting</option>
                                    <option value="student_council_voting">Student Council Voting</option>
                                </select>
                            </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <span class="icon icon-plus"></span> Add Schedule
                        </button>
                        </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Current Schedules</h5>
                    <div class="header-actions">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search schedules...">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <span class="icon icon-search"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="schedulesTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Event Name</th>
                                    <th>Event Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM election_schedule ORDER BY start_date ASC";
                                $result = $conn->query($sql);
                                $current_time = time();
                                
                                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                                    $start_time = strtotime($row['start_date']);
                                    $end_time = strtotime($row['end_date']);
                                    
                                    // Determine status
                                    $status = '';
                                    $status_class = '';
                                    if ($current_time < $start_time) {
                                        $status = 'Upcoming';
                                        $status_class = 'bg-warning';
                                    } elseif ($current_time > $end_time) {
                                        $status = 'Completed';
                                        $status_class = 'bg-secondary';
                                    } else {
                                        $status = 'Ongoing';
                                        $status_class = 'bg-success';
                                    }
                                    
                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['event_name']) . "</td>
                                        <td>
                                            <span class='badge bg-info'>" . 
                                                ucwords(str_replace('_', ' ', $row['event_type'])) . 
                                            "</span>
                                        </td>
                                        <td>" . date('M j, Y g:i A', strtotime($row['start_date'])) . "</td>
                                        <td>" . date('M j, Y g:i A', strtotime($row['end_date'])) . "</td>
                                        <td><span class='badge {$status_class}'>{$status}</span></td>
                                        <td>
                                            <a href='edit_schedule.php?id={$row['schedule_id']}' 
                                               class='btn btn-sm btn-warning'>
                                                <span class='icon'>✏️</span> Edit
                                            </a>
                                            <a href='delete_schedule.php?id={$row['schedule_id']}' 
                                               class='btn btn-sm btn-danger'
                                               onclick='return confirm(\"Are you sure you want to delete this schedule?\")'>
                                                <span class='icon'>🗑️</span> Delete
                                            </a>
                                        </td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control {
        border-radius: 10px;
    }
    
    .btn {
        border-radius: 10px;
        padding: 0.5rem 1rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.75rem;
        margin-right: 0.25rem;
    }
    
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .card-header {
        border-bottom: 1px solid #eee;
        padding: 1rem;
    }
    
    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .badge {
        padding: 0.5rem 0.75rem;
        font-weight: 500;
    }
    
    .input-group {
        width: 300px;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('schedulesTable');
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

    // Date validation
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const eventType = document.getElementById('event_type');

    function validateDates() {
        if (startDate.value && endDate.value) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
            
            if (end < start) {
                alert('End date cannot be earlier than the start date.');
                endDate.value = '';
                return false;
            }

            // Validate duration for voting events
            if (eventType.value.includes('voting')) {
                const duration = end - start;
                const maxDuration = 24 * 60 * 60 * 1000; // 24 hours in milliseconds
                
                if (duration > maxDuration) {
                    alert('Voting events cannot be longer than 24 hours.');
                    endDate.value = '';
                    return false;
                }
            }
        }
        return true;
    }

    startDate.addEventListener('change', validateDates);
    endDate.addEventListener('change', validateDates);
    eventType.addEventListener('change', validateDates);
});
</script>

<?php include 'includes/new_footer.php'; ?>