<?php
// application_forms.php
include '../includes/db_connection.php'; // Include the database connection
?>
<div id="application-forms" class="mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4>Application Forms</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Form Name</th>
                        <th>Download</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $forms_sql = "SELECT * FROM application_forms";
                    $forms_result = $conn->query($forms_sql);
                    while ($row = $forms_result->fetch(PDO::FETCH_ASSOC)) {
                        $file_path = "../election_commission/" . $row['file_path'];
                        echo "<tr>
                                <td>{$row['form_name']}</td>
                                <td><a href='{$file_path}' class='btn btn-primary btn-sm' download>Download</a></td>
                            </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>