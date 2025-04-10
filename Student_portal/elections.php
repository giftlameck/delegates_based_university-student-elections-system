<?php
// elections.php (in C:\xampp1\htdocs\My_Election_system\Student_portal\elections\)
if (session_status() === PHP_SESSION_NONE) {
    session_name('student_portal');
    session_start();
}

if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['feedback_submit'])) {
        // Handle feedback submission
        $student_id = $_SESSION['student_id'];
        $rating = $_POST['rating'];
        $feedback_type = $_POST['feedback_type'];
        $comments = $_POST['comments'];
        $suggestions = $_POST['suggestions'];
        $created_at = date('Y-m-d H:i:s');

        try {
            $stmt = $conn->prepare("INSERT INTO election_feedback (student_id, rating, feedback_type, comments, suggestions, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $rating, $feedback_type, $comments, $suggestions, $created_at]);
            $success_message = "Thank you for your feedback!";
        } catch(PDOException $e) {
            $error_message = "Error submitting feedback. Please try again.";
        }
    } elseif (isset($_POST['support_submit'])) {
        // Handle support ticket submission
        $student_id = $_SESSION['student_id'];
        $issue_type = $_POST['issue_type'];
        $description = $_POST['description'];
        $priority = $_POST['priority'];
        $created_at = date('Y-m-d H:i:s');

        try {
            $stmt = $conn->prepare("INSERT INTO election_support_tickets (student_id, issue_type, description, priority, status, created_at) VALUES (?, ?, ?, ?, 'open', ?)");
            $stmt->execute([$student_id, $issue_type, $description, $priority, $created_at]);
            $success_message = "Your support ticket has been submitted successfully!";
        } catch(PDOException $e) {
            $error_message = "Error submitting support ticket. Please try again.";
        }
    }
}

