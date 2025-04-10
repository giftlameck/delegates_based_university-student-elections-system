<?php
session_name('student_portal');
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}
include 'includes/header.php';
include 'includes/db_connection.php';

$student_id = $_SESSION['student_id'];
$message = '';
$message_type = '';

// Handle contact information update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_contact'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);

    if (filter_var($email, FILTER_VALIDATE_EMAIL) || empty($email)) {
        $sql = "UPDATE student_details SET email = :email, phone = :phone, address = :address WHERE Student_id = :student_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'student_id' => $student_id
        ]);

        $message = 'Contact information updated successfully!';
        $message_type = 'success';
    } else {
        $message = 'Invalid email format. Please enter a valid email address.';
        $message_type = 'danger';
    }
}

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
        $upload_dir = 'uploads/profile_photos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = $student_id . '_' . time() . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Update database with new photo path
            $sql = "UPDATE student_details SET photo_path = :photo_path WHERE Student_id = :student_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'photo_path' => $target_path,
                'student_id' => $student_id
            ]);

            // Update session with new photo path
            $_SESSION['student_photo'] = $target_path;

            $message = 'Profile photo updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error uploading file. Please try again.';
            $message_type = 'danger';
        }
    } else {
        $message = 'Invalid file type or size. Please upload a JPEG, PNG, or GIF file under 5MB.';
        $message_type = 'danger';
    }
}

// Fetch student details
$sql = "SELECT * FROM student_details WHERE Student_id = :student_id";
$stmt = $conn->prepare($sql);
$stmt->execute(['student_id' => $student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Set photo path from database if not in session
if (!isset($_SESSION['student_photo']) && isset($student['photo_path'])) {
    $_SESSION['student_photo'] = $student['photo_path'];
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <?php
                    $photo_path = isset($_SESSION['student_photo']) && file_exists($_SESSION['student_photo']) 
                        ? $_SESSION['student_photo'] 
                        : 'assets/images/default-avatar.svg';
                    ?>
                    <img src="<?php echo $photo_path; ?>" alt="Profile Photo" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="profile_photo">Change Profile Photo</label>
                            <input type="file" class="form-control-file" id="profile_photo" name="profile_photo" accept="image/*">
                            <small class="form-text text-muted">Max file size: 5MB. Supported formats: JPEG, PNG, GIF</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload Photo</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Student Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6 class="text-muted mb-3">Basic Information</h6>
                                <p><strong>Student ID:</strong> <?php echo $student['Student_id']; ?></p>
                                <p><strong>Name:</strong> <?php echo $_SESSION['student_name']; ?></p>
                                <p><strong>School:</strong> <?php echo $_SESSION['school']; ?></p>
                                <p><strong>Programme:</strong> <?php echo $_SESSION['programme']; ?></p>
                                <p><strong>Gender:</strong> <?php echo isset($student['Gender']) ? $student['Gender'] : 'Not specified'; ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6 class="text-muted mb-3">Contact Information</h6>
                                <?php if (isset($student['email']) || isset($student['phone']) || isset($student['address'])): ?>
                                    <div class="contact-info mb-3">
                                        <?php if (isset($student['email']) && !empty($student['email'])): ?>
                                            <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                                        <?php endif; ?>
                                        <?php if (isset($student['phone']) && !empty($student['phone'])): ?>
                                            <p><i class="fas fa-phone"></i> <strong>Phone:</strong> <?php echo htmlspecialchars($student['phone']); ?></p>
                                        <?php endif; ?>
                                        <?php if (isset($student['address']) && !empty($student['address'])): ?>
                                            <p><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong> <?php echo htmlspecialchars($student['address']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <button class="btn btn-outline-primary btn-sm mb-3" type="button" data-toggle="collapse" data-target="#contactForm" aria-expanded="false">
                                    <i class="fas fa-edit"></i> <?php echo (isset($student['email']) || isset($student['phone']) || isset($student['address'])) ? 'Update Contact Info' : 'Add Contact Info'; ?>
                                </button>

                                <div class="collapse" id="contactForm">
                                    <form action="" method="POST" class="contact-form">
                                        <input type="hidden" name="update_contact" value="1">
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo isset($student['email']) ? htmlspecialchars($student['email']) : ''; ?>"
                                                   placeholder="Enter your email">
                                        </div>
                                        <div class="form-group">
                                            <label for="phone">Phone</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo isset($student['phone']) ? htmlspecialchars($student['phone']) : ''; ?>"
                                                   placeholder="Enter your phone number">
                                        </div>
                                        <div class="form-group">
                                            <label for="address">Address</label>
                                            <textarea class="form-control" id="address" name="address" rows="2" 
                                                      placeholder="Enter your address"><?php echo isset($student['address']) ? htmlspecialchars($student['address']) : ''; ?></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
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
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding: 1rem;
}

.card-title {
    color: #2c3e50;
    font-weight: 600;
    margin: 0;
}

.form-control-file {
    padding: 0.375rem 0.75rem;
}

.btn-primary {
    background-color: #3498db;
    border-color: #3498db;
}

.btn-primary:hover {
    background-color: #2980b9;
    border-color: #2980b9;
}

.alert {
    border-radius: 10px;
    margin-bottom: 20px;
}

.contact-form {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 15px;
}

.contact-form .form-group {
    margin-bottom: 1rem;
}

.contact-form label {
    font-weight: 500;
    color: #2c3e50;
}

.contact-form .form-control {
    border: 1px solid #dee2e6;
    border-radius: 5px;
}

.contact-form .form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
}

.contact-form textarea {
    resize: vertical;
    min-height: 80px;
}

.info-section {
    padding: 15px;
    background-color: #fff;
    border-radius: 8px;
}

.contact-info p {
    margin-bottom: 0.5rem;
}

.contact-info i {
    width: 20px;
    color: #3498db;
    margin-right: 5px;
}

.btn-outline-primary {
    color: #3498db;
    border-color: #3498db;
}

.btn-outline-primary:hover {
    background-color: #3498db;
    border-color: #3498db;
    color: #fff;
}

.collapse {
    transition: all 0.3s ease;
}
</style>

<?php include 'includes/footer.php'; ?> 