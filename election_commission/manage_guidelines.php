<?php
session_name('election_commission');
session_start();
if (!isset($_SESSION['commission_username'])) {
    header('Location: index.php');
    exit();
}
include 'includes/db_connection.php';

// Add Guideline
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_guideline'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $type = $_POST['type'];

    $sql = "INSERT INTO election_guidelines (title, description, type) VALUES (:title, :description, :type)";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['title' => $title, 'description' => $description, 'type' => $type]);
}

// Delete Guideline
if (isset($_GET['delete'])) {
    $guideline_id = $_GET['delete'];
    $sql = "DELETE FROM election_guidelines WHERE guideline_id = :guideline_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['guideline_id' => $guideline_id]);
    header('Location: manage_guidelines.php');
    exit();
}

// Fetch Guidelines
$sql = "SELECT * FROM election_guidelines ORDER BY guideline_id DESC";
$stmt = $conn->query($sql);
$guidelines = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/new_layout.php';
?>

<!-- Page Content -->
<div class="container-fluid">
        <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Add New Guideline</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Type</label>
                                <select class="form-control" id="type" name="type" required>
                                    <option value="Announcement">Announcement</option>
                                    <option value="Instruction">Instruction</option>
                                    <option value="Eligibility">Eligibility</option>
                                </select>
                            </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="add_guideline" class="btn btn-primary">
                            <span class="icon icon-book"></span> Add Guideline
                        </button>
                        </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Guidelines List</h5>
                    <div class="header-actions">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search guidelines...">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <span class="icon icon-book"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="guidelinesTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guidelines as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                switch($row['type']) {
                                                    case 'Announcement':
                                                        echo 'bg-info';
                                                        break;
                                                    case 'Instruction':
                                                        echo 'bg-primary';
                                                        break;
                                                    case 'Eligibility':
                                                        echo 'bg-success';
                                                        break;
                                                    default:
                                                        echo 'bg-secondary';
                                                }
                                            ?>">
                                                <?php echo htmlspecialchars($row['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit_guideline.php?id=<?php echo $row['guideline_id']; ?>" 
                                               class="btn btn-sm btn-warning">
                                                <span class="icon">✏️</span> Edit
                                            </a>
                                            <a href="manage_guidelines.php?delete=<?php echo $row['guideline_id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this guideline?')">
                                                <span class="icon">🗑️</span> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .badge {
        padding: 6px 12px;
        font-weight: 500;
        font-size: 12px;
    }
    
    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .btn-sm {
        padding: 0.25rem 0.75rem;
        margin-right: 0.25rem;
    }
    
    .input-group {
        width: 300px;
    }
    
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .card-header {
        border-bottom: 1px solid #eee;
        padding: 1rem;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }

    textarea.form-control {
        min-height: 100px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('guidelinesTable');
    const rows = table.getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function(e) {
        const searchText = e.target.value.toLowerCase();

        Array.from(rows).forEach(function(row) {
            if(row.getElementsByTagName('td').length > 0) {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            }
        });
    });
});
</script>

<?php include 'includes/new_footer.php'; ?>