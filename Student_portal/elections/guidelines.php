<?php
// guidelines.php
include '../includes/db_connection.php'; // Include the database connection
?>
<div id="guidelines">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4>Guidelines</h4>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="guidelinesTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="announcements-tab" data-toggle="tab" href="#announcements" role="tab">Announcements</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="instructions-tab" data-toggle="tab" href="#instructions" role="tab">Instructions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="eligibility-tab" data-toggle="tab" href="#eligibility" role="tab">Eligibility</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="disqualification-tab" data-toggle="tab" href="#disqualification" role="tab">Candidate Disqualification</a>
                </li>
            </ul>
            <div class="tab-content mt-3" id="guidelinesTabContent">
                <div class="tab-pane fade show active" id="announcements" role="tabpanel">
                    <?php
                    $announcements_sql = "SELECT * FROM election_guidelines WHERE type = 'Announcement'";
                    $announcements_result = $conn->query($announcements_sql);
                    while ($row = $announcements_result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<div class='card mb-3'>
                                <div class='card-body'>
                                    <h5>{$row['title']}</h5>
                                    <p>{$row['description']}</p>
                                </div>
                            </div>";
                    }
                    ?>
                </div>
                <div class="tab-pane fade" id="instructions" role="tabpanel">
                    <?php
                    $instructions_sql = "SELECT * FROM election_guidelines WHERE type = 'Instruction'";
                    $instructions_result = $conn->query($instructions_sql);
                    while ($row = $instructions_result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<div class='card mb-3'>
                                <div class='card-body'>
                                    <h5>{$row['title']}</h5>
                                    <p>{$row['description']}</p>
                                </div>
                            </div>";
                    }
                    ?>
                </div>
                <div class="tab-pane fade" id="eligibility" role="tabpanel">
                    <?php
                    $eligibility_sql = "SELECT * FROM election_guidelines WHERE type = 'Eligibility'";
                    $eligibility_result = $conn->query($eligibility_sql);
                    while ($row = $eligibility_result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<div class='card mb-3'>
                                <div class='card-body'>
                                    <h5>{$row['title']}</h5>
                                    <p>{$row['description']}</p>
                                </div>
                            </div>";
                    }
                    ?>
                </div>
                <div class="tab-pane fade" id="disqualification" role="tabpanel">
                    <div class="card shadow">
                        <div class="card-body">
                            <h5 class="text-danger">Disqualifications for Election to the Students' Council</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">❌ Have ever been suspended from the University.</li>
                                <li class="list-group-item">❌ Are a representative/delegate of an Electoral College.</li>
                                <li class="list-group-item">❌ Have previously been removed from office for violating this Constitution.</li>
                                <li class="list-group-item">❌ Have been convicted of a criminal offense in Kenya or any other country.</li>
                                <li class="list-group-item">❌ Will not be physically available to serve for the entire term.</li>
                                <li class="list-group-item">❌ Have already served two terms in any university or university college in Kenya.</li>
                                <li class="list-group-item">❌ Final-year students are ineligible for election to ensure continuity and transition.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>