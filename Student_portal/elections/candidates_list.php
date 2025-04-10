<?php
// candidates_list.php
include '../includes/db_connection.php'; // Include the database connection
?>
<div id="candidates-list" class="mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4>Candidates List</h4>
        </div>
        <div class="card-body">
            <div class="btn-group mb-3" role="group">
                <button type="button" class="btn btn-outline-primary" onclick="showCandidates('Delegate')">Delegates</button>
                <button type="button" class="btn btn-outline-primary" onclick="showCandidates('Student Council')">Student Council</button>
            </div>
            <div id="delegates" style="display: none;">
                <h5 class="text-center">List of Delegates</h5>
                <?php
                $delegates_sql = "SELECT * FROM applications WHERE candidate_type = 'Delegate'";
                $delegates_result = $conn->query($delegates_sql);
                if ($delegates_result->rowCount() > 0) {
                    echo "<table class='table table-bordered'>
                            <thead class='thead-dark'>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>School</th>
                                    <th>Programme</th>
                                    <th>Year</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>";
                    while ($row = $delegates_result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>
                                <td>{$row['student_id']}</td>
                                <td>{$row['student_name']}</td>
                                <td>{$row['school']}</td>
                                <td>{$row['programme']}</td>
                                <td>{$row['year']}</td>
                                <td>{$row['status']}</td>
                            </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<p class='text-danger text-center'>There is no Candidate who has applied at the moment!</p>";
                }
                ?>
            </div>
            <div id="student-council" style="display: none;">
                <h5 class="text-center">List of Student Council Candidates</h5>
                <?php
                $sc_sql = "SELECT * FROM applications WHERE candidate_type = 'Student Council'";
                $sc_result = $conn->query($sc_sql);
                if ($sc_result->rowCount() > 0) {
                    echo "<table class='table table-bordered'>
                            <thead class='thead-dark'>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>School</th>
                                    <th>Programme</th>
                                    <th>Year</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>";
                    while ($row = $sc_result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>
                                <td>{$row['student_id']}</td>
                                <td>{$row['student_name']}</td>
                                <td>{$row['school']}</td>
                                <td>{$row['programme']}</td>
                                <td>{$row['year']}</td>
                                <td>{$row['position']}</td>
                                <td>{$row['status']}</td>
                            </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<p class='text-danger text-center'>There is no Candidate who has applied at the moment!</p>";
                }
                ?>
            </div>
        </div>
    </div>
</div>