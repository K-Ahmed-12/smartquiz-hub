<?php
/**
 * Database Setup Script for SmartQuiz Hub
 * Run this file to set up or update the database and tables
 */

// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server (without specifying database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>SmartQuiz Hub Database Setup</h2>";
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";
    
    echo "<p><strong>Setting up database and tables...</strong></p>";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS smartquiz_hub");
    echo "<p>✓ Database: <strong>smartquiz_hub</strong> ready</p>";
    
    // Use the database
    $pdo->exec("USE smartquiz_hub");
    echo "<p>✓ Using database: <strong>smartquiz_hub</strong></p>";
    
    // Create tables with IF NOT EXISTS
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin', 'instructor') DEFAULT 'user',
            profile_image VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            email_verified BOOLEAN DEFAULT FALSE,
            reset_token VARCHAR(255) DEFAULT NULL,
            reset_token_expires DATETIME DEFAULT NULL
        )",
        
        'categories' => "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            icon VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'quizzes' => "CREATE TABLE IF NOT EXISTS quizzes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            category_id INT,
            difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
            time_limit INT DEFAULT 30,
            total_marks INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            allow_retake BOOLEAN DEFAULT TRUE,
            randomize_questions BOOLEAN DEFAULT FALSE,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        'questions' => "CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quiz_id INT NOT NULL,
            question_text TEXT NOT NULL,
            question_type ENUM('multiple_choice', 'true_false', 'short_answer', 'fill_in_blank') NOT NULL,
            question_image VARCHAR(255) DEFAULT NULL,
            marks INT DEFAULT 1,
            order_number INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
        )",
        
        'question_options' => "CREATE TABLE IF NOT EXISTS question_options (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question_id INT NOT NULL,
            option_text TEXT NOT NULL,
            is_correct BOOLEAN DEFAULT FALSE,
            option_order INT DEFAULT 0,
            FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
        )",
        
        'quiz_attempts' => "CREATE TABLE IF NOT EXISTS quiz_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            quiz_id INT NOT NULL,
            start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            end_time TIMESTAMP NULL,
            score DECIMAL(5,2) DEFAULT 0,
            total_marks INT DEFAULT 0,
            percentage DECIMAL(5,2) DEFAULT 0,
            status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
            time_taken INT DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
        )",
        
        'user_answers' => "CREATE TABLE IF NOT EXISTS user_answers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            attempt_id INT NOT NULL,
            question_id INT NOT NULL,
            answer_text TEXT,
            selected_option_id INT DEFAULT NULL,
            is_correct BOOLEAN DEFAULT FALSE,
            marks_awarded DECIMAL(4,2) DEFAULT 0,
            answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
            FOREIGN KEY (selected_option_id) REFERENCES question_options(id) ON DELETE SET NULL
        )",
        
        'settings' => "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        'contact_messages' => "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            subject VARCHAR(200),
            message TEXT NOT NULL,
            status ENUM('new', 'read', 'replied') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    // Create all tables
    foreach ($tables as $tableName => $sql) {
        try {
            $pdo->exec($sql);
            echo "<p>✓ Table: <strong>$tableName</strong> ready</p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠ Table $tableName: " . $e->getMessage() . "</p>";
        }
    }
    
    // Insert default data with IGNORE to prevent duplicates
    echo "<p><strong>Setting up default data...</strong></p>";
    
    // Insert categories
    $categories = [
        ['General Knowledge', 'Test your general knowledge across various topics', 'fas fa-globe'],
        ['Programming', 'Programming languages and computer science concepts', 'fas fa-code'],
        ['Mathematics', 'Mathematical concepts and problem solving', 'fas fa-calculator'],
        ['Science', 'Physics, Chemistry, Biology and other sciences', 'fas fa-flask'],
        ['History', 'World history and historical events', 'fas fa-landmark'],
        ['Literature', 'Books, authors and literary works', 'fas fa-book'],
        ['Sports', 'Sports knowledge and trivia', 'fas fa-football-ball'],
        ['Technology', 'Latest technology trends and innovations', 'fas fa-microchip']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, description, icon) VALUES (?, ?, ?)");
    $categoryCount = 0;
    foreach ($categories as $category) {
        if ($stmt->execute($category)) {
            $categoryCount++;
        }
    }
    echo "<p>✓ Categories: <strong>$categoryCount</strong> categories ready</p>";
    
    // Insert admin user
    $adminExists = $pdo->query("SELECT COUNT(*) FROM users WHERE email = 'admin@smartquizhub.com'")->fetchColumn();
    if (!$adminExists) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, email_verified) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Admin User', 'admin@smartquizhub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1]);
        echo "<p>✓ Admin user: <strong>created</strong></p>";
    } else {
        echo "<p>✓ Admin user: <strong>already exists</strong></p>";
    }
    
    // Insert settings
    $settings = [
        ['site_name', 'SmartQuiz Hub', 'Website name'],
        ['site_description', 'Practice. Learn. Improve.', 'Website tagline'],
        ['default_quiz_time', '30', 'Default quiz time limit in minutes'],
        ['allow_retakes', '1', 'Allow users to retake quizzes'],
        ['email_verification', '1', 'Require email verification for new users'],
        ['leaderboard_enabled', '1', 'Enable leaderboard feature'],
        ['dark_mode_enabled', '1', 'Enable dark mode toggle']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
    $settingCount = 0;
    foreach ($settings as $setting) {
        if ($stmt->execute($setting)) {
            $settingCount++;
        }
    }
    echo "<p>✓ Settings: <strong>$settingCount</strong> settings configured</p>";
    
    echo "<hr>";
    echo "<h3>✅ Database setup completed successfully!</h3>";
    echo "<p><strong>Default Admin Credentials:</strong></p>";
    echo "<ul>";
    echo "<li>Email: <strong>admin@smartquizhub.com</strong></li>";
    echo "<li>Password: <strong>admin123</strong></li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Visit <a href='http://localhost/smartquiz-hub' target='_blank'>http://localhost/smartquiz-hub</a> to access the application</li>";
    echo "<li>Login with the admin credentials above</li>";
    echo "<li>Start creating quizzes and managing the system</li>";
    echo "</ol>";
    
    echo "<p style='margin-top: 30px;'>";
    echo "<a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to SmartQuiz Hub →</a>";
    echo "</p>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";
    echo "<h2 style='color: red;'>❌ Setup Failed</h2>";
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
