<?php
/**
 * Security Information and Demo Access Guide
 */

require_once 'config/config.php';
requireAdmin();

$site_name = getSetting('site_name', 'SmartQuiz Hub');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security & Demo Access - <?php echo $site_name; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background: #f8f9fa; }
        .security-container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .security-card { 
            background: white; 
            border-radius: 10px; 
            padding: 30px; 
            margin-bottom: 25px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .security-card h3 { color: #007bff; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
        .alert-security { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .secret-url { 
            background: #f8f9fa; 
            border: 2px dashed #007bff; 
            border-radius: 10px; 
            padding: 20px; 
            text-align: center; 
            margin: 20px 0; 
        }
        .secret-url code { 
            background: #e9ecef; 
            padding: 10px 15px; 
            border-radius: 5px; 
            font-size: 1.1rem; 
            color: #495057; 
        }
        .copy-btn { 
            background: #007bff; 
            border: none; 
            color: white; 
            padding: 8px 15px; 
            border-radius: 5px; 
            margin-left: 10px; 
            cursor: pointer; 
        }
        .status-good { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-danger { color: #dc3545; }
    </style>
</head>
<body>
    <div class="security-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-shield-alt me-3"></i>Security & Demo Access</h1>
            <a href="admin/index.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Admin
            </a>
        </div>
        
        <!-- Security Status -->
        <div class="security-card">
            <h3><i class="fas fa-lock me-2"></i>Security Status</h3>
            
            <div class="row">
                <div class="col-md-6">
                    <h5>✅ Security Improvements Applied</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check status-good me-2"></i>Demo credentials removed from login page</li>
                        <li><i class="fas fa-check status-good me-2"></i>Secret URL system implemented</li>
                        <li><i class="fas fa-check status-good me-2"></i>Auto-timeout on demo page (10 minutes)</li>
                        <li><i class="fas fa-check status-good me-2"></i>Copy-to-clipboard functionality</li>
                        <li><i class="fas fa-check status-good me-2"></i>Access key protection</li>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h5>⚠️ Setup Files (Keep Secure)</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-exclamation-triangle status-warning me-2"></i>setup.php - Contains setup credentials</li>
                        <li><i class="fas fa-exclamation-triangle status-warning me-2"></i>setup-database.php - Database setup</li>
                        <li><i class="fas fa-exclamation-triangle status-warning me-2"></i>reset-admin-password.php - Password reset</li>
                        <li><i class="fas fa-info-circle text-info me-2"></i>These are for development only</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Demo Access System -->
        <div class="security-card">
            <h3><i class="fas fa-key me-2"></i>Demo Access System</h3>
            
            <div class="alert alert-security">
                <h5><i class="fas fa-info-circle me-2"></i>How It Works</h5>
                <p class="mb-0">Demo credentials are now hidden from public view and only accessible via a secret URL with an access key.</p>
            </div>
            
            <div class="secret-url">
                <h5><i class="fas fa-link me-2"></i>Secret Demo URL</h5>
                <p class="text-muted">Share this URL to provide demo access:</p>
                <div class="d-flex align-items-center justify-content-center">
                    <code id="secret-url"><?php echo getSetting('site_url', 'http://localhost/smartquiz-hub'); ?>/demo-access.php?key=smartquiz2024demo</code>
                    <button class="copy-btn" onclick="copySecretUrl()">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                <small class="text-muted mt-2 d-block">Access Key: <strong>smartquiz2024demo</strong></small>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="text-center p-3 border rounded">
                        <i class="fas fa-user-shield fa-2x text-primary mb-2"></i>
                        <h6>Admin Account</h6>
                        <small class="text-muted">Full system access</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3 border rounded">
                        <i class="fas fa-chalkboard-teacher fa-2x text-warning mb-2"></i>
                        <h6>Instructor Account</h6>
                        <small class="text-muted">Quiz management</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3 border rounded">
                        <i class="fas fa-user-graduate fa-2x text-success mb-2"></i>
                        <h6>Student Account</h6>
                        <small class="text-muted">Quiz taking</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Recommendations -->
        <div class="security-card">
            <h3><i class="fas fa-shield-alt me-2"></i>Security Recommendations</h3>
            
            <div class="row">
                <div class="col-md-6">
                    <h5>🔒 Production Security</h5>
                    <ul>
                        <li>Change default admin password</li>
                        <li>Remove or restrict setup files</li>
                        <li>Use strong passwords</li>
                        <li>Enable HTTPS</li>
                        <li>Regular security updates</li>
                        <li>Backup database regularly</li>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h5>🎯 Demo Environment</h5>
                    <ul>
                        <li>Use secret URL for demo access</li>
                        <li>Regularly reset demo data</li>
                        <li>Monitor demo usage</li>
                        <li>Set session timeouts</li>
                        <li>Limit demo features if needed</li>
                        <li>Document demo limitations</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- File Security Status -->
        <div class="security-card">
            <h3><i class="fas fa-file-shield me-2"></i>File Security Status</h3>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Purpose</th>
                            <th>Security Status</th>
                            <th>Recommendation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>login.php</code></td>
                            <td>User login</td>
                            <td><span class="status-good"><i class="fas fa-check"></i> Secured</span></td>
                            <td>Demo credentials removed</td>
                        </tr>
                        <tr>
                            <td><code>demo-access.php</code></td>
                            <td>Demo credentials</td>
                            <td><span class="status-good"><i class="fas fa-check"></i> Protected</span></td>
                            <td>Secret key required</td>
                        </tr>
                        <tr>
                            <td><code>setup.php</code></td>
                            <td>Initial setup</td>
                            <td><span class="status-warning"><i class="fas fa-exclamation-triangle"></i> Development</span></td>
                            <td>Remove in production</td>
                        </tr>
                        <tr>
                            <td><code>reset-admin-password.php</code></td>
                            <td>Password reset</td>
                            <td><span class="status-warning"><i class="fas fa-exclamation-triangle"></i> Development</span></td>
                            <td>Remove in production</td>
                        </tr>
                        <tr>
                            <td><code>debug-login.php</code></td>
                            <td>Login debugging</td>
                            <td><span class="status-danger"><i class="fas fa-times"></i> Debug Only</span></td>
                            <td>Remove in production</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="security-card">
            <h3><i class="fas fa-tools me-2"></i>Quick Actions</h3>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <a href="demo-access.php?key=smartquiz2024demo" class="btn btn-primary w-100" target="_blank">
                        <i class="fas fa-key me-2"></i>Test Demo Access
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="admin/settings.php" class="btn btn-outline-primary w-100">
                        <i class="fas fa-cog me-2"></i>Website Settings
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="admin/users.php" class="btn btn-outline-success w-100">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="index.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-home me-2"></i>View Site
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function copySecretUrl() {
            const urlElement = document.getElementById('secret-url');
            const url = urlElement.textContent;
            
            // Create temporary textarea
            const textarea = document.createElement('textarea');
            textarea.value = url;
            document.body.appendChild(textarea);
            
            // Select and copy
            textarea.select();
            document.execCommand('copy');
            
            // Remove temporary element
            document.body.removeChild(textarea);
            
            // Show feedback
            const button = event.target.closest('.copy-btn');
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Copied!';
            button.style.background = '#28a745';
            
            setTimeout(() => {
                button.innerHTML = originalContent;
                button.style.background = '#007bff';
            }, 2000);
        }
    </script>
</body>
</html>
