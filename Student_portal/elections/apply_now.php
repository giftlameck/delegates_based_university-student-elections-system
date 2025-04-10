<?php
// apply_now.php
include '../includes/db_connection.php';

// Fetch the candidate application schedule
$sql = "SELECT * FROM election_schedule WHERE event_type = 'candidate_application'";
$stmt = $conn->query($sql);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

$current_date = date('Y-m-d H:i:s'); // Current date and time
$application_open = false;
$notification_message = '';

if ($schedule) {
    $start_date = $schedule['start_date'];
    $end_date = $schedule['end_date'];

    if ($current_date < $start_date) {
        // Application period has not started
        $notification_message = "Sorry, the candidate application period is yet to open! It will be open from $start_date to $end_date.";
    } elseif ($current_date > $end_date) {
        // Application period has ended
        $notification_message = "Sorry, the candidate application period elapsed on $end_date.";
    } else {
        // Application period is open
        $application_open = true;
    }
} else {
    // No schedule found
    $notification_message = "Sorry, the candidate application period has not been scheduled yet.";
}
?>
<div id="apply-now" class="mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4>Apply Now</h4>
        </div>
        <div class="card-body">
            <?php
            if (!$application_open) {
                // Display notification if application is not open
                echo "<div class='alert alert-warning'>$notification_message</div>";
            } else {
                // Proceed with the application form if the application period is open
                $student_id = $_SESSION['student_id'];
                $sql = "SELECT * FROM applications WHERE student_id = :student_id";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['student_id' => $student_id]);
                $existing_application = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing_application) {
                    echo "<div class='alert alert-warning'>
                            Sorry! You have already applied as <strong>{$existing_application['candidate_type']}</strong>.
                            <br>You cannot apply again as either Delegate or Student Council.
                        </div>";
                } else {
                    $year = $_SESSION['year'];
                    $gender = $_SESSION['gender'];
                    $is_eligible_for_student_council = ($year == 2 || $year == 3);
                    $is_eligible_for_chairperson = ($year == 3);

                    // Check if this student is linked as a Vice Chairperson or Chairperson
                    $auto_chairperson_id = null;
                    $auto_vice_chairperson_id = null;
                    $linked_role = null;
                    $linked_by_id = null;

                    $sql = "SELECT student_id FROM applications WHERE vice_chairperson_id = :student_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute(['student_id' => $student_id]);
                    $chairperson_match = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($chairperson_match) {
                        $auto_chairperson_id = $chairperson_match['student_id'];
                        $linked_role = 'Vice Chairperson';
                        $linked_by_id = $chairperson_match['student_id'];
                    }

                    $sql = "SELECT student_id FROM applications WHERE chairperson_id = :student_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute(['student_id' => $student_id]);
                    $vice_chairperson_match = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($vice_chairperson_match) {
                        $auto_vice_chairperson_id = $vice_chairperson_match['student_id'];
                        $linked_role = 'Chairperson';
                        $linked_by_id = $vice_chairperson_match['student_id'];
                    }

                    if ($linked_role) {
                        echo "<div class='alert alert-info'>
                                You are linked as a <strong>$linked_role</strong> by <strong>$linked_by_id</strong>. 
                                You must apply as <strong>$linked_role</strong>.
                              </div>";
                    }
                    ?>
                    <form method="POST" action="submit_application.php" enctype="multipart/form-data" id="application-form">
                        <input type="hidden" id="linked_role" value="<?php echo $linked_role ?? ''; ?>">
                        <input type="hidden" id="linked_by_id" value="<?php echo $linked_by_id ?? ''; ?>">

                        <div class="form-group">
                            <label for="student_name">Student Name</label>
                            <input type="text" class="form-control" id="student_name" name="student_name" value="<?php echo $_SESSION['student_name']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="student_id">Student ID</label>
                            <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo $_SESSION['student_id']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="school">School</label>
                            <input type="text" class="form-control" id="school" name="school" value="<?php echo $_SESSION['school']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="programme">Programme</label>
                            <input type="text" class="form-control" id="programme" name="programme" value="<?php echo $_SESSION['programme']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <input type="text" class="form-control" id="gender" name="gender" value="<?php echo $_SESSION['gender']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="year">Year of Study</label>
                            <input type="text" class="form-control" id="year" name="year" value="<?php echo $_SESSION['year']; ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="candidate_type">Candidate Type</label>
                            <select class="form-control" id="candidate_type" name="candidate_type" required>
                                <option value="Delegate">Delegate</option>
                                <option value="Student Council" <?php echo $is_eligible_for_student_council ? '' : 'disabled'; ?>>Student Council</option>
                            </select>
                        </div>

                        <div class="form-group" id="position-group" style="display: none;">
                            <label for="position">Position</label>
                            <select class="form-control" id="position" name="position">
                                <option value="">Select Position</option>
                                <option value="Chairperson" <?php echo $is_eligible_for_chairperson ? '' : 'disabled'; ?>>Chairperson</option>
                                <option value="Vice Chairperson">Vice Chairperson</option>
                                <option value="Secretary General">Secretary General</option>
                                <option value="Treasurer">Treasurer</option>
                                <option value="Campus Representative">Campus Representative</option>
                                <option value="PWD Representative">PWD Representative</option>
                                <option value="Games & Entertainment">Games & Entertainment</option>
                            </select>
                        </div>

                        <div class="form-group" id="vice-chairperson-group" style="display: none;">
                            <label for="vice_chairperson_id">Vice Chairperson's Student ID</label>
                            <input type="text" class="form-control" id="vice_chairperson_id" name="vice_chairperson_id" value="<?php echo $auto_vice_chairperson_id ?? ''; ?>" <?php echo $auto_vice_chairperson_id ? 'readonly' : ''; ?>>
                            <small class="form-text text-muted">The Vice Chairperson must be of the opposite gender and a 2nd or 3rd year student.</small>
                        </div>

                        <div class="form-group" id="chairperson-group" style="display: none;">
                            <label for="chairperson_id">Chairperson's Student ID</label>
                            <input type="text" class="form-control" id="chairperson_id" name="chairperson_id" value="<?php echo $auto_chairperson_id ?? ''; ?>" <?php echo $auto_chairperson_id ? 'readonly' : ''; ?>>
                            <small class="form-text text-muted">The Chairperson must be of the opposite gender and a 3rd year student.</small>
                        </div>

                        <div class="form-group">
                            <label for="photo">Upload Photo</label>
                            <input type="file" class="form-control" id="photo" name="photo" required>
                        </div>

                        <div class="form-group">
                            <label for="application_form">Upload Filled Application Form</label>
                            <input type="file" class="form-control" id="application_form" name="application_form" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit Application</button>
                    </form>

                    <script>
                        // Show/hide position field
                        document.getElementById('candidate_type').addEventListener('change', function() {
                            var positionGroup = document.getElementById('position-group');
                            positionGroup.style.display = this.value === 'Student Council' ? 'block' : 'none';
                        });

                        // Show/hide counterpart ID fields
                        document.getElementById('position').addEventListener('change', function() {
                            var viceChairGroup = document.getElementById('vice-chairperson-group');
                            var chairGroup = document.getElementById('chairperson-group');
                            viceChairGroup.style.display = this.value === 'Chairperson' ? 'block' : 'none';
                            chairGroup.style.display = this.value === 'Vice Chairperson' ? 'block' : 'none';
                        });

                        // Validate form submission
                        document.getElementById('application-form').onsubmit = function(e) {
                            var linkedRole = document.getElementById('linked_role').value;
                            var candidateType = document.getElementById('candidate_type').value;
                            var position = document.getElementById('position').value || '';
                            var linkedById = document.getElementById('linked_by_id').value;

                            if (linkedRole) {
                                if (linkedRole === 'Vice Chairperson') {
                                    if (candidateType !== 'Student Council' || position !== 'Vice Chairperson') {
                                        e.preventDefault();
                                        alert('You are linked as a Vice Chairperson by ' + linkedById + '. You must apply as Vice Chairperson.');
                                        return false;
                                    }
                                } else if (linkedRole === 'Chairperson') {
                                    if (candidateType !== 'Student Council' || position !== 'Chairperson') {
                                        e.preventDefault();
                                        alert('You are linked as a Chairperson by ' + linkedById + '. You must apply as Chairperson.');
                                        return false;
                                    }
                                }
                            }
                            return true; // Allow submission if all checks pass
                        };
                    </script>
                    <?php
                }
            }
            ?>
        </div>
    </div>
</div>