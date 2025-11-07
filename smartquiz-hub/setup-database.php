<?php
/**
 * Database Setup Script for SmartQuiz Hub
 * This script creates the admin user and sample data
 */

require_once 'config/config.php';

echo "<h2>SmartQuiz Hub - Database Setup</h2>";

try {
    $db = getDB();
    
    echo "<h3>Step 1: Creating Admin User</h3>";
    
    // Check if admin already exists
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE email = 'admin@smartquiz.com'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "<p>✅ Admin user already exists!</p>";
    } else {
        // Create admin user
        $admin_data = [
            'name' => 'Administrator',
            'email' => 'admin@smartquiz.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'is_active' => 1,
            'email_verified' => 1
        ];
        
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, role, is_active, email_verified, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $admin_data['name'],
            $admin_data['email'], 
            $admin_data['password'],
            $admin_data['role'],
            $admin_data['is_active'],
            $admin_data['email_verified']
        ]);
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>✅ Admin User Created Successfully!</h4>";
        echo "<p><strong>Email:</strong> admin@smartquiz.com</p>";
        echo "<p><strong>Password:</strong> admin123</p>";
        echo "<p><strong>Role:</strong> Admin</p>";
        echo "</div>";
    }
    
    echo "<h3>Step 2: Creating Sample Categories</h3>";
    
    // Check if categories exist
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM categories");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "<p>✅ Categories already exist ({$result['count']} found)</p>";
    } else {
        $categories = [
            ['name' => 'General Knowledge', 'description' => 'Test your general knowledge across various topics', 'icon' => 'fas fa-globe'],
            ['name' => 'Science', 'description' => 'Physics, Chemistry, Biology and more', 'icon' => 'fas fa-flask'],
            ['name' => 'Technology', 'description' => 'Programming, IT, and modern technology', 'icon' => 'fas fa-laptop-code'],
            ['name' => 'History', 'description' => 'World history and historical events', 'icon' => 'fas fa-landmark'],
            ['name' => 'Mathematics', 'description' => 'Math problems and calculations', 'icon' => 'fas fa-calculator']
        ];
        
        $stmt = $db->prepare("INSERT INTO categories (name, description, icon, created_at) VALUES (?, ?, ?, NOW())");
        
        foreach ($categories as $category) {
            $stmt->execute([$category['name'], $category['description'], $category['icon']]);
        }
        
        echo "<p>✅ Created " . count($categories) . " sample categories</p>";
    }
    
    echo "<h3>Step 3: Creating Sample Test User</h3>";
    
    // Check if test user exists
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE email = 'user@test.com'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "<p>✅ Test user already exists!</p>";
    } else {
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, role, is_active, email_verified, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            'Test User',
            'user@test.com',
            password_hash('user123', PASSWORD_DEFAULT),
            'user',
            1,
            1
        ]);
        
        echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>✅ Test User Created!</h4>";
        echo "<p><strong>Email:</strong> user@test.com</p>";
        echo "<p><strong>Password:</strong> user123</p>";
        echo "<p><strong>Role:</strong> User</p>";
        echo "</div>";
    }
    
    echo "<h3>Step 4: Database Status Summary</h3>";
    
    // Get counts of all main tables
    $tables = ['users', 'categories', 'quizzes', 'questions', 'quiz_attempts'];
    
    echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0; background: white;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Table</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Records</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Status</th>";
    echo "</tr>";
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$table}");
            $stmt->execute();
            $result = $stmt->fetch();
            $count = $result['count'];
            $status = $count > 0 ? '<span style="color: green;">✅ Ready</span>' : '<span style="color: orange;">⚠️ Empty</span>';
            
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'><strong>{$table}</strong></td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$count}</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$status}</td>";
            echo "</tr>";
        } catch (PDOException $e) {
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'><strong>{$table}</strong></td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>-</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'><span style='color: red;'>❌ Error</span></td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    echo "<h3>✅ Setup Complete!</h3>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🎉 Your SmartQuiz Hub is Ready!</h4>";
    echo "<p><strong>Admin Login:</strong></p>";
    echo "<ul>";
    echo "<li>Email: <code>admin@smartquiz.com</code></li>";
    echo "<li>Password: <code>admin123</code></li>";
    echo "</ul>";
    echo "<p><strong>Test User Login:</strong></p>";
    echo "<ul>";
    echo "<li>Email: <code>user@test.com</code></li>";
    echo "<li>Password: <code>user123</code></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>⚠️ Important Security Notes:</h4>";
    echo "<ul>";
    echo "<li>Change the default admin password after first login</li>";
    echo "<li>Delete this setup file after use for security</li>";
    echo "<li>The test user is for demonstration purposes only</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p style='margin-top: 30px;'>";
    echo "<a href='login.php' style='background: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Login Now</a>";
    echo "<a href='index.php' style='background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px;'>Go to Homepage</a>";
    echo "</p>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Database Error!</h4>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    echo "<h5>Troubleshooting Steps:</h5>";
    echo "<ol>";
    echo "<li><strong>Check XAMPP:</strong> Make sure MySQL service is running in XAMPP Control Panel</li>";
    echo "<li><strong>Check Database:</strong> Open phpMyAdmin (http://localhost/phpmyadmin) and verify 'smartquiz_hub' database exists</li>";
    echo "<li><strong>Import Schema:</strong> If database is empty, import the schema.sql file from your project</li>";
    echo "<li><strong>Check Config:</strong> Verify database settings in config/database.php</li>";
    echo "</ol>";
    
    echo "<p><strong>Database Config Check:</strong></p>";
    echo "<ul>";
    echo "<li>Host: localhost</li>";
    echo "<li>Database: smartquiz_hub</li>";
    echo "<li>Username: root</li>";
    echo "<li>Password: (empty)</li>";
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
h2 { color: #007bff; margin-bottom: 20px; }
h3 { color: #28a745; margin-top: 30px; margin-bottom: 15px; }
h4 { margin-bottom: 10px; }
p { margin: 10px 0; }
ul, ol { margin: 10px 0 20px 20px; }
table { background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
code { 
    background: #f8f9fa; 
    padding: 2px 6px; 
    border-radius: 3px; 
    font-family: 'Courier New', monospace;
    color: #e83e8c;
}
a { 
    display: inline-block;
    transition: all 0.3s ease;
}
a:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
</style>
