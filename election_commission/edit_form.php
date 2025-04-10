<?php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

// Fetch form details
if (isset($_GET['id'])) {
    $form_id = $_GET['id'];
    $sql = "SELECT * FROM application_forms WHERE form_id = :form_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['form_id' => $form_id]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Update form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_form'])) {
    $form_id = $_POST['form_id'];
    $form_name = $_POST['form_name'];
    $file_name = $_FILES['form_file']['name'];
    $file_tmp = $_FILES['form_file']['tmp_name'];
    $file_path = "uploads/" . $file_name;

    if (!empty($file_name)) {
        // Create uploads directory if it doesn't exist
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        // Delete old file if it exists
        if (!empty($form['file_path']) && file_exists($form['file_path'])) {
            unlink($form['file_path']);
        }
        
        // Move the new file
        move_uploaded_file($file_tmp, $file_path);
        // Update the form with the new file
        $sql = "UPDATE application_forms SET form_name = :form_name, file_path = :file_path WHERE form_id = :form_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['form_name' => $form_name, 'file_path' => $file_path, 'form_id' => $form_id]);
    } else {
        // Update only the form name
        $sql = "UPDATE application_forms SET form_name = :form_name WHERE form_id = :form_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['form_name' => $form_name, 'form_id' => $form_id]);
    }

    header('Location: upload_forms.php');
    exit();
}

include 'includes/new_layout.php';
?>

<!-- Page Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Edit Form</h5>
                    <div class="header-actions">
                        <a href="upload_forms.php" class="btn btn-outline-secondary btn-sm">
                            <span class="icon icon-arrow-left"></span> Back to Forms
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <input type="hidden" name="form_id" value="<?php echo htmlspecialchars($form['form_id']); ?>">
                        
                        <div class="mb-4">
                            <label for="form_name" class="form-label">Form Name</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="icon icon-file-text"></span>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       id="form_name" 
                                       name="form_name" 
                                       value="<?php echo htmlspecialchars($form['form_name']); ?>" 
                                       required>
                            </div>
                            <div class="invalid-feedback">Please provide a form name.</div>
                        </div>

                        <div class="mb-4">
                            <label for="form_file" class="form-label">Upload New Form (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="icon icon-upload"></span>
                                </span>
                                <input type="file" 
                                       class="form-control" 
                                       id="form_file" 
                                       name="form_file"
                                       accept=".pdf,.doc,.docx">
                            </div>
                            <div class="form-text">
                                <?php if (!empty($form['file_path'])): ?>
                                    Current file: <a href="<?php echo htmlspecialchars($form['file_path']); ?>" target="_blank">
                                        <span class="icon icon-file"></span> View Current Form
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="form-text text-muted">
                                Accepted formats: PDF, DOC, DOCX. Maximum file size: 5MB
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="update_form" class="btn btn-primary">
                                <span class="icon icon-save"></span> Update Form
                            </button>
                            <a href="upload_forms.php" class="btn btn-secondary">
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
    
    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    
    .card {
        border: none;
        border-radius: 15px;
    }
    
    .gap-2 {
        gap: 0.5rem;
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
</script>

<?php include 'includes/new_footer.php'; ?>