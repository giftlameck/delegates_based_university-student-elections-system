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
    $delegate_id = $_POST['delegate_id'];
    $programme = $_SESSION['programme'];

    // Check voting schedule
    $schedule = $conn->query("SELECT start_date, end_date FROM election_schedule WHERE event_type = 'delegate_voting' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $current_date = new DateTime();
    if (!$schedule || $current_date < new DateTime($schedule['start_date']) || $current_date > new DateTime($schedule['end_date'])) {
        echo "<script>alert('Voting is not currently open!'); window.location.href='elections.php';</script>";
        exit();
    }

    // Check if student has already voted
    $sql = "SELECT * FROM delegate_votes WHERE voter_id = :student_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['student_id' => $student_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<script>alert('You have already voted for a delegate!'); window.location.href='elections.php';</script>";
        exit();
    }

    // Verify the delegate
    $sql = "SELECT * FROM applications 
            WHERE student_id = :delegate_id 
            AND candidate_type = 'Delegate' 
            AND status = 'Approved' 
            AND programme = :programme";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['delegate_id' => $delegate_id, 'programme' => $programme]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<script>alert('Invalid delegate selection!'); window.location.href='elections.php';</script>";
        exit();
    }

    // Record the vote
    $sql = "INSERT INTO delegate_votes (voter_id, delegate_id, programme, voted_at) 
            VALUES (:student_id, :delegate_id, :programme, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['student_id' => $student_id, 'delegate_id' => $delegate_id, 'programme' => $programme]);

    // Update winners dynamically
    function updateDelegateWinners($conn, $programme) {
        $conn->exec("DELETE FROM delegate_winners WHERE programme = '$programme'");
        $approved_count = $conn->query("SELECT COUNT(*) FROM applications WHERE candidate_type = 'Delegate' AND status = 'Approved' AND programme = '$programme'")->fetchColumn();
        $votes_sql = "SELECT a.student_id, a.student_name, sd.gender, COUNT(dv.delegate_id) as vote_count
                      FROM applications a
                      LEFT JOIN delegate_votes dv ON a.student_id = dv.delegate_id
                      LEFT JOIN student_details sd ON a.student_id = sd.Student_id
                      WHERE a.candidate_type = 'Delegate' AND a.status = 'Approved' AND a.programme = '$programme'
                      GROUP BY a.student_id, a.student_name, sd.gender
                      ORDER BY vote_count DESC, a.student_id ASC";
        $delegates = $conn->query($votes_sql)->fetchAll(PDO::FETCH_ASSOC);

        if ($approved_count <= 3 && $approved_count > 0) {
            foreach ($delegates as $delegate) {
                $conn->exec("INSERT INTO delegate_winners (delegate_id, student_name, programme, school, gender, vote_count, status)
                             VALUES ('{$delegate['student_id']}', '{$delegate['student_name']}', '$programme', 
                                     (SELECT school FROM student_details WHERE Student_id = '{$delegate['student_id']}'), 
                                     '{$delegate['gender']}', 0, 'unopposed')");
            }
        } elseif ($approved_count > 3 && !empty($delegates[0]['vote_count'])) {
            $winners = [];
            $male_count = 0;
            $female_count = 0;
            $all_same_gender = array_unique(array_column($delegates, 'gender')) === ['M'] || array_unique(array_column($delegates, 'gender')) === ['F'];

            foreach ($delegates as $delegate) {
                if (count($winners) < 3 || ($all_same_gender && count($winners) < 3)) {
                    $winners[] = $delegate;
                    $delegate['gender'] === 'M' ? $male_count++ : $female_count++;
                } elseif (!$all_same_gender && $male_count >= 2 && $delegate['gender'] === 'F') {
                    for ($i = 2; $i >= 0; $i--) {
                        if ($winners[$i]['gender'] === 'M') {
                            array_splice($winners, $i, 1, [$delegate]);
                            $male_count--;
                            $female_count++;
                            break;
                        }
                    }
                } elseif (!$all_same_gender && $female_count >= 2 && $delegate['gender'] === 'M') {
                    for ($i = 2; $i >= 0; $i--) {
                        if ($winners[$i]['gender'] === 'F') {
                            array_splice($winners, $i, 1, [$delegate]);
                            $female_count--;
                            $male_count++;
                            break;
                        }
                    }
                }
            }

            foreach ($winners as $winner) {
                $conn->exec("INSERT INTO delegate_winners (delegate_id, student_name, programme, school, gender, vote_count, status)
                             VALUES ('{$winner['student_id']}', '{$winner['student_name']}', '$programme', 
                                     (SELECT school FROM student_details WHERE Student_id = '{$winner['student_id']}'), 
                                     '{$winner['gender']}', {$winner['vote_count']}, 'elected')");
            }
        }
    }

    updateDelegateWinners($conn, $programme);

    echo "<script>alert('Vote submitted successfully! Thank you for voting.'); window.location.href='elections.php';</script>";
} else {
    header('Location: elections.php');
    exit();
}
?>