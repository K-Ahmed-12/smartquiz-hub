<?php
/**
 * Admin Settings Panel - Website Configuration
 */

require_once '../config/config.php';
requireAdmin();

$success = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = getDB();
        
        // Get all POST data
        $settings = [
            'site_name' => sanitizeInput($_POST['site_name']),
            'site_tagline' => sanitizeInput($_POST['site_tagline']),
            'site_description' => sanitizeInput($_POST['site_description']),
            'admin_email' => sanitizeInput($_POST['admin_email']),
            'contact_email' => sanitizeInput($_POST['contact_email']),
            'site_url' => sanitizeInput($_POST['site_url']),
            'timezone' => sanitizeInput($_POST['timezone']),
            'date_format' => sanitizeInput($_POST['date_format']),
            'time_format' => sanitizeInput($_POST['time_format']),
            'items_per_page' => (int)$_POST['items_per_page'],
            'leaderboard_limit' => (int)$_POST['leaderboard_limit'],
            'session_timeout' => (int)$_POST['session_timeout'],
            'allow_registration' => isset($_POST['allow_registration']) ? 1 : 0,
            'require_email_verification' => isset($_POST['require_email_verification']) ? 1 : 0,
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
            'google_analytics_id' => sanitizeInput($_POST['google_analytics_id']),
            'facebook_url' => sanitizeInput($_POST['facebook_url']),
            'twitter_url' => sanitizeInput($_POST['twitter_url']),
            'instagram_url' => sanitizeInput($_POST['instagram_url']),
            'linkedin_url' => sanitizeInput($_POST['linkedin_url']),
            'footer_text' => sanitizeInput($_POST['footer_text']),
            'privacy_policy_url' => sanitizeInput($_POST['privacy_policy_url']),
            'terms_of_service_url' => sanitizeInput($_POST['terms_of_service_url'])
        ];
        
        // Validation
        if (empty($settings['site_name'])) {
            $errors[] = 'Site name is required';
        }
        
        if (!filter_var($settings['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid admin email is required';
        }
        
        if (!filter_var($settings['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid contact email is required';
        }
        
        if ($settings['items_per_page'] < 5 || $settings['items_per_page'] > 100) {
            $errors[] = 'Items per page must be between 5 and 100';
        }
        
        if ($settings['leaderboard_limit'] < 5 || $settings['leaderboard_limit'] > 50) {
            $errors[] = 'Leaderboard limit must be between 5 and 50';
        }
        
        if ($settings['session_timeout'] < 15 || $settings['session_timeout'] > 1440) {
            $errors[] = 'Session timeout must be between 15 and 1440 minutes';
        }
        
        if (empty($errors)) {
            // Create settings table if it doesn't exist
            $db->exec("
                CREATE TABLE IF NOT EXISTS site_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) UNIQUE NOT NULL,
                    setting_value TEXT,
                    setting_type ENUM('string', 'integer', 'boolean', 'text') DEFAULT 'string',
                    description TEXT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            
            // Update or insert each setting
            foreach ($settings as $key => $value) {
                $type = is_int($value) ? 'integer' : (is_bool($value) || in_array($key, ['allow_registration', 'require_email_verification', 'maintenance_mode']) ? 'boolean' : 'string');
                
                $stmt = $db->prepare("
                    INSERT INTO site_settings (setting_key, setting_value, setting_type) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    setting_type = VALUES(setting_type)
                ");
                $stmt->execute([$key, $value, $type]);
            }
            
            $success = 'Settings updated successfully!';
        }
        
    } catch (PDOException $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
}

// Load current settings
try {
    $db = getDB();
    
    // Check if settings table exists
    $stmt = $db->query("SHOW TABLES LIKE 'site_settings'");
    $table_exists = $stmt->fetch();
    
    $current_settings = [];
    if ($table_exists) {
        $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
        while ($row = $stmt->fetch()) {
            $current_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    // Default values
    $defaults = [
        'site_name' => SITE_NAME ?? 'SmartQuiz Hub',
        'site_tagline' => SITE_TAGLINE ?? 'Test Your Knowledge',
        'site_description' => 'An interactive quiz platform for learning and assessment',
        'admin_email' => 'admin@smartquizhub.com',
        'contact_email' => 'contact@smartquizhub.com',
        'site_url' => 'http://localhost/smartquiz-hub',
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s',
        'items_per_page' => 10,
        'leaderboard_limit' => 20,
        'session_timeout' => 60,
        'allow_registration' => 1,
        'require_email_verification' => 0,
        'maintenance_mode' => 0,
        'google_analytics_id' => '',
        'facebook_url' => '',
        'twitter_url' => '',
        'instagram_url' => '',
        'linkedin_url' => '',
        'footer_text' => '© 2024 SmartQuiz Hub. All rights reserved.',
        'privacy_policy_url' => '',
        'terms_of_service_url' => ''
    ];
    
    // Merge with current settings
    $settings = array_merge($defaults, $current_settings);
    
} catch (PDOException $e) {
    $settings = $defaults;
    $errors[] = 'Could not load current settings';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Settings - Admin Panel</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        .settings-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .settings-section h4 {
            color: #007bff;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        
        .form-text {
            font-size: 0.875rem;
        }
        
        .btn-save {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .settings-nav {
            position: sticky;
            top: 20px;
        }
        
        .nav-pills .nav-link {
            border-radius: 8px;
            margin-bottom: 5px;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(45deg, #007bff, #0056b3);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-brain me-2"></i><?php echo $settings['site_name']; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-lg-3">
                <div class="settings-nav">
                    <h5 class="mb-3"><i class="fas fa-cog me-2"></i>Settings</h5>
                    <nav class="nav nav-pills flex-column">
                        <a class="nav-link active" href="#general" data-bs-toggle="pill">
                            <i class="fas fa-globe me-2"></i>General
                        </a>
                        <a class="nav-link" href="#contact" data-bs-toggle="pill">
                            <i class="fas fa-envelope me-2"></i>Contact & Email
                        </a>
                        <a class="nav-link" href="#display" data-bs-toggle="pill">
                            <i class="fas fa-desktop me-2"></i>Display & Format
                        </a>
                        <a class="nav-link" href="#security" data-bs-toggle="pill">
                            <i class="fas fa-shield-alt me-2"></i>Security & Access
                        </a>
                        <a class="nav-link" href="#social" data-bs-toggle="pill">
                            <i class="fas fa-share-alt me-2"></i>Social Media
                        </a>
                        <a class="nav-link" href="#advanced" data-bs-toggle="pill">
                            <i class="fas fa-code me-2"></i>Advanced
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-cog me-3"></i>Website Settings</h2>
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
                
                <!-- Alerts -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Settings Form -->
                <form method="POST" class="needs-validation" novalidate>
                    <div class="tab-content">
                        <!-- General Settings -->
                        <div class="tab-pane fade show active" id="general">
                            <div class="settings-section">
                                <h4><i class="fas fa-globe me-2"></i>General Information</h4>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="site_name" class="form-label">Site Name *</label>
                                        <input type="text" class="form-control" id="site_name" name="site_name" 
                                               value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                                        <div class="form-text">The name of your website</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="site_tagline" class="form-label">Site Tagline</label>
                                        <input type="text" class="form-control" id="site_tagline" name="site_tagline" 
                                               value="<?php echo htmlspecialchars($settings['site_tagline']); ?>">
                                        <div class="form-text">A short description or slogan</div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="site_description" class="form-label">Site Description</label>
                                    <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                                    <div class="form-text">Used for SEO and site description</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="site_url" class="form-label">Site URL</label>
                                    <input type="url" class="form-control" id="site_url" name="site_url" 
                                           value="<?php echo htmlspecialchars($settings['site_url']); ?>">
                                    <div class="form-text">The full URL of your website</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contact & Email Settings -->
                        <div class="tab-pane fade" id="contact">
                            <div class="settings-section">
                                <h4><i class="fas fa-envelope me-2"></i>Contact & Email Settings</h4>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="admin_email" class="form-label">Admin Email *</label>
                                        <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                               value="<?php echo htmlspecialchars($settings['admin_email']); ?>" required>
                                        <div class="form-text">Email for admin notifications</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="contact_email" class="form-label">Contact Email *</label>
                                        <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                               value="<?php echo htmlspecialchars($settings['contact_email']); ?>" required>
                                        <div class="form-text">Public contact email</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Display & Format Settings -->
                        <div class="tab-pane fade" id="display">
                            <div class="settings-section">
                                <h4><i class="fas fa-desktop me-2"></i>Display & Format Settings</h4>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="timezone" class="form-label">Timezone</label>
                                        <select class="form-select" id="timezone" name="timezone">
                                            <option value="UTC" <?php echo $settings['timezone'] == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                            <option value="America/New_York" <?php echo $settings['timezone'] == 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                            <option value="America/Chicago" <?php echo $settings['timezone'] == 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                            <option value="America/Denver" <?php echo $settings['timezone'] == 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                            <option value="America/Los_Angeles" <?php echo $settings['timezone'] == 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                            <option value="Europe/London" <?php echo $settings['timezone'] == 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                            <option value="Europe/Paris" <?php echo $settings['timezone'] == 'Europe/Paris' ? 'selected' : ''; ?>>Paris</option>
                                            <option value="Asia/Tokyo" <?php echo $settings['timezone'] == 'Asia/Tokyo' ? 'selected' : ''; ?>>Tokyo</option>
                                            <option value="Asia/Dhaka" <?php echo $settings['timezone'] == 'Asia/Dhaka' ? 'selected' : ''; ?>>Dhaka</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="date_format" class="form-label">Date Format</label>
                                        <select class="form-select" id="date_format" name="date_format">
                                            <option value="Y-m-d" <?php echo $settings['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>2024-01-15</option>
                                            <option value="m/d/Y" <?php echo $settings['date_format'] == 'm/d/Y' ? 'selected' : ''; ?>>01/15/2024</option>
                                            <option value="d/m/Y" <?php echo $settings['date_format'] == 'd/m/Y' ? 'selected' : ''; ?>>15/01/2024</option>
                                            <option value="F j, Y" <?php echo $settings['date_format'] == 'F j, Y' ? 'selected' : ''; ?>>January 15, 2024</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="time_format" class="form-label">Time Format</label>
                                        <select class="form-select" id="time_format" name="time_format">
                                            <option value="H:i:s" <?php echo $settings['time_format'] == 'H:i:s' ? 'selected' : ''; ?>>24-hour (14:30:00)</option>
                                            <option value="h:i:s A" <?php echo $settings['time_format'] == 'h:i:s A' ? 'selected' : ''; ?>>12-hour (2:30:00 PM)</option>
                                            <option value="H:i" <?php echo $settings['time_format'] == 'H:i' ? 'selected' : ''; ?>>24-hour short (14:30)</option>
                                            <option value="h:i A" <?php echo $settings['time_format'] == 'h:i A' ? 'selected' : ''; ?>>12-hour short (2:30 PM)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="items_per_page" class="form-label">Items Per Page</label>
                                        <input type="number" class="form-control" id="items_per_page" name="items_per_page" 
                                               value="<?php echo $settings['items_per_page']; ?>" min="5" max="100">
                                        <div class="form-text">Number of items to show per page (5-100)</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="leaderboard_limit" class="form-label">Leaderboard Limit</label>
                                        <input type="number" class="form-control" id="leaderboard_limit" name="leaderboard_limit" 
                                               value="<?php echo $settings['leaderboard_limit']; ?>" min="5" max="50">
                                        <div class="form-text">Number of users to show on leaderboard (5-50)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Security & Access Settings -->
                        <div class="tab-pane fade" id="security">
                            <div class="settings-section">
                                <h4><i class="fas fa-shield-alt me-2"></i>Security & Access Settings</h4>
                                
                                <div class="mb-3">
                                    <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                                    <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                           value="<?php echo $settings['session_timeout']; ?>" min="15" max="1440">
                                    <div class="form-text">Auto-logout after inactivity (15-1440 minutes)</div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="allow_registration" name="allow_registration" 
                                                   <?php echo $settings['allow_registration'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="allow_registration">
                                                Allow User Registration
                                            </label>
                                        </div>
                                        <div class="form-text">Allow new users to register</div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="require_email_verification" name="require_email_verification" 
                                                   <?php echo $settings['require_email_verification'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="require_email_verification">
                                                Require Email Verification
                                            </label>
                                        </div>
                                        <div class="form-text">Verify email before account activation</div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                                   <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="maintenance_mode">
                                                Maintenance Mode
                                            </label>
                                        </div>
                                        <div class="form-text">Show maintenance page to users</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Social Media Settings -->
                        <div class="tab-pane fade" id="social">
                            <div class="settings-section">
                                <h4><i class="fas fa-share-alt me-2"></i>Social Media Links</h4>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="facebook_url" class="form-label">Facebook URL</label>
                                        <input type="url" class="form-control" id="facebook_url" name="facebook_url" 
                                               value="<?php echo htmlspecialchars($settings['facebook_url']); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="twitter_url" class="form-label">Twitter URL</label>
                                        <input type="url" class="form-control" id="twitter_url" name="twitter_url" 
                                               value="<?php echo htmlspecialchars($settings['twitter_url']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="instagram_url" class="form-label">Instagram URL</label>
                                        <input type="url" class="form-control" id="instagram_url" name="instagram_url" 
                                               value="<?php echo htmlspecialchars($settings['instagram_url']); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="linkedin_url" class="form-label">LinkedIn URL</label>
                                        <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" 
                                               value="<?php echo htmlspecialchars($settings['linkedin_url']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Advanced Settings -->
                        <div class="tab-pane fade" id="advanced">
                            <div class="settings-section">
                                <h4><i class="fas fa-code me-2"></i>Advanced Settings</h4>
                                
                                <div class="mb-3">
                                    <label for="google_analytics_id" class="form-label">Google Analytics ID</label>
                                    <input type="text" class="form-control" id="google_analytics_id" name="google_analytics_id" 
                                           value="<?php echo htmlspecialchars($settings['google_analytics_id']); ?>" 
                                           placeholder="G-XXXXXXXXXX">
                                    <div class="form-text">Google Analytics tracking ID</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="footer_text" class="form-label">Footer Text</label>
                                    <input type="text" class="form-control" id="footer_text" name="footer_text" 
                                           value="<?php echo htmlspecialchars($settings['footer_text']); ?>">
                                    <div class="form-text">Text to display in the footer</div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="privacy_policy_url" class="form-label">Privacy Policy URL</label>
                                        <input type="url" class="form-control" id="privacy_policy_url" name="privacy_policy_url" 
                                               value="<?php echo htmlspecialchars($settings['privacy_policy_url']); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="terms_of_service_url" class="form-label">Terms of Service URL</label>
                                        <input type="url" class="form-control" id="terms_of_service_url" name="terms_of_service_url" 
                                               value="<?php echo htmlspecialchars($settings['terms_of_service_url']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Save Button -->
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success btn-lg btn-save">
                            <i class="fas fa-save me-2"></i>Save All Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Auto-hide alerts
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
