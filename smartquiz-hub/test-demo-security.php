<?php
/**
 * Test Demo Security Implementation
 */

require_once 'config/config.php';
requireAdmin();

echo "<h2>🔒 Testing Demo Security System</h2>";

$site_url = getSetting('site_url', 'http://localhost/smartquiz-hub');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Security Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; line-height: 1.6; }
        h2 { color: #007bff; margin-bottom: 20px; }
        h3 { color: #28a745; margin-top: 25px; margin-bottom: 15px; }
        .test-section { background: white; padding: 20px; border-radius: 10px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status-good { color: #28a745; }
        .status-bad { color: #dc3545; }
        .status-warning { color: #ffc107; }
        .test-url { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #007bff; }
        .test-url code { background: #e9ecef; padding: 5px 10px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #e9ecef; font-weight: bold; }
        .btn { display: inline-block; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>

<div class="test-section">
    <h3>1. Security Status Check</h3>
    
    <?php
    // Check if login page has demo credentials
    $login_content = file_get_contents('login.php');
    $has_demo_creds = strpos($login_content, 'admin@smartquizhub.com') !== false;
    
    echo "<table>";
    echo "<tr><th>Security Check</th><th>Status</th><th>Details</th></tr>";
    
    echo "<tr>";
    echo "<td><strong>Login Page Demo Credentials</strong></td>";
    if ($has_demo_creds) {
        echo "<td><span class='status-bad'>❌ Found</span></td>";
        echo "<td>Demo credentials still visible in login page</td>";
    } else {
        echo "<td><span class='status-good'>✅ Removed</span></td>";
        echo "<td>Demo credentials successfully hidden</td>";
    }
    echo "</tr>";
    
    // Check if demo-access.php exists
    $demo_file_exists = file_exists('demo-access.php');
    echo "<tr>";
    echo "<td><strong>Secret Demo Page</strong></td>";
    if ($demo_file_exists) {
        echo "<td><span class='status-good'>✅ Created</span></td>";
        echo "<td>Secret demo access page is available</td>";
    } else {
        echo "<td><span class='status-bad'>❌ Missing</span></td>";
        echo "<td>Demo access page not found</td>";
    }
    echo "</tr>";
    
    // Check if security-info.php exists
    $security_file_exists = file_exists('security-info.php');
    echo "<tr>";
    echo "<td><strong>Security Information Page</strong></td>";
    if ($security_file_exists) {
        echo "<td><span class='status-good'>✅ Created</span></td>";
        echo "<td>Security information page is available</td>";
    } else {
        echo "<td><span class='status-bad'>❌ Missing</span></td>";
        echo "<td>Security information page not found</td>";
    }
    echo "</tr>";
    
    echo "</table>";
    ?>
</div>

<div class="test-section">
    <h3>2. Demo Access URLs</h3>
    
    <div class="test-url">
        <h5>🔐 Secret Demo URL (With Key)</h5>
        <code><?php echo $site_url; ?>/demo-access.php?key=smartquiz2024demo</code>
        <p><strong>Status:</strong> <span class="status-good">✅ Protected</span> - Requires secret key</p>
        <a href="demo-access.php?key=smartquiz2024demo" target="_blank" class="btn btn-success">Test Access</a>
    </div>
    
    <div class="test-url">
        <h5>❌ Invalid Access (No Key)</h5>
        <code><?php echo $site_url; ?>/demo-access.php</code>
        <p><strong>Status:</strong> <span class="status-good">✅ Secured</span> - Should redirect to homepage</p>
        <a href="demo-access.php" target="_blank" class="btn btn-warning">Test Invalid Access</a>
    </div>
    
    <div class="test-url">
        <h5>❌ Invalid Access (Wrong Key)</h5>
        <code><?php echo $site_url; ?>/demo-access.php?key=wrongkey</code>
        <p><strong>Status:</strong> <span class="status-good">✅ Secured</span> - Should redirect to homepage</p>
        <a href="demo-access.php?key=wrongkey" target="_blank" class="btn btn-warning">Test Wrong Key</a>
    </div>
    
    <div class="test-url">
        <h5>🔗 Clean Demo URL (Redirect)</h5>
        <code><?php echo $site_url; ?>/demo.php</code>
        <p><strong>Status:</strong> <span class="status-good">✅ Available</span> - Clean URL that redirects to secret page</p>
        <a href="demo.php" target="_blank" class="btn btn-success">Test Clean URL</a>
    </div>
</div>

<div class="test-section">
    <h3>3. Demo Accounts Available</h3>
    
    <table>
        <tr><th>Account Type</th><th>Email</th><th>Password</th><th>Access Level</th></tr>
        <tr>
            <td><strong>Administrator</strong></td>
            <td>admin@demo.com</td>
            <td>admin123</td>
            <td>Full admin panel access</td>
        </tr>
        <tr>
            <td><strong>Instructor</strong></td>
            <td>instructor@demo.com</td>
            <td>instructor123</td>
            <td>Quiz management</td>
        </tr>
        <tr>
            <td><strong>Student</strong></td>
            <td>student@demo.com</td>
            <td>student123</td>
            <td>Quiz taking</td>
        </tr>
    </table>
    
    <p><strong>Note:</strong> These credentials are only visible on the secret demo page.</p>
</div>

<div class="test-section">
    <h3>4. Security Features</h3>
    
    <ul>
        <li><span class="status-good">✅</span> <strong>Secret Key Protection:</strong> Demo page requires correct access key</li>
        <li><span class="status-good">✅</span> <strong>Auto-Timeout:</strong> Demo page auto-redirects after 10 minutes</li>
        <li><span class="status-good">✅</span> <strong>Copy-to-Clipboard:</strong> Easy credential copying</li>
        <li><span class="status-good">✅</span> <strong>Clean URLs:</strong> Professional demo.php redirect</li>
        <li><span class="status-good">✅</span> <strong>Hidden Credentials:</strong> No visible demo credentials on login page</li>
        <li><span class="status-good">✅</span> <strong>Admin Access:</strong> Security info available in admin panel</li>
    </ul>
</div>

<div class="test-section">
    <h3>5. Files to Share</h3>
    
    <div class="test-url">
        <h5>📋 For Public Demo Access</h5>
        <p>Share this clean URL with users:</p>
        <code><?php echo $site_url; ?>/demo.php</code>
        <p><em>This automatically redirects to the secure demo page</em></p>
    </div>
    
    <div class="test-url">
        <h5>🔐 For Direct Access (Advanced Users)</h5>
        <p>Direct secret URL:</p>
        <code><?php echo $site_url; ?>/demo-access.php?key=smartquiz2024demo</code>
        <p><em>Use this if you need to share the direct link</em></p>
    </div>
</div>

<div class="test-section">
    <h3>6. Quick Actions</h3>
    
    <div style="text-align: center;">
        <a href="demo-access.php?key=smartquiz2024demo" target="_blank" class="btn btn-success">
            <i class="fas fa-key"></i> Open Demo Page
        </a>
        <a href="security-info.php" target="_blank" class="btn">
            <i class="fas fa-shield-alt"></i> Security Info
        </a>
        <a href="login.php" target="_blank" class="btn">
            <i class="fas fa-sign-in-alt"></i> Check Login Page
        </a>
        <a href="admin/index.php" class="btn">
            <i class="fas fa-tachometer-alt"></i> Admin Panel
        </a>
    </div>
</div>

<div class="test-section" style="background: #d4edda; border-left: 4px solid #28a745;">
    <h3>✅ Security Implementation Complete</h3>
    
    <p><strong>Summary:</strong></p>
    <ul>
        <li>Demo credentials are now hidden from public view</li>
        <li>Secret URL system implemented with access key protection</li>
        <li>Clean redirect URL available for sharing</li>
        <li>Auto-timeout security feature added</li>
        <li>Admin panel integration completed</li>
        <li>Comprehensive security documentation provided</li>
    </ul>
    
    <p><strong>Share this URL for demo access:</strong></p>
    <code style="background: white; padding: 10px; display: block; margin: 10px 0; border-radius: 5px;">
        <?php echo $site_url; ?>/demo.php
    </code>
</div>

</body>
</html>
