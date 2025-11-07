<?php
/**
 * Database Reset Script for SmartQuiz Hub
 * WARNING: This will delete all data and recreate the database
 */

// Security check - only allow in development
if ($_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1') {
    die('This script can only be run on localhost for security reasons.');
}

// Confirmation check
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";
    echo "<h2 style='color: red;'>⚠️ Database Reset Confirmation</h2>";
    echo "<p><strong>WARNING:</strong> This will completely delete the SmartQuiz Hub database and all data!</p>";
    echo "<p>This action cannot be undone. All quizzes, users, and results will be lost.</p>";
    echo "<p>Are you sure you want to proceed?</p>";
    echo "<p style='margin-top: 30px;'>";
    echo "<a href='?confirm=yes' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Yes, Reset Database</a>";
    echo "<a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Cancel</a>";
    echo "</p>";
    echo "</div>";
    exit;
}

// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>SmartQuiz Hub Database Reset</h2>";
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";
    
    // Drop the database if it exists
    echo "<p><strong>Dropping existing database...</strong></p>";
    $pdo->exec("DROP DATABASE IF EXISTS smartquiz_hub");
    echo "<p>✓ Database <strong>smartquiz_hub</strong> dropped</p>";
    
    echo "<hr>";
    echo "<h3>✅ Database reset completed!</h3>";
    echo "<p>The database has been completely removed. You can now run the setup script to create a fresh database.</p>";
    
    echo "<p style='margin-top: 30px;'>";
    echo "<a href='setup.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Run Setup Script</a>";
    echo "<a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Homepage</a>";
    echo "</p>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";
    echo "<h2 style='color: red;'>❌ Reset Failed</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Please ensure:</strong></p>";
    echo "<ul>";
    echo "<li>XAMPP is running</li>";
    echo "<li>MySQL service is started</li>";
    echo "<li>Database credentials are correct</li>";
    echo "</ul>";
    echo "</div>";
}
?>
