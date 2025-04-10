<?php
session_name('election_commission');
session_start();
include 'includes/db_connection.php';

if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}

if (isset($_GET['id'])) {
    $application_id = $_GET['id'];
    $sql = "SELECT a.*, sd.gender, sd.school, dw.status as winner_status 
            FROM applications a 
            LEFT JOIN student_details sd ON a.student_id = sd.Student_id 
            LEFT JOIN delegate_winners dw ON a.student_id = dw.delegate_id
            WHERE a.application_id = :application_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['application_id' => $application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        echo "<script>alert('Application not found.'); window.location.href='manage_applications.php';</script>";
        exit();
    }
}

// Update application status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'];
    
    // First, let's add the remarks column if it doesn't exist
    try {
        $conn->exec("ALTER TABLE applications ADD COLUMN IF NOT EXISTS remarks TEXT");
    } catch (PDOException $e) {
        // If the column already exists or there's another issue, we'll continue anyway
    }
    
    $remarks = $_POST['remarks'] ?? '';
    
    $sql = "UPDATE applications SET status = :status, remarks = :remarks WHERE application_id = :application_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'status' => $status, 
        'remarks' => $remarks,
        'application_id' => $application_id
    ]);
    
    echo "<script>alert('Application status updated successfully!'); window.location.href='manage_applications.php';</script>";
    exit();
}

include 'includes/new_layout.php';
?>

