<?php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

// Upload Application Form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_form'])) {
    $form_name = $_POST['form_name'];
    $file_name = $_FILES['form_file']['name'];
    $file_tmp = $_FILES['form_file']['tmp_name'];
    $file_path = "uploads/" . $file_name;

    // Create the uploads directory if it doesn't exist
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }

    // Move the uploaded file to the uploads directory
    if (move_uploaded_file($file_tmp, $file_path)) {
        // Insert the form details into the database
        $sql = "INSERT INTO application_forms (form_name, file_path) VALUES (:form_name, :file_path)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['form_name' => $form_name, 'file_path' => $file_path]);
        echo "<script>alert('Form uploaded successfully!');</script>";
    } else {
        echo "<script>alert('Failed to upload file!');</script>";
    }
}

// Delete Form
if (isset($_GET['delete'])) {
    $form_id = $_GET['delete'];
    
    // Get file path before deleting
    $sql = "SELECT file_path FROM application_forms WHERE form_id = :form_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['form_id' => $form_id]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete file if it exists
    if ($form && !empty($form['file_path']) && file_exists($form['file_path'])) {
        unlink($form['file_path']);
    }
    
    // Delete database record
    $sql = "DELETE FROM application_forms WHERE form_id = :form_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['form_id' => $form_id]);
    
    header('Location: upload_forms.php');
    exit();
}

// Fetch all uploaded forms
$sql = "SELECT * FROM application_forms ORDER BY form_id DESC";
$stmt = $conn->query($sql);
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/new_layout.php';
?>

<!-- Page Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Upload Form Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <span class="icon icon-upload"></span> Upload New Form
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="form_name" class="form-label">Form Name</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <span class="icon icon-file-text"></span>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="form_name" 
                                           name="form_name" 
                                           placeholder="Enter form name"
                                           required>
                                </div>
                                <div class="invalid-feedback">Please provide a form name.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="form_file" class="form-label">Upload Form</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <span class="icon icon-upload"></span>
                                    </span>
                                    <input type="file" 
                                           class="form-control" 
                                           id="form_file" 
                                           name="form_file"
                                           accept=".pdf,.doc,.docx"
                                           required>
                                </div>
                                <div class="invalid-feedback">Please select a file to upload.</div>
                                <div class="form-text text-muted">
                                    Accepted formats: PDF, DOC, DOCX. Maximum file size: 5MB
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="upload_form" class="btn btn-primary">
                                <span class="icon icon-upload-cloud"></span> Upload Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Forms List Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <span class="icon icon-list"></span> Uploaded Forms
                    </h5>
                    <div class="header-actions">
                        <input type="text" 
                               class="form-control form-control-sm" 
                               id="searchInput" 
                               placeholder="Search forms..."
                               style="width: 200px;">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="formsTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Form Name</th>
                                    <th>File</th>
                                    <th>Upload Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($forms as $form): ?>
                                    <tr>
                                        <td>
                                            <span class="icon icon-file-text text-primary"></span>
                                            <?php echo htmlspecialchars($form['form_name']); ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($form['file_path']); ?>" 
                                               target="_blank"
                                               class="btn btn-link btn-sm">
                                                <span class="icon icon-eye"></span> View Form
                                            </a>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                <?php echo date('M d, Y', strtotime($form['created_at'] ?? 'now')); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit_form.php?id=<?php echo $form['form_id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <span class="icon icon-edit-2"></span> Edit
                                                </a>
                                                <a href="upload_forms.php?delete=<?php echo $form['form_id']; ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Are you sure you want to delete this form?')">
                                                    <span class="icon icon-trash-2"></span> Delete
                                                </a>
                                            </div>
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
    
    .btn-sm {
        padding: 0.25rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .btn-group {
        gap: 0.5rem;
    }
    
    .btn-group .btn {
        border-radius: 10px !important;
    }
    
    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    
    .card {
        border: none;
        border-radius: 15px;
    }
    
    /* File input styling */
    input[type="file"] {
        padding: 0.375rem 0.75rem;
        line-height: 1.5;
    }
    
    input[type="file"]::-webkit-file-upload-button {
        margin-right: 1rem;
        padding: 0.375rem 0.75rem;
        background: #e9ecef;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        color: #212529;
        cursor: pointer;
    }
    
    input[type="file"]::-webkit-file-upload-button:hover {
        background: #dde0e3;
    }
    
    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .icon {
        margin-right: 0.25rem;
    }
</style>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
    
    // File size validation
    document.getElementById('form_file').addEventListener('change', function(e) {
        const file = e.target.files[0]
        if (file) {
            if (file.size > 5 * 1024 * 1024) { // 5MB
                alert('File size exceeds 5MB limit. Please choose a smaller file.')
                e.target.value = ''
            }
        }
    })
})()

// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase()
    const table = document.getElementById('formsTable')
    const rows = table.getElementsByTagName('tr')
    
    for (let i = 1; i < rows.length; i++) {
        const formName = rows[i].getElementsByTagName('td')[0].textContent.toLowerCase()
        rows[i].style.display = formName.includes(searchText) ? '' : 'none'
    }
})
</script>

<?php include 'includes/new_footer.php'; ?>