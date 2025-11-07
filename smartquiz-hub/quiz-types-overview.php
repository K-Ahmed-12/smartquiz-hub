<?php
/**
 * Quiz Question Types Overview - SmartQuiz Hub
 * Complete guide to all available question types and features
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Question Types - SmartQuiz Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h3 mb-0"><i class="fas fa-clipboard-list me-2"></i>Quiz Question Types Overview</h1>
                    </div>
                    <div class="card-body">
                        
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>SmartQuiz Hub Question System</h5>
                            <p class="mb-0">Our quiz system supports <strong>4 different question types</strong> to create engaging and comprehensive assessments. Each type offers unique features for different learning objectives.</p>
                        </div>

                        <!-- Question Types Grid -->
                        <div class="row g-4 mb-5">
                            
                            <!-- Multiple Choice -->
                            <div class="col-md-6">
                                <div class="card border-primary h-100">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="fas fa-list-ul me-2"></i>Multiple Choice</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Description:</strong> Questions with multiple answer options where students select one or more correct answers.</p>
                                        
                                        <h6>Features:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>2-10 answer options</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Single or multiple correct answers</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Randomizable option order</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Automatic scoring</li>
                                        </ul>
                                        
                                        <div class="example-box bg-light p-3 rounded mt-3">
                                            <h6>Example:</h6>
                                            <p class="mb-2"><strong>Q:</strong> Which of the following are programming languages?</p>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" checked disabled>
                                                <label class="form-check-label">JavaScript ✓</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" disabled>
                                                <label class="form-check-label">HTML</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" checked disabled>
                                                <label class="form-check-label">Python ✓</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" disabled>
                                                <label class="form-check-label">CSS</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- True/False -->
                            <div class="col-md-6">
                                <div class="card border-success h-100">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>True/False</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Description:</strong> Simple binary choice questions where students determine if a statement is true or false.</p>
                                        
                                        <h6>Features:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Quick to answer</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Perfect for fact checking</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Automatic scoring</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Great for large question sets</li>
                                        </ul>
                                        
                                        <div class="example-box bg-light p-3 rounded mt-3">
                                            <h6>Example:</h6>
                                            <p class="mb-2"><strong>Q:</strong> The Earth is the third planet from the Sun.</p>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="tf1" checked disabled>
                                                <label class="form-check-label">True ✓</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="tf1" disabled>
                                                <label class="form-check-label">False</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Short Answer -->
                            <div class="col-md-6">
                                <div class="card border-warning h-100">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Short Answer</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Description:</strong> Open-ended questions where students type their answer in a text field.</p>
                                        
                                        <h6>Features:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Free-form text input</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Case-insensitive matching</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Multiple acceptable answers</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Encourages critical thinking</li>
                                        </ul>
                                        
                                        <div class="example-box bg-light p-3 rounded mt-3">
                                            <h6>Example:</h6>
                                            <p class="mb-2"><strong>Q:</strong> What is the capital city of France?</p>
                                            <input type="text" class="form-control" value="Paris" disabled>
                                            <small class="text-muted">Correct answers: Paris, paris, PARIS</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fill in the Blank -->
                            <div class="col-md-6">
                                <div class="card border-info h-100">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0"><i class="fas fa-pen me-2"></i>Fill in the Blank</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Description:</strong> Questions with missing words or phrases that students need to complete.</p>
                                        
                                        <h6>Features:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Context-based learning</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Tests specific knowledge</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Visual blank indicators</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Flexible answer matching</li>
                                        </ul>
                                        
                                        <div class="example-box bg-light p-3 rounded mt-3">
                                            <h6>Example:</h6>
                                            <p class="mb-2"><strong>Q:</strong> The _____ is the largest ocean on Earth.</p>
                                            <input type="text" class="form-control" value="Pacific Ocean" disabled>
                                            <small class="text-muted">Correct answers: Pacific, Pacific Ocean</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Features -->
                        <div class="card border-secondary mb-4">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Advanced Quiz Features</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-star me-1"></i>Scoring & Grading</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Custom points per question</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Automatic percentage calculation</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Instant result display</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Detailed answer review</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-clock me-1"></i>Time Management</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Customizable time limits</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Auto-save progress</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Time remaining indicator</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Auto-submit on timeout</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-random me-1"></i>Randomization</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Shuffle question order</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Randomize answer options</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Prevent cheating</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Fair assessment</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-redo me-1"></i>Retake Options</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Allow/disable retakes</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Track attempt history</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Best score tracking</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Progress monitoring</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- How to Create Questions -->
                        <div class="card border-primary mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>How to Create Questions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>For Admins:</h6>
                                        <ol>
                                            <li>Login to admin panel</li>
                                            <li>Go to "Create Quiz" or "Manage Quizzes"</li>
                                            <li>Create a new quiz or edit existing one</li>
                                            <li>Add questions using the question creator</li>
                                            <li>Select question type and configure options</li>
                                            <li>Set correct answers and point values</li>
                                            <li>Save and publish the quiz</li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Best Practices:</h6>
                                        <ul>
                                            <li><strong>Clear Questions:</strong> Write concise, unambiguous questions</li>
                                            <li><strong>Balanced Difficulty:</strong> Mix easy, medium, and hard questions</li>
                                            <li><strong>Varied Types:</strong> Use different question types for engagement</li>
                                            <li><strong>Fair Scoring:</strong> Assign appropriate points based on difficulty</li>
                                            <li><strong>Review Answers:</strong> Provide explanations for learning</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Links -->
                        <div class="text-center">
                            <h5 class="mb-3">Ready to Get Started?</h5>
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-1"></i>Admin Login
                                </a>
                                <a href="admin/quiz-create.php" class="btn btn-success">
                                    <i class="fas fa-plus-circle me-1"></i>Create Quiz
                                </a>
                                <a href="quizzes.php" class="btn btn-info">
                                    <i class="fas fa-list me-1"></i>Browse Quizzes
                                </a>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-home me-1"></i>Homepage
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
