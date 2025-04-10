<?php
// student_council_functions.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students' Council Functions</title>
    <!-- Local Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .section-card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-bottom: none;
        }
        .card-header h4 {
            margin: 0;
            font-weight: 600;
        }
        .card-body {
            background-color: #ffffff;
        }
        .card-body h5 {
            color: #007bff;
            margin-top: 20px;
            font-weight: 600;
        }
        .card-body ul {
            list-style-type: none;
            padding-left: 0;
        }
        .card-body ul li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            color: #555;
        }
        .card-body ul li:last-child {
            border-bottom: none;
        }
        .card-body ul li::before {
            content: "✔";
            color: #28a745;
            margin-right: 10px;
        }
        .card-body ul li strong {
            color: #007bff;
        }
    </style>
</head>
<body>
    <div id="student-council-functions" class="container mt-5">
        <div class="card shadow section-card mb-4">
            <div class="card-header text-white">
                <h4>Functions and Responsibilities of the Students' Council</h4>
            </div>
            <div class="card-body">
                <ul>
                    <li>Overseeing the administration and finances of SOMU.</li>
                    <li>Making emergency decisions in consultation with other SC members.</li>
                    <li>Managing all matters directly affecting SOMU governance.</li>
                    <li>Appointing members to Council Committees.</li>
                    <li>Creating ad hoc committees for specific tasks as needed.</li>
                    <li>Supervising all SOMU activities.</li>
                    <li>Holding at least five meetings per academic semester.</li>
                </ul>
            </div>
        </div>
        <div class="card shadow section-card">
            <div class="card-header text-white">
                <h4>Duties and Responsibilities of Students' Council Members</h4>
            </div>
            <div class="card-body">
                <h5>Chairperson</h5>
                <ul>
                    <li>Presides over all general meetings.</li>
                    <li>Responsible for the overall administration of the SC.</li>
                    <li>Represents SOMU in the University Senate and Students' Disciplinary Committee.</li>
                    <li>Acts as the official spokesperson of SOMU.</li>
                    <li>Appoints the SOMU Editor after a competitive process.</li>
                    <li>Coordinates students' academic issues and security matters.</li>
                </ul>
                <h5>Vice-Chairperson</h5>
                <ul>
                    <li>Deputizes the Chairperson.</li>
                    <li>Oversees students' welfare, including accommodation and transport.</li>
                    <li>Ensures the coordination of financial matters.</li>
                    <li>Member of the student welfare committee.</li>
                </ul>
                <h5>Treasurer</h5>
                <ul>
                    <li>Manages SOMU's finances.</li>
                    <li>Co-signs bank cheques and financial documents.</li>
                    <li>Chairs the Finance Committee.</li>
                    <li>Prepares and presents audited financial reports.</li>
                </ul>
                <h5>Secretary-General</h5>
                <ul>
                    <li>Serves as the Secretary of SOMU and SC.</li>
                    <li>Maintains SOMU records.</li>
                    <li>Represents SOMU in the University Senate.</li>
                    <li>Issues meeting notices and communicates SC decisions.</li>
                </ul>
                <h5>Other Council Members</h5>
                <ul>
                    <li><strong>Campuses Representative:</strong> Oversees student issues in campuses and learning centers.</li>
                    <li><strong>Special Needs Representative:</strong> Advocates for PWD, gender equity, and cultural heritage issues.</li>
                    <li><strong>Sports & Entertainment Representative:</strong> Coordinates recreational activities and student entertainment.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Local Bootstrap JS and dependencies -->
    <script src="../../assets/js/jquery.min.js"></script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>