<!-- Page Content -->
<div class="container-fluid">
        <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center">
                        <a href="manage_applications.php" class="btn btn-outline-secondary btn-sm me-3">
                            <span class="icon icon-arrow-left"></span> Back to Applications
                        </a>
                        <h5 class="card-title mb-0">Review Application</h5>
                    </div>
                    <div class="header-actions">
                        <span class="badge bg-<?php 
                            switch($application['status']) {
                                case 'Pending': echo 'warning'; break;
                                case 'Approved': echo 'success'; break;
                                case 'Rejected': echo 'danger'; break;
                                default: echo 'secondary';
                            }
                        ?> px-3 py-2">
                            <span class="icon <?php 
                                switch($application['status']) {
                                    case 'Pending': echo 'icon-clock'; break;
                                    case 'Approved': echo 'icon-check'; break;
                                    case 'Rejected': echo 'icon-x'; break;
                                    default: echo 'icon-info';
                                }
                            ?>"></span>
                            <?php echo htmlspecialchars($application['status']); ?>
                        </span>
                    </div>
                    </div>
                    <div class="card-body">
                    <div class="row">
                        <!-- Applicant Profile Section -->
                        <div class="col-md-4">
                            <div class="applicant-profile text-center mb-4">
                                <div class="profile-photo mb-3">
                                    <img src="../Student_portal/<?= htmlspecialchars($application['photo_path']) ?>" 
                                         class="img-thumbnail rounded-circle" 
                                         style="width: 150px; height: 150px; object-fit: cover;">
                                </div>
                                <h5 class="mb-1"><?= htmlspecialchars($application['student_name']) ?></h5>
                                <p class="text-muted mb-2"><?= htmlspecialchars($application['student_id']) ?></p>
                                <div class="candidate-type-badge mb-3">
                                    <span class="badge bg-primary px-3 py-2">
                                        <span class="icon icon-user"></span>
                                        <?= htmlspecialchars($application['candidate_type']) ?>
                                        <?= $application['position'] ? ' - ' . htmlspecialchars($application['position']) : '' ?>
                                    </span>
                                </div>
                                <div class="mt-3">
                                    <a href="../Student_portal/<?= htmlspecialchars($application['form_path']) ?>" 
                                       target="_blank" 
                                       class="btn btn-outline-primary btn-sm">
                                        <span class="icon icon-file"></span> View Application Form
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Application Details Section -->
                        <div class="col-md-8">
                            <!-- Personal Information Card -->
                            <div class="card bg-light mb-4">
                                <div class="card-body">
                                    <h6 class="card-title d-flex align-items-center mb-4">
                                        <span class="icon icon-user me-2"></span> Personal Information
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="detail-item">
                                                <label class="text-muted">School</label>
                                                <p class="mb-2"><?= htmlspecialchars($application['school']) ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="detail-item">
                                                <label class="text-muted">Programme</label>
                                                <p class="mb-2"><?= htmlspecialchars($application['programme']) ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="detail-item">
                                                <label class="text-muted">Gender</label>
                                                <p class="mb-2">
                                                    <span class="badge <?= $application['gender'] === 'M' ? 'bg-info' : 'bg-danger' ?>">
                                                        <span class="icon <?= $application['gender'] === 'M' ? 'icon-male' : 'icon-female' ?>"></span>
                                                        <?= $application['gender'] === 'M' ? 'Male' : 'Female' ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="detail-item">
                                                <label class="text-muted">Year</label>
                                                <p class="mb-2"><?= htmlspecialchars($application['year']) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Application Status Card -->
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title d-flex align-items-center mb-4">
                                        <span class="icon icon-clipboard me-2"></span> Application Status
                                    </h6>
                                    <form method="POST" action="">
                                        <div class="mb-4">
                                            <label for="status" class="form-label">Update Status</label>
                                            <select class="form-select form-select-lg" id="status" name="status" required>
                                                <option value="Pending" <?= ($application['status'] == 'Pending') ? 'selected' : ''; ?>>
                                                    🕒 Pending Review
                                                </option>
                                                <option value="Approved" <?= ($application['status'] == 'Approved') ? 'selected' : ''; ?>>
                                                    ✅ Approve Application
                                                </option>
                                                <option value="Rejected" <?= ($application['status'] == 'Rejected') ? 'selected' : ''; ?>>
                                                    ❌ Reject Application
                                                </option>
                                </select>
                                        </div>
                                        <div class="mb-4">
                                            <label for="remarks" class="form-label">Remarks</label>
                                            <textarea class="form-control" id="remarks" name="remarks" rows="3"
                                                      placeholder="Add any comments or reasons for the decision..."><?= htmlspecialchars($application['remarks'] ?? '') ?></textarea>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="submit" name="update_status" class="btn btn-primary">
                                                <span class="icon icon-save"></span> Update Status
                                            </button>
                                            <a href="manage_applications.php" class="btn btn-secondary">
                                                <span class="icon icon-x"></span> Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-photo img {
        border: 3px solid #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .profile-photo img:hover {
        transform: scale(1.05);
    }
    
    .detail-item label {
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
        display: block;
    }
    
    .detail-item p {
        font-weight: 500;
        margin-bottom: 0;
    }
    
    .form-select,
    .form-control {
        border-radius: 10px;
        padding: 0.75rem 1rem;
    }
    
    .btn {
        border-radius: 10px;
        padding: 0.5rem 1.5rem;
        font-weight: 500;
    }
    
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .badge {
        padding: 0.5rem 0.75rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .gap-2 {
        gap: 0.5rem;
    }

    .candidate-type-badge .badge {
        font-size: 0.9rem;
    }

    .header-actions .badge {
        font-size: 1rem;
    }

    .form-select-lg {
        font-size: 1rem;
    }

    .card-title {
        font-weight: 600;
    }

    .detail-item {
        background: #fff;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const remarksTextarea = document.getElementById('remarks');
    
    statusSelect.addEventListener('change', function() {
        if (this.value === 'Rejected' && !remarksTextarea.value.trim()) {
            remarksTextarea.setAttribute('required', 'required');
            remarksTextarea.placeholder = 'Please provide a reason for rejection...';
            remarksTextarea.classList.add('is-invalid');
        } else {
            remarksTextarea.removeAttribute('required');
            remarksTextarea.placeholder = 'Add any comments or reasons for the decision...';
            remarksTextarea.classList.remove('is-invalid');
        }
    });
});
</script>

<?php include 'includes/new_footer.php'; ?>
