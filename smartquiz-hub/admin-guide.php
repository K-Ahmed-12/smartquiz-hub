<?php
/**
 * Admin Access Guide for SmartQuiz Hub
 * Complete guide on how to access and use admin features
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access Guide - SmartQuiz Hub</title>
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
                        <h1 class="h3 mb-0"><i class="fas fa-user-shield me-2"></i>Admin Access Guide</h1>
                    </div>
                    <div class="card-body">
                        
                        <!-- Step 1: Login Credentials -->
                        <div class="mb-5">
                            <h2 class="h4 text-primary mb-3"><i class="fas fa-key me-2"></i>Step 1: Admin Login Credentials</h2>
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle me-2"></i>Default Admin Account</h5>
                                <p class="mb-2"><strong>Email:</strong> <code>admin@smartquizhub.com</code></p>
                                <p class="mb-2"><strong>Password:</strong> <code>admin123</code></p>
                                <p class="mb-0"><small><i class="fas fa-exclamation-triangle me-1"></i>Remember to change this password after first login for security!</small></p>
                            </div>
                        </div>

                        <!-- Step 2: How to Login -->
                        <div class="mb-5">
                            <h2 class="h4 text-primary mb-3"><i class="fas fa-sign-in-alt me-2"></i>Step 2: How to Login as Admin</h2>
                            <ol class="list-group list-group-numbered">
                                <li class="list-group-item">
                                    <strong>Visit the Login Page:</strong><br>
                                    <a href="login.php" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="fas fa-external-link-alt me-1"></i>http://localhost/smartquiz-hub/login.php
                                    </a>
                                </li>
                                <li class="list-group-item">
                                    <strong>Enter Admin Credentials:</strong><br>
                                    Use the email and password provided above
                                </li>
                                <li class="list-group-item">
                                    <strong>Click Login:</strong><br>
                                    You'll be redirected to the user dashboard
                                </li>
                                <li class="list-group-item">
                                    <strong>Access Admin Panel:</strong><br>
                                    Look for the "Admin Panel" option in your profile dropdown menu
                                </li>
                            </ol>
                        </div>

                        <!-- Step 3: Admin Panel Features -->
                        <div class="mb-5">
                            <h2 class="h4 text-primary mb-3"><i class="fas fa-cogs me-2"></i>Step 3: Admin Panel Features</h2>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h5>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>URL:</strong> <code>/admin/</code></p>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check text-success me-2"></i>View total users</li>
                                                <li><i class="fas fa-check text-success me-2"></i>View total quizzes</li>
                                                <li><i class="fas fa-check text-success me-2"></i>View quiz attempts</li>
                                                <li><i class="fas fa-check text-success me-2"></i>View average scores</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Recent activity</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Quiz Management</h5>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>URL:</strong> <code>/admin/quizzes.php</code></p>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check text-success me-2"></i>View all quizzes</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Edit existing quizzes</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Delete quizzes</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Toggle quiz status</li>
                                                <li><i class="fas fa-check text-success me-2"></i>View quiz statistics</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card border-warning">
                                        <div class="card-header bg-warning text-dark">
                                            <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Create Quiz</h5>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>URL:</strong> <code>/admin/quiz-create.php</code></p>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check text-success me-2"></i>Create new quizzes</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Add multiple questions</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Set quiz categories</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Configure time limits</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Set difficulty levels</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card border-secondary">
                                        <div class="card-header bg-secondary text-white">
                                            <h5 class="mb-0"><i class="fas fa-users me-2"></i>User Management</h5>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Available in Dashboard</strong></p>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check text-success me-2"></i>View user statistics</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Monitor user activity</li>
                                                <li><i class="fas fa-check text-success me-2"></i>View quiz attempts</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Track performance</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Quick Access Links -->
                        <div class="mb-5">
                            <h2 class="h4 text-primary mb-3"><i class="fas fa-rocket me-2"></i>Step 4: Quick Access Links</h2>
                            <div class="d-grid gap-2 d-md-block">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login Page
                                </a>
                                <a href="admin/" class="btn btn-success">
                                    <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                                </a>
                                <a href="admin/quizzes.php" class="btn btn-info">
                                    <i class="fas fa-clipboard-list me-2"></i>Manage Quizzes
                                </a>
                                <a href="admin/quiz-create.php" class="btn btn-warning">
                                    <i class="fas fa-plus-circle me-2"></i>Create Quiz
                                </a>
                            </div>
                        </div>

                        <!-- Step 5: Security Notes -->
                        <div class="mb-5">
                            <h2 class="h4 text-danger mb-3"><i class="fas fa-shield-alt me-2"></i>Security & Best Practices</h2>
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i>Important Security Notes</h5>
                                <ul class="mb-0">
                                    <li><strong>Change Default Password:</strong> Update the admin password immediately after first login</li>
                                    <li><strong>Admin Access Only:</strong> Only users with 'admin' or 'instructor' role can access admin features</li>
                                    <li><strong>Session Security:</strong> Admin sessions expire after 1 hour of inactivity</li>
                                    <li><strong>Protected Routes:</strong> All admin pages require authentication and proper role</li>
                                    <li><strong>Local Development:</strong> This setup is for localhost development only</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Troubleshooting -->
                        <div class="mb-4">
                            <h2 class="h4 text-secondary mb-3"><i class="fas fa-question-circle me-2"></i>Troubleshooting</h2>
                            <div class="accordion" id="troubleshootingAccordion">
                                <div class="accordion-item">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#trouble1">
                                            Can't login with admin credentials
                                        </button>
                                    </h3>
                                    <div id="trouble1" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <ol>
                                                <li>Make sure you've run <code>setup.php</code> to create the database</li>
                                                <li>Check that XAMPP MySQL service is running</li>
                                                <li>Verify the admin user exists in the database</li>
                                                <li>Try running <code>setup.php</code> again (it's safe to run multiple times)</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#trouble2">
                                            Don't see "Admin Panel" option
                                        </button>
                                    </h3>
                                    <div id="trouble2" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <ol>
                                                <li>Make sure you're logged in with the admin account</li>
                                                <li>Check your user role in the database (should be 'admin')</li>
                                                <li>Look in the profile dropdown menu (top right corner)</li>
                                                <li>Try logging out and logging back in</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#trouble3">
                                            Admin pages show "Access Denied"
                                        </button>
                                    </h3>
                                    <div id="trouble3" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <ol>
                                                <li>Ensure you're logged in as admin</li>
                                                <li>Check that your session hasn't expired</li>
                                                <li>Verify the admin user role in the database</li>
                                                <li>Clear browser cookies and try again</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="text-center">
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-home me-2"></i>Back to Homepage
                            </a>
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
