<?php
require_once '../config/config.php';

// Require admin access
requireAdmin();

$errors = [];
$success = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'create_admin') {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $role = sanitizeInput($_POST['role']);
        
        // Validation
        if (empty($name)) {
            $errors[] = 'Name is required';
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required';
        }
        
        if (empty($password) || strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        if (!in_array($role, ['admin', 'instructor'])) {
            $errors[] = 'Invalid role selected';
        }
        
        if (empty($errors)) {
            try {
                $db = getDB();
                
                // Check if email already exists
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $errors[] = 'Email already exists';
                } else {
                    // Create new admin/instructor
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("
                        INSERT INTO users (name, email, password, role, is_active, email_verified) 
                        VALUES (?, ?, ?, ?, 1, 1)
                    ");
                    
                    if ($stmt->execute([$name, $email, $hashedPassword, $role])) {
                        showAlert("$role created successfully!", 'success');
                        redirect('user-management.php');
                    } else {
                        $errors[] = 'Failed to create user';
                    }
                }
            } catch(PDOException $e) {
                $errors[] = 'Database error occurred';
            }
        }
    }
    
    if ($action == 'toggle_status') {
        $user_id = (int)$_POST['user_id'];
        $new_status = (int)$_POST['new_status'];
        
        try {
            $db = getDB();
            $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ? AND id != ?");
            $stmt->execute([$new_status, $user_id, $_SESSION['user_id']]);
            
            $status_text = $new_status ? 'activated' : 'deactivated';
            showAlert("User $status_text successfully!", 'success');
            redirect('user-management.php');
        } catch(PDOException $e) {
            $errors[] = 'Failed to update user status';
        }
    }
    
    if ($action == 'change_role') {
        $user_id = (int)$_POST['user_id'];
        $new_role = sanitizeInput($_POST['new_role']);
        
        if (in_array($new_role, ['user', 'admin', 'instructor'])) {
            try {
                $db = getDB();
                $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ? AND id != ?");
                $stmt->execute([$new_role, $user_id, $_SESSION['user_id']]);
                
                showAlert("User role updated successfully!", 'success');
                redirect('user-management.php');
            } catch(PDOException $e) {
                $errors[] = 'Failed to update user role';
            }
        } else {
            $errors[] = 'Invalid role selected';
        }
    }
}

// Get all users
try {
    $db = getDB();
    
    // Get users with statistics
    $stmt = $db->prepare("
        SELECT u.*, 
               COUNT(DISTINCT q.id) as quizzes_created,
               COUNT(DISTINCT qa.id) as quiz_attempts,
               AVG(qa.percentage) as avg_score
        FROM users u
        LEFT JOIN quizzes q ON u.id = q.created_by
        LEFT JOIN quiz_attempts qa ON u.id = qa.user_id AND qa.status = 'completed'
        GROUP BY u.id
        ORDER BY u.role DESC, u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    // Get role counts
    $stmt = $db->prepare("
        SELECT role, COUNT(*) as count 
        FROM users 
        WHERE is_active = 1 
        GROUP BY role
    ");
    $stmt->execute();
    $role_counts = [];
    while ($row = $stmt->fetch()) {
        $role_counts[$row['role']] = $row['count'];
    }
    
} catch(PDOException $e) {
    $users = [];
    $role_counts = [];
    $errors[] = 'Failed to load users';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel - <?php echo SITE_NAME; ?></title>
    
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
                        <a class="nav-link active" href="user-management.php">
                            <i class="fas fa-users me-1"></i>Users
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../dashboard.php">User Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0">User Management</h1>
                        <p class="text-muted mb-0">Manage users, admins, and instructors</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                            <i class="fas fa-user-plus me-1"></i>Add Admin/Instructor
                        </button>
                    </div>
                </div>

                <!-- Display alerts -->
                <?php displayAlert(); ?>

                <!-- Display errors -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Role Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Admins</h5>
                                        <h2 class="mb-0"><?php echo $role_counts['admin'] ?? 0; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user-shield fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Instructors</h5>
                                        <h2 class="mb-0"><?php echo $role_counts['instructor'] ?? 0; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chalkboard-teacher fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Users</h5>
                                        <h2 class="mb-0"><?php echo $role_counts['user'] ?? 0; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Users</h5>
                                        <h2 class="mb-0"><?php echo count($users); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user-friends fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>All Users
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Quizzes Created</th>
                                        <th>Quiz Attempts</th>
                                        <th>Avg Score</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($user['name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $roleClass = [
                                                    'admin' => 'bg-danger',
                                                    'instructor' => 'bg-success',
                                                    'user' => 'bg-primary'
                                                ];
                                                $roleIcon = [
                                                    'admin' => 'fa-user-shield',
                                                    'instructor' => 'fa-chalkboard-teacher',
                                                    'user' => 'fa-user'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $roleClass[$user['role']]; ?>">
                                                    <i class="fas <?php echo $roleIcon[$user['role']]; ?> me-1"></i>
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $user['quizzes_created'] ?? 0; ?></td>
                                            <td><?php echo $user['quiz_attempts'] ?? 0; ?></td>
                                            <td>
                                                <?php if ($user['avg_score']): ?>
                                                    <?php echo number_format($user['avg_score'], 1); ?>%
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <div class="btn-group btn-group-sm">
                                                        <!-- Role Change -->
                                                        <button class="btn btn-outline-primary btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#changeRoleModal"
                                                                data-user-id="<?php echo $user['id']; ?>"
                                                                data-user-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                                data-current-role="<?php echo $user['role']; ?>">
                                                            <i class="fas fa-user-cog"></i>
                                                        </button>
                                                        
                                                        <!-- Status Toggle -->
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <input type="hidden" name="new_status" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                                                            <button type="submit" class="btn btn-outline-<?php echo $user['is_active'] ? 'warning' : 'success'; ?> btn-sm"
                                                                    onclick="return confirm('Are you sure?')">
                                                                <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-info">You</span>
                                                <?php endif; ?>
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

    <!-- Create Admin Modal -->
    <div class="modal fade" id="createAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Admin/Instructor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_admin">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <small class="form-text text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select role...</option>
                                <option value="admin">Admin (Full access)</option>
                                <option value="instructor">Instructor (Quiz management)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Role Modal -->
    <div class="modal fade" id="changeRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Change User Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="change_role">
                        <input type="hidden" name="user_id" id="change_role_user_id">
                        
                        <p>Change role for: <strong id="change_role_user_name"></strong></p>
                        
                        <div class="mb-3">
                            <label for="new_role" class="form-label">New Role</label>
                            <select class="form-select" id="new_role" name="new_role" required>
                                <option value="user">User (Regular user)</option>
                                <option value="instructor">Instructor (Quiz management)</option>
                                <option value="admin">Admin (Full access)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Change Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Handle change role modal
        document.addEventListener('DOMContentLoaded', function() {
            const changeRoleModal = document.getElementById('changeRoleModal');
            changeRoleModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-user-id');
                const userName = button.getAttribute('data-user-name');
                const currentRole = button.getAttribute('data-current-role');
                
                document.getElementById('change_role_user_id').value = userId;
                document.getElementById('change_role_user_name').textContent = userName;
                document.getElementById('new_role').value = currentRole;
            });
        });
    </script>
</body>
</html>
