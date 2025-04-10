<?php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

$schedule_id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $schedule_id) {
    $event_name = $_POST['event_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $event_type = $_POST['event_type'];

    // Validate end date is not earlier than start date
    if (strtotime($end_date) < strtotime($start_date)) {
        echo "<script>
            alert('End date cannot be earlier than the start date.');
            window.location.href = 'edit_schedule.php?id=$schedule_id';
        </script>";
        exit();
    }

    // Fetch existing schedules for validation
    $sql = "SELECT * FROM election_schedule WHERE schedule_id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$schedule_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $isValid = true;
    $errorMessage = '';

    // Validate Candidate Applications
    if ($event_type == 'candidate_application') {
        // Check if Delegate Voting exists and ensure Candidate Applications end before Delegate Voting starts
        $delegate_voting = array_filter($schedules, function($schedule) {
            return $schedule['event_type'] == 'delegate_voting';
        });
        if (!empty($delegate_voting)) {
            $delegate_start_date = strtotime($delegate_voting[0]['start_date']);
            if (strtotime($end_date) > $delegate_start_date) {
                $isValid = false;
                $errorMessage = 'Candidate Applications cannot end after Delegate Voting starts.';
            }
        }
    }

    // Validate Delegate Voting
    if ($event_type == 'delegate_voting') {
        // Check if Candidate Applications exists and ensure Delegate Voting starts after Candidate Applications end
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
        // Check if Delegate Voting exists and ensure Student Council Voting starts after Delegate Voting ends
        $delegate_voting = array_filter($schedules, function($schedule) {
            return $schedule['event_type'] == 'delegate_voting';
        });
        if (empty($delegate_voting)) {
            $isValid = false;
            $errorMessage = 'Student Council Voting cannot be scheduled before Delegate Voting is scheduled.';
        } else {
            $delegate_end_date = strtotime($delegate_voting[0]['end_date']);
            if (strtotime($start_date) < $delegate_end_date) {
                $isValid = false;
                $errorMessage = 'Student Council Voting cannot start before Delegate Voting ends.';
            }
        }

        // Validate duration (not more than one day)
        $duration = strtotime($end_date) - strtotime($start_date);
        if ($duration > 86400) { // 86400 seconds = 1 day
            $isValid = false;
            $errorMessage = 'Student Council Voting duration cannot be more than one day.';
        }
    }

    // Update schedule if valid
    if ($isValid) {
        $sql = "UPDATE election_schedule SET event_name = ?, start_date = ?, end_date = ?, event_type = ? WHERE schedule_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$event_name, $start_date, $end_date, $event_type, $schedule_id]);
        echo "<script>
            alert('Schedule updated successfully.');
            window.location.href = 'schedule_elections.php';
        </script>";
    } else {
        echo "<script>
            alert('$errorMessage');
            window.location.href = 'edit_schedule.php?id=$schedule_id';
        </script>";
    }
}

if ($schedule_id) {
    $sql = "SELECT * FROM election_schedule WHERE schedule_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$schedule_id]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
}

include 'includes/new_layout.php';
?>

<!-- Page Content -->
<div class="container-fluid">
        <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Edit Schedule</h5>
                    <div class="header-actions">
                        <span class="icon icon-calendar"></span>
                    </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="edit_schedule.php?id=<?php echo $schedule_id; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="event_name" class="form-label">Event Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <span class="icon icon-tag"></span>
                                        </span>
                                        <input type="text" 
                                               class="form-control" 
                                               id="event_name" 
                                               name="event_name" 
                                               value="<?php echo htmlspecialchars($schedule['event_name']); ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="event_type" class="form-label">Event Type</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <span class="icon icon-list"></span>
                                        </span>
                                        <select class="form-select" id="event_type" name="event_type" required>
                                            <option value="candidate_application" 
                                                    <?php echo $schedule['event_type'] == 'candidate_application' ? 'selected' : ''; ?>>
                                                Candidate Applications
                                            </option>
                                            <option value="delegate_voting" 
                                                    <?php echo $schedule['event_type'] == 'delegate_voting' ? 'selected' : ''; ?>>
                                                Delegate Voting
                                            </option>
                                            <option value="student_council_voting" 
                                                    <?php echo $schedule['event_type'] == 'student_council_voting' ? 'selected' : ''; ?>>
                                                Student Council Voting
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <span class="icon icon-calendar"></span>
                                        </span>
                                        <input type="datetime-local" 
                                               class="form-control" 
                                               id="start_date" 
                                               name="start_date" 
                                               value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['start_date'])); ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <span class="icon icon-calendar"></span>
                                        </span>
                                        <input type="datetime-local" 
                                               class="form-control" 
                                               id="end_date" 
                                               name="end_date" 
                                               value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['end_date'])); ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mb-4">
                            <span class="icon icon-info"></span>
                            <strong>Note:</strong>
                            <ul class="mb-0">
                                <li>Voting events cannot be longer than 24 hours.</li>
                                <li>Candidate Applications must end before Delegate Voting starts.</li>
                                <li>Delegate Voting must end before Student Council Voting starts.</li>
                            </ul>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <span class="icon icon-save"></span> Update Schedule
                            </button>
                            <a href="schedule_elections.php" class="btn btn-secondary">
                                <span class="icon icon-x"></span> Cancel
                            </a>
                        </div>
                        </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control,
    .form-select,
    .input-group-text {
        border-radius: 10px;
    }
    
    .input-group > :not(:first-child) {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    
    .input-group > :not(:last-child) {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    
    .btn {
        border-radius: 10px;
        padding: 0.5rem 1rem;
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
    
    .alert {
        border-radius: 10px;
    }
    
    .alert ul {
        padding-left: 1.5rem;
        margin-top: 0.5rem;
    }
    
    .gap-2 {
        gap: 0.5rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
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