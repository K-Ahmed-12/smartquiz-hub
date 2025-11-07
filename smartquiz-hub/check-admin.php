<?php
/**
 * Quick Admin Access Checker
 * Use this to verify admin access is working
 */

require_once 'config/config.php';

echo "<h2>SmartQuiz Hub - Admin Access Checker</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";

// Check if user is logged in
if (!isLoggedIn()) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ Not Logged In</h3>";
    echo "<p>You need to login first to access the admin panel.</p>";
    echo "<p><strong>Steps to fix:</strong></p>";
    echo "<ol>";
    echo "<li>Go to the <a href='login.php'>Login Page</a></li>";
    echo "<li>Use admin credentials: <code>admin@smartquizhub.com</code> / <code>admin123</code></li>";
    echo "<li>After login, try accessing admin panel again</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #d1edff; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>✓ Logged In Successfully</h3>";
    echo "<p><strong>User:</strong> " . $_SESSION['user_name'] . "</p>";
    echo "<p><strong>Email:</strong> " . $_SESSION['user_email'] . "</p>";
    echo "<p><strong>Role:</strong> " . $_SESSION['user_role'] . "</p>";
    echo "</div>";
    
    // Check admin status
    if (isAdmin()) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>✅ Admin Access Granted</h3>";
        echo "<p>You have admin privileges! You can now access the admin panel.</p>";
        echo "<p><strong>Admin Panel Links:</strong></p>";
        echo "<ul>";
        echo "<li><a href='admin/' style='color: #155724; font-weight: bold;'>Admin Dashboard</a></li>";
        echo "<li><a href='admin/quizzes.php' style='color: #155724;'>Manage Quizzes</a></li>";
        echo "<li><a href='admin/quiz-create.php' style='color: #155724;'>Create New Quiz</a></li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>⚠ No Admin Access</h3>";
        echo "<p>Your account doesn't have admin privileges.</p>";
        echo "<p><strong>Current Role:</strong> " . $_SESSION['user_role'] . "</p>";
        echo "<p><strong>Required Roles:</strong> admin or instructor</p>";
        echo "<p><strong>To fix this:</strong></p>";
        echo "<ol>";
        echo "<li>Make sure you're using the correct admin account</li>";
        echo "<li>Check if the admin user was created properly</li>";
        echo "<li>Run the <a href='setup.php'>setup script</a> if needed</li>";
        echo "</ol>";
        echo "</div>";
    }
}

// Database check
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🔍 Database Status</h3>";
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'instructor')");
    $stmt->execute();
    $adminCount = $stmt->fetch()['count'];
    
    echo "<p><strong>Admin users in database:</strong> $adminCount</p>";
    
    if ($adminCount == 0) {
        echo "<p style='color: #dc3545;'>❌ No admin users found! Please run the setup script.</p>";
        echo "<p><a href='setup.php' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Run Setup Script</a></p>";
    } else {
        echo "<p style='color: #28a745;'>✓ Admin users found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: #dc3545;'>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and run the setup script.</p>";
}
echo "</div>";

// Quick links
echo "<div style='background: #e9ecef; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🔗 Quick Links</h3>";
echo "<p>";
echo "<a href='login.php' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Login</a>";
echo "<a href='setup.php' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Setup Database</a>";
echo "<a href='admin/test-access.php' style='background: #ffc107; color: black; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Test Admin Access</a>";
echo "<a href='index.php' style='background: #6c757d; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Homepage</a>";
echo "</p>";
echo "</div>";

echo "</div>";
?>
