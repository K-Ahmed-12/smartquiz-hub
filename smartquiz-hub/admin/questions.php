<?php
require_once '../config/config.php';

// Require admin access
requireAdmin();

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = getDB();
        
        if (isset($_POST['delete_question'])) {
            $id = (int)$_POST['question_id'];
            
            // Delete question and its options (CASCADE will handle this)
            $stmt = $db->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->execute([$id]);
            
            $success_message = 'Question deleted successfully!';
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$quiz_filter = $_GET['quiz'] ?? '';
$type_filter = $_GET['type'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query conditions
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "q.question_text LIKE ?";
    $params[] = "%$search%";
}

if (!empty($quiz_filter)) {
    $conditions[] = "q.quiz_id = ?";
    $params[] = $quiz_filter;
}

if (!empty($type_filter)) {
    $conditions[] = "q.question_type = ?";
    $params[] = $type_filter;
}

$where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

try {
    $db = getDB();
    
    // Get all quizzes for filter dropdown
    $stmt = $db->prepare("SELECT id, title FROM quizzes ORDER BY title");
    $stmt->execute();
    $quizzes = $stmt->fetchAll();
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM questions q $where_clause";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_questions = $stmt->fetch()['total'];
    $total_pages = ceil($total_questions / $per_page);
    
    // Get questions with pagination
    $sql = "SELECT q.*, 
                   quiz.title as quiz_title,
                   quiz.difficulty as quiz_difficulty,
                   c.name as category_name,
                   COUNT(qo.id) as option_count
            FROM questions q 
            JOIN quizzes quiz ON q.quiz_id = quiz.id
            LEFT JOIN categories c ON quiz.category_id = c.id
            LEFT JOIN question_options qo ON q.id = qo.question_id
            $where_clause 
            GROUP BY q.id 
            ORDER BY q.created_at DESC 
            LIMIT $per_page OFFSET $offset";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $questions = $stmt->fetchAll();
    
    // Get options for each question
    foreach ($questions as &$question) {
        $stmt = $db->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY option_order, id");
        $stmt->execute([$question['id']]);
        $question['options'] = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    $questions = [];
    $quizzes = [];
    $total_questions = 0;
    $total_pages = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - <?php echo SITE_NAME; ?> Admin</title>
    
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
                        <a class="nav-link" href="quizzes.php">
                            <i class="fas fa-clipboard-list me-1"></i>Quizzes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="questions.php">
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
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-question-circle me-3"></i>Manage Questions
                    </h1>
                    <p class="page-subtitle">View and manage quiz questions across all quizzes</p>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search Questions</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search question text..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="quiz" class="form-label">Quiz</label>
                        <select class="form-select" id="quiz" name="quiz">
                            <option value="">All Quizzes</option>
                            <?php foreach ($quizzes as $quiz): ?>
                                <option value="<?php echo $quiz['id']; ?>" <?php echo $quiz_filter == $quiz['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($quiz['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">All Types</option>
                            <option value="multiple_choice" <?php echo $type_filter == 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                            <option value="true_false" <?php echo $type_filter == 'true_false' ? 'selected' : ''; ?>>True/False</option>
                            <option value="short_answer" <?php echo $type_filter == 'short_answer' ? 'selected' : ''; ?>>Short Answer</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <a href="?" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Questions List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Questions</h5>
                <span class="badge bg-primary"><?php echo $total_questions; ?> Total</span>
            </div>
            <div class="card-body">
                <?php if (empty($questions)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No questions found matching your criteria.</p>
                        <a href="quiz-create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Your First Quiz
                        </a>
                    </div>
                <?php else: ?>
                    <div class="questions-list">
                        <?php foreach ($questions as $question): ?>
                            <div class="question-item card mb-3">
                                <div class="card-header">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="mb-1">
                                                <span class="badge bg-<?php echo $question['question_type'] == 'multiple_choice' ? 'primary' : ($question['question_type'] == 'true_false' ? 'success' : 'warning'); ?> me-2">
                                                    <?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?>
                                                </span>
                                                <?php echo htmlspecialchars($question['quiz_title']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-folder me-1"></i><?php echo htmlspecialchars($question['category_name']); ?>
                                                <i class="fas fa-star ms-2 me-1"></i><?php echo $question['marks']; ?> mark<?php echo $question['marks'] > 1 ? 's' : ''; ?>
                                            </small>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteQuestion(<?php echo $question['id']; ?>, '<?php echo htmlspecialchars(substr($question['question_text'], 0, 50)); ?>...')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="question-content">
                                        <p class="question-text mb-3">
                                            <strong>Q:</strong> <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                                        </p>
                                        
                                        <?php if ($question['question_type'] == 'multiple_choice' && !empty($question['options'])): ?>
                                            <div class="options-list">
                                                <strong>Options:</strong>
                                                <ul class="list-unstyled mt-2">
                                                    <?php foreach ($question['options'] as $option): ?>
                                                        <li class="mb-1">
                                                            <span class="badge bg-<?php echo $option['is_correct'] ? 'success' : 'light text-dark'; ?> me-2">
                                                                <?php echo $option['is_correct'] ? '✓' : '○'; ?>
                                                            </span>
                                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php elseif ($question['question_type'] == 'true_false' && !empty($question['options'])): ?>
                                            <div class="options-list">
                                                <strong>Correct Answer:</strong>
                                                <?php foreach ($question['options'] as $option): ?>
                                                    <?php if ($option['is_correct']): ?>
                                                        <span class="badge bg-success ms-2">
                                                            <?php echo $option['option_text']; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php elseif ($question['question_type'] == 'short_answer'): ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                This is a short answer question that requires manual grading.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&quiz=<?php echo urlencode($quiz_filter); ?>&type=<?php echo urlencode($type_filter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Question Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="question_id" id="delete_question_id">
                        <p>Are you sure you want to delete this question?</p>
                        <p class="text-muted"><span id="delete_question_text"></span></p>
                        <p class="text-danger"><strong>This action cannot be undone and will also delete all associated options and user answers.</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_question" class="btn btn-danger">Delete Question</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function deleteQuestion(id, text) {
            document.getElementById('delete_question_id').value = id;
            document.getElementById('delete_question_text').textContent = text;
            
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
    
    <style>
        .page-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: none;
        }
        
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .question-item {
            transition: all 0.3s ease;
        }
        
        .question-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .question-text {
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        .options-list ul li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .options-list ul li:last-child {
            border-bottom: none;
        }
        
        .btn {
            border-radius: 8px;
        }
    </style>
</body>
</html>
