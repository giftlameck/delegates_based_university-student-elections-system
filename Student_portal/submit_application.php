<?php
session_name('student_portal');
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_SESSION['student_id'];
    $student_name = $_SESSION['student_name'];
    $school = $_SESSION['school'];
    $programme = $_SESSION['programme'];
    $gender = $_SESSION['gender'];
    $year = $_SESSION['year'];

    $candidate_type = $_POST['candidate_type'];
    $position = $_POST['position'] ?? null;
    $vice_chairperson_id = $_POST['vice_chairperson_id'] ?? null;
    $chairperson_id = $_POST['chairperson_id'] ?? null;

    // Define upload directory
    $uploads_dir = '../Student_portal/uploads/';
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0777, true);
    }

    function uploadFile($file, $allowed_types, $destination_folder) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => "File upload error: " . $file['error']];
        }
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            return ['error' => "Invalid file type: $file_type"];
        }
        $file_name = time() . "_" . basename($file['name']);
        $file_path = $destination_folder . $file_name;
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return ['error' => "Error moving file"];
        }
        return ['path' => $file_path];
    }

    $photo_result = uploadFile($_FILES['photo'], ['image/jpeg', 'image/png', 'image/gif'], $uploads_dir);
    if (isset($photo_result['error'])) {
        die("<script>alert('{$photo_result['error']}'); window.location.href='elections.php';</script>");
    }

    $form_result = uploadFile($_FILES['application_form'], ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], $uploads_dir);
    if (isset($form_result['error'])) {
        die("<script>alert('{$form_result['error']}'); window.location.href='elections.php';</script>");
    }

    // Check if student is linked and enforce role
    $linked_role = null;
    $sql = "SELECT student_id FROM applications WHERE vice_chairperson_id = :student_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['student_id' => $student_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        $linked_role = 'Vice Chairperson';
    }
    $sql = "SELECT student_id FROM applications WHERE chairperson_id = :student_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['student_id' => $student_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        $linked_role = 'Chairperson';
    }

    if ($linked_role) {
        if ($linked_role === 'Vice Chairperson' && ($candidate_type !== 'Student Council' || $position !== 'Vice Chairperson')) {
            echo "<script>alert('You are linked as a Vice Chairperson. You must apply as Vice Chairperson.'); window.location.href='elections.php';</script>";
            exit();
        } else if ($linked_role === 'Chairperson' && ($candidate_type !== 'Student Council' || $position !== 'Chairperson')) {
            echo "<script>alert('You are linked as a Chairperson. You must apply as Chairperson.'); window.location.href='elections.php';</script>";
            exit();
        }
    }

    // Nullify IDs if role doesn't match
    if ($candidate_type !== 'Student Council' || $position !== 'Chairperson') {
        $vice_chairperson_id = null; // Clear if not applying as Chairperson
    }
    if ($candidate_type !== 'Student Council' || $position !== 'Vice Chairperson') {
        $chairperson_id = null; // Clear if not applying as Vice Chairperson
    }

    // Existing Restrictions
    if ($position == 'Vice Chairperson' && !empty($chairperson_id)) {
        $sql = "SELECT * FROM student_details WHERE Student_id = :chairperson_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['chairperson_id' => $chairperson_id]);
        $chairperson = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$chairperson || $chairperson['Year'] != 3 || $chairperson['Gender'] == $gender) {
            echo "<script>alert('Invalid Chairperson! Must be a 3rd-year student of opposite gender.'); window.location.href='elections.php';</script>";
            exit();
        }
        $sql = "SELECT * FROM applications WHERE student_id = :chairperson_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['chairperson_id' => $chairperson_id]);
        $chair_application = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($chair_application && ($chair_application['candidate_type'] == 'Delegate' || ($chair_application['candidate_type'] == 'Student Council' && $chair_application['position'] != 'Chairperson'))) {
            echo "<script>alert('The selected Chairperson has already applied as a Delegate or another Student Council position.'); window.location.href='elections.php';</script>";
            exit();
        }
    }

    if ($position == 'Chairperson' && !empty($vice_chairperson_id)) {
        $sql = "SELECT * FROM student_details WHERE Student_id = :vice_chairperson_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['vice_chairperson_id' => $vice_chairperson_id]);
        $vice_chairperson = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$vice_chairperson || $vice_chairperson['Year'] == 1 || $vice_chairperson['Year'] == 4 || $vice_chairperson['Gender'] == $gender) {
            echo "<script>alert('Invalid Vice Chairperson! Must be a 2nd or 3rd-year student of opposite gender.'); window.location.href='elections.php';</script>";
            exit();
        }
        $sql = "SELECT * FROM applications WHERE student_id = :vice_chairperson_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['vice_chairperson_id' => $vice_chairperson_id]);
        $vice_application = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($vice_application && ($vice_application['candidate_type'] == 'Delegate' || ($vice_application['candidate_type'] == 'Student Council' && $vice_application['position'] != 'Vice Chairperson'))) {
            echo "<script>alert('The selected Vice Chairperson has already applied as a Delegate or another Student Council position.'); window.location.href='elections.php';</script>";
            exit();
        }
    }

    if ($position == 'Chairperson' && !empty($vice_chairperson_id)) {
        $sql = "SELECT * FROM applications WHERE vice_chairperson_id = :vice_chairperson_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['vice_chairperson_id' => $vice_chairperson_id]);
        if ($stmt->rowCount() > 0) {
            echo "<script>alert('This Vice Chairperson is already linked to another Chairperson.'); window.location.href='elections.php';</script>";
            exit();
        }
    }

    if ($position == 'Vice Chairperson' && !empty($chairperson_id)) {
        $sql = "SELECT * FROM applications WHERE chairperson_id = :chairperson_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['chairperson_id' => $chairperson_id]);
        if ($stmt->rowCount() > 0) {
            echo "<script>alert('This Chairperson is already linked to another Vice Chairperson.'); window.location.href='elections.php';</script>";
            exit();
        }
    }

    // Insert into database
    $sql = "INSERT INTO applications (student_id, student_name, school, programme, gender, year, candidate_type, position, photo_path, form_path, vice_chairperson_id, chairperson_id)
            VALUES (:student_id, :student_name, :school, :programme, :gender, :year, :candidate_type, :position, :photo_path, :form_path, :vice_chairperson_id, :chairperson_id)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'student_id' => $student_id,
        'student_name' => $student_name,
        'school' => $school,
        'programme' => $programme,
        'gender' => $gender,
        'year' => $year,
        'candidate_type' => $candidate_type,
        'position' => $position,
        'photo_path' => $photo_result['path'],
        'form_path' => $form_result['path'],
        'vice_chairperson_id' => $vice_chairperson_id,
        'chairperson_id' => $chairperson_id
    ]);

    echo "<script>alert('Application submitted successfully!'); window.location.href='elections.php';</script>";
}
?>