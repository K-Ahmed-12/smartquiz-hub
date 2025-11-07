<?php
/**
 * Demo Credentials Access Page - Secret URL
 * Access: /demo-access.php?key=smartquiz2024demo
 */

require_once 'config/config.php';

// Secret key for demo access
$secret_key = 'smartquiz2024demo';
$provided_key = $_GET['key'] ?? '';

// Check if correct key is provided
if ($provided_key !== $secret_key) {
    // Redirect to homepage if wrong key or no key
    header('Location: index.php');
    exit;
}

// Demo credentials
$demo_accounts = [
    'admin' => [
        'email' => 'admin@demo.com',
        'password' => 'admin123',
        'role' => 'Administrator',
        'description' => 'Full access to admin panel, can manage users, quizzes, categories, and settings'
    ],
    'instructor' => [
        'email' => 'instructor@demo.com', 
        'password' => 'instructor123',
        'role' => 'Instructor',
        'description' => 'Can create and manage quizzes, view reports, but limited admin access'
    ],
    'student' => [
        'email' => 'student@demo.com',
        'password' => 'student123', 
        'role' => 'Student',
        'description' => 'Can take quizzes, view results, and access leaderboard'
    ]
];

// Get site info
$site_name = getSetting('site_name', 'SmartQuiz Hub');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Credentials - <?php echo $site_name; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .demo-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .demo-header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        
        .demo-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .demo-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .credentials-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .credentials-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        
        .role-badge {
            font-size: 0.9rem;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .role-admin { background: #dc3545; color: white; }
        .role-instructor { background: #fd7e14; color: white; }
        .role-student { background: #198754; color: white; }
        
        .credential-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #007bff;
        }
        
        .credential-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .credential-value {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            color: #212529;
            background: white;
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            user-select: all;
        }
        
        .copy-btn {
            background: #007bff;
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            margin-left: 10px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .copy-btn:hover {
            background: #0056b3;
        }
        
        .quick-login {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-btn {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.4);
            color: white;
            text-decoration: none;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin: 30px 0;
            text-align: center;
        }
        
        .warning-box h5 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .warning-box p {
            color: #856404;
            margin: 0;
        }
        
        .features-list {
            background: #e7f3ff;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
        }
        
        .features-list h6 {
            color: #0066cc;
            margin-bottom: 10px;
        }
        
        .features-list ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .features-list li {
            color: #495057;
            margin: 5px 0;
        }
        
        .back-link {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            text-decoration: none;
            backdrop-filter: blur(10px);
            transition: background 0.3s ease;
        }
        
        .back-link:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-link">
        <i class="fas fa-arrow-left me-2"></i>Back to Site
    </a>
    
    <div class="demo-container">
        <div class="demo-header">
            <h1><i class="fas fa-key me-3"></i>Demo Credentials</h1>
            <p>Test accounts for exploring <?php echo $site_name; ?> features</p>
        </div>
        
        <div class="warning-box">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Demo Environment</h5>
            <p>These are test accounts for demonstration purposes only. Data may be reset periodically.</p>
        </div>
        
        <?php foreach ($demo_accounts as $type => $account): ?>
        <div class="credentials-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="role-badge role-<?php echo $type; ?>">
                        <i class="fas fa-<?php echo $type == 'admin' ? 'crown' : ($type == 'instructor' ? 'chalkboard-teacher' : 'user-graduate'); ?> me-2"></i>
                        <?php echo $account['role']; ?>
                    </span>
                </div>
                <div class="quick-login">
                    <a href="login.php" class="login-btn">
                        <i class="fas fa-sign-in-alt me-2"></i>Login Page
                    </a>
                </div>
            </div>
            
            <p class="text-muted mb-3"><?php echo $account['description']; ?></p>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="credential-item">
                        <div class="credential-label">Email Address</div>
                        <div class="d-flex align-items-center">
                            <div class="credential-value flex-grow-1" id="email-<?php echo $type; ?>">
                                <?php echo $account['email']; ?>
                            </div>
                            <button class="copy-btn" onclick="copyToClipboard('email-<?php echo $type; ?>')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="credential-item">
                        <div class="credential-label">Password</div>
                        <div class="d-flex align-items-center">
                            <div class="credential-value flex-grow-1" id="password-<?php echo $type; ?>">
                                <?php echo $account['password']; ?>
                            </div>
                            <button class="copy-btn" onclick="copyToClipboard('password-<?php echo $type; ?>')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($type == 'admin'): ?>
            <div class="features-list">
                <h6><i class="fas fa-star me-2"></i>Admin Features</h6>
                <ul>
                    <li>Full admin panel access</li>
                    <li>User management</li>
                    <li>Quiz and question creation</li>
                    <li>Category management</li>
                    <li>Website settings configuration</li>
                    <li>Reports and analytics</li>
                </ul>
            </div>
            <?php elseif ($type == 'instructor'): ?>
            <div class="features-list">
                <h6><i class="fas fa-chalkboard me-2"></i>Instructor Features</h6>
                <ul>
                    <li>Create and manage quizzes</li>
                    <li>Add questions to quizzes</li>
                    <li>View quiz reports</li>
                    <li>Monitor student progress</li>
                    <li>Limited admin access</li>
                </ul>
            </div>
            <?php else: ?>
            <div class="features-list">
                <h6><i class="fas fa-graduation-cap me-2"></i>Student Features</h6>
                <ul>
                    <li>Take available quizzes</li>
                    <li>View quiz results and history</li>
                    <li>Access leaderboard</li>
                    <li>Track personal progress</li>
                    <li>Update profile information</li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <div class="credentials-card">
            <div class="text-center">
                <h5><i class="fas fa-info-circle me-2"></i>Getting Started</h5>
                <p class="text-muted mb-4">Choose an account type above and use the credentials to log in</p>
                
                <div class="row text-center">
                    <div class="col-md-4 mb-3">
                        <a href="login.php" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="quizzes.php" class="btn btn-outline-primary btn-lg w-100">
                            <i class="fas fa-list me-2"></i>Browse Quizzes
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="leaderboard.php" class="btn btn-outline-primary btn-lg w-100">
                            <i class="fas fa-trophy me-2"></i>Leaderboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-white-50">
                <i class="fas fa-lock me-1"></i>
                This page is only accessible via the secret URL
            </small>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Copy to clipboard function
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent.trim();
            
            // Create temporary textarea
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            
            // Select and copy
            textarea.select();
            document.execCommand('copy');
            
            // Remove temporary element
            document.body.removeChild(textarea);
            
            // Show feedback
            const button = element.nextElementSibling;
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.style.background = '#28a745';
            
            setTimeout(() => {
                button.innerHTML = originalContent;
                button.style.background = '#007bff';
            }, 1500);
        }
        
        // Auto-hide page after 10 minutes for security
        setTimeout(() => {
            if (confirm('For security reasons, this page will redirect to the homepage. Continue viewing?')) {
                // Reset timer
                setTimeout(arguments.callee, 600000); // 10 more minutes
            } else {
                window.location.href = 'index.php';
            }
        }, 600000); // 10 minutes
    </script>
</body>
</html>
