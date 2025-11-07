<?php
/**
 * Login Debug Script
 * This will help diagnose login issues
 */

require_once 'config/config.php';

echo "<h2>Login Debug - SmartQuiz Hub</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";

try {
    $db = getDB();
    
    // Check if admin user exists
    echo "<h3>1. Checking Admin User in Database</h3>";
    $stmt = $db->prepare("SELECT id, name, email, password, role, is_active, email_verified FROM users WHERE email = ?");
    $stmt->execute(['admin@smartquizhub.com']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p>✅ Admin user found in database!</p>";
        echo "<p><strong>ID:</strong> {$admin['id']}</p>";
        echo "<p><strong>Name:</strong> {$admin['name']}</p>";
        echo "<p><strong>Email:</strong> {$admin['email']}</p>";
        echo "<p><strong>Role:</strong> {$admin['role']}</p>";
        echo "<p><strong>Active:</strong> " . ($admin['is_active'] ? 'Yes' : 'No') . "</p>";
        echo "<p><strong>Email Verified:</strong> " . ($admin['email_verified'] ? 'Yes' : 'No') . "</p>";
        echo "<p><strong>Password Hash:</strong> " . substr($admin['password'], 0, 20) . "...</p>";
        echo "</div>";
        
        // Test password verification
        echo "<h3>2. Testing Password Verification</h3>";
        $testPassword = 'admin123';
        if (password_verify($testPassword, $admin['password'])) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p>✅ Password verification successful!</p>";
            echo "<p>The password 'admin123' matches the stored hash.</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p>❌ Password verification failed!</p>";
            echo "<p>The password 'admin123' does not match the stored hash.</p>";
            echo "<p><strong>This means the password hash is incorrect.</strong></p>";
            echo "</div>";
            
            // Generate correct password hash
            echo "<h3>3. Generating Correct Password Hash</h3>";
            $correctHash = password_hash('admin123', PASSWORD_DEFAULT);
            echo "<p><strong>Correct hash for 'admin123':</strong></p>";
            echo "<code style='background: #f8f9fa; padding: 10px; display: block; margin: 10px 0;'>$correctHash</code>";
            
            // Update the password
            echo "<h3>4. Fixing Password Hash</h3>";
            $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
            if ($updateStmt->execute([$correctHash, 'admin@smartquizhub.com'])) {
                echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<p>✅ Password hash updated successfully!</p>";
                echo "<p>You can now login with: admin@smartquizhub.com / admin123</p>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<p>❌ Failed to update password hash</p>";
                echo "</div>";
            }
        }
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p>❌ Admin user not found in database!</p>";
        echo "<p>The email 'admin@smartquizhub.com' does not exist in the users table.</p>";
        echo "</div>";
        
        // Create admin user
        echo "<h3>2. Creating Admin User</h3>";
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $createStmt = $db->prepare("INSERT INTO users (name, email, password, role, is_active, email_verified) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($createStmt->execute(['Admin User', 'admin@smartquizhub.com', $adminPassword, 'admin', 1, 1])) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p>✅ Admin user created successfully!</p>";
            echo "<p><strong>Email:</strong> admin@smartquizhub.com</p>";
            echo "<p><strong>Password:</strong> admin123</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p>❌ Failed to create admin user</p>";
            echo "</div>";
        }
    }
    
    // Check all users
    echo "<h3>3. All Users in Database</h3>";
    $allUsersStmt = $db->query("SELECT id, name, email, role, is_active FROM users ORDER BY id");
    $allUsers = $allUsersStmt->fetchAll();
    
    if ($allUsers) {
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>ID</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Name</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Email</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Role</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Active</th>";
        echo "</tr>";
        
        foreach ($allUsers as $user) {
            echo "<tr>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$user['id']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$user['name']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$user['email']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'><strong>{$user['role']}</strong></td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found in database.</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Please make sure:</p>";
    echo "<ul>";
    echo "<li>XAMPP MySQL service is running</li>";
    echo "<li>Database 'smartquiz_hub' exists</li>";
    echo "<li>Run setup.php to create the database</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Next Steps</h3>";
echo "<div style='background: #e9ecef; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>After running this script:</strong></p>";
echo "<ol>";
echo "<li>Try logging in again with: <strong>admin@smartquizhub.com</strong> / <strong>admin123</strong></li>";
echo "<li>If it still doesn't work, run the <a href='setup.php'>setup script</a></li>";
echo "<li>Go to the <a href='login.php'>login page</a> to test</li>";
echo "</ol>";
echo "</div>";

echo "<p style='margin-top: 20px;'>";
echo "<a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Try Login Again</a>";
echo "<a href='setup.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Run Setup</a>";
echo "<a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Homepage</a>";
echo "</p>";

echo "</div>";
?>
