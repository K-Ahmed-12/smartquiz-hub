<?php
require_once '../config/config.php';

// Require admin access
requireAdmin();

$action = $_GET['action'] ?? '';
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $db = getDB();
    
    // Handle actions
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if ($action == 'toggle_status' && $quiz_id > 0) {
            $stmt = $db->prepare("UPDATE quizzes SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$quiz_id]);
            showAlert('Quiz status updated successfully.', 'success');
            redirect('quizzes.php');
        }
        
        if ($action == 'delete' && $quiz_id > 0) {
            // Check if quiz has attempts
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM quiz_attempts WHERE quiz_id = ?");
            $stmt->execute([$quiz_id]);
            $attempt_count = $stmt->fetch()['count'];
            
            if ($attempt_count > 0) {
                showAlert('Cannot delete quiz with existing attempts. Deactivate it instead.', 'danger');
            } else {
                $stmt = $db->prepare("DELETE FROM quizzes WHERE id = ?");
                $stmt->execute([$quiz_id]);
                showAlert('Quiz deleted successfully.', 'success');
            }
            redirect('quizzes.php');
        }
    }
    
    // Get filters
    $category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $status_filter = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Build WHERE clause
    $where_conditions = ['1=1'];
    $params = [];
    
    if ($category_filter > 0) {
        $where_conditions[] = 'q.category_id = ?';
        $params[] = $category_filter;
    }
    
    if ($status_filter !== '') {
        $where_conditions[] = 'q.is_active = ?';
        $params[] = $status_filter == 'active' ? 1 : 0;
    }
    
    if (!empty($search)) {
        $where_conditions[] = '(q.title LIKE ? OR q.description LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Get quizzes
    $stmt = $db->prepare("
        SELECT q.*, c.name as category_name, u.name as creator_name,
               COUNT(DISTINCT questions.id) as question_count,
               COUNT(DISTINCT qa.id) as attempt_count,
               AVG(qa.percentage) as avg_score
        FROM quizzes q
        LEFT JOIN categories c ON q.category_id = c.id
        LEFT JOIN users u ON q.created_by = u.id
        LEFT JOIN questions ON q.id = questions.quiz_id
        LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.status = 'completed'
        $where_clause
        GROUP BY q.id
        ORDER BY q.created_at DESC
    ");
    $stmt->execute($params);
    $quizzes = $stmt->fetchAll();
    
    // Get categories for filter
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $quizzes = [];
    $categories = [];
    showAlert('Database error occurred.', 'danger');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes - Admin Panel - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cog me-2"></i>Admin Panel
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="quizzes.php">
                            <i class="fas fa-clipboard-list me-1"></i>Quizzes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="questions.php">
                            <i class="fas fa-question-circle me-1"></i>Questions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-1"></i>Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-tags me-1"></i>Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home me-1"></i>Back to Site
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid py-4">
        <!-- Alert Container -->
        <div class="alert-container">
            <?php displayAlert(); ?>
        </div>
        
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h1 class="h3 mb-0">Manage Quizzes</h1>
                <p class="text-muted">Create, edit, and manage all quizzes</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="quiz-create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create New Quiz
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search quizzes..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quizzes Table -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($quizzes)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No quizzes found</h5>
                        <p class="text-muted">Create your first quiz to get started!</p>
                        <a href="quiz-create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Quiz
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Quiz Details</th>
                                    <th>Category</th>
                                    <th>Questions</th>
                                    <th>Attempts</th>
                                    <th>Avg Score</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quizzes as $quiz): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($quiz['title']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars(substr($quiz['description'], 0, 60)) . (strlen($quiz['description']) > 60 ? '...' : ''); ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i><?php echo $quiz['time_limit']; ?> min
                                                <i class="fas fa-star ms-2 me-1"></i><?php echo $quiz['total_marks']; ?> marks
                                                <span class="badge bg-<?php echo $quiz['difficulty'] == 'easy' ? 'success' : ($quiz['difficulty'] == 'medium' ? 'warning' : 'danger'); ?> ms-2">
                                                    <?php echo ucfirst($quiz['difficulty']); ?>
                                                </span>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($quiz['category_name']); ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?php echo $quiz['question_count']; ?></span>
                                        <?php if ($quiz['question_count'] > 0): ?>
                                            <br><a href="questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="small text-primary">
                                                <i class="fas fa-edit me-1"></i>Manage
                                            </a>
                                        <?php else: ?>
                                            <br><a href="question-create.php?quiz_id=<?php echo $quiz['id']; ?>" class="small text-warning">
                                                <i class="fas fa-plus me-1"></i>Add Questions
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?php echo $quiz['attempt_count']; ?></span>
                                        <?php if ($quiz['attempt_count'] > 0): ?>
                                            <br><a href="quiz-attempts.php?quiz_id=<?php echo $quiz['id']; ?>" class="small text-info">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($quiz['avg_score'] !== null): ?>
                                            <span class="fw-bold <?php echo $quiz['avg_score'] >= 70 ? 'text-success' : ($quiz['avg_score'] >= 50 ? 'text-warning' : 'text-danger'); ?>">
                                                <?php echo number_format($quiz['avg_score'], 1); ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="?action=toggle_status&id=<?php echo $quiz['id']; ?>" class="d-inline">
                                            <button type="submit" class="btn btn-sm btn-outline-<?php echo $quiz['is_active'] ? 'success' : 'secondary'; ?>" 
                                                    onclick="return confirm('Are you sure you want to change the status?')">
                                                <?php if ($quiz['is_active']): ?>
                                                    <i class="fas fa-eye me-1"></i>Active
                                                <?php else: ?>
                                                    <i class="fas fa-eye-slash me-1"></i>Inactive
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($quiz['created_at'])); ?>
                                            <br>by <?php echo htmlspecialchars($quiz['creator_name']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="../take-quiz.php?id=<?php echo $quiz['id']; ?>" 
                                               class="btn btn-sm btn-outline-info" title="Preview Quiz">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="quiz-edit.php?id=<?php echo $quiz['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Edit Quiz">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmDelete(<?php echo $quiz['id']; ?>, '<?php echo htmlspecialchars($quiz['title']); ?>')"
                                                    title="Delete Quiz">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the quiz "<span id="quizTitle"></span>"?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. All questions and attempts will be permanently deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="deleteForm" class="d-inline">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Quiz
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function confirmDelete(quizId, quizTitle) {
            document.getElementById('quizTitle').textContent = quizTitle;
            document.getElementById('deleteForm').action = '?action=delete&id=' + quizId;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
        
        // Auto-submit form on filter change
        document.addEventListener('DOMContentLoaded', function() {
            const filterSelects = document.querySelectorAll('select[name="category"], select[name="status"]');
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    this.form.submit();
                });
            });
        });
    </script>
    
    <style>
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
        
        .btn-group .btn {
            border-radius: 0.375rem;
            margin-right: 0.25rem;
        }
        
        .btn-group .btn:last-child {
            margin-right: 0;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .table-responsive {
            border-radius: 0.5rem;
        }
        
        .badge {
            font-size: 0.75em;
        }
        
        @media (max-width: 768px) {
            .btn-group {
                display: flex;
                flex-direction: column;
                width: 100%;
            }
            
            .btn-group .btn {
                margin-right: 0;
                margin-bottom: 0.25rem;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>
</body>
</html>
