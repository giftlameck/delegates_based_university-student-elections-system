<?php
// elections/load_section.php
include '../includes/db_connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('student_portal');
    session_start();
}

if (!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}

// Get the section to load
$section = $_GET['section'] ?? 'elections';

switch($section) {
    case 'guidelines':
        include 'guidelines.php';
        break;
    case 'application_forms':
        include 'application_forms.php';
        break;
    case 'student_council_functions':
            include 'student_council_functions.php';
            break;
    case 'apply_now':
        include 'apply_now.php';
        break;
    case 'candidates_list':
        include 'candidates_list.php';
        break;
    case 'delegate_voting':
        include 'delegate_voting.php';
        break;
    case 'student_council_voting':
        include 'student_council_voting.php';
        break;
    case 'delegate_results_student':
        include 'delegate_results_student.php';
        break;
    case 'student_council_results':
        include 'student_council_results.php';
        break;
    case 'faq':
        include 'faq.php';
        break;
    case 'support':
        include 'support.php';
        break;
    case 'feedback':
        include 'feedback.php';
        break;
    default:
    echo "Section not found.";
    break;
}
?>