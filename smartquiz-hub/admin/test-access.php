<?php
/**
 * Admin Access Test Script
 * This will help diagnose admin panel access issues
 */

echo "<h2>Admin Panel Access Test</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";

// Test 1: Check if config is loading
echo "<h3>Test 1: Configuration Loading</h3>";
try {
    require_once '../config/config.php';
    echo "<p style='color: green;'>✓ Config loaded successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Config loading failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Check session
echo "<h3>Test 2: Session Status</h3>";
if (session_status() == PHP_SESSION_ACTIVE) {
    echo "<p style='color: green;'>✓ Session is active</p>";
} else {
    echo "<p style='color: orange;'>⚠ Session not active</p>";
}

// Test 3: Check if user is logged in
echo "<h3>Test 3: Login Status</h3>";
if (isLoggedIn()) {
    echo "<p style='color: green;'>✓ User is logged in</p>";
    echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
    echo "<p>User Role: " . ($_SESSION['user_role'] ?? 'Not set') . "</p>";
    echo "<p>User Name: " . ($_SESSION['user_name'] ?? 'Not set') . "</p>";
} else {
    echo "<p style='color: red;'>❌ User is not logged in</p>";
    echo "<p><a href='../login.php'>Go to Login Page</a></p>";
}

// Test 4: Check admin status
echo "<h3>Test 4: Admin Status</h3>";
if (isLoggedIn()) {
    if (isAdmin()) {
        echo "<p style='color: green;'>✓ User has admin access</p>";
    } else {
        echo "<p style='color: red;'>❌ User does not have admin access</p>";
        echo "<p>Current role: " . ($_SESSION['user_role'] ?? 'Not set') . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ Cannot check admin status - user not logged in</p>";
}

// Test 5: Database connection
echo "<h3>Test 5: Database Connection</h3>";
try {
    $db = getDB();
    if ($db) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
        
        // Test admin user exists
        $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE role IN ('admin', 'instructor') LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<p style='color: green;'>✓ Admin user found in database</p>";
            echo "<p>Admin: {$admin['name']} ({$admin['email']}) - Role: {$admin['role']}</p>";
        } else {
            echo "<p style='color: red;'>❌ No admin user found in database</p>";
            echo "<p><a href='../setup.php'>Run Setup Script</a></p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 6: File permissions
echo "<h3>Test 6: File Access</h3>";
$adminIndexPath = __DIR__ . '/index.php';
if (file_exists($adminIndexPath)) {
    echo "<p style='color: green;'>✓ Admin index.php exists</p>";
    if (is_readable($adminIndexPath)) {
        echo "<p style='color: green;'>✓ Admin index.php is readable</p>";
    } else {
        echo "<p style='color: red;'>❌ Admin index.php is not readable</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Admin index.php does not exist</p>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li><a href='../login.php'>Login Page</a></li>";
echo "<li><a href='../setup.php'>Database Setup</a></li>";
echo "<li><a href='index.php'>Try Admin Panel</a></li>";
echo "<li><a href='../index.php'>Back to Homepage</a></li>";
echo "</ul>";

echo "</div>";
?>