// Get the section to load
$section = $_GET['section'] ?? 'elections';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elections</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="../assets/css/sweetalert2.min.css">
    <script src="../assets/js/sweetalert2.min.js"></script>
    <style>
        .election-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
            padding: 15px;
            background-color: #ffffff;
            margin-bottom: 20px;
            height: 100%;
        }
        .election-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .election-icon {
            width: 50px;
            height: 50px;
            margin-bottom: 10px;
            border-radius: 50%;
            background-color: #007bff;
            padding: 10px;
        }
        .election-card h5 {
            color: #007bff;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        .election-card p {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }
        .election-card .btn {
            width: 100%;
            border-radius: 20px;
            font-size: 0.9rem;
            padding: 5px 10px;
            background-color: #007bff;
            border: none;
        }
        .election-card .btn:hover {
            background-color: #0056b3;
        }
        .container {
            min-height: calc(100vh - 100px);
            padding-bottom: 20px;
        }
        footer {
            position: relative;
            bottom: 0;
            width: 100%;
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        .back-to-cards {
            margin-bottom: 20px;
        }
        .cards-section {
            display: block;
        }
        .cards-section.hidden {
            display: none;
        }
        .intro-text {
            background-color: #28a745;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        .intro-text h2 {
            color: white;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .intro-text p {
            color: #f8f9fa;
            font-size: 1rem;
            margin-bottom: 0;
        }
        .marquee {
            background-color: #28a745;
            color: white;
            padding: 10px;
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .marquee.hidden, .intro-text.hidden {
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container mt-4" id="main-container">
        <div class="marquee" id="marquee">
            <marquee behavior="scroll" direction="left">
                🎉 No more queues! 🎉 Apply, Vote, or View Elections Results from anywhere! 🎉 Fast, secure, and transparent elections! 🎉
            </marquee>
        </div>
        <div class="intro-text" id="intro-text">
            <h2>Welcome to the University Students Election System</h2>
            <p>
                Say goodbye to long queues and manual voting! This platform is designed to make elections faster, 
                more secure, and accessible to everyone. Whether you're voting for delegates or applying as a candidate, 
                everything is just a click away. Let's make the election process efficient, transparent, and fun!
            </p>
        </div>
        <div class="cards-section" id="cards-section">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card election-card" onclick="loadSection('guidelines')">
                        <img src="../assets/images/guide.png" alt="Guidelines Icon" class="election-icon">
                        <h5>Guidelines</h5>
                        <p>Read the election guidelines and rules to understand the process.</p>
                        <button class="btn btn-primary">View Guidelines</button>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card election-card" onclick="loadSection('student_council_functions')">
                        <img src="../assets/images/duties.jpeg" alt="Functions Icon" class="election-icon">
                        <h5>Student Council Functions</h5>
                        <p>Learn about the roles and responsibilities of the Student Council.</p>
                        <button class="btn btn-primary">Learn More</button>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card election-card" onclick="loadSection('application_forms')">
                        <img src="../assets/images/form.png" alt="Forms Icon" class="election-icon">
                        <h5>Application Forms</h5>
                        <p>Access and download the required application forms for candidates.</p>
                        <button class="btn btn-primary">View Forms</button>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card election-card" onclick="loadSection('apply_now')">
                        <img src="../assets/images/apply.png" alt="Apply Icon" class="election-icon">
                        <h5>Apply Now</h5>
                        <p>Submit your application to become a candidate in the elections.</p>
                        <button class="btn btn-success">Apply Now</button>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card election-card" onclick="loadSection('candidates_list')">
                        <img src="../assets/images/instructions.png" alt="Candidates Icon" class="election-icon">
                        <h5>Candidates List</h5>
                        <p>View the list of approved candidates for the elections.</p>
                        <button class="btn btn-primary">View Candidates</button>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card election-card" onclick="loadSection('delegate_voting')">
                        <img src="../assets/images/vote.png" alt="Vote Icon" class="election-icon">
                        <h5>Delegate Voting</h5>
                        <p>Participate in the delegate voting process for your programme.</p>
                        <button class="btn btn-warning">Vote Now</button>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card election-card" onclick="loadSection('delegate_results_student')">
                        <img src="../assets/images/results.jpeg" alt="Results Icon" class="election-icon">
                        <h5>Delegate Results</h5>
                        <p>View the results and winners of the delegate elections for your programme.</p>
                        <button class="btn btn-info">View Results</button>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card election-card" onclick="loadSection('student_council_voting')">
                        <img src="../assets/images/council_vote.jpeg" alt="Council Vote Icon" class="election-icon">
                        <h5>Student Council Voting</h5>
                        <p>Vote for your Student Council Chairperson and Vice Chairperson ticket.</p>
                        <button class="btn btn-danger">Vote Now</button>
                    </div>
                </div>
               
                <div class="col-md-4 mb-4">
                    <div class="card election-card" onclick="loadSection('student_council_results')">
                        <img src="../assets/images/results.jpeg" alt="Council Results Icon" class="election-icon">
                        <h5>Student Council Results</h5>
                        <p>View the winners and results of the Student Council elections.</p>
                        <button class="btn btn-info">View Results</button>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card election-card" onclick="loadSection('faq')">
                        <img src="../assets/images/faq.jpg" alt="FAQ Icon" class="election-icon">
                        <h5>Frequently Asked Questions</h5>
                        <p>Find answers to common questions about the election process.</p>
                        <button class="btn btn-secondary">View FAQ</button>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card election-card" onclick="loadSection('support')">
                        <img src="../assets/images/support.png" alt="Support Icon" class="election-icon">
                        <h5>Election Support</h5>
                        <p>Get help with technical issues, voting problems, or general inquiries about the election process.</p>
                        <button class="btn btn-primary">Get Support</button>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card election-card" onclick="loadSection('feedback')">
                        <img src="../assets/images/feedback.png" alt="Feedback Icon" class="election-icon">
                        <h5>Election Feedback</h5>
                        <p>Share your experience with the online election system and help us improve.</p>
                        <button class="btn btn-primary">Give Feedback</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-12" id="content">
                <!-- Content will be loaded here dynamically -->
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

    <script>
        function loadSection(section) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'elections/load_section.php?section=' + section, true);
            xhr.onload = function() {
                if (this.status == 200) {
                    document.getElementById('marquee').classList.add('hidden');
                    document.getElementById('intro-text').classList.add('hidden');
                    document.getElementById('cards-section').classList.add('hidden');
                    document.getElementById('content').innerHTML = `
                        <div class="back-to-cards">
                            <button class="btn btn-secondary" onclick="showMainPage()">← Back to Elections</button>
                        </div>
                        ${this.responseText}
                    `;
                    document.getElementById('content').classList.add('content-loaded');
                    attachEventListeners();
                    if (section === 'delegate_voting') {
                        attachDelegateVoteListener();
                    } else if (section === 'student_council_voting') {
                        attachStudentCouncilVoteListener();
                    }
                }
            };
            xhr.send();
        }

        function showMainPage() {
            document.getElementById('marquee').classList.remove('hidden');
            document.getElementById('intro-text').classList.remove('hidden');
            document.getElementById('cards-section').classList.remove('hidden');
            document.getElementById('content').innerHTML = '';
            document.getElementById('content').classList.remove('content-loaded');
        }

        function attachEventListeners() {
            var candidateTypeDropdown = document.getElementById('candidate_type');
            if (candidateTypeDropdown) {
                candidateTypeDropdown.addEventListener('change', function() {
                    var positionGroup = document.getElementById('position-group');
                    var viceChairpersonGroup = document.getElementById('vice-chairperson-group');
                    var chairpersonGroup = document.getElementById('chairperson-group');
                    if (this.value === 'Student Council') {
                        positionGroup.style.display = 'block';
                    } else {
                        positionGroup.style.display = 'none';
                        viceChairpersonGroup.style.display = 'none';
                        chairpersonGroup.style.display = 'none';
                    }
                });
            }

            var positionDropdown = document.getElementById('position');
            if (positionDropdown) {
                positionDropdown.addEventListener('change', function() {
                    var viceChairpersonGroup = document.getElementById('vice-chairperson-group');
                    var chairpersonGroup = document.getElementById('chairperson-group');
                    if (this.value === 'Chairperson') {
                        viceChairpersonGroup.style.display = 'block';
                        chairpersonGroup.style.display = 'none';
                    } else if (this.value === 'Vice Chairperson') {
                        chairpersonGroup.style.display = 'block';
                        viceChairpersonGroup.style.display = 'none';
                    } else {
                        viceChairpersonGroup.style.display = 'none';
                        chairpersonGroup.style.display = 'none';
                    }
                });
            }
        }

        function attachDelegateVoteListener() {
            console.log('Attaching delegate vote listener');
            var form = document.querySelector('.delegate-vote-form');
            if (!form) {
                console.error('Delegate vote form not found');
                return;
            }
            form.onsubmit = function(e) {
                console.log('Form submit triggered');
                e.preventDefault();
                var selected = this.querySelector('input[name="delegate_id"]:checked');
                if (!selected) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Selection',
                        text: 'Please select a delegate to vote for.',
                    });
                    console.log('No delegate selected');
                    return false;
                }
                var card = selected.closest('.ballot-card');
                var candidateName = card.dataset.name;
                var candidatePhoto = card.dataset.photo;
                console.log('Selected candidate: ' + candidateName + ', Photo: ' + candidatePhoto);
                Swal.fire({
                    title: 'Confirm Your Vote',
                    html: `
                        <div style="text-align: center;">
                            <p>You are about to vote for:</p>
                            <img src="${candidatePhoto}" style="width: 80px; height: 80px; border-radius: 50%; border: 2px solid #007bff; margin-bottom: 10px;" onerror="this.src='../assets/images/default-avatar.png';">
                            <p>${candidateName}</p>
                            <p>Are you sure?</p>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Vote!',
                    cancelButtonText: 'No, Cancel',
                    customClass: { confirmButton: 'btn btn-success', cancelButton: 'btn btn-danger' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log('Vote confirmed, submitting');
                        this.submit();
                    } else {
                        console.log('Vote cancelled');
                    }
                });
            };
        }

        function attachStudentCouncilVoteListener() {
            console.log('Attaching student council vote listeners');
            var forms = document.querySelectorAll('.position-vote-form');
            if (forms.length === 0) {
                console.error('No student council vote forms found');
                return;
            }
            forms.forEach(form => {
                console.log('Attaching listener to form:', form.id);
                form.onsubmit = function(e) {
                    console.log('Form submit triggered for:', form.id);
                    e.preventDefault();
                    var position = this.dataset.position;
                    var inputName = position === 'Chairperson' ? 'ticket_id' : 'candidate_id';
                    var selected = this.querySelector(`input[name="${inputName}"]:checked`);
                    if (!selected) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'No Selection',
                            text: `Please select a ${position === 'Chairperson' ? 'ticket' : 'candidate'} to vote for.`,
                        });
                        console.log('No selection made');
                        return false;
                    }
                    var card = selected.closest('.card');
                    var confirmHtml = '';
                    if (position === 'Chairperson') {
                        var chairName = card.dataset.chairName;
                        var viceName = card.dataset.viceName;
                        var chairPhoto = card.dataset.chairPhoto;
                        var vicePhoto = card.dataset.vicePhoto;
                        confirmHtml = `
                            <div style="text-align: center;">
                                <p>You are about to vote for:</p>
                                <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 10px;">
                                    <div>
                                        <img src="${chairPhoto}" style="width: 80px; height: 80px; border-radius: 50%; border: 2px solid #007bff;" onerror="this.src='../assets/images/default-avatar.png';">
                                        <p>${chairName}</p>
                                    </div>
                                    <div>
                                        <img src="${vicePhoto}" style="width: 80px; height: 80px; border-radius: 50%; border: 2px solid #007bff;" onerror="this.src='../assets/images/default-avatar.png';">
                                        <p>${viceName}</p>
                                    </div>
                                </div>
                                <p>Are you sure?</p>
                            </div>
                        `;
                    } else {
                        var name = card.dataset.name;
                        var photo = card.dataset.photo;
                        confirmHtml = `
                            <div style="text-align: center;">
                                <p>You are about to vote for:</p>
                                <img src="${photo}" style="width: 80px; height: 80px; border-radius: 50%; border: 2px solid #007bff; margin-bottom: 10px;" onerror="this.src='../assets/images/default-avatar.png';">
                                <p>${name}</p>
                                <p>Are you sure?</p>
                            </div>
                        `;
                    }
                    console.log('Showing confirmation for:', position);
                    Swal.fire({
                        title: 'Confirm Your Vote',
                        html: confirmHtml,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Vote!',
                        cancelButtonText: 'No, Cancel',
                        customClass: { confirmButton: 'btn btn-success', cancelButton: 'btn btn-danger' }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            console.log('Vote confirmed, submitting form');
                            this.submit();
                        } else {
                            console.log('Vote cancelled');
                        }
                    });
                };
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            attachEventListeners();
            const urlParams = new URLSearchParams(window.location.search);
            const autoloadSection = urlParams.get('autoload');
            if (autoloadSection) {
                loadSection(autoloadSection);
            }
        });

        function showCandidates(type) {
            document.getElementById('delegates').style.display = (type === 'Delegate') ? 'block' : 'none';
            document.getElementById('student-council').style.display = (type === 'Student Council') ? 'block' : 'none';
        }
    </script>
</body>
</html>