<?php
/**
 * Admin Setup Script for SmartQuiz Hub
 * This script checks for admin users and creates one if needed
 */

require_once 'config/config.php';

echo "<h2>SmartQuiz Hub - Admin Setup</h2>";

try {
    $db = getDB();
    
    // Check if any admin users exist
    $stmt = $db->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "<h3>Current Admin Status</h3>";
    echo "<p>Admin users found: <strong>{$result['admin_count']}</strong></p>";
    
    if ($result['admin_count'] == 0) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>⚠️ No Admin Users Found!</h4>";
        echo "<p>Creating default admin account...</p>";
        echo "</div>";
        
        // Create default admin user
        $admin_email = 'admin@smartquiz.com';
        $admin_password = 'admin123';
        $admin_name = 'Administrator';
        
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, role, is_active, email_verified) 
            VALUES (?, ?, ?, 'admin', 1, 1)
        ");
        $stmt->execute([$admin_name, $admin_email, $hashed_password]);
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>✅ Default Admin Created Successfully!</h4>";
        echo "<p><strong>Email:</strong> {$admin_email}</p>";
        echo "<p><strong>Password:</strong> {$admin_password}</p>";
        echo "<p><strong>Note:</strong> Please change this password after first login!</p>";
        echo "</div>";
    } else {
        echo "<h3>Existing Admin Users</h3>";
        
        // Show existing admin users
        $stmt = $db->prepare("SELECT id, name, email, is_active, created_at FROM users WHERE role = 'admin'");
        $stmt->execute();
        $admins = $stmt->fetchAll();
        
        echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>ID</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Name</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Email</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Status</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Created</th>";
        echo "</tr>";
        
        foreach ($admins as $admin) {
            $status = $admin['is_active'] ? '<span style="color: green;">Active</span>' : '<span style="color: red;">Inactive</span>';
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$admin['id']}</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($admin['name']) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($admin['email']) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$status}</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . date('Y-m-d H:i', strtotime($admin['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Test Database Connection</h3>";
    echo "<p>✅ Database connection successful!</p>";
    echo "<p>Database: <strong>smartquiz_hub</strong></p>";
    
    // Check if tables exist
    $stmt = $db->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll();
    
    echo "<p>Tables found: <strong>" . count($tables) . "</strong></p>";
    
    if (count($tables) == 0) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>❌ No Tables Found!</h4>";
        echo "<p>Please import the database schema first:</p>";
        echo "<ol>";
        echo "<li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>";
        echo "<li>Create database 'smartquiz_hub' if it doesn't exist</li>";
        echo "<li>Import the schema.sql file from your project</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    echo "<h3>Login Instructions</h3>";
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<p>To login as admin:</p>";
    echo "<ol>";
    echo "<li>Go to: <a href='login.php'>http://localhost/smartquiz-hub/login.php</a></li>";
    echo "<li>Use the admin credentials shown above</li>";
    echo "<li>After login, you'll be redirected to the dashboard</li>";
    echo "<li>Access admin panel from the user dropdown menu</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Database Error!</h4>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h5>Common Solutions:</h5>";
    echo "<ul>";
    echo "<li>Make sure XAMPP MySQL service is running</li>";
    echo "<li>Check if database 'smartquiz_hub' exists in phpMyAdmin</li>";
    echo "<li>Verify database credentials in config/database.php</li>";
    echo "<li>Import the database schema if tables don't exist</li>";
    echo "</ul>";
    echo "</div>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    background: #f8f9fa; 
    line-height: 1.6;
}
h2 { color: #007bff; }
h3 { color: #28a745; margin-top: 30px; }
h4 { margin-bottom: 10px; }
p { margin: 10px 0; }
ul, ol { margin: 10px 0 20px 20px; }
table { background: white; }
a { color: #007bff; }
</style>
