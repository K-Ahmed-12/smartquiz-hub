<?php
require_once '../config/config.php';

// Require admin access
requireAdmin();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $category_id = (int)$_POST['category_id'];
    $difficulty = sanitizeInput($_POST['difficulty']);
    $time_limit = (int)$_POST['time_limit'];
    $allow_retake = isset($_POST['allow_retake']) ? 1 : 0;
    $randomize_questions = isset($_POST['randomize_questions']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Quiz title is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Quiz description is required';
    }
    
    if ($category_id <= 0) {
        $errors[] = 'Please select a category';
    }
    
    if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
        $errors[] = 'Invalid difficulty level';
    }
    
    if ($time_limit <= 0 || $time_limit > 300) {
        $errors[] = 'Time limit must be between 1 and 300 minutes';
    }
    
    // Create quiz if no errors
    if (empty($errors)) {
        try {
            $db = getDB();
            
            $stmt = $db->prepare("
                INSERT INTO quizzes (title, description, category_id, difficulty, time_limit, 
                                   allow_retake, randomize_questions, is_active, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $title, $description, $category_id, $difficulty, $time_limit,
                $allow_retake, $randomize_questions, $is_active, $_SESSION['user_id']
            ]);
            
            $quiz_id = $db->lastInsertId();
            
            showAlert('Quiz created successfully! You can now add questions to it.', 'success');
            redirect("question-create.php?quiz_id=$quiz_id");
            
        } catch(PDOException $e) {
            $errors[] = 'Failed to create quiz. Please try again.';
        }
    }
}

try {
    $db = getDB();
    
    // Get categories
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $categories = [];
    $errors[] = 'Failed to load categories.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz - Admin Panel - <?php echo SITE_NAME; ?></title>
    
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
                <h1 class="h3 mb-0">Create New Quiz</h1>
                <p class="text-muted">Set up a new quiz with basic information</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="quizzes.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Quizzes
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Quiz Creation Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-plus-circle me-2"></i>Quiz Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <!-- Quiz Title -->
                                <div class="col-12">
                                    <label for="title" class="form-label">Quiz Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                           placeholder="Enter quiz title" required>
                                    <div class="invalid-feedback">
                                        Please provide a quiz title.
                                    </div>
                                </div>
                                
                                <!-- Quiz Description -->
                                <div class="col-12">
                                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="description" name="description" rows="4" 
                                              placeholder="Describe what this quiz covers..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    <div class="invalid-feedback">
                                        Please provide a quiz description.
                                    </div>
                                </div>
                                
                                <!-- Category and Difficulty -->
                                <div class="col-md-6">
                                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a category.
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="difficulty" class="form-label">Difficulty Level <span class="text-danger">*</span></label>
                                    <select class="form-select" id="difficulty" name="difficulty" required>
                                        <option value="">Select Difficulty</option>
                                        <option value="easy" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] == 'easy') ? 'selected' : ''; ?>>
                                            Easy
                                        </option>
                                        <option value="medium" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] == 'medium') ? 'selected' : ''; ?>>
                                            Medium
                                        </option>
                                        <option value="hard" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] == 'hard') ? 'selected' : ''; ?>>
                                            Hard
                                        </option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a difficulty level.
                                    </div>
                                </div>
                                
                                <!-- Time Limit -->
                                <div class="col-md-6">
                                    <label for="time_limit" class="form-label">Time Limit (minutes) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="time_limit" name="time_limit" 
                                           value="<?php echo htmlspecialchars($_POST['time_limit'] ?? '30'); ?>" 
                                           min="1" max="300" required>
                                    <div class="form-text">Set the time limit for completing this quiz (1-300 minutes)</div>
                                    <div class="invalid-feedback">
                                        Please provide a valid time limit (1-300 minutes).
                                    </div>
                                </div>
                                
                                <!-- Quiz Settings -->
                                <div class="col-12">
                                    <label class="form-label">Quiz Settings</label>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="allow_retake" name="allow_retake" 
                                                       <?php echo (isset($_POST['allow_retake']) || !isset($_POST['title'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="allow_retake">
                                                    Allow Retakes
                                                </label>
                                                <div class="form-text">Users can take this quiz multiple times</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="randomize_questions" name="randomize_questions"
                                                       <?php echo isset($_POST['randomize_questions']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="randomize_questions">
                                                    Randomize Questions
                                                </label>
                                                <div class="form-text">Questions appear in random order</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                                       <?php echo (isset($_POST['is_active']) || !isset($_POST['title'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_active">
                                                    Active
                                                </label>
                                                <div class="form-text">Quiz is visible to users</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>Create Quiz
                                    </button>
                                    <a href="quizzes.php" class="btn btn-outline-secondary btn-lg ms-2">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Help Card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Next Steps
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="d-flex align-items-start">
                                    <div class="step-number me-3">1</div>
                                    <div>
                                        <h6>Create Quiz</h6>
                                        <p class="text-muted mb-0">Fill out the basic quiz information above</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-start">
                                    <div class="step-number me-3">2</div>
                                    <div>
                                        <h6>Add Questions</h6>
                                        <p class="text-muted mb-0">Create questions with multiple choice, true/false, or short answer formats</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-start">
                                    <div class="step-number me-3">3</div>
                                    <div>
                                        <h6>Publish</h6>
                                        <p class="text-muted mb-0">Activate the quiz to make it available to users</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.needs-validation');
            
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
            
            // Character counter for title and description
            const titleInput = document.getElementById('title');
            const descriptionInput = document.getElementById('description');
            
            function addCharacterCounter(input, maxLength) {
                const counter = document.createElement('div');
                counter.className = 'form-text text-end';
                counter.innerHTML = `<span class="current">0</span>/${maxLength} characters`;
                input.parentNode.appendChild(counter);
                
                input.addEventListener('input', function() {
                    const current = this.value.length;
                    counter.querySelector('.current').textContent = current;
                    
                    if (current > maxLength * 0.9) {
                        counter.classList.add('text-warning');
                    } else {
                        counter.classList.remove('text-warning');
                    }
                    
                    if (current > maxLength) {
                        counter.classList.add('text-danger');
                        counter.classList.remove('text-warning');
                    } else {
                        counter.classList.remove('text-danger');
                    }
                });
                
                // Trigger initial count
                input.dispatchEvent(new Event('input'));
            }
            
            addCharacterCounter(titleInput, 200);
            addCharacterCounter(descriptionInput, 1000);
        });
    </script>
    
    <style>
        .step-number {
            width: 30px;
            height: 30px;
            background: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.875rem;
        }
        
        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .form-switch .form-check-input {
            width: 2em;
            margin-left: -2.5em;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .btn-lg {
            padding: 0.75rem 2rem;
        }
        
        @media (max-width: 768px) {
            .btn-lg {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .btn-lg.ms-2 {
                margin-left: 0 !important;
            }
        }
    </style>
</body>
</html>
