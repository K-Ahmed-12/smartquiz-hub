<?php
/**
 * Credential Checker for SmartQuiz Hub
 * This script helps debug login issues
 */

require_once 'config/config.php';

echo "<h2>SmartQuiz Hub - Credential Checker</h2>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_email = $_POST['email'];
    $test_password = $_POST['password'];
    
    try {
        $db = getDB();
        
        echo "<h3>Testing Credentials</h3>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($test_email) . "</p>";
        
        // Check if user exists
        $stmt = $db->prepare("SELECT id, name, email, password, role, is_active FROM users WHERE email = ?");
        $stmt->execute([$test_email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
            echo "<h4>❌ User Not Found</h4>";
            echo "<p>No user found with email: <strong>" . htmlspecialchars($test_email) . "</strong></p>";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
            echo "<h4>✅ User Found</h4>";
            echo "<p><strong>Name:</strong> " . htmlspecialchars($user['name']) . "</p>";
            echo "<p><strong>Role:</strong> " . htmlspecialchars($user['role']) . "</p>";
            echo "<p><strong>Active:</strong> " . ($user['is_active'] ? 'Yes' : 'No') . "</p>";
            echo "</div>";
            
            // Check password
            if (password_verify($test_password, $user['password'])) {
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
                echo "<h4>✅ Password Correct</h4>";
                echo "<p>The password is valid for this user.</p>";
                
                if (!$user['is_active']) {
                    echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
                    echo "<p>⚠️ <strong>Account is inactive!</strong> This might be why login is failing.</p>";
                    echo "</div>";
                }
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
                echo "<h4>❌ Password Incorrect</h4>";
                echo "<p>The password does not match for this user.</p>";
                echo "<p><strong>Stored hash:</strong> <code style='font-size: 0.8em;'>" . substr($user['password'], 0, 50) . "...</code></p>";
                echo "</div>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h4>❌ Database Error</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}

// Show all users for reference
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, role, is_active, created_at FROM users ORDER BY role DESC, name");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<h3>All Users in Database</h3>";
        echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0; background: white;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>ID</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Name</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Email</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Role</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Status</th>";
        echo "</tr>";
        
        foreach ($users as $user) {
            $status = $user['is_active'] ? '<span style="color: green;">Active</span>' : '<span style="color: red;">Inactive</span>';
            $role_color = $user['role'] == 'admin' ? 'style="background: #fff3cd;"' : '';
            echo "<tr {$role_color}>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$user['id']}</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'><strong>" . ucfirst($user['role']) . "</strong></td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$status}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
        echo "<h4>⚠️ No Users Found</h4>";
        echo "<p>The users table is empty. You need to create an admin user first.</p>";
        echo "<p><a href='setup-admin.php'>Run Admin Setup</a></p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ Cannot fetch users</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<h3>Test Login Credentials</h3>
<form method="POST" style="background: white; padding: 20px; border-radius: 5px; margin: 20px 0;">
    <div style="margin-bottom: 15px;">
        <label for="email" style="display: block; margin-bottom: 5px;">Email:</label>
        <input type="email" id="email" name="email" required 
               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : 'admin@smartquiz.com'; ?>">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="password" style="display: block; margin-bottom: 5px;">Password:</label>
        <input type="password" id="password" name="password" required 
               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
               value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : 'admin123'; ?>">
    </div>
    
    <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
        Test Credentials
    </button>
</form>

<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
    <h4>Quick Actions</h4>
    <p>
        <a href="setup-admin.php" style="background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin-right: 10px;">Setup Admin</a>
        <a href="login.php" style="background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;">Go to Login</a>
    </p>
</div>

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
table { background: white; }
code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
</style>
