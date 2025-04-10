<?php
// elections/faq.php
include '../includes/db_connection.php';

// Add this SQL query at the beginning of the file, after the database connection
$additional_faqs_sql = "INSERT INTO election_faqs (category, question, answer, question_order) VALUES
('General', 'What is the purpose of the Student Council?', 'The Student Council serves as the voice of the student body, representing student interests, organizing events, and working with university administration to improve student life and academic experience.', 3),
('General', 'How often are elections held?', 'Elections are typically held annually, with delegate elections followed by Student Council elections. The exact dates are announced through official university channels.', 4),
('General', 'What is the difference between a Delegate and a Student Council member?', 'Delegates represent their specific programme and vote in Student Council elections. Student Council members are elected to specific positions and handle various aspects of student governance.', 5),
('Voting Process', 'What happens if I miss the voting period?', 'If you miss the voting period, you will not be able to vote until the next election cycle. It is important to check the election timeline and vote within the specified period.', 3),
('Voting Process', 'How is my vote kept secure?', 'The system uses secure authentication and encryption to protect your vote. Each student can only vote once, and votes are stored securely in the database with proper access controls.', 4),
('Voting Process', 'Can I see who voted for whom?', 'No, the voting system is designed to be anonymous. While the system tracks who has voted, it does not record which candidate received which vote.', 5),
('Candidacy', 'What are the eligibility criteria for running as a candidate?', 'Candidates must be currently enrolled students with a minimum GPA, no disciplinary records, and must meet attendance requirements. Specific criteria may vary by position.', 3),
('Candidacy', 'How long is the campaign period?', 'The campaign period typically lasts for two weeks before the election. Candidates must follow campaign guidelines and university policies during this period.', 4),
('Candidacy', 'What documents do I need to submit with my application?', 'Required documents include a completed application form, academic transcript, recommendation letter, and a statement of purpose. Additional documents may be required for specific positions.', 5),
('Results', 'How are ties handled in elections?', 'In case of a tie, a runoff election is held between the tied candidates. If the tie persists, the position may be decided by the current Student Council or through a coin toss.', 3),
('Results', 'When can I file an election complaint?', 'Election complaints must be filed within 24 hours of the results announcement. Complaints should be submitted to the Election Commission with supporting evidence.', 4),
('Results', 'What happens if a winner is disqualified?', 'If a winner is disqualified, the position goes to the candidate with the next highest number of votes. If no other candidates are available, a special election may be called.', 5),
('Technical', 'What should I do if I encounter technical issues while voting?', 'If you experience technical issues, try refreshing the page or using a different browser. If problems persist, contact the IT support team or the Election Commission immediately.', 1),
('Technical', 'How do I reset my password if I forget it?', 'You can reset your password through the \"Forgot Password\" link on the login page. You will need to verify your identity through your registered email.', 2),
('Technical', 'Can I vote using my mobile device?', 'Yes, the election system is mobile-responsive and can be accessed through any device with an internet connection and a web browser.', 3),
('Election Timeline', 'What is the typical election timeline?', 'The election process typically follows this timeline: 1) Announcement of elections (2 weeks before), 2) Candidate registration (1 week), 3) Campaign period (2 weeks), 4) Voting period (1 week), 5) Results announcement (within 48 hours of voting end).', 1),
('Election Timeline', 'When are the next elections scheduled?', 'The exact dates for upcoming elections are announced through the university portal and official channels. Students are advised to regularly check these sources for updates.', 2),
('Election Timeline', 'How long is the voting period?', 'The voting period typically lasts for one week, during which eligible voters can cast their ballots at any time through the online system.', 3),
('Campaign Guidelines', 'What are the campaign rules?', 'Campaign rules include: no negative campaigning, no use of university resources, no harassment, and adherence to social media guidelines. Specific rules are provided in the election guidelines.', 1),
('Campaign Guidelines', 'Can candidates use social media for campaigning?', 'Yes, candidates can use social media for campaigning, but they must follow university guidelines and maintain professional conduct. All campaign materials must be approved by the Election Commission.', 2),
('Campaign Guidelines', 'What campaign materials are allowed?', 'Allowed materials include posters, flyers, and digital content that comply with university guidelines. All materials must be approved before distribution.', 3)";

try {
    $conn->exec($additional_faqs_sql);
} catch(PDOException $e) {
    // If the FAQs already exist, we'll just continue with displaying them
    // No need to show error as this is expected behavior
}

// Fetch FAQs from database
$faq_sql = "SELECT * FROM election_faqs ORDER BY category, question_order";
$faq_result = $conn->query($faq_sql);
$faqs = $faq_result->fetchAll(PDO::FETCH_ASSOC);

// Group FAQs by category
$faq_categories = [];
foreach ($faqs as $faq) {
    $faq_categories[$faq['category']][] = $faq;
}
?>

<div class="mt-4">
    <h3 class="text-primary mb-4 text-center" style="font-weight: bold; text-shadow: 1px 1px 2px rgba(0,0,0,0.1);">
        Frequently Asked Questions
    </h3>

    <div class="row">
        <div class="col-md-3">
            <!-- Category Navigation -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Categories</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($faq_categories as $category => $items): ?>
                        <a href="#<?php echo strtolower(str_replace(' ', '-', $category)); ?>" 
                           class="list-group-item list-group-item-action">
                            <?php echo htmlspecialchars($category); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <!-- FAQ Content -->
            <?php foreach ($faq_categories as $category => $items): ?>
                <div class="card shadow-sm mb-4" id="<?php echo strtolower(str_replace(' ', '-', $category)); ?>">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><?php echo htmlspecialchars($category); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="faqAccordion<?php echo str_replace(' ', '', $category); ?>">
                            <?php foreach ($items as $index => $faq): ?>
                                <div class="card border-0 mb-2">
                                    <div class="card-header bg-light" id="heading<?php echo $index; ?>">
                                        <h2 class="mb-0">
                                            <button class="btn btn-link btn-block text-left" 
                                                    type="button" 
                                                    data-toggle="collapse" 
                                                    data-target="#collapse<?php echo $index; ?>" 
                                                    aria-expanded="false" 
                                                    aria-controls="collapse<?php echo $index; ?>">
                                                <?php echo htmlspecialchars($faq['question']); ?>
                                            </button>
                                        </h2>
                                    </div>
                                    <div id="collapse<?php echo $index; ?>" 
                                         class="collapse" 
                                         aria-labelledby="heading<?php echo $index; ?>" 
                                         data-parent="#faqAccordion<?php echo str_replace(' ', '', $category); ?>">
                                        <div class="card-body">
                                            <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
    .accordion .btn-link {
        color: #2c3e50;
        text-decoration: none;
        font-weight: 500;
        width: 100%;
        text-align: left;
        padding: 10px 15px;
    }

    .accordion .btn-link:hover {
        color: #007bff;
        text-decoration: none;
    }

    .accordion .card-header {
        background: #f8f9fa;
        border: none;
        border-radius: 5px;
        margin-bottom: 5px;
    }

    .accordion .card-body {
        background: #fff;
        border-radius: 0 0 5px 5px;
        padding: 15px 20px;
    }

    .list-group-item {
        border: none;
        padding: 10px 15px;
        color: #2c3e50;
        transition: all 0.3s ease;
    }

    .list-group-item:hover {
        background: #e9ecef;
        color: #007bff;
    }

    .list-group-item.active {
        background: #007bff;
        color: #fff;
    }

    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .card-header {
        border-radius: 5px 5px 0 0;
    }
</style> 