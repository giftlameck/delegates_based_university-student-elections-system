<?php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

// Fetch guideline details
if (isset($_GET['id'])) {
    $guideline_id = $_GET['id'];
    $sql = "SELECT * FROM election_guidelines WHERE guideline_id = :guideline_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['guideline_id' => $guideline_id]);
    $guideline = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Update guideline
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_guideline'])) {
    $guideline_id = $_POST['guideline_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $type = $_POST['type'];

    $sql = "UPDATE election_guidelines SET title = :title, description = :description, type = :type WHERE guideline_id = :guideline_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['title' => $title, 'description' => $description, 'type' => $type, 'guideline_id' => $guideline_id]);

    header('Location: manage_guidelines.php');
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
                    <h5 class="card-title mb-0">Edit Guideline</h5>
                    <div class="header-actions">
                        <a href="manage_guidelines.php" class="btn btn-outline-secondary btn-sm">
                            <span class="icon icon-arrow-left"></span> Back to Guidelines
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="guideline_id" value="<?php echo htmlspecialchars($guideline['guideline_id']); ?>">
                        
                        <div class="mb-4">
                            <label for="title" class="form-label">Title</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="icon icon-file-text"></span>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       id="title" 
                                       name="title" 
                                       value="<?php echo htmlspecialchars($guideline['title']); ?>" 
                                       required>
                            </div>
                            <div class="invalid-feedback">Please provide a title.</div>
                        </div>

                        <div class="mb-4">
                            <label for="type" class="form-label">Type</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="icon icon-tag"></span>
                                </span>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="Announcement" <?php echo ($guideline['type'] == 'Announcement') ? 'selected' : ''; ?>>
                                        📢 Announcement
                                    </option>
                                    <option value="Instruction" <?php echo ($guideline['type'] == 'Instruction') ? 'selected' : ''; ?>>
                                        📝 Instruction
                                    </option>
                                    <option value="Eligibility" <?php echo ($guideline['type'] == 'Eligibility') ? 'selected' : ''; ?>>
                                        ✅ Eligibility
                                    </option>
                                </select>
                            </div>
                            <div class="invalid-feedback">Please select a type.</div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Description</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="icon icon-align-left"></span>
                                </span>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="5" 
                                          required><?php echo htmlspecialchars($guideline['description']); ?></textarea>
                            </div>
                            <div class="invalid-feedback">Please provide a description.</div>
                            <div class="form-text">
                                Use clear and concise language. Format using paragraphs for better readability.
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="update_guideline" class="btn btn-primary">
                                <span class="icon icon-save"></span> Update Guideline
                            </button>
                            <a href="manage_guidelines.php" class="btn btn-secondary">
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
    
    textarea.form-control {
        min-height: 120px;
    }
    
    .card {
        border: none;
        border-radius: 15px;
    }
    
    .gap-2 {
        gap: 0.5rem;
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
})()
</script>

<?php include 'includes/new_footer.php'; ?>