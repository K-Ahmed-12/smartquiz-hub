<?php
/**
 * Multi-Admin System Overview - SmartQuiz Hub
 * Complete guide to multi-admin capabilities and user roles
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Admin System - SmartQuiz Hub</title>
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
                        <h1 class="h3 mb-0"><i class="fas fa-users-cog me-2"></i>Multi-Admin System Overview</h1>
                    </div>
                    <div class="card-body">
                        
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle me-2"></i>Yes! SmartQuiz Hub Supports Multiple Admins</h5>
                            <p class="mb-0">The system is designed with a flexible role-based access control system that supports unlimited admins and instructors with different permission levels.</p>
                        </div>

                        <!-- User Roles Overview -->
                        <div class="row g-4 mb-5">
                            
                            <!-- Admin Role -->
                            <div class="col-md-4">
                                <div class="card border-danger h-100">
                                    <div class="card-header bg-danger text-white text-center">
                                        <i class="fas fa-user-shield fa-2x mb-2"></i>
                                        <h5 class="mb-0">Admin</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Full System Access</strong></p>
                                        
                                        <h6>Permissions:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Create/Edit/Delete Quizzes</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Manage All Questions</li>
                                            <li><i class="fas fa-check text-success me-2"></i>User Management</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Create Other Admins</li>
                                            <li><i class="fas fa-check text-success me-2"></i>System Settings</li>
                                            <li><i class="fas fa-check text-success me-2"></i>View All Reports</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Category Management</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Database Access</li>
                                        </ul>
                                        
                                        <div class="badge bg-danger">Highest Level</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Instructor Role -->
                            <div class="col-md-4">
                                <div class="card border-success h-100">
                                    <div class="card-header bg-success text-white text-center">
                                        <i class="fas fa-chalkboard-teacher fa-2x mb-2"></i>
                                        <h5 class="mb-0">Instructor</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Quiz Management Access</strong></p>
                                        
                                        <h6>Permissions:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Create/Edit Own Quizzes</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Manage Own Questions</li>
                                            <li><i class="fas fa-check text-success me-2"></i>View Quiz Reports</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Access Admin Panel</li>
                                            <li><i class="fas fa-times text-danger me-2"></i>User Management</li>
                                            <li><i class="fas fa-times text-danger me-2"></i>System Settings</li>
                                            <li><i class="fas fa-times text-danger me-2"></i>Delete Other's Quizzes</li>
                                            <li><i class="fas fa-times text-danger me-2"></i>Create Admins</li>
                                        </ul>
                                        
                                        <div class="badge bg-success">Medium Level</div>
                                    </div>
                                </div>
                            </div>

                            <!-- User Role -->
                            <div class="col-md-4">
                                <div class="card border-primary h-100">
                                    <div class="card-header bg-primary text-white text-center">
                                        <i class="fas fa-user fa-2x mb-2"></i>
                                        <h5 class="mb-0">User</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Quiz Taking Access</strong></p>
                                        
                                        <h6>Permissions:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Take Quizzes</li>
                                            <li><i class="fas fa-check text-success me-2"></i>View Own Results</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Dashboard Access</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Profile Management</li>
                                            <li><i class="fas fa-times text-danger me-2"></i>Admin Panel Access</li>
                                            <li><i class="fas fa-times text-danger me-2"></i>Create Quizzes</li>
                                            <li><i class="fas fa-times text-danger me-2"></i>User Management</li>
                                            <li><i class="fas fa-times text-danger me-2"></i>System Settings</li>
                                        </ul>
                                        
                                        <div class="badge bg-primary">Basic Level</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Multi-Admin Features -->
                        <div class="card border-info mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>Multi-Admin Features</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-plus-circle me-1"></i>Admin Creation</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Unlimited admin accounts</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Easy admin creation interface</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Role assignment during creation</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Email-based authentication</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-user-cog me-1"></i>User Management</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>View all users and roles</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Change user roles dynamically</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Activate/deactivate accounts</li>
                                            <li><i class="fas fa-check text-success me-2"></i>User activity monitoring</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-shield-alt me-1"></i>Security Features</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Role-based access control</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Session management</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Password hashing</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Self-protection (can't modify own role)</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-chart-bar me-1"></i>Analytics & Tracking</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Quiz creation tracking</li>
                                            <li><i class="fas fa-check text-success me-2"></i>User performance metrics</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Role distribution statistics</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Activity timestamps</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- How to Add Multiple Admins -->
                        <div class="card border-success mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-plus me-2"></i>How to Add Multiple Admins</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Step-by-Step Process:</h6>
                                        <ol>
                                            <li><strong>Login as Admin:</strong> Use existing admin credentials</li>
                                            <li><strong>Access User Management:</strong> Go to Admin Panel → Users</li>
                                            <li><strong>Click "Add Admin/Instructor":</strong> Blue button in top right</li>
                                            <li><strong>Fill User Details:</strong> Name, email, password</li>
                                            <li><strong>Select Role:</strong> Choose "Admin" or "Instructor"</li>
                                            <li><strong>Create User:</strong> Click "Create User" button</li>
                                            <li><strong>New Admin Ready:</strong> They can now login and access admin features</li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Admin Management Features:</h6>
                                        <ul>
                                            <li><strong>Role Changes:</strong> Convert users to admins anytime</li>
                                            <li><strong>Account Control:</strong> Activate/deactivate admin accounts</li>
                                            <li><strong>Permission Levels:</strong> Different access for admins vs instructors</li>
                                            <li><strong>Self-Protection:</strong> Admins can't accidentally remove their own access</li>
                                            <li><strong>Audit Trail:</strong> Track who created what quizzes</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Current System Status -->
                        <div class="card border-warning mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Current System Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Default Setup:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-user-shield text-primary me-2"></i><strong>1 Admin:</strong> admin@smartquizhub.com</li>
                                            <li><i class="fas fa-key text-secondary me-2"></i><strong>Password:</strong> admin123</li>
                                            <li><i class="fas fa-check text-success me-2"></i><strong>Status:</strong> Ready to create more admins</li>
                                            <li><i class="fas fa-cogs text-info me-2"></i><strong>Features:</strong> All multi-admin features active</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>What You Can Do Now:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-plus text-success me-2"></i>Add unlimited admins</li>
                                            <li><i class="fas fa-plus text-success me-2"></i>Create instructor accounts</li>
                                            <li><i class="fas fa-edit text-primary me-2"></i>Change user roles anytime</li>
                                            <li><i class="fas fa-eye text-info me-2"></i>Monitor all user activity</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Access Links -->
                        <div class="text-center">
                            <h5 class="mb-3">Quick Access</h5>
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-1"></i>Admin Login
                                </a>
                                <a href="admin/user-management.php" class="btn btn-success">
                                    <i class="fas fa-users-cog me-1"></i>User Management
                                </a>
                                <a href="admin/" class="btn btn-info">
                                    <i class="fas fa-tachometer-alt me-1"></i>Admin Dashboard
                                </a>
                                <a href="check-admin.php" class="btn btn-warning">
                                    <i class="fas fa-check-circle me-1"></i>Check Admin Access
                                </a>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-home me-1"></i>Homepage
                                </a>
                            </div>
                        </div>

                        <!-- Technical Details -->
                        <div class="mt-5">
                            <h5>Technical Implementation</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Database Structure:</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-database me-2"></i>Users table with role field</li>
                                        <li><i class="fas fa-code me-2"></i>ENUM('user', 'admin', 'instructor')</li>
                                        <li><i class="fas fa-shield-alt me-2"></i>Role-based function checks</li>
                                        <li><i class="fas fa-lock me-2"></i>Session-based authentication</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Security Features:</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-key me-2"></i>Password hashing (bcrypt)</li>
                                        <li><i class="fas fa-clock me-2"></i>Session timeout protection</li>
                                        <li><i class="fas fa-ban me-2"></i>Self-modification prevention</li>
                                        <li><i class="fas fa-eye me-2"></i>Activity logging</li>
                                    </ul>
                                </div>
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
