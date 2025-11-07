<?php
/**
 * Reset Admin Password Script
 * Use this to reset the admin password if you're having login issues
 */

require_once 'config/config.php';

echo "<h2>Reset Admin Password - SmartQuiz Hub</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 20px;'>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    try {
        $db = getDB();
        
        // Generate new password hash
        $newPassword = 'admin123';
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Check if admin user exists
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute(['admin@smartquizhub.com']);
        $adminExists = $checkStmt->fetch();
        
        if ($adminExists) {
            // Update existing admin password
            $updateStmt = $db->prepare("UPDATE users SET password = ?, is_active = 1, email_verified = 1 WHERE email = ?");
            $success = $updateStmt->execute([$passwordHash, 'admin@smartquizhub.com']);
            
            if ($success) {
                echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
                echo "<h3>✅ Admin Password Reset Successfully!</h3>";
                echo "<p><strong>Email:</strong> admin@smartquizhub.com</p>";
                echo "<p><strong>New Password:</strong> admin123</p>";
                echo "<p>You can now login with these credentials.</p>";
                echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px;'>";
                echo "<h3>❌ Failed to Update Password</h3>";
                echo "<p>There was an error updating the admin password.</p>";
                echo "</div>";
            }
        } else {
            // Create new admin user
            $createStmt = $db->prepare("INSERT INTO users (name, email, password, role, is_active, email_verified) VALUES (?, ?, ?, ?, ?, ?)");
            $success = $createStmt->execute(['Admin User', 'admin@smartquizhub.com', $passwordHash, 'admin', 1, 1]);
            
            if ($success) {
                echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
                echo "<h3>✅ Admin User Created Successfully!</h3>";
                echo "<p><strong>Email:</strong> admin@smartquizhub.com</p>";
                echo "<p><strong>Password:</strong> admin123</p>";
                echo "<p>The admin user has been created with these credentials.</p>";
                echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px;'>";
                echo "<h3>❌ Failed to Create Admin User</h3>";
                echo "<p>There was an error creating the admin user.</p>";
                echo "</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px;'>";
        echo "<h3>❌ Database Error</h3>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
        echo "<p>Please make sure:</p>";
        echo "<ul>";
        echo "<li>XAMPP MySQL service is running</li>";
        echo "<li>Database exists (run setup.php first)</li>";
        echo "<li>Database connection is working</li>";
        echo "</ul>";
        echo "</div>";
    }
} else {
    // Show confirmation form
    echo "<div style='background: #fff3cd; color: #856404; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ Admin Password Reset</h3>";
    echo "<p>This will reset the admin password to the default: <strong>admin123</strong></p>";
    echo "<p>If the admin user doesn't exist, it will be created.</p>";
    echo "</div>";
    
    echo "<form method='POST' style='margin: 20px 0;'>";
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>";
    echo "<h4>Confirm Password Reset</h4>";
    echo "<p>Are you sure you want to reset/create the admin password?</p>";
    echo "<label style='display: block; margin: 10px 0;'>";
    echo "<input type='checkbox' name='confirm_reset' value='1' required> ";
    echo "Yes, I want to reset the admin password to 'admin123'";
    echo "</label>";
    echo "<button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px;'>Reset Admin Password</button>";
    echo "</div>";
    echo "</form>";
    
    echo "<div style='background: #e9ecef; padding: 15px; border-radius: 5px;'>";
    echo "<h4>Alternative Solutions</h4>";
    echo "<p>If you're still having issues, try these:</p>";
    echo "<ul>";
    echo "<li><a href='setup.php'>Run the complete database setup</a></li>";
    echo "<li><a href='debug-login.php'>Run login diagnostics</a></li>";
    echo "<li><a href='check-admin.php'>Check admin access status</a></li>";
    echo "</ul>";
    echo "</div>";
}

echo "</div>";
?>